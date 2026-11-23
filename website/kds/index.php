<?php
/**
 * Kitchen display for La Sem Cakes: /kds/
 *
 * A screen for the bakery and the counter (a tablet or a TV with a browser). It shows
 * the day's pickup orders in pickup-time order and table orders (with their priority
 * number) as they come in, warns when it's time to start one, chimes for new orders and
 * "call staff" requests, and moves orders along with one tap:
 *
 *   New / Confirmed  → START  → Preparing (timer) → READY → Ready for pickup → PICKED UP
 *
 * The data comes from kds/api.php every few seconds (kds/assets/kds.js).
 */
define('KDS', true);

// /kds without the trailing slash: go to /kds/ (the screen's links and its cookie belong to that folder).
$requested = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($requested !== '' && substr($requested, -1) !== '/' && substr($requested, -9) !== 'index.php') {
    header('Location: /' . ltrim($requested, '/\\') . '/' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''), true, 301);
    exit;
}

require __DIR__ . '/inc/kds.php';

admin_send_headers();
kds_session_start();

function kds_page(string $title, string $bodyHtml, string $bodyAttrs = '', bool $app = false): void
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex, nofollow"><meta name="color-scheme" content="dark"><meta name="theme-color" content="#120c09">'
        . '<meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes">'
        . '<title>' . e($title) . '</title>'
        . '<link rel="icon" href="' . e(SITE_BASE) . 'images/logo.png">'
        . '<link rel="stylesheet" href="' . kds_asset('kds.css') . '"></head>'
        . '<body' . $bodyAttrs . '>' . $bodyHtml
        . ($app ? '<script src="' . kds_asset('kds.js') . '" defer></script>' : '')
        . '</body></html>';
    exit;
}

$post = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($post && isset($_POST['kds_logout'])) {
    if (hash_equals(kds_csrf(), (string) ($_POST['csrf'] ?? ''))) kds_logout();
    header('Location: ' . KDS_BASE, true, 303);
    exit;
}

// ---------- sign-in ----------
if (!kds_signed_in()) {
    if (!admin_account()) {
        kds_page('Kitchen display', '<main class="kds-login"><img class="kds-login-logo" src="' . e(SITE_BASE) . 'images/logo.png" alt="La Sem Cakes" height="64">'
            . '<h1>Kitchen display</h1><p>Set up the admin panel first at <a href="' . e(SITE_BASE) . 'admin/">' . e(SITE_BASE) . 'admin/</a>, then come back.</p></main>');
    }
    $error = '';
    if ($post && isset($_POST['secret']) && is_string($_POST['secret'])) {
        $user = trim((string) ($_POST['user'] ?? ''));
        if (rate_blocked('kds-login', 8, 900)) {
            $error = 'Too many wrong attempts. Please wait 15 minutes.';
        } elseif (($who = kds_check($user, $_POST['secret'])) !== '') {
            rate_clear('kds-login');
            kds_begin($who);
            admin_log('kds.login', $who);
            header('Location: ' . KDS_BASE, true, 303);
            exit;
        } else {
            rate_hit('kds-login', 900);
            admin_log('kds.login.failed', $user);
            $error = $user === '' ? 'That passcode isn\'t right.' : 'That user name or password isn\'t right.';
        }
    }
    $hasPin = kds_settings()['pin_hash'] !== '';
    $adminForm = '<form method="post" action="' . e(KDS_BASE) . '">'
        . '<input name="user" placeholder="Admin user name" autocomplete="username" required>'
        . '<input type="password" name="secret" placeholder="Admin password" autocomplete="current-password" required>'
        . '<button type="submit">Open kitchen display</button></form>';
    kds_page('Kitchen display · La Sem', '<main class="kds-login">'
        . '<img class="kds-login-logo" src="' . e(SITE_BASE) . 'images/logo.png" alt="La Sem Cakes" height="64">'
        . '<h1>Kitchen display</h1>'
        . ($error !== '' ? '<p class="kds-error" role="alert">' . e($error) . '</p>' : '')
        . ($hasPin
            ? '<p>Enter the kitchen passcode.</p><form method="post" action="' . e(KDS_BASE) . '">'
                . '<input type="password" name="secret" placeholder="Passcode" autocomplete="current-password" inputmode="numeric" required autofocus>'
                . '<button type="submit">Open kitchen display</button></form>'
                . '<details class="kds-alt"><summary>Use the admin sign-in instead</summary>' . $adminForm . '</details>'
            : '<p>Sign in with the admin user name and password. You can set a simpler kitchen passcode in the admin, under Store settings.</p>' . $adminForm)
        . '</main>');
}

