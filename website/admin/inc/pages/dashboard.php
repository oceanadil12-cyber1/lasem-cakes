<?php
/** Dashboard: today's pickups, what needs an answer, and how the week is going. */
if (!defined('ADMIN')) { http_response_code(403); exit('Forbidden'); }

require_once __DIR__ . '/orders.php'; // pickup_label(), paid_badge(), order_next_form()

function admin_page_dashboard(): void
{
    $today = date('Y-m-d');
    $all = orders_all();
    $todays = array_values(array_filter($all, fn($o) => $o['type'] === 'pickup' && $o['day'] === $today && $o['status'] !== 'cancelled'));
    $tables = array_values(array_filter($all, fn($o) => $o['type'] === 'dine-in' && $o['day'] === $today && $o['status'] !== 'cancelled'));
    $tablesOpen = count(array_filter($tables, fn($o) => in_array($o['status'], ORDER_OPEN, true)));
    usort($todays, fn($a, $b) => $a['pickup'] <=> $b['pickup']);
    $open = array_filter($todays, fn($o) => in_array($o['status'], ORDER_OPEN, true));
    $toConfirm = array_values(array_filter($all, fn($o) => $o['status'] === 'new' && $o['type'] === 'pickup'));
    $enquiries = enquiries_all();
    $newEnq = array_values(array_filter($enquiries, fn($e) => $e['status'] === 'new'));

    $weekStart = strtotime('-6 days 00:00');
    $week = array_filter($all, fn($o) => $o['status'] !== 'cancelled' && $o['ts'] >= $weekStart);
    $weekSales = array_sum(array_column($week, 'total'));
    $toCollect = array_sum(array_map(fn($o) => $o['paid'] ? 0 : $o['total'], $open));

    // Sales per day for the last 14 days (by the day the order was placed).
    $points = [];
    for ($i = 13; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $points[] = ['label' => date('j', strtotime($d)), 'tip' => date('D M j', strtotime($d)), 'value' => 0];
    }
    $idx = array_flip(array_map(fn($i) => date('Y-m-d', strtotime("-$i days")), range(13, 0)));
    foreach ($all as $o) {
        $d = date('Y-m-d', $o['ts']);
        if ($o['status'] !== 'cancelled' && isset($idx[$d])) $points[$idx[$d]]['value'] += $o['total'] / 100;
    }

    // Today's pickups.
    $rows = '';
    foreach ($todays as $o) {
        $late = in_array($o['status'], ORDER_OPEN, true) && $o['pickup'] < time();
        $rows .= '<tr><td class="nowrap" data-label="Pickup"><b' . ($late ? ' class="late"' : '') . '>' . h(date('g:i A', $o['pickup'])) . '</b></td>'
            . '<td data-label="Order"><a class="row-link" href="' . h(admin_url('orders', ['id' => $o['number']])) . '"><b>' . h($o['name']) . '</b></a><div class="cell-sub">' . h($o['number']) . ' &middot; ' . h(plural(array_sum(array_map(fn($i) => (int) ($i['qty'] ?? 0), $o['items'])), 'item')) . '</div></td>'
            . '<td class="num" data-label="Total">' . h(fmt_money($o['total'])) . '<div class="cell-sub">' . paid_badge($o) . '</div></td>'
            . '<td data-label="Status">' . order_status_badge($o) . '</td>'
            . '<td class="act">' . order_next_form($o, 'dashboard') . '</td></tr>';
    }
    $pickups = card('Today\'s pickups', $rows === ''
            ? empty_state('No pickups today', 'Orders for today appear here as they come in.', 'bag')
            : '<div class="table flush stack"><table class="tbl compact stack"><tbody>' . $rows . '</tbody></table></div>',
        btn_link('Prep list', admin_url('prep'), 'sm ghost', 'list'));

    // Coming up: the next 7 days.
    $up = '';
    for ($i = 1; $i <= 7; $i++) {
        $d = date('Y-m-d', strtotime("+$i days"));
        $n = count(array_filter($all, fn($o) => $o['type'] === 'pickup' && $o['day'] === $d && in_array($o['status'], ORDER_OPEN, true)));
        if ($n) $up .= '<li><a href="' . h(admin_url('prep', ['day' => $d])) . '">' . h($i === 1 ? 'Tomorrow' : date('l, M j', strtotime($d))) . '</a><span>' . h(plural($n, 'pickup')) . '</span></li>';
    }
    $coming = card('Coming up', $up === '' ? '<p class="muted">No pickups booked for the next 7 days yet.</p>' : '<ul class="linklist">' . $up . '</ul>');

    // Enquiries waiting.
    $enq = '';
    foreach (array_slice($newEnq ?: $enquiries, 0, 5) as $e) {
        $enq .= '<li><a href="' . h(admin_url('enquiries', ['id' => $e['id']])) . '">' . h($e['name'] !== '' ? $e['name'] : 'No name') . '</a>'
            . '<span>' . h($e['occasion'] !== '' ? snip($e['occasion'], 30) : snip($e['message'], 30)) . ' &middot; ' . h(fmt_ago($e['ts'])) . '</span></li>';
    }
    $enqCard = card($newEnq ? 'New cake enquiries' : 'Latest cake enquiries', $enq === '' ? '<p class="muted">No enquiries yet. They arrive from the contact page.</p>' : '<ul class="linklist">' . $enq . '</ul>',
        btn_link('All', admin_url('enquiries'), 'sm ghost'));

    // Recent changes.
    $act = '';
    foreach (array_slice(admin_activity(8), 0, 8) as $a) {
        [$label, $tone] = admin_action_label($a['action']);
        $act .= '<li><span>' . badge($label, $tone) . ($a['detail'] !== '' ? ' <span class="muted small">' . h(snip($a['detail'], 40)) . '</span>' : '') . '</span><span>' . h(fmt_ago((int) strtotime($a['time']))) . '</span></li>';
    }

    $closed = !menu_ordering_open();
    $hello = (int) date('G') < 12 ? 'Good morning' : ((int) date('G') < 17 ? 'Good afternoon' : 'Good evening');
    $body = page_head($hello, h(date('l, F j')) . ' &middot; La Sem Cakes')
        . ($closed ? '<div class="callout">' . icon('alert', 18) . '<div><b>Online ordering is switched off</b><p class="muted">Customers can browse the menu but can\'t place pickup orders.</p></div>'
            . '<div class="callout-act">' . btn_link('Turn it on', admin_url('settings') . '#ordering', 'sm primary') . '</div></div>' : '')
        . '<div class="stats">'
        . stat_tile('Pickups today', (string) count($open), admin_url('orders', ['s' => 'today']), $toCollect ? h(fmt_money($toCollect)) . ' to collect' : (count($todays) ? 'All done' : ''), 'clock')
        . stat_tile('Table orders today', (string) count($tables), admin_url('orders', ['s' => 'tables']), $tablesOpen ? h(plural($tablesOpen, 'open order')) : '', 'users')
        . stat_tile('Orders to confirm', (string) count($toConfirm), admin_url('orders', ['s' => 'new']), '', 'bell', $toConfirm ? 'warn' : '')
        . stat_tile('New enquiries', (string) count($newEnq), admin_url('enquiries', ['s' => 'new']), '', 'mail', $newEnq ? 'warn' : '')
        . stat_tile('Sales, last 7 days', fmt_money($weekSales), admin_url('orders', ['s' => 'all']), h(plural(count($week), 'order')), 'dollar')
        . '</div>'
        . '<div class="split"><div class="stack">' . $pickups . column_chart('Sales by day', 'Pickup and table orders in dollars, last 14 days', $points, 'dollar') . '</div>'
        . '<div class="stack">' . $coming . $enqCard
        . card('Recent changes', $act === '' ? '<p class="muted">Nothing yet.</p>' : '<ul class="linklist">' . $act . '</ul>', btn_link('All', admin_url('activity'), 'sm ghost'))
        . '</div></div>';
    admin_render('Dashboard', $body, 'dashboard');
}
