# MonkeysLegion Dev Server

A lightweight, hot-reload development server for MonkeysLegion applications. Built on PHP's native web server with automatic file watching and zero configuration.

## Features

- 🚀 **Zero Configuration** - Works out of the box with MonkeysLegion projects
- 🔥 **Hot Reload** - Automatic restart on file changes (two methods available)
    - 🎯 **entr** - External file watcher (recommended, most efficient)
    - 🔄 **FileWatcher** - Built-in pure PHP watcher (zero external dependencies)
- ⚡ **Fast** - Uses PHP's built-in server with opcache disabled for instant updates
- 🎯 **Simple CLI** - Easy commands: `start`, `stop`, `restart`, `status`
- 🛡️ **Process Management** - Robust PID tracking with fallback mechanisms
- 📁 **Smart Routing** - Serves static files and routes dynamic requests to your app
- 🔧 **Flexible** - Customizable host, port, and document root
- 💪 **No External Dependencies** - Works with pure PHP, `entr` is optional

## Requirements

- PHP 8.0 or higher
- POSIX extension (included in most PHP installations)
- `entr` for efficient hot-reload (optional, automatic fallback to pure PHP watcher)

### Installing entr

```bash
# Ubuntu/Debian
sudo apt-get install entr

# macOS
brew install entr

# Fedora/RHEL
sudo dnf install entr
```

## Installation

### Via Composer (Recommended)

```bash
composer require --dev monkeyscloud/monkeyslegion-dev-server
```

### Manual Installation

1. Clone or download this package to your project's `vendor` directory
2. Ensure the autoloader is configured correctly
3. Copy the `bin/dev-server` script to your project's `bin/` directory

## Quick Start

```bash
# Start the server (default: http://127.0.0.1:8000)
./vendor/bin/dev-server

# Or if copied to your bin/ directory
./bin/dev-server
```

That's it! Your MonkeysLegion app is now running with hot-reload enabled.

## Usage

### Basic Commands

```bash
# Start server on default host:port (127.0.0.1:8000)
./bin/dev-server
./bin/dev-server start

# Start on custom host and port
./bin/dev-server 0.0.0.0 3000

# Stop the server
./bin/dev-server stop

# Restart the server
./bin/dev-server restart

# Restart on custom host:port
./bin/dev-server restart 0.0.0.0 3000

# Check server status
./bin/dev-server status

# Show help
./bin/dev-server help
```

### Environment Variables

You can set default host and port via environment variables:

```bash
# In your .env or shell profile
export ML_DEV_HOST=0.0.0.0
export ML_DEV_PORT=3000

# Now just run:
./bin/dev-server
```

### Programmatic Usage

```php
<?php

use MonkeysLegion\DevServer\DevServer;

// Start server
$server = new DevServer();
$server->serve('127.0.0.1', 8000);

// Stop server
DevServer::stop();

// Restart server
DevServer::restart('127.0.0.1', 8000);

// Check status
DevServer::status();
```

## How It Works

### Hot Reload

The dev server offers **two file watching methods**, automatically selecting the best available option:

#### 1. entr (Recommended)
When `entr` is installed, the dev server uses it for efficient file watching. This is the fastest and most resource-friendly method.

**Advantages:**
- ✅ Most efficient (low CPU usage)
- ✅ Instant change detection
- ✅ Battle-tested and reliable

**Installation:**
```bash
# Ubuntu/Debian
sudo apt-get install entr

# macOS
brew install entr

# Fedora/RHEL
sudo dnf install entr
```

#### 2. FileWatcher (Built-in Fallback)
When `entr` is not available, the dev server automatically falls back to a pure PHP file watcher. No external dependencies required!

**Advantages:**
- ✅ Zero external dependencies
- ✅ Works on any system with PHP
- ✅ Automatic fallback (no configuration needed)
- ✅ Watches for new files, modifications, and deletions

**How it works:**
- Polls watched directories every second
- Tracks file modification times and sizes
- Ignores `vendor/`, `node_modules/`, `.git/`, etc.
- Filters by extension (`.php`, `.css`, `.js`, `.html`, `.json`)

**Performance note:** The pure PHP watcher is slightly less efficient than `entr` as it uses polling. For production-like performance testing, install `entr`.

### Choosing Your Method

The dev server automatically selects the best available method:

```bash
# With entr installed:
./bin/dev-server
# Output: 🔁  Hot-reload enabled via entr (recommended)

# Without entr:
./bin/dev-server  
# Output: 🔁  Hot-reload enabled via FileWatcher
```

You can also force the use of FileWatcher programmatically:

```php
$server = new DevServer();
$server->serve('127.0.0.1', 8000, null, true); // Force FileWatcher
```

### opcache Configuration

Both methods disable PHP's opcache for CLI mode so changes are reflected immediately without manual restart:

