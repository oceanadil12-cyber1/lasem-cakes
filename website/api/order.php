<?php
/**
 * Places a pickup or delivery order from the order page (js/order.js).
 *
 * The browser only sends what was chosen (item ids, quantities, flavour, notes, pickup or
 * delivery time, address, contact details). Prices, delivery fee, tax and totals are worked out
 * here from data/menu.json, so they can't be changed in the browser. Orders are saved to
 * private/orders/ (listed in the admin panel at /admin/) and emailed to the bakery, with a
 * confirmation to the customer. Payment is taken at pickup or on delivery.
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

$menu = menu_data();
if (!menu_ordering_open($menu)) {
    fail(menu_closed_message($menu) . ' ' . SITE['phone']);
}
$byVariant = [];
foreach (menu_for_customers($menu)['categories'] as $cat) {
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
    if (!empty($x['p']['soldOut'])) {
        fail($x['p']['name'] . ' is sold out today. Please remove it from your order.');
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
/* ---------- Pickup or delivery ---------- */

$delivery = menu_delivery($menu);
$isDelivery = (($in['fulfilment'] ?? 'pickup') === 'delivery');
$address = null;
$fee = 0;
if ($isDelivery) {
    if (!$delivery['enabled']) {
        fail('Delivery isn’t available right now. Please choose pickup, or call us at ' . SITE['phone'] . '.');
    }
    if ($subtotal < $delivery['minOrder']) {
        fail('Delivery orders start at ' . money($delivery['minOrder']) . ' before tax. Add a little more, or choose pickup.');
    }
    $d = (array) ($in['delivery'] ?? []);
    $address = [
        'street'       => clean($d['street'] ?? '', 120),
        'unit'         => clean($d['unit'] ?? '', 40),
        'city'         => clean($d['city'] ?? '', 60),
        'postal'       => strtoupper(preg_replace('/\s+/', '', clean($d['postal'] ?? '', 12))),
        'instructions' => clean($d['instructions'] ?? '', 300),
    ];
    if ($address['street'] === '') {
        fail('Please enter the delivery address.');
    }
    if ($address['city'] === '') {
        fail('Please enter the city for delivery.');
    }
    if (!preg_match('/^[A-Z]\d[A-Z]\d[A-Z]\d$/', $address['postal'])) {
        fail('Please enter a valid postal code (for example L4W 2Z2).');
    }
    if (!delivery_postal_allowed($delivery, $address['postal'])) {
        fail('Sorry, we don’t deliver to that postal code yet' . ($delivery['areas'] !== '' ? ' (we deliver to ' . $delivery['areas'] . ')' : '') . '. Please choose pickup, or call us at ' . SITE['phone'] . '.');
    }
    $address['postal'] = substr($address['postal'], 0, 3) . ' ' . substr($address['postal'], 3);
    $fee = delivery_fee($delivery, $subtotal);
    $taxable += $fee; // the delivery charge is taxable
}
$tax = (int) round($taxable * $menu['taxRate']);
$grand = $subtotal + $fee + $tax;
$addressLabel = $address ? trim(($address['unit'] !== '' ? $address['unit'] . ' – ' : '') . $address['street'] . ', ' . $address['city'] . ' ' . $address['postal']) : '';
$whenWord = $isDelivery ? 'delivery' : 'pickup';

/* ---------- Pickup or delivery time ---------- */

