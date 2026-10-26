<?php
// Terms, returns, cancellation, shipping and privacy pages.
page_start(['title' => $page['title'], 'description' => $page['description'], 'path' => $page['path']]);
page_banner($page['title'], 'Policies');
?>
<section class="section">
  <div class="wrap prose narrow">
<?php
$inList = false;
foreach ($page['body'] ?? [] as $b) {
    if ($b['t'] === 'li' && !$inList) { echo "<ul>\n"; $inList = true; }
    if ($b['t'] !== 'li' && $inList) { echo "</ul>\n"; $inList = false; }
    // Some staging paragraphs pack a list into one line with "•" bullets.
    $parts = array_map('trim', explode('•', $b['x']));
    if ($b['t'] === 'p' && count($parts) > 1) {
        echo '<p>' . e($parts[0]) . "</p>\n<ul>";
        foreach (array_slice($parts, 1) as $li) echo '<li>' . e($li) . '</li>';
        echo "</ul>\n";
    } else {
        echo $b['t'] === 'li' ? '<li>' . e($b['x']) . "</li>\n" : '<p>' . e($b['x']) . "</p>\n";
    }
}
if ($inList) echo "</ul>\n";
?>
    <p class="small-note">Questions? Call <a href="tel:<?= e(SITE['phone_tel']) ?>"><?= e(SITE['phone']) ?></a> or email <a href="mailto:<?= e(SITE['email']) ?>"><?= e(SITE['email']) ?></a>.</p>
    <div class="pills">
<?php foreach (content()['pages'] as $s => $p): if ($p['type'] !== 'policy') continue; ?>
      <a class="pill<?= $s === $page['slug'] ? ' is-current' : '' ?>" href="<?= e(url($s . '/')) ?>"><?= e($p['title']) ?></a>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php page_end();
