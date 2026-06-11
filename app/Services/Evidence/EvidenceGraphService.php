<?php

namespace App\Services\Evidence;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\QaReleaseGate;
use App\Models\ReleaseDecision;
use App\Models\RiskAcceptance;
use App\Services\BlindSpots\QaBlindSpotDetectorService;
use Illuminate\Support\Collection;

class EvidenceGraphService
{
    public function __construct(private readonly QaBlindSpotDetectorService $blindSpots)
    {
    }

    /** @return array<string, mixed> */
    public function summarize(Project $project): array
    {
        $endpoints = $project->endpoints()
            ->with([
                'latestScanResult.scanRun',
                'scanResults.scanRun',
                'assertionRules',
                'testCases.latestResult',
                'findings.evidence',
                'contractValidationResults.run',
                'producedBehaviorLinks.consumerEndpoint',
                'consumedBehaviorLinks.producerEndpoint',
            ])
            ->orderBy('path')
            ->orderBy('method')
            ->get();

        $findings = $project->findings()
            ->with([
                'endpoint',
                'scanRun',
                'scanResult',
                'contractValidationResult',
                'testCase',
                'evidence.capturedBy',
                'owner',
                'verifiedBy',
                'linkedReleaseGate',
                'activeRiskAcceptance',
                'riskAcceptances.acceptedBy',
            ])
            ->latest()
            ->get();

        $releaseGates = $project->qaReleaseGates()->with('items')->latest()->limit(5)->get();
        $releaseDecisions = $project->releaseDecisions()->with(['owner', 'releaseGate'])->latest('created_at')->limit(5)->get();
        $riskAcceptances = $project->riskAcceptances()->with(['finding.endpoint', 'acceptedBy'])->latest('accepted_at')->limit(20)->get();
        $blindSpotSummary = $this->blindSpots->summarize($project);

        $endpointMaps = $endpoints->map(fn (Endpoint $endpoint): array => $this->endpointMap($endpoint));
        $findingChains = $findings->map(fn (Finding $finding): array => $this->findingChain($finding));
        $releaseGraph = $this->releaseGraph($project, $releaseGates, $releaseDecisions, $riskAcceptances, $blindSpotSummary);
        $missingLinks = $this->missingLinks($endpointMaps, $findingChains, $releaseGraph);

        return [
            'summary' => [
                'endpoints' => $endpoints->count(),
                'endpoint_maps' => $endpointMaps->count(),
                'scan_results' => $project->scanResults()->count(),
                'snapshots' => $project->snapshots()->count(),
                'compare_runs' => $project->compareRuns()->count(),
                'findings' => $findings->count(),
                'finding_evidence' => $project->findingEvidence()->count(),
                'accepted_risks' => $project->riskAcceptances()->count(),
                'release_gates' => $project->qaReleaseGates()->count(),
                'release_decisions' => $project->releaseDecisions()->count(),
                'blind_spots' => (int) ($blindSpotSummary['summary']['total'] ?? 0),
                'missing_links' => $missingLinks->count(),
            ],
            'endpoint_maps' => $endpointMaps,
            'finding_chains' => $findingChains,
            'release_graph' => $releaseGraph,
            'risk_acceptances' => $riskAcceptances,
            'blind_spots' => $blindSpotSummary,
            'missing_links' => $missingLinks,
            'generated_at' => now(),
        ];
    }

