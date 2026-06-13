<?php

namespace App\Services\ReleaseWorkflow;

use App\Models\AuditLog;
use App\Models\ClientPortalAcknowledgement;
use App\Models\Finding;
use App\Models\Project;
use App\Models\QaReleaseGate;
use App\Models\ReleaseDecision;
use App\Models\ReleaseWorkflow;
use App\Models\ReleaseWorkflowStep;
use App\Models\ReportVersion;
use App\Models\RiskAcceptance;
use App\Models\ScanRun;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\BlindSpots\QaBlindSpotDetectorService;
use App\Services\Cockpit\QaCockpitService;
use App\Services\ReleaseReadinessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WorkflowConsolidationService
{
    /** @var array<int, string> */
    private array $stepOrder = [
        'project_setup',
        'endpoint_inventory',
        'latest_scan',
        'blind_spots',
        'critical_high_triage',
        'fixed_retested',
        'accepted_risks',
        'release_readiness',
        'release_gate',
        'release_decision',
        'report_generated',
        'report_reviewed',
        'report_approved',
        'client_handoff',
        'client_acknowledgement',
    ];

    public function __construct(
        private readonly ReleaseReadinessService $readiness,
        private readonly QaBlindSpotDetectorService $blindSpots,
        private readonly QaCockpitService $cockpit,
    ) {
    }

    /** @return array<string, mixed> */
    public function summarize(Project $project): array
    {
        $context = $this->context($project);
        $steps = collect($this->stepOrder)
            ->map(fn (string $key): array => $this->evaluateStep($key, $project, $context))
            ->values();

        $workflow = $this->persistSnapshot($project, $steps, $context);
        $steps = $this->applyStoredStepState($workflow, $steps);
        $summary = $this->summary($steps);
        $nextAction = $steps->first(fn (array $step): bool => $step['state'] !== ReleaseWorkflow::STATE_COMPLETED && $step['state'] !== ReleaseWorkflow::STATE_SKIPPED);

        AuditLogService::withoutRecording(function () use ($workflow, $steps, $summary, $context, $nextAction): void {
            $workflow->forceFill([
                'overall_state' => $summary['overall_state'],
                'progress_percent' => $summary['progress_percent'],
                'completed_steps' => $summary['completed'],
                'blocked_steps' => $summary['blocked'],
                'needs_review_steps' => $summary['needs_review'],
                'ready_steps' => $summary['ready'],
                'not_started_steps' => $summary['not_started'],
                'skipped_steps' => $summary['skipped_with_reason'],
                'blocker_count' => $summary['blocker_count'],
                'missing_evidence_count' => $summary['missing_evidence_count'],
                'next_step_key' => $nextAction['key'] ?? null,
                'snapshot_json' => $this->snapshotArray($steps, $summary, $context),
                'evaluated_at' => now(),
            ])->save();
        });

        return [
            'generated_at' => now(),
            'workflow_record' => $workflow->fresh(['steps']),
            'readiness' => $context['readiness'],
            'blind_spots' => $context['blind_spots'],
            'cockpit' => $context['cockpit'],
            'steps' => $steps,
            'summary' => $summary,
            'next_action' => $nextAction,
            'precheck' => $this->precheck($steps),
            'latest' => [
                'scan' => $context['latest_scan'],
                'release_gate' => $context['latest_gate'],
                'release_decision' => $context['latest_decision'],
                'report' => $context['latest_report'],
                'approved_report' => $context['latest_approved_report'],
                'client_portal' => $context['latest_client_portal'],
                'client_acknowledgement' => $context['latest_acknowledgement'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function snapshotForDecision(Project $project): array
    {
        $summary = $this->summarize($project);

        return [
            'workflow_state' => $summary['summary']['overall_state'],
            'progress_percent' => $summary['summary']['progress_percent'],
            'blocker_count' => $summary['summary']['blocker_count'],
            'missing_evidence_count' => $summary['summary']['missing_evidence_count'],
            'next_step_key' => $summary['next_action']['key'] ?? null,
            'steps' => $summary['steps']->map(fn (array $step): array => [
                'key' => $step['key'],
                'label' => $step['label'],
                'state' => $step['state'],
                'blocker_count' => $step['blocker_count'],
                'missing_evidence_count' => $step['missing_evidence_count'],
                'required_action' => $step['required_action'],
            ])->values()->all(),
            'precheck_failures' => $summary['precheck']['failures'],
            'evaluated_at' => now()->toIso8601String(),
        ];
    }

    public function skipStep(Project $project, string $stepKey, string $reason, ?User $actor = null): ReleaseWorkflow
    {
        $summary = $this->summarize($project);
        $workflow = $summary['workflow_record'];
        $step = $workflow->steps()->where('step_key', $stepKey)->firstOrFail();

        $step->forceFill([
            'state' => ReleaseWorkflow::STATE_SKIPPED,
            'manual_state' => ReleaseWorkflow::STATE_SKIPPED,
            'manual_reason' => trim($reason),
            'skipped_at' => now(),
        ])->save();

        $this->recordWorkflowAudit($project, $actor, 'workflow_step_skipped', 'Workflow step skipped: '.$step->label, [
            'step_key' => $stepKey,
            'reason' => trim($reason),
        ]);

        return $this->summarize($project)['workflow_record'];
    }

    public function reopenStep(Project $project, string $stepKey, ?User $actor = null): ReleaseWorkflow
    {
        $summary = $this->summarize($project);
        $workflow = $summary['workflow_record'];
        $step = $workflow->steps()->where('step_key', $stepKey)->firstOrFail();

        $step->forceFill([
            'state' => $step->computed_state,
            'manual_state' => null,
            'manual_reason' => null,
            'skipped_at' => null,
            'reopened_at' => now(),
        ])->save();

        $this->recordWorkflowAudit($project, $actor, 'workflow_step_reopened', 'Workflow step reopened: '.$step->label, [
            'step_key' => $stepKey,
        ]);

        return $this->summarize($project)['workflow_record'];
    }

    /** @return array<string, mixed> */
    private function context(Project $project): array
    {
        $readiness = $this->readiness->summarize($project);
        $blindSpots = $this->blindSpots->summarize($project);
        $cockpit = $this->cockpit->summarize($project);

        return [
            'readiness' => $readiness,
            'blind_spots' => $blindSpots,
            'cockpit' => $cockpit,
            'endpoint_count' => $project->endpoints()->count(),
            'active_endpoint_count' => $project->endpoints()->where('is_active', true)->count(),
            'latest_scan' => $project->scanRuns()->latest()->first(),
            'critical_high_open_count' => $this->criticalHighOpenCount($project),
            'critical_high_untriaged_count' => $this->criticalHighUntriagedCount($project),
            'fixed_waiting_retest_count' => $this->fixedWaitingRetestCount($project),
            'critical_missing_evidence_count' => $this->criticalMissingEvidenceCount($project),
            'expired_risk_count' => $this->expiredAcceptedRiskCount($project),
            'active_risk_count' => $project->riskAcceptances()->where('status', RiskAcceptance::STATUS_ACTIVE)->count(),
            'risk_without_expiry_count' => $project->riskAcceptances()->where('status', RiskAcceptance::STATUS_ACTIVE)->whereNull('accepted_until')->count(),
            'expiring_risk_count' => $project->riskAcceptances()
                ->where('status', RiskAcceptance::STATUS_ACTIVE)
                ->whereNotNull('accepted_until')
                ->whereBetween('accepted_until', [now(), now()->addDays(14)])
                ->count(),
            'latest_gate' => $project->qaReleaseGates()->latest()->first(),
            'latest_decision' => $project->releaseDecisions()->latest()->first(),
            'latest_report' => $project->reportVersions()->latest()->first(),
            'latest_reviewed_report' => $project->reportVersions()
                ->whereIn('status', [ReportVersion::STATUS_REVIEWED, ReportVersion::STATUS_APPROVED])
                ->latest('reviewed_at')
                ->latest()
                ->first(),
            'latest_approved_report' => $project->reportVersions()
                ->where('status', ReportVersion::STATUS_APPROVED)
                ->latest('approved_at')
                ->latest()
                ->first(),
            'latest_client_portal' => $project->clientPortalAccesses()->latest()->first(),
            'latest_acknowledgement' => $project->clientPortalAcknowledgements()
                ->where('acknowledgement_type', ClientPortalAcknowledgement::TYPE_RELEASE_ACKNOWLEDGEMENT)
                ->latest('acknowledged_at')
                ->latest()
                ->first(),
        ];
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function evaluateStep(string $key, Project $project, array $context): array
    {
        return match ($key) {
            'project_setup' => $this->step(
                $key,
                $project->base_url ? ReleaseWorkflow::STATE_COMPLETED : ReleaseWorkflow::STATE_NEEDS_REVIEW,
                route('projects.settings.edit', $project),
                $project->base_url ? 0 : 1,
                $project->base_url ? 0 : 1,
                $project->base_url ? __('messages.release_workflow.actions.review_project_settings') : __('messages.release_workflow.actions.complete_project_settings'),
                [__('messages.release_workflow.criteria.project_has_base_url'), __('messages.release_workflow.criteria.project_is_active')],
                $project->base_url ? [] : [__('messages.release_workflow.blockers.missing_base_url')]
            ),
            'endpoint_inventory' => $this->step(
                $key,
                $context['endpoint_count'] > 0 ? ReleaseWorkflow::STATE_COMPLETED : ReleaseWorkflow::STATE_BLOCKED,
                route('projects.endpoint-inventory.index', $project),
                $context['endpoint_count'] > 0 ? 0 : 1,
                $context['endpoint_count'] > 0 ? 0 : 1,
                $context['endpoint_count'] > 0 ? __('messages.release_workflow.actions.review_endpoint_inventory') : __('messages.release_workflow.actions.add_endpoints'),
                [__('messages.release_workflow.criteria.endpoints_exist'), __('messages.release_workflow.criteria.endpoint_inventory_reviewed')],
                $context['endpoint_count'] > 0 ? [] : [__('messages.release_workflow.blockers.no_endpoints')],
                ['endpoints' => $context['endpoint_count'], 'active_endpoints' => $context['active_endpoint_count']]
            ),
            'latest_scan' => $this->latestScanStep($project, $context),
            'blind_spots' => $this->blindSpotStep($project, $context),
            'critical_high_triage' => $this->criticalHighTriageStep($project, $context),
            'fixed_retested' => $this->fixedRetestedStep($project, $context),
            'accepted_risks' => $this->acceptedRiskStep($project, $context),
            'release_readiness' => $this->releaseReadinessStep($project, $context),
            'release_gate' => $this->releaseGateStep($project, $context),
            'release_decision' => $this->releaseDecisionStep($project, $context),
            'report_generated' => $this->reportGeneratedStep($project, $context),
            'report_reviewed' => $this->reportReviewedStep($project, $context),
            'report_approved' => $this->reportApprovedStep($project, $context),
            'client_handoff' => $this->clientHandoffStep($project, $context),
            'client_acknowledgement' => $this->clientAcknowledgementStep($project, $context),
            default => $this->step($key, ReleaseWorkflow::STATE_NOT_STARTED, route('projects.show', $project), 0, 0, __('messages.release_workflow.actions.review_project'), []),
        };
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function latestScanStep(Project $project, array $context): array
    {
        $scan = $context['latest_scan'];
        if (! $scan) {
            return $this->step('latest_scan', ReleaseWorkflow::STATE_NOT_STARTED, route('projects.scans.create', $project), 1, 1, __('messages.release_workflow.actions.run_scan'), [__('messages.release_workflow.criteria.latest_scan_completed')], [__('messages.release_workflow.blockers.no_scan')]);
        }

        $state = match ($scan->status) {
            ScanRun::STATUS_COMPLETED => ReleaseWorkflow::STATE_COMPLETED,
            ScanRun::STATUS_FAILED => ReleaseWorkflow::STATE_BLOCKED,
            ScanRun::STATUS_RUNNING, ScanRun::STATUS_PENDING => ReleaseWorkflow::STATE_IN_PROGRESS,
            default => ReleaseWorkflow::STATE_NEEDS_REVIEW,
        };

        return $this->step(
            'latest_scan',
            $state,
            route('projects.scans.show', [$project, $scan]),
            $scan->status === ScanRun::STATUS_FAILED ? 1 : 0,
            $scan->status === ScanRun::STATUS_COMPLETED ? 0 : 1,
            $scan->status === ScanRun::STATUS_COMPLETED ? __('messages.release_workflow.actions.review_latest_scan') : __('messages.release_workflow.actions.run_scan'),
            [__('messages.release_workflow.criteria.latest_scan_completed'), __('messages.release_workflow.criteria.scan_not_failed')],
            $scan->status === ScanRun::STATUS_FAILED ? [__('messages.release_workflow.blockers.latest_scan_failed')] : [],
            ['scan_id' => $scan->id, 'status' => $scan->status, 'finished_at' => $scan->finished_at?->toIso8601String()]
        );
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function blindSpotStep(Project $project, array $context): array
    {
        $releaseBlockers = (int) ($context['blind_spots']['summary']['release_blockers'] ?? 0);
        $total = (int) ($context['blind_spots']['summary']['total'] ?? 0);
        $state = $releaseBlockers > 0 ? ReleaseWorkflow::STATE_BLOCKED : ($total > 0 ? ReleaseWorkflow::STATE_NEEDS_REVIEW : ReleaseWorkflow::STATE_COMPLETED);

        return $this->step('blind_spots', $state, route('projects.blind-spots.index', $project), $releaseBlockers, $releaseBlockers, __('messages.release_workflow.actions.review_blind_spots'), [__('messages.release_workflow.criteria.no_release_blocking_blind_spots')], $releaseBlockers > 0 ? [__('messages.release_workflow.blockers.release_blocking_blind_spots')] : [], ['blind_spots' => $total, 'release_blockers' => $releaseBlockers]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function criticalHighTriageStep(Project $project, array $context): array
    {
        $untriaged = (int) $context['critical_high_untriaged_count'];
        $open = (int) $context['critical_high_open_count'];
        $state = $untriaged > 0 ? ReleaseWorkflow::STATE_BLOCKED : ($open > 0 ? ReleaseWorkflow::STATE_NEEDS_REVIEW : ReleaseWorkflow::STATE_COMPLETED);

        return $this->step('critical_high_triage', $state, route('projects.findings.index', $project), $untriaged, $untriaged, __('messages.release_workflow.actions.triage_critical_high'), [__('messages.release_workflow.criteria.critical_high_triaged')], $untriaged > 0 ? [__('messages.release_workflow.blockers.untriaged_critical_high_findings')] : [], ['open_critical_high' => $open, 'untriaged_critical_high' => $untriaged]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function fixedRetestedStep(Project $project, array $context): array
    {
        $waiting = (int) $context['fixed_waiting_retest_count'];
        $missingEvidence = (int) $context['critical_missing_evidence_count'];
        $state = $waiting > 0 || $missingEvidence > 0 ? ReleaseWorkflow::STATE_BLOCKED : ReleaseWorkflow::STATE_COMPLETED;

        return $this->step('fixed_retested', $state, route('projects.findings.index', $project), $waiting, $waiting + $missingEvidence, __('messages.release_workflow.actions.add_retest_evidence'), [__('messages.release_workflow.criteria.fixed_findings_verified'), __('messages.release_workflow.criteria.critical_findings_have_evidence')], array_filter([$waiting > 0 ? __('messages.release_workflow.blockers.fixed_not_verified') : null, $missingEvidence > 0 ? __('messages.release_workflow.blockers.critical_missing_evidence') : null]), ['waiting_for_retest' => $waiting, 'critical_missing_evidence' => $missingEvidence]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function acceptedRiskStep(Project $project, array $context): array
    {
        $expired = (int) $context['expired_risk_count'];
        $withoutExpiry = (int) $context['risk_without_expiry_count'];
        $expiring = (int) $context['expiring_risk_count'];
        $active = (int) $context['active_risk_count'];
        $state = $expired > 0 ? ReleaseWorkflow::STATE_BLOCKED : (($withoutExpiry + $expiring) > 0 ? ReleaseWorkflow::STATE_NEEDS_REVIEW : ($active > 0 ? ReleaseWorkflow::STATE_COMPLETED : ReleaseWorkflow::STATE_READY));

        return $this->step('accepted_risks', $state, route('projects.risk-acceptances.index', $project), $expired, $expired, __('messages.release_workflow.actions.review_accepted_risks'), [__('messages.release_workflow.criteria.accepted_risks_not_expired'), __('messages.release_workflow.criteria.accepted_risks_reviewed')], $expired > 0 ? [__('messages.release_workflow.blockers.expired_accepted_risk')] : [], ['active' => $active, 'expired' => $expired, 'without_expiry' => $withoutExpiry, 'expiring' => $expiring]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function releaseReadinessStep(Project $project, array $context): array
    {
        $score = (int) ($context['readiness']['score'] ?? 0);
        $blockers = is_countable($context['readiness']['blocking_issues'] ?? null) ? count($context['readiness']['blocking_issues']) : 0;
        $state = $blockers > 0 ? ReleaseWorkflow::STATE_BLOCKED : ($score >= 80 ? ReleaseWorkflow::STATE_COMPLETED : ReleaseWorkflow::STATE_NEEDS_REVIEW);

        return $this->step('release_readiness', $state, route('projects.release-readiness.show', $project), $blockers, $blockers, __('messages.release_workflow.actions.review_release_readiness'), [__('messages.release_workflow.criteria.readiness_calculated'), __('messages.release_workflow.criteria.no_readiness_blockers')], $blockers > 0 ? array_values($context['readiness']['blocking_issues'] ?? []) : [], ['score' => $score, 'status' => $context['readiness']['status'] ?? null]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function releaseGateStep(Project $project, array $context): array
    {
        $gate = $context['latest_gate'];
        if (! $gate) {
            return $this->step('release_gate', ReleaseWorkflow::STATE_NOT_STARTED, route('projects.release-gates.create', $project), 1, 1, __('messages.release_workflow.actions.create_release_gate'), [__('messages.release_workflow.criteria.release_gate_created')], [__('messages.release_workflow.blockers.no_release_gate')]);
        }

        $state = match ($gate->final_decision) {
            QaReleaseGate::DECISION_BLOCKED => ReleaseWorkflow::STATE_BLOCKED,
            QaReleaseGate::DECISION_PENDING => ReleaseWorkflow::STATE_NEEDS_REVIEW,
            QaReleaseGate::DECISION_PASS, QaReleaseGate::DECISION_CONDITIONAL_PASS => ReleaseWorkflow::STATE_COMPLETED,
            default => ReleaseWorkflow::STATE_NEEDS_REVIEW,
        };

        return $this->step('release_gate', $state, route('projects.release-gates.show', [$project, $gate]), (int) $gate->blocker_count, (int) $gate->blocker_count, __('messages.release_workflow.actions.evaluate_release_gate'), [__('messages.release_workflow.criteria.release_gate_evaluated')], $gate->final_decision === QaReleaseGate::DECISION_BLOCKED ? [__('messages.release_workflow.blockers.release_gate_blocked')] : [], ['gate_id' => $gate->id, 'decision' => $gate->final_decision, 'score' => $gate->score]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function releaseDecisionStep(Project $project, array $context): array
    {
        $decision = $context['latest_decision'];
        if (! $decision) {
            return $this->step('release_decision', ReleaseWorkflow::STATE_NOT_STARTED, route('projects.release-decisions.index', $project), 1, 1, __('messages.release_workflow.actions.finalize_release_decision'), [__('messages.release_workflow.criteria.release_decision_finalized')], [__('messages.release_workflow.blockers.no_release_decision')]);
        }

        $state = match ($decision->decision_status) {
            ReleaseDecision::STATUS_GO, ReleaseDecision::STATUS_CONDITIONAL_GO => ReleaseWorkflow::STATE_COMPLETED,
            ReleaseDecision::STATUS_NO_GO, ReleaseDecision::STATUS_BLOCKED => ReleaseWorkflow::STATE_BLOCKED,
            ReleaseDecision::STATUS_PENDING_EVIDENCE => ReleaseWorkflow::STATE_NEEDS_REVIEW,
            default => ReleaseWorkflow::STATE_NEEDS_REVIEW,
        };

        return $this->step('release_decision', $state, route('projects.release-decisions.show', [$project, $decision]), $state === ReleaseWorkflow::STATE_BLOCKED ? 1 : 0, $state === ReleaseWorkflow::STATE_COMPLETED ? 0 : 1, __('messages.release_workflow.actions.finalize_release_decision'), [__('messages.release_workflow.criteria.release_decision_finalized')], $state === ReleaseWorkflow::STATE_BLOCKED ? [__('messages.release_workflow.blockers.release_decision_no_go')] : [], ['decision_id' => $decision->id, 'decision_status' => $decision->decision_status]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function reportGeneratedStep(Project $project, array $context): array
    {
        $report = $context['latest_report'];
        return $this->step('report_generated', $report ? ReleaseWorkflow::STATE_COMPLETED : ReleaseWorkflow::STATE_NOT_STARTED, route('projects.report-versions.index', $project), $report ? 0 : 1, $report ? 0 : 1, __('messages.release_workflow.actions.generate_report'), [__('messages.release_workflow.criteria.report_generated')], $report ? [] : [__('messages.release_workflow.blockers.no_report_generated')], ['report_id' => $report?->id, 'status' => $report?->status]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function reportReviewedStep(Project $project, array $context): array
    {
        $report = $context['latest_report'];
        $reviewed = $context['latest_reviewed_report'];
        $state = $reviewed ? ReleaseWorkflow::STATE_COMPLETED : ($report ? ReleaseWorkflow::STATE_NEEDS_REVIEW : ReleaseWorkflow::STATE_NOT_STARTED);

        return $this->step('report_reviewed', $state, route('projects.report-versions.index', $project), 0, $reviewed ? 0 : 1, __('messages.release_workflow.actions.review_report'), [__('messages.release_workflow.criteria.report_reviewed')], [], ['report_id' => $reviewed?->id ?? $report?->id, 'status' => $reviewed?->status ?? $report?->status]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function reportApprovedStep(Project $project, array $context): array
    {
        $report = $context['latest_report'];
        $approved = $context['latest_approved_report'];
        $state = $approved ? ReleaseWorkflow::STATE_COMPLETED : ($report ? ReleaseWorkflow::STATE_NEEDS_REVIEW : ReleaseWorkflow::STATE_NOT_STARTED);

        return $this->step('report_approved', $state, route('projects.report-versions.index', $project), 0, $approved ? 0 : 1, __('messages.release_workflow.actions.approve_report'), [__('messages.release_workflow.criteria.report_approved')], [], ['report_id' => $approved?->id ?? $report?->id, 'status' => $approved?->status ?? $report?->status]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function clientHandoffStep(Project $project, array $context): array
    {
        $portal = $context['latest_client_portal'];
        $available = $portal && $portal->isAvailable();
        $approvedReport = (bool) $context['latest_approved_report'];
        $decision = (bool) $context['latest_decision'];
        $state = $available ? ReleaseWorkflow::STATE_COMPLETED : ($approvedReport && $decision ? ReleaseWorkflow::STATE_READY : ReleaseWorkflow::STATE_NOT_STARTED);

        return $this->step('client_handoff', $state, route('projects.client-portal.index', $project), 0, $available ? 0 : 1, __('messages.release_workflow.actions.prepare_client_handoff'), [__('messages.release_workflow.criteria.portal_link_available'), __('messages.release_workflow.criteria.handoff_has_decision_and_report')], [], ['portal_id' => $portal?->id, 'available' => $available]);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function clientAcknowledgementStep(Project $project, array $context): array
    {
        $ack = $context['latest_acknowledgement'];
        $portal = $context['latest_client_portal'];
        $state = $ack ? ReleaseWorkflow::STATE_COMPLETED : (($portal && $portal->isAvailable()) ? ReleaseWorkflow::STATE_READY : ReleaseWorkflow::STATE_NOT_STARTED);

        return $this->step('client_acknowledgement', $state, route('projects.client-portal.index', $project), 0, $ack ? 0 : 1, __('messages.release_workflow.actions.wait_client_acknowledgement'), [__('messages.release_workflow.criteria.client_acknowledgement_received')], [], ['acknowledgement_id' => $ack?->id, 'portal_id' => $portal?->id]);
    }

    /** @param array<int, string> $criteria @param array<int, string> $blockers @param array<string, mixed> $evidence @return array<string, mixed> */
    private function step(string $key, string $state, string $url, int $blockers, int $missingEvidence, string $requiredAction, array $criteria = [], array $blockersList = [], array $evidence = []): array
    {
        return [
            'key' => $key,
            'label' => __('messages.release_workflow.steps.'.$key),
            'description' => __('messages.release_workflow.step_descriptions.'.$key),
            'state' => $state,
            'computed_state' => $state,
            'state_label' => __('messages.release_workflow.states.'.$state),
            'status' => $this->legacyStatus($state),
            'status_label' => __('messages.release_workflow.states.'.$state),
            'status_css' => $this->stateCss($state),
            'state_css' => $this->stateCss($state),
            'blocker_count' => $blockers,
            'missing_evidence_count' => $missingEvidence,
            'value' => $this->valueLabel($state, $blockers, $missingEvidence),
            'url' => $url,
            'required_action' => $requiredAction,
            'suggested_action_label' => __('messages.release_workflow.open_step'),
            'suggested_action_url' => $url,
            'completion_criteria' => array_values($criteria),
            'blocker_reasons' => array_values($blockersList),
            'evidence_summary' => $evidence,
            'manual_reason' => null,
            'is_manual' => false,
        ];
    }

    /** @param Collection<int, array<string, mixed>> $steps */
    private function persistSnapshot(Project $project, Collection $steps, array $context): ReleaseWorkflow
    {
        return AuditLogService::withoutRecording(function () use ($project, $steps): ReleaseWorkflow {
            $workflow = ReleaseWorkflow::query()->firstOrCreate(['project_id' => $project->id], ['evaluated_at' => now()]);

            foreach ($steps as $step) {
                $existing = $workflow->steps()->where('step_key', $step['key'])->first();
                $manualState = $existing?->manual_state;
                $manualReason = $existing?->manual_reason;
                $state = $manualState ?: $step['state'];

                $attributes = [
                    'project_id' => $project->id,
                    'label' => $step['label'],
                    'state' => $state,
                    'computed_state' => $step['computed_state'],
                    'manual_state' => $manualState,
                    'manual_reason' => $manualReason,
                    'blocker_count' => $step['blocker_count'],
                    'missing_evidence_count' => $step['missing_evidence_count'],
                    'required_action' => $step['required_action'],
                    'suggested_action_label' => $step['suggested_action_label'],
                    'suggested_action_url' => $step['suggested_action_url'],
                    'completion_criteria_json' => $step['completion_criteria'],
                    'blocker_reasons_json' => $step['blocker_reasons'],
                    'evidence_summary_json' => $step['evidence_summary'],
                    'completed_at' => $state === ReleaseWorkflow::STATE_COMPLETED ? ($existing?->completed_at ?: now()) : null,
                ];

                $workflow->steps()->updateOrCreate(['step_key' => $step['key']], $attributes);
            }

            return $workflow->fresh(['steps']);
        });
    }

    /** @param ReleaseWorkflow $workflow @param Collection<int, array<string, mixed>> $steps @return Collection<int, array<string, mixed>> */
    private function applyStoredStepState(ReleaseWorkflow $workflow, Collection $steps): Collection
    {
        $records = $workflow->steps->keyBy('step_key');

        return $steps->map(function (array $step) use ($records): array {
            $record = $records->get($step['key']);
            if (! $record instanceof ReleaseWorkflowStep) {
                return $step;
            }

            $step['state'] = $record->state;
            $step['state_label'] = $record->state_label;
            $step['status'] = $this->legacyStatus($record->state);
            $step['status_label'] = $record->state_label;
            $step['state_css'] = $record->state_css;
            $step['status_css'] = $record->state_css;
            $step['manual_reason'] = $record->manual_reason;
            $step['is_manual'] = $record->manual_state !== null;
            $step['completed_at'] = $record->completed_at;
            $step['skipped_at'] = $record->skipped_at;
            $step['reopened_at'] = $record->reopened_at;

            return $step;
        })->values();
    }

    /** @param Collection<int, array<string, mixed>> $steps @return array<string, mixed> */
    private function summary(Collection $steps): array
    {
        $total = max(1, $steps->count());
        $completedEquivalent = $steps->whereIn('state', [ReleaseWorkflow::STATE_COMPLETED, ReleaseWorkflow::STATE_SKIPPED])->count();
        $blocked = $steps->where('state', ReleaseWorkflow::STATE_BLOCKED)->count();
        $needsReview = $steps->where('state', ReleaseWorkflow::STATE_NEEDS_REVIEW)->count();
        $ready = $steps->where('state', ReleaseWorkflow::STATE_READY)->count();
        $notStarted = $steps->where('state', ReleaseWorkflow::STATE_NOT_STARTED)->count();
        $inProgress = $steps->where('state', ReleaseWorkflow::STATE_IN_PROGRESS)->count();
        $skipped = $steps->where('state', ReleaseWorkflow::STATE_SKIPPED)->count();

        $overall = $blocked > 0
            ? ReleaseWorkflow::STATE_BLOCKED
            : ($needsReview > 0 || $inProgress > 0 ? ReleaseWorkflow::STATE_NEEDS_REVIEW : ($ready > 0 || $notStarted > 0 ? ReleaseWorkflow::STATE_READY : ReleaseWorkflow::STATE_COMPLETED));

        return [
            'total' => $steps->count(),
            'complete' => $steps->where('state', ReleaseWorkflow::STATE_COMPLETED)->count(),
            'completed' => $steps->where('state', ReleaseWorkflow::STATE_COMPLETED)->count(),
            'warning' => $needsReview + $inProgress + $ready,
            'blocked' => $blocked,
            'missing' => $notStarted,
            'not_started' => $notStarted,
            'in_progress' => $inProgress,
            'needs_review' => $needsReview,
            'ready' => $ready,
            'skipped_with_reason' => $skipped,
            'blocker_count' => (int) $steps->sum('blocker_count'),
            'missing_evidence_count' => (int) $steps->sum('missing_evidence_count'),
            'progress_percent' => (int) round(($completedEquivalent / $total) * 100),
            'overall_state' => $overall,
            'overall_status' => $this->legacyStatus($overall),
            'overall_state_label' => __('messages.release_workflow.states.'.$overall),
            'overall_state_css' => $this->stateCss($overall),
        ];
    }

    /** @param Collection<int, array<string, mixed>> $steps @return array<string, mixed> */
    private function precheck(Collection $steps): array
    {
        $failures = $steps
            ->filter(fn (array $step): bool => in_array($step['state'], [ReleaseWorkflow::STATE_BLOCKED, ReleaseWorkflow::STATE_NOT_STARTED, ReleaseWorkflow::STATE_NEEDS_REVIEW], true))
            ->map(fn (array $step): array => [
                'key' => $step['key'],
                'label' => $step['label'],
                'state' => $step['state'],
                'state_label' => $step['state_label'],
                'required_action' => $step['required_action'],
                'blockers' => $step['blocker_reasons'],
            ])
            ->values();

        return [
            'passed' => $failures->isEmpty(),
            'failures' => $failures->all(),
            'failure_count' => $failures->count(),
        ];
    }

    /** @param Collection<int, array<string, mixed>> $steps @param array<string, mixed> $summary @param array<string, mixed> $context @return array<string, mixed> */
    private function snapshotArray(Collection $steps, array $summary, array $context): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => $summary,
            'latest_ids' => [
                'scan_run_id' => $context['latest_scan']?->id,
                'release_gate_id' => $context['latest_gate']?->id,
                'release_decision_id' => $context['latest_decision']?->id,
                'report_version_id' => $context['latest_report']?->id,
                'approved_report_version_id' => $context['latest_approved_report']?->id,
                'client_portal_access_id' => $context['latest_client_portal']?->id,
                'client_acknowledgement_id' => $context['latest_acknowledgement']?->id,
            ],
            'steps' => $steps->map(fn (array $step): array => [
                'key' => $step['key'],
                'state' => $step['state'],
                'blocker_count' => $step['blocker_count'],
                'missing_evidence_count' => $step['missing_evidence_count'],
                'required_action' => $step['required_action'],
                'manual_reason' => $step['manual_reason'],
            ])->values()->all(),
        ];
    }

    private function criticalHighOpenCount(Project $project): int
    {
        return $project->findings()
            ->whereIn('severity', [Finding::SEVERITY_CRITICAL, Finding::SEVERITY_HIGH])
            ->whereIn('status', Finding::OPEN_STATUSES)
            ->count();
    }

    private function criticalHighUntriagedCount(Project $project): int
    {
        return $project->findings()
            ->whereIn('severity', [Finding::SEVERITY_CRITICAL, Finding::SEVERITY_HIGH])
            ->whereIn('status', [Finding::STATUS_OPEN, Finding::STATUS_REOPENED])
            ->count();
    }

    private function fixedWaitingRetestCount(Project $project): int
    {
        return $project->findings()
            ->where(function ($query): void {
                $query->whereIn('status', [Finding::STATUS_FIXED, Finding::STATUS_READY_FOR_RETEST, Finding::STATUS_RETEST_FAILED])
                    ->orWhere(function ($inner): void {
                        $inner->where('retest_required', true)
                            ->where('verification_status', '!=', Finding::VERIFICATION_VERIFIED);
                    });
            })
            ->where('status', '!=', Finding::STATUS_VERIFIED)
            ->count();
    }

    private function criticalMissingEvidenceCount(Project $project): int
    {
        return $project->findings()
            ->whereIn('severity', [Finding::SEVERITY_CRITICAL, Finding::SEVERITY_HIGH])
            ->whereDoesntHave('evidence')
            ->count();
    }

    private function expiredAcceptedRiskCount(Project $project): int
    {
        return $project->riskAcceptances()
            ->where('status', RiskAcceptance::STATUS_ACTIVE)
            ->whereNotNull('accepted_until')
            ->where('accepted_until', '<', now())
            ->count();
    }

    private function stateCss(string $state): string
    {
        return match ($state) {
            ReleaseWorkflow::STATE_COMPLETED, ReleaseWorkflow::STATE_READY => 'success',
            ReleaseWorkflow::STATE_NEEDS_REVIEW, ReleaseWorkflow::STATE_IN_PROGRESS, ReleaseWorkflow::STATE_SKIPPED => 'warning',
            ReleaseWorkflow::STATE_BLOCKED => 'danger',
            default => 'default',
        };
    }

    private function legacyStatus(string $state): string
    {
        return match ($state) {
            ReleaseWorkflow::STATE_COMPLETED => 'complete',
            ReleaseWorkflow::STATE_READY => 'ready',
            ReleaseWorkflow::STATE_NEEDS_REVIEW, ReleaseWorkflow::STATE_IN_PROGRESS, ReleaseWorkflow::STATE_SKIPPED => 'warning',
            ReleaseWorkflow::STATE_BLOCKED => 'blocked',
            default => 'missing',
        };
    }

    private function valueLabel(string $state, int $blockers, int $missingEvidence): string
    {
        if ($blockers > 0) {
            return trans_choice('messages.release_workflow.blocker_count', $blockers, ['count' => $blockers]);
        }

        if ($missingEvidence > 0) {
            return trans_choice('messages.release_workflow.missing_evidence_count', $missingEvidence, ['count' => $missingEvidence]);
        }

        return __('messages.release_workflow.states.'.$state);
    }

    /** @param array<string, mixed> $metadata */
    private function recordWorkflowAudit(Project $project, ?User $actor, string $action, string $summary, array $metadata = []): void
    {
        app(AuditLogService::class)->record([
            'project_id' => $project->id,
            'user_id' => $actor?->id,
            'event_type' => AuditLog::EVENT_SYSTEM,
            'action' => AuditLog::ACTION_UPDATED,
            'severity' => AuditLog::SEVERITY_NOTICE,
            'auditable_type' => ReleaseWorkflow::class,
            'auditable_id' => $project->releaseWorkflow?->id,
            'subject_label' => 'release workflow',
            'subject_name' => $project->name,
            'summary' => $summary,
            'metadata' => array_merge(['workflow_action' => $action], $metadata),
        ]);
    }
}
