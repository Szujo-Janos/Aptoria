<?php

namespace App\Services;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class QaCockpitService
{
    public function snapshot(Project $project): array
    {
        $endpointCount = $this->countIf('endpoints', fn () => $project->endpoints()->count());
        $activeEndpointCount = $this->countIf('endpoints', fn () => $project->endpoints()->where('is_active', true)->count());
        $safeEndpointCount = $this->countIf('endpoints', fn () => $project->endpoints()->where('is_active', true)->where('excluded_from_scan', false)->whereIn('method', ['GET', 'HEAD'])->count());
        $scanCovered = $this->distinctEndpointCount('scan_results', $project);
        $quickTestCovered = $this->distinctEndpointCount('endpoint_test_runs', $project);
        $nativeTestCovered = $this->distinctEndpointCount('test_cases', $project);
        $evidenceCovered = $this->distinctEndpointCount('finding_evidence', $project, function ($query): void {
            if (Schema::hasColumn('finding_evidence', 'repository_status')) {
                $query->where('repository_status', '!=', FindingEvidence::STATUS_ARCHIVED);
            }
        });
        $verifiedEvidence = $this->countIf('finding_evidence', fn () => $project->evidence()->where('repository_status', FindingEvidence::STATUS_VERIFIED)->count());
        $evidenceCount = $this->countIf('finding_evidence', fn () => $project->evidence()->where(function ($query): void {
            if (Schema::hasColumn('finding_evidence', 'repository_status')) {
                $query->where('repository_status', '!=', FindingEvidence::STATUS_ARCHIVED)->orWhereNull('repository_status');
            }
        })->count());
        $openFindings = $this->countIf('findings', fn () => $project->findings()->whereNotIn('status', ['verified'])->count());
        $highCriticalOpen = $this->countIf('findings', fn () => $project->findings()->whereIn('severity', ['high', 'critical'])->whereNotIn('status', ['verified'])->count());
        $nativeRunCount = $this->countIf('test_runs', fn () => $project->testRuns()->count());
        $failedNativeRuns = $this->countIf('test_runs', fn () => $project->testRuns()->whereIn('status', ['fail', 'blocked'])->count());
        $latestReadiness = Schema::hasTable('release_readiness_runs') ? $project->releaseReadinessRuns()->latest()->first() : null;
        $latestGate = Schema::hasTable('release_gates') ? $project->releaseGates()->latest()->first() : null;
        $latestEvidencePack = Schema::hasTable('evidence_packs') ? $project->evidencePacks()->latest()->first() : null;
        $latestReport = Schema::hasTable('report_versions') ? $project->reportVersions()->latest()->first() : null;

        $coverage = [
            'scan' => $this->percent($scanCovered, max($safeEndpointCount, 1)),
            'quick_test' => $this->percent($quickTestCovered, max($endpointCount, 1)),
            'native_test' => $this->percent($nativeTestCovered, max($endpointCount, 1)),
            'evidence' => $this->percent($evidenceCovered, max($endpointCount, 1)),
            'verified_evidence' => $this->percent($verifiedEvidence, max($evidenceCount, 1)),
        ];

        $score = $this->score([
            'inventory' => $endpointCount > 0 ? 100 : 0,
            'scan' => $coverage['scan'],
            'test' => max($coverage['quick_test'], $coverage['native_test']),
            'evidence' => $coverage['evidence'],
            'verification' => $coverage['verified_evidence'],
            'blocker_health' => $highCriticalOpen === 0 ? 100 : max(0, 100 - ($highCriticalOpen * 20)),
        ]);

        $blindSpots = $this->blindSpots($project, [
            'endpoint_count' => $endpointCount,
            'active_endpoint_count' => $activeEndpointCount,
            'safe_endpoint_count' => $safeEndpointCount,
            'scan_covered' => $scanCovered,
            'native_test_covered' => $nativeTestCovered,
            'quick_test_covered' => $quickTestCovered,
            'evidence_count' => $evidenceCount,
            'verified_evidence' => $verifiedEvidence,
            'open_findings' => $openFindings,
            'high_critical_open' => $highCriticalOpen,
            'native_run_count' => $nativeRunCount,
            'failed_native_runs' => $failedNativeRuns,
            'latest_readiness' => $latestReadiness,
            'latest_gate' => $latestGate,
            'latest_evidence_pack' => $latestEvidencePack,
            'latest_report' => $latestReport,
        ]);

        return [
            'score' => $score,
            'score_tone' => $this->scoreTone($score, count(array_filter($blindSpots, fn (array $spot): bool => $spot['severity'] === 'blocker'))),
            'metrics' => [
                'endpoints' => $endpointCount,
                'active_endpoints' => $activeEndpointCount,
                'safe_endpoints' => $safeEndpointCount,
                'scan_covered' => $scanCovered,
                'quick_test_covered' => $quickTestCovered,
                'native_test_covered' => $nativeTestCovered,
                'evidence_covered' => $evidenceCovered,
                'evidence_count' => $evidenceCount,
                'verified_evidence' => $verifiedEvidence,
                'open_findings' => $openFindings,
                'high_critical_open' => $highCriticalOpen,
                'native_run_count' => $nativeRunCount,
                'failed_native_runs' => $failedNativeRuns,
            ],
            'coverage' => $coverage,
            'coverage_rows' => $this->coverageRows($project),
            'blind_spots' => $blindSpots,
            'latest_readiness' => $latestReadiness,
            'latest_gate' => $latestGate,
            'latest_evidence_pack' => $latestEvidencePack,
            'latest_report' => $latestReport,
        ];
    }

