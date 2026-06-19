<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectSettingsController extends Controller
{
    public function edit(Project $project): View
    {
        $settings = $this->settings($project);

        return view('project_settings.edit', [
            'project' => $project,
            'settings' => $settings,
            'environments' => $project->environments()->orderByDesc('is_default')->orderBy('name')->get(),
            'authProfiles' => $project->authProfiles()->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'default_environment_id' => ['nullable', 'integer'],
            'default_auth_profile_id' => ['nullable', 'integer'],
        ]);

        $environmentId = $data['default_environment_id'] ?? null;
        if ($environmentId) {
            $environment = $project->environments()->whereKey($environmentId)->firstOrFail();
            $project->environments()->where('id', '!=', $environment->id)->update(['is_default' => false]);
            $environment->update(['is_default' => true]);
            ProjectSetting::set($project, 'scan.default_environment_id', $environment->id);
        }

        $authProfileId = $data['default_auth_profile_id'] ?? null;
        if ($authProfileId) {
            $authProfile = $project->authProfiles()->whereKey($authProfileId)->firstOrFail();
            $project->authProfiles()->where('id', '!=', $authProfile->id)->update(['is_default' => false]);
            $authProfile->update(['is_default' => true]);
            ProjectSetting::set($project, 'scan.default_auth_profile_id', $authProfile->id);
        } else {
            ProjectSetting::set($project, 'scan.default_auth_profile_id', '');
            $project->authProfiles()->update(['is_default' => false]);
        }

        ProjectSetting::set($project, 'scan.require_confirmation', $request->boolean('scan_require_confirmation'));
        ProjectSetting::set($project, 'scan.allow_private_networks', $request->boolean('scan_allow_private_networks'));
        ProjectSetting::set($project, 'scan.safe_methods_only', $request->boolean('scan_safe_methods_only'));

        $auditLogger->record('updated', __('messages.audit_messages.project_scan_settings_updated'), $project, [
            'default_environment_id' => $environmentId,
            'default_auth_profile_id' => $authProfileId,
            'scan_require_confirmation' => $request->boolean('scan_require_confirmation'),
            'scan_allow_private_networks' => $request->boolean('scan_allow_private_networks'),
            'scan_safe_methods_only' => $request->boolean('scan_safe_methods_only'),
        ], 'project_settings');

        return redirect()->route('projects.settings.edit', $project)->with('status', __('messages.project_settings.updated'));
    }

    private function settings(Project $project): array
    {
        return [
            'default_environment_id' => ProjectSetting::get($project, 'scan.default_environment_id', $project->defaultEnvironment()?->id),
            'default_auth_profile_id' => ProjectSetting::get($project, 'scan.default_auth_profile_id', $project->defaultAuthProfile()?->id),
            'scan_require_confirmation' => ProjectSetting::get($project, 'scan.require_confirmation', '1') !== '0',
            'scan_allow_private_networks' => ProjectSetting::get($project, 'scan.allow_private_networks', '0') === '1',
            'scan_safe_methods_only' => ProjectSetting::get($project, 'scan.safe_methods_only', '1') !== '0',
        ];
    }
}
