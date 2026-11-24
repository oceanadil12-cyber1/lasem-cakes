<?php
// Articles & Videos: the bakery's own videos (promotional films, products, cannoli, zeppole, focaccia,
// croccante, gelato), award ceremonies on YouTube and press links. Mirrors the staging site's posts.
// Every video file is a light 720p copy in videos/<folder>/ with a preview frame in images/videos/;
// nothing downloads until someone presses play, and YouTube loads only when clicked.

// A clip: [file under videos/ without .mp4, label]. Its preview frame is images/videos/<basename>.webp.
$posts = [
    ['promotional-video-3', 'Promotional videos', 'Our main promotional films, from the newest to the very first.', ['clips' => [
        ['promo/main-2025', 'Main video 2025'],
        ['promo/main-2020', 'Main video 2020'],
        ['promo/main-2015', 'Main video 2015'],
        ['promo/main-2000', 'Main video 2000'],
    ]]],
    ['other-promotional-videos', 'Other promotional videos', 'Short films from the bakery: our counters, sfoglia and cannoli, coffee and dolci.', ['clips' => [
        ['promo/promo-01', 'Pastries up close'],
        ['promo/promo-02', 'Welcome to La Sem'],
        ['promo/promo-03', 'Come visit the bakery'],
        ['promo/promo-04', 'Macarons'],
        ['promo/promo-05', 'A pastry with a cherry on top'],
        ['promo/promo-06-sfoglia-cannoli', 'Sfoglia and cannoli'],
        ['promo/promo-07-coffee', 'Enjoy a coffee'],
        ['promo/promo-08-dolce-uovo', 'Dolce uovo'],
    ]]],
    ['other-product-videos', 'Other product videos', 'Cakes and treats from our kitchen, decorated by hand.', ['clips' => [
        ['products/product-video-01', 'Finishing a white tiered cake'],
        ['products/product-video-02', 'Pink cake with yellow roses'],
        ['products/product-video-03', 'Fruit and cream cake with cream puffs'],
        ['products/product-video-04', 'Lilac butterfly cake'],
        ['products/product-video-05', 'Decorating a tall white cake'],
        ['products/product-video-06', 'Blue tile cake with red sugar flowers'],
        ['products/product-video-07', 'Black and gold birthday cake'],
        ['products/product-video-08', 'White cake with painted flowers'],
        ['products/product-video-09', 'Two-tier cake with pink flowers'],
        ['products/product-video-10', 'Cartoon character cake'],
        ['products/product-video-11', 'Halloween cake'],
        ['products/product-video-12', 'Piping a pink number cake'],
        ['products/product-video-13', 'Layering a cake'],
        ['Cup-Cakes', 'Cupcakes'],
        ['Keto-Dessert-1', 'Keto desserts'],
        ['Birthday-Novelty-Cake-416', 'A birthday novelty cake'],
    ]]],
    ['cannol-from-scrach', 'Cannoli, made in house from scratch', 'We make our cannoli in house from scratch, still done the old-fashioned way.', ['clips' => [
        ['bakery/cannoli-2025', 'Cannoli, 2025'],
        ['bakery/cannoli-2015', 'Cannoli, 2015'],
    ]]],
    ['z-e-p-p-o-l-e-time-year-make-zeppole-house-scratch', 'Zeppole time', 'It’s that time of year again. We make our zeppole in house from scratch.', ['clips' => [
        ['bakery/zeppole-1', 'Zeppole, video 1'],
        ['bakery/zeppole-2', 'Zeppole, video 2'],
    ]]],
    ['focaccia', 'Focaccia', 'Our focaccia, baked from a recipe we have used for more than 60 years, and the sandwiches we make with it.', ['clips' => [
        ['bakery/focaccia-sandwiches', 'Focaccia sandwiches'],
    ]]],
    ['croccante-almond-brittle', 'Croccante (almond brittle)', 'Toasted almonds in golden caramel, made the Italian way.', ['clips' => [
        ['bakery/croccante', 'Croccante (almond brittle)'],
    ]]],
    ['gelato-videos', 'Gelato', 'Italian gelato, made at La Sem.', ['clips' => [
        ['bakery/gelato', 'Gelato'],
    ]]],
    ['top-choice-2019', 'Top Choice 2019', 'Relive the moment at the Top Choice Awards Gala 2019, as La Sem Patisserie wins Top Cake Boutique of 2019 in Mississauga!', ['youtube' => ['AS7t4B_1KA8', 'C_4FkAdjzGo']]],
    ['top-choice-2018', 'Top Choice 2018', 'Relive the moment at the Top Choice Awards Gala 2018, as La Sem Patisserie wins Top Cake Boutique of 2018 in Mississauga!', ['youtube' => ['99F4QSBTvZQ']]],
    ['relive-moment-top-choice-awards-gala-2017', 'Relive the moment! Top Choice Awards Gala 2017', 'La Sem Patisserie wins Top Cake Boutique of 2017 in Mississauga!', ['youtube' => ['dCv4Op3aDv4']]],
    ['2016-top-choice-award-ceremony-best-bakery-award', '2016 Top Choice Award ceremony: Best Bakery', 'Watch as Top Choice Award announces La Sem Patisserie as the Top Cake Boutique of 2016 in Mississauga!', ['youtube' => ['KpZSt0Nrlt4']]],
    ['2015-top-choice-award-ceremony-best-bakery-award', '2015 Top Choice Award ceremony: Best Bakery', 'Watch as Top Choice Award announces La Sem Patisserie as the Top Cake Boutique of 2015 in Mississauga!', ['youtube' => ['leI5ySH76rI']]],
    ['readers-choice-awards-2020', 'Readers’ Choice Awards 2020', 'La Sem Patisserie in the Mississauga News Readers’ Choice Awards.', ['link' => ['https://www.mississauga.com/readerschoice/business/la-sem-patisserie', 'See our Readers’ Choice listing']]],
];

