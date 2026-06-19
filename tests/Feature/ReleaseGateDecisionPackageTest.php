<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ReleaseGate;
use App\Models\ReleaseGateItem;
use App\Models\ReportVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseGateDecisionPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_release_gate_can_create_fixed_decision_package_report(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Package QA Project']);
        $gate = ReleaseGate::create([
            'project_id' => $project->id,
            'created_by_user_id' => $user->id,
            'finalized_by_user_id' => $user->id,
            'title' => 'v1.2 release gate',
            'status' => 'approved',
            'automated_decision' => 'pass',
            'final_decision' => 'go',
            'score' => 91,
            'grade' => 'A',
            'decision_note' => 'Approved after gate review.',
            'finalized_at' => now(),
        ]);
        ReleaseGateItem::create([
            'project_id' => $project->id,
            'release_gate_id' => $gate->id,
            'item_key' => 'verified_evidence',
            'category' => 'evidence',
            'label' => 'Verified evidence exists',
            'icon' => 'fingerprint',
            'automated_state' => 'pass',
            'effective_state' => 'pass',
            'severity' => 'warning',
        ]);

        $response = $this->actingAs($user)->post(route('projects.release-gates.report-version.store', [$project, $gate]), [
            'notes' => 'Customer handoff package.',
            'confirm_decision_package' => '1',
        ]);

        $response->assertRedirect();
        $report = ReportVersion::query()->where('release_gate_id', $gate->id)->first();
        $this->assertNotNull($report);
        $this->assertSame('release_decision', $report->type);
        $this->assertSame($gate->id, $report->release_gate_id);
        $this->assertNotEmpty($report->checksum);
        $this->assertSame('release_gate_decision_package', data_get($report->data_json, 'source.type'));
    }

    public function test_release_gate_zip_download_is_real_archive(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $project = Project::factory()->create(['user_id' => $user->id]);
        $gate = ReleaseGate::create([
            'project_id' => $project->id,
            'created_by_user_id' => $user->id,
            'title' => 'Decision package gate',
            'status' => 'ready',
            'automated_decision' => 'pass',
            'final_decision' => 'pending',
            'score' => 88,
            'grade' => 'B',
        ]);

        $response = $this->actingAs($user)->get(route('projects.release-gates.download', [$project, $gate, 'zip']));

        $response->assertOk();
        $this->assertStringStartsWith('PK', $response->baseResponse->getContent());
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));
    }
}
