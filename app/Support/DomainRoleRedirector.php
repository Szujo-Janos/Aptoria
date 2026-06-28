<?php

namespace App\Support;

use Illuminate\Http\Request;

class DomainRoleRedirector
{
    /** @var list<string> */
    private const PUBLIC_LANDING_PATTERNS = [
        'assets/*',
        'favicon.ico',
        'robots.txt',
        'sitemap.xml',
        'build/*',
        'up',
        'language/*',
        'privacy',
        'terms',
        'demo-guide',
        'demo-guide/*',
    ];

    /** @var list<string> */
    private const LANDING_TO_DEMO_PATTERNS = [
        'login',
        'dashboard',
        'dashboard/*',
        'demo-workspace',
        'demo-workspace/*',
        'projects',
        'projects/*',
        'audit-log',
        'audit-log/*',
        'help',
        'help/*',
        'profile',
        'profile/*',
        'demo-api',
        'demo-api/*',
        'client-portal',
        'client-portal/*',
        'modules',
        'modules/*',
    ];

    /** @var list<string> */
    private const LANDING_TO_ADMIN_PATTERNS = [
        'setup',
        'setup/*',
        'program-settings',
        'program-settings/*',
        'users',
        'users/*',
        'runtime-diagnostics',
        'runtime-diagnostics.json',
        'deployment-readiness',
        'deployment-readiness.json',
        'subdomain-deployment',
        'subdomain-deployment/*',
        'subdomain-deployment.json',
    ];

    /** @var list<string> */
    private const LICENSE_PATTERNS = [
        'license',
        'license/*',
        'api/license',
        'api/license/*',
    ];

    /** @var list<string> */
    private const DEMO_GUIDE_PATTERNS = [
        'demo-guide',
        'demo-guide/*',
        'projects/*/demo-guide',
    ];

    /** @var list<string> */
    private const DEMO_TO_LANDING_PATTERNS = [
        'privacy',
        'terms',
    ];

    /** @var list<string> */
    private const DEMO_TO_ADMIN_PATTERNS = [
        'setup',
        'setup/*',
        'program-settings',
        'program-settings/*',
        'users',
        'users/*',
        'runtime-diagnostics',
        'runtime-diagnostics.json',
        'deployment-readiness',
        'deployment-readiness.json',
        'subdomain-deployment',
        'subdomain-deployment/*',
        'subdomain-deployment.json',
    ];

    public static function unauthenticatedRedirectTo(Request $request): string
    {
        return self::redirectUrl($request) ?? '/login';
    }

    public static function redirectUrl(Request $request): ?string
    {
        $role = self::role($request);

        return match ($role) {
            'landing' => self::landingRedirectUrl($request),
            'demo' => self::demoRedirectUrl($request),
            default => null,
        };
    }

    private static function landingRedirectUrl(Request $request): ?string
    {
        if ($request->path() === '/') {
            return null;
        }

        if (self::matches($request, self::PUBLIC_LANDING_PATTERNS)) {
            return null;
        }

        if (self::matches($request, self::LICENSE_PATTERNS)) {
            return self::roleUrl($request, 'license_url');
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return null;
        }

        if (self::matches($request, self::LANDING_TO_ADMIN_PATTERNS)) {
            return self::roleUrl($request, 'admin_url');
        }

        if (self::matches($request, self::LANDING_TO_DEMO_PATTERNS)) {
            return self::roleUrl($request, 'demo_url');
        }

        return null;
    }

    private static function demoRedirectUrl(Request $request): ?string
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return null;
        }

        if (self::matches($request, self::DEMO_GUIDE_PATTERNS)) {
            return self::landingPath('/demo-guide');
        }

        if (self::matches($request, self::DEMO_TO_LANDING_PATTERNS)) {
            return self::roleUrl($request, 'landing_url');
        }

        if (self::matches($request, self::LICENSE_PATTERNS)) {
            return self::roleUrl($request, 'license_url');
        }

        if (self::matches($request, self::DEMO_TO_ADMIN_PATTERNS)) {
            return self::roleUrl($request, 'admin_url');
        }

        return null;
    }

    /**
     * @param list<string> $patterns
     */
    private static function matches(Request $request, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    private static function roleUrl(Request $request, string $configKey): ?string
    {
        $baseUrl = rtrim((string) config('aptoria.domain.'.$configKey), '/');

        if ($baseUrl === '') {
            return null;
        }

        $path = '/'.ltrim($request->path(), '/');
        $query = $request->getQueryString();

        return $baseUrl.$path.($query ? '?'.$query : '');
    }

    private static function landingPath(string $path): string
    {
        $baseUrl = rtrim((string) config('aptoria.domain.landing_url', 'https://aptoria.dev'), '/');

        if ($baseUrl === '') {
            $baseUrl = 'https://aptoria.dev';
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }

    private static function role(Request $request): string
    {
        $configured = strtolower(trim((string) config('aptoria.domain.role', 'local')));

        if ($configured !== '' && $configured !== 'local') {
            return $configured;
        }

        return self::roleFromHost($request) ?? ($configured !== '' ? $configured : 'local');
    }

    private static function roleFromHost(Request $request): ?string
    {
        $host = strtolower($request->getHost());

        $hostsByRole = [
            'landing' => self::hostFromUrl((string) config('aptoria.domain.landing_url', 'https://aptoria.dev')),
            'demo' => self::hostFromUrl((string) config('aptoria.domain.demo_url', 'https://demo.aptoria.dev')),
            'admin' => self::hostFromUrl((string) config('aptoria.domain.admin_url', 'https://admin.aptoria.dev')),
            'license' => self::hostFromUrl((string) config('aptoria.domain.license_url', 'https://license.aptoria.dev')),
        ];

        foreach ($hostsByRole as $role => $roleHost) {
            if ($roleHost !== null && $host === $roleHost) {
                return $role;
            }
        }

        return match ($host) {
            'aptoria.dev' => 'landing',
            'demo.aptoria.dev' => 'demo',
            'admin.aptoria.dev' => 'admin',
            'license.aptoria.dev' => 'license',
            default => null,
        };
    }

    private static function hostFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }
}
