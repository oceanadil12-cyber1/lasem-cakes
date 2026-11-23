<?php
/**
 * Orders from the website: pickup orders (/order/) and table orders (/dine-in/, with a
 * table and the day's priority number). Payment is taken at the counter.
 * The order itself (items, prices, customer) is saved by api/order.php and never
 * changed here; the team adds the status, payment and a note to the same file.
 */
if (!defined('ADMIN')) { http_response_code(403); exit('Forbidden'); }

/** The list's filters from a query string or a form, for links and for coming back after a click. */
function orders_filters(array $src): array
{
    $out = [];
    foreach (['s', 'q', 'pg'] as $k) {
        if (isset($src[$k]) && is_string($src[$k]) && $src[$k] !== '') $out[$k] = mb_substr($src[$k], 0, 80);
    }
    return $out;
}

/** [next status, button label] for the one-tap button on an open order. */
function order_next(array $o): ?array
{
    if ($o['type'] === 'dine-in') { // table orders are for right now: no confirming step
        switch ($o['status']) {
            case 'new':
            case 'confirmed': return ['preparing', 'Start'];
            case 'preparing': return ['ready', 'Mark ready'];
            case 'ready':     return ['picked_up', order_status_label(['type' => 'dine-in', 'status' => 'picked_up'])];
        }
        return null;
    }
    switch ($o['status']) {
        case 'new':       return ['confirmed', 'Confirm'];
        case 'confirmed': return ['preparing', 'Start'];
        case 'preparing': return ['ready', 'Mark ready'];
        case 'ready':     return ['picked_up', order_is_delivery($o) ? 'Delivered' : 'Picked up'];
    }
    return null;
}

