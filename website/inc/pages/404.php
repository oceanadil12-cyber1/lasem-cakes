<?php
page_start(['title' => 'Page not found', 'path' => '', 'description' => 'This page could not be found.']);
page_banner('We couldn’t find that page', 'Oops', 'It may have moved when we rebuilt the website. Try one of these instead.');
?>
<section class="section">
  <div class="wrap notfound">
    <a class="btn btn-solid" href="<?= e(url()) ?>">Home</a>
    <a class="btn btn-line" href="<?= e(url('bakery/')) ?>">Cakes &amp; bakery</a>
    <a class="btn btn-line" href="<?= e(url('gallery/')) ?>">Gallery</a>
    <a class="btn btn-line" href="<?= e(url('contact-us/')) ?>">Contact us</a>
  </div>
</section>
<?php page_end();
