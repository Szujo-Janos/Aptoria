<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ReleaseGate;
use App\Models\ReleaseGateItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseGateWorkflowFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_gate_creation_creates_reviewable_gate_items(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Gate QA Project']);

        $response = $this->actingAs($user)->post(route('projects.release-gates.store', $project), [
            'title' => 'v1.0 release gate',
            'release_version' => 'v1.0.0',
            'target_environment' => 'Staging',
            'gate_profile' => 'standard',
            'decision_note' => 'Release scope review.',
            'confirm_create_gate' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('release_gates', ['project_id' => $project->id, 'title' => 'v1.0 release gate']);
        $this->assertGreaterThan(0, ReleaseGate::first()->items()->count());
    }

    public function test_blocked_gate_cannot_be_finalized_as_go(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $gate = ReleaseGate::create([
            'project_id' => $project->id,
            'created_by_user_id' => $user->id,
            'title' => 'Blocked gate',
            'status' => 'blocked',
            'automated_decision' => 'blocked',
            'score' => 60,
            'grade' => 'C',
        ]);
        ReleaseGateItem::create([
            'project_id' => $project->id,
            'release_gate_id' => $gate->id,
            'item_key' => 'blocked_item',
            'category' => 'findings',
            'label' => 'Open critical finding',
            'icon' => 'octagon-alert',
            'automated_state' => 'blocked',
            'effective_state' => 'blocked',
            'severity' => 'blocker',
        ]);

        $response = $this->actingAs($user)->post(route('projects.release-gates.finalize', [$project, $gate]), [
            'final_decision' => 'go',
            'decision_note' => 'Trying to approve with blockers.',
            'confirm_finalize_gate' => '1',
        ]);

        $response->assertSessionHasErrors('final_decision');
        $this->assertDatabaseMissing('release_gates', ['id' => $gate->id, 'final_decision' => 'go']);
    }
}
