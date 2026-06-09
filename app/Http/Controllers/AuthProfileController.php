<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthProfileRequest;
use App\Models\AuthProfile;
use App\Models\Project;
use App\Services\Auth\AuthProfileRuntimeService;
use App\Services\Auth\AuthProfileTestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthProfileController extends Controller
{
    public function create(Project $project): View
    {
        return view('auth_profiles.create', [
            'project' => $project,
            'authProfile' => new AuthProfile(['type' => AuthProfile::TYPE_NONE]),
            'types' => AuthProfile::TYPES,
            'probeableEndpoints' => collect(),
        ]);
    }

    public function store(AuthProfileRequest $request, Project $project): RedirectResponse
    {
        $data = $this->buildProfileData($request, new AuthProfile());

        if (! $project->authProfiles()->exists()) {
            $data['is_default'] = true;
        }

        if ($data['is_default']) {
            $project->authProfiles()->update(['is_default' => false]);
        }

        $project->authProfiles()->create($data);

        return redirect()->route('projects.show', $project)->with('success', __('messages.auth_profiles.created'));
    }

    public function edit(Project $project, AuthProfile $authProfile): View
    {
        $this->ensureProfileBelongsToProject($project, $authProfile);

        $project->loadMissing(['endpoints' => fn ($query) => $query
            ->where('is_active', true)
            ->where('excluded_from_scan', false)
            ->whereIn('method', ['GET', 'HEAD'])
            ->orderBy('method')
            ->orderBy('path')]);

        return view('auth_profiles.edit', [
            'project' => $project,
            'authProfile' => $authProfile,
            'types' => AuthProfile::TYPES,
            'probeableEndpoints' => $project->endpoints,
        ]);
    }

    public function update(AuthProfileRequest $request, Project $project, AuthProfile $authProfile): RedirectResponse
    {
        $this->ensureProfileBelongsToProject($project, $authProfile);

        $data = $this->buildProfileData($request, $authProfile);

        if ($data['is_default']) {
            $project->authProfiles()->whereKeyNot($authProfile->id)->update(['is_default' => false]);
        }

        $authProfile->update($data);

        return redirect()->route('projects.show', $project)->with('success', __('messages.auth_profiles.updated'));
    }

    public function destroy(Project $project, AuthProfile $authProfile): RedirectResponse
    {
        $this->ensureProfileBelongsToProject($project, $authProfile);

        $wasDefault = $authProfile->is_default;
        $authProfile->delete();

        if ($wasDefault) {
            $project->authProfiles()->oldest()->first()?->update(['is_default' => true]);
        }

        return redirect()->route('projects.show', $project)->with('success', __('messages.auth_profiles.deleted'));
    }


    public function test(Request $request, Project $project, AuthProfile $authProfile, AuthProfileTestService $tester): RedirectResponse
    {
        $this->ensureProfileBelongsToProject($project, $authProfile);

        $validated = $request->validate([
            'test_target' => ['required', Rule::in(['endpoint', 'custom'])],
            'test_endpoint_id' => [
                Rule::requiredIf(fn (): bool => $request->input('test_target') === 'endpoint'),
                'nullable',
                Rule::exists('endpoints', 'id')->where('project_id', $project->id),
            ],
            'test_method' => [
                Rule::requiredIf(fn (): bool => $request->input('test_target') === 'custom'),
                'nullable',
                Rule::in(['GET', 'HEAD']),
            ],
            'test_url' => [
                Rule::requiredIf(fn (): bool => $request->input('test_target') === 'custom'),
                'nullable',
                'url',
                'max:1000',
            ],
        ]);

        $result = $tester->run($project, $authProfile, $validated);

        return back()
            ->with('auth_profile_test_result', $result)
            ->with($result['ok'] ? 'success' : 'warning', (string) $result['message']);
    }

    private function buildProfileData(AuthProfileRequest $request, AuthProfile $profile): array
    {
        $validated = $request->validated();
        $type = $validated['type'];

        $data = [
            'name' => $validated['name'],
            'type' => $type,
            'notes' => $validated['notes'] ?? null,
            'is_default' => $request->boolean('is_default'),
            'username' => null,
            'header_name' => null,
            'encrypted_token' => null,
            'encrypted_password' => null,
            'encrypted_header_value' => null,
        ];

        if ($type === AuthProfile::TYPE_BEARER) {
            $data['encrypted_token'] = $request->filled('token') ? $validated['token'] : $profile->encrypted_token;
        }

        if ($type === AuthProfile::TYPE_BASIC) {
            $data['username'] = $validated['username'] ?? null;
            $data['encrypted_password'] = $request->filled('password') ? $validated['password'] : $profile->encrypted_password;
        }

        if ($type === AuthProfile::TYPE_CUSTOM_HEADER) {
            $data['header_name'] = $validated['header_name'] ?? null;
            $data['encrypted_header_value'] = $request->filled('header_value') ? $validated['header_value'] : $profile->encrypted_header_value;
        }

        return $data;
    }


    private function ensureProfileBelongsToProject(Project $project, AuthProfile $authProfile): void
    {
        abort_unless($authProfile->project_id === $project->id, 404);
    }
}
