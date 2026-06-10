<?php

namespace Tests\Feature;

use App\Models\CompareItem;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\Snapshot;
use App\Models\User;
use App\Services\SafeProbeService;
use App\Services\Snapshots\SnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SnapshotCompareTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_can_be_saved_as_snapshot(): void
    {
        $this->seed();
        Http::fake(['*' => Http::response(['ok' => true], 200, ['Content-Type' => 'application/json'])]);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $scanRun = app(SafeProbeService::class)->runProject($project, null, $admin);

        $snapshot = app(SnapshotService::class)->createFromScanRun($scanRun, $admin, 'Baseline snapshot');

        $this->assertSame('Baseline snapshot', $snapshot->name);
        $this->assertSame($project->id, $snapshot->project_id);
        $this->assertSame($project->endpoints()->count(), $snapshot->items()->count());
        $this->assertNotNull($snapshot->snapshot_hash);
    }

    public function test_snapshot_compare_detects_new_removed_and_changed_endpoints(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();

        $snapshotA = Snapshot::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'name' => 'A',
            'endpoint_count' => 2,
        ]);
        $snapshotB = Snapshot::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'name' => 'B',
            'endpoint_count' => 2,
        ]);

        $snapshotA->items()->create([
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/1',
            'auth_required' => false,
            'risk_level' => Endpoint::RISK_REVIEW,
            'status_code' => 200,
        ]);
        $snapshotA->items()->create([
            'method' => Endpoint::METHOD_GET,
            'path' => '/removed',
            'auth_required' => false,
            'risk_level' => Endpoint::RISK_LOW,
            'status_code' => 200,
        ]);
        $snapshotB->items()->create([
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/1',
            'auth_required' => false,
            'risk_level' => Endpoint::RISK_HIGH,
            'status_code' => 500,
        ]);
        $snapshotB->items()->create([
            'method' => Endpoint::METHOD_GET,
            'path' => '/new',
            'auth_required' => false,
            'risk_level' => Endpoint::RISK_PUBLIC,
            'status_code' => 200,
        ]);

        $compareRun = app(SnapshotService::class)->compare($snapshotA, $snapshotB, $admin);

        $this->assertSame(4, $compareRun->items()->count());
        $this->assertSame(1, $compareRun->items()->where('change_type', CompareItem::TYPE_NEW)->count());
        $this->assertSame(1, $compareRun->items()->where('change_type', CompareItem::TYPE_REMOVED)->count());
        $this->assertSame(2, $compareRun->items()->where('change_type', CompareItem::TYPE_CHANGED)->count());
    }


    public function test_snapshot_compare_detects_body_schema_security_and_header_drift(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();

        $snapshotA = Snapshot::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'name' => 'Baseline',
            'endpoint_count' => 1,
        ]);
        $snapshotB = Snapshot::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'name' => 'Current',
            'endpoint_count' => 1,
        ]);

        $snapshotA->items()->create([
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/{id}',
            'auth_required' => true,
            'risk_level' => Endpoint::RISK_HIGH,
            'status_code' => 200,
            'content_type' => 'application/json',
            'metadata_json' => [
                'headers' => ['content-type' => 'application/json', 'strict-transport-security' => 'max-age=31536000'],
                'body_preview' => '{"id":1,"name":"Alice"}',
                'sensitive_data_detected' => false,
                'sensitive_data_count' => 0,
                'broken_auth_detected' => false,
            ],
        ]);
        $snapshotB->items()->create([
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/{id}',
            'auth_required' => true,
            'risk_level' => Endpoint::RISK_HIGH,
            'status_code' => 200,
            'content_type' => 'application/json',
            'metadata_json' => [
                'headers' => ['content-type' => 'application/json; charset=utf-8'],
                'body_preview' => '{"id":"1","name":"Alice","email":"alice@example.test"}',
                'sensitive_data_detected' => true,
                'sensitive_data_count' => 1,
                'broken_auth_detected' => true,
            ],
        ]);

        $compareRun = app(SnapshotService::class)->compare($snapshotA, $snapshotB, $admin);

        $this->assertDatabaseHas('compare_items', ['compare_run_id' => $compareRun->id, 'field_changed' => 'body_preview']);
        $this->assertDatabaseHas('compare_items', ['compare_run_id' => $compareRun->id, 'field_changed' => 'response_schema']);
        $this->assertDatabaseHas('compare_items', ['compare_run_id' => $compareRun->id, 'field_changed' => 'sensitive_data']);
        $this->assertDatabaseHas('compare_items', ['compare_run_id' => $compareRun->id, 'field_changed' => 'broken_auth']);
        $this->assertDatabaseHas('compare_items', ['compare_run_id' => $compareRun->id, 'field_changed' => 'security_header']);
        $this->assertGreaterThanOrEqual(1, $compareRun->fresh()->summary_json['breaking_count'] ?? 0);

        $this->actingAs($admin)
            ->get(route('projects.snapshots.compares.show', [$project, $compareRun]))
            ->assertOk()
            ->assertSee(__('messages.snapshots.diff_viewer_title'))
            ->assertSee(__('messages.snapshots.diff_groups.schema'))
            ->assertSee(__('messages.snapshots.breaking_changes'));
    }

    public function test_snapshot_index_renders_compare_ui(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('projects.snapshots.index', $project))
            ->assertOk()
            ->assertSee('Snapshots')
            ->assertSee('Compare snapshots');
    }
}
