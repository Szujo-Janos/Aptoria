<?php

namespace App\Http\Middleware;

use App\Services\Security\SetupAccessService;
use App\Services\Setup\SetupStateService;
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
                $route = auth()->check() ? 'profile.show' : 'login';

                return redirect()->route($route)
                    ->with('info', __('messages.setup.locked_redirect'));
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

        abort(403, 'Setup is locked behind a strong setup token on non-local hosts. Open /setup?setup_token=YOUR_TOKEN once or run the installer locally/through SSH. After the token is accepted it is stored in the session and removed from the URL. The generated token is stored in storage/app/setup-token.txt if APTORIA_SETUP_TOKEN is not set.');
    }
}
