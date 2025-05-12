<?php
declare(strict_types=1);

// Built‑in PHP server router for MonkeysLegion.
// Always use the project’s public/ folder (cwd), never the package’s.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Determine the project root from the current working directory
$projectRoot = getcwd();

// Map the URI to the project’s public folder
$file = $projectRoot . '/public' . $uri;

// If it’s a real file (and not the root path), let PHP serve it directly
if ($uri !== '/' && is_file($file)) {
    return false;
}

// Otherwise fall back to the project’s front controller
require $projectRoot . '/public/index.php';