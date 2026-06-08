<?php

namespace Tests\Feature;

use App\Models\ContractValidationResult;
use App\Models\ContractValidationRun;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\User;
use App\Services\Contracts\OpenApiContractValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenApiContractValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_validation_records_pass_fail_and_missing_endpoint_evidence(): void
    {
        $this->seed();

        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('path', '/todos/1')->firstOrFail();
        $scanRun = $project->scanRuns()->create([
            'status' => ScanRun::STATUS_COMPLETED,
            'mode' => 'manual',
            'started_at' => now(),
            'finished_at' => now(),
        ]);
        $scanRun->results()->create([
            'endpoint_id' => $endpoint->id,
            'method' => $endpoint->method,
            'url' => $project->base_url.$endpoint->path,
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 200,
            'content_type' => 'application/json; charset=utf-8',
            'body_preview' => '{"id":1,"title":"demo"}',
        ]);

        $payload = <<<'JSON'
{
  "openapi": "3.0.0",
  "paths": {
    "/todos/1": {
      "get": {
        "responses": {
          "200": {
            "description": "OK",
            "content": {
              "application/json": {
                "schema": {
                  "type": "object",
                  "required": ["id", "title"],
                  "properties": {
                    "id": {"type": "integer"},
                    "title": {"type": "string"}
                  }
                }
              }
            }
          }
        }
      }
    },
    "/missing-in-inventory": {
      "get": {
        "responses": {
          "200": {"description": "OK"}
        }
      }
    }
  }
}
JSON;

        $run = app(OpenApiContractValidationService::class)->validate($project, $payload, $scanRun, 'Demo contract');

        $this->assertSame(ContractValidationRun::STATUS_COMPLETED, $run->status);
        $this->assertGreaterThan(0, $run->total_checks);
        $this->assertGreaterThan(0, $run->passed_count);
        $this->assertSame(1, $run->missing_endpoint_count);
        $this->assertGreaterThan(0, $run->breaking_count);
        $this->assertDatabaseHas('contract_validation_results', [
            'contract_validation_run_id' => $run->id,
            'path' => '/missing-in-inventory',
            'check_type' => ContractValidationResult::CHECK_OPERATION_IMPLEMENTED,
            'status' => ContractValidationResult::STATUS_FAIL,
        ]);
    }

    public function test_contract_validation_ui_pages_render(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('path', '/todos/1')->firstOrFail();
        $run = $project->contractValidationRuns()->create([
            'source_name' => 'Demo contract',
            'status' => ContractValidationRun::STATUS_COMPLETED,
            'total_checks' => 1,
            'passed_count' => 1,
            'started_at' => now(),
            'finished_at' => now(),
        ]);
        $run->results()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'method' => $endpoint->method,
            'path' => $endpoint->path,
            'check_type' => ContractValidationResult::CHECK_STATUS_CODE,
            'severity' => ContractValidationResult::SEVERITY_LOW,
            'status' => ContractValidationResult::STATUS_PASS,
            'message' => 'Status matches.',
        ]);

        $this->actingAs($admin)
            ->get(route('projects.contract-validations.index', $project))
            ->assertOk()
            ->assertSee('Contract Validation')
            ->assertSee('Demo contract');

        $this->actingAs($admin)
            ->get(route('projects.contract-validations.show', [$project, $run]))
            ->assertOk()
            ->assertSee('Validation results')
            ->assertSee('Status matches.');

        $this->actingAs($admin)
            ->get(route('projects.endpoints.show', [$project, $endpoint]))
            ->assertOk()
            ->assertSee('Contract Validation Results')
            ->assertSee('Status matches.');
    }
}
