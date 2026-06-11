<?php

namespace App\Services\Contracts;

use App\Models\ContractValidationResult;
use App\Models\ContractValidationRun;
use App\Models\Project;
use Illuminate\Support\Collection;

class ContractRealityService
{
    /** @return array<string, mixed> */
    public function summarize(Project $project, ?ContractValidationRun $run = null): array
    {
        $run ??= $project->contractValidationRuns()
            ->with(['scanRun.environment', 'results.endpoint', 'results.scanResult'])
            ->latest()
            ->first();

        if (! $run) {
            return [
                'run' => null,
                'summary' => $this->emptySummary(),
                'rows' => collect(),
                'blockers' => collect(),
                'warnings' => collect(),
                'status' => 'missing',
                'label' => __('messages.contract_reality.statuses.missing'),
                'css' => 'default',
            ];
        }

        $run->loadMissing(['scanRun.environment', 'results.endpoint', 'results.scanResult']);
        $rows = $run->results
            ->map(fn (ContractValidationResult $result): array => $this->row($result))
            ->values();

        $summary = $this->emptySummary();
        $summary['total'] = $rows->count();
        $summary['matches_contract'] = $rows->where('reality_type', 'matches_contract')->count();
        $summary['contract_drift'] = $rows->where('reality_type', 'contract_drift')->count();
        $summary['missing_documented_endpoint'] = $rows->where('reality_type', 'missing_documented_endpoint')->count();
        $summary['undocumented_endpoint'] = $rows->where('reality_type', 'undocumented_endpoint')->count();
        $summary['undocumented_response'] = $rows->where('reality_type', 'undocumented_response')->count();
        $summary['auth_contract_mismatch'] = $rows->where('reality_type', 'auth_contract_mismatch')->count();
        $summary['breaking_contract_mismatch'] = $rows->where('is_breaking', true)->count();
        $summary['skipped'] = $rows->where('status', ContractValidationResult::STATUS_SKIPPED)->count();

        $blockers = $rows->filter(fn (array $row): bool => $row['is_breaking'])->values();
        $warnings = $rows->filter(fn (array $row): bool => $row['status'] === ContractValidationResult::STATUS_WARNING)->values();

        $status = match (true) {
            $blockers->isNotEmpty() => 'blocking',
            $warnings->isNotEmpty() => 'review',
            default => 'aligned',
        };

        return [
            'run' => $run,
            'summary' => $summary,
            'rows' => $rows,
            'blockers' => $blockers,
            'warnings' => $warnings,
            'status' => $status,
            'label' => __('messages.contract_reality.statuses.'.$status),
            'css' => match ($status) {
                'blocking' => 'danger',
                'review' => 'warning',
                'aligned' => 'success',
                default => 'default',
            },
        ];
    }

    /** @return array<string, int> */
    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'matches_contract' => 0,
            'contract_drift' => 0,
            'missing_documented_endpoint' => 0,
            'undocumented_endpoint' => 0,
            'undocumented_response' => 0,
            'auth_contract_mismatch' => 0,
            'breaking_contract_mismatch' => 0,
            'skipped' => 0,
        ];
    }

    /** @return array<string, mixed> */
    private function row(ContractValidationResult $result): array
    {
        $type = $this->realityType($result);
        $isBreaking = $result->status === ContractValidationResult::STATUS_FAIL
            && in_array($result->severity, [ContractValidationResult::SEVERITY_HIGH, ContractValidationResult::SEVERITY_CRITICAL], true);

        return [
            'id' => $result->id,
            'result' => $result,
            'method' => $result->method,
            'path' => $result->path,
            'endpoint' => $result->endpoint,
            'scan_result' => $result->scanResult,
            'check_type' => $result->check_type,
            'check_type_label' => $result->check_type_label,
            'reality_type' => $type,
            'reality_label' => __('messages.contract_reality.types.'.$type),
            'severity' => $result->severity,
            'severity_label' => $result->severity_label,
            'severity_css' => $result->severity_css,
            'status' => $result->status,
            'status_label' => $result->status_label,
            'status_css' => $result->status_css,
            'message' => $result->message,
            'expected' => $result->expected,
            'actual' => $result->actual,
            'evidence' => $result->evidence_json,
            'is_breaking' => $isBreaking,
        ];
    }

    private function realityType(ContractValidationResult $result): string
    {
        if ($result->status === ContractValidationResult::STATUS_PASS) {
            return 'matches_contract';
        }

        return match ($result->check_type) {
            ContractValidationResult::CHECK_OPERATION_IMPLEMENTED => 'missing_documented_endpoint',
            ContractValidationResult::CHECK_OPERATION_DOCUMENTED => 'undocumented_endpoint',
            ContractValidationResult::CHECK_UNDOCUMENTED_RESPONSE_FIELD => 'undocumented_response',
            ContractValidationResult::CHECK_AUTH_REQUIREMENT => 'auth_contract_mismatch',
            default => 'contract_drift',
        };
    }
}
