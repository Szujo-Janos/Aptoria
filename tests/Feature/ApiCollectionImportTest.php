<?php

namespace Tests\Feature;

use App\Models\AuthProfile;
use App\Models\Endpoint;
use App\Models\EndpointAssertionRule;
use App\Models\Environment;
use App\Models\Project;
use App\Models\TestCase as QaTestCase;
use App\Models\TestSuite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCollectionImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_postman_collection_preview_extracts_endpoint_request_metadata(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = $this->project($admin);

        $this->actingAs($admin)
            ->post(route('projects.endpoints.import.preview', $project), [
                'format' => 'postman',
                'import_source' => 'paste',
                'payload' => $this->postmanPayload(),
            ])
            ->assertOk()
            ->assertSee(__('messages.import_preview.title'))
            ->assertSee('/users')
            ->assertSee('/users/{id}')
            ->assertSee(__('messages.endpoints.request_metadata'))
            ->assertSee('request header')
            ->assertSee('raw')
            ->assertDontSee('super-secret-token');
    }

    public function test_postman_collection_import_creates_endpoints_with_headers_body_and_path_params(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = $this->project($admin);

        $this->actingAs($admin)
            ->post(route('projects.endpoints.import', $project), [
                'format' => 'postman',
                'import_source' => 'paste',
                'payload' => $this->postmanPayload(),
            ])
            ->assertRedirect(route('projects.endpoints.index', $project));

        $this->assertDatabaseHas('endpoints', [
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/users',
            'name' => 'List users',
            'expected_status' => 200,
            'expected_content_type' => 'application/json',
        ]);

        $this->assertDatabaseHas('endpoints', [
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_PATCH,
            'path' => '/users/{id}',
            'auth_required' => true,
            'request_body_type' => 'raw',
        ]);

        $endpoint = $project->endpoints()->where('method', Endpoint::METHOD_PATCH)->where('path', '/users/{id}')->firstOrFail();

        $this->assertIsArray($endpoint->request_headers);
        $this->assertSame('Authorization', $endpoint->request_headers[0]['key']);
        $this->assertStringNotContainsString('super-secret-token', $endpoint->request_headers[0]['value']);
        $this->assertStringContainsString('"token":"***"', (string) $endpoint->request_body_preview);
        $this->assertStringContainsString('Postman collection', (string) $endpoint->qa_notes);
    }


    public function test_postman_collection_with_environment_import_creates_environment_auth_assertions_and_suite(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = $this->project($admin);

        $this->actingAs($admin)
            ->post(route('projects.endpoints.import.preview', $project), [
                'format' => 'postman',
                'import_source' => 'paste',
                'payload' => $this->postmanMaxPayload(),
                'postman_environment_payload' => $this->postmanEnvironmentPayload(),
                'postman_create_environment' => true,
                'postman_create_auth_profile' => true,
                'postman_create_assertions' => true,
                'postman_create_test_suites' => true,
            ])
            ->assertOk()
            ->assertSee(__('messages.import_preview.postman_plan_title'))
            ->assertSee('Demo Postman Environment')
            ->assertSee('Postman - Users')
            ->assertSee(__('messages.import_preview.postman_assertions'))
            ->assertDontSee('demo-postman-token')
            ->assertDontSee('super-secret-token');

        $this->actingAs($admin)
            ->post(route('projects.endpoints.import', $project), [
                'format' => 'postman',
                'import_source' => 'paste',
                'payload' => $this->postmanMaxPayload(),
                'postman_environment_payload' => $this->postmanEnvironmentPayload(),
                'postman_create_environment' => true,
                'postman_create_auth_profile' => true,
                'postman_create_assertions' => true,
                'postman_create_test_suites' => true,
            ])
            ->assertRedirect(route('projects.endpoints.index', $project));

        $environment = Environment::query()->where('project_id', $project->id)->where('name', 'Demo Postman Environment')->firstOrFail();
        $this->assertSame('https://jsonplaceholder.typicode.com', $environment->base_url);

        $authProfile = AuthProfile::query()->where('project_id', $project->id)->where('type', AuthProfile::TYPE_BEARER)->firstOrFail();
        $this->assertSame('demo-postman-token', $authProfile->encrypted_token);

        $endpoint = Endpoint::query()->where('project_id', $project->id)->where('method', Endpoint::METHOD_GET)->where('path', '/users/{userId}')->firstOrFail();
        $this->assertSame($environment->id, $endpoint->environment_id);
        $this->assertSame($authProfile->id, $endpoint->auth_profile_id);
        $this->assertTrue($endpoint->auth_required);

        $this->assertDatabaseHas('endpoint_assertion_rules', [
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'rule_key' => EndpointAssertionRule::RULE_STATUS_CODE,
            'expected_value' => '200',
        ]);
        $this->assertDatabaseHas('endpoint_assertion_rules', [
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'rule_key' => EndpointAssertionRule::RULE_JSON_PATH_VALUE,
            'target_path' => '$.id',
        ]);

        $suite = TestSuite::query()->where('project_id', $project->id)->where('name', 'Postman - Users')->firstOrFail();
        $this->assertDatabaseHas('test_cases', [
            'project_id' => $project->id,
            'test_suite_id' => $suite->id,
            'endpoint_id' => $endpoint->id,
            'type' => QaTestCase::TYPE_HYBRID,
            'status' => QaTestCase::STATUS_READY,
        ]);
    }

    public function test_collection_import_form_renders_postman_option_without_raw_translation_keys(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = $this->project($admin);

        $this->actingAs($admin)
            ->get(route('projects.endpoints.import.form', $project))
            ->assertOk()
            ->assertSee(__('messages.endpoints.import_format_postman'))
            ->assertSee(__('messages.endpoints.use_postman_sample'))
            ->assertSee(__('messages.endpoints.postman_advanced_title'))
            ->assertSee(__('messages.endpoints.use_postman_environment_sample'))
            ->assertDontSee('messages.endpoints')
            ->assertDontSee('messages.import_preview');
    }

    private function project(User $admin): Project
    {
        return Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Collection Import API',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);
    }

    private function postmanPayload(): string
    {
        return <<<'JSON'
{
  "info": {
    "name": "Collection Import Demo",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Users",
      "item": [
        {
          "name": "List users",
          "request": {
            "method": "GET",
            "header": [
              {"key": "Accept", "value": "application/json"}
            ],
            "url": {
              "raw": "{{baseUrl}}/users",
              "host": ["{{baseUrl}}"],
              "path": ["users"]
            }
          },
          "response": [
            {"name": "OK", "code": 200, "header": [{"key": "Content-Type", "value": "application/json"}]}
          ]
        },
        {
          "name": "Update user",
          "request": {
            "method": "PATCH",
            "header": [
              {"key": "Authorization", "value": "Bearer super-secret-token"},
              {"key": "Content-Type", "value": "application/json"}
            ],
            "body": {
              "mode": "raw",
              "raw": "{\"name\":\"Demo User\",\"token\":\"super-secret-token\"}"
            },
            "url": "{{baseUrl}}/users/:id"
          },
          "response": [
            {"name": "OK", "code": 200, "header": [{"key": "Content-Type", "value": "application/json"}]}
          ]
        }
      ]
    }
  ]
}
JSON;
    }

    private function postmanEnvironmentPayload(): string
    {
        return <<<'JSON'
{
  "name": "Demo Postman Environment",
  "values": [
    {"key": "baseUrl", "value": "https://jsonplaceholder.typicode.com", "enabled": true},
    {"key": "token", "value": "demo-postman-token", "enabled": true},
    {"key": "userId", "value": "1", "enabled": true}
  ]
}
JSON;
    }

    private function postmanMaxPayload(): string
    {
        return <<<'JSON'
{
  "info": {
    "name": "Max Postman Demo",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "auth": {
    "type": "bearer",
    "bearer": [{"key": "token", "value": "{{token}}", "type": "string"}]
  },
  "item": [
    {
      "name": "Users",
      "item": [
        {
          "name": "Read user",
          "request": {
            "method": "GET",
            "header": [
              {"key": "Accept", "value": "application/json"}
            ],
            "url": "{{baseUrl}}/users/{{userId}}"
          },
          "event": [
            {"listen": "test", "script": {"exec": [
              "pm.response.to.have.status(200);",
              "pm.expect(pm.response.responseTime).to.be.below(1000);",
              "pm.response.to.have.header('Content-Type');",
              "var jsonData = pm.response.json();",
              "pm.expect(jsonData.id).to.exist;"
            ]}}
          ],
          "response": [
            {"name": "OK", "code": 200, "header": [{"key": "Content-Type", "value": "application/json"}], "body": "{\"id\":1}"}
          ]
        }
      ]
    }
  ]
}
JSON;
    }

}
