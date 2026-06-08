<?php

namespace App\Http\Middleware;

use App\Services\Settings\SettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeoutMinutes = max(5, min(1440, app(SettingService::class)->integer('security.session_timeout_minutes', 120)));
        $lastActivity = (int) $request->session()->get('aptoria_last_activity_at', time());

        if ((time() - $lastActivity) > ($timeoutMinutes * 60)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', __('messages.auth.session_expired'));
        }

        $request->session()->put('aptoria_last_activity_at', time());

        return $next($request);
    }
}
