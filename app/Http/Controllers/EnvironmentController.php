<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnvironmentRequest;
use App\Models\Environment;
use App\Models\Project;
use App\Services\Settings\ProjectSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EnvironmentController extends Controller
{
    public function index(Project $project, ProjectSettingService $settings): View
    {
        $project->load([
            'authProfiles',
            'environments' => fn ($query) => $query
                ->with('authProfile')
                ->withCount(['endpoints', 'scanRuns', 'snapshots'])
                ->orderByRaw("CASE environment_type WHEN 'local' THEN 1 WHEN 'dev' THEN 2 WHEN 'staging' THEN 3 WHEN 'production' THEN 4 ELSE 5 END")
                ->orderBy('name'),
        ]);

        $settings->seedDefaults($project);

        return view('environments.index', [
            'project' => $project,
            'defaultEnvironmentId' => (string) $settings->get($project, 'scan.default_environment_id', ''),
            'defaultAuthProfileId' => (string) $settings->get($project, 'scan.default_auth_profile_id', ''),
            'typeOptions' => Environment::typeOptions(),
        ]);
    }

    public function create(Project $project, ProjectSettingService $settings): View
    {
        $project->load('authProfiles');
        $settings->seedDefaults($project);

        return view('environments.create', [
            'project' => $project,
            'environment' => new Environment([
                'base_url' => $project->base_url,
                'environment_type' => Environment::TYPE_STAGING,
            ]),
            'authProfiles' => $project->authProfiles,
            'typeOptions' => Environment::typeOptions(),
            'isDefaultEnvironment' => false,
        ]);
    }

    public function store(EnvironmentRequest $request, Project $project, ProjectSettingService $settings): RedirectResponse
    {
        $validated = $request->validated();
        $type = (string) $validated['environment_type'];
        unset($validated['make_default']);

        $environment = $project->environments()->create([
            ...$validated,
            'is_production' => $type === Environment::TYPE_PRODUCTION || $request->boolean('is_production'),
        ]);

        $settings->seedDefaults($project);
        if ($request->boolean('make_default') || $project->environments()->count() === 1) {
            $settings->set($project, 'scan.default_environment_id', (string) $environment->id);
            if ($environment->auth_profile_id) {
                $settings->set($project, 'scan.default_auth_profile_id', (string) $environment->auth_profile_id);
            }
        }

        return redirect()->route('projects.environments.index', $project)->with('success', __('messages.environments.created'));
    }

    public function edit(Project $project, Environment $environment, ProjectSettingService $settings): View
    {
        $this->ensureEnvironmentBelongsToProject($project, $environment);

        $project->load('authProfiles');
        $settings->seedDefaults($project);

        return view('environments.edit', [
            'project' => $project,
            'environment' => $environment,
            'authProfiles' => $project->authProfiles,
            'typeOptions' => Environment::typeOptions(),
            'isDefaultEnvironment' => (string) $settings->get($project, 'scan.default_environment_id', '') === (string) $environment->id,
        ]);
    }

    public function update(EnvironmentRequest $request, Project $project, Environment $environment, ProjectSettingService $settings): RedirectResponse
    {
        $this->ensureEnvironmentBelongsToProject($project, $environment);

        $validated = $request->validated();
        $type = (string) $validated['environment_type'];
        unset($validated['make_default']);

        $environment->update([
            ...$validated,
            'is_production' => $type === Environment::TYPE_PRODUCTION || $request->boolean('is_production'),
        ]);

        if ($request->boolean('make_default')) {
            $settings->set($project, 'scan.default_environment_id', (string) $environment->id);
            if ($environment->auth_profile_id) {
                $settings->set($project, 'scan.default_auth_profile_id', (string) $environment->auth_profile_id);
            }
        }

        return redirect()->route('projects.environments.index', $project)->with('success', __('messages.environments.updated'));
    }

    public function setDefault(Project $project, Environment $environment, ProjectSettingService $settings): RedirectResponse
    {
        $this->ensureEnvironmentBelongsToProject($project, $environment);

        $settings->seedDefaults($project);
        $settings->set($project, 'scan.default_environment_id', (string) $environment->id);
        if ($environment->auth_profile_id) {
            $settings->set($project, 'scan.default_auth_profile_id', (string) $environment->auth_profile_id);
        }

        return redirect()->route('projects.environments.index', $project)->with('success', __('messages.environments.default_updated'));
    }

    public function destroy(Project $project, Environment $environment, ProjectSettingService $settings): RedirectResponse
    {
        $this->ensureEnvironmentBelongsToProject($project, $environment);

        if ($project->environments()->count() <= 1) {
            return redirect()->route('projects.environments.index', $project)->withErrors(__('messages.environments.must_keep_one'));
        }

        $wasDefault = (string) $settings->get($project, 'scan.default_environment_id', '') === (string) $environment->id;
        $environment->delete();

        if ($wasDefault) {
            $fallback = $project->environments()->where('is_production', false)->orderBy('name')->first()
                ?: $project->environments()->orderBy('name')->first();
            $settings->set($project, 'scan.default_environment_id', (string) ($fallback?->id ?? ''));
        }

        return redirect()->route('projects.environments.index', $project)->with('success', __('messages.environments.deleted'));
    }

    private function ensureEnvironmentBelongsToProject(Project $project, Environment $environment): void
    {
        abort_unless($environment->project_id === $project->id, 404);
    }
}
