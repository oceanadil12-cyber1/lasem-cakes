<?php
// Landing pages for the three menu sections: /bakery/, /gallery/, /awards/.
$pages = content()['pages'];
$of = function ($type) use ($pages) {
    return array_filter($pages, function ($p) use ($type) { return $p['type'] === $type; });
};
$grid = function ($items, $prefix = '', $showCount = false) {
    echo '<div class="tiles">';
    foreach ($items as $slug => $p) {
        echo '<a class="tile" href="' . e(url($prefix . $slug . '/')) . '">';
        if (!empty($p['images'])) echo img($p['images'][0]['src'], '', ['sizes' => '(max-width: 600px) 100vw, (max-width: 1000px) 50vw, 33vw']);
        echo '<span class="tile-label"><b>' . e($p['menu'] ?? $p['title']) . '</b>';
        if ($showCount) echo '<i>' . count($p['images']) . ' photos</i>';
        echo '</span></a>';
    }
    echo '</div>';
};

if ($section === 'bakery') {
    page_start(['title' => 'Cakes & Bakery', 'path' => 'bakery/', 'description' => 'Custom cakes for every occasion and fresh Italian pastries, zeppole, cookies, gelato, cheesecake and focaccia from La Sem in Mississauga.']);
    page_banner('Cakes & bakery', 'Made fresh in Mississauga', 'Custom cakes for every celebration, and the pastries, cookies, gelato and bread on our counter every day.');
    ?>
<section class="section">
  <div class="wrap">
    <header class="section-head"><p class="script">Custom cakes</p><h2>Cakes for every occasion</h2>
      <p>Choose the sponge, filling and icing, the shape and the size. See <a class="link" href="<?= e(url('cake-type-fillings/')) ?>">all cake types &amp; fillings</a>.</p></header>
    <?php $grid($of('occasion')); ?>
  </div>
</section>
<section class="section section-tint">
  <div class="wrap">
    <header class="section-head"><p class="script">From our counter</p><h2>Pastries, gelato &amp; more</h2></header>
    <?php $grid($of('bakery')); ?>
  </div>
</section>
<?php
    order_band();
} elseif ($section === 'gallery') {
    page_start(['title' => 'Cake Gallery', 'path' => 'gallery/', 'description' => 'Browse photos of La Sem birthday, wedding, baby shower, religious and specialty cakes, pastries, cookies and focaccia.']);
    page_banner('Gallery', 'Made at La Sem', 'Browse cakes and treats we’ve made over the years. Bring a photo you like when you order.');
    ?>
<section class="section">
  <div class="wrap"><?php $grid($of('gallery'), '', true); ?></div>
</section>
<?php
    order_band('Like something you see?', 'Show us a photo from the gallery, or bring your own idea. We’ll help with size, flavour and design.');
} else {
    page_start(['title' => 'Awards', 'path' => 'awards/', 'description' => 'La Sem has been named Top Cake Boutique in Mississauga every year since 2015, plus Readers’ Choice and People’s Choice awards.']);
    page_banner('Awards', 'Voted by our customers', 'Top Cake Boutique in Mississauga every year since 2015, with Readers’ Choice and People’s Choice awards along the way.');
    ?>
<section class="section">
  <div class="wrap"><?php $grid($of('award'), 'awards/'); ?></div>
</section>
<?php
}
page_end();
