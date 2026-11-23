<?php
/**
 * Where the admin panel reads and writes. No database: everything is JSON files.
 *
 *   private/orders/LS-*.json      pickup orders (written by api/order.php; status, payment and notes added here)
 *   private/messages/*.json       cake enquiries (written by api/contact.php; status and notes added here)
 *   private/admin.json            the sign-in (user name + password hash)
 *   private/activity.jsonl        who did what in the admin
 *   private/backups/              the previous copy of every data file the admin saves
 *   data/menu.json                menu, prices and online-ordering settings
 *   data/pages.json, testimonials.json, settings.json   website content and business details
 *
 * private/ is never served (router.php, .htaccess) and never committed (.gitignore).
 */
if (!defined('ADMIN') && !defined('KDS')) { http_response_code(403); exit('Forbidden'); }

// status key => [label, tone]. The first key is what a record has until someone changes it.
const ORDER_STATUSES = [
    'new'       => ['New',       'info'],
    'confirmed' => ['Confirmed', 'gold'],
    'preparing' => ['Preparing', 'warn'],
    'ready'     => ['Ready',     'good'],
    'picked_up' => ['Picked up', 'muted'],
    'cancelled' => ['Cancelled', 'crit'],
];
const ORDER_OPEN = ['new', 'confirmed', 'preparing', 'ready'];

const ENQUIRY_STATUSES = [
    'new'      => ['New',      'info'],
    'read'     => ['Read',     'muted'],
    'replied'  => ['Replied',  'gold'],
    'booked'   => ['Booked',   'good'],
    'archived' => ['Archived', 'muted'],
];

// ---------- files ----------

function private_dir(string $sub = ''): string
{
    $dir = __DIR__ . '/../../private' . ($sub !== '' ? '/' . $sub : '');
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    return $dir;
}

function json_read(string $file, $default = [])
{
    if (!is_file($file)) return $default;
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : $default;
}

/** Write JSON safely: to a temporary file first, then swapped in, so a reader never sees half a file. */
function json_write(string $file, $data): bool
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $json . "\n", LOCK_EX) === false) return false;
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

// The website's data files the admin may change.
const DATA_FILES = ['menu', 'pages', 'testimonials', 'settings'];

function data_path(string $name): string
{
    if (!in_array($name, DATA_FILES, true)) throw new InvalidArgumentException('Unknown data file ' . $name);
    return __DIR__ . '/../../data/' . $name . '.json';
}

function data_read(string $name): array
{
    return json_read(data_path($name), []);
}

/**
 * A data file's JSON in the same layout the project's files already use (one-space
 * indent; testimonials one per line), so a change in the admin is a small change in git.
 */
function data_encode(string $name, array $data): string
{
    $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
    if ($name === 'testimonials') {
        $row = fn($t) => ' {' . implode(', ', array_map(fn($k) => json_encode((string) $k, $flags) . ': ' . json_encode($t[$k], $flags), array_keys($t))) . '}';
        return "[\n" . implode(",\n", array_map($row, $data)) . "\n]\n";
    }
    $json = (string) json_encode($data, $flags | JSON_PRETTY_PRINT);
    return preg_replace_callback('/^(?: {4})+/m', fn($m) => str_repeat(' ', strlen($m[0]) / 4), $json);
}

