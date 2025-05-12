<?php
declare(strict_types=1);

// PHP built‑in server router for MonkeysLegion apps.
// Always serve from the project’s public/ directory.

$uri         = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$projectRoot = getcwd();
$file        = $projectRoot . '/public' . $uri;

// If the request matches a real file under public/, serve it directly
if ($uri !== '/' && is_file($file)) {
    return false;
}

// Otherwise fall back to the project's front controller
require $projectRoot . '/public/index.php';