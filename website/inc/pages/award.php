<?php
// One award (Top Choice, Readers' Choice, People's Choice): what it is and every badge won.
page_start([
    'title'       => $page['title'],
    'description' => $page['body'][0]['x'] ?? 'La Sem Cakes has won the ' . $page['title'] . '.',
    'path'        => $page['path'],
]);
page_banner($page['title'], 'Awards', '', ['Awards' => url('awards/'), $page['title'] => url($page['path'])]);
?>
<section class="section">
  <div class="wrap">
<?php if (!empty($page['body'])): ?>
    <div class="prose narrow">
<?php foreach ($page['body'] as $b): ?>
      <p><?= e($b['x']) ?></p>
<?php endforeach; ?>
<?php if (!empty($page['link'])): ?>
      <p><a class="link" href="<?= e($page['link']) ?>" rel="noopener">More about the <?= e($page['title']) ?></a></p>
<?php endif; ?>
    </div>
<?php endif; ?>
    <div class="badges" data-lightbox-group>
<?php foreach ($page['images'] as $im):
        preg_match('/(20\d\d)/', $im['src'], $y); ?>
      <a class="badge" href="<?= e(url('images/uploads/' . preg_replace('/\.\w+$/', '.webp', $im['src']))) ?>" data-lightbox>
        <?= img($im['src'], $page['title'] . (isset($y[1]) ? ' ' . $y[1] : ''), ['sizes' => '220px']) ?>
<?php if (isset($y[1])): ?><span><?= e($y[1]) ?></span><?php endif; ?>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tint">
  <div class="wrap">
    <div class="pills">
<?php foreach (content()['pages'] as $s => $p): if ($p['type'] !== 'award') continue; ?>
      <a class="pill<?= $s === $page['slug'] ? ' is-current' : '' ?>" href="<?= e(url('awards/' . $s . '/')) ?>"><?= e($p['menu'] ?? $p['title']) ?></a>
<?php endforeach; ?>
      <a class="pill" href="<?= e(url('articles-videos/')) ?>">Award ceremony videos</a>
    </div>
  </div>
</section>
<?php page_end();
