#!/usr/bin/env php
<?php
declare(strict_types=1);

// PHP built-in server router (vendor version).
// Always serve files from the project’s public/ directory.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Compute project root based on this script’s location:
// vendor/monkeyscloud/monkeyslegion-dev-server/bin/dev-router.php
$projectRoot = dirname(__DIR__, 4);

// Map URI to project’s public folder
$file = $projectRoot . '/public' . $uri;

// If static file exists, serve directly
if ($uri !== '/' && is_file($file)) {
    return false;
}

// Otherwise dispatch to project front controller
require $projectRoot . '/public/index.php';