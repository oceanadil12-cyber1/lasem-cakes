<?php
/**
 * Places a pickup order from the order page (js/order.js).
 *
 * The browser only sends what was chosen (item ids, quantities, flavour, notes, pickup time,
 * contact details). Prices, tax and totals are worked out here from data/menu.json, so they
 * can't be changed in the browser. Orders are saved to private/orders/ (the admin panel will
 * list them) and emailed to the bakery, with a confirmation to the customer.
 * Payment is taken at pickup.
 */
define('BASE_URL', rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') . '/');
require __DIR__ . '/../inc/site.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

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
function money($cents)
{
    return '$' . number_format($cents / 100, 2);
}
function clean($s, $max)
{
    $s = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', (string) $s));
    return mb_substr($s, 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Orders are placed from the order page.', 405);
}
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) {
    fail('We couldn’t read your order. Please try again.', 400);
}

$menu = json_decode(file_get_contents(__DIR__ . '/../data/menu.json'), true);
$byVariant = [];
foreach ($menu['categories'] as $cat) {
    foreach ($cat['products'] as $p) {
        foreach ($p['variants'] as $v) {
            $byVariant[$v['id']] = ['p' => $p, 'v' => $v, 'cat' => $cat];
        }
    }
}

$dir = __DIR__ . '/../private/orders';
if (!is_dir($dir . '/keys')) {
    @mkdir($dir . '/keys', 0750, true);
}

// The same checkout sent twice (double tap, flaky connection) returns the first order.
$key = preg_replace('/[^a-z0-9]/i', '', (string) ($in['key'] ?? ''));
if ($key !== '' && is_file($dir . '/keys/' . $key)) {
    $num = trim(file_get_contents($dir . '/keys/' . $key));
    $prev = json_decode((string) @file_get_contents($dir . '/' . $num . '.json'), true);
    if ($prev) {
        reply(['ok' => true, 'order' => $prev['public']]);
    }
}

/* ---------- Items: re-priced from the menu ---------- */

$lines = $in['lines'] ?? [];
if (!is_array($lines) || !$lines) {
    fail('Your order is empty.');
}
if (count($lines) > 50) {
    fail('That’s a big order! Please call us at ' . SITE['phone'] . ' for orders this size.');
}
$items = [];
$subtotal = 0;
$taxable = 0;
foreach ($lines as $l) {
    $x = $byVariant[(string) ($l['variant'] ?? '')] ?? null;
    if (!$x) {
        fail('An item in your order is no longer available. Please remove it and try again.');
    }
    $qty = (int) ($l['qty'] ?? 0);
    if ($qty < 1 || $qty > 50) {
        fail('Please check the quantities in your order (1 to 50 of each item).');
    }
    $choice = null;
    if (!empty($x['p']['choice'])) {
        $choice = (string) ($l['choice'] ?? '');
        if (!in_array($choice, $x['p']['choice']['options'], true)) {
            fail('Please choose a ' . strtolower($x['p']['choice']['label']) . ' for ' . $x['p']['name'] . '.');
        }
    }
    $note = clean($l['note'] ?? '', 200);
    $total = $x['v']['price'] * $qty;
    $subtotal += $total;
    if (!empty($x['cat']['taxable'])) {
        $taxable += $total;
    }
    $detail = implode(' · ', array_filter([$x['v']['label'], $choice]));
    $items[] = [
        'variant' => $x['v']['id'],
        'name'    => $x['p']['name'],
        'detail'  => $detail,
        'note'    => $note,
        'qty'     => $qty,
        'price'   => $x['v']['price'],
        'total'   => $total,
        'taxable' => !empty($x['cat']['taxable']),
    ];
}
$tax = (int) round($taxable * $menu['taxRate']);
$grand = $subtotal + $tax;

/* ---------- Pickup time ---------- */

$date = (string) ($in['pickup']['date'] ?? '');
$mins = (int) ($in['pickup']['time'] ?? -1);
$tz = new DateTimeZone('America/Toronto');
$day = DateTime::createFromFormat('!Y-m-d', $date, $tz);
[$oh, $om] = array_map('intval', explode(':', $menu['hours']['opens']));
[$ch, $cm] = array_map('intval', explode(':', $menu['hours']['closes']));
$now = new DateTime('now', $tz);
$today = new DateTime('today', $tz);
if (!$day || $day->format('Y-m-d') !== $date || $mins % 15 !== 0 || $mins < $oh * 60 + $om || $mins > $ch * 60 + $cm) {
    fail('Please choose a pickup time during online ordering hours.');
}
$pickup = (clone $day)->setTime(intdiv($mins, 60), $mins % 60);
$earliest = (clone $now)->modify('+' . ((int) $menu['leadMinutes'] - 5) . ' minutes');
$latest = (clone $today)->modify('+' . ((int) $menu['daysAhead'] + 1) . ' days');
if ($pickup < $earliest) {
    fail('That pickup time has passed or is too soon. Please choose a later time.');
}
if ($pickup >= $latest) {
    fail('Orders can be placed up to ' . (int) $menu['daysAhead'] . ' days ahead.');
}
$pickupLabel = $pickup->format('l, F j') . ' at ' . $pickup->format('g:i a');

/* ---------- Customer ---------- */

$name = clean($in['customer']['name'] ?? '', 80);
$phone = clean($in['customer']['phone'] ?? '', 30);
$email = clean($in['customer']['email'] ?? '', 120);
$notes = clean($in['notes'] ?? '', 500);
if ($name === '') {
    fail('Please enter your name.');
}
if (strlen(preg_replace('/\D/', '', $phone)) < 10) {
    fail('Please enter a phone number with area code.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Please enter a valid email address.');
}

/* ---------- Order number and save ---------- */

// LS-241024-001: date + a counter that restarts each day. The lock stops two orders sharing a number.
$counter = fopen($dir . '/counter.json', 'c+');
if (!$counter || !flock($counter, LOCK_EX)) {
    fail('Sorry, we couldn’t save your order. Please call us at ' . SITE['phone'] . '.', 500);
}
$c = json_decode(stream_get_contents($counter), true) ?: [];
$stamp = $now->format('ymd');
$seq = (($c['day'] ?? '') === $stamp ? (int) $c['seq'] : 0) + 1;
ftruncate($counter, 0);
rewind($counter);
fwrite($counter, json_encode(['day' => $stamp, 'seq' => $seq]));
fflush($counter);
flock($counter, LOCK_UN);
fclose($counter);
$number = 'LS-' . $stamp . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

$public = [
    'number'      => $number,
    'firstName'   => explode(' ', $name)[0],
    'email'       => $email,
    'pickupLabel' => $pickupLabel,
    'lines'       => array_map(function ($i) { return ['name' => $i['name'], 'detail' => $i['detail'], 'qty' => $i['qty'], 'total' => $i['total']]; }, $items),
    'subtotal'    => $subtotal,
    'tax'         => $tax,
    'total'       => $grand,
    'emailed'     => false,
];
$order = [
    'number'   => $number,
    'status'   => 'new',
    'placed'   => $now->format('c'),
    'pickup'   => $pickup->format('c'),
    'customer' => ['name' => $name, 'phone' => $phone, 'email' => $email],
    'notes'    => $notes,
    'items'    => $items,
    'subtotal' => $subtotal,
    'tax'      => $tax,
    'total'    => $grand,
    'payment'  => 'pay at pickup',
    'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
];

// Saved before any email goes out, so every email matches a real order.
$order['public'] = $public;
if (!@file_put_contents($dir . '/' . $number . '.json', json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    fail('Sorry, we couldn’t save your order. Please call us at ' . SITE['phone'] . '.', 500);
}
if ($key !== '') {
    @file_put_contents($dir . '/keys/' . $key, $number);
}

/* ---------- Emails ---------- */

$rows = [];
foreach ($items as $i) {
    $rows[] = $i['qty'] . ' x ' . $i['name'] . ($i['detail'] ? ' (' . $i['detail'] . ')' : '') . '  ' . money($i['total']) . ($i['note'] ? "\n    Note: " . $i['note'] : '');
}
$summary = implode("\n", $rows) . "\n\nSubtotal: " . money($subtotal) . "\nHST: " . money($tax) . "\nTotal: " . money($grand) . " (pay at pickup)";
$host = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'lasemcakes.ca');
$from = 'From: La Sem Cakes <no-reply@' . $host . ">\r\nContent-Type: text/plain; charset=UTF-8";

@mail(
    SITE['email'],
    'New pickup order ' . $number . ' – ' . $pickup->format('D M j, g:i a'),
    "New online order $number\n\nPickup: $pickupLabel\nName: $name\nPhone: $phone\nEmail: $email\n" . ($notes ? "Notes: $notes\n" : '') . "\n$summary\n",
    $from . "\r\nReply-To: " . $email
);
$public['emailed'] = @mail(
    $email,
    'Your La Sem order ' . $number,
    "Hi {$public['firstName']},\n\nThank you for your order! Here are the details.\n\nOrder: $number\nPickup: $pickupLabel\nWhere: " . SITE['street'] . ', ' . SITE['city'] . "\n\n$summary\n\nPlease pay at the counter when you pick up. To change or cancel, call " . SITE['phone'] . ".\n\nLa Sem Cakes\n",
    $from . "\r\nReply-To: " . SITE['email']
);


$order['public'] = $public;
@file_put_contents($dir . '/' . $number . '.json', json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

reply(['ok' => true, 'order' => $public]);
