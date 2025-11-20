#!/usr/bin/env php
<?php
declare(strict_types=1);

// PHP built-in server router (vendor version).
// Always serve files from the project’s public/ directory.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$projectRoot = dirname(__DIR__, 4);

// 🔁 Dev reload endpoint
if ($uri === '/_dev/reload.json') {
    $marker = $projectRoot . '/var/cache/dev-reload.json';

    header('Content-Type: application/json');

    if (!file_exists($marker)) {
        echo json_encode(['version' => 0]);
        return true;
    }

    readfile($marker);
    return true;
}

// Map URI to project’s public folder
$file = $projectRoot . '/public' . $uri;

// If static file exists, serve directly
if ($uri !== '/' && is_file($file)) {
    return false;
}

// Otherwise dispatch to project front controller
require $projectRoot . '/public/index.php';