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
