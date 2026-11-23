<?php
/**
 * La Sem Cakes — site settings and small helpers shared by every page.
 * Business details below are the defaults; anything saved in the admin panel
 * (Store settings, kept in data/settings.json) takes their place.
 */

const SITE_DEFAULTS = [
    'name'      => 'La Sem Cakes',
    'legal'     => 'La Sem Patisserie',
    'phone'     => '(905) 624-8888',
    'phone_tel' => '+19056248888',
    'fax'       => '(905) 238-3232',
    'email'     => 'info@lasemcakes.ca',
    'street'    => '1275 Eglinton Ave. East',
    'city'      => 'Mississauga',
    'region'    => 'ON',
    'postal'    => 'L4W 2Z2',
    'hours'     => 'Every day, 8:30 am – 6:00 pm',
    'opens'     => '08:30',
    'closes'    => '18:00',
    'doordash'  => 'https://order.online/business/la-sem-patisserie-88690', // old third-party store, no longer linked
    'maps_url'  => 'https://maps.app.goo.gl/ZXmpbuNYha5AK2APA',
    'facebook'  => 'https://www.facebook.com/lasembakery',
    'url'       => 'https://lasemcakes.ca',
    'notify_email' => '',      // where order and enquiry emails go ('' = the email above)
    'announcement' => '',      // a short message shown in a bar above the header ('' = none)
];

define('SITE', (function () {
    $file = __DIR__ . '/../data/settings.json';
    $saved = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    $site = array_merge(SITE_DEFAULTS, is_array($saved) ? array_intersect_key($saved, SITE_DEFAULTS) : []);
    $site['phone_tel'] = '+1' . substr(preg_replace('/\D/', '', $site['phone']), -10);
    return $site;
})());

// Pages about La Sem elsewhere, linked from the site (and listed for search engines in business_schema()).
const TOP_CHOICE_LISTING = 'https://topchoiceawards.com/winner/la-sem-patisserie'; // verified 2026 Top Choice winner
const FOODWAYS = 'https://omeka.utm.utoronto.ca/s/foodways/';                       // U of T Mississauga food-history archive
const FOODWAYS_LA_SEM = FOODWAYS . 'item-set/1690';                                  // its La Sem Pasticcerie collection

/** Where order and enquiry notifications are emailed. */
function notify_email()
{
    return SITE['notify_email'] !== '' ? SITE['notify_email'] : SITE['email'];
}

date_default_timezone_set('America/Toronto');

require_once __DIR__ . '/ordering.php';
require_once __DIR__ . '/dinein.php';
require_once __DIR__ . '/reviews.php';

// Bump after CSS/JS changes so browsers fetch the new files.
const ASSET_VERSION = '20261119f';

// Our own menu ordering (/order/, table ordering, kitchen display) is switched off: the client
// orders through DoorDash and takes custom cakes through the "Order Request" pop-up, like the
// staging site. Set to true to bring the ordering system back; nothing of it has been deleted.
const ONLINE_ORDERING = false;
const DOORDASH_URL = 'https://order.online/business/la-sem-patisserie-88690';

/** Escape for HTML. */
function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** Site-relative URL. Works whether the site sits at the domain root or in a sub-folder. */
function url($path = '')
{
    return BASE_URL . ltrim($path, '/');
}

function asset($path)
{
    return url($path) . '?v=' . ASSET_VERSION;
}

/** All page content imported from the staging site (data/pages.json). */
function content()
{
    static $data = null;
    if ($data === null) {
        $data = json_decode(file_get_contents(__DIR__ . '/../data/pages.json'), true);
    }
    return $data;
}

function testimonials()
{
    static $t = null;
    if ($t === null) {
        $t = json_decode(file_get_contents(__DIR__ . '/../data/testimonials.json'), true);
    }
    return $t;
}

/** Full-size URL of an uploaded photo (photos added in the admin keep their own file name). */
function img_url($key)
{
    $m = image_manifest()[$key] ?? null;
    if ($m && !empty($m['file'])) return url('images/uploads/' . $m['file']);
    return url('images/uploads/' . preg_replace('/\.(jpe?g|png|webp|gif|avif)$/i', '', $key) . '.webp');
}

