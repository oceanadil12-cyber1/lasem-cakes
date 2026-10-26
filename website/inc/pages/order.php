<?php
// Online ordering for pickup: the full bakery menu, cart and checkout on La Sem's own site.
// Menu and prices come from data/menu.json (imported from the DoorDash store; the admin panel will edit it).
$menu = json_decode(file_get_contents(__DIR__ . '/../../data/menu.json'), true);
foreach ($menu['categories'] as &$c) {
    foreach ($c['products'] as &$p) {
        if (!empty($p['img'])) $p['img'] = url($p['img']);
    }
}
unset($c, $p);
$fmt = function ($h) { return date('g:i a', strtotime($h)); };
page_start([
    'title'       => 'Order Online for Pickup',
    'path'        => 'order/',
    'description' => 'Order La Sem cakes, cannoli and pastries, zeppole, cookies, croissants and focaccia sandwiches online for pickup at 1275 Eglinton Ave. East, Mississauga.',
    'scripts'     => ['js/order.js'],
]);
?>
<section class="order-hero">
  <div class="wrap order-hero-inner">
    <div>
      <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a> <span aria-hidden="true">/</span> <a href="<?= e(url('order/')) ?>">Order online</a></nav>
      <h1>Order for pickup</h1>
      <p class="banner-intro">Cakes, pastries, zeppole, cookies and focaccia sandwiches, ready when you arrive. Pay at the counter when you pick up.</p>
    </div>
    <ul class="order-facts">
      <li><b>Pickup at</b><?= e(SITE['street']) ?>, <?= e(SITE['city']) ?></li>
      <li><b>Online ordering</b>Every day, <?= e($fmt($menu['hours']['opens'])) ?> – <?= e($fmt($menu['hours']['closes'])) ?></li>
      <li><b>Ready in</b>About <?= (int) $menu['leadMinutes'] ?> minutes, or order up to <?= (int) $menu['daysAhead'] ?> days ahead</li>
    </ul>
  </div>
</section>

<div class="order-bar" data-order-bar>
  <div class="wrap order-bar-inner">
    <label class="menu-search">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 4a6.5 6.5 0 0 1 5.2 10.4l4.4 4.4-1.4 1.4-4.4-4.4A6.5 6.5 0 1 1 10.5 4zm0 2a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9z"/></svg>
      <span class="sr">Search the menu</span>
      <input type="search" placeholder="Search the menu" data-menu-search autocomplete="off">
    </label>
    <nav class="cat-nav" aria-label="Menu categories" data-cat-nav>
      <a href="#cat-popular" class="is-active">Most ordered</a>
<?php foreach ($menu['categories'] as $c): ?>
      <a href="#cat-<?= e($c['id']) ?>"><?= e($c['name']) ?></a>
<?php endforeach; ?>
    </nav>
  </div>
</div>

<section class="order-page">
  <div class="wrap order-layout">
    <div class="menu-col" data-menu>
      <noscript><p class="notice err">Online ordering needs JavaScript. Please turn it on, or call us at <?= e(SITE['phone']) ?> to order.</p></noscript>
    </div>
    <aside class="cart-col" aria-label="Your order" data-cart-panel>
      <div class="cart-box" data-cart></div>
    </aside>
  </div>
</section>

<button class="cart-fab" type="button" data-cart-fab hidden>
  <span class="cart-fab-count" data-fab-count>0</span>
  <span>View order</span>
  <b data-fab-total>$0.00</b>
</button>

<div class="sheet" data-item-sheet hidden role="dialog" aria-modal="true" aria-labelledby="sheet-title"></div>
<div class="sheet sheet-cart" data-cart-sheet hidden role="dialog" aria-modal="true" aria-label="Your order"></div>

<script type="application/json" id="menu-data"><?= json_encode($menu, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script>window.LASEM_ORDER = { api: <?= json_encode(url('api/order.php')) ?>, terms: <?= json_encode(url('terms-conditions/')) ?>, contact: <?= json_encode(url('contact-us/') . '#enquiry') ?>, cancel: <?= json_encode(url('cancellation-policy/')) ?>, phone: <?= json_encode(SITE['phone']) ?>, tel: <?= json_encode(SITE['phone_tel']) ?> };</script>
<?php page_end();
