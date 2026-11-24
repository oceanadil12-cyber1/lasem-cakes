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
<?php
    // The 3D scene files wait until the page has loaded; everything else loads as normal.
    $late = [];
    foreach ($o['scripts'] ?? [] as $s):
        $src = strpos($s, 'vendor/') !== false ? url($s) : asset($s);
        if (strpos($s, 'three') !== false || strpos($s, 'scene3d') !== false || strpos($s, 'scenes/') !== false) { $late[] = $src; continue; }
?>
<script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
<script src="<?= e(asset('js/main.js')) ?>"></script>
<?php if ($late): ?>
<script>
/* 3D hero: fetched once the page has loaded and the browser is idle, in order. On Save-Data or
   2G connections it is skipped and the still photo is shown instead. */
(function (list) {
  var hero = document.querySelector('[data-cake-hero]');
  var c = navigator.connection || {};
  if (c.saveData || /(^|-)2g$/.test(c.effectiveType || '')) { if (hero) hero.classList.add('no-webgl'); return; }
  function next() {
    if (!list.length) return;
    var el = document.createElement('script');
    el.src = list.shift();
    el.onload = next;
    el.onerror = function () { if (hero) hero.classList.add('no-webgl'); };
    document.body.appendChild(el);
  }
  function go() { if ('requestIdleCallback' in window) requestIdleCallback(next, { timeout: 1200 }); else setTimeout(next, 150); }
  if (document.readyState === 'complete') go(); else window.addEventListener('load', go);
})(<?= json_encode($late, JSON_UNESCAPED_SLASHES) ?>);
</script>
<?php endif; ?>
</body>
</html>
<?php
}

