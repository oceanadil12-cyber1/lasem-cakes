<?php
/**
 * Deliveries: the day's delivery orders in time order with the address, map and phone links,
 * the rider, one-tap "Out for delivery" and "Delivered" steps, a route link for the whole run,
 * and a printable run sheet. Also the invoice: ?page=deliveries&invoice=LS-… prints the
 * customer, rider and bakery copies of any website order (pickup orders too).
 */
require_once __DIR__ . '/orders.php'; // order_is_delivery(), order_address(), pickup_label(), paid_badge(), order_type_chip()

function admin_page_deliveries(): void
{
    $inv = strtoupper(get_str('invoice', 20));
    if ($inv !== '') admin_invoice($inv); // ends the request
    if (is_post()) admin_deliveries_post();
    admin_deliveries_list();
}

/** Delivery orders (not cancelled) for one day, soonest first. */
function deliveries_for_day(string $day): array
{
    $list = array_values(array_filter(orders_all(), fn($o) => order_is_delivery($o) && $o['day'] === $day && $o['status'] !== 'cancelled'));
    usort($list, fn($a, $b) => $a['pickup'] <=> $b['pickup']);
    return $list;
}

/** Where a delivery stands: to pack (new/confirmed/preparing), packed (ready), out (ready + out_at), delivered (picked_up). */
function delivery_stage(array $o): string
{
    if ($o['status'] === 'picked_up') return 'delivered';
    if ($o['status'] === 'ready') return $o['out_at'] ? 'out' : 'packed';
    return 'packing';
}

function delivery_stage_badge(array $o): string
{
    switch (delivery_stage($o)) {
        case 'delivered': return badge('Delivered', 'muted');
        case 'out':       return badge('Out for delivery', 'gold');
        case 'packed':    return badge('Packed, ready to go', 'good');
    }
    return order_status_badge($o);
}

/** Google Maps directions from the bakery through every stop still to be delivered. */
function delivery_route_url(array $stops): string
{
    $bakery = SITE['street'] . ', ' . SITE['city'] . ' ' . SITE['region'] . ' ' . SITE['postal'];
    $addresses = array_values(array_filter(array_map('order_address', $stops)));
    if (!$addresses) return '';
    $last = array_pop($addresses);
    return 'https://www.google.com/maps/dir/?api=1&origin=' . rawurlencode($bakery) . '&destination=' . rawurlencode($last)
        . ($addresses ? '&waypoints=' . rawurlencode(implode('|', array_slice($addresses, 0, 9))) : '') . '&travelmode=driving';
}

function admin_deliveries_post(): void
{
    require_csrf();
    $num = strtoupper(post_str('id', 20));
    $o = order_find($num);
    $day = post_str('day', 10);
    $back = admin_url('deliveries', preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) ? ['day' => $day] : []);
    if (!$o || !order_is_delivery($o)) {
        flash('That delivery wasn\'t found.', 'err');
        redirect($back);
    }
    $rider = post_str('rider', 60);
    switch ($_POST['action'] ?? '') {
        case 'rider':
            order_set($num, ['rider' => $rider]);
            admin_log('delivery.rider', $num . ': ' . ($rider !== '' ? $rider : 'no rider'));
            flash($rider !== '' ? 'Rider for ' . $num . ' set to ' . $rider . '.' : 'Rider for ' . $num . ' cleared.');
            break;
        case 'out':
            if ($o['status'] !== 'picked_up') {
                if ($o['status'] !== 'ready') order_set_status($num, 'ready');
                order_set($num, ['out_at' => date('c')] + ($rider !== '' ? ['rider' => $rider] : []));
                admin_log('delivery.out', $num . ($rider !== '' ? ' with ' . $rider : ''));
                flash('Order ' . $num . ' is out for delivery' . ($rider !== '' ? ' with ' . $rider : '') . '.');
            }
            break;
        case 'delivered':
            order_set_status($num, 'picked_up');
            order_set($num, ['paid' => true] + ($o['out_at'] ? [] : ['out_at' => date('c')]));
            admin_log('delivery.done', $num . ' delivered, paid');
            flash('Order ' . $num . ' delivered and marked paid.');
            break;
        case 'undo':
            if ($o['status'] === 'picked_up') {
                order_set_status($num, 'ready');
                order_set($num, ['paid' => false]);
            }
            order_set($num, ['out_at' => '']);
            admin_log('delivery.undo', $num);
            flash('Order ' . $num . ' is back with the kitchen as packed.');
            break;
    }
    redirect($back);
}

