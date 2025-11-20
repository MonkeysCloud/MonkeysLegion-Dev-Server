<?php

declare(strict_types=1);

namespace MonkeysLegion\DevServer;

/**
 * Simple hot-reload development server for MonkeysLegion apps.
 */
final class DevServer
{
    private static function getPidFile(): string
    {
        $projectRoot = getcwd();
        return $projectRoot . '/var/run/dev-server.pid';
    }

    /**
     * Serve the application using PHP's built-in webserver, in the background.
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
        $pidFile = self::getPidFile();
        @mkdir(dirname($pidFile), 0775, true);

        // disable opcache for CLI to enable hot-reload
        $phpBinary = escapeshellarg(PHP_BINARY);

        $cmd = sprintf(
            '%s '.
            '-d opcache.enable_cli=0 '.
            '-d opcache.enable=0 '.
            '-d opcache.validate_timestamps=1 '.
            '-d opcache.revalidate_freq=0 '.
            '-d realpath_cache_ttl=0 '.
            '-S %s:%d -t %s %s > /dev/null 2>&1 & echo $!',
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
        file_put_contents($pidFile, (string)$pid);

        echo "🚀  Dev server running at http://{$host}:{$port} (PID {$pid})\n";
    }

    /**
     * Stop the running dev-server (via PID file + SIGTERM).
     */
    public static function stop(): void
    {
        $pidFile = self::getPidFile();

        if (!is_file($pidFile)) {
            echo "⚠️  No running server found (no PID file)\n";

            // Try to kill by port anyway
            self::killByPort();
            exit(1);
        }

        $pid = (int) file_get_contents($pidFile);

        if ($pid <= 0) {
            echo "⚠️  Invalid PID in file\n";
            @unlink($pidFile);
            self::killByPort();
            exit(1);
        }

        // Kill the process group (handles child processes)
        $killed = false;

        // Try to kill the process group first (negative PID)
        if (function_exists('posix_kill')) {
            // Kill entire process group
            @posix_kill(-$pid, SIGTERM);
            // Also kill the main process
            $killed = @posix_kill($pid, SIGTERM);

            if (!$killed) {
                // Try SIGKILL if SIGTERM didn't work
                sleep(1);
                $killed = @posix_kill($pid, SIGKILL);
                @posix_kill(-$pid, SIGKILL);
            }
        } else {
            // Fallback for systems without posix_kill
            exec("kill -TERM {$pid} 2>/dev/null", $output, $result);
            $killed = ($result === 0);
        }

        @unlink($pidFile);

        if ($killed) {
            echo "🛑  Stopped dev server (PID {$pid})\n";
        } else {
            echo "⚠️  Process {$pid} may have already stopped\n";
            self::killByPort();
        }
    }

    /**
     * Restart the dev server.
     */
    public static function restart(string $host = '127.0.0.1', int $port = 8000): void
    {
        echo "🔄  Restarting dev server...\n";
        self::stop();
        sleep(1); // Give it a moment to clean up

        $server = new self();
        $server->serve($host, $port);
    }

    /**
     * Fallback: kill PHP processes listening on common dev ports.
     */
    private static function killByPort(int $port = 8000): void
    {
        // Try to find and kill PHP processes on the port
        $cmd = "lsof -ti:$port 2>/dev/null | xargs kill -9 2>/dev/null";
        @exec($cmd);

        // Alternative: pkill approach
        $cmd2 = "pkill -f 'php.*-S.*:$port' 2>/dev/null";
        @exec($cmd2);
    }

    /**
     * Get the status of the dev server.
     */
    public static function status(): void
    {
        $pidFile = self::getPidFile();

        if (!is_file($pidFile)) {
            echo "❌  Dev server is not running (no PID file)\n";
            return;
        }

        $pid = (int) file_get_contents($pidFile);

        // Check if process is actually running
        if (function_exists('posix_kill')) {
            $running = @posix_kill($pid, 0); // Signal 0 just checks if process exists
        } else {
            exec("ps -p {$pid} 2>/dev/null", $output, $result);
            $running = ($result === 0 && count($output) > 1);
        }

        if ($running) {
            echo "✅  Dev server is running (PID {$pid})\n";

            // Try to get the URL
            exec("lsof -nP -iTCP -sTCP:LISTEN -p {$pid} 2>/dev/null | grep -oE ':[0-9]+'", $ports);
            if (!empty($ports[0])) {
                $port = trim($ports[0], ':');
                echo "🌐  Listening on: http://127.0.0.1:{$port}\n";
            }
        } else {
            echo "❌  Dev server is not running (stale PID file)\n";
            @unlink($pidFile);
        }
    }
}