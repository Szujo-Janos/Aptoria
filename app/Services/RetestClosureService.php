<?php

namespace App\Services;

use App\Models\Finding;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class RetestClosureService
{
    public function summary(Project $project): array
    {
        if (! Schema::hasTable('findings')) {
            return $this->emptySummary();
        }

        $hasRetestColumns = Schema::hasColumn('findings', 'retest_status');
        $findings = $project->findings()
            ->with(['endpoint', 'retestEvidence'])
            ->latest('updated_at')
            ->get();

        $retestScope = $findings->filter(function (Finding $finding) use ($hasRetestColumns): bool {
            if ((bool) $finding->retest_required) {
                return true;
            }

            if ($hasRetestColumns && in_array((string) $finding->retest_status, ['required', 'ready_for_retest', 'passed', 'failed'], true)) {
                return true;
            }

            return in_array((string) $finding->status, ['ready_for_retest', 'retest_failed'], true);
        })->values();

        $required = $retestScope->filter(fn (Finding $finding): bool => (string) $finding->retest_status === 'required' && $finding->status !== 'verified')->count();
        $ready = $retestScope->filter(fn (Finding $finding): bool => (string) $finding->retest_status === 'ready_for_retest' && $finding->status !== 'verified')->count();
        $failed = $retestScope->filter(fn (Finding $finding): bool => (string) $finding->retest_status === 'failed' && $finding->status !== 'verified')->count();
        $passed = $retestScope->filter(fn (Finding $finding): bool => (string) $finding->retest_status === 'passed' || $finding->status === 'verified')->count();
        $pending = $required + $ready;
        $open = $pending + $failed;
        $missingEvidence = $retestScope->filter(function (Finding $finding): bool {
            if ($finding->status === 'verified' || $finding->retest_status === 'passed') {
                return false;
            }

            return (bool) $finding->retest_required && ! $finding->retest_evidence_id && ! $finding->retestEvidence;
        })->count();

        $regressionScope = $findings->where('source', 'regression')->values();
        $openRegression = $regressionScope->filter(fn (Finding $finding): bool => $finding->status !== 'verified')->count();
        $closedRegression = $regressionScope->filter(fn (Finding $finding): bool => $finding->status === 'verified')->count();
        $regressionRetestOpen = $regressionScope->filter(function (Finding $finding): bool {
            return $finding->status !== 'verified'
                && ((bool) $finding->retest_required || in_array((string) $finding->retest_status, ['required', 'ready_for_retest', 'failed'], true));
        })->count();

        $staleReady = $retestScope->filter(function (Finding $finding): bool {
            return (string) $finding->retest_status === 'ready_for_retest'
                && $finding->ready_for_retest_at
                && $finding->ready_for_retest_at->lessThan(now()->subDays(7));
        })->count();

        $total = $retestScope->count();
        $closureRate = $total > 0 ? (int) round(($passed / max($total, 1)) * 100) : 100;
        $status = $this->closureStatus($failed, $regressionRetestOpen, $pending, $missingEvidence, $staleReady);

        return [
            'status' => $status,
            'tone' => $this->tone($status),
            'label' => __('messages.release_readiness.retest_closure.statuses.'.$status),
            'summary' => __('messages.release_readiness.retest_closure.summary_'.$status),
            'total' => $total,
            'passed' => $passed,
            'required' => $required,
            'ready_for_retest' => $ready,
            'failed' => $failed,
            'pending' => $pending,
            'open' => $open,
            'missing_evidence' => $missingEvidence,
            'stale_ready' => $staleReady,
            'closure_rate' => $closureRate,
            'regression_total' => $regressionScope->count(),
            'regression_closed' => $closedRegression,
            'regression_open' => $openRegression,
            'regression_retest_open' => $regressionRetestOpen,
            'rows' => $this->rows($retestScope),
            'locked_at' => now()->toDateTimeString(),
        ];
    }

    private function closureStatus(int $failed, int $regressionRetestOpen, int $pending, int $missingEvidence, int $staleReady): string
    {
        if ($failed > 0 || $regressionRetestOpen > 0) {
            return 'blocked';
        }

        if ($pending > 0 || $missingEvidence > 0 || $staleReady > 0) {
            return 'needs_review';
        }

        return 'closed';
    }

    private function tone(string $status): string
    {
        return match ($status) {
            'closed' => 'success',
            'needs_review' => 'warning',
            default => 'danger',
        };
    }

    private function rows(Collection $retestScope): array
    {
        return $retestScope
            ->filter(fn (Finding $finding): bool => $finding->status !== 'verified' || in_array((string) $finding->retest_status, ['failed', 'ready_for_retest', 'required'], true))
            ->take(12)
            ->map(function (Finding $finding): array {
                return [
                    'id' => $finding->id,
                    'title' => $finding->title,
                    'source' => $finding->source,
                    'severity' => $finding->severity,
                    'severity_label' => $finding->severity_label,
                    'severity_tone' => $finding->severity_tone,
                    'status' => $finding->status,
                    'status_label' => $finding->status_label,
                    'status_tone' => $finding->status_tone,
                    'retest_status' => $finding->retest_status ?: 'not_required',
                    'retest_status_label' => $finding->retest_status_label,
                    'retest_status_tone' => $finding->retest_status_tone,
                    'endpoint' => $finding->endpoint ? trim($finding->endpoint->method.' '.$finding->endpoint->path) : '—',
                    'retest_requested_at' => $finding->retest_requested_at?->toDateTimeString(),
                    'ready_for_retest_at' => $finding->ready_for_retest_at?->toDateTimeString(),
                    'retested_at' => $finding->retested_at?->toDateTimeString(),
                    'has_retest_evidence' => (bool) ($finding->retest_evidence_id || $finding->retestEvidence),
                ];
            })
            ->values()
            ->all();
    }

    private function emptySummary(): array
    {
        return [
            'status' => 'closed',
            'tone' => 'success',
            'label' => __('messages.release_readiness.retest_closure.statuses.closed'),
            'summary' => __('messages.release_readiness.retest_closure.summary_closed'),
            'total' => 0,
            'passed' => 0,
            'required' => 0,
            'ready_for_retest' => 0,
            'failed' => 0,
            'pending' => 0,
            'open' => 0,
            'missing_evidence' => 0,
            'stale_ready' => 0,
            'closure_rate' => 100,
            'regression_total' => 0,
            'regression_closed' => 0,
            'regression_open' => 0,
            'regression_retest_open' => 0,
            'rows' => [],
            'locked_at' => now()->toDateTimeString(),
        ];
    }
}
