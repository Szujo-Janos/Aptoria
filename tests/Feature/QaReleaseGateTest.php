<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\QaReleaseGate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaReleaseGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_gate_page_is_available_for_project(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Gate API',
            'slug' => 'gate-api',
            'base_url' => 'https://example.test',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('projects.release-gates.index', $project))
            ->assertOk()
            ->assertSee(__('messages.release_gates.title'));
    }

    public function test_release_gate_snapshot_can_be_created(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Gate API',
            'slug' => 'gate-api',
            'base_url' => 'https://example.test',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('projects.release-gates.store', $project), [
                'release_name' => 'v1.0 release candidate',
                'target_environment' => 'staging',
                'gate_profile' => QaReleaseGate::PROFILE_STANDARD,
                'final_decision' => QaReleaseGate::DECISION_BLOCKED,
                'reviewed_by' => 'QA',
                'decision_notes' => 'No evidence yet, gate remains blocked.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('qa_release_gates', [
            'project_id' => $project->id,
            'release_name' => 'v1.0 release candidate',
            'automated_status' => QaReleaseGate::STATUS_BLOCKED,
            'final_decision' => QaReleaseGate::DECISION_BLOCKED,
        ]);
    }
}
