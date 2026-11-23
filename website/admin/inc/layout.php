<?php
/**
 * The shell around every admin page: sidebar navigation, top bar, flash messages.
 * admin_render() prints a finished page and ends the request.
 */
if (!defined('ADMIN')) { http_response_code(403); exit('Forbidden'); }

// [group, [[page, label, icon], ...]] — the sidebar, top to bottom.
const ADMIN_NAV = [
    ['Overview', [
        ['dashboard', 'Dashboard', 'home'],
    ]],
    ['Customers', [
        ['orders', 'Orders', 'bag'],
        ['prep', 'Prep list', 'list'],
        ['deliveries', 'Deliveries', 'truck'],
        ['enquiries', 'Cake enquiries', 'mail'],
    ]],
    ['Website', [
        ['menu', 'Menu & prices', 'dollar'],
        ['tables', 'Tables & QR codes', 'users'],
        ['content', 'Page content', 'edit'],
        ['settings', 'Store settings', 'sliders'],
    ]],
    ['System', [
        ['activity', 'Activity log', 'activity'],
    ]],
];

/** The section's name as shown in the sidebar (used for the tab title and breadcrumb). */
function admin_section_label(string $page): string
{
    foreach (ADMIN_NAV as [, $links]) {
        foreach ($links as [$p, $label]) if ($p === $page) return $label;
    }
    return 'Admin';
}

function admin_asset(string $file): string
{
    $path = __DIR__ . '/../assets/' . $file;
    return h(ADMIN_BASE . 'assets/' . $file . '?v=' . (is_file($path) ? filemtime($path) : 0));
}

/** <head> shared by the sign-in page and the panel. */
function admin_head(string $title): string
{
    return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex, nofollow"><meta name="color-scheme" content="dark"><meta name="theme-color" content="#120c09">'
        . '<title>' . h($title) . '</title>'
        . '<link rel="icon" href="' . h(SITE_BASE) . 'images/logo.png">'
        . '<link rel="stylesheet" href="' . admin_asset('admin.css') . '"></head>';
}

/** A page with no sidebar (sign-in, first-run setup). Ends the request. */
function admin_render_bare(string $title, string $bodyHtml): void
{
    header('Content-Type: text/html; charset=utf-8');
    echo admin_head($title . ' · La Sem Admin') . '<body class="bare"><main class="bare-main">' . $bodyHtml . '</main></body></html>';
    exit;
}