/** Save a data file, keeping the previous version in private/backups (the newest 30 per file). */
function data_save(string $name, array $data): bool
{
    $path = data_path($name);
    if (is_file($path)) {
        $dir = private_dir('backups');
        @copy($path, $dir . '/' . $name . '-' . date('Ymd-His') . '.json');
        $old = glob($dir . '/' . $name . '-*.json') ?: [];
        sort($old);
        foreach (array_slice($old, 0, max(0, count($old) - 30)) as $f) @unlink($f);
    }
    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, data_encode($name, $data), LOCK_EX) === false) return false;
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/** Change one JSON record under a lock (two people clicking at once can't undo each other). $fn gets the record by reference. */
function json_update(string $file, callable $fn): bool
{
    $h = @fopen($file, 'c+');
    if (!$h || !flock($h, LOCK_EX)) return false;
    $data = json_decode((string) stream_get_contents($h), true);
    if (!is_array($data)) {
        flock($h, LOCK_UN);
        fclose($h);
        return false;
    }
    $fn($data);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    ftruncate($h, 0);
    rewind($h);
    fwrite($h, $json);
    fflush($h);
    flock($h, LOCK_UN);
    fclose($h);
    return true;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

// ---------- orders ----------

/** An order file as the admin uses it (times as timestamps, team fields filled in). */
function order_from_file(array $r): array
{
    $c = (array) ($r['customer'] ?? []);
    $pickup = (int) strtotime((string) ($r['pickup'] ?? ''));
    return [
        'number'   => (string) ($r['number'] ?? ''),
        'type'     => ($r['type'] ?? '') === 'dine-in' ? 'dine-in' : 'pickup',
        'queue'    => (int) ($r['queue'] ?? 0),           // table orders: the day's priority number
        'table'    => (string) ($r['table']['name'] ?? ''),
        'device'   => (string) ($r['device'] ?? ''),
        'status'   => isset(ORDER_STATUSES[$r['status'] ?? '']) ? $r['status'] : 'new',
        'ts'       => (int) strtotime((string) ($r['placed'] ?? '')),
        'pickup'   => $pickup,
        'day'      => $pickup ? date('Y-m-d', $pickup) : '',
        'fulfilment' => ($r['fulfilment'] ?? '') === 'delivery' ? 'delivery' : 'pickup', // website orders: collected, or delivered
        'delivery' => is_array($r['delivery'] ?? null) ? $r['delivery'] : [],  // street, unit, city, postal, instructions
        'fee'      => (int) ($r['fee'] ?? 0),
        'rider'    => (string) ($r['rider'] ?? ''),
        'out_at'   => (int) strtotime((string) ($r['out_at'] ?? '')), // when a delivery left the bakery
        'name'     => (string) ($c['name'] ?? ''),
        'phone'    => (string) ($c['phone'] ?? ''),
        'email'    => (string) ($c['email'] ?? ''),
        'notes'    => (string) ($r['notes'] ?? ''),
        'items'    => array_values(array_filter((array) ($r['items'] ?? []), 'is_array')),
        'subtotal' => (int) ($r['subtotal'] ?? 0),
        'tax'      => (int) ($r['tax'] ?? 0),
        'total'    => (int) ($r['total'] ?? 0),
        'paid'     => !empty($r['paid']),
        'paid_at'  => (string) ($r['paid_at'] ?? ''),
        'note'     => (string) ($r['team_note'] ?? ''),
        'history'  => array_values(array_filter((array) ($r['history'] ?? []), 'is_array')),
        'started'  => (int) strtotime((string) ($r['started_at'] ?? '')), // when the kitchen started it (KDS or admin)
        'ready_at' => (int) strtotime((string) ($r['ready_at'] ?? '')),
    ];
}

/** Every order, newest placed first. */
function orders_all(bool $reload = false): array
{
    static $all = null;
    if ($all !== null && !$reload) return $all;
    $all = [];
    foreach (glob(private_dir('orders') . '/LS-*.json') ?: [] as $f) {
        $r = json_read($f, null);
        if (is_array($r) && !empty($r['number'])) $all[] = order_from_file($r);
    }
    usort($all, fn($a, $b) => $b['ts'] <=> $a['ts'] ?: strcmp($b['number'], $a['number']));
    return $all;
}

function order_file(string $number): ?string
{
    if (!preg_match('/^LS-\d{6}-\d{3,5}$/', $number)) return null;
    $f = private_dir('orders') . '/' . $number . '.json';
    return is_file($f) ? $f : null;
}

function order_find(string $number): ?array
{
    $f = order_file($number);
    $r = $f ? json_read($f, null) : null;
    return is_array($r) ? order_from_file($r) : null;
}

/** Set an order's status (kept in its history with the time). */
function order_set_status(string $number, string $status): bool
{
    $f = order_file($number);
    if (!$f || !isset(ORDER_STATUSES[$status])) return false;
    return json_update($f, function (&$r) use ($status) {
        if (($r['status'] ?? 'new') === $status) return;
        $r['status'] = $status;
        $r['history'][] = ['s' => $status, 't' => date('c')];
        // Kitchen times for the KDS: started stays from the first start; ready is the latest.
        if ($status === 'preparing' && empty($r['started_at'])) $r['started_at'] = date('c');
        if ($status === 'ready') $r['ready_at'] = date('c');
        if (in_array($status, ['new', 'confirmed'], true)) unset($r['started_at'], $r['ready_at']);
        if ($status === 'preparing') unset($r['ready_at']);
        $r['history'] = array_slice($r['history'], -30);
    });
}

/** Team fields on an order: 'team_note', 'paid', 'rider', 'out_at' (deliveries). */
function order_set(string $number, array $fields): bool
{
    $f = order_file($number);
    if (!$f) return false;
    return json_update($f, function (&$r) use ($fields) {
        if (array_key_exists('team_note', $fields)) $r['team_note'] = (string) $fields['team_note'];
        if (array_key_exists('rider', $fields)) $r['rider'] = (string) $fields['rider'];
        if (array_key_exists('out_at', $fields)) $r['out_at'] = (string) $fields['out_at'];
        if (array_key_exists('paid', $fields)) {
            $r['paid'] = (bool) $fields['paid'];
            $r['paid_at'] = $fields['paid'] ? date('c') : '';
        }
    });
}

/** An order's status name: table orders are "Served" (or "Collected"), not "Picked up". */
function order_status_label(array $o): string
{
    if ($o['type'] === 'dine-in' && $o['status'] === 'picked_up') return dinein_settings()['serve'] === 'counter' ? 'Collected' : 'Served';
    if (($o['fulfilment'] ?? '') === 'delivery' && $o['status'] === 'picked_up') return 'Delivered';
    if (($o['fulfilment'] ?? '') === 'delivery' && $o['status'] === 'ready' && !empty($o['out_at'])) return 'Out for delivery';
    return ORDER_STATUSES[$o['status']][0] ?? $o['status'];
}

function order_status_badge(array $o): string
{
    return badge(order_status_label($o), ORDER_STATUSES[$o['status']][1] ?? 'muted');
}

/** One order line as text: "Gelato · Large · Pistachio". */
function order_line_label(array $i): string
{
    return trim((string) ($i['name'] ?? '') . (!empty($i['detail']) ? ' · ' . $i['detail'] : ''));
}

// ---------- enquiries ----------

function enquiry_from_file(array $r, string $id): array
{
    return [
        'id'       => $id,
        'status'   => isset(ENQUIRY_STATUSES[$r['status'] ?? '']) ? $r['status'] : 'new',
        'ts'       => (int) strtotime((string) ($r['received'] ?? '')),
        'name'     => (string) ($r['name'] ?? ''),
        'phone'    => (string) ($r['phone'] ?? ''),
        'email'    => (string) ($r['email'] ?? ''),
        'occasion' => (string) ($r['occasion'] ?? ''),
        'date'     => (string) ($r['date'] ?? ''),
        'guests'   => (string) ($r['guests'] ?? ''),
        'message'  => (string) ($r['message'] ?? ''),
        'note'     => (string) ($r['team_note'] ?? ''),
        'history'  => array_values(array_filter((array) ($r['history'] ?? []), 'is_array')),
    ];
}

function enquiries_all(bool $reload = false): array
{
    static $all = null;
    if ($all !== null && !$reload) return $all;
    $all = [];
    foreach (glob(private_dir('messages') . '/*.json') ?: [] as $f) {
        $r = json_read($f, null);
        if (is_array($r)) $all[] = enquiry_from_file($r, basename($f, '.json'));
    }
    usort($all, fn($a, $b) => $b['ts'] <=> $a['ts'] ?: strcmp($b['id'], $a['id']));
    return $all;
}

function enquiry_file(string $id): ?string
{
    if (!preg_match('/^\d{8}-\d{6}-[0-9a-f]{6}$/', $id)) return null;
    $f = private_dir('messages') . '/' . $id . '.json';
    return is_file($f) ? $f : null;
}

function enquiry_find(string $id): ?array
{
    $f = enquiry_file($id);
    $r = $f ? json_read($f, null) : null;
    return is_array($r) ? enquiry_from_file($r, $id) : null;
}

/** 'status' and/or 'team_note'. */
function enquiry_set(string $id, array $fields): bool
{
    $f = enquiry_file($id);
    if (!$f) return false;
    return json_update($f, function (&$r) use ($fields) {
        if (isset($fields['status'], ENQUIRY_STATUSES[$fields['status']]) && ($r['status'] ?? 'new') !== $fields['status']) {
            $r['status'] = $fields['status'];
            $r['history'][] = ['s' => $fields['status'], 't' => date('c')];
            $r['history'] = array_slice($r['history'], -30);
        }
        if (array_key_exists('team_note', $fields)) $r['team_note'] = (string) $fields['team_note'];
    });
}

function enquiry_delete(string $id): bool
{
    $f = enquiry_file($id);
    return $f ? @unlink($f) : false;
}

// ---------- photos ----------

/**
 * Save an uploaded photo from $_FILES[$field] into $dir as "$base.<ext>".
 * With PHP's GD library it is resized to at most $maxW wide and saved as WebP (plus an
 * 800 px copy when $small); without GD the photo is kept as it was uploaded.
 * Returns ['file', 'w', 'h', 'sm'], or ['error' => message] ('' = no file was chosen).
 */
function admin_save_upload(string $field, string $dir, string $base, int $maxW, bool $small = false): array
{
    $f = $_FILES[$field] ?? null;
    if (!is_array($f) || !isset($f['error']) || is_array($f['error']) || $f['error'] === UPLOAD_ERR_NO_FILE) return ['error' => ''];
    if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $f['size'] > 12 * 1048576) {
        return ['error' => 'That photo is too large. Please use one under ' . min(12, (int) ini_get('upload_max_filesize') ?: 12) . ' MB.'];
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) return ['error' => 'The photo didn\'t upload. Please try again.'];
    $info = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) return ['error' => 'Please choose a JPG, PNG or WebP photo.'];
    [$w, $h] = $info;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    if (function_exists('imagewebp') && function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring((string) file_get_contents($f['tmp_name']));
        if ($src) {
            $save = function (int $maxWidth, string $path) use ($src, $w, $h): array {
                $nw = min($w, $maxWidth);
                $nh = (int) round($h * $nw / $w);
                $dst = imagecreatetruecolor($nw, $nh);
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagewebp($dst, $path, 82);
                imagedestroy($dst);
                return [$nw, $nh];
            };
            [$nw, $nh] = $save($maxW, $dir . '/' . $base . '.webp');
            $sm = $small && $nw > 800;
            if ($sm) $save(800, $dir . '/' . $base . '-800.webp');
            imagedestroy($src);
            return ['file' => $base . '.webp', 'w' => $nw, 'h' => $nh, 'sm' => $sm];
        }
    }
    $name = $base . '.' . $types[$info[2]];
    if (!@move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) return ['error' => 'The photo couldn\'t be saved. Check that the images folder can be written to.'];
    return ['file' => $name, 'w' => $w, 'h' => $h, 'sm' => false];
}

