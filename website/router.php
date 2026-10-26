<?php
// For PHP's built-in server only (local preview): php -S localhost:8081 -t website website/router.php
// Real files are served as they are; every other URL goes to index.php, like .htaccess does on the host.
$file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($_SERVER['REQUEST_URI'] !== '/' && is_file($file)) {
    if (preg_match('#/(inc|data|private)/#', $file)) {
        http_response_code(403);
        return true;
    }
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
