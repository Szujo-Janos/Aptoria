<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\Setup\SetupStateService;
use App\Services\Settings\SettingsRuntimeService;
use Illuminate\Http\RedirectResponse as HttpRedirectResponse;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(SetupStateService $setupState): View|HttpRedirectResponse
    {
        if (! $setupState->isInstalled()) {
            return redirect()->route('setup.index')->with('warning', __('messages.setup.required_before_login'));
        }

        return view('auth.login');
    }

    public function login(Request $request, SettingsRuntimeService $runtime): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower((string) $credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => __('messages.auth.too_many_attempts', ['seconds' => $seconds]),
            ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            return redirect()->intended(route($runtime->defaultLandingRouteName()))->with('success', __('messages.auth.login_success'));
        }

        RateLimiter::hit($throttleKey, 60);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors(['email' => __('messages.auth.too_many_attempts', ['seconds' => $seconds])])
                ->with('error', __('messages.auth.too_many_attempts', ['seconds' => $seconds]))
                ->onlyInput('email');
        }

        return back()
            ->withErrors(['email' => __('messages.auth.login_failed')])
            ->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', __('messages.auth.logout_success'));
    }
}
