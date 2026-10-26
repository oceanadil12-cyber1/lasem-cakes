<?php
/**
 * La Sem Cakes — site settings and small helpers shared by every page.
 * Business details live here so the admin panel can take them over later.
 */

const SITE = [
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
];

date_default_timezone_set('America/Toronto');

// Bump after CSS/JS changes so browsers fetch the new files.
const ASSET_VERSION = '20261026a';

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
        'src'      => url($base . '.webp'),
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
    return [
        ['label' => 'About Us', 'href' => url('about-us/')],
        ['label' => 'Bakery', 'href' => url('bakery/'), 'mega' => [
            'Cakes for every occasion' => array_merge($group('occasion'), [['label' => 'Cake types & fillings', 'href' => url('cake-type-fillings/')]]),
            'From our counter'        => $group('bakery'),
        ]],
        ['label' => 'Gallery', 'href' => url('gallery/'), 'children' => $group('gallery')],
        ['label' => 'Awards', 'href' => url('awards/'), 'children' => $group('award')],
        ['label' => 'Testimonials', 'href' => url('testimonials/')],
        ['label' => 'Articles & Videos', 'href' => url('articles-videos/')],
        ['label' => 'Contact', 'href' => url('contact-us/')],
    ];
}