/** A clip's <video>: preview frame, real proportions, nothing downloaded before play. Skips missing files. */
function video_clip($file, $label)
{
    $root = __DIR__ . '/../../';
    if (!is_file($root . 'videos/' . $file . '.mp4')) return '';
    $poster = 'images/videos/' . basename($file) . '.webp';
    $w = 9; $h = 16;
    if (is_file($root . $poster) && ($s = @getimagesize($root . $poster))) { $w = $s[0]; $h = $s[1]; }
    // data-poster: main.js sets the preview frame when the group scrolls near, so 35 frames don't load up front.
    $attr = is_file($root . $poster) ? ' data-poster="' . e(url($poster)) . '"' : '';
    return '<figure class="vclip' . ($w > $h ? ' is-wide' : '') . '" style="--ar:' . $w . '/' . $h . '">'
        . '<video controls preload="none" playsinline' . $attr . ' width="' . $w . '" height="' . $h . '" aria-label="' . e($label) . '">'
        . '<source src="' . e(url('videos/' . $file . '.mp4')) . '" type="video/mp4"></video>'
        . '<figcaption>' . e($label) . '</figcaption></figure>';
}

page_start([
    'title'       => 'Articles & Videos',
    'path'        => 'articles-videos/',
    'description' => 'Videos from La Sem: promotional films, cakes and treats from our kitchen, cannoli and zeppole made from scratch, focaccia, gelato and our Top Choice Award ceremonies.',
    'scripts'     => ['js/chip-slider.js'],
]);
page_banner('Articles & videos', 'Behind the counter', 'Our films, cakes from the kitchen, cannoli and zeppole made from scratch, and award nights.', ['Articles & videos' => url('articles-videos/')]);
?>
<section class="section">
  <div class="wrap posts">
<?php foreach ($posts as [$slug, $title, $text, $media]):
    $clips = '';
    foreach ($media['clips'] ?? [] as [$file, $label]) $clips .= video_clip($file, $label);
    if (isset($media['clips']) && $clips === '') continue; // no files yet: leave the post out
    $many = isset($media['clips']) && substr_count($clips, '<figure') > 1;
?>
    <article class="post<?= $many ? ' post-wide' : '' ?>" id="<?= e($slug) ?>">
<?php if ($many): ?>
      <div class="post-body">
        <h2><?= e($title) ?></h2>
        <p><?= e($text) ?></p>
      </div>
      <div class="vrow" data-chip-slider data-video-row><?= $clips ?></div>
<?php else: ?>
      <div class="post-media">
<?php if ($clips !== ''): ?>
        <?= $clips ?>
<?php elseif (!empty($media['youtube'])): foreach ($media['youtube'] as $id): ?>
        <button class="yt" type="button" data-yt="<?= e($id) ?>" aria-label="Play video: <?= e($title) ?>">
          <img src="https://i.ytimg.com/vi/<?= e($id) ?>/hqdefault.jpg" alt="" loading="lazy" width="480" height="360">
          <span class="yt-play" aria-hidden="true"></span>
        </button>
<?php endforeach; else: ?>
        <a class="post-link-card" href="<?= e($media['link'][0]) ?>" rel="noopener">
          <span class="play-ring" aria-hidden="true"></span><?= e($media['link'][1]) ?>
        </a>
<?php endif; ?>
      </div>
      <div class="post-body">
        <h2><?= e($title) ?></h2>
        <p><?= e($text) ?></p>
      </div>
<?php endif; ?>
    </article>
<?php endforeach; ?>
  </div>
</section>
<?php page_end();
