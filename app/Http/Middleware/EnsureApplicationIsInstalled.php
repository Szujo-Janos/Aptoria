<?php

namespace App\Http\Middleware;

use App\Services\SetupStateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationIsInstalled
{
    public function __construct(private readonly SetupStateService $setupState)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isLandingOnlyRuntime()) {
            return $next($request);
        }

        if ($this->setupState->canUseApplication()) {
            return $next($request);
        }

        if ($this->isSetupRequest($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Aptoria setup must be completed and locked before using the application.',
                'setup_url' => url('/setup'),
            ], 503);
        }

        return redirect()->route('setup.index')
            ->with('warning', __('messages.setup.auto_redirect_notice'));
    }

    private function isSetupRequest(Request $request): bool
    {
        return $request->is('setup') || $request->is('setup/*') || $request->is('language/*') || $request->is('up');
    }

    private function isLandingOnlyRuntime(): bool
    {
        return strtolower((string) config('aptoria.domain.role', 'local')) === 'landing';
    }
}
