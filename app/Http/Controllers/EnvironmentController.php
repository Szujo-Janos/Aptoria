<?php

namespace App\Http\Controllers;

use App\Models\Environment;
use App\Models\Project;
use App\Models\ProjectSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnvironmentController extends Controller
{
    public function index(Project $project): View
    {
        return view('environments.index', [
            'project' => $project,
            'environments' => $project->environments()->latest()->get(),
            'defaultEnvironment' => $project->defaultEnvironment(),
        ]);
    }

    public function store(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $this->validated($request);
        $data['is_production'] = $request->boolean('is_production') || $data['environment_type'] === 'production';
        $data['is_default'] = $request->boolean('is_default') || ! $project->environments()->exists();

        if ($data['is_default']) {
            $project->environments()->update(['is_default' => false]);
        }

        $environment = $project->environments()->create($data);

        if ($environment->is_default) {
            ProjectSetting::set($project, 'scan.default_environment_id', $environment->id);
        }

        $auditLogger->record('created', __('messages.audit_messages.environment_created'), $project, [
            'environment_id' => $environment->id,
            'name' => $environment->name,
            'type' => $environment->environment_type,
            'is_default' => $environment->is_default,
        ], 'environment');

        return redirect()->route('projects.environments.index', $project)->with('status', __('messages.environments.created'));
    }

    public function update(Request $request, Project $project, Environment $environment, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $environment);
        $before = $environment->only(['name', 'base_url', 'environment_type', 'is_production', 'is_default']);
        $data = $this->validated($request);
        $data['is_production'] = $request->boolean('is_production') || $data['environment_type'] === 'production';
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            $project->environments()->where('id', '!=', $environment->id)->update(['is_default' => false]);
        }

        $environment->update($data);

        if ($environment->is_default) {
            ProjectSetting::set($project, 'scan.default_environment_id', $environment->id);
        }

        $auditLogger->record('updated', __('messages.audit_messages.environment_updated'), $project, [
            'environment_id' => $environment->id,
            'before' => $before,
            'after' => $environment->only(['name', 'base_url', 'environment_type', 'is_production', 'is_default']),
        ], 'environment');

        return redirect()->route('projects.environments.index', $project)->with('status', __('messages.environments.updated'));
    }

    public function destroy(Project $project, Environment $environment, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $environment);
        $wasDefault = $environment->is_default;

        $auditLogger->record('deleted', __('messages.audit_messages.environment_deleted'), $project, [
            'environment_id' => $environment->id,
            'name' => $environment->name,
            'type' => $environment->environment_type,
        ], 'environment', 'warning');

        $environment->delete();

        if ($wasDefault) {
            $next = $project->environments()->oldest()->first();
            if ($next) {
                $next->update(['is_default' => true]);
                ProjectSetting::set($project, 'scan.default_environment_id', $next->id);
            } else {
                ProjectSetting::set($project, 'scan.default_environment_id', '');
            }
        }

        return redirect()->route('projects.environments.index', $project)->with('status', __('messages.environments.deleted'));
    }

    public function makeDefault(Project $project, Environment $environment, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $environment);
        $project->environments()->where('id', '!=', $environment->id)->update(['is_default' => false]);
        $environment->update(['is_default' => true]);
        ProjectSetting::set($project, 'scan.default_environment_id', $environment->id);

        $auditLogger->record('updated', __('messages.audit_messages.default_environment_changed'), $project, [
            'environment_id' => $environment->id,
            'name' => $environment->name,
        ], 'environment');

        return redirect()->route('projects.environments.index', $project)->with('status', __('messages.environments.default_changed'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'base_url' => ['required', 'url', 'max:500'],
            'environment_type' => ['required', 'in:'.implode(',', Environment::TYPES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function ensureBelongsToProject(Project $project, Environment $environment): void
    {
        abort_unless((int) $environment->project_id === (int) $project->id, 404);
    }
}
