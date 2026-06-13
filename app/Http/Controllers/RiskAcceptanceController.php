<?php

namespace App\Http\Controllers;

use App\Models\Finding;
use App\Models\Project;
use App\Models\RiskAcceptance;
use App\Services\Risk\RiskAcceptanceLedgerService;
use App\Services\Settings\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RiskAcceptanceController extends Controller
{
    public function index(Project $project, Request $request, RiskAcceptanceLedgerService $ledger, SettingService $settings): View
    {
        $status = (string) $request->query('status', 'active');
        $severity = (string) $request->query('severity', '');
        $expiry = (string) $request->query('expiry', '');

        $query = $project->riskAcceptances()
            ->with(['finding.endpoint', 'acceptedBy', 'renewedFrom'])
            ->when($status !== '' && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($severity !== '', fn ($query) => $query->whereHas('finding', fn ($findingQuery) => $findingQuery->where('severity', $severity)))
            ->when($expiry === 'missing', fn ($query) => $query->whereNull('accepted_until')->where('status', RiskAcceptance::STATUS_ACTIVE))
            ->when($expiry === 'expired', fn ($query) => $query->whereNotNull('accepted_until')->where('accepted_until', '<', now())->where('status', RiskAcceptance::STATUS_ACTIVE))
            ->when($expiry === 'soon', fn ($query) => $query->whereNotNull('accepted_until')->whereBetween('accepted_until', [now(), now()->addDays(14)])->where('status', RiskAcceptance::STATUS_ACTIVE));

        $acceptances = $query->latest('accepted_at')
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25))
            ->withQueryString();

        $summary = $ledger->summarize($project);

        return view('risk_acceptances.index', compact('project', 'acceptances', 'summary', 'status', 'severity', 'expiry'));
    }

    public function store(Request $request, Project $project, Finding $finding): RedirectResponse
    {
        $this->ensureFindingBelongsToProject($project, $finding);
        $this->authorizeProject($project, 'risk.accept');

        $data = $this->validatedPayload($request);
        $data['project_id'] = $project->id;
        $data['finding_id'] = $finding->id;
        $data['accepted_by_user_id'] = $request->user()?->id;
        $data['accepted_at'] = now();
        $data['status'] = RiskAcceptance::STATUS_ACTIVE;

        $active = $finding->riskAcceptances()->where('status', RiskAcceptance::STATUS_ACTIVE)->latest('accepted_at')->first();
        if ($active) {
            $active->forceFill(['status' => RiskAcceptance::STATUS_RENEWED])->save();
            $data['renewed_from_id'] = $active->id;
        }

        $acceptance = RiskAcceptance::query()->create($data);

        $finding->lifecycleEvents()->create([
            'project_id' => $project->id,
            'user_id' => $request->user()?->id,
            'from_status' => $finding->getOriginal('status'),
            'to_status' => Finding::STATUS_ACCEPTED_RISK,
            'note' => __('messages.risk_acceptances.lifecycle_note', ['id' => $acceptance->id]),
            'changed_at' => now(),
        ]);

        return redirect()
            ->route('projects.findings.show', [$project, $finding])
            ->with('success', __('messages.risk_acceptances.created'));
    }

    public function update(Request $request, Project $project, RiskAcceptance $riskAcceptance): RedirectResponse
    {
        $this->ensureAcceptanceBelongsToProject($project, $riskAcceptance);
        $this->authorizeProject($project, 'risk.accept');

        $data = $request->validate([
            'status' => ['required', Rule::in(RiskAcceptance::STATUSES)],
            'accepted_until' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:5000'],
            'business_justification' => ['nullable', 'string', 'max:5000'],
            'mitigation_note' => ['nullable', 'string', 'max:5000'],
            'evidence_requirement' => ['nullable', 'string', 'max:5000'],
            'release_scope' => ['nullable', 'string', 'max:180'],
            'expiry_action' => ['required', Rule::in(RiskAcceptance::EXPIRY_ACTIONS)],
        ]);

        $riskAcceptance->update($data);

        return redirect()
            ->route('projects.risk-acceptances.index', $project)
            ->with('success', __('messages.risk_acceptances.updated'));
    }

    /** @return array<string, mixed> */
    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'accepted_until' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:5000'],
            'business_justification' => ['nullable', 'string', 'max:5000'],
            'mitigation_note' => ['nullable', 'string', 'max:5000'],
            'evidence_requirement' => ['nullable', 'string', 'max:5000'],
            'release_scope' => ['nullable', 'string', 'max:180'],
            'expiry_action' => ['required', Rule::in(RiskAcceptance::EXPIRY_ACTIONS)],
        ]);
    }

    private function ensureFindingBelongsToProject(Project $project, Finding $finding): void
    {
        abort_unless($finding->project_id === $project->id, 404);
    }

    private function ensureAcceptanceBelongsToProject(Project $project, RiskAcceptance $riskAcceptance): void
    {
        abort_unless($riskAcceptance->project_id === $project->id, 404);
    }
}
