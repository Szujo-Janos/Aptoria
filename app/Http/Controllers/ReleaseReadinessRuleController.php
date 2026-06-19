<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReleaseReadinessRule;
use App\Services\AuditLogger;
use App\Services\ReleaseReadinessProfileService;
use App\Services\ReleaseReadinessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReleaseReadinessRuleController extends Controller
{
    public function index(Project $project, ReleaseReadinessProfileService $profileService): View
    {
        ReleaseReadinessRule::syncDefaults($project);

        return $this->view($project, $profileService);
    }

    public function simulate(Request $request, Project $project, ReleaseReadinessProfileService $profileService, ReleaseReadinessService $readinessService): View
    {
        ReleaseReadinessRule::syncDefaults($project);
        $payload = $this->rulesPayload($request);
        $simulation = $readinessService->simulate($project, $payload);

        return $this->view($project, $profileService, $simulation, $payload);
    }

    public function applyProfile(Request $request, Project $project, ReleaseReadinessProfileService $profileService, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate(['profile_key' => ['required', 'string']]);
        $summary = $profileService->apply($project, $data['profile_key']);

        $auditLogger->record('release_readiness_profile_applied', __('messages.audit_messages.release_readiness_profile_applied'), $project, [
            'profile_key' => $summary['profile_key'],
            'deviation_count' => $summary['deviation_count'],
        ], 'release', 'info');

        return redirect()->route('projects.release-readiness.rules.index', $project)->with('status', __('messages.release_readiness.profile_applied'));
    }

    public function update(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        ReleaseReadinessRule::syncDefaults($project);
        $payload = $this->rulesPayload($request);

        foreach ($payload as $ruleId => $ruleData) {
            $rule = $project->releaseReadinessRules()->whereKey((int) $ruleId)->first();
            if (! $rule) {
                continue;
            }

            $rule->update([
                'enabled' => (bool) ($ruleData['enabled'] ?? false),
                'failure_level' => $ruleData['failure_level'],
                'metadata_json' => array_merge(is_array($rule->metadata_json) ? $rule->metadata_json : [], ['manually_customized_at' => now()->toDateTimeString()]),
            ]);
        }

        $auditLogger->record('release_readiness_rules_updated', __('messages.audit_messages.release_readiness_rules_updated'), $project, [
            'enabled' => $project->releaseReadinessRules()->where('enabled', true)->count(),
            'disabled' => $project->releaseReadinessRules()->where('enabled', false)->count(),
        ], 'release', 'info');

        return redirect()->route('projects.release-readiness.rules.index', $project)->with('status', __('messages.release_readiness.rules_updated'));
    }

    public function reset(Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        ReleaseReadinessRule::syncDefaults($project);

        foreach ($project->releaseReadinessRules as $rule) {
            $rule->update([
                'enabled' => true,
                'failure_level' => $rule->default_failure_level,
            ]);
        }

        $auditLogger->record('release_readiness_rules_reset', __('messages.audit_messages.release_readiness_rules_reset'), $project, [], 'release', 'warning');

        return redirect()->route('projects.release-readiness.rules.index', $project)->with('status', __('messages.release_readiness.rules_reset'));
    }

    private function view(Project $project, ReleaseReadinessProfileService $profileService, ?array $simulation = null, ?array $payload = null): View
    {
        $rules = $project->releaseReadinessRules()->orderBy('sort_order')->get();
        if ($payload) {
            $rules->each(function (ReleaseReadinessRule $rule) use ($payload): void {
                if (isset($payload[$rule->id])) {
                    $rule->enabled = (bool) ($payload[$rule->id]['enabled'] ?? false);
                    $rule->failure_level = (string) ($payload[$rule->id]['failure_level'] ?? $rule->failure_level);
                }
            });
        }

        return view('release_readiness.rules', [
            'project' => $project,
            'rulesByCategory' => $rules->groupBy('category'),
            'profiles' => $profileService->profiles(),
            'profileSummary' => $profileService->summary($project),
            'simulation' => $simulation,
        ]);
    }

    private function rulesPayload(Request $request): array
    {
        $data = $request->validate([
            'rules' => ['required', 'array'],
            'rules.*.enabled' => ['nullable', 'boolean'],
            'rules.*.failure_level' => ['required', 'string', 'in:blocker,warning'],
        ]);

        return $data['rules'];
    }
}
