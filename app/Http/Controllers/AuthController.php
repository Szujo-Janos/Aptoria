<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => __('messages.auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey)]),
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors(['email' => __('messages.auth.failed')])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $user = $request->user();
        $user->forceFill([
            'first_login_at' => $user->first_login_at ?: now(),
            'last_login_at' => now(),
        ])->save();

        $auditLogger->record('login', __('messages.audit_messages.login'), null, ['subject_label' => $user->email], 'auth');

        if ($user->password_change_required) {
            return redirect()->route('profile.show')->with('status', __('messages.profile.password_change_required'));
        }

        return redirect()->intended(route('dashboard'));
    }

    private function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }

    public function logout(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $auditLogger->record('logout', __('messages.audit_messages.logout'), null, ['subject_label' => $request->user()?->email], 'auth');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
