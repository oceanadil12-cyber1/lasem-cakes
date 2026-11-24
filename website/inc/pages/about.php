<?php
page_start([
    'title'       => 'About Us',
    'path'        => 'about-us/',
    'description' => 'La Sem began in Bari, Italy, in 1930 and opened in Toronto in 1963 with the GTA’s first bakery patio. Today we bake custom cakes, pastries, gelato and bread in Mississauga.',
    'og_image'    => 'images/uploads/2026/10/About-us-pg-1st-picture-scaled.webp',
]);
page_banner('About La Sem Bakery', 'Since 1930', '', ['About us' => url('about-us/')], [
    // [photo, focus point kept in view when the banner crops it]
    ['2026/11/about-banner-1963.jpg', '45% 25%'],
    ['2026/11/about-banner-awning.jpg', '55% 0%'],
    ['2026/11/about-banner-storefront.jpg', '60% 18%'],
]);
?>
<!-- 2nd fold: the bakery's own film (same block as on the home page), loaded only when someone presses play -->
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
        <a class="btn btn-solid" href="<?= e(DOORDASH_URL) ?>" target="_blank" rel="noopener">Order for pickup</a>
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
<section class="section about-story" aria-labelledby="story-title">
  <span class="about-story-year" aria-hidden="true">1930</span>
  <div class="wrap about-intro">
    <div class="about-story-copy" data-reveal>
      <p class="script">Our story</p>
      <h2 id="story-title">A family tradition, handed down by hand since 1930</h2>
      <blockquote class="about-quote">Some recipes survive because they are written down. Others survive because someone stands beside you and shows you when the dough feels right, when the pastry is ready, and when a cake finally looks finished.</blockquote>
      <p class="about-second"><b>La Sem belongs to the second kind of tradition.</b></p>
      <p>Our story began in Bari, Italy, in 1930, when Mimi Semeraro founded La Sem and a young Nicola Ventola discovered his passion for cakes, pastries, and confectionery. In 1963, Nicola carried that experience to Canada and opened La Sem in mid-town Toronto, beginning a new chapter for the family bakery.</p>
      <ol class="about-timeline">
        <li><b>1930</b><span>Bari, Italy</span></li>
        <li><b>1963</b><span>Toronto</span></li>
        <li><b>Today</b><span>Mississauga</span></li>
      </ol>
      <div class="about-actions">
        <a class="btn btn-solid" href="<?= e(SITE['maps_url']) ?>" rel="noopener">Get directions</a>
        <a class="btn btn-line" href="<?= e(url('awards/')) ?>">See our awards</a>
      </div>
    </div>
    <figure class="about-photo" data-reveal>
      <?= img('2026/10/About-us-pg-1st-picture-scaled.jpg', 'A tall La Sem celebration cake', ['sizes' => '(max-width: 900px) 100vw, 40vw', 'eager' => true]) ?>
      <figcaption class="about-badge"><b>12</b><span>years of Top Choice Awards</span></figcaption>
    </figure>
  </div>
</section>

<section class="section section-tint">
  <div class="wrap">
    <div class="journey">
      <figure><?= img('2026/10/001.png', 'Bari, Italy', ['sizes' => '(max-width: 700px) 100vw, 40vw']) ?><figcaption><b>Bari, Italy</b> 1930</figcaption></figure>
      <span class="journey-arrow" aria-hidden="true">→</span>
      <figure><?= img('2026/10/002.png', 'Toronto skyline', ['sizes' => '(max-width: 700px) 100vw, 40vw']) ?><figcaption><b>Toronto, Canada</b> 1963</figcaption></figure>
    </div>
    <div class="prose narrow center-text">
      <h2>From Bari to Toronto, with an idea that felt new</h2>
      <p>When La Sem arrived in Toronto in 1963, the intention was clear. We wanted to bring the character of a true European bakery to the city.</p>
      <p>That meant much more than filling display cases. The bakery became a place to sit, gather, talk, celebrate, and enjoy something made with care. La Sem introduced the first outdoor bakery patio in the GTA, bringing a little of the European café experience into Toronto life. The address changed over the years. The instinct behind it did not.</p>
      <p>Those Toronto years are now part of the city’s food history: the University of Toronto Mississauga’s <a class="link" href="<?= e(FOODWAYS_LA_SEM) ?>" rel="noopener" target="_blank">Mapping Italian-Canadian Foodways</a> archive keeps a La Sem collection, with early storefront photos, a 1972 review and a National Post feature. See it with all our awards on <a class="link" href="<?= e(url('awards/')) ?>">Awards &amp; recognition</a>.</p>
    </div>
  </div>
</section>

