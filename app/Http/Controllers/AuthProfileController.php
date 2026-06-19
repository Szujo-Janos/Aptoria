<?php

namespace App\Http\Controllers;

use App\Models\AuthProfile;
use App\Models\Project;
use App\Models\ProjectSetting;
use App\Services\AuditLogger;
use App\Services\AuthProfileTesterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AuthProfileController extends Controller
{
    public function index(Project $project): View
    {
        return view('auth_profiles.index', [
            'project' => $project,
            'authProfiles' => $project->authProfiles()->latest()->get(),
            'defaultAuthProfile' => $project->defaultAuthProfile(),
            'environments' => $project->environments()->orderByDesc('is_default')->orderBy('name')->get(),
            'defaultEnvironment' => $project->defaultEnvironment(),
            'authTestResult' => session('auth_profile_test_result'),
        ]);
    }

    public function store(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_default'] = $request->boolean('is_default') || ! $project->authProfiles()->exists();
        $data = $this->normalizeSecretPayload($data, $request);

        if ($data['is_default']) {
            $project->authProfiles()->update(['is_default' => false]);
        }

        $profile = $project->authProfiles()->create($data);

        if ($profile->is_default) {
            ProjectSetting::set($project, 'scan.default_auth_profile_id', $profile->id);
        }

        $auditLogger->record('created', __('messages.audit_messages.auth_profile_created'), $project, [
            'auth_profile_id' => $profile->id,
            'name' => $profile->name,
            'type' => $profile->type,
            'is_default' => $profile->is_default,
        ], 'auth_profile');

        return redirect()->route('projects.auth-profiles.index', $project)->with('status', __('messages.auth_profiles.created'));
    }

    public function update(Request $request, Project $project, AuthProfile $authProfile, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $authProfile);
        $before = $authProfile->only(['name', 'type', 'username', 'header_name', 'is_default']);
        $data = $this->validated($request, $authProfile);
        $data['is_default'] = $request->boolean('is_default');
        $data = $this->normalizeSecretPayload($data, $request, $authProfile);

        if ($data['is_default']) {
            $project->authProfiles()->where('id', '!=', $authProfile->id)->update(['is_default' => false]);
        }

        $authProfile->update($data);

        if ($authProfile->is_default) {
            ProjectSetting::set($project, 'scan.default_auth_profile_id', $authProfile->id);
        }

        $auditLogger->record('updated', __('messages.audit_messages.auth_profile_updated'), $project, [
            'auth_profile_id' => $authProfile->id,
            'before' => $before,
            'after' => $authProfile->only(['name', 'type', 'username', 'header_name', 'is_default']),
        ], 'auth_profile');

        return redirect()->route('projects.auth-profiles.index', $project)->with('status', __('messages.auth_profiles.updated'));
    }


    public function test(Request $request, Project $project, AuthProfileTesterService $testerService, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'environment_id' => ['nullable', 'integer'],
            'auth_profile_id' => ['nullable', 'integer'],
            'method' => ['required', 'in:GET,HEAD'],
            'test_path' => ['required', 'string', 'max:2048'],
            'expected_status' => ['nullable', 'integer', 'between:100,599'],
        ]);

        $environment = null;
        if (! empty($data['environment_id'])) {
            $environment = $project->environments()->whereKey($data['environment_id'])->firstOrFail();
        }

        $authProfile = null;
        if (! empty($data['auth_profile_id'])) {
            $authProfile = $project->authProfiles()->whereKey($data['auth_profile_id'])->firstOrFail();
        }

        $result = $testerService->test(
            $project,
            $environment,
            $authProfile,
            (string) $data['method'],
            (string) $data['test_path'],
            ! empty($data['expected_status']) ? (int) $data['expected_status'] : null,
        );

        $auditLogger->record('auth_profile_test_completed', __('messages.audit_messages.auth_profile_test_completed'), $project, [
            'environment_id' => $environment?->id,
            'auth_profile_id' => $authProfile?->id,
            'method' => $result['method'] ?? $data['method'],
            'url' => $result['url'] ?? null,
            'state' => $result['state'] ?? 'unknown',
            'status_code' => $result['status_code'] ?? null,
            'response_time_ms' => $result['response_time_ms'] ?? null,
        ], 'auth_profile');

        return redirect()
            ->route('projects.auth-profiles.index', $project)
            ->with('status', __('messages.auth_profiles.test_completed'))
            ->with('auth_profile_test_result', $result);
    }

    public function destroy(Project $project, AuthProfile $authProfile, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $authProfile);
        $wasDefault = $authProfile->is_default;

        $auditLogger->record('deleted', __('messages.audit_messages.auth_profile_deleted'), $project, [
            'auth_profile_id' => $authProfile->id,
            'name' => $authProfile->name,
            'type' => $authProfile->type,
        ], 'auth_profile', 'warning');

        $authProfile->delete();

        if ($wasDefault) {
            $next = $project->authProfiles()->oldest()->first();
            if ($next) {
                $next->update(['is_default' => true]);
                ProjectSetting::set($project, 'scan.default_auth_profile_id', $next->id);
            } else {
                ProjectSetting::set($project, 'scan.default_auth_profile_id', '');
            }
        }

        return redirect()->route('projects.auth-profiles.index', $project)->with('status', __('messages.auth_profiles.deleted'));
    }

    public function makeDefault(Project $project, AuthProfile $authProfile, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $authProfile);
        $project->authProfiles()->where('id', '!=', $authProfile->id)->update(['is_default' => false]);
        $authProfile->update(['is_default' => true]);
        ProjectSetting::set($project, 'scan.default_auth_profile_id', $authProfile->id);

        $auditLogger->record('updated', __('messages.audit_messages.default_auth_profile_changed'), $project, [
            'auth_profile_id' => $authProfile->id,
            'name' => $authProfile->name,
            'type' => $authProfile->type,
        ], 'auth_profile');

        return redirect()->route('projects.auth-profiles.index', $project)->with('status', __('messages.auth_profiles.default_changed'));
    }

    private function validated(Request $request, ?AuthProfile $existing = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:'.implode(',', AuthProfile::TYPES)],
            'token' => ['nullable', 'string', 'max:4000'],
            'username' => ['nullable', 'string', 'max:160'],
            'password' => ['nullable', 'string', 'max:4000'],
            'header_name' => ['nullable', 'string', 'max:160'],
            'header_value' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request, $existing): void {
            $type = $request->string('type')->toString();
            if ($type === 'bearer' && ! $request->filled('token') && (! $existing || $existing->type !== 'bearer' || ! $existing->hasStoredSecretFor('bearer'))) {
                $validator->errors()->add('token', __('messages.auth_profiles.token_required'));
            }
            if ($type === 'basic') {
                if (! $request->filled('username')) {
                    $validator->errors()->add('username', __('messages.auth_profiles.username_required'));
                }
                if (! $request->filled('password') && (! $existing || $existing->type !== 'basic' || ! $existing->hasStoredSecretFor('basic'))) {
                    $validator->errors()->add('password', __('messages.auth_profiles.password_required'));
                }
            }
            if ($type === 'custom_header') {
                if (! $request->filled('header_name')) {
                    $validator->errors()->add('header_name', __('messages.auth_profiles.header_name_required'));
                }
                if (! $request->filled('header_value') && (! $existing || $existing->type !== 'custom_header' || ! $existing->hasStoredSecretFor('custom_header'))) {
                    $validator->errors()->add('header_value', __('messages.auth_profiles.header_value_required'));
                }
            }
        });

        return $validator->validate();
    }

    private function normalizeSecretPayload(array $data, Request $request, ?AuthProfile $existing = null): array
    {
        $data['encrypted_token'] = null;
        $data['encrypted_password'] = null;
        $data['encrypted_header_value'] = null;

        if ($data['type'] === 'bearer') {
            if ($request->filled('token')) {
                $data['encrypted_token'] = $request->input('token');
            } elseif ($existing && $existing->type === 'bearer' && $existing->hasStoredSecretFor('bearer')) {
                unset($data['encrypted_token']);
            }
        }

        if ($data['type'] === 'basic') {
            if ($request->filled('password')) {
                $data['encrypted_password'] = $request->input('password');
            } elseif ($existing && $existing->type === 'basic' && $existing->hasStoredSecretFor('basic')) {
                unset($data['encrypted_password']);
            }
        } else {
            $data['username'] = null;
        }

        if ($data['type'] === 'custom_header') {
            if ($request->filled('header_value')) {
                $data['encrypted_header_value'] = $request->input('header_value');
            } elseif ($existing && $existing->type === 'custom_header' && $existing->hasStoredSecretFor('custom_header')) {
                unset($data['encrypted_header_value']);
            }
        } else {
            $data['header_name'] = null;
        }

        unset($data['token'], $data['password'], $data['header_value']);

        return $data;
    }

    private function ensureBelongsToProject(Project $project, AuthProfile $authProfile): void
    {
        abort_unless((int) $authProfile->project_id === (int) $project->id, 404);
    }
}
