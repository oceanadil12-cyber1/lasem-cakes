<?php
// One photo gallery (Birthday & Novelty, Wedding & Shower…). Every photo is named under it: its own "title"
// (e.g. "Birthday & Novelty Cake #12", as on the staging site), else "<caption> #<place>". 40 photos a page. Photos open full size in a lightbox.
$label   = $page['menu'] ?? $page['title'];
$caption = $page['caption'] ?? $label;
$all     = $page['images'];
$total   = count($all);
$per     = 40;
$pages_n = max(1, (int) ceil($total / $per));
$current = min($pages_n, max(1, (int) ($_GET['page'] ?? 1)));
$offset  = ($current - 1) * $per;
$shown   = array_slice($all, $offset, $per, true);
$first   = $all[0]['src'] ?? null;
$page_url = function ($n) use ($page) { return url($page['path']) . ($n > 1 ? '?page=' . $n : ''); };
page_start([
    'title'       => $page['title'] . ' Gallery' . ($current > 1 ? ' (page ' . $current . ')' : ''),
    'description' => $page['description'],
    'path'        => $page['path'] . ($current > 1 ? '?page=' . $current : ''),
    'og_image'    => $first ? 'images/uploads/' . preg_replace('/\.\w+$/', '.webp', $first) : null,
]);
page_banner($label, 'Gallery', $total ? $total . ' photos of ' . strtolower($label) . ' made at La Sem. Tap a photo to see it larger.' : 'Photos of ' . strtolower($label) . ' made at La Sem.', ['Gallery' => url('gallery/'), $label => url($page['path'])]);
?>
<section class="section">
  <div class="wrap">
<?php if (!empty($page['body'])): ?>
    <div class="prose narrow"><?= body_html($page['body']) ?></div>
<?php endif; ?>
<?php if ($pages_n > 1): ?>
    <p class="gallery-range">Showing <?= $offset + 1 ?>–<?= $offset + count($shown) ?> of <?= $total ?> photos</p>
<?php endif; ?>
    <div class="masonry gallery-grid" data-lightbox-group>
<?php foreach ($shown as $i => $im):
        $name = !empty($im['title']) ? $im['title'] : $caption . ' #' . ($i + 1);
        $alt  = $im['alt'] && $im['alt'] !== $label ? $im['alt'] : $name; ?>
      <figure>
        <a href="<?= e(img_url($im['src'])) ?>" data-lightbox data-caption="<?= e($name) ?>">
          <?= img($im['src'], $alt, ['sizes' => '(max-width: 600px) 50vw, (max-width: 1000px) 33vw, 300px', 'eager' => $i - $offset < 4]) ?>
        </a>
        <figcaption><?= e($name) ?></figcaption>
      </figure>
<?php endforeach; ?>
    </div>
<?php if ($pages_n > 1): ?>
    <nav class="pager" aria-label="Gallery pages">
<?php if ($current > 1): ?>
      <a class="pager-step" href="<?= e($page_url($current - 1)) ?>" rel="prev">&lsaquo; Previous</a>
<?php endif; ?>
<?php for ($n = 1; $n <= $pages_n; $n++):
        if ($n !== 1 && $n !== $pages_n && abs($n - $current) > 2) {
            if ($n === 2 || $n === $pages_n - 1) echo '      <span class="pager-gap">…</span>' . PHP_EOL;
            continue;
        } ?>
      <a href="<?= e($page_url($n)) ?>"<?= $n === $current ? ' aria-current="page"' : '' ?>><?= $n ?></a>
<?php endfor; ?>
<?php if ($current < $pages_n): ?>
      <a class="pager-step" href="<?= e($page_url($current + 1)) ?>" rel="next">Next &rsaquo;</a>
<?php endif; ?>
    </nav>
<?php endif; ?>
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
