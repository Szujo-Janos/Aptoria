<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChangeIsCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->password_change_required && ! $request->routeIs('profile.*') && ! $request->routeIs('logout')) {
            return redirect()->route('profile.show')->with('status', __('messages.profile.password_change_required'));
        }

        return $next($request);
    }
}