```ini
opcache.enable_cli=0              # Disable opcache for CLI
opcache.enable=0                   # Disable opcache entirely
opcache.validate_timestamps=1      # Check for file changes
opcache.revalidate_freq=0          # Check on every request
realpath_cache_ttl=0               # Don't cache resolved paths
```

### Directory Structure

```
your-project/
├── app/                    # Watched for changes
├── config/                 # Watched for changes  
├── public/                 # Watched for changes (static files served directly)
│   └── index.php          # Front controller
├── resources/
│   └── views/             # Watched for changes
├── var/
│   └── run/
│       └── dev-server.pid # Process ID file
├── bin/
│   └── dev-server         # CLI script
└── vendor/
    └── monkeyscloud/
        └── monkeyslegion-dev-server/
```

### Routing

The dev server uses a smart router that:

1. Serves static files directly from `public/` if they exist
2. Routes all other requests through `public/index.php`
3. Handles pretty URLs and rewrite rules automatically

## Configuration

### Custom Document Root

By default, the server uses `public/` as the document root. You can customize this:

```php
$server = new DevServer();
$server->serve('127.0.0.1', 8000, '/path/to/docroot');

// Or relative to project root
$server->serve('127.0.0.1', 8000, 'web');
```

### Custom Router Script

The server looks for a router script in this order:

1. `{project}/bin/dev-router.php` (your custom router)
2. `{project}/vendor/monkeyscloud/monkeyslegion-dev-server/bin/dev-router.php` (default)

Create your own router in `bin/dev-router.php` if you need custom routing logic:

```php
#!/usr/bin/env php
<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$projectRoot = dirname(__DIR__);

// Custom routing logic here
if (str_starts_with($uri, '/api/')) {
    require $projectRoot . '/public/api.php';
    return;
}

// Serve static files
$file = $projectRoot . '/public' . $uri;
if ($uri !== '/' && is_file($file)) {
    return false; // Let PHP's server handle it
}

// Default to front controller
require $projectRoot . '/public/index.php';
```

### Watched Directories

To customize which directories are watched for changes, edit the `dev-server` script:

```php
// Find this section:
$paths = "find app public resources config -type f 2>/dev/null";

// Customize to watch different directories:
$paths = "find app src templates public -type f 2>/dev/null";
```

## Advanced Usage

### Running on a Network

To make your dev server accessible from other devices on your network:

```bash
# Listen on all interfaces
./bin/dev-server 0.0.0.0 8000

# Now accessible at http://your-ip:8000
```

### Multiple Instances

You can run multiple dev servers on different ports:

```bash
# Terminal 1
./bin/dev-server 127.0.0.1 8000

# Terminal 2  
./bin/dev-server 127.0.0.1 8001

# Terminal 3
./bin/dev-server 127.0.0.1 8002
```

Note: Each instance needs its own PID file, so you may need to modify the `getPidFile()` method to include the port number.

### Production Warning

⚠️ **Never use this dev server in production!**

This server is designed for development only. For production, use:
- Apache with mod_php or php-fpm
- Nginx with php-fpm
- Caddy
- Or any other production-grade web server

## Troubleshooting

### Server Won't Stop

If `./bin/dev-server stop` doesn't work:

```bash
# Find the process
ps aux | grep "php.*-S"

# Kill by PID
kill -9 <PID>

# Or kill by port
lsof -ti:8000 | xargs kill -9

# Or use pkill
pkill -f 'php.*-S.*:8000'

# Clean up stale PID file
rm var/run/dev-server.pid
```

### Port Already in Use

```bash
# Find what's using the port
lsof -i :8000

# Kill it
lsof -ti:8000 | xargs kill -9

# Then start your server
./bin/dev-server
```

### Hot Reload Not Working

**With entr:**

1. **Check if entr is installed:**
   ```bash
   which entr
   # If empty, install it or dev server will use FileWatcher
   ```

2. **Verify opcache is disabled:**
   ```bash
   php -d opcache.enable_cli=0 -r "var_dump(ini_get('opcache.enable_cli'));"
   # Should output: string(1) "0"
   ```

3. **Check watched directories exist:**
   ```bash
   ls -la app/ public/ resources/ config/
   ```

**With FileWatcher (pure PHP):**

1. **Check which method is being used:**
   ```bash
   ./bin/dev-server
   # Look for: "Hot-reload enabled via entr" or "Hot-reload enabled via FileWatcher"
   ```

2. **Check file permissions:**
   ```bash
   # FileWatcher needs read access to watched directories
   ls -la app/ public/ resources/ config/
   ```

3. **Manually restart if needed:**
   ```bash
   ./bin/dev-server restart
   ```

**General troubleshooting:**

4. **Manual restart:**
   ```bash
   ./bin/dev-server restart
   ```

### Changes Not Appearing