/** "Today, 2:30 PM" / "Tomorrow, 9:00 AM" / "Sat Nov 1, 11:15 AM" for a pickup time. */
function pickup_label(int $ts): string
{
    if (!$ts) return '';
    $d = date('Y-m-d', $ts);
    $day = $d === date('Y-m-d') ? 'Today' : ($d === date('Y-m-d', strtotime('+1 day')) ? 'Tomorrow' : ($d === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday' : date('D M j', $ts)));
    return $day . ', ' . date('g:i A', $ts);
}

function paid_badge(array $o): string
{
    if ($o['status'] === 'cancelled') return '';
    return $o['paid'] ? badge('Paid', 'good') : badge($o['type'] === 'dine-in' ? 'Not paid yet' : (order_is_delivery($o) ? 'Pay on delivery' : 'Pay at pickup'), 'warn');
}

/** A website order going out by car rather than collected at the counter. */
function order_is_delivery(array $o): bool
{
    return ($o['fulfilment'] ?? '') === 'delivery';
}

/** "12 Main St, Mississauga L4W 2Z2" for a delivery order. */
function order_address(array $o): string
{
    $a = (array) ($o['delivery'] ?? []);
    if (!$a) return '';
    return trim((($a['unit'] ?? '') !== '' ? $a['unit'] . ' – ' : '') . ($a['street'] ?? '') . ', ' . ($a['city'] ?? '') . ' ' . ($a['postal'] ?? ''));
}

/** When / where: "Today, 2:30 PM" for a pickup, "Table 5 · #27" for a table order. */
function order_when(array $o): string
{
    return $o['type'] === 'dine-in' ? $o['table'] . ' · #' . $o['queue'] : pickup_label($o['pickup']);
}

/** Late? A pickup past its time, or a table order waiting longer than the set minutes. */
function order_late(array $o): bool
{
    if (!in_array($o['status'], ORDER_OPEN, true)) return false;
    if ($o['type'] === 'dine-in') return $o['status'] !== 'ready' && time() - $o['ts'] > dinein_settings()['lateMins'] * 60;
    return $o['pickup'] < time();
}

function order_type_chip(array $o): string
{
    if ($o['type'] === 'dine-in') return '<span class="chip">' . icon('users', 13) . ' Table</span>';
    return order_is_delivery($o)
        ? '<span class="chip gold">' . icon('truck', 13) . ' Delivery</span>'
        : '<span class="chip">' . icon('bag', 13) . ' Pickup</span>';
}

/** Other orders from the same phone number or email, newest first. */
function order_repeat_orders(array $o): array
{
    $digits = substr(preg_replace('/\D/', '', $o['phone']), -10);
    return array_values(array_filter(orders_all(), function ($x) use ($o, $digits) {
        if ($x['number'] === $o['number']) return false;
        $samePhone = strlen($digits) === 10 && substr(preg_replace('/\D/', '', $x['phone']), -10) === $digits;
        return $samePhone || ($o['email'] !== '' && strcasecmp($x['email'], $o['email']) === 0);
    }));
}

function admin_page_orders(): void
{
    if (is_post()) admin_orders_post();
    $num = strtoupper(get_str('id', 20));
    if ($num !== '') admin_order_detail($num); // ends the request
    admin_orders_list();
}

/** Change an order's status, payment or note, then back to the list (with its filters) or the order. */
function admin_orders_post(): void
{
    require_csrf();
    parse_str((string) ($_POST['back'] ?? ''), $b);
    $filters = orders_filters(is_array($b) ? $b : []);
    $num = strtoupper(post_str('id', 20));
    $o = order_find($num);
    if (!$o) {
        flash('That order wasn\'t found.', 'err');
        redirect(admin_url('orders', $filters));
    }
    $from = (string) ($_POST['from'] ?? '');
    $back = $from === 'list' ? admin_url('orders', $filters) : ($from === 'dashboard' ? admin_url() : ($from === 'prep' ? admin_url('prep', ['day' => $o['day']]) : admin_url('orders', ['id' => $num])));
    switch ($_POST['action'] ?? '') {
        case 'status':
            $status = (string) ($_POST['status'] ?? '');
            if (isset(ORDER_STATUSES[$status]) && order_set_status($num, $status)) {
                admin_log('order.status', $num . ' (' . $o['name'] . '): ' . strtolower(ORDER_STATUSES[$status][0]));
                flash('Order ' . $num . ' marked ' . strtolower(ORDER_STATUSES[$status][0]) . '.');
                // Collecting the order at the counter means it was paid.
                if ($status === 'picked_up' && !$o['paid'] && !empty($_POST['paid'])) order_set($num, ['paid' => true]);
            }
            break;
        case 'paid':
            $paid = !empty($_POST['paid']);
            order_set($num, ['paid' => $paid]);
            admin_log('order.paid', $num . ': ' . ($paid ? 'paid' : 'not paid'));
            flash('Order ' . $num . ($paid ? ' marked paid.' : ' marked not paid.'));
            break;
        case 'note':
            order_set($num, ['team_note' => post_str('note', 1000)]);
            admin_log('order.note', $num);
            flash('Note saved.');
            break;
    }
    redirect($back);
}

/** The one-tap "next step" button for a row (list, dashboard, prep list). */
function order_next_form(array $o, string $from, string $back = ''): string
{
    $next = order_next($o);
    if (!$next) return '';
    return form_open(admin_url('orders'), 'inline')
        . '<input type="hidden" name="id" value="' . h($o['number']) . '"><input type="hidden" name="action" value="status">'
        . '<input type="hidden" name="status" value="' . h($next[0]) . '"><input type="hidden" name="from" value="' . h($from) . '">'
        . ($next[0] === 'picked_up' ? '<input type="hidden" name="paid" value="1">' : '')
        . ($back !== '' ? '<input type="hidden" name="back" value="' . h($back) . '">' : '')
        . btn_submit($next[1], 'sm primary') . '</form>';
}

function admin_order_detail(string $num): void
{
    $o = order_find($num);
    if (!$o) {
        admin_render('Order', '<p class="back"><a href="' . h(admin_url('orders')) . '">' . icon('left', 15) . ' Orders</a></p>' . empty_state('Order not found', 'Check the order number.', 'bag'), 'orders');
    }

    $rows = '';
    foreach ($o['items'] as $i) {
        $rows .= '<tr><td class="qty">' . (int) ($i['qty'] ?? 0) . '&times;</td><td><b>' . h($i['name'] ?? '') . '</b>'
            . (!empty($i['detail']) ? '<div class="cell-sub">' . h($i['detail']) . '</div>' : '')
            . (!empty($i['note']) ? '<div class="cell-note">' . icon('edit', 12) . ' ' . h($i['note']) . '</div>' : '')
            . '</td><td class="num">' . h(fmt_money((int) ($i['total'] ?? 0))) . '</td></tr>';
    }
    $totals = '<tr class="sum"><td colspan="2">Subtotal</td><td class="num">' . h(fmt_money($o['subtotal'])) . '</td></tr>'
        . (order_is_delivery($o) ? '<tr class="sum"><td colspan="2">Delivery</td><td class="num">' . ($o['fee'] > 0 ? h(fmt_money($o['fee'])) : 'Free') . '</td></tr>' : '')
        . '<tr class="sum"><td colspan="2">HST</td><td class="num">' . h(fmt_money($o['tax'])) . '</td></tr>'
        . '<tr class="sum grand"><td colspan="2">Total</td><td class="num">' . h(fmt_money($o['total'])) . '</td></tr>';

    $next = order_next($o);
    $buttons = '';
    foreach (ORDER_STATUSES as $key => [$label]) {
        if ($key === $o['status']) continue;
        $cls = 'sm' . ($next && $next[0] === $key ? ' primary' : ($key === 'cancelled' ? ' danger' : ''));
        $text = $next && $next[0] === $key ? $next[1] : ($key === 'new' ? 'Back to new' : ($key === 'cancelled' ? 'Cancel order' : 'Mark ' . strtolower(order_status_label(['type' => $o['type'], 'fulfilment' => $o['fulfilment'], 'status' => $key]))));
        $buttons .= btn_submit($text, $cls, 'status', $key, '', $key === 'cancelled' ? 'Cancel order ' . $num . '? Call the customer to let them know.' : '');
    }
    $timeline = '<li><b>Placed online</b><span>' . h(fmt_when($o['ts'])) . '</span></li>';
    foreach ($o['history'] as $step) {
        if (!isset($step['s'], $step['t'], ORDER_STATUSES[$step['s']])) continue;
        $timeline .= '<li><b>' . h(order_status_label(['type' => $o['type'], 'fulfilment' => $o['fulfilment'], 'status' => $step['s']])) . '</b><span>' . h(fmt_when((string) $step['t'])) . '</span></li>';
    }

    $repeat = order_repeat_orders($o);
    $repeatHtml = '';
    foreach (array_slice($repeat, 0, 6) as $x) {
        $repeatHtml .= '<li><a href="' . h(admin_url('orders', ['id' => $x['number']])) . '">' . h($x['number']) . '</a><span>' . h(date('M j, Y', $x['pickup'])) . ' &middot; ' . h(fmt_money($x['total'])) . '</span></li>';
    }

    $late = order_late($o);
    $dine = $o['type'] === 'dine-in';
    $deliv = order_is_delivery($o);
    $payForm = form_open(admin_url('orders'), 'no-print') . '<input type="hidden" name="id" value="' . h($num) . '"><input type="hidden" name="action" value="paid">'
        . ($o['paid']
            ? '<input type="hidden" name="paid" value="0">' . btn_submit('Undo: not paid', 'sm ghost')
            : '<input type="hidden" name="paid" value="1">' . btn_submit('Mark paid', 'sm primary', '', '', 'check'))
        . '</form>';

    $body = '<p class="back no-print"><a href="' . h(admin_url('orders')) . '">' . icon('left', 15) . ' Orders</a></p>'
        . page_head($dine ? 'Table order #' . $o['queue'] : 'Order ' . $o['number'],
            ($dine ? '<b>' . h($o['table']) . '</b> &middot; ' . h($o['number']) . ($late ? ' ' . badge('Waiting a while', 'crit') : '')
                : ($deliv ? 'Delivery' : 'Pickup') . ' <b>' . h(pickup_label($o['pickup'])) . '</b>' . ($late ? ' ' . badge($deliv ? 'Past delivery time' : 'Past pickup time', 'crit') : '')) . ' &middot; placed ' . h(fmt_ago($o['ts'])),
            order_type_chip($o) . ' ' . order_status_badge($o) . ' ' . paid_badge($o)
            . (!$dine ? btn_link('Invoice', admin_url('deliveries', ['invoice' => $num]), 'sm no-print', 'print', ' target="_blank" rel="noopener"') : '')
            . ($deliv ? btn_link('Deliveries', admin_url('deliveries', ['day' => $o['day']]), 'sm no-print', 'truck') : '')
            . '<button class="btn sm no-print" type="button" data-print>' . icon('print', 16) . '<span>Print ticket</span></button>')
        . '<div class="split"><div class="stack">'
        .   card('Items', '<div class="table flush"><table class="tbl items"><tbody>' . $rows . '</tbody><tfoot>' . $totals . '</tfoot></table></div>')
        .   ($o['notes'] !== '' ? card('Customer\'s note', '<p class="callout-note">' . nl2br(h($o['notes'])) . '</p>') : '')
        .   card('Note for the team', form_open(admin_url('orders'), 'no-print')
                . '<input type="hidden" name="id" value="' . h($num) . '"><input type="hidden" name="action" value="note">'
                . '<label class="field wide"><span class="sr">Note</span><textarea name="note" rows="3" maxlength="1000" placeholder="Only the team sees this: called the customer, writing on the cake, allergies…">' . h($o['note']) . '</textarea></label>'
                . '<div class="bar">' . btn_submit('Save note', 'primary') . '</div></form>'
                . ($o['note'] !== '' ? '<div class="print-only">' . nl2br(h($o['note'])) . '</div>' : ''), '', 'no-print-empty')
        . '</div><div class="stack">'
        .   card($dine ? 'Table' : 'Customer', kv($dine ? [
                ['Table', '<b>' . h($o['table']) . '</b>'],
                ['Number', '<b>#' . (int) $o['queue'] . '</b> (today\'s priority number)'],
                ['Name', h($o['name'])],
                ['Ordered on', h($o['device'] === 'table tablet' ? 'The table\'s tablet' : 'Their phone (scanned the QR code)')],
                ['Time', h(date('l, F j, g:i A', $o['ts']))],
            ] : array_merge([
                ['Name', '<b>' . h($o['name']) . '</b>'],
                ['Phone', $o['phone'] !== '' ? tel_link($o['phone']) : ''],
                ['Email', $o['email'] !== '' ? mail_link($o['email']) : ''],
                [$deliv ? 'Deliver' : 'Pickup', h(date('l, F j, Y', $o['pickup'])) . '<br><b>' . h(date('g:i A', $o['pickup'])) . '</b>'],
            ], $deliv ? [
                ['Address', '<b>' . h(order_address($o)) . '</b><br><a href="https://www.google.com/maps/search/?api=1&query=' . rawurlencode(order_address($o)) . '" target="_blank" rel="noopener">Open in Maps</a>'
                    . (($o['delivery']['instructions'] ?? '') !== '' ? '<div class="cell-note">' . icon('chat', 12) . ' ' . h($o['delivery']['instructions']) . '</div>' : '')],
            ] : [])))
        .   card('Payment', '<p class="pay"><b>' . ($o['paid'] ? 'Paid' : ($dine ? 'Not paid yet' : ($deliv ? 'Pay on delivery' : 'Pay at pickup'))) . '</b></p>'
                . ($o['paid']
                    ? '<p class="muted">' . h(fmt_money($o['total'])) . ' collected' . ($o['paid_at'] !== '' ? ' ' . h(fmt_when($o['paid_at'])) : '') . '.</p>'
                    : '<p class="muted">Collect <b class="cash">' . h(fmt_money($o['total'])) . '</b> (HST' . ($deliv && $o['fee'] > 0 ? ' and ' . h(fmt_money($o['fee'])) . ' delivery' : '') . ' included) ' . ($deliv ? 'at the door' : 'at the counter') . '. Nothing was charged online.</p>')
                . $payForm)
        .   card('Status', form_open(admin_url('orders'), 'no-print')
                . '<input type="hidden" name="id" value="' . h($num) . '"><input type="hidden" name="action" value="status">'
                . ($next && $next[0] === 'picked_up' && !$o['paid'] ? '<input type="hidden" name="paid" value="1">' : '')
                . '<div class="btns">' . $buttons . '</div></form><ol class="timeline">' . $timeline . '</ol>')
        .   ($repeatHtml !== '' ? card('More from this customer', '<ul class="linklist">' . $repeatHtml . '</ul>'
                . (count($repeat) > 6 ? '<p class="muted small">and ' . (count($repeat) - 6) . ' more</p>' : ''), '', 'no-print') : '')
        . '</div></div>';
    admin_render('Order ' . $num, $body, 'orders');
}

function admin_orders_list(): void
{
    $today = date('Y-m-d');
    $views = [
        'today'     => ['Today',     fn($o) => $o['day'] === $today && $o['status'] !== 'cancelled'],
        'tables'    => ['Table orders', fn($o) => $o['type'] === 'dine-in' && $o['day'] === $today && $o['status'] !== 'cancelled'],
        'upcoming'  => ['Upcoming',  fn($o) => $o['day'] >= $today && in_array($o['status'], ORDER_OPEN, true)],
        'new'       => ['To confirm', fn($o) => $o['status'] === 'new' && $o['type'] === 'pickup'],
        'ready'     => ['Ready',     fn($o) => $o['status'] === 'ready'],
        'picked_up' => ['Picked up / served', fn($o) => $o['status'] === 'picked_up'],
        'cancelled' => ['Cancelled', fn($o) => $o['status'] === 'cancelled'],
        'all'       => ['All',       fn($o) => true],
    ];
    $view = get_choice('s', array_keys($views), 'upcoming');
    $q = get_str('q', 80);

    $all = orders_all();
    $match = fn($o) => $q === '' || stripos($o['number'] . ' ' . $o['table'] . ' ' . $o['name'] . ' ' . $o['phone'] . ' ' . $o['email'] . ' ' . $o['notes'] . ' ' . implode(' ', array_map('order_line_label', $o['items'])), $q) !== false;
    $matched = array_values(array_filter($all, $match));
    $shown = array_values(array_filter($matched, $views[$view][1]));
    // Open orders read best soonest pickup first; finished ones newest first.
    if (in_array($view, ['today', 'tables', 'upcoming', 'new', 'ready'], true)) usort($shown, fn($a, $b) => $a['pickup'] <=> $b['pickup']);

    $filters = orders_filters($_GET);
    unset($filters['pg']);

    if (isset($_GET['csv'])) {
        csv_download('lasem-orders', ['order', 'type', 'table', 'number', 'placed', 'pickup', 'address', 'status', 'paid', 'name', 'phone', 'email', 'items', 'customer note', 'subtotal', 'delivery', 'hst', 'total', 'team note'],
            array_map(fn($o) => [$o['number'], $o['type'] === 'dine-in' ? 'dine-in' : (order_is_delivery($o) ? 'delivery' : 'pickup'), $o['table'], $o['queue'] ?: '', date('Y-m-d H:i', $o['ts']), date('Y-m-d H:i', $o['pickup']), order_address($o), $o['status'], $o['paid'] ? 'yes' : 'no', $o['name'], $o['phone'], $o['email'],
                implode(' | ', array_map(fn($i) => ($i['qty'] ?? 0) . 'x ' . order_line_label($i), $o['items'])), $o['notes'],
                number_format($o['subtotal'] / 100, 2, '.', ''), number_format($o['fee'] / 100, 2, '.', ''), number_format($o['tax'] / 100, 2, '.', ''), number_format($o['total'] / 100, 2, '.', ''), $o['note']], $shown));
    }

    // Numbers at a glance.
    $todayOpen = array_filter($all, fn($o) => $o['type'] === 'pickup' && $o['day'] === $today && in_array($o['status'], ORDER_OPEN, true));
    $toConfirm = count(array_filter($all, fn($o) => $o['status'] === 'new' && $o['type'] === 'pickup'));
    $tablesToday = count(array_filter($all, fn($o) => $o['type'] === 'dine-in' && $o['day'] === $today && $o['status'] !== 'cancelled'));
    $since = strtotime('-29 days 00:00');
    $sold = array_filter($all, fn($o) => $o['status'] !== 'cancelled' && $o['ts'] >= $since);
    $sales = array_sum(array_column($sold, 'total'));
    $toCollect = array_sum(array_map(fn($o) => $o['paid'] ? 0 : $o['total'], $todayOpen));

    $tabs = [];
    foreach ($views as $key => [$label, $fn]) {
        $tabs[] = ['label' => $label, 'href' => admin_url('orders', array_diff_key($filters, ['s' => 1]) + ($key === 'upcoming' ? [] : ['s' => $key])), 'on' => $key === $view,
            'count' => count(array_filter($matched, $fn))];
    }
    [$pageRows, $pager] = paginate($shown, 50, 'orders', $filters);

    $back = http_build_query($filters + (isset($_GET['pg']) ? ['pg' => get_str('pg', 6)] : []));
    $rows = '';
    foreach ($pageRows as $o) {
        $first = $o['items'][0] ?? null;
        $late = order_late($o);
        $rows .= '<tr class="order-' . h($o['status']) . '">'
            . '<td class="nowrap" data-label="Pickup"><b' . ($late ? ' class="late"' : '') . '>' . h(order_when($o)) . '</b>' . ($o['type'] === 'dine-in' || order_is_delivery($o) ? '<div class="cell-sub">' . order_type_chip($o) . '</div>' : '') . ($late ? '<div class="cell-sub">' . badge('Late', 'crit') . '</div>' : '') . '</td>'
            . '<td class="nowrap" data-label="Order"><a class="row-link" href="' . h(admin_url('orders', ['id' => $o['number']])) . '"><b>' . h($o['number']) . '</b></a><div class="cell-sub">placed ' . h(fmt_ago($o['ts'])) . '</div></td>'
            . '<td data-label="Customer">' . guest_cell($o['name'] !== '' ? $o['name'] : ($o['type'] === 'dine-in' ? $o['table'] : ''), $o['phone']) . '</td>'
            . '<td class="q" data-label="Items">' . ($first ? h(($first['qty'] ?? 0) . '× ' . snip(order_line_label($first), 60)) . (count($o['items']) > 1 ? '<div class="cell-sub">+ ' . plural(count($o['items']) - 1, 'more item') . '</div>' : '') : '<span class="muted">&ndash;</span>')
            . ($o['notes'] !== '' ? '<div class="cell-sub">' . icon('chat', 12) . ' ' . h(snip($o['notes'], 50)) . '</div>' : '')
            . ($o['note'] !== '' ? '<div class="cell-sub">' . icon('edit', 12) . ' ' . h(snip($o['note'], 50)) . '</div>' : '') . '</td>'
            . '<td class="num" data-label="Total">' . h(fmt_money($o['total'])) . '<div class="cell-sub">' . paid_badge($o) . '</div></td>'
            . '<td data-label="Status">' . order_status_badge($o) . '</td>'
            . '<td class="act">' . order_next_form($o, 'list', $back) . '</td></tr>';
    }

    $empty = $q !== '' ? 'No matching orders' : ($view === 'today' ? 'No pickups today' : ($view === 'upcoming' ? 'No upcoming pickups' : 'No orders here'));
    $body = page_head('Orders', 'Pickup orders from the website\'s <a href="' . h(SITE_BASE) . 'order/" target="_blank" rel="noopener">order page</a> and table orders from the QR codes. Customers pay at the counter.',
            btn_link('Prep list', admin_url('prep'), 'sm', 'list') . btn_link('Download spreadsheet', admin_url('orders', $filters + ['csv' => 1]), 'sm', 'download'))
        . '<div class="stats">'
        . stat_tile('Pickups today', (string) count($todayOpen), admin_url('orders', ['s' => 'today']), $toCollect ? h(fmt_money($toCollect)) . ' to collect' : '', 'clock')
        . stat_tile('Table orders today', (string) $tablesToday, admin_url('orders', ['s' => 'tables']), '', 'users')
        . stat_tile('To confirm', (string) $toConfirm, admin_url('orders', ['s' => 'new']), $toConfirm ? 'Call or confirm these' : 'All caught up', 'bell', $toConfirm ? 'warn' : '')
        . stat_tile('Orders, last 30 days', (string) count($sold), admin_url('orders', ['s' => 'all']), '', 'bag')
        . stat_tile('Sales, last 30 days', fmt_money($sales), admin_url('orders', ['s' => 'all']), $sold ? 'Average ' . h(fmt_money((int) round($sales / count($sold)))) : '', 'dollar')
        . '</div>'
        . '<div class="toolbar">' . tabs_html($tabs) . '<span class="grow"></span>' . search_form('orders', array_diff_key($filters, ['q' => 1]), $q, 'Search name, phone, item…') . '</div>'
        . ($rows === ''
            ? empty_state($empty, $q !== '' ? '' : 'Orders placed on the website appear here as soon as they come in.', 'bag')
            : '<div class="table stack"><table class="tbl orders stack"><thead><tr><th>Pickup</th><th>Order</th><th>Customer</th><th>Items</th><th class="num">Total</th><th>Status</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table></div>' . $pager);
    admin_render('Orders', $body, 'orders');
}
