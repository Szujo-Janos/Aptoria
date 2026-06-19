<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private const SYSTEM_ROLES = ['admin', 'user'];

    private const SUPPORTED_TIMEZONES = [
        'Europe/Budapest',
        'UTC',
        'Europe/London',
        'Europe/Berlin',
        'America/New_York',
        'America/Los_Angeles',
    ];

    public function index(): View
    {
        $users = User::query()
            ->withCount(['projectMemberships', 'ownedProjects'])
            ->orderByRaw("case role when 'admin' then 1 else 2 end")
            ->orderBy('name')
            ->get();

        return view('users.index', [
            'users' => $users,
            'systemRoles' => self::SYSTEM_ROLES,
            'supportedLocales' => config('aptoria.supported_locale_names', ['en' => 'English', 'hu' => 'Magyar']),
            'supportedTimezones' => self::SUPPORTED_TIMEZONES,
            'adminCount' => User::query()->where('role', 'admin')->count(),
            'passwordChangeRequiredCount' => User::query()->where('password_change_required', true)->count(),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(self::SYSTEM_ROLES)],
            'locale' => ['required', 'string', 'in:en,hu'],
            'timezone' => ['required', 'string', 'in:'.implode(',', self::SUPPORTED_TIMEZONES)],
        ]);

        $temporaryPassword = $this->generateTemporaryPassword();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'locale' => $data['locale'],
            'timezone' => $data['timezone'],
            'password' => Hash::make($temporaryPassword),
            'password_change_required' => true,
        ]);

        $auditLogger->record('created', __('messages.audit_messages.user_created'), $user, [
            'subject_label' => $user->email,
            'managed_user_id' => $user->id,
            'managed_user_email' => $user->email,
            'role' => $user->role,
            'password_change_required' => true,
        ], 'users');

        return redirect()->route('users.index')
            ->with('status', __('messages.users.created'))
            ->with('temporary_user_name', $user->name)
            ->with('temporary_user_email', $user->email)
            ->with('temporary_password', $temporaryPassword);
    }

    public function update(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(self::SYSTEM_ROLES)],
            'locale' => ['required', 'string', 'in:en,hu'],
            'timezone' => ['required', 'string', 'in:'.implode(',', self::SUPPORTED_TIMEZONES)],
        ]);

        if ($user->isAdmin() && $data['role'] !== 'admin' && $this->isLastAdmin($user)) {
            return back()->withErrors(['role' => __('messages.users.last_admin_blocked')]);
        }

        if ((int) $request->user()->id === (int) $user->id && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => __('messages.users.self_demote_blocked')]);
        }

        $before = $user->only(['name', 'email', 'role', 'locale', 'timezone']);
        $user->update($data);

        $auditLogger->record('updated', __('messages.audit_messages.user_updated'), $user, [
            'subject_label' => $user->email,
            'managed_user_id' => $user->id,
            'before' => $before,
            'after' => $user->only(['name', 'email', 'role', 'locale', 'timezone']),
        ], 'users');

        return redirect()->route('users.index')->with('status', __('messages.users.updated'));
    }

    public function resetTemporaryPassword(Request $request, User $user, AuditLogger $auditLogger): RedirectResponse
    {
        if (! $request->user()->isAdmin()) {
            abort(403);
        }

        $temporaryPassword = $this->generateTemporaryPassword();

        $user->update([
            'password' => Hash::make($temporaryPassword),
            'password_change_required' => true,
        ]);

        $auditLogger->record('updated', __('messages.audit_messages.user_password_reset'), $user, [
            'subject_label' => $user->email,
            'managed_user_id' => $user->id,
            'managed_user_email' => $user->email,
            'password_change_required' => true,
        ], 'users', 'warning');

        return redirect()->route('users.index')
            ->with('status', __('messages.users.temporary_password_reset'))
            ->with('temporary_user_name', $user->name)
            ->with('temporary_user_email', $user->email)
            ->with('temporary_password', $temporaryPassword);
    }

    private function isLastAdmin(User $user): bool
    {
        return User::query()->where('role', 'admin')->where('id', '!=', $user->id)->doesntExist();
    }

    private function generateTemporaryPassword(): string
    {
        return 'Temp-'.bin2hex(random_bytes(5)).'!9Aa';
    }
}
