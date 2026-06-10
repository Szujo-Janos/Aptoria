<?php

namespace App\Services\System;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Models\User;
use App\Services\Database\DatabaseMaintenanceService;
use App\Services\Security\SecurityStatusService;
use App\Services\Setup\SetupStateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemHealthService
{
    public function __construct(
        private readonly DatabaseMaintenanceService $databaseMaintenance,
        private readonly SecurityStatusService $securityStatus,
        private readonly SetupStateService $setupState,
    ) {
    }

    /** @return array<string, mixed> */
    public function report(): array
    {
        $checks = array_merge(
            $this->runtimeChecks(),
            $this->applicationChecks(),
            $this->storageChecks(),
            $this->cacheChecks(),
            $this->databaseChecks(),
            $this->securityChecks(),
            $this->importExportChecks(),
            $this->reportingChecks(),
            $this->maintenanceChecks(),
            $this->automationChecks(),
            $this->queueChecks(),
        );

        return [
            'product' => 'Aptoria',
            'version' => (string) config('aptoria.version'),
            'generated_at' => now()->toIso8601String(),
            'summary' => $this->summarize($checks),
            'categories' => $this->categories($checks),
            'checks' => $checks,
            'system_info' => $this->systemInfo(),
            'next_steps' => $this->nextSteps($checks),
        ];
    }

    /** @return array<string, string> */
    public function systemInfo(): array
    {
        return [
            'Aptoria version' => 'v'.(string) config('aptoria.version'),
            'Laravel version' => app()->version(),
            'PHP version' => PHP_VERSION,
            'APP_ENV' => (string) config('app.env'),
            'APP_DEBUG' => config('app.debug') ? 'true' : 'false',
            'APP_URL' => (string) config('app.url'),
            'Database connection' => (string) config('database.default'),
            'Queue connection' => (string) config('queue.default'),
            'Cache store' => (string) config('cache.default'),
            'Filesystem disk' => (string) config('filesystems.default'),
            'Session driver' => (string) config('session.driver'),
            'Timezone' => (string) config('app.timezone'),
            'Setup lock file' => $this->setupState->hasLockFile() ? 'present' : 'missing',
            'PHP binary' => PHP_BINARY,
            'PHP SAPI' => PHP_SAPI,
            'Loaded php.ini' => php_ini_loaded_file() ?: 'not reported',
            'Operating system' => PHP_OS_FAMILY.' / '.php_uname('s').' '.php_uname('r'),
            'Memory limit' => (string) ini_get('memory_limit'),
            'Max execution time' => (string) ini_get('max_execution_time'),
            'Upload max filesize' => (string) ini_get('upload_max_filesize'),
            'Post max size' => (string) ini_get('post_max_size'),
            'Mail transport' => (string) config('mail.default'),
            'Project root' => base_path(),
            'Public path' => public_path(),
            'Storage path' => storage_path(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function runtimeChecks(): array
    {
        $extensions = ['ctype', 'curl', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'zip'];
        $checks = [
            $this->check(
                'runtime',
                'php_version',
                'PHP runtime version',
                version_compare(PHP_VERSION, '8.2.0', '>='),
                'Running PHP '.PHP_VERSION.'.',
                'Install or select PHP 8.2 or newer.'
            ),
            $this->check(
                'runtime',
                'composer_lock',
                'composer.lock is present',
                is_file(base_path('composer.lock')),
                'composer.lock exists in the project root.',
                'Restore composer.lock from the release package.'
            ),
            $this->check(
                'runtime',
                'vendor_autoload',
                'Composer vendor autoload is installed',
                is_file(base_path('vendor/autoload.php')),
                'vendor/autoload.php exists.',
                'Run composer install through the XAMPP/update script before using the app.'
            ),
        ];

        foreach ($extensions as $extension) {
            $checks[] = $this->check(
                'runtime',
                'php_extension_'.$extension,
                'PHP extension: '.$extension,
                extension_loaded($extension),
                'Loaded.',
                'Enable the '.$extension.' PHP extension.'
            );
        }

        if ((string) config('database.default') === 'sqlite') {
            $checks[] = $this->check(
                'runtime',
                'php_extension_pdo_sqlite',
                'PHP extension: pdo_sqlite',
                extension_loaded('pdo_sqlite'),
                'Loaded for SQLite.',
                'Enable pdo_sqlite in php.ini.'
            );
        }

        $checks[] = $this->check(
            'runtime',
            'memory_limit',
            'PHP memory limit',
            $this->memoryLimitMegabytes() === null || $this->memoryLimitMegabytes() >= 128,
            'memory_limit='.(string) ini_get('memory_limit'),
            'Use at least 128M for reports, imports and larger scans.',
            'warning'
        );

        $checks[] = $this->check(
            'runtime',
            'max_execution_time',
            'PHP max execution time',
            $this->iniSizeMegabytes('max_execution_time') === 0 || (int) ini_get('max_execution_time') >= 30,
            'max_execution_time='.(string) ini_get('max_execution_time'),
            'Use at least 30 seconds for imports, reports and scheduled monitor runs.',
            'warning'
        );

        $checks[] = $this->check(
            'runtime',
            'upload_limit',
            'Upload limit supports evidence and import files',
            $this->iniSizeMegabytes('upload_max_filesize') >= 2 && $this->iniSizeMegabytes('post_max_size') >= 2,
            'upload_max_filesize='.(string) ini_get('upload_max_filesize').', post_max_size='.(string) ini_get('post_max_size'),
            'Use at least 2M for report logos and preferably more for evidence/import files.',
            'warning'
        );

        $checks[] = $this->check(
            'runtime',
            'pdf_renderer',
            'Built-in PDF renderer is available',
            class_exists(\App\Services\Reports\SimplePdfReportRenderer::class),
            'Dependency-free PDF renderer class is available.',
            'Restore app/Services/Reports/SimplePdfReportRenderer.php from the release package.'
        );

        return $checks;
    }

    /** @return array<int, array<string, mixed>> */
    private function applicationChecks(): array
    {
        $timezone = (string) config('app.timezone');
        $knownEnv = in_array((string) config('app.env'), ['local', 'production', 'testing', 'staging'], true);
        $isProduction = app()->environment('production');

        return [
            $this->check(
                'application',
                'app_key',
                'APP_KEY is configured',
                trim((string) config('app.key')) !== '',
                'Application encryption key is configured.',
                'Run php artisan key:generate or use first-run setup.'
            ),
            $this->check(
                'application',
                'app_url',
                'APP_URL is valid',
                filter_var(config('app.url'), FILTER_VALIDATE_URL) !== false,
                'APP_URL='.(string) config('app.url'),
                'Set APP_URL in .env to the real local/server URL.',
                'warning'
            ),
            $this->check(
                'application',
                'app_env_known',
                'APP_ENV is known',
                $knownEnv,
                'APP_ENV='.(string) config('app.env'),
                'Use local, staging, production or testing.',
                'warning'
            ),
            $this->check(
                'application',
                'app_debug',
                'APP_DEBUG is safe for environment',
                ! $isProduction || ! (bool) config('app.debug'),
                'APP_DEBUG='.(config('app.debug') ? 'true' : 'false'),
                'Set APP_DEBUG=false before exposing a production server.',
                'warning'
            ),
            $this->check(
                'application',
                'timezone',
                'Application timezone is valid',
                in_array($timezone, timezone_identifiers_list(), true),
                'Timezone='.$timezone,
                'Set a valid IANA timezone such as Europe/Budapest.'
            ),
            $this->check(
                'application',
                'setup_lock',
                'First-run setup is locked',
                $this->setupState->hasLockFile(),
                'storage/app/installed.lock exists.',
                'Finish setup so normal pages cannot open before installation is complete.'
            ),
            $this->check(
                'application',
                'public_assets',
                'Bundled Aptoria UI vendor assets are present',
                is_dir(public_path('assets/aptoria-ui/vendor')),
                'public/assets/aptoria-ui/vendor exists.',
                'Restore public/assets/aptoria-ui/vendor from the release ZIP.'
            ),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function storageChecks(): array
    {
        $paths = [
            ['storage_root', 'storage/ is writable', storage_path()],
            ['storage_app', 'storage/app is writable', storage_path('app')],
            ['storage_logs', 'storage/logs is writable', storage_path('logs')],
            ['framework_cache', 'storage/framework/cache is writable', storage_path('framework/cache')],
            ['framework_sessions', 'storage/framework/sessions is writable', storage_path('framework/sessions')],
            ['framework_views', 'storage/framework/views is writable', storage_path('framework/views')],
            ['bootstrap_cache', 'bootstrap/cache is writable', base_path('bootstrap/cache')],
        ];

        return array_map(
            fn (array $item): array => $this->check(
                'storage',
                $item[0],
                $item[1],
                is_dir($item[2]) && is_writable($item[2]),
                $item[2].' is writable.',
                'Create the directory and make it writable for the web server/PHP user.'
            ),
            $paths
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function cacheChecks(): array
    {
        $checks = [
            $this->check(
                'cache',
                'cache_store_configured',
                'Cache store is configured',
                trim((string) config('cache.default')) !== '',
                'CACHE_STORE/CACHE_DRIVER='.(string) config('cache.default'),
                'Set a valid cache store in .env.'
            ),
        ];

        try {
            $key = 'aptoria-health-'.str_replace('.', '', uniqid('', true));
            Cache::put($key, 'ok', 60);
            $readBack = Cache::get($key);
            Cache::forget($key);

            $checks[] = $this->check(
                'cache',
                'cache_write_read',
                'Cache write/read/delete probe works',
                $readBack === 'ok',
                $readBack === 'ok' ? 'Temporary cache probe succeeded.' : 'Temporary cache probe did not return the expected value.',
                'Fix cache permissions or switch to a supported cache store.'
            );
        } catch (Throwable $exception) {
            $checks[] = $this->check('cache', 'cache_write_read', 'Cache write/read/delete probe works', false, $exception->getMessage(), 'Fix cache permissions or switch to a supported cache store.');
        }

        return $checks;
    }

    /** @return array<int, array<string, mixed>> */
    private function databaseChecks(): array
    {
        $checks = [
            $this->check(
                'database',
                'connection_name',
                'Database connection name is configured',
                trim((string) config('database.default')) !== '',
                'DB_CONNECTION='.(string) config('database.default'),
                'Set DB_CONNECTION in .env.'
            ),
        ];

        if ((string) config('database.default') === 'sqlite') {
            $databasePath = (string) config('database.connections.sqlite.database');
            $checks[] = $this->check(
                'database',
                'sqlite_file',
                'SQLite database file exists',
                $databasePath === ':memory:' || is_file($databasePath),
                $databasePath,
                'Create database/database.sqlite or run the setup/update script.'
            );
            $checks[] = $this->check(
                'database',
                'sqlite_not_public',
                'SQLite database is outside public assets',
                $databasePath === ':memory:' || ! $this->isInsidePublicPath($databasePath),
                $databasePath === ':memory:' ? ':memory:' : $databasePath,
                'Move the SQLite database outside public/.'
            );
        }

        try {
            DB::connection()->getPdo();
            $checks[] = $this->check('database', 'database_connection', 'Database connection works', true, 'Connected.', '');
        } catch (Throwable $exception) {
            $checks[] = $this->check('database', 'database_connection', 'Database connection works', false, $exception->getMessage(), 'Fix .env database credentials or create the SQLite file.');

            return $checks;
        }

        foreach (['migrations', 'users', 'projects', 'endpoints', 'scan_runs', 'settings'] as $table) {
            try {
                $checks[] = $this->check(
                    'database',
                    'table_'.$table,
                    'Database table exists: '.$table,
                    Schema::hasTable($table),
                    Schema::hasTable($table) ? 'Found.' : 'Missing.',
                    'Run php artisan migrate.'
                );
            } catch (Throwable $exception) {
                $checks[] = $this->check('database', 'table_'.$table, 'Database table exists: '.$table, false, $exception->getMessage(), 'Run php artisan migrate.');
            }
        }

        try {
            $summary = $this->databaseMaintenance->summary();
            $checks[] = $this->check(
                'database',
                'database_summary',
                'Database summary can be read',
                true,
                $summary['table_count'].' tables / '.$summary['row_count'].' rows / schema '.substr((string) $summary['schema_hash'], 0, 16),
                ''
            );
        } catch (Throwable $exception) {
            $checks[] = $this->check('database', 'database_summary', 'Database summary can be read', false, $exception->getMessage(), 'Check database permissions and migrations.');
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            $checks[] = $this->sqliteForeignKeyCheck();
        }

        return $checks;
    }

    /** @return array<int, array<string, mixed>> */
    private function securityChecks(): array
    {
        try {
            return collect($this->securityStatus->checks())
                ->map(fn (array $check): array => [
                    'category' => 'security',
                    'key' => 'security_'.strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) $check['label'])),
                    'label' => (string) $check['label'],
                    'status' => (string) $check['status'],
                    'css' => (string) $check['css'],
                    'detail' => (string) $check['detail'],
                    'fix' => $check['status'] === 'ok' ? '' : 'Review Security status in Settings or adjust the related .env/settings value.',
                ])
                ->all();
        } catch (Throwable $exception) {
            return [
                $this->check('security', 'security_status_service', 'Security status checks can be read', false, $exception->getMessage(), 'Fix the security status service error.'),
            ];
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function importExportChecks(): array
    {
        $checks = [];

        try {
            $payload = $this->databaseMaintenance->exportPayload();
            $checks[] = $this->check(
                'import_export',
                'database_export_payload',
                'Database export payload can be generated',
                ($payload['type'] ?? '') === DatabaseMaintenanceService::EXPORT_TYPE,
                'Full database export payload is available.',
                'Check DatabaseMaintenanceService and database permissions.'
            );
        } catch (Throwable $exception) {
            $checks[] = $this->check('import_export', 'database_export_payload', 'Database export payload can be generated', false, $exception->getMessage(), 'Check DatabaseMaintenanceService and database permissions.');
        }

        $checks[] = $this->pathWritableCheck(
            'import_export',
            'storage_exports_writable',
            'Export storage target is writable',
            storage_path('app/exports'),
            'Create storage/app/exports or make storage/app writable for generated exports.',
            'warning'
        );

        $checks[] = $this->pathWritableCheck(
            'import_export',
            'storage_imports_writable',
            'Import staging target is writable',
            storage_path('app/imports'),
            'Create storage/app/imports or make storage/app writable for uploaded/imported files.',
            'warning'
        );

        $checks[] = $this->check(
            'import_export',
            'temporary_upload_directory',
            'PHP temporary upload directory is writable',
            is_dir(sys_get_temp_dir()) && is_writable(sys_get_temp_dir()),
            sys_get_temp_dir(),
            'Fix the PHP temporary directory permissions used for uploads.'
        );

        $checks[] = $this->check(
            'import_export',
            'newman_import_support',
            'Newman JSON/JUnit import prerequisites',
            extension_loaded('json') && extension_loaded('xml'),
            'json='.(extension_loaded('json') ? 'loaded' : 'missing').', xml='.(extension_loaded('xml') ? 'loaded' : 'missing'),
            'Enable json and xml PHP extensions for Newman JSON/JUnit import.'
        );

        return $checks;
    }

    /** @return array<int, array<string, mixed>> */
    private function reportingChecks(): array
    {
        $domPdfAvailable = class_exists('Dompdf\\Dompdf') || class_exists('Barryvdh\\DomPDF\\Facade\\Pdf');

        return [
            $this->check(
                'reporting',
                'simple_pdf_renderer',
                'Built-in PDF renderer is available',
                class_exists(\App\Services\Reports\SimplePdfReportRenderer::class),
                'Dependency-free PDF renderer class is available.',
                'Restore app/Services/Reports/SimplePdfReportRenderer.php from the release package.'
            ),
            $this->info(
                'reporting',
                'dompdf_status',
                'DOMPDF availability',
                $domPdfAvailable ? 'DOMPDF is installed and can be used by custom deployments.' : 'DOMPDF is not installed; Aptoria will use the built-in PDF renderer.'
            ),
            $this->pathWritableCheck(
                'reporting',
                'report_output_writable',
                'Report output storage is writable',
                storage_path('app/reports'),
                'Create storage/app/reports or make storage/app writable for report output.',
                'warning'
            ),
            $this->pathWritableCheck(
                'reporting',
                'report_logo_storage_writable',
                'Project report logo storage is writable',
                storage_path('app/private/report-logos'),
                'Create storage/app/private/report-logos or make storage/app/private writable.',
                'warning'
            ),
            $this->pathWritableCheck(
                'reporting',
                'finding_evidence_storage_writable',
                'Finding evidence attachment storage is writable',
                storage_path('app/private/finding-evidence'),
                'Create storage/app/private/finding-evidence or make storage/app/private writable.',
                'warning'
            ),
            $this->check(
                'reporting',
                'report_views_present',
                'Report presentation templates are present',
                is_file(resource_path('views/reports/html.blade.php')) || is_file(resource_path('views/reports/partials/readiness-endpoint-table.blade.php')),
                'Report Blade templates were found.',
                'Restore resources/views/reports from the release package.'
            ),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function maintenanceChecks(): array
    {
        $checks = [];

        $checks[] = $this->check(
            'maintenance',
            'version_file',
            'VERSION file is present',
            is_file(base_path('VERSION')) && trim((string) file_get_contents(base_path('VERSION'))) !== '',
            is_file(base_path('VERSION')) ? 'VERSION='.trim((string) file_get_contents(base_path('VERSION'))) : 'Missing.',
            'Restore VERSION from the release package.'
        );

        $checks[] = $this->check(
            'maintenance',
            'readme_changelog_policy',
            'README references CHANGELOG.md',
            is_file(base_path('README.md')) && str_contains((string) file_get_contents(base_path('README.md')), 'CHANGELOG.md'),
            'README points to CHANGELOG.md for release history.',
            'Keep release notes in CHANGELOG.md and reference it from README.'
        );

        return $checks;
    }

    /** @return array<int, array<string, mixed>> */
    private function automationChecks(): array
    {
        $consoleRoutes = is_file(base_path('routes/console.php')) ? (string) file_get_contents(base_path('routes/console.php')) : '';

        return [
            $this->check(
                'automation',
                'monitor_command_registered',
                'Scheduled monitor command is registered',
                str_contains($consoleRoutes, 'aptoria:run-monitors'),
                'aptoria:run-monitors command is declared.',
                'Restore routes/console.php monitor command definition.',
                'warning'
            ),
            $this->check(
                'automation',
                'health_command_registered',
                'System health command is registered',
                str_contains($consoleRoutes, 'aptoria:health'),
                'aptoria:health command is declared.',
                'Restore routes/console.php health command definition.',
                'warning'
            ),
            $this->check(
                'automation',
                'queue_connection_configured',
                'Queue connection is configured',
                trim((string) config('queue.default')) !== '',
                'QUEUE_CONNECTION='.(string) config('queue.default'),
                'Set QUEUE_CONNECTION in .env.',
                'warning'
            ),
            $this->check(
                'automation',
                'mail_mailer_configured',
                'Mail transport is configured',
                trim((string) config('mail.default')) !== '',
                'MAIL_MAILER='.(string) config('mail.default'),
                'Set MAIL_MAILER before enabling email alerts.',
                'info'
            ),
            $this->check(
                'automation',
                'task_scheduler_note',
                'Scheduler hook reminder',
                true,
                'Scheduled monitors require php artisan schedule:run from Windows Task Scheduler or cron.',
                '',
                'info'
            ),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function queueChecks(): array
    {
        $queue = (string) config('queue.default');
        $checks = [
            $this->check(
                'queue',
                'queue_connection_configured',
                'Queue connection is configured',
                trim($queue) !== '',
                'QUEUE_CONNECTION='.$queue,
                'Set QUEUE_CONNECTION in .env.',
                'warning'
            ),
        ];

        if ($queue === 'database') {
            foreach (['jobs', (string) config('queue.failed.table', 'failed_jobs')] as $table) {
                try {
                    $checks[] = $this->check(
                        'queue',
                        'queue_table_'.$table,
                        'Queue table exists: '.$table,
                        Schema::hasTable($table),
                        Schema::hasTable($table) ? 'Found.' : 'Missing.',
                        'Run php artisan queue:table / queue:failed-table and migrate, or switch queue connection.'
                    );
                } catch (Throwable $exception) {
                    $checks[] = $this->check('queue', 'queue_table_'.$table, 'Queue table exists: '.$table, false, $exception->getMessage(), 'Check database connection and migrations.', 'warning');
                }
            }
        }

        if ($queue === 'sync') {
            $checks[] = $this->info(
                'queue',
                'sync_queue_note',
                'Synchronous queue mode',
                'QUEUE_CONNECTION=sync runs notification jobs inline. This is fine for local/XAMPP use; use database/redis for heavier server workloads.'
            );
        }

        return $checks;
    }

    /** @param array<int, array<string, mixed>> $checks @return array<string, mixed> */
    private function summarize(array $checks): array
    {
        $failed = collect($checks)->where('status', 'fail')->count();
        $warnings = collect($checks)->where('status', 'warning')->count();
        $info = collect($checks)->where('status', 'info')->count();
        $ok = collect($checks)->where('status', 'ok')->count();
        $total = count($checks);
        $score = $total > 0 ? (int) round((($ok + ($info * 0.5)) / $total) * 100) : 0;

        return [
            'status' => $failed > 0 ? 'fail' : ($warnings > 0 ? 'warning' : 'ok'),
            'css' => $failed > 0 ? 'danger' : ($warnings > 0 ? 'warning' : 'success'),
            'label' => $failed > 0 ? 'Needs attention' : ($warnings > 0 ? 'Warnings only' : 'Ready'),
            'ok' => $ok,
            'warnings' => $warnings,
            'failed' => $failed,
            'info' => $info,
            'total' => $total,
            'score' => max(0, min(100, $score)),
            'can_continue' => $failed === 0,
        ];
    }

    /** @param array<int, array<string, mixed>> $checks @return array<string, array<string, mixed>> */
    private function categories(array $checks): array
    {
        $labels = [
            'runtime' => 'Runtime',
            'application' => 'Application',
            'storage' => 'Storage & permissions',
            'cache' => 'Cache',
            'database' => 'Database',
            'security' => 'Security posture',
            'import_export' => 'Import & export',
            'reporting' => 'Reporting & evidence',
            'maintenance' => 'Maintenance',
            'automation' => 'Automation',
            'queue' => 'Queue',
        ];

        $categories = [];
        foreach ($labels as $key => $label) {
            $items = collect($checks)->where('category', $key)->values()->all();
            $summary = $this->summarize($items);
            $categories[$key] = [
                'key' => $key,
                'label' => $label,
                'summary' => $summary,
                'checks' => $items,
            ];
        }

        return $categories;
    }

    /** @param array<int, array<string, mixed>> $checks @return array<int, array<string, string>> */
    private function nextSteps(array $checks): array
    {
        return collect($checks)
            ->filter(fn (array $check): bool => in_array($check['status'], ['fail', 'warning'], true) && trim((string) ($check['fix'] ?? '')) !== '')
            ->map(fn (array $check): array => [
                'status' => (string) $check['status'],
                'label' => (string) $check['label'],
                'fix' => (string) $check['fix'],
            ])
            ->take(8)
            ->values()
            ->all();
    }

    private function pathWritableCheck(string $category, string $key, string $label, string $path, string $fix, string $severity = 'fail'): array
    {
        $exists = is_dir($path);
        $target = $exists ? $path : dirname($path);
        $writable = is_dir($target) && is_writable($target);
        $detail = $exists
            ? $path.($writable ? ' is writable.' : ' is not writable.')
            : $path.' does not exist; parent '.dirname($path).($writable ? ' is writable.' : ' is not writable.');

        return $this->check($category, $key, $label, $writable, $detail, $fix, $severity);
    }

    private function info(string $category, string $key, string $label, string $detail): array
    {
        return [
            'category' => $category,
            'key' => $key,
            'label' => $label,
            'status' => 'info',
            'css' => 'info',
            'detail' => $detail,
            'fix' => '',
        ];
    }

    private function check(string $category, string $key, string $label, bool $passes, string $detail, string $fix, string $severity = 'fail'): array
    {
        $status = $passes ? ($severity === 'info' ? 'info' : 'ok') : ($severity === 'info' ? 'warning' : $severity);

        return [
            'category' => $category,
            'key' => $key,
            'label' => $label,
            'status' => $status,
            'css' => match ($status) {
                'ok' => 'success',
                'warning' => 'warning',
                'info' => 'info',
                default => 'danger',
            },
            'detail' => $detail,
            'fix' => $passes ? '' : $fix,
        ];
    }

    private function sqliteForeignKeyCheck(): array
    {
        try {
            $violations = DB::select('PRAGMA foreign_key_check');

            return $this->check(
                'database',
                'sqlite_foreign_key_integrity',
                'SQLite foreign key integrity',
                $violations === [],
                $violations === [] ? 'No foreign key violations detected.' : count($violations).' violations detected.',
                'Export data, inspect broken references and restore from a clean backup if needed.'
            );
        } catch (Throwable $exception) {
            return $this->check('database', 'sqlite_foreign_key_integrity', 'SQLite foreign key integrity', false, $exception->getMessage(), 'Check SQLite support and database permissions.', 'warning');
        }
    }

    private function isInsidePublicPath(string $path): bool
    {
        $realPath = realpath($path) ?: $path;
        $publicPath = realpath(public_path()) ?: public_path();

        return str_starts_with(str_replace('\\', '/', $realPath), str_replace('\\', '/', $publicPath));
    }

    private function iniSizeMegabytes(string $key): int
    {
        $value = trim((string) ini_get($key));
        if ($value === '' || $value === '-1') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return match ($unit) {
            'g' => (int) ceil($number * 1024),
            'm' => (int) ceil($number),
            'k' => max(1, (int) ceil($number / 1024)),
            default => (int) ceil($number),
        };
    }

    private function memoryLimitMegabytes(): ?int
    {
        $value = trim((string) ini_get('memory_limit'));
        if ($value === '' || $value === '-1') {
            return null;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024,
            'k' => max(1, (int) ceil($number / 1024)),
            default => $number,
        };
    }
}
