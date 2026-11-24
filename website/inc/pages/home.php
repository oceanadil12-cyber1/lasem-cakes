<?php
page_start([
    'title'       => '',
    'path'        => '',
    'hero'        => true,
    'description' => 'Family-run European bakery in Mississauga since 1963. Custom birthday and wedding cakes, cannoli and pastries, gelato, Pane di Altamura and focaccia. Order online or call (905) 624-8888.',
    'og_image'    => 'images/uploads/2026/10/wedding-cakes.webp',
    'scripts'     => ['js/vendor/three.min.js', 'js/scene3d.js', 'js/scenes/signature.js'],
]);

// The occasion explorer: page, photo, name, one line about it, photo description.
$occasions = [
    ['wedding-cakes', '2026/10/wedding-cakes.jpg', 'Wedding cakes', 'Tiered showpieces with hand-made sugar flowers, in fondant or fresh cream.', 'Six-tier white wedding cake with black and blush sugar flowers'],
    ['novelty-cakes', '2026/08/65th-BD-cake.jpg', 'Birthdays & novelty', 'Any theme, any age, any shape, from milestone birthdays to favourite heroes.', 'Black and gold two-tier 65th birthday cake'],
    ['bridal-shower-cakes', '2026/10/Bridal-shower.jpg', 'Bridal showers', 'Painted porcelain patterns, sugar peonies and colours to match the day.', 'Three-tier blue and white porcelain-pattern cake with red sugar flowers'],
    ['anniversary-cakes', '2026/10/Anniversary-1.jpg', 'Engagements & anniversaries', 'Soft, romantic designs dressed with fresh flowers.', 'Two-tier anniversary cake covered in white daisies'],
    ['baptism-cakes', '2026/10/Baptism-2-1.jpg', 'Baptism, Communion & Confirmation', 'Graceful cakes for faith milestones, finished with crosses, pearls and flowers.', 'White baptism cake with blue flowers and a gold cross'],
    ['graduation-cakes', '2026/10/Graduation.jpg', 'Graduation', 'Celebrate the cap-toss moment with clean lines, pearls and gold.', 'White graduation cake with pearls, a gold seam and a graduation cap'],
];

$collection = [
    ['wedding-cakes', '2026/09/chocolate-drip-cake-featured-image.png', 'Cakes', 'Combining creativity, structure and traditional European craftsmanship, La Sem provides celebration cakes for every occasion with attention to flavour, finish, and the details that make the occasion feel personal.'],
    ['gelato', '2026/10/Gelato2.jpg', 'Gelato', 'Made fresh in house with a smooth rich texture, using imported Italian ingredients, our traditional gelato comes in a variety of flavours.'],
    ['pastries-mississauga', '2026/10/patries-2.jpg', 'Assorted pastries', 'Our traditional European-style pastries including cannoli, fruit tarts, éclairs, baba, neopolitans and others are made fresh daily and balance flavour and texture.'],
    ['cookies-mississauga', '2026/10/Almond-Shortbread_cookies.avif', 'Almond cookies', 'Visit La Sem for freshly made cookies, from buttery shortbread to classic amaretti, almond cookies and biscotti. Perfect for any occasion.'],
    ['foccacia-pizza-lasagna-other-buns', '2026/09/lasme-img-05.png', 'Bread', 'Enjoy our homemade Pane di Altamura (Matarese bread). A crunchy rustic artisan bread made from a traditional family recipe and baked fresh Friday through Sunday.'],
    ['foccacia-pizza-lasagna-other-buns', '2026/09/lasme-img-06-1.jpg', 'Sourdough bread', 'Crispy crust, soft airy centre and that irresistible homemade taste. Baked fresh from our homemade starter.'],
    ['focaccia-sandwiches', '2026/10/Focaccia-sandwitch.jpg', 'Focaccia sandwiches', 'Our focaccia sandwiches bring together freshly baked focaccia bread and quality savoury fillings. A popular option for a satisfying lunch or a quick snack.'],
];

// The moving ribbon under the stats.
$specialties = ['Cannoli', 'Wedding cakes', 'Zeppole', 'Gelato', 'Pane di Altamura', 'Macarons', 'Birthday cakes', 'Sfogliatelle', 'Cupcakes', 'Tiramisu', 'Focaccia', 'Amaretti'];

