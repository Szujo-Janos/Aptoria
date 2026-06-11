<?php

namespace Tests\Feature;

use App\Models\ContractValidationResult;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\User;
use App\Services\Contracts\ContractRealityService;
use App\Services\Contracts\OpenApiContractValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractRealityCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_reality_detects_auth_mismatch_and_undocumented_response_fields(): void
    {
        $admin = User::query()->create([
            'name' => 'Contract Reality Admin',
            'email' => 'contract-reality@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Contract Reality API',
            'slug' => 'contract-reality-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/{id}',
            'name' => 'Show user',
            'auth_required' => false,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $scanRun = $project->scanRuns()->create([
            'status' => ScanRun::STATUS_COMPLETED,
            'mode' => 'manual',
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $scanRun->results()->create([
            'endpoint_id' => $endpoint->id,
            'method' => $endpoint->method,
            'url' => $project->base_url.'/users/123',
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 200,
            'content_type' => 'application/json',
            'body_preview' => '{"id":123,"name":"Ada","internal_status":"debug"}',
            'auth_applied' => false,
        ]);

        $payload = <<<'JSON'
{
  "openapi": "3.0.0",
  "security": [{"bearerAuth": []}],
  "components": {
    "securitySchemes": {
      "bearerAuth": {"type": "http", "scheme": "bearer"}
    }
  },
  "paths": {
    "/users/{id}": {
      "get": {
        "responses": {
          "200": {
            "description": "OK",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "required": ["id", "name"],
                  "properties": {
                    "id": {"type": "integer"},
                    "name": {"type": "string"}
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
JSON;

        $run = app(OpenApiContractValidationService::class)->validate($project, $payload, $scanRun, 'Reality contract');

        $this->assertDatabaseHas('contract_validation_results', [
            'contract_validation_run_id' => $run->id,
            'check_type' => ContractValidationResult::CHECK_AUTH_REQUIREMENT,
            'status' => ContractValidationResult::STATUS_FAIL,
        ]);

        $this->assertDatabaseHas('contract_validation_results', [
            'contract_validation_run_id' => $run->id,
            'check_type' => ContractValidationResult::CHECK_UNDOCUMENTED_RESPONSE_FIELD,
            'status' => ContractValidationResult::STATUS_FAIL,
        ]);

        $summary = app(ContractRealityService::class)->summarize($project->fresh(), $run->fresh());

        $this->assertSame(1, $summary['summary']['auth_contract_mismatch']);
        $this->assertSame(1, $summary['summary']['undocumented_response']);
        $this->assertGreaterThanOrEqual(1, $summary['summary']['breaking_contract_mismatch']);

        $this->actingAs($admin)
            ->get(route('projects.contract-reality.index', $project))
            ->assertOk()
            ->assertSee(__('messages.contract_reality.title'))
            ->assertSee('GET')
            ->assertSee('/users/{id}')
            ->assertSee('internal_status');
    }
}
