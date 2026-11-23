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
        if (!$ok) http_response_code(str_contains($query, 'missing') ? 422 : 500);
        echo json_encode(['ok' => $ok, 'error' => $ok ? null : (str_contains($query, 'missing')
            ? 'Please add the order date and your phone number.'
            : 'Sorry, it could not be sent. Please call us instead.')]);
        exit;
    }
    header('Location: ' . url('contact-us/') . '?' . $query . '#enquiry', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    back('');
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

$f['received'] = date('c');
$f['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';

$dir = __DIR__ . '/../private/messages';
if (!is_dir($dir)) {
    @mkdir($dir, 0750, true);
}
$saved = @file_put_contents($dir . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.json', json_encode($f, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$lines = [];
foreach (['name' => 'Name', 'phone' => 'Phone', 'email' => 'Email', 'occasion' => 'Cake / occasion', 'date' => 'Date needed', 'guests' => 'Guests', 'message' => 'Message', 'page' => 'Sent from page'] as $k => $label) {
    if (($f[$k] ?? '') !== '') {
        $lines[] = $label . ': ' . $f[$k];
    }
}
$headers = 'From: La Sem website <no-reply@' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'lasemcakes.ca') . ">\r\nContent-Type: text/plain; charset=UTF-8";
if (filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
    $headers .= "\r\nReply-To: " . $f['email'];
}
$subject = ($isRequest ? 'Order request from ' : 'Cake enquiry from ') . ($f['name'] === 'Order request' ? $f['phone'] : $f['name']);
$mailed = @mail(notify_email(), str_replace(["\r", "\n"], ' ', $subject), implode("\n", $lines), $headers);

back($saved || $mailed ? 'sent=1' : 'error=send');
