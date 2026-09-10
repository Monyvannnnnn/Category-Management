<?php
// api/index.php - Vercel Serverless Entrypoint

// Change working directory to project root
chdir(__DIR__ . '/..');

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Handle root URL
if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    require __DIR__ . '/../index.php';
    exit;
}

// Target PHP file in root
$targetFile = __DIR__ . '/..' . $uri;

if (file_exists($targetFile) && is_file($targetFile) && substr($targetFile, -4) === '.php') {
    require $targetFile;
    exit;
}

// Default fallback
require __DIR__ . '/../index.php';
