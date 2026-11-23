<?php
/**
 * Loads everything the admin panel needs. Included by admin/index.php only:
 * the guard below stops these files running if someone requests them directly
 * (admin/inc/.htaccess and router.php also block the folder).
 */
if (!defined('ADMIN')) { http_response_code(403); exit('Forbidden'); }

// URL prefixes, computed from the request so the panel works from any folder.
define('ADMIN_BASE', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/index.php')), '/') . '/');
define('SITE_BASE', rtrim(str_replace('\\', '/', dirname(rtrim(ADMIN_BASE, '/'))), '/') . '/');
define('BASE_URL', SITE_BASE); // what the website's url() helper builds on

// The same settings and helpers the website uses, so the admin edits exactly what the site reads.
require_once __DIR__ . '/../../inc/site.php';

require_once __DIR__ . '/store.php';
require_once __DIR__ . '/ui.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

/** Where each section lives: page name => file in admin/inc/pages/ (the whitelist for ?page=). */
const ADMIN_PAGES = ['dashboard', 'orders', 'prep', 'deliveries', 'enquiries', 'tables', 'menu', 'content', 'settings', 'activity'];
// Sections that only serve our own online ordering; hidden while ONLINE_ORDERING is off (inc/site.php).
const ADMIN_ORDERING_PAGES = ['dashboard', 'orders', 'prep', 'deliveries', 'tables', 'menu'];

/** Admin sections in use right now. */
function admin_pages_live(): array
{
    return ONLINE_ORDERING ? ADMIN_PAGES : array_values(array_diff(ADMIN_PAGES, ADMIN_ORDERING_PAGES));
}
