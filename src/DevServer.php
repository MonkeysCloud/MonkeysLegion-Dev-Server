<?php

declare(strict_types=1);

namespace MonkeysLegion\DevServer;

/**
 * Simple hot‑reload development server for MonkeysLegion apps.
 */
final class DevServer
{
    /**
     * Serve the application using PHP’s built‑in webserver.
     *
     * @param string      $host    Host (default “127.0.0.1”)
     * @param int         $port    Port (default 8000)
     * @param string|null $docRoot Document root (default “<cwd>/public”)
     */
    public function serve(
        string $host = '127.0.0.1',
        int $port = 8000,
        ?string $docRoot = null
    ): void {
        // Determine project root and document root
        $projectRoot = getcwd();
        $docRoot     = $docRoot ?? $projectRoot . DIRECTORY_SEPARATOR . 'public';
        if (! is_dir($docRoot)) {
            fwrite(STDERR, "Public directory not found at {$docRoot}\n");
            exit(1);
        }

        // Prefer the project's bin/dev-router.php if it exists
        $router = $projectRoot
            . DIRECTORY_SEPARATOR . 'bin'
            . DIRECTORY_SEPARATOR . 'dev-router.php';
        if (! is_file($router)) {
            // Fallback to package's dev-router
            $router = __DIR__ . '/../../bin/dev-router.php';
        }

        $command = sprintf(
            '%s -S %s:%d -t %s %s',
            escapeshellarg((string) PHP_BINARY),
            $host,
            $port,
            escapeshellarg($docRoot),
            escapeshellarg($router)
        );

        echo "🚀  Starting MonkeysLegion dev server at http://{$host}:{$port}\n";
        passthru($command);
    }
}
