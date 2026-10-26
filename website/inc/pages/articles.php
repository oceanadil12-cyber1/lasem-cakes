<?php
// Articles & Videos: award ceremonies, behind-the-scenes and promotional videos (from the staging posts).
// YouTube videos load only when clicked, so the page stays light.
$posts = [
    ['promotional-video-3', '2023-04-30', 'Promotional videos', 'Check out these videos of some tasty treats.', ['mp4' => ['Cup-Cakes' => 'Cupcakes', 'Keto-Dessert-1' => 'Keto desserts', 'Birthday-Novelty-Cake-416' => 'A birthday novelty cake']]],
    ['readers-choice-awards-2020', '2021-01-31', 'Readers’ Choice Awards 2020', 'La Sem Patisserie in the Mississauga News Readers’ Choice Awards.', ['link' => ['https://www.mississauga.com/readerschoice/business/la-sem-patisserie', 'See our Readers’ Choice listing']]],
    ['top-choice-2019', '2021-01-31', 'Top Choice 2019', 'Relive the moment at the Top Choice Awards Gala 2019, as La Sem Patisserie wins Top Cake Boutique of 2019 in Mississauga!', ['youtube' => ['AS7t4B_1KA8', 'C_4FkAdjzGo']]],
    ['top-choice-2018', '2021-01-31', 'Top Choice 2018', 'Relive the moment at the Top Choice Awards Gala 2018, as La Sem Patisserie wins Top Cake Boutique of 2018 in Mississauga!', ['youtube' => ['99F4QSBTvZQ']]],
    ['relive-moment-top-choice-awards-gala-2017', '2017-09-27', 'Relive the moment! Top Choice Awards Gala 2017', 'La Sem Patisserie wins Top Cake Boutique of 2017 in Mississauga!', ['youtube' => ['dCv4Op3aDv4']]],
    ['2016-top-choice-award-ceremony-best-bakery-award', '2017-06-04', '2016 Top Choice Award ceremony: Best Bakery', 'Watch as Top Choice Award announces La Sem Patisserie as the Top Cake Boutique of 2016 in Mississauga!', ['youtube' => ['KpZSt0Nrlt4']]],
    ['2015-top-choice-award-ceremony-best-bakery-award', '2017-06-04', '2015 Top Choice Award ceremony: Best Bakery', 'Watch as Top Choice Award announces La Sem Patisserie as the Top Cake Boutique of 2015 in Mississauga!', ['youtube' => ['leI5ySH76rI']]],
    ['z-e-p-p-o-l-e-time-year-make-zeppole-house-scratch', '2017-06-04', 'Zeppole time: made in house from scratch', 'It’s that time of year again. Watch how we make our zeppole from scratch!', ['link' => ['https://www.facebook.com/lasembakery/videos/1048057018632723/', 'Watch on Facebook']]],
    ['cannol-from-scrach', '2017-06-04', 'Cannoli, still made the old-fashioned way', 'We make our cannoli in house from scratch, still done the old-fashioned way. Let us know what you think!', ['link' => ['https://www.facebook.com/lasembakery/videos/1048042765300815/', 'Watch on Facebook']]],
    ['promotional-video-2', '2017-06-04', 'Promotional video 2', 'Watch our promotional video created back in 2015! Like and share it with your friends and family.', ['link' => ['https://www.facebook.com/lasembakery/videos/755891321182629/', 'Watch on Facebook']]],
    ['2748-2', '2017-06-04', 'Promotional video 1', 'We at La Sem specialize in any type of cakes and pastries.', ['link' => ['http://www.facebook.com/lasembakery/videos/328472617257837/', 'Watch on Facebook']]],
];
page_start([
    'title'       => 'Articles & Videos',
    'path'        => 'articles-videos/',
    'description' => 'Videos from La Sem: Top Choice Award ceremonies, how we make cannoli and zeppole from scratch, and treats from our Mississauga bakery.',
]);
page_banner('Articles & videos', 'Behind the counter', 'Award nights, how we make our cannoli and zeppole, and a few treats from the bakery.', ['Articles & videos' => url('articles-videos/')]);
?>
<section class="section">
  <div class="wrap posts">
<?php foreach ($posts as [$slug, $date, $title, $text, $media]): ?>
    <article class="post" id="<?= e($slug) ?>">
      <div class="post-media">
<?php if (!empty($media['youtube'])): foreach ($media['youtube'] as $id): ?>
        <button class="yt" type="button" data-yt="<?= e($id) ?>" aria-label="Play video: <?= e($title) ?>">
          <img src="https://i.ytimg.com/vi/<?= e($id) ?>/hqdefault.jpg" alt="" loading="lazy" width="480" height="360">
          <span class="yt-play" aria-hidden="true"></span>
        </button>
<?php endforeach; elseif (!empty($media['mp4'])): ?>
        <div class="mp4s">
<?php foreach ($media['mp4'] as $file => $label): ?>
          <video controls preload="none" playsinline aria-label="<?= e($label) ?>"><source src="<?= e(url('videos/' . $file . '.mp4')) ?>" type="video/mp4"></video>
<?php endforeach; ?>
        </div>
<?php else: ?>
        <a class="post-link-card" href="<?= e($media['link'][0]) ?>" rel="noopener">
          <span class="play-ring" aria-hidden="true"></span><?= e($media['link'][1]) ?>
        </a>
<?php endif; ?>
      </div>
      <div class="post-body">
        <time datetime="<?= e($date) ?>"><?= e(date('F j, Y', strtotime($date))) ?></time>
        <h2><?= e($title) ?></h2>
        <p><?= e($text) ?></p>
<?php if (!empty($media['link']) && !empty($media['youtube'])): ?><p><a class="link" href="<?= e($media['link'][0]) ?>" rel="noopener"><?= e($media['link'][1]) ?></a></p><?php endif; ?>
      </div>
    </article>
<?php endforeach; ?>
  </div>
</section>
<?php page_end();