    private function coverageRows(Project $project): Collection
    {
        if (! Schema::hasTable('endpoints')) {
            return collect();
        }

        return $project->endpoints()
            ->withCount([
                'scanResults',
                'testRuns',
                'assertionRules',
                'findings',
                'evidence' => function ($query): void {
                    if (Schema::hasColumn('finding_evidence', 'repository_status')) {
                        $query->where('repository_status', '!=', FindingEvidence::STATUS_ARCHIVED);
                    }
                },
            ])
            ->orderBy('method')
            ->orderBy('path')
            ->limit(80)
            ->get()
            ->map(function (Endpoint $endpoint): array {
                $nativeTests = Schema::hasTable('test_cases') ? $endpoint->project->testCases()->where('endpoint_id', $endpoint->id)->count() : 0;
                $verifiedEvidence = Schema::hasTable('finding_evidence') ? $endpoint->evidence()->where('repository_status', FindingEvidence::STATUS_VERIFIED)->count() : 0;
                $signals = [
                    'scan' => $endpoint->scan_results_count > 0,
                    'quick_test' => $endpoint->test_runs_count > 0,
                    'native_test' => $nativeTests > 0,
                    'evidence' => $endpoint->evidence_count > 0,
                    'verified' => $verifiedEvidence > 0,
                    'assertions' => $endpoint->assertion_rules_count > 0,
                    'findings_clean' => $endpoint->findings_count === 0,
                ];
                $present = collect($signals)->filter()->count();
                $score = (int) round(($present / max(count($signals), 1)) * 100);
                $missing = [];
                foreach ($signals as $key => $ready) {
                    if (! $ready && $key !== 'findings_clean') {
                        $missing[] = $key;
                    }
                }

                return [
                    'endpoint' => $endpoint,
                    'native_test_count' => $nativeTests,
                    'verified_evidence_count' => $verifiedEvidence,
                    'score' => $score,
                    'tone' => $this->scoreTone($score, $endpoint->findings_count > 0 ? 1 : 0),
                    'signals' => $signals,
                    'missing' => $missing,
                ];
            });
    }

