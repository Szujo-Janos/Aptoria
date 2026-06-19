<?php

namespace App\Services;

use App\Models\ReleaseGate;
use App\Models\ReportVersion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReleaseGateDecisionPackageService
{
    public function __construct(private readonly ReportVisualStandardService $visualStandardService)
    {
    }

    public function createReportVersion(ReleaseGate $gate, ?User $user, ?string $notes = null): ReportVersion
    {
        $gate->loadMissing(['project', 'createdBy', 'finalizedBy', 'readinessRun', 'items.reviewedBy', 'events.user']);
        $data = $this->packageData($gate);
        $markdown = $this->markdown($gate, $data);
        $checksum = $this->checksum($markdown, $data);
        $project = $gate->project;

        $report = $project->reportVersions()->create([
            'generated_by_user_id' => $user?->id,
            'release_readiness_run_id' => $gate->release_readiness_run_id,
            'release_gate_id' => $gate->id,
            'type' => 'release_decision',
            'status' => 'draft',
            'title' => __('messages.release_gates.package.report_title', [
                'project' => $project?->name ?? __('messages.common.not_available'),
                'gate' => $gate->title,
                'date' => now()->format('Y-m-d H:i'),
            ]),
            'content_markdown' => $markdown,
            'content_html' => $this->visualStandardService->inlineHtmlFromMarkdown($markdown, 'aptoria-release-gate-decision-package-html'),
            'data_json' => $data,
            'checksum' => $checksum,
            'notes' => $notes,
            'generated_at' => now(),
        ]);

        $this->recordGatePackageEvent($gate, $user, $report);

        return $report->fresh(['generatedBy', 'releaseGate']) ?? $report;
    }

    public function latestReportVersion(ReleaseGate $gate): ?ReportVersion
    {
        return $gate->reportVersions()
            ->with(['generatedBy', 'reviewedBy', 'approvedBy'])
            ->latest('generated_at')
            ->latest()
            ->first();
    }

    public function exportHtml(ReleaseGate $gate, ?ReportVersion $report = null): string
    {
        $report = $report ?: $this->transientReport($gate);

        return $this->visualStandardService->exportHtml($report);
    }

    public function exportPdf(ReleaseGate $gate, ?ReportVersion $report = null): string
    {
        $data = $report && is_array($report->data_json) ? $report->data_json : $this->packageData($gate);

        return $this->visualStandardService->exportReleaseGateDecisionPackagePdf($gate, $data, $report);
    }

    public function exportJson(ReleaseGate $gate, ?ReportVersion $report = null): string
    {
        $data = $report && is_array($report->data_json) ? $report->data_json : $this->packageData($gate);
        $payload = [
            'package' => $data,
            'report_version' => $report ? [
                'id' => $report->id,
                'status' => $report->status,
                'checksum' => $report->checksum,
                'generated_at' => $report->generated_at?->toDateTimeString(),
                'approval' => $report->approvalSummary(),
            ] : null,
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
    }

    public function zipBinary(ReleaseGate $gate, ?ReportVersion $report = null): string
    {
        $report = $report ?: $this->latestReportVersion($gate);
        $data = $report && is_array($report->data_json) ? $report->data_json : $this->packageData($gate);
        $markdown = $report?->content_markdown ?: $this->markdown($gate, $data);
        $html = $this->exportHtml($gate, $report);
        $pdf = $this->exportPdf($gate, $report);
        $json = $this->exportJson($gate, $report);
        $readme = $this->readme($gate, $report);
        $checksum = implode(PHP_EOL, [
            hash('sha256', $readme).'  README.md',
            hash('sha256', $markdown).'  release-gate-report.md',
            hash('sha256', $html).'  release-gate-report.html',
            hash('sha256', $pdf).'  release-gate-report.pdf',
            hash('sha256', $json).'  decision-package.json',
            ($report?->checksum ?: $this->checksum($markdown, $data)).'  release-gate-decision-package-record',
            '',
        ]);

        return $this->buildStoredZip([
            'README.md' => $readme,
            'release-gate-report.md' => $markdown,
            'release-gate-report.html' => $html,
            'release-gate-report.pdf' => $pdf,
            'decision-package.json' => $json,
            'checksum.sha256' => $checksum,
        ]);
    }

    public function packageData(ReleaseGate $gate): array
    {
        $gate->loadMissing(['project', 'createdBy', 'finalizedBy', 'readinessRun', 'items.reviewedBy', 'events.user']);
        $project = $gate->project;
        $items = $gate->items;
        $events = $gate->events;

        $itemsByState = [
            'pass' => $items->where('effective_state', 'pass')->count(),
            'warning' => $items->where('effective_state', 'warning')->count(),
            'blocked' => $items->where('effective_state', 'blocked')->count(),
            'waived' => $items->where('effective_state', 'waived')->count(),
        ];

        return [
            'source' => [
                'type' => 'release_gate_decision_package',
                'release_gate_id' => $gate->id,
                'release_readiness_run_id' => $gate->release_readiness_run_id,
                'final_decision' => $gate->final_decision,
                'finalized_at' => $gate->finalized_at?->toDateTimeString(),
                'finalized_by' => $gate->finalizedBy?->name,
            ],
            'project' => [
                'id' => $project?->id,
                'name' => $project?->name,
                'base_url' => $project?->base_url,
                'release_goal' => $project?->release_goal,
            ],
            'release_gate' => [
                'id' => $gate->id,
                'title' => $gate->title,
                'release_version' => $gate->release_version,
                'target_environment' => $gate->target_environment,
                'gate_profile' => $gate->gate_profile,
                'profile_label' => $gate->profile_label,
                'status' => $gate->status,
                'status_label' => $gate->status_label,
                'automated_decision' => $gate->automated_decision,
                'automated_decision_label' => $gate->automated_decision_label,
                'final_decision' => $gate->final_decision,
                'final_decision_label' => $gate->final_decision_label,
                'score' => $gate->score,
                'grade' => $gate->grade,
                'blocker_count' => $gate->blocker_count,
                'warning_count' => $gate->warning_count,
                'passed_item_count' => $gate->passed_item_count,
                'total_item_count' => $gate->total_item_count,
                'decision_note' => $gate->decision_note,
                'created_by' => $gate->createdBy?->name,
                'created_at' => $gate->created_at?->toDateTimeString(),
                'evaluated_at' => $gate->evaluated_at?->toDateTimeString(),
                'finalized_by' => $gate->finalizedBy?->name,
                'finalized_at' => $gate->finalized_at?->toDateTimeString(),
            ],
            'metrics' => [
                'score' => $gate->score,
                'grade' => $gate->grade,
                'blockers' => $gate->blocker_count,
                'warnings' => $gate->warning_count,
                'passed_items' => $gate->passed_item_count,
                'total_items' => $gate->total_item_count,
                'evidence' => $gate->evidence_count,
                'verified_evidence' => $gate->verified_evidence_count,
                'test_runs' => $gate->test_run_count,
                'failed_test_runs' => $gate->failed_test_run_count,
                'open_findings' => $gate->open_finding_count,
                'high_critical_open_findings' => $gate->high_critical_open_count,
            ],
            'evidence_summary' => [
                'decision' => [
                    'label' => $gate->final_decision_label,
                    'value' => $gate->final_decision,
                ],
                'readiness' => [
                    'score' => $gate->score,
                    'grade' => $gate->grade,
                    'blockers' => $gate->blocker_count,
                    'warnings' => $gate->warning_count,
                ],
                'items' => $itemsByState,
            ],
            'readiness_metrics' => [
                'score' => $gate->score,
                'grade' => $gate->grade,
                'blocker_count' => $gate->blocker_count,
                'warning_count' => $gate->warning_count,
                'passed_check_count' => $gate->passed_item_count,
                'check_count' => $gate->total_item_count,
            ],
            'source_state' => $gate->source_state_json ?: [],
            'gate_items' => $this->itemRows($items),
            'gate_events' => $this->eventRows($events),
            'report_preview' => [
                'headline' => $this->headline($gate),
                'subtitle' => __('messages.release_gates.package.subtitle'),
                'decision_label' => $gate->final_decision_label,
                'score' => $gate->score,
                'grade' => $gate->grade,
            ],
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    public function markdown(ReleaseGate $gate, array $data): string
    {
        $lines = [
            '# '.__('messages.release_gates.package.title'),
            '',
            '**'.__('messages.projects.name').':** '.data_get($data, 'project.name', '—'),
            '**'.__('messages.release_gates.gate').':** '.data_get($data, 'release_gate.title', '—'),
            '**'.__('messages.release_gates.final_decision').':** '.data_get($data, 'release_gate.final_decision_label', '—'),
            '**'.__('messages.release_readiness.score').':** '.data_get($data, 'release_gate.score', '—').'/100 · '.data_get($data, 'release_gate.grade', '—'),
            '**'.__('messages.release_gates.metrics.blockers').':** '.data_get($data, 'release_gate.blocker_count', 0),
            '**'.__('messages.release_gates.metrics.warnings').':** '.data_get($data, 'release_gate.warning_count', 0),
            '',
            '## '.__('messages.release_decisions.report.executive_summary'),
            $this->headline($gate),
            '',
            '## '.__('messages.release_gates.package.decision_context'),
            '- '.__('messages.release_gates.form.release_version').': '.(data_get($data, 'release_gate.release_version') ?: '—'),
            '- '.__('messages.release_gates.form.target_environment').': '.(data_get($data, 'release_gate.target_environment') ?: '—'),
            '- '.__('messages.release_gates.form.gate_profile').': '.data_get($data, 'release_gate.profile_label', '—'),
            '- '.__('messages.release_gates.created_by').': '.(data_get($data, 'release_gate.created_by') ?: '—'),
            '- '.__('messages.release_gates.package.finalized_by').': '.(data_get($data, 'release_gate.finalized_by') ?: '—'),
            '- '.__('messages.release_gates.package.finalized_at').': '.(data_get($data, 'release_gate.finalized_at') ?: '—'),
            '',
            '## '.__('messages.release_gates.items_title'),
        ];

        foreach ((array) data_get($data, 'gate_items', []) as $item) {
            $lines[] = '- ['.($item['effective_state_label'] ?? '—').'] '.($item['label'] ?? '—').' — '.($item['required_action'] ?? '—');
            if (! empty($item['reviewer_note'])) {
                $lines[] = '  - '.__('messages.release_gates.form.reviewer_note').': '.$item['reviewer_note'];
            }
        }

        $lines[] = '';
        $lines[] = '## '.__('messages.release_gates.timeline_title');
        foreach ((array) data_get($data, 'gate_events', []) as $event) {
            $lines[] = '- '.($event['occurred_at'] ?? '—').' · '.($event['summary'] ?? '—').' · '.($event['user'] ?? __('messages.common.system'));
        }

        if (filled($gate->decision_note)) {
            $lines[] = '';
            $lines[] = '## '.__('messages.release_gates.form.decision_note');
            $lines[] = trim((string) $gate->decision_note);
        }

        return implode("\n", $lines)."\n";
    }

    private function transientReport(ReleaseGate $gate): ReportVersion
    {
        $gate->loadMissing(['project', 'createdBy', 'finalizedBy', 'readinessRun', 'items.reviewedBy', 'events.user']);
        $data = $this->packageData($gate);
        $markdown = $this->markdown($gate, $data);
        $report = new ReportVersion([
            'project_id' => $gate->project_id,
            'generated_by_user_id' => auth()->id(),
            'release_readiness_run_id' => $gate->release_readiness_run_id,
            'release_gate_id' => $gate->id,
            'type' => 'release_decision',
            'status' => 'draft',
            'title' => __('messages.release_gates.package.report_title', [
                'project' => $gate->project?->name ?? __('messages.common.not_available'),
                'gate' => $gate->title,
                'date' => now()->format('Y-m-d H:i'),
            ]),
            'content_markdown' => $markdown,
            'content_html' => $this->visualStandardService->inlineHtmlFromMarkdown($markdown, 'aptoria-release-gate-decision-package-html'),
            'data_json' => $data,
            'checksum' => $this->checksum($markdown, $data),
            'generated_at' => now(),
        ]);
        $report->setRelation('project', $gate->project);
        $report->setRelation('generatedBy', auth()->user() ?: $gate->createdBy);
        $report->setRelation('releaseGate', $gate);

        return $report;
    }

    private function itemRows(Collection $items): array
    {
        return $items->values()->map(fn ($item): array => [
            'id' => $item->id,
            'category' => $item->category,
            'category_label' => $item->category_label,
            'label' => $item->label,
            'icon' => $item->icon,
            'automated_state' => $item->automated_state,
            'automated_state_label' => __('messages.release_gates.item_states.'.($item->automated_state ?: 'warning')),
            'manual_state' => $item->manual_state,
            'effective_state' => $item->effective_state,
            'effective_state_label' => $item->effective_state_label,
            'severity' => $item->severity,
            'required_action' => $item->required_action,
            'reviewer_note' => $item->reviewer_note,
            'reviewed_by' => $item->reviewedBy?->name,
            'reviewed_at' => $item->reviewed_at?->toDateTimeString(),
            'metadata' => $item->metadata_json ?: [],
        ])->all();
    }

    private function eventRows(Collection $events): array
    {
        return $events->values()->map(fn ($event): array => [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'summary' => $event->summary,
            'severity' => $event->severity,
            'user' => $event->user?->name,
            'occurred_at' => $event->occurred_at?->toDateTimeString() ?? $event->created_at?->toDateTimeString(),
        ])->all();
    }

    private function headline(ReleaseGate $gate): string
    {
        if ($gate->final_decision === 'go') {
            return __('messages.release_gates.package.headlines.go');
        }
        if ($gate->final_decision === 'conditional_go') {
            return __('messages.release_gates.package.headlines.conditional_go');
        }
        if ($gate->final_decision === 'no_go') {
            return __('messages.release_gates.package.headlines.no_go');
        }
        if ((int) $gate->blocker_count > 0) {
            return __('messages.release_gates.package.headlines.blocked');
        }

        return __('messages.release_gates.package.headlines.pending');
    }

    private function checksum(string $markdown, array $data): string
    {
        return hash('sha256', $markdown.'|'.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function readme(ReleaseGate $gate, ?ReportVersion $report): string
    {
        return implode(PHP_EOL, [
            '# Aptoria Release Gate Decision Package',
            '',
            'Project: '.($gate->project?->name ?? '—'),
            'Gate: '.$gate->title,
            'Decision: '.$gate->final_decision_label,
            'Score: '.$gate->score.'/100',
            'Report version: '.($report ? '#'.$report->id.' · '.$report->status : 'not stored'),
            'Checksum: '.($report?->checksum ?: 'generated in checksum.sha256'),
            '',
            'Files:',
            '- release-gate-report.html — printable report-standard HTML',
            '- release-gate-report.pdf — formatted PDF summary',
            '- release-gate-report.md — Markdown source text',
            '- decision-package.json — structured gate decision data',
            '- checksum.sha256 — integrity references',
            '',
        ]);
    }

    private function recordGatePackageEvent(ReleaseGate $gate, ?User $user, ReportVersion $report): void
    {
        if (! class_exists(\App\Models\ReleaseGateEvent::class)) {
            return;
        }

        \App\Models\ReleaseGateEvent::create([
            'project_id' => $gate->project_id,
            'release_gate_id' => $gate->id,
            'user_id' => $user?->id,
            'event_type' => 'decision_package_created',
            'summary' => __('messages.release_gates.events.decision_package_created', ['report' => '#'.$report->id]),
            'severity' => 'info',
            'metadata_json' => ['report_version_id' => $report->id, 'checksum' => $report->checksum],
            'occurred_at' => now(),
        ]);
    }

    private function buildStoredZip(array $files): string
    {
        $data = '';
        $centralDirectory = '';
        $entries = 0;
        $dosTime = $this->zipDosTime();
        $dosDate = $this->zipDosDate();

        foreach ($files as $name => $contents) {
            $name = str_replace('\\', '/', ltrim((string) $name, '/'));
            $contents = (string) $contents;
            $crc = (int) sprintf('%u', crc32($contents));
            $size = strlen($contents);
            $offset = strlen($data);
            $nameLength = strlen($name);

            $data .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0);
            $data .= $name.$contents;

            $centralDirectory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset);
            $centralDirectory .= $name;
            $entries++;
        }

        $centralOffset = strlen($data);
        $centralSize = strlen($centralDirectory);

        return $data
            .$centralDirectory
            .pack('VvvvvVVv', 0x06054b50, 0, 0, $entries, $entries, $centralSize, $centralOffset, 0);
    }

    private function zipDosTime(): int
    {
        $hour = (int) date('H');
        $minute = (int) date('i');
        $second = (int) floor(((int) date('s')) / 2);

        return ($hour << 11) | ($minute << 5) | $second;
    }

    private function zipDosDate(): int
    {
        $year = max(1980, (int) date('Y')) - 1980;
        $month = (int) date('n');
        $day = (int) date('j');

        return ($year << 9) | ($month << 5) | $day;
    }
}
