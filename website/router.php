<?php
// For PHP's built-in server only (local preview): php -S localhost:8081 -t website website/router.php
// Real files are served as they are; every other URL goes to index.php, like .htaccess does on the host.
$path = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if (preg_match('#/(inc|data|private)(/|$)#', $path)) {
    http_response_code(403);
    return true;
}
// The admin panel, the kitchen display and table ordering have their own front controllers.
if (preg_match('#^/(admin|kds|dine-in)(/|$)#', $path, $m) && !is_file($file)) {
    $_SERVER['SCRIPT_NAME'] = '/' . $m[1] . '/index.php';
    require __DIR__ . '/' . $m[1] . '/index.php';
    return true;
}
if ($path !== '/' && is_file($file)) {
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
