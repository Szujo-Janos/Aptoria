<?php

namespace App\Services\Imports;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NewmanImportService
{
    /** @return array<string,mixed> */
    public function preview(Project $project, string $format, string $payload): array
    {
        $rows = $this->parse($format, $payload);
        $passes = count(array_filter($rows, fn (array $row): bool => $row['status'] === TestCaseResult::STATUS_PASS));
        $fails = count(array_filter($rows, fn (array $row): bool => $row['status'] === TestCaseResult::STATUS_FAIL));
        $skipped = count(array_filter($rows, fn (array $row): bool => $row['status'] === TestCaseResult::STATUS_SKIPPED));

        foreach ($rows as &$row) {
            $row['endpoint_match'] = $this->findEndpoint($project, $row['method'], $row['path'])?->id;
            $row['suite_exists'] = $project->testSuites()->where('name', $row['suite_name'])->exists();
            $row['test_case_exists'] = $project->testCases()->where('title', $row['title'])->exists();
        }
        unset($row);

        return [
            'format' => $format,
            'total' => count($rows),
            'passes' => $passes,
            'fails' => $fails,
            'skipped' => $skipped,
            'rows' => $rows,
            'collection_name' => $this->collectionName($rows),
        ];
    }

    /** @return array<string,int> */
    public function import(Project $project, string $format, string $payload, bool $createFindings = true): array
    {
        $preview = $this->preview($project, $format, $payload);
        $createdSuites = 0;
        $createdCases = 0;
        $createdResults = 0;
        $createdFindings = 0;

        foreach ($preview['rows'] as $row) {
            $suite = TestSuite::query()->firstOrCreate([
                'project_id' => $project->id,
                'name' => $row['suite_name'],
            ], [
                'description' => __('messages.newman_import.imported_suite_description'),
                'status' => TestSuite::STATUS_ACTIVE,
            ]);
            if ($suite->wasRecentlyCreated) {
                $createdSuites++;
            }

            $endpoint = $this->findEndpoint($project, $row['method'], $row['path']);
            $testCase = TestCase::query()->firstOrCreate([
                'project_id' => $project->id,
                'test_suite_id' => $suite->id,
                'title' => $row['title'],
            ], [
                'endpoint_id' => $endpoint?->id,
                'description' => __('messages.newman_import.imported_case_description'),
                'steps' => __('messages.newman_import.imported_case_steps', ['method' => $row['method'] ?: 'N/A', 'path' => $row['path'] ?: $row['title']]),
                'expected_result' => __('messages.newman_import.imported_case_expected'),
                'type' => TestCase::TYPE_AUTOMATED,
                'priority' => $row['status'] === TestCaseResult::STATUS_FAIL ? TestCase::PRIORITY_HIGH : TestCase::PRIORITY_MEDIUM,
                'status' => TestCase::STATUS_READY,
            ]);
            if ($testCase->wasRecentlyCreated) {
                $createdCases++;
            } elseif ($endpoint && ! $testCase->endpoint_id) {
                $testCase->endpoint_id = $endpoint->id;
                $testCase->save();
            }

            $result = $testCase->results()->create([
                'project_id' => $project->id,
                'status' => $row['status'],
                'actual_result' => $row['actual_result'],
                'notes' => $row['notes'],
                'executed_at' => $row['executed_at'] ?: now(),
            ]);
            $createdResults++;

            $testCase->update([
                'last_run_status' => $result->status,
                'last_run_at' => $result->executed_at,
                'actual_result' => $result->actual_result,
            ]);

            if ($createFindings && $row['status'] === TestCaseResult::STATUS_FAIL) {
                $finding = Finding::query()->create([
                    'project_id' => $project->id,
                    'endpoint_id' => $endpoint?->id,
                    'test_case_id' => $testCase->id,
                    'title' => __('messages.newman_import.finding_title', ['title' => $row['title']]),
                    'description' => $row['actual_result'],
                    'source' => Finding::SOURCE_TEST_CASE,
                    'severity' => Finding::SEVERITY_HIGH,
                    'status' => Finding::STATUS_OPEN,
                    'reproduction_steps' => $row['reproduction_steps'],
                    'expected_result' => __('messages.newman_import.imported_case_expected'),
                    'actual_result' => $row['actual_result'],
                    'recommendation' => __('messages.newman_import.finding_recommendation'),
                    'detected_at' => $result->executed_at ?: now(),
                ]);
                FindingEvidence::query()->create([
                    'finding_id' => $finding->id,
                    'project_id' => $project->id,
                    'type' => FindingEvidence::TYPE_LOG,
                    'source_label' => 'Newman import',
                    'content' => $row['evidence'],
                    'metadata_json' => [
                        'format' => $format,
                        'suite' => $row['suite_name'],
                        'status' => $row['status'],
                        'duration_ms' => $row['duration_ms'],
                    ],
                ]);
                $createdFindings++;
            }
        }

        return [
            'suites' => $createdSuites,
            'cases' => $createdCases,
            'results' => $createdResults,
            'findings' => $createdFindings,
            'failed' => $preview['fails'],
            'passed' => $preview['passes'],
            'skipped' => $preview['skipped'],
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function parse(string $format, string $payload): array
    {
        return match ($format) {
            'junit' => $this->parseJUnit($payload),
            default => $this->parseJson($payload),
        };
    }

    /** @return array<int,array<string,mixed>> */
    private function parseJson(string $payload): array
    {
        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            throw new RuntimeException(__('messages.newman_import.reason_json_parse_failed'));
        }

        $collectionName = (string) Arr::get($decoded, 'collection.info.name', 'Newman Collection');
        $executions = Arr::get($decoded, 'run.executions', []);
        if (! is_array($executions) || $executions === []) {
            throw new RuntimeException(__('messages.newman_import.reason_no_executions'));
        }

        $rows = [];
        foreach ($executions as $index => $execution) {
            if (! is_array($execution)) {
                continue;
            }
            $itemName = $this->string(Arr::get($execution, 'item.name')) ?: 'Newman request '.($index + 1);
            [$method, $path] = $this->methodAndPath($execution);
            $assertions = is_array($execution['assertions'] ?? null) ? $execution['assertions'] : [];
            $failedAssertions = [];
            $skippedAssertions = 0;
            foreach ($assertions as $assertion) {
                if (! is_array($assertion)) {
                    continue;
                }
                if (($assertion['skipped'] ?? false) === true) {
                    $skippedAssertions++;
                    continue;
                }
                if (is_array($assertion['error'] ?? null)) {
                    $failedAssertions[] = trim((string) ($assertion['assertion'] ?? 'Assertion')).': '.trim((string) ($assertion['error']['message'] ?? 'failed'));
                }
            }
            $status = $failedAssertions !== [] ? TestCaseResult::STATUS_FAIL : ($skippedAssertions > 0 && count($assertions) === $skippedAssertions ? TestCaseResult::STATUS_SKIPPED : TestCaseResult::STATUS_PASS);
            $code = Arr::get($execution, 'response.code');
            $duration = $this->int(Arr::get($execution, 'response.responseTime')) ?? $this->int(Arr::get($execution, 'response.response_time'));
            $suiteName = $this->suiteName($collectionName, Arr::get($execution, 'item.path'));
            $actual = $status === TestCaseResult::STATUS_FAIL
                ? implode("\n", $failedAssertions)
                : __('messages.newman_import.actual_passed', ['code' => $code ?: 'N/A']);

            $rows[] = [
                'suite_name' => $suiteName,
                'title' => $this->title($method, $path, $itemName),
                'method' => $method,
                'path' => $path,
                'status' => $status,
                'http_status' => $this->int($code),
                'duration_ms' => $duration,
                'actual_result' => $actual,
                'notes' => __('messages.newman_import.result_notes_json'),
                'reproduction_steps' => $method && $path ? $method.' '.$path : $itemName,
                'evidence' => $this->jsonEvidence($execution, $failedAssertions),
                'executed_at' => null,
            ];
        }

        return $rows;
    }

    /** @return array<int,array<string,mixed>> */
    private function parseJUnit(string $payload): array
    {
        $rows = [];
        if (function_exists('simplexml_load_string')) {
            $xml = @simplexml_load_string($payload);
            if ($xml !== false) {
                $suites = $xml->getName() === 'testsuite' ? [$xml] : iterator_to_array($xml->testsuite ?? []);
                foreach ($suites as $suite) {
                    $suiteName = $this->string((string) ($suite['name'] ?? 'Newman JUnit')) ?: 'Newman JUnit';
                    foreach ($suite->testcase ?? [] as $case) {
                        $name = $this->string((string) ($case['name'] ?? 'Newman test case')) ?: 'Newman test case';
                        $failure = trim((string) ($case->failure ?? ''));
                        $error = trim((string) ($case->error ?? ''));
                        $skipped = isset($case->skipped);
                        $status = $failure !== '' || $error !== '' ? TestCaseResult::STATUS_FAIL : ($skipped ? TestCaseResult::STATUS_SKIPPED : TestCaseResult::STATUS_PASS);
                        [$method, $path] = $this->methodAndPathFromName($name);
                        $rows[] = [
                            'suite_name' => 'Newman - '.$suiteName,
                            'title' => $name,
                            'method' => $method,
                            'path' => $path,
                            'status' => $status,
                            'http_status' => null,
                            'duration_ms' => (int) round(((float) ($case['time'] ?? 0)) * 1000),
                            'actual_result' => $status === TestCaseResult::STATUS_FAIL ? trim($failure."\n".$error) : __('messages.newman_import.actual_passed', ['code' => 'N/A']),
                            'notes' => __('messages.newman_import.result_notes_junit'),
                            'reproduction_steps' => $method && $path ? $method.' '.$path : $name,
                            'evidence' => trim($failure."\n".$error) ?: $name,
                            'executed_at' => null,
                        ];
                    }
                }
            }
        }

        if ($rows === []) {
            throw new RuntimeException(__('messages.newman_import.reason_junit_parse_failed'));
        }

        return $rows;
    }

    private function collectionName(array $rows): string
    {
        $suite = (string) ($rows[0]['suite_name'] ?? 'Newman import');
        return preg_replace('/^Newman\s+-\s+/', '', $suite) ?: $suite;
    }

    private function findEndpoint(Project $project, ?string $method, ?string $path): ?Endpoint
    {
        if (! $method || ! $path) {
            return null;
        }
        return $project->endpoints()->where('method', strtoupper($method))->where('path', Endpoint::normalizePath($path))->first();
    }

    /** @return array{0:?string,1:?string} */
    private function methodAndPath(array $execution): array
    {
        $request = $execution['request'] ?? Arr::get($execution, 'item.request', []);
        $method = strtoupper((string) Arr::get($request, 'method', '')) ?: null;
        $url = Arr::get($request, 'url', Arr::get($execution, 'item.request.url'));
        $path = null;
        if (is_string($url)) {
            $path = $this->urlToPath($url);
        } elseif (is_array($url)) {
            if (is_string($url['raw'] ?? null)) {
                $path = $this->urlToPath((string) $url['raw']);
            } elseif (is_array($url['path'] ?? null)) {
                $path = '/'.implode('/', array_map(fn ($v): string => trim((string) $v, '/'), $url['path']));
            }
        }
        if (! $method || ! $path) {
            [$methodFromName, $pathFromName] = $this->methodAndPathFromName((string) Arr::get($execution, 'item.name', ''));
            $method = $method ?: $methodFromName;
            $path = $path ?: $pathFromName;
        }

        return [$method, $path ? Endpoint::normalizePath($path) : null];
    }

    /** @return array{0:?string,1:?string} */
    private function methodAndPathFromName(string $name): array
    {
        if (preg_match('/\b(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)\s+([^\s]+)/i', $name, $matches)) {
            return [strtoupper($matches[1]), Endpoint::normalizePath($matches[2])];
        }
        return [null, null];
    }

    private function urlToPath(string $url): string
    {
        $url = preg_replace('/\{\{[^}]+\}\}/', 'https://postman.local', $url) ?? $url;
        $parts = parse_url($url);
        $path = is_array($parts) ? ($parts['path'] ?? '/') : $url;
        if (is_array($parts) && ! empty($parts['query'])) {
            $path .= '?'.$parts['query'];
        }
        $path = preg_replace('~(?<=/):([A-Za-z_][A-Za-z0-9_]*)~', '{$1}', $path) ?? $path;
        return Endpoint::normalizePath($path);
    }

    private function suiteName(string $collectionName, mixed $path): string
    {
        if (is_array($path) && count($path) > 1) {
            return 'Newman - '.(string) $path[0];
        }
        return 'Newman - '.$collectionName;
    }

    private function title(?string $method, ?string $path, string $fallback): string
    {
        if ($method && $path) {
            return $method.' '.$path;
        }
        return $fallback;
    }

    /** @param array<int,string> $failures */
    private function jsonEvidence(array $execution, array $failures): string
    {
        return trim("Failed assertions:\n".($failures === [] ? 'None' : implode("\n", $failures))."\n\nExecution excerpt:\n".(json_encode($execution, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: ''));
    }

    private function string(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : Str::limit($value, 240, '…');
    }

    private function int(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }
        return (int) $value;
    }
}
