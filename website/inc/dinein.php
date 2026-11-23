<?php
/**
 * Table ordering (dine-in): shared by the table page (dine-in/), its API (api/dine-in.php),
 * the kitchen display and the admin panel.
 *
 *   private/tables.json      the tables: [{id, name, code, active}]. The code is the secret part of
 *                            each table's QR link (/dine-in/?t=CODE), so nobody can order for a table
 *                            from home by guessing a number.
 *   data/menu.json "dineIn"  on/off, how orders reach the customer (to the table / collect at the
 *                            counter), HST on every table order, the payment note, the "late" time.
 *   private/orders/          table orders are ordinary order files with "type": "dine-in", the table,
 *                            a priority number ("queue", 1, 2, 3… each day) and a tracking key.
 *   private/calls/           "call staff" requests from a table, until the KDS marks them done.
 */

const DINEIN_DEFAULTS = [
    'on'       => true,
    'serve'    => 'table',  // 'table' = we bring it; 'counter' = collect at the counter when the number is called
    'taxAll'   => true,     // food eaten in is taxable in Ontario, so HST goes on every table order
    'payNote'  => 'Pay at the counter when you\'re ready.',
    'lateMins' => 15,       // the KDS shows a table order as late after this many minutes
];

function dinein_private($sub = '')
{
    $dir = __DIR__ . '/../private' . ($sub !== '' ? '/' . $sub : '');
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    return $dir;
}

function dinein_settings($menu = null)
{
    $menu = $menu ?: menu_data();
    $s = array_merge(DINEIN_DEFAULTS, array_intersect_key((array) ($menu['dineIn'] ?? []), DINEIN_DEFAULTS));
    $s['on'] = $s['on'] !== false;
    $s['serve'] = $s['serve'] === 'counter' ? 'counter' : 'table';
    $s['taxAll'] = $s['taxAll'] !== false;
    $s['lateMins'] = max(3, min(120, (int) $s['lateMins']));
    $s['payNote'] = trim((string) $s['payNote']) !== '' ? trim((string) $s['payNote']) : DINEIN_DEFAULTS['payNote'];
    return $s;
}

/** All tables, in order. */
function dinein_tables()
{
    $f = dinein_private() . '/tables.json';
    $t = is_file($f) ? json_decode((string) file_get_contents($f), true) : [];
    return is_array($t) ? array_values(array_filter($t, 'is_array')) : [];
}

function dinein_table_by_code($code)
{
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
    if (strlen($code) < 6) return null;
    foreach (dinein_tables() as $t) {
        if (!empty($t['active']) && hash_equals((string) $t['code'], $code)) return $t;
    }
    return null;
}

/** A new table code: 8 characters that are easy to read (no 0/O, 1/I). */
function dinein_new_code()
{
    $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    for ($i = 0; $i < 8; $i++) $code .= $abc[random_int(0, strlen($abc) - 1)];
    return $code;
}

/** Is table ordering open right now? [bool, reason shown to guests when it isn't]. */
function dinein_open($menu = null)
{
    $menu = $menu ?: menu_data();
    $s = dinein_settings($menu);
    if (!$s['on']) return [false, 'Table ordering is switched off right now. Please order at the counter.'];
    if (in_array(date('Y-m-d'), menu_closed_dates($menu), true)) return [false, 'We\'re closed today. Please order at the counter if we\'re open.'];
    $now = date('H:i');
    if ($now < SITE['opens'] || $now >= SITE['closes']) {
        return [false, 'Table ordering is open during store hours (' . date('g:i a', strtotime(SITE['opens'])) . ' – ' . date('g:i a', strtotime(SITE['closes'])) . ').'];
    }
    return [true, ''];
}

/** The menu for tables: what customers can order online, minus categories marked "not for table ordering". */
function dinein_menu($menu = null)
{
    $m = menu_for_customers($menu);
    $m['categories'] = array_values(array_filter($m['categories'], function ($c) { return empty($c['noDineIn']); }));
    return $m;
}

/**
 * Price the guest's lines from the menu (the browser's prices are never used).
 * Returns [items, subtotal, tax, error message or ''].
 */
