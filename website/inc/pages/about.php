<?php
page_start([
    'title'       => 'About Us',
    'path'        => 'about-us/',
    'description' => 'La Sem began in Bari, Italy, in 1930 and opened in Toronto in 1963 with the GTA’s first bakery patio. Today we bake custom cakes, pastries, gelato and bread in Mississauga.',
    'og_image'    => 'images/uploads/2026/10/About-us-pg-1st-picture-scaled.webp',
]);
page_banner('About La Sem Bakery', 'Since 1930', '', ['About us' => url('about-us/')]);
?>
<section class="section">
  <div class="wrap about-intro">
    <div class="prose">
      <p class="lead">Some recipes survive because they are written down. Others survive because someone stands beside you and shows you when the dough feels right, when the pastry is ready, and when a cake finally looks finished.</p>
      <p>La Sem belongs to the second kind of tradition.</p>
      <p>Our story began in Bari, Italy, in 1930, when Mimi Semeraro founded La Sem and a young Nicola Ventola discovered his passion for cakes, pastries, and confectionery. In 1963, Nicola carried that experience to Canada and opened La Sem in mid-town Toronto, beginning a new chapter for the family bakery.</p>
      <p><a class="btn btn-solid" href="<?= e(SITE['maps_url']) ?>" rel="noopener">Get directions</a></p>
    </div>
    <figure class="about-photo">
      <?= img('2026/10/About-us-pg-1st-picture-scaled.jpg', 'A tall La Sem celebration cake', ['sizes' => '(max-width: 900px) 100vw, 40vw', 'eager' => true]) ?>
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
    </div>
  </div>
</section>

<section class="stats" aria-label="La Sem in numbers">
  <ul class="wrap stats-list">
    <li><b>60+</b><span>years of experience</span></li>
    <li><b>300k+</b><span>customized cakes delivered</span></li>
    <li><b>25+</b><span>awards won</span></li>
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
  <div class="wrap team">
    <?= img('2026/06/Staff-1-4.jpg', 'The La Sem team', ['sizes' => '100vw']) ?>
  </div>
</section>

<section class="order-band" aria-label="Order">
  <div class="wrap order-inner">
    <div>
      <p class="script light">Come visit us</p>
      <h2>Taste the tradition for yourself</h2>
      <p>Drop by the bakery in Mississauga, order for pick-up online, or tell us about the custom cake you have in mind.</p>
    </div>
    <div class="order-actions">
      <a class="btn btn-cream btn-lg" href="<?= e(url('order/')) ?>">Order online</a>
      <a class="btn btn-outline-light btn-lg" href="<?= e(url('contact-us/')) ?>#enquiry">Custom cake request</a>
    </div>
  </div>
</section>
<?php visit_section(); page_end();
