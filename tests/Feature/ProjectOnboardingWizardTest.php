<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectOnboardingWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guided_project_wizard_creates_project_scan_snapshot_and_report_completion(): void
    {
        $this->seed();

        Http::fake([
            'jsonplaceholder.typicode.com/*' => Http::response(['id' => 1, 'title' => 'Onboarding smoke response'], 200, [
                'Content-Type' => 'application/json; charset=utf-8',
            ]),
        ]);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('projects.wizard.store'), [
            'name' => 'Onboarding QA Project',
            'description' => 'Created by the guided onboarding wizard test.',
            'base_url' => 'https://jsonplaceholder.typicode.com',
            'is_active' => '1',
            'environment_name' => 'staging',
            'environment_base_url' => 'https://jsonplaceholder.typicode.com',
            'auth_name' => 'No Auth',
            'auth_type' => 'none',
            'format' => 'csv',
            'import_source' => 'paste',
            'payload' => "method,path,name,risk_level,auth_required,expected_status,expected_content_type,tags,description\nGET,/todos/1,Todo detail,public,false,200,application/json,onboarding,First onboarding endpoint",
            'assert_status_code' => '1',
            'assert_status_code_value' => '200',
            'assert_response_time' => '1',
            'assert_response_time_value' => '2500',
            'assert_required_content_type' => '1',
            'assert_https' => '1',
            'assert_max_risk' => '1',
            'assert_max_risk_value' => '70',
            'run_initial_scan' => '1',
            'create_initial_snapshot' => '1',
            'generate_initial_report' => '1',
        ]);

        $project = Project::query()->where('name', 'Onboarding QA Project')->firstOrFail();

        $response->assertRedirect();
        $this->assertStringContainsString('/projects/'.$project->id.'/wizard/complete', (string) $response->headers->get('Location'));

        $this->assertSame(1, $project->endpoints()->count());
        $this->assertSame(1, $project->scanRuns()->count());
        $this->assertSame(1, $project->snapshots()->count());

        $scanRun = $project->scanRuns()->firstOrFail();
        $snapshot = $project->snapshots()->firstOrFail();

        $this->assertSame(1, $scanRun->results()->count());
        $this->assertSame($scanRun->id, $snapshot->scan_run_id);

        $this->actingAs($admin)
            ->get(route('projects.wizard.complete', [
                'project' => $project,
                'scanRun' => $scanRun->id,
                'snapshot' => $snapshot->id,
                'report' => '1',
                'imported' => '1',
            ]))
            ->assertOk()
            ->assertSee('Project onboarding complete')
            ->assertSee('First safe scan')
            ->assertSee('First snapshot')
            ->assertSee('First report')
            ->assertSee('HTML')
            ->assertSee('PDF');
    }

    public function test_guided_project_wizard_rejects_empty_endpoint_payload(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->post(route('projects.wizard.store'), [
            'name' => 'Empty onboarding payload',
            'base_url' => 'https://example.com',
            'is_active' => '1',
            'environment_name' => 'staging',
            'environment_base_url' => 'https://example.com',
            'auth_name' => 'No Auth',
            'auth_type' => 'none',
            'format' => 'csv',
            'import_source' => 'paste',
            'payload' => "method,path,name\nGET,,Missing path",
        ])->assertSessionHasErrors('payload');

        $this->assertFalse(Project::query()->where('name', 'Empty onboarding payload')->exists());
    }
    public function test_guided_project_wizard_requires_credentials_for_selected_auth_type(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->post(route('projects.wizard.store'), [
            'name' => 'Bearer without token',
            'base_url' => 'https://example.com',
            'is_active' => '1',
            'environment_name' => 'staging',
            'environment_base_url' => 'https://example.com',
            'auth_name' => 'Bearer profile',
            'auth_type' => 'bearer',
            'format' => 'csv',
            'import_source' => 'paste',
            'payload' => "method,path,name\nGET,/health,Health",
        ])->assertSessionHasErrors('token');

        $this->assertFalse(Project::query()->where('name', 'Bearer without token')->exists());
    }

}
