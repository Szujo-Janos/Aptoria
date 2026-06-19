<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Project;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssertionRuleController extends Controller
{
    public function index(Project $project): View
    {
        $rules = $project->assertionRules()
            ->with('endpoint')
            ->latest()
            ->get();

        return view('assertions.index', [
            'project' => $project,
            'rules' => $rules,
            'endpoints' => $project->endpoints()->orderBy('method')->orderBy('path')->get(),
            'metrics' => [
                'total' => $rules->count(),
                'enabled' => $rules->where('enabled', true)->count(),
                'project_level' => $rules->whereNull('endpoint_id')->count(),
                'blockers' => $rules->where('severity', 'blocker')->count(),
            ],
        ]);
    }

    public function store(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $this->validated($request, $project);
        $rule = $project->assertionRules()->create($data);

        $auditLogger->record('created', __('messages.audit_messages.assertion_rule_created'), $project, [
            'assertion_rule_id' => $rule->id,
            'rule_key' => $rule->rule_key,
            'severity' => $rule->severity,
        ], 'assertion');

        return redirect()->route('projects.assertions.index', $project)->with('status', __('messages.assertions.created'));
    }

    public function update(Request $request, Project $project, EndpointAssertionRule $assertion, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $assertion);
        $before = $assertion->only(['name', 'rule_key', 'operator', 'expected_value', 'severity', 'enabled']);
        $assertion->update($this->validated($request, $project));

        $auditLogger->record('updated', __('messages.audit_messages.assertion_rule_updated'), $project, [
            'assertion_rule_id' => $assertion->id,
            'before' => $before,
            'after' => $assertion->only(['name', 'rule_key', 'operator', 'expected_value', 'severity', 'enabled']),
        ], 'assertion');

        return redirect()->route('projects.assertions.index', $project)->with('status', __('messages.assertions.updated'));
    }

    public function destroy(Project $project, EndpointAssertionRule $assertion, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $assertion);

        $auditLogger->record('deleted', __('messages.audit_messages.assertion_rule_deleted'), $project, [
            'assertion_rule_id' => $assertion->id,
            'name' => $assertion->name,
        ], 'assertion', 'warning');

        $assertion->delete();

        return redirect()->route('projects.assertions.index', $project)->with('status', __('messages.assertions.deleted'));
    }

    private function validated(Request $request, Project $project): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'endpoint_id' => ['nullable', 'integer'],
            'rule_key' => ['required', Rule::in(EndpointAssertionRule::RULE_KEYS)],
            'operator' => ['required', Rule::in(EndpointAssertionRule::OPERATORS)],
            'expected_value' => ['required', 'string', 'max:1000'],
            'target_path' => ['nullable', 'string', 'max:255'],
            'severity' => ['required', Rule::in(EndpointAssertionRule::SEVERITIES)],
            'description' => ['nullable', 'string', 'max:3000'],
        ]);

        $data['enabled'] = $request->boolean('enabled', true);
        $data['endpoint_id'] = $this->validEndpointId($project, $data['endpoint_id'] ?? null);

        return $data;
    }

    private function validEndpointId(Project $project, mixed $endpointId): ?int
    {
        if (! $endpointId) {
            return null;
        }

        return Endpoint::query()->where('project_id', $project->id)->where('id', $endpointId)->exists()
            ? (int) $endpointId
            : null;
    }

    private function ensureBelongsToProject(Project $project, EndpointAssertionRule $assertion): void
    {
        abort_unless((int) $assertion->project_id === (int) $project->id, 404);
    }
}
