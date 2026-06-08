<?php

define('LARAVEL_START', microtime(true));

$basePath = dirname(__DIR__);

aptoria_first_run_preflight($basePath);

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Illuminate\Http\Request::capture());

function aptoria_first_run_preflight(string $basePath): void
{
    aptoria_ensure_runtime_directories($basePath);

    $envState = aptoria_ensure_first_run_files($basePath);

    // Vendor-included deploy packages are often uploaded over an earlier failed
    // install. In that case storage/app/installed.lock or bootstrap/cache/config.php
    // can survive with APP_KEY=null. Laravel reads cached config before .env and
    // crashes with MissingAppKeyException. Clear cached PHP config whenever setup
    // is not locked, .env was repaired, or cached app.key is empty/missing.
    if (! aptoria_is_setup_locked($basePath) || $envState['changed'] || aptoria_cached_app_key_is_missing($basePath)) {
        aptoria_clear_bootstrap_cache($basePath);
    }

    $vendorAutoload = $basePath.'/vendor/autoload.php';

    if (! is_file($vendorAutoload)) {
        $installLog = null;
        $installAttempted = false;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['aptoria_action'] ?? '') === 'install_dependencies') {
            $installAttempted = true;

            if (! aptoria_web_installer_authorized($basePath)) {
                $installLog = [
                    'success' => false,
                    'command' => 'web installer blocked',
                    'output' => 'Automatic dependency installation from the browser is allowed only from localhost or with a valid setup token. Use SSH/cPanel Terminal, or open this page with ?setup_token=YOUR_TOKEN first.',
                ];
            } else {
                $installLog = aptoria_try_web_dependency_install($basePath);

                if (is_file($vendorAutoload)) {
                    header('Location: '.aptoria_current_url_without_query());
                    exit;
                }
            }
        }

        aptoria_render_preflight_page(
            'Dependency installation required',
            'Aptoria was copied successfully, but Laravel dependencies are not installed yet. The release ZIP intentionally does not contain vendor/. Install dependencies once, then refresh this page and the web setup wizard will start automatically.',
            aptoria_install_sections($basePath),
            503,
            $installAttempted,
            $installLog
        );
    }
}

function aptoria_is_setup_locked(string $basePath): bool
{
    return is_file($basePath.'/storage/app/installed.lock');
}

function aptoria_clear_bootstrap_cache(string $basePath): void
{
    $cacheDirectory = $basePath.'/bootstrap/cache';

    if (! is_dir($cacheDirectory)) {
        return;
    }

    foreach (glob($cacheDirectory.'/*.php') ?: [] as $cacheFile) {
        @unlink($cacheFile);
    }
}

function aptoria_cached_app_key_is_missing(string $basePath): bool
{
    $configCache = $basePath.'/bootstrap/cache/config.php';

    if (! is_file($configCache)) {
        return false;
    }

    $config = @include $configCache;

    if (! is_array($config)) {
        return true;
    }

    $key = $config['app']['key'] ?? null;

    return ! is_string($key) || trim($key) === '';
}

function aptoria_ensure_runtime_directories(string $basePath): void
{
    $directories = [
        'bootstrap/cache',
        'database/backups',
        'storage/app',
        'storage/app/private',
        'storage/app/public',
        'storage/framework/cache',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/testing',
        'storage/framework/views',
        'storage/logs',
    ];

    foreach ($directories as $directory) {
        $path = $basePath.'/'.$directory;
        if (! is_dir($path)) {
            @mkdir($path, 0775, true);
        }
    }
}