/** A safe file name from any text: "Wedding Cake (2).JPG" → "wedding-cake-2". */
function file_slug(string $s, int $max = 50): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s), '-'));
    return substr($s !== '' ? $s : 'photo', 0, $max);
}

// ---------- what needs attention ----------

/** Numbers for the sidebar badges: orders nobody has confirmed yet, enquiries nobody has opened. */
function admin_counts(): array
{
    if (!ONLINE_ORDERING) { // ordering is switched off: only enquiries wait for the team
        return ['enquiries' => count(array_filter(enquiries_all(), fn($e) => $e['status'] === 'new'))];
    }
    return [
        'orders'    => count(array_filter(orders_all(), fn($o) => $o['status'] === 'new')),
        'deliveries' => count(array_filter(orders_all(), fn($o) => ($o['fulfilment'] ?? '') === 'delivery' && $o['day'] === date('Y-m-d') && in_array($o['status'], ORDER_OPEN, true))),
        'enquiries' => count(array_filter(enquiries_all(), fn($e) => $e['status'] === 'new')),
    ];
}

/** Alerts for the bell in the top bar (asked for every 30 seconds by admin.js). */
function admin_notifications(): array
{
    $now = time();
    $alerts = [];
    foreach (ONLINE_ORDERING ? orders_all() : [] as $o) {
        if (!in_array($o['status'], ORDER_OPEN, true) || abs($o['pickup'] - $now) > 86400) continue;
        $href = '?page=orders&id=' . $o['number'];
        if ($o['type'] === 'dine-in') {
            $late = dinein_settings()['lateMins'];
            if ($o['status'] !== 'ready' && $now - $o['ts'] >= $late * 60) {
                $alerts[] = ['type' => 'table-late', 'tone' => 'warn', 'href' => $href, 'ts' => $o['ts'],
                    'message' => $o['table'] . ': order ' . $o['queue'] . ' waiting ' . intdiv($now - $o['ts'], 60) . ' min'];
            }
            continue;
        }
        $at = date('g:i a', $o['pickup']);
        if ($o['status'] === 'new' && $now - $o['ts'] >= 600) {
            $alerts[] = ['type' => 'order-new', 'tone' => 'crit', 'href' => $href, 'ts' => $o['ts'],
                'message' => $o['number'] . ' (' . $o['name'] . ') not confirmed yet, pickup ' . $at];
        } elseif ($o['status'] !== 'ready' && $o['pickup'] > $now && $o['pickup'] - $now <= 3600) {
            $alerts[] = ['type' => 'pickup-soon', 'tone' => 'warn', 'href' => $href, 'ts' => $o['pickup'],
                'message' => $o['number'] . ' (' . $o['name'] . ') picks up at ' . $at . ', not ready yet'];
        } elseif ($o['status'] === 'ready' && $now - $o['pickup'] >= 3600) {
            $alerts[] = ['type' => 'pickup-late', 'tone' => 'warn', 'href' => $href, 'ts' => $o['pickup'],
                'message' => $o['number'] . ' (' . $o['name'] . ') was due at ' . $at . ' and hasn\'t been picked up'];
        }
    }
    foreach (dinein_calls() as $c) {
        $alerts[] = ['type' => 'table-call', 'tone' => 'crit', 'href' => SITE_BASE . 'kds/', 'ts' => intdiv($c['at'], 1000),
            'message' => $c['table'] . ' is calling for staff' . ($c['name'] !== '' ? ' (' . $c['name'] . ')' : '')];
    }
    usort($alerts, fn($a, $b) => $a['ts'] <=> $b['ts']);
    return array_slice($alerts, 0, 20);
}

