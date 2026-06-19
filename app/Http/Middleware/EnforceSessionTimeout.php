<?php

namespace App\Http\Middleware;

use App\Models\ProgramSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        $timeoutMinutes = max(0, (int) ProgramSetting::get(
            'security.session_timeout_minutes',
            config('aptoria.security.session_timeout_minutes', 120)
        ));

        if ($timeoutMinutes === 0) {
            $request->session()->put('aptoria_last_activity_at', now()->timestamp);

            return $next($request);
        }

        $lastActivity = (int) $request->session()->get('aptoria_last_activity_at', 0);
        $expired = $lastActivity > 0 && (now()->timestamp - $lastActivity) > ($timeoutMinutes * 60);

        if ($expired) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => __('messages.auth.session_expired')], 419);
            }

            return redirect()->route('login')->with('warning', __('messages.auth.session_expired'));
        }

        $request->session()->put('aptoria_last_activity_at', now()->timestamp);

        return $next($request);
    }
}
