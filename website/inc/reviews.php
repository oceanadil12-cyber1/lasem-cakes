<?php
/**
 * Google reviews: the bakery's own 5-star reviews from its Google Business Profile, shown on the
 * home and testimonials pages (google_reviews_section()).
 *
 * They come from Google's official Places API (New), which is how Google allows reviews to be
 * shown on a website (with the reviewer's name and photo, and "Google" credited). Setup is in the
 * admin, Store settings → Google reviews: an API key (Google Cloud, "Places API (New)" enabled) and,
 * if needed, the Place ID (found automatically from the business name and address).
 *
 *   private/google.json          the API key and Place ID (never committed, never shown on the site)
 *   private/google-reviews.json  the saved result: rating, number of reviews, the 5-star reviews
 *
 * Google returns at most 5 reviews per request, so every refresh adds any new 5-star review to the
 * saved ones (the newest 30 are kept). Refreshes happen at most every 12 hours, or from the admin.
 */

const GOOGLE_PROFILE_URL = 'https://maps.app.goo.gl/VnNGhAXHpFVgKws17'; // the bakery's Google Business Profile
const GOOGLE_REVIEWS_TTL = 43200;
const GOOGLE_REVIEWS_KEEP = 30;

function google_config()
{
    $f = dinein_private() . '/google.json';
    $c = is_file($f) ? json_decode((string) file_get_contents($f), true) : [];
    return array_merge(['api_key' => '', 'place_id' => '', 'maps_url' => ''], is_array($c) ? $c : []);
}

function google_save_config(array $c)
{
    return (bool) @file_put_contents(dinein_private() . '/google.json', json_encode(array_merge(google_config(), $c), JSON_PRETTY_PRINT), LOCK_EX);
}

function google_reviews_cache()
{
    $f = dinein_private() . '/google-reviews.json';
    $c = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    return is_array($c) ? $c : ['rating' => 0, 'total' => 0, 'url' => '', 'reviews' => [], 'fetched' => 0, 'error' => ''];
}

/** One call to the Places API (New). Returns [decoded JSON or null, error message]. */
function google_api($method, $url, $key, $fieldMask, $body = null)
{
    if (!function_exists('curl_init')) return [null, 'PHP\'s curl extension is needed to reach Google.'];
    $ch = curl_init($url);
    $headers = ['X-Goog-Api-Key: ' . $key, 'X-Goog-FieldMask: ' . $fieldMask, 'Accept-Language: en'];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 8,
    ]);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) return [null, 'Google couldn\'t be reached (' . $err . ').'];
    $data = json_decode((string) $raw, true);
    if ($code !== 200) {
        return [null, 'Google said: ' . (is_array($data) ? (string) ($data['error']['message'] ?? 'error ' . $code) : 'error ' . $code)];
    }
    return [is_array($data) ? $data : null, is_array($data) ? '' : 'Google sent an answer that couldn\'t be read.'];
}

/** Fetch the latest from Google and merge it into the saved reviews. Returns '' or an error message. */
function google_reviews_refresh()
{
    $cfg = google_config();
    $cache = google_reviews_cache();
    if ($cfg['api_key'] === '') return 'Add a Google API key first.';

    // Find the Place ID once, from the business name and address.
    if ($cfg['place_id'] === '') {
        [$found, $err] = google_api('POST', 'https://places.googleapis.com/v1/places:searchText', $cfg['api_key'], 'places.id,places.displayName,places.formattedAddress',
            ['textQuery' => SITE['legal'] . ', ' . SITE['street'] . ', ' . SITE['city'] . ' ' . SITE['region']]);
        $id = (string) ($found['places'][0]['id'] ?? '');
        if ($id === '') {
            $cache['error'] = $err !== '' ? $err : 'The business wasn\'t found on Google. Enter its Place ID in the admin.';
            google_write_cache($cache);
            return $cache['error'];
        }
        $cfg['place_id'] = $id;
        google_save_config(['place_id' => $id]);
    }

    [$place, $err] = google_api('GET', 'https://places.googleapis.com/v1/places/' . rawurlencode($cfg['place_id']), $cfg['api_key'],
        'displayName,rating,userRatingCount,googleMapsUri,reviews');
    if (!$place) {
        $cache['error'] = $err;
        $cache['fetched'] = time(); // don't retry on every page view; the admin can retry now
        google_write_cache($cache);
        return $err;
    }

    $byId = [];
    foreach ($cache['reviews'] as $r) $byId[$r['id']] = $r;
    foreach ((array) ($place['reviews'] ?? []) as $r) {
        $text = trim((string) ($r['originalText']['text'] ?? $r['text']['text'] ?? ''));
        if ((int) ($r['rating'] ?? 0) !== 5 || $text === '') continue; // 5-star reviews with words only
        $id = (string) ($r['name'] ?? md5(($r['authorAttribution']['displayName'] ?? '') . ($r['publishTime'] ?? '')));
        $byId[$id] = [
            'id'     => $id,
            'author' => (string) ($r['authorAttribution']['displayName'] ?? 'Google user'),
            'photo'  => (string) ($r['authorAttribution']['photoUri'] ?? ''),
            'link'   => (string) ($r['authorAttribution']['uri'] ?? ''),
            'text'   => mb_substr($text, 0, 1200),
            'time'   => (int) strtotime((string) ($r['publishTime'] ?? 'now')),
        ];
    }
    $reviews = array_values($byId);
    usort($reviews, function ($a, $b) { return $b['time'] <=> $a['time']; });

    $cache = [
        'rating'  => (float) ($place['rating'] ?? 0),
        'total'   => (int) ($place['userRatingCount'] ?? 0),
        'url'     => (string) ($place['googleMapsUri'] ?? ''),
        'reviews' => array_slice($reviews, 0, GOOGLE_REVIEWS_KEEP),
        'fetched' => time(),
        'error'   => '',
    ];
    google_write_cache($cache);
    return '';
}

