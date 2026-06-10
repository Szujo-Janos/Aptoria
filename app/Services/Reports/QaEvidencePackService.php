<?php

namespace App\Services\Reports;

use App\Models\CompareRun;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\Snapshot;
use App\Services\AssertionEvaluationService;
use App\Services\Exports\ExportCreditService;
use App\Services\Auth\AuthProfileRuntimeService;
use App\Services\RegressionEvaluationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class QaEvidencePackService
{
    public const DECISION_PASS = 'pass';
    public const DECISION_PASS_WITH_WARNING = 'pass_with_warning';
    public const DECISION_FAIL = 'fail';
    public const DECISION_BLOCKED = 'blocked';

    public const DECISIONS = [
        self::DECISION_PASS,
        self::DECISION_PASS_WITH_WARNING,
        self::DECISION_FAIL,
        self::DECISION_BLOCKED,
    ];

    public function __construct(
        private readonly AssertionEvaluationService $assertions,
        private readonly RegressionEvaluationService $regressions,
        private readonly ReportExportService $exports,
        private readonly AuthProfileRuntimeService $authRuntime,
        private readonly ExportCreditService $credits,
    ) {
    }

    public function defaultSelection(Project $project): array
    {
        $snapshots = $project->snapshots()
            ->with('environment')
            ->latest()
            ->limit(50)
            ->get();

        $baseline = $this->findSnapshotByWords($snapshots, ['baseline'])
            ?: $snapshots->sortBy('created_at')->first();

        $validation = $this->findSnapshotByWords($snapshots, ['corrected', 'validation'])
            ?: $this->findSnapshotByWords($snapshots, ['assertion', 'validation'])
            ?: $this->findSnapshotByWords($snapshots, ['validation']);

        $recovery = $this->findSnapshotByWords($snapshots, ['recovery'])
            ?: $this->findSnapshotByWords($snapshots, ['post-negative']);

        $negative = $this->findSnapshotByWords($snapshots, ['negative'], ['recovery'])
            ?: $this->findSnapshotByWords($snapshots, ['control'], ['recovery']);

        $compareIds = $project->compareRuns()
            ->latest()
            ->limit(10)
            ->pluck('id')
            ->all();

        return [
            'baseline_snapshot_id' => $baseline?->id,
            'validation_snapshot_id' => $validation?->id,
            'negative_snapshot_id' => $negative?->id,
            'recovery_snapshot_id' => $recovery?->id,
            'compare_run_ids' => $compareIds,
            'final_decision' => null,
            'decision_reason' => null,
        ];
    }

    public function selectionFromInput(Project $project, array $input): array
    {
        $defaults = $this->defaultSelection($project);

        $selection = [
            'baseline_snapshot_id' => $this->snapshotIdOrNull($project, $input['baseline_snapshot_id'] ?? $defaults['baseline_snapshot_id']),
            'validation_snapshot_id' => $this->snapshotIdOrNull($project, $input['validation_snapshot_id'] ?? $defaults['validation_snapshot_id']),
            'negative_snapshot_id' => $this->snapshotIdOrNull($project, $input['negative_snapshot_id'] ?? $defaults['negative_snapshot_id']),
            'recovery_snapshot_id' => $this->snapshotIdOrNull($project, $input['recovery_snapshot_id'] ?? $defaults['recovery_snapshot_id']),
            'compare_run_ids' => $this->compareIds($project, $input['compare_run_ids'] ?? $defaults['compare_run_ids']),
            'final_decision' => $this->validDecision($input['final_decision'] ?? null),
            'decision_reason' => trim((string) ($input['decision_reason'] ?? '')) ?: null,
        ];

        return $selection;
    }

    public function buildContext(Project $project, array $selection): array
    {
        $project->loadMissing(['environments', 'authProfiles']);
        $project->loadCount(['endpoints', 'scanRuns', 'snapshots', 'compareRuns', 'findings']);

        $snapshotRoles = [
            'baseline' => $this->snapshotById($project, $selection['baseline_snapshot_id'] ?? null),
            'validation' => $this->snapshotById($project, $selection['validation_snapshot_id'] ?? null),
            'negative_control' => $this->snapshotById($project, $selection['negative_snapshot_id'] ?? null),
            'recovery' => $this->snapshotById($project, $selection['recovery_snapshot_id'] ?? null),
        ];

        $snapshotSummaries = [];
        foreach ($snapshotRoles as $role => $snapshot) {
            if ($snapshot instanceof Snapshot) {
                $snapshotSummaries[$role] = $this->snapshotSummary($snapshot, $role);
            }
        }

        $compareRuns = $project->compareRuns()
            ->with(['snapshotA', 'snapshotB', 'items'])
            ->whereIn('id', $selection['compare_run_ids'] ?? [])
            ->latest()
            ->get();

        $compareSummaries = $compareRuns
            ->map(fn (CompareRun $compareRun): array => $this->compareSummary($compareRun))
            ->values()
            ->all();

        $findingSummary = $this->findingSummary($project);

        $autoDecision = $this->autoDecision($snapshotSummaries, $compareSummaries, $findingSummary);
        $finalDecision = $selection['final_decision'] ?: $autoDecision['decision'];

        return [
            'project' => $project,
            'snapshot_roles' => $snapshotRoles,
            'snapshot_summaries' => $snapshotSummaries,
            'compare_runs' => $compareRuns,
            'compare_summaries' => $compareSummaries,
            'finding_summary' => $findingSummary,
            'selection' => $selection,
            'final_decision' => $finalDecision,
            'final_decision_label' => $this->decisionLabel($finalDecision),
            'decision_reason' => $selection['decision_reason'] ?: $autoDecision['reason'],
        ];
    }

    public function notesMarkdown(Project $project, array $selection): string
    {
        $context = $this->buildContext($project, $selection);
        /** @var Project $project */
        $project = $context['project'];

        $lines = [];
        $lines[] = '# QA Evidence Notes';
        $lines[] = '';
        $lines[] = '**Project:** '.$this->md($project->name);
        $lines[] = '**Base URL:** '.$this->md($this->authRuntime->maskForExport($project->display_base_url));
        foreach ($this->credits->projectBrandingMarkdownLines($project) as $brandingLine) {
            $lines[] = $this->mdBrandingLine($brandingLine);
        }
        if (($disclaimer = $this->credits->projectDisclaimerMarkdown($project)) !== '') {
            $lines[] = '**Disclaimer:** '.$this->md($disclaimer);
        }
        $lines[] = '**Generated:** '.now()->format('Y-m-d H:i:s');
        $lines[] = '**Aptoria version:** '.config('aptoria.version');
        $lines[] = '**Final QA decision:** '.$this->md($context['final_decision_label']);
        $lines[] = '**Decision reason:** '.$this->md($context['decision_reason']);
        $lines[] = '';

        $lines[] = '## Project Scope';
        $lines[] = '';
        $lines[] = '| Metric | Value |';
        $lines[] = '|---|---:|';
        $lines[] = '| Endpoints | '.$project->endpoints_count.' |';
        $lines[] = '| Environments | '.$project->environments->count().' |';
        $lines[] = '| Auth profiles | '.$project->authProfiles->count().' |';
        $lines[] = '| Scan runs | '.$project->scan_runs_count.' |';
        $lines[] = '| Snapshots | '.$project->snapshots_count.' |';
        $lines[] = '| Compare runs | '.$project->compare_runs_count.' |';
        $lines[] = '| Findings | '.$project->findings_count.' |';
        $lines[] = '';

        $lines[] = '## Tested Endpoints';
        $lines[] = '';
        $endpoints = $project->endpoints()
            ->orderBy('method')
            ->orderBy('path')
            ->get();
        if ($endpoints->isEmpty()) {
            $lines[] = 'No endpoints are registered for this project.';
        } else {
            $lines[] = '| Method | Path | Auth | Active | Excluded |';
            $lines[] = '|---|---|---|---|---|';
            foreach ($endpoints as $endpoint) {
                $lines[] = '| '.$this->md($endpoint->method).' | '.$this->md($endpoint->path).' | '.$this->md($endpoint->auth_required ? 'Yes' : 'No').' | '.$this->md($endpoint->is_active ? 'Yes' : 'No').' | '.$this->md($endpoint->excluded_from_scan ? 'Yes' : 'No').' |';
            }
        }
        $lines[] = '';

        $lines[] = '## Evidence Snapshot Roles';
        $lines[] = '';
        $lines[] = '| Role | Snapshot | Created | Endpoints | Assertion PASS | Assertion WARNING | Assertion FAIL | QA use |';
        $lines[] = '|---|---|---|---:|---:|---:|---:|---|';
        foreach (['baseline', 'validation', 'negative_control', 'recovery'] as $role) {
            $snapshot = $context['snapshot_roles'][$role] ?? null;
            $summary = $context['snapshot_summaries'][$role] ?? null;
            if (! $snapshot instanceof Snapshot || ! is_array($summary)) {
                $lines[] = '| '.$this->md($this->roleLabel($role)).' | n/a | n/a | 0 | 0 | 0 | 0 | Not selected |';
                continue;
            }
            $lines[] = '| '.$this->md($this->roleLabel($role)).' | '.$this->md('#'.$snapshot->id.' '.$snapshot->name).' | '.$this->md($this->dateValue($snapshot->created_at)).' | '.$summary['endpoint_count'].' | '.$summary['assertions']['pass'].' | '.$summary['assertions']['warning'].' | '.$summary['assertions']['fail'].' | '.$this->md($summary['qa_use']).' |';
        }
        $lines[] = '';

        foreach ($context['snapshot_summaries'] as $role => $summary) {
            $lines[] = '### '.$this->roleLabel($role);
            $lines[] = '';
            $lines[] = '**Snapshot:** '.$this->md('#'.$summary['id'].' '.$summary['name']);
            $lines[] = '**QA use:** '.$this->md($summary['qa_use']);
            $lines[] = '';
            $lines[] = '| Metric | Value |';
            $lines[] = '|---|---:|';
            $lines[] = '| Endpoints | '.$summary['endpoint_count'].' |';
            $lines[] = '| HTTP 4xx | '.$summary['http_4xx'].' |';
            $lines[] = '| HTTP 5xx | '.$summary['http_5xx'].' |';
            $lines[] = '| Assertion PASS | '.$summary['assertions']['pass'].' |';
            $lines[] = '| Assertion WARNING | '.$summary['assertions']['warning'].' |';
            $lines[] = '| Assertion FAIL | '.$summary['assertions']['fail'].' |';
            $lines[] = '';
            if ($summary['failed_rules'] !== []) {
                $lines[] = '**Failed rules:**';
                $lines[] = '';
                foreach ($summary['failed_rules'] as $failedRule) {
                    $lines[] = '- '.$this->md($failedRule);
                }
                $lines[] = '';
            }
        }

        $lines[] = '## Snapshot Compare Evidence';
        $lines[] = '';
        if ($context['compare_runs']->isEmpty()) {
            $lines[] = 'No compare runs were selected.';
        } else {
            $lines[] = '| Compare | Baseline | Target | Changes | Critical | High | Regression | Warnings |';
            $lines[] = '|---|---|---|---:|---:|---:|---:|---:|';
            foreach ($context['compare_summaries'] as $summary) {
                $lines[] = '| #'.$summary['id'].' | '.$this->md($summary['baseline']).' | '.$this->md($summary['target']).' | '.$summary['total_changes'].' | '.$summary['critical_count'].' | '.$summary['high_count'].' | '.$summary['regression_detected_count'].' | '.$summary['regression_warning_count'].' |';
            }
        }
        $lines[] = '';

        $lines[] = '## Findings Summary';
        $lines[] = '';
        $findingSummary = $context['finding_summary'];
        $lines[] = '| Metric | Count |';
        $lines[] = '|---|---:|';
        $lines[] = '| Total findings | '.$findingSummary['total'].' |';
        $lines[] = '| Open findings | '.$findingSummary['open'].' |';
        $lines[] = '| Critical open findings | '.$findingSummary['critical_open'].' |';
        $lines[] = '| High open findings | '.$findingSummary['high_open'].' |';
        $lines[] = '';

        $lines[] = '## Final QA Decision';
        $lines[] = '';
        $lines[] = '**Status:** '.$this->md($context['final_decision_label']);
        $lines[] = '';
        $lines[] = '**Reason:** '.$this->md($context['decision_reason']);
        $lines[] = '';
        $lines[] = '## Evidence Handling Rules';
        $lines[] = '';
        $lines[] = '- Baseline and validation snapshots may be used for normal regression comparison.';
        $lines[] = '- Negative control snapshots are evidence-only and must not be used as release baselines.';
        $lines[] = '- Recovery snapshots confirm that intentionally broken assertions were restored and the project returned to a clean state.';
        $lines[] = '- Response-time-only warnings can be accepted when assertions, HTTP status and critical/high regression counts remain clean.';
        $lines[] = '- This evidence pack is generated from stored Aptoria records and does not execute new requests.';
        $lines[] = '';
        $this->credits->appendMarkdownFooter($lines, 'qa_evidence_notes', $project);

        return implode("\n", $lines)."\n";
    }

    public function summaryJson(Project $project, array $selection): string
    {
        $context = $this->buildContext($project, $selection);
        /** @var Project $project */
        $project = $context['project'];

        $payload = [
            'exported_by' => 'Aptoria',
            'exported_at' => now()->toIso8601String(),
            'version' => config('aptoria.version'),
            'generated_by' => $this->credits->metadata('qa_evidence_summary_json', $project),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'base_url' => $this->authRuntime->maskForExport($project->base_url),
            ],
            'selection' => $selection,
            'final_decision' => $context['final_decision'],
            'final_decision_label' => $context['final_decision_label'],
            'decision_reason' => $context['decision_reason'],
            'snapshots' => $context['snapshot_summaries'],
            'compares' => $context['compare_summaries'],
            'findings' => $context['finding_summary'],
        ];

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    }

    public function buildZip(Project $project, array $selection): string
    {
        $context = $this->buildContext($project, $selection);
        $tmp = tempnam(sys_get_temp_dir(), 'aptoria-evidence-');
        if ($tmp === false) {
            throw new RuntimeException('Could not allocate a temporary ZIP file.');
        }
        @unlink($tmp);
        $zipPath = $tmp.'.zip';

        $files = [
            'qa-notes.md' => $this->notesMarkdown($project, $selection),
            'summary.json' => $this->summaryJson($project, $selection),
            'findings/open-findings.md' => $this->openFindingsMarkdown($project),
            'APTORIA_CREDITS.txt' => $this->credits->textFile('qa_evidence_pack_zip', $project),
        ];

        foreach ($context['snapshot_roles'] as $role => $snapshot) {
            if (! $snapshot instanceof Snapshot) {
                continue;
            }
            $files['snapshots/'.$role.'-snapshot-'.$snapshot->id.'-'.$this->slug($snapshot->name).'.json'] = $this->exports->snapshotJson($snapshot);
        }

        foreach ($context['compare_runs'] as $compareRun) {
            $files['compares/compare-'.$compareRun->id.'-'.$this->slug(($compareRun->snapshotA?->name ?: 'baseline').'-vs-'.($compareRun->snapshotB?->name ?: 'target')).'.md'] = $this->exports->compareMarkdown($compareRun);
        }

        $this->writeZipFile($zipPath, $files);

        return $zipPath;
    }


    /**
     * Writes a ZIP archive from string payloads.
     *
     * XAMPP installations often ship with the PHP zip extension disabled. When
     * ZipArchive is unavailable, this method falls back to a small standards-
     * compliant stored ZIP writer so Evidence Pack exports still download on
     * Windows/XAMPP without php.ini changes.
     *
     * @param array<string, string> $files
     */
    private function writeZipFile(string $zipPath, array $files): void
    {
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($opened !== true) {
                throw new RuntimeException('Could not create QA Evidence Pack ZIP.');
            }

            foreach ($files as $name => $contents) {
                $zip->addFromString($name, $contents);
            }

            if (! $zip->close()) {
                throw new RuntimeException('Could not finalize QA Evidence Pack ZIP.');
            }

            return;
        }

        $binary = $this->buildPortableStoredZip($files);
        if (file_put_contents($zipPath, $binary) === false) {
            throw new RuntimeException('Could not write QA Evidence Pack ZIP file.');
        }
    }

    /**
     * Builds an uncompressed ZIP archive without requiring ext-zip.
     *
     * @param array<string, string> $files
     */
    private function buildPortableStoredZip(array $files): string
    {
        $local = '';
        $central = '';
        $entries = 0;
        [$dosTime, $dosDate] = $this->dosTimestampParts(time());

        foreach ($files as $name => $contents) {
            $name = str_replace('\\', '/', trim($name, '/'));
            if ($name === '') {
                continue;
            }

            $offset = strlen($local);
            $size = strlen($contents);
            $crc = (int) sprintf('%u', crc32($contents));
            $nameLength = strlen($name);
            $flags = 0x0800; // UTF-8 file names.
            $method = 0; // Stored, no compression.

            $local .= pack('VvvvvvVVVvv', 0x04034b50, 20, $flags, $method, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0);
            $local .= $name.$contents;

            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, $flags, $method, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset);
            $central .= $name;
            $entries++;
        }

        $centralOffset = strlen($local);
        $centralSize = strlen($central);

        return $local.$central.pack('VvvvvVVv', 0x06054b50, 0, 0, $entries, $entries, $centralSize, $centralOffset, 0);
    }

    /**
     * @return array{0:int,1:int}
     */
    private function dosTimestampParts(int $timestamp): array
    {
        $date = getdate($timestamp);
        $year = max(1980, (int) $date['year']);

        $dosTime = ((int) $date['hours'] << 11)
            | ((int) $date['minutes'] << 5)
            | ((int) floor(((int) $date['seconds']) / 2));

        $dosDate = (($year - 1980) << 9)
            | ((int) $date['mon'] << 5)
            | (int) $date['mday'];

        return [$dosTime, $dosDate];
    }

    public function filename(Project $project, string $extension): string
    {
        return Str::slug($project->name ?: 'aptoria').'-qa-evidence-pack-'.now()->format('Ymd-His').'.'.$extension;
    }

    public function decisionLabel(string $decision): string
    {
        return match ($decision) {
            self::DECISION_PASS => 'PASS',
            self::DECISION_PASS_WITH_WARNING => 'PASS WITH WARNING',
            self::DECISION_FAIL => 'FAIL',
            self::DECISION_BLOCKED => 'BLOCKED',
            default => strtoupper($decision),
        };
    }

    private function autoDecision(array $snapshotSummaries, array $compareSummaries, array $findingSummary): array
    {
        foreach ($snapshotSummaries as $role => $summary) {
            if ($role === 'negative_control') {
                continue;
            }
            if (($summary['assertions']['fail'] ?? 0) > 0 || ($summary['http_5xx'] ?? 0) > 0) {
                return [
                    'decision' => self::DECISION_FAIL,
                    'reason' => 'One or more non-negative evidence snapshots contain failed assertions or 5xx responses.',
                ];
            }
        }

        foreach ($compareSummaries as $summary) {
            if (($summary['regression_detected_count'] ?? 0) > 0 || ($summary['critical_count'] ?? 0) > 0 || ($summary['high_count'] ?? 0) > 0) {
                return [
                    'decision' => self::DECISION_FAIL,
                    'reason' => 'Critical/high compare changes or detected regressions require investigation.',
                ];
            }
        }

        if (($findingSummary['critical_open'] ?? 0) > 0 || ($findingSummary['high_open'] ?? 0) > 0) {
            return [
                'decision' => self::DECISION_FAIL,
                'reason' => 'Critical/high open findings are still active.',
            ];
        }

        foreach ($snapshotSummaries as $role => $summary) {
            if ($role !== 'negative_control' && ($summary['assertions']['warning'] ?? 0) > 0) {
                return [
                    'decision' => self::DECISION_PASS_WITH_WARNING,
                    'reason' => 'Functional checks are clean, but at least one non-blocking assertion warning is present.',
                ];
            }
        }

        foreach ($compareSummaries as $summary) {
            if (($summary['regression_warning_count'] ?? 0) > 0) {
                return [
                    'decision' => self::DECISION_PASS_WITH_WARNING,
                    'reason' => 'No blocking regression was detected, but at least one non-blocking regression warning is present.',
                ];
            }
        }

        return [
            'decision' => self::DECISION_PASS,
            'reason' => 'All selected functional evidence is clean and no blocking regression was detected.',
        ];
    }

    private function snapshotSummary(Snapshot $snapshot, string $role): array
    {
        $snapshot->loadMissing(['environment', 'items.endpoint']);

        $assertions = [
            AssertionEvaluationService::STATUS_PASS => 0,
            AssertionEvaluationService::STATUS_WARNING => 0,
            AssertionEvaluationService::STATUS_FAIL => 0,
            AssertionEvaluationService::STATUS_NOT_CONFIGURED => 0,
        ];
        $failedRules = [];
        $warningRules = [];
        $http4xx = 0;
        $http5xx = 0;

        foreach ($snapshot->items as $item) {
            $statusCode = (int) ($item->status_code ?? 0);
            if ($statusCode >= 400 && $statusCode < 500) {
                $http4xx++;
            }
            if ($statusCode >= 500) {
                $http5xx++;
            }

            if (! $item->endpoint) {
                $assertions[AssertionEvaluationService::STATUS_NOT_CONFIGURED]++;
                continue;
            }

            $evaluation = $this->assertions->evaluate($item->endpoint, null, $item);
            $status = $evaluation['status'] ?? AssertionEvaluationService::STATUS_NOT_CONFIGURED;
            $assertions[$status] = ($assertions[$status] ?? 0) + 1;

            foreach ($evaluation['failed_rules'] ?? [] as $rule) {
                $failedRules[] = ($item->method.' '.$item->path).' — '.($rule['message'] ?? $rule['rule_label'] ?? 'Failed rule');
            }
            foreach ($evaluation['warning_rules'] ?? [] as $rule) {
                $warningRules[] = ($item->method.' '.$item->path).' — '.($rule['message'] ?? $rule['rule_label'] ?? 'Warning rule');
            }
        }

        return [
            'id' => $snapshot->id,
            'name' => $snapshot->name,
            'role' => $role,
            'created_at' => $snapshot->created_at?->toIso8601String(),
            'environment' => $snapshot->environment?->name,
            'endpoint_count' => $snapshot->endpoint_count ?: $snapshot->items->count(),
            'http_4xx' => $http4xx,
            'http_5xx' => $http5xx,
            'assertions' => [
                'pass' => $assertions[AssertionEvaluationService::STATUS_PASS] ?? 0,
                'warning' => $assertions[AssertionEvaluationService::STATUS_WARNING] ?? 0,
                'fail' => $assertions[AssertionEvaluationService::STATUS_FAIL] ?? 0,
                'not_configured' => $assertions[AssertionEvaluationService::STATUS_NOT_CONFIGURED] ?? 0,
            ],
            'failed_rules' => $failedRules,
            'warning_rules' => $warningRules,
            'qa_use' => $role === 'negative_control'
                ? 'Evidence only. Do not use as baseline.'
                : 'Eligible QA evidence for baseline/validation/recovery documentation.',
        ];
    }

    private function compareSummary(CompareRun $compareRun): array
    {
        $summary = $compareRun->summary_json ?: [];
        $regression = $this->regressions->evaluateCompare($compareRun);

        return [
            'id' => $compareRun->id,
            'created_at' => $compareRun->created_at?->toIso8601String(),
            'baseline' => $compareRun->snapshotA?->name ?: 'n/a',
            'target' => $compareRun->snapshotB?->name ?: 'n/a',
            'total_changes' => (int) ($summary['total_changes'] ?? $compareRun->items->count()),
            'new_count' => (int) ($summary['new_count'] ?? 0),
            'removed_count' => (int) ($summary['removed_count'] ?? 0),
            'changed_count' => (int) ($summary['changed_count'] ?? 0),
            'critical_count' => (int) ($summary['critical_count'] ?? 0),
            'high_count' => (int) ($summary['high_count'] ?? 0),
            'regression_label' => $regression['label'] ?? 'No regression',
            'regression_detected_count' => (int) ($regression['detected_count'] ?? 0),
            'regression_warning_count' => (int) ($regression['warning_count'] ?? 0),
            'recovered_count' => (int) ($regression['recovered_count'] ?? 0),
            'improved_count' => (int) ($regression['improved_count'] ?? 0),
        ];
    }

    private function findingSummary(Project $project): array
    {
        $open = $project->findings()
            ->whereIn('status', Finding::OPEN_STATUSES)
            ->get();

        return [
            'total' => $project->findings()->count(),
            'open' => $open->count(),
            'critical_open' => $open->where('severity', Finding::SEVERITY_CRITICAL)->count(),
            'high_open' => $open->where('severity', Finding::SEVERITY_HIGH)->count(),
        ];
    }

    private function openFindingsMarkdown(Project $project): string
    {
        $findings = $project->findings()
            ->with(['endpoint', 'evidence'])
            ->whereIn('status', Finding::OPEN_STATUSES)
            ->orderBy('severity')
            ->latest('detected_at')
            ->get();

        $lines = [];
        $lines[] = '# Open Findings';
        $lines[] = '';
        $lines[] = '**Project:** '.$this->md($project->name);
        $lines[] = '**Generated:** '.now()->format('Y-m-d H:i:s');
        $lines[] = '';

        if ($findings->isEmpty()) {
            $lines[] = 'No open findings.';
            $lines[] = '';
            $this->credits->appendMarkdownFooter($lines, 'open_findings_report', $project);
            return implode("\n", $lines)."\n";
        }

        $lines[] = '| Severity | Status | Source | Endpoint | Evidence | Attachments | Title |';
        $lines[] = '|---|---|---|---|---:|---:|---|';
        foreach ($findings as $finding) {
            $endpoint = $finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path : 'n/a';
            $attachmentCount = $finding->evidence->filter(fn ($evidence): bool => $evidence->has_attachment)->count();
            $lines[] = '| '.$this->md($finding->severity).' | '.$this->md($finding->status).' | '.$this->md($finding->source).' | '.$this->md($endpoint).' | '.$finding->evidence->count().' | '.$attachmentCount.' | '.$this->md($finding->title).' |';
        }

        $lines[] = '';
        $this->credits->appendMarkdownFooter($lines, 'open_findings_report', $project);

        return implode("\n", $lines)."\n";
    }

    private function findSnapshotByWords(Collection $snapshots, array $words, array $excludedWords = []): ?Snapshot
    {
        return $snapshots->first(function (Snapshot $snapshot) use ($words, $excludedWords): bool {
            $text = Str::lower($snapshot->name.' '.$snapshot->description);

            foreach ($excludedWords as $word) {
                if (str_contains($text, Str::lower($word))) {
                    return false;
                }
            }

            foreach ($words as $word) {
                if (! str_contains($text, Str::lower($word))) {
                    return false;
                }
            }

            return true;
        });
    }

    private function snapshotById(Project $project, mixed $id): ?Snapshot
    {
        if (! $id) {
            return null;
        }

        return $project->snapshots()
            ->with(['environment', 'items.endpoint'])
            ->whereKey((int) $id)
            ->first();
    }

    private function snapshotIdOrNull(Project $project, mixed $id): ?int
    {
        if (! $id) {
            return null;
        }

        return $project->snapshots()->whereKey((int) $id)->exists() ? (int) $id : null;
    }

    private function compareIds(Project $project, mixed $ids): array
    {
        $ids = is_array($ids) ? $ids : explode(',', (string) $ids);
        $ids = collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return $project->compareRuns()
            ->whereIn('id', $ids->all())
            ->pluck('id')
            ->all();
    }

    private function validDecision(mixed $decision): ?string
    {
        $decision = (string) $decision;

        return in_array($decision, self::DECISIONS, true) ? $decision : null;
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'baseline' => 'Baseline snapshot',
            'validation' => 'Assertion validation snapshot',
            'negative_control' => 'Negative assertion control',
            'recovery' => 'Post-negative recovery snapshot',
            default => Str::headline($role),
        };
    }

    private function slug(string $value): string
    {
        return Str::slug(Str::limit($value, 80, '')) ?: 'evidence';
    }

    private function dateValue(mixed $date): string
    {
        return $date ? $date->format('Y-m-d H:i:s') : 'n/a';
    }

    private function mdBrandingLine(string $line): string
    {
        if (! str_contains($line, ':** ')) {
            return $this->md($line);
        }

        [$label, $value] = explode(':** ', $line, 2);

        return $label.':** '.$this->md($value);
    }

    private function md(mixed $value): string
    {
        $text = trim((string) $value);
        $text = str_replace(["\r", "\n", '|'], [' ', ' ', '\\|'], $text);

        return $text === '' ? 'n/a' : $text;
    }
}
