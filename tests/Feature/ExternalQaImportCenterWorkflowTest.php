<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\ExternalImportRun;
use App\Models\Project;
use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalQaImportCenterWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_center_page_exposes_preview_workflow(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('projects.import-center.index', $project))
            ->assertOk()
            ->assertSee(__('messages.import_center.title'))
            ->assertSee(__('messages.import_center.source_types.postman_collection'))
            ->assertSee(__('messages.import_center.source_types.newman_json'))
            ->assertSee(__('messages.import_center.source_types.jira_csv'))
            ->assertSee(__('messages.import_center.source_types.openapi_json'))
            ->assertSee(__('messages.import_center.source_types.qa_csv'));
    }

    public function test_postman_collection_preview_and_apply_creates_endpoints_and_assertions(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $collection = json_encode([
            'info' => ['name' => 'Customer API', 'version' => '1.0.0'],
            'item' => [[
                'name' => 'Health check',
                'request' => [
                    'method' => 'GET',
                    'url' => ['raw' => '{{baseUrl}}/health', 'path' => ['health']],
                    'header' => [['key' => 'Accept', 'value' => 'application/json']],
                ],
                'event' => [[
                    'listen' => 'test',
                    'script' => ['exec' => ['pm.response.to.have.status(200);', 'pm.expect(pm.response.responseTime).to.be.below(500);']],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $response = $this->actingAs($user)->post(route('projects.import-center.store', $project), [
            'source_type' => 'postman_collection',
            'source_name' => 'Customer API Collection',
            'import_content' => $collection,
            'confirm_preview' => '1',
        ]);

        $run = $project->externalImportRuns()->firstOrFail();
        $response->assertRedirect(route('projects.import-center.show', [$project, $run]));

        $this->assertSame('previewed', $run->status);
        $this->assertSame(1, $run->endpoint_count);
        $this->assertSame(2, $run->assertion_count);
        $this->assertDatabaseHas('external_import_items', ['external_import_run_id' => $run->id, 'entity_type' => 'endpoint', 'method' => 'GET', 'path' => '/health']);

        $this->actingAs($user)->post(route('projects.import-center.apply', [$project, $run]), ['confirm_apply' => '1'])->assertRedirect();

        $this->assertDatabaseHas('endpoints', ['project_id' => $project->id, 'method' => 'GET', 'path' => '/health']);
        $this->assertSame(2, $project->assertionRules()->count());
        $this->assertSame('applied', $run->fresh()->status);
    }

    public function test_newman_json_import_creates_failed_assertion_finding_and_evidence(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        Endpoint::factory()->create(['project_id' => $project->id, 'method' => 'GET', 'path' => '/checkout']);

        $newman = json_encode([
            'collection' => ['info' => ['name' => 'Checkout Collection']],
            'run' => [
                'executions' => [[
                    'item' => ['name' => 'Checkout status'],
                    'request' => ['method' => 'GET', 'url' => ['raw' => '{{baseUrl}}/checkout', 'path' => ['checkout']]],
                    'response' => ['code' => 500, 'responseTime' => 900, 'body' => '{"error":"timeout"}'],
                    'assertions' => [[
                        'assertion' => 'Status code is 200',
                        'error' => ['message' => 'expected 500 to equal 200'],
                    ]],
                ]],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)->post(route('projects.import-center.store', $project), [
            'source_type' => 'newman_json',
            'source_name' => 'Newman checkout run',
            'import_content' => $newman,
            'confirm_preview' => '1',
        ])->assertRedirect();

        $run = $project->externalImportRuns()->firstOrFail();
        $this->assertSame(1, $run->finding_count);
        $this->assertSame(1, $run->evidence_count);
        $this->assertSame(2, $run->blocker_count);

        $this->actingAs($user)->post(route('projects.import-center.apply', [$project, $run]), ['confirm_apply' => '1'])->assertRedirect();

        $this->assertDatabaseHas('findings', ['project_id' => $project->id, 'severity' => 'high', 'source' => 'assertion']);
        $this->assertDatabaseHas('finding_evidence', ['project_id' => $project->id, 'source_label' => 'Newman JSON']);
    }

    public function test_jira_csv_import_creates_finding_preview(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $csv = "Issue key,Summary,Priority,Status,Description,Assignee\nAPT-12,Billing regression,High,Open,Checkout returns wrong tax,QA Lead\n";

        $this->actingAs($user)->post(route('projects.import-center.store', $project), [
            'source_type' => 'jira_csv',
            'source_name' => 'Jira sprint export',
            'import_content' => $csv,
            'confirm_preview' => '1',
        ])->assertRedirect();

        $run = ExternalImportRun::query()->firstOrFail();
        $this->assertSame(1, $run->finding_count);
        $this->assertSame(1, $run->evidence_count);
        $this->assertDatabaseHas('external_import_items', ['external_import_run_id' => $run->id, 'external_key' => 'APT-12', 'entity_type' => 'finding']);
    }

    public function test_openapi_json_adapter_normalizes_contract_into_endpoints_assertions_and_evidence(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $openApi = json_encode([
            'openapi' => '3.0.3',
            'info' => ['title' => 'Customer API', 'version' => '1.2.0'],
            'paths' => [
                '/customers' => [
                    'get' => [
                        'operationId' => 'listCustomers',
                        'summary' => 'List customers',
                        'responses' => [
                            '200' => ['description' => 'OK', 'content' => ['application/json' => ['schema' => ['type' => 'array']]]],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)->post(route('projects.import-center.store', $project), [
            'source_type' => 'openapi_json',
            'source_name' => 'Customer API Contract',
            'import_content' => $openApi,
            'confirm_preview' => '1',
        ])->assertRedirect();

        $run = ExternalImportRun::query()->firstOrFail();
        $this->assertSame(1, $run->endpoint_count);
        $this->assertSame(2, $run->assertion_count);
        $this->assertSame(1, $run->evidence_count);
        $this->assertDatabaseHas('external_import_items', ['external_import_run_id' => $run->id, 'entity_type' => 'evidence', 'path' => '/customers']);

        $this->actingAs($user)->post(route('projects.import-center.apply', [$project, $run]), ['confirm_apply' => '1'])->assertRedirect();

        $this->assertDatabaseHas('endpoints', ['project_id' => $project->id, 'method' => 'GET', 'path' => '/customers']);
        $this->assertDatabaseHas('finding_evidence', ['project_id' => $project->id, 'type' => 'contract', 'source_label' => 'OpenAPI']);
    }

    public function test_generic_qa_csv_adapter_normalizes_failed_test_row_into_finding_and_test_evidence(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $csv = "entity,method,path,title,result,severity,expected,actual,source_label\n".
            "test_result,GET,/customers,List customers smoke test,fail,high,HTTP 200,HTTP 500,Manual QA\n";

        $this->actingAs($user)->post(route('projects.import-center.store', $project), [
            'source_type' => 'qa_csv',
            'source_name' => 'Manual QA evidence sheet',
            'import_content' => $csv,
            'confirm_preview' => '1',
        ])->assertRedirect();

        $run = ExternalImportRun::query()->firstOrFail();
        $this->assertSame(1, $run->endpoint_count);
        $this->assertSame(1, $run->finding_count);
        $this->assertSame(1, $run->evidence_count);

        $this->actingAs($user)->post(route('projects.import-center.apply', [$project, $run]), ['confirm_apply' => '1'])->assertRedirect();

        $this->assertDatabaseHas('findings', ['project_id' => $project->id, 'source' => 'test_case', 'severity' => 'high']);
        $this->assertDatabaseHas('finding_evidence', ['project_id' => $project->id, 'type' => 'test_result', 'source_label' => 'Manual QA']);
        $this->assertDatabaseHas('evidence_lifecycle_events', ['project_id' => $project->id, 'action' => 'created']);
    }

    public function test_release_readiness_includes_external_import_checks(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('projects.release-readiness.index', $project))
            ->assertOk()
            ->assertSee(__('messages.release_readiness.external_import.title'))
            ->assertSee(__('messages.release_readiness.checks.external_qa_import_present'));
    }
    public function test_import_center_uses_semantic_import_icons(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('projects.import-center.index', $project))
            ->assertOk()
            ->assertSee('data-lucide="brackets-contain"', false)
            ->assertSee('data-lucide="file-search"', false)
            ->assertSee('data-lucide="file-clock"', false)
            ->assertDontSee('data-lucide="package-plus"', false)
            ->assertDontSee('data-lucide="package-search"', false);
    }


    public function test_import_preview_marks_existing_endpoint_conflicts_and_blocks_apply(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        Endpoint::factory()->create(['project_id' => $project->id, 'method' => 'GET', 'path' => '/users/{id}', 'expected_status' => 200, 'auth_required' => true]);

        $collection = json_encode([
            'info' => ['name' => 'Conflict Collection'],
            'item' => [[
                'name' => 'User detail',
                'request' => ['method' => 'GET', 'url' => ['raw' => '{{baseUrl}}/users/:id', 'path' => ['users', ':id']]],
                'event' => [[
                    'listen' => 'test',
                    'script' => ['exec' => ['pm.response.to.have.status(404);']],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)->post(route('projects.import-center.store', $project), [
            'source_type' => 'postman_collection',
            'source_name' => 'Conflict Collection',
            'import_content' => $collection,
            'confirm_preview' => '1',
        ])->assertRedirect();

        $run = ExternalImportRun::query()->firstOrFail();
        $this->assertSame(1, (int) ($run->summary['conflict_count'] ?? 0));
        $this->assertDatabaseHas('external_import_items', [
            'external_import_run_id' => $run->id,
            'entity_type' => 'endpoint',
            'match_status' => 'conflict',
            'apply_strategy' => 'skip',
        ]);

        $this->actingAs($user)
            ->post(route('projects.import-center.apply', [$project, $run]), ['confirm_apply' => '1'])
            ->assertSessionHasErrors('confirm_apply');
    }




    public function test_import_auth_requirement_drift_is_review_note_not_blocking_conflict(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $endpoint = Endpoint::factory()->create([
            'project_id' => $project->id,
            'method' => 'GET',
            'path' => '/users/{id}',
            'expected_status' => 200,
            'auth_required' => true,
        ]);

        $collection = json_encode([
            'info' => ['name' => 'Path Match Collection'],
            'item' => [[
                'name' => 'User detail',
                'request' => ['method' => 'GET', 'url' => ['raw' => '{{baseUrl}}/users/:id', 'path' => ['users', ':id']]],
                'event' => [[
                    'listen' => 'test',
                    'script' => ['exec' => ['pm.response.to.have.status(200);']],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)->post(route('projects.import-center.store', $project), [
            'source_type' => 'postman_collection',
            'source_name' => 'Path Match Collection',
            'import_content' => $collection,
            'confirm_preview' => '1',
        ])->assertRedirect();

        $run = ExternalImportRun::query()->firstOrFail();
        $this->assertSame(0, (int) ($run->summary['conflict_count'] ?? 0));
        $this->assertDatabaseHas('external_import_items', [
            'external_import_run_id' => $run->id,
            'entity_type' => 'endpoint',
            'match_status' => 'update',
            'apply_strategy' => 'update',
            'target_type' => Endpoint::class,
            'target_id' => $endpoint->id,
        ]);

        $this->actingAs($user)
            ->post(route('projects.import-center.apply', [$project, $run]), ['confirm_apply' => '1'])
            ->assertRedirect();

        $this->assertTrue((bool) $endpoint->fresh()->auth_required);
        $this->assertSame(1, $project->assertionRules()->count());

        $this->actingAs($user)->post(route('projects.import-center.store', $project), [
            'source_type' => 'postman_collection',
            'source_name' => 'Path Match Collection Duplicate',
            'import_content' => $collection,
            'confirm_preview' => '1',
        ])->assertRedirect();

        $secondRun = ExternalImportRun::query()->latest('id')->firstOrFail();
        $this->assertDatabaseHas('external_import_items', [
            'external_import_run_id' => $secondRun->id,
            'entity_type' => 'assertion',
            'match_status' => 'duplicate',
            'apply_strategy' => 'skip',
        ]);
    }

    public function test_har_json_import_creates_browser_network_evidence_and_finding(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $har = json_encode([
            'log' => [
                'version' => '1.2',
                'creator' => ['name' => 'Chrome DevTools'],
                'browser' => ['name' => 'Chrome'],
                'entries' => [[
                    'startedDateTime' => '2026-06-17T10:00:00.000Z',
                    'time' => 321,
                    'request' => [
                        'method' => 'GET',
                        'url' => 'https://api.example.test/orders/42?access_token=secret',
                        'headers' => [['name' => 'Authorization', 'value' => 'Bearer secret-token']],
                    ],
                    'response' => [
                        'status' => 500,
                        'statusText' => 'Internal Server Error',
                        'headers' => [['name' => 'Content-Type', 'value' => 'application/json']],
                        'content' => ['mimeType' => 'application/json', 'text' => '{"error":"boom"}'],
                    ],
                ]],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)->post(route('projects.import-center.store', $project), [
            'source_type' => 'har_json',
            'source_name' => 'Browser checkout HAR',
            'import_content' => $har,
            'confirm_preview' => '1',
        ])->assertRedirect();

        $run = ExternalImportRun::query()->firstOrFail();
        $this->assertSame(1, $run->endpoint_count);
        $this->assertSame(1, $run->evidence_count);
        $this->assertSame(1, $run->finding_count);
        $this->assertDatabaseHas('external_import_items', ['external_import_run_id' => $run->id, 'entity_type' => 'evidence', 'path' => '/orders/42']);

        $this->actingAs($user)->post(route('projects.import-center.apply', [$project, $run]), ['confirm_apply' => '1'])->assertRedirect();

        $this->assertDatabaseHas('endpoints', ['project_id' => $project->id, 'method' => 'GET', 'path' => '/orders/42']);
        $this->assertDatabaseHas('finding_evidence', ['project_id' => $project->id, 'source_label' => 'Browser HAR']);
        $this->assertDatabaseHas('findings', ['project_id' => $project->id, 'severity' => 'high']);
    }

}
