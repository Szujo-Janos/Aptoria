<?php

namespace App\Services\Setup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class EnvironmentCheckService
{
    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $checks = array_merge(
            $this->phpChecks(),
            $this->fileChecks(),
            $this->applicationChecks(),
            $this->databaseChecks(),
            $this->productionChecks(),
        );

        return [
            'checks' => $checks,
            'summary' => $this->summarize($checks),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function phpChecks(): array
    {
        $requiredExtensions = ['ctype', 'curl', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml'];
        $checks = [
            $this->check(
                'php_version',
                'PHP >= 8.2',
                version_compare(PHP_VERSION, '8.2.0', '>='),
                'Running PHP '.PHP_VERSION,
                'Install PHP 8.2 or newer.'
            ),
        ];

        foreach ($requiredExtensions as $extension) {
            $checks[] = $this->check(
                'php_ext_'.$extension,
                'PHP extension: '.$extension,
                extension_loaded($extension),
                extension_loaded($extension) ? 'Loaded' : 'Missing',
                'Enable or install the '.$extension.' PHP extension.'
            );
        }

        if ((string) config('database.default') === 'sqlite') {
            $checks[] = $this->check(
                'php_ext_pdo_sqlite',
                'PHP extension: pdo_sqlite',
                extension_loaded('pdo_sqlite'),
                extension_loaded('pdo_sqlite') ? 'Loaded' : 'Missing',
                'Enable pdo_sqlite for the default local SQLite setup.'
            );
        }

        return $checks;
    }

    /** @return array<int, array<string, mixed>> */
    private function fileChecks(): array
    {
        $paths = [
            ['env_example', '.env.example exists', base_path('.env.example'), 'file', 'The release ZIP must include .env.example.'],
            ['env', '.env exists', base_path('.env'), 'file', 'Create .env from .env.example or run the installer script.'],
            ['artisan', 'artisan exists', base_path('artisan'), 'file', 'The project root must contain artisan.'],
            ['composer_json', 'composer.json exists', base_path('composer.json'), 'file', 'The project root must contain composer.json.'],
            ['vendor_autoload', 'vendor/autoload.php exists', base_path('vendor/autoload.php'), 'file', 'Run composer install.'],
            ['storage', 'storage/ is writable', storage_path(), 'dir_writable', 'Make storage writable by the web server user.'],
            ['storage_logs', 'storage/logs is writable', storage_path('logs'), 'dir_writable', 'Create storage/logs and make it writable.'],
            ['bootstrap_cache', 'bootstrap/cache is writable', base_path('bootstrap/cache'), 'dir_writable', 'Create bootstrap/cache and make it writable.'],
        ];

        $checks = [];
        foreach ($paths as [$key, $label, $path, $type, $fix]) {
            $ok = match ($type) {
                'file' => is_file($path),
                'dir_writable' => is_dir($path) && is_writable($path),
                default => false,
            };

            $checks[] = $this->check($key, $label, $ok, $path, $fix);
        }

        return $checks;
    }

    /** @return array<int, array<string, mixed>> */
    private function applicationChecks(): array
    {
        $appKey = (string) config('app.key');
        $checks = [
            $this->check(
                'app_key',
                'APP_KEY is configured',
                trim($appKey) !== '',
                trim($appKey) !== '' ? 'Configured' : 'Missing',
                'Run php artisan key:generate or use the setup action.'
            ),
            $this->check(
                'app_url',
                'APP_URL is configured',
                filter_var(config('app.url'), FILTER_VALIDATE_URL) !== false,
                (string) config('app.url'),
                'Set APP_URL in .env.'
            ),
        ];

        return $checks;
    }

    /** @return array<int, array<string, mixed>> */
    private function databaseChecks(): array
    {
        $checks = [];
        $connection = (string) config('database.default');

        $checks[] = $this->check(
            'db_connection_name',
            'Database connection configured',
            $connection !== '',
            $connection !== '' ? $connection : 'Missing',
            'Set DB_CONNECTION in .env.'
        );

        if ($connection === 'sqlite') {
            $database = (string) config('database.connections.sqlite.database');
            $checks[] = $this->check(
                'sqlite_file',
                'SQLite database file exists',
                $database === ':memory:' || is_file($database),
                $database,
                'Create database/database.sqlite or run the installer script.'
            );
        }

        try {
            DB::connection()->getPdo();
            $checks[] = $this->check('db_connection', 'Database connection works', true, 'Connected', '');
        } catch (Throwable $exception) {
            $checks[] = $this->check('db_connection', 'Database connection works', false, $exception->getMessage(), 'Fix database credentials or create the SQLite file.');
        }

        try {
            $checks[] = $this->check(
                'migrations_table',
                'Migrations table exists',
                Schema::hasTable('migrations'),
                Schema::hasTable('migrations') ? 'Found' : 'Missing',
                'Run php artisan migrate --seed.'
            );
        } catch (Throwable $exception) {
            $checks[] = $this->check('migrations_table', 'Migrations table exists', false, $exception->getMessage(), 'Run migrations after database is ready.');
        }

        try {
            $checks[] = $this->check(
                'users_table',
                'Users table exists',
                Schema::hasTable('users'),
                Schema::hasTable('users') ? 'Found' : 'Missing',
                'Run php artisan migrate --seed.'
            );
        } catch (Throwable $exception) {
            $checks[] = $this->check('users_table', 'Users table exists', false, $exception->getMessage(), 'Run migrations after database is ready.');
        }

        return $checks;
    }

    /** @return array<int, array<string, mixed>> */
    private function productionChecks(): array
    {
        $checks = [];
        $isProduction = app()->environment('production');
        $appUrl = (string) config('app.url');

        $checks[] = $this->check(
            'app_env',
            'APP_ENV mode',
            ! $isProduction || config('app.debug') === false,
            'APP_ENV='.config('app.env').', APP_DEBUG='.(config('app.debug') ? 'true' : 'false'),
            'For production, set APP_DEBUG=false.',
            $isProduction && config('app.debug') ? 'warning' : 'ok'
        );

        $checks[] = $this->check(
            'app_url_https',
            'Production APP_URL uses HTTPS',
            ! $isProduction || str_starts_with(strtolower($appUrl), 'https://'),
            $appUrl !== '' ? $appUrl : 'Missing',
            'For production, set APP_URL to the public HTTPS URL.',
            $isProduction && ! str_starts_with(strtolower($appUrl), 'https://') ? 'warning' : 'ok'
        );

        $checks[] = $this->check(
            'session_secure_cookie',
            'Secure session cookie in production',
            ! $isProduction || (bool) config('session.secure'),
            'SESSION_SECURE_COOKIE='.(config('session.secure') ? 'true' : 'false'),
            'For HTTPS production, set SESSION_SECURE_COOKIE=true.',
            $isProduction && ! (bool) config('session.secure') ? 'warning' : 'ok'
        );

        $checks[] = $this->check(
            'session_http_only',
            'HTTP-only session cookie',
            (bool) config('session.http_only'),
            'SESSION_HTTP_ONLY='.(config('session.http_only') ? 'true' : 'false'),
            'Keep SESSION_HTTP_ONLY=true.',
            ! (bool) config('session.http_only') ? 'warning' : 'ok'
        );

        $checks[] = $this->check(
            'public_webroot_notice',
            'Public webroot reminder',
            true,
            'Apache/Nginx document root should point to the public/ directory online.',
            '',
            'info'
        );

        $checks[] = $this->check(
            'scheduler_notice',
            'Scheduler reminder',
            true,
            'Scheduled monitoring needs a cron/scheduled task for php artisan schedule:run.',
            '',
            'info'
        );

        $checks[] = $this->check(
            'security_audit_command_notice',
            'Security audit command',
            true,
            'Run php artisan aptoria:security-audit before exposing the application.',
            '',
            'info'
        );

        return $checks;
    }

    /**
     * @param array<int, array<string, mixed>> $checks
     * @return array<string, int|bool>
     */
    private function summarize(array $checks): array
    {
        $failed = 0;
        $warnings = 0;
        $ok = 0;
        $info = 0;

        foreach ($checks as $check) {
            if ($check['status'] === 'fail') {
                $failed++;
            } elseif ($check['status'] === 'warning') {
                $warnings++;
            } elseif ($check['status'] === 'info') {
                $info++;
            } else {
                $ok++;
            }
        }

        return [
            'ok' => $ok,
            'warnings' => $warnings,
            'failed' => $failed,
            'info' => $info,
            'can_continue' => $failed === 0,
        ];
    }

    /** @return array<string, mixed> */
    private function check(string $key, string $label, bool $passes, string $detail, string $fix, string $statusWhenPass = 'ok'): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'status' => $passes ? $statusWhenPass : 'fail',
            'detail' => $detail,
            'fix' => $fix,
        ];
    }
}
