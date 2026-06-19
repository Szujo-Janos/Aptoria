<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditLogger;
use App\Services\PasswordPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $identityFields = [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
            'timezone' => $user->timezone,
            'report_organization' => $user->report_organization,
            'report_prepared_by' => $user->report_prepared_by,
            'report_role_title' => $user->report_role_title,
            'report_confidentiality_label' => $user->report_confidentiality_label,
        ];

        $filled = collect($identityFields)->filter(fn ($value): bool => filled($value))->count();
        $completeness = (int) round(($filled / count($identityFields)) * 100);

        $recentActivity = AuditLog::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(6)
            ->get();

        return view('profile.show', [
            'user' => $user,
            'identityCompleteness' => $completeness,
            'recentActivity' => $recentActivity,
            'locales' => [
                'en' => 'English',
                'hu' => 'Magyar',
            ],
            'timezones' => [
                'Europe/Budapest',
                'UTC',
                'Europe/London',
                'Europe/Berlin',
                'America/New_York',
            ],
        ]);
    }

    public function update(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$request->user()->id],
            'locale' => ['required', 'in:en,hu'],
            'timezone' => ['required', 'string', 'max:64'],
            'report_organization' => ['nullable', 'string', 'max:255'],
            'report_prepared_by' => ['nullable', 'string', 'max:255'],
            'report_role_title' => ['nullable', 'string', 'max:255'],
            'report_confidentiality_label' => ['nullable', 'string', 'max:255'],
            'report_disclaimer' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $user->fill($validated)->save();

        $request->session()->put('locale', $user->locale);

        $auditLogger->record(
            'profile_updated',
            __('messages.audit_messages.profile_updated'),
            null,
            ['subject_label' => $user->email],
            'auth'
        );

        return back()->with('status', __('messages.profile.profile_updated'));
    }

    public function updatePassword(Request $request, AuditLogger $auditLogger, PasswordPolicyService $passwordPolicy): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => $passwordPolicy->rules($user, true),
        ]);

        $wasForced = (bool) $user->password_change_required;

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'password_change_required' => false,
        ])->save();

        $auditLogger->record(
            $wasForced ? 'forced_password_change_completed' : 'password_changed',
            $wasForced ? __('messages.audit_messages.forced_password_change_completed') : __('messages.audit_messages.password_changed'),
            null,
            ['subject_label' => $user->email],
            'auth'
        );

        return back()->with('status', __('messages.profile.password_updated'));
    }
}
