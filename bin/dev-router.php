<?php
declare(strict_types=1);

// PHP‑cli server router:
// - Serve actual files in public/
// - Otherwise forward to index.php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$publicFile = __DIR__ . '/../public' . $uri;

if ($uri !== '/' && is_file($publicFile)) {
    return false; // serve file
}

require __DIR__ . '/../public/index.php';