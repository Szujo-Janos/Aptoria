<?php

namespace App\Services;

use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\TestRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NativeTestEvidenceService
{
    public function __construct(private readonly EvidenceRepositoryService $evidenceRepository)
    {
    }

    /** @return array{run: TestRun, evidence: FindingEvidence, finding: Finding|null} */
    public function recordRun(Project $project, TestCase $testCase, array $data, ?User $user): array
    {
        return DB::transaction(function () use ($project, $testCase, $data, $user): array {
            $finding = null;

            if (($data['status'] ?? null) === 'fail' && ! empty($data['create_finding'])) {
                $finding = $project->findings()->create([
                    'endpoint_id' => $testCase->endpoint_id,
                    'title' => $data['finding_title'] ?: __('messages.native_tests.generated_finding_title', ['case' => $testCase->title]),
                    'source' => 'test_case',
                    'severity' => $data['finding_severity'] ?? 'medium',
                    'status' => 'confirmed',
                    'priority' => $data['finding_priority'] ?? 'high',
                    'summary' => $data['failure_summary'] ?: $data['actual_result'] ?: __('messages.native_tests.generated_finding_summary'),
                    'reproduction_steps' => $testCase->steps,
                    'expected_result' => $testCase->expected_result,
                    'actual_result' => $data['actual_result'] ?? null,
                    'recommendation' => __('messages.native_tests.generated_finding_recommendation'),
                    'evidence_required' => true,
                    'retest_required' => true,
                    'metadata_json' => [
                        'source' => 'native_test_run',
                        'test_case_id' => $testCase->id,
                    ],
                ]);
            }

            $run = $project->testRuns()->create([
                'test_suite_id' => $testCase->test_suite_id,
                'test_case_id' => $testCase->id,
                'endpoint_id' => $testCase->endpoint_id,
                'executed_by_user_id' => $user?->id,
                'finding_id' => $finding?->id,
                'status' => $data['status'],
                'executed_at' => $data['executed_at'] ?? now(),
                'duration_ms' => $data['duration_ms'] ?? null,
                'environment_label' => $data['environment_label'] ?? null,
                'actual_result' => $data['actual_result'] ?? null,
                'failure_summary' => $data['failure_summary'] ?? null,
                'evidence_summary' => $data['evidence_summary'] ?? null,
                'metadata_json' => [
                    'source' => 'native',
                    'create_finding' => ! empty($data['create_finding']),
                ],
            ]);

            $evidencePayload = $this->evidenceRepository->prepareForCreate([
                'finding_id' => $finding?->id,
                'endpoint_id' => $testCase->endpoint_id,
                'test_case_id' => $testCase->id,
                'test_run_id' => $run->id,
                'type' => 'test_result',
                'title' => __('messages.native_tests.evidence_title', ['case' => $testCase->title]),
                'source_label' => __('messages.native_tests.native_test_source'),
                'content' => json_encode([
                    'test_case' => $testCase->title,
                    'suite' => $testCase->suite?->name,
                    'status' => $run->status,
                    'executed_at' => $run->executed_at?->toIso8601String(),
                    'duration_ms' => $run->duration_ms,
                    'environment' => $run->environment_label,
                    'preconditions' => $testCase->preconditions,
                    'steps' => $testCase->steps,
                    'expected_result' => $testCase->expected_result,
                    'actual_result' => $run->actual_result,
                    'failure_summary' => $run->failure_summary,
                    'evidence_summary' => $run->evidence_summary,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'captured_at' => $run->executed_at ?? now(),
                'metadata_json' => [
                    'native_test' => true,
                    'test_case_id' => $testCase->id,
                    'test_run_id' => $run->id,
                    'status' => $run->status,
                ],
            ], $project, $user);

            $evidence = FindingEvidence::create($evidencePayload);
            $this->evidenceRepository->recordCreated($evidence, $user);

            $run->forceFill(['finding_evidence_id' => $evidence->id])->save();
            $this->refreshCaseCounters($testCase, $run->status);

            return ['run' => $run->fresh(['evidence', 'finding']), 'evidence' => $evidence, 'finding' => $finding];
        });
    }

    private function refreshCaseCounters(TestCase $testCase, string $status): void
    {
        $counts = $testCase->runs()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $testCase->forceFill([
            'last_run_status' => $status,
            'last_run_at' => now(),
            'run_count' => (int) $counts->sum(),
            'pass_count' => (int) ($counts['pass'] ?? 0),
            'fail_count' => (int) ($counts['fail'] ?? 0),
        ])->save();
    }
}
