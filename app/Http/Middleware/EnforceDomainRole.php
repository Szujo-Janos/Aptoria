<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceDomainRole
{
    /** @var list<string> */
    private array $publicAssetPatterns = [
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
    private array $landingToDemoPatterns = [
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
    private array $landingToAdminPatterns = [
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
    private array $licensePatterns = [
        'license',
        'license/*',
        'api/license',
        'api/license/*',
    ];

    /** @var list<string> */
    private array $demoGuidePatterns = [
        'demo-guide',
        'demo-guide/*',
        'projects/*/demo-guide',
    ];

    /** @var list<string> */
    private array $demoToLandingPatterns = [
        'privacy',
        'terms',
    ];

    /** @var list<string> */
    private array $demoBlockedPatterns = [
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

    public function handle(Request $request, Closure $next): Response
    {
        $role = $this->role($request);

        if ($role === 'landing') {
            $redirect = $this->enforceLandingOnly($request);

            if ($redirect instanceof RedirectResponse) {
                return $redirect;
            }
        }

        if ($role === 'demo') {
            $redirect = $this->enforceDemoOnly($request);

            if ($redirect instanceof RedirectResponse) {
                return $redirect;
            }
        }

        return $next($request);
    }

    private function enforceLandingOnly(Request $request): ?RedirectResponse
    {
        if ($request->path() === '/') {
            return null;
        }

        foreach ($this->publicAssetPatterns as $pattern) {
            if ($request->is($pattern)) {
                return null;
            }
        }

        foreach ($this->licensePatterns as $pattern) {
            if ($request->is($pattern)) {
                return $this->redirectToRoleUrl($request, 'license_url');
            }
        }

        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort(404);
        }

        foreach ($this->landingToAdminPatterns as $pattern) {
            if ($request->is($pattern)) {
                return $this->redirectToRoleUrl($request, 'admin_url');
            }
        }

        foreach ($this->landingToDemoPatterns as $pattern) {
            if ($request->is($pattern)) {
                return $this->redirectToRoleUrl($request, 'demo_url');
            }
        }

        abort(404);
    }

    private function enforceDemoOnly(Request $request): ?RedirectResponse
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            foreach ($this->demoGuidePatterns as $pattern) {
                if ($request->is($pattern)) {
                    return $this->redirectToLandingPath('/demo-guide');
                }
            }

            foreach ($this->demoToLandingPatterns as $pattern) {
                if ($request->is($pattern)) {
                    return $this->redirectToRoleUrl($request, 'landing_url');
                }
            }

            foreach ($this->licensePatterns as $pattern) {
                if ($request->is($pattern)) {
                    return $this->redirectToRoleUrl($request, 'license_url');
                }
            }

            foreach ($this->demoBlockedPatterns as $pattern) {
                if ($request->is($pattern)) {
                    return $this->redirectToRoleUrl($request, 'admin_url');
                }
            }
        }

        foreach ($this->demoBlockedPatterns as $pattern) {
            if ($request->is($pattern)) {
                abort(403, __('messages.demo_mode.blocked_action'));
            }
        }

        return null;
    }

    private function redirectToLandingPath(string $path): RedirectResponse
    {
        $baseUrl = rtrim((string) config('aptoria.domain.landing_url', 'https://aptoria.dev'), '/');

        if ($baseUrl === '') {
            $baseUrl = 'https://aptoria.dev';
        }

        return redirect()->away($baseUrl.'/'.ltrim($path, '/'), 302);
    }

    private function redirectToRoleUrl(Request $request, string $configKey): RedirectResponse
    {
        $baseUrl = rtrim((string) config('aptoria.domain.'.$configKey), '/');

        if ($baseUrl === '') {
            abort(404);
        }

        $path = '/'.ltrim($request->path(), '/');
        $query = $request->getQueryString();

        return redirect()->away($baseUrl.$path.($query ? '?'.$query : ''), 302);
    }

    private function role(Request $request): string
    {
        $configured = strtolower(trim((string) config('aptoria.domain.role', 'local')));

        if ($configured !== '' && $configured !== 'local') {
            return $configured;
        }

        return $this->roleFromHost($request) ?? ($configured !== '' ? $configured : 'local');
    }

    private function roleFromHost(Request $request): ?string
    {
        $host = strtolower($request->getHost());

        $hostsByRole = [
            'landing' => $this->hostFromUrl((string) config('aptoria.domain.landing_url', 'https://aptoria.dev')),
            'demo' => $this->hostFromUrl((string) config('aptoria.domain.demo_url', 'https://demo.aptoria.dev')),
            'admin' => $this->hostFromUrl((string) config('aptoria.domain.admin_url', 'https://admin.aptoria.dev')),
            'license' => $this->hostFromUrl((string) config('aptoria.domain.license_url', 'https://license.aptoria.dev')),
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

    private function hostFromUrl(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }
}
