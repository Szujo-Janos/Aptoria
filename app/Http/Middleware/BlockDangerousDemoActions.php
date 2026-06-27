<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockDangerousDemoActions
{
    /** @var list<string> */
    private array $blockedRoutePrefixes = [
        'users.',
        'program-settings.',
        'projects.client-portal.',
        'projects.members.',
    ];

    /** @var list<string> */
    private array $blockedRouteNames = [
        'profile.update',
        'profile.password.update',
        'program-settings.update',
        'projects.store',
        'projects.update',
        'projects.destroy',
        'projects.settings.update',
        'projects.import-center.store',
        'projects.import-center.apply',
        'projects.import-center.undo',
    ];

    /** @var list<string> */
    private array $blockedRouteSuffixes = [
        '.destroy',
        '.delete',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('aptoria.demo.mode', false)) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        if ($routeName === '' || in_array($routeName, ['logout', 'login.store'], true)) {
            return $next($request);
        }

        if ($this->isBlocked($routeName, $request->method())) {
            abort(403, __('messages.demo_mode.blocked_action'));
        }

        return $next($request);
    }

    private function isBlocked(string $routeName, string $method): bool
    {
        if (in_array($routeName, $this->blockedRouteNames, true)) {
            return true;
        }

        foreach ($this->blockedRoutePrefixes as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        foreach ($this->blockedRouteSuffixes as $suffix) {
            if (str_ends_with($routeName, $suffix)) {
                return true;
            }
        }

        if (str_starts_with($routeName, 'projects.') && strtoupper($method) === 'DELETE') {
            return true;
        }

        if (str_starts_with($routeName, 'projects.import-center.') && strtoupper($method) !== 'GET') {
            return true;
        }

        return false;
    }
}
