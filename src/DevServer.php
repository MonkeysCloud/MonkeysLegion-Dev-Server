<?php

declare(strict_types=1);

namespace MonkeysLegion\DevServer;

/**
 * Simple hot-reload development server for MonkeysLegion apps.
 */
final class DevServer
{
    private const PID_FILE = __DIR__ . '/../../var/run/dev-server.pid';

    /**
     * Serve the application using PHP’s built-in webserver, in the background.
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
        $projectRoot = getcwd();

        // figure out docRoot
        if ($docRoot !== null) {
            $docRootPath = is_dir($docRoot)
                ? $docRoot
                : $projectRoot . DIRECTORY_SEPARATOR . $docRoot;
        } else {
            $docRootPath = $projectRoot . DIRECTORY_SEPARATOR . 'public';
        }

        if (!is_dir($docRootPath)) {
            fwrite(STDERR, "Public directory not found at {$docRootPath}\n");
            exit(1);
        }

        // find router script
        $projectRouter = $projectRoot . '/bin/dev-router.php';
        if (is_file($projectRouter)) {
            $router = $projectRouter;
        } else {
            $vendorRouter = $projectRoot
                . '/vendor/monkeyscloud/monkeyslegion-dev-server/bin/dev-router.php';
            if (!is_file($vendorRouter)) {
                fwrite(STDERR, "Error: dev-router.php not found in bin/ or vendor package\n");
                exit(1);
            }
            $router = $vendorRouter;
        }

        // ensure PID directory
        @mkdir(dirname(self::PID_FILE), 0775, true);

        // prepend “-d opcache.enable_cli=0” to PHP_BINARY
        $phpBinary = escapeshellarg(PHP_BINARY);

        $cmd = sprintf(
        // note: -d goes immediately after the binary, before -S
            '%s -d opcache.enable_cli=0 -S %s:%d -t %s %s > /dev/null 2>&1 & echo $!',
            $phpBinary,
            $host,
            $port,
            escapeshellarg($docRootPath),
            escapeshellarg($router)
        );

        // launch & capture pid
        $pid = (int) shell_exec($cmd);
        if ($pid <= 0) {
            fwrite(STDERR, "❌  Failed to start server\n");
            exit(1);
        }

        // write PID file
        file_put_contents(self::PID_FILE, (string)$pid);

        echo "🚀  Dev server running at http://{$host}:{$port} (PID {$pid})\n";
    }

    /**
     * Stop the running dev-server (via PID file + SIGTERM).
     */
    public static function stop(): void
    {
        if (!is_file(self::PID_FILE)) {
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