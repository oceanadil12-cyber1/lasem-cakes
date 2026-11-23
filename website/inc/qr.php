<?php
/**
 * QR codes as SVG, made here so no outside library or service is needed (table QR codes,
 * "track your order" codes). Byte mode, error correction level M, versions 1–10
 * (up to 213 characters, plenty for a URL). Follows ISO/IEC 18004.
 *
 *   echo qr_svg('https://lasemcakes.ca/dine-in/?t=AB12CD', 'Scan to order');
 */

// version => [EC codewords per block, [[blocks, data codewords per block], ...]] for level M
const QR_M_BLOCKS = [
    1 => [10, [[1, 16]]],
    2 => [16, [[1, 28]]],
    3 => [26, [[1, 44]]],
    4 => [18, [[2, 32]]],
    5 => [24, [[2, 43]]],
    6 => [16, [[4, 27]]],
    7 => [18, [[4, 31]]],
    8 => [22, [[2, 38], [2, 39]]],
    9 => [22, [[3, 36], [2, 37]]],
    10 => [26, [[4, 43], [1, 44]]],
];
const QR_ALIGN = [1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30], 6 => [6, 34],
    7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50]];

/** GF(256) tables for Reed–Solomon (polynomial x^8 + x^4 + x^3 + x^2 + 1). */
function qr_gf(): array
{
    static $t = null;
    if ($t === null) {
        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $x;
            $log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) $x ^= 0x11D;
        }
        for ($i = 255; $i < 512; $i++) $exp[$i] = $exp[$i - 255];
        $t = [$exp, $log];
    }
    return $t;
}

function qr_gf_mul(int $a, int $b): int
{
    if ($a === 0 || $b === 0) return 0;
    [$exp, $log] = qr_gf();
    return $exp[$log[$a] + $log[$b]];
}

/** The $n error-correction codewords for $data. */
function qr_rs(array $data, int $n): array
{
    [$exp] = qr_gf();
    $gen = [1];
    for ($i = 0; $i < $n; $i++) {
        $next = array_fill(0, count($gen) + 1, 0);
        foreach ($gen as $j => $c) {
            $next[$j] ^= $c;
            $next[$j + 1] ^= qr_gf_mul($c, $exp[$i]);
        }
        $gen = $next;
    }
    $msg = array_merge($data, array_fill(0, $n, 0));
    for ($i = 0, $len = count($data); $i < $len; $i++) {
        $coef = $msg[$i];
        if ($coef === 0) continue;
        foreach ($gen as $j => $g) $msg[$i + $j] ^= qr_gf_mul($g, $coef);
    }
    return array_slice($msg, count($data));
}