function google_write_cache(array $c)
{
    @file_put_contents(dinein_private() . '/google-reviews.json', json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

/** The saved reviews, refreshed first when they're more than 12 hours old (and a key is set). */
function google_reviews()
{
    $cache = google_reviews_cache();
    if (google_config()['api_key'] !== '' && time() - (int) $cache['fetched'] > GOOGLE_REVIEWS_TTL) {
        google_reviews_refresh();
        $cache = google_reviews_cache();
    }
    return $cache;
}

/** "3 weeks ago" for a review date. */
function review_ago($ts)
{
    $d = time() - $ts;
    if ($d < 86400 * 2) return 'Yesterday';
    if ($d < 86400 * 14) return floor($d / 86400) . ' days ago';
    if ($d < 86400 * 60) return floor($d / (86400 * 7)) . ' weeks ago';
    if ($d < 86400 * 365) return floor($d / (86400 * 30)) . ' months ago';
    $y = floor($d / (86400 * 365));
    return $y . ' year' . ($y > 1 ? 's' : '') . ' ago';
}

/**
 * The reviews section: rating summary on one side, the 5-star reviews scrolling upward on the
 * other (two columns on wide screens). Prints nothing until Google reviews are set up.
 */
function google_reviews_section($heading = 'Loved by our customers')
{
    $g = google_reviews();
    if (empty($g['reviews'])) return;
    $profile = google_config()['maps_url'] ?: GOOGLE_PROFILE_URL;
    $star = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6L2.5 9.4l6.6-.8z"/></svg>';
    $stars = str_repeat($star, 5);
    $card = function ($r, $hidden) use ($stars) {
        $name = e($r['author']);
        return '<article class="gr-card"' . ($hidden ? ' aria-hidden="true"' : '') . '>'
            . '<header>' . ($r['photo'] !== '' ? '<img src="' . e($r['photo']) . '" alt="" width="40" height="40" loading="lazy" referrerpolicy="no-referrer">' : '<span class="gr-initial">' . e(mb_strtoupper(mb_substr($r['author'], 0, 1))) . '</span>')
            . '<div><b>' . ($r['link'] !== '' ? '<a href="' . e($r['link']) . '" rel="noopener nofollow" target="_blank"' . ($hidden ? ' tabindex="-1"' : '') . '>' . $name . '</a>' : $name) . '</b>'
            . '<span class="gr-meta"><span class="gr-stars" role="img" aria-label="5 out of 5 stars">' . $stars . '</span>' . e(review_ago($r['time'])) . '</span></div>'
            . '<span class="gr-g" title="Posted on Google">G</span></header>'
            . '<p>' . nl2br(e($r['text'])) . '</p></article>';
    };
    // Two columns on wide screens and one column with every review on phones; each list is
    // repeated once so the upward scroll loops without a gap.
    $cols = [[], []];
    foreach ($g['reviews'] as $i => $r) $cols[$i % 2][] = $r;
    if (!$cols[1]) $cols[1] = $cols[0];
    $cols[2] = $g['reviews'];
    ?>
<section class="section gr-section" aria-labelledby="gr-title">
  <div class="wrap gr">
    <div class="gr-summary" data-reveal>
      <p class="script">Google reviews</p>
      <h2 id="gr-title"><?= e($heading) ?></h2>
<?php if ($g['rating'] > 0): ?>
      <div class="gr-score"><b><?= e(number_format($g['rating'], 1)) ?></b><span class="gr-stars big" role="img" aria-label="<?= e(number_format($g['rating'], 1)) ?> out of 5 stars"><?= $stars ?></span></div>
      <p class="gr-count">Based on <?= (int) $g['total'] ?> reviews on Google</p>
<?php endif; ?>
      <p class="gr-note">A few of our five-star reviews. They scroll by themselves; hover to pause.</p>
      <div class="gr-actions">
        <a class="btn btn-solid" href="<?= e($profile) ?>" rel="noopener" target="_blank">Read all reviews</a>
        <a class="btn btn-line" href="<?= e($profile) ?>" rel="noopener" target="_blank">Write a review</a>
      </div>
      <p class="gr-powered">Reviews from <b>Google</b></p>
    </div>
    <div class="gr-wall" role="region" aria-label="Five-star Google reviews" tabindex="0">
<?php foreach ($cols as $n => $list): ?>
      <div class="gr-col<?= ['', ' is-second', ' is-all'][$n] ?>">
        <div class="gr-track" style="--gr-time: <?= max(30, count($list) * 9) ?>s">
<?php for ($r = 0; $r < 2; $r++) foreach ($list as $rev) echo '          ' . $card($rev, $r > 0 || ($n === 1 && $cols[1] === $cols[0])) . "\n"; ?>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>
<?php
}