// ---------- the screen ----------
$s = kds_settings();
$icon = function (string $d, int $size = 20): string {
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
};
kds_page('Kitchen display · La Sem', '
<div class="kds-app">
  <header class="kds-header">
    <a class="kds-brand" href="' . e(KDS_BASE) . '"><img src="' . e(SITE_BASE) . 'images/logo.png" alt="" height="34"><span>Kitchen display</span></a>
    <time class="kds-clock" id="kds-clock" aria-label="Time now"></time>
    <div class="kds-summary">
      <button type="button" class="kds-stat" data-view="today" data-stat="start"><b id="stat-start">0</b><span>To start</span></button>
      <button type="button" class="kds-stat" data-view="today" data-stat="preparing"><b id="stat-preparing">0</b><span>Preparing</span></button>
      <button type="button" class="kds-stat" data-view="ready" data-stat="ready"><b id="stat-ready">0</b><span>Ready</span></button>
      <button type="button" class="kds-stat" data-view="today" data-stat="late"><b id="stat-late">0</b><span>Late</span></button>
    </div>
    <div class="kds-actions">
      <button class="kds-icon" id="kds-sound" type="button" title="Sound on" aria-pressed="true">' . $icon('<path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14"/>') . '</button>
      <button class="kds-icon" id="kds-full" type="button" title="Full screen">' . $icon('<path d="M8 3H5a2 2 0 0 0-2 2v3M21 8V5a2 2 0 0 0-2-2h-3M3 16v3a2 2 0 0 0 2 2h3M16 21h3a2 2 0 0 0 2-2v-3"/>') . '</button>
      <button class="kds-icon" id="kds-settings-open" type="button" title="Settings">' . $icon('<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>') . '</button>
      <form method="post" action="' . e(KDS_BASE) . '"><input type="hidden" name="kds_logout" value="1"><input type="hidden" name="csrf" value="' . e(kds_csrf()) . '">
        <button class="kds-icon" type="submit" title="Sign out">' . $icon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>') . '</button></form>
    </div>
  </header>

  <nav class="kds-views" aria-label="Which orders">
    <button type="button" data-view="today" class="on">Today <i id="count-today">0</i></button>
    <button type="button" data-view="tables">Table orders <i id="count-tables">0</i></button>
    <button type="button" data-view="tomorrow">Tomorrow <i id="count-tomorrow">0</i></button>
    <button type="button" data-view="ready">Ready for pickup <i id="count-ready">0</i></button>
    <button type="button" data-view="done">Picked up today <i id="count-done">0</i></button>
    <span class="kds-conn" id="kds-conn" hidden>Reconnecting…</span>
  </nav>

  <main class="kds-grid" id="kds-grid" aria-live="polite"></main>

  <div class="kds-sheet" id="kds-settings" hidden>
    <div class="kds-sheet-back" data-close></div>
    <form class="kds-sheet-body" id="kds-settings-form">
      <h2>Kitchen display settings</h2>
      <label class="kds-field"><span>Start orders</span><span class="kds-field-in"><input type="number" name="prep_minutes" min="5" max="1440" value="' . (int) $s['prep_minutes'] . '"> minutes before pickup</span>
        <small>Orders turn amber ("Start now") when pickup is this close and they haven\'t been started.</small></label>
      <label class="kds-check"><input type="checkbox" name="sound" value="1"' . ($s['sound'] ? ' checked' : '') . '> Chime when a new order comes in</label>
      <p class="kds-hint">Tip: tap the screen once after opening it so the browser allows the chime. The kitchen passcode is set in the admin panel, under Store settings.</p>
      <div class="kds-sheet-actions"><button type="button" class="kds-btn" data-close>Cancel</button><button type="submit" class="kds-btn kds-btn-go">Save</button></div>
    </form>
  </div>
  <div class="kds-toast" id="kds-toast" role="status" hidden></div>
</div>',
    ' data-api="' . e(KDS_BASE . 'api.php') . '" data-csrf="' . e(kds_csrf()) . '" data-prep="' . (int) $s['prep_minutes'] . '" data-sound="' . ($s['sound'] ? '1' : '0') . '" data-admin="' . e(SITE_BASE . 'admin/') . '"',
    true);
