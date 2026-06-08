<?php

namespace App\Services;

use App\Models\Project;
use App\Models\TestCase;
use App\Models\TestSuite;
use Illuminate\Support\Collection;

class TestExecutionDashboardService
{
    /** @return array<string, mixed> */
    public function summarize(Project $project): array
    {
        $runCounts = $this->emptyRunCounts();
        $caseCounts = $project->testCases()
            ->selectRaw("COALESCE(last_run_status, 'not_run') as run_status, COUNT(*) as total")
            ->groupBy('run_status')
            ->pluck('total', 'run_status');

        foreach ($caseCounts as $status => $total) {
            $runCounts[$status ?: TestCase::RUN_NOT_RUN] = (int) $total;
        }

        $total = array_sum($runCounts);
        $executed = $runCounts[TestCase::RUN_PASS]
            + $runCounts[TestCase::RUN_FAIL]
            + $runCounts[TestCase::RUN_BLOCKED]
            + $runCounts[TestCase::RUN_SKIPPED];

        $activeCases = $project->testCases()
            ->whereIn('status', [TestCase::STATUS_READY, TestCase::STATUS_ACTIVE])
            ->count();

        $readyNotRun = $project->testCases()
            ->whereIn('status', [TestCase::STATUS_READY, TestCase::STATUS_ACTIVE])
            ->where(function ($query): void {
                $query->whereNull('last_run_status')
                    ->orWhere('last_run_status', TestCase::RUN_NOT_RUN);
            })
            ->count();

        $criticalOpen = $project->testCases()
            ->whereIn('priority', [TestCase::PRIORITY_CRITICAL, TestCase::PRIORITY_HIGH])
            ->whereIn('status', [TestCase::STATUS_READY, TestCase::STATUS_ACTIVE])
            ->where(function ($query): void {
                $query->whereNull('last_run_status')
                    ->orWhereIn('last_run_status', [TestCase::RUN_NOT_RUN, TestCase::RUN_FAIL, TestCase::RUN_BLOCKED]);
            })
            ->count();

        return [
            'total' => $total,
            'executed' => $executed,
            'active_cases' => $activeCases,
            'ready_not_run' => $readyNotRun,
            'critical_attention' => $criticalOpen,
            'execution_percent' => $total > 0 ? (int) round(($executed / $total) * 100) : 0,
            'pass_rate' => $executed > 0 ? (int) round(($runCounts[TestCase::RUN_PASS] / $executed) * 100) : 0,
            'run_counts' => $runCounts,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    public function suiteSummaries(Project $project): Collection
    {
        return $project->testSuites()
            ->with(['testCases' => fn ($query) => $query->select('id', 'project_id', 'test_suite_id', 'last_run_status')])
            ->orderBy('name')
            ->get()
            ->map(function (TestSuite $suite): array {
                $counts = $this->emptyRunCounts();

                foreach ($suite->testCases as $testCase) {
                    $status = $testCase->last_run_status ?: TestCase::RUN_NOT_RUN;
                    $counts[$status] = ($counts[$status] ?? 0) + 1;
                }

                $total = array_sum($counts);
                $executed = $counts[TestCase::RUN_PASS]
                    + $counts[TestCase::RUN_FAIL]
                    + $counts[TestCase::RUN_BLOCKED]
                    + $counts[TestCase::RUN_SKIPPED];

                return [
                    'suite' => $suite,
                    'total' => $total,
                    'executed' => $executed,
                    'execution_percent' => $total > 0 ? (int) round(($executed / $total) * 100) : 0,
                    'pass_rate' => $executed > 0 ? (int) round(($counts[TestCase::RUN_PASS] / $executed) * 100) : 0,
                    'run_counts' => $counts,
                ];
            });
    }

    /** @return array<string, int> */
    private function emptyRunCounts(): array
    {
        return collect(TestCase::RUN_STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => 0])
            ->all();
    }
}
