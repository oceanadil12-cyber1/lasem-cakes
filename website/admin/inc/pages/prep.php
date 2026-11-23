<?php
/**
 * Prep list: everything to make for one day's pickups, added up across orders
 * (how many cannoli, which cake sizes and flavours), then each order in pickup
 * order for packing. Prints on one or two pages.
 */
if (!defined('ADMIN')) { http_response_code(403); exit('Forbidden'); }

require_once __DIR__ . '/orders.php'; // paid_badge(), order_next_form()

function admin_page_prep(): void
{
    $day = get_str('day', 10);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) || !strtotime($day)) $day = date('Y-m-d');

    // Pickups only: table orders are made on the spot and shown on the kitchen display.
    $orders = array_values(array_filter(orders_all(), fn($o) => $o['type'] === 'pickup' && $o['day'] === $day && $o['status'] !== 'cancelled'));
    usort($orders, fn($a, $b) => $a['pickup'] <=> $b['pickup']);

    // Day pills: today and the next 6 days, with how many pickups each has.
    $byDay = [];
    foreach (orders_all() as $o) if ($o['status'] !== 'cancelled' && $o['type'] === 'pickup') $byDay[$o['day']] = ($byDay[$o['day']] ?? 0) + 1;
    $tabs = [];
    for ($i = -1; $i <= 6; $i++) {
        $d = date('Y-m-d', strtotime(($i >= 0 ? '+' : '') . $i . ' days'));
        $label = $i === -1 ? 'Yesterday' : ($i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : date('D M j', strtotime($d))));
        $tabs[] = ['label' => $label, 'href' => admin_url('prep', $i === 0 ? [] : ['day' => $d]), 'on' => $d === $day, 'count' => $byDay[$d] ?? 0];
    }
    $picker = '<form class="search" method="get"><input type="hidden" name="page" value="prep">' . icon('calendar', 16)
        . '<input type="date" name="day" value="' . h($day) . '" aria-label="Another day"><button class="btn sm" type="submit">Show</button></form>';

    // Added up: item + size/flavour => quantity, and which orders.
    $totals = [];
    foreach ($orders as $o) {
        foreach ($o['items'] as $i) {
            $key = order_line_label($i);
            $totals[$key]['qty'] = ($totals[$key]['qty'] ?? 0) + (int) ($i['qty'] ?? 0);
            $totals[$key]['orders'][$o['number']] = true;
            if (!empty($i['note'])) $totals[$key]['notes'][] = $o['number'] . ': ' . $i['note'];
        }
    }
    ksort($totals, SORT_NATURAL | SORT_FLAG_CASE);
    $sumRows = '';
    foreach ($totals as $label => $t) {
        $sumRows .= '<tr><td class="qty">' . (int) $t['qty'] . '&times;</td><td><b>' . h($label) . '</b>'
            . (!empty($t['notes']) ? implode('', array_map(fn($n) => '<div class="cell-note">' . icon('edit', 12) . ' ' . h($n) . '</div>', $t['notes'])) : '')
            . '</td><td class="muted small">' . h(plural(count($t['orders']), 'order')) . '</td></tr>';
    }

    // Each order, in pickup order.
    $list = '';
    foreach ($orders as $o) {
        $items = '';
        foreach ($o['items'] as $i) {
            $items .= '<li><b>' . (int) ($i['qty'] ?? 0) . '&times;</b> ' . h(order_line_label($i)) . (!empty($i['note']) ? ' <em class="muted">(' . h($i['note']) . ')</em>' : '') . '</li>';
        }
        $list .= '<article class="prep-order">'
            . '<header><b class="prep-time">' . h(date('g:i A', $o['pickup'])) . '</b>'
            . '<a href="' . h(admin_url('orders', ['id' => $o['number']])) . '">' . h($o['number']) . '</a>'
            . (order_is_delivery($o) ? order_type_chip($o) . ' ' : '')
            . '<span>' . h($o['name']) . ' &middot; ' . h($o['phone']) . (order_is_delivery($o) ? ' &middot; ' . h(order_address($o)) : '') . '</span><span class="grow"></span>'
            . order_status_badge($o) . ' ' . paid_badge($o) . '<span class="no-print">' . order_next_form($o, 'prep') . '</span></header>'
            . '<ul>' . $items . '</ul>'
            . ($o['notes'] !== '' ? '<p class="cell-note">' . icon('chat', 12) . ' ' . h($o['notes']) . '</p>' : '')
            . ($o['note'] !== '' ? '<p class="cell-note">' . icon('edit', 12) . ' ' . h($o['note']) . '</p>' : '')
            . '</article>';
    }

    $title = date('l, F j', strtotime($day));
    $body = page_head('Prep list', h($title) . ' &middot; ' . h(plural(count($orders), 'pickup order')),
            '<button class="btn sm no-print" type="button" data-print>' . icon('print', 16) . '<span>Print</span></button>')
        . '<div class="toolbar no-print">' . tabs_html($tabs) . '<span class="grow"></span>' . $picker . '</div>'
        . ($orders === []
            ? empty_state('No pickups on ' . $title, 'Orders for this day will be added up here.', 'list')
            : '<div class="split"><div class="stack">'
                . card('To make', '<div class="table flush"><table class="tbl items"><tbody>' . $sumRows . '</tbody></table></div>')
                . '</div><div class="stack">' . card('Orders by pickup time', '<div class="prep-list">' . $list . '</div>') . '</div></div>');
    admin_render('Prep list', $body, 'prep');
}