    private function blindSpots(Project $project, array $state): array
    {
        $spots = [];

        if ($state['endpoint_count'] === 0) {
            $spots[] = $this->spot('inventory', 'blocker', 'route', __('messages.qa_cockpit.blind_spots.no_endpoints'), __('messages.qa_cockpit.actions.add_endpoints'), route('projects.endpoints.index', $project));
        }

        if ($state['safe_endpoint_count'] > 0 && $state['scan_covered'] === 0) {
            $spots[] = $this->spot('scan', 'warning', 'scan-eye', __('messages.qa_cockpit.blind_spots.no_scan_evidence'), __('messages.qa_cockpit.actions.run_safe_scan'), route('projects.safe-scans.index', $project));
        }

        if ($state['endpoint_count'] > 0 && max($state['native_test_covered'], $state['quick_test_covered']) === 0) {
            $spots[] = $this->spot('tests', 'warning', 'flask-conical', __('messages.qa_cockpit.blind_spots.no_test_evidence'), __('messages.qa_cockpit.actions.create_tests'), route('projects.tests.index', $project));
        }

        if ($state['evidence_count'] === 0) {
            $spots[] = $this->spot('evidence', 'blocker', 'folder-check', __('messages.qa_cockpit.blind_spots.no_evidence'), __('messages.qa_cockpit.actions.add_evidence'), route('projects.evidence.index', $project));
        } elseif ($state['verified_evidence'] === 0) {
            $spots[] = $this->spot('evidence', 'warning', 'fingerprint', __('messages.qa_cockpit.blind_spots.no_verified_evidence'), __('messages.qa_cockpit.actions.verify_evidence'), route('projects.evidence.index', $project));
        }

        if ($state['high_critical_open'] > 0) {
            $spots[] = $this->spot('findings', 'blocker', 'octagon-alert', trans_choice('messages.qa_cockpit.blind_spots.high_critical_findings', $state['high_critical_open'], ['count' => $state['high_critical_open']]), __('messages.qa_cockpit.actions.triage_findings'), route('projects.findings.index', $project));
        }

        if ($state['failed_native_runs'] > 0) {
            $spots[] = $this->spot('tests', 'warning', 'circle-x', trans_choice('messages.qa_cockpit.blind_spots.failed_test_runs', $state['failed_native_runs'], ['count' => $state['failed_native_runs']]), __('messages.qa_cockpit.actions.review_test_runs'), route('projects.tests.index', $project));
        }

        if (! $state['latest_readiness']) {
            $spots[] = $this->spot('release', 'info', 'shield-chevron', __('messages.qa_cockpit.blind_spots.no_readiness'), __('messages.qa_cockpit.actions.generate_readiness'), route('projects.release-readiness.index', $project));
        }

        if (! $state['latest_gate']) {
            $spots[] = $this->spot('release', 'info', 'workflow', __('messages.qa_cockpit.blind_spots.no_gate'), __('messages.qa_cockpit.actions.create_gate'), route('projects.release-gates.index', $project));
        }

        if (! $state['latest_evidence_pack'] && ! $state['latest_report']) {
            $spots[] = $this->spot('reports', 'info', 'archive', __('messages.qa_cockpit.blind_spots.no_export'), __('messages.qa_cockpit.actions.create_export'), route('projects.evidence-packs.index', $project));
        }

        return $spots;
    }

    private function spot(string $category, string $severity, string $icon, string $title, string $action, string $url): array
    {
        return compact('category', 'severity', 'icon', 'title', 'action', 'url');
    }

    private function distinctEndpointCount(string $table, Project $project, ?callable $callback = null): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'endpoint_id')) {
            return 0;
        }

        $query = $project->{$this->relationForTable($table)}()->whereNotNull('endpoint_id');

        if ($callback) {
            $callback($query);
        }

        return (int) $query->distinct('endpoint_id')->count('endpoint_id');
    }

    private function relationForTable(string $table): string
    {
        return match ($table) {
            'scan_results' => 'scanResults',
            'endpoint_test_runs' => 'endpointTestRuns',
            'test_cases' => 'testCases',
            'finding_evidence' => 'evidence',
            default => 'endpoints',
        };
    }

    private function countIf(string $table, callable $callback): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) $callback();
    }

    private function percent(int $value, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return min(100, (int) round(($value / $total) * 100));
    }

    private function score(array $parts): int
    {
        $weights = [
            'inventory' => 15,
            'scan' => 15,
            'test' => 20,
            'evidence' => 20,
            'verification' => 15,
            'blocker_health' => 15,
        ];

        $score = 0;
        foreach ($weights as $key => $weight) {
            $score += (($parts[$key] ?? 0) / 100) * $weight;
        }

        return max(0, min(100, (int) round($score)));
    }

    private function scoreTone(int $score, int $blockers = 0): string
    {
        if ($blockers > 0 || $score < 50) {
            return 'danger';
        }

        if ($score < 75) {
            return 'warning';
        }

        return 'success';
    }
}
