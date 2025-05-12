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
     * @param string      $host    Host (default "127.0.0.1")
     * @param int         $port    Port (default 8000)
     * @param string|null $docRoot Document root (default "<cwd>/public")
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

        // Determine router script: project or vendor fallback
        $projectRouter = $projectRoot
            . DIRECTORY_SEPARATOR . 'bin'
            . DIRECTORY_SEPARATOR . 'dev-router.php';

        if (is_file($projectRouter)) {
            $router = $projectRouter;
        } else {
            // Fallback to vendor package's router
            $vendorRouter = $projectRoot
                . DIRECTORY_SEPARATOR . 'vendor'
                . DIRECTORY_SEPARATOR . 'monkeyscloud'
                . DIRECTORY_SEPARATOR . 'monkeyslegion-dev-server'
                . DIRECTORY_SEPARATOR . 'bin'
                . DIRECTORY_SEPARATOR . 'dev-router.php';
            if (is_file($vendorRouter)) {
                $router = $vendorRouter;
            } else {
                fwrite(STDERR, "Error: dev-router.php not found in project bin or vendor package.\n");
                exit(1);
            }
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
