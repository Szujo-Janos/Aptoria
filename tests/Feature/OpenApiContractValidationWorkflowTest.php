<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Project;
use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenApiContractValidationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_contract_validation_compares_contract_with_endpoint_inventory(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        Endpoint::factory()->create([
            'project_id' => $project->id,
            'method' => 'GET',
            'path' => '/health',
            'risk_level' => 'low',
            'auth_required' => false,
        ]);

        Endpoint::factory()->create([
            'project_id' => $project->id,
            'method' => 'GET',
            'path' => '/admin/users',
            'risk_level' => 'high',
            'auth_required' => true,
        ]);

        $contract = json_encode([
            'openapi' => '3.0.3',
            'info' => ['title' => 'Customer API', 'version' => '1.4.0'],
            'paths' => [
                '/health' => ['get' => ['operationId' => 'healthCheck', 'summary' => 'Health check']],
                '/customers' => ['get' => ['operationId' => 'listCustomers', 'summary' => 'List customers']],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->actingAs($user)->post(route('projects.contract-validation.store', $project), [
            'source_name' => 'Customer API OpenAPI',
            'source_version' => 'v1.4',
            'contract_content' => $contract,
            'confirm_validation' => '1',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('contract_validation_runs', [
            'project_id' => $project->id,
            'source_name' => 'Customer API OpenAPI',
            'status' => 'blocked',
            'documented_operations' => 2,
            'inventory_operations' => 2,
            'matched_operations' => 1,
            'undocumented_inventory_operations' => 1,
            'missing_inventory_operations' => 1,
            'blocker_count' => 1,
            'warning_count' => 1,
        ]);

        $this->assertDatabaseHas('contract_validation_results', [
            'project_id' => $project->id,
            'result_type' => 'undocumented_endpoint',
            'severity' => 'blocker',
            'method' => 'GET',
            'path' => '/admin/users',
        ]);

        $run = $project->contractValidationRuns()->firstOrFail();

        $this->actingAs($user)
            ->get(route('projects.contract-validation.show', [$project, $run]))
            ->assertOk()
            ->assertSee('Customer API OpenAPI')
            ->assertSee('Endpoints missing from contract')
            ->assertSee('/admin/users')
            ->assertSee('Contract operations missing from inventory')
            ->assertSee('/customers');
    }

    public function test_release_readiness_includes_contract_validation_checks(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);
        Endpoint::factory()->create(['project_id' => $project->id, 'method' => 'GET', 'path' => '/health']);

        $contract = json_encode([
            'openapi' => '3.0.3',
            'info' => ['title' => 'Health API', 'version' => '1.0.0'],
            'paths' => ['/health' => ['get' => ['operationId' => 'healthCheck']]],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($user)->post(route('projects.contract-validation.store', $project), [
            'source_name' => 'Health API',
            'contract_content' => $contract,
            'confirm_validation' => '1',
        ]);

        $this->actingAs($user)
            ->get(route('projects.release-readiness.index', $project))
            ->assertOk()
            ->assertSee('OpenAPI contract validation')
            ->assertSee('No blocking contract drift')
            ->assertSee('No contract drift needs review');
    }

    public function test_contract_validation_records_missing_paths_as_review_warning_instead_of_form_error(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        Endpoint::factory()->create([
            'project_id' => $project->id,
            'method' => 'GET',
            'path' => '/health',
            'risk_level' => 'low',
            'auth_required' => false,
        ]);

        $contract = json_encode([
            'openapi' => '3.0.3',
            'info' => ['title' => 'Draft contract', 'version' => '0.1.0'],
        ], JSON_THROW_ON_ERROR);

        $response = $this->actingAs($user)->post(route('projects.contract-validation.store', $project), [
            'source_name' => 'Draft contract without paths',
            'contract_content' => $contract,
            'confirm_validation' => '1',
        ]);

        $response->assertRedirect();

        $run = $project->contractValidationRuns()->firstOrFail();

        $this->assertSame('warning', $run->status);
        $this->assertSame(0, $run->documented_operations);
        $this->assertSame(1, $run->inventory_operations);
        $this->assertSame(1, $run->undocumented_inventory_operations);
        $this->assertSame(2, $run->warning_count);
        $this->assertContains('missing_paths_normalized', $run->summary['source_warnings']);

        $this->assertDatabaseHas('contract_validation_results', [
            'contract_validation_run_id' => $run->id,
            'result_type' => 'undocumented_endpoint',
            'severity' => 'warning',
            'method' => 'GET',
            'path' => '/health',
        ]);
    }

    public function test_contract_validation_accepts_wrapped_or_fenced_openapi_json(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        Endpoint::factory()->create(['project_id' => $project->id, 'method' => 'GET', 'path' => '/health']);

        $wrapped = json_encode([
            'document' => [
                'openapi' => '3.0.3',
                'info' => ['title' => 'Wrapped API', 'version' => '1.0.0'],
                'paths' => ['/health' => ['get' => ['operationId' => 'healthCheck']]],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->actingAs($user)->post(route('projects.contract-validation.store', $project), [
            'source_name' => 'Wrapped API',
            'contract_content' => "```json\n{$wrapped}\n```",
            'confirm_validation' => '1',
        ]);

        $response->assertRedirect();

        $run = $project->contractValidationRuns()->firstOrFail();

        $this->assertSame('passed', $run->status);
        $this->assertSame(1, $run->documented_operations);
        $this->assertSame(1, $run->matched_operations);
        $this->assertContains('wrapped_document_detected', $run->summary['source_warnings']);
    }

}
