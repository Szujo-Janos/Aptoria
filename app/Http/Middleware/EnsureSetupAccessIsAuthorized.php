<?php

namespace App\Http\Middleware;

use App\Services\EnvironmentCheckService;
use App\Services\SetupAccessService;
use App\Services\SetupStateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSetupAccessIsAuthorized
{
    public function __construct(
        private readonly SetupAccessService $setupAccess,
        private readonly SetupStateService $setupState,
        private readonly EnvironmentCheckService $environmentCheck,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->setupState->isLocked()) {
            if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
                return redirect()->route(auth()->check() ? 'dashboard' : 'login')
                    ->with('status', __('messages.setup.locked_redirect'));
            }

            abort(403, __('messages.setup.locked'));
        }

        if ($this->setupAccess->authorizeRequest($request)) {
            if (($request->isMethod('GET') || $request->isMethod('HEAD')) && $request->query->has('setup_token')) {
                return redirect()->to($request->fullUrlWithoutQuery(['setup_token']));
            }

            return $next($request);
        }

        $this->setupAccess->configuredToken(true);

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return response()->view('setup.denied', [
                'access' => $this->setupAccess->accessContext($request),
                'checks' => $this->environmentCheck->report()['checks'],
            ], 403);
        }

        abort(403, __('messages.setup.access_denied_title'));
    }
}
