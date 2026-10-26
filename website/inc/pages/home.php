<?php
page_start([
    'title'       => '',
    'path'        => '',
    'hero'        => true,
    'description' => 'Family-run European bakery in Mississauga since 1963. Custom birthday and wedding cakes, cannoli and pastries, gelato, Pane di Altamura and focaccia. Order online or call (905) 624-8888.',
    'og_image'    => 'images/uploads/2026/10/wedding-cakes.webp',
    'scripts'     => ['js/vendor/three.min.js', 'js/scene3d.js', 'js/scenes/signature.js'],
]);

$occasions = [
    ['wedding-cakes', '2026/10/wedding-cakes.jpg', 'Wedding cakes', 'Tiered, sugar flowers, fondant or cream', 'Five-tier white wedding cake with flowers'],
    ['novelty-cakes', '2026/10/Birthday-Novelty-cake-2.jpg', 'Birthday & novelty', 'Any theme, any shape', 'Number cake decorated with chocolates and macarons'],
    ['baptism-cakes', '2026/10/Baptism-1.avif', 'Baptism, Communion & Confirmation', '', 'Baptism cake with a gold cross'],
    ['anniversary-cakes', '2026/10/Anniversary-1.jpg', 'Engagement & anniversary', '', 'Anniversary cake with white flowers'],
    ['baby-shower-cakes', '2026/10/Baby-shower.jpg', 'Baby shower', '', 'Pink baby carriage cake'],
    ['graduation-cakes', '2026/10/Graduation.jpg', 'Graduation', '', 'Graduation cake with a cap on top'],
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
    <?= img('2026/09/chocolate-drip-cake-featured-image.png', 'A chocolate drip cake from La Sem', ['class' => 'hero-fallback', 'eager' => true]) ?>

    <div class="hero-copy wrap">
      <p class="eyebrow"><span class="dot"></span> A legacy of European baking in Mississauga</p>
      <h1 id="hero-title">A family tradition <em>of excellence</em></h1>
      <p class="lede">Our family baking story began in Bari, Italy in 1930, before coming to Canada in 1963 on St. Clair Avenue West in midtown Toronto. Since then, La Sem has carried its European baking heritage forward, creating cakes, pastries, breads, gelato and more for meaningful occasions, with care.</p>
      <div class="hero-actions">
        <a class="btn btn-solid btn-lg" href="<?= e(url('contact-us/')) ?>#enquiry">Order a custom cake</a>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('order/')) ?>">Order for pick-up</a>
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
      <li><b>60+</b><span>years in Canada</span></li>
      <li><b>12</b><span>consecutive Top Choice Awards</span></li>
      <li><b>Multiple</b><span>Readers’ Choice Awards</span></li>
      <li><b>Best</b><span>cannoli in the GTA</span></li>
    </ul>
  </section>

  <!-- Occasions -->
  <section class="section" id="cakes" aria-labelledby="cakes-title">
    <div class="wrap">
      <header class="section-head">
        <p class="script">Proudly serving Mississauga and surrounding areas</p>
        <h2 id="cakes-title">Cakes for life’s biggest moments</h2>
        <p>We create cakes for birthdays, weddings, engagements, anniversaries, baptisms, communions, confirmations, graduations, baby showers, bridal showers and other family celebrations, plus bachelor and bachelorette and novelty cakes shaped around the tone of the gathering.</p>
      </header>
      <div class="occasions">
<?php foreach ($occasions as $i => [$slug, $src, $label, $sub, $alt]): ?>
        <a class="occ<?= $i === 0 ? ' occ-tall' : '' ?>" href="<?= e(url($slug . '/')) ?>">
          <?= img($src, $alt, ['sizes' => $i === 0 ? '(max-width: 900px) 100vw, 40vw' : '(max-width: 900px) 50vw, 30vw']) ?>
          <span class="occ-label"><b><?= e($label) ?></b><?php if ($sub): ?><i><?= e($sub) ?></i><?php endif; ?></span>
        </a>
<?php endforeach; ?>
      </div>
      <p class="center-cta"><a class="btn btn-line" href="<?= e(url('bakery/')) ?>">See all cakes &amp; occasions</a></p>
    </div>
  </section>

  <!-- Signature collection -->
  <section class="section section-tint" id="bakery" aria-labelledby="bakery-title">
    <div class="wrap">
      <header class="section-head">
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
      <header class="section-head">
        <p class="script">Customer favourites</p>
        <h2 id="fav-title">What people come back for</h2>
      </header>
      <div class="favs">
<?php foreach ($favourites as [$slug, $src, $name, $text]): ?>
        <a class="fav" href="<?= e(url($slug . '/')) ?>">
          <?= img($src, $name, ['sizes' => '(max-width: 700px) 100vw, 25vw']) ?>
          <div><h3><?= e($name) ?></h3><p><?= e($text) ?></p></div>
        </a>
<?php endforeach; ?>
      </div>
    </div>
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
      <ol class="steps">
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
      <figure class="story-photo">
        <img src="<?= e(url('images/story/storefront-1963.jpg')) ?>" alt="The original La Sem pasticceria on St. Clair Avenue West" loading="lazy" width="1070" height="540">
        <figcaption>St. Clair Avenue West, Toronto</figcaption>
      </figure>
      <div class="story-text">
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
      <div class="award-card">
        <img src="<?= e(url('images/story/top-choice-2026.png')) ?>" alt="Top Choice Award, 12 years on top" loading="lazy" width="160" height="225">
        <div>
          <h2 id="awards-title">Mississauga’s choice, twelve years running</h2>
          <p>Named Top Cake Boutique by Top Choice Awards every year since 2015, with Readers’ Choice and People’s Choice awards along the way.</p>
          <p><a class="link" href="<?= e(url('awards/')) ?>">See all awards</a></p>
        </div>
      </div>
      <div class="reviews">
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

<?php visit_section(); ?>

<?php page_end();
