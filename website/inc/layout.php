<?php
/**
 * Shared page frame: <head>, header with the awning and menus, footer.
 *
 * page_start([
 *   'title' => 'Wedding Cakes', 'description' => '…', 'path' => 'wedding-cakes/',
 *   'hero' => true,          // transparent header over a dark hero (home page)
 *   'scripts' => ['js/x.js'],
 * ]);
 */

function page_start($o)
{
    $title = isset($o['title']) && $o['title'] !== '' ? $o['title'] . ' | ' . SITE['name'] : SITE['name'] . ' | Custom Cakes, Wedding Cakes & Italian Bakery in Mississauga';
    $desc = $o['description'] ?? 'Family-run European bakery in Mississauga since 1963. Custom cakes, pastries, gelato and bread.';
    $canonical = SITE['url'] . '/' . ltrim($o['path'] ?? '', '/');
    $GLOBALS['page_opts'] = $o;
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(SITE['name']) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<?php if (!empty($o['og_image'])): ?><meta property="og:image" content="<?= e(SITE['url'] . '/' . $o['og_image']) ?>">
<?php endif; ?>
<meta name="theme-color" content="#2a1910">
<link rel="icon" href="<?= e(url('images/logo.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,400&family=Kaushan+Script&family=Manrope:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
<script type="application/ld+json"><?= json_encode(business_schema(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body class="<?= empty($o['hero']) ? 'has-solid-header' : 'has-hero' ?>">
<a class="skip" href="#main">Skip to content</a>
<?php site_header(); ?>
<main id="main">
<?php
}

function page_end()
{
    $o = $GLOBALS['page_opts'] ?? [];
    ?>
</main>
<?php site_footer(); ?>
<?php foreach ($o['scripts'] ?? [] as $s): ?>
<script src="<?= e(strpos($s, 'vendor/') !== false ? url($s) : asset($s)) ?>"></script>
<?php endforeach; ?>
<script src="<?= e(asset('js/main.js')) ?>"></script>
</body>
</html>
<?php
}

function site_header()
{
    ?>
<header class="site-header" data-header>
  <div class="awning" aria-hidden="true"></div>
  <div class="bar wrap">
    <a class="brand" href="<?= e(url()) ?>" aria-label="La Sem Cakes, home">
      <img src="<?= e(url('images/logo.png')) ?>" alt="" width="181" height="98">
    </a>
    <nav class="nav" aria-label="Main">
      <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-list" data-nav-toggle>
        <span></span><span></span><span></span><b class="sr">Menu</b>
      </button>
      <ul id="nav-list" class="nav-list" data-nav-list>
<?php foreach (nav_items() as $i => $item):
        $hasSub = !empty($item['children']) || !empty($item['mega']); ?>
        <li class="<?= $hasSub ? 'has-sub' : '' ?><?= !empty($item['mega']) ? ' has-mega' : '' ?>">
          <a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
<?php if ($hasSub): ?>
          <button class="sub-toggle" type="button" aria-expanded="false" aria-controls="sub-<?= $i ?>" aria-label="Show <?= e($item['label']) ?> pages" data-sub-toggle>
            <svg viewBox="0 0 12 12" aria-hidden="true"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
          </button>
<?php if (!empty($item['mega'])): ?>
          <div class="sub mega" id="sub-<?= $i ?>">
<?php foreach ($item['mega'] as $heading => $links): ?>
            <div>
              <p class="sub-head"><?= e($heading) ?></p>
              <ul><?php foreach ($links as $l): ?><li><a href="<?= e($l['href']) ?>"><?= e($l['label']) ?></a></li><?php endforeach; ?></ul>
            </div>
<?php endforeach; ?>
          </div>
<?php else: ?>
          <ul class="sub" id="sub-<?= $i ?>">
            <?php foreach ($item['children'] as $l): ?><li><a href="<?= e($l['href']) ?>"><?= e($l['label']) ?></a></li><?php endforeach; ?>
          </ul>
<?php endif; ?>
<?php endif; ?>
        </li>
<?php endforeach; ?>
        <li class="nav-cta-m"><a class="btn btn-solid" href="<?= e(url('order/')) ?>">Order Online</a></li>
      </ul>
    </nav>
    <div class="bar-actions">
      <a class="tel" href="tel:<?= e(SITE['phone_tel']) ?>"><?= e(SITE['phone']) ?></a>
      <a class="btn btn-solid" href="<?= e(url('order/')) ?>">Order Online</a>
    </div>
  </div>
</header>
<?php
}

function site_footer()
{
    $pages = content()['pages'];
    $col = function ($type) use ($pages) {
        foreach ($pages as $slug => $p) {
            if ($p['type'] === $type) {
                echo '<li><a href="' . e(url($slug . '/')) . '">' . e($p['menu'] ?? $p['title']) . "</a></li>\n";
            }
        }
    };
    ?>
<footer class="site-footer">
  <div class="awning flip" aria-hidden="true"></div>
  <div class="wrap foot">
    <div class="foot-brand">
      <img src="<?= e(url('images/logo.png')) ?>" alt="La Sem" width="140" height="76" loading="lazy">
      <p>Family-run European bakery and patisserie. Custom cakes, pastries, gelato and bread since 1963.</p>
      <p class="social">
        <a href="<?= e(SITE['facebook']) ?>" rel="noopener" aria-label="La Sem on Facebook">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 8h3V4h-3c-2.8 0-4.5 1.8-4.5 4.6V11H7v4h2.5v9h4v-9h3l.5-4h-3.5V8.8c0-.5.3-.8.5-.8z"/></svg>
        </a>
      </p>
    </div>
    <nav aria-label="Cakes">
      <h3>Cakes</h3>
      <ul><?php $col('occasion'); ?></ul>
    </nav>
    <nav aria-label="Bakery">
      <h3>Bakery</h3>
      <ul><?php $col('bakery'); ?></ul>
    </nav>
    <nav aria-label="Gallery">
      <h3>Gallery</h3>
      <ul><?php $col('gallery'); ?></ul>
    </nav>
    <div>
      <h3>Visit us</h3>
      <p><?= e(SITE['street']) ?><br><?= e(SITE['city'] . ', ' . SITE['region'] . ' ' . SITE['postal']) ?></p>
      <p><?= e(SITE['hours']) ?></p>
      <p><a href="tel:<?= e(SITE['phone_tel']) ?>"><?= e(SITE['phone']) ?></a><br>
         <a href="mailto:<?= e(SITE['email']) ?>"><?= e(SITE['email']) ?></a></p>
      <ul class="foot-links">
        <li><a href="<?= e(url('about-us/')) ?>">About us</a></li>
        <li><a href="<?= e(url('testimonials/')) ?>">Testimonials</a></li>
        <li><a href="<?= e(url('articles-videos/')) ?>">Articles &amp; videos</a></li>
        <li><a href="<?= e(url('contact-us/')) ?>">Contact us</a></li>
      </ul>
    </div>
  </div>
  <div class="wrap foot-bottom">
    <p>&copy; <?= date('Y') ?> <?= e(SITE['legal']) ?></p>
    <p class="policies">
      <?php foreach ($pages as $slug => $p) {
          if ($p['type'] === 'policy') {
              echo '<a href="' . e(url($slug . '/')) . '">' . e($p['title']) . '</a> ';
          }
      } ?>
    </p>
  </div>
</footer>
<?php
}

function business_schema()
{
    return [
        '@context' => 'https://schema.org',
        '@type' => 'Bakery',
        'name' => SITE['name'],
        'alternateName' => SITE['legal'],
        'url' => SITE['url'] . '/',
        'telephone' => SITE['phone_tel'],
        'email' => SITE['email'],
        'foundingDate' => '1963',
        'image' => SITE['url'] . '/images/logo.png',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => SITE['street'],
            'addressLocality' => SITE['city'],
            'addressRegion' => SITE['region'],
            'postalCode' => SITE['postal'],
            'addressCountry' => 'CA',
        ],
        'openingHoursSpecification' => [[
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
            'opens' => SITE['opens'],
            'closes' => SITE['closes'],
        ]],
        'sameAs' => [SITE['facebook']],
    ];
}

/** Page title band used by inner pages. */
function page_banner($title, $script = '', $intro = '', $crumbs = [])
{
    ?>
<section class="banner">
  <div class="wrap">
<?php if ($crumbs): ?>
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a><?php foreach ($crumbs as $label => $href): ?> <span aria-hidden="true">/</span> <a href="<?= e($href) ?>"><?= e($label) ?></a><?php endforeach; ?></nav>
<?php endif; ?>
<?php if ($script): ?><p class="script light"><?= e($script) ?></p><?php endif; ?>
    <h1><?= e($title) ?></h1>
<?php if ($intro): ?><p class="banner-intro"><?= e($intro) ?></p><?php endif; ?>
  </div>
</section>
<?php
}

/** Address, hours and map. */
function visit_section()
{
    ?>
<section class="section" id="visit" aria-labelledby="visit-title">
  <div class="wrap visit">
    <div class="visit-info">
      <p class="script">Come visit us</p>
      <h2 id="visit-title">Taste the tradition for yourself</h2>
      <dl>
        <dt>Address</dt>
        <dd><?= e(SITE['street']) ?><br><?= e(SITE['city'] . ', ' . SITE['region'] . ' ' . SITE['postal']) ?></dd>
        <dt>Hours</dt>
        <dd><?= e(SITE['hours']) ?> <span class="open-now" data-open-now data-opens="<?= e(SITE['opens']) ?>" data-closes="<?= e(SITE['closes']) ?>"></span></dd>
        <dt>Phone</dt>
        <dd><a href="tel:<?= e(SITE['phone_tel']) ?>"><?= e(SITE['phone']) ?></a></dd>
        <dt>Fax</dt>
        <dd><?= e(SITE['fax']) ?></dd>
        <dt>Email</dt>
        <dd><a href="mailto:<?= e(SITE['email']) ?>"><?= e(SITE['email']) ?></a></dd>
      </dl>
      <a class="btn btn-solid" href="<?= e(SITE['maps_url']) ?>" rel="noopener">Get directions</a>
    </div>
    <div class="map">
      <iframe title="Map to La Sem Cakes" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
        src="https://maps.google.com/maps?q=La%20Sem%20Cakes%2C%201275%20Eglinton%20Ave%20E%2C%20Mississauga%2C%20ON%20L4W%202Z2&amp;z=15&amp;output=embed"></iframe>
    </div>
  </div>
</section>
<?php
}

/** "Ready to order?" band shown at the end of most pages. */
function order_band($heading = 'Tell us about your cake', $text = 'Share the date, the guest count and your idea. We’ll help with size, flavour and design, then have it ready for pick-up.')
{
    ?>
<section class="order-band" aria-label="Order">
  <div class="wrap order-inner">
    <div>
      <p class="script light">Planning a celebration?</p>
      <h2><?= e($heading) ?></h2>
      <p><?= e($text) ?></p>
    </div>
    <div class="order-actions">
      <a class="btn btn-cream btn-lg" href="tel:<?= e(SITE['phone_tel']) ?>">Call <?= e(SITE['phone']) ?></a>
      <a class="btn btn-outline-light btn-lg" href="<?= e(url('contact-us/')) ?>#enquiry">Send an enquiry</a>
    </div>
  </div>
</section>
<?php
}