<section class="about-stats" aria-label="La Sem in numbers">
  <ul class="wrap about-stats-list">
    <li>
      <span class="as-icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M24 12.5s-4.2-2.6-4.2-5.1a2.2 2.2 0 0 1 4.2-.9 2.2 2.2 0 0 1 4.2.9c0 2.5-4.2 5.1-4.2 5.1z"/><rect x="16" y="16" width="16" height="7" rx="1.5"/><rect x="12" y="23" width="24" height="8" rx="1.5"/><rect x="8" y="31" width="32" height="9" rx="1.5"/><path d="M12 27c2 1.6 4 1.6 6 0s4-1.6 6 0 4 1.6 6 0 4-1.6 6 0M8 35c2.7 1.8 5.3 1.8 8 0s5.3-1.8 8 0 5.3 1.8 8 0 5.3-1.8 8 0M5 43h38"/></svg></span>
      <div><b data-count="60" data-suffix="+">60+</b><span class="as-label">Years of experience</span></div>
    </li>
    <li>
      <span class="as-icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 12v8M24 12v8M31 12v8"/><path d="M17 5.5c1.3 1.6 1.3 3 0 4-1.3-1-1.3-2.4 0-4zM24 5.5c1.3 1.6 1.3 3 0 4-1.3-1-1.3-2.4 0-4zM31 5.5c1.3 1.6 1.3 3 0 4-1.3-1-1.3-2.4 0-4z"/><rect x="11" y="20" width="26" height="9" rx="2"/><path d="M11 25c2.2 2 4.3 2 6.5 0s4.3-2 6.5 0 4.3 2 6.5 0 4.3-2 6.5 0"/><rect x="8" y="29" width="32" height="11" rx="2"/><path d="M8 34.5h32M5 43h38"/></svg></span>
      <div><b data-count="300" data-suffix="k+">300k+</b><span class="as-label">Customized cakes delivered</span></div>
    </li>
    <li>
      <span class="as-icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="24" cy="16" r="6"/><path d="M13 38v-3a11 11 0 0 1 22 0v3z"/><circle cx="11" cy="20" r="4.5"/><path d="M3.5 36v-1.5a7.5 7.5 0 0 1 10.5-6.9"/><circle cx="37" cy="20" r="4.5"/><path d="M44.5 36v-1.5a7.5 7.5 0 0 0-10.5-6.9"/></svg></span>
      <div><b data-count="50" data-suffix="+">50+</b><span class="as-label">Team members</span></div>
    </li>
    <li>
      <a href="<?= e(url('awards/')) ?>">
        <span class="as-icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="24" cy="18" r="12"/><circle cx="24" cy="18" r="8.5"/><path d="M24 12.6l1.7 3.4 3.8.6-2.75 2.7.65 3.8L24 21.3l-3.4 1.8.65-3.8-2.75-2.7 3.8-.6z"/><path d="M17 28.5L13 41l5-1.5 3 4 3-13M31 28.5L35 41l-5-1.5-3 4-3-13"/></svg></span>
        <div><b data-count="25" data-suffix="+">25+</b><span class="as-label">Total awards won</span></div>
      </a>
    </li>
  </ul>
</section>

<section class="section">
  <div class="wrap story">
    <div class="story-text">
      <p class="script">Today</p>
      <h2>Baking for the moments people remember</h2>
      <p>Today, La Sem is based in Mississauga, but much of what happens inside the bakery would still be familiar to the generations that came before us.</p>
      <p>There are cakes created for birthdays that families have been planning for months. Wedding cakes that become part of photographs kept for decades. Zeppole that announce the arrival of a familiar season. Pastries picked up because relatives are coming over. Focaccia made from a recipe the bakery has used for more than 60 years, now also served as sandwiches.</p>
      <p>Our counters include custom cakes, pastries, cookies, gelato, breads, zeppole, and other European bakery favourites, each representing a different reason to stop by.</p>
    </div>
    <figure class="story-photo">
      <?= img('2026/10/003.png', 'Wedding cake decorated with flowers', ['sizes' => '(max-width: 900px) 100vw, 45vw']) ?>
    </figure>
  </div>
</section>

<section class="section section-tint" aria-labelledby="people-title">
  <div class="wrap">
    <header class="section-head">
      <p class="script">Behind the counter</p>
      <h2 id="people-title">The people who bake for you</h2>
      <p>Every cake is finished by hand by a team that has celebrated twelve Top Choice Awards together, and still gets excited about the next order.</p>
    </header>
  </div>
  <?php
  // Team photo slider: [photo, description]
  $team_slides = [
      ['2026/11/team-la-sem-2026.jpg', 'The La Sem team today in black chef jackets inside the bakery'],
      ['2026/11/team-top-choice-award.jpg', 'The La Sem team in white chef jackets holding the Top Choice Award, beside the ice sculpture of the award'],
      ['2026/11/team-thank-you-mississauga.jpg', 'La Sem thank-you poster for being voted the number one bakery in Mississauga, with the team at the counter'],
  ];
  ?>
  <div class="wrap team team-slider team-under-head" data-team-slider aria-roledescription="carousel" aria-label="The La Sem team over the years">
    <div class="team-viewport">
      <div class="team-track" data-team-track>
<?php foreach ($team_slides as $n => $t): ?>
        <figure class="team-slide" aria-roledescription="slide" aria-label="<?= $n + 1 ?> of <?= count($team_slides) ?>"><?= img($t[0], $t[1], ['sizes' => '(max-width: 1240px) 100vw, 1200px', 'eager' => false]) ?></figure>
<?php endforeach; ?>
      </div>
      <button class="team-arrow team-prev" type="button" data-team-prev aria-label="Previous photo"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7"/></svg></button>
      <button class="team-arrow team-next" type="button" data-team-next aria-label="Next photo"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7"/></svg></button>
    </div>
    <div class="team-dots" data-team-dots>
<?php foreach ($team_slides as $n => $t): ?>
      <button type="button" aria-label="Show photo <?= $n + 1 ?>"<?= $n === 0 ? ' aria-current="true"' : '' ?>></button>
<?php endforeach; ?>
    </div>
  </div>
</section>

<section class="order-band" aria-label="Order">
  <div class="wrap order-inner">
    <div>
      <p class="script light">Come visit us</p>
      <h2>Taste the tradition for yourself</h2>
      <p>Drop by the bakery in Mississauga, order through DoorDash, or tell us about the custom cake you have in mind.</p>
    </div>
    <div class="order-actions">
      <a class="btn btn-cream btn-lg" href="<?= e(DOORDASH_URL) ?>" target="_blank" rel="noopener">DoorDash Online</a>
      <button class="btn btn-outline-light btn-lg" type="button" data-order-request>Online Custom Order Request</button>
    </div>
  </div>
</section>
<?php visit_section(false); page_end();
