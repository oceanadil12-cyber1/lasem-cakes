<?php
/**
 * POST kds/api.php (JSON body, kitchen session + CSRF token) — the kitchen display's data.
 *
 *   {"action": "orders"}                          today's and tomorrow's pickups (late ones too), and today's picked-up orders
 *   {"action": "move", "number": "...", "to": "preparing" | "ready" | "picked_up" | "back"}
 *   {"action": "settings", "prep_minutes": 30, "sound": true}
 *   {"action": "call-done", "id": "..."}          a table's "call staff" request was answered
 *
 * Lives inside /kds/ so the session cookie (path /kds/) is sent with it.
 */
define('KDS', true);
require __DIR__ . '/inc/kds.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('X-Robots-Tag: noindex, nofollow');

function kds_reply(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') kds_reply(['ok' => false, 'error' => 'Use POST.'], 405);
kds_session_start();
if (!kds_signed_in()) kds_reply(['ok' => false, 'signedOut' => true], 401);
session_write_close(); // nothing below changes the session; don't make parallel requests wait

$req = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($req) || !hash_equals((string) ($_SESSION['kds_csrf'] ?? ''), (string) ($req['csrf'] ?? ''))) {
    kds_reply(['ok' => false, 'error' => 'Please reload the page.'], 403);
}
$who = (string) ($_SESSION['kds_who'] ?? 'kitchen');

/** An order as the screen needs it (times in milliseconds for JavaScript). */
function kds_order(array $o): array
{
    $picked = 0;
    foreach ($o['history'] as $h) if (($h['s'] ?? '') === 'picked_up') $picked = (int) strtotime((string) $h['t']);
    return [
        'number'  => $o['number'],
        'type'    => $o['type'],
        'queue'   => $o['queue'],
        'table'   => $o['table'],
        'status'  => $o['status'],
        'name'    => $o['name'],
        'phone'   => $o['phone'],
        'placed'  => $o['ts'] * 1000,
        'pickup'  => $o['pickup'] * 1000,
        'day'     => $o['day'],
        'delivery' => $o['fulfilment'] === 'delivery',
        'address' => $o['fulfilment'] === 'delivery' ? trim((($o['delivery']['unit'] ?? '') !== '' ? $o['delivery']['unit'] . ' – ' : '') . ($o['delivery']['street'] ?? '') . ', ' . ($o['delivery']['city'] ?? '') . ' ' . ($o['delivery']['postal'] ?? '')) : '',
        'items'   => array_map(fn($i) => [
            'qty'    => (int) ($i['qty'] ?? 0),
            'name'   => (string) ($i['name'] ?? ''),
            'detail' => (string) ($i['detail'] ?? ''),
            'note'   => (string) ($i['note'] ?? ''),
        ], $o['items']),
        'notes'   => $o['notes'],
        'teamNote' => $o['note'],
        'total'   => $o['total'],
        'paid'    => $o['paid'],
        'started' => $o['started'] * 1000,
        'readyAt' => $o['ready_at'] * 1000,
        'pickedAt' => $picked * 1000,
    ];
}

function kds_orders(): array
{
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $oldest = date('Y-m-d', strtotime('-3 days')); // late orders still open from the last few days
    $out = [];
    foreach (orders_all(true) as $o) {
        $open = in_array($o['status'], ORDER_OPEN, true) && $o['day'] >= $oldest && $o['day'] <= $tomorrow;
        $doneToday = $o['status'] === 'picked_up' && $o['day'] >= $oldest;
        if (!$open && !$doneToday) continue;
        $k = kds_order($o);
        if ($doneToday && date('Y-m-d', (int) ($k['pickedAt'] / 1000)) !== $today) continue;
        $out[] = $k;
    }
    usort($out, fn($a, $b) => $a['pickup'] <=> $b['pickup']);
    return $out;
}

$action = (string) ($req['action'] ?? '');

if ($action === 'orders') {
    kds_reply(['ok' => true, 'now' => time() * 1000, 'today' => date('Y-m-d'), 'tomorrow' => date('Y-m-d', strtotime('+1 day')),
        'orders' => kds_orders(), 'ordering' => menu_ordering_open(), 'calls' => dinein_calls(), 'dinein' => array_intersect_key(dinein_settings(), ['serve' => 1, 'lateMins' => 1])]);
}

if ($action === 'move') {
    $num = strtoupper(trim((string) ($req['number'] ?? '')));
    $to = (string) ($req['to'] ?? '');
    $o = order_find($num);
    if (!$o) kds_reply(['ok' => false, 'error' => 'That order wasn\'t found.'], 404);
    // Which moves the screen may make, from which statuses.
    $moves = [
        'preparing' => ['new', 'confirmed'],
        'ready'     => ['new', 'confirmed', 'preparing'],
        'picked_up' => ['ready'],
    ];
    if ($to === 'back') {
        $to = $o['status'] === 'picked_up' ? 'ready' : ($o['status'] === 'ready' ? 'preparing' : '');
        if ($to === '') kds_reply(['ok' => false, 'error' => 'This order can\'t go back a step.'], 409);
    } elseif (!isset($moves[$to]) || !in_array($o['status'], $moves[$to], true)) {
        kds_reply(['ok' => false, 'error' => 'Order ' . $num . ' is already ' . strtolower(ORDER_STATUSES[$o['status']][0]) . '.', 'orders' => kds_orders()], 409);
    }
    if (!order_set_status($num, $to)) kds_reply(['ok' => false, 'error' => 'The order couldn\'t be saved. Please try again.'], 500);
    if ($to === 'picked_up' && !$o['paid']) order_set($num, ['paid' => true]); // paid at the counter on collection
    admin_log('kds.status', $num . ' (' . $o['name'] . '): ' . strtolower(ORDER_STATUSES[$to][0]) . ' · ' . $who);
    kds_reply(['ok' => true, 'orders' => kds_orders()]);
}

if ($action === 'call-done') {
    $calls = array_column(dinein_calls(), null, 'id');
    $id = (string) ($req['id'] ?? '');
    if (isset($calls[$id]) && dinein_call_done($id)) admin_log('kds.call', $calls[$id]['table'] . ' · ' . $who);
    kds_reply(['ok' => true, 'calls' => dinein_calls()]);
}

if ($action === 'settings') {
    $prep = max(5, min(1440, (int) ($req['prep_minutes'] ?? 30)));
    $sound = !empty($req['sound']);
    if (!kds_save_settings(['prep_minutes' => $prep, 'sound' => $sound])) kds_reply(['ok' => false, 'error' => 'Settings couldn\'t be saved.'], 500);
    admin_log('settings.kds', 'start ' . $prep . ' min before pickup, sound ' . ($sound ? 'on' : 'off') . ' · ' . $who);
    kds_reply(['ok' => true, 'prep_minutes' => $prep, 'sound' => $sound]);
}

kds_reply(['ok' => false, 'error' => 'Unknown action.'], 400);
