<?php

namespace App\Http\Controllers;

use App\Models\Finding;
use App\Models\Project;
use App\Models\QaReleaseGate;
use App\Models\ScanRun;
use App\Services\Settings\SettingService;
use App\Services\Setup\SetupStateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    public function show(Request $request, SettingService $settings, SetupStateService $setupState): View
    {
        $user = $request->user();
        $latestScan = ScanRun::query()->latest('created_at')->first();
        $latestGate = QaReleaseGate::query()->latest('created_at')->first();
        $locale = (string) $request->session()->get('locale', $user->locale ?? config('aptoria.default_locale', 'en'));
        $timezone = $user->timezone ?: $settings->string('app.timezone', config('app.timezone', 'UTC'));

        return view('profile.show', [
            'user' => $user,
            'supportedLocales' => config('aptoria.supported_locales', ['en' => 'English']),
            'currentLocale' => $locale,
            'currentTimezone' => $timezone,
            'sessionTimeoutMinutes' => $settings->integer('security.session_timeout_minutes', 120),
            'setupCompleted' => $setupState->isInstalled(),
            'activity' => [
                'projects' => Project::query()->count(),
                'open_findings' => Finding::query()->whereIn('status', Finding::OPEN_STATUSES)->count(),
                'latest_scan' => $latestScan?->created_at,
                'latest_release_gate' => $latestGate?->created_at,
                'latest_release_decision' => $latestGate?->final_decision,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $supportedLocales = array_keys(config('aptoria.supported_locales', ['en' => 'English']));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'locale' => ['required', Rule::in($supportedLocales)],
            'timezone' => ['nullable', 'timezone', 'max:80'],
        ]);

        $user->update($validated);
        $request->session()->put('locale', $validated['locale']);

        return redirect()
            ->route('profile.show')
            ->with('success', __('messages.profile.saved'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check((string) $validated['current_password'], (string) $user->password)) {
            return back()
                ->withErrors(['current_password' => __('messages.profile.current_password_invalid')])
                ->onlyInput();
        }

        $user->forceFill([
            'password' => Hash::make((string) $validated['password']),
        ])->save();

        return redirect()
            ->route('profile.show')
            ->with('success', __('messages.profile.password_saved'));
    }
}
