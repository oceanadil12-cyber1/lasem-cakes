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
// As on the staging site: a custom order request (pop-up) and DoorDash. Cakes lead with the request,
// counter items with DoorDash.
$requestBtn = function ($cls) use ($page) {
    return '<button class="btn ' . $cls . ' btn-lg" type="button" data-order-request data-request-for="' . e($page['menu'] ?? $page['title']) . '">Online Custom Order Request</button>';
};
$doordashBtn = function ($cls) {
    return '<a class="btn ' . $cls . ' btn-lg" href="' . e(DOORDASH_URL) . '" target="_blank" rel="noopener">DoorDash Online</a>';
};
if ($scene) {
    scene_hero($page, $isCake
        ? $requestBtn('btn-solid') . $doordashBtn('btn-ghost')
        : $doordashBtn('btn-solid') . $requestBtn('btn-ghost'));
} else {
    page_banner($page['heading'] ?? $page['title'], $isCake ? 'Custom cakes' : 'From our counter', '', ['Bakery' => url('bakery/'), ($page['menu'] ?? $page['title']) => url($page['path'])]);
}
?>
<section class="section">
  <div class="wrap detail">
    <div class="detail-text prose">
      <?= body_html($page['body'] ?? []) ?>
      <div class="detail-actions">
        <?= $isCake ? $requestBtn('btn-solid') . $doordashBtn('btn-line') : $doordashBtn('btn-solid') . $requestBtn('btn-line') ?>
      </div>
<?php if ($isCake): ?>
      <p class="small-note">Not sure about flavours? See every <a href="<?= e(url('cake-type-fillings/')) ?>">cake type, filling and icing</a> we offer.</p>
<?php endif; ?>
    </div>
    <div class="showcase" data-showcase data-lightbox-group>
      <div class="showcase-stage">
<?php $n = count($page['images']);
      foreach ($page['images'] as $i => $im):
        $m = image_manifest()[$im['src']] ?? null;
        $bg = !empty($m['sm']) ? url('images/uploads/' . preg_replace('/\.\w+$/', '', $im['src']) . '-800.webp') : img_url($im['src']); ?>
        <a class="showcase-slide<?= $i === 0 ? ' is-active' : '' ?>" href="<?= e(img_url($im['src'])) ?>" data-lightbox<?= $i ? ' tabindex="-1"' : '' ?>>
          <span class="showcase-bg" style="background-image:url('<?= e($bg) ?>')"></span>
          <?= img($im['src'], $im['alt'] ?: $page['title'] . ' by La Sem', ['sizes' => '(max-width: 900px) 100vw, 45vw', 'eager' => $i === 0]) ?>
        </a>
<?php endforeach; ?>
<?php if ($n > 1): ?>
        <button class="showcase-nav showcase-prev" type="button" data-showcase-step="-1" aria-label="Previous photo">&#8249;</button>
        <button class="showcase-nav showcase-next" type="button" data-showcase-step="1" aria-label="Next photo">&#8250;</button>
        <span class="showcase-count" aria-live="polite"><b data-showcase-index>1</b> / <?= $n ?></span>
<?php endif; ?>
      </div>
<?php if ($n > 1): ?>
      <div class="showcase-thumbs" role="tablist" aria-label="<?= e($page['title']) ?> photos">
<?php foreach ($page['images'] as $i => $im): ?>
        <button class="showcase-thumb<?= $i === 0 ? ' is-active' : '' ?>" type="button" role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" data-showcase-go="<?= $i ?>" aria-label="Photo <?= $i + 1 ?> of <?= $n ?>">
          <?= img($im['src'], '', ['sizes' => '96px']) ?>
        </button>
<?php endforeach; ?>
      </div>
<?php endif; ?>
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
