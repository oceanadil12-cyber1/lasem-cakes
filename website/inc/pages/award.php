<?php
// One award (Top Choice, Readers' Choice, People's Choice): a hero with a vector medal for every
// winning year that flips from year to year, what the award is and how it is decided, La Sem's
// record, why it matters for your order, the badges as issued, and links to the other awards.
require_once __DIR__ . '/../award-medal.php';

$slug = $page['slug'];
$pages = content()['pages'];

// The year of a badge comes from its file name (never from the upload folder). One FoodPages
// badge has no year in its name; the badge itself reads "#1 2014".
function badge_year($src)
{
    static $known = ['2026/07/foodpages_logo.png' => 2014];
    if (isset($known[$src])) return $known[$src];
    return preg_match('/(20[01]\d|202\d)/', basename($src), $m) ? (int) $m[1] : null;
}

// Winning years (each year counted once, oldest first), and the badges newest first.
$years = [];
$badges = [];
foreach ($page['images'] ?? [] as $im) {
    $y = badge_year($im['src']);
    if ($y) $years[$y] = true;
    $badges[] = ['src' => $im['src'], 'year' => $y];
}
$years = array_keys($years);
sort($years);
usort($badges, function ($a, $b) { return ($b['year'] ?? 0) <=> ($a['year'] ?? 0); });
// "2014, 2015 and 2016"
$yearList = count($years) > 1 ? implode(', ', array_slice($years, 0, -1)) . ' and ' . end($years) : (string) ($years[0] ?? '');
$count = count($years);
$firstYear = $years ? min($years) : null;
$lastYear = $years ? max($years) : null;

