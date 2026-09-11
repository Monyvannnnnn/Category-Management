<?php
// api/index.php - Vercel Serverless Entrypoint

// Change working directory to project root
chdir(__DIR__ . '/..');

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$targetFile = __DIR__ . '/..' . $uri;

// 1. Handle root or /index.php
if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

// 2. Target exists as a PHP script
if (file_exists($targetFile) && is_file($targetFile) && substr($targetFile, -4) === '.php') {
    require $targetFile;
    exit;
}

// 3. Target exists as a static asset (CSS, JS, Fonts, Images)
if (file_exists($targetFile) && is_file($targetFile)) {
    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'   => 'text/css; charset=utf-8',
        'js'    => 'application/javascript; charset=utf-8',
        'json'  => 'application/json; charset=utf-8',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf'
    ];
    if (isset($mimeTypes[$ext])) {
        header("Content-Type: " . $mimeTypes[$ext]);
    }
    readfile($targetFile);
    exit;
}

// 4. Default fallback
require __DIR__ . '/../index.php';
