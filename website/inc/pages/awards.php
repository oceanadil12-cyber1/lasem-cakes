<?php
// Awards & recognition (/awards/): the verified 2026 Top Choice win, every badge since 2014 by year,
// what each award means, La Sem in the University of Toronto Mississauga food-history archive,
// and the award-night videos. Badge photos come from the three award pages in data/pages.json.

$pages = content()['pages'];
$awardNames = ['top-choice-award' => 'Top Choice', 'readers-choice-award' => 'Readers’ Choice', 'peoples-choice' => 'People’s Choice'];

// Every badge, grouped by the year in its file name (newest first).
$years = [];
foreach ($awardNames as $slug => $name) {
    foreach ($pages[$slug]['images'] ?? [] as $im) {
        if (!preg_match('/(20[01]\d|202\d)/', basename($im['src']), $m)) continue;
        $years[(int) $m[1]][] = ['src' => $im['src'], 'award' => $name, 'slug' => $slug];
    }
}
krsort($years);
$topChoiceYears = count(array_filter($years, function ($list) { return (bool) array_filter($list, function ($b) { return $b['award'] === 'Top Choice'; }); }));
$readersYears = count(array_filter($years, function ($list) { return (bool) array_filter($list, function ($b) { return $b['award'] === 'Readers’ Choice'; }); }));
$first = min(array_keys($years));
$topChoiceList = array_keys(array_filter($years, function ($list) { return (bool) array_filter($list, function ($b) { return $b['award'] === 'Top Choice'; }); }));
sort($topChoiceList);
require_once __DIR__ . '/../award-medal.php';

$archive = [
    ['Early years', 'The first storefront', 'A photo of La Sem’s storefront in its early years in Toronto, when the bakery brought a European café feel to the street.', 'item/1993'],
    ['1972', 'An early review', 'One of the earliest write-ups about La Sem, from 1972, kept in the collection as part of the city’s Italian food story.', 'item/1691'],
    ['2000s', 'The storefront, decades on', 'The La Sem storefront in the early 2000s, still baking the same family recipes.', 'item/1995'],
    ['2002', 'National Post: “Little Italy, Big Taste”', 'A National Post feature on Italian food in Toronto that includes La Sem, published July 31, 2002.', 'item/2932'],
    ['2019', '“Passion for over 50 years”', 'A mississauga.com feature from October 2019 showing the owners with their awards.', 'item/1997'],
];

$videos = [
    ['AS7t4B_1KA8', 'Top Choice Awards Gala 2019', 'top-choice-2019'],
    ['99F4QSBTvZQ', 'Top Choice Awards 2018', 'top-choice-2018'],
    ['dCv4Op3aDv4', 'Top Choice Awards Gala 2017', 'relive-moment-top-choice-awards-gala-2017'],
];

page_start([
    'title'       => 'Awards & Recognition',
    'path'        => 'awards/',
    'description' => 'La Sem Patisserie is Mississauga’s 2026 Top Choice Cake Boutique, a winner every year since 2015, with Readers’ Choice and People’s Choice awards, and a place in the University of Toronto Mississauga food-history archive.',
    'og_image'    => 'images/uploads/2026/07/Top-Cake-Boutique-of-2026.webp',
    'scripts'     => ['js/chip-slider.js'],
]);
?>
<section class="aw-hero" aria-labelledby="aw-title">
  <div class="wrap aw-hero-inner">
    <div class="aw-hero-copy">
      <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a> <span aria-hidden="true">/</span> <a href="<?= e(url('awards/')) ?>">Awards</a></nav>
      <p class="aw-verified"><span aria-hidden="true">✓</span> Verified 2026 winner</p>
      <h1 id="aw-title">Mississauga’s Top Choice Cake Boutique, <em><?= (int) $topChoiceYears ?> years running</em></h1>
      <p class="banner-intro">Every year since 2015, Mississauga has voted La Sem its favourite cake boutique in the Top Choice Awards survey. Add <?= (int) $readersYears ?> years of Readers’ Choice awards from the Mississauga News, and a family bakery history the University of Toronto keeps in its archive.</p>
      <div class="hero-actions">
        <a class="btn btn-solid btn-lg" href="<?= e(TOP_CHOICE_LISTING) ?>" rel="noopener" target="_blank">See our verified 2026 listing</a>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('contact-us/')) ?>#enquiry">Order an award-winning cake</a>
      </div>
    </div>
    <div class="aw-stage" data-aw-cycle>
      <span class="aw-medal-glow" aria-hidden="true"></span>
      <div class="aw-coins">