/** Image sizes, written when the images were optimised. */
function image_manifest()
{
    static $m = null;
    if ($m === null) {
        $file = __DIR__ . '/../images/uploads/manifest.json';
        $m = is_file($file) ? json_decode(file_get_contents($file), true) : [];
    }
    return $m;
}

/**
 * <img> for an uploaded photo. $key is its original upload path, e.g. "2026/10/Gelato2.jpg".
 * Adds width/height (no layout shift), an 800 px source for small screens and lazy loading.
 */
function img($key, $alt = '', $opts = [])
{
    $m = image_manifest()[$key] ?? null;
    $base = 'images/uploads/' . preg_replace('/\.(jpe?g|png|webp|gif|avif)$/i', '', $key);
    $attrs = [
        'src'      => img_url($key),
        'alt'      => $alt,
        'loading'  => $opts['eager'] ?? false ? 'eager' : 'lazy',
        'decoding' => 'async',
    ];
    if ($m) {
        $attrs['width'] = $m['w'];
        $attrs['height'] = $m['h'];
        if (!empty($m['sm'])) {
            $attrs['srcset'] = url($base . '-800.webp') . ' 800w, ' . url($base . '.webp') . ' ' . $m['w'] . 'w';
            $attrs['sizes'] = $opts['sizes'] ?? '(max-width: 800px) 100vw, 50vw';
        }
    }
    if (!empty($opts['class'])) {
        $attrs['class'] = $opts['class'];
    }
    $html = '<img';
    foreach ($attrs as $k => $v) {
        $html .= ' ' . $k . '="' . e($v) . '"';
    }
    return $html . '>';
}

/** Main menu. Dropdown groups are built from the page data so new pages appear automatically. */
function nav_items()
{
    $pages = content()['pages'];
    $group = function ($type) use ($pages) {
        $out = [];
        foreach ($pages as $slug => $p) {
            if ($p['type'] === $type) {
                $out[] = ['label' => $p['menu'] ?? $p['title'], 'href' => url(($type === 'award' ? 'awards/' : '') . $slug . '/')];
            }
        }
        return $out;
    };
    $az = function ($items) {
        usort($items, function ($x, $y) { return strnatcasecmp($x['label'], $y['label']); });
        return $items;
    };
    return [
        ['label' => 'About Us', 'href' => url('about-us/')],
        // Bakery: every cake and counter page in one A-to-Z list, like the staging site's menu.
        ['label' => 'Bakery', 'href' => url('bakery/'), 'az' => $az(array_merge($group('occasion'), $group('bakery')))],
        ['label' => 'Gallery', 'href' => url('gallery/'), 'children' => $group('gallery')],
        ['label' => 'Awards', 'href' => url('awards/'), 'children' => $group('award')],
        ['label' => 'Testimonials', 'href' => url('testimonials/')],
        ['label' => 'Articles & Videos', 'href' => url('articles-videos/')],
        ['label' => 'Contact', 'href' => url('contact-us/')],
        // As on the staging site: DoorDash plus the store policies.
        ['label' => 'Order Online', 'href' => DOORDASH_URL, 'external' => true, 'children' => array_merge(
            [['label' => 'Order online – DoorDash', 'href' => DOORDASH_URL, 'external' => true]],
            $group('policy')
        )],
    ];
}

/** A page's text blocks from pages.json as HTML: paragraphs, with "li" blocks grouped into lists. */
function body_html($body)
{
    $html = '';
    $inList = false;
    foreach ($body ?: [] as $b) {
        $li = ($b['t'] ?? 'p') === 'li';
        if ($li && !$inList) { $html .= "<ul>\n"; $inList = true; }
        if (!$li && $inList) { $html .= "</ul>\n"; $inList = false; }
        // Some staging paragraphs pack a list into one line with "â€¢" bullets.
        $parts = array_map('trim', explode('â€¢', (string) $b['x']));
        if (!$li && count($parts) > 1) {
            $html .= '<p>' . e($parts[0]) . "</p>\n<ul>";
            foreach (array_slice($parts, 1) as $item) $html .= '<li>' . e($item) . '</li>';
            $html .= "</ul>\n";
        } else {
            $html .= $li ? '<li>' . e($b['x']) . "</li>\n" : '<p>' . e($b['x']) . "</p>\n";
        }
    }
    return $html . ($inList ? "</ul>\n" : '');
}
