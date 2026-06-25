<?php

namespace App\Services;

use App\Models\ClientPortalAccess;
use App\Models\Project;
use App\Models\ReportVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ClientPortalDecisionHandoffService
{
    public function approvedDecisionPackages(Project $project, ?ClientPortalAccess $access = null): Collection
    {
        if (! Schema::hasTable('report_versions')) {
            return collect();
        }

        $query = $project->reportVersions()
            ->with(['releaseGate', 'approvedBy'])
            ->where('status', 'approved')
            ->where(function ($query): void {
                $query->whereNotNull('release_gate_id')
                    ->orWhere('type', 'release_decision');
            })
            ->latest('approved_at')
            ->latest('generated_at')
            ->latest();

        if ($access?->report_version_id) {
            $query->whereKey($access->report_version_id);
        }

        return $query->get()
            ->filter(fn (ReportVersion $report): bool => $this->isDecisionPackage($report))
            ->values();
    }

    public function isDecisionPackage(ReportVersion $report): bool
    {
        $data = is_array($report->data_json) ? $report->data_json : [];

        return (bool) $report->release_gate_id
            || data_get($data, 'source.type') === 'release_gate_decision_package'
            || data_get($data, 'release_gate.id') !== null;
    }

    public function summary(ReportVersion $report): array
    {
        $data = is_array($report->data_json) ? $report->data_json : [];
        $gate = $report->releaseGate;

        return [
            'report_id' => $report->id,
            'title' => $report->title,
            'checksum' => $report->checksum,
            'status' => $report->status,
            'approved_at' => $report->approved_at?->toDateTimeString(),
            'approved_by' => $report->approvedBy?->name,
            'gate_title' => data_get($data, 'release_gate.title') ?: $gate?->title,
            'release_version' => data_get($data, 'release_gate.release_version') ?: $gate?->release_version,
            'target_environment' => data_get($data, 'release_gate.target_environment') ?: $gate?->target_environment,
            'profile' => data_get($data, 'release_gate.profile_label') ?: $gate?->profile_label,
            'final_decision' => data_get($data, 'release_gate.final_decision') ?: $gate?->final_decision,
            'final_decision_label' => data_get($data, 'release_gate.final_decision_label') ?: $gate?->final_decision_label,
            'score' => data_get($data, 'metrics.score', data_get($data, 'release_gate.score', $gate?->score)),
            'grade' => data_get($data, 'metrics.grade', data_get($data, 'release_gate.grade', $gate?->grade)),
            'blockers' => (int) data_get($data, 'metrics.blockers', data_get($data, 'release_gate.blocker_count', $gate?->blocker_count ?? 0)),
            'warnings' => (int) data_get($data, 'metrics.warnings', data_get($data, 'release_gate.warning_count', $gate?->warning_count ?? 0)),
            'verified_evidence' => (int) data_get($data, 'metrics.verified_evidence', $gate?->verified_evidence_count ?? 0),
            'test_runs' => (int) data_get($data, 'metrics.test_runs', $gate?->test_run_count ?? 0),
            'high_critical_open_findings' => (int) data_get($data, 'metrics.high_critical_open_findings', $gate?->high_critical_open_count ?? 0),
            'decision_note' => data_get($data, 'release_gate.decision_note') ?: $gate?->decision_note,
            'item_count' => count((array) data_get($data, 'gate_items', [])),
        ];
    }

    public function publicMatrix(Collection $packages): array
    {
        return [
            'packages' => $packages->count(),
            'go' => $packages->filter(fn (ReportVersion $report): bool => data_get($this->summary($report), 'final_decision') === 'go')->count(),
            'conditional_go' => $packages->filter(fn (ReportVersion $report): bool => data_get($this->summary($report), 'final_decision') === 'conditional_go')->count(),
            'no_go' => $packages->filter(fn (ReportVersion $report): bool => data_get($this->summary($report), 'final_decision') === 'no_go')->count(),
            'total_blockers' => $packages->sum(fn (ReportVersion $report): int => (int) data_get($this->summary($report), 'blockers', 0)),
            'verified_evidence' => $packages->sum(fn (ReportVersion $report): int => (int) data_get($this->summary($report), 'verified_evidence', 0)),
        ];
    }
}
