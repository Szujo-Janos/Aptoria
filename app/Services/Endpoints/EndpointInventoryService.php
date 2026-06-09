<?php

namespace App\Services\Endpoints;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EndpointInventoryService
{
    /**
     * @param array<string, mixed> $filters
     * @return array{summary:array<string,mixed>, endpoints:LengthAwarePaginator, filters:array<string,mixed>, filter_options:array<string,mixed>}
     */
    public function index(Project $project, array $filters, int $perPage = 25): array
    {
        $filters = $this->normalizeFilters($filters);
        $summary = $this->summary($project);

        $query = Endpoint::query()
            ->where('project_id', $project->id)
            ->with(['environment', 'authProfile', 'latestScanResult.scanRun', 'latestContractValidationResult'])
            ->withCount([
                'assertionRules',
                'testCases',
                'findings',
                'findings as open_findings_count' => fn (Builder $query): Builder => $query->whereIn('status', Finding::OPEN_STATUSES),
            ]);

        $this->applyFilters($query, $filters);
        $this->applySort($query, (string) ($filters['sort'] ?? 'risk'));

        $endpoints = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Endpoint $endpoint): Endpoint => $this->decorate($endpoint));

        return [
            'summary' => $summary,
            'endpoints' => $endpoints,
            'filters' => $filters,
            'filter_options' => $this->filterOptions($project),
        ];
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['q'] !== '') {
            $needle = '%'.$filters['q'].'%';
            $query->where(function (Builder $query) use ($needle): void {
                $query->where('path', 'like', $needle)
                    ->orWhere('name', 'like', $needle)
                    ->orWhere('tags', 'like', $needle)
                    ->orWhere('description', 'like', $needle);
            });
        }

        if ($filters['method'] !== '') {
            $query->where('method', strtoupper((string) $filters['method']));
        }

        if ($filters['risk'] !== '') {
            $query->where('risk_level', $filters['risk']);
        }

        if ($filters['environment'] !== '') {
            if ($filters['environment'] === 'project_default') {
                $query->whereNull('environment_id');
            } else {
                $query->where('environment_id', (int) $filters['environment']);
            }
        }

        if ($filters['auth'] !== '') {
            match ($filters['auth']) {
                'required' => $query->where('auth_required', true),
                'public' => $query->where('auth_required', false),
                'profile' => $query->whereNotNull('auth_profile_id'),
                'missing_profile' => $query->where('auth_required', true)->whereNull('auth_profile_id'),
                default => null,
            };
        }

        if ($filters['scan'] !== '') {
            match ($filters['scan']) {
                'scanned' => $query->whereHas('latestScanResult'),
                'not_scanned' => $query->doesntHave('latestScanResult'),
                'passed' => $query->whereHas('latestScanResult', fn (Builder $query): Builder => $query->where('status', ScanResult::STATUS_COMPLETED)),
                'failed' => $query->whereHas('latestScanResult', fn (Builder $query): Builder => $query->where('status', ScanResult::STATUS_FAILED)),
                'skipped' => $query->whereHas('latestScanResult', fn (Builder $query): Builder => $query->where('status', ScanResult::STATUS_SKIPPED)),
                default => null,
            };
        }

        if ($filters['findings'] !== '') {
            match ($filters['findings']) {
                'open' => $query->whereHas('findings', fn (Builder $query): Builder => $query->whereIn('status', Finding::OPEN_STATUSES)),
                'none' => $query->whereDoesntHave('findings', fn (Builder $query): Builder => $query->whereIn('status', Finding::OPEN_STATUSES)),
                default => null,
            };
        }

        if ($filters['coverage'] !== '') {
            match ($filters['coverage']) {
                'missing_assertions' => $query->doesntHave('assertionRules'),
                'missing_test_cases' => $query->doesntHave('testCases'),
                'missing_expected_status' => $query->whereNull('expected_status'),
                'missing_expected_content_type' => $query->whereNull('expected_content_type'),
                default => null,
            };
        }

        if ($filters['source'] !== '') {
            match ($filters['source']) {
                'postman' => $query->where(function (Builder $query): void {
                    $query->where('qa_notes', 'like', '%Postman%')
                        ->orWhere('risk_reason', 'like', '%Postman%')
                        ->orWhereNotNull('request_headers')
                        ->orWhereNotNull('request_body_preview');
                }),
                'openapi' => $query->where(function (Builder $query): void {
                    $query->where('qa_notes', 'like', '%OpenAPI%')
                        ->orWhere('qa_notes', 'like', '%Swagger%')
                        ->orWhere('risk_reason', 'like', '%OpenAPI%')
                        ->orWhere('risk_reason', 'like', '%Swagger%');
                }),
                'manual' => $query->whereNull('request_headers')
                    ->whereNull('request_body_preview')
                    ->where(function (Builder $query): void {
                        $query->whereNull('qa_notes')
                            ->orWhere(function (Builder $query): void {
                                $query->where('qa_notes', 'not like', '%Postman%')
                                    ->where('qa_notes', 'not like', '%OpenAPI%')
                                    ->where('qa_notes', 'not like', '%Swagger%');
                            });
                    }),
                default => null,
            };
        }

        if ($filters['status'] !== '') {
            match ($filters['status']) {
                'active' => $query->where('is_active', true)->where('excluded_from_scan', false),
                'inactive' => $query->where('is_active', false),
                'excluded' => $query->where('excluded_from_scan', true),
                default => null,
            };
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'method' => $query->orderBy('method')->orderBy('path'),
            'path' => $query->orderBy('path')->orderBy('method'),
            'newest' => $query->latest('created_at'),
            'oldest' => $query->oldest('created_at'),
            default => $query->orderByRaw("CASE risk_level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'review' THEN 3 WHEN 'public' THEN 4 WHEN 'low' THEN 5 ELSE 9 END")
                ->orderBy('method')
                ->orderBy('path'),
        };
    }

    private function decorate(Endpoint $endpoint): Endpoint
    {
        $endpoint->setAttribute('inventory_source', $this->source($endpoint));
        $endpoint->setAttribute('inventory_flags', $this->flags($endpoint));
        $endpoint->setAttribute('inventory_scan_css', $this->scanCss($endpoint));
        $endpoint->setAttribute('inventory_scan_label', $this->scanLabel($endpoint));

        return $endpoint;
    }

    /** @return array<int, array{key:string,label:string,css:string}> */
    private function flags(Endpoint $endpoint): array
    {
        $flags = [];

        if (! $endpoint->latestScanResult) {
            $flags[] = ['key' => 'not_scanned', 'label' => __('messages.endpoint_inventory.flags.not_scanned'), 'css' => 'default'];
        }

        if (($endpoint->open_findings_count ?? 0) > 0) {
            $flags[] = ['key' => 'open_findings', 'label' => __('messages.endpoint_inventory.flags.open_findings', ['count' => $endpoint->open_findings_count]), 'css' => 'danger'];
        }

        if (($endpoint->assertion_rules_count ?? 0) === 0) {
            $flags[] = ['key' => 'missing_assertions', 'label' => __('messages.endpoint_inventory.flags.missing_assertions'), 'css' => 'warning'];
        }

        if (($endpoint->test_cases_count ?? 0) === 0) {
            $flags[] = ['key' => 'missing_test_cases', 'label' => __('messages.endpoint_inventory.flags.missing_test_cases'), 'css' => 'warning'];
        }

        if (! $endpoint->expected_status) {
            $flags[] = ['key' => 'missing_expected_status', 'label' => __('messages.endpoint_inventory.flags.missing_expected_status'), 'css' => 'info'];
        }

        if ($endpoint->auth_required && ! $endpoint->auth_profile_id) {
            $flags[] = ['key' => 'missing_auth_profile', 'label' => __('messages.endpoint_inventory.flags.missing_auth_profile'), 'css' => 'warning'];
        }

        if (! $endpoint->is_active) {
            $flags[] = ['key' => 'inactive', 'label' => __('messages.endpoint_inventory.flags.inactive'), 'css' => 'default'];
        }

        if ($endpoint->excluded_from_scan) {
            $flags[] = ['key' => 'excluded', 'label' => __('messages.endpoint_inventory.flags.excluded'), 'css' => 'default'];
        }

        return $flags;
    }

    /** @return array{key:string,label:string,css:string} */
    private function source(Endpoint $endpoint): array
    {
        $haystack = strtolower((string) $endpoint->qa_notes.' '.(string) $endpoint->risk_reason);

        if (str_contains($haystack, 'postman') || $endpoint->request_headers || $endpoint->request_body_preview) {
            return ['key' => 'postman', 'label' => __('messages.endpoint_inventory.sources.postman'), 'css' => 'info'];
        }

        if (str_contains($haystack, 'openapi') || str_contains($haystack, 'swagger')) {
            return ['key' => 'openapi', 'label' => __('messages.endpoint_inventory.sources.openapi'), 'css' => 'primary'];
        }

        return ['key' => 'manual', 'label' => __('messages.endpoint_inventory.sources.manual'), 'css' => 'default'];
    }

    private function scanCss(Endpoint $endpoint): string
    {
        if (! $endpoint->latestScanResult) {
            return 'default';
        }

        return $endpoint->latestScanResult->status_css;
    }

    private function scanLabel(Endpoint $endpoint): string
    {
        if (! $endpoint->latestScanResult) {
            return __('messages.endpoint_inventory.scan_states.not_scanned');
        }

        return $endpoint->latestScanResult->status_label;
    }

    /** @return array<string, mixed> */
    private function summary(Project $project): array
    {
        $endpoints = $project->endpoints()
            ->with('latestScanResult')
            ->withCount([
                'findings as open_findings_count' => fn (Builder $query): Builder => $query->whereIn('status', Finding::OPEN_STATUSES),
            ])
            ->get();

        $scanned = $endpoints->filter(fn (Endpoint $endpoint): bool => $endpoint->latestScanResult !== null);
        $responseTimes = $scanned
            ->map(fn (Endpoint $endpoint): mixed => $endpoint->latestScanResult?->response_time_ms)
            ->filter(fn (mixed $value): bool => is_numeric($value));

        return [
            'total' => $endpoints->count(),
            'active' => $endpoints->where('is_active', true)->where('excluded_from_scan', false)->count(),
            'auth_required' => $endpoints->where('auth_required', true)->count(),
            'critical' => $endpoints->where('risk_level', Endpoint::RISK_CRITICAL)->count(),
            'high' => $endpoints->where('risk_level', Endpoint::RISK_HIGH)->count(),
            'review_queue' => $endpoints->filter(fn (Endpoint $endpoint): bool => in_array($endpoint->risk_level, [Endpoint::RISK_CRITICAL, Endpoint::RISK_HIGH, Endpoint::RISK_REVIEW], true))->count(),
            'scanned' => $scanned->count(),
            'not_scanned' => max(0, $endpoints->count() - $scanned->count()),
            'failed_scan' => $scanned->filter(fn (Endpoint $endpoint): bool => $endpoint->latestScanResult?->status === ScanResult::STATUS_FAILED)->count(),
            'open_findings' => $endpoints->sum('open_findings_count'),
            'avg_response_time' => $responseTimes->isEmpty() ? null : (int) round($responseTimes->avg()),
            'scan_coverage_percent' => $endpoints->isEmpty() ? 0 : (int) round(($scanned->count() / max(1, $endpoints->count())) * 100),
        ];
    }

    /** @return array<string, mixed> */
    private function filterOptions(Project $project): array
    {
        return [
            'methods' => Endpoint::METHODS,
            'risks' => Endpoint::RISKS,
            'environments' => $project->environments()->orderBy('name')->get(['id', 'name']),
            'auth' => ['required', 'public', 'profile', 'missing_profile'],
            'scan' => ['scanned', 'not_scanned', 'passed', 'failed', 'skipped'],
            'findings' => ['open', 'none'],
            'coverage' => ['missing_assertions', 'missing_test_cases', 'missing_expected_status', 'missing_expected_content_type'],
            'source' => ['postman', 'openapi', 'manual'],
            'status' => ['active', 'inactive', 'excluded'],
            'sort' => ['risk', 'method', 'path', 'newest', 'oldest'],
        ];
    }

    /** @param array<string, mixed> $filters */
    private function normalizeFilters(array $filters): array
    {
        return [
            'q' => trim((string) ($filters['q'] ?? '')),
            'method' => trim((string) ($filters['method'] ?? '')),
            'risk' => trim((string) ($filters['risk'] ?? '')),
            'environment' => trim((string) ($filters['environment'] ?? '')),
            'auth' => trim((string) ($filters['auth'] ?? '')),
            'scan' => trim((string) ($filters['scan'] ?? '')),
            'findings' => trim((string) ($filters['findings'] ?? '')),
            'coverage' => trim((string) ($filters['coverage'] ?? '')),
            'source' => trim((string) ($filters['source'] ?? '')),
            'status' => trim((string) ($filters['status'] ?? '')),
            'sort' => trim((string) ($filters['sort'] ?? 'risk')),
        ];
    }
}
