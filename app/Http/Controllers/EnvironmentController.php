<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnvironmentRequest;
use App\Models\Environment;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EnvironmentController extends Controller
{
    public function create(Project $project): View
    {
        $project->load('authProfiles');

        return view('environments.create', [
            'project' => $project,
            'environment' => new Environment(['base_url' => $project->base_url]),
            'authProfiles' => $project->authProfiles,
        ]);
    }

    public function store(EnvironmentRequest $request, Project $project): RedirectResponse
    {
        $project->environments()->create([
            ...$request->validated(),
            'is_production' => $request->boolean('is_production'),
        ]);

        return redirect()->route('projects.show', $project)->with('success', __('messages.environments.created'));
    }

    public function edit(Project $project, Environment $environment): View
    {
        $this->ensureEnvironmentBelongsToProject($project, $environment);

        $project->load('authProfiles');

        return view('environments.edit', [
            'project' => $project,
            'environment' => $environment,
            'authProfiles' => $project->authProfiles,
        ]);
    }

    public function update(EnvironmentRequest $request, Project $project, Environment $environment): RedirectResponse
    {
        $this->ensureEnvironmentBelongsToProject($project, $environment);

        $environment->update([
            ...$request->validated(),
            'is_production' => $request->boolean('is_production'),
        ]);

        return redirect()->route('projects.show', $project)->with('success', __('messages.environments.updated'));
    }

    public function destroy(Project $project, Environment $environment): RedirectResponse
    {
        $this->ensureEnvironmentBelongsToProject($project, $environment);

        if ($project->environments()->count() <= 1) {
            return redirect()->route('projects.show', $project)->withErrors(__('messages.environments.must_keep_one'));
        }

        $environment->delete();

        return redirect()->route('projects.show', $project)->with('success', __('messages.environments.deleted'));
    }

    private function ensureEnvironmentBelongsToProject(Project $project, Environment $environment): void
    {
        abort_unless($environment->project_id === $project->id, 404);
    }
}
