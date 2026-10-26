<?php
/**
 * 3D hero for the cake and bakery pages: each page has its own animation (js/scenes/<slug>.js)
 * and its own colour mood. Pages without a scene file fall back to the plain banner.
 */

/** [script line, glow colour, top colour, base colour, accent colour] per page. */
function scene_themes()
{
    return [
        // Cakes for every occasion
        'wedding-cakes'        => ['Tier by tier, flower by flower', '#d9b77a', '#1d1517', '#2e2226', '#e6c88e'],
        'engagement-cakes'     => ['For the question, and the yes', '#c2364d', '#1e0d10', '#33151b', '#f0a3b0'],
        'anniversary-cakes'    => ['Every year worth celebrating', '#c99a3d', '#1f1309', '#33200f', '#f0c869'],
        'bridal-shower-cakes'  => ['Pretty in pink, piped by hand', '#e07a98', '#22121a', '#3a1d2a', '#f4b3c6'],
        'baby-shower-cakes'    => ['Building blocks for a new arrival', '#6fb3d9', '#0f1a24', '#1a2d3d', '#a7d8f2'],
        'baptism-cakes'        => ['Soft blue and gold, for a blessed day', '#8fbfe6', '#0f1622', '#1c2a3f', '#d8c08a'],
        'communion-cakes'      => ['A milestone in white and gold', '#e3c47e', '#15131c', '#26222f', '#ead39b'],
        'confirmation-cakes'   => ['Faith, family and a fitting cake', '#c4373a', '#1c0c0c', '#301616', '#efb08a'],
        'graduation-cakes'     => ['Caps off to the graduate', '#d6a93a', '#0d1426', '#1a2540', '#f1cf72'],
        'bachelor-party-cakes' => ['Pop the cork, cut the cake', '#d4aa4f', '#0d0b0d', '#1f1a1d', '#f2d27c'],
        'novelty-cakes'        => ['Make a wish', '#a86bd6', '#140d1f', '#251836', '#f3c14b'],
        // From our counter
        'pastries-mississauga'    => ['Fresh from the pastry case', '#e2783a', '#1f120b', '#2a1910', '#f2a66b'],
        'zeppole-mississauga'     => ['Mr. Ventola’s recipe, since 1963', '#c2283d', '#1e0c0c', '#2e1513', '#f08a8a'],
        'cookies-mississauga'     => ['Baked for sharing', '#c98a3e', '#1c120a', '#2c1d10', '#ebc283'],
        'keto-cookies'            => ['Gluten free, sugar free, still a treat', '#7cae5a', '#0e170e', '#1a2919', '#bcdc95'],
        'cupcakes-mississauga'    => ['Swirled, sprinkled, ready to share', '#e45d8c', '#1e0e16', '#331826', '#f7a8c4'],
        'croissants-mississauga'  => ['Golden, flaky, baked this morning', '#e2a63a', '#1d1408', '#2f2110', '#f3cd7c'],
        'cheese-cake-mississauga' => ['New York style, finished with fruit', '#d6455a', '#1c0f10', '#2e191b', '#f3b2a6'],
        'gelato'                  => ['Made in house, scoop by scoop', '#5fbfa3', '#0c1917', '#152b27', '#a6e3cf'],
        'focaccia-sandwiches'     => ['Built on our homemade focaccia', '#9cae4a', '#13160b', '#222812', '#d4dd8f'],
    ];
}

function has_scene($slug)
{
    return isset(scene_themes()[$slug]) && is_file(__DIR__ . '/../js/scenes/' . $slug . '.js');
}

function scene_scripts($slug)
{
    return ['js/vendor/three.min.js', 'js/scene3d.js', 'js/scenes/' . $slug . '.js'];
}

/** First sentence or two of the page text, for the hero. */
function hero_lede($page)
{
    $text = $page['body'][0]['x'] ?? '';
    if (mb_strlen($text) <= 200) return $text;
    $cut = mb_substr($text, 0, 200);
    $dot = max(mb_strrpos($cut, '. '), mb_strrpos($cut, '! '));
    return $dot > 80 ? mb_substr($cut, 0, $dot + 1) : rtrim(mb_substr($cut, 0, mb_strrpos($cut, ' '))) . '…';
}

function scene_hero($page, $actions)
{
    [$script, $glow, $bg1, $bg2, $accent] = scene_themes()[$page['slug']];
    $first = $page['images'][0]['src'] ?? null;
    $label = $page['menu'] ?? $page['title'];
    ?>
<section class="hero hero-page" data-cake-hero aria-labelledby="hero-title"
  style="--glow: <?= e($glow) ?>; --bg1: <?= e($bg1) ?>; --bg2: <?= e($bg2) ?>; --accent: <?= e($accent) ?>">
  <canvas class="hero-canvas" role="img" aria-label="Animation: <?= e(strtolower($label)) ?> being made at La Sem"></canvas>
  <?php if ($first) echo img($first, $page['title'] . ' by La Sem', ['class' => 'hero-fallback', 'eager' => true]); ?>
  <div class="hero-copy wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url()) ?>">Home</a> <span aria-hidden="true">/</span> <a href="<?= e(url('bakery/')) ?>">Bakery</a> <span aria-hidden="true">/</span> <a href="<?= e(url($page['path'])) ?>"><?= e($label) ?></a></nav>
    <p class="hero-script"><?= e($script) ?></p>
    <h1 id="hero-title"><?= e($page['heading'] ?? $page['title']) ?></h1>
    <p class="lede"><?= e(hero_lede($page)) ?></p>
    <div class="hero-actions"><?= $actions ?></div>
  </div>
  <div class="player" aria-label="Animation controls">
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
    <p class="hint">Drag to turn</p>
  </div>
</section>
<?php
}
