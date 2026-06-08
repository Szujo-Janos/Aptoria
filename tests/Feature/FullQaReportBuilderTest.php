<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullQaReportBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_qa_report_builder_renders_and_exports_selected_sections(): void
    {
        $user = User::query()->create([
            'name' => 'QA Admin',
            'email' => 'report-builder@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Report Builder API',
            'slug' => 'report-builder-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/health',
            'name' => 'Health',
            'auth_required' => false,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_LOW,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Health endpoint is not covered by contract evidence',
            'source' => Finding::SOURCE_MANUAL,
            'severity' => Finding::SEVERITY_MEDIUM,
            'status' => Finding::STATUS_OPEN,
        ]);

        $this->actingAs($user)
            ->get(route('projects.reports.builder.create', $project))
            ->assertOk()
            ->assertSee('Full QA Report Builder')
            ->assertSee('Executive Summary')
            ->assertSee('Findings &amp; Evidence', false);

        $this->actingAs($user)
            ->post(route('projects.reports.builder.markdown', $project), [
                'title' => 'Custom QA Signoff',
                'audience' => 'release',
                'decision' => 'conditional',
                'scope_notes' => 'RC-1 smoke review.',
                'sections' => ['executive_summary', 'findings_evidence', 'endpoint_inventory', 'appendix'],
                'endpoint_limit' => 25,
                'test_case_limit' => 25,
                'finding_limit' => 25,
                'contract_result_limit' => 25,
                'problem_endpoints_only' => '1',
                'include_evidence_details' => '1',
            ])
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# Custom QA Signoff', false)
            ->assertSee('## Executive Summary', false)
            ->assertSee('## Findings & Evidence', false)
            ->assertSee('Health endpoint is not covered by contract evidence', false)
            ->assertSee('## Endpoint Inventory', false)
            ->assertSee('## Appendix', false)
            ->assertDontSee('## OpenAPI Contract Validation', false);
    }
}