/** @return array{changed:bool, app_key:string} */
function aptoria_ensure_first_run_files(string $basePath): array
{
    $changed = false;
    $envPath = $basePath.'/.env';
    $examplePath = $basePath.'/.env.example';

    if (! is_file($envPath)) {
        if (! is_file($examplePath)) {
            aptoria_render_preflight_page(
                'Missing .env.example',
                'The first-run installer cannot create .env because .env.example is missing from the project root.',
                aptoria_install_sections($basePath),
                500
            );
        }

        if (! @copy($examplePath, $envPath)) {
            aptoria_render_preflight_page(
                'Cannot create .env',
                'The first-run installer could not create .env. Check write permissions for the project root.',
                aptoria_install_sections($basePath),
                500
            );
        }

        $changed = true;
    }

    $appKey = '';

    if (is_file($envPath)) {
        $env = (string) file_get_contents($envPath);

        $detectedUrl = aptoria_detect_app_url();
        $currentUrl = aptoria_env_value($env, 'APP_URL');
        $placeholderUrl = in_array($currentUrl, ['', 'http://localhost', 'http://127.0.0.1:8000'], true);

        // Do not rewrite .env when the detected URL already matches the current
        // value. Laravel's `artisan serve` watches .env and restarts the server
        // whenever its timestamp changes; rewriting the same APP_URL on each
        // asset/request can therefore reset the browser connection right after
        // login. Only persist a detected URL when the stored value actually
        // needs to change.
        if ($detectedUrl !== '' && $placeholderUrl && $currentUrl !== $detectedUrl) {
            $env = aptoria_set_env_value($env, 'APP_URL', $detectedUrl);
            $changed = true;
        }

        $appKey = aptoria_env_value($env, 'APP_KEY');
        if ($appKey === '' || $appKey === 'null') {
            $appKey = 'base64:'.base64_encode(random_bytes(32));
            $env = aptoria_set_env_value($env, 'APP_KEY', $appKey);
            $changed = true;
        }

        if (aptoria_env_value($env, 'DB_CONNECTION') === '') {
            $env = aptoria_set_env_value($env, 'DB_CONNECTION', 'sqlite');
            $changed = true;
        }

        if ($changed) {
            aptoria_write_env($envPath, $env);
        }

        // Make the repaired key available to the current PHP process as well.
        // Laravel will later load .env, but this protects first-run hosts with
        // restricted environment handling.
        if ($appKey !== '') {
            $_ENV['APP_KEY'] = $appKey;
            $_SERVER['APP_KEY'] = $appKey;
            @putenv('APP_KEY='.$appKey);
        }

        $connection = aptoria_env_value($env, 'DB_CONNECTION') ?: 'sqlite';
        if ($connection === 'sqlite') {
            $database = aptoria_env_value($env, 'DB_DATABASE') ?: 'database/database.sqlite';
            if ($database !== ':memory:' && ! str_starts_with($database, 'file:')) {
                $databasePath = aptoria_absolute_path($basePath, $database);
                $databaseDirectory = dirname($databasePath);
                if (! is_dir($databaseDirectory)) {
                    @mkdir($databaseDirectory, 0775, true);
                }
                if (! is_file($databasePath)) {
                    @touch($databasePath);
                }
            }
        }
    }

    return ['changed' => $changed, 'app_key' => $appKey];
}

function aptoria_env_value(string $env, string $key): string
{
    // Do not use \s here: it also matches line breaks and can read the next
    // environment entry as the value of an intentionally empty setting.
    if (! preg_match('/^'.preg_quote($key, '/').'[ \t]*=[ \t]*(.*)$/m', $env, $matches)) {
        return '';
    }

    $value = trim((string) $matches[1]);

    if ($value === '' || $value === 'null') {
        return '';
    }

    return trim($value, "\"'");
}

function aptoria_set_env_value(string $env, string $key, string $value): string
{
    $line = $key.'='.$value;

    if (preg_match('/^'.preg_quote($key, '/').'[ \t]*=.*$/m', $env)) {
        return preg_replace('/^'.preg_quote($key, '/').'[ \t]*=.*$/m', $line, $env) ?? $env;
    }

    return rtrim($env).PHP_EOL.$line.PHP_EOL;
}

