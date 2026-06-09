<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Environment;
use App\Models\Finding;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndpointInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_inventory_renders_audit_columns_and_gaps(): void
    {
        $admin = User::query()->create([
            'name' => 'Inventory Admin',
            'email' => 'inventory@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Inventory API',
            'slug' => 'inventory-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $environment = Environment::query()->create([
            'project_id' => $project->id,
            'name' => 'Staging',
            'base_url' => 'https://staging.example.test',
            'is_production' => false,
        ]);

        $scannedEndpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'environment_id' => $environment->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/{id}',
            'name' => 'Get user',
            'auth_required' => true,
            'expected_status' => 200,
            'expected_content_type' => 'application/json',
            'risk_level' => Endpoint::RISK_HIGH,
            'qa_notes' => 'Imported from a Postman collection.',
            'request_headers' => [['key' => 'Authorization', 'value' => 'Bearer ***']],
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $unscannedEndpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_POST,
            'path' => '/orders',
            'name' => 'Create order',
            'auth_required' => false,
            'risk_level' => Endpoint::RISK_REVIEW,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $scanRun = ScanRun::query()->create([
            'project_id' => $project->id,
            'environment_id' => $environment->id,
            'created_by' => $admin->id,
            'status' => 'completed',
            'mode' => 'safe',
            'total_endpoints' => 1,
            'scanned_count' => 1,
            'success_count' => 1,
        ]);

        ScanResult::query()->create([
            'scan_run_id' => $scanRun->id,
            'endpoint_id' => $scannedEndpoint->id,
            'method' => Endpoint::METHOD_GET,
            'url' => 'https://staging.example.test/users/1',
            'status' => ScanResult::STATUS_COMPLETED,
            'status_code' => 200,
            'response_time_ms' => 123,
            'content_type' => 'application/json',
            'risk_level' => Endpoint::RISK_HIGH,
        ]);

        Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $scannedEndpoint->id,
            'title' => 'Auth review required',
            'source' => Finding::SOURCE_SCAN,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_OPEN,
        ]);

        $this->actingAs($admin)
            ->get(route('projects.endpoint-inventory.index', $project))
            ->assertOk()
            ->assertSee(__('messages.endpoint_inventory.title'))
            ->assertSee('/users/{id}')
            ->assertSee('Staging')
            ->assertSee('200')
            ->assertSee('123 ms')
            ->assertSee(__('messages.endpoint_inventory.sources.postman'))
            ->assertSee(__('messages.endpoint_inventory.flags.open_findings', ['count' => 1]))
            ->assertSee(__('messages.endpoint_inventory.flags.not_scanned'));

        $this->actingAs($admin)
            ->get(route('projects.endpoint-inventory.index', ['project' => $project, 'scan' => 'not_scanned']))
            ->assertOk()
            ->assertSee('/orders')
            ->assertDontSee('/users/{id}');
    }

    public function test_endpoint_inventory_is_reachable_from_project_navigation(): void
    {
        $admin = User::query()->create([
            'name' => 'Inventory Nav Admin',
            'email' => 'inventory-nav@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Navigation API',
            'slug' => 'navigation-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee(route('projects.endpoint-inventory.index', $project), false)
            ->assertSee(__('messages.endpoint_inventory.short_title'));
    }
}