function site_header()
{
    ?>
<header class="site-header" data-header>
<?php if (SITE['announcement'] !== ''): // set in the admin, Store settings ?>
  <p class="announce" role="note"><?= e(SITE['announcement']) ?></p>
<?php endif; ?>
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
<?php
    // Current page: its menu link (and the menu it sits under) is shown in orange.
    $here = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/') . '/';
    $isHere = function ($l) use ($here) {
        if (!empty($l['external'])) return false;
        return rtrim((string) parse_url($l['href'], PHP_URL_PATH), '/') . '/' === $here;
    };
    $cur = function ($l) use ($isHere) { return $isHere($l) ? ' aria-current="page"' : ''; };
foreach (nav_items() as $i => $item):
        $hasSub = !empty($item['children']) || !empty($item['mega']) || !empty($item['az']);
        $kids = array_merge($item['children'] ?? [], $item['az'] ?? [], $item['more'] ?? [], ...array_values($item['mega'] ?? []));
        $root = rtrim((string) parse_url($item['href'], PHP_URL_PATH), '/') . '/';
        $active = $isHere($item) || array_filter($kids, $isHere)
            || (empty($item['external']) && $root !== url() && strpos($here, $root) === 0); ?>
        <li class="<?= $hasSub ? 'has-sub' : '' ?><?= !empty($item['mega']) || !empty($item['az']) ? ' has-mega' : '' ?><?= $active ? ' is-current' : '' ?>">
          <a href="<?= e($item['href']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $cur($item) ?>><?= e($item['label']) ?></a>
<?php if ($hasSub): ?>
          <button class="sub-toggle" type="button" aria-expanded="false" aria-controls="sub-<?= $i ?>" aria-label="Show <?= e($item['label']) ?> pages" data-sub-toggle>
            <svg viewBox="0 0 12 12" aria-hidden="true"><path d="M2 4l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
          </button>
<?php if (!empty($item['az'])): ?>
          <div class="sub mega mega-az" id="sub-<?= $i ?>">
            <ul class="az-list"><?php foreach ($item['az'] as $l): ?><li><a href="<?= e($l['href']) ?>"<?= $cur($l) ?>><?= e($l['label']) ?></a></li><?php endforeach; ?></ul>
<?php if (!empty($item['more'])): ?>
            <p class="az-more"><?php foreach ($item['more'] as $l): ?><a href="<?= e($l['href']) ?>"<?= $cur($l) ?>><?= e($l['label']) ?></a><?php endforeach; ?></p>
<?php endif; ?>
          </div>
<?php elseif (!empty($item['mega'])): ?>
          <div class="sub mega" id="sub-<?= $i ?>">
<?php foreach ($item['mega'] as $heading => $links): ?>
            <div>
              <p class="sub-head"><?= e($heading) ?></p>
              <ul><?php foreach ($links as $l): ?><li><a href="<?= e($l['href']) ?>"<?= $cur($l) ?>><?= e($l['label']) ?></a></li><?php endforeach; ?></ul>
            </div>
<?php endforeach; ?>
          </div>
<?php else: ?>
          <ul class="sub" id="sub-<?= $i ?>">
            <?php foreach ($item['children'] as $l): ?><li><a href="<?= e($l['href']) ?>"<?= !empty($l['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $cur($l) ?>><?= e($l['label']) ?></a></li><?php endforeach; ?>
          </ul>
<?php endif; ?>
<?php endif; ?>
        </li>
<?php endforeach; ?>
        <li class="nav-cta-m"><button class="btn btn-solid" type="button" data-order-request>Order Request</button></li>
      </ul>
    </nav>
    <div class="bar-actions">
      <a class="tel" href="tel:<?= e(SITE['phone_tel']) ?>"><?= e(SITE['phone']) ?></a>
      <button class="btn btn-solid" type="button" data-order-request>Order Request</button>
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
    <p>&copy; <?= date('Y') ?> <?= e(SITE['legal']) ?> <span class="foot-credit">Website design by <a href="https://seoservicesincanada.ca/" target="_blank" rel="noopener">Seo Services In Canada</a></span></p>
    <p class="policies">
      <?php foreach ($pages as $slug => $p) {
          if ($p['type'] === 'policy') {
              echo '<a href="' . e(url($slug . '/')) . '">' . e($p['title']) . '</a> ';
          }
      } ?>
    </p>
  </div>
</footer>
<?php order_request_dialog(); ?>
<?php
}

/**
 * The "Order Request" pop-up (same fields as the staging site's): opened by any [data-order-request]
 * button. It posts to api/contact.php, so requests land in the admin's Cake enquiries and the bakery's email.
 */
function order_request_dialog()
{
    ?>
<dialog class="req" id="order-request" aria-labelledby="req-title">
  <form class="req-form" method="post" action="<?= e(url('api/contact.php')) ?>" data-request-form novalidate>
    <button class="req-close" type="button" data-request-close aria-label="Close">&times;</button>
    <p class="script">Custom orders</p>
    <h2 id="req-title">Order Request</h2>
    <p class="req-for" data-request-for-label hidden></p>
    <input type="hidden" name="form" value="request">
    <input type="hidden" name="occasion" value="" data-request-for>
    <input type="hidden" name="page" value="<?= e(strtok($_SERVER['REQUEST_URI'] ?? '', '?')) ?>">
    <label>Name<input name="name" autocomplete="name" maxlength="200"></label>
    <div class="req-row">
      <label><span>Order date <b aria-hidden="true">*</b></span><input name="date" type="date" required min="<?= e(date('Y-m-d')) ?>"></label>
      <label><span>Phone number <b aria-hidden="true">*</b></span><input name="phone" type="tel" required autocomplete="tel" maxlength="40"></label>
    </div>
    <label>Email address<input name="email" type="email" autocomplete="email" maxlength="200"></label>
    <label>Order description<textarea name="message" rows="4" maxlength="4000" placeholder="Cake, size, flavour, design, writing on the cake…"></textarea></label>
    <label class="hp" aria-hidden="true">Leave this empty<input name="website" tabindex="-1" autocomplete="off"></label>
    <p class="req-status" role="status" aria-live="polite" data-request-status></p>
    <button class="btn btn-solid btn-lg" type="submit" data-request-send>Send</button>
  </form>
  <div class="req-done" data-request-done hidden>
    <button class="req-close" type="button" data-request-close aria-label="Close">&times;</button>
    <h2>Thank you!</h2>
    <p>Your order request is on its way. We’ll get back to you soon. For anything urgent, call <a href="tel:<?= e(SITE['phone_tel']) ?>"><?= e(SITE['phone']) ?></a>.</p>
    <button class="btn btn-line" type="button" data-request-close>Close</button>
  </div>
</dialog>
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
        'sameAs' => array_values(array_filter([SITE['facebook'], GOOGLE_PROFILE_URL, TOP_CHOICE_LISTING])),
        'award' => [
            'Top Choice Award, Top Cake Boutique in Mississauga, every year 2015–2026',
            'Mississauga News Readers’ Choice Award',
            'People’s Choice Award',
        ],
        'subjectOf' => [
            '@type' => 'Collection',
            'name' => 'La Sem Pasticcerie, Mapping Italian-Canadian Foodways (University of Toronto Mississauga)',
            'url' => FOODWAYS_LA_SEM,
        ],
    ];
}

/** Page title band used by inner pages. */
function page_banner($title, $script = '', $intro = '', $crumbs = [], $slides = [])
{
    ?>
<section class="banner<?= $slides ? ' banner-slides' : '' ?>"<?= $slides ? ' data-banner-slider' : '' ?>>
<?php if ($slides): ?>
  <div class="banner-media" aria-hidden="true">
    <div class="banner-track" data-banner-track>
<?php foreach ($slides as $i => $s): ?>
      <?= str_replace('<img ', '<img style="object-position:' . e($s[1] ?? '50% 50%') . '" ', img($s[0], '', ['sizes' => '100vw', 'eager' => $i === 0, 'priority' => $i === 0])) ?>
<?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
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
/** $photo: false leaves out the storefront photo (e.g. About us, whose banner already shows it). */
function visit_section($photo = true)
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
    <div class="visit-media<?= $photo ? '' : ' map-only' ?>">
<?php if ($photo): ?>
    <figure class="visit-photo"><?= img('2026/06/La-sem-Name-board.jpg', 'The La Sem Patisserie and Italian Bakery storefront on Eglinton Avenue East', ['sizes' => '(max-width: 900px) 100vw, 55vw']) ?><figcaption>Look for the orange sign at 1275 Eglinton Ave. East</figcaption></figure>
<?php endif; ?>
    <div class="map">
      <iframe title="Map to La Sem Cakes" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
        src="https://maps.google.com/maps?q=La%20Sem%20Cakes%2C%201275%20Eglinton%20Ave%20E%2C%20Mississauga%2C%20ON%20L4W%202Z2&amp;z=15&amp;output=embed"></iframe>
    </div>
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
      <button class="btn btn-outline-light btn-lg" type="button" data-order-request>Online Custom Order Request</button>
    </div>
  </div>
</section>
<?php
}
