<?php
/**
 * Store settings:
 *   Online ordering     open/closed, pickup hours, notice needed, days ahead, closed days, HST   (data/menu.json)
 *   Business details    name, phone, email, address, hours, links; announcement bar; where emails go (data/settings.json)
 *   Kitchen display     passcode for /kds/, when orders should be started, chime     (private/kds.json)
 *   Google reviews      API key + Place ID for the 5-star Google reviews on the site  (private/google.json)
 *   Your sign-in        change the admin password
 */
if (!defined('ADMIN')) { http_response_code(403); exit('Forbidden'); }

// settings.json key => [label, max length, input type, hint]
const STORE_FIELDS = [
    'name'      => ['Business name', 80, 'text', ''],
    'legal'     => ['Legal name', 80, 'text', 'Used in the site\'s structured data for Google.'],
    'phone'     => ['Phone', 30, 'tel', ''],
    'fax'       => ['Fax', 30, 'tel', 'Leave blank to hide.'],
    'email'     => ['Email', 120, 'email', 'Shown on the website.'],
    'street'    => ['Street address', 120, 'text', ''],
    'city'      => ['City', 60, 'text', ''],
    'region'    => ['Province', 10, 'text', ''],
    'postal'    => ['Postal code', 10, 'text', ''],
    'hours'     => ['Opening hours (as shown on the site)', 120, 'text', 'e.g. Every day, 8:30 am – 6:00 pm'],
    'opens'     => ['Store opens', 5, 'time', 'For Google.'],
    'closes'    => ['Store closes', 5, 'time', 'For Google.'],
    'maps_url'  => ['Google Maps link', 300, 'url', ''],
    'facebook'  => ['Facebook page', 300, 'url', ''],
];

function admin_page_settings(): void
{
    if (is_post()) {
        require_csrf();
        $op = (string) ($_POST['op'] ?? '');
        if ($op === 'ordering') admin_settings_ordering();
        if ($op === 'store') admin_settings_store();
        if ($op === 'kds') admin_settings_kds();
        if ($op === 'google') admin_settings_google();
        if ($op === 'password') {
            $err = admin_change_password((string) ($_POST['current'] ?? ''), (string) ($_POST['new'] ?? ''), (string) ($_POST['again'] ?? ''));
            flash($err !== '' ? $err : 'Password changed. Other devices signed in to the admin have been signed out.', $err !== '' ? 'err' : 'ok');
            redirect(admin_url('settings') . '#password');
        }
        redirect(admin_url('settings'));
    }
    admin_settings_page();
}

function valid_time(string $t): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);
}

