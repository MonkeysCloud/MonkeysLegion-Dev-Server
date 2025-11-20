<?php

declare(strict_types=1);

namespace MonkeysLegion\DevServer;

/**
 * Pure PHP file watcher for hot-reload without external dependencies.
 * Polls directories for file changes and restarts the server when detected.
 */
final class FileWatcher
{
    private array $watchPaths = [];
    private array $fileHashes = [];
    private int $checkInterval = 1; // seconds between checks
    private array $extensions = [];

    /**
     * @param array $paths Directories or files to watch
     * @param array $extensions File extensions to watch (e.g., ['php', 'html', 'css'])
     * @param int $interval Seconds between checks (default: 1)
     */
    public function __construct(array $paths, array $extensions = [], int $interval = 1)
    {
        $this->watchPaths = $paths;
        $this->extensions = $extensions;
        $this->checkInterval = $interval;
    }

    /**
     * Start watching and execute callback when changes detected.
     *
     * @param callable $onChangeCallback Function to call when files change
     */
    public function watch(callable $onChangeCallback): void
    {
        echo "👀  Watching for file changes (checking every {$this->checkInterval}s)...\n";

        // Initial scan
        $this->scanFiles();

        while (true) {
            sleep($this->checkInterval);

            if ($this->hasChanges()) {
                echo "🔄  Changes detected, restarting...\n";
                $onChangeCallback();

                // Rescan after restart
                $this->scanFiles();
            }
        }
    }

    /**
     * Scan all files and store their modification times.
     */
    private function scanFiles(): void
    {
        $this->fileHashes = [];

        foreach ($this->watchPaths as $path) {
            if (!file_exists($path)) {
                continue;
            }

            if (is_file($path)) {
                $this->addFile($path);
            } elseif (is_dir($path)) {
                $this->scanDirectory($path);
            }
        }

        echo "📁  Watching " . count($this->fileHashes) . " files\n";
    }

    /**
     * Recursively scan directory for files.
     */
    private function scanDirectory(string $dir): void
    {
        $items = @scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            // Skip common directories to ignore
            if (is_dir($path)) {
                $basename = basename($path);
                if (in_array($basename, ['vendor', 'node_modules', '.git', 'var', 'storage', 'cache'])) {
                    continue;
                }
                $this->scanDirectory($path);
            } elseif (is_file($path)) {
                $this->addFile($path);
            }
        }
    }

    /**
     * Add a file to the watch list if it matches extensions filter.
     */
    private function addFile(string $path): void
    {
        // If extensions filter is set, check if file matches
        if (!empty($this->extensions)) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (!in_array($ext, $this->extensions)) {
                return;
            }
        }

        // Store modification time and size as quick change detection
        $mtime = @filemtime($path);
        $size = @filesize($path);

        if ($mtime !== false && $size !== false) {
            $this->fileHashes[$path] = $mtime . ':' . $size;
        }
    }

    /**
     * Check if any watched files have changed.
     */
    private function hasChanges(): bool
    {
        foreach ($this->fileHashes as $path => $oldHash) {
            // Check if file was deleted
            if (!file_exists($path)) {
                return true;
            }

            // Check if file was modified
            $mtime = @filemtime($path);
            $size = @filesize($path);
            $newHash = $mtime . ':' . $size;

            if ($newHash !== $oldHash) {
                echo "📝  Changed: " . basename($path) . "\n";
                return true;
            }
        }

        // Check for new files
        foreach ($this->watchPaths as $path) {
            if (is_dir($path) && $this->hasNewFiles($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if directory has new files not in our watch list.
     */
    private function hasNewFiles(string $dir): bool
    {
        $items = @scandir($dir);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $basename = basename($path);
                if (in_array($basename, ['vendor', 'node_modules', '.git', 'var', 'storage', 'cache'])) {
                    continue;
                }
                if ($this->hasNewFiles($path)) {
                    return true;
                }
            } elseif (is_file($path)) {
                if (!isset($this->fileHashes[$path])) {
                    // Check extension filter
                    if (!empty($this->extensions)) {
                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                        if (in_array($ext, $this->extensions)) {
                            echo "➕  New file: " . basename($path) . "\n";
                            return true;
                        }
                    } else {
                        echo "➕  New file: " . basename($path) . "\n";
                        return true;
                    }
                }
            }
        }

        return false;
    }
}