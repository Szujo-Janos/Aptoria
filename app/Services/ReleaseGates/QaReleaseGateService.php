<?php

namespace App\Services\ReleaseGates;

use App\Models\QaReleaseGate;
use App\Models\QaReleaseGateItem;
use App\Models\Project;
use App\Services\ReleaseReadinessService;
use App\Services\Exports\ExportCreditService;
use App\Services\Security\SensitiveValueMasker;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QaReleaseGateService
{
    public function __construct(
        private readonly ReleaseReadinessService $readiness,
        private readonly SensitiveValueMasker $masker,
        private readonly ExportCreditService $credits,
    ) {
    }

    /** @return array<string, mixed> */
    public function evaluate(Project $project, string $profile = QaReleaseGate::PROFILE_STANDARD): array
    {
        $summary = $this->readiness->summarize($project);
        $blockers = $this->itemsFromMessages($summary['blocking_issues'] ?? [], QaReleaseGateItem::TYPE_BLOCKER, QaReleaseGateItem::SEVERITY_CRITICAL, 'release_readiness');
        $warnings = $this->itemsFromMessages($summary['warnings'] ?? [], QaReleaseGateItem::TYPE_WARNING, QaReleaseGateItem::SEVERITY_MEDIUM, 'release_readiness');

        $qaCoverage = $summary['qa_coverage'] ?? [];
        $testExecution = $summary['test_execution'] ?? [];
        $contract = $summary['latest_contract_validation'] ?? null;
        $latestScan = $summary['latest_scan'] ?? null;
        $openFindings = $summary['finding_counts'] ?? [];

        if (($qaCoverage['blocked'] ?? 0) > 0) {
            $blockers[] = $this->item(QaReleaseGateItem::TYPE_BLOCKER, QaReleaseGateItem::SEVERITY_CRITICAL, 'qa_coverage', 'qa_coverage_blocked', __('messages.release_gates.rules.qa_coverage_blocked.title'), __('messages.release_gates.rules.qa_coverage_blocked.message', ['count' => $qaCoverage['blocked']]));
        }

        if (($qaCoverage['coverage_percent'] ?? 0) < ($profile === QaReleaseGate::PROFILE_STRICT ? 90 : 80) && ($summary['endpoint_count'] ?? 0) > 0) {
            $warnings[] = $this->item(QaReleaseGateItem::TYPE_WARNING, QaReleaseGateItem::SEVERITY_MEDIUM, 'qa_coverage', 'qa_coverage_low', __('messages.release_gates.rules.qa_coverage_low.title'), __('messages.release_gates.rules.qa_coverage_low.message', ['percent' => $qaCoverage['coverage_percent'] ?? 0]));
        }

        if (($testExecution['total'] ?? 0) === 0) {
            $warnings[] = $this->item(QaReleaseGateItem::TYPE_WARNING, QaReleaseGateItem::SEVERITY_MEDIUM, 'test_execution', 'no_test_cases', __('messages.release_gates.rules.no_test_cases.title'), __('messages.release_gates.rules.no_test_cases.message'));
        }

        if (($testExecution['total'] ?? 0) > 0 && ($testExecution['execution_percent'] ?? 0) < ($profile === QaReleaseGate::PROFILE_STRICT ? 95 : 80)) {
            $warnings[] = $this->item(QaReleaseGateItem::TYPE_WARNING, QaReleaseGateItem::SEVERITY_MEDIUM, 'test_execution', 'low_test_execution', __('messages.release_gates.rules.low_test_execution.title'), __('messages.release_gates.rules.low_test_execution.message', ['percent' => $testExecution['execution_percent'] ?? 0]));
        }

        if (! $contract) {
            $warnings[] = $this->item(QaReleaseGateItem::TYPE_WARNING, QaReleaseGateItem::SEVERITY_MEDIUM, 'contract_validation', 'no_contract_validation', __('messages.release_gates.rules.no_contract_validation.title'), __('messages.release_gates.rules.no_contract_validation.message'));
        }

        if (($openFindings['open'] ?? 0) > 0 && ($openFindings['critical_open'] ?? 0) === 0 && ($openFindings['high_open'] ?? 0) === 0) {
            $warnings[] = $this->item(QaReleaseGateItem::TYPE_WARNING, QaReleaseGateItem::SEVERITY_LOW, 'findings', 'open_findings', __('messages.release_gates.rules.open_findings.title'), __('messages.release_gates.rules.open_findings.message', ['count' => $openFindings['open']]));
        }

        if ($profile === QaReleaseGate::PROFILE_STRICT && (($summary['score'] ?? 0) < 90)) {
            $blockers[] = $this->item(QaReleaseGateItem::TYPE_BLOCKER, QaReleaseGateItem::SEVERITY_HIGH, 'release_readiness', 'strict_score', __('messages.release_gates.rules.strict_score.title'), __('messages.release_gates.rules.strict_score.message', ['score' => $summary['score'] ?? 0]));
        }

        $evidence = [];
        $evidence[] = $this->item(QaReleaseGateItem::TYPE_EVIDENCE, QaReleaseGateItem::SEVERITY_INFO, 'release_readiness', 'readiness_score', __('messages.release_gates.evidence.readiness_score'), __('messages.release_gates.evidence.readiness_score_body', ['score' => $summary['score'] ?? 0, 'status' => $summary['label'] ?? 'n/a']));
        $evidence[] = $this->item(QaReleaseGateItem::TYPE_EVIDENCE, QaReleaseGateItem::SEVERITY_INFO, 'endpoint_coverage', 'endpoint_coverage', __('messages.release_gates.evidence.endpoint_coverage'), __('messages.release_gates.evidence.endpoint_coverage_body', ['percent' => $summary['coverage_percent'] ?? 0, 'covered' => $summary['coverage_count'] ?? 0, 'total' => $summary['endpoint_count'] ?? 0]));
        $evidence[] = $this->item(QaReleaseGateItem::TYPE_EVIDENCE, QaReleaseGateItem::SEVERITY_INFO, 'qa_coverage', 'qa_coverage', __('messages.release_gates.evidence.qa_coverage'), __('messages.release_gates.evidence.qa_coverage_body', ['percent' => $qaCoverage['coverage_percent'] ?? 0, 'blocked' => $qaCoverage['blocked'] ?? 0]));
        $evidence[] = $this->item(QaReleaseGateItem::TYPE_EVIDENCE, QaReleaseGateItem::SEVERITY_INFO, 'test_execution', 'test_execution', __('messages.release_gates.evidence.test_execution'), __('messages.release_gates.evidence.test_execution_body', ['percent' => $testExecution['execution_percent'] ?? 0, 'pass_rate' => $testExecution['pass_rate'] ?? 0]));
        $evidence[] = $this->item(QaReleaseGateItem::TYPE_EVIDENCE, QaReleaseGateItem::SEVERITY_INFO, 'findings', 'findings', __('messages.release_gates.evidence.findings'), __('messages.release_gates.evidence.findings_body', ['open' => $openFindings['open'] ?? 0, 'critical' => $openFindings['critical_open'] ?? 0, 'high' => $openFindings['high_open'] ?? 0]));
        $evidence[] = $this->item(QaReleaseGateItem::TYPE_EVIDENCE, QaReleaseGateItem::SEVERITY_INFO, 'scan', 'latest_scan', __('messages.release_gates.evidence.latest_scan'), $latestScan ? __('messages.release_gates.evidence.latest_scan_body', ['status' => $latestScan->status_label, 'scanned' => $latestScan->scanned_count, 'errors' => $latestScan->error_count]) : __('messages.release_gates.evidence.no_scan_body'));
        $evidence[] = $this->item(QaReleaseGateItem::TYPE_EVIDENCE, QaReleaseGateItem::SEVERITY_INFO, 'contract_validation', 'contract_validation', __('messages.release_gates.evidence.contract_validation'), $contract ? __('messages.release_gates.evidence.contract_validation_body', ['breaking' => $contract->breaking_count, 'failed' => $contract->failed_count, 'warnings' => $contract->warning_count]) : __('messages.release_gates.evidence.no_contract_body'));

        $recommendations = $this->itemsFromMessages($summary['recommended_actions'] ?? [], QaReleaseGateItem::TYPE_RECOMMENDATION, QaReleaseGateItem::SEVERITY_INFO, 'recommendation');

        $blockers = $this->uniqueItems($blockers);
        $warnings = $this->uniqueItems($warnings);
        $evidence = $this->uniqueItems($evidence);
        $recommendations = $this->uniqueItems($recommendations);

        $automatedStatus = $this->automatedStatus($summary, count($blockers), count($warnings));

        return [
            'summary' => $summary,
            'profile' => $profile,
            'automated_status' => $automatedStatus,
            'automated_label' => __('messages.release_gates.statuses.'.$automatedStatus),
            'automated_css' => $this->statusCss($automatedStatus),
            'default_decision' => $this->defaultDecision($automatedStatus),
            'blockers' => $blockers,
            'warnings' => $warnings,
            'evidence' => $evidence,
            'recommendations' => $recommendations,
            'counts' => [
                'blockers' => count($blockers),
                'warnings' => count($warnings),
                'evidence' => count($evidence),
                'recommendations' => count($recommendations),
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    public function createGate(Project $project, array $data): QaReleaseGate
    {
        $profile = (string) ($data['gate_profile'] ?? QaReleaseGate::PROFILE_STANDARD);
        $evaluation = $this->evaluate($project, $profile);
        $summary = $evaluation['summary'];
        $testExecution = $summary['test_execution'] ?? [];
        $qaCoverage = $summary['qa_coverage'] ?? [];

        $gate = $project->qaReleaseGates()->create([
            'release_name' => $data['release_name'],
            'target_environment' => $data['target_environment'] ?? null,
            'gate_profile' => $profile,
            'automated_status' => $evaluation['automated_status'],
            'final_decision' => $data['final_decision'] ?? $evaluation['default_decision'],
            'score' => $summary['score'] ?? 0,
            'grade' => $summary['grade'] ?? null,
            'endpoint_count' => $summary['endpoint_count'] ?? 0,
            'endpoint_coverage_percent' => $summary['coverage_percent'] ?? 0,
            'qa_coverage_percent' => $qaCoverage['coverage_percent'] ?? 0,
            'test_execution_percent' => $testExecution['execution_percent'] ?? 0,
            'test_pass_rate' => $testExecution['pass_rate'] ?? 0,
            'blocker_count' => $evaluation['counts']['blockers'],
            'warning_count' => $evaluation['counts']['warnings'],
            'evidence_count' => $evaluation['counts']['evidence'],
            'reviewed_by' => $data['reviewed_by'] ?? null,
            'reviewed_at' => ($data['final_decision'] ?? QaReleaseGate::DECISION_PENDING) !== QaReleaseGate::DECISION_PENDING ? now() : null,
            'decision_notes' => $data['decision_notes'] ?? null,
            'summary_json' => $this->compactSummary($summary),
        ]);

        foreach (['blockers', 'warnings', 'evidence', 'recommendations'] as $bucket) {
            foreach ($evaluation[$bucket] as $item) {
                $gate->items()->create([
                    ...$item,
                    'project_id' => $project->id,
                ]);
            }
        }

        return $gate->load(['items.endpoint', 'project']);
    }

    /** @param array<string, mixed> $data */
    public function updateDecision(QaReleaseGate $gate, array $data): QaReleaseGate
    {
        $gate->update([
            'final_decision' => $data['final_decision'],
            'reviewed_by' => $data['reviewed_by'] ?? null,
            'reviewed_at' => $data['final_decision'] !== QaReleaseGate::DECISION_PENDING ? now() : null,
            'decision_notes' => $data['decision_notes'] ?? null,
        ]);

        return $gate->refresh()->load(['items.endpoint', 'project']);
    }

    public function markdown(QaReleaseGate $gate): string
    {
        $gate->loadMissing(['project', 'blockers.endpoint', 'warnings.endpoint', 'evidence.endpoint', 'recommendations.endpoint']);
        $lines = [];
        $lines[] = '# Aptoria QA Release Gate';
        $lines[] = '';
        $lines[] = '**Project:** '.$this->md($gate->project->name);
        foreach ($this->credits->projectBrandingMarkdownLines($gate->project) as $brandingLine) {
            $lines[] = $this->mdBrandingLine($brandingLine);
        }
        if (($disclaimer = $this->credits->projectDisclaimerMarkdown($gate->project)) !== '') {
            $lines[] = '**Disclaimer:** '.$this->md($disclaimer);
        }
        $lines[] = '**Release:** '.$this->md($gate->release_name);
        $lines[] = '**Target environment:** '.$this->md($gate->target_environment ?: 'n/a');
        $lines[] = '**Generated:** '.$gate->created_at->format('Y-m-d H:i:s');
        $lines[] = '**Aptoria version:** '.config('aptoria.version');
        $lines[] = '';
        $lines[] = '## Gate Decision';
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Automated gate | '.$this->md($gate->automated_status_label).' |';
        $lines[] = '| Final decision | '.$this->md($gate->final_decision_label).' |';
        $lines[] = '| Score | '.$gate->score.' / 100 |';
        $lines[] = '| Grade | '.$this->md($gate->grade ?: 'n/a').' |';
        $lines[] = '| Blockers | '.$gate->blocker_count.' |';
        $lines[] = '| Warnings | '.$gate->warning_count.' |';
        $lines[] = '| Evidence items | '.$gate->evidence_count.' |';
        $lines[] = '| Endpoint coverage | '.$gate->endpoint_coverage_percent.'% |';
        $lines[] = '| QA coverage | '.$gate->qa_coverage_percent.'% |';
        $lines[] = '| Test execution | '.$gate->test_execution_percent.'% |';
        $lines[] = '| Test pass rate | '.$gate->test_pass_rate.'% |';
        $lines[] = '';
        if ($gate->decision_notes) {
            $lines[] = '## Reviewer Notes';
            $lines[] = '';
            $lines[] = $this->masker->maskForExport($gate->decision_notes);
            $lines[] = '';
        }
        $lines = array_merge($lines, $this->itemSection('Blocking Items', $gate->blockers));
        $lines = array_merge($lines, $this->itemSection('Warnings', $gate->warnings));
        $lines = array_merge($lines, $this->itemSection('Evidence Snapshot', $gate->evidence));
        $lines = array_merge($lines, $this->itemSection('Recommendations', $gate->recommendations));
        $lines[] = '_This gate is a saved decision snapshot. It uses stored Aptoria evidence and does not execute new HTTP requests._';
        $lines[] = '';
        $this->credits->appendMarkdownFooter($lines, 'qa_release_gate_report', $gate->project);

        return implode("\n", $lines)."\n";
    }

    /** @param array<int, string> $messages @return array<int, array<string, mixed>> */
    private function itemsFromMessages(array $messages, string $type, string $severity, string $source): array
    {
        return collect($messages)->map(fn (string $message): array => $this->item($type, $severity, $source, Str::slug($message, '_'), Str::limit($message, 120, ''), $message))->all();
    }

    /** @return array<string, mixed> */
    private function item(string $type, string $severity, string $source, string $ruleKey, string $title, ?string $message = null, ?string $recommendation = null, ?int $endpointId = null, array $metadata = []): array
    {
        return [
            'endpoint_id' => $endpointId,
            'item_type' => $type,
            'source' => $source,
            'severity' => $severity,
            'rule_key' => Str::limit($ruleKey, 120, ''),
            'title' => Str::limit($title, 240, ''),
            'message' => $message,
            'recommendation' => $recommendation,
            'metadata_json' => $metadata,
        ];
    }

    /** @param array<int, array<string, mixed>> $items @return array<int, array<string, mixed>> */
    private function uniqueItems(array $items): array
    {
        return collect($items)
            ->unique(fn (array $item): string => ($item['item_type'] ?? '').'|'.($item['source'] ?? '').'|'.($item['rule_key'] ?? '').'|'.($item['message'] ?? ''))
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $summary */
    private function automatedStatus(array $summary, int $blockerCount, int $warningCount): string
    {
        if ($blockerCount > 0 || ($summary['status'] ?? '') === ReleaseReadinessService::STATUS_FAIL || ($summary['status'] ?? '') === ReleaseReadinessService::STATUS_IDLE) {
            return QaReleaseGate::STATUS_BLOCKED;
        }

        if ($warningCount > 0 || ($summary['status'] ?? '') === ReleaseReadinessService::STATUS_WARNING) {
            return QaReleaseGate::STATUS_WARNING;
        }

        return QaReleaseGate::STATUS_PASS;
    }

    private function defaultDecision(string $status): string
    {
        return match ($status) {
            QaReleaseGate::STATUS_PASS => QaReleaseGate::DECISION_PASS,
            QaReleaseGate::STATUS_WARNING => QaReleaseGate::DECISION_CONDITIONAL_PASS,
            default => QaReleaseGate::DECISION_BLOCKED,
        };
    }

    private function statusCss(string $status): string
    {
        return match ($status) {
            QaReleaseGate::STATUS_PASS => 'success',
            QaReleaseGate::STATUS_WARNING => 'warning',
            QaReleaseGate::STATUS_BLOCKED => 'danger',
            default => 'default',
        };
    }

    /** @param array<string, mixed> $summary @return array<string, mixed> */
    private function compactSummary(array $summary): array
    {
        return [
            'status' => Arr::get($summary, 'status'),
            'label' => Arr::get($summary, 'label'),
            'score' => Arr::get($summary, 'score'),
            'grade' => Arr::get($summary, 'grade'),
            'endpoint_count' => Arr::get($summary, 'endpoint_count'),
            'coverage_percent' => Arr::get($summary, 'coverage_percent'),
            'qa_coverage' => Arr::get($summary, 'qa_coverage'),
            'test_execution' => Arr::get($summary, 'test_execution'),
            'finding_counts' => Arr::get($summary, 'finding_counts'),
        ];
    }

    /** @param Collection<int, QaReleaseGateItem> $items @return array<int, string> */
    private function itemSection(string $title, Collection $items): array
    {
        $lines = [];
        $lines[] = '## '.$title;
        $lines[] = '';
        if ($items->isEmpty()) {
            $lines[] = 'No items in this section.';
            $lines[] = '';

            return $lines;
        }
        $lines[] = '| Severity | Source | Item | Message |';
        $lines[] = '|---|---|---|---|';
        foreach ($items as $item) {
            $lines[] = '| '.$this->md($item->severity_label).' | '.$this->md($item->source).' | '.$this->md($item->title).' | '.$this->md($item->message ?: '').' |';
        }
        $lines[] = '';

        return $lines;
    }

    private function mdBrandingLine(string $line): string
    {
        if (! str_contains($line, ':** ')) {
            return $this->md($line);
        }

        [$label, $value] = explode(':** ', $line, 2);

        return $label.':** '.$this->md($value);
    }

    private function md(?string $value): string
    {
        return str_replace('|', '\\|', $this->masker->maskForExport((string) ($value ?? '')));
    }
}
