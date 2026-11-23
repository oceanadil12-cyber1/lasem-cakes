<?php
/**
 * Table ordering for guests: /dine-in/
 *
 *   /dine-in/?t=CODE            a table's own page (the link in its QR code): menu, order, priority number, live status
 *   /dine-in/?t=CODE&tablet=1   the same on a tablet fixed to the table: after each order it shows the number and a
 *                               "track on your phone" QR code, then starts fresh for the next guest
 *   /dine-in/track/?n=…&k=…     follow one order (opened from the tablet's QR code)
 *   /dine-in/board/             "Now serving" screen for a TV at the counter: numbers being prepared and ready
 *   /dine-in/qr/?n=…&k=…        the tracking QR code (SVG) for one order
 *
 * The page talks to api/dine-in.php (dine-in/assets/dine-in.js). Tables and their codes are managed in the
 * admin panel (Tables & QR codes).
 */
define('BASE_URL', rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/dine-in/index.php'))), '/') . '/');
require __DIR__ . '/../inc/site.php';
if (!ONLINE_ORDERING) { header('Location: ' . url(), true, 302); exit; } // table ordering is switched off
require __DIR__ . '/../inc/qr.php';

header('X-Robots-Tag: noindex, nofollow');
header('Referrer-Policy: no-referrer'); // the table code and tracking keys stay out of other sites' logs
header('X-Content-Type-Options: nosniff');

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$sub = preg_match('#/dine-in/(track|board|qr)/?$#', $path, $m) ? $m[1] : '';
if ($sub === '' && substr($path, -1) !== '/' && substr($path, -9) !== 'index.php') {
    header('Location: /' . ltrim($path, '/\\') . '/' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''), true, 301);
    exit;
}

/** The address of this site as the guest's phone sees it (for tracking QR codes). */
function here_origin()
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    $host = preg_replace('/[^A-Za-z0-9.:\-\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? parse_url(SITE['url'], PHP_URL_HOST)));
    return ($https ? 'https://' : 'http://') . $host;
}

function track_url($n, $k)
{
    return here_origin() . url('dine-in/track/') . '?n=' . rawurlencode($n) . '&k=' . rawurlencode($k);
}

// ---------- tracking QR code ----------
if ($sub === 'qr') {
    $n = (string) ($_GET['n'] ?? '');
    $k = (string) ($_GET['k'] ?? '');
    $f = dinein_private('orders') . '/' . $n . '.json';
    $o = preg_match('/^LS-\d{6}-\d{3,5}$/', $n) && is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    if (!is_array($o) || empty($o['track']) || !hash_equals((string) $o['track'], $k)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: image/svg+xml');
    header('Cache-Control: private, max-age=3600');
    echo qr_svg(track_url($n, $k), 'Scan to follow order ' . (int) $o['queue'] . ' on your phone');
    exit;
}

function dinein_page($title, $bodyClass, $html, $script = true)
{
    header('Content-Type: text/html; charset=utf-8');
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#2a1910">
<title><?= e($title) ?> · La Sem Cakes</title>
<link rel="icon" href="<?= e(url('images/logo.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= e(asset('dine-in/assets/dine-in.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
<?= $html ?>
<?php if ($script): ?><script src="<?= e(asset('js/chip-slider.js')) ?>" defer></script>
<script src="<?= e(asset('dine-in/assets/dine-in.js')) ?>" defer></script><?php endif; ?>
</body>
</html>
<?php
    exit;
}

$logo = '<img class="di-logo" src="' . e(url('images/logo.png')) . '" alt="La Sem Cakes" width="181" height="98">';
$settings = dinein_settings();

// ---------- "Now serving" board ----------
if ($sub === 'board') {
    dinein_page('Now serving', 'di-board-page', '
<main class="di-board" data-board data-api="' . e(url('api/dine-in.php')) . '">
  <header class="di-board-head">' . $logo . '<h1>Your order number</h1><time data-clock></time></header>
  <section class="di-board-col"><h2>Being prepared</h2><div class="di-board-nums" data-board-preparing></div></section>
  <section class="di-board-col is-ready"><h2>' . ($settings['serve'] === 'counter' ? 'Ready: please collect' : 'Ready') . '</h2><div class="di-board-nums" data-board-ready></div></section>
  <button class="di-board-sound" type="button" data-board-sound>Tap to turn on the chime</button>
</main>');
}

// ---------- follow one order ----------
if ($sub === 'track') {
    dinein_page('Your order', 'di-app-page', '
<div class="di-app" data-app data-mode="track" data-api="' . e(url('api/dine-in.php')) . '" data-n="' . e((string) ($_GET['n'] ?? '')) . '" data-k="' . e((string) ($_GET['k'] ?? '')) . '">
  <header class="di-top">' . $logo . '<span class="di-where">Your order</span></header>
  <main class="di-main" data-screen="track"></main>
</div>');
}

// ---------- a table ----------
$table = dinein_table_by_code($_GET['t'] ?? '');
if (!$table) {
    dinein_page('Order at your table', 'di-app-page', '
<div class="di-app"><main class="di-main di-center">' . $logo . '
  <h1>Order from your table</h1>
  <p class="di-lead">Scan the QR code on your table with your phone\'s camera to see the menu and order.</p>
  <p class="di-muted">If you scanned a code and see this, it may have been replaced. Please ask our team, or order at the counter.</p>
  <p><a class="di-btn di-btn-line" href="' . e(url('order/')) . '">Order for pickup instead</a></p>
</main></div>', false);
}

[$open, $why] = dinein_open();
$tablet = !empty($_GET['tablet']);
$menu = dinein_menu();
foreach ($menu['categories'] as &$c) {
    foreach ($c['products'] as &$p) if (!empty($p['img'])) $p['img'] = url($p['img']);
}
unset($c, $p);
$data = [
    'table'    => ['name' => $table['name'], 'code' => $table['code']],
    'tablet'   => $tablet,
    'open'     => $open,
    'closed'   => $why,
    'serve'    => $settings['serve'],
    'payNote'  => $settings['payNote'],
    'taxAll'   => $settings['taxAll'],
    'taxRate'  => (float) ($menu['taxRate'] ?? 0.13),
    'menu'     => ['categories' => $menu['categories']],
    'api'      => url('api/dine-in.php'),
    'qr'       => url('dine-in/qr/'),
    'pickup'   => url('order/'),
    'phone'    => SITE['phone'],
];
dinein_page($table['name'], 'di-app-page' . ($tablet ? ' is-tablet' : ''), '
<div class="di-app" data-app data-mode="table">
  <header class="di-top">' . $logo . '<span class="di-where"><b>' . e($table['name']) . '</b></span>
    <button class="di-call" type="button" data-call>Call staff</button></header>
  <main class="di-main" data-screen="welcome"></main>
  <div class="di-cartbar" data-cartbar hidden></div>
  <div class="di-sheet" data-sheet hidden role="dialog" aria-modal="true" aria-labelledby="di-sheet-title"></div>
  <div class="di-toast" data-toast role="status" hidden></div>
</div>
<script type="application/json" id="di-data">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>');
