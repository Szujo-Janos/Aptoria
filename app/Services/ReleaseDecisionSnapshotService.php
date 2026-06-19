<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ReleaseDecisionSnapshot;
use App\Models\ReleaseReadinessRun;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ReleaseDecisionSnapshotService
{
    public function __construct(private readonly ReleaseReadinessService $readinessService)
    {
    }

    public function currentSummary(Project $project, ?array $evaluation = null): array
    {
        $evaluation ??= $this->readinessService->evaluate($project);
        $sourceState = $this->sourceState($project, $evaluation['metrics'] ?? []);
        $recommendedDecision = $this->decisionFromReadinessStatus((string) ($evaluation['status'] ?? 'blocked'));

        return [
            'recommended_decision' => $recommendedDecision,
            'recommended_tone' => $this->decisionTone($recommendedDecision),
            'readiness_status' => (string) ($evaluation['status'] ?? 'blocked'),
            'readiness_score' => (int) ($evaluation['score'] ?? 0),
            'readiness_grade' => (string) ($evaluation['grade'] ?? 'D'),
            'source_state' => $sourceState,
            'evidence_summary' => $this->evidenceSummary($project, $evaluation, $sourceState, $recommendedDecision),
            'markdown_preview' => $this->markdownSummary($project, $evaluation, $sourceState, $recommendedDecision, null),
        ];
    }

    public function createSnapshot(Project $project, ?User $user, string $decision, ?string $decisionNote = null): ReleaseDecisionSnapshot
    {
        $decision = in_array($decision, ReleaseDecisionSnapshot::DECISIONS, true) ? $decision : 'needs_review';
        $run = $this->readinessService->createRun($project, $user, $decisionNote);
        $evaluation = [
            'status' => $run->status,
            'score' => $run->score,
            'grade' => $run->grade,
            'blocker_count' => $run->blocker_count,
            'warning_count' => $run->warning_count,
            'check_count' => $run->check_count,
            'passed_check_count' => $run->passed_check_count,
            'metrics' => $run->metrics,
            'checks' => $run->checks,
            'summary' => $run->summary,
        ];
        $sourceState = $this->sourceState($project, $run->metrics);
        $evidenceSummary = $this->evidenceSummary($project, $evaluation, $sourceState, $decision);
        $markdown = $this->markdownSummary($project, $evaluation, $sourceState, $decision, $decisionNote);

        return $project->releaseDecisionSnapshots()->create([
            'release_readiness_run_id' => $run->id,
            'decided_by_user_id' => $user?->id,
            'decision' => $decision,
            'title' => __('messages.release_decisions.default_title', ['date' => now()->format('Y-m-d H:i')]),
            'evidence_summary_markdown' => $markdown,
            'evidence_summary_json' => $evidenceSummary,
            'readiness_metrics_json' => $run->metrics,
            'readiness_checks_json' => $run->checks,
            'source_state_json' => $sourceState,
            'decision_note' => $decisionNote,
            'decided_at' => now(),
        ]);
    }


    public function reportPreview(ReleaseDecisionSnapshot $snapshot): array
    {
        $snapshot->loadMissing(['project', 'decidedBy', 'releaseReadinessRun']);

        $summary = $snapshot->evidence_summary;
        $sourceState = $snapshot->source_state;
        $checks = $snapshot->readiness_checks;
        $metrics = $snapshot->readiness_metrics;
        $project = $snapshot->project;
        $readiness = $summary['readiness'] ?? [];
        $signals = $summary['signals'] ?? [];
        $decisionTone = $summary['decision']['tone'] ?? $snapshot->decision_tone;
        $decisionLabel = $summary['decision']['label'] ?? $snapshot->decision_label;

        $signalRows = [
            [
                'key' => 'quick_tests',
                'icon' => 'test-tube',
                'label' => __('messages.release_decisions.quick_test_state'),
                'tone' => $signals['quick_tests']['tone'] ?? 'secondary',
                'state_label' => $signals['quick_tests']['label'] ?? __('messages.release_decisions.signal_states.missing'),
                'summary' => ($sourceState['quick_tests']['passed'] ?? 0).' / '.($sourceState['quick_tests']['total'] ?? 0).' '.__('messages.release_decisions.passed_short'),
                'details' => __('messages.release_decisions.report.quick_tests_details', [
                    'failed' => $sourceState['quick_tests']['failed'] ?? 0,
                    'warning' => $sourceState['quick_tests']['warning'] ?? 0,
                ]),
            ],
            [
                'key' => 'assertions',
                'icon' => 'badge-check',
                'label' => __('messages.release_decisions.assertion_state'),
                'tone' => $signals['assertions']['tone'] ?? 'secondary',
                'state_label' => $signals['assertions']['label'] ?? __('messages.release_decisions.signal_states.missing'),
                'summary' => __('messages.release_decisions.report.scan_summary', ['scan' => $sourceState['assertions']['latest_scan_id'] ?? '—']),
                'details' => __('messages.release_decisions.report.assertion_details', [
                    'failed' => $sourceState['assertions']['failed'] ?? 0,
                    'expectations' => $sourceState['assertions']['expectation_failures'] ?? 0,
                ]),
            ],
            [
                'key' => 'batch',
                'icon' => 'layers',
                'label' => __('messages.release_decisions.batch_state'),
                'tone' => $signals['batch']['tone'] ?? 'secondary',
                'state_label' => $signals['batch']['label'] ?? __('messages.release_decisions.signal_states.missing'),
                'summary' => __('messages.release_decisions.report.batch_summary', ['batch' => $sourceState['batch']['latest_batch_id'] ?? '—']),
                'details' => __('messages.release_decisions.report.batch_details', [
                    'passed' => $sourceState['batch']['passed'] ?? 0,
                    'total' => $sourceState['batch']['total'] ?? 0,
                    'failed' => $sourceState['batch']['failed'] ?? 0,
                    'warning' => $sourceState['batch']['warning'] ?? 0,
                ]),
            ],
            [
                'key' => 'risk',
                'icon' => 'bug',
                'label' => __('messages.release_decisions.risk_state'),
                'tone' => $signals['risk']['tone'] ?? 'secondary',
                'state_label' => $signals['risk']['label'] ?? __('messages.release_decisions.signal_states.missing'),
                'summary' => __('messages.release_decisions.report.risk_summary', ['open' => $sourceState['risk']['open_findings'] ?? 0]),
                'details' => __('messages.release_decisions.report.risk_details', [
                    'critical' => $sourceState['risk']['critical_findings'] ?? 0,
                    'high' => $sourceState['risk']['high_findings'] ?? 0,
                    'missing' => $sourceState['risk']['missing_evidence'] ?? 0,
                    'retest' => $sourceState['risk']['retest_needed'] ?? 0,
                ]),
            ],
            [
                'key' => 'risk_acceptance_expiry',
                'icon' => 'calendar-clock',
                'label' => __('messages.release_decisions.risk_acceptance_expiry_state'),
                'tone' => $signals['risk_acceptance_expiry']['tone'] ?? 'secondary',
                'state_label' => $signals['risk_acceptance_expiry']['label'] ?? __('messages.release_decisions.signal_states.missing'),
                'summary' => __('messages.release_decisions.report.risk_acceptance_expiry_summary', [
                    'active' => $sourceState['risk']['active_risk_acceptances'] ?? 0,
                    'expiring' => $sourceState['risk']['expiring_soon_risk_acceptances'] ?? 0,
                    'expired' => $sourceState['risk']['expired_risk_acceptances'] ?? 0,
                ]),
                'details' => __('messages.release_decisions.report.risk_acceptance_expiry_details', [
                    'next' => $sourceState['risk']['next_risk_acceptance_expiry_at'] ?? '—',
                    'accepted' => ($sourceState['risk']['accepted_critical_findings'] ?? 0) + ($sourceState['risk']['accepted_high_findings'] ?? 0),
                ]),
            ],
            [
                'key' => 'contract_validation',
                'icon' => 'file-check-2',
                'label' => __('messages.release_decisions.contract_validation_state'),
                'tone' => $signals['contract_validation']['tone'] ?? 'secondary',
                'state_label' => $signals['contract_validation']['label'] ?? __('messages.release_decisions.signal_states.missing'),
                'summary' => __('messages.release_decisions.report.contract_validation_summary', [
                    'matched' => $sourceState['contract_validation']['matched_operations'] ?? 0,
                    'inventory' => $sourceState['contract_validation']['inventory_operations'] ?? 0,
                ]),
                'details' => __('messages.release_decisions.report.contract_validation_details', [
                    'undocumented' => $sourceState['contract_validation']['undocumented'] ?? 0,
                    'missing' => $sourceState['contract_validation']['missing_inventory'] ?? 0,
                    'blockers' => $sourceState['contract_validation']['blockers'] ?? 0,
                ]),
            ],
            [
                'key' => 'retest_closure',
                'icon' => 'shield-check',
                'label' => __('messages.release_decisions.retest_closure_state'),
                'tone' => $signals['retest_closure']['tone'] ?? 'secondary',
                'state_label' => $signals['retest_closure']['label'] ?? __('messages.release_decisions.signal_states.missing'),
                'summary' => __('messages.release_decisions.report.retest_closure_summary', ['rate' => $sourceState['retest_closure']['rate'] ?? 100]),
                'details' => __('messages.release_decisions.report.retest_closure_details', [
                    'open' => $sourceState['retest_closure']['open'] ?? 0,
                    'pending' => $sourceState['retest_closure']['pending'] ?? 0,
                    'failed' => $sourceState['retest_closure']['failed'] ?? 0,
                    'regression' => $sourceState['retest_closure']['regression_retest_open'] ?? 0,
                ]),
            ],
        ];

        $blockingChecks = collect($checks)->filter(fn ($check) => ($check['level'] ?? null) === 'blocker')->values()->all();
        $warningChecks = collect($checks)->filter(fn ($check) => ($check['level'] ?? null) === 'warning')->values()->all();
        $passedChecks = collect($checks)->filter(fn ($check) => ($check['passed'] ?? false) === true)->values()->all();

        return [
            'title' => __('messages.release_decisions.report.title'),
            'subtitle' => __('messages.release_decisions.report.subtitle'),
            'decision_label' => $decisionLabel,
            'decision_tone' => $decisionTone,
            'decision_value' => $snapshot->decision,
            'score' => (int) ($readiness['score'] ?? $snapshot->releaseReadinessRun?->score ?? 0),
            'grade' => (string) ($readiness['grade'] ?? $snapshot->releaseReadinessRun?->grade ?? 'D'),
            'status_label' => (string) ($readiness['status_label'] ?? $snapshot->releaseReadinessRun?->status_label ?? $decisionLabel),
            'headline' => $this->reportHeadline($snapshot->decision, (int) ($readiness['blockers'] ?? 0), (int) ($readiness['warnings'] ?? 0)),
            'meta' => [
                __('messages.workspace.current_project') => $project?->name ?? '—',
                __('messages.projects.base_url') => $project?->base_url ?: __('messages.common.not_available'),
                __('messages.projects.release_goal') => $project?->release_goal ?: __('messages.common.not_available'),
                __('messages.release_decisions.decided_by') => $snapshot->decidedBy?->name ?? '—',
                __('messages.release_decisions.generated_at') => $snapshot->decided_at?->format('Y-m-d H:i') ?? $snapshot->created_at?->format('Y-m-d H:i') ?? '—',
                __('messages.release_decisions.readiness_snapshot') => $snapshot->release_readiness_run_id ? '#'.$snapshot->release_readiness_run_id : '—',
            ],
            'counters' => [
                ['label' => __('messages.release_readiness.blockers'), 'value' => (int) ($readiness['blockers'] ?? 0), 'tone' => 'danger'],
                ['label' => __('messages.release_readiness.warnings'), 'value' => (int) ($readiness['warnings'] ?? 0), 'tone' => 'warning'],
                ['label' => __('messages.release_readiness.passed_checks'), 'value' => (int) ($readiness['passed_checks'] ?? count($passedChecks)).' / '.(int) ($readiness['check_count'] ?? count($checks)), 'tone' => 'success'],
                ['label' => __('messages.findings.open_findings'), 'value' => (int) ($metrics['open_findings'] ?? $sourceState['risk']['open_findings'] ?? 0), 'tone' => 'secondary'],
            ],
            'signals' => $signalRows,
            'blocking_checks' => $blockingChecks,
            'warning_checks' => $warningChecks,
            'passed_checks' => $passedChecks,
            'checks' => $checks,
            'markdown' => $this->reportMarkdown($snapshot, $signalRows, $blockingChecks, $warningChecks),
        ];
    }


    public function exportMarkdown(ReleaseDecisionSnapshot $snapshot): string
    {
        return $this->reportPreview($snapshot)['markdown'];
    }

    public function exportHtml(ReleaseDecisionSnapshot $snapshot): string
    {
        $preview = $this->reportPreview($snapshot);
        $escape = fn ($value): string => e((string) $value);
        $rows = '';
        foreach ($preview['meta'] as $label => $value) {
            $rows .= '<tr><th>'.$escape($label).'</th><td>'.$escape($value).'</td></tr>';
        }

        $counters = '';
        foreach ($preview['counters'] as $counter) {
            $counters .= '<div class="metric"><span>'.$escape($counter['label']).'</span><strong>'.$escape($counter['value']).'</strong></div>';
        }

        $signals = '';
        foreach ($preview['signals'] as $signal) {
            $signals .= '<tr><td>'.$escape($signal['label']).'</td><td>'.$escape($signal['state_label']).'</td><td>'.$escape($signal['summary']).'</td><td>'.$escape($signal['details']).'</td></tr>';
        }

        $blocking = $this->htmlCheckRows($preview['blocking_checks']);
        $warnings = $this->htmlCheckRows($preview['warning_checks']);
        $note = filled($snapshot->decision_note)
            ? '<section><h2>'.$escape(__('messages.release_decisions.decision_note')).'</h2><p>'.$escape($snapshot->decision_note).'</p></section>'
            : '';

        return '<!doctype html>
<html lang="'.app()->getLocale().'">
<head>
<meta charset="utf-8">
<title>'.$escape($preview['title']).'</title>
<style>
:root{--border:#d9dee8;--muted:#667085;--ink:#1f2937;--soft:#f7f9fc;}
*{box-sizing:border-box;}
body{margin:0;background:#f3f6fb;color:var(--ink);font-family:Calibri,Arial,sans-serif;font-size:13px;line-height:1.45;}
.page{max-width:1040px;margin:28px auto;padding:0 18px;}
.header{background:#fff;border:1px solid var(--border);border-radius:18px;padding:26px 28px;margin-bottom:16px;}
h1{margin:0 0 8px;font-weight:400;font-size:28px;}
h2{margin:0 0 12px;font-weight:400;font-size:18px;}
p{margin:0 0 10px;}.muted{color:var(--muted);}.decision{display:inline-block;border-radius:999px;background:var(--soft);border:1px solid var(--border);padding:6px 10px;margin-bottom:12px;}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px;}
.metric{background:#fff;border:1px solid var(--border);border-radius:14px;padding:16px;min-height:86px;}.metric span{display:block;color:var(--muted);text-transform:uppercase;font-size:11px;letter-spacing:.04em;}.metric strong{display:block;font-size:26px;font-weight:300;margin-top:8px;}
section{background:#fff;border:1px solid var(--border);border-radius:16px;padding:18px 20px;margin-bottom:16px;}
table{width:100%;border-collapse:collapse;table-layout:fixed;}th,td{border-bottom:1px solid var(--border);padding:9px 10px;text-align:left;vertical-align:top;word-break:break-word;}th{font-weight:400;color:var(--muted);background:#fbfcfe;}tr:last-child th,tr:last-child td{border-bottom:0;}
.footer{color:var(--muted);font-size:11px;text-align:center;margin:20px 0;}
@media(max-width:800px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));}.page{margin:12px auto;}}
</style>
</head>
<body>
<main class="page">
<header class="header">
<span class="decision">'.$escape($preview['decision_label']).'</span>
<h1>'.$escape($preview['title']).'</h1>
<p class="muted">'.$escape($preview['headline']).'</p>
</header>
<div class="grid">'.$counters.'</div>
<section><h2>'.$escape(__('messages.release_decisions.report.executive_summary')).'</h2><p>'.$escape($preview['subtitle']).'</p><p><strong>'.$escape(__('messages.release_readiness.score')).':</strong> '.$escape($preview['score']).'% / '.$escape($preview['grade']).' · '.$escape($preview['status_label']).'</p></section>
<section><h2>'.$escape(__('messages.release_decisions.report.metadata')).'</h2><table>'.$rows.'</table></section>
<section><h2>'.$escape(__('messages.release_decisions.report.evidence_signals')).'</h2><table><thead><tr><th>'.$escape(__('messages.release_decisions.report.signal')).'</th><th>'.$escape(__('messages.common.status')).'</th><th>'.$escape(__('messages.release_decisions.report.summary')).'</th><th>'.$escape(__('messages.release_decisions.report.details')).'</th></tr></thead><tbody>'.$signals.'</tbody></table></section>
<section><h2>'.$escape(__('messages.release_decisions.report.blocking_checks')).'</h2><table>'.$blocking.'</table></section>
<section><h2>'.$escape(__('messages.release_decisions.report.warning_checks')).'</h2><table>'.$warnings.'</table></section>
'.$note.'
<div class="footer">'.$escape(__('messages.release_decisions.report.export_footer')).'</div>
</main>
</body>
</html>';
    }

    public function exportPdf(ReleaseDecisionSnapshot $snapshot): string
    {
        $preview = $this->reportPreview($snapshot);
        $plain = $this->plainReportText($snapshot, $preview);

        return $this->minimalPdf($preview['title'], $plain);
    }

    private function plainReportText(ReleaseDecisionSnapshot $snapshot, array $preview): string
    {
        $lines = [];
        $lines[] = $preview['title'];
        $lines[] = str_repeat('=', mb_strlen($preview['title']));
        $lines[] = '';
        $lines[] = __('messages.release_decisions.decision').': '.$preview['decision_label'];
        $lines[] = __('messages.release_readiness.score').': '.$preview['score'].'% / '.$preview['grade'].' - '.$preview['status_label'];
        $lines[] = $preview['headline'];
        $lines[] = '';
        $lines[] = __('messages.release_decisions.report.metadata');
        foreach ($preview['meta'] as $label => $value) {
            $lines[] = '- '.$label.': '.$value;
        }
        $lines[] = '';
        $lines[] = __('messages.release_decisions.report.evidence_signals');
        foreach ($preview['signals'] as $signal) {
            $lines[] = '- '.$signal['label'].': '.$signal['state_label'].' | '.$signal['summary'].' | '.$signal['details'];
        }
        $lines[] = '';
        $lines[] = __('messages.release_decisions.report.blocking_checks');
        $lines = array_merge($lines, $this->plainCheckLines($preview['blocking_checks']));
        $lines[] = '';
        $lines[] = __('messages.release_decisions.report.warning_checks');
        $lines = array_merge($lines, $this->plainCheckLines($preview['warning_checks']));

        if (filled($snapshot->decision_note)) {
            $lines[] = '';
            $lines[] = __('messages.release_decisions.decision_note');
            $lines[] = trim((string) $snapshot->decision_note);
        }

        return implode("\n", $lines);
    }

    private function plainCheckLines(array $checks): array
    {
        if ($checks === []) {
            return ['- '.__('messages.release_decisions.report.none')];
        }

        return collect($checks)->map(fn ($check): string => '- '.($check['label'] ?? '—').' — '.($check['hint'] ?? ''))->values()->all();
    }

    private function htmlCheckRows(array $checks): string
    {
        if ($checks === []) {
            return '<tr><td>'.e(__('messages.release_decisions.report.none')).'</td></tr>';
        }

        return collect($checks)->map(fn ($check): string => '<tr><td>'.e((string) ($check['label'] ?? '—')).'</td><td>'.e((string) ($check['hint'] ?? '')).'</td></tr>')->implode('');
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

    private function sourceState(Project $project, array $metrics): array
    {
        $latestQuickRuns = Schema::hasTable('endpoint_test_runs')
            ? $project->endpointTestRuns()->with('endpoint')->latest('checked_at')->latest()->take(100)->get()->unique('endpoint_id')
            : collect();
        $latestScan = null;
        if (Schema::hasTable('scan_runs')) {
            $latestScanQuery = $project->scanRuns()->where('status', 'completed');
            if (Schema::hasTable('scan_results')) {
                $latestScanQuery->withCount(['results as failed_results_count' => fn ($query) => $query->where('status', 'failed')]);
            }
            $latestScan = $latestScanQuery->latest('completed_at')->latest()->first();
        }
        $latestBatch = Schema::hasTable('endpoint_test_batches')
            ? $project->endpointTestBatches()->latest('completed_at')->latest()->first()
            : null;

        $latestScanWarningCount = $latestScan && Schema::hasTable('scan_results')
            ? $latestScan->results()->where('status', 'warning')->count()
            : 0;
        $latestScanAssertionFailures = $latestScan && Schema::hasTable('scan_results')
            ? $latestScan->results()
                ->where(function ($query): void {
                    $query->where('expected_status_matched', false)
                        ->orWhere('expected_content_type_matched', false)
                        ->orWhere('status', 'failed');
                })
                ->count()
            : 0;

        return [
            'release_readiness' => [
                'status' => (string) ($metrics['release_readiness_status'] ?? ''),
                'score' => (int) ($metrics['release_readiness_score'] ?? 0),
            ],
            'quick_tests' => [
                'total' => $latestQuickRuns->count(),
                'passed' => $latestQuickRuns->where('state', 'passed')->count(),
                'warning' => $latestQuickRuns->whereIn('state', ['warning', 'skipped'])->count(),
                'failed' => $latestQuickRuns->where('state', 'failed')->count(),
                'latest_checked_at' => optional($latestQuickRuns->first()?->checked_at)->toDateTimeString(),
                'latest_run_ids' => $latestQuickRuns->pluck('id')->values()->all(),
            ],
            'assertions' => [
                'latest_scan_id' => $latestScan?->id,
                'latest_scan_at' => $latestScan?->completed_at?->toDateTimeString(),
                'latest_scan_status' => $latestScan?->status,
                'failed' => (int) ($latestScan?->failed_results_count ?? 0),
                'warning' => (int) $latestScanWarningCount,
                'expectation_failures' => (int) $latestScanAssertionFailures,
            ],
            'batch' => [
                'latest_batch_id' => $latestBatch?->id,
                'state' => $latestBatch?->state,
                'total' => (int) ($latestBatch?->total ?? 0),
                'passed' => (int) ($latestBatch?->passed ?? 0),
                'warning' => (int) ($latestBatch?->warning ?? 0),
                'failed' => (int) ($latestBatch?->failed ?? 0),
                'skipped' => (int) ($latestBatch?->skipped ?? 0),
                'completed_at' => $latestBatch?->completed_at?->toDateTimeString(),
            ],
            'risk' => [
                'open_findings' => (int) ($metrics['open_findings'] ?? 0),
                'critical_findings' => (int) ($metrics['critical_findings'] ?? 0),
                'high_findings' => (int) ($metrics['high_findings'] ?? 0),
                'missing_evidence' => (int) ($metrics['findings_missing_evidence'] ?? 0),
                'retest_needed' => (int) ($metrics['retest_needed'] ?? 0),
                'accepted_critical_findings' => (int) ($metrics['accepted_critical_findings'] ?? 0),
                'accepted_high_findings' => (int) ($metrics['accepted_high_findings'] ?? 0),
                'active_risk_acceptances' => (int) ($metrics['risk_acceptance_active'] ?? 0),
                'expiring_soon_risk_acceptances' => (int) ($metrics['risk_acceptance_expiring_soon'] ?? 0),
                'expired_risk_acceptances' => (int) ($metrics['risk_acceptance_expired'] ?? 0),
                'next_risk_acceptance_expiry_at' => $metrics['risk_acceptance_next_expiry_at'] ?? null,
            ],
            'contract_validation' => [
                'latest_run_id' => $metrics['contract_validation_latest_run_id'] ?? null,
                'status' => (string) ($metrics['contract_validation_status'] ?? 'missing'),
                'documented_operations' => (int) ($metrics['contract_validation_documented_operations'] ?? 0),
                'inventory_operations' => (int) ($metrics['contract_validation_inventory_operations'] ?? 0),
                'matched_operations' => (int) ($metrics['contract_validation_matched_operations'] ?? 0),
                'undocumented' => (int) ($metrics['contract_validation_undocumented'] ?? 0),
                'missing_inventory' => (int) ($metrics['contract_validation_missing_inventory'] ?? 0),
                'blockers' => (int) ($metrics['contract_validation_blockers'] ?? 0),
                'warnings' => (int) ($metrics['contract_validation_warnings'] ?? 0),
                'validated_at' => $metrics['contract_validation_validated_at'] ?? null,
            ],
            'retest_closure' => [
                'status' => (string) ($metrics['retest_closure_status'] ?? 'closed'),
                'rate' => (int) ($metrics['retest_closure_rate'] ?? 100),
                'total' => (int) ($metrics['retest_closure_total'] ?? 0),
                'open' => (int) ($metrics['retest_closure_open'] ?? 0),
                'pending' => (int) ($metrics['retest_closure_pending'] ?? 0),
                'failed' => (int) ($metrics['retest_closure_failed'] ?? 0),
                'missing_evidence' => (int) ($metrics['retest_closure_missing_evidence'] ?? 0),
                'regression_open' => (int) ($metrics['retest_closure_regression_open'] ?? 0),
                'regression_retest_open' => (int) ($metrics['retest_closure_regression_retest_open'] ?? 0),
            ],
        ];
    }

    private function evidenceSummary(Project $project, array $evaluation, array $sourceState, string $decision): array
    {
        return [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'base_url' => $project->base_url,
                'release_goal' => $project->release_goal,
            ],
            'decision' => [
                'value' => $decision,
                'label' => __('messages.release_decisions.decisions.'.$decision),
                'tone' => $this->decisionTone($decision),
            ],
            'readiness' => [
                'status' => (string) ($evaluation['status'] ?? 'blocked'),
                'status_label' => __('messages.release_readiness.statuses.'.($evaluation['status'] ?? 'blocked')),
                'score' => (int) ($evaluation['score'] ?? 0),
                'grade' => (string) ($evaluation['grade'] ?? 'D'),
                'blockers' => (int) ($evaluation['blocker_count'] ?? 0),
                'warnings' => (int) ($evaluation['warning_count'] ?? 0),
                'passed_checks' => (int) ($evaluation['passed_check_count'] ?? 0),
                'check_count' => (int) ($evaluation['check_count'] ?? 0),
            ],
            'signals' => [
                'quick_tests' => $this->signalState($sourceState['quick_tests']['failed'] > 0, $sourceState['quick_tests']['warning'] > 0, $sourceState['quick_tests']['total'] > 0),
                'assertions' => $this->signalState($sourceState['assertions']['failed'] > 0 || $sourceState['assertions']['expectation_failures'] > 0, $sourceState['assertions']['warning'] > 0, filled($sourceState['assertions']['latest_scan_id'])),
                'batch' => $this->signalState($sourceState['batch']['failed'] > 0, ($sourceState['batch']['warning'] + $sourceState['batch']['skipped']) > 0, $sourceState['batch']['total'] > 0),
                'risk' => $this->signalState($sourceState['risk']['critical_findings'] > 0 || $sourceState['risk']['expired_risk_acceptances'] > 0, $sourceState['risk']['high_findings'] > 0 || $sourceState['risk']['missing_evidence'] > 0 || $sourceState['risk']['retest_needed'] > 0 || $sourceState['risk']['active_risk_acceptances'] > 0, true),
                'risk_acceptance_expiry' => $this->signalState($sourceState['risk']['expired_risk_acceptances'] > 0, $sourceState['risk']['expiring_soon_risk_acceptances'] > 0, $sourceState['risk']['active_risk_acceptances'] > 0 || $sourceState['risk']['expired_risk_acceptances'] > 0),
                'contract_validation' => $this->signalState($sourceState['contract_validation']['blockers'] > 0, $sourceState['contract_validation']['warnings'] > 0, filled($sourceState['contract_validation']['latest_run_id'])),
                'retest_closure' => $this->signalState($sourceState['retest_closure']['failed'] > 0 || $sourceState['retest_closure']['regression_retest_open'] > 0, $sourceState['retest_closure']['pending'] > 0 || $sourceState['retest_closure']['missing_evidence'] > 0, true),
            ],
        ];
    }

    private function signalState(bool $blocked, bool $warning, bool $hasEvidence): array
    {
        if (! $hasEvidence) {
            return ['state' => 'missing', 'tone' => 'secondary', 'label' => __('messages.release_decisions.signal_states.missing')];
        }

        if ($blocked) {
            return ['state' => 'blocked', 'tone' => 'danger', 'label' => __('messages.release_decisions.signal_states.blocked')];
        }

        if ($warning) {
            return ['state' => 'needs_review', 'tone' => 'warning', 'label' => __('messages.release_decisions.signal_states.needs_review')];
        }

        return ['state' => 'ready', 'tone' => 'success', 'label' => __('messages.release_decisions.signal_states.ready')];
    }

    private function markdownSummary(Project $project, array $evaluation, array $sourceState, string $decision, ?string $decisionNote): string
    {
        $lines = [
            '# '.__('messages.release_decisions.markdown_title'),
            '',
            '- '.__('messages.workspace.current_project').': '.$project->name,
            '- '.__('messages.projects.base_url').': '.($project->base_url ?: __('messages.common.not_available')),
            '- '.__('messages.release_decisions.decision').': '.__('messages.release_decisions.decisions.'.$decision),
            '- '.__('messages.release_readiness.score').': '.(int) ($evaluation['score'] ?? 0).'% / '.(string) ($evaluation['grade'] ?? 'D'),
            '- '.__('messages.release_readiness.blockers').': '.(int) ($evaluation['blocker_count'] ?? 0),
            '- '.__('messages.release_readiness.warnings').': '.(int) ($evaluation['warning_count'] ?? 0),
            '- '.__('messages.release_decisions.generated_at').': '.now()->toDateTimeString(),
            '',
            '## '.__('messages.release_decisions.evidence_summary'),
            '- '.__('messages.release_decisions.quick_test_state').': '.$sourceState['quick_tests']['passed'].' / '.$sourceState['quick_tests']['total'].' '.__('messages.release_decisions.passed_short').', '.$sourceState['quick_tests']['failed'].' '.__('messages.release_decisions.failed_short').', '.$sourceState['quick_tests']['warning'].' '.__('messages.release_decisions.warning_short'),
            '- '.__('messages.release_decisions.assertion_state').': scan #'.($sourceState['assertions']['latest_scan_id'] ?? '—').', '.$sourceState['assertions']['expectation_failures'].' '.__('messages.release_decisions.expectation_failures_short').', '.$sourceState['assertions']['failed'].' '.__('messages.release_decisions.failed_short'),
            '- '.__('messages.release_decisions.batch_state').': batch #'.($sourceState['batch']['latest_batch_id'] ?? '—').', '.$sourceState['batch']['passed'].' / '.$sourceState['batch']['total'].' '.__('messages.release_decisions.passed_short').', '.$sourceState['batch']['failed'].' '.__('messages.release_decisions.failed_short').', '.$sourceState['batch']['warning'].' '.__('messages.release_decisions.warning_short').', '.$sourceState['batch']['skipped'].' skipped',
            '- '.__('messages.release_decisions.risk_state').': '.$sourceState['risk']['critical_findings'].' critical, '.$sourceState['risk']['high_findings'].' high, '.$sourceState['risk']['missing_evidence'].' missing evidence, '.$sourceState['risk']['retest_needed'].' retest pending, '.$sourceState['risk']['active_risk_acceptances'].' accepted risk',
            '- '.__('messages.release_decisions.risk_acceptance_expiry_state').': '.$sourceState['risk']['expiring_soon_risk_acceptances'].' expiring soon, '.$sourceState['risk']['expired_risk_acceptances'].' expired, next expiry '.($sourceState['risk']['next_risk_acceptance_expiry_at'] ?? '—'),
            '- '.__('messages.release_decisions.contract_validation_state').': '.$sourceState['contract_validation']['matched_operations'].' / '.$sourceState['contract_validation']['inventory_operations'].' matched, '.$sourceState['contract_validation']['undocumented'].' undocumented, '.$sourceState['contract_validation']['missing_inventory'].' missing inventory, '.$sourceState['contract_validation']['blockers'].' blockers',
            '- '.__('messages.release_decisions.retest_closure_state').': '.$sourceState['retest_closure']['rate'].'% closure, '.$sourceState['retest_closure']['open'].' open, '.$sourceState['retest_closure']['failed'].' failed, '.$sourceState['retest_closure']['regression_retest_open'].' regression retest open',
        ];

        if (filled($decisionNote)) {
            $lines[] = '';
            $lines[] = '## '.__('messages.release_decisions.decision_note');
            $lines[] = trim((string) $decisionNote);
        }

        return implode("\n", $lines);
    }


    private function reportHeadline(string $decision, int $blockers, int $warnings): string
    {
        if ($decision === 'ready' && $blockers === 0 && $warnings === 0) {
            return __('messages.release_decisions.report.headline_ready');
        }

        if ($decision === 'blocked' || $blockers > 0) {
            return trans_choice('messages.release_decisions.report.headline_blocked', max($blockers, 1), ['count' => $blockers]);
        }

        return trans_choice('messages.release_decisions.report.headline_review', max($warnings, 1), ['count' => $warnings]);
    }

    private function reportMarkdown(ReleaseDecisionSnapshot $snapshot, array $signalRows, array $blockingChecks, array $warningChecks): string
    {
        $summary = $snapshot->evidence_summary;
        $readiness = $summary['readiness'] ?? [];
        $project = $snapshot->project;
        $lines = [
            '# '.__('messages.release_decisions.report.markdown_title'),
            '',
            '## '.__('messages.release_decisions.report.executive_summary'),
            '- '.__('messages.workspace.current_project').': '.($project?->name ?? '—'),
            '- '.__('messages.projects.base_url').': '.($project?->base_url ?: __('messages.common.not_available')),
            '- '.__('messages.release_decisions.decision').': '.$snapshot->decision_label,
            '- '.__('messages.release_readiness.score').': '.(int) ($readiness['score'] ?? 0).'% / '.(string) ($readiness['grade'] ?? 'D'),
            '- '.__('messages.release_readiness.blockers').': '.(int) ($readiness['blockers'] ?? 0),
            '- '.__('messages.release_readiness.warnings').': '.(int) ($readiness['warnings'] ?? 0),
            '- '.__('messages.release_decisions.generated_at').': '.($snapshot->decided_at?->toDateTimeString() ?? $snapshot->created_at?->toDateTimeString() ?? now()->toDateTimeString()),
            '',
            '## '.__('messages.release_decisions.report.evidence_signals'),
        ];

        foreach ($signalRows as $signal) {
            $lines[] = '- '.$signal['label'].': '.$signal['state_label'].' — '.$signal['summary'].'; '.$signal['details'];
        }

        $lines[] = '';
        $lines[] = '## '.__('messages.release_decisions.report.blocking_checks');
        if ($blockingChecks === []) {
            $lines[] = '- '.__('messages.release_decisions.report.none');
        } else {
            foreach ($blockingChecks as $check) {
                $lines[] = '- '.($check['label'] ?? '—').' — '.($check['hint'] ?? '');
            }
        }

        $lines[] = '';
        $lines[] = '## '.__('messages.release_decisions.report.warning_checks');
        if ($warningChecks === []) {
            $lines[] = '- '.__('messages.release_decisions.report.none');
        } else {
            foreach ($warningChecks as $check) {
                $lines[] = '- '.($check['label'] ?? '—').' — '.($check['hint'] ?? '');
            }
        }

        if (filled($snapshot->decision_note)) {
            $lines[] = '';
            $lines[] = '## '.__('messages.release_decisions.decision_note');
            $lines[] = trim((string) $snapshot->decision_note);
        }

        return implode("\n", $lines);
    }

    private function decisionFromReadinessStatus(string $status): string
    {
        return match ($status) {
            'ready' => 'ready',
            'blocked' => 'blocked',
            default => 'needs_review',
        };
    }

    private function decisionTone(string $decision): string
    {
        return match ($decision) {
            'ready' => 'success',
            'blocked' => 'danger',
            default => 'warning',
        };
    }
}