    /** @return array<string, mixed> */
    public function endpointMap(Endpoint $endpoint): array
    {
        $endpoint->loadMissing([
            'latestScanResult.scanRun',
            'scanResults.scanRun',
            'assertionRules',
            'testCases.latestResult',
            'findings.evidence',
            'contractValidationResults.run',
            'producedBehaviorLinks.consumerEndpoint',
            'consumedBehaviorLinks.producerEndpoint',
        ]);

        $evidenceCount = $endpoint->findings->sum(fn (Finding $finding): int => $finding->evidence->count());
        $missing = collect();

        if ($endpoint->scanResults->isEmpty()) {
            $missing->push(__('messages.evidence_graph.missing.endpoint_scan'));
        }

        if ($endpoint->assertionRules->isEmpty()) {
            $missing->push(__('messages.evidence_graph.missing.endpoint_assertion'));
        }

        if ($endpoint->findings->isNotEmpty() && $evidenceCount === 0) {
            $missing->push(__('messages.evidence_graph.missing.finding_evidence'));
        }

        return [
            'endpoint' => $endpoint,
            'scan_results_count' => $endpoint->scanResults->count(),
            'latest_scan_result' => $endpoint->latestScanResult,
            'assertion_rules_count' => $endpoint->assertionRules->count(),
            'test_cases_count' => $endpoint->testCases->count(),
            'contract_results_count' => $endpoint->contractValidationResults->count(),
            'findings_count' => $endpoint->findings->count(),
            'evidence_count' => $evidenceCount,
            'produced_links_count' => $endpoint->producedBehaviorLinks->count(),
            'consumed_links_count' => $endpoint->consumedBehaviorLinks->count(),
            'missing_links' => $missing,
            'graph_nodes' => collect([
                ['type' => 'endpoint', 'label' => $endpoint->method.' '.$endpoint->path, 'css' => 'info'],
                ['type' => 'scan_result', 'label' => __('messages.evidence_graph.nodes.scan_results').': '.$endpoint->scanResults->count(), 'css' => $endpoint->scanResults->isEmpty() ? 'danger' : 'success'],
                ['type' => 'assertions', 'label' => __('messages.evidence_graph.nodes.assertions').': '.$endpoint->assertionRules->count(), 'css' => $endpoint->assertionRules->isEmpty() ? 'warning' : 'success'],
                ['type' => 'findings', 'label' => __('messages.evidence_graph.nodes.findings').': '.$endpoint->findings->count(), 'css' => $endpoint->findings->isEmpty() ? 'default' : 'warning'],
                ['type' => 'evidence', 'label' => __('messages.evidence_graph.nodes.evidence').': '.$evidenceCount, 'css' => $evidenceCount === 0 && $endpoint->findings->isNotEmpty() ? 'danger' : 'success'],
            ]),
        ];
    }

    /** @return array<string, mixed> */
    public function findingChain(Finding $finding): array
    {
        $finding->loadMissing([
            'endpoint',
            'scanRun',
            'scanResult',
            'contractValidationResult',
            'testCase',
            'evidence.capturedBy',
            'owner',
            'verifiedBy',
            'linkedReleaseGate',
            'activeRiskAcceptance',
            'riskAcceptances.acceptedBy',
        ]);

        $missing = collect();
        if ($finding->evidence->isEmpty()) {
            $missing->push(__('messages.evidence_graph.missing.finding_evidence'));
        }
        if ($finding->status === Finding::STATUS_FIXED && ! $finding->has_retest_evidence) {
            $missing->push(__('messages.evidence_graph.missing.retest_evidence'));
        }
        if ($finding->status === Finding::STATUS_ACCEPTED_RISK && ! $finding->activeRiskAcceptance) {
            $missing->push(__('messages.evidence_graph.missing.accepted_risk_ledger'));
        }

        return [
            'finding' => $finding,
            'endpoint' => $finding->endpoint,
            'evidence_count' => $finding->evidence->count(),
            'has_retest_evidence' => $finding->has_retest_evidence,
            'has_scan_link' => $finding->scanRun !== null || $finding->scanResult !== null,
            'has_contract_link' => $finding->contractValidationResult !== null,
            'has_release_gate_link' => $finding->linkedReleaseGate !== null,
            'has_active_risk_acceptance' => $finding->activeRiskAcceptance !== null,
            'missing_links' => $missing,
            'graph_nodes' => collect([
                ['type' => 'finding', 'label' => $finding->title, 'css' => $finding->severity_css],
                ['type' => 'endpoint', 'label' => $finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path : __('messages.common.not_available'), 'css' => $finding->endpoint ? 'info' : 'warning'],
                ['type' => 'evidence', 'label' => __('messages.evidence_graph.nodes.evidence').': '.$finding->evidence->count(), 'css' => $finding->evidence->isEmpty() ? 'danger' : 'success'],
                ['type' => 'verification', 'label' => $finding->verification_status_label, 'css' => $finding->verification_status_css],
                ['type' => 'accepted_risk', 'label' => $finding->activeRiskAcceptance ? __('messages.evidence_graph.nodes.accepted_risk_active') : __('messages.evidence_graph.nodes.accepted_risk_none'), 'css' => $finding->activeRiskAcceptance ? 'warning' : 'default'],
            ]),
        ];
    }

