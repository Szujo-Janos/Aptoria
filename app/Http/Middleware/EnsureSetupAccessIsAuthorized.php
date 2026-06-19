<?php

namespace App\Http\Middleware;

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
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->setupState->isLocked()) {
            if ($request->isMethod('GET')) {
                return redirect()->route(auth()->check() ? 'dashboard' : 'login')
                    ->with('status', __('messages.setup.locked_redirect'));
            }

            abort(403, __('messages.setup.locked'));
        }

        if ($this->setupAccess->authorizeRequest($request)) {
            if ($request->isMethod('GET') && $request->query->has('setup_token')) {
                return redirect()->to($request->fullUrlWithoutQuery(['setup_token']));
            }

            return $next($request);
        }

        $this->setupAccess->configuredToken(true);

        return $next($request);
    }
}