// The photo reel: the first photos of each gallery, alternating so the reel mixes cakes and pastries.
$reel = [];
foreach (content()['pages'] as $slug => $p) {
    if ($p['type'] !== 'gallery' || $slug === 'bachelor-bachelorette') continue; // keep the home page family-friendly
    foreach (array_slice($p['images'], 0, 3) as $i => $im) $reel[$i][] = [$slug, $im['src'], $p['menu'] ?? $p['title']];
}
$reel = array_slice(array_merge(...array_values($reel ?: [[]])), 0, 24);
$reelRows = [array_slice($reel, 0, 12), array_slice($reel, 12, 12)];

$favourites = [
    ['pastries-mississauga', '2026/09/lasme-img-08-1.jpg', 'Macarons', 'Delicate French-style macarons that bring colour, texture, and variety with a crisp shell and deliciously smooth fillings.'],
    ['cupcakes-mississauga', '2026/10/Cupcakes-2.jpg', 'Cupcakes', 'Delicious cupcakes for every sweet occasion.'],
    ['zeppole-mississauga', '2026/10/Zeppole-2.jpg', 'Zeppole', 'This lightly fried puff filled with vanilla custard and amarena is made in-house following Mr. Ventola’s original recipe. He was one of the first to bring this St. Joseph’s Day specialty to Toronto.'],
    ['keto-cookies', '2026/10/keto_Peanut-Butter-cookies-rotated.jpg', 'Gourmet cookies', 'Freshly baked gourmet cookies crafted with premium ingredients and irresistible flavours. Our keto cookies offer a lower-carb choice.'],
];
?>

  <!-- Hero: the cake is built live in 3D -->
  <section class="hero" data-cake-hero aria-labelledby="hero-title">
    <canvas class="hero-canvas" aria-label="Animation: a La Sem cake being layered with sponge, Chantilly cream and fresh fruit, iced, and topped with cherries, strawberries, pineapple, peaches, kiwi, blueberries and a chocolate plaque" role="img"></canvas>
    <?= img('2026/09/chocolate-drip-cake-featured-image.png', 'A chocolate drip cake from La Sem', ['class' => 'hero-fallback']) ?>

    <div class="hero-copy wrap">
      <p class="eyebrow"><span class="dot"></span> A legacy of European baking in Mississauga</p>
      <h1 id="hero-title">A family tradition <em>of excellence</em></h1>
      <p class="lede">Our family baking story began in Bari, Italy in 1930, before coming to Canada in 1963 on St. Clair Avenue West in midtown Toronto. Since then, La Sem has carried its European baking heritage forward, creating cakes, pastries, breads, gelato and more for meaningful occasions, with care.</p>
      <div class="hero-actions">
        <button class="btn btn-solid btn-lg" type="button" data-order-request>Order a custom cake</button>
        <a class="btn btn-ghost btn-lg" href="<?= e(DOORDASH_URL) ?>" target="_blank" rel="noopener">DoorDash Online</a>
      </div>
      <a class="hero-award" href="<?= e(url('awards/top-choice-award/')) ?>">
        <img src="<?= e(url('images/story/top-choice-2026.png')) ?>" alt="" width="160" height="225">
        <span><b>12 years</b> Top Choice Award winner</span>
      </a>
    </div>

    <div class="player" aria-label="Cake animation controls">
      <p class="caption" data-cake-caption aria-live="polite"></p>
      <div class="player-row">
        <button class="pbtn" type="button" data-cake-play aria-label="Pause" data-state="playing">
          <svg class="i-pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5h3v14H7zM14 5h3v14h-3z"/></svg>
          <svg class="i-play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
        </button>
        <div class="track" aria-hidden="true"><i data-cake-progress></i></div>
        <button class="pbtn" type="button" data-cake-replay aria-label="Replay from the start">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5V2L7 6l5 4V7a5 5 0 1 1-5 5H5a7 7 0 1 0 7-7z"/></svg>
        </button>
      </div>
      <p class="hint">Drag to turn the cake</p>
    </div>
  </section>

  <section class="proof" aria-label="La Sem at a glance">
    <ul class="wrap proof-list">
      <li><b data-count="60" data-suffix="+">60+</b><span>years in Canada</span></li>
      <li><b data-count="12">12</b><span>consecutive Top Choice Awards</span></li>
      <li><b>Multiple</b><span>Readers’ Choice Awards</span></li>
      <li><b>Best</b><span>cannoli in the GTA</span></li>
    </ul>
  </section>

  <!-- Video: the bakery's own film, loaded only when someone presses play -->
  <section class="watch" id="watch" aria-labelledby="watch-title">
    <div class="wrap watch-inner">
      <div class="watch-copy" data-reveal>
        <p class="script">Step inside La Sem</p>
        <h2 id="watch-title">Authentic Italian flavours, baked in Mississauga</h2>
        <p>Meet the team behind twelve years of Top Choice Awards. See the cannoli being filled, the cakes being finished by hand, and the recipes that came with our family from Bari.</p>
        <ul class="watch-points">
          <li>Family recipes since 1930</li>
          <li>Everything made fresh in house</li>
          <li>Top Cake Boutique, 12 years running</li>
        </ul>
        <div class="watch-actions">
          <a class="btn btn-solid" href="<?= e(DOORDASH_URL) ?>" target="_blank" rel="noopener">DoorDash Online</a>
          <a class="btn btn-ghost" href="#visit">Visit the bakery</a>
        </div>
      </div>
      <div class="watch-media" data-reveal>
        <button class="yt watch-video" type="button" data-yt="syuc3oXXmpc" aria-label="Play video: Lasem Italian Bakery, authentic Italian flavours in Mississauga">
          <img src="<?= e(url('images/story/video-poster.webp')) ?>" srcset="<?= e(url('images/story/video-poster-800.webp')) ?> 800w, <?= e(url('images/story/video-poster.webp')) ?> 1280w" sizes="(max-width: 900px) 100vw, 640px" alt="" width="1280" height="720" loading="lazy" decoding="async">
          <span class="watch-play" aria-hidden="true"><i></i></span>
          <span class="watch-label">Watch our story</span>
        </button>
      </div>
    </div>
  </section>

  <div class="ribbon" aria-hidden="true">
    <div class="ribbon-track">
