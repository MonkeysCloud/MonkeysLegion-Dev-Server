<?php
declare(strict_types=1);

// Router script for PHP built‑in server:
// - If the requested file exists under public/, serve it.
// - Otherwise dispatch to public/index.php.

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/../public' . $uri;

if ($uri !== '/' && is_file($file)) {
    return false; // serve the file directly
}

require __DIR__ . '/../public/index.php';