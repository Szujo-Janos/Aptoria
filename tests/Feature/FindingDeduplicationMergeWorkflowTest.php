<?php

namespace Tests\Feature;

use App\Models\Finding;
use App\Models\Project;
use App\Models\User;
use App\Services\FindingDeduplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindingDeduplicationMergeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_scan_creates_merge_candidate(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        Finding::factory()->create(['project_id' => $project->id, 'title' => 'Billing API returns 500 error', 'severity' => 'high', 'status' => 'open']);
        Finding::factory()->create(['project_id' => $project->id, 'title' => 'Billing API returns 500 error', 'severity' => 'high', 'status' => 'open']);

        $summary = app(FindingDeduplicationService::class)->scan($project);

        $this->assertGreaterThanOrEqual(1, $summary['candidate_count']);
    }
}