<?php for ($r = 0; $r < 2; $r++): foreach ($specialties as $sp): ?>
      <span><?= e($sp) ?></span>
<?php endforeach; endfor; ?>
    </div>
  </div>

  <!-- Occasions: a big photo that changes with the occasion you point at (or by itself, every few seconds) -->
  <section class="section occx" id="cakes" aria-labelledby="cakes-title">
    <div class="wrap occx-grid" data-occx>
      <div class="occx-stage" aria-hidden="true">
        <span class="occx-frame"></span>
<?php foreach ($occasions as $i => [$slug, $src, $label, $sub, $alt]): ?>
        <a class="occx-shot<?= $i === 0 ? ' is-on' : '' ?>" href="<?= e(url($slug . '/')) ?>" tabindex="-1" data-occx-shot="<?= $i ?>">
          <?= img($src, $alt, ['sizes' => '(max-width: 900px) 90vw, 46vw', 'eager' => $i === 0]) ?>
          <span class="occx-caption"><b><?= sprintf('%02d', $i + 1) ?></b><?= e($label) ?></span>
        </a>
<?php endforeach; ?>
        <span class="occx-seal"><b>Since</b>1963</span>
      </div>
      <div class="occx-copy">
        <header class="section-head left occx-head" data-reveal>
          <p class="script">Proudly serving Mississauga and surrounding areas</p>
          <h2 id="cakes-title">Cakes for life’s <em>biggest moments</em></h2>
          <p>Every cake is designed around your celebration: the guests, the colours, the story. Choose an occasion to see what we make.</p>
        </header>
        <ol class="occx-list" data-reveal>
<?php foreach ($occasions as $i => [$slug, $src, $label, $sub, $alt]): ?>
          <li>
            <a class="occx-row<?= $i === 0 ? ' is-on' : '' ?>" href="<?= e(url($slug . '/')) ?>" data-occx-row="<?= $i ?>">
              <span class="occx-thumb"><?= img($src, '', ['sizes' => '(max-width: 900px) 70vw, 1px']) ?></span>
              <span class="occx-num"><?= sprintf('%02d', $i + 1) ?></span>
              <span class="occx-text"><b><?= e($label) ?></b><i><?= e($sub) ?></i></span>
              <svg class="occx-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <span class="occx-bar" aria-hidden="true"></span>
            </a>
          </li>
