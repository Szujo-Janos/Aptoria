<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindingsEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_finding_can_be_created_with_evidence(): void
    {
        $user = User::query()->create([
            'name' => 'QA Admin',
            'email' => 'qa@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'QA API',
            'slug' => 'qa-api',
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

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'Health endpoint contract mismatch',
            'source' => Finding::SOURCE_CONTRACT,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_OPEN,
            'expected_result' => 'HTTP 200 JSON',
            'actual_result' => 'HTTP 500',
        ]);

        $evidence = FindingEvidence::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'type' => FindingEvidence::TYPE_HTTP,
            'source_label' => 'Stored scan response',
            'content' => 'HTTP 500 returned during QA scan.',
        ]);

        $this->assertTrue($finding->fresh()->is_open);
        $this->assertSame($endpoint->id, $finding->fresh()->endpoint->id);
        $this->assertSame($evidence->id, $finding->fresh()->evidence()->first()->id);
    }
}