// ---------- kitchen display (KDS) ----------

/**
 * KDS settings (private/kds.json): the kitchen passcode (hash only), how many minutes before
 * pickup an order should be started, and whether new orders chime.
 */
function kds_settings(): array
{
    $s = json_read(private_dir() . '/kds.json', []);
    return [
        'prep_minutes' => max(5, min(1440, (int) ($s['prep_minutes'] ?? 30))),
        'sound'        => ($s['sound'] ?? true) !== false,
        'pin_hash'     => (string) ($s['pin_hash'] ?? ''),
        'pin_changed'  => (string) ($s['pin_changed'] ?? ''),
    ];
}

function kds_save_settings(array $changes): bool
{
    return json_write(private_dir() . '/kds.json', array_merge(kds_settings(), $changes));
}

/** What a signed-in KDS session is tied to: changing the passcode or the admin password signs tablets out. */
function kds_version(): string
{
    $a = json_read(private_dir() . '/admin.json', []);
    return substr(hash('sha256', kds_settings()['pin_hash'] . '|' . ($a['hash'] ?? '')), 0, 16);
}

// ---------- guess limits ----------

function rate_file(string $key): string
{
    return private_dir('limits') . '/' . hash('sha256', $key . '|' . client_ip()) . '.json';
}

/** Has this visitor used up $max tries of $key in the last $window seconds? */
function rate_blocked(string $key, int $max, int $window): bool
{
    $r = json_read(rate_file($key), []);
    return ($r['start'] ?? 0) > time() - $window && ($r['n'] ?? 0) >= $max;
}