function aptoria_write_env(string $envPath, string $env): void
{
    $current = is_file($envPath) ? (string) @file_get_contents($envPath) : null;

    if ($current === $env) {
        return;
    }

    if (@file_put_contents($envPath, $env) === false) {
        aptoria_render_preflight_page(
            'Cannot update .env',
            'The first-run installer could not write .env. Check write permissions for the project root.',
            [],
            500
        );
    }
}

function aptoria_absolute_path(string $basePath, string $path): string
{
    $normalized = str_replace('\\', '/', $path);

    if (preg_match('~^(?:[A-Za-z]:/|//|/)~', $normalized)) {
        return $path;
    }

    return $basePath.'/'.ltrim($path, '/');
}

function aptoria_detect_app_url(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return '';
    }

    $https = (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    $scheme = $https ? 'https' : 'http';
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = rtrim(str_replace('/public/index.php', '', $script), '/');
    $base = rtrim(str_replace('/index.php', '', $base), '/');

    return $scheme.'://'.$host.$base;
}

function aptoria_current_url_without_query(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $uri = strtok($uri, '?') ?: $uri;

    return $uri;
}


function aptoria_is_local_request(): bool
{
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = explode(':', $host)[0] ?? $host;

    return in_array($remote, ['127.0.0.1', '::1'], true)
        || in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)
        || str_ends_with($host, '.localhost');
}

function aptoria_setup_token_value(string $basePath, bool $createIfMissing = true): string
{
    $envPath = $basePath.'/.env';
    if (is_file($envPath)) {
        $env = (string) file_get_contents($envPath);
        $envToken = aptoria_env_value($env, 'APTORIA_SETUP_TOKEN');
        if ($envToken !== '') {
            return $envToken;
        }
    }

    $tokenPath = $basePath.'/storage/app/setup-token.txt';
    if (is_file($tokenPath)) {
        return trim((string) file_get_contents($tokenPath));
    }

    if (! $createIfMissing) {
        return '';
    }

    $directory = dirname($tokenPath);
    if (! is_dir($directory)) {
        @mkdir($directory, 0775, true);
    }

    $token = bin2hex(random_bytes(24));
    @file_put_contents($tokenPath, $token.PHP_EOL);

    return is_file($tokenPath) ? $token : '';
}

function aptoria_web_installer_authorized(string $basePath): bool
{
    if (aptoria_is_local_request()) {
        return true;
    }

    $submitted = (string) ($_GET['setup_token'] ?? $_POST['setup_token'] ?? '');
    if ($submitted === '') {
        return false;
    }

    $expected = aptoria_setup_token_value($basePath, true);

    return $expected !== '' && hash_equals($expected, $submitted);
}

/** @return array<int, array{title:string, commands:array<int, string>, note?:string}> */
function aptoria_install_sections(string $basePath): array
{
    $isWindows = PHP_OS_FAMILY === 'Windows';

    $linuxCommands = [
        'cd '.escapeshellarg($basePath),
        'chmod +x scripts/install-linux.sh',
        'bash scripts/install-linux.sh',
    ];

    $composerPharCommands = [
        'cd '.escapeshellarg($basePath),
        'php -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');"',
        'php composer-setup.php --install-dir=. --filename=composer.phar',
        'rm -f composer-setup.php',
        'COMPOSER_BIN="php composer.phar" bash scripts/install-linux.sh',
    ];

    $windowsPath = str_replace('/', '\\', $basePath);
    $windowsCommands = [
        'cd "'.$windowsPath.'"',
        'Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass',
        '.\\scripts\\install-windows-xampp.ps1',
        'C:\\xampp\\php\\php.exe artisan serve',
    ];

    if ($isWindows) {
        return [
            [
                'title' => 'Windows / XAMPP',
                'commands' => $windowsCommands,
                'note' => 'Use this only on a local Windows/XAMPP machine.',
            ],
            [
                'title' => 'Online Linux hosting / VPS / cPanel SSH',
                'commands' => $linuxCommands,
                'note' => 'Use this on the live web server. Composer must be available on the server.',
            ],
        ];
    }

    return [
        [
            'title' => 'Online Linux hosting / VPS / cPanel SSH',
            'commands' => $linuxCommands,
            'note' => 'Use this on primafarm.hu or any normal Linux web host. This is the correct block for online hosting.',
        ],
        [
            'title' => 'Online hosting without global Composer',
            'commands' => $composerPharCommands,
            'note' => 'Use this if the composer command is not available but SSH can run PHP commands.',
        ],
    ];
}

