<?php
/**
 * Custom cake enquiry form (contact page) and the "Order Request" pop-up (form=request).
 * The pop-up sends with fetch and gets JSON back; without JavaScript both forms redirect.
 * Every enquiry is saved to private/messages/ first, so nothing is lost if email fails;
 * they are listed in the admin panel (Cake enquiries). Then it's emailed to the bakery.
 */
define('BASE_URL', rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') . '/');
require __DIR__ . '/../inc/site.php';

$isRequest = ($_POST['form'] ?? '') === 'request';
$wantsJson = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

function back($query)
{
    global $wantsJson;
    if ($wantsJson) {
        header('Content-Type: application/json; charset=UTF-8');
        $ok = str_contains($query, 'sent=1');
        if (!$ok) http_response_code(str_contains($query, 'missing') || str_contains($query, 'photos') ? 422 : 500);
        echo json_encode(['ok' => $ok, 'error' => $ok ? null : (str_contains($query, 'missing')
            ? 'Please add the order date and your phone number.'
            : (str_contains($query, 'photos')
                ? 'Please send up to 5 photos (JPG, PNG, WebP or HEIC), 10 MB in total.'
                : 'Sorry, it could not be sent. Please call us instead.'))]);
        exit;
    }
    header('Location: ' . url('contact-us/') . '?' . $query . '#enquiry', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back('');
}
// A post bigger than post_max_size arrives with $_POST and $_FILES empty.
if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    back('error=photos');
}

/**
 * "Upload pictures": up to PHOTO_MAX images, PHOTO_BYTES in total. Returns
 * [[tmp path, extension, original name], …], or null if any file is not allowed.
 */
const PHOTO_MAX = 5;
const PHOTO_BYTES = 10 * 1048576;
const PHOTO_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/heic' => 'heic', 'image/heif' => 'heif'];
function uploaded_photos(): ?array
{
    $u = $_FILES['photos'] ?? null;
    if (!$u || !is_array($u['name'] ?? null)) return [];
    $out = [];
    $total = 0;
    foreach ($u['name'] as $i => $name) {
        $err = $u['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) continue;
        $total += (int) $u['size'][$i];
        if ($err !== UPLOAD_ERR_OK || $total > PHOTO_BYTES || !is_uploaded_file($u['tmp_name'][$i])) return null;
        $type = function_exists('finfo_open') ? (string) finfo_file(finfo_open(FILEINFO_MIME_TYPE), $u['tmp_name'][$i]) : '';
        $ext = PHOTO_TYPES[$type] ?? null;
        // Some systems don't recognise HEIC; trust the name only for those.
        if (!$ext && preg_match('/\.(heic|heif)$/i', (string) $name, $m) && in_array($type, ['', 'application/octet-stream'], true)) $ext = strtolower($m[1]);
        if (!$ext) return null;
        $out[] = [$u['tmp_name'][$i], $ext, mb_substr(basename((string) $name), 0, 120)];
    }
    return count($out) <= PHOTO_MAX ? $out : null;
}

$f = [];
foreach (['name', 'phone', 'email', 'occasion', 'date', 'guests', 'message', 'website', 'page'] as $k) {
    $f[$k] = trim(mb_substr((string) ($_POST[$k] ?? ''), 0, $k === 'message' ? 4000 : 200));
}

// Bots fill in the hidden "website" field; pretend it worked.
if ($f['website'] !== '') {
    back('sent=1');
}
unset($f['website']);

if ($isRequest) {
    // Same rules as the staging site's pop-up: order date and phone are required.
    if ($f['date'] === '' || $f['phone'] === '') back('error=missing');
    $f['source'] = 'Order Request pop-up';
    if ($f['name'] === '') $f['name'] = 'Order request';
} elseif ($f['name'] === '' || $f['message'] === '' || ($f['phone'] === '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL))) {
    back('error=missing');
}
if ($f['page'] === '') unset($f['page']);
if ($f['guests'] !== '' && !ctype_digit($f['guests'])) $f['guests'] = preg_replace('/[^\d]/', '', $f['guests']);

$photos = uploaded_photos();
if ($photos === null) back('error=photos');

$f['received'] = date('c');
$f['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';

$dir = __DIR__ . '/../private/messages';
if (!is_dir($dir)) {
    @mkdir($dir, 0750, true);
}
$id = date('Ymd-His') . '-' . bin2hex(random_bytes(3));

// Photos go in private/messages/photos/<enquiry id>-<n>.<ext>; the admin panel shows them.
$attach = [];
if ($photos) {
    $pdir = $dir . '/photos';
    if (!is_dir($pdir)) @mkdir($pdir, 0750, true);
    $f['photos'] = [];
    foreach ($photos as $n => [$tmp, $ext, $orig]) {
        $file = $id . '-' . ($n + 1) . '.' . $ext;
        if (@move_uploaded_file($tmp, $pdir . '/' . $file)) {
            $f['photos'][] = ['file' => $file, 'name' => $orig];
            $attach[] = [$pdir . '/' . $file, $orig !== '' ? $orig : $file, array_search($ext, PHOTO_TYPES, true) ?: 'application/octet-stream'];
        }
    }
}

$saved = @file_put_contents($dir . '/' . $id . '.json', json_encode($f, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$lines = [];
foreach (['name' => 'Name', 'phone' => 'Phone', 'email' => 'Email', 'occasion' => 'Cake / occasion', 'date' => 'Date needed', 'guests' => 'Guests', 'message' => 'Message', 'page' => 'Sent from page'] as $k => $label) {
    if (($f[$k] ?? '') !== '') {
        $lines[] = $label . ': ' . $f[$k];
    }
}
// Attach the photos unless together they're too big for most mailboxes (then they're only in the admin panel).
$attachBytes = array_sum(array_map(fn($a) => (int) @filesize($a[0]), $attach));
if ($attach) {
    $lines[] = 'Photos: ' . count($attach) . ($attachBytes > 15 * 1048576 ? ' (too big to attach, open the enquiry in the admin panel)' : ' (attached)');
}
$headers = 'From: La Sem website <no-reply@' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'lasemcakes.ca') . '>';
if (filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
    $headers .= "\r\nReply-To: " . $f['email'];
}
$text = implode("\n", $lines);
if ($attach && $attachBytes <= 15 * 1048576) {
    $b = 'lasem-' . bin2hex(random_bytes(8));
    $headers .= "\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"$b\"";
    $body = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $text . "\r\n";
    foreach ($attach as [$path, $name, $mime]) {
        $safe = preg_replace('/[^\w.\- ]/u', '_', $name);
        $body .= "--$b\r\nContent-Type: $mime; name=\"$safe\"\r\nContent-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"$safe\"\r\n\r\n"
            . chunk_split(base64_encode((string) file_get_contents($path))) . "\r\n";
    }
    $body .= "--$b--";
} else {
    $headers .= "\r\nContent-Type: text/plain; charset=UTF-8";
    $body = $text;
}
$subject = ($isRequest ? 'Order request from ' : 'Cake enquiry from ') . ($f['name'] === 'Order request' ? $f['phone'] : $f['name']);
$mailed = @mail(notify_email(), str_replace(["\r", "\n"], ' ', $subject), $body, $headers);

back($saved || $mailed ? 'sent=1' : 'error=send');
