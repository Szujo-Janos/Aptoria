<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceDomainRole
{
    /** @var list<string> */
    private array $publicAssetPatterns = [
        'assets/*',
        'favicon.ico',
        'robots.txt',
        'build/*',
        'up',
        'language/*',
    ];

    /** @var list<string> */
    private array $demoBlockedPatterns = [
        'setup',
        'setup/*',
        'license',
        'license/*',
        'program-settings',
        'program-settings/*',
        'users',
        'users/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $role = $this->role();

        if ($role === 'landing') {
            $this->enforceLandingOnly($request);
        }

        if ($role === 'demo') {
            $this->enforceDemoOnly($request);
        }

        return $next($request);
    }

    private function enforceLandingOnly(Request $request): void
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            abort(404);
        }

        if ($request->path() === '/') {
            return;
        }

        foreach ($this->publicAssetPatterns as $pattern) {
            if ($request->is($pattern)) {
                return;
            }
        }

        abort(404);
    }

    private function enforceDemoOnly(Request $request): void
    {
        foreach ($this->demoBlockedPatterns as $pattern) {
            if ($request->is($pattern)) {
                abort(403, __('messages.demo_mode.blocked_action'));
            }
        }
    }

    private function role(): string
    {
        $role = strtolower(trim((string) config('aptoria.domain.role', 'local')));

        return $role !== '' ? $role : 'local';
    }
}