    /** @param Collection<int, QaReleaseGate> $releaseGates @param Collection<int, ReleaseDecision> $releaseDecisions @param Collection<int, RiskAcceptance> $riskAcceptances @param array<string, mixed> $blindSpotSummary @return array<string, mixed> */
    public function releaseGraph(Project $project, Collection $releaseGates, Collection $releaseDecisions, Collection $riskAcceptances, array $blindSpotSummary): array
    {
        $latestScan = $project->scanRuns()->latest()->first();
        $latestSnapshot = $project->snapshots()->latest()->first();
        $latestCompare = $project->compareRuns()->latest()->first();
        $latestGate = $releaseGates->first();
        $latestDecision = $releaseDecisions->first();
        $activeRisks = $riskAcceptances->filter(fn (RiskAcceptance $acceptance): bool => $acceptance->status === RiskAcceptance::STATUS_ACTIVE);
        $blindSpotsTotal = (int) ($blindSpotSummary['summary']['total'] ?? 0);

        $missing = collect();
        if (! $latestScan) {
            $missing->push(__('messages.evidence_graph.missing.release_scan'));
        }
        if (! $latestSnapshot) {
            $missing->push(__('messages.evidence_graph.missing.release_snapshot'));
        }
        if (! $latestGate) {
            $missing->push(__('messages.evidence_graph.missing.release_gate'));
        }
        if (! $latestDecision) {
            $missing->push(__('messages.evidence_graph.missing.release_decision'));
        }

        return [
            'latest_scan' => $latestScan,
            'latest_snapshot' => $latestSnapshot,
            'latest_compare' => $latestCompare,
            'latest_gate' => $latestGate,
            'latest_decision' => $latestDecision,
            'active_risks_count' => $activeRisks->count(),
            'blind_spots_count' => $blindSpotsTotal,
            'missing_links' => $missing,
            'graph_nodes' => collect([
                ['type' => 'scan', 'label' => __('messages.evidence_graph.nodes.latest_scan').': '.($latestScan?->id ?: 'n/a'), 'css' => $latestScan ? 'success' : 'danger'],
                ['type' => 'snapshot', 'label' => __('messages.evidence_graph.nodes.latest_snapshot').': '.($latestSnapshot?->id ?: 'n/a'), 'css' => $latestSnapshot ? 'success' : 'warning'],
                ['type' => 'release_gate', 'label' => __('messages.evidence_graph.nodes.release_gate').': '.($latestGate?->id ?: 'n/a'), 'css' => $latestGate ? $latestGate->automated_status_css : 'warning'],
                ['type' => 'release_decision', 'label' => __('messages.evidence_graph.nodes.release_decision').': '.($latestDecision?->status_label ?: 'n/a'), 'css' => $latestDecision?->status_css ?: 'warning'],
                ['type' => 'accepted_risks', 'label' => __('messages.evidence_graph.nodes.accepted_risks').': '.$activeRisks->count(), 'css' => $activeRisks->isEmpty() ? 'success' : 'warning'],
                ['type' => 'blind_spots', 'label' => __('messages.evidence_graph.nodes.blind_spots').': '.$blindSpotsTotal, 'css' => $blindSpotsTotal === 0 ? 'success' : 'danger'],
            ]),
        ];
    }

    /** @param Collection<int, array<string, mixed>> $endpointMaps @param Collection<int, array<string, mixed>> $findingChains @param array<string, mixed> $releaseGraph @return Collection<int, array<string, mixed>> */
    private function missingLinks(Collection $endpointMaps, Collection $findingChains, array $releaseGraph): Collection
    {
        $items = collect();

        foreach ($endpointMaps as $map) {
            foreach ($map['missing_links'] as $missing) {
                $endpoint = $map['endpoint'];
                $items->push([
                    'scope' => __('messages.evidence_graph.scopes.endpoint'),
                    'related' => $endpoint->method.' '.$endpoint->path,
                    'missing' => $missing,
                    'severity_css' => in_array($endpoint->risk_level, [Endpoint::RISK_CRITICAL, Endpoint::RISK_HIGH], true) ? 'danger' : 'warning',
                ]);
            }
        }

        foreach ($findingChains as $chain) {
            foreach ($chain['missing_links'] as $missing) {
                $finding = $chain['finding'];
                $items->push([
                    'scope' => __('messages.evidence_graph.scopes.finding'),
                    'related' => $finding->title,
                    'missing' => $missing,
                    'severity_css' => $finding->severity_css,
                ]);
            }
        }

        foreach ($releaseGraph['missing_links'] as $missing) {
            $items->push([
                'scope' => __('messages.evidence_graph.scopes.release'),
                'related' => __('messages.evidence_graph.release_scope_label'),
                'missing' => $missing,
                'severity_css' => 'warning',
            ]);
        }

        return $items->values();
    }
}
