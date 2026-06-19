<?php

namespace App\Services;

use App\Models\ReleaseDecisionSnapshot;
use App\Models\ReportVersion;
use App\Models\User;

class ReleaseDecisionReportVersionService
{
    public function __construct(
        private readonly ReleaseDecisionSnapshotService $snapshotService,
        private readonly ReportVisualStandardService $visualStandardService,
    ) {
    }

    public function createFromSnapshot(ReleaseDecisionSnapshot $snapshot, ?User $user, ?string $notes = null): ReportVersion
    {
        $snapshot->loadMissing(['project', 'decidedBy', 'releaseReadinessRun']);
        $project = $snapshot->project;
        $preview = $this->snapshotService->reportPreview($snapshot);
        $markdown = $preview['markdown'];
        $data = [
            'source' => [
                'type' => 'release_decision_snapshot',
                'release_decision_snapshot_id' => $snapshot->id,
                'release_readiness_run_id' => $snapshot->release_readiness_run_id,
                'decision' => $snapshot->decision,
                'decided_at' => $snapshot->decided_at?->toDateTimeString(),
                'decided_by' => $snapshot->decidedBy?->name,
            ],
            'project' => [
                'id' => $project?->id,
                'name' => $project?->name,
                'base_url' => $project?->base_url,
                'release_goal' => $project?->release_goal,
            ],
            'report_preview' => $preview,
            'evidence_summary' => $snapshot->evidence_summary,
            'source_state' => $snapshot->source_state,
            'readiness_metrics' => $snapshot->readiness_metrics,
            'readiness_checks' => $snapshot->readiness_checks,
            'generated_at' => now()->toDateTimeString(),
        ];

        $checksum = hash('sha256', $markdown.'|'.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $project->reportVersions()->create([
            'generated_by_user_id' => $user?->id,
            'release_readiness_run_id' => $snapshot->release_readiness_run_id,
            'release_decision_snapshot_id' => $snapshot->id,
            'type' => 'release_decision',
            'status' => 'draft',
            'title' => __('messages.reports.release_decision_title', [
                'project' => $project?->name ?? __('messages.common.not_available'),
                'id' => $snapshot->id,
                'date' => now()->format('Y-m-d H:i'),
            ]),
            'content_markdown' => $markdown,
            'content_html' => $this->visualStandardService->inlineHtmlFromMarkdown($markdown, 'aptoria-release-decision-report-html'),
            'data_json' => $data,
            'checksum' => $checksum,
            'notes' => $notes,
            'generated_at' => now(),
        ]);
    }

    public function updateStatus(ReportVersion $report, string $status, ?User $user, array $context = []): ReportVersion
    {
        if (! in_array($status, ReportVersion::STATUSES, true)) {
            $status = 'draft';
        }

        $report->status = $status;

        if ($status === 'reviewed') {
            $report->reviewed_by_user_id = $user?->id;
            $report->reviewed_at = now();
            $report->review_note = $context['review_note'] ?? $report->review_note;
        }

        if ($status === 'approved') {
            $report->approved_by_user_id = $user?->id;
            $report->approved_at = now();
            $report->approval_note = $context['approval_note'] ?? $report->approval_note;
            $report->approval_signoff_name = $context['approval_signoff_name'] ?? ($user?->name ?: $report->approval_signoff_name);
            $report->approval_signoff_role = $context['approval_signoff_role'] ?? $report->approval_signoff_role;
            $report->approval_signoff_statement = $context['approval_signoff_statement'] ?? __('messages.reports.default_signoff_statement');
            $report->approval_signed_at = now();
            $report->approval_context_json = $this->approvalContext($report, $user, $context);

            if (! $report->reviewed_at) {
                $report->reviewed_by_user_id = $user?->id;
                $report->reviewed_at = now();
                $report->review_note = $context['review_note'] ?? $report->review_note;
            }
        }

        if ($status === 'archived') {
            $report->archived_by_user_id = $user?->id;
            $report->archived_at = now();
            $report->archive_note = $context['archive_note'] ?? $report->archive_note;
        }

        $report->save();

        return $report;
    }


    private function approvalContext(ReportVersion $report, ?User $user, array $context = []): array
    {
        return [
            'approved_by_user_id' => $user?->id,
            'approved_by_name' => $user?->name,
            'approved_at' => now()->toDateTimeString(),
            'status_before_approval' => $report->getOriginal('status'),
            'signoff_name' => $context['approval_signoff_name'] ?? $user?->name,
            'signoff_role' => $context['approval_signoff_role'] ?? null,
            'source_checksum' => $report->checksum,
            'source_type' => $report->type,
            'release_readiness_run_id' => $report->release_readiness_run_id,
            'release_decision_snapshot_id' => $report->release_decision_snapshot_id,
        ];
    }

    public function exportHtml(ReportVersion $report): string
    {
        return $this->visualStandardService->exportHtml($report);
    }

    public function exportPdf(ReportVersion $report): string
    {
        $report->loadMissing(['releaseGate.items.reviewedBy', 'releaseGate.events.user', 'releaseGate.createdBy', 'releaseGate.finalizedBy']);
        if ($report->releaseGate) {
            return $this->visualStandardService->exportReleaseGateDecisionPackagePdf(
                $report->releaseGate,
                is_array($report->data_json) ? $report->data_json : [],
                $report
            );
        }

        return $this->minimalPdf($report->title ?: __('messages.reports.snapshot_detail'), $report->content_markdown ?: '');
    }


    private function approvalHtmlBlock(ReportVersion $report): string
    {
        if (! $report->has_approval_signoff && blank($report->review_note) && blank($report->approval_note) && blank($report->archive_note)) {
            return '';
        }

        $escape = fn ($value): string => e((string) $value);
        $rows = '<div class="meta">';
        $rows .= '<div><span>'.$escape(__('messages.reports.review_note')).'</span><strong>'.$escape($report->review_note ?: '—').'</strong></div>';
        $rows .= '<div><span>'.$escape(__('messages.reports.approval_note')).'</span><strong>'.$escape($report->approval_note ?: '—').'</strong></div>';
        $rows .= '<div><span>'.$escape(__('messages.reports.signoff_statement')).'</span><strong>'.$escape($report->approval_signoff_statement ?: '—').'</strong></div>';
        $rows .= '</div>';

        return '<div class="approval-signoff-block"><h2>'.$escape(__('messages.reports.approval_signoff')).'</h2>'.$rows.'</div>';
    }

    private function inlineHtmlFromMarkdown(string $markdown): string
    {
        $html = e($markdown);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^\*\*(.+?)\*\*: (.+)$/m', '<p><strong>$1:</strong> $2</p>', $html);
        $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
        $html = nl2br($html);

        return '<article class="aptoria-report-html aptoria-release-decision-report-html">'.$html.'</article>';
    }

    private function minimalPdf(string $title, string $text): string
    {
        $lines = [];
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $wrapped = wordwrap($this->pdfText($line), 92, "\n", true);
            foreach (explode("\n", $wrapped) as $part) {
                $lines[] = $part;
            }
        }

        $pages = array_chunk($lines, 54);
        if ($pages === []) {
            $pages = [[]];
        }

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $objectNumber = 3;
        foreach ($pages as $_) {
            $kids[] = $objectNumber.' 0 R';
            $objectNumber += 2;
        }
        $objects[] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($pages).' >>';

        $pageObjectNumber = 3;
        foreach ($pages as $pageLines) {
            $stream = "BT\n/F1 10 Tf\n50 795 Td\n13 TL\n";
            foreach ($pageLines as $line) {
                $stream .= '('.$this->escapePdfString($line).") Tj\nT*\n";
            }
            $stream .= "ET\n";
            $contentObjectNumber = $pageObjectNumber + 1;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 '.($contentObjectNumber + 1).' 0 R >> >> /Contents '.$contentObjectNumber.' 0 R >>';
            $objects[] = '<< /Length '.strlen($stream).' >>' . "\nstream\n" . $stream . "endstream";
            $pageObjectNumber += 2;
        }

        $fontObjectNumber = count($objects) + 1;
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        for ($i = 2; $i < count($objects); $i += 2) {
            $objects[$i] = preg_replace('/\/F1 \d+ 0 R/', '/F1 '.$fontObjectNumber.' 0 R', $objects[$i]);
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i])."\n";
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xrefOffset."\n%%EOF";

        return $pdf;
    }

    private function pdfText(string $text): string
    {
        $text = str_replace(['ő', 'Ő', 'ű', 'Ű'], ['o', 'O', 'u', 'U'], $text);
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '', $text);
    }

    private function escapePdfString(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
