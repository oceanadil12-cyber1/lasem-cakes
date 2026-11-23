<?php
/**
 * Table ordering API (used by dine-in/assets/dine-in.js and the "Now serving" board).
 *
 *   POST {"action": "order", "code": "TABLECODE", "name": "Maria", "lines": [...], "notes": "...", "key": "..."}
 *        → places a table order, priced here from data/menu.json; returns its priority number and tracking key
 *   POST {"action": "track", "orders": [{"n": "LS-…", "k": "trackingkey"}, ...]}
 *        → live status of the guest's own orders (the key proves it's theirs)
 *   POST {"action": "call", "code": "TABLECODE", "name": "Maria"}
 *        → "call staff": shows on the kitchen display until someone taps Done
 *   GET  ?action=board → priority numbers being prepared and ready (numbers only, no names)
 *
 * Payment is taken at the counter (or as set in the admin); nothing is charged here.
 */
define('BASE_URL', rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') . '/');
require __DIR__ . '/../inc/site.php';

if (!ONLINE_ORDERING) { // ordering is switched off in inc/site.php
    http_response_code(410);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Online ordering is not available. Please order through DoorDash or call us.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function reply($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function fail($msg, $code = 422)
{
    reply(['ok' => false, 'error' => $msg], $code);
}

/** A simple per-visitor limit: at most $max actions of $kind per $window seconds. */
function too_many($kind, $max, $window)
{
    $f = dinein_private('limits') . '/' . hash('sha256', 'dinein-' . $kind . '|' . ($_SERVER['REMOTE_ADDR'] ?? '')) . '.json';
    $r = is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
    if (($r['start'] ?? 0) <= time() - $window) $r = ['start' => time(), 'n' => 0];
    if ($r['n'] >= $max) return true;
    $r['n']++;
    @file_put_contents($f, json_encode($r), LOCK_EX);
    return false;
}

$dir = dinein_private('orders');
if (!is_dir($dir . '/keys')) @mkdir($dir . '/keys', 0750, true);

// ---------- the board ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'board') {
    $preparing = $ready = [];
    $today = date('ymd');
    foreach (glob($dir . '/LS-' . $today . '-*.json') ?: [] as $f) {
        $o = json_decode((string) file_get_contents($f), true);
        if (!is_array($o) || ($o['type'] ?? '') !== 'dine-in' || empty($o['queue'])) continue;
        $st = (string) ($o['status'] ?? 'new');
        if (in_array($st, ['new', 'confirmed', 'preparing'], true)) $preparing[] = (int) $o['queue'];
        elseif ($st === 'ready') $ready[] = (int) $o['queue'];
    }
    sort($preparing);
    sort($ready);
    reply(['ok' => true, 'preparing' => $preparing, 'ready' => $ready, 'serve' => dinein_settings()['serve']]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Use POST.', 405);
$in = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($in)) fail('We couldn’t read that. Please try again.', 400);
$action = (string) ($in['action'] ?? '');

// ---------- track ----------
if ($action === 'track') {
    $out = [];
    $s = dinein_settings();
    foreach (array_slice((array) ($in['orders'] ?? []), 0, 10) as $t) {
        $n = (string) ($t['n'] ?? '');
        if (!preg_match('/^LS-\d{6}-\d{3,5}$/', $n) || !is_file($dir . '/' . $n . '.json')) continue;
        $o = json_decode((string) file_get_contents($dir . '/' . $n . '.json'), true);
        if (!is_array($o) || empty($o['track']) || !hash_equals((string) $o['track'], (string) ($t['k'] ?? ''))) continue;
        $out[] = dinein_public_status($o, $s);
    }
    reply(['ok' => true, 'orders' => $out]);
}

// Ordering and calling staff need a real table.
$table = dinein_table_by_code($in['code'] ?? '');
if (!$table) fail('This table link isn’t valid any more. Please scan the QR code on your table again, or order at the counter.', 404);
[$open, $why] = dinein_open();
if (!$open) fail($why);
$name = dinein_clean($in['name'] ?? '', 30);

// ---------- call staff ----------
if ($action === 'call') {
    $last = dinein_private('calls') . '/last-' . (int) $table['id'];
    if (is_file($last) && time() - filemtime($last) < 60) reply(['ok' => true, 'already' => true]);
    if (too_many('call', 10, 3600)) fail('We’ve had a few calls from you already. Someone will be with you shortly.', 429);
    @touch($last);
    $id = time() . '-' . bin2hex(random_bytes(3));
    @file_put_contents(dinein_private('calls') . '/' . $id . '.json', json_encode(['table' => $table['name'], 'tableId' => (int) $table['id'], 'name' => $name, 'at' => time()]));
    reply(['ok' => true]);
}

if ($action !== 'order') fail('Unknown request.', 400);

// ---------- place an order ----------

// The same order sent twice (double tap, slow connection) returns the first one.
$key = preg_replace('/[^a-z0-9]/i', '', (string) ($in['key'] ?? ''));
if ($key !== '' && is_file($dir . '/keys/' . $key)) {
    $prev = json_decode((string) @file_get_contents($dir . '/' . trim((string) file_get_contents($dir . '/keys/' . $key)) . '.json'), true);
    if (is_array($prev)) reply(['ok' => true, 'order' => dinein_public_status($prev), 'track' => ['n' => $prev['number'], 'k' => $prev['track']]]);
}
if (too_many('order', 20, 3600)) fail('That’s a lot of orders from this device. Please order at the counter.', 429);

$menu = menu_data();
[$items, $subtotal, $tax, $err] = dinein_price($in['lines'] ?? [], $menu);
if ($err !== '') fail($err);

$number = order_next_number($dir, 'counter');
$queue = (int) order_next_number($dir, 'queue');
if ($number === '' || !$queue) fail('Sorry, we couldn’t save your order. Please order at the counter.', 500);

$now = date('c');
$order = [
    'number'   => $number,
    'type'     => 'dine-in',
    'status'   => 'new',
    'placed'   => $now,
    'pickup'   => $now, // table orders are for right now
    'queue'    => $queue,
    'table'    => ['id' => (int) $table['id'], 'name' => (string) $table['name']],
    'track'    => bin2hex(random_bytes(8)),
    'customer' => ['name' => $name, 'phone' => '', 'email' => ''],
    'notes'    => dinein_clean($in['notes'] ?? '', 300),
    'items'    => $items,
    'subtotal' => $subtotal,
    'tax'      => $tax,
    'total'    => $subtotal + $tax,
    'payment'  => 'pay at counter',
    'device'   => !empty($in['kiosk']) ? 'table tablet' : 'phone',
    'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
];
if (!@file_put_contents($dir . '/' . $number . '.json', json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX)) {
    fail('Sorry, we couldn’t save your order. Please order at the counter.', 500);
}
if ($key !== '') @file_put_contents($dir . '/keys/' . $key, $number);

reply(['ok' => true, 'order' => dinein_public_status($order), 'track' => ['n' => $number, 'k' => $order['track']]]);