function admin_settings_ordering(): void
{
    $menu = data_read('menu');
    $back = admin_url('settings') . '#ordering';
    $opens = (string) ($_POST['opens'] ?? '');
    $closes = (string) ($_POST['closes'] ?? '');
    $lead = (int) ($_POST['lead'] ?? 30);
    $ahead = (int) ($_POST['ahead'] ?? 14);
    $tax = str_replace(',', '.', trim((string) ($_POST['tax'] ?? '13')));
    $errors = [];
    if (!valid_time($opens) || !valid_time($closes) || $opens >= $closes) $errors[] = 'Check the pickup hours: the first pickup time must be before the last.';
    if ($lead < 0 || $lead > 2880) $errors[] = 'Notice needed must be between 0 and 2,880 minutes (2 days).';
    if ($ahead < 0 || $ahead > 90) $errors[] = 'Customers can order between 0 and 90 days ahead.';
    if (!is_numeric($tax) || (float) $tax < 0 || (float) $tax > 25) $errors[] = 'HST must be a percentage between 0 and 25.';
    $dfee = str_replace(',', '.', trim((string) ($_POST['dfee'] ?? '0')));
    $dmin = str_replace(',', '.', trim((string) ($_POST['dmin'] ?? '0')));
    $dfree = str_replace(',', '.', trim((string) ($_POST['dfree'] ?? '0')));
    $dlead = (int) ($_POST['dlead'] ?? 0);
    foreach (['Delivery fee' => $dfee, 'Minimum delivery order' => $dmin, 'Free delivery over' => $dfree] as $what => $v) {
        if (!is_numeric($v) || (float) $v < 0 || (float) $v > 10000) $errors[] = $what . ' must be a dollar amount between 0 and 10,000.';
    }
    if ($dlead < 0 || $dlead > 2880) $errors[] = 'Extra delivery notice must be between 0 and 2,880 minutes.';
    if ($errors) {
        foreach ($errors as $e) flash($e, 'err');
        redirect($back);
    }

    $dates = [];
    foreach (menu_closed_dates($menu) as $d) if (empty($_POST['undate'][$d])) $dates[] = $d;
    foreach ((array) ($_POST['adddate'] ?? []) as $d) {
        if (is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && $d >= date('Y-m-d')) $dates[] = $d;
    }
    $dates = array_values(array_unique($dates));
    sort($dates);

    $wasOpen = menu_ordering_open($menu);
    $menu['ordering'] = !empty($_POST['ordering']);
    $menu['closedMessage'] = trim(post_str('closedMessage', 300));
    $menu['hours'] = ['opens' => $opens, 'closes' => $closes];
    $menu['leadMinutes'] = $lead;
    $menu['daysAhead'] = $ahead;
    $menu['taxRate'] = round((float) $tax / 100, 4);
    $menu['closedDates'] = $dates;
    $prefixes = [];
    foreach (preg_split('/[s,;]+/', strtoupper(post_str('dprefixes', 400))) as $p) {
        if ($p !== '' && preg_match('/^[A-Z](d([A-Z](d)?)?)?$/', $p)) $prefixes[] = $p;
    }
    $menu['delivery'] = [
        'enabled'        => !empty($_POST['denabled']),
        'fee'            => (int) round($dfee * 100),
        'minOrder'       => (int) round($dmin * 100),
        'freeOver'       => (int) round($dfree * 100),
        'leadMinutes'    => $dlead,
        'areas'          => post_str('dareas', 120),
        'postalPrefixes' => array_values(array_unique($prefixes)),
        'note'           => post_str('dnote', 300),
    ];
    if (data_save('menu', $menu)) {
        admin_log('settings.order', ($menu['ordering'] ? 'open' : 'closed') . ', pickups ' . $opens . '–' . $closes . ', delivery ' . ($menu['delivery']['enabled'] ? 'on' : 'off') . ', ' . plural(count($dates), 'closed day'));
        flash($wasOpen !== $menu['ordering']
            ? ($menu['ordering'] ? 'Online ordering is on. Customers can place pickup orders again.' : 'Online ordering is off. The order page now says it\'s closed.')
            : 'Online ordering settings saved.');
    } else {
        flash('The settings couldn\'t be saved. Check that website/data/menu.json can be written to.', 'err');
    }
    redirect($back);
}

function admin_settings_store(): void
{
    $saved = data_read('settings');
    $new = [];
    $errors = [];
    foreach (STORE_FIELDS as $k => [$label, $max, $type]) {
        $v = trim(post_str($k, $max));
        if ($type === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) { $errors[] = $label . ' doesn\'t look like an email address.'; continue; }
        if ($type === 'url' && $v !== '' && !preg_match('#^https?://#i', $v)) { $errors[] = $label . ' must start with https://'; continue; }
        if ($type === 'time' && $v !== '' && !valid_time($v)) { $errors[] = 'Check ' . strtolower($label) . '.'; continue; }
        if ($k === 'phone' && strlen(preg_replace('/\D/', '', $v)) < 10) { $errors[] = 'The phone number needs an area code.'; continue; }
        if ($v === '' && in_array($k, ['name', 'email', 'street', 'city'], true)) { $errors[] = $label . ' can\'t be empty.'; continue; }
        $new[$k] = $v;
    }
    $notify = trim(post_str('notify_email', 120));
    if ($notify !== '' && !filter_var($notify, FILTER_VALIDATE_EMAIL)) $errors[] = 'The email for orders and enquiries doesn\'t look right.';
    else $new['notify_email'] = $notify;
    $new['announcement'] = trim(post_str('announcement', 200));

    $settings = array_merge($saved, $new);
    if (data_save('settings', $settings)) {
        admin_log('settings.store', implode(', ', array_keys(array_diff_assoc($new, array_merge(SITE_DEFAULTS, $saved)))) ?: 'no changes');
        flash($errors ? 'Saved, except for the problems below.' : 'Business details saved. The website is updated.');
    } else {
        flash('The settings couldn\'t be saved. Check that the website/data folder can be written to.', 'err');
    }
    foreach ($errors as $e) flash($e, 'err');
    redirect(admin_url('settings') . '#store');
}