$date = (string) ($in['pickup']['date'] ?? '');
$mins = (int) ($in['pickup']['time'] ?? -1);
$tz = new DateTimeZone('America/Toronto');
$day = DateTime::createFromFormat('!Y-m-d', $date, $tz);
[$oh, $om] = array_map('intval', explode(':', $menu['hours']['opens']));
[$ch, $cm] = array_map('intval', explode(':', $menu['hours']['closes']));
$now = new DateTime('now', $tz);
$today = new DateTime('today', $tz);
if (!$day || $day->format('Y-m-d') !== $date || $mins % 15 !== 0 || $mins < $oh * 60 + $om || $mins > $ch * 60 + $cm) {
    fail('Please choose a ' . $whenWord . ' time during online ordering hours.');
}
$pickup = (clone $day)->setTime(intdiv($mins, 60), $mins % 60);
$lead = (int) $menu['leadMinutes'] + ($isDelivery ? $delivery['leadMinutes'] : 0);
$earliest = (clone $now)->modify('+' . ($lead - 5) . ' minutes');
$latest = (clone $today)->modify('+' . ((int) $menu['daysAhead'] + 1) . ' days');
if ($pickup < $earliest) {
    fail('That ' . $whenWord . ' time has passed or is too soon. Please choose a later time.');
}
if (in_array($date, menu_closed_dates($menu), true)) {
    fail('We’re closed for ' . ($isDelivery ? 'deliveries' : 'pickups') . ' on ' . $day->format('l, F j') . '. Please choose another day.');
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

$number = order_next_number($dir, 'counter');
if ($number === '') {
    fail('Sorry, we couldn’t save your order. Please call us at ' . SITE['phone'] . '.', 500);
}

$public = [
    'number'      => $number,
    'firstName'   => explode(' ', $name)[0],
    'email'       => $email,
    'pickupLabel' => $pickupLabel,
    'fulfilment'  => $isDelivery ? 'delivery' : 'pickup',
    'address'     => $addressLabel,
    'fee'         => $fee,
    'lines'       => array_map(function ($i) { return ['name' => $i['name'], 'detail' => $i['detail'], 'qty' => $i['qty'], 'total' => $i['total']]; }, $items),
    'subtotal'    => $subtotal,
    'tax'         => $tax,
    'total'       => $grand,
    'emailed'     => false,
];
$order = [
    'number'     => $number,
    'status'     => 'new',
    'placed'     => $now->format('c'),
    'pickup'     => $pickup->format('c'),
    'fulfilment' => $isDelivery ? 'delivery' : 'pickup',
    'delivery'   => $address,
    'fee'        => $fee,
    'customer'   => ['name' => $name, 'phone' => $phone, 'email' => $email],
    'notes'      => $notes,
    'items'      => $items,
    'subtotal'   => $subtotal,
    'tax'        => $tax,
    'total'      => $grand,
    'payment'    => $isDelivery ? 'pay on delivery' : 'pay at pickup',
    'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
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
$summary = implode("\n", $rows) . "\n\nSubtotal: " . money($subtotal)
    . ($isDelivery ? "\nDelivery: " . ($fee > 0 ? money($fee) : 'Free') : '')
    . "\nHST: " . money($tax) . "\nTotal: " . money($grand) . ' (pay ' . ($isDelivery ? 'on delivery' : 'at pickup') . ')';
$host = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'lasemcakes.ca');
$from = 'From: La Sem Cakes <no-reply@' . $host . ">\r\nContent-Type: text/plain; charset=UTF-8";
$whereLines = $isDelivery
    ? "Deliver to: $addressLabel\n" . ($address['instructions'] !== '' ? "Delivery instructions: {$address['instructions']}\n" : '')
    : '';

@mail(
    notify_email(),
    'New ' . $whenWord . ' order ' . $number . ' – ' . $pickup->format('D M j, g:i a'),
    "New online order $number\n\n" . ucfirst($whenWord) . ": $pickupLabel\n$whereLines" . "Name: $name\nPhone: $phone\nEmail: $email\n" . ($notes ? "Notes: $notes\n" : '') . "\n$summary\n",
    $from . "\r\nReply-To: " . $email
);
$public['emailed'] = @mail(
    $email,
    'Your La Sem order ' . $number,
    "Hi {$public['firstName']},\n\nThank you for your order! Here are the details.\n\nOrder: $number\n" . ucfirst($whenWord) . ": $pickupLabel\n"
    . ($isDelivery ? $whereLines : 'Where: ' . SITE['street'] . ', ' . SITE['city'] . "\n")
    . "\n$summary\n\n"
    . ($isDelivery ? 'Please have payment ready when your order arrives (cash or card).' : 'Please pay at the counter when you pick up.')
    . ' To change or cancel, call ' . SITE['phone'] . ".\n\nLa Sem Cakes\n",
    $from . "\r\nReply-To: " . SITE['email']
);


$order['public'] = $public;
@file_put_contents($dir . '/' . $number . '.json', json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

reply(['ok' => true, 'order' => $public]);