/** The QR code's modules for $text: a square array of rows of booleans (true = dark). */
function qr_matrix(string $text): array
{
    $bytes = array_values(unpack('C*', $text) ?: []);
    $len = count($bytes);
    // Smallest version that fits.
    $version = 0;
    foreach (QR_M_BLOCKS as $v => [$ec, $groups]) {
        $capacity = 0;
        foreach ($groups as [$n, $k]) $capacity += $n * $k;
        $bits = 4 + ($v < 10 ? 8 : 16) + 8 * $len;
        if ($bits <= $capacity * 8) { $version = $v; break; }
    }
    if (!$version) throw new InvalidArgumentException('Text too long for a QR code here.');
    [$ecLen, $groups] = QR_M_BLOCKS[$version];
    $capacity = 0;
    foreach ($groups as [$n, $k]) $capacity += $n * $k;

    // Bit stream: byte mode, length, data, terminator, padding.
    $bits = [];
    $put = function (int $value, int $count) use (&$bits) {
        for ($i = $count - 1; $i >= 0; $i--) $bits[] = ($value >> $i) & 1;
    };
    $put(0b0100, 4);
    $put($len, $version < 10 ? 8 : 16);
    foreach ($bytes as $b) $put($b, 8);
    $put(0, min(4, $capacity * 8 - count($bits)));
    while (count($bits) % 8) $bits[] = 0;
    $data = [];
    foreach (array_chunk($bits, 8) as $chunk) $data[] = (int) bindec(implode('', $chunk));
    for ($pad = 0xEC; count($data) < $capacity; $pad ^= 0xEC ^ 0x11) $data[] = $pad;

    // Split into blocks, add error correction, interleave.
    $blocks = [];
    $pos = 0;
    foreach ($groups as [$n, $k]) {
        for ($i = 0; $i < $n; $i++) {
            $d = array_slice($data, $pos, $k);
            $pos += $k;
            $blocks[] = [$d, qr_rs($d, $ecLen)];
        }
    }
    $codewords = [];
    $maxData = max(array_map(fn($b) => count($b[0]), $blocks));
    for ($i = 0; $i < $maxData; $i++) foreach ($blocks as $b) if (isset($b[0][$i])) $codewords[] = $b[0][$i];
    for ($i = 0; $i < $ecLen; $i++) foreach ($blocks as $b) $codewords[] = $b[1][$i];

    // Function patterns.
    $size = $version * 4 + 17;
    $m = array_fill(0, $size, array_fill(0, $size, false));
    $fn = array_fill(0, $size, array_fill(0, $size, false));
    $set = function (int $x, int $y, bool $dark) use (&$m, &$fn) { $m[$y][$x] = $dark; $fn[$y][$x] = true; };
    for ($i = 0; $i < $size; $i++) { $set(6, $i, $i % 2 === 0); $set($i, 6, $i % 2 === 0); }
    foreach ([[3, 3], [$size - 4, 3], [3, $size - 4]] as [$cx, $cy]) {
        for ($dy = -4; $dy <= 4; $dy++) for ($dx = -4; $dx <= 4; $dx++) {
            $x = $cx + $dx; $y = $cy + $dy;
            $dist = max(abs($dx), abs($dy));
            if ($x >= 0 && $x < $size && $y >= 0 && $y < $size) $set($x, $y, $dist !== 2 && $dist !== 4);
        }
    }
    $align = QR_ALIGN[$version];
    $last = count($align) - 1;
    foreach ($align as $i => $ax) foreach ($align as $j => $ay) {
        if (($i === 0 && $j === 0) || ($i === 0 && $j === $last) || ($i === $last && $j === 0)) continue;
        for ($dy = -2; $dy <= 2; $dy++) for ($dx = -2; $dx <= 2; $dx++) $set($ax + $dx, $ay + $dy, max(abs($dx), abs($dy)) !== 1);
    }
    $drawFormat = function (int $mask) use ($set, $size) {
        $data = (0b00 << 3) | $mask; // level M = 00
        $rem = $data;
        for ($i = 0; $i < 10; $i++) $rem = ($rem << 1) ^ (($rem >> 9) * 0x537);
        $bits = (($data << 10) | $rem) ^ 0x5412;
        $bit = fn($i) => (($bits >> $i) & 1) === 1;
        for ($i = 0; $i <= 5; $i++) $set(8, $i, $bit($i));
        $set(8, 7, $bit(6));
        $set(8, 8, $bit(7));
        $set(7, 8, $bit(8));
        for ($i = 9; $i < 15; $i++) $set(14 - $i, 8, $bit($i));
        for ($i = 0; $i < 8; $i++) $set($size - 1 - $i, 8, $bit($i));
        for ($i = 8; $i < 15; $i++) $set(8, $size - 15 + $i, $bit($i));
        $set(8, $size - 8, true); // the dark module
    };
    $drawFormat(0); // reserve the format areas
    if ($version >= 7) {
        $rem = $version;
        for ($i = 0; $i < 12; $i++) $rem = ($rem << 1) ^ (($rem >> 11) * 0x1F25);
        $vbits = ($version << 12) | $rem;
        for ($i = 0; $i < 18; $i++) {
            $dark = (($vbits >> $i) & 1) === 1;
            $a = $size - 11 + $i % 3;
            $b = intdiv($i, 3);
            $set($a, $b, $dark);
            $set($b, $a, $dark);
        }
    }

    // Data, in the zigzag order.
    $i = 0;
    $total = count($codewords) * 8;
    for ($right = $size - 1; $right >= 1; $right -= 2) {
        if ($right === 6) $right = 5;
        for ($vert = 0; $vert < $size; $vert++) {
            for ($j = 0; $j < 2; $j++) {
                $x = $right - $j;
                $upward = (($right + 1) & 2) === 0;
                $y = $upward ? $size - 1 - $vert : $vert;
                if (!$fn[$y][$x] && $i < $total) {
                    $m[$y][$x] = (($codewords[$i >> 3] >> (7 - ($i & 7))) & 1) === 1;
                    $i++;
                }
            }
        }
    }

    // Try the 8 masks and keep the one that reads best.
    $masked = function (array $m, int $mask) use ($fn, $size): array {
        for ($y = 0; $y < $size; $y++) for ($x = 0; $x < $size; $x++) {
            if ($fn[$y][$x]) continue;
            switch ($mask) {
                case 0: $inv = ($x + $y) % 2 === 0; break;
                case 1: $inv = $y % 2 === 0; break;
                case 2: $inv = $x % 3 === 0; break;
                case 3: $inv = ($x + $y) % 3 === 0; break;
                case 4: $inv = (intdiv($x, 3) + intdiv($y, 2)) % 2 === 0; break;
                case 5: $inv = $x * $y % 2 + $x * $y % 3 === 0; break;
                case 6: $inv = ($x * $y % 2 + $x * $y % 3) % 2 === 0; break;
                default: $inv = (($x + $y) % 2 + $x * $y % 3) % 2 === 0;
            }
            if ($inv) $m[$y][$x] = !$m[$y][$x];
        }
        return $m;
    };
    $best = null;
    $bestScore = PHP_INT_MAX;
    $base = $m;
    for ($mask = 0; $mask < 8; $mask++) {
        $m = $masked($base, $mask);
        $drawFormat($mask);
        $score = qr_penalty($m, $size);
        if ($score < $bestScore) { $bestScore = $score; $best = $mask; }
    }
    $m = $masked($base, $best);
    $drawFormat($best);
    return $m;
}

