<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthProfileRequest;
use App\Models\AuthProfile;
use App\Models\Project;
use App\Services\Auth\AuthProfileRuntimeService;
use App\Services\Security\NetworkTargetGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Throwable;

class AuthProfileController extends Controller
{
    public function create(Project $project): View
    {
        return view('auth_profiles.create', [
            'project' => $project,
            'authProfile' => new AuthProfile(['type' => AuthProfile::TYPE_NONE]),
            'types' => AuthProfile::TYPES,
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

        return view('auth_profiles.edit', [
            'project' => $project,
            'authProfile' => $authProfile,
            'types' => AuthProfile::TYPES,
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


    public function test(Request $request, Project $project, AuthProfile $authProfile, AuthProfileRuntimeService $authRuntime, NetworkTargetGuard $networkGuard): RedirectResponse
    {
        $this->ensureProfileBelongsToProject($project, $authProfile);

        $validated = $request->validate([
            'test_method' => ['required', 'in:GET,HEAD'],
            'test_url' => ['required', 'url', 'max:1000'],
        ]);

        $url = (string) $validated['test_url'];
        if ($networkGuard->isBlocked($url, false, false)) {
            return back()->withErrors(['test_url' => __('messages.auth_profiles.test_private_blocked')]);
        }

        if (! $authRuntime->isComplete($authProfile)) {
            return back()->withErrors(['test_url' => __('messages.auth_profiles.test_incomplete')]);
        }

        try {
            $pendingRequest = Http::timeout(10)
                ->connectTimeout(5)
                ->acceptJson()
                ->withUserAgent('Aptoria/'.config('aptoria.version').' Auth Profile Test')
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 3,
                        'on_redirect' => function ($request, $response, $uri) use ($networkGuard): void {
                            $networkGuard->assertAllowed((string) $uri, false, false);
                        },
                    ],
                    'verify' => true,
                    'http_errors' => false,
                ]);

            $pendingRequest = $authRuntime->applyToRequest($pendingRequest, $authProfile);
            $started = microtime(true);
            $response = $pendingRequest->send((string) $validated['test_method'], $url);
            $durationMs = (int) round((microtime(true) - $started) * 1000);

            return back()->with('success', __('messages.auth_profiles.test_success', [
                'status' => $response->status(),
                'time' => $durationMs,
            ]));
        } catch (Throwable $exception) {
            return back()->withErrors(['test_url' => __('messages.auth_profiles.test_failed', [
                'message' => $authRuntime->maskValue($exception->getMessage()),
            ])]);
        }
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
