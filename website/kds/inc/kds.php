<?php
/**
 * Shared by kds/index.php (the screen) and kds/api.php (its data): settings, the
 * kitchen session and sign-in. Orders are the same JSON files the admin panel uses
 * (admin/inc/store.php), so a change on the KDS shows in the admin and the other way round.
 *
 * Sign-in: the kitchen passcode set in the admin (Store settings → Kitchen display),
 * or the admin user name and password. The session has its own cookie (path /kds/)
 * and lasts while the screen is open; it ends after 12 hours with the screen closed,
 * or as soon as the passcode or the admin password changes.
 */
if (!defined('KDS')) { http_response_code(403); exit('Forbidden'); }

define('KDS_BASE', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/kds/index.php')), '/') . '/');
define('SITE_BASE', rtrim(str_replace('\\', '/', dirname(rtrim(KDS_BASE, '/'))), '/') . '/');
define('BASE_URL', SITE_BASE);

require_once __DIR__ . '/../../inc/site.php';
if (!ONLINE_ORDERING) { // the kitchen display only serves our own ordering, which is switched off
    http_response_code(410);
    exit('The kitchen display is not in use: online ordering is switched off.');
}
require_once __DIR__ . '/../../admin/inc/store.php';
require_once __DIR__ . '/../../admin/inc/auth.php'; // admin_account(), admin_send_headers()

const KDS_IDLE_SECONDS = 43200;

function kds_session_start(): void
{
    $dir = private_dir('sessions');
    if (is_dir($dir) && is_writable($dir)) session_save_path($dir);
    ini_set('session.gc_maxlifetime', (string) KDS_IDLE_SECONDS);
    ini_set('session.use_strict_mode', '1');
    session_name('lasem_kds');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => KDS_BASE,
        'httponly' => true,
        'samesite' => 'Strict',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

/** Signed in? Every poll from the open screen keeps the session alive. */
function kds_signed_in(): bool
{
    if (empty($_SESSION['kds_ok'])) return false;
    if (time() - (int) ($_SESSION['kds_seen'] ?? 0) > KDS_IDLE_SECONDS || ($_SESSION['kds_v'] ?? '') !== kds_version()) {
        $_SESSION = [];
        return false;
    }
    $_SESSION['kds_seen'] = time();
    return true;
}

function kds_csrf(): string
{
    if (empty($_SESSION['kds_csrf'])) $_SESSION['kds_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['kds_csrf'];
}

/** Check a passcode, or "user name + password" of the admin. Returns who signed in, or ''. */
function kds_check(string $user, string $secret): string
{
    $s = kds_settings();
    if ($user === '' && $s['pin_hash'] !== '' && password_verify($secret, $s['pin_hash'])) return 'kitchen';
    $a = admin_account();
    if ($a && $user !== '' && hash_equals($a['user'], strtolower($user)) && password_verify($secret, $a['hash'])) return $a['user'];
    return '';
}

function kds_begin(string $who): void
{
    session_regenerate_id(true);
    $_SESSION['kds_ok'] = true;
    $_SESSION['kds_who'] = $who;
    $_SESSION['kds_seen'] = time();
    $_SESSION['kds_v'] = kds_version();
    unset($_SESSION['kds_csrf']);
}

function kds_logout(): void
{
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'domain' => $p['domain'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Strict']);
    session_destroy();
}

function kds_asset(string $file): string
{
    $path = __DIR__ . '/../assets/' . $file;
    return e(KDS_BASE . 'assets/' . $file . '?v=' . (is_file($path) ? filemtime($path) : 0));
}
