<?php

namespace App\Services\Reports;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ReportVersion;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\ReleaseReadinessService;
use Illuminate\Support\Collection;

class ReportVersioningService
{
    public function __construct(
        private readonly FullQaReportBuilderService $builder,
        private readonly ReportExportService $exports,
        private readonly ReleaseReadinessService $readiness,
        private readonly AuditLogService $auditLog
    ) {
    }

    /** @param array<string, mixed> $options */
    public function create(Project $project, array $options, ?User $user = null): ReportVersion
    {
        $type = (string) ($options['report_type'] ?? ReportVersion::TYPE_TECHNICAL);
        if (! in_array($type, ReportVersion::TYPES, true)) {
            $type = ReportVersion::TYPE_TECHNICAL;
        }

        $title = trim((string) ($options['title'] ?? ''));
        if ($title === '') {
            $title = __('messages.report_versions.default_titles.'.$type);
        }

        $markdown = $this->markdownFor($project, $type, $options);
        $sources = $this->sourceSnapshot($project);

        $version = ReportVersion::query()->create([
            'project_id' => $project->id,
            'generated_by_user_id' => $user?->id,
            'title' => $title,
            'report_type' => $type,
            'report_format' => 'markdown',
            'status' => ReportVersion::STATUS_DRAFT,
            'content_checksum' => hash('sha256', $markdown),
            'markdown_content' => $markdown,
            'source_scan_ids' => $sources['scan_ids'],
            'source_snapshot_ids' => $sources['snapshot_ids'],
            'source_compare_ids' => $sources['compare_ids'],
            'source_finding_state' => $sources['finding_state'],
            'source_release_gate_ids' => $sources['release_gate_ids'],
            'source_release_decision_ids' => $sources['release_decision_ids'],
            'source_evidence_ids' => $sources['evidence_ids'],
            'source_options_json' => $options,
            'generated_at' => now(),
        ]);

        $this->recordAudit($version, AuditLog::ACTION_CREATED, 'Report version created');

        return $version;
    }

    public function markReviewed(ReportVersion $version, ?User $user = null): ReportVersion
    {
        if ($version->status !== ReportVersion::STATUS_ARCHIVED) {
            $version->forceFill([
                'status' => ReportVersion::STATUS_REVIEWED,
                'reviewed_at' => now(),
            ])->save();
        }

        $this->recordAudit($version, AuditLog::ACTION_UPDATED, 'Report version marked as reviewed');

        return $version;
    }

    public function approve(ReportVersion $version, ?User $user = null): ReportVersion
    {
        if ($version->status !== ReportVersion::STATUS_ARCHIVED) {
            $version->forceFill([
                'status' => ReportVersion::STATUS_APPROVED,
                'approved_by_user_id' => $user?->id,
                'approved_at' => now(),
                'reviewed_at' => $version->reviewed_at ?: now(),
            ])->save();
        }

        $this->recordAudit($version, AuditLog::ACTION_UPDATED, 'Report version approved');

        return $version;
    }

    public function archive(ReportVersion $version): ReportVersion
    {
        $version->forceFill([
            'status' => ReportVersion::STATUS_ARCHIVED,
            'archived_at' => now(),
        ])->save();

        $this->recordAudit($version, AuditLog::ACTION_UPDATED, 'Report version archived');

        return $version;
    }

    /** @return array<string, mixed> */
    public function jsonPackage(ReportVersion $version): array
    {
        return [
            'id' => $version->id,
            'project_id' => $version->project_id,
            'title' => $version->title,
            'report_type' => $version->report_type,
            'report_format' => $version->report_format,
            'status' => $version->status,
            'content_checksum' => $version->content_checksum,
            'generated_by_user_id' => $version->generated_by_user_id,
            'approved_by_user_id' => $version->approved_by_user_id,
            'generated_at' => $version->generated_at?->toIso8601String(),
            'reviewed_at' => $version->reviewed_at?->toIso8601String(),
            'approved_at' => $version->approved_at?->toIso8601String(),
            'archived_at' => $version->archived_at?->toIso8601String(),
            'sources' => [
                'scan_ids' => $version->source_scan_ids ?? [],
                'snapshot_ids' => $version->source_snapshot_ids ?? [],
                'compare_ids' => $version->source_compare_ids ?? [],
                'finding_state' => $version->source_finding_state ?? [],
                'release_gate_ids' => $version->source_release_gate_ids ?? [],
                'release_decision_ids' => $version->source_release_decision_ids ?? [],
                'evidence_ids' => $version->source_evidence_ids ?? [],
            ],
        ];
    }

    /** @param array<string, mixed> $options */
    private function markdownFor(Project $project, string $type, array $options): string
    {
        return match ($type) {
            ReportVersion::TYPE_EXECUTIVE => $this->builder->markdown($project, FullQaReportBuilderService::profileOptions(FullQaReportBuilderService::REPORT_PROFILE_EXECUTIVE)),
            ReportVersion::TYPE_RELEASE_READINESS => $this->readiness->markdown($project),
            ReportVersion::TYPE_FULL_PROJECT => $this->exports->fullProjectMarkdown($project),
            default => $this->builder->markdown($project, FullQaReportBuilderService::profileOptions(FullQaReportBuilderService::REPORT_PROFILE_TECHNICAL)),
        };
    }

    /** @return array<string, mixed> */
    private function sourceSnapshot(Project $project): array
    {
        $project->loadMissing([
            'scanRuns',
            'snapshots',
            'compareRuns',
            'findings.endpoint',
            'findingEvidence',
            'qaReleaseGates',
            'releaseDecisions',
        ]);

        return [
            'scan_ids' => $project->scanRuns->pluck('id')->values()->all(),
            'snapshot_ids' => $project->snapshots->pluck('id')->values()->all(),
            'compare_ids' => $project->compareRuns->pluck('id')->values()->all(),
            'release_gate_ids' => $project->qaReleaseGates->pluck('id')->values()->all(),
            'release_decision_ids' => $project->releaseDecisions->pluck('id')->values()->all(),
            'evidence_ids' => $project->findingEvidence->pluck('id')->values()->all(),
            'finding_state' => $this->findingState($project->findings),
        ];
    }

    /** @param Collection<int, \App\Models\Finding> $findings @return array<int, array<string, mixed>> */
    private function findingState(Collection $findings): array
    {
        return $findings
            ->sortBy('id')
            ->map(fn ($finding): array => [
                'id' => $finding->id,
                'endpoint_id' => $finding->endpoint_id,
                'title' => $finding->title,
                'severity' => $finding->severity,
                'status' => $finding->status,
                'verification_status' => $finding->verification_status,
                'updated_at' => $finding->updated_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function recordAudit(ReportVersion $version, string $action, string $summary): void
    {
        $this->auditLog->record([
            'project_id' => $version->project_id,
            'event_type' => AuditLog::EVENT_REPORT,
            'action' => $action,
            'severity' => $version->status === ReportVersion::STATUS_APPROVED ? AuditLog::SEVERITY_NOTICE : AuditLog::SEVERITY_INFO,
            'auditable_type' => ReportVersion::class,
            'auditable_id' => $version->id,
            'subject_label' => 'report_version',
            'subject_name' => $version->title,
            'summary' => $summary.': '.$version->title,
            'metadata' => [
                'status' => $version->status,
                'report_type' => $version->report_type,
                'content_checksum' => $version->content_checksum,
            ],
        ]);
    }
}
