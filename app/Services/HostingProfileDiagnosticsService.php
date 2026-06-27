<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class HostingProfileDiagnosticsService
{
    /** @var list<string> */
    private array $supportedRoles = ['local', 'landing', 'demo', 'customer'];

    public function run(): array
    {
        $checks = [];
        $role = $this->domainRole();
        $appUrl = (string) config('app.url', '');
        $appEnv = (string) config('app.env', 'production');
        $appDebug = (bool) config('app.debug', false);
        $licenseRequired = (bool) config('aptoria.license.required', false);
        $licenseMode = (string) config('aptoria.license.mode', 'local_package');
        $authorityUrl = trim((string) config('aptoria.license.authority.url', ''));
        $demoMode = (bool) config('aptoria.demo.mode', false);
        $demoTargets = (array) config('aptoria.demo.allowed_targets', []);
        $sessionDomain = trim((string) config('session.domain', ''));

        $this->addCheck($checks, 'domain_role_supported', 'error', in_array($role, $this->supportedRoles, true), 'APTORIA_DOMAIN_ROLE uses a supported value.', 'Use one of: '.implode(', ', $this->supportedRoles).'.', $role);
        $this->addCheck($checks, 'app_key_configured', 'error', trim((string) config('app.key', '')) !== '', 'APP_KEY is configured.', 'Generate an app key before deployment: php artisan key:generate.', $this->masked((string) config('app.key', '')));
        $this->addCheck($checks, 'storage_writable', 'error', is_writable(storage_path()), 'Laravel storage is writable.', 'Fix filesystem permissions for storage/ and bootstrap/cache/.', storage_path());
        $this->addCheck($checks, 'debug_disabled_for_public_runtime', 'error', ! $this->isPublicRole($role) || ! $appDebug, 'APP_DEBUG is disabled for public/runtime roles.', 'Set APP_DEBUG=false for landing, demo and customer/portable deployments.', $appDebug ? 'true' : 'false');
        $this->addCheck($checks, 'https_for_public_app_url', 'warning', ! $this->isHostedPublicRole($role) || Str::startsWith($appUrl, 'https://'), 'APP_URL uses HTTPS for hosted public roles.', 'Use HTTPS for aptoria.dev and demo.aptoria.dev.', $appUrl);
        $this->addCheck($checks, 'session_domain_not_shared', 'warning', $sessionDomain === '' || ! Str::startsWith($sessionDomain, '.'), 'SESSION_DOMAIN is not shared across all aptoria.dev subdomains.', 'Keep SESSION_DOMAIN empty unless cookie sharing is intentionally required.', $sessionDomain === '' ? '(empty)' : $sessionDomain);

        if ((string) config('database.default') === 'sqlite') {
            $database = (string) config('database.connections.sqlite.database', database_path('database.sqlite'));
            $databaseDir = dirname($database);
            $this->addCheck($checks, 'sqlite_directory_writable', 'warning', is_dir($databaseDir) && is_writable($databaseDir), 'SQLite database directory is writable.', 'Create the database directory and make it writable, or configure a server database.', $databaseDir);
        }

        if ($role === 'landing') {
            $this->addCheck($checks, 'landing_license_not_required', 'error', ! $licenseRequired, 'Landing role does not enforce local runtime licensing.', 'Set APTORIA_LICENSE_REQUIRED=false for the public landing profile.', $licenseRequired ? 'true' : 'false');
            $this->addCheck($checks, 'landing_demo_mode_disabled', 'error', ! $demoMode, 'Landing role is not running public demo mode.', 'Set APTORIA_DEMO_MODE=false for aptoria.dev.', $demoMode ? 'true' : 'false');
            $this->addCheck($checks, 'landing_app_url_matches_role', 'warning', $this->hostContains($appUrl, 'aptoria.dev') && ! $this->hostContains($appUrl, 'demo.aptoria.dev'), 'Landing APP_URL points at aptoria.dev.', 'Use APP_URL=https://aptoria.dev for the landing profile.', $appUrl);
        }

        if ($role === 'demo') {
            $this->addCheck($checks, 'demo_mode_enabled', 'error', $demoMode, 'Demo role has APTORIA_DEMO_MODE enabled.', 'Set APTORIA_DEMO_MODE=true for demo.aptoria.dev.', $demoMode ? 'true' : 'false');
            $this->addCheck($checks, 'demo_allowed_targets_present', 'error', count(array_filter($demoTargets)) > 0, 'Demo allowed target allowlist is not empty.', 'Set APTORIA_DEMO_ALLOWED_TARGETS=demo.aptoria.dev. Empty allowlists must fail closed.', implode(',', $demoTargets));
            $this->addCheck($checks, 'demo_viewer_read_only', 'warning', (bool) config('aptoria.demo.viewer_read_only', true), 'Demo viewer read-only protection is enabled.', 'Keep APTORIA_DEMO_VIEWER_READ_ONLY=true on public demo deployments.', (bool) config('aptoria.demo.viewer_read_only', true) ? 'true' : 'false');
            $this->addCheck($checks, 'demo_license_not_required', 'warning', ! $licenseRequired, 'Public demo does not require customer runtime activation.', 'Set APTORIA_LICENSE_REQUIRED=false for public demo.', $licenseRequired ? 'true' : 'false');
            $this->addCheck($checks, 'demo_app_url_matches_role', 'warning', $this->hostContains($appUrl, 'demo.aptoria.dev'), 'Demo APP_URL points at demo.aptoria.dev.', 'Use APP_URL=https://demo.aptoria.dev for the demo profile.', $appUrl);
        }

        if ($role === 'customer') {
            $this->addCheck($checks, 'customer_license_required', 'error', $licenseRequired, 'Customer/portable role enforces licensing.', 'Set APTORIA_LICENSE_REQUIRED=true for guarded customer/portable builds.', $licenseRequired ? 'true' : 'false');
            $this->addCheck($checks, 'customer_license_mode_online_ready', 'warning', in_array($licenseMode, ['hybrid', 'online_authority', 'local_package'], true), 'Customer license mode is recognized.', 'Use APTORIA_LICENSE_MODE=hybrid for online authority guarded portable builds.', $licenseMode);
            $this->addCheck($checks, 'customer_authority_url_configured', 'error', $authorityUrl !== '', 'Customer runtime has a configured license authority URL.', 'Set APTORIA_LICENSE_AUTHORITY_URL=https://license.aptoria.dev.', $authorityUrl === '' ? '(empty)' : $authorityUrl);
            $this->addCheck($checks, 'customer_authority_url_https', 'warning', $authorityUrl === '' || Str::startsWith($authorityUrl, 'https://'), 'Customer authority URL uses HTTPS.', 'Use HTTPS for the license authority endpoint.', $authorityUrl === '' ? '(empty)' : $authorityUrl);
            $this->addCheck($checks, 'customer_demo_mode_disabled', 'error', ! $demoMode, 'Customer runtime is not accidentally running demo mode.', 'Set APTORIA_DEMO_MODE=false for customer/portable profiles.', $demoMode ? 'true' : 'false');
        }

        if ($role === 'local') {
            $this->addCheck($checks, 'local_runtime_not_public_profile', 'info', true, 'Local profile is intended for development and XAMPP testing.', 'Use landing/demo/customer profiles for hosted deployments.', $role);
        }

        $summary = $this->summary($checks);

        return [
            'diagnostics_format' => 'aptoria-hosting-profile-diagnostics-v1',
            'generated_at' => now()->toIso8601String(),
            'version' => (string) config('aptoria.version'),
            'status' => $summary['errors'] > 0 ? 'error' : ($summary['warnings'] > 0 ? 'warning' : 'ok'),
            'role' => $role,
            'app_env' => $appEnv,
            'app_url' => $appUrl,
            'summary' => $summary,
            'checks' => $checks,
        ];
    }

    /** @param list<array<string, mixed>> $checks */
    private function addCheck(array &$checks, string $id, string $severity, bool $passed, string $message, string $remediation, string $actual): void
    {
        $checks[] = [
            'id' => $id,
            'severity' => $severity,
            'passed' => $passed,
            'status' => $passed ? 'pass' : $severity,
            'message' => $message,
            'actual' => $actual,
            'remediation' => $remediation,
        ];
    }

    /** @param list<array<string, mixed>> $checks */
    private function summary(array $checks): array
    {
        $summary = ['passed' => 0, 'warnings' => 0, 'errors' => 0, 'info' => 0, 'total' => count($checks)];
        foreach ($checks as $check) {
            if ((bool) ($check['passed'] ?? false)) {
                $summary['passed']++;
                continue;
            }

            $severity = (string) ($check['severity'] ?? 'warning');
            if ($severity === 'error') {
                $summary['errors']++;
            } elseif ($severity === 'info') {
                $summary['info']++;
            } else {
                $summary['warnings']++;
            }
        }

        return $summary;
    }

    private function domainRole(): string
    {
        $role = strtolower(trim((string) config('aptoria.domain.role', 'local')));

        return $role !== '' ? $role : 'local';
    }

    private function isPublicRole(string $role): bool
    {
        return in_array($role, ['landing', 'demo', 'customer'], true);
    }

    private function isHostedPublicRole(string $role): bool
    {
        return in_array($role, ['landing', 'demo'], true);
    }

    private function hostContains(string $url, string $expectedHost): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === strtolower($expectedHost);
    }

    private function masked(string $value): string
    {
        if ($value === '') {
            return '(empty)';
        }

        return substr($value, 0, 8).'…';
    }
}
