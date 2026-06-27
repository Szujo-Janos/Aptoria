<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class DeploymentReadinessService
{
    public function __construct(
        private readonly EnvironmentCheckService $environmentCheck,
        private readonly HostingProfileDiagnosticsService $hostingDiagnostics,
        private readonly SetupStateService $setupState,
        private readonly SubdomainSmokeResultService $subdomainSmokeResults,
    ) {
    }

    /** @return array<string,mixed> */
    public function run(string $mode = 'runtime'): array
    {
        $mode = in_array($mode, ['runtime', 'installer'], true) ? $mode : 'runtime';
        $checks = [];
        $stages = [];

        $this->stage($stages, 'runtime_files', 'Runtime files', $this->runtimeFileChecks($mode));
        $this->stage($stages, 'environment', 'Server environment', $this->environmentChecks());
        $this->stage($stages, 'hosting_profile', 'Hosting profile', $this->hostingProfileChecks($mode));
        $this->stage($stages, 'database', 'Database and migrations', $this->databaseChecks($mode));
        $this->stage($stages, 'license', 'License authority readiness', $this->licenseChecks());
        $this->stage($stages, 'demo', 'Public demo readiness', $this->demoChecks());
        $this->stage($stages, 'deployment_tools', 'Deployment tools', $this->deploymentToolChecks());
        $this->stage($stages, 'subdomain_smoke', 'Subdomain smoke results', $this->subdomainSmokeChecks());

        foreach ($stages as $stage) {
            foreach ($stage['checks'] as $check) {
                $checks[] = $check;
            }
        }

        $summary = $this->summary($checks);
        $blocking = array_values(array_filter($checks, fn (array $check): bool => ($check['status'] ?? '') === 'error'));
        $warnings = array_values(array_filter($checks, fn (array $check): bool => ($check['status'] ?? '') === 'warning'));
        $score = $this->score($summary);

        return [
            'deployment_readiness_format' => 'aptoria-deployment-readiness-v1',
            'generated_at' => now()->toIso8601String(),
            'version' => (string) config('aptoria.version'),
            'mode' => $mode,
            'role' => $this->role(),
            'app_url' => (string) config('app.url', ''),
            'status' => $summary['errors'] > 0 ? 'error' : ($summary['warnings'] > 0 ? 'warning' : 'ok'),
            'score' => $score,
            'release_blocked' => $summary['errors'] > 0,
            'install_blocked' => $this->installBlocked($checks),
            'summary' => $summary,
            'blocking_checks' => array_slice($blocking, 0, 8),
            'warning_checks' => array_slice($warnings, 0, 8),
            'stages' => $stages,
            'checks' => $checks,
            'next_steps' => $this->nextSteps($summary, $mode),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function runtimeFileChecks(string $mode): array
    {
        return [
            $this->check('env_file_present', 'error', File::exists(base_path('.env')) || $mode === 'installer', '.env runtime configuration is present or can be created by the installer.', 'Copy a hosting profile to .env before deployment, or let the installer create .env from .env.example.', File::exists(base_path('.env')) ? '.env exists' : '.env missing'),
            $this->check('env_example_present', 'error', File::exists(base_path('.env.example')), '.env.example is available as fallback.', 'Restore .env.example before running the guided installer.', File::exists(base_path('.env.example')) ? '.env.example exists' : '.env.example missing'),
            $this->check('app_key_ready', $mode === 'installer' ? 'warning' : 'error', trim((string) config('app.key', '')) !== '' || $this->envHasApplicationKey() || $mode === 'installer', 'APP_KEY is ready or will be generated during installer preflight.', 'Run php artisan key:generate --force after applying the correct hosting profile.', $this->mask((string) config('app.key', ''))),
            $this->check('storage_app_not_public', 'error', ! File::exists(public_path('storage/app')), 'Private storage is not exposed below public/storage/app.', 'Remove public exposure for storage/app and keep runtime state outside the public web root.', File::exists(public_path('storage/app')) ? 'public/storage/app exists' : 'not exposed'),
            $this->check('forbidden_runtime_files_not_in_public_root', 'error', ! File::exists(public_path('.env')), '.env is not exposed in the public document root.', 'Move the document root to public/ and keep .env outside web access.', File::exists(public_path('.env')) ? 'public/.env exists' : 'not exposed'),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function environmentChecks(): array
    {
        $report = $this->environmentCheck->report();
        $checks = [];
        foreach ($report['checks'] ?? [] as $item) {
            $status = match ($item['status'] ?? 'warning') {
                'ok', 'info' => 'pass',
                'failed' => 'error',
                default => 'warning',
            };
            $checks[] = $this->rawCheck(
                'environment_'.($item['key'] ?? 'check'),
                $status === 'error' ? 'error' : ($status === 'warning' ? 'warning' : 'info'),
                $status,
                (string) ($item['label'] ?? 'Environment check'),
                (string) ($item['fix'] ?? 'Review the environment check.'),
                (string) ($item['detail'] ?? '')
            );
        }

        return $checks;
    }

    /** @return list<array<string,mixed>> */
    private function hostingProfileChecks(string $mode): array
    {
        $diagnostics = $this->hostingDiagnostics->run();
        $checks = [];
        foreach (($diagnostics['checks'] ?? []) as $item) {
            $id = (string) ($item['id'] ?? 'hosting_check');
            $severity = (string) ($item['severity'] ?? 'warning');
            $passed = (bool) ($item['passed'] ?? false);

            if ($mode === 'installer' && in_array($id, ['app_key_configured'], true)) {
                $severity = 'warning';
            }

            $checks[] = $this->rawCheck(
                'hosting_'.$id,
                $severity === 'error' ? 'error' : ($severity === 'info' ? 'info' : 'warning'),
                $passed ? 'pass' : ($severity === 'error' ? 'error' : ($severity === 'info' ? 'info' : 'warning')),
                (string) ($item['message'] ?? $id),
                (string) ($item['remediation'] ?? 'Review hosting profile configuration.'),
                (string) ($item['actual'] ?? '')
            );
        }

        return $checks;
    }

    /** @return list<array<string,mixed>> */
    private function databaseChecks(string $mode): array
    {
        $default = (string) config('database.default', '');
        $checks = [
            $this->check('database_connection_configured', 'error', $default !== '', 'Database connection is configured.', 'Set DB_CONNECTION and the matching database credentials in .env.', $default === '' ? '(empty)' : $default),
            $this->check('users_table_ready', $mode === 'installer' ? 'info' : 'warning', $this->hasTable('users') || $mode === 'installer', 'Users table exists or will be created by installer migrations.', 'Run php artisan migrate --force before locking setup.', $this->hasTable('users') ? 'users table exists' : 'users table missing'),
            $this->check('projects_table_ready', $mode === 'installer' ? 'info' : 'warning', $this->hasTable('projects') || $mode === 'installer', 'Projects table exists or will be created by installer migrations.', 'Run php artisan migrate --force before normal application use.', $this->hasTable('projects') ? 'projects table exists' : 'projects table missing'),
        ];

        if ($default === 'sqlite') {
            $path = (string) config('database.connections.sqlite.database', database_path('database.sqlite'));
            $resolved = $this->resolvePath($path, database_path('database.sqlite'));
            $checks[] = $this->check('sqlite_database_file_ready', $mode === 'installer' ? 'warning' : 'error', File::exists($resolved) || $mode === 'installer', 'SQLite database file exists or can be created by the installer.', 'Create database/database.sqlite or let the guided installer create it.', $resolved);
            $checks[] = $this->check('sqlite_parent_writable', 'error', $this->writableOrCreatable(dirname($resolved)), 'SQLite parent directory is writable.', 'Fix permissions for the database directory.', dirname($resolved));
        }

        return $checks;
    }

    /** @return list<array<string,mixed>> */
    private function licenseChecks(): array
    {
        $role = $this->role();
        $required = (bool) config('aptoria.license.required', false);
        $mode = (string) config('aptoria.license.mode', 'local_package');
        $authorityUrl = trim((string) config('aptoria.license.authority.url', ''));
        $authorityPublicKey = trim((string) config('aptoria.license.authority.public_key', ''));
        $authorityPublicKeyPath = (string) config('aptoria.license.authority.public_key_path', storage_path('app/license-authority-public.pem'));
        $checks = [];

        $checks[] = $this->check('license_mode_known', 'warning', in_array($mode, ['local_package', 'hybrid', 'online_authority'], true), 'License mode is recognized.', 'Use local_package, hybrid or online_authority.', $mode);

        if ($role === 'customer') {
            $checks[] = $this->check('customer_license_enforced', 'error', $required, 'Customer/portable runtime enforces licensing.', 'Set APTORIA_LICENSE_REQUIRED=true for guarded customer builds.', $required ? 'true' : 'false');
            $checks[] = $this->check('customer_authority_configured', 'error', $authorityUrl !== '', 'Customer/portable runtime has an authority URL.', 'Set APTORIA_LICENSE_AUTHORITY_URL=https://license.aptoria.dev.', $authorityUrl === '' ? '(empty)' : $authorityUrl);
        } else {
            $checks[] = $this->check('non_customer_license_policy_reviewed', 'info', true, 'Non-customer profile is not required to enforce local runtime licensing.', 'No action required unless this is a guarded portable build.', $role);
        }

        if ($authorityUrl !== '') {
            $checks[] = $this->check('authority_url_https', 'warning', Str::startsWith($authorityUrl, 'https://'), 'License authority URL uses HTTPS.', 'Use HTTPS for the license authority endpoint.', $authorityUrl);
            $checks[] = $this->check('authority_url_points_to_license_host', 'warning', parse_url($authorityUrl, PHP_URL_HOST) === 'license.aptoria.dev', 'License authority URL points to license.aptoria.dev.', 'Keep runtime lease checks on https://license.aptoria.dev.', $authorityUrl);
        }

        if (in_array($mode, ['hybrid', 'online_authority'], true)) {
            $checks[] = $this->check('authority_public_key_available', 'error', $authorityPublicKey !== '' || File::exists($this->resolvePath($authorityPublicKeyPath, storage_path('app/license-authority-public.pem'))), 'Authority public key is configured for lease signature verification.', 'Set APTORIA_LICENSE_AUTHORITY_PUBLIC_KEY or APTORIA_LICENSE_AUTHORITY_PUBLIC_KEY_PATH.', $authorityPublicKey !== '' ? 'inline public key configured' : $authorityPublicKeyPath);
        }

        return $checks;
    }

    /** @return list<array<string,mixed>> */
    private function demoChecks(): array
    {
        $role = $this->role();
        $demoMode = (bool) config('aptoria.demo.mode', false);
        $targets = (array) config('aptoria.demo.allowed_targets', []);
        $viewerReadOnly = (bool) config('aptoria.demo.viewer_read_only', true);

        if ($role !== 'demo') {
            return [
                $this->check('demo_mode_disabled_outside_demo_role', 'warning', ! $demoMode, 'Demo mode is disabled outside the public demo role.', 'Set APTORIA_DEMO_MODE=false outside demo.aptoria.dev.', $demoMode ? 'true' : 'false'),
            ];
        }

        return [
            $this->check('demo_mode_enabled', 'error', $demoMode, 'Demo role has APTORIA_DEMO_MODE enabled.', 'Set APTORIA_DEMO_MODE=true for demo.aptoria.dev.', $demoMode ? 'true' : 'false'),
            $this->check('demo_allowed_targets_not_empty', 'error', count(array_filter($targets)) > 0, 'Public demo has a non-empty safe-scan target allowlist.', 'Set APTORIA_DEMO_ALLOWED_TARGETS=demo.aptoria.dev.', implode(',', $targets)),
            $this->check('demo_viewer_read_only_enabled', 'warning', $viewerReadOnly, 'Public demo viewer read-only guard is enabled.', 'Keep APTORIA_DEMO_VIEWER_READ_ONLY=true on public demo.', $viewerReadOnly ? 'true' : 'false'),
        ];
    }

    /** @return list<array<string,mixed>> */
    private function deploymentToolChecks(): array
    {
        $required = [
            'scripts/smoke-subdomains.ps1',
            'scripts/smoke-subdomains.sh',
            'scripts/check-release-zip.ps1',
            'scripts/validate-hosting-profile.ps1',
            'scripts/validate-hosting-profile.sh',
            'docs/HOSTING_CHECKLIST.md',
            'docs/DEPLOYMENT_SMOKE_TESTS.md',
        ];

        $checks = [];
        foreach ($required as $path) {
            $checks[] = $this->check('deployment_tool_'.Str::slug($path, '_'), 'warning', File::exists(base_path($path)), $path.' is present.', 'Restore the deployment smoke/readiness tooling before release.', $path);
        }

        $checks[] = $this->check('setup_lock_state_known', 'info', true, 'Setup lock state was read successfully.', 'Lock setup after installation and before normal use.', $this->setupState->isInstalled() ? 'installed.lock present' : 'setup open');

        return $checks;
    }

    /** @return list<array<string,mixed>> */
    private function subdomainSmokeChecks(): array
    {
        $latest = $this->subdomainSmokeResults->latest();
        $freshness = $this->subdomainSmokeResults->freshness($latest);

        if (! $latest) {
            return [
                $this->check('subdomain_smoke_result_imported', 'warning', false, 'A subdomain smoke result has been imported.', 'Run scripts/smoke-subdomains with JSON output, then import it on the Subdomain deployment dashboard.', 'missing'),
            ];
        }

        $summary = $latest['summary'] ?? ['failed' => 0, 'passed' => 0, 'total' => 0];
        $domains = $this->subdomainSmokeResults->dashboard()['domains'] ?? [];
        $checks = [
            $this->check('subdomain_smoke_result_imported', 'info', true, 'A subdomain smoke result has been imported.', 'No action required.', (string) ($latest['generated_at'] ?? 'imported')),
            $this->check('subdomain_smoke_result_successful', 'error', (int) ($summary['failed'] ?? 0) === 0, 'Latest subdomain smoke result passed.', 'Fix failed host boundary checks before public deployment.', ((int) ($summary['passed'] ?? 0)).' passed / '.((int) ($summary['failed'] ?? 0)).' failed'),
            $this->check('subdomain_smoke_result_fresh', 'warning', ($freshness['status'] ?? '') === 'fresh', 'Latest subdomain smoke result is fresh.', 'Rerun smoke tests after DNS, HTTPS, vhost or deploy changes.', (string) ($freshness['message'] ?? 'unknown')),
        ];

        foreach (['landing', 'demo', 'admin', 'license'] as $domain) {
            $domainStatus = (string) ($domains[$domain]['status'] ?? 'missing');
            $checks[] = $this->check(
                'subdomain_smoke_'.$domain.'_boundary',
                $domainStatus === 'failed' ? 'error' : 'warning',
                $domainStatus === 'passed',
                ucfirst($domain).' subdomain boundary checks passed.',
                'Run smoke-subdomains and verify '.$domain.' host exposes only the intended routes.',
                $domainStatus
            );
        }

        return $checks;
    }

    /** @param list<array<string,mixed>> $stages @param list<array<string,mixed>> $checks */
    private function stage(array &$stages, string $id, string $title, array $checks): void
    {
        $summary = $this->summary($checks);
        $stages[] = [
            'id' => $id,
            'title' => $title,
            'status' => $summary['errors'] > 0 ? 'error' : ($summary['warnings'] > 0 ? 'warning' : 'ok'),
            'tone' => $summary['errors'] > 0 ? 'danger' : ($summary['warnings'] > 0 ? 'warning' : 'success'),
            'summary' => $summary,
            'checks' => $checks,
        ];
    }

    private function check(string $id, string $severity, bool $passed, string $message, string $remediation, string $actual): array
    {
        return $this->rawCheck($id, $severity, $passed ? 'pass' : $severity, $message, $remediation, $actual);
    }

    private function rawCheck(string $id, string $severity, string $status, string $message, string $remediation, string $actual): array
    {
        return [
            'id' => $id,
            'severity' => $severity,
            'passed' => $status === 'pass' || $status === 'info',
            'status' => $status,
            'message' => $message,
            'actual' => $actual,
            'remediation' => $remediation,
        ];
    }

    /** @param list<array<string,mixed>> $checks @return array<string,int> */
    private function summary(array $checks): array
    {
        $summary = ['passed' => 0, 'warnings' => 0, 'errors' => 0, 'info' => 0, 'total' => count($checks)];
        foreach ($checks as $check) {
            $status = (string) ($check['status'] ?? 'warning');
            if ($status === 'pass') {
                $summary['passed']++;
            } elseif ($status === 'error') {
                $summary['errors']++;
            } elseif ($status === 'info') {
                $summary['info']++;
            } else {
                $summary['warnings']++;
            }
        }

        return $summary;
    }

    /** @param array<string,int> $summary */
    private function score(array $summary): int
    {
        $total = max(1, (int) ($summary['total'] ?? 1));
        $penalty = ((int) ($summary['errors'] ?? 0) * 18) + ((int) ($summary['warnings'] ?? 0) * 6);

        return max(0, min(100, 100 - (int) round(($penalty / $total) * 4)));
    }

    /** @param list<array<string,mixed>> $checks */
    private function installBlocked(array $checks): bool
    {
        foreach ($checks as $check) {
            $id = (string) ($check['id'] ?? '');
            if (($check['status'] ?? '') === 'error' && str_starts_with($id, 'environment_')) {
                return true;
            }
            if (in_array($id, ['env_example_present', 'sqlite_parent_writable', 'forbidden_runtime_files_not_in_public_root'], true) && ($check['status'] ?? '') === 'error') {
                return true;
            }
        }

        return false;
    }

    /** @param array<string,int> $summary @return list<string> */
    private function nextSteps(array $summary, string $mode): array
    {
        if (($summary['errors'] ?? 0) > 0) {
            return [
                'Fix error-level checks before public deployment.',
                'Run php artisan aptoria:deployment-preflight --json to inspect machine-readable details.',
                'Run the subdomain smoke scripts after DNS and HTTPS are configured.',
            ];
        }

        if (($summary['warnings'] ?? 0) > 0) {
            return [
                'Review warnings before release.',
                $mode === 'installer' ? 'Finish the guided installer, then rerun preflight in runtime mode.' : 'Use --strict when you want warnings to fail CI or manual release checks.',
                'Run scripts/check-release-zip.ps1 before publishing the ZIP.',
            ];
        }

        return [
            'Deployment readiness looks clean.',
            'Run smoke-subdomains after deployment to verify host boundaries.',
            'Keep runtime/private files out of the release ZIP.',
        ];
    }

    private function role(): string
    {
        $role = strtolower(trim((string) config('aptoria.domain.role', 'local')));

        return $role !== '' ? $role : 'local';
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }

    private function writableOrCreatable(string $path): bool
    {
        if (is_dir($path)) {
            return is_writable($path);
        }

        $parent = dirname($path);

        return is_dir($parent) ? is_writable($parent) : is_writable(dirname($parent));
    }

    private function resolvePath(string $path, string $fallback): string
    {
        $path = trim($path) !== '' ? $path : $fallback;
        $normalized = str_replace('\\', '/', $path);
        if (str_starts_with($normalized, '/') || preg_match('~^[A-Za-z]:[\\/]~', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private function envHasApplicationKey(): bool
    {
        $env = base_path('.env');
        if (! is_file($env)) {
            return false;
        }

        $contents = (string) file_get_contents($env);
        if (preg_match('/^APP_KEY\s*=\s*(.*)$/m', $contents, $matches) !== 1) {
            return false;
        }

        return trim((string) $matches[1]) !== '';
    }

    private function mask(string $value): string
    {
        if (trim($value) === '') {
            return '(empty)';
        }

        return substr($value, 0, 8).'…';
    }
}
