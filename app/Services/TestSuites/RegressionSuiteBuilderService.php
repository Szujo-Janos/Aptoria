<?php

namespace App\Services\TestSuites;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\TestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use App\Models\User;
use App\Services\AssertionEvaluationService;
use App\Services\SafeProbeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegressionSuiteBuilderService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function build(Project $project, array $payload): TestSuite
    {
        /** @var Collection<int, Endpoint> $endpoints */
        $endpoints = Endpoint::query()
            ->where('project_id', $project->id)
            ->whereIn('id', array_map('intval', $payload['endpoint_ids'] ?? []))
            ->orderByRaw("CASE method WHEN 'GET' THEN 0 WHEN 'HEAD' THEN 1 WHEN 'POST' THEN 2 WHEN 'PUT' THEN 3 WHEN 'PATCH' THEN 4 WHEN 'DELETE' THEN 5 ELSE 6 END")
            ->orderBy('path')
            ->get();

        return DB::transaction(function () use ($project, $payload, $endpoints): TestSuite {
            $suite = $project->testSuites()->create([
                'name' => trim((string) $payload['name']),
                'description' => trim((string) ($payload['description'] ?? '')) ?: __('messages.regression_builder.generated_description'),
                'status' => TestSuite::STATUS_ACTIVE,
            ]);

            $jsonPaths = $this->jsonPaths((string) ($payload['required_json_paths'] ?? ''));
            $caseCount = 0;
            $assertionCount = 0;
            $priority = (string) ($payload['priority'] ?? TestCase::PRIORITY_HIGH);
            $expectedStatus = isset($payload['expected_status']) && $payload['expected_status'] !== ''
                ? (int) $payload['expected_status']
                : null;
            $includeStatusAssertions = (bool) ($payload['include_status_assertions'] ?? true);
            $includeJsonPathAssertions = (bool) ($payload['include_json_path_assertions'] ?? false);

            foreach ($endpoints->values() as $index => $endpoint) {
                $status = $expectedStatus ?: ($endpoint->expected_status ?: 200);

                $project->testCases()->create([
                    'test_suite_id' => $suite->id,
                    'endpoint_id' => $endpoint->id,
                    'title' => __('messages.regression_builder.case_title', [
                        'method' => $endpoint->method,
                        'path' => $endpoint->path,
                    ]),
                    'description' => __('messages.regression_builder.case_description'),
                    'preconditions' => $endpoint->auth_required
                        ? __('messages.regression_builder.case_preconditions_auth')
                        : __('messages.regression_builder.case_preconditions_public'),
                    'steps' => __('messages.regression_builder.case_steps', [
                        'method' => $endpoint->method,
                        'path' => $endpoint->path,
                    ]),
                    'expected_result' => __('messages.regression_builder.case_expected', ['status' => $status]),
                    'type' => TestCase::TYPE_HYBRID,
                    'priority' => $priority,
                    'status' => TestCase::STATUS_READY,
                    'last_run_status' => TestCase::RUN_NOT_RUN,
                    'execution_order' => $index + 1,
                    'builder_metadata_json' => [
                        'source' => 'regression_suite_builder',
                        'expected_status' => $status,
                        'required_json_paths' => $jsonPaths,
                    ],
                ]);
                $caseCount++;

                if ($includeStatusAssertions) {
                    $assertionCount += $this->ensureStatusAssertion($project, $endpoint, $status);
                }

                if ($includeJsonPathAssertions) {
                    foreach ($jsonPaths as $path) {
                        $assertionCount += $this->ensureJsonPathAssertion($project, $endpoint, $path);
                    }
                }
            }

            $suite->description = trim(($suite->description ?: '')."\n\n".__('messages.regression_builder.generated_footer', [
                'cases' => $caseCount,
                'assertions' => $assertionCount,
            ]));
            $suite->save();

            return $suite->refresh();
        });
    }

    /**
     * @return array{total:int,pass:int,fail:int,blocked:int,skipped:int}
     */
    public function runSuite(Project $project, TestSuite $suite, ?User $user, SafeProbeService $safeProbe, AssertionEvaluationService $assertions): array
    {
        $summary = [
            'total' => 0,
            'pass' => 0,
            'fail' => 0,
            'blocked' => 0,
            'skipped' => 0,
        ];

        $cases = $suite->testCases()
            ->with('endpoint')
            ->orderBy('execution_order')
            ->orderBy('id')
            ->get();

        foreach ($cases as $testCase) {
            $summary['total']++;
            $endpoint = $testCase->endpoint;

            if (! $endpoint) {
                $this->recordResult($project, $testCase, TestCaseResult::STATUS_SKIPPED, __('messages.regression_builder.run_note_no_endpoint'));
                $summary['skipped']++;
                continue;
            }

            if (! $endpoint->isProbeable()) {
                $this->recordResult($project, $testCase, TestCaseResult::STATUS_BLOCKED, __('messages.regression_builder.run_note_not_probeable'));
                $summary['blocked']++;
                continue;
            }

            $scanRun = $safeProbe->runEndpoint($project, $endpoint, $user);
            /** @var ScanResult|null $scanResult */
            $scanResult = $scanRun->results()->where('endpoint_id', $endpoint->id)->latest()->first();

            if (! $scanResult || $scanResult->status !== ScanResult::STATUS_COMPLETED) {
                $this->recordResult($project, $testCase, TestCaseResult::STATUS_BLOCKED, $scanResult?->error_message ?: __('messages.regression_builder.run_note_scan_unavailable'), $scanResult);
                $summary['blocked']++;
                continue;
            }

            $evaluation = $assertions->evaluate($endpoint, $scanResult);
            $failed = $scanResult->status_code !== null && (int) $scanResult->status_code >= 500;
            $status = ($evaluation['status'] === AssertionEvaluationService::STATUS_FAIL || $failed)
                ? TestCaseResult::STATUS_FAIL
                : TestCaseResult::STATUS_PASS;

            $note = $status === TestCaseResult::STATUS_PASS
                ? __('messages.regression_builder.run_note_pass', ['status' => $scanResult->status_code ?? __('messages.common.not_available')])
                : __('messages.regression_builder.run_note_fail', ['status' => $scanResult->status_code ?? __('messages.common.not_available')]);

            $this->recordResult($project, $testCase, $status, $note, $scanResult);
            $summary[$status]++;
        }

        return $summary;
    }

    private function ensureStatusAssertion(Project $project, Endpoint $endpoint, int $status): int
    {
        $rule = EndpointAssertionRule::query()->firstOrCreate([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
        ], [
            'operator' => EndpointAssertionRule::OPERATOR_EQUALS,
            'expected_value' => (string) $status,
            'severity' => EndpointAssertionRule::SEVERITY_FAIL,
            'enabled' => true,
        ]);

        return $rule->wasRecentlyCreated ? 1 : 0;
    }

    private function ensureJsonPathAssertion(Project $project, Endpoint $endpoint, string $path): int
    {
        $rule = EndpointAssertionRule::query()->firstOrCreate([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'rule_key' => EndpointAssertionRule::RULE_JSON_PATH_VALUE,
            'target_path' => $path,
        ], [
            'operator' => EndpointAssertionRule::OPERATOR_EXISTS,
            'expected_value' => null,
            'severity' => EndpointAssertionRule::SEVERITY_WARNING,
            'enabled' => true,
        ]);

        return $rule->wasRecentlyCreated ? 1 : 0;
    }

    private function recordResult(Project $project, TestCase $testCase, string $status, string $note, ?ScanResult $scanResult = null): TestCaseResult
    {
        $result = $testCase->results()->create([
            'project_id' => $project->id,
            'scan_run_id' => $scanResult?->scan_run_id,
            'scan_result_id' => $scanResult?->id,
            'status' => $status,
            'actual_result' => $note,
            'notes' => __('messages.regression_builder.auto_run_note'),
            'executed_at' => now(),
        ]);

        $testCase->update([
            'last_run_status' => $result->status,
            'last_run_at' => $result->executed_at,
            'actual_result' => $note,
        ]);

        return $result;
    }

    /** @return array<int, string> */
    private function jsonPaths(string $input): array
    {
        return collect(preg_split('/\r\n|\r|\n|,/', $input) ?: [])
            ->map(fn (string $path): string => trim($path))
            ->filter()
            ->map(fn (string $path): string => Str::startsWith($path, '$') ? $path : '$.'.ltrim($path, '.'))
            ->unique()
            ->values()
            ->all();
    }
}
