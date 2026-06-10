<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\User;
use App\Services\SafeProbeService;
use App\Services\Schema\SchemaDriftDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SchemaDriftDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_service_detects_added_removed_type_and_nullability_changes(): void
    {
        $detector = app(SchemaDriftDetector::class);

        $baseline = $detector->schemaFromPreview(json_encode([
            'id' => 1,
            'name' => 'Jane',
            'email' => 'jane@example.test',
        ]), 'application/json');

        $current = $detector->schemaFromPreview(json_encode([
            'id' => '1',
            'name' => null,
            'role' => 'admin',
        ]), 'application/json');

        $changes = $detector->compareSchemas($baseline, $current);
        $kinds = array_column($changes, 'kind');

        $this->assertContains('type_changed', $kinds);
        $this->assertContains('nullability_changed', $kinds);
        $this->assertContains('removed', $kinds);
        $this->assertContains('added', $kinds);
    }

    public function test_safe_probe_records_schema_drift_result_finding_and_inventory_filter(): void
    {
        $admin = User::query()->create([
            'name' => 'Schema Admin',
            'email' => 'schema-admin@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Schema API',
            'slug' => 'schema-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/1',
            'name' => 'Get user',
            'auth_required' => false,
            'expected_status' => 200,
            'expected_content_type' => 'application/json',
            'risk_level' => Endpoint::RISK_LOW,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $baselineRun = $project->scanRuns()->create([
            'created_by' => $admin->id,
            'status' => 'completed',
            'mode' => 'safe',
        ]);

        $baselineRun->results()->create([
            'endpoint_id' => $endpoint->id,
            'method' => Endpoint::METHOD_GET,
            'url' => 'https://api.example.test/users/1',
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 200,
            'content_type' => 'application/json',
            'body_preview' => json_encode(['id' => 1, 'name' => 'Jane', 'email' => 'jane@example.test']),
            'response_schema_json' => [
                '$' => 'object',
                '$.id' => 'integer',
                '$.name' => 'string',
                '$.email' => 'string',
            ],
            'risk_level' => Endpoint::RISK_LOW,
        ]);

        Http::fake([
            '*' => Http::response([
                'id' => '1',
                'name' => null,
                'role' => 'admin',
            ], 200, ['Content-Type' => 'application/json']),
        ]);

        $scanRun = app(SafeProbeService::class)->runEndpoint($project, $endpoint, $admin);
        $result = $scanRun->results()->latest('id')->firstOrFail();

        $this->assertSame(ScanResult::STATUS_COMPLETED, $result->status);
        $this->assertTrue((bool) $result->schema_drift_detected);
        $this->assertGreaterThanOrEqual(3, $result->schema_drift_count);
        $this->assertIsArray($result->response_schema_json);
        $this->assertSame('object', $result->response_schema_json['$']);
        $this->assertNotSame(__('messages.schema_drift.not_checked'), $result->schema_drift_summary_label);

        $finding = Finding::query()->where('scan_result_id', $result->id)->firstOrFail();
        $this->assertSame(Finding::SOURCE_REGRESSION, $finding->source);
        $this->assertContains($finding->severity, [Finding::SEVERITY_HIGH, Finding::SEVERITY_CRITICAL]);
        $this->assertSame(1, $finding->evidence()->count());

        $this->actingAs($admin)
            ->get(route('projects.endpoint-inventory.index', ['project' => $project, 'coverage' => 'schema_drift']))
            ->assertOk()
            ->assertSee('/users/1')
            ->assertSee(__('messages.schema_drift.detected'));
    }
}