function dinein_price($lines, $menu = null)
{
    $menu = $menu ?: menu_data();
    $s = dinein_settings($menu);
    $by = [];
    foreach (dinein_menu($menu)['categories'] as $cat) {
        foreach ($cat['products'] as $p) foreach ($p['variants'] as $v) $by[$v['id']] = [$p, $v, $cat];
    }
    if (!is_array($lines) || !$lines) return [[], 0, 0, 'Your order is empty.'];
    if (count($lines) > 40) return [[], 0, 0, 'That\'s a big order! Please order at the counter.'];
    $items = [];
    $sub = 0;
    $taxable = 0;
    foreach ($lines as $l) {
        $x = $by[(string) ($l['variant'] ?? '')] ?? null;
        if (!$x) return [[], 0, 0, 'An item in your order is no longer available. Please remove it and try again.'];
        [$p, $v, $cat] = $x;
        if (!empty($p['soldOut'])) return [[], 0, 0, $p['name'] . ' is sold out. Please remove it from your order.'];
        $qty = (int) ($l['qty'] ?? 0);
        if ($qty < 1 || $qty > 30) return [[], 0, 0, 'Please check the quantities (1 to 30 of each item).'];
        $choice = null;
        if (!empty($p['choice'])) {
            $choice = (string) ($l['choice'] ?? '');
            if (!in_array($choice, $p['choice']['options'], true)) return [[], 0, 0, 'Please choose a ' . strtolower($p['choice']['label']) . ' for ' . $p['name'] . '.'];
        }
        $total = $v['price'] * $qty;
        $sub += $total;
        $isTaxable = $s['taxAll'] || !empty($cat['taxable']);
        if ($isTaxable) $taxable += $total;
        $items[] = [
            'variant' => $v['id'],
            'name'    => $p['name'],
            'detail'  => implode(' · ', array_filter([$v['label'], $choice])),
            'note'    => dinein_clean($l['note'] ?? '', 150),
            'qty'     => $qty,
            'price'   => $v['price'],
            'total'   => $total,
            'taxable' => $isTaxable,
        ];
    }
    return [$items, $sub, (int) round($taxable * ($menu['taxRate'] ?? 0.13)), ''];
}

function dinein_clean($s, $max)
{
    return mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $s)), 0, $max);
}

/** What a guest sees about their order (no customer details, no team notes). */
function dinein_public_status($o, $settings = null)
{
    $s = $settings ?: dinein_settings();
    $status = (string) ($o['status'] ?? 'new');
    $steps = [
        'new'       => ['received', 'Order received', 'We\'ve got your order. It\'ll be started in a moment.'],
        'confirmed' => ['received', 'Order received', 'We\'ve got your order. It\'ll be started in a moment.'],
        'preparing' => ['preparing', 'Being prepared', 'Our team is preparing your order now.'],
        'ready'     => ['ready', 'Ready!', $s['serve'] === 'counter' ? 'Please collect your order at the counter.' : 'Your order is on its way to your table.'],
        'picked_up' => ['done', $s['serve'] === 'counter' ? 'Collected' : 'Served', 'Enjoy! Thank you for visiting La Sem.'],
        'cancelled' => ['cancelled', 'Cancelled', 'This order was cancelled. Please ask at the counter.'],
    ];
    [$step, $label, $text] = $steps[$status] ?? $steps['new'];
    return [
        'number' => (string) $o['number'],
        'queue'  => (int) ($o['queue'] ?? 0),
        'table'  => (string) ($o['table']['name'] ?? ''),
        'name'   => explode(' ', (string) ($o['customer']['name'] ?? ''))[0],
        'step'   => $step,
        'label'  => $label,
        'text'   => $text,
        'placed' => (int) strtotime((string) $o['placed']) * 1000,
        'lines'  => array_map(function ($i) { return ['qty' => $i['qty'], 'name' => $i['name'], 'detail' => $i['detail'], 'total' => $i['total']]; }, (array) $o['items']),
        'subtotal' => (int) $o['subtotal'],
        'tax'    => (int) $o['tax'],
        'total'  => (int) $o['total'],
        'paid'   => !empty($o['paid']),
    ];
}

/** "Call staff" requests still waiting: [{id, table, name, at}]. */
function dinein_calls()
{
    $out = [];
    foreach (glob(dinein_private('calls') . '/*.json') ?: [] as $f) {
        $c = json_decode((string) file_get_contents($f), true);
        if (!is_array($c)) continue;
        if (time() - (int) ($c['at'] ?? 0) > 7200) { @unlink($f); continue; } // forgotten calls fade after 2 hours
        $out[] = ['id' => basename($f, '.json'), 'table' => (string) ($c['table'] ?? ''), 'name' => (string) ($c['name'] ?? ''), 'at' => (int) $c['at'] * 1000];
    }
    usort($out, function ($a, $b) { return $a['at'] <=> $b['at']; });
    return $out;
}

function dinein_call_done($id)
{
    if (!preg_match('/^[0-9]{10}-[0-9a-f]{6}$/', (string) $id)) return false;
    $f = dinein_private('calls') . '/' . $id . '.json';
    return is_file($f) && @unlink($f);
}
