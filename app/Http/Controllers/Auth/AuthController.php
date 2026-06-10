<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\AuditLog;
use App\Services\Audit\AuditLogService;
use App\Services\Setup\SetupStateService;
use App\Services\Settings\SettingsRuntimeService;
use Illuminate\Http\RedirectResponse as HttpRedirectResponse;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(SetupStateService $setupState): View|HttpRedirectResponse
    {
        if (! $setupState->canUseApplication()) {
            return redirect()->route('setup.index')->with('warning', __('messages.setup.required_before_login'));
        }

        return view('auth.login');
    }

    public function login(Request $request, SettingsRuntimeService $runtime, SetupStateService $setupState): RedirectResponse
    {
        if (! $setupState->canUseApplication()) {
            return redirect()->route('setup.index')->with('warning', __('messages.setup.required_before_login'));
        }

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

            $user = Auth::user();
            $locale = $user?->locale;
            if (is_string($locale) && in_array($locale, array_keys(config('aptoria.supported_locales', ['en' => 'English'])), true)) {
                $request->session()->put('locale', $locale);
            }

            app(AuditLogService::class)->record([
                'user_id' => $user?->id,
                'event_type' => AuditLog::EVENT_AUTH,
                'action' => AuditLog::ACTION_LOGIN,
                'severity' => AuditLog::SEVERITY_INFO,
                'subject_label' => 'user',
                'subject_name' => $user?->email,
                'summary' => 'User signed in: '.($user?->email ?? 'unknown'),
            ]);

            $shouldShowProfile = $this->recordSuccessfulLoginAndShouldShowProfile($user);

            if ($shouldShowProfile) {
                return redirect()->route('profile.show')
                    ->with('success', __('messages.auth.login_success'))
                    ->with('info', __('messages.auth.first_login_profile_redirect'));
            }

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

    private function recordSuccessfulLoginAndShouldShowProfile(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        try {
            if (! Schema::hasColumn('users', 'first_login_at')) {
                return false;
            }

            $isFirstSuccessfulLogin = $user->first_login_at === null;
            $now = now();

            $user->forceFill([
                'first_login_at' => $user->first_login_at ?: $now,
                'last_login_at' => $now,
            ])->save();

            return $isFirstSuccessfulLogin;
        } catch (Throwable) {
            return false;
        }
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        app(AuditLogService::class)->record([
            'user_id' => $user?->id,
            'event_type' => AuditLog::EVENT_AUTH,
            'action' => AuditLog::ACTION_LOGOUT,
            'severity' => AuditLog::SEVERITY_INFO,
            'subject_label' => 'user',
            'subject_name' => $user?->email,
            'summary' => 'User signed out: '.($user?->email ?? 'unknown'),
        ]);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', __('messages.auth.logout_success'));
    }
}