function admin_settings_google(): void
{
    $back = admin_url('settings') . '#google';
    $changes = [];
    $key = trim(post_str('api_key', 200));
    if (!empty($_POST['nokey'])) $changes['api_key'] = '';
    elseif ($key !== '') {
        if (!preg_match('/^[A-Za-z0-9_\-]{20,120}$/', $key)) {
            flash('That doesn\'t look like a Google API key (they start with "AIza"). Nothing was saved.', 'err');
            redirect($back);
        }
        $changes['api_key'] = $key;
    }
    $place = trim(post_str('place_id', 200));
    if ($place !== '' && !preg_match('/^[A-Za-z0-9_\-]{10,200}$/', $place)) {
        flash('That Place ID doesn\'t look right. Nothing was saved.', 'err');
        redirect($back);
    }
    $changes['place_id'] = $place; // blank = find it automatically from the business name and address
    $maps = trim(post_str('maps_url', 300));
    $changes['maps_url'] = preg_match('#^https://#', $maps) ? $maps : '';
    if (!google_save_config($changes)) {
        flash('The Google settings couldn\'t be saved. Check that website/private can be written to.', 'err');
        redirect($back);
    }
    admin_log('settings.google', isset($changes['api_key']) ? ($changes['api_key'] === '' ? 'key removed' : 'key changed') : 'place');
    if (google_config()['api_key'] === '') {
        flash('Google settings saved. Add an API key to show the reviews.');
        redirect($back);
    }
    $err = google_reviews_refresh();
    $g = google_reviews_cache();
    flash($err === '' ? 'Saved and refreshed: ' . count($g['reviews']) . ' five-star reviews ready, ' . number_format($g['rating'], 1) . ' stars from ' . $g['total'] . ' reviews.' : 'Saved, but Google didn\'t answer: ' . $err, $err === '' ? 'ok' : 'err');
    redirect($back);
}

function admin_settings_kds(): void
{
    $back = admin_url('settings') . '#kds';
    $changes = ['prep_minutes' => max(5, min(1440, (int) ($_POST['prep_minutes'] ?? 30))), 'sound' => !empty($_POST['sound'])];
    $pin = (string) ($_POST['pin'] ?? '');
    $note = '';
    if (!empty($_POST['nopin'])) {
        $changes += ['pin_hash' => '', 'pin_changed' => date('c')];
        $note = ' The passcode is removed: the kitchen display now needs the admin sign-in, and open screens were signed out.';
    } elseif ($pin !== '') {
        if (!preg_match('/^\S{6,40}$/', $pin)) {
            flash('Use 6 to 40 characters for the kitchen passcode (no spaces). Nothing was saved.', 'err');
            redirect($back);
        }
        if ($pin !== (string) ($_POST['pin2'] ?? '')) {
            flash('The two passcodes don\'t match. Nothing was saved.', 'err');
            redirect($back);
        }
        $changes += ['pin_hash' => password_hash($pin, PASSWORD_DEFAULT), 'pin_changed' => date('c')];
        $note = ' New passcode set; kitchen screens that were open need to sign in again.';
    }
    if (kds_save_settings($changes)) {
        admin_log('settings.kds', 'start ' . $changes['prep_minutes'] . ' min before pickup' . (isset($changes['pin_hash']) ? ($changes['pin_hash'] === '' ? ', passcode removed' : ', passcode changed') : ''));
        flash('Kitchen display settings saved.' . $note);
    } else {
        flash('The kitchen display settings couldn\'t be saved. Check that website/private can be written to.', 'err');
    }
    redirect($back);
}

