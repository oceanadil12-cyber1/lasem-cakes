<?php
/**
 * The admin panel for the La Sem Cakes website: /admin/
 *
 *   Customers   pickup and delivery orders, the day's prep list, deliveries with invoices, cake enquiries
 *   Website     menu & prices, page content, store settings (business details, ordering hours)
 *   System      who changed what (activity log)
 *
 *   /admin/?page=orders                    a section
 *   /admin/?page=orders&id=LS-261026-001   one record
 *   /admin/?api=counts                     JSON for the sidebar badges (signed in only)
 *
 * The first visit asks for the one-time code in private/setup-code.txt and creates
 * the sign-in. Each section is admin/inc/pages/<name>.php and defines admin_page_<name>().
 */
define('ADMIN', true);

// /admin without the trailing slash: go to /admin/ (the panel's links and its sign-in cookie
// belong to that folder; Apache does this by itself, PHP's built-in server doesn't).
$requested = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($requested !== '' && substr($requested, -1) !== '/' && substr($requested, -9) !== 'index.php') {
    // One leading slash only: "//host" or "/\host" in a Location header would point at another site.
    header('Location: /' . ltrim($requested, '/\\') . '/' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''), true, 301);
    exit;
}

require __DIR__ . '/inc/bootstrap.php';

admin_send_headers();
admin_session_start();

function send_json(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$account = admin_account();
if (!$account) admin_setup(); // first run: create the sign-in

// Sidebar badges and the bell, asked for every 30 seconds by admin.js.
if (isset($_GET['api'])) {
    if (!admin_signed_in(false)) send_json(['ok' => false, 'signedOut' => true], 401);
    if ($_GET['api'] === 'counts') send_json(['ok' => true] + admin_counts());
    if ($_GET['api'] === 'notifications') send_json(['ok' => true, 'alerts' => admin_notifications()]);
    send_json(['ok' => false], 404);
}

if (!admin_signed_in()) admin_login($account); // shows the sign-in page, or redirects once the password is right

if (is_post() && isset($_POST['logout'])) {
    require_csrf();
    admin_logout();
}

$page = get_choice('page', admin_pages_live(), ONLINE_ORDERING ? 'dashboard' : 'enquiries');
require __DIR__ . '/inc/pages/' . $page . '.php';
call_user_func('admin_page_' . str_replace('-', '_', $page));
