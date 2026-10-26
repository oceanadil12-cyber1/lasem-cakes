<?php
$sent = isset($_GET['sent']);
$error = $_GET['error'] ?? '';
$cake = trim($_GET['cake'] ?? '');
page_start([
    'title'       => 'Contact Us',
    'path'        => 'contact-us/',
    'description' => 'Contact La Sem Cakes, 1275 Eglinton Ave. East, Mississauga. Call (905) 624-8888, email info@lasemcakes.ca, or send a custom cake enquiry. Open every day 8:30 am – 6:00 pm.',
]);
page_banner('Contact us', 'We’d love to hear from you', 'Thank you for choosing us for your special occasion. We wish to serve and do everything possible for you to have your dream cake, beautiful and delicious.', ['Contact us' => url('contact-us/')]);
?>
<section class="section">
  <div class="wrap contact">
    <div class="contact-info">
      <h2>Visit or call</h2>
      <dl>
        <dt>Address</dt><dd><?= e(SITE['street']) ?><br><?= e(SITE['city'] . ', ' . SITE['region'] . ' ' . SITE['postal']) ?>, Canada</dd>
        <dt>Phone</dt><dd><a href="tel:<?= e(SITE['phone_tel']) ?>"><?= e(SITE['phone']) ?></a></dd>
        <dt>Fax</dt><dd><?= e(SITE['fax']) ?></dd>
        <dt>Email</dt><dd><a href="mailto:<?= e(SITE['email']) ?>"><?= e(SITE['email']) ?></a></dd>
      </dl>
      <h3>Hours <span class="open-now" data-open-now data-opens="<?= e(SITE['opens']) ?>" data-closes="<?= e(SITE['closes']) ?>"></span></h3>
      <table class="hours">
        <?php foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $d): ?>
        <tr data-day="<?= e($d) ?>"><th><?= e($d) ?></th><td>8:30 am – 6:00 pm</td></tr>
        <?php endforeach; ?>
      </table>
      <p><a class="btn btn-line" href="<?= e(SITE['maps_url']) ?>" rel="noopener">Get directions</a></p>
    </div>

    <div class="contact-form" id="enquiry">
      <h2>Custom cake enquiry</h2>
<?php if ($sent): ?>
      <div class="notice ok" role="status"><b>Thank you, your enquiry is on its way.</b> We’ll get back to you soon. For anything urgent, call <?= e(SITE['phone']) ?>.</div>
<?php elseif ($error): ?>
      <div class="notice err" role="alert"><?= e($error === 'missing' ? 'Please fill in your name, a phone number or email, and your message.' : 'Sorry, something went wrong sending your message. Please call us at ' . SITE['phone'] . '.') ?></div>
<?php endif; ?>
      <p>Tell us about your celebration and we’ll help with size, flavour and design.</p>
      <form method="post" action="<?= e(url('api/contact.php')) ?>">
        <div class="field-row">
          <label>Your name <span aria-hidden="true">*</span><input name="name" required autocomplete="name"></label>
          <label>Phone<input name="phone" type="tel" autocomplete="tel"></label>
        </div>
        <label>Email<input name="email" type="email" autocomplete="email"></label>
        <div class="field-row">
          <label>Cake or occasion
            <select name="occasion">
              <option value="">Choose…</option>
<?php foreach (content()['pages'] as $p): if ($p['type'] !== 'occasion') continue; ?>
              <option<?= $cake === $p['title'] ? ' selected' : '' ?>><?= e($p['title']) ?></option>
<?php endforeach; ?>
              <option>Birthday cake</option>
              <option>Pastries or trays</option>
              <option>Something else</option>
            </select>
          </label>
          <label>Date needed<input name="date" type="date"></label>
        </div>
        <label>Number of guests<input name="guests" type="number" min="1" inputmode="numeric"></label>
        <label>Your idea <span aria-hidden="true">*</span><textarea name="message" rows="5" required placeholder="Flavours, colours, theme, writing on the cake…"></textarea></label>
        <label class="hp" aria-hidden="true">Leave this empty<input name="website" tabindex="-1" autocomplete="off"></label>
        <p class="small-note">We reply by phone or email. Please include at least one.</p>
        <button class="btn btn-solid btn-lg" type="submit">Send enquiry</button>
      </form>
    </div>
  </div>
</section>

<section class="map-wide" aria-label="Map">
  <iframe title="Map to La Sem Cakes" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
    src="https://maps.google.com/maps?q=1275%20Eglinton%20Ave%20E%2C%20Mississauga%2C%20ON%20L4W%202Z2%2C%20Canada&amp;t=m&amp;z=14&amp;output=embed&amp;iwloc=near"></iframe>
</section>
<?php page_end();