/** A finished admin page: $active is the sidebar entry to highlight, $scripts = extra files from admin/assets/. Ends the request. */
function admin_render(string $title, string $bodyHtml, string $active = '', array $scripts = []): void
{
    $counts = admin_counts();
    $waiting = array_sum($counts);

    $nav = '';
    foreach (ADMIN_NAV as [$group, $links]) {
        if (!array_filter($links, fn($l) => in_array($l[0], admin_pages_live(), true))) continue;
        $nav .= '<div class="nav-group"><p class="nav-label">' . h($group) . '</p>';
        foreach ($links as [$page, $label, $ico]) {
            if (!in_array($page, admin_pages_live(), true)) continue;
            $on = $page === $active;
            $badge = isset($counts[$page])
                ? '<b class="nav-count" data-badge="' . h($page) . '"' . ($counts[$page] > 0 ? '' : ' hidden') . '>' . (int) $counts[$page] . '</b>'
                : '';
            $nav .= '<a class="nav-link' . ($on ? ' on' : '') . '" href="' . h(admin_url($page)) . '"' . ($on ? ' aria-current="page"' : '') . '>'
                . icon($ico, 18) . '<span>' . h($label) . '</span>' . $badge . '</a>';
        }
        $nav .= '</div>';
    }

    $ordering = menu_ordering_open();
    header('Content-Type: text/html; charset=utf-8');
    echo admin_head(($waiting ? "($waiting) " : '') . $title . ' · La Sem Admin')
        . '<body data-counts-url="' . h(admin_url('', ['api' => 'counts'])) . '" data-notif-url="' . h(admin_url('', ['api' => 'notifications'])) . '" data-title="' . h($title . ' · La Sem Admin') . '">'
        . '<a class="skip" href="#content">Skip to content</a>'
        . '<input type="checkbox" id="navtoggle" class="navtoggle" tabindex="-1" aria-hidden="true">'
        . '<div class="shell">'
        . '<aside class="side" id="side" aria-label="Main">'
        .   '<a class="side-brand" href="' . h(admin_url()) . '"><img src="' . h(SITE_BASE) . 'images/logo.png" alt="" height="40">'
        .   '<span><b>La Sem Admin</b><small>Cakes &amp; Patisserie</small></span></a>'
        .   '<nav class="side-nav">' . $nav . '</nav>'
        .   '<div class="side-foot">'
        .     (ONLINE_ORDERING
                ? '<a class="nav-link" href="' . h(SITE_BASE) . 'order/" target="_blank" rel="noopener">' . icon('bag', 18) . '<span>Order page</span>'
                . '<b class="nav-state ' . ($ordering ? 'on' : 'off') . '">' . ($ordering ? 'Open' : 'Closed') . '</b></a>'
                . '<a class="nav-link" href="' . h(SITE_BASE) . 'kds/" target="_blank" rel="noopener">' . icon('clock', 18) . '<span>Kitchen display</span></a>'
                : '')
        .     '<a class="nav-link" href="' . h(SITE_BASE) . '" target="_blank" rel="noopener">' . icon('external', 18) . '<span>View website</span></a>'
        .     form_open(admin_url(), 'signout') . '<input type="hidden" name="logout" value="1">'
        .       '<button class="nav-link" type="submit">' . icon('logout', 18) . '<span>Sign out' . (admin_user() !== '' ? ' <small class="muted">(' . h(admin_user()) . ')</small>' : '') . '</span></button></form>'
        .   '</div>'
        . '</aside>'
        . '<label class="scrim" for="navtoggle"></label>'
        . '<div class="main">'
        .   '<header class="topbar">'
        .     '<label class="burger" for="navtoggle" tabindex="0" role="button" aria-label="Open menu" aria-controls="side">' . icon('menu', 22) . '</label>'
        .     '<div class="crumbs"><span>La Sem Admin</span><span class="sep">/</span><b>' . h(admin_section_label($active)) . '</b></div>'
        .     '<span class="grow"></span>'
        .     '<div class="notif-wrap" id="notif-wrap">'
        .       '<button class="notif-bell" id="notif-bell" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="notif-panel">'
        .         icon('bell', 18) . '<b class="notif-dot" id="notif-dot" hidden></b>'
        .       '</button>'
        .       '<div class="notif-panel" id="notif-panel" role="menu" hidden>'
        .         '<div class="notif-head"><b>Notifications</b><button class="notif-close" id="notif-close" type="button" aria-label="Close">' . icon('x', 14) . '</button></div>'
        .         '<div class="notif-body" id="notif-body"><p class="notif-empty">No alerts right now.</p></div>'
        .       '</div>'
        .     '</div>'
        .     '<a class="btn sm ghost hide-sm" href="' . h(SITE_BASE) . '" target="_blank" rel="noopener">' . icon('external', 15) . '<span>View website</span></a>'
        .   '</header>'
        .   '<main class="content" id="content">' . flash_html() . $bodyHtml . '</main>'
        . '</div></div>'
        . '<div class="tip" id="tip" role="tooltip" hidden></div><div class="toasts" id="toasts" aria-live="polite"></div>'
        . '<script src="' . admin_asset('admin.js') . '" defer></script>'
        . implode('', array_map(fn($js) => '<script src="' . admin_asset($js) . '" defer></script>', $scripts))
        . '</body></html>';
    exit;
}