/** How hard a masked code is to read (lower is better): long runs, 2×2 blocks, finder look-alikes, dark/light balance. */
function qr_penalty(array $m, int $size): int
{
    $score = 0;
    $lines = [];
    for ($i = 0; $i < $size; $i++) {
        $lines[] = $m[$i];
        $lines[] = array_column($m, $i);
    }
    foreach ($lines as $line) {
        $run = 1;
        for ($i = 1; $i <= $size; $i++) {
            if ($i < $size && $line[$i] === $line[$i - 1]) { $run++; continue; }
            if ($run >= 5) $score += 3 + $run - 5;
            $run = 1;
        }
        $s = implode('', array_map(fn($b) => $b ? '1' : '0', $line));
        $score += 40 * (substr_count($s, '10111010000') + substr_count($s, '00001011101'));
    }
    $dark = 0;
    for ($y = 0; $y < $size; $y++) for ($x = 0; $x < $size; $x++) {
        if ($m[$y][$x]) $dark++;
        if ($x < $size - 1 && $y < $size - 1 && $m[$y][$x] === $m[$y][$x + 1] && $m[$y][$x] === $m[$y + 1][$x] && $m[$y][$x] === $m[$y + 1][$x + 1]) $score += 3;
    }
    $score += 10 * intdiv(abs($dark * 100 - 50 * $size * $size), 5 * $size * $size);
    return $score;
}

/** An <svg> of the QR code (with the 4-module quiet zone), scaling to its container. */
function qr_svg(string $text, string $label = ''): string
{
    $m = qr_matrix($text);
    $size = count($m);
    $q = 4;
    $path = '';
    foreach ($m as $y => $row) {
        $x = 0;
        while ($x < $size) {
            if (!$row[$x]) { $x++; continue; }
            $start = $x;
            while ($x < $size && $row[$x]) $x++;
            $path .= 'M' . ($start + $q) . ' ' . ($y + $q) . 'h' . ($x - $start) . 'v1h-' . ($x - $start) . 'z';
        }
    }
    $full = $size + 2 * $q;
    return '<svg class="qr" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $full . ' ' . $full . '" shape-rendering="crispEdges" role="img" aria-label="'
        . htmlspecialchars($label !== '' ? $label : 'QR code', ENT_QUOTES, 'UTF-8') . '">'
        . '<rect width="' . $full . '" height="' . $full . '" fill="#fff"/><path d="' . $path . '" fill="#000"/></svg>';
}
