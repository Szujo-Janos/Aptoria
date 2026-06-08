<?php

namespace App\Services\Security;

use App\Models\User;
use App\Services\Setup\SetupStateService;
use App\Services\Settings\SettingService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SecurityStatusService
{
    public function __construct(
        private readonly SetupStateService $setupState,
        private readonly SetupAccessService $setupAccess,
        private readonly SettingService $settings,
    ) {
    }

    /** @return array<int, array{label:string,status:string,css:string,detail:string}> */
    public function checks(): array
    {
        $checks = [
            $this->check('APP_KEY', config('app.key') !== null && trim((string) config('app.key')) !== '', 'Application encryption key is configured.', 'APP_KEY is missing or empty.'),
            $this->check('APP_DEBUG', ! $this->settings->boolean('security.warn_app_debug_enabled', true) || ! (bool) config('app.debug'), 'Debug mode is disabled.', 'Debug mode is enabled. Use APP_DEBUG=false on servers.'),
            $this->check('APP_ENV', ! $this->settings->boolean('security.warn_unknown_app_env', true) || app()->environment('production') || app()->environment('local') || app()->environment('testing'), 'Known application environment: '.app()->environment().'.', 'Unexpected APP_ENV value: '.app()->environment().'.'),
            $this->check('Setup lock', ! $this->settings->boolean('security.require_setup_lock_before_public_access', true) || $this->setupState->hasLockFile(), 'storage/app/installed.lock exists.', 'Setup is not locked. Finish setup before exposing the app.'),
            $this->check('Setup token strength', $this->setupTokenConfigured(), 'A strong setup token is configured or generated.', 'No strong setup token exists. Non-local setup should be protected with a long random token.'),
            $this->check('Default admin password', ! $this->usesDefaultAdminPassword(), 'Default admin password was not detected.', 'Default admin password appears to be active. Change it immediately.'),
            $this->check('Storage writable', is_writable(storage_path()), 'Storage directory is writable.', 'Storage directory is not writable.'),
            $this->check('Bootstrap cache writable', is_writable(base_path('bootstrap/cache')), 'Bootstrap cache directory is writable.', 'bootstrap/cache is not writable.'),
            $this->check('SQLite file excluded', ! is_file(database_path('database.sqlite')) || ! $this->isLikelyPublicPath(database_path('database.sqlite')), 'SQLite database is outside public web assets.', 'SQLite database appears to be under a public path.'),
        ];

        return array_merge($checks, $this->productionChecks());
    }

    /** @param array<int, array{status:string}> $checks */
    public function summary(array $checks): array
    {
        $warnings = collect($checks)->where('status', 'warning')->count();
        $failed = collect($checks)->where('status', 'fail')->count();

        $warningsFail = $warnings > 0 && (
            $this->settings->boolean('security.audit_strict_mode', false)
            || $this->settings->boolean('security.audit_fail_on_warnings', false)
        );

        return [
            'status' => ($failed > 0 || $warningsFail) ? 'fail' : ($warnings > 0 ? 'warning' : 'ok'),
            'css' => ($failed > 0 || $warningsFail) ? 'danger' : ($warnings > 0 ? 'warning' : 'success'),
            'warnings' => $warnings,
            'failed' => $failed,
            'total' => count($checks),
        ];
    }

    private function check(string $label, bool $ok, string $okDetail, string $failDetail, string $severity = 'fail'): array
    {
        return [
            'label' => $label,
            'status' => $ok ? 'ok' : $severity,
            'css' => $ok ? 'success' : ($severity === 'warning' ? 'warning' : 'danger'),
            'detail' => $ok ? $okDetail : $failDetail,
        ];
    }

    /** @return array<int, array{label:string,status:string,css:string,detail:string}> */
    private function productionChecks(): array
    {
        $isProduction = app()->environment('production');
        $appUrl = (string) config('app.url');

        return [
            $this->check(
                'Production APP_URL HTTPS',
                ! $isProduction || str_starts_with(strtolower($appUrl), 'https://'),
                'Production APP_URL uses HTTPS or the app is not in production mode.',
                'APP_ENV=production should use an HTTPS APP_URL.',
                'warning'
            ),
            $this->check(
                'Secure session cookie',
                ! $isProduction || (bool) config('session.secure'),
                'Secure session cookies are enabled or the app is not in production mode.',
                'SESSION_SECURE_COOKIE should be true in production.',
                'warning'
            ),
            $this->check(
                'HTTP-only session cookie',
                (bool) config('session.http_only'),
                'Session cookie is HTTP-only.',
                'SESSION_HTTP_ONLY should stay true.',
                'warning'
            ),
            $this->check(
                'Session SameSite policy',
                in_array(strtolower((string) config('session.same_site')), ['lax', 'strict'], true),
                'Session SameSite policy is lax or strict.',
                'SESSION_SAME_SITE should be lax or strict unless a specific integration needs otherwise.',
                'warning'
            ),
            $this->check(
                'Production log level',
                ! $isProduction || ! in_array(strtolower((string) config('logging.level', env('LOG_LEVEL', 'debug'))), ['debug'], true),
                'Production log level is not debug or the app is not in production mode.',
                'LOG_LEVEL should not be debug in production.',
                'warning'
            ),
        ];
    }

    private function setupTokenConfigured(): bool
    {
        $envToken = trim((string) env('APTORIA_SETUP_TOKEN', ''));
        if ($envToken !== '') {
            return $this->setupAccess->isUsableToken($envToken);
        }

        if (! is_file($this->setupAccess->tokenPath())) {
            return false;
        }

        return $this->setupAccess->isUsableToken((string) file_get_contents($this->setupAccess->tokenPath()));
    }

    private function usesDefaultAdminPassword(): bool
    {
        try {
            if (! Schema::hasTable('users')) {
                return false;
            }

            $email = config('aptoria.default_admin.email', 'admin@example.com');
            $password = config('aptoria.default_admin.password', 'change-me-now');
            $user = User::query()->where('email', $email)->first();

            return $user instanceof User && Hash::check((string) $password, (string) $user->password);
        } catch (Throwable) {
            return false;
        }
    }

    private function isLikelyPublicPath(string $path): bool
    {
        $realPath = realpath($path) ?: $path;
        $publicPath = realpath(public_path()) ?: public_path();

        return str_starts_with(str_replace('\\', '/', $realPath), str_replace('\\', '/', $publicPath));
    }
}
