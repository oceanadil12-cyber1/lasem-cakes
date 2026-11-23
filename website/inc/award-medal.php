<?php
// Award medals drawn as vector graphics, so they stay sharp at any size (the badge files from the
// old site are only 160 pixels tall). award_medal_svg() takes:
//   theme  gold (Top Choice), platinum (Readers' Choice) or bronze (People's Choice)
//   top / bottom   the text around the rim;  small  the line above the check mark;  year
//   plaque  ['num' => 12, 'lines' => ['YEARS', 'ON TOP']] or ['lines' => ['PLATINUM']] (one or two lines)
//   label   the accessible description
// $i keeps gradient ids unique when several medals share a page.
const AWARD_THEMES = [
    'gold'     => ['rim' => ['#fff3cc', '#dcb45f', '#9a7020', '#e9c97c', '#fff6d6'], 'face' => ['#fbe8b0', '#d7ad56', '#9f7522'], 'ink' => '#2a1a08', 'line' => '#7a5516', 'thin' => '#c9a15b', 'check' => '#b58a2a', 'num' => '#f3d58a'],
    'platinum' => ['rim' => ['#ffffff', '#c9ccd3', '#6f737c', '#dfe1e6', '#ffffff'], 'face' => ['#f4f5f7', '#bfc3cb', '#7d828c'], 'ink' => '#1f2430', 'line' => '#5d626c', 'thin' => '#aab0ba', 'check' => '#8d929c', 'num' => '#e8eaee'],
    'bronze'   => ['rim' => ['#ffe1cc', '#d2925f', '#7a4722', '#ecb990', '#ffe6d3'], 'face' => ['#f6d2b4', '#cf905f', '#8a5430'], 'ink' => '#3a1f0c', 'line' => '#7a4722', 'thin' => '#d9a27a', 'check' => '#b8744a', 'num' => '#f6d2b4'],
];