function admin_settings_page(): void
{
    $menu = data_read('menu');
    $open = menu_ordering_open($menu);
    $dv = menu_delivery($menu);
    $dollars = fn($c) => rtrim(rtrim(number_format($c / 100, 2, '.', ''), '0'), '.');
    $dates = '';
    foreach (menu_closed_dates($menu) as $d) {
        $dates .= '<label class="check chip"><input type="checkbox" name="undate[' . h($d) . ']" value="1"> ' . h(date('D, M j, Y', strtotime($d))) . ' <small class="muted">remove</small></label>';
    }
    $ordering = '<div id="ordering">' . card('Online ordering', form_open(admin_url('settings')) . '<input type="hidden" name="op" value="ordering">'
        . '<div class="toggle-row"><label class="switch"><input type="checkbox" name="ordering" value="1" ' . ($open ? 'checked' : '') . ' aria-label="Online ordering on"><span></span></label>'
        . '<div><b>' . ($open ? 'Online ordering is on' : 'Online ordering is off') . '</b><p class="muted small">Turn it off to stop new pickup orders (holidays, busy days). The menu stays visible.</p></div></div>'
        . '<label class="field wide"><span>Message while ordering is off</span><input name="closedMessage" value="' . h((string) ($menu['closedMessage'] ?? '')) . '" maxlength="300" placeholder="' . h(menu_closed_message(['closedMessage' => ''])) . '"></label>'
        . '<div class="form-grid">'
        . '<label class="field sm"><span>First pickup</span><input type="time" name="opens" value="' . h($menu['hours']['opens'] ?? '09:00') . '" step="300" required></label>'
        . '<label class="field sm"><span>Last pickup</span><input type="time" name="closes" value="' . h($menu['hours']['closes'] ?? '17:00') . '" step="300" required></label>'
        . '<label class="field sm"><span>Notice needed</span><span class="mins"><input name="lead" value="' . (int) ($menu['leadMinutes'] ?? 30) . '" inputmode="numeric" required> min</span></label>'
        . '<label class="field sm"><span>Order up to</span><span class="mins"><input name="ahead" value="' . (int) ($menu['daysAhead'] ?? 14) . '" inputmode="numeric" required> days ahead</span></label>'
        . '<label class="field sm"><span>HST</span><span class="mins"><input name="tax" value="' . h(rtrim(rtrim(number_format(($menu['taxRate'] ?? 0.13) * 100, 2, '.', ''), '0'), '.')) . '" inputmode="decimal" required> %</span></label>'
        . '</div>'
        . '<p class="muted small">Pickup times are offered every 15 minutes between the first and last pickup. HST is only added to categories marked "Add HST" in Menu &amp; prices.</p>'
        . '<h3 class="sub-h">Closed days</h3><p class="muted small">No pickups can be booked on these days (holidays, private events).</p>'
        . ($dates !== '' ? '<div class="chips">' . $dates . '</div>' : '<p class="muted">None set.</p>')
        . '<div class="form-grid">'
        . '<label class="field sm"><span>Add a closed day</span><input type="date" name="adddate[]" min="' . date('Y-m-d') . '"></label>'
        . '<label class="field sm"><span>And another</span><input type="date" name="adddate[]" min="' . date('Y-m-d') . '"></label>'
        . '</div>'
        . '<h3 class="sub-h">Delivery</h3><p class="muted small">Customers choose pickup or delivery in their cart. The fee is added to the order (HST applies) and collected on delivery. Leave the postal codes empty to accept any address.</p>'
        . '<div class="toggle-row"><label class="switch"><input type="checkbox" name="denabled" value="1" ' . ($dv['enabled'] ? 'checked' : '') . ' aria-label="Delivery on"><span></span></label>'
        . '<div><b>' . ($dv['enabled'] ? 'Delivery is on' : 'Delivery is off') . '</b><p class="muted small">Turn it off to offer pickup only.</p></div></div>'
        . '<div class="form-grid">'
        . '<label class="field sm"><span>Delivery fee</span><span class="mins">$ <input name="dfee" value="' . h($dollars($dv['fee'])) . '" inputmode="decimal"></span></label>'
        . '<label class="field sm"><span>Free delivery over</span><span class="mins">$ <input name="dfree" value="' . h($dollars($dv['freeOver'])) . '" inputmode="decimal"> (0 = never)</span></label>'
        . '<label class="field sm"><span>Minimum order</span><span class="mins">$ <input name="dmin" value="' . h($dollars($dv['minOrder'])) . '" inputmode="decimal"></span></label>'
        . '<label class="field sm"><span>Extra notice</span><span class="mins"><input name="dlead" value="' . (int) $dv['leadMinutes'] . '" inputmode="numeric"> min on top of pickup notice</span></label>'
        . '</div>'
        . '<label class="field wide"><span>Delivery area (shown to customers)</span><input name="dareas" value="' . h($dv['areas']) . '" maxlength="120" placeholder="e.g. Mississauga and nearby"></label>'
        . '<label class="field wide"><span>Postal codes we deliver to (first 3 characters, comma separated)</span><input name="dprefixes" value="' . h(implode(', ', $dv['postalPrefixes'])) . '" maxlength="400" placeholder="e.g. L4W, L4X, L5A — empty = anywhere"></label>'
        . '<label class="field wide"><span>Note shown at checkout</span><input name="dnote" value="' . h($dv['note']) . '" maxlength="300" placeholder="e.g. Someone needs to be home to receive the order."></label>'
        . '<div class="bar">' . btn_submit('Save ordering settings', 'primary', '', '', 'check') . '</div></form>') . '</div>';

    $fields = '';
    foreach (STORE_FIELDS as $k => [$label, $max, $type, $hint]) {
        $wide = in_array($k, ['hours', 'maps_url', 'facebook', 'street'], true);
        $small = in_array($k, ['region', 'postal', 'opens', 'closes'], true);
        $fields .= '<label class="field' . ($wide ? ' wide' : ($small ? ' sm' : '')) . '"><span>' . h($label) . '</span>'
            . '<input type="' . $type . '" name="' . $k . '" value="' . h((string) SITE[$k]) . '" maxlength="' . $max . '">'
            . ($hint !== '' ? '<small class="muted">' . h($hint) . '</small>' : '') . '</label>';
    }
    $store = '<div id="store">' . card('Business details', form_open(admin_url('settings')) . '<input type="hidden" name="op" value="store">'
        . '<p class="muted small">Shown in the website\'s header, footer, contact page, order page and emails.</p>'
        . '<div class="form-grid">' . $fields . '</div>'
        . '<h3 class="sub-h">Emails</h3><div class="form-grid">'
        . '<label class="field wide"><span>Send new orders and enquiries to</span><input type="email" name="notify_email" value="' . h(SITE['notify_email']) . '" maxlength="120" placeholder="' . h(SITE['email']) . '">'
        . '<small class="muted">Leave blank to use the email above. Every order and enquiry is also kept here in the admin.</small></label></div>'
        . '<h3 class="sub-h">Announcement bar</h3><div class="form-grid">'
        . '<label class="field wide"><span>Short message at the top of every page</span><input name="announcement" value="' . h(SITE['announcement']) . '" maxlength="200" placeholder="e.g. Closed Dec 25–26. Order Christmas cakes by Dec 20!">'
        . '<small class="muted">Leave blank for no bar.</small></label></div>'
        . '<div class="bar">' . btn_submit('Save business details', 'primary', '', '', 'check') . '</div></form>') . '</div>';

    $k = kds_settings();
    $pinSet = $k['pin_hash'] !== '';
    $kds = '<div id="kds">' . card('Kitchen display', form_open(admin_url('settings')) . '<input type="hidden" name="op" value="kds">'
        . '<p class="muted small">A live screen for the bakery and the counter: today\'s pickups in time order, a chime for new orders, and one-tap Start, Ready and Picked up. Open it on a tablet at <a href="' . h(SITE_BASE) . 'kds/" target="_blank" rel="noopener">' . h(SITE_BASE) . 'kds/</a>.</p>'
        . '<div class="form-grid">'
        . '<label class="field sm"><span>Start orders</span><span class="mins"><input name="prep_minutes" value="' . (int) $k['prep_minutes'] . '" inputmode="numeric"> min before pickup</span></label>'
        . '<label class="check"><input type="checkbox" name="sound" value="1"' . ($k['sound'] ? ' checked' : '') . '> Chime for new orders</label>'
        . '</div>'
        . '<h3 class="sub-h">Kitchen passcode</h3>'
        . '<p class="muted small">' . ($pinSet
            ? 'A passcode is set' . ($k['pin_changed'] !== '' ? ' (changed ' . h(fmt_ago((int) strtotime($k['pin_changed']))) . ')' : '') . '. Staff use it to open the kitchen display; it doesn\'t open this admin.'
            : 'No passcode yet, so the kitchen display asks for the admin sign-in. Set a passcode for the kitchen staff: it opens only the kitchen display, not this admin.') . '</p>'
        . '<input type="text" name="username" value="kitchen" autocomplete="username" hidden>'
        . '<div class="form-grid">'
        . '<label class="field"><span>' . ($pinSet ? 'New passcode' : 'Passcode') . ' <small class="muted">(6+ characters, numbers are fine)</small></span><input type="password" name="pin" autocomplete="new-password" inputmode="numeric" minlength="6" maxlength="40"></label>'
        . '<label class="field"><span>Passcode again</span><input type="password" name="pin2" autocomplete="new-password" inputmode="numeric" maxlength="40"></label>'
        . ($pinSet ? '<label class="check danger"><input type="checkbox" name="nopin" value="1"> Remove the passcode</label>' : '')
        . '</div><div class="bar">' . btn_submit('Save kitchen display', 'primary', '', '', 'check') . ' ' . btn_link('Open kitchen display', SITE_BASE . 'kds/', 'sm ghost', 'external', ' target="_blank" rel="noopener"') . '</div></form>') . '</div>';

    $gc = google_config();
    $gr = google_reviews_cache();
    $hasKey = $gc['api_key'] !== '';
    $google = '<div id="google">' . card('Google reviews', form_open(admin_url('settings')) . '<input type="hidden" name="op" value="google">'
        . '<p class="muted small">Shows your 5-star Google reviews on the home and testimonials pages, scrolling upward, with the reviewers\' names and Google credited, as Google requires. They refresh twice a day; Google sends up to 5 reviews at a time and new 5-star ones are added to the saved list.</p>'
        . ($hasKey
            ? ($gr['error'] !== ''
                ? '<div class="callout">' . icon('alert', 18) . '<div><b>Last refresh didn\'t work</b><p class="muted">' . h($gr['error']) . '</p></div></div>'
                : '<p class="small"><b>' . count($gr['reviews']) . ' five-star reviews</b> saved' . ($gr['rating'] ? ' · ' . h(number_format($gr['rating'], 1)) . ' stars from ' . (int) $gr['total'] . ' reviews' : '') . ($gr['fetched'] ? ' · refreshed ' . h(fmt_ago((int) $gr['fetched'])) : '') . '</p>')
            : '<p class="small"><b>Not set up yet.</b> In Google Cloud, create a project, turn on <b>Places API (New)</b>, create an API key (restrict it to that API) and paste it here.</p>')
        . '<input type="text" name="username" value="google" autocomplete="username" hidden>'
        . '<div class="form-grid">'
        . '<label class="field wide"><span>API key ' . ($hasKey ? '<small class="muted">(saved; leave blank to keep it)</small>' : '') . '</span><input type="password" name="api_key" autocomplete="new-password" placeholder="' . ($hasKey ? '••••••••' . h(substr($gc['api_key'], -4)) : 'AIza…') . '"></label>'
        . '<label class="field wide"><span>Place ID <small class="muted">(blank = found automatically)</small></span><input name="place_id" value="' . h($gc['place_id']) . '" maxlength="200"></label>'
        . '<label class="field wide"><span>Your Google Maps link <small class="muted">(for "Read all reviews")</small></span><input type="url" name="maps_url" value="' . h($gc['maps_url']) . '" placeholder="https://maps.app.goo.gl/…"></label>'
        . ($hasKey ? '<label class="check danger"><input type="checkbox" name="nokey" value="1"> Remove the API key (stops refreshing; saved reviews stay)</label>' : '')
        . '</div><div class="bar">' . btn_submit($hasKey ? 'Save and refresh now' : 'Save', 'primary', '', '', 'refresh') . '</div></form>') . '</div>';

    $account = admin_account();
    $password = '<div id="password">' . card('Your sign-in', form_open(admin_url('settings')) . '<input type="hidden" name="op" value="password">'
        . '<p class="muted small">Signed in as <b>' . h($account['user'] ?? '') . '</b>' . (!empty($account['changed']) ? '. Password last changed ' . h(fmt_ago((int) strtotime($account['changed']))) : '') . '.</p>'
        . '<input type="text" name="username" value="' . h($account['user'] ?? '') . '" autocomplete="username" hidden>'
        . '<div class="form-grid">'
        . '<label class="field"><span>Current password</span><input type="password" name="current" autocomplete="current-password" required></label>'
        . '<label class="field"><span>New password <small class="muted">(' . ADMIN_MIN_PASSWORD . '+ characters)</small></span><input type="password" name="new" autocomplete="new-password" minlength="' . ADMIN_MIN_PASSWORD . '" required></label>'
        . '<label class="field"><span>New password again</span><input type="password" name="again" autocomplete="new-password" required></label>'
        . '</div><div class="bar">' . btn_submit('Change password', 'primary', '', '', 'lock') . '</div></form>') . '</div>';

    $body = page_head('Store settings', 'Online ordering, business details, the kitchen display and your sign-in.')
        . '<div class="split"><div class="stack">' . $ordering . $store . '</div><div class="stack">' . $kds . $google . $password
        . card('Backups', '<p class="muted small">Every time something is saved here, the previous version of the file is kept on the server in <code>website/private/backups</code> (the newest 30 of each).</p>')
        . '</div></div>';
    admin_render('Store settings', $body, 'settings');
}
