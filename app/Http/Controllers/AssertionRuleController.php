<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssertionRuleRequest;
use App\Models\EndpointAssertionRule;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use App\Services\Settings\SettingService;
use Illuminate\View\View;

class AssertionRuleController extends Controller
{
    public function create(Project $project, SettingService $settings): View
    {
        $project->load('endpoints');
        $endpointId = request()->integer('endpoint_id') ?: null;

        return view('assertion_rules.create', [
            'project' => $project,
            'rule' => new EndpointAssertionRule([
                'project_id' => $project->id,
                'endpoint_id' => $endpointId,
                'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
                'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
                'target_path' => null,
                'expected_value' => (string) $settings->integer('assertions.default_status_code', 200),
                'severity' => EndpointAssertionRule::SEVERITY_FAIL,
                'enabled' => true,
            ]),
        ]);
    }

    public function store(AssertionRuleRequest $request, Project $project): RedirectResponse
    {
        $rule = $project->assertionRules()->create($this->payload($request));

        return $this->redirectAfterMutation($project, $rule)
            ->with('success', __('messages.assertions.created'));
    }

    public function edit(Project $project, EndpointAssertionRule $assertionRule): View
    {
        $this->ensureRuleBelongsToProject($project, $assertionRule);
        $project->load('endpoints');

        return view('assertion_rules.edit', [
            'project' => $project,
            'rule' => $assertionRule,
        ]);
    }

    public function update(AssertionRuleRequest $request, Project $project, EndpointAssertionRule $assertionRule): RedirectResponse
    {
        $this->ensureRuleBelongsToProject($project, $assertionRule);
        $assertionRule->update($this->payload($request));

        return $this->redirectAfterMutation($project, $assertionRule)
            ->with('success', __('messages.assertions.updated'));
    }

    public function destroy(Project $project, EndpointAssertionRule $assertionRule): RedirectResponse
    {
        $this->ensureRuleBelongsToProject($project, $assertionRule);
        $endpoint = $assertionRule->endpoint;
        $assertionRule->delete();

        $redirect = $endpoint
            ? redirect()->route('projects.endpoints.show', [$project, $endpoint])
            : redirect()->route('projects.settings.edit', $project);

        return $redirect->with('success', __('messages.assertions.deleted'));
    }

    private function payload(AssertionRuleRequest $request): array
    {
        $validated = $request->validated();

        return [
            'endpoint_id' => $validated['endpoint_id'] ?? null,
            'rule_key' => $validated['rule_key'],
            'operator' => $validated['operator'],
            'target_path' => trim((string) ($validated['target_path'] ?? '')) ?: null,
            'expected_value' => trim((string) ($validated['expected_value'] ?? '')) ?: null,
            'severity' => $validated['severity'],
            'enabled' => $request->boolean('enabled'),
        ];
    }

    private function redirectAfterMutation(Project $project, EndpointAssertionRule $rule): RedirectResponse
    {
        return $rule->endpoint_id
            ? redirect()->route('projects.endpoints.show', [$project, $rule->endpoint_id])
            : redirect()->route('projects.settings.edit', $project);
    }

    private function ensureRuleBelongsToProject(Project $project, EndpointAssertionRule $rule): void
    {
        abort_unless($rule->project_id === $project->id, 404);
    }
}
