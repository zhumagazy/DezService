<?php
// Not used on the server — .htaccess does the same there.
// Dev router emulating .htaccess for `php -S`
$p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(storage|app)(/|$)#', $p)) { http_response_code(403); exit('403'); }
if ($p !== '/' && is_file($_SERVER['DOCUMENT_ROOT'] . $p)) return false;
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
