<?php

namespace App\Services\Settings;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class SettingsRuntimeService
{
    public function __construct(private readonly SettingService $settings)
    {
    }

    public function dashboardRangeDays(): int
    {
        return max(1, min(365, $this->settings->integer('app.default_dashboard_range_days', 30)));
    }

    public function dateFormat(): string
    {
        $format = $this->settings->string('app.date_format', 'Y-m-d H:i');

        return in_array($format, ['Y-m-d H:i', 'd.m.Y H:i', 'm/d/Y H:i'], true) ? $format : 'Y-m-d H:i';
    }

    public function timezone(): string
    {
        return $this->settings->string('app.timezone', config('app.timezone', 'UTC'));
    }

    public function formatDate(mixed $date): string
    {
        if ($date === null || $date === '') {
            return 'n/a';
        }

        try {
            $carbon = $date instanceof Carbon ? $date : Carbon::parse((string) $date);
            return $carbon->timezone($this->timezone())->format($this->dateFormat());
        } catch (\Throwable) {
            return (string) $date;
        }
    }

    /** @return array<string, string> */
    public function landingRoutes(): array
    {
        return [
            'dashboard' => 'dashboard',
            'projects' => 'projects.index',
            'reports' => 'reports.index',
            'release_readiness' => 'release-readiness.index',
        ];
    }

    public function defaultLandingRouteName(): string
    {
        $route = $this->landingRoutes()[$this->settings->string('app.default_landing_page', 'dashboard')] ?? 'dashboard';

        return Route::has($route) ? $route : 'dashboard';
    }

    /** @return array<string, string> */
    public function projectViewRoutes(): array
    {
        return [
            'overview' => 'projects.show',
            'endpoints' => 'projects.endpoints.index',
            'scans' => 'projects.scans.index',
            'qa_evidence' => 'projects.qa-evidence.index',
            'release_readiness' => 'projects.release-gates.index',
            'calendar' => 'projects.calendar.index',
        ];
    }

    public function defaultProjectViewRouteName(): string
    {
        $route = $this->projectViewRoutes()[$this->settings->string('app.default_project_view', 'overview')] ?? 'projects.show';

        return Route::has($route) ? $route : 'projects.show';
    }

    /** @return array<int, string> */
    public function allowedScanMethods(): array
    {
        $methods = array_map('strtoupper', $this->settings->csv('scan.allowed_methods'));
        $methods = array_values(array_intersect($methods, ['GET', 'HEAD', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']));

        if ($methods === []) {
            $methods = ['GET', 'HEAD'];
        }

        if ($this->settings->boolean('probe.safe_methods_only', true)) {
            $methods = array_values(array_intersect($methods, ['GET', 'HEAD']));
        }

        if ($this->settings->boolean('probe.block_destructive_methods', true)) {
            $methods = array_values(array_diff($methods, ['POST', 'PUT', 'PATCH', 'DELETE']));
        }

        return $methods === [] ? ['GET', 'HEAD'] : $methods;
    }

    /** @return array<string, array<string, mixed>> */
    public function scanProfiles(): array
    {
        return [
            'safe' => [
                'label' => 'Safe',
                'enabled' => $this->settings->boolean('scan.profile_safe_enabled', true),
                'mode' => $this->settings->string('scan.default_mode', 'safe'),
                'timeout' => $this->settings->integer('scan.timeout_seconds', 10),
                'rate_limit_ms' => max(250, $this->settings->integer('scan.rate_limit_ms', 250)),
                'max_endpoints' => $this->settings->integer('scan.max_endpoints_per_scan', 100),
            ],
            'staging' => [
                'label' => 'Staging',
                'enabled' => $this->settings->boolean('scan.profile_staging_enabled', true),
                'mode' => 'balanced',
                'timeout' => min(120, max(10, $this->settings->integer('scan.timeout_seconds', 10) + 5)),
                'rate_limit_ms' => $this->settings->integer('scan.rate_limit_ms', 250),
                'max_endpoints' => min(2000, max($this->settings->integer('scan.max_endpoints_per_scan', 100), 250)),
            ],
            'production' => [
                'label' => 'Production',
                'enabled' => $this->settings->boolean('scan.profile_production_enabled', true),
                'mode' => 'production-safe',
                'timeout' => $this->settings->integer('scan.timeout_seconds', 10),
                'rate_limit_ms' => max(500, $this->settings->integer('scan.rate_limit_ms', 250)),
                'max_endpoints' => $this->settings->integer('scan.max_endpoints_per_scan', 100),
            ],
            'aggressive_local' => [
                'label' => 'Aggressive local',
                'enabled' => $this->settings->boolean('scan.profile_aggressive_local_enabled', false),
                'mode' => 'aggressive-local',
                'timeout' => min(120, max(5, $this->settings->integer('scan.timeout_seconds', 10))),
                'rate_limit_ms' => min(100, $this->settings->integer('scan.rate_limit_ms', 250)),
                'max_endpoints' => min(2000, max($this->settings->integer('scan.max_endpoints_per_scan', 100), 500)),
            ],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function enabledScanProfiles(): array
    {
        return array_filter($this->scanProfiles(), fn (array $profile): bool => (bool) ($profile['enabled'] ?? false));
    }

    public function defaultScanProfile(): string
    {
        $default = $this->settings->string('scan.default_profile', 'safe');
        $profiles = $this->enabledScanProfiles();

        if (array_key_exists($default, $profiles)) {
            return $default;
        }

        return array_key_first($profiles) ?: 'safe';
    }

    /** @return array<string, mixed> */
    public function scanProfile(string $profile): array
    {
        $profiles = $this->enabledScanProfiles();

        return $profiles[$profile] ?? $profiles[$this->defaultScanProfile()] ?? $this->scanProfiles()['safe'];
    }

    public function maxConcurrentScans(): int
    {
        return max(1, min(10, $this->settings->integer('scan.max_concurrent_scans', 1)));
    }

    public function requireTypedProductionConfirmation(): bool
    {
        return $this->settings->boolean('probe.require_typed_production_confirmation', true);
    }

    public function productionConfirmationPhrase(): string
    {
        return $this->settings->string('probe.production_confirmation_phrase', 'SCAN PRODUCTION');
    }

}
