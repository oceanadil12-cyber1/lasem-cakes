<?php
/**
 * Front controller: every page URL comes here (see .htaccess) and is matched to a template.
 * URLs are the same as the staging site (/wedding-cakes/, /awards/top-choice-award/ …);
 * addresses from the old lasemcakes.ca are redirected to their new home.
 */

// Sub-folder installs (e.g. example.com/lasemcakes/) work as well as the domain root.
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/');

require __DIR__ . '/inc/site.php';
require __DIR__ . '/inc/layout.php';
require __DIR__ . '/inc/scene-hero.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim(substr($path, strlen(BASE_URL) - 1), '/');
$path = strtolower(urldecode($path));
if ($path === 'index.php') {
    $path = '';
}

// Old lasemcakes.ca addresses → new pages (301, so search rankings carry over).
$redirects = [
    'home'                        => '',
    'bakery/cake-category'        => 'bakery/',
    'bakery/cake-type-fillings'   => 'cake-type-fillings/',
    'bakery/pastries'             => 'pastries-mississauga/',
    'bakery/cookies'              => 'cookies-mississauga/',
    'bakery/gelato'               => 'gelato/',
    'bakery/bread'                => 'foccacia-pizza-lasagna-other-buns/',
    'bakery/zeppole'              => 'zeppole-mississauga/',
    'blog'                        => 'articles-videos/',
];
if (isset($redirects[$path])) {
    redirect($redirects[$path]);
}
if (preg_match('#^gallery/([a-z0-9-]+)$#', $path, $m)) {
    redirect($m[1] . '/');
}
if (preg_match('#^\d{4}/\d{2}/\d{2}/([a-z0-9-]+)$#', $path, $m)) {
    redirect('articles-videos/#' . $m[1]);
}

$pages = content()['pages'];
$slug = preg_replace('#^awards/#', '', $path);

if ($path === 'sitemap.xml') {
    $urls = ['', 'about-us/', 'bakery/', 'cake-type-fillings/', 'gallery/', 'awards/', 'testimonials/', 'articles-videos/', 'contact-us/'];
    foreach ($pages as $s => $p) {
        $urls[] = ($p['type'] === 'award' ? 'awards/' : '') . $s . '/';
    }
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        echo '  <url><loc>' . e(SITE['url'] . '/' . $u) . "</loc></url>\n";
    }
    echo '</urlset>';
    exit;
}

switch (true) {
    case $path === '':
        require __DIR__ . '/inc/pages/home.php';
        break;
    case $path === 'about-us':
        require __DIR__ . '/inc/pages/about.php';
        break;
    case $path === 'contact-us':
        require __DIR__ . '/inc/pages/contact.php';
        break;
    case $path === 'testimonials':
        require __DIR__ . '/inc/pages/testimonials.php';
        break;
    case $path === 'articles-videos':
        require __DIR__ . '/inc/pages/articles.php';
        break;
    case $path === 'order':
        // Our own ordering is off (inc/site.php): send visitors to DoorDash, as the staging site does.
        if (!ONLINE_ORDERING) { header('Location: ' . DOORDASH_URL, true, 302); exit; }
        require __DIR__ . '/inc/pages/order.php';
        break;
    case $path === 'cake-type-fillings':
        require __DIR__ . '/inc/pages/fillings.php';
        break;
    case $path === 'awards':
        require __DIR__ . '/inc/pages/awards.php';
        break;
    case in_array($path, ['bakery', 'gallery'], true):
        $section = $path;
        require __DIR__ . '/inc/pages/section.php';
        break;
    case isset($pages[$slug]) && ($pages[$slug]['type'] === 'award') === (strpos($path, 'awards/') === 0):
        $page = $pages[$slug];
        $page['slug'] = $slug;
        $page['path'] = $path . '/';
        $templates = ['occasion' => 'product', 'bakery' => 'product', 'gallery' => 'gallery', 'award' => 'award', 'policy' => 'policy'];
        require __DIR__ . '/inc/pages/' . $templates[$page['type']] . '.php';
        break;
    default:
        http_response_code(404);
        require __DIR__ . '/inc/pages/404.php';
}

function redirect($to)
{
    header('Location: ' . url($to), true, 301);
    exit;
}
