<?php

namespace App\Services;

use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\ExternalImportItem;
use App\Models\ExternalImportRun;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\TestCase;
use App\Models\TestRun;
use App\Models\TestSuite;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExternalQaImportService
{
    private const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    /** @return array<int, array<string, mixed>> */
    public function sourceAdapters(): array
    {
        return [
            ['key' => 'postman_collection', 'icon' => 'file-code-2', 'tone' => 'primary', 'outputs' => ['endpoint', 'assertion'], 'sample_key' => 'postman'],
            ['key' => 'newman_json', 'icon' => 'test-tube', 'tone' => 'success', 'outputs' => ['endpoint', 'assertion', 'finding', 'evidence'], 'sample_key' => 'newman'],
            ['key' => 'jira_csv', 'icon' => 'clipboard-search', 'tone' => 'warning', 'outputs' => ['finding', 'evidence'], 'sample_key' => 'jira_csv'],
            ['key' => 'jira_json', 'icon' => 'bug', 'tone' => 'danger', 'outputs' => ['finding', 'evidence'], 'sample_key' => 'jira_json'],
            ['key' => 'openapi_json', 'icon' => 'brackets-contain', 'tone' => 'info', 'outputs' => ['endpoint', 'assertion', 'evidence', 'finding'], 'sample_key' => 'openapi'],
            ['key' => 'qa_csv', 'icon' => 'table-export', 'tone' => 'secondary', 'outputs' => ['endpoint', 'finding', 'evidence'], 'sample_key' => 'qa_csv'],
            ['key' => 'har_json', 'icon' => 'scan-eye', 'tone' => 'dark', 'outputs' => ['endpoint', 'finding', 'evidence'], 'sample_key' => 'har'],
        ];
    }

    public function preview(Project $project, array $data, ?User $user = null): ExternalImportRun
    {
        [$items, $sourceMeta] = $this->parse((string) $data['source_type'], (string) $data['import_content'], $project);
        $items = $this->resolvePreviewMapping($project, $items);

        if ($items === []) {
            throw ValidationException::withMessages(['import_content' => __('messages.import_center.errors.no_items')]);
        }

        return DB::transaction(function () use ($project, $data, $user, $items, $sourceMeta): ExternalImportRun {
            $summary = $this->buildSummary($items, $sourceMeta);

            $run = $project->externalImportRuns()->create([
                'created_by_user_id' => $user?->id,
                'source_type' => $data['source_type'],
                'source_name' => $data['source_name'] ?? ($sourceMeta['name'] ?? null),
                'source_version' => $data['source_version'] ?? ($sourceMeta['version'] ?? null),
                'status' => 'previewed',
                'item_count' => $summary['item_count'],
                'endpoint_count' => $summary['endpoint_count'],
                'assertion_count' => $summary['assertion_count'],
                'finding_count' => $summary['finding_count'],
                'evidence_count' => $summary['evidence_count'],
                'warning_count' => $summary['warning_count'],
                'blocker_count' => $summary['blocker_count'],
                'summary_json' => $summary,
                'raw_excerpt' => Str::limit((string) $data['import_content'], 5000, ''),
                'previewed_at' => now(),
            ]);

            foreach ($items as $item) {
                $run->items()->create(array_merge($item, ['project_id' => $project->id]));
            }

            return $run->load(['items', 'createdBy']);
        });
    }

    public function apply(Project $project, ExternalImportRun $run, ?User $user = null): array
    {
        abort_unless((int) $run->project_id === (int) $project->id, 404);

        if ($run->status === 'applied') {
            return $run->summary + ['already_applied' => true];
        }

        if ($run->status === 'reverted') {
            throw ValidationException::withMessages(['confirm_apply' => __('messages.import_center.errors.already_reverted')]);
        }

        if ($run->items()->where('match_status', 'conflict')->exists()) {
            throw ValidationException::withMessages(['confirm_apply' => __('messages.import_center.errors.unresolved_conflicts')]);
        }

        return DB::transaction(function () use ($project, $run, $user): array {
            $created = ['endpoints' => 0, 'assertions' => 0, 'findings' => 0, 'evidence' => 0];
            $updated = ['endpoints' => 0, 'findings' => 0];
            $skipped = ['duplicates' => 0, 'needs_review' => 0, 'skipped' => 0];
            $endpointMap = [];
            $findingMap = [];

            $items = $run->items()->orderByRaw("CASE entity_type WHEN 'endpoint' THEN 0 WHEN 'assertion' THEN 1 WHEN 'finding' THEN 2 ELSE 3 END")->get();

            foreach ($items as $item) {
                $payload = is_array($item->payload_json) ? $item->payload_json : [];

                if (in_array($item->match_status, ['duplicate', 'skip', 'needs_review', 'conflict'], true) || $item->apply_strategy === 'skip') {
                    $skipped[$item->match_status === 'duplicate' ? 'duplicates' : ($item->match_status === 'needs_review' ? 'needs_review' : 'skipped')]++;
                    $item->status = 'skipped';
                    $item->trace_note = __('messages.import_center.trace.skipped_preview_item');
                    $item->applied_at = now();
                    $item->save();
                    continue;
                }

                if ($item->target_type === Endpoint::class && $item->target_id) {
                    $payload['_target_endpoint_id'] = $item->target_id;
                }
                if ($item->target_type === Finding::class && $item->target_id) {
                    $payload['_target_finding_id'] = $item->target_id;
                }

                if ($item->entity_type === 'endpoint') {
                    $item->original_payload_json = $this->snapshotEndpoint($project, $payload);
                    [$endpoint, $wasCreated] = $this->applyEndpoint($project, $payload);
                    $item->endpoint_id = $endpoint->id;
                    $endpointMap[$this->operationKey($endpoint->method, $endpoint->path)] = $endpoint;
                    if ($wasCreated) {
                        $created['endpoints']++;
                        $item->created_record_type = Endpoint::class;
                        $item->created_record_id = $endpoint->id;
                        $item->trace_note = __('messages.import_center.trace.created_record', ['record' => 'Endpoint #'.$endpoint->id]);
                    } else {
                        $updated['endpoints']++;
                        $item->updated_record_type = Endpoint::class;
                        $item->updated_record_id = $endpoint->id;
                        $item->trace_note = __('messages.import_center.trace.updated_record', ['record' => 'Endpoint #'.$endpoint->id]);
                    }
                }

                if ($item->entity_type === 'assertion') {
                    $endpoint = $this->resolveEndpointForPayload($project, $payload, $endpointMap);
                    $assertion = $this->applyAssertion($project, $payload, $endpoint);
                    $item->endpoint_id = $endpoint?->id;
                    if ($assertion->wasRecentlyCreated) {
                        $created['assertions']++;
                        $item->created_record_type = EndpointAssertionRule::class;
                        $item->created_record_id = $assertion->id;
                        $item->trace_note = __('messages.import_center.trace.created_record', ['record' => 'Assertion #'.$assertion->id]);
                    } else {
                        $item->updated_record_type = EndpointAssertionRule::class;
                        $item->updated_record_id = $assertion->id;
                        $item->trace_note = __('messages.import_center.trace.linked_existing_record', ['record' => 'Assertion #'.$assertion->id]);
                    }
                }

                if ($item->entity_type === 'finding') {
                    $endpoint = $this->resolveEndpointForPayload($project, $payload, $endpointMap);
                    $item->original_payload_json = $this->snapshotFinding($project, $payload);
                    [$finding, $wasCreated] = $this->applyFinding($project, $payload, $endpoint);
                    $item->endpoint_id = $endpoint?->id;
                    $item->finding_id = $finding->id;
                    $findingMap[(string) ($payload['external_key'] ?? $item->external_key ?? $finding->title)] = $finding;
                    if ($wasCreated) {
                        $created['findings']++;
                        $item->created_record_type = Finding::class;
                        $item->created_record_id = $finding->id;
                        $item->trace_note = __('messages.import_center.trace.created_record', ['record' => 'Finding #'.$finding->id]);
                    } else {
                        $updated['findings']++;
                        $item->updated_record_type = Finding::class;
                        $item->updated_record_id = $finding->id;
                        $item->trace_note = __('messages.import_center.trace.updated_record', ['record' => 'Finding #'.$finding->id]);
                    }
                }

                if ($item->entity_type === 'evidence') {
                    $endpoint = $this->resolveEndpointForPayload($project, $payload, $endpointMap);
                    $finding = $this->resolveFindingForPayload($project, $payload, $findingMap);
                    $evidence = $this->applyEvidence($project, $payload, $endpoint, $finding, $user);
                    $item->endpoint_id = $endpoint?->id;
                    $item->finding_id = $finding?->id;
                    if ($evidence->wasRecentlyCreated) {
                        $created['evidence']++;
                        $item->created_record_type = FindingEvidence::class;
                        $item->created_record_id = $evidence->id;
                        $item->trace_note = __('messages.import_center.trace.created_record', ['record' => 'Evidence #'.$evidence->id]);
                    } else {
                        $item->updated_record_type = FindingEvidence::class;
                        $item->updated_record_id = $evidence->id;
                        $item->trace_note = __('messages.import_center.trace.linked_existing_record', ['record' => 'Evidence #'.$evidence->id]);
                    }
                }

                $item->status = 'applied';
                $item->applied_at = now();
                $item->save();
            }

            $traceSummary = [
                'created_records' => array_sum($created),
                'updated_records' => array_sum($updated),
                'skipped_items' => array_sum($skipped),
                'traceable_items' => $run->items()->whereNotNull('created_record_id')->orWhereNotNull('updated_record_id')->count(),
            ];

            $summary = array_merge($run->summary, [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'traceability' => $traceSummary,
                'applied_by_user_id' => $user?->id,
                'applied_at' => now()->toDateTimeString(),
            ]);

            $run->update([
                'status' => 'applied',
                'summary_json' => $summary,
                'trace_summary_json' => $traceSummary,
                'applied_at' => now(),
            ]);

            return $summary;
        });
    }

    public function undo(Project $project, ExternalImportRun $run, ?User $user = null): array
    {
        abort_unless((int) $run->project_id === (int) $project->id, 404);

        if ($run->status !== 'applied') {
            throw ValidationException::withMessages(['confirm_undo' => __('messages.import_center.errors.undo_requires_applied')]);
        }

        return DB::transaction(function () use ($run, $user): array {
            $summary = ['deleted' => 0, 'restored' => 0, 'preserved' => 0, 'missing' => 0];
            $items = $run->items()->orderByRaw("CASE entity_type WHEN 'evidence' THEN 0 WHEN 'finding' THEN 1 WHEN 'assertion' THEN 2 WHEN 'endpoint' THEN 3 ELSE 4 END")->get();

            foreach ($items as $item) {
                if ($item->created_record_type && $item->created_record_id) {
                    $result = $this->deleteCreatedRecord($item->created_record_type, (int) $item->created_record_id);
                    $summary[$result ? 'deleted' : 'missing']++;
                    $item->revert_status = $result ? 'deleted' : 'missing';
                    $item->revert_action = $result ? 'delete_created_record' : 'created_record_missing';
                } elseif ($item->updated_record_type && $item->updated_record_id && is_array($item->original_payload_json) && $item->original_payload_json !== []) {
                    $result = $this->restoreUpdatedRecord($item->updated_record_type, (int) $item->updated_record_id, $item->original_payload_json);
                    $summary[$result ? 'restored' : 'missing']++;
                    $item->revert_status = $result ? 'restored' : 'missing';
                    $item->revert_action = $result ? 'restore_original_values' : 'updated_record_missing';
                } else {
                    $summary['preserved']++;
                    $item->revert_status = 'preserved';
                    $item->revert_action = 'no_change_needed';
                }

                $item->status = 'reverted';
                $item->reverted_at = now();
                $item->save();
            }

            $run->update([
                'status' => 'reverted',
                'reverted_by_user_id' => $user?->id,
                'reverted_at' => now(),
                'revert_summary_json' => $summary + ['reverted_by_user_id' => $user?->id, 'reverted_at' => now()->toDateTimeString()],
            ]);

            return $summary;
        });
    }

    public function summary(Project $project): array
    {
        if (! Schema::hasTable('external_import_runs')) {
            return $this->emptySummary();
        }

        $latest = $project->externalImportRuns()->latest('previewed_at')->latest()->first();

        if (! $latest) {
            return $this->emptySummary();
        }

        return [
            'has_run' => true,
            'latest_run_id' => $latest->id,
            'source_type' => $latest->source_type,
            'source_type_label' => $latest->source_type_label,
            'source_name' => $latest->source_name,
            'source_version' => $latest->source_version,
            'status' => $latest->status,
            'status_label' => $latest->status_label,
            'status_tone' => $latest->status_tone,
            'item_count' => $latest->item_count,
            'endpoint_count' => $latest->endpoint_count,
            'assertion_count' => $latest->assertion_count,
            'finding_count' => $latest->finding_count,
            'evidence_count' => $latest->evidence_count,
            'warning_count' => $latest->warning_count,
            'blocker_count' => $latest->blocker_count,
            'new_count' => (int) ($latest->summary['new_count'] ?? 0),
            'update_count' => (int) ($latest->summary['update_count'] ?? 0),
            'duplicate_count' => (int) ($latest->summary['duplicate_count'] ?? 0),
            'conflict_count' => (int) ($latest->summary['conflict_count'] ?? 0),
            'needs_review_count' => (int) ($latest->summary['needs_review_count'] ?? 0),
            'previewed_at' => $latest->previewed_at?->toDateTimeString(),
            'applied_at' => $latest->applied_at?->toDateTimeString(),
        ];
    }

    public function markdownEvidence(ExternalImportRun $run): string
    {
        $lines = [
            '# '.__('messages.import_center.markdown_title'),
            '',
            '- '.__('messages.import_center.source_type').': '.$run->source_type_label,
            '- '.__('messages.import_center.source_name').': '.($run->source_name ?: __('messages.common.not_available')),
            '- '.__('messages.common.status').': '.$run->status_label,
            '- '.__('messages.import_center.items').': '.$run->item_count,
            '- '.__('messages.import_center.entity_types.endpoint').': '.$run->endpoint_count,
            '- '.__('messages.import_center.entity_types.assertion').': '.$run->assertion_count,
            '- '.__('messages.import_center.entity_types.finding').': '.$run->finding_count,
            '- '.__('messages.import_center.entity_types.evidence').': '.$run->evidence_count,
            '- '.__('messages.release_readiness.blockers').': '.$run->blocker_count,
            '- '.__('messages.release_readiness.warnings').': '.$run->warning_count,
            '- '.__('messages.import_center.match_statuses.conflict').': '.($run->summary['conflict_count'] ?? 0),
            '- '.__('messages.import_center.match_statuses.needs_review').': '.($run->summary['needs_review_count'] ?? 0),
            '- '.__('messages.import_center.previewed_at').': '.($run->previewed_at?->toDateTimeString() ?? __('messages.common.not_available')),
            '- '.__('messages.import_center.applied_at').': '.($run->applied_at?->toDateTimeString() ?? __('messages.common.not_available')),
            '',
            '## '.__('messages.import_center.preview_items'),
        ];

        foreach ($run->items()->orderBy('entity_type')->orderBy('id')->limit(80)->get() as $item) {
            $operation = trim(($item->method ? $item->method.' ' : '').($item->path ?: ''));
            $lines[] = '- ['.$item->severity_label.'] '.$item->entity_type_label.' / '.$item->action_label.' / '.$item->match_status_label.' — '.$item->title.($operation !== '' ? ' — `'.$operation.'`' : '').($item->conflict_reason ? ' — '.$item->conflict_reason : '');
        }

        return implode("\n", $lines);
    }


    private function snapshotEndpoint(Project $project, array $payload): array
    {
        $method = strtoupper((string) ($payload['method'] ?? 'GET'));
        $path = $this->normalizePath((string) ($payload['path'] ?? '/'));
        $endpoint = ! empty($payload['_target_endpoint_id'])
            ? $project->endpoints()->whereKey((int) $payload['_target_endpoint_id'])->first()
            : $this->findEndpointCandidate($project, $method, $path);

        return $endpoint ? $endpoint->only(['name', 'description', 'tags', 'auth_required', 'expected_status', 'expected_content_type', 'risk_level', 'is_active', 'excluded_from_scan', 'notes']) : [];
    }

    private function snapshotFinding(Project $project, array $payload): array
    {
        $finding = ! empty($payload['_target_finding_id'])
            ? $project->findings()->whereKey((int) $payload['_target_finding_id'])->first()
            : $this->findFindingCandidate($project, $payload, (string) ($payload['external_key'] ?? ''));

        return $finding ? $finding->only(['endpoint_id', 'title', 'source', 'severity', 'status', 'priority', 'owner_name', 'summary', 'reproduction_steps', 'expected_result', 'actual_result', 'recommendation', 'evidence_required', 'retest_required', 'metadata_json']) : [];
    }

    private function deleteCreatedRecord(string $recordType, int $recordId): bool
    {
        if (! class_exists($recordType)) {
            return false;
        }
        $record = $recordType::query()->find($recordId);
        if (! $record) {
            return false;
        }
        $record->delete();

        return true;
    }

    private function restoreUpdatedRecord(string $recordType, int $recordId, array $originalValues): bool
    {
        if (! class_exists($recordType)) {
            return false;
        }
        $record = $recordType::query()->find($recordId);
        if (! $record) {
            return false;
        }
        $record->fill($originalValues);
        $record->save();

        return true;
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: array<string, mixed>} */
    private function parse(string $sourceType, string $content, Project $project): array
    {
        $content = trim(preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content);

        if ($content === '') {
            throw ValidationException::withMessages(['import_content' => __('messages.import_center.errors.empty_content')]);
        }

        return match ($sourceType) {
            'postman_collection' => $this->parsePostmanCollection($content, $project),
            'newman_json' => $this->parseNewmanJson($content, $project),
            'jira_csv' => $this->parseJiraCsv($content, $project),
            'jira_json' => $this->parseJiraJson($content, $project),
            'har_json' => $this->parseHarJson($content, $project),
            'openapi_json' => $this->parseOpenApiJson($content, $project),
            'qa_csv' => $this->parseQaCsv($content, $project),
            default => throw ValidationException::withMessages(['source_type' => __('messages.import_center.errors.unsupported_source')]),
        };
    }

    private function parsePostmanCollection(string $content, Project $project): array
    {
        $document = $this->decodeJson($content);
        $items = [];
        $meta = [
            'name' => Arr::get($document, 'info.name'),
            'version' => Arr::get($document, 'info.version'),
            'warnings' => [],
        ];

        $this->walkPostmanItems(Arr::wrap($document['item'] ?? []), [], $items, $project);

        return [$this->dedupeItems($items), $meta];
    }

    private function walkPostmanItems(array $nodes, array $folders, array &$items, Project $project): void
    {
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $name = (string) ($node['name'] ?? __('messages.import_center.unnamed_item'));
            if (isset($node['item']) && is_array($node['item'])) {
                $this->walkPostmanItems($node['item'], array_merge($folders, [$name]), $items, $project);
                continue;
            }

            $request = $node['request'] ?? null;
            if (! is_array($request)) {
                continue;
            }

            $method = strtoupper((string) ($request['method'] ?? 'GET'));
            if (! in_array($method, self::METHODS, true)) {
                $method = 'GET';
            }

            $path = $this->pathFromPostmanUrl($request['url'] ?? '/');
            $authRequired = isset($request['auth']) || $this->hasAuthorizationHeader($request);
            $endpointExists = $project->endpoints()->where('method', $method)->where('path', $path)->exists();
            $tags = implode(',', array_filter($folders));
            $description = is_array($request['description'] ?? null) ? (string) Arr::get($request, 'description.content') : (string) ($request['description'] ?? '');

            $payload = [
                'method' => $method,
                'path' => $path,
                'name' => $name,
                'description' => $description ?: __('messages.import_center.generated_from_postman'),
                'tags' => $tags,
                'auth_required' => $authRequired,
                'expected_status' => $this->firstStatusAssertion($node),
                'expected_content_type' => 'application/json',
                'risk_level' => $authRequired ? 'review' : 'low',
                'notes' => __('messages.import_center.import_note_postman'),
                'source' => 'postman_collection',
            ];

            $items[] = $this->item('endpoint', $endpointExists ? 'update' : 'create', 'info', $name, __('messages.import_center.summaries.endpoint_from_postman'), $payload, $method, $path);

            foreach ($this->postmanAssertions($node, $method, $path, $name) as $assertionPayload) {
                $items[] = $this->item('assertion', 'create', $assertionPayload['severity'] === 'blocker' ? 'warning' : 'info', $assertionPayload['name'], __('messages.import_center.summaries.assertion_from_postman'), $assertionPayload, $method, $path);
            }
        }
    }

    private function parseNewmanJson(string $content, Project $project): array
    {
        $document = $this->decodeJson($content);
        $items = [];
        $meta = [
            'name' => Arr::get($document, 'collection.info.name') ?? Arr::get($document, 'run.name'),
            'version' => Arr::get($document, 'collection.info.version'),
            'stats' => Arr::get($document, 'run.stats', []),
        ];

        foreach (Arr::wrap(Arr::get($document, 'run.executions', [])) as $execution) {
            if (! is_array($execution)) {
                continue;
            }

            $request = $execution['request'] ?? [];
            $response = $execution['response'] ?? [];
            $method = strtoupper((string) Arr::get($request, 'method', 'GET'));
            $path = $this->pathFromPostmanUrl(Arr::get($request, 'url', Arr::get($response, 'url', '/')));
            $name = (string) (Arr::get($execution, 'item.name') ?: trim($method.' '.$path));
            $statusCode = (int) Arr::get($response, 'code', Arr::get($response, 'statusCode', 0));
            $responseTime = (int) Arr::get($response, 'responseTime', Arr::get($execution, 'response.responseTime', 0));
            $assertions = Arr::wrap($execution['assertions'] ?? []);
            $failures = collect($assertions)->filter(fn ($assertion): bool => is_array($assertion) && ! empty($assertion['error']));
            $severity = $failures->isNotEmpty() || $statusCode >= 500 ? 'blocker' : ($statusCode >= 400 ? 'warning' : 'info');

            $items[] = $this->item('endpoint', $project->endpoints()->where('method', $method)->where('path', $path)->exists() ? 'update' : 'create', 'info', $name, __('messages.import_center.summaries.endpoint_from_newman'), [
                'method' => $method,
                'path' => $path,
                'name' => $name,
                'description' => __('messages.import_center.generated_from_newman'),
                'tags' => 'newman',
                'auth_required' => $this->hasAuthorizationHeader($request),
                'expected_status' => $statusCode ?: 200,
                'expected_content_type' => 'application/json',
                'risk_level' => $severity === 'blocker' ? 'high' : 'low',
                'notes' => __('messages.import_center.import_note_newman'),
                'source' => 'newman_json',
            ], $method, $path);

            $items[] = $this->item('evidence', 'create', $severity, __('messages.import_center.evidence_titles.newman_run', ['operation' => trim($method.' '.$path)]), __('messages.import_center.summaries.evidence_from_newman'), [
                'method' => $method,
                'path' => $path,
                'title' => __('messages.import_center.evidence_titles.newman_run', ['operation' => trim($method.' '.$path)]),
                'type' => 'json_response',
                'source_label' => 'Newman JSON',
                'content' => json_encode([
                    'status_code' => $statusCode,
                    'response_time_ms' => $responseTime,
                    'assertions' => $assertions,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'response_excerpt' => Str::limit((string) (Arr::get($response, 'stream') ?? Arr::get($response, 'body') ?? ''), 4000),
                'metadata' => ['newman' => true, 'status_code' => $statusCode, 'response_time_ms' => $responseTime],
            ], $method, $path);

            foreach ($assertions as $assertion) {
                if (! is_array($assertion)) {
                    continue;
                }
                $assertionName = (string) ($assertion['assertion'] ?? __('messages.import_center.unnamed_assertion'));
                if (empty($assertion['error'])) {
                    $items[] = $this->item('assertion', 'create', 'info', $assertionName, __('messages.import_center.summaries.assertion_from_newman'), [
                        'method' => $method,
                        'path' => $path,
                        'name' => $assertionName,
                        'rule_key' => 'status_code',
                        'operator' => 'equals',
                        'expected_value' => (string) ($statusCode ?: 200),
                        'target_path' => null,
                        'severity' => 'warning',
                        'description' => __('messages.import_center.imported_newman_assertion'),
                    ], $method, $path);
                    continue;
                }

                $errorMessage = (string) Arr::get($assertion, 'error.message', __('messages.import_center.newman_failed_assertion'));
                $externalKey = sha1($method.' '.$path.' '.$assertionName.' '.$errorMessage);
                $items[] = $this->item('finding', 'create', 'blocker', __('messages.import_center.finding_titles.newman_failure', ['assertion' => $assertionName]), $errorMessage, [
                    'method' => $method,
                    'path' => $path,
                    'external_key' => $externalKey,
                    'title' => __('messages.import_center.finding_titles.newman_failure', ['assertion' => $assertionName]),
                    'source' => 'assertion',
                    'severity' => 'high',
                    'status' => 'confirmed',
                    'priority' => 'high',
                    'summary' => $errorMessage,
                    'reproduction_steps' => __('messages.import_center.reproduction.newman', ['operation' => trim($method.' '.$path)]),
                    'expected_result' => $assertionName,
                    'actual_result' => $errorMessage,
                    'recommendation' => __('messages.import_center.recommendations.newman_failure'),
                    'metadata' => ['external_source' => 'newman_json', 'assertion' => $assertionName, 'external_key' => $externalKey],
                ], $method, $path, $externalKey);
            }
        }

        foreach (Arr::wrap(Arr::get($document, 'run.failures', [])) as $failure) {
            if (! is_array($failure)) {
                continue;
            }
            $source = Arr::get($failure, 'source', []);
            $method = strtoupper((string) Arr::get($source, 'request.method', 'GET'));
            $path = $this->pathFromPostmanUrl(Arr::get($source, 'request.url', '/'));
            $message = (string) (Arr::get($failure, 'error.message') ?? __('messages.import_center.newman_failure'));
            $title = (string) (Arr::get($failure, 'error.test') ?? Arr::get($failure, 'error.name') ?? __('messages.import_center.newman_failure'));
            $externalKey = sha1($method.' '.$path.' '.$title.' '.$message);
            $items[] = $this->item('finding', 'create', 'blocker', __('messages.import_center.finding_titles.newman_failure', ['assertion' => $title]), $message, [
                'method' => $method,
                'path' => $path,
                'external_key' => $externalKey,
                'title' => __('messages.import_center.finding_titles.newman_failure', ['assertion' => $title]),
                'source' => 'assertion',
                'severity' => 'high',
                'status' => 'confirmed',
                'priority' => 'high',
                'summary' => $message,
                'actual_result' => $message,
                'recommendation' => __('messages.import_center.recommendations.newman_failure'),
                'metadata' => ['external_source' => 'newman_json', 'external_key' => $externalKey],
            ], $method, $path, $externalKey);
        }

        return [$this->dedupeItems($items), $meta];
    }


    private function parseHarJson(string $content, Project $project): array
    {
        $document = $this->decodeJson($content);
        $entries = Arr::wrap(Arr::get($document, 'log.entries', []));
        $items = [];
        $meta = [
            'name' => Arr::get($document, 'log.browser.name', 'Browser HAR'),
            'version' => Arr::get($document, 'log.version'),
            'creator' => Arr::get($document, 'log.creator.name'),
        ];

        foreach ($entries as $index => $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $request = Arr::get($entry, 'request', []);
            $response = Arr::get($entry, 'response', []);
            if (! is_array($request) || ! is_array($response)) {
                continue;
            }

            $method = strtoupper((string) Arr::get($request, 'method', 'GET'));
            if (! in_array($method, self::METHODS, true)) {
                $method = 'GET';
            }
            $url = (string) Arr::get($request, 'url', '/');
            $path = $this->normalizePath($url);
            $statusCode = (int) Arr::get($response, 'status', 0);
            $mimeType = (string) Arr::get($response, 'content.mimeType', '');
            $timeMs = (int) round((float) Arr::get($entry, 'time', 0));
            $title = trim($method.' '.$path);
            $entryKey = sha1($method.' '.$path.' '.$statusCode.' '.$index);
            $severity = $statusCode >= 500 || $statusCode === 0 ? 'blocker' : ($statusCode >= 400 ? 'warning' : 'info');

            $items[] = $this->item('endpoint', $project->endpoints()->where('method', $method)->where('path', $path)->exists() ? 'update' : 'create', 'info', $title, __('messages.import_center.summaries.endpoint_from_har'), [
                'method' => $method,
                'path' => $path,
                'name' => $title,
                'description' => __('messages.import_center.generated_from_har'),
                'tags' => 'browser-har',
                'auth_required' => $this->harHasAuthLikeHeaders($request),
                'expected_status' => $statusCode > 0 ? $statusCode : null,
                'expected_content_type' => $mimeType !== '' ? $mimeType : null,
                'risk_level' => $severity === 'blocker' ? 'high' : ($severity === 'warning' ? 'review' : 'low'),
                'notes' => __('messages.import_center.import_note_har'),
                'source' => 'har_json',
            ], $method, $path, $entryKey);

            $requestExcerpt = $this->harRequestExcerpt($request);
            $responseExcerpt = $this->harResponseExcerpt($response);
            $items[] = $this->item('evidence', 'create', $severity, __('messages.import_center.evidence_titles.har_entry', ['operation' => $title]), __('messages.import_center.summaries.evidence_from_har'), [
                'method' => $method,
                'path' => $path,
                'external_key' => $entryKey,
                'title' => __('messages.import_center.evidence_titles.har_entry', ['operation' => $title]),
                'type' => 'http',
                'source_label' => 'Browser HAR',
                'content' => json_encode([
                    'startedDateTime' => Arr::get($entry, 'startedDateTime'),
                    'time_ms' => $timeMs,
                    'status_code' => $statusCode,
                    'mime_type' => $mimeType,
                    'url' => $this->maskSensitiveString($url),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'request_excerpt' => $requestExcerpt,
                'response_excerpt' => $responseExcerpt,
                'metadata' => [
                    'external_source' => 'har_json',
                    'external_key' => $entryKey,
                    'status_code' => $statusCode,
                    'time_ms' => $timeMs,
                    'mime_type' => $mimeType,
                ],
            ], $method, $path, $entryKey);

            if ($statusCode >= 400 || $statusCode === 0) {
                $items[] = $this->item('finding', 'create', $severity, __('messages.import_center.finding_titles.har_http_error', ['operation' => $title, 'status' => $statusCode ?: '0']), __('messages.import_center.summaries.finding_from_har'), [
                    'method' => $method,
                    'path' => $path,
                    'external_key' => $entryKey,
                    'title' => __('messages.import_center.finding_titles.har_http_error', ['operation' => $title, 'status' => $statusCode ?: '0']),
                    'source' => 'manual',
                    'severity' => $statusCode >= 500 || $statusCode === 0 ? 'high' : 'medium',
                    'status' => 'confirmed',
                    'priority' => $statusCode >= 500 || $statusCode === 0 ? 'high' : 'normal',
                    'summary' => __('messages.import_center.har_status_summary', ['status' => $statusCode ?: '0', 'operation' => $title]),
                    'reproduction_steps' => __('messages.import_center.reproduction.har', ['operation' => $title]),
                    'expected_result' => __('messages.import_center.expected.har'),
                    'actual_result' => __('messages.import_center.har_status_summary', ['status' => $statusCode ?: '0', 'operation' => $title]),
                    'recommendation' => __('messages.import_center.recommendations.har_http_error'),
                    'metadata' => [
                        'external_source' => 'har_json',
                        'external_key' => $entryKey,
                        'status_code' => $statusCode,
                        'time_ms' => $timeMs,
                        'url' => $this->maskSensitiveString($url),
                    ],
                ], $method, $path, $entryKey);
            }
        }

        return [$this->dedupeItems($items), $meta];
    }

    private function parseOpenApiJson(string $content, Project $project): array
    {
        $document = $this->decodeJson($content);
        $paths = Arr::get($document, 'paths', []);
        if (! is_array($paths) || $paths === []) {
            throw ValidationException::withMessages(['import_content' => __('messages.import_center.errors.invalid_openapi')]);
        }

        $items = [];
        $meta = [
            'name' => Arr::get($document, 'info.title', 'OpenAPI'),
            'version' => Arr::get($document, 'info.version'),
            'adapter' => 'openapi_json',
            'normalized_domains' => ['endpoint', 'assertion', 'evidence', 'finding'],
        ];

        foreach ($paths as $path => $operations) {
            if (! is_array($operations)) {
                continue;
            }

            foreach ($operations as $method => $operation) {
                $method = strtoupper((string) $method);
                if (! in_array($method, self::METHODS, true) || ! is_array($operation)) {
                    continue;
                }

                $normalizedPath = $this->normalizePath((string) $path);
                $operationTitle = (string) (Arr::get($operation, 'operationId') ?: Arr::get($operation, 'summary') ?: trim($method.' '.$normalizedPath));
                $responses = Arr::get($operation, 'responses', []);
                $preferredStatus = $this->preferredOpenApiStatus($responses);
                $contentType = $this->preferredOpenApiContentType($responses, $preferredStatus);
                $authRequired = $this->openApiOperationRequiresAuth($document, $operation);
                $externalKey = sha1('openapi|'.$method.'|'.$normalizedPath.'|'.$operationTitle);
                $endpointExists = $project->endpoints()->where('method', $method)->where('path', $normalizedPath)->exists();

                $items[] = $this->item('endpoint', $endpointExists ? 'update' : 'create', 'info', $operationTitle, __('messages.import_center.summaries.endpoint_from_openapi'), [
                    'method' => $method,
                    'path' => $normalizedPath,
                    'name' => $operationTitle,
                    'description' => (string) (Arr::get($operation, 'description') ?: Arr::get($operation, 'summary') ?: __('messages.import_center.generated_from_openapi')),
                    'tags' => implode(',', array_map('strval', Arr::wrap(Arr::get($operation, 'tags', [])))),
                    'auth_required' => $authRequired,
                    'expected_status' => $preferredStatus,
                    'expected_content_type' => $contentType,
                    'risk_level' => $authRequired ? 'review' : 'low',
                    'notes' => __('messages.import_center.import_note_openapi'),
                    'source' => 'openapi_json',
                    'metadata' => ['external_source' => 'openapi_json', 'operation_id' => Arr::get($operation, 'operationId'), 'external_key' => $externalKey],
                ], $method, $normalizedPath, $externalKey);

                if ($preferredStatus !== null) {
                    $items[] = $this->item('assertion', 'create', 'info', __('messages.import_center.assertion_titles.status_code', ['operation' => trim($method.' '.$normalizedPath), 'status' => $preferredStatus]), __('messages.import_center.summaries.assertion_from_openapi'), [
                        'method' => $method,
                        'path' => $normalizedPath,
                        'name' => __('messages.import_center.assertion_titles.status_code', ['operation' => trim($method.' '.$normalizedPath), 'status' => $preferredStatus]),
                        'rule_key' => 'status_code',
                        'operator' => 'equals',
                        'expected_value' => (string) $preferredStatus,
                        'target_path' => null,
                        'severity' => ((int) $preferredStatus >= 500) ? 'blocker' : 'warning',
                        'description' => __('messages.import_center.imported_openapi_assertion'),
                    ], $method, $normalizedPath, $externalKey.'|status');
                }

                if ($contentType !== null) {
                    $items[] = $this->item('assertion', 'create', 'info', __('messages.import_center.assertion_titles.content_type', ['operation' => trim($method.' '.$normalizedPath), 'content_type' => $contentType]), __('messages.import_center.summaries.assertion_from_openapi'), [
                        'method' => $method,
                        'path' => $normalizedPath,
                        'name' => __('messages.import_center.assertion_titles.content_type', ['operation' => trim($method.' '.$normalizedPath), 'content_type' => $contentType]),
                        'rule_key' => 'content_type_contains',
                        'operator' => 'contains',
                        'expected_value' => $contentType,
                        'target_path' => null,
                        'severity' => 'warning',
                        'description' => __('messages.import_center.imported_openapi_assertion'),
                    ], $method, $normalizedPath, $externalKey.'|content-type');
                }

                $items[] = $this->item('evidence', 'create', 'info', __('messages.import_center.evidence_titles.openapi_operation', ['operation' => trim($method.' '.$normalizedPath)]), __('messages.import_center.summaries.evidence_from_openapi'), [
                    'method' => $method,
                    'path' => $normalizedPath,
                    'external_key' => $externalKey,
                    'title' => __('messages.import_center.evidence_titles.openapi_operation', ['operation' => trim($method.' '.$normalizedPath)]),
                    'type' => 'contract',
                    'source_label' => 'OpenAPI',
                    'content' => json_encode($operation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'metadata' => ['external_source' => 'openapi_json', 'external_key' => $externalKey, 'operation_id' => Arr::get($operation, 'operationId')],
                    'repository_notes' => __('messages.import_center.repository_notes.openapi'),
                ], $method, $normalizedPath, $externalKey);

                if (! is_array($responses) || $responses === []) {
                    $items[] = $this->item('finding', 'create', 'warning', __('messages.import_center.finding_titles.openapi_missing_responses', ['operation' => trim($method.' '.$normalizedPath)]), __('messages.import_center.summaries.finding_from_openapi'), [
                        'method' => $method,
                        'path' => $normalizedPath,
                        'external_key' => $externalKey.'|missing-responses',
                        'title' => __('messages.import_center.finding_titles.openapi_missing_responses', ['operation' => trim($method.' '.$normalizedPath)]),
                        'source' => 'contract',
                        'severity' => 'medium',
                        'status' => 'confirmed',
                        'priority' => 'normal',
                        'summary' => __('messages.import_center.openapi_missing_responses_summary'),
                        'recommendation' => __('messages.import_center.recommendations.openapi_missing_responses'),
                        'metadata' => ['external_source' => 'openapi_json', 'external_key' => $externalKey.'|missing-responses'],
                    ], $method, $normalizedPath, $externalKey.'|missing-responses');
                }
            }
        }

        return [$this->dedupeItems($items), $meta];
    }

    private function parseQaCsv(string $content, Project $project): array
    {
        $rows = $this->csvRows($content);
        if (count($rows) < 2) {
            throw ValidationException::withMessages(['import_content' => __('messages.import_center.errors.invalid_csv')]);
        }

        $headers = array_map(fn ($header): string => Str::of((string) $header)->lower()->replace([' ', '-'], '_')->trim('_')->toString(), array_shift($rows));
        $items = [];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;
            $record = [];
            foreach ($headers as $index => $header) {
                $record[$header] = $row[$index] ?? null;
            }
            $this->appendQaCsvItems($items, $record, $project, $rowNumber);
        }

        return [$this->dedupeItems($items), ['name' => 'QA CSV', 'version' => null, 'adapter' => 'qa_csv', 'normalized_domains' => ['endpoint', 'finding', 'evidence']]];
    }

    private function appendQaCsvItems(array &$items, array $record, Project $project, int $rowNumber): void
    {
        [$method, $path] = $this->operationFromRecord($record);
        $entity = Str::lower((string) ($record['entity'] ?? $record['type'] ?? $record['kind'] ?? ''));
        $result = Str::lower((string) ($record['result'] ?? $record['status'] ?? $record['outcome'] ?? ''));
        $title = (string) ($record['title'] ?? $record['name'] ?? $record['summary'] ?? ($method && $path ? trim($method.' '.$path) : __('messages.import_center.unnamed_csv_item')));
        $externalKey = (string) ($record['external_key'] ?? $record['key'] ?? $record['id'] ?? sha1('qa_csv|'.$rowNumber.'|'.$title.'|'.$method.'|'.$path));
        $severity = $this->mapGenericSeverity((string) ($record['severity'] ?? $record['priority'] ?? ($this->isFailingResult($result) ? 'high' : 'info')));
        $previewSeverity = in_array($severity, ['critical', 'high'], true) ? 'blocker' : ($severity === 'medium' ? 'warning' : 'info');

        if ($method && $path) {
            $items[] = $this->item('endpoint', $project->endpoints()->where('method', $method)->where('path', $path)->exists() ? 'update' : 'create', 'info', $title, __('messages.import_center.summaries.endpoint_from_qa_csv'), [
                'method' => $method,
                'path' => $path,
                'name' => $title,
                'description' => (string) ($record['description'] ?? $record['summary'] ?? __('messages.import_center.generated_from_qa_csv')),
                'tags' => (string) ($record['tags'] ?? 'qa-csv'),
                'auth_required' => $this->truthy($record['auth_required'] ?? $record['auth'] ?? false),
                'expected_status' => $record['expected_status'] ?? $record['status_code'] ?? null,
                'expected_content_type' => $record['expected_content_type'] ?? $record['content_type'] ?? null,
                'risk_level' => $previewSeverity === 'blocker' ? 'high' : 'low',
                'notes' => __('messages.import_center.import_note_qa_csv'),
                'source' => 'qa_csv',
                'metadata' => ['external_source' => 'qa_csv', 'external_key' => $externalKey, 'row_number' => $rowNumber],
            ], $method, $path, $externalKey.'|endpoint');
        }

        if ($entity === 'endpoint') {
            return;
        }

        if (in_array($entity, ['assertion', 'rule'], true) && $method && $path) {
            $expected = (string) ($record['expected_value'] ?? $record['expected_status'] ?? $record['status_code'] ?? '200');
            $items[] = $this->item('assertion', 'create', 'info', $title, __('messages.import_center.summaries.assertion_from_qa_csv'), [
                'method' => $method,
                'path' => $path,
                'name' => $title,
                'rule_key' => (string) ($record['rule_key'] ?? 'status_code'),
                'operator' => (string) ($record['operator'] ?? 'equals'),
                'expected_value' => $expected,
                'target_path' => $record['target_path'] ?? null,
                'severity' => $previewSeverity === 'blocker' ? 'blocker' : 'warning',
                'description' => (string) ($record['description'] ?? __('messages.import_center.imported_qa_csv_assertion')),
            ], $method, $path, $externalKey.'|assertion');

            return;
        }

        $shouldCreateFinding = in_array($entity, ['finding', 'bug', 'defect', 'issue'], true) || $this->isFailingResult($result) || in_array($severity, ['critical', 'high'], true);
        if ($shouldCreateFinding) {
            $items[] = $this->item('finding', 'create', $previewSeverity === 'info' ? 'warning' : $previewSeverity, $title, (string) ($record['summary'] ?? $record['description'] ?? __('messages.import_center.summaries.finding_from_qa_csv')), [
                'method' => $method,
                'path' => $path,
                'external_key' => $externalKey,
                'title' => $title,
                'source' => in_array($entity, ['test', 'test_result', 'case'], true) ? 'test_case' : 'manual',
                'severity' => $severity === 'info' ? 'medium' : $severity,
                'status' => $this->mapGenericFindingStatus((string) ($record['finding_status'] ?? $record['status'] ?? 'open')),
                'priority' => in_array($severity, ['critical', 'high'], true) ? 'high' : 'normal',
                'owner_name' => $record['owner'] ?? $record['assignee'] ?? null,
                'summary' => (string) ($record['summary'] ?? $record['description'] ?? $title),
                'reproduction_steps' => (string) ($record['steps'] ?? $record['reproduction_steps'] ?? __('messages.import_center.reproduction.qa_csv', ['row' => $rowNumber])),
                'expected_result' => (string) ($record['expected'] ?? $record['expected_result'] ?? ''),
                'actual_result' => (string) ($record['actual'] ?? $record['actual_result'] ?? $record['result'] ?? ''),
                'recommendation' => (string) ($record['recommendation'] ?? __('messages.import_center.recommendations.qa_csv')), 
                'metadata' => ['external_source' => 'qa_csv', 'external_key' => $externalKey, 'row_number' => $rowNumber, 'result' => $result],
            ], $method, $path, $externalKey);
        }

        $hasEvidenceMaterial = in_array($entity, ['evidence', 'test', 'test_result', 'case', 'finding', 'bug', 'defect', 'issue'], true) || $result !== '' || filled($record['content'] ?? null) || filled($record['actual'] ?? null) || filled($record['actual_result'] ?? null);
        if ($hasEvidenceMaterial) {
            $items[] = $this->item('evidence', 'create', $previewSeverity, __('messages.import_center.evidence_titles.qa_csv_row', ['row' => $rowNumber, 'title' => $title]), __('messages.import_center.summaries.evidence_from_qa_csv'), [
                'method' => $method,
                'path' => $path,
                'external_key' => $externalKey,
                'title' => __('messages.import_center.evidence_titles.qa_csv_row', ['row' => $rowNumber, 'title' => $title]),
                'type' => in_array($entity, ['test', 'test_result', 'case'], true) ? 'test_result' : 'note',
                'source_label' => (string) ($record['source_label'] ?? 'QA CSV'),
                'content' => json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'request_excerpt' => (string) ($record['request'] ?? $record['request_excerpt'] ?? ''),
                'response_excerpt' => (string) ($record['response'] ?? $record['response_excerpt'] ?? ''),
                'metadata' => ['external_source' => 'qa_csv', 'external_key' => $externalKey, 'row_number' => $rowNumber, 'result' => $result],
                'repository_notes' => __('messages.import_center.repository_notes.qa_csv'),
            ], $method, $path, $externalKey.'|evidence');
        }
    }

    private function parseJiraCsv(string $content, Project $project): array
    {
        $rows = $this->csvRows($content);
        if (count($rows) < 2) {
            throw ValidationException::withMessages(['import_content' => __('messages.import_center.errors.invalid_csv')]);
        }

        $headers = array_map(fn ($header): string => Str::lower(trim((string) $header)), array_shift($rows));
        $items = [];
        foreach ($rows as $row) {
            $record = [];
            foreach ($headers as $index => $header) {
                $record[$header] = $row[$index] ?? null;
            }
            $this->appendJiraIssueItem($items, $record);
        }

        return [$this->dedupeItems($items), ['name' => 'Jira CSV', 'version' => null]];
    }

    private function parseJiraJson(string $content, Project $project): array
    {
        $document = $this->decodeJson($content);
        $issues = Arr::get($document, 'issues', is_array($document) && array_is_list($document) ? $document : []);
        $items = [];
        foreach (Arr::wrap($issues) as $issue) {
            if (! is_array($issue)) {
                continue;
            }
            $fields = Arr::get($issue, 'fields', []);
            $record = [
                'key' => Arr::get($issue, 'key'),
                'summary' => Arr::get($fields, 'summary', Arr::get($issue, 'summary')),
                'description' => Arr::get($fields, 'description', Arr::get($issue, 'description')),
                'status' => Arr::get($fields, 'status.name', Arr::get($issue, 'status')),
                'priority' => Arr::get($fields, 'priority.name', Arr::get($issue, 'priority')),
                'assignee' => Arr::get($fields, 'assignee.displayName', Arr::get($issue, 'assignee')),
                'labels' => implode(',', Arr::wrap(Arr::get($fields, 'labels', Arr::get($issue, 'labels', [])))),
                'components' => collect(Arr::wrap(Arr::get($fields, 'components', [])))->map(fn ($component) => is_array($component) ? ($component['name'] ?? '') : (string) $component)->filter()->implode(','),
            ];
            $this->appendJiraIssueItem($items, $record);
        }

        return [$this->dedupeItems($items), ['name' => Arr::get($document, 'name', 'Jira JSON'), 'version' => null]];
    }

    private function appendJiraIssueItem(array &$items, array $record): void
    {
        $key = (string) ($record['issue key'] ?? $record['key'] ?? $record['issuekey'] ?? $record['id'] ?? '');
        $summary = (string) ($record['summary'] ?? $record['title'] ?? $record['issue summary'] ?? __('messages.import_center.unnamed_jira_issue'));
        $priorityRaw = Str::lower((string) ($record['priority'] ?? 'medium'));
        $statusRaw = Str::lower((string) ($record['status'] ?? 'open'));
        $severity = str_contains($priorityRaw, 'blocker') || str_contains($priorityRaw, 'critical') ? 'critical' : (str_contains($priorityRaw, 'high') ? 'high' : (str_contains($priorityRaw, 'low') ? 'low' : 'medium'));
        $previewSeverity = in_array($severity, ['critical', 'high'], true) ? 'blocker' : 'warning';
        $externalKey = $key !== '' ? $key : sha1($summary.($record['description'] ?? ''));
        $description = $this->stringifyJiraDescription($record['description'] ?? $record['description body'] ?? null);

        $items[] = $this->item('finding', 'create', $previewSeverity, trim(($key ? $key.' · ' : '').$summary), $description ?: __('messages.import_center.summaries.finding_from_jira'), [
            'external_key' => $externalKey,
            'title' => trim(($key ? $key.' · ' : '').$summary),
            'source' => 'manual',
            'severity' => $severity,
            'status' => $this->mapJiraStatus($statusRaw),
            'priority' => $severity === 'critical' ? 'urgent' : ($severity === 'high' ? 'high' : 'normal'),
            'owner_name' => $record['assignee'] ?? null,
            'summary' => $description ?: $summary,
            'reproduction_steps' => __('messages.import_center.reproduction.jira', ['key' => $key ?: __('messages.common.not_available')]),
            'expected_result' => __('messages.import_center.expected.jira'),
            'actual_result' => $summary,
            'recommendation' => __('messages.import_center.recommendations.jira_issue'),
            'metadata' => [
                'external_source' => 'jira',
                'external_key' => $externalKey,
                'jira_status' => $record['status'] ?? null,
                'jira_priority' => $record['priority'] ?? null,
                'labels' => $record['labels'] ?? null,
                'components' => $record['components'] ?? null,
            ],
        ], null, null, $externalKey);

        $items[] = $this->item('evidence', 'create', 'info', __('messages.import_center.evidence_titles.jira_issue', ['key' => $key ?: $externalKey]), __('messages.import_center.summaries.evidence_from_jira'), [
            'external_key' => $externalKey,
            'title' => __('messages.import_center.evidence_titles.jira_issue', ['key' => $key ?: $externalKey]),
            'type' => 'note',
            'source_label' => 'Jira',
            'content' => json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'metadata' => ['external_source' => 'jira', 'external_key' => $externalKey],
        ], null, null, $externalKey);
    }

    private function applyEndpoint(Project $project, array $payload): array
    {
        $method = strtoupper((string) ($payload['method'] ?? 'GET'));
        $path = $this->normalizePath((string) ($payload['path'] ?? '/'));
        $endpoint = ! empty($payload['_target_endpoint_id'])
            ? $project->endpoints()->whereKey((int) $payload['_target_endpoint_id'])->first()
            : $project->endpoints()->where('method', $method)->where('path', $path)->first();
        $wasCreated = false;

        $values = [
            'name' => $payload['name'] ?? trim($method.' '.$path),
            'description' => $payload['description'] ?? null,
            'tags' => $payload['tags'] ?? null,
            'auth_required' => (bool) ($payload['auth_required'] ?? false),
            'expected_status' => $payload['expected_status'] ?? null,
            'expected_content_type' => $payload['expected_content_type'] ?? null,
            'risk_level' => $payload['risk_level'] ?? 'low',
            'is_active' => true,
            'excluded_from_scan' => ! in_array($method, ['GET', 'HEAD'], true),
            'notes' => $payload['notes'] ?? null,
        ];

        if (! $endpoint) {
            $endpoint = $project->endpoints()->create(array_merge($values, ['method' => $method, 'path' => $path]));
            $wasCreated = true;
        } else {
            $endpoint->fill(array_filter($values, fn ($value): bool => $value !== null));
            $endpoint->save();
        }

        return [$endpoint, $wasCreated];
    }

    private function applyAssertion(Project $project, array $payload, ?Endpoint $endpoint): EndpointAssertionRule
    {
        return EndpointAssertionRule::firstOrCreate([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint?->id,
            'name' => (string) ($payload['name'] ?? __('messages.import_center.unnamed_assertion')),
            'rule_key' => (string) ($payload['rule_key'] ?? 'status_code'),
            'operator' => (string) ($payload['operator'] ?? 'equals'),
        ], [
            'expected_value' => $payload['expected_value'] ?? null,
            'target_path' => $payload['target_path'] ?? null,
            'severity' => $payload['severity'] ?? 'warning',
            'enabled' => true,
            'description' => $payload['description'] ?? __('messages.import_center.imported_assertion_description'),
        ]);
    }

    private function applyFinding(Project $project, array $payload, ?Endpoint $endpoint): array
    {
        $externalKey = (string) ($payload['external_key'] ?? '');
        $title = (string) ($payload['title'] ?? __('messages.import_center.unnamed_finding'));
        $finding = ! empty($payload['_target_finding_id'])
            ? $project->findings()->whereKey((int) $payload['_target_finding_id'])->first()
            : $project->findings()->where('title', $title)->first();
        if (! $finding && $externalKey !== '') {
            $finding = $project->findings()->get()->first(function (Finding $candidate) use ($externalKey): bool {
                $metadata = is_array($candidate->metadata_json) ? $candidate->metadata_json : [];

                return (string) ($metadata['external_key'] ?? '') === $externalKey;
            });
        }
        $wasCreated = false;

        $values = [
            'endpoint_id' => $endpoint?->id,
            'title' => $title,
            'source' => $payload['source'] ?? 'manual',
            'severity' => $payload['severity'] ?? 'medium',
            'status' => $payload['status'] ?? 'open',
            'priority' => $payload['priority'] ?? 'normal',
            'owner_name' => $payload['owner_name'] ?? null,
            'summary' => $payload['summary'] ?? null,
            'reproduction_steps' => $payload['reproduction_steps'] ?? null,
            'expected_result' => $payload['expected_result'] ?? null,
            'actual_result' => $payload['actual_result'] ?? null,
            'recommendation' => $payload['recommendation'] ?? null,
            'evidence_required' => true,
            'retest_required' => in_array($payload['severity'] ?? 'medium', ['high', 'critical'], true),
            'metadata_json' => $payload['metadata'] ?? [],
        ];

        if (! $finding) {
            $finding = $project->findings()->create($values);
            $wasCreated = true;
        } else {
            $finding->fill(array_filter($values, fn ($value): bool => $value !== null && $value !== []));
            $finding->save();
        }

        return [$finding, $wasCreated];
    }

    private function applyEvidence(Project $project, array $payload, ?Endpoint $endpoint, ?Finding $finding, ?User $user): FindingEvidence
    {
        $repository = new EvidenceRepositoryService();
        $attributes = [
            'project_id' => $project->id,
            'finding_id' => $finding?->id,
            'endpoint_id' => $endpoint?->id,
            'title' => (string) ($payload['title'] ?? __('messages.import_center.unnamed_evidence')),
            'source_label' => $payload['source_label'] ?? 'External import',
        ];

        $values = $repository->prepareForCreate([
            'finding_id' => $finding?->id,
            'endpoint_id' => $endpoint?->id,
            'type' => $payload['type'] ?? 'note',
            'title' => $attributes['title'],
            'source_label' => $attributes['source_label'],
            'content' => $payload['content'] ?? null,
            'url' => $payload['url'] ?? null,
            'request_excerpt' => $payload['request_excerpt'] ?? null,
            'response_excerpt' => $payload['response_excerpt'] ?? null,
            'repository_notes' => $payload['repository_notes'] ?? __('messages.import_center.repository_notes.imported'),
            'metadata_json' => array_merge(['external_import' => true], $payload['metadata'] ?? []),
        ], $project, $user);

        $evidence = FindingEvidence::firstOrCreate($attributes, $values);

        if ($evidence->wasRecentlyCreated) {
            $repository->recordCreated($evidence, $user);
        } else {
            $repository->syncIntegrityState($evidence);
        }

        if (($payload['type'] ?? null) === 'test_result' && ! $evidence->test_run_id) {
            $this->linkImportedTestEvidence($project, $payload, $endpoint, $finding, $evidence, $user);
        }

        return $evidence;
    }

    private function resolveEndpointForPayload(Project $project, array $payload, array $endpointMap): ?Endpoint
    {
        $method = $payload['method'] ?? null;
        $path = $payload['path'] ?? null;
        if (! $method || ! $path) {
            return null;
        }
        $key = $this->operationKey((string) $method, (string) $path);
        if (isset($endpointMap[$key])) {
            return $endpointMap[$key];
        }

        return $project->endpoints()->where('method', strtoupper((string) $method))->where('path', $this->normalizePath((string) $path))->first();
    }

    private function resolveFindingForPayload(Project $project, array $payload, array $findingMap): ?Finding
    {
        $externalKey = (string) ($payload['external_key'] ?? '');
        if ($externalKey !== '' && isset($findingMap[$externalKey])) {
            return $findingMap[$externalKey];
        }
        if ($externalKey !== '') {
            return $project->findings()->where('metadata_json->external_key', $externalKey)->latest()->first();
        }

        return null;
    }

    private function item(string $entityType, string $action, string $severity, string $title, string $summary, array $payload, ?string $method = null, ?string $path = null, ?string $externalKey = null): array
    {
        return [
            'entity_type' => $entityType,
            'action' => $action,
            'match_status' => $action === 'update' ? 'update' : 'new',
            'apply_strategy' => in_array($action, ['skip', 'duplicate'], true) ? 'skip' : $action,
            'severity' => $severity,
            'external_key' => $externalKey ?? ($payload['external_key'] ?? null),
            'normalized_key' => null,
            'source_hash' => null,
            'method' => $method ? strtoupper($method) : null,
            'path' => $path ? $this->normalizePath($path) : null,
            'title' => Str::limit($title, 255, ''),
            'summary' => Str::limit($summary, 2000, ''),
            'payload_json' => $payload,
            'status' => 'previewed',
        ];
    }


    private function resolvePreviewMapping(Project $project, array $items): array
    {
        $resolved = [];
        $seen = [];

        foreach ($items as $item) {
            $item['normalized_key'] = $this->previewKey($item);
            $item['source_hash'] = hash('sha256', json_encode([$item['entity_type'] ?? null, $item['external_key'] ?? null, $item['method'] ?? null, $item['path'] ?? null, $item['title'] ?? null, $item['payload_json'] ?? null], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: serialize($item));

            if (isset($seen[$item['normalized_key']])) {
                $item = $this->markItem($item, 'duplicate', 'skip', 'info', __('messages.import_center.conflict_reasons.duplicate_in_source'));
                $resolved[] = $item;
                continue;
            }
            $seen[$item['normalized_key']] = true;

            $resolved[] = match ($item['entity_type'] ?? '') {
                'endpoint' => $this->resolveEndpointPreview($project, $item),
                'assertion' => $this->resolveAssertionPreview($project, $item),
                'finding' => $this->resolveFindingPreview($project, $item),
                'evidence' => $this->resolveEvidencePreview($project, $item),
                default => $item,
            };
        }

        return $resolved;
    }

    private function resolveEndpointPreview(Project $project, array $item): array
    {
        $payload = is_array($item['payload_json'] ?? null) ? $item['payload_json'] : [];
        $endpoint = $this->findEndpointCandidate($project, (string) ($item['method'] ?? $payload['method'] ?? 'GET'), (string) ($item['path'] ?? $payload['path'] ?? '/'));

        if (! $endpoint) {
            return $this->markItem($item, 'new', 'create', $item['severity'] ?? 'info');
        }

        $item['endpoint_id'] = $endpoint->id;
        $item['target_type'] = Endpoint::class;
        $item['target_id'] = $endpoint->id;
        $conflicts = [];
        $reviewNotes = [];

        if (array_key_exists('expected_status', $payload) && $payload['expected_status'] !== null && $endpoint->expected_status !== null && (int) $payload['expected_status'] !== (int) $endpoint->expected_status) {
            $conflicts[] = __('messages.import_center.conflict_reasons.expected_status', ['current' => $endpoint->expected_status, 'incoming' => $payload['expected_status']]);
        }

        if (array_key_exists('auth_required', $payload) && (bool) $payload['auth_required'] !== (bool) $endpoint->auth_required) {
            $reviewNotes[] = __('messages.import_center.conflict_reasons.auth_required');
            $payload['auth_required'] = (bool) $endpoint->auth_required;
            $item['payload_json'] = $payload;
        }

        if ($conflicts !== []) {
            $item['conflict_reason'] = trim(implode(' ', array_merge($conflicts, $reviewNotes)));
            return $this->markItem($item, 'conflict', 'skip', 'blocker', $item['conflict_reason']);
        }

        if ($reviewNotes !== []) {
            $item['conflict_reason'] = implode(' ', $reviewNotes);
            return $this->markItem($item, 'update', 'update', 'warning', $item['conflict_reason']);
        }

        return $this->markItem($item, 'update', 'update', $item['severity'] ?? 'info');
    }

    private function resolveAssertionPreview(Project $project, array $item): array
    {
        $payload = is_array($item['payload_json'] ?? null) ? $item['payload_json'] : [];
        $endpoint = $this->findEndpointCandidate($project, (string) ($item['method'] ?? $payload['method'] ?? ''), (string) ($item['path'] ?? $payload['path'] ?? ''));
        if (! $endpoint && ($item['method'] ?? null) && ($item['path'] ?? null)) {
            return $this->markItem($item, 'needs_review', 'skip', 'warning', __('messages.import_center.conflict_reasons.endpoint_missing_for_assertion'));
        }
        if ($endpoint) {
            $item['endpoint_id'] = $endpoint->id;
            $item['target_type'] = Endpoint::class;
            $item['target_id'] = $endpoint->id;
        }

        $duplicate = EndpointAssertionRule::query()
            ->where('project_id', $project->id)
            ->where('endpoint_id', $endpoint?->id)
            ->where('rule_key', (string) ($payload['rule_key'] ?? 'status_code'))
            ->where('operator', (string) ($payload['operator'] ?? 'equals'))
            ->where('expected_value', (string) ($payload['expected_value'] ?? ''))
            ->where('target_path', $payload['target_path'] ?? null)
            ->first();

        if ($duplicate) {
            $item['target_type'] = EndpointAssertionRule::class;
            $item['target_id'] = $duplicate->id;
            return $this->markItem($item, 'duplicate', 'skip', 'info', __('messages.import_center.conflict_reasons.duplicate_assertion'));
        }

        return $this->markItem($item, 'new', 'create', $item['severity'] ?? 'info');
    }

    private function resolveFindingPreview(Project $project, array $item): array
    {
        $payload = is_array($item['payload_json'] ?? null) ? $item['payload_json'] : [];
        $endpoint = $this->findEndpointCandidate($project, (string) ($item['method'] ?? $payload['method'] ?? ''), (string) ($item['path'] ?? $payload['path'] ?? ''));
        if ($endpoint) {
            $item['endpoint_id'] = $endpoint->id;
        }
        $finding = $this->findFindingCandidate($project, $payload, (string) ($item['external_key'] ?? ''));
        if ($finding) {
            $item['finding_id'] = $finding->id;
            $item['target_type'] = Finding::class;
            $item['target_id'] = $finding->id;
            return $this->markItem($item, 'update', 'update', $item['severity'] ?? 'warning');
        }

        return $this->markItem($item, 'new', 'create', $item['severity'] ?? 'warning');
    }

    private function resolveEvidencePreview(Project $project, array $item): array
    {
        $payload = is_array($item['payload_json'] ?? null) ? $item['payload_json'] : [];
        $endpoint = $this->findEndpointCandidate($project, (string) ($item['method'] ?? $payload['method'] ?? ''), (string) ($item['path'] ?? $payload['path'] ?? ''));
        if ($endpoint) {
            $item['endpoint_id'] = $endpoint->id;
        }
        $finding = $this->findFindingCandidate($project, $payload, (string) ($item['external_key'] ?? ''));
        if ($finding) {
            $item['finding_id'] = $finding->id;
        }

        $duplicate = FindingEvidence::query()
            ->where('project_id', $project->id)
            ->where('title', (string) ($payload['title'] ?? $item['title'] ?? ''))
            ->where('source_label', (string) ($payload['source_label'] ?? 'External import'))
            ->first();
        if ($duplicate) {
            $item['target_type'] = FindingEvidence::class;
            $item['target_id'] = $duplicate->id;
            return $this->markItem($item, 'duplicate', 'skip', 'info', __('messages.import_center.conflict_reasons.duplicate_evidence'));
        }

        return $this->markItem($item, 'new', 'create', $item['severity'] ?? 'info');
    }

    private function markItem(array $item, string $matchStatus, string $strategy, string $severity, ?string $reason = null): array
    {
        $item['match_status'] = $matchStatus;
        $item['apply_strategy'] = $strategy;
        $item['action'] = match ($matchStatus) {
            'duplicate', 'skip' => 'skip',
            'conflict' => 'conflict',
            'needs_review' => 'needs_review',
            'update' => 'update',
            default => 'create',
        };
        $item['severity'] = $severity;
        if ($reason !== null) {
            $item['conflict_reason'] = $reason;
        }

        return $item;
    }

    private function findEndpointCandidate(Project $project, string $method, string $path): ?Endpoint
    {
        if ($method === '' || $path === '') {
            return null;
        }
        $method = strtoupper($method);
        $normalized = $this->normalizePath($path);
        $direct = $project->endpoints()->where('method', $method)->where('path', $normalized)->first();
        if ($direct) {
            return $direct;
        }

        $signature = $this->operationSignature($method, $normalized);
        return $project->endpoints()->where('method', $method)->get()->first(function (Endpoint $endpoint) use ($signature): bool {
            return $this->operationSignature($endpoint->method, $endpoint->path) === $signature;
        });
    }

    private function findFindingCandidate(Project $project, array $payload, string $externalKey = ''): ?Finding
    {
        $externalKey = $externalKey !== '' ? $externalKey : (string) ($payload['external_key'] ?? '');
        if ($externalKey !== '') {
            $candidate = $project->findings()->get()->first(function (Finding $finding) use ($externalKey): bool {
                $metadata = is_array($finding->metadata_json) ? $finding->metadata_json : [];
                return (string) ($metadata['external_key'] ?? '') === $externalKey;
            });
            if ($candidate) {
                return $candidate;
            }
        }

        $title = (string) ($payload['title'] ?? '');
        return $title !== '' ? $project->findings()->where('title', $title)->first() : null;
    }

    private function previewKey(array $item): string
    {
        return implode('|', [
            $item['entity_type'] ?? '',
            $this->operationSignature((string) ($item['method'] ?? ''), (string) ($item['path'] ?? '')),
            $item['external_key'] ?? '',
            Str::lower((string) ($item['title'] ?? '')),
        ]);
    }

    private function operationSignature(string $method, string $path): string
    {
        $path = $this->normalizePath($path);
        $segments = array_map(function (string $segment): string {
            if ($segment === '') {
                return '';
            }
            if (preg_match('/^\{[^}]+\}$/', $segment) || str_starts_with($segment, ':')) {
                return '{param}';
            }
            if (preg_match('/^[0-9]+$/', $segment) || preg_match('/^[0-9a-f]{8}-[0-9a-f-]{13,}$/i', $segment)) {
                return '{param}';
            }
            return Str::lower($segment);
        }, explode('/', trim($path, '/')));

        return strtoupper($method).' /'.implode('/', array_filter($segments, fn ($segment): bool => $segment !== ''));
    }

    private function buildSummary(array $items, array $sourceMeta): array
    {
        $collection = collect($items);

        return [
            'item_count' => count($items),
            'endpoint_count' => $collection->where('entity_type', 'endpoint')->count(),
            'assertion_count' => $collection->where('entity_type', 'assertion')->count(),
            'finding_count' => $collection->where('entity_type', 'finding')->count(),
            'evidence_count' => $collection->where('entity_type', 'evidence')->count(),
            'warning_count' => $collection->where('severity', 'warning')->count(),
            'blocker_count' => $collection->where('severity', 'blocker')->count(),
            'new_count' => $collection->where('match_status', 'new')->count(),
            'update_count' => $collection->where('match_status', 'update')->count(),
            'duplicate_count' => $collection->where('match_status', 'duplicate')->count(),
            'conflict_count' => $collection->where('match_status', 'conflict')->count(),
            'needs_review_count' => $collection->where('match_status', 'needs_review')->count(),
            'skip_count' => $collection->where('match_status', 'skip')->count(),
            'source_meta' => $sourceMeta,
            'previewed_at' => now()->toDateTimeString(),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'has_run' => false,
            'latest_run_id' => null,
            'source_type' => null,
            'source_type_label' => __('messages.import_center.none_imported'),
            'source_name' => null,
            'source_version' => null,
            'status' => 'missing',
            'status_label' => __('messages.import_center.statuses.missing'),
            'status_tone' => 'secondary',
            'item_count' => 0,
            'endpoint_count' => 0,
            'assertion_count' => 0,
            'finding_count' => 0,
            'evidence_count' => 0,
            'warning_count' => 0,
            'blocker_count' => 0,
            'new_count' => 0,
            'update_count' => 0,
            'duplicate_count' => 0,
            'conflict_count' => 0,
            'needs_review_count' => 0,
            'previewed_at' => null,
            'applied_at' => null,
        ];
    }

    private function decodeJson(string $content): array
    {
        $content = $this->sanitizeJsonContent($content);
        $document = json_decode($content, true);
        if (! is_array($document)) {
            throw ValidationException::withMessages(['import_content' => __('messages.import_center.errors.invalid_json')]);
        }

        return $document;
    }

    private function sanitizeJsonContent(string $content): string
    {
        $content = trim($content);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $content, $matches)) {
            return trim($matches[1]);
        }

        return $content;
    }

    private function preferredOpenApiStatus(mixed $responses): ?int
    {
        if (! is_array($responses)) {
            return null;
        }
        foreach ($responses as $status => $response) {
            if (is_string($status) && preg_match('/^2\d\d$/', $status)) {
                return (int) $status;
            }
        }
        foreach ($responses as $status => $response) {
            if (is_string($status) && preg_match('/^\d{3}$/', $status)) {
                return (int) $status;
            }
        }

        return null;
    }

    private function preferredOpenApiContentType(mixed $responses, ?int $status): ?string
    {
        if (! is_array($responses)) {
            return null;
        }
        $response = $status !== null ? ($responses[(string) $status] ?? null) : null;
        if (! is_array($response)) {
            $response = collect($responses)->first(fn ($candidate): bool => is_array($candidate) && is_array($candidate['content'] ?? null));
        }
        $content = is_array($response) ? ($response['content'] ?? null) : null;
        if (! is_array($content) || $content === []) {
            return null;
        }

        return (string) array_key_first($content);
    }

    private function openApiOperationRequiresAuth(array $document, array $operation): bool
    {
        if (array_key_exists('security', $operation)) {
            return Arr::wrap($operation['security']) !== [];
        }

        return Arr::wrap($document['security'] ?? []) !== [];
    }

    private function operationFromRecord(array $record): array
    {
        $method = strtoupper((string) ($record['method'] ?? $record['http_method'] ?? ''));
        $path = (string) ($record['path'] ?? $record['url'] ?? $record['endpoint'] ?? '');
        $operation = (string) ($record['operation'] ?? $record['request'] ?? '');

        if (($method === '' || $path === '') && preg_match('/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)\s+(.+)$/i', trim($operation), $matches)) {
            $method = strtoupper($matches[1]);
            $path = $matches[2];
        }

        if (! in_array($method, self::METHODS, true)) {
            $method = '';
        }

        return [$method !== '' ? $method : null, $path !== '' ? $this->normalizePath($path) : null];
    }

    private function mapGenericSeverity(string $value): string
    {
        $value = Str::lower(trim($value));
        if (str_contains($value, 'critical') || str_contains($value, 'blocker') || str_contains($value, 'urgent')) {
            return 'critical';
        }
        if (str_contains($value, 'high') || str_contains($value, 'fail') || str_contains($value, 'error')) {
            return 'high';
        }
        if (str_contains($value, 'medium') || str_contains($value, 'warning')) {
            return 'medium';
        }
        if (str_contains($value, 'low')) {
            return 'low';
        }

        return 'info';
    }

    private function mapGenericFindingStatus(string $status): string
    {
        $status = Str::lower($status);
        if (str_contains($status, 'verified') || str_contains($status, 'closed') || str_contains($status, 'done') || str_contains($status, 'fixed')) {
            return 'fixed';
        }
        if (str_contains($status, 'progress')) {
            return 'in_progress';
        }
        if (str_contains($status, 'triage')) {
            return 'triaged';
        }
        if (str_contains($status, 'confirm') || str_contains($status, 'fail') || str_contains($status, 'error')) {
            return 'confirmed';
        }

        return 'open';
    }

    private function isFailingResult(string $result): bool
    {
        $result = Str::lower($result);

        return str_contains($result, 'fail') || str_contains($result, 'error') || str_contains($result, 'blocked') || str_contains($result, 'critical');
    }

    private function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(Str::lower((string) $value), ['1', 'true', 'yes', 'y', 'required', 'auth', 'authenticated'], true);
    }

    private function csvRows(string $content): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === false) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    private function postmanAssertions(array $node, string $method, string $path, string $name): array
    {
        $assertions = [];
        foreach (Arr::wrap($node['event'] ?? []) as $event) {
            if (! is_array($event) || ($event['listen'] ?? null) !== 'test') {
                continue;
            }
            $script = implode("\n", Arr::wrap(Arr::get($event, 'script.exec', [])));
            if (preg_match_all('/to\.have\.status\((\d{3})\)/i', $script, $matches)) {
                foreach ($matches[1] as $status) {
                    $assertions[] = [
                        'method' => $method,
                        'path' => $path,
                        'name' => __('messages.import_center.assertion_titles.status_code', ['operation' => trim($method.' '.$path), 'status' => $status]),
                        'rule_key' => 'status_code',
                        'operator' => 'equals',
                        'expected_value' => (string) $status,
                        'target_path' => null,
                        'severity' => ((int) $status >= 500) ? 'blocker' : 'warning',
                        'description' => __('messages.import_center.imported_postman_assertion', ['name' => $name]),
                    ];
                }
            }
            if (preg_match_all('/responseTime\)\.to\.be\.below\((\d+)\)/i', $script, $matches)) {
                foreach ($matches[1] as $maxMs) {
                    $assertions[] = [
                        'method' => $method,
                        'path' => $path,
                        'name' => __('messages.import_center.assertion_titles.response_time', ['operation' => trim($method.' '.$path), 'ms' => $maxMs]),
                        'rule_key' => 'max_response_time',
                        'operator' => 'less_than_or_equal',
                        'expected_value' => (string) $maxMs,
                        'target_path' => null,
                        'severity' => 'warning',
                        'description' => __('messages.import_center.imported_postman_assertion', ['name' => $name]),
                    ];
                }
            }
        }

        return $assertions;
    }

    private function firstStatusAssertion(array $node): ?int
    {
        foreach ($this->postmanAssertions($node, 'GET', '/', '') as $assertion) {
            if (($assertion['rule_key'] ?? null) === 'status_code') {
                return (int) $assertion['expected_value'];
            }
        }

        return null;
    }

    private function pathFromPostmanUrl(mixed $url): string
    {
        if (is_string($url)) {
            return $this->normalizePath($url);
        }
        if (is_array($url)) {
            if (isset($url['path'])) {
                $path = is_array($url['path']) ? implode('/', array_map('strval', $url['path'])) : (string) $url['path'];
                return $this->normalizePath($path);
            }
            if (isset($url['raw'])) {
                return $this->normalizePath((string) $url['raw']);
            }
        }

        return '/';
    }


    private function harHasAuthLikeHeaders(array $request): bool
    {
        foreach (Arr::wrap($request['headers'] ?? []) as $header) {
            if (! is_array($header)) {
                continue;
            }
            $name = Str::lower((string) ($header['name'] ?? ''));
            if (in_array($name, ['authorization', 'cookie', 'x-api-key', 'x-auth-token'], true) && filled($header['value'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function harRequestExcerpt(array $request): string
    {
        $lines = [trim(strtoupper((string) ($request['method'] ?? 'GET')).' '.$this->maskSensitiveString((string) ($request['url'] ?? '/')))];
        foreach (Arr::wrap($request['headers'] ?? []) as $header) {
            if (! is_array($header)) {
                continue;
            }
            $name = (string) ($header['name'] ?? '');
            $value = $this->maskSensitiveHeader($name, (string) ($header['value'] ?? ''));
            $lines[] = $name.': '.$value;
        }
        $body = Arr::get($request, 'postData.text');
        if (is_string($body) && $body !== '') {
            $lines[] = '';
            $lines[] = Str::limit($this->maskSensitiveString($body), 3000, '');
        }

        return Str::limit(implode("\n", array_filter($lines, fn ($line): bool => $line !== '')), 6000, '');
    }

    private function harResponseExcerpt(array $response): string
    {
        $lines = ['HTTP '.(string) ($response['status'] ?? '0').' '.(string) ($response['statusText'] ?? '')];
        foreach (Arr::wrap($response['headers'] ?? []) as $header) {
            if (! is_array($header)) {
                continue;
            }
            $name = (string) ($header['name'] ?? '');
            $value = $this->maskSensitiveHeader($name, (string) ($header['value'] ?? ''));
            $lines[] = $name.': '.$value;
        }
        $body = Arr::get($response, 'content.text');
        if (is_string($body) && $body !== '') {
            $lines[] = '';
            $lines[] = Str::limit($this->maskSensitiveString($body), 3000, '');
        }

        return Str::limit(implode("\n", array_filter($lines, fn ($line): bool => $line !== '')), 6000, '');
    }

    private function maskSensitiveHeader(string $name, string $value): string
    {
        $lower = Str::lower($name);
        if (in_array($lower, ['authorization', 'cookie', 'set-cookie', 'x-api-key', 'x-auth-token'], true)) {
            return '[masked]';
        }

        return $this->maskSensitiveString($value);
    }

    private function maskSensitiveString(string $value): string
    {
        $value = preg_replace('/(authorization|token|access_token|api_key|password|secret)=([^&\s]+)/i', '$1=[masked]', $value) ?? $value;
        $value = preg_replace('/Bearer\s+[A-Za-z0-9._\-]+/i', 'Bearer [masked]', $value) ?? $value;

        return $value;
    }

    private function hasAuthorizationHeader(array $request): bool
    {
        foreach (Arr::wrap($request['header'] ?? []) as $header) {
            if (is_array($header) && Str::lower((string) ($header['key'] ?? '')) === 'authorization' && filled($header['value'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        $path = preg_replace('/\{\{[^}]+\}\}/', '', $path) ?: $path;
        $path = preg_replace('#^https?://[^/]+#i', '', $path) ?: $path;
        $path = preg_replace('#\?.*$#', '', $path) ?: $path;
        $path = preg_replace('#/+#', '/', $path) ?: $path;
        $path = '/'.ltrim($path, '/');

        return $path !== '/' ? rtrim($path, '/') : '/';
    }

    private function operationKey(string $method, string $path): string
    {
        return strtoupper($method).' '.$this->normalizePath($path);
    }

    private function dedupeItems(array $items): array
    {
        $seen = [];
        $deduped = [];
        foreach ($items as $item) {
            $key = implode('|', [
                $item['entity_type'] ?? '',
                $item['method'] ?? '',
                $item['path'] ?? '',
                $item['external_key'] ?? '',
                $item['title'] ?? '',
            ]);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $deduped[] = $item;
        }

        return $deduped;
    }

    private function mapJiraStatus(string $status): string
    {
        if (str_contains($status, 'done') || str_contains($status, 'closed') || str_contains($status, 'resolved')) {
            return 'fixed';
        }
        if (str_contains($status, 'progress')) {
            return 'in_progress';
        }
        if (str_contains($status, 'triage')) {
            return 'triaged';
        }

        return 'open';
    }

    private function stringifyJiraDescription(mixed $description): string
    {
        if (is_string($description)) {
            return $description;
        }
        if (is_array($description)) {
            return Str::limit(json_encode($description, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', 6000, '');
        }

        return '';
    }
}