/** @return array{success:bool, output:string, command:string} */
function aptoria_try_web_dependency_install(string $basePath): array
{
    if (! function_exists('proc_open')) {
        return [
            'success' => false,
            'command' => 'proc_open unavailable',
            'output' => 'The server disabled proc_open, so the web installer cannot run Composer automatically. Use the SSH command block instead, or ask the hosting provider to enable Composer for this account.',
        ];
    }

    $command = aptoria_detect_web_composer_command($basePath);
    if ($command === null) {
        return [
            'success' => false,
            'command' => 'composer not found',
            'output' => 'Composer was not found on the server. Use the "Online hosting without global Composer" SSH block, or install dependencies locally and upload vendor/ as a server deployment package. The standard release ZIP still does not include vendor/.',
        ];
    }

    $fullCommand = 'cd '.escapeshellarg($basePath).' && '.$command.' install --no-dev --no-interaction --prefer-dist --optimize-autoloader 2>&1';

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = @proc_open($fullCommand, $descriptors, $pipes, $basePath);
    if (! is_resource($process)) {
        return [
            'success' => false,
            'command' => $fullCommand,
            'output' => 'The server could not start the Composer process. Use SSH or cPanel Terminal instead.',
        ];
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'success' => $exitCode === 0 && is_file($basePath.'/vendor/autoload.php'),
        'command' => $fullCommand,
        'output' => trim($output) !== '' ? trim($output) : 'No output returned.',
    ];
}

function aptoria_detect_web_composer_command(string $basePath): ?string
{
    $candidates = [
        'composer',
        '/usr/local/bin/composer',
        '/opt/cpanel/composer/bin/composer',
    ];

    foreach ($candidates as $candidate) {
        if (aptoria_command_exists($candidate)) {
            return escapeshellcmd($candidate);
        }
    }

    if (is_file($basePath.'/composer.phar')) {
        return escapeshellcmd(PHP_BINARY).' '.escapeshellarg($basePath.'/composer.phar');
    }

    return null;
}

function aptoria_command_exists(string $command): bool
{
    if (str_contains($command, '/')) {
        return is_file($command) && is_executable($command);
    }

    if (! function_exists('shell_exec')) {
        return false;
    }

    $result = @shell_exec('command -v '.escapeshellarg($command).' 2>/dev/null');

    return is_string($result) && trim($result) !== '';
}

/**
 * @param array<int, array{title:string, commands:array<int, string>, note?:string}> $sections
 * @param array{success:bool, output:string, command:string}|null $installLog
 */