function admin_deliveries_list(): void
{
    $today = date('Y-m-d');
    $day = get_str('day', 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) $day = $today;
    $orders = deliveries_for_day($day);
    $title = $day === $today ? 'Today' : ($day === date('Y-m-d', strtotime('+1 day')) ? 'Tomorrow' : date('l, M j', strtotime($day)));

    // Day pills: today and the next 6 days, with how many deliveries each has.
    $byDay = [];
    foreach (orders_all() as $o) if (order_is_delivery($o) && $o['status'] !== 'cancelled') $byDay[$o['day']] = ($byDay[$o['day']] ?? 0) + 1;
    $pills = '';
    for ($i = 0; $i < 7; $i++) {
        $d = date('Y-m-d', strtotime("+$i day"));
        $pills .= '<a class="pill' . ($d === $day ? ' on' : '') . '" href="' . h(admin_url('deliveries', ['day' => $d])) . '">' . h($i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : date('D j', strtotime($d))))
            . (($byDay[$d] ?? 0) ? ' <b>' . (int) $byDay[$d] . '</b>' : '') . '</a>';
    }
    if (!isset($byDay[$day]) && $day < $today) $pills .= '<span class="pill on">' . h($title) . '</span>';

    $packing = array_filter($orders, fn($o) => delivery_stage($o) === 'packing');
    $packed = array_filter($orders, fn($o) => delivery_stage($o) === 'packed');
    $out = array_filter($orders, fn($o) => delivery_stage($o) === 'out');
    $done = array_filter($orders, fn($o) => delivery_stage($o) === 'delivered');
    $toCollect = array_sum(array_map(fn($o) => $o['paid'] ? 0 : $o['total'], array_filter($orders, fn($o) => $o['status'] !== 'picked_up')));
    $route = delivery_route_url(array_values(array_filter($orders, fn($o) => $o['status'] !== 'picked_up')));

    $rows = '';
    foreach ($orders as $n => $o) {
        $stage = delivery_stage($o);
        $late = in_array($o['status'], ORDER_OPEN, true) && $o['pickup'] < time();
        $items = '';
        foreach ($o['items'] as $i) {
            $items .= '<li><b>' . (int) ($i['qty'] ?? 0) . '&times;</b> ' . h(order_line_label($i)) . (!empty($i['note']) ? ' <em class="muted">(' . h($i['note']) . ')</em>' : '') . '</li>';
        }
        $hidden = '<input type="hidden" name="id" value="' . h($o['number']) . '"><input type="hidden" name="day" value="' . h($day) . '">';
        $riderForm = form_open(admin_url('deliveries'), 'rider-form no-print') . $hidden . '<input type="hidden" name="action" value="rider">'
            . '<label class="field sm"><span class="sr">Rider</span><input name="rider" value="' . h($o['rider']) . '" maxlength="60" placeholder="Rider’s name" list="riders"></label>'
            . btn_submit('Save', 'sm') . '</form>';
        $acts = '';
        if ($stage === 'packing' || $stage === 'packed') {
            $acts = form_open(admin_url('deliveries'), 'inline') . $hidden . '<input type="hidden" name="action" value="out"><input type="hidden" name="rider" value="' . h($o['rider']) . '">'
                . btn_submit('Out for delivery', 'sm primary', '', '', 'truck', $stage === 'packing' ? 'Order ' . $o['number'] . ' hasn’t been marked ready by the kitchen yet. Send it out anyway?' : '') . '</form>';
        } elseif ($stage === 'out') {
            $acts = form_open(admin_url('deliveries'), 'inline') . $hidden . '<input type="hidden" name="action" value="delivered">'
                . btn_submit('Delivered · collect ' . fmt_money($o['total']), 'sm primary', '', '', 'check') . '</form>'
                . form_open(admin_url('deliveries'), 'inline') . $hidden . '<input type="hidden" name="action" value="undo">' . btn_submit('Back', 'sm ghost') . '</form>';
        } else {
            $acts = form_open(admin_url('deliveries'), 'inline') . $hidden . '<input type="hidden" name="action" value="undo">' . btn_submit('Undo delivered', 'sm ghost', '', '', '', 'Mark ' . $o['number'] . ' as not delivered (and not paid)?') . '</form>';
        }
        $rows .= '<article class="dlv is-' . h($stage) . ($late ? ' is-late' : '') . '">'
            . '<div class="dlv-when"><b class="dlv-stop">' . ($n + 1) . '</b><b class="dlv-time' . ($late ? ' late' : '') . '">' . h(date('g:i A', $o['pickup'])) . '</b>'
            .   ($o['out_at'] ? '<span class="muted small">out ' . h(date('g:i A', $o['out_at'])) . '</span>' : '') . '</div>'
            . '<div class="dlv-main">'
            .   '<div class="dlv-head"><a class="row-link" href="' . h(admin_url('orders', ['id' => $o['number']])) . '"><b>' . h($o['number']) . '</b></a> ' . delivery_stage_badge($o) . ' ' . paid_badge($o) . ($late ? ' ' . badge('Late', 'crit') : '') . '</div>'
            .   '<div class="dlv-who"><b>' . h($o['name']) . '</b>' . ($o['phone'] !== '' ? ' &middot; ' . tel_link($o['phone']) : '') . '</div>'
            .   '<div class="dlv-addr">' . icon('truck', 14) . ' ' . map_link(order_address($o)) . '</div>'
            .   (($o['delivery']['instructions'] ?? '') !== '' ? '<div class="dlv-note">' . icon('chat', 12) . ' ' . h($o['delivery']['instructions']) . '</div>' : '')
            .   '<ul class="dlv-items">' . $items . '</ul>'
            .   ($o['notes'] !== '' ? '<div class="dlv-note">' . icon('chat', 12) . ' ' . h($o['notes']) . '</div>' : '')
            .   ($o['note'] !== '' ? '<div class="dlv-note">' . icon('edit', 12) . ' Team: ' . h($o['note']) . '</div>' : '')
            . '</div>'
            . '<div class="dlv-side">'
            .   '<div class="dlv-total"><b>' . h(fmt_money($o['total'])) . '</b><span class="muted small">' . ($o['paid'] ? 'paid' : 'to collect') . '</span></div>'
            .   '<div class="dlv-rider">' . ($o['rider'] !== '' ? '<span class="chip">' . icon('users', 12) . ' ' . h($o['rider']) . '</span>' : '<span class="muted small no-print">No rider yet</span>') . '</div>'
            .   $riderForm
            .   '<div class="btns no-print">' . $acts
            .     btn_link('Invoice', admin_url('deliveries', ['invoice' => $o['number']]), 'sm', 'print', ' target="_blank" rel="noopener"') . '</div>'
            . '</div></article>';
    }

    $riders = [];
    foreach (orders_all() as $o) if (($o['rider'] ?? '') !== '') $riders[$o['rider']] = true;
    $datalist = '<datalist id="riders">' . implode('', array_map(fn($r) => '<option value="' . h($r) . '">', array_keys($riders))) . '</datalist>';

    $body = page_head('Deliveries', h($title) . ' &middot; ' . h(plural(count($orders), 'delivery', 'deliveries'))
            . ($toCollect ? ' &middot; ' . h(fmt_money($toCollect)) . ' to collect at the door' : ''),
            ($route !== '' ? btn_link('Route in Maps', $route, '', 'external', ' target="_blank" rel="noopener"') : '')
            . '<button class="btn no-print" type="button" data-print>' . icon('print', 16) . '<span>Print run sheet</span></button>')
        . '<div class="daypills no-print">' . $pills . '</div>'
        . '<div class="stats no-print">'
        .   stat_tile('To pack', (string) count($packing), admin_url('prep', ['day' => $day]), 'with the kitchen', 'list')
        .   stat_tile('Ready to go', (string) count($packed), '#', 'packed, waiting for a rider', 'bag')
        .   stat_tile('Out for delivery', (string) count($out), '#', '', 'truck', count($out) ? 'attn' : '')
        .   stat_tile('Delivered', (string) count($done), '#', count($orders) ? count($done) . ' of ' . count($orders) : '', 'check')
        . '</div>'
        . '<div class="print-only run-head"><b>La Sem delivery run sheet</b> &middot; ' . h($title . ', ' . date('F j, Y', strtotime($day))) . ' &middot; ' . h(plural(count($orders), 'stop')) . '</div>'
        . ($rows === ''
            ? empty_state('No deliveries ' . ($day === $today ? 'today' : 'on ' . $title), 'Delivery orders from the order page appear here in time order, with the address and a route for the rider.', 'truck')
            : '<div class="dlv-list">' . $rows . '</div>')
        . $datalist
        . '<p class="muted small no-print">Tip: set the rider, tap <b>Out for delivery</b> when the order leaves, then <b>Delivered</b> at the door (it marks the order paid). Open the invoice for a printable customer, rider and bakery copy.</p>';
    admin_render('Deliveries', $body, 'deliveries');
}

