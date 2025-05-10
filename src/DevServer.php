<?php

declare(strict_types=1);

namespace MonkeysLegion\DevServer;

/**
 * Simple hot-reload development server for MonkeysLegion apps.
 */
final class DevServer
{
    /**
     * Host for the server (default "127.0.0.1").
     * Port for the server (default 8000).
     * Document root (default project/public).
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

        $command = sprintf(
            '%s -S %s:%d -t %s %s',
            escapeshellarg((string) PHP_BINARY),
            $host,
            $port,
            escapeshellarg($docRoot),
            escapeshellarg($docRoot . DIRECTORY_SEPARATOR . 'index.php')
        );

        echo "Starting MonkeysLegion dev server at http://{$host}:{$port}\n";
        passthru($command);
    }
}