#!/usr/bin/env php
<?php
declare(strict_types=1);

// PHP built-in server router (vendor version).
// Always serve files from the project’s public/ directory.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Find project root by looking for composer.json
$projectRoot = dirname(__DIR__, 4);
if (!file_exists($projectRoot . '/composer.json')) {
    // If vendor script, try going up one more level
    $projectRoot = dirname(__DIR__, 5);
}
if (!file_exists($projectRoot . '/composer.json')) {
    // Fallback: try to find it by going up from current directory
    $current = __DIR__;
    for ($i = 0; $i < 10; $i++) {
        if (file_exists($current . '/composer.json')) {
            $projectRoot = $current;
            break;
        }
        $current = dirname($current);
    }
}

// 🔁 Dev reload endpoint
if ($uri === '/_dev/reload.json') {
    $marker = $projectRoot . '/var/cache/dev-reload.json';

    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');

    if (!file_exists($marker)) {
        echo json_encode(['version' => 0, 'status' => 'no_marker']);
        return true;
    }

    $content = @file_get_contents($marker);
    if ($content === false) {
        echo json_encode(['version' => 0, 'status' => 'read_error']);
        return true;
    }

    echo $content;
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