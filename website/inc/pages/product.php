<?php
// One cake occasion (Wedding Cakes, Baptism Cakes…) or one bakery item (Gelato, Zeppole…).
$isCake = $page['type'] === 'occasion';
$first = $page['images'][0]['src'] ?? null;
$scene = has_scene($page['slug']);
page_start([
    'title'       => $page['title'],
    'description' => $page['description'],
    'path'        => $page['path'],
    'og_image'    => $first ? 'images/uploads/' . preg_replace('/\.\w+$/', '.webp', $first) : null,
    'hero'        => $scene,
    'scripts'     => $scene ? scene_scripts($page['slug']) : [],
]);
if ($scene) {
    scene_hero($page, $isCake
        ? '<a class="btn btn-solid btn-lg" href="' . e(url('contact-us/')) . '?cake=' . e(rawurlencode($page['title'])) . '#enquiry">Request this cake</a>'
          . '<a class="btn btn-ghost btn-lg" href="' . e(url('cake-type-fillings/')) . '">Flavours &amp; fillings</a>'
        : '<a class="btn btn-solid btn-lg" href="' . e(url('order/')) . '">Order for pickup</a>'
          . '<a class="btn btn-ghost btn-lg" href="tel:' . e(SITE['phone_tel']) . '">Call ' . e(SITE['phone']) . '</a>');
} else {
    page_banner($page['heading'] ?? $page['title'], $isCake ? 'Custom cakes' : 'From our counter', '', ['Bakery' => url('bakery/'), ($page['menu'] ?? $page['title']) => url($page['path'])]);
}
?>
<section class="section">
  <div class="wrap detail">
    <div class="detail-text prose">
<?php foreach ($page['body'] ?? [] as $b): ?>
      <p><?= e($b['x']) ?></p>
<?php endforeach; ?>
      <div class="detail-actions">
<?php if ($isCake): ?>
        <a class="btn btn-solid btn-lg" href="<?= e(url('contact-us/')) ?>?cake=<?= e(rawurlencode($page['title'])) ?>#enquiry">Request this cake</a>
        <a class="btn btn-line btn-lg" href="tel:<?= e(SITE['phone_tel']) ?>">Call <?= e(SITE['phone']) ?></a>
<?php else: ?>
        <a class="btn btn-solid btn-lg" href="<?= e(url('order/')) ?>">Order online</a>
        <a class="btn btn-line btn-lg" href="tel:<?= e(SITE['phone_tel']) ?>">Call <?= e(SITE['phone']) ?></a>
<?php endif; ?>
      </div>
<?php if ($isCake): ?>
      <p class="small-note">Not sure about flavours? See every <a href="<?= e(url('cake-type-fillings/')) ?>">cake type, filling and icing</a> we offer.</p>
<?php endif; ?>
    </div>
    <div class="detail-media count-<?= min(count($page['images']), 3) ?>" data-lightbox-group>
<?php foreach ($page['images'] as $i => $im): ?>
      <a href="<?= e(url('images/uploads/' . preg_replace('/\.\w+$/', '.webp', $im['src']))) ?>" data-lightbox>
        <?= img($im['src'], $im['alt'] ?: $page['title'] . ' by La Sem', ['sizes' => '(max-width: 900px) 100vw, 45vw', 'eager' => $i === 0]) ?>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tint">
  <div class="wrap">
    <header class="section-head">
      <p class="script"><?= $isCake ? 'More celebrations' : 'More from our counter' ?></p>
      <h2><?= $isCake ? 'Cakes for every occasion' : 'Fresh every day' ?></h2>
    </header>
    <div class="cards">
<?php foreach (content()['pages'] as $s => $p):
        if ($p['type'] !== $page['type'] || $s === $page['slug'] || empty($p['images'])) continue; ?>
      <a class="card" href="<?= e(url($s . '/')) ?>">
        <?= img($p['images'][0]['src'], '', ['sizes' => '(max-width: 600px) 50vw, 240px']) ?>
        <span><?= e($p['menu'] ?? $p['title']) ?></span>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>

<?php order_band(); page_end();
