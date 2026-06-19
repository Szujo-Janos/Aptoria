<?php

namespace App\Services;

use App\Models\EvidencePack;
use App\Models\ReleaseGate;
use App\Models\ReportVersion;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class ReportVisualStandardService
{
    public const STANDARD_VERSION = 'report-visual-standard-v1.1';

    public function exportHtml(ReportVersion $report): string
    {
        $report->loadMissing(['project', 'generatedBy', 'reviewedBy', 'approvedBy', 'archivedBy']);

        $title = $report->title ?: $this->t('messages.reports.snapshot_detail', 'Report version');
        $project = $report->project;
        $generatedBy = $report->generatedBy;
        $preparedBy = $generatedBy?->report_prepared_by ?: $generatedBy?->name ?: $this->t('messages.common.not_available', 'Not available');
        $organization = $generatedBy?->report_organization ?: 'Aptoria';
        $roleTitle = $generatedBy?->report_role_title ?: $this->t('messages.reports.prepared_by_role_fallback', 'QA reviewer');
        $confidentiality = $generatedBy?->report_confidentiality_label ?: $this->t('messages.profile.default_confidentiality', 'Internal QA Evidence');
        $disclaimer = $generatedBy?->report_disclaimer ?: $this->t('messages.reports.standard_disclaimer', 'This report is a fixed QA evidence snapshot. It supports review and release decisions, but it does not replace human approval.');
        $data = is_array($report->data_json) ? $report->data_json : [];
        $escape = fn ($value): string => e((string) $value);
        $generatedAt = $report->generated_at?->format('Y-m-d H:i') ?? $report->created_at?->format('Y-m-d H:i') ?? '—';
        $projectName = $project?->name ?? data_get($data, 'project.name', '—');
        $baseUrl = $project?->base_url ?: data_get($data, 'project.base_url', '—');

        $sections = [];
        $sections[] = $this->executiveSummarySection($report, $data, count($sections) + 1);
        $sections[] = $this->evidenceSummarySection($report, $data, count($sections) + 1);
        $sections[] = $this->findingsRiskSection($data, count($sections) + 1);
        $sections[] = $this->releaseDecisionSection($report, $data, count($sections) + 1);

        if ($this->hasApprovalContext($report)) {
            $sections[] = $this->approvalSection($report, count($sections) + 1);
        }

        $sections[] = $this->technicalAppendixSection($report, count($sections) + 1);

        return '<!doctype html>
<html lang="'.$escape(app()->getLocale()).'">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>'.$escape($title).'</title>
<style>'.$this->css().'</style>
</head>
<body>
<main class="report" data-aptoria-report-standard="'.self::STANDARD_VERSION.'">
  <header class="report-header">
    <div class="header-top">
      <div class="brand">'.$this->logoHtml().'</div>
      <div class="title-block">
        <div class="kicker">'.$escape($confidentiality).'</div>
        <h1>'.$escape($title).'</h1>
        <div class="subtitle">'.$escape($this->t('messages.reports.standard_subtitle', 'Fixed QA evidence report generated from Aptoria project state.')).'</div>
      </div>
      <div class="prepared">
        <div class="prepared-label">'.$escape($this->t('messages.reports.prepared_by', 'Prepared by')).'</div>
        <div class="prepared-name">'.$escape($preparedBy).'</div>
        <div>'.$escape($roleTitle).'</div>
        <div>'.$escape($organization).'</div>
        <div>'.$escape($generatedAt).'</div>
      </div>
    </div>
    <table class="meta-table" aria-label="Report metadata">
      <tr><th>'.$escape($this->t('messages.projects.name', 'Project')).'</th><td>'.$escape($projectName).'</td><th>'.$escape($this->t('messages.reports.type', 'Type')).'</th><td>'.$escape($report->type_label).'</td></tr>
      <tr><th>'.$escape($this->t('messages.projects.base_url', 'Base URL')).'</th><td>'.$escape($baseUrl ?: '—').'</td><th>'.$escape($this->t('messages.reports.status', 'Report status')).'</th><td>'.$this->statusPill($report->status_label, $this->statusTone($report->status)).'</td></tr>
      <tr><th>'.$escape($this->t('messages.reports.generated_at', 'Generated at')).'</th><td>'.$escape($generatedAt).'</td><th>'.$escape($this->t('messages.reports.checksum', 'Checksum')).'</th><td><code>'.$escape($report->checksum ?: '—').'</code></td></tr>
    </table>
  </header>
  <div class="summary-strip">'.implode('', $this->summaryCells($report, $data)).'</div>
  <div class="report-body">
    <div class="notice"><h2>'.$escape($this->t('messages.reports.standard_notice_title', 'Evidence note')).'</h2><p>'.$escape($disclaimer).'</p></div>
    '.implode("\n", $sections).'
  </div>
  <footer class="report-footer">
    <div class="footer-grid">
      <div><div class="footer-title">Aptoria QA Evidence Report</div><div>'.$escape($this->t('messages.reports.versioned_export_footer', 'Generated from a fixed Aptoria ReportVersion record.')).'</div></div>
      <div class="footer-meta">'.$escape($this->t('messages.reports.standard_version', 'Report standard')).': '.self::STANDARD_VERSION.'<br>'.$escape($this->t('messages.reports.checksum', 'Checksum')).': <code>'.$escape(Str::limit($report->checksum ?: '—', 24, '')).'</code></div>
    </div>
  </footer>
</main>
</body>
</html>';
    }


    public function exportEvidencePackHtml(EvidencePack $pack): string
    {
        $pack->loadMissing(['project', 'createdBy', 'releaseReadinessRun', 'reportVersion']);

        $project = $pack->project;
        $createdBy = $pack->createdBy;
        $readiness = $pack->releaseReadinessRun;
        $report = $pack->reportVersion;
        $manifest = is_array($pack->manifest_json) ? $pack->manifest_json : [];
        $counts = data_get($manifest, 'counts', []);
        $escape = fn ($value): string => e((string) $value);
        $title = $pack->title ?: $this->t('messages.evidence_packs.title', 'Evidence Pack Builder');
        $generatedAt = $pack->generated_at?->format('Y-m-d H:i') ?? $pack->created_at?->format('Y-m-d H:i') ?? '—';
        $projectName = $project?->name ?? data_get($manifest, 'project.name', '—');
        $baseUrl = $project?->base_url ?: data_get($manifest, 'project.base_url', '—');
        $preparedBy = $createdBy?->report_prepared_by ?: $createdBy?->name ?: $this->t('messages.common.not_available', 'Not available');
        $organization = $createdBy?->report_organization ?: 'Aptoria';
        $roleTitle = $createdBy?->report_role_title ?: $this->t('messages.reports.prepared_by_role_fallback', 'QA reviewer');
        $confidentiality = $createdBy?->report_confidentiality_label ?: $this->t('messages.profile.default_confidentiality', 'Internal QA Evidence');
        $disclaimer = $createdBy?->report_disclaimer ?: $this->t('messages.evidence_packs.standard_notice_copy', 'This evidence package is a fixed export package for review, audit and release handoff. It supports decisions, but it does not replace human approval.');
        $packTypeLabel = $this->safeLabel('messages.evidence_packs.types.'.($pack->pack_type ?: 'release_evidence'), Str::of((string) ($pack->pack_type ?: 'release_evidence'))->replace('_', ' ')->title()->toString());
        $statusLabel = $this->safeLabel('messages.evidence_packs.statuses.'.($pack->status ?: 'generated'), Str::of((string) ($pack->status ?: 'generated'))->replace('_', ' ')->title()->toString());

        $summaryCells = [
            $this->summaryCell($this->t('messages.evidence_packs.pack_status', 'Pack status'), $statusLabel, 'ok'),
            $this->summaryCell($this->t('messages.evidence_packs.type', 'Type'), $packTypeLabel, 'ok'),
            $this->summaryCell($this->t('messages.release_readiness.blockers', 'Blockers'), $readiness?->blocker_count ?? '—', is_numeric($readiness?->blocker_count) && (int) $readiness->blocker_count > 0 ? 'danger' : 'ok'),
            $this->summaryCell($this->t('messages.release_readiness.warnings', 'Warnings'), $readiness?->warning_count ?? '—', is_numeric($readiness?->warning_count) && (int) $readiness->warning_count > 0 ? 'warn' : 'ok'),
            $this->summaryCell($this->t('messages.evidence.total', 'Total evidence'), data_get($counts, 'evidence', '—'), 'ok'),
        ];

        $sections = [];
        $sections[] = $this->section(1, $this->t('messages.release_decisions.report.executive_summary', 'Executive Summary'),
            '<p class="lead">'.$escape($this->t('messages.evidence_packs.standard_headline', 'This package consolidates the selected Aptoria QA evidence into a fixed, checksum-backed handoff document.')).'</p>'.
            '<p>'.$escape($this->t('messages.evidence_packs.standard_subtitle', 'The HTML export uses the shared Aptoria report visual standard, so evidence packs no longer download as raw dashboard fragments.')).'</p>'.
            '<div class="mini-grid">'.
            '<div class="mini-card"><span>'.$escape($this->t('messages.evidence_packs.sections', 'Sections')).'</span><strong>'.$escape(implode(', ', (array) ($pack->included_sections_json ?? [])) ?: '—').'</strong></div>'.
            '<div class="mini-card"><span>'.$escape($this->t('messages.reports.linked_report', 'Linked report')).'</span><strong>'.$escape($report?->title ?: '—').'</strong></div>'.
            '<div class="mini-card"><span>'.$escape($this->t('messages.nav.release_readiness', 'Release readiness')).'</span><strong>'.$escape($readiness ? (($readiness->status ?? '—').' · '.($readiness->score ?? '—').'/100') : '—').'</strong></div>'.
            '<div class="mini-card"><span>'.$escape($this->t('messages.evidence_packs.pack', 'Pack')).'</span><strong>#'.$escape($pack->id ?: '—').'</strong></div>'.
            '</div>'
        );

        $summaryRows = [
            [$this->t('messages.evidence_packs.type', 'Type'), $packTypeLabel],
            [$this->t('messages.evidence_packs.sections', 'Sections'), implode(', ', (array) ($pack->included_sections_json ?? [])) ?: '—'],
            [$this->t('messages.nav.release_readiness', 'Release readiness'), $readiness ? ('#'.$readiness->id.' · '.$readiness->status.' · score '.$readiness->score.'/100') : '—'],
            [$this->t('messages.nav.reports', 'Reports'), $report ? ('#'.$report->id.' · '.$report->title.' · '.$report->status_label) : '—'],
            [$this->t('messages.reports.metric_labels.evidence', 'Evidence items'), data_get($counts, 'evidence', 0)],
            [$this->t('messages.nav.findings', 'Findings'), data_get($counts, 'findings', 0)],
            [$this->t('messages.nav.import_center', 'Import center'), data_get($counts, 'imports', 0)],
            [$this->t('messages.nav.contract_validation', 'OpenAPI contract'), data_get($counts, 'contract_runs', 0)],
            [$this->t('messages.risk_acceptance.accepted_risk', 'Accepted risk'), data_get($counts, 'risk_acceptances', 0)],
        ];
        $sections[] = $this->section(2, $this->t('messages.evidence_packs.evidence_summary', 'Evidence Summary'), $this->rowsTable($summaryRows));

        $sections[] = $this->section(3, $this->t('messages.evidence_packs.manifest', 'Manifest'), '<pre class="manifest-json"><code>'.$escape(json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}').'</code></pre>');

        $content = $this->normalizeReportBodyHtml($this->inlineHtmlFromMarkdown($pack->content_markdown ?: ''), $title);
        $checksum = '<div class="appendix-note"><strong>'.$escape($this->t('messages.reports.checksum', 'Checksum')).':</strong> <code>'.$escape($pack->checksum ?: '—').'</code></div>';
        $sections[] = $this->section(4, $this->t('messages.reports.standard_sections.appendix', 'Technical Appendix'), '<div class="report-content">'.$content.'</div>'.$checksum);

        return '<!doctype html>
<html lang="'.$escape(app()->getLocale()).'">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>'.$escape($title).'</title>
<style>'.$this->css().'</style>
</head>
<body>
<main class="report" data-aptoria-report-standard="'.self::STANDARD_VERSION.'">
  <header class="report-header">
    <div class="header-top">
      <div class="brand">'.$this->logoHtml().'</div>
      <div class="title-block">
        <div class="kicker">'.$escape($confidentiality).'</div>
        <h1>'.$escape($title).'</h1>
        <div class="subtitle">'.$escape($this->t('messages.evidence_packs.standard_export_subtitle', 'Fixed QA evidence package generated from Aptoria project state.')).'</div>
      </div>
      <div class="prepared">
        <div class="prepared-label">'.$escape($this->t('messages.reports.prepared_by', 'Prepared by')).'</div>
        <div class="prepared-name">'.$escape($preparedBy).'</div>
        <div>'.$escape($roleTitle).'</div>
        <div>'.$escape($organization).'</div>
        <div>'.$escape($generatedAt).'</div>
      </div>
    </div>
    <table class="meta-table" aria-label="Report metadata">
      <tr><th>'.$escape($this->t('messages.projects.name', 'Project')).'</th><td>'.$escape($projectName).'</td><th>'.$escape($this->t('messages.reports.type', 'Type')).'</th><td>'.$escape($packTypeLabel).'</td></tr>
      <tr><th>'.$escape($this->t('messages.projects.base_url', 'Base URL')).'</th><td>'.$escape($baseUrl ?: '—').'</td><th>'.$escape($this->t('messages.reports.status', 'Report status')).'</th><td>'.$this->statusPill($statusLabel, 'ok').'</td></tr>
      <tr><th>'.$escape($this->t('messages.reports.generated_at', 'Generated at')).'</th><td>'.$escape($generatedAt).'</td><th>'.$escape($this->t('messages.reports.checksum', 'Checksum')).'</th><td><code>'.$escape($pack->checksum ?: '—').'</code></td></tr>
    </table>
  </header>
  <div class="summary-strip">'.implode('', $summaryCells).'</div>
  <div class="report-body">
    <div class="notice"><h2>'.$escape($this->t('messages.reports.standard_notice_title', 'Evidence note')).'</h2><p>'.$escape($disclaimer).'</p></div>
    '.implode("\n", $sections).'
  </div>
  <footer class="report-footer">
    <div class="footer-grid">
      <div><div class="footer-title">Aptoria QA Evidence Package</div><div>'.$escape($this->t('messages.evidence_packs.versioned_export_footer', 'Generated from a fixed Aptoria EvidencePack record.')).'</div></div>
      <div class="footer-meta">'.$escape($this->t('messages.reports.standard_version', 'Report standard')).': '.self::STANDARD_VERSION.'<br>'.$escape($this->t('messages.reports.checksum', 'Checksum')).': <code>'.$escape(Str::limit($pack->checksum ?: '—', 24, '')).'</code></div>
    </div>
  </footer>
</main>
</body>
</html>';
    }

    public function exportEvidencePackPdf(EvidencePack $pack): string
    {
        $pack->loadMissing(['project', 'createdBy', 'releaseReadinessRun', 'reportVersion']);
        $manifest = is_array($pack->manifest_json) ? $pack->manifest_json : [];
        $counts = data_get($manifest, 'counts', []);
        $title = $pack->title ?: $this->t('messages.evidence_packs.title', 'Evidence Pack');
        $projectName = $pack->project?->name ?? data_get($manifest, 'project.name', '—');
        $baseUrl = $pack->project?->base_url ?: data_get($manifest, 'project.base_url', '—');
        $packType = Str::of((string) ($pack->pack_type ?: 'release_evidence'))->replace('_', ' ')->title()->toString();
        $status = Str::of((string) ($pack->status ?: 'generated'))->replace('_', ' ')->title()->toString();
        $generatedAt = $pack->generated_at?->format('Y-m-d H:i') ?? '—';
        $readiness = $pack->releaseReadinessRun;
        $report = $pack->reportVersion;

        $metaRows = [
            [$this->t('messages.projects.name', 'Project'), $projectName],
            [$this->t('messages.projects.base_url', 'Base URL'), $baseUrl ?: '—'],
            [$this->t('messages.evidence_packs.type', 'Type'), $packType],
            [$this->t('messages.reports.status', 'Report status'), $status],
            [$this->t('messages.reports.generated_at', 'Generated at'), $generatedAt],
            [$this->t('messages.reports.checksum', 'Checksum'), $pack->checksum ?: '—'],
        ];

        $summaryCards = [
            [$this->t('messages.reports.status', 'Report status'), $status],
            [$this->t('messages.nav.release_readiness', 'Release readiness'), $readiness ? (($readiness->score ?? '—').'/100') : '—'],
            [$this->t('messages.nav.findings', 'Findings'), (string) data_get($counts, 'findings', 0)],
            [$this->t('messages.reports.metric_labels.evidence', 'Evidence items'), (string) data_get($counts, 'evidence', 0)],
            [$this->t('messages.evidence_packs.pack', 'Pack'), '#'.($pack->id ?: '—')],
        ];

        $summaryRows = [
            [$this->t('messages.evidence_packs.sections', 'Sections'), implode(', ', (array) ($pack->included_sections_json ?? [])) ?: '—'],
            [$this->t('messages.nav.release_readiness', 'Release readiness'), $readiness ? ('#'.$readiness->id.' · '.$readiness->status.' · score '.$readiness->score.'/100') : '—'],
            [$this->t('messages.nav.reports', 'Reports'), $report ? ('#'.$report->id.' · '.$report->title.' · '.$report->status_label) : '—'],
            [$this->t('messages.nav.import_center', 'Import center'), (string) data_get($counts, 'imports', 0)],
            [$this->t('messages.nav.contract_validation', 'OpenAPI contract'), (string) data_get($counts, 'contract_runs', 0)],
            [$this->t('messages.risk_acceptance.accepted_risk', 'Accepted risk'), (string) data_get($counts, 'risk_acceptances', 0)],
        ];

        $sections = [
            [
                'title' => $this->t('messages.evidence_packs.evidence_summary', 'Evidence Summary'),
                'type' => 'table',
                'rows' => $summaryRows,
            ],
            [
                'title' => $this->t('messages.evidence_packs.manifest', 'Manifest'),
                'type' => 'table',
                'rows' => [
                    [$this->t('messages.projects.name', 'Project'), $projectName],
                    [$this->t('messages.evidence_packs.sections', 'Sections'), implode(', ', (array) ($pack->included_sections_json ?? [])) ?: '—'],
                    [$this->t('messages.reports.metric_labels.evidence', 'Evidence items'), (string) data_get($counts, 'evidence', 0)],
                    [$this->t('messages.nav.findings', 'Findings'), (string) data_get($counts, 'findings', 0)],
                    [$this->t('messages.reports.checksum', 'Checksum'), $pack->checksum ?: '—'],
                ],
            ],
            [
                'title' => $this->t('messages.reports.standard_sections.appendix', 'Technical Appendix'),
                'type' => 'markdown',
                'content' => $pack->content_markdown ?: '—',
            ],
        ];

        return $this->formattedEvidencePdf(
            $title,
            $this->t('messages.evidence_packs.standard_export_subtitle', 'Fixed QA evidence package generated from Aptoria project state.'),
            $metaRows,
            $summaryCards,
            $sections,
            $pack->checksum ?: '—'
        );
    }



    public function exportReleaseGateDecisionPackagePdf(ReleaseGate $gate, array $data, ?ReportVersion $report = null): string
    {
        $gate->loadMissing(['project', 'createdBy', 'finalizedBy', 'readinessRun', 'items.reviewedBy', 'events.user']);
        $title = $report?->title ?: $this->t('messages.release_gates.package.title', 'Release Gate Decision Package');
        $projectName = $gate->project?->name ?? data_get($data, 'project.name', '—');
        $baseUrl = $gate->project?->base_url ?: data_get($data, 'project.base_url', '—');
        $checksum = $report?->checksum ?: hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');

        $metaRows = [
            [$this->t('messages.projects.name', 'Project'), $projectName],
            [$this->t('messages.projects.base_url', 'Base URL'), $baseUrl ?: '—'],
            [$this->t('messages.release_gates.gate', 'Gate'), $gate->title],
            [$this->t('messages.release_gates.form.release_version', 'Release version'), $gate->release_version ?: '—'],
            [$this->t('messages.release_gates.form.target_environment', 'Target environment'), $gate->target_environment ?: '—'],
            [$this->t('messages.release_gates.final_decision', 'Final decision'), $gate->final_decision_label],
            [$this->t('messages.release_gates.package.finalized_by', 'Finalized by'), $gate->finalizedBy?->name ?: '—'],
            [$this->t('messages.release_gates.package.finalized_at', 'Finalized at'), $gate->finalized_at?->format('Y-m-d H:i') ?? '—'],
            [$this->t('messages.reports.checksum', 'Checksum'), $checksum ?: '—'],
        ];

        $summaryCards = [
            [$this->t('messages.release_gates.final_decision', 'Final decision'), $gate->final_decision_label],
            [$this->t('messages.release_readiness.score', 'Score'), $gate->score.'/100'],
            [$this->t('messages.release_gates.metrics.blockers', 'Blockers'), (string) $gate->blocker_count],
            [$this->t('messages.release_gates.metrics.warnings', 'Warnings'), (string) $gate->warning_count],
            [$this->t('messages.release_gates.metrics.verified_evidence', 'Verified evidence'), $gate->verified_evidence_count.' / '.$gate->evidence_count],
        ];

        $itemRows = [];
        foreach ((array) data_get($data, 'gate_items', []) as $item) {
            $itemRows[] = [
                trim(($item['category_label'] ?? '').' / '.($item['label'] ?? 'Gate item')),
                trim(($item['effective_state_label'] ?? '—').' · '.($item['required_action'] ?? '')),
            ];
            if (! empty($item['reviewer_note'])) {
                $itemRows[] = [$this->t('messages.release_gates.form.reviewer_note', 'Reviewer note'), $item['reviewer_note']];
            }
        }
        if ($itemRows === []) {
            $itemRows[] = [$this->t('messages.release_gates.items_title', 'Gate items'), '—'];
        }

        $eventRows = [];
        foreach ((array) data_get($data, 'gate_events', []) as $event) {
            $eventRows[] = [
                (string) ($event['occurred_at'] ?? '—'),
                trim(($event['summary'] ?? '—').' · '.($event['user'] ?? $this->t('messages.common.system', 'System'))),
            ];
        }
        if ($eventRows === []) {
            $eventRows[] = [$this->t('messages.release_gates.timeline_title', 'Gate timeline'), '—'];
        }

        $sections = [
            [
                'title' => $this->t('messages.release_decisions.report.executive_summary', 'Executive Summary'),
                'type' => 'table',
                'rows' => [
                    [$this->t('messages.release_gates.package.headline', 'Decision summary'), data_get($data, 'report_preview.headline', '—')],
                    [$this->t('messages.release_gates.automated_state', 'Automated state'), $gate->automated_decision_label],
                    [$this->t('messages.release_gates.statuses.needs_review', 'Gate status'), $gate->status_label],
                    [$this->t('messages.release_gates.form.decision_note', 'Decision note'), $gate->decision_note ?: '—'],
                ],
            ],
            [
                'title' => $this->t('messages.release_gates.items_title', 'Gate items'),
                'type' => 'table',
                'rows' => $itemRows,
            ],
            [
                'title' => $this->t('messages.release_gates.source_state_title', 'Source state'),
                'type' => 'table',
                'rows' => [
                    [$this->t('messages.nav.evidence', 'Evidence'), $gate->verified_evidence_count.' verified / '.$gate->evidence_count.' total'],
                    [$this->t('messages.nav.native_tests', 'Native tests'), $gate->failed_test_run_count.' failed or blocked / '.$gate->test_run_count.' total'],
                    [$this->t('messages.nav.findings', 'Findings'), $gate->high_critical_open_count.' high-critical / '.$gate->open_finding_count.' open'],
                    [$this->t('messages.nav.release_readiness', 'Release readiness'), '#'.($gate->release_readiness_run_id ?: '—').' · '.$gate->score.'/100 · '.$gate->grade],
                ],
            ],
            [
                'title' => $this->t('messages.release_gates.timeline_title', 'Gate timeline'),
                'type' => 'table',
                'rows' => $eventRows,
            ],
            [
                'title' => $this->t('messages.reports.standard_sections.appendix', 'Technical Appendix'),
                'type' => 'markdown',
                'content' => $report?->content_markdown ?: $this->t('messages.release_gates.package.appendix_note', 'Structured decision data is included in the decision-package.json export.'),
            ],
        ];

        return $this->formattedEvidencePdf(
            $title,
            $this->t('messages.release_gates.package.subtitle', 'Fixed release gate decision package generated from Aptoria evidence state.'),
            $metaRows,
            $summaryCards,
            $sections,
            $checksum ?: '—'
        );
    }

    public function inlineHtmlFromMarkdown(string $markdown, string $class = ''): string
    {
        $html = $this->markdownToInnerHtml($markdown);
        $class = trim('aptoria-report-html '.$class);

        return '<article class="'.$class.'">'.$html.'</article>';
    }

    private function executiveSummarySection(ReportVersion $report, array $data, int $number): string
    {
        $preview = data_get($data, 'report_preview', []);
        $headline = data_get($preview, 'headline') ?: data_get($data, 'summary.headline') ?: $this->executiveFallback($report, $data);
        $subtitle = data_get($preview, 'subtitle') ?: $this->t('messages.reports.snapshot_detail_copy', 'This report is stored as fixed evidence and can be downloaded without recalculating the project state.');
        $decisionLabel = data_get($preview, 'decision_label') ?: data_get($data, 'evidence_summary.decision.label') ?: data_get($data, 'source.decision');
        $score = $this->readinessScore($data);
        $grade = data_get($preview, 'grade') ?: data_get($data, 'evidence_summary.readiness.grade') ?: data_get($data, 'latest_readiness.grade');

        $meta = [
            [$this->t('messages.reports.status', 'Report status'), $report->status_label],
            [$this->t('messages.release_readiness.score', 'Score'), $score === null ? '—' : $score.'/100'],
            [$this->t('messages.release_readiness.grade', 'Grade'), $grade ?: '—'],
            [$this->t('messages.release_decisions.decision', 'Decision'), $decisionLabel ?: '—'],
        ];

        $cards = '';
        foreach ($meta as [$label, $value]) {
            $cards .= '<div class="mini-card"><span>'.e($label).'</span><strong>'.e((string) $value).'</strong></div>';
        }

        return $this->section($number, $this->t('messages.release_decisions.report.executive_summary', 'Executive Summary'),
            '<p class="lead">'.e((string) $headline).'</p><p>'.e((string) $subtitle).'</p><div class="mini-grid">'.$cards.'</div>'
        );
    }

    private function evidenceSummarySection(ReportVersion $report, array $data, int $number): string
    {
        $rows = [];
        $metrics = data_get($data, 'metrics', []);
        $readinessMetrics = data_get($data, 'readiness_metrics', []);
        $sourceState = data_get($data, 'source_state', []);
        $previewSignals = data_get($data, 'report_preview.signals', []);

        foreach ([
            'endpoints' => $this->t('messages.reports.metric_labels.endpoints', 'Endpoints'),
            'safe_endpoints' => $this->t('messages.reports.metric_labels.safe_endpoints', 'Safe scan candidates'),
            'scan_runs' => $this->t('messages.reports.metric_labels.scan_runs', 'Scan runs'),
            'scan_results' => $this->t('messages.reports.metric_labels.scan_results', 'Scan results'),
            'evidence' => $this->t('messages.reports.metric_labels.evidence', 'Evidence items'),
            'readiness_snapshots' => $this->t('messages.reports.metric_labels.readiness_snapshots', 'Readiness snapshots'),
        ] as $key => $label) {
            if (array_key_exists($key, $metrics)) {
                $rows[] = [$label, $metrics[$key]];
            }
        }

        foreach ([
            'quick_tests' => $this->t('messages.release_decisions.quick_test_state', 'Quick test state'),
            'assertions' => $this->t('messages.release_decisions.assertion_state', 'Assertion state'),
            'batch' => $this->t('messages.release_decisions.batch_state', 'Batch state'),
            'contract_validation' => $this->t('messages.release_decisions.contract_validation_state', 'OpenAPI contract state'),
            'retest_closure' => $this->t('messages.release_decisions.retest_closure_state', 'Retest closure state'),
        ] as $key => $label) {
            $state = data_get($sourceState, $key);
            if (is_array($state) && $state !== []) {
                $rows[] = [$label, $this->compactArraySummary($state)];
            }
        }

        foreach ($previewSignals as $signal) {
            if (is_array($signal)) {
                $rows[] = [($signal['label'] ?? 'Signal'), trim(($signal['state_label'] ?? '—').' · '.($signal['summary'] ?? '').' '.($signal['details'] ?? ''))];
            }
        }

        foreach ([
            'check_count' => $this->t('messages.release_readiness.checks', 'Readiness checks'),
            'passed_check_count' => $this->t('messages.release_readiness.passed_checks', 'Passed checks'),
            'blocker_count' => $this->t('messages.release_readiness.blockers', 'Blockers'),
            'warning_count' => $this->t('messages.release_readiness.warnings', 'Warnings'),
        ] as $key => $label) {
            if (array_key_exists($key, $readinessMetrics)) {
                $rows[] = [$label, $readinessMetrics[$key]];
            }
        }

        if ($rows === []) {
            $rows[] = [$this->t('messages.evidence.total', 'Total evidence'), data_get($data, 'evidence_summary.total', '—')];
        }

        return $this->section($number, $this->t('messages.reports.standard_sections.evidence_summary', 'Evidence Summary'), $this->rowsTable($rows));
    }

    private function findingsRiskSection(array $data, int $number): string
    {
        $risk = data_get($data, 'source_state.risk', []);
        $metrics = data_get($data, 'metrics', []);
        $rows = [
            [$this->t('messages.findings.open_findings', 'Open findings'), data_get($metrics, 'open_findings', data_get($risk, 'open_findings', '—'))],
            [$this->t('messages.findings.critical', 'Critical findings'), data_get($risk, 'critical_findings', '—')],
            [$this->t('messages.findings.high', 'High findings'), data_get($risk, 'high_findings', '—')],
            [$this->t('messages.release_decisions.missing_evidence', 'Missing evidence'), data_get($risk, 'missing_evidence', '—')],
            [$this->t('messages.release_decisions.retest_pending', 'Retest pending'), data_get($risk, 'retest_needed', '—')],
            [$this->t('messages.release_decisions.accepted_risk', 'Accepted risk'), data_get($risk, 'active_risk_acceptances', '—')],
        ];

        return $this->section($number, $this->t('messages.reports.standard_sections.findings', 'Findings & Risk'), $this->rowsTable($rows));
    }

    private function releaseDecisionSection(ReportVersion $report, array $data, int $number): string
    {
        $decision = data_get($data, 'report_preview.decision_label') ?: data_get($data, 'evidence_summary.decision.label') ?: data_get($data, 'source.decision') ?: '—';
        $status = data_get($data, 'report_preview.status_label') ?: data_get($data, 'evidence_summary.readiness.status_label') ?: $report->status_label;
        $score = $this->readinessScore($data);
        $blockers = data_get($data, 'latest_readiness.blocker_count', data_get($data, 'readiness_metrics.blocker_count', data_get($data, 'evidence_summary.readiness.blockers', '—')));
        $warnings = data_get($data, 'latest_readiness.warning_count', data_get($data, 'readiness_metrics.warning_count', data_get($data, 'evidence_summary.readiness.warnings', '—')));
        $sourceType = data_get($data, 'source.type', $report->type_label);
        $decidedAt = data_get($data, 'source.decided_at', data_get($data, 'generated_at', '—'));

        $rows = [
            [$this->t('messages.release_decisions.decision', 'Decision'), $decision],
            [$this->t('messages.common.status', 'Status'), $status],
            [$this->t('messages.release_readiness.score', 'Score'), $score === null ? '—' : $score.'/100'],
            [$this->t('messages.release_readiness.blockers', 'Blockers'), $blockers],
            [$this->t('messages.release_readiness.warnings', 'Warnings'), $warnings],
            [$this->t('messages.reports.source_context', 'Source context'), $sourceType],
            [$this->t('messages.release_decisions.generated_at', 'Generated at'), $decidedAt],
        ];

        return $this->section($number, $this->t('messages.reports.standard_sections.release_decision', 'Release Decision'), $this->rowsTable($rows));
    }

    private function technicalAppendixSection(ReportVersion $report, int $number): string
    {
        $content = $report->content_html ?: $this->inlineHtmlFromMarkdown($report->content_markdown ?: '');
        $content = $this->normalizeReportBodyHtml($content, $report->title ?: '');
        $checksum = '<div class="appendix-note"><strong>'.e($this->t('messages.reports.checksum', 'Checksum')).':</strong> <code>'.e($report->checksum ?: '—').'</code></div>';

        return $this->section($number, $this->t('messages.reports.standard_sections.appendix', 'Technical Appendix'), '<div class="report-content">'.$content.'</div>'.$checksum);
    }

    private function section(int $number, string $title, string $body): string
    {
        return '<section class="report-section" id="section-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT).'"><h2><span>'.str_pad((string) $number, 2, '0', STR_PAD_LEFT).'</span>'.e($title).'</h2>'.$body.'</section>';
    }

    private function rowsTable(array $rows): string
    {
        $htmlRows = '';
        foreach ($rows as [$label, $value]) {
            $htmlRows .= '<tr><th>'.e((string) $label).'</th><td>'.e(is_array($value) ? $this->compactArraySummary($value) : (string) $value).'</td></tr>';
        }

        return '<table class="rows-table"><tbody>'.$htmlRows.'</tbody></table>';
    }

    private function summaryCells(ReportVersion $report, array $data): array
    {
        $score = $this->readinessScore($data);
        $blockers = data_get($data, 'latest_readiness.blocker_count', data_get($data, 'readiness_metrics.blocker_count', data_get($data, 'evidence_summary.readiness.blockers', data_get($data, 'report_preview.counters.0.value', '—'))));
        $warnings = data_get($data, 'latest_readiness.warning_count', data_get($data, 'readiness_metrics.warning_count', data_get($data, 'evidence_summary.readiness.warnings', data_get($data, 'report_preview.counters.1.value', '—'))));
        $openFindings = data_get($data, 'metrics.open_findings', data_get($data, 'source_state.risk.open_findings', data_get($data, 'report_preview.counters.3.value', '—')));
        $evidence = data_get($data, 'metrics.evidence', data_get($data, 'evidence_summary.total', '—'));

        return [
            $this->summaryCell($this->t('messages.reports.status', 'Report status'), $report->status_label, $this->statusTone($report->status)),
            $this->summaryCell($this->t('messages.release_readiness.score', 'Score'), $score === null ? '—' : $score.'/100', is_numeric($score) && (int) $score >= 80 ? 'ok' : (is_numeric($score) && (int) $score < 60 ? 'danger' : 'warn')),
            $this->summaryCell($this->t('messages.release_readiness.blockers', 'Blockers'), $blockers, is_numeric($blockers) && (int) $blockers > 0 ? 'danger' : 'ok'),
            $this->summaryCell($this->t('messages.release_readiness.warnings', 'Warnings'), $warnings, is_numeric($warnings) && (int) $warnings > 0 ? 'warn' : 'ok'),
            $this->summaryCell($this->t('messages.evidence.total', 'Total evidence'), $evidence === '—' ? $openFindings : $evidence, 'ok'),
        ];
    }

    private function summaryCell(string $label, mixed $value, string $tone = ''): string
    {
        $toneClass = in_array($tone, ['danger', 'warn', 'ok'], true) ? ' '.$tone : '';

        return '<div class="summary-cell"><span class="summary-label">'.e($label).'</span><span class="summary-value'.$toneClass.'">'.e((string) $value).'</span></div>';
    }

    private function statusPill(string $label, string $tone): string
    {
        $toneClass = in_array($tone, ['danger', 'warn', 'ok'], true) ? ' '.$tone : '';

        return '<span class="status-pill'.$toneClass.'">'.e($label).'</span>';
    }

    private function statusTone(?string $status): string
    {
        return match ($status) {
            'approved' => 'ok',
            'archived' => 'danger',
            'reviewed' => 'ok',
            default => 'warn',
        };
    }

    private function hasApprovalContext(ReportVersion $report): bool
    {
        return $report->has_approval_signoff || filled($report->review_note) || filled($report->approval_note) || filled($report->archive_note);
    }

    private function approvalSection(ReportVersion $report, int $number): string
    {
        $rows = [
            [$this->t('messages.reports.review_note', 'Review note'), $report->review_note ?: '—'],
            [$this->t('messages.reports.approval_note', 'Approval note'), $report->approval_note ?: '—'],
            [$this->t('messages.reports.signoff_name', 'Sign-off name'), $report->approval_signoff_display],
            [$this->t('messages.reports.approval_signed_at', 'Signed at'), $report->approval_signed_at?->format('Y-m-d H:i') ?? '—'],
            [$this->t('messages.reports.signoff_statement', 'Sign-off statement'), $report->approval_signoff_statement ?: '—'],
            [$this->t('messages.reports.archive_note', 'Archive note'), $report->archive_note ?: '—'],
        ];

        return $this->section($number, $this->t('messages.reports.approval_signoff', 'Approval sign-off'), $this->rowsTable($rows));
    }

    private function readinessScore(array $data): ?int
    {
        $score = data_get($data, 'latest_readiness.score', data_get($data, 'readiness_metrics.score', data_get($data, 'evidence_summary.readiness.score', data_get($data, 'report_preview.score'))));

        return is_numeric($score) ? (int) $score : null;
    }

    private function executiveFallback(ReportVersion $report, array $data): string
    {
        $blockers = data_get($data, 'latest_readiness.blocker_count', data_get($data, 'readiness_metrics.blocker_count', data_get($data, 'evidence_summary.readiness.blockers')));
        $warnings = data_get($data, 'latest_readiness.warning_count', data_get($data, 'readiness_metrics.warning_count', data_get($data, 'evidence_summary.readiness.warnings')));
        if (is_numeric($blockers) && (int) $blockers > 0) {
            return 'The current evidence package contains release blockers and needs remediation before production approval.';
        }
        if (is_numeric($warnings) && (int) $warnings > 0) {
            return 'The current evidence package contains warnings and needs manual review before final release approval.';
        }
        if ($report->status === 'approved') {
            return 'The report has been approved and can be used as a fixed evidence package for handoff.';
        }

        return 'This report captures the current QA evidence state in a fixed, reviewable package.';
    }

    private function compactArraySummary(array $data): string
    {
        $pairs = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            $label = Str::of((string) $key)->replace('_', ' ')->title();
            $pairs[] = $label.': '.$value;
            if (count($pairs) >= 6) {
                break;
            }
        }

        return $pairs === [] ? '—' : implode(' · ', $pairs);
    }

    private function normalizeReportBodyHtml(string $html, string $reportTitle): string
    {
        $html = trim($html);
        $html = preg_replace('/<\/?article\b[^>]*>/i', '', $html) ?? $html;
        $html = preg_replace('/<h1\b([^>]*)>(.*?)<\/h1>/is', '<h3$1>$2</h3>', $html) ?? $html;
        $html = preg_replace('/<h2\b([^>]*)>(.*?)<\/h2>/is', '<h3$1>$2</h3>', $html) ?? $html;
        $html = preg_replace('/<h3\b([^>]*)>\s*'.preg_quote(e($reportTitle), '/').'\s*<\/h3>/is', '', $html) ?? $html;

        return trim($html) ?: '<p>—</p>';
    }

    private function markdownToInnerHtml(string $markdown): string
    {
        $lines = preg_split('/\R/', trim($markdown)) ?: [];
        $html = '';
        $inList = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }
                continue;
            }

            if (str_starts_with($trimmed, '- ')) {
                if (! $inList) {
                    $html .= '<ul>';
                    $inList = true;
                }
                $html .= '<li>'.$this->inlineMarkdown(e(substr($trimmed, 2))).'</li>';
                continue;
            }

            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }

            if (str_starts_with($trimmed, '### ')) {
                $html .= '<h4>'.$this->inlineMarkdown(e(substr($trimmed, 4))).'</h4>';
            } elseif (str_starts_with($trimmed, '## ')) {
                $html .= '<h3>'.$this->inlineMarkdown(e(substr($trimmed, 3))).'</h3>';
            } elseif (str_starts_with($trimmed, '# ')) {
                $html .= '<h3>'.$this->inlineMarkdown(e(substr($trimmed, 2))).'</h3>';
            } elseif ($trimmed === '---') {
                $html .= '<hr>';
            } else {
                $html .= '<p>'.$this->inlineMarkdown(e($trimmed)).'</p>';
            }
        }

        if ($inList) {
            $html .= '</ul>';
        }

        return $html ?: '<p>—</p>';
    }

    private function inlineMarkdown(string $html): string
    {
        return preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html) ?? $html;
    }

    private function logoHtml(): string
    {
        $path = public_path('assets/aptoria-ui/assets/images/logo-color.svg');
        if (is_file($path)) {
            $base64 = base64_encode((string) file_get_contents($path));

            return '<img class="logo" src="data:image/svg+xml;base64,'.$base64.'" alt="Aptoria">';
        }

        return '<span class="logo-fallback"><span>A</span><strong>Aptoria</strong></span>';
    }

    private function safeLabel(string $key, string $fallback): string
    {
        return $this->t($key, $fallback);
    }

    private function t(string $key, string $fallback, array $replace = []): string
    {
        if (Lang::has($key)) {
            $translated = __($key, $replace);

            return is_string($translated) && $translated !== $key ? $translated : $fallback;
        }

        return $fallback;
    }

    private function formattedEvidencePdf(string $title, string $subtitle, array $metaRows, array $summaryCards, array $sections, string $checksum): string
    {
        $pageWidth = 595;
        $pageHeight = 842;
        $margin = 42;
        $contentWidth = $pageWidth - ($margin * 2);
        $streams = [];
        $stream = '';
        $y = 800;
        $sectionNumber = 1;

        $newPage = function () use (&$streams, &$stream, &$y): void {
            if ($stream !== '') {
                $streams[] = $stream;
            }
            $stream = '';
            $y = 800;
        };

        $cmd = function (string $command) use (&$stream): void {
            $stream .= $command."\n";
        };

        $rgb = function (string $hex): array {
            $hex = ltrim($hex, '#');
            if (strlen($hex) !== 6) {
                return [0, 0, 0];
            }

            return [hexdec(substr($hex, 0, 2)) / 255, hexdec(substr($hex, 2, 2)) / 255, hexdec(substr($hex, 4, 2)) / 255];
        };

        $fill = function (string $hex) use ($cmd, $rgb): void {
            [$r, $g, $b] = $rgb($hex);
            $cmd(sprintf('%.3F %.3F %.3F rg', $r, $g, $b));
        };

        $stroke = function (string $hex) use ($cmd, $rgb): void {
            [$r, $g, $b] = $rgb($hex);
            $cmd(sprintf('%.3F %.3F %.3F RG', $r, $g, $b));
        };

        $rect = function (float $x, float $bottomY, float $w, float $h, string $fillHex = '', string $strokeHex = '') use ($cmd, $fill, $stroke): void {
            $cmd('q');
            if ($fillHex !== '') {
                $fill($fillHex);
            }
            if ($strokeHex !== '') {
                $stroke($strokeHex);
            }
            $cmd(sprintf('%.2F %.2F %.2F %.2F re %s', $x, $bottomY, $w, $h, $fillHex !== '' && $strokeHex !== '' ? 'B' : ($fillHex !== '' ? 'f' : 'S')));
            $cmd('Q');
        };

        $line = function (float $x1, float $y1, float $x2, float $y2, string $hex = '#d9e2ee', float $width = 1) use ($cmd, $stroke): void {
            $cmd('q');
            $stroke($hex);
            $cmd(sprintf('%.2F w %.2F %.2F m %.2F %.2F l S', $width, $x1, $y1, $x2, $y2));
            $cmd('Q');
        };

        $text = function (float $x, float $baselineY, string $value, string $font = 'F1', float $size = 10, string $hex = '#111827') use ($cmd, $fill): void {
            $fill($hex);
            $cmd(sprintf('BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET', $font, $size, $x, $baselineY, $this->escapePdfString($this->pdfText($value))));
        };

        $wrap = function (string $value, int $maxChars): array {
            $lines = [];
            foreach (preg_split('/\R/', trim($value)) ?: [] as $raw) {
                $raw = trim($this->pdfText($raw));
                if ($raw === '') {
                    $lines[] = '';
                    continue;
                }
                foreach (explode("\n", wordwrap($raw, $maxChars, "\n", true)) as $line) {
                    $lines[] = $line;
                }
            }

            return $lines === [] ? ['—'] : $lines;
        };

        $ensureSpace = function (float $height) use (&$y, $margin, $newPage, $line, $text, $pageWidth, $checksum): void {
            if ($y - $height < $margin + 32) {
                $line($margin, 54, $pageWidth - $margin, 54, '#d9e2ee', 0.8);
                $text($margin, 36, 'Aptoria report standard: report-visual-standard-v1.1', 'F1', 7.5, '#64748b');
                $text(360, 36, 'Checksum: '.substr($checksum, 0, 28), 'F3', 7.2, '#64748b');
                $newPage();
            }
        };

        $drawOfficialLogo = function (float $x, float $topY, float $width) use ($cmd): bool {
            $logoCommands = $this->officialLogoImagePdfCommands($x, $topY, $width);
            if ($logoCommands === '') {
                return false;
            }

            $cmd($logoCommands);

            return true;
        };

        $drawHeader = function () use (&$y, $margin, $contentWidth, $rect, $line, $text, $title, $subtitle, $drawOfficialLogo): void {
            $rect($margin, 808, $contentWidth * 0.72, 7, '#0787d7');
            $rect($margin + ($contentWidth * 0.72), 808, $contentWidth * 0.28, 7, '#12c8bd');
            $rect($margin, 728, $contentWidth, 80, '#fbfdff', '#d9e2ee');

            $logoDrawn = $drawOfficialLogo($margin + 16, 792, 174);
            if (! $logoDrawn) {
                $rect($margin + 16, 758, 44, 34, '#0787d7');
                $text($margin + 25, 770, 'APT', 'F2', 13, '#ffffff');
                $text($margin + 74, 786, 'APTORIA', 'F2', 15, '#0f172a');
            }
            $text($margin + 18, 736, 'QA Evidence Document', 'F1', 8.3, '#64748b');

            $text($margin + 210, 787, $title, 'F2', 17, '#111827');
            foreach (array_slice($this->pdfWrap($subtitle, 56), 0, 2) as $index => $lineText) {
                $text($margin + 210, 768 - ($index * 12), $lineText, 'F1', 9, '#475569');
            }
            $y = 710;
            $line($margin, $y, $margin + $contentWidth, $y, '#d9e2ee');
            $y -= 20;
        };

        $drawTable = function (array $rows, float $labelWidth = 150) use (&$y, $margin, $contentWidth, $ensureSpace, $rect, $text, $wrap): void {
            $valueWidth = $contentWidth - $labelWidth;
            foreach ($rows as [$label, $value]) {
                $labelLines = $wrap((string) $label, 24);
                $valueLines = $wrap(is_array($value) ? $this->compactArraySummary($value) : (string) $value, 64);
                $rowHeight = max(24, (max(count($labelLines), count($valueLines)) * 11) + 11);
                $ensureSpace($rowHeight + 2);
                $bottom = $y - $rowHeight;
                $rect($margin, $bottom, $labelWidth, $rowHeight, '#f1f5f9', '#d9e2ee');
                $rect($margin + $labelWidth, $bottom, $valueWidth, $rowHeight, '#ffffff', '#d9e2ee');
                foreach (array_slice($labelLines, 0, 4) as $idx => $lineText) {
                    $text($margin + 9, $y - 16 - ($idx * 11), $lineText, 'F2', 8.4, '#334155');
                }
                foreach (array_slice($valueLines, 0, 7) as $idx => $lineText) {
                    $text($margin + $labelWidth + 9, $y - 16 - ($idx * 11), $lineText, 'F1', 8.4, '#111827');
                }
                $y -= $rowHeight;
            }
            $y -= 12;
        };

        $drawSectionTitle = function (int $number, string $heading) use (&$y, $margin, $contentWidth, $ensureSpace, $line, $text): void {
            $ensureSpace(44);
            $text($margin, $y, str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'F2', 9, '#0787d7');
            $text($margin + 28, $y, $heading, 'F2', 14, '#0f172a');
            $y -= 10;
            $line($margin, $y, $margin + $contentWidth, $y, '#d9e2ee');
            $y -= 14;
        };

        $drawMarkdown = function (string $markdown) use (&$y, $margin, $contentWidth, $ensureSpace, $text, $wrap): void {
            foreach (preg_split('/\R/', trim($markdown)) ?: [] as $rawLine) {
                $lineValue = trim($rawLine);
                if ($lineValue === '') {
                    $y -= 5;
                    continue;
                }
                $font = 'F1';
                $size = 8.6;
                $x = $margin;
                $color = '#111827';
                if (str_starts_with($lineValue, '### ')) {
                    $lineValue = substr($lineValue, 4);
                    $font = 'F2';
                    $size = 9.8;
                    $color = '#0f172a';
                    $ensureSpace(22);
                    $y -= 3;
                } elseif (str_starts_with($lineValue, '## ')) {
                    $lineValue = substr($lineValue, 3);
                    $font = 'F2';
                    $size = 11;
                    $color = '#0f172a';
                    $ensureSpace(25);
                    $y -= 4;
                } elseif (str_starts_with($lineValue, '# ')) {
                    $lineValue = substr($lineValue, 2);
                    $font = 'F2';
                    $size = 11;
                    $color = '#0f172a';
                    $ensureSpace(25);
                } elseif (str_starts_with($lineValue, '- ')) {
                    $lineValue = '• '.substr($lineValue, 2);
                    $x = $margin + 12;
                }
                $lineValue = str_replace('**', '', $lineValue);
                foreach ($wrap($lineValue, $x === $margin ? 88 : 82) as $wrappedLine) {
                    $ensureSpace(14);
                    $text($x, $y, $wrappedLine, $font, $size, $color);
                    $y -= 12;
                }
            }
            $y -= 6;
        };

        $drawHeader();
        $drawSectionTitle($sectionNumber++, $this->t('messages.reports.metadata', 'Metadata'));
        $drawTable($metaRows);

        $ensureSpace(82);
        $cardGap = 7;
        $cardWidth = ($contentWidth - ($cardGap * 4)) / 5;
        foreach ($summaryCards as $index => [$label, $value]) {
            $x = $margin + ($index * ($cardWidth + $cardGap));
            $rect($x, $y - 58, $cardWidth, 58, '#f8fafc', '#d9e2ee');
            $text($x + 7, $y - 18, strtoupper($this->pdfText((string) $label)), 'F2', 6.8, '#64748b');
            foreach (array_slice($wrap((string) $value, 12), 0, 2) as $lineIndex => $wrappedValue) {
                $text($x + 7, $y - 37 - ($lineIndex * 11), $wrappedValue, 'F2', 11.5, '#0f172a');
            }
        }
        $y -= 78;

        foreach ($sections as $section) {
            $drawSectionTitle($sectionNumber++, (string) ($section['title'] ?? 'Section'));
            if (($section['type'] ?? 'table') === 'markdown') {
                $drawMarkdown((string) ($section['content'] ?? '—'));
            } else {
                $drawTable((array) ($section['rows'] ?? []));
            }
        }

        $line($margin, 54, $pageWidth - $margin, 54, '#d9e2ee', 0.8);
        $text($margin, 36, 'Aptoria report standard: report-visual-standard-v1.1', 'F1', 7.5, '#64748b');
        $text(360, 36, 'Checksum: '.substr($checksum, 0, 28), 'F3', 7.2, '#64748b');

        if ($stream !== '') {
            $streams[] = $stream;
        }

        return $this->pdfFromStreams($streams);
    }

    private function officialLogoImagePdfCommands(float $x, float $topY, float $width): string
    {
        $path = public_path('assets/aptoria-ui/assets/images/logo-color-pdf.jpg');
        if (! is_file($path)) {
            return '';
        }

        $image = (string) file_get_contents($path);
        if ($image === '') {
            return '';
        }

        $size = @getimagesize($path);
        $pixelWidth = (int) ($size[0] ?? 0);
        $pixelHeight = (int) ($size[1] ?? 0);
        if ($pixelWidth <= 0 || $pixelHeight <= 0) {
            return '';
        }

        $height = $width * ($pixelHeight / $pixelWidth);
        $bottomY = $topY - $height;

        return sprintf(
            "q\n%.2F 0 0 %.2F %.2F %.2F cm\nBI\n/W %d /H %d /CS /RGB /BPC 8 /F /DCTDecode\nID\n%s\nEI\nQ\n",
            $width,
            $height,
            $x,
            $bottomY,
            $pixelWidth,
            $pixelHeight,
            $image
        );
    }

    private function officialLogoPdfCommands(float $x, float $topY, float $width): string
    {
        $path = public_path('assets/aptoria-ui/assets/images/logo-color.svg');
        if (! is_file($path)) {
            return '';
        }

        $svg = (string) file_get_contents($path);
        if (! preg_match('/viewBox="\s*([\d.]+)\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*"/i', $svg, $viewBoxMatch)) {
            return '';
        }

        $viewWidth = (float) $viewBoxMatch[3];
        if ($viewWidth <= 0) {
            return '';
        }

        $scale = $width / $viewWidth;
        $fills = [];
        if (preg_match_all('/\.(s\d+)\s*\{\s*fill:\s*(#[0-9A-Fa-f]{6})/i', $svg, $styleMatches, PREG_SET_ORDER)) {
            foreach ($styleMatches as $styleMatch) {
                $fills[$styleMatch[1]] = strtolower($styleMatch[2]);
            }
        }

        $commands = "q\n";
        if (preg_match_all('/<path\b([^>]*)>/i', $svg, $pathMatches, PREG_SET_ORDER)) {
            foreach ($pathMatches as $pathMatch) {
                $attributes = $pathMatch[1];
                if (! preg_match('/\bd="([^"]+)"/i', $attributes, $dMatch)) {
                    continue;
                }

                $fill = '#112c50';
                if (preg_match('/\bclass="([^"]+)"/i', $attributes, $classMatch)) {
                    foreach (preg_split('/\s+/', trim($classMatch[1])) ?: [] as $className) {
                        if (isset($fills[$className])) {
                            $fill = $fills[$className];
                            break;
                        }
                    }
                }

                $pathCommands = $this->svgPathToPdfCommands($dMatch[1], $x, $topY, $scale);
                if ($pathCommands === '') {
                    continue;
                }

                [$r, $g, $b] = $this->pdfRgb($fill);
                $commands .= sprintf("%.3F %.3F %.3F rg\n", $r, $g, $b);
                $commands .= $pathCommands;
                $commands .= "f\n";
            }
        }
        $commands .= "Q\n";

        return trim($commands) === 'q Q' ? '' : $commands;
    }

    private function svgPathToPdfCommands(string $d, float $offsetX, float $topY, float $scale): string
    {
        preg_match_all('/[MmLlHhVvCcSsQqTtZz]|[-+]?(?:\d*\.\d+|\d+\.?)(?:[eE][-+]?\d+)?/', $d, $matches);
        $tokens = $matches[0] ?? [];
        if ($tokens === []) {
            return '';
        }

        $isCommand = static fn ($token): bool => is_string($token) && preg_match('/^[A-Za-z]$/', $token) === 1;
        $toPdfX = static fn (float $value): float => $offsetX + ($value * $scale);
        $toPdfY = static fn (float $value): float => $topY - ($value * $scale);
        $number = static fn ($token): float => (float) $token;

        $out = '';
        $i = 0;
        $command = '';
        $currentX = 0.0;
        $currentY = 0.0;
        $startX = 0.0;
        $startY = 0.0;
        $lastCubicControlX = null;
        $lastCubicControlY = null;
        $lastQuadraticControlX = null;
        $lastQuadraticControlY = null;

        while ($i < count($tokens)) {
            if ($isCommand($tokens[$i])) {
                $command = $tokens[$i++];
            }

            if ($command === '') {
                break;
            }

            $relative = strtolower($command) === $command;
            $lower = strtolower($command);

            $hasNumber = fn (): bool => $i < count($tokens) && ! $isCommand($tokens[$i]);

            if ($lower === 'm') {
                if (! $hasNumber() || $i + 1 >= count($tokens)) {
                    break;
                }
                $x = $number($tokens[$i++]);
                $y = $number($tokens[$i++]);
                if ($relative) {
                    $x += $currentX;
                    $y += $currentY;
                }
                $currentX = $startX = $x;
                $currentY = $startY = $y;
                $out .= sprintf("%.2F %.2F m\n", $toPdfX($currentX), $toPdfY($currentY));
                $lastCubicControlX = $lastCubicControlY = $lastQuadraticControlX = $lastQuadraticControlY = null;

                while ($hasNumber() && $i + 1 < count($tokens)) {
                    $x = $number($tokens[$i++]);
                    $y = $number($tokens[$i++]);
                    if ($relative) {
                        $x += $currentX;
                        $y += $currentY;
                    }
                    $currentX = $x;
                    $currentY = $y;
                    $out .= sprintf("%.2F %.2F l\n", $toPdfX($currentX), $toPdfY($currentY));
                }
            } elseif ($lower === 'l') {
                while ($hasNumber() && $i + 1 < count($tokens)) {
                    $x = $number($tokens[$i++]);
                    $y = $number($tokens[$i++]);
                    if ($relative) {
                        $x += $currentX;
                        $y += $currentY;
                    }
                    $currentX = $x;
                    $currentY = $y;
                    $out .= sprintf("%.2F %.2F l\n", $toPdfX($currentX), $toPdfY($currentY));
                }
                $lastCubicControlX = $lastCubicControlY = $lastQuadraticControlX = $lastQuadraticControlY = null;
            } elseif ($lower === 'h') {
                while ($hasNumber()) {
                    $x = $number($tokens[$i++]);
                    if ($relative) {
                        $x += $currentX;
                    }
                    $currentX = $x;
                    $out .= sprintf("%.2F %.2F l\n", $toPdfX($currentX), $toPdfY($currentY));
                }
                $lastCubicControlX = $lastCubicControlY = $lastQuadraticControlX = $lastQuadraticControlY = null;
            } elseif ($lower === 'v') {
                while ($hasNumber()) {
                    $y = $number($tokens[$i++]);
                    if ($relative) {
                        $y += $currentY;
                    }
                    $currentY = $y;
                    $out .= sprintf("%.2F %.2F l\n", $toPdfX($currentX), $toPdfY($currentY));
                }
                $lastCubicControlX = $lastCubicControlY = $lastQuadraticControlX = $lastQuadraticControlY = null;
            } elseif ($lower === 'c') {
                while ($hasNumber() && $i + 5 < count($tokens)) {
                    $x1 = $number($tokens[$i++]);
                    $y1 = $number($tokens[$i++]);
                    $x2 = $number($tokens[$i++]);
                    $y2 = $number($tokens[$i++]);
                    $x = $number($tokens[$i++]);
                    $y = $number($tokens[$i++]);
                    if ($relative) {
                        $x1 += $currentX;
                        $y1 += $currentY;
                        $x2 += $currentX;
                        $y2 += $currentY;
                        $x += $currentX;
                        $y += $currentY;
                    }
                    $out .= sprintf("%.2F %.2F %.2F %.2F %.2F %.2F c\n", $toPdfX($x1), $toPdfY($y1), $toPdfX($x2), $toPdfY($y2), $toPdfX($x), $toPdfY($y));
                    $currentX = $x;
                    $currentY = $y;
                    $lastCubicControlX = $x2;
                    $lastCubicControlY = $y2;
                    $lastQuadraticControlX = $lastQuadraticControlY = null;
                }
            } elseif ($lower === 's') {
                while ($hasNumber() && $i + 3 < count($tokens)) {
                    $x1 = $lastCubicControlX === null ? $currentX : (2 * $currentX) - $lastCubicControlX;
                    $y1 = $lastCubicControlY === null ? $currentY : (2 * $currentY) - $lastCubicControlY;
                    $x2 = $number($tokens[$i++]);
                    $y2 = $number($tokens[$i++]);
                    $x = $number($tokens[$i++]);
                    $y = $number($tokens[$i++]);
                    if ($relative) {
                        $x2 += $currentX;
                        $y2 += $currentY;
                        $x += $currentX;
                        $y += $currentY;
                    }
                    $out .= sprintf("%.2F %.2F %.2F %.2F %.2F %.2F c\n", $toPdfX($x1), $toPdfY($y1), $toPdfX($x2), $toPdfY($y2), $toPdfX($x), $toPdfY($y));
                    $currentX = $x;
                    $currentY = $y;
                    $lastCubicControlX = $x2;
                    $lastCubicControlY = $y2;
                    $lastQuadraticControlX = $lastQuadraticControlY = null;
                }
            } elseif ($lower === 'q') {
                while ($hasNumber() && $i + 3 < count($tokens)) {
                    $qx = $number($tokens[$i++]);
                    $qy = $number($tokens[$i++]);
                    $x = $number($tokens[$i++]);
                    $y = $number($tokens[$i++]);
                    if ($relative) {
                        $qx += $currentX;
                        $qy += $currentY;
                        $x += $currentX;
                        $y += $currentY;
                    }
                    $cx1 = $currentX + (2 / 3) * ($qx - $currentX);
                    $cy1 = $currentY + (2 / 3) * ($qy - $currentY);
                    $cx2 = $x + (2 / 3) * ($qx - $x);
                    $cy2 = $y + (2 / 3) * ($qy - $y);
                    $out .= sprintf("%.2F %.2F %.2F %.2F %.2F %.2F c\n", $toPdfX($cx1), $toPdfY($cy1), $toPdfX($cx2), $toPdfY($cy2), $toPdfX($x), $toPdfY($y));
                    $currentX = $x;
                    $currentY = $y;
                    $lastQuadraticControlX = $qx;
                    $lastQuadraticControlY = $qy;
                    $lastCubicControlX = $lastCubicControlY = null;
                }
            } elseif ($lower === 't') {
                while ($hasNumber() && $i + 1 < count($tokens)) {
                    $qx = $lastQuadraticControlX === null ? $currentX : (2 * $currentX) - $lastQuadraticControlX;
                    $qy = $lastQuadraticControlY === null ? $currentY : (2 * $currentY) - $lastQuadraticControlY;
                    $x = $number($tokens[$i++]);
                    $y = $number($tokens[$i++]);
                    if ($relative) {
                        $x += $currentX;
                        $y += $currentY;
                    }
                    $cx1 = $currentX + (2 / 3) * ($qx - $currentX);
                    $cy1 = $currentY + (2 / 3) * ($qy - $currentY);
                    $cx2 = $x + (2 / 3) * ($qx - $x);
                    $cy2 = $y + (2 / 3) * ($qy - $y);
                    $out .= sprintf("%.2F %.2F %.2F %.2F %.2F %.2F c\n", $toPdfX($cx1), $toPdfY($cy1), $toPdfX($cx2), $toPdfY($cy2), $toPdfX($x), $toPdfY($y));
                    $currentX = $x;
                    $currentY = $y;
                    $lastQuadraticControlX = $qx;
                    $lastQuadraticControlY = $qy;
                    $lastCubicControlX = $lastCubicControlY = null;
                }
            } elseif ($lower === 'z') {
                $out .= "h\n";
                $currentX = $startX;
                $currentY = $startY;
                $lastCubicControlX = $lastCubicControlY = $lastQuadraticControlX = $lastQuadraticControlY = null;
            } else {
                // Unsupported command: stop parsing this path rather than producing a corrupt PDF.
                break;
            }
        }

        return $out;
    }

    private function pdfRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return [0, 0, 0];
        }

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    private function pdfWrap(string $text, int $maxChars): array
    {
        $lines = [];
        foreach (preg_split('/\R/', trim($text)) ?: [] as $raw) {
            $raw = trim($this->pdfText($raw));
            if ($raw === '') {
                $lines[] = '';
                continue;
            }
            foreach (explode("\n", wordwrap($raw, max(10, $maxChars), "\n", true)) as $line) {
                $lines[] = $line;
            }
        }

        return $lines === [] ? ['—'] : $lines;
    }

    private function pdfFromStreams(array $streams): string
    {
        if ($streams === []) {
            $streams = [''];
        }

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $fontObjectNumber = 3 + (count($streams) * 2);
        $boldFontObjectNumber = $fontObjectNumber + 1;
        $monoFontObjectNumber = $fontObjectNumber + 2;
        foreach ($streams as $index => $_) {
            $kids[] = (3 + ($index * 2)).' 0 R';
        }
        $objects[] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($streams).' >>';

        foreach ($streams as $index => $stream) {
            $pageObjectNumber = 3 + ($index * 2);
            $contentObjectNumber = $pageObjectNumber + 1;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 '.$fontObjectNumber.' 0 R /F2 '.$boldFontObjectNumber.' 0 R /F3 '.$monoFontObjectNumber.' 0 R >> >> /Contents '.$contentObjectNumber.' 0 R >>';
            $objects[] = '<< /Length '.strlen($stream).' >>' . "\nstream\n" . $stream . "endstream";
        }

        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';

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

    private function minimalPdf(string $title, string $text): string
    {
        $lines = [];
        foreach (preg_split('/\R/', $title."\n\n".$text) ?: [] as $line) {
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

    private function css(): string
    {
        return <<<'CSS'
:root {
  --text:#111827;
  --muted:#64748b;
  --line:#d9e2ee;
  --strong:#0f172a;
  --accent:#0787d7;
  --accent-2:#12c8bd;
  --soft:#f8fafc;
  --panel:#ffffff;
  --danger:#991b1b;
  --warn:#92400e;
  --ok:#166534;
}
* { box-sizing:border-box; }
html { background:#e9eef5; }
body { margin:0; font-family: Arial, Helvetica, sans-serif; color:var(--text); font-size:13px; line-height:1.5; }
.report { max-width:1120px; margin:28px auto; background:var(--panel); border:1px solid #cbd5e1; box-shadow:0 18px 46px rgba(15,23,42,.10); }
.report-header { padding:0; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#ffffff 0%,#fbfdff 100%); }
.report-header:before { content:""; display:block; height:7px; background:linear-gradient(90deg,var(--accent),var(--accent-2)); }
.header-top { display:grid; grid-template-columns:220px minmax(0,1fr) 230px; gap:26px; align-items:start; padding:28px 36px 18px; }
.brand { min-width:0; display:flex; align-items:flex-start; }
.logo { width:190px; max-width:100%; max-height:64px; object-fit:contain; object-position:left center; display:block; }
.logo-fallback { display:flex; align-items:center; gap:10px; color:var(--strong); }
.logo-fallback span { display:inline-grid; place-items:center; width:38px; height:38px; border-radius:12px; color:#fff; background:linear-gradient(135deg,var(--accent),var(--accent-2)); font-weight:700; }
.logo-fallback strong { font-size:24px; letter-spacing:-.03em; }
.title-block { min-width:0; }
.kicker { color:var(--accent); text-transform:uppercase; font-size:10px; font-weight:700; letter-spacing:.14em; margin-bottom:7px; }
h1 { margin:0; font-size:27px; line-height:1.13; letter-spacing:-.02em; color:var(--strong); font-weight:700; }
.subtitle { margin-top:9px; color:#475569; font-size:13px; max-width:620px; }
.prepared { min-width:0; text-align:right; font-size:12px; color:#475569; border-left:1px solid var(--line); padding-left:18px; }
.prepared div { margin:0 0 3px; }
.prepared-label { color:var(--muted); text-transform:uppercase; font-size:10px; letter-spacing:.08em; font-weight:700; }
.prepared-name { color:var(--strong); font-size:14px; font-weight:700; }
.meta-table { margin:0 36px 24px; width:calc(100% - 72px); border-collapse:collapse; table-layout:fixed; font-size:12px; border:1px solid var(--line); }
.meta-table th { width:148px; background:#f1f5f9; color:#334155; font-weight:700; text-align:left; border:1px solid var(--line); padding:8px 10px; }
.meta-table td { border:1px solid var(--line); padding:8px 10px; color:#111827; word-break:break-word; }
.status-pill { display:inline-flex; align-items:center; border-radius:999px; border:1px solid #f59e0b; background:#fffbeb; color:var(--warn); padding:3px 9px; font-size:11px; font-weight:700; }
.status-pill.ok { border-color:#86efac; background:#f0fdf4; color:var(--ok); }
.status-pill.danger { border-color:#fca5a5; background:#fef2f2; color:var(--danger); }
.summary-strip { display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); border-bottom:1px solid var(--line); background:#f8fafc; }
.summary-cell { padding:15px 18px; border-right:1px solid var(--line); }
.summary-cell:last-child { border-right:0; }
.summary-label { display:block; font-size:10px; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); font-weight:700; margin-bottom:5px; }
.summary-value { font-size:20px; font-weight:700; color:var(--strong); }
.summary-value.danger { color:var(--danger); }
.summary-value.warn { color:var(--warn); }
.summary-value.ok { color:var(--ok); }
.report-body { padding:30px 36px 34px; }
.notice { border:1px solid var(--line); border-left:5px solid var(--accent); padding:13px 15px; margin:0 0 25px; background:#f8fbff; }
.notice h2 { margin:0 0 5px; padding:0; border:0; font-size:15px; color:var(--strong); }
.notice p { margin:0; color:#475569; }
.report-section { margin:0 0 26px; page-break-inside:auto; }
.report-section h2 { margin:0 0 12px; padding:0 0 8px; border-bottom:1px solid var(--line); color:var(--strong); font-size:18px; font-weight:700; display:flex; gap:10px; align-items:baseline; }
.report-section h2 span { font-size:11px; color:var(--accent); font-weight:700; letter-spacing:.08em; }
.lead { font-size:15px; color:var(--strong); margin-bottom:8px; }
.mini-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; margin-top:16px; }
.mini-card { border:1px solid var(--line); background:#fbfdff; padding:12px 13px; min-height:74px; }
.mini-card span { display:block; font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; font-weight:700; margin-bottom:7px; }
.mini-card strong { display:block; color:var(--strong); font-size:15px; }
h3 { font-size:15px; margin:16px 0 8px; color:var(--strong); }
h4 { font-size:13px; margin:14px 0 7px; color:var(--strong); }
p { margin:0 0 10px; }
ul { margin:8px 0 15px; padding-left:20px; }
li { margin:3px 0; }
table { width:100%; border-collapse:collapse; margin:10px 0 16px; font-size:12px; table-layout:auto; }
th { background:#f1f5f9; color:#334155; font-weight:700; text-align:left; border:1px solid var(--line); padding:8px 9px; vertical-align:top; }
td { border:1px solid var(--line); padding:8px 9px; vertical-align:top; }
.rows-table th { width:230px; }
tr:nth-child(even) td { background:#fcfdff; }
code { font-family:Consolas, Monaco, monospace; font-size:11.5px; background:#f1f5f9; border:1px solid #e2e8f0; padding:1px 3px; word-break:break-all; }
hr { border:0; border-top:1px solid var(--line); margin:20px 0; }
pre.manifest-json { white-space:pre-wrap; word-break:break-word; background:#f8fafc; border:1px solid var(--line); padding:12px 14px; margin:10px 0 16px; font-family:Consolas, Monaco, monospace; font-size:11.5px; line-height:1.45; }
.report-content h1, .report-content h2 { font-size:15px; margin:16px 0 8px; }
.report-content h3 { font-size:15px; }
.appendix-note { margin-top:16px; padding:10px 12px; background:#f8fafc; border:1px solid var(--line); color:#475569; }
.report-footer { border-top:1px solid var(--line); padding:15px 36px 18px; background:#f8fafc; color:#475569; font-size:10.5px; line-height:1.4; }
.footer-grid { display:grid; grid-template-columns:1.4fr 1fr; gap:18px; }
.footer-title { font-weight:700; color:#111827; margin-bottom:2px; }
.footer-meta { color:#64748b; text-align:right; }
a { color:#0f78bd; text-decoration:none; }
@media(max-width:900px) {
  .report { margin:0; border:0; box-shadow:none; }
  .header-top { grid-template-columns:1fr; padding-left:20px; padding-right:20px; }
  .prepared { text-align:left; border-left:0; padding-left:0; border-top:1px solid var(--line); padding-top:14px; }
  .meta-table { margin-left:20px; margin-right:20px; width:calc(100% - 40px); }
  .report-body,.report-footer { padding-left:20px; padding-right:20px; }
  .summary-strip { grid-template-columns:1fr 1fr; }
  .summary-cell { border-bottom:1px solid var(--line); }
  .mini-grid { grid-template-columns:1fr 1fr; }
  .footer-grid { grid-template-columns:1fr; }
  .footer-meta { text-align:left; }
}
@media(max-width:560px) {
  .summary-strip,.mini-grid { grid-template-columns:1fr; }
  .meta-table,.meta-table tbody,.meta-table tr,.meta-table th,.meta-table td { display:block; width:100%; }
  .meta-table th { border-bottom:0; }
}
@media print {
  html, body { background:white; }
  .report { margin:0; border:0; box-shadow:none; }
  .report-header:before { height:4px; }
  a { color:inherit; }
  .report-section { page-break-inside:avoid; }
}
CSS;
    }
}
