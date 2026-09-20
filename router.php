<?php

$publicDir = __DIR__ . '/public';
$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = urldecode((string)parse_url($rawUri, PHP_URL_PATH));
$filePath = $publicDir . $uri;

if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'  => 'text/css; charset=utf-8',
        'js'   => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
    ];

    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
    } else {
        header('Content-Type: ' . (mime_content_type($filePath) ?: 'application/octet-stream'));
    }

    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require $publicDir . '/index.php';