/* ---------- Invoice: customer, rider and bakery copies ---------- */

function admin_invoice(string $num): void
{
    $o = order_find($num);
    if (!$o || $o['type'] === 'dine-in') {
        admin_render('Invoice', '<p class="back"><a href="' . h(admin_url('deliveries')) . '">' . icon('left', 15) . ' Deliveries</a></p>' . empty_state('Invoice not available', 'Invoices are for website pickup and delivery orders.', 'print'), 'deliveries');
    }
    $which = get_choice('copies', ['all', 'customer', 'rider', 'bakery'], 'all');
    $deliv = order_is_delivery($o);
    $copies = $which === 'all' ? ($deliv ? ['customer', 'rider', 'bakery'] : ['customer', 'bakery']) : [$which];

    $rows = '';
    foreach ($o['items'] as $k => $i) {
        $qty = (int) ($i['qty'] ?? 0);
        $unit = (int) ($i['price'] ?? ($qty ? intdiv((int) ($i['total'] ?? 0), $qty) : 0));
        $rows .= '<tr><td class="n">' . ($k + 1) . '</td><td><b>' . h($i['name'] ?? '') . '</b>'
            . (!empty($i['detail']) ? '<div class="inv-sub">' . h($i['detail']) . '</div>' : '')
            . (!empty($i['note']) ? '<div class="inv-sub">Note: ' . h($i['note']) . '</div>' : '')
            . '</td><td class="num">' . $qty . '</td><td class="num">' . h(fmt_money($unit)) . '</td><td class="num">' . h(fmt_money((int) ($i['total'] ?? 0))) . '</td></tr>';
    }
    $totals = '<tr><td colspan="4">Subtotal</td><td class="num">' . h(fmt_money($o['subtotal'])) . '</td></tr>'
        . ($deliv ? '<tr><td colspan="4">Delivery</td><td class="num">' . ($o['fee'] > 0 ? h(fmt_money($o['fee'])) : 'Free') . '</td></tr>' : '')
        . '<tr><td colspan="4">HST (13%)</td><td class="num">' . h(fmt_money($o['tax'])) . '</td></tr>'
        . '<tr class="inv-grand"><td colspan="4">' . ($o['paid'] ? 'Total (paid)' : 'Total due') . '</td><td class="num">' . h(fmt_money($o['total'])) . '</td></tr>';

    $when = ($deliv ? 'Delivery' : 'Pickup') . ': ' . date('l, F j, Y', $o['pickup']) . ' at ' . date('g:i A', $o['pickup']);
    $addr = $deliv ? order_address($o) : SITE['street'] . ', ' . SITE['city'] . ' ' . SITE['region'] . ' ' . SITE['postal'];
    $instr = (string) ($o['delivery']['instructions'] ?? '');
    $pay = $o['paid']
        ? 'Paid' . ($o['paid_at'] !== '' ? ' on ' . date('M j, Y g:i A', strtotime($o['paid_at'])) : '')
        : ($deliv ? 'Pay on delivery: ' . fmt_money($o['total']) . ' (cash or card)' : 'Pay at pickup: ' . fmt_money($o['total']) . ' (cash, debit or credit)');

    $html = '';
    foreach ($copies as $copy) {
        $label = ['customer' => 'Customer copy', 'rider' => 'Rider copy', 'bakery' => 'Bakery copy'][$copy];
        $foot = '';
        if ($copy === 'customer') {
            $foot = '<p>Thank you for ordering from La Sem! ' . ($deliv ? 'Please have payment ready when your order arrives.' : 'Please pay at the counter when you pick up.')
                . ' Questions or changes: call ' . h(SITE['phone']) . ' or email ' . h(SITE['email']) . '.</p>';
        } elseif ($copy === 'rider') {
            $foot = '<div class="inv-sign">'
                . '<div><span>Rider</span><b>' . ($o['rider'] !== '' ? h($o['rider']) : '&nbsp;') . '</b></div>'
                . '<div><span>Left the bakery at</span><b>' . ($o['out_at'] ? h(date('g:i A', $o['out_at'])) : '&nbsp;') . '</b></div>'
                . '<div><span>Delivered at</span><b>&nbsp;</b></div>'
                . '<div><span>Amount collected</span><b>' . ($o['paid'] ? 'Paid' : '$&nbsp;') . '</b></div>'
                . '<div><span>Cash / card</span><b>&nbsp;</b></div>'
                . '<div class="wide"><span>Received by (customer signature)</span><b>&nbsp;</b></div>'
                . '</div>';
        } else {
            $foot = '<div class="inv-sign">'
                . '<div><span>Packed by</span><b>&nbsp;</b></div>'
                . '<div><span>Checked by</span><b>&nbsp;</b></div>'
                . '<div><span>Rider</span><b>' . ($o['rider'] !== '' ? h($o['rider']) : '&nbsp;') . '</b></div>'
                . '<div><span>Payment</span><b>' . ($o['paid'] ? 'Paid' : '☐ cash &nbsp; ☐ card') . '</b></div>'
                . '</div>'
                . ($o['note'] !== '' ? '<p class="inv-team"><b>Team note:</b> ' . h($o['note']) . '</p>' : '');
        }
        $html .= '<section class="inv-copy inv-' . h($copy) . '">'
            . '<header class="inv-head">'
            .   '<div class="inv-brand"><img src="' . h(SITE_BASE) . 'images/logo.png" alt="" height="44"><div><b>' . h(SITE['legal']) . '</b>'
            .     '<span>' . h(SITE['street'] . ', ' . SITE['city'] . ' ' . SITE['region'] . ' ' . SITE['postal']) . '</span><span>' . h(SITE['phone']) . ' &middot; ' . h(SITE['email']) . ' &middot; ' . h(preg_replace('#^https?://#', '', SITE['url'])) . '</span></div></div>'
            .   '<div class="inv-meta"><b>Invoice</b><span class="inv-num">' . h($o['number']) . '</span><span>Placed ' . h(date('M j, Y g:i A', $o['ts'])) . '</span><span class="inv-tag">' . h($label) . '</span></div>'
            . '</header>'
            . '<div class="inv-grid">'
            .   '<div><h4>' . ($deliv ? 'Bill to' : 'Customer') . '</h4><b>' . h($o['name']) . '</b>' . ($o['phone'] !== '' ? '<span>' . h($o['phone']) . '</span>' : '') . ($o['email'] !== '' ? '<span>' . h($o['email']) . '</span>' : '') . '</div>'
            .   '<div><h4>' . ($deliv ? 'Deliver to' : 'Pick up at') . '</h4><b>' . h($addr) . '</b><span>' . h($when) . '</span>' . ($instr !== '' ? '<span>Instructions: ' . h($instr) . '</span>' : '') . '</div>'
            .   '<div><h4>Payment</h4><b>' . h($pay) . '</b><span>' . ($deliv ? 'Delivery order' : 'Pickup order') . ' &middot; ' . h(order_status_label($o)) . '</span></div>'
            . '</div>'
            . '<table class="inv-table"><thead><tr><th class="n">#</th><th>Item</th><th class="num">Qty</th><th class="num">Unit</th><th class="num">Total</th></tr></thead>'
            .   '<tbody>' . $rows . '</tbody><tfoot>' . $totals . '</tfoot></table>'
            . ($o['notes'] !== '' ? '<p class="inv-notes"><b>Customer note:</b> ' . h($o['notes']) . '</p>' : '')
            . '<footer class="inv-foot">' . $foot . '</footer>'
            . '</section>';
    }

    $pick = '';
    foreach (['all' => 'All copies', 'customer' => 'Customer', 'rider' => 'Rider', 'bakery' => 'Bakery'] as $k => $l) {
        if ($k === 'rider' && !$deliv) continue;
        $pick .= '<a class="pill' . ($k === $which ? ' on' : '') . '" href="' . h(admin_url('deliveries', ['invoice' => $num, 'copies' => $k])) . '">' . h($l) . '</a>';
    }
    $bar = '<div class="inv-bar no-print">'
        . '<a class="btn sm" href="' . h(admin_url($deliv ? 'deliveries' : 'orders', $deliv ? ['day' => $o['day']] : ['id' => $num])) . '">' . icon('left', 15) . '<span>Back</span></a>'
        . '<div class="daypills">' . $pick . '</div>'
        . '<button class="btn primary" type="button" data-print>' . icon('print', 16) . '<span>Print</span></button>'
        . '</div>';

    header('Content-Type: text/html; charset=utf-8');
    echo admin_head('Invoice ' . $num . ' · La Sem Admin')
        . '<body class="invoice-page"><main class="invoice">' . $bar . $html . '</main>'
        . '<script src="' . admin_asset('print.js') . '" defer></script></body></html>';
    exit;
}
