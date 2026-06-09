<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\TestCase as QaTestCase;
use App\Models\TestCaseResult;
use App\Models\TestSuite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostmanGlobalsAndNewmanImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_postman_globals_are_used_during_collection_preview(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = $this->project($admin);

        $this->actingAs($admin)
            ->post(route('projects.endpoints.import.preview', $project), [
                'format' => 'postman',
                'import_source' => 'paste',
                'payload' => $this->postmanPayloadWithGlobals(),
                'postman_globals_payload' => $this->postmanGlobalsPayload(),
            ])
            ->assertOk()
            ->assertSee('/{apiVersion}/tenants/{tenantId}/users')
            ->assertSee(__('messages.import_preview.postman_globals'))
            ->assertDontSee('global-secret-token')
            ->assertDontSee('messages.endpoints')
            ->assertDontSee('messages.import_preview');
    }

    public function test_newman_json_import_creates_test_results_and_findings(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = $this->project($admin);
        $endpoint = $project->endpoints()->create([
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/999999',
            'name' => 'Missing user',
            'risk_level' => Endpoint::RISK_REVIEW,
            'auth_required' => false,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('projects.newman-import.preview', $project), [
                'format' => 'json',
                'payload' => $this->newmanJsonPayload(),
                'create_findings' => true,
            ])
            ->assertOk()
            ->assertSee(__('messages.newman_import.preview_title'))
            ->assertSee('GET /users/999999')
            ->assertSee(__('messages.test_cases.run_statuses.fail'));

        $this->actingAs($admin)
            ->post(route('projects.newman-import.store', $project), [
                'format' => 'json',
                'payload' => $this->newmanJsonPayload(),
                'create_findings' => true,
            ])
            ->assertRedirect(route('projects.test-execution.index', $project));

        $suite = TestSuite::query()->where('project_id', $project->id)->where('name', 'Newman - Users')->firstOrFail();
        $case = QaTestCase::query()->where('project_id', $project->id)->where('test_suite_id', $suite->id)->where('title', 'GET /users/999999')->firstOrFail();
        $this->assertSame($endpoint->id, $case->endpoint_id);
        $this->assertSame(TestCaseResult::STATUS_FAIL, $case->last_run_status);

        $this->assertDatabaseHas('test_case_results', [
            'project_id' => $project->id,
            'test_case_id' => $case->id,
            'status' => TestCaseResult::STATUS_FAIL,
        ]);
        $this->assertDatabaseHas('findings', [
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'test_case_id' => $case->id,
            'source' => Finding::SOURCE_TEST_CASE,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_OPEN,
        ]);
    }

    public function test_newman_junit_import_form_has_no_raw_translation_keys(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = $this->project($admin);

        $this->actingAs($admin)
            ->get(route('projects.newman-import.create', $project))
            ->assertOk()
            ->assertSee(__('messages.newman_import.title'))
            ->assertSee(__('messages.newman_import.format_junit'))
            ->assertDontSee('messages.newman_import');
    }

    private function project(User $admin): Project
    {
        return Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Postman Newman API',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);
    }

    private function postmanPayloadWithGlobals(): string
    {
        return <<<'JSON'
{
  "info": {"name": "Globals Demo", "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"},
  "item": [
    {"name": "Global user", "request": {"method": "GET", "header": [{"key": "Authorization", "value": "Bearer {{globalToken}}"}], "url": "{{baseUrl}}/{{apiVersion}}/tenants/{{tenantId}}/users"}}
  ]
}
JSON;
    }

    private function postmanGlobalsPayload(): string
    {
        return <<<'JSON'
{
  "name": "Workspace Globals",
  "values": [
    {"key": "baseUrl", "value": "https://api.example.test", "enabled": true},
    {"key": "apiVersion", "value": "v1", "enabled": true},
    {"key": "tenantId", "value": "acme", "enabled": true},
    {"key": "globalToken", "value": "global-secret-token", "enabled": true}
  ]
}
JSON;
    }

    private function newmanJsonPayload(): string
    {
        return <<<'JSON'
{
  "collection": {"info": {"name": "Newman Demo"}},
  "run": {
    "executions": [
      {
        "item": {"name": "Read missing user", "path": ["Users", "Read missing user"]},
        "request": {"method": "GET", "url": {"raw": "https://api.example.test/users/999999", "path": ["users", "999999"]}},
        "response": {"code": 404, "status": "Not Found", "responseTime": 90},
        "assertions": [
          {"assertion": "Status code is 200", "error": {"message": "expected 404 to equal 200"}}
        ]
      }
    ]
  }
}
JSON;
    }
}