function rate_hit(string $key, int $window): void
{
    $f = rate_file($key);
    $r = json_read($f, []);
    if (($r['start'] ?? 0) <= time() - $window) $r = ['start' => time(), 'n' => 0];
    $r['n']++;
    json_write($f, $r);
}

function rate_clear(string $key): void
{
    @unlink(rate_file($key));
}

// ---------- activity log ----------

// action code => [label, tone] for the Activity page and the dashboard.
const ADMIN_ACTIONS = [
    'setup'            => ['Admin sign-in created', 'gold'],
    'login'            => ['Signed in', 'muted'],
    'login.failed'     => ['Wrong password', 'crit'],
    'logout'           => ['Signed out', 'muted'],
    'password'         => ['Password changed', 'gold'],
    'order.status'     => ['Order status changed', 'gold'],
    'order.paid'       => ['Order payment updated', 'good'],
    'order.note'       => ['Order note saved', 'muted'],
    'enquiry.status'   => ['Enquiry updated', 'gold'],
    'enquiry.note'     => ['Enquiry note saved', 'muted'],
    'enquiry.delete'   => ['Enquiry deleted', 'crit'],
    'menu.product'     => ['Menu item saved', 'gold'],
    'menu.add'         => ['Menu item added', 'gold'],
    'menu.delete'      => ['Menu item deleted', 'crit'],
    'menu.quick'       => ['Menu item switched', 'gold'],
    'menu.category'    => ['Menu category saved', 'gold'],
    'menu.photo'       => ['Menu photo changed', 'gold'],
    'page.save'        => ['Page content saved', 'gold'],
    'page.photo'       => ['Page photo added', 'gold'],
    'testimonials'     => ['Testimonials saved', 'gold'],
    'settings.store'   => ['Business details saved', 'gold'],
    'settings.order'   => ['Online ordering settings saved', 'gold'],
    'settings.kds'     => ['Kitchen display settings saved', 'gold'],
    'settings.google'  => ['Google reviews settings saved', 'gold'],
    'kds.login'        => ['Kitchen display signed in', 'muted'],
    'kds.login.failed' => ['Kitchen display wrong passcode', 'crit'],
    'kds.status'       => ['Kitchen display: order status', 'gold'],
    'kds.call'         => ['Kitchen display: staff call answered', 'muted'],
    'tables.settings'  => ['Table ordering settings saved', 'gold'],
    'tables.add'       => ['Tables added', 'gold'],
    'tables.save'      => ['Tables saved', 'gold'],
    'tables.code'      => ['Table QR code replaced', 'warn'],
    'tables.delete'    => ['Table removed', 'crit'],
];