If your code changes aren't showing up:

1. **Hard refresh your browser** - `Ctrl+Shift+R` (or `Cmd+Shift+R` on Mac)
2. **Check browser cache** - Open DevTools → Network → Disable cache
3. **Restart the server** - `./bin/dev-server restart`
4. **Check PHP error logs** - Look for syntax errors

### Permission Denied

```bash
# Make sure the script is executable
chmod +x bin/dev-server
chmod +x vendor/bin/dev-server

# Create var/run directory if it doesn't exist
mkdir -p var/run
chmod 775 var/run
```

## Technical Details

### PHP Configuration

The server runs with these PHP settings:

```ini
opcache.enable_cli=0              # Disable opcache for CLI
opcache.enable=0                   # Disable opcache entirely
opcache.validate_timestamps=1      # Check for file changes
opcache.revalidate_freq=0          # Check on every request
realpath_cache_ttl=0               # Don't cache resolved paths
```

### Process Management

- **PID File:** `{project}/var/run/dev-server.pid`
- **Process Group:** The server kills the entire process group to ensure child processes are terminated
- **Signals Used:** `SIGTERM` (graceful shutdown), `SIGKILL` (forced shutdown as fallback)
- **Fallback Methods:** If PID-based shutdown fails, the server attempts to kill by port using `lsof` and `pkill`

### File Watching

When `entr` is available, the server watches these file patterns:

- `app/**/*.php` - Application code
- `resources/views/**/*.ml.php` - Template files
- `public/**/*.*` - Static assets (CSS, JS, images)
- `config/*.php` - Configuration files

Changes to any of these files trigger an automatic server restart.

## API Reference

### DevServer Class

#### `serve(string $host, int $port, ?string $docRoot): void`

Start the development server.

**Parameters:**
- `$host` - Host to bind to (default: `127.0.0.1`)
- `$port` - Port to listen on (default: `8000`)
- `$docRoot` - Document root path or directory name (default: `public`)

**Example:**
```php
$server = new DevServer();
$server->serve('0.0.0.0', 3000, 'web');
```

#### `stop(): void`

Stop the running development server.

**Example:**
```php
DevServer::stop();
```

#### `restart(string $host, int $port): void`

Restart the development server.

**Parameters:**
- `$host` - Host to bind to (default: `127.0.0.1`)
- `$port` - Port to listen on (default: `8000`)

**Example:**
```php
DevServer::restart('127.0.0.1', 8000);
```

#### `status(): void`

Check the status of the development server.

**Example:**
```php
DevServer::status();
```

## Comparison with Other Tools

| Feature | MonkeysLegion DevServer | Symfony Local Server | Laravel Valet | php -S |
|---------|------------------------|---------------------|---------------|--------|
| Hot Reload | ✅ Yes (with entr) | ✅ Yes | ✅ Yes | ❌ No |
| Zero Config | ✅ Yes | ⚠️ Partial | ⚠️ Requires setup | ✅ Yes |
| Process Management | ✅ Yes (start/stop/restart) | ✅ Yes | ✅ Yes | ❌ Manual |
| Multiple Projects | ⚠️ Different ports | ✅ Yes | ✅ Yes | ❌ No |
| HTTPS Support | ❌ No | ✅ Yes | ✅ Yes | ❌ No |
| Framework Agnostic | ✅ Yes | ⚠️ Symfony-focused | ⚠️ Laravel-focused | ✅ Yes |
| Installation | Composer | Binary | Binary | Built-in |

## Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

```bash
git clone https://github.com/monkeyscloud/monkeyslegion-dev-server.git
cd monkeyslegion-dev-server
composer install
```

### Running Tests

```bash
composer test
```

## License

This package is open-source software licensed under the [MIT license](LICENSE).

## Credits

- **Author:** MonkeysCloud Team
- **Maintainer:** [Your Name]
- **Inspired by:** Symfony Local Server, Laravel Valet, and PHP's built-in web server

## Support

- **Issues:** [GitHub Issues](https://github.com/monkeyscloud/monkeyslegion-dev-server/issues)
- **Documentation:** [Full Docs](https://docs.monkeyslegion.com/dev-server)
- **Community:** [Discord](https://discord.gg/monkeyslegion)

## Changelog

### 1.0.1 (2025-11-19)
- ✨ Added `restart` command
- ✨ Added `status` command
- 🐛 Fixed stop command not working reliably
- 🐛 Fixed hot reload with proper opcache settings
- 🔧 Improved process group management
- 🔧 Added fallback kill-by-port mechanism
- 📝 Comprehensive documentation

### 1.0.0 (2024-01-01)
- 🎉 Initial release
- ✨ Basic start/stop functionality
- ✨ Hot reload with entr support
- ✨ Custom host/port configuration

---

Made with ❤️ by the MonkeysCloud team