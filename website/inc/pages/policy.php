<?= body_html($page['body'] ?? []) ?>
<?php
// Terms, returns, cancellation, shipping and privacy pages.
page_start(['title' => $page['title'], 'description' => $page['description'], 'path' => $page['path']]);
page_banner($page['title'], 'Policies');
?>
<section class="section">
  <div class="wrap prose narrow">
<?= body_html($page['body'] ?? []) ?>
    <p class="small-note">Questions? Call <a href="tel:<?= e(SITE['phone_tel']) ?>"><?= e(SITE['phone']) ?></a> or email <a href="mailto:<?= e(SITE['email']) ?>"><?= e(SITE['email']) ?></a>.</p>
    <div class="pills">
<?php foreach (content()['pages'] as $s => $p): if ($p['type'] !== 'policy') continue; ?>
      <a class="pill<?= $s === $page['slug'] ? ' is-current' : '' ?>" href="<?= e(url($s . '/')) ?>"><?= e($p['title']) ?></a>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php page_end();