<?php endforeach; ?>
        </ol>
        <p class="occx-more">Also <a href="<?= e(url('baby-shower-cakes/')) ?>">baby showers</a>, <a href="<?= e(url('bachelor-party-cakes/')) ?>">bachelor &amp; bachelorette</a> and <a href="<?= e(url('cake-type-fillings/')) ?>">any flavour you can imagine</a>.</p>
        <div class="occx-actions">
          <a class="btn btn-solid" href="<?= e(url('contact-us/')) ?>#enquiry">Design your cake</a>
          <a class="btn btn-line" href="<?= e(url('bakery/')) ?>">See all occasions</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Signature collection -->
  <section class="section section-tint" id="bakery" aria-labelledby="bakery-title">
    <div class="wrap">
      <header class="section-head" data-reveal>
        <p class="script">Our signature collection</p>
        <h2 id="bakery-title">Custom baking for special events</h2>
        <p>Explore La Sem’s selection of sweet and savoury favourites, prepared with traditional European methods, fresh ingredients, and careful attention to quality.</p>
      </header>
      <div class="counter" data-scroller>
<?php foreach ($collection as [$slug, $src, $name, $text]): ?>
        <a class="item" href="<?= e(url($slug . '/')) ?>">
          <?= img($src, $name, ['sizes' => '300px']) ?>
          <h3><?= e($name) ?></h3>
          <p><?= e($text) ?></p>
        </a>
<?php endforeach; ?>
      </div>
      <div class="scroller-nav">
        <button type="button" class="round" data-scroll="-1" aria-label="Previous items">&larr;</button>
        <button type="button" class="round" data-scroll="1" aria-label="Next items">&rarr;</button>
      </div>
    </div>
  </section>

  <!-- Customer favourites -->
  <section class="section" aria-labelledby="fav-title">
    <div class="wrap">
      <header class="section-head" data-reveal>
        <p class="script">Customer favourites</p>
        <h2 id="fav-title">What people come back for</h2>
      </header>
      <div class="favs" data-reveal>
<?php foreach ($favourites as [$slug, $src, $name, $text]): ?>
        <a class="fav" href="<?= e(url($slug . '/')) ?>">
          <?= img($src, $name, ['sizes' => '(max-width: 700px) 100vw, 25vw']) ?>
          <div><h3><?= e($name) ?></h3><p><?= e($text) ?></p></div>
        </a>
<?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Photo reel: a moving wall of real La Sem cakes and pastries -->
  <section class="reel-section" aria-labelledby="reel-title">
    <header class="section-head wrap" data-reveal>
      <p class="script">Fresh from our display case</p>
      <h2 id="reel-title">Made here, every day</h2>
    </header>
<?php foreach ($reelRows as $n => $row): if (!$row) continue; ?>
    <div class="reel<?= $n ? ' is-reverse' : '' ?>">
      <div class="reel-track">
<?php for ($r = 0; $r < 2; $r++): foreach ($row as [$slug, $src, $label]): ?>
        <a class="reel-item" href="<?= e(url($slug . '/')) ?>"<?= $r ? ' tabindex="-1" aria-hidden="true"' : '' ?>>
          <?= img($src, $r ? '' : $label . ' by La Sem', ['sizes' => '260px']) ?>
          <span><?= e($label) ?></span>
        </a>
<?php endforeach; endfor; ?>
      </div>
    </div>
