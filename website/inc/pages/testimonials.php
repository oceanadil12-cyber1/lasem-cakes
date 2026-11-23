<?php
page_start([
    'title'       => 'Testimonials',
    'path'        => 'testimonials/',
    'description' => 'What families say about their La Sem wedding, birthday and celebration cakes. Read customer thank-you notes from Mississauga and beyond.',
]);
page_banner('Your opinion matters!', 'Testimonials', 'Thank-you notes from the families we’ve baked for.', ['Testimonials' => url('testimonials/')]);
?>
<section class="section">
  <div class="wrap">
    <div class="quotes">
<?php foreach (testimonials() as $t): ?>
      <blockquote class="quote">
        <p><?= e($t['text']) ?></p>
        <footer><b><?= e($t['name']) ?></b><?php if (!empty($t['occasion'])): ?><span><?= e($t['occasion']) ?></span><?php endif; ?></footer>
      </blockquote>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php google_reviews_section('Five stars on Google'); ?>
<?php order_band('Planning your own celebration?'); page_end();
