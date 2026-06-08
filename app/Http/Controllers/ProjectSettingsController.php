<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Settings\ProjectSettingService;
use App\Services\Endpoints\PathParameterResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectSettingsController extends Controller
{
    public function edit(Project $project, ProjectSettingService $settings, PathParameterResolver $pathParameters): View
    {
        $project->load(['environments', 'authProfiles', 'assertionRules' => fn ($query) => $query->whereNull('endpoint_id')->orderBy('rule_key')]);
        $settings->seedDefaults($project);

        return view('project_settings.edit', [
            'project' => $project,
            'settings' => $settings->grouped($project),
            'assertionRules' => $project->assertionRules,
            'pathParameterDefaults' => $pathParameters->formatText($project),
        ]);
    }

    public function update(Request $request, Project $project, ProjectSettingService $settings, PathParameterResolver $pathParameters): RedirectResponse
    {
        $environmentIds = $project->environments()->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $authProfileIds = $project->authProfiles()->pluck('id')->map(fn ($id): string => (string) $id)->all();

        $validated = $request->validate([
            'scan_enabled' => ['nullable', 'boolean'],
            'scan_default_environment_id' => ['nullable', Rule::in($environmentIds)],
            'scan_default_auth_profile_id' => ['nullable', Rule::in($authProfileIds)],
            'scan_max_endpoints_per_scan' => ['required', 'integer', 'min:1', 'max:2000'],
            'scan_allow_private_networks' => ['nullable', 'boolean'],
            'scan_require_confirmation' => ['nullable', 'boolean'],
            'scan_store_response_body_preview' => ['nullable', 'boolean'],
            'risk_sensitive_keywords' => ['nullable', 'string', 'max:5000'],
            'risk_internal_keywords' => ['nullable', 'string', 'max:5000'],
            'project_notes' => ['nullable', 'string', 'max:10000'],
            'path_parameter_defaults' => ['nullable', 'string', 'max:5000'],
        ]);

        $settings->updateMany($project, [
            'scan.enabled' => $request->boolean('scan_enabled'),
            'scan.default_environment_id' => $validated['scan_default_environment_id'] ?? '',
            'scan.default_auth_profile_id' => $validated['scan_default_auth_profile_id'] ?? '',
            'scan.max_endpoints_per_scan' => $validated['scan_max_endpoints_per_scan'],
            'scan.allow_private_networks' => $request->boolean('scan_allow_private_networks'),
            'scan.require_confirmation' => $request->boolean('scan_require_confirmation'),
            'scan.store_response_body_preview' => $request->boolean('scan_store_response_body_preview'),
            'risk.sensitive_keywords' => $validated['risk_sensitive_keywords'] ?? '',
            'risk.internal_keywords' => $validated['risk_internal_keywords'] ?? '',
            'project.notes' => $validated['project_notes'] ?? '',
        ]);

        $pathParameters->updateProjectDefaultsFromText($project, (string) ($validated['path_parameter_defaults'] ?? ''));

        return redirect()
            ->route('projects.settings.edit', $project)
            ->with('success', __('messages.project_settings.saved'));
    }

    public function reset(Project $project, ProjectSettingService $settings, PathParameterResolver $pathParameters): RedirectResponse
    {
        $settings->reset($project);
        $pathParameters->updateProjectDefaultsFromText($project, '');

        return redirect()
            ->route('projects.settings.edit', $project)
            ->with('success', __('messages.project_settings.reset_done'));
    }

    public function export(Project $project, ProjectSettingService $settings, PathParameterResolver $pathParameters): JsonResponse
    {
        $project->load(['assertionRules.endpoint']);

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'base_url' => $project->base_url,
            ],
            'version' => config('aptoria.version'),
            'settings' => $settings->export($project),
            'path_parameter_defaults' => $pathParameters->formatText($project),
            'default_assertion_rules' => $project->assertionRules
                ->whereNull('endpoint_id')
                ->map(fn ($rule): array => [
                    'rule_key' => $rule->rule_key,
                    'operator' => $rule->operator,
                    'expected_value' => $rule->expected_value,
                    'severity' => $rule->severity,
                    'enabled' => (bool) $rule->enabled,
                ])
                ->values()
                ->all(),
        ]);
    }
}
