<?php

namespace Tests\Feature;

use App\Models\CompareItem;
use App\Models\Project;
use App\Models\Snapshot;
use App\Models\User;
use App\Services\RegressionEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegressionEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_code_deterioration_is_detected_as_regression(): void
    {
        $this->seed();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $snapshotA = Snapshot::query()->create(['project_id' => $project->id, 'name' => 'A', 'endpoint_count' => 1]);
        $snapshotB = Snapshot::query()->create(['project_id' => $project->id, 'name' => 'B', 'endpoint_count' => 1]);
        $compareRun = $project->compareRuns()->create([
            'snapshot_a_id' => $snapshotA->id,
            'snapshot_b_id' => $snapshotB->id,
            'summary_json' => [],
        ]);
        $compareRun->items()->create([
            'change_type' => CompareItem::TYPE_CHANGED,
            'method' => 'GET',
            'path' => '/todos/1',
            'field_changed' => 'status_code',
            'old_value' => '200',
            'new_value' => '500',
            'severity' => CompareItem::SEVERITY_HIGH,
        ]);

        $evaluation = app(RegressionEvaluationService::class)->evaluateCompare($compareRun);

        $this->assertSame(RegressionEvaluationService::STATUS_DETECTED, $evaluation['status']);
        $this->assertSame(1, $evaluation['detected_count']);
        $this->assertSame('Regression detected', $evaluation['label']);
    }

    public function test_slow_response_change_can_be_a_regression_warning(): void
    {
        $this->seed();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $snapshotA = Snapshot::query()->create(['project_id' => $project->id, 'name' => 'A', 'endpoint_count' => 1]);
        $snapshotB = Snapshot::query()->create(['project_id' => $project->id, 'name' => 'B', 'endpoint_count' => 1]);
        $compareRun = $project->compareRuns()->create([
            'snapshot_a_id' => $snapshotA->id,
            'snapshot_b_id' => $snapshotB->id,
            'summary_json' => [],
        ]);
        $compareRun->items()->create([
            'change_type' => CompareItem::TYPE_CHANGED,
            'method' => 'GET',
            'path' => '/todos/1',
            'field_changed' => 'response_time_ms',
            'old_value' => '100 ms',
            'new_value' => '180 ms',
            'severity' => CompareItem::SEVERITY_REVIEW,
        ]);

        $evaluation = app(RegressionEvaluationService::class)->evaluateCompare($compareRun);

        $this->assertSame(RegressionEvaluationService::STATUS_WARNING, $evaluation['status']);
        $this->assertSame(1, $evaluation['warning_count']);
    }

    public function test_faster_response_change_is_not_a_regression(): void
    {
        $this->seed();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $snapshotA = Snapshot::query()->create(['project_id' => $project->id, 'name' => 'A', 'endpoint_count' => 1]);
        $snapshotB = Snapshot::query()->create(['project_id' => $project->id, 'name' => 'B', 'endpoint_count' => 1]);
        $compareRun = $project->compareRuns()->create([
            'snapshot_a_id' => $snapshotA->id,
            'snapshot_b_id' => $snapshotB->id,
            'summary_json' => [],
        ]);
        $compareRun->items()->create([
            'change_type' => CompareItem::TYPE_CHANGED,
            'method' => 'GET',
            'path' => '/todos/1',
            'field_changed' => 'response_time_ms',
            'old_value' => '180 ms',
            'new_value' => '100 ms',
            'severity' => CompareItem::SEVERITY_INFO,
        ]);

        $evaluation = app(RegressionEvaluationService::class)->evaluateCompare($compareRun);

        $this->assertSame(RegressionEvaluationService::STATUS_NONE, $evaluation['status']);
        $this->assertSame(0, $evaluation['warning_count']);
        $this->assertSame(0, $evaluation['detected_count']);
    }

    public function test_snapshot_compare_details_display_regression_summary(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $snapshotA = Snapshot::query()->create(['project_id' => $project->id, 'name' => 'A', 'endpoint_count' => 1]);
        $snapshotB = Snapshot::query()->create(['project_id' => $project->id, 'name' => 'B', 'endpoint_count' => 1]);
        $compareRun = $project->compareRuns()->create([
            'snapshot_a_id' => $snapshotA->id,
            'snapshot_b_id' => $snapshotB->id,
            'summary_json' => ['total_changes' => 1, 'changed_count' => 1],
        ]);
        $compareRun->items()->create([
            'change_type' => CompareItem::TYPE_CHANGED,
            'method' => 'GET',
            'path' => '/todos/1',
            'field_changed' => 'status_code',
            'old_value' => '200',
            'new_value' => '500',
            'severity' => CompareItem::SEVERITY_HIGH,
        ]);

        $this->actingAs($admin)
            ->get(route('projects.snapshots.compares.show', [$project, $compareRun]))
            ->assertOk()
            ->assertSee('Regression status')
            ->assertSee('Regression detected')
            ->assertSee('Assertion status');
    }
}