function admin_action_label(string $code): array
{
    return ADMIN_ACTIONS[$code] ?? [$code, 'muted'];
}

/** Note what someone did in the admin: $action is a short code (see ADMIN_ACTIONS), $detail plain text. */
function admin_log(string $action, string $detail = ''): void
{
    $file = private_dir() . '/activity.jsonl';
    $line = json_encode(['t' => date('c'), 'a' => $action, 'd' => mb_substr($detail, 0, 300), 'ip' => client_ip()], JSON_UNESCAPED_UNICODE) . "\n";
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    // Keep the log to a sensible size: past ~1 MB, keep the newest 3,000 entries.
    if (@filesize($file) > 1048576) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        @file_put_contents($file, implode("\n", array_slice($lines, -3000)) . "\n", LOCK_EX);
    }
}

/** The newest $limit activity entries, newest first: [time, action, detail, ip]. */
function admin_activity(int $limit = 200): array
{
    $file = private_dir() . '/activity.jsonl';
    $lines = is_file($file) ? (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : [];
    $out = [];
    foreach (array_reverse(array_slice($lines, -$limit)) as $l) {
        $r = json_decode($l, true);
        if (is_array($r)) $out[] = ['time' => (string) ($r['t'] ?? ''), 'action' => (string) ($r['a'] ?? ''), 'detail' => (string) ($r['d'] ?? ''), 'ip' => (string) ($r['ip'] ?? '')];
    }
    return $out;
}
