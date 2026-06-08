<?php

namespace Tests\Feature;

use App\Models\CompareItem;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaEvidencePackTest extends TestCase
{
    use RefreshDatabase;

    public function test_qa_evidence_pack_builder_and_notes_export_are_available(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('method', Endpoint::METHOD_GET)->firstOrFail();

        $baseline = $this->snapshot($project, $admin, $endpoint, 'Baseline scan - Public API');
        $validation = $this->snapshot($project, $admin, $endpoint, 'Assertion validation scan - Public API - corrected count rules');
        $negative = $this->snapshot($project, $admin, $endpoint, 'Negative assertion control - GET todos 1 id expected 999');
        $recovery = $this->snapshot($project, $admin, $endpoint, 'Post-negative recovery scan - Public API');

        $compareRun = $project->compareRuns()->create([
            'snapshot_a_id' => $validation->id,
            'snapshot_b_id' => $recovery->id,
            'created_by' => $admin->id,
            'summary_json' => [
                'total_changes' => 1,
                'changed_count' => 1,
                'critical_count' => 0,
                'high_count' => 0,
            ],
        ]);
        $compareRun->items()->create([
            'change_type' => CompareItem::TYPE_CHANGED,
            'method' => Endpoint::METHOD_GET,
            'path' => $endpoint->path,
            'field_changed' => 'response_time_ms',
            'old_value' => '100 ms',
            'new_value' => '130 ms',
            'severity' => CompareItem::SEVERITY_REVIEW,
        ]);

        $this->actingAs($admin)
            ->get(route('projects.qa-evidence.index', $project))
            ->assertOk()
            ->assertSee('QA Evidence Pack')
            ->assertSee('Download Evidence Pack ZIP')
            ->assertSee('Negative control snapshot');

        $this->actingAs($admin)
            ->get(route('projects.qa-evidence.notes', [
                'project' => $project,
                'baseline_snapshot_id' => $baseline->id,
                'validation_snapshot_id' => $validation->id,
                'negative_snapshot_id' => $negative->id,
                'recovery_snapshot_id' => $recovery->id,
                'compare_run_ids' => [$compareRun->id],
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# QA Evidence Notes', false)
            ->assertSee('Negative assertion control', false)
            ->assertSee('Evidence only. Do not use as baseline.', false)
            ->assertSee('PASS WITH WARNING', false)
            ->assertSee('Response-time-only warnings can be accepted', false);

        $zipResponse = $this->actingAs($admin)
            ->get(route('projects.qa-evidence.zip', [
                'project' => $project,
                'baseline_snapshot_id' => $baseline->id,
                'validation_snapshot_id' => $validation->id,
                'negative_snapshot_id' => $negative->id,
                'recovery_snapshot_id' => $recovery->id,
                'compare_run_ids' => [$compareRun->id],
            ]));

        $zipResponse
            ->assertOk()
            ->assertHeader('Content-Type', 'application/zip')
            ->assertDownload();
    }

    private function snapshot(Project $project, User $admin, Endpoint $endpoint, string $name): Snapshot
    {
        $snapshot = Snapshot::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'name' => $name,
            'endpoint_count' => 1,
            'summary_json' => ['endpoint_count' => 1],
        ]);

        $snapshot->items()->create([
            'endpoint_id' => $endpoint->id,
            'method' => $endpoint->method,
            'path' => $endpoint->path,
            'auth_required' => false,
            'risk_level' => Endpoint::RISK_LOW,
            'status_code' => 200,
            'content_type' => 'application/json',
            'response_time_ms' => 120,
        ]);

        return $snapshot;
    }
}