function aptoria_render_preflight_page(string $title, string $message, array $sections, int $status, bool $installAttempted = false, ?array $installLog = null): void
{
    http_response_code($status);
    $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $escapedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $isWindows = PHP_OS_FAMILY === 'Windows';
    $environmentLabel = $isWindows ? 'Windows/XAMPP detected' : 'Online/Linux hosting detected';

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Aptoria first-run setup</title>';
    echo '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">';
    echo '<style>body{font-family:Roboto,Arial,sans-serif;background:#f3f5f7;margin:0;color:#263238}.wrap{max-width:980px;margin:48px auto;padding:0 20px}.card{background:#fff;border:1px solid #dfe4ea;border-radius:6px;padding:28px;box-shadow:0 1px 2px rgba(0,0,0,.04)}h1{margin-top:0;color:#1f2d3d}.badge{display:inline-block;background:#2f80ed;color:#fff;border-radius:3px;padding:5px 9px;font-size:12px;font-weight:bold;margin-bottom:12px}.env{display:inline-block;background:#e8f5e9;color:#1b5e20;border-radius:3px;padding:5px 9px;font-size:12px;font-weight:bold;margin-left:8px}pre{background:#101820;color:#e8f0fe;padding:18px;border-radius:4px;overflow:auto;white-space:pre-wrap}.muted{color:#607d8b}.warn{background:#fff4df;border-left:4px solid #f0ad4e;padding:12px;margin:16px 0}.ok{background:#e8f5e9;border-left:4px solid #4caf50;padding:12px;margin:16px 0}.bad{background:#ffebee;border-left:4px solid #e53935;padding:12px;margin:16px 0}.section{margin-top:18px}.btn{border:0;border-radius:4px;background:#2f80ed;color:#fff;font-weight:bold;padding:11px 16px;cursor:pointer}.btn:hover{background:#1d6fd0}.small{font-size:12px}</style>';
    echo '</head><body><div class="wrap"><div class="card"><span class="badge">Aptoria first-run installer</span><span class="env">'.htmlspecialchars($environmentLabel, ENT_QUOTES, 'UTF-8').'</span>';
    echo '<h1>'.$escapedTitle.'</h1><p>'.$escapedMessage.'</p>';
    echo '<div class="warn"><strong>Release rule:</strong> vendor/ is not included in Aptoria release ZIP files. Dependencies must be installed on the target server once. On online hosting, use the Linux/SSH block, not XAMPP.</div>';

    if (! $isWindows) {
        if (aptoria_web_installer_authorized(dirname(__DIR__))) {
            $tokenField = htmlspecialchars((string) ($_GET['setup_token'] ?? $_POST['setup_token'] ?? ''), ENT_QUOTES, 'UTF-8');
            echo '<form method="POST" class="section"><input type="hidden" name="aptoria_action" value="install_dependencies"><input type="hidden" name="setup_token" value="'.$tokenField.'"><button class="btn" type="submit">Try automatic online dependency install</button> <span class="muted small">Works only if the hosting allows running Composer from PHP.</span></form>';
        } else {
            aptoria_setup_token_value(dirname(__DIR__), true);
            echo '<div class="warn"><strong>Automatic browser install is locked.</strong> Use SSH/cPanel Terminal, or open this page with <code>?setup_token=YOUR_TOKEN</code>. If APTORIA_SETUP_TOKEN is not set in .env, the generated token is stored in <code>storage/app/setup-token.txt</code>.</div>';
        }
    }

    if ($installAttempted && $installLog !== null) {
        $class = $installLog['success'] ? 'ok' : 'bad';
        echo '<div class="'.$class.'"><strong>Automatic install '.($installLog['success'] ? 'succeeded' : 'failed').'.</strong><br><small>Command: '.htmlspecialchars($installLog['command'], ENT_QUOTES, 'UTF-8').'</small><pre>'.htmlspecialchars($installLog['output'], ENT_QUOTES, 'UTF-8').'</pre></div>';
    }

    if ($sections !== []) {
        echo '<p class="muted">Run the matching command block, then refresh this page:</p>';
        foreach ($sections as $section) {
            echo '<div class="section"><h3>'.htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8').'</h3>';
            if (($section['note'] ?? '') !== '') {
                echo '<p class="muted small">'.htmlspecialchars((string) $section['note'], ENT_QUOTES, 'UTF-8').'</p>';
            }
            echo '<pre>'.htmlspecialchars(implode(PHP_EOL, $section['commands']), ENT_QUOTES, 'UTF-8').'</pre></div>';
        }
    }

    echo '<p class="muted">After dependencies and runtime files are ready, all non-setup URLs redirect to /setup until setup is locked.</p>';
    echo '</div></div></body></html>';
    exit;
}
