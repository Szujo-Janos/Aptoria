<?php

namespace App\Services;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Project;
use Illuminate\Support\Collection;

class EndpointAssertionEvaluationService
{
    public function evaluate(Project $project, Endpoint $endpoint, array $quickTestResult): array
    {
        $rules = $this->rulesFor($project, $endpoint);
        $items = [];
        $failed = 0;
        $passed = 0;

        foreach ($rules as $rule) {
            $actual = $this->actualValue($rule, $quickTestResult);
            $ok = $this->compare($actual, $rule->operator, $rule->expected_value);

            if ($ok) {
                $passed++;
            } else {
                $failed++;
            }

            $items[] = [
                'rule_id' => $rule->id,
                'name' => $rule->name,
                'rule_key' => $rule->rule_key,
                'rule_label' => $rule->rule_label,
                'operator' => $rule->operator,
                'expected' => $rule->expected_value,
                'actual' => $actual,
                'severity' => $rule->severity,
                'passed' => $ok,
            ];
        }

        return [
            'total' => $rules->count(),
            'passed' => $passed,
            'failed' => $failed,
            'items' => $items,
            'has_blocker_failure' => collect($items)->contains(fn (array $item) => ! $item['passed'] && $item['severity'] === 'blocker'),
            'has_warning_failure' => collect($items)->contains(fn (array $item) => ! $item['passed'] && $item['severity'] !== 'info'),
        ];
    }

    private function rulesFor(Project $project, Endpoint $endpoint): Collection
    {
        return EndpointAssertionRule::query()
            ->where('project_id', $project->id)
            ->where('enabled', true)
            ->where(function ($query) use ($endpoint): void {
                $query->whereNull('endpoint_id')->orWhere('endpoint_id', $endpoint->id);
            })
            ->orderByRaw('case when endpoint_id is null then 1 else 0 end')
            ->orderBy('name')
            ->get();
    }

    private function actualValue(EndpointAssertionRule $rule, array $result): mixed
    {
        return match ($rule->rule_key) {
            'status_code' => $result['status_code'] ?? null,
            'max_response_time' => $result['response_time_ms'] ?? null,
            'content_type_contains' => $result['content_type'] ?? null,
            'max_response_size' => $result['response_size'] ?? null,
            'body_contains', 'body_not_contains' => $result['body_preview'] ?? null,
            default => null,
        };
    }

    private function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        if ($actual === null || $actual === '') {
            return false;
        }

        $actualString = mb_strtolower((string) $actual);
        $expectedString = mb_strtolower((string) $expected);
        $actualNumber = is_numeric($actual) ? (float) $actual : null;
        $expectedNumber = is_numeric($expected) ? (float) $expected : null;

        return match ($operator) {
            'equals' => (string) $actual === (string) $expected,
            'not_equals' => (string) $actual !== (string) $expected,
            'contains' => str_contains($actualString, $expectedString),
            'not_contains' => ! str_contains($actualString, $expectedString),
            'less_than_or_equal' => $actualNumber !== null && $expectedNumber !== null && $actualNumber <= $expectedNumber,
            'greater_than_or_equal' => $actualNumber !== null && $expectedNumber !== null && $actualNumber >= $expectedNumber,
            default => false,
        };
    }
}
