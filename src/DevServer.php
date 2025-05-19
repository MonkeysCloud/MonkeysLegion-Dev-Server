<?php

declare(strict_types=1);

namespace MonkeysLegion\DevServer;

/**
 * Simple hot‑reload development server for MonkeysLegion apps.
 */
final class DevServer
{
    private const PID_FILE = __DIR__ . '/../../var/run/dev-server.pid';

    /**
     * Serve the application using PHP’s built‑in webserver.
     *
     * @param string      $host    Host (default "127.0.0.1")
     * @param int         $port    Port (default 8000)
     * @param string|null $docRoot Document root path or directory name (default "public")
     */
    public function serve(
        string $host = '127.0.0.1',
        int $port = 8000,
        ?string $docRoot = null
    ): void {
        // Determine project root and document root
        $projectRoot = getcwd();

        // Use provided docRoot if absolute or relative, otherwise default to <project>/public
        if ($docRoot !== null) {
            $docRootPath = is_dir($docRoot)
                ? $docRoot
                : $projectRoot . DIRECTORY_SEPARATOR . $docRoot;
        } else {
            $docRootPath = $projectRoot . DIRECTORY_SEPARATOR . 'public';
        }

        if (! is_dir($docRootPath)) {
            fwrite(STDERR, "Public directory not found at {$docRootPath}\n");
            exit(1);
        }

        // Determine router script: project or vendor fallback
        $projectRouter = $projectRoot
            . DIRECTORY_SEPARATOR . 'bin'
            . DIRECTORY_SEPARATOR . 'dev-router.php';

        if (is_file($projectRouter)) {
            $router = $projectRouter;
        } else {
            $vendorRouter = $projectRoot
                . DIRECTORY_SEPARATOR . 'vendor'
                . DIRECTORY_SEPARATOR . 'monkeyscloud'
                . DIRECTORY_SEPARATOR . 'monkeyslegion-dev-server'
                . DIRECTORY_SEPARATOR . 'bin'
                . DIRECTORY_SEPARATOR . 'dev-router.php';
            if (! is_file($vendorRouter)) {
                fwrite(STDERR, "Error: dev-router.php not found in project bin or vendor package.\n");
                exit(1);
            }
            $router = $vendorRouter;
        }

        // Build and run built-in PHP server command
        $command = sprintf(
            '%s -S %s:%d -t %s %s',
            escapeshellarg(PHP_BINARY),
            $host,
            $port,
            escapeshellarg($docRootPath),
            escapeshellarg($router)
        );

        echo "🚀  Starting MonkeysLegion dev server at http://{$host}:{$port}\n";
        passthru($command);
    }

    public static function stop(): void
    {
        if (! is_file(self::PID_FILE)) {
            echo "⚠️  No running server found (no PID file)\n";
            exit(1);
        }

        $pid = (int) file_get_contents(self::PID_FILE);
        if ($pid > 0 && posix_kill($pid, SIGTERM)) {
            @unlink(self::PID_FILE);
            echo "🛑  Stopped dev server (PID {$pid})\n";
            exit(0);
        }

        echo "❌  Failed to stop process {$pid}\n";
        exit(1);
    }

}