<?php endforeach; ?>
    <p class="center-cta wrap"><a class="btn btn-line" href="<?= e(url('gallery/')) ?>">Browse the galleries</a></p>
  </section>

  <!-- Build your cake: the real menu -->
  <section class="section section-tint" id="flavours" aria-labelledby="flavours-title">
    <div class="wrap build">
      <header class="section-head left">
        <p class="script">Design your own</p>
        <h2 id="flavours-title">Build your cake, layer by layer</h2>
        <p>Every custom cake starts with four choices. Shapes come in round, square, hexagon, heart and petal, from 6″ to 18″, two or three layers high.</p>
        <p class="note">All custom cakes are moistened with liqueur. Tell us and we’ll leave it out.</p>
        <p><a class="btn btn-line" href="<?= e(url('cake-type-fillings/')) ?>">All cake types &amp; fillings</a></p>
      </header>
      <ol class="steps" data-reveal>
        <li><span class="n">1</span><h3>Sponge</h3>
          <p class="chips"><span>European vanilla</span><span>European chocolate</span><span>Almond</span><span>Red velvet</span><span>Sacher</span><span>Carrot</span><span>Pound cake</span><span>Sfoglia (eggless)</span><span>Gluten-free</span><span>NY cheesecake</span><span>Ice cream</span></p></li>
        <li><span class="n">2</span><h3>Filling</h3>
          <p class="chips"><span>Chantilly cream</span><span>Vanilla custard</span><span>Chocolate custard</span><span>Bacio</span><span>Hazelnut</span><span>Tiramisu cream</span><span>Frutto di bosco</span><span>Zabaglione</span><span>Torrone</span><span>Cappuccino</span><span>Lemon</span><span>Mango</span><span>+ 12 more</span></p></li>
        <li><span class="n">3</span><h3>Fresh fruit</h3>
          <p class="chips"><span>Strawberries</span><span>Kiwi</span><span>Peach slices</span><span>Pineapple chunks</span><span>Mixed fruit</span></p></li>
        <li><span class="n">4</span><h3>Icing</h3>
          <p class="chips"><span>Whipped cream</span><span>Butter cream</span><span>Chocolate ganache</span><span>Rolled fondant</span></p></li>
      </ol>
    </div>
  </section>

<?php order_band(); ?>

  <!-- Story -->
  <section class="section" id="story" aria-labelledby="story-title">
    <div class="wrap story">
      <figure class="story-photo" data-reveal>
        <img src="<?= e(url('images/story/storefront-1963.jpg')) ?>" alt="The original La Sem pasticceria on St. Clair Avenue West" loading="lazy" width="1070" height="540">
        <figcaption>St. Clair Avenue West, Toronto</figcaption>
      </figure>
      <div class="story-text" data-reveal>
        <p class="script">Our story</p>
        <h2 id="story-title">Made with passion, baked with love</h2>
        <p>At La Sem, we blend time-honoured European baking techniques with the finest ingredients to create unforgettable flavours and beautiful creations for all your special occasions. From handcrafted cakes and delicate pastries to classic Italian favourites, every treat is made with care and attention to detail.</p>
        <ol class="timeline">
          <li><b>1930</b><span>Mimi Semeraro founds La Sem in Bari, Italy</span></li>
          <li><b>1963</b><span>Nicola Ventola opens La Sem in midtown Toronto, with the GTA’s first bakery patio</span></li>
          <li><b>Today</b><span>Baking for Mississauga at 1275 Eglinton Ave. East</span></li>
        </ol>
        <p><a class="btn btn-line" href="<?= e(url('about-us/')) ?>">Read our story</a></p>
      </div>
    </div>
    <div class="wrap team">
      <?= img('2026/06/Staff-1-4.jpg', 'The La Sem team in their chef jackets', ['sizes' => '100vw']) ?>
    </div>
  </section>

  <!-- Awards + reviews -->
  <section class="section section-tint" id="awards" aria-labelledby="awards-title">
    <div class="wrap awards">
      <div class="award-card" data-reveal>
        <img src="<?= e(url('images/story/top-choice-2026.png')) ?>" alt="Top Choice Award, 12 years on top" loading="lazy" width="160" height="225">
        <div>
          <h2 id="awards-title">Mississauga’s choice, twelve years running</h2>
          <p>Named Top Cake Boutique by Top Choice Awards every year since 2015, with Readers’ Choice and People’s Choice awards along the way.</p>
          <p><a class="link" href="<?= e(url('awards/')) ?>">See all awards</a> &nbsp; <a class="link" href="<?= e(TOP_CHOICE_LISTING) ?>" rel="noopener" target="_blank">Verified 2026 winner ↗</a></p>
        </div>
      </div>
      <div class="reviews" data-reveal>
<?php foreach (array_slice(testimonials(), 0, 3) as $t): ?>
        <blockquote>
          <p>“<?= e($t['short'] ?? $t['text']) ?>”</p>
          <footer><?= e($t['name']) ?></footer>
        </blockquote>
<?php endforeach; ?>
      </div>
      <p class="center-cta"><a class="btn btn-line" href="<?= e(url('testimonials/')) ?>">Read more testimonials</a></p>
    </div>
  </section>

<?php google_reviews_section(); ?>

<?php visit_section(); ?>

<?php page_end();
