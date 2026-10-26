<?php
// One photo gallery (Birthday & Novelty, Wedding & Shower…). Photos open full size in a lightbox.
$label = $page['menu'] ?? $page['title'];
$first = $page['images'][0]['src'] ?? null;
page_start([
    'title'       => $page['title'] . ' Gallery',
    'description' => $page['description'],
    'path'        => $page['path'],
    'og_image'    => $first ? 'images/uploads/' . preg_replace('/\.\w+$/', '.webp', $first) : null,
]);
page_banner($label, 'Gallery', count($page['images']) . ' photos of ' . strtolower($label) . ' made at La Sem. Tap a photo to see it larger.', ['Gallery' => url('gallery/'), $label => url($page['path'])]);
?>
<section class="section">
  <div class="wrap">
<?php if (!empty($page['body'])): ?>
    <div class="prose narrow"><?php foreach ($page['body'] as $b): ?><p><?= e($b['x']) ?></p><?php endforeach; ?></div>
<?php endif; ?>
    <div class="masonry" data-lightbox-group>
<?php foreach ($page['images'] as $i => $im):
        $alt = ($im['alt'] ?: $label) . ' #' . ($i + 1); ?>
      <a href="<?= e(url('images/uploads/' . preg_replace('/\.\w+$/', '.webp', $im['src']))) ?>" data-lightbox>
        <?= img($im['src'], $alt, ['sizes' => '(max-width: 600px) 50vw, (max-width: 1000px) 33vw, 300px', 'eager' => $i < 4]) ?>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tint">
  <div class="wrap">
    <header class="section-head"><p class="script">Keep browsing</p><h2>More galleries</h2></header>
    <div class="cards">
<?php foreach (content()['pages'] as $s => $p):
        if ($p['type'] !== 'gallery' || $s === $page['slug'] || empty($p['images'])) continue; ?>
      <a class="card" href="<?= e(url($s . '/')) ?>">
        <?= img($p['images'][0]['src'], '', ['sizes' => '(max-width: 600px) 50vw, 240px']) ?>
        <span><?= e($p['menu'] ?? $p['title']) ?></span>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>

<?php order_band('Like something you see?', 'Show us a photo from the gallery, or bring your own idea. We’ll help with size, flavour and design.'); page_end();
