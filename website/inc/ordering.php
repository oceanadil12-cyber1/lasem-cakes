<?php
/**
 * Online ordering rules shared by the order page, api/order.php and the admin panel.
 * Everything comes from data/menu.json, which Menu & prices and Store settings edit:
 *
 *   ordering        false = online ordering switched off (the page says so and orders are refused)
 *   closedMessage   what the order page says while it's off
 *   closedDates     ["2026-12-25", ...] days with no pickups
 *   product hidden  not on the order page at all
 *   product soldOut shown, but can't be added
 */

function menu_data()
{
    static $menu = null;
    if ($menu === null) {
        $menu = json_decode((string) file_get_contents(__DIR__ . '/../data/menu.json'), true) ?: ['categories' => []];
    }
    return $menu;
}

function menu_ordering_open($menu = null)
{
    $menu = $menu ?: menu_data();
    return ($menu['ordering'] ?? true) !== false;
}

function menu_closed_message($menu = null)
{
    $menu = $menu ?: menu_data();
    $msg = trim((string) ($menu['closedMessage'] ?? ''));
    return $msg !== '' ? $msg : 'Online ordering is closed right now. Please call us to place an order.';
}

/** Days with no pickups, as Y-m-d strings (today and later only). */
function menu_closed_dates($menu = null)
{
    $menu = $menu ?: menu_data();
    $today = date('Y-m-d');
    return array_values(array_filter((array) ($menu['closedDates'] ?? []), function ($d) use ($today) {
        return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && $d >= $today;
    }));
}

/** The menu as customers see it: hidden items and empty categories left out. */
function menu_for_customers($menu = null)
{
    $menu = $menu ?: menu_data();
    $cats = [];
    foreach ($menu['categories'] as $c) {
        if (!empty($c['hidden'])) continue;
        $c['products'] = array_values(array_filter($c['products'], function ($p) {
            return empty($p['hidden']) && !empty($p['variants']);
        }));
        if ($c['products']) $cats[] = $c;
    }
    $menu['categories'] = $cats;
    $menu['closedDates'] = menu_closed_dates($menu);
    $menu['delivery'] = menu_delivery($menu);
    return $menu;
}

/**
 * The next number from a daily counter in $dir (a lock stops two orders sharing one).
 *   'counter' → order numbers LS-241024-001 (pickup and table orders share them)
 *   'queue'   → table-order priority numbers 1, 2, 3… (start again each day)
 * Returns '' if the counter can't be used.
 */
function order_next_number($dir, $kind)
{
    $h = @fopen($dir . '/' . ($kind === 'queue' ? 'queue' : 'counter') . '.json', 'c+');
    if (!$h || !flock($h, LOCK_EX)) return '';
    $c = json_decode(stream_get_contents($h), true) ?: [];
    $stamp = date('ymd');
    $seq = (($c['day'] ?? '') === $stamp ? (int) $c['seq'] : 0) + 1;
    if ($kind !== 'queue') {
        while (is_file($dir . '/LS-' . $stamp . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . '.json')) {
            $seq++; // never reuse a number (e.g. if counter.json was lost)
        }
    }
    ftruncate($h, 0);
    rewind($h);
    fwrite($h, json_encode(['day' => $stamp, 'seq' => $seq]));
    fflush($h);
    flock($h, LOCK_UN);
    fclose($h);
    return $kind === 'queue' ? (string) $seq : 'LS-' . $stamp . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
}

/**
 * Delivery settings (Store settings → Online ordering), with safe defaults:
 *   enabled, fee and minOrder in cents, freeOver (0 = never free), leadMinutes added to the
 *   pickup notice, areas (plain words for customers), postalPrefixes (e.g. ["L4W", "L5B"];
 *   empty = any address), note shown at checkout.
 */
function menu_delivery($menu = null)
{
    $menu = $menu ?: menu_data();
    $d = (array) ($menu['delivery'] ?? []);
    $prefixes = [];
    foreach ((array) ($d['postalPrefixes'] ?? []) as $p) {
        $p = strtoupper(preg_replace('/\s+/', '', (string) $p));
        if ($p !== '' && preg_match('/^[A-Z](\d([A-Z](\d)?)?)?$/', $p)) $prefixes[] = $p;
    }
    return [
        'enabled'        => !empty($d['enabled']),
        'fee'            => max(0, (int) ($d['fee'] ?? 0)),
        'minOrder'       => max(0, (int) ($d['minOrder'] ?? 0)),
        'freeOver'       => max(0, (int) ($d['freeOver'] ?? 0)),
        'leadMinutes'    => max(0, (int) ($d['leadMinutes'] ?? 0)),
        'areas'          => trim((string) ($d['areas'] ?? '')),
        'postalPrefixes' => array_values(array_unique($prefixes)),
        'note'           => trim((string) ($d['note'] ?? '')),
    ];
}

/** The delivery fee for an order subtotal (free above the "free over" amount, when one is set). */
function delivery_fee($delivery, $subtotal)
{
    if ($delivery['freeOver'] > 0 && $subtotal >= $delivery['freeOver']) return 0;
    return $delivery['fee'];
}

/** Does a postal code (letters and digits, no space) fall in the delivery area? */
function delivery_postal_allowed($delivery, $postal)
{
    if (!$delivery['postalPrefixes']) return true;
    foreach ($delivery['postalPrefixes'] as $p) {
        if (strpos($postal, $p) === 0) return true;
    }
    return false;
}
