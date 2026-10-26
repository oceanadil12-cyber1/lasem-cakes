<?php
/**
 * Custom cake enquiry form (contact page).
 * Every enquiry is saved to private/messages/ first, so nothing is lost if email fails;
 * the admin panel will list them later. Then it's emailed to the bakery.
 */
define('BASE_URL', rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') . '/');
require __DIR__ . '/../inc/site.php';

function back($query)
{
    header('Location: ' . url('contact-us/') . '?' . $query . '#enquiry', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back('');
}

$f = [];
foreach (['name', 'phone', 'email', 'occasion', 'date', 'guests', 'message', 'website'] as $k) {
    $f[$k] = trim(mb_substr((string) ($_POST[$k] ?? ''), 0, $k === 'message' ? 4000 : 200));
}

// Bots fill in the hidden "website" field; pretend it worked.
if ($f['website'] !== '') {
    back('sent=1');
}
unset($f['website']);

if ($f['name'] === '' || $f['message'] === '' || ($f['phone'] === '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL))) {
    back('error=missing');
}

$f['received'] = date('c');
$f['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';

$dir = __DIR__ . '/../private/messages';
if (!is_dir($dir)) {
    @mkdir($dir, 0750, true);
}
$saved = @file_put_contents($dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json', json_encode($f, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$lines = [];
foreach (['name' => 'Name', 'phone' => 'Phone', 'email' => 'Email', 'occasion' => 'Cake / occasion', 'date' => 'Date needed', 'guests' => 'Guests', 'message' => 'Message'] as $k => $label) {
    if ($f[$k] !== '') {
        $lines[] = $label . ': ' . $f[$k];
    }
}
$headers = 'From: La Sem website <no-reply@' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'lasemcakes.ca') . ">\r\nContent-Type: text/plain; charset=UTF-8";
if (filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
    $headers .= "\r\nReply-To: " . $f['email'];
}
$mailed = @mail(SITE['email'], 'Cake enquiry from ' . str_replace(["\r", "\n"], ' ', $f['name']), implode("\n", $lines), $headers);

back($saved || $mailed ? 'sent=1' : 'error=send');