// Everything that differs between the three awards.
$info = [
    'top-choice-award' => [
        'script'  => 'Voted by Mississauga',
        'heading' => 'Top Choice Award: Mississauga’s Top Cake Boutique, ' . $count . ' years running',
        'intro'   => 'Every year since ' . $firstYear . ', customers across Mississauga have named La Sem their Top Choice cake boutique. The ' . $lastYear . ' win is verified on the Top Choice Awards website.',
        'pill'    => ['Verified ' . $lastYear . ' winner', true],
        'theme'   => 'gold', 'top' => 'TOP CHOICE AWARD', 'bottom' => 'MARK OF EXCELLENCE', 'small' => 'TOP CAKE BOUTIQUE',
        'caption' => 'Top Choice Awards · Mississauga',
        'medal'   => function ($year, $k) {
            return ['title' => 'Top Cake Boutique of ' . $year, 'plaque' => $k > 1 ? ['num' => $k, 'lines' => ['YEARS', 'ON TOP']] : ['lines' => ['FIRST YEAR ON TOP']]];
        },
        'record'  => function ($year, $k) { return $k > 1 ? $k . ' years on top' : 'First win'; },
        'about'   => [
            'Top Choice Awards is an annual survey that collects and reviews the opinions of thousands of customers across the Greater Toronto Area. Businesses do not nominate themselves: customers vote, the survey is verified, and only one winner is named in each category and city.',
            'La Sem has been named Top Cake Boutique of Mississauga every year since ' . $firstYear . '. The current listing is published on the Top Choice Awards website, where the ' . $lastYear . ' win can be checked.',
        ],
        'how'     => [
            ['Customers vote', 'Top Choice runs an open survey each year, asking people across the city which cake boutique they trust most.'],
            ['Votes are verified', 'Responses are reviewed before results are published, so one winner is named per category, per city.'],
            ['One winner per year', 'The Mark of Excellence badge is issued for that year only. Winning again means being voted in again.'],
        ],
        'links'   => [
            ['See La Sem’s verified ' . $lastYear . ' listing', TOP_CHOICE_LISTING, 'On the Top Choice Awards website'],
            ['About Top Choice Awards', $page['link'] ?? 'https://topchoiceawards.com/', 'How the annual survey works'],
        ],
    ],
    'readers-choice-award' => [
        'script'  => 'Voted by Mississauga News readers',
        'heading' => 'Readers’ Choice Award: ' . $count . ' years of honours from the people who read the Mississauga News',
        'intro'   => 'The Mississauga News asks its readers to vote for their favourite local businesses every year. La Sem has been recognised in the bakery and cakes categories ' . $count . ' times between ' . $firstYear . ' and ' . $lastYear . ', including Diamond, Platinum and Gold honours.',
        'pill'    => ['Diamond winner ' . $lastYear, false],
        'theme'   => 'platinum', 'top' => 'READERS’ CHOICE AWARD', 'bottom' => 'MISSISSAUGA NEWS', 'small' => 'BAKERY & CAKES',
        'caption' => 'Mississauga News Readers’ Choice',
        // The highest honour on that year's badges, for the medal plaque.
        'medal'   => function ($year, $k) {
            $tier = [2014 => ['GOLD WINNER'], 2016 => ['BEST OF', 'THE BEST'], 2017 => ['NOMINEE'], 2018 => ['PLATINUM'], 2019 => ['PLATINUM'], 2021 => ['PLATINUM'], 2023 => ['DIAMOND', 'WINNER']][$year] ?? ['WINNER'];
            return ['title' => 'Readers’ Choice ' . $year, 'plaque' => ['lines' => $tier]];
        },
        // What each badge actually says, by file name.
        'record'  => function ($year, $k, $src = '') {
            $by = ['2023-Best-Bakery' => 'Diamond winner · Best Bakery, Cupcakes & Cakes', 'ReaderChoice2023' => 'Platinum winner', 'ReadersChoice2022' => 'Winner', 'ReaderChoice2021' => 'Platinum winner', '2020-ReadersChoice' => 'Winner', '2019-RCA' => 'Platinum winner', '2018-RCA' => 'Platinum', '2017-Readers' => 'Nominee', 'best_of_the_best_2016' => 'Best of the Best', 'readers_choice_2016' => 'Silver winner', '2014_RC_Gold' => 'Gold winner'];
            foreach ($by as $needle => $label) if (strpos(basename($src), $needle) !== false) return $label;
            return 'Winner';
        },
        'about'   => [
            'For more than twenty years the Mississauga News has run the Readers’ Choice Awards, inviting readers to vote for their favourite businesses and services in more than a hundred categories, from automotive dealers to home builders to bakeries.',
            'Readers vote for the places they actually use. La Sem first won Gold in ' . $firstYear . ' and has been recognised again and again since, with Platinum honours in 2018, 2019 and 2021, and the Diamond award for Best Bakery, Cupcakes and Cakes in ' . $lastYear . '.',
        ],
        'how'     => [
            ['Readers nominate', 'Each year the Mississauga News opens nominations, and readers put forward the businesses they want recognised.'],
            ['Readers vote', 'The shortlist goes to a public vote. Winners are the businesses readers choose, not a panel of judges.'],
            ['Diamond, Platinum, Gold', 'The top three in a category receive Diamond, Platinum and Gold honours, published in the paper and online.'],
        ],
        'links'   => [
            ['La Sem on the Readers’ Choice site', 'https://www.mississauga.com/readerschoice/business/la-sem-patisserie', 'The Mississauga News listing'],
            ['About the Readers’ Choice Awards', 'https://www.mississauga.com/readerschoice/', 'How the Mississauga News vote works'],
        ],
    ],
    'peoples-choice' => [
        'script'  => 'Voted by diners',
        'heading' => 'People’s Choice: picked by the people who eat here',
        'intro'   => 'People’s Choice honours come from diners rather than judges. La Sem was chosen on FoodPages, Canada’s restaurant guide, in ' . $yearList . '.',
        'pill'    => ['Diners’ pick', false],
        'theme'   => 'bronze', 'top' => 'PEOPLE’S CHOICE', 'bottom' => 'FOODPAGES.CA', 'small' => 'BAKERY & CAFÉ',
        'caption' => 'People’s Choice · FoodPages.ca',
        'medal'   => function ($year, $k) { return ['title' => 'People’s Choice ' . $year, 'plaque' => ['lines' => ['CHOSEN BY', 'DINERS']]]; },
        'record'  => function ($year, $k) { return 'People’s Choice'; },
        'about'   => [
            'FoodPages is a Canadian restaurant and food guide where diners rate and review the places they visit. Its People’s Choice honours go to the businesses that customers rate highest, so they reflect everyday visits rather than a single judged tasting.',
            'La Sem was named a People’s Choice in ' . $yearList . ', alongside its Top Choice and Readers’ Choice awards from the same years.',
        ],
        'how'     => [
            ['Diners rate', 'Customers leave ratings and reviews on FoodPages after visiting.'],
            ['Ratings are counted', 'Businesses with the strongest customer ratings in their area are recognised.'],
            ['Nobody applies', 'There is no entry form. The honour comes from customers who chose to write about their visit.'],
        ],
        'links'   => [
            ['FoodPages.ca', 'https://www.foodpages.ca/', 'The restaurant guide behind the People’s Choice honours'],
        ],
    ],
][$slug] ?? null;