function award_medal_svg($o, $i = 0)
{
    $t = AWARD_THEMES[$o['theme'] ?? 'gold'];
    $id = 'm' . (int) $i;
    $p = $o['plaque'] ?? ['lines' => []];
    $lines = array_values($p['lines'] ?? []);
    if (!empty($p['num'])) {
        $n = (int) $p['num'];
        $nx = $n > 9 ? 150 : 162;
        $tx = $n > 9 ? 254 : 250;
        $plaque = '<text class="aw-m-num" x="' . $nx . '" y="452" text-anchor="middle">' . $n . '</text>'
            . '<text class="aw-m-plaque" x="' . $tx . '" y="432" text-anchor="middle">' . e($lines[0] ?? '') . '</text>'
            . '<text class="aw-m-plaque" x="' . $tx . '" y="456" text-anchor="middle">' . e($lines[1] ?? '') . '</text>';
    } elseif (count($lines) > 1) {
        $plaque = '<text class="aw-m-plaque" x="200" y="430" text-anchor="middle">' . e($lines[0]) . '</text>'
            . '<text class="aw-m-plaque" x="200" y="456" text-anchor="middle">' . e($lines[1]) . '</text>';
    } else {
        $plaque = '<text class="aw-m-plaque aw-m-plaque-lg" x="200" y="455" text-anchor="middle">' . e($lines[0] ?? '') . '</text>';
    }
    $stops = '';
    foreach ([0, .3, .55, .8, 1] as $k => $off) {
        $stops .= '<stop offset="' . $off . '" stop-color="' . $t['rim'][$k] . '"/>';
    }
    $top = e($o['top'] ?? 'TOP CHOICE AWARD');
    $bottom = e($o['bottom'] ?? 'MARK OF EXCELLENCE');
    $small = e($o['small'] ?? '');
    $year = (int) ($o['year'] ?? date('Y'));
    $label = e($o['label'] ?? ($top . ' ' . $year));
    return <<<SVG
<svg class="aw-medal-svg" viewBox="0 0 400 490" role="img" aria-label="{$label}" style="--m-ink:{$t['ink']};--m-line:{$t['line']};--m-num:{$t['num']}">
  <defs>
    <linearGradient id="{$id}-rim" x1="0" y1="0" x2="1" y2="1">{$stops}</linearGradient>
    <radialGradient id="{$id}-face" cx=".38" cy=".32" r=".8">
      <stop offset="0" stop-color="{$t['face'][0]}"/><stop offset=".55" stop-color="{$t['face'][1]}"/><stop offset="1" stop-color="{$t['face'][2]}"/>
    </radialGradient>
    <radialGradient id="{$id}-disc" cx=".45" cy=".4" r=".7">
      <stop offset="0" stop-color="#fffaf0"/><stop offset="1" stop-color="#f1e6d2"/>
    </radialGradient>
    <linearGradient id="{$id}-plaque" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#3b2b22"/><stop offset="1" stop-color="#140f0c"/>
    </linearGradient>
    <linearGradient id="{$id}-gloss" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#fff" stop-opacity=".55"/><stop offset=".5" stop-color="#fff" stop-opacity="0"/>
    </linearGradient>
    <path id="{$id}-top" d="M 62 200 A 138 138 0 0 1 338 200"/>
    <path id="{$id}-bot" d="M 44 200 A 156 156 0 0 0 356 200"/>
  </defs>
  <rect x="172" y="372" width="56" height="30" fill="#1a120c"/>
  <rect x="64" y="392" width="272" height="86" rx="12" fill="url(#{$id}-plaque)" stroke="{$t['thin']}" stroke-width="2.5"/>
  <rect x="72" y="400" width="256" height="70" rx="8" fill="none" stroke="{$t['thin']}" stroke-width="1" opacity=".45"/>
  {$plaque}
  <circle cx="200" cy="200" r="192" fill="url(#{$id}-rim)"/>
  <circle cx="200" cy="200" r="186" fill="none" stroke="{$t['line']}" stroke-width="5" stroke-dasharray="2.5 4.5" opacity=".35"/>
  <circle cx="200" cy="200" r="176" fill="url(#{$id}-face)" stroke="{$t['line']}" stroke-width="2"/>
  <circle cx="200" cy="200" r="126" fill="none" stroke="{$t['line']}" stroke-width="3"/>
  <circle cx="200" cy="200" r="120" fill="url(#{$id}-disc)"/>
  <circle cx="200" cy="200" r="114" fill="none" stroke="{$t['thin']}" stroke-width="1.5" opacity=".7"/>
  <text class="aw-m-arc"><textPath href="#{$id}-top" startOffset="50%" text-anchor="middle">{$top}</textPath></text>
  <text class="aw-m-arc"><textPath href="#{$id}-bot" startOffset="50%" text-anchor="middle">{$bottom}</textPath></text>
  <path d="M 44 200 l 5 -7 l 5 7 l -5 7 z M 346 200 l 5 -7 l 5 7 l -5 7 z" fill="{$t['ink']}" opacity=".8"/>
  <text class="aw-m-small" x="200" y="124" text-anchor="middle">{$small}</text>
  <path d="M 142 206 L 184 248 L 266 150" fill="none" stroke="{$t['check']}" stroke-width="30" stroke-linecap="round" stroke-linejoin="round" opacity=".35"/>
  <path d="M 142 200 L 184 242 L 266 144" fill="none" stroke="{$t['ink']}" stroke-width="22" stroke-linecap="round" stroke-linejoin="round"/>
  <text class="aw-m-year" x="200" y="300" text-anchor="middle">{$year}</text>
  <ellipse cx="150" cy="118" rx="118" ry="64" fill="url(#{$id}-gloss)" transform="rotate(-28 150 118)" opacity=".7"/>
</svg>
SVG;
}

/** The Top Choice medal for one winning year ($n = how many years in a row by then). */
function top_choice_medal_svg($year, $n, $i = 0)
{
    return award_medal_svg([
        'theme'  => 'gold',
        'top'    => 'TOP CHOICE AWARD',
        'bottom' => 'MARK OF EXCELLENCE',
        'small'  => 'TOP CAKE BOUTIQUE',
        'year'   => $year,
        'plaque' => $n > 1 ? ['num' => $n, 'lines' => ['YEARS', 'ON TOP']] : ['lines' => ['FIRST YEAR ON TOP']],
        'label'  => 'Top Choice Award, Top Cake Boutique of ' . (int) $year . ', ' . ($n > 1 ? (int) $n . ' years on top' : 'first year on top'),
    ], $i);
}
