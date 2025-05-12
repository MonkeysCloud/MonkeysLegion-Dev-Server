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
        $docRoot = $docRoot ?? getcwd() . DIRECTORY_SEPARATOR . 'public';
        if (! is_dir($docRoot)) {
            fwrite(STDERR, "Public directory not found at {$docRoot}\n");
            exit(1);
        }

        // Build router script path
        $router = __DIR__ . '/../../bin/dev-router.php';

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