page_start([
    'title'       => $page['title'],
    'description' => $info ? $info['intro'] : ($page['body'][0]['x'] ?? 'La Sem Cakes has won the ' . $page['title'] . '.'),
    'path'        => $page['path'],
]);
$k = 0;
?>
<section class="aw-hero award-hero" aria-labelledby="award-title">
  <div class="wrap aw-hero-inner">
    <div class="aw-hero-copy">
      <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a> <span aria-hidden="true">/</span> <a href="<?= e(url('awards/')) ?>">Awards</a> <span aria-hidden="true">/</span> <a href="<?= e(url($page['path'])) ?>"><?= e($page['title']) ?></a></nav>
<?php if ($info): ?>
      <p class="aw-verified<?= $info['pill'][1] ? '' : ' is-soft' ?>"><span aria-hidden="true">✓</span> <?= e($info['pill'][0]) ?></p>
      <p class="script"><?= e($info['script']) ?></p>
      <h1 id="award-title"><?= e($info['heading']) ?></h1>
      <p class="banner-intro"><?= e($info['intro']) ?></p>
<?php else: ?>
      <p class="script">Awards</p>
      <h1 id="award-title"><?= e($page['title']) ?></h1>
<?php endif; ?>
      <div class="hero-actions">
        <a class="btn btn-solid btn-lg" href="<?= e(url('contact-us/')) ?>#enquiry">Order an award-winning cake</a>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('awards/')) ?>">All awards &amp; recognition</a>
      </div>
    </div>
<?php if ($info && $years): ?>
    <div class="aw-stage" data-aw-cycle>
      <span class="aw-medal-glow" aria-hidden="true"></span>
      <div class="aw-coins">
<?php foreach ($years as $i => $y): $last = $i === $count - 1; $m = $info['medal']($y, $i + 1); ?>
        <a class="aw-coin<?= $last ? ' is-active' : '' ?>" href="#record" data-aw-title="<?= e($m['title']) ?>"<?= $last ? '' : ' tabindex="-1"' ?>>
          <?= award_medal_svg(['theme' => $info['theme'], 'top' => $info['top'], 'bottom' => $info['bottom'], 'small' => $info['small'], 'year' => $y, 'plaque' => $m['plaque'], 'label' => $page['title'] . ' ' . $y], $i) ?>
        </a>
<?php endforeach; ?>
      </div>
      <p class="aw-stage-cap"><b data-aw-caption><?= e($info['medal']($lastYear, $count)['title']) ?></b><span><?= e($info['caption']) ?></span></p>
    </div>
<?php endif; ?>
  </div>
</section>

<?php if ($info): ?>
<section class="proof" aria-label="At a glance">
  <ul class="wrap proof-list">
    <li><b data-count="<?= (int) $count ?>"><?= (int) $count ?></b><span><?= $count === 1 ? 'year won' : 'years won' ?></span></li>
    <li><b>Since <?= (int) $firstYear ?></b><span>first <?= e($page['title']) ?></span></li>
    <li><b><?= (int) $lastYear ?></b><span>most recent win</span></li>
    <li><b data-count="<?= (int) (date('Y') - 1963) ?>" data-suffix="+"><?= (int) (date('Y') - 1963) ?>+</b><span>years baking in the GTA</span></li>
  </ul>
</section>

<section class="section" aria-labelledby="about-title">
  <div class="wrap award-about">
    <div class="prose" data-reveal>
      <p class="script">About the award</p>
      <h2 id="about-title">What the <?= e($page['title']) ?> is</h2>
<?php foreach ($info['about'] as $p): ?>
      <p><?= e($p) ?></p>
<?php endforeach; ?>
      <p>Every award on this page was decided by customers. You can read what they say for themselves on our <a href="<?= e(url('testimonials/')) ?>">testimonials page</a>, or browse the <a href="<?= e(url('gallery/')) ?>">cake gallery</a> to see the work behind the votes.</p>
    </div>
    <div class="aw-cards award-how" data-reveal>