<?php foreach ($topChoiceList as $i => $y): $last = $i === count($topChoiceList) - 1; ?>
        <a class="aw-coin<?= $last ? ' is-active' : '' ?>" href="<?= e(url('awards/top-choice-award/')) ?>" data-aw-title="Top Cake Boutique of <?= (int) $y ?>"<?= $last ? '' : ' tabindex="-1"' ?>>
          <?= top_choice_medal_svg($y, $i + 1, $i) ?>
        </a>
<?php endforeach; ?>
      </div>
      <p class="aw-stage-cap"><b data-aw-caption>Top Cake Boutique of <?= (int) end($topChoiceList) ?></b><span>Top Choice Awards · Mississauga</span></p>
    </div>
  </div>
</section>

<section class="proof" aria-label="Awards at a glance">
  <ul class="wrap proof-list">
    <li><b data-count="<?= (int) $topChoiceYears ?>"><?= (int) $topChoiceYears ?></b><span>Top Choice Awards in a row</span></li>
    <li><b data-count="<?= (int) $readersYears ?>"><?= (int) $readersYears ?></b><span>years of Readers’ Choice</span></li>
    <li><b data-count="<?= (int) (date('Y') - 1963) ?>" data-suffix="+"><?= (int) (date('Y') - 1963) ?>+</b><span>years baking in the GTA</span></li>
    <li><b>Since <?= (int) $first ?></b><span>winning year after year</span></li>
  </ul>
</section>

<!-- The trophy wall: every badge, year by year -->
<section class="section" aria-labelledby="wall-title">
  <div class="wrap">
    <header class="section-head" data-reveal>
      <p class="script">The trophy wall</p>
      <h2 id="wall-title">Every award, year by year</h2>
      <p>The years roll by on their own. Hover to pause, use the arrows, and tap a badge to see it larger.</p>
    </header>
    <div class="aw-years" data-chip-slider data-autoplay data-lightbox-group>
<?php foreach ($years as $year => $badges): ?>
      <div class="aw-year" data-reveal>
        <b class="aw-year-num"><?= (int) $year ?></b>
        <div class="aw-badges">
<?php foreach ($badges as $b): ?>
          <a href="<?= e(img_url($b['src'])) ?>" data-lightbox title="<?= e($b['award'] . ' ' . $year) ?>">
            <?= img($b['src'], $b['award'] . ' award ' . $year, ['sizes' => '120px']) ?>
            <span><?= e($b['award']) ?></span>
          </a>
<?php endforeach; ?>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>

<!-- What each award is -->
<section class="section section-tint" aria-labelledby="mean-title">
  <div class="wrap">
    <header class="section-head" data-reveal>
      <p class="script">Voted by real customers</p>
      <h2 id="mean-title">What the awards mean</h2>
    </header>
    <div class="aw-cards">
      <article class="aw-card" data-reveal>
        <span class="aw-card-icon" aria-hidden="true">★</span>
        <h3>Top Choice Award</h3>
        <p>Top Choice Awards surveys thousands of local customers every year. La Sem has been named Mississauga’s Top Cake Boutique every year since 2015, and is a verified 2026 winner.</p>
        <p class="aw-card-links"><a class="link" href="<?= e(url('awards/top-choice-award/')) ?>">All Top Choice badges</a> <a class="link ext" href="<?= e(TOP_CHOICE_LISTING) ?>" rel="noopener" target="_blank">Verified listing</a></p>
      </article>
      <article class="aw-card" data-reveal>
        <span class="aw-card-icon" aria-hidden="true">✦</span>
        <h3>Readers’ Choice Award</h3>
        <p>Mississauga News readers vote for their favourite local businesses in more than 100 categories. La Sem has been recognized with Gold, Platinum and Best of the Best honours since 2014.</p>
        <p class="aw-card-links"><a class="link" href="<?= e(url('awards/readers-choice-award/')) ?>">All Readers’ Choice badges</a></p>
      </article>
      <article class="aw-card" data-reveal>
        <span class="aw-card-icon" aria-hidden="true">♥</span>
        <h3>People’s Choice</h3>
        <p>People’s Choice honours from diners, including on FoodPages: another award picked by customers rather than judges.</p>
        <p class="aw-card-links"><a class="link" href="<?= e(url('awards/peoples-choice/')) ?>">See the People’s Choice awards</a></p>
      </article>
    </div>
  </div>
</section>

<!-- La Sem in the archives -->
<section class="section aw-archive" aria-labelledby="archive-title">
  <div class="wrap aw-archive-inner">
    <div class="aw-archive-intro" data-reveal>
      <p class="script">Part of the city’s food history</p>
      <h2 id="archive-title">La Sem in the University of Toronto archive</h2>
      <p>The University of Toronto Mississauga’s <b>Mapping Italian-Canadian Foodways</b> project records the bakeries, cafés and shops that shaped Italian food in the Greater Toronto and Hamilton Area. La Sem has its own collection in it, with photos, a 1972 review and newspaper features.</p>
      <figure class="aw-archive-photo">
        <img src="<?= e(url('images/story/storefront-1963.jpg')) ?>" alt="The original La Sem pasticceria on St. Clair Avenue West" loading="lazy" width="1070" height="540">
        <figcaption>La Sem on St. Clair Avenue West, Toronto</figcaption>
      </figure>
      <p><a class="btn btn-line" href="<?= e(FOODWAYS_LA_SEM) ?>" rel="noopener" target="_blank">Open the La Sem collection</a></p>
    </div>
    <ol class="aw-timeline">
<?php foreach ($archive as [$when, $title, $text, $path]): ?>
      <li data-reveal>
        <span class="aw-when"><?= e($when) ?></span>
        <a class="aw-item" href="<?= e(FOODWAYS . $path) ?>" rel="noopener" target="_blank">
          <b><?= e($title) ?></b>
          <span><?= e($text) ?></span>
          <i>View in the archive</i>
        </a>
      </li>
<?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- Award nights -->
<section class="section section-tint" aria-labelledby="nights-title">
  <div class="wrap">
    <header class="section-head" data-reveal>
      <p class="script">Relive the moment</p>
      <h2 id="nights-title">Award nights on video</h2>
    </header>
    <div class="aw-videos">
<?php foreach ($videos as [$id, $title, $post]): ?>
      <figure data-reveal>
        <button class="yt" type="button" data-yt="<?= e($id) ?>" aria-label="Play video: <?= e($title) ?>">
          <img src="https://i.ytimg.com/vi/<?= e($id) ?>/hqdefault.jpg" alt="" loading="lazy" width="480" height="360">
          <span class="yt-play" aria-hidden="true"></span>
        </button>
        <figcaption><a href="<?= e(url('articles-videos/')) ?>#<?= e($post) ?>"><?= e($title) ?></a></figcaption>
      </figure>
<?php endforeach; ?>
    </div>
    <p class="center-cta"><a class="btn btn-line" href="<?= e(url('articles-videos/')) ?>">All articles &amp; videos</a></p>
  </div>
</section>

<?php google_reviews_section('What award-winning tastes like'); ?>

<?php order_band('Taste why Mississauga keeps voting for us', 'Order a custom cake for your celebration, or pick up pastries, cannoli and zeppole from our counter.'); page_end();
