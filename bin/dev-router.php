#!/usr/bin/env php
<?php
declare(strict_types=1);

// PHP built-in server router (vendor version).
// Always serve files from the project’s public/ directory.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ------------------------------------------------------------
// 1) Resolve project root
// ------------------------------------------------------------
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

// Normalize path (important for symlinks)
$projectRoot = realpath($projectRoot) ?: $projectRoot;

// ------------------------------------------------------------
// 2)  🔁  Dev Hot Reload Endpoint
// ------------------------------------------------------------
if ($uri === '/_dev/reload.json') {
    $marker = $projectRoot . '/var/cache/dev-reload.json';

    // Ensure headers disable all caching
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    // Marker missing → return stable JSON
    if (!is_file($marker)) {
        echo json_encode([
                'version' => 0,
                'status'  => 'no_marker'
        ]);
        return true;
    }

    // Read marker file
    $content = @file_get_contents($marker);

    // Content unreadable → fallback JSON
    if ($content === false) {
        echo json_encode([
                'version' => 0,
                'status'  => 'read_error'
        ]);
        return true;
    }

    // Valid → emit raw version JSON from file
    echo $content;
    return true;
}

// ------------------------------------------------------------
// 3) Route static files from /public
// ------------------------------------------------------------
$file = $projectRoot . '/public' . $uri;

if ($uri !== '/' && is_file($file)) {
    return false; // Let built-in server serve the file
}

// ------------------------------------------------------------
// 4) Fallback → Front controller
// ------------------------------------------------------------
require $projectRoot . '/public/index.php';