<?php foreach ($info['how'] as $n => $step): ?>
      <article class="aw-card">
        <span class="aw-card-icon" aria-hidden="true"><?= $n + 1 ?></span>
        <h3><?= e($step[0]) ?></h3>
        <p><?= e($step[1]) ?></p>
      </article>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-tint" id="record" aria-labelledby="record-title">
  <div class="wrap">
    <header class="section-head" data-reveal>
      <p class="script">Year by year</p>
      <h2 id="record-title">La Sem’s <?= e($page['title']) ?> record</h2>
      <p><?= $count ?> <?= $count === 1 ? 'year' : 'years' ?> between <?= (int) $firstYear ?> and <?= (int) $lastYear ?>, with the badge as issued each time. Tap a badge to see it larger.</p>
    </header>
    <div class="award-record" data-lightbox-group data-reveal>
<?php foreach ($badges as $b): $y = $b['year']; $k = $y ? array_search($y, $years) + 1 : 0; ?>
      <a href="<?= e(img_url($b['src'])) ?>" data-lightbox title="<?= e($page['title'] . ($y ? ' ' . $y : '')) ?>">
        <?= img($b['src'], $page['title'] . ($y ? ' badge ' . $y : ' badge'), ['sizes' => '140px']) ?>
        <b><?= $y ? (int) $y : 'Badge' ?></b>
        <span><?= e($y ? $info['record']($y, $k, $b['src']) : $page['title']) ?></span>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" aria-labelledby="why-title">
  <div class="wrap award-why">
    <div class="prose" data-reveal>
      <p class="script">What it means for you</p>
      <h2 id="why-title">Why it matters when you order</h2>
      <p>An award chosen by customers is a promise about the everyday experience: the same recipes, the same decorators and the same counter that people voted for. Whether you need a tiered <a href="<?= e(url('wedding-cakes/')) ?>">wedding cake</a>, a <a href="<?= e(url('novelty-cakes/')) ?>">novelty birthday cake</a> or a tray of <a href="<?= e(url('pastries-mississauga/')) ?>">pastries</a>, it comes from the kitchen behind these badges.</p>
      <ul>
        <li>Choose from every <a href="<?= e(url('cake-type-fillings/')) ?>">cake type, filling and icing</a> we make.</li>
        <li>See finished work in the <a href="<?= e(url('gallery/')) ?>">cake gallery</a> before you decide.</li>
        <li>Read about the family behind the bakery on our <a href="<?= e(url('about-us/')) ?>">about page</a>, baking in the GTA since 1963.</li>
        <li>Watch the award nights in our <a href="<?= e(url('articles-videos/')) ?>">articles and videos</a>.</li>
      </ul>
    </div>
    <aside class="award-links" data-reveal>
<?php foreach ($info['links'] as $l): ?>
      <a href="<?= e($l[1]) ?>" rel="noopener" target="_blank"><b><?= e($l[0]) ?> ↗</b><span><?= e($l[2]) ?></span></a>
<?php endforeach; ?>
      <a href="<?= e(url('awards/')) ?>"><b>Awards &amp; recognition</b><span>Every badge since 2014, and La Sem in the University of Toronto archive</span></a>
      <a href="<?= e(url('contact-us/')) ?>#enquiry"><b>Plan a cake with us</b><span>Tell us the occasion, the date and the number of guests</span></a>
    </aside>
  </div>
</section>
<?php else: ?>
<section class="section">
  <div class="wrap">
<?php if (!empty($page['body'])): ?>
    <div class="prose narrow"><?= body_html($page['body']) ?></div>
<?php endif; ?>
    <div class="badges" data-lightbox-group>
<?php foreach ($page['images'] as $im): preg_match('/(20\d\d)/', $im['src'], $y); ?>
      <a class="badge" href="<?= e(img_url($im['src'])) ?>" data-lightbox>
        <?= img($im['src'], $page['title'] . (isset($y[1]) ? ' ' . $y[1] : ''), ['sizes' => '220px']) ?>
<?php if (isset($y[1])): ?><span><?= e($y[1]) ?></span><?php endif; ?>
      </a>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section-tint">
  <div class="wrap">
    <header class="section-head">
      <p class="script">More recognition</p>
      <h2>The other awards</h2>
    </header>
    <div class="pills">
<?php foreach ($pages as $s => $p): if ($p['type'] !== 'award') continue; ?>
      <a class="pill<?= $s === $slug ? ' is-current' : '' ?>" href="<?= e(url('awards/' . $s . '/')) ?>"><?= e($p['menu'] ?? $p['title']) ?></a>
<?php endforeach; ?>
      <a class="pill" href="<?= e(url('awards/')) ?>">All awards &amp; recognition</a>
      <a class="pill" href="<?= e(url('articles-videos/')) ?>">Award ceremony videos</a>
    </div>
  </div>
</section>
<?php order_band(); page_end();
