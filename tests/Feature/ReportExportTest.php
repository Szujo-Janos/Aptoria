<?php

namespace Tests\Feature;

use App\Models\CompareItem;
use App\Models\Endpoint;
use App\Models\Project;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_center_renders_for_admin(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Reports')
            ->assertSee('Endpoint CSV');
    }

    public function test_endpoint_inventory_csv_downloads(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('projects.reports.endpoints.csv', $project))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('method,path,full_url', false)
            ->assertSee('assertion_status', false)
            ->assertSee('regression_status', false)
            ->assertSee('/todos/1');
    }

    public function test_scan_markdown_snapshot_json_and_compare_markdown_download(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $endpoint = $project->endpoints()->where('method', Endpoint::METHOD_GET)->firstOrFail();

        $scanRun = ScanRun::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'status' => ScanRun::STATUS_COMPLETED,
            'total_endpoints' => 1,
            'scanned_count' => 1,
            'skipped_count' => 0,
            'success_count' => 1,
        ]);
        $scanRun->results()->create([
            'endpoint_id' => $endpoint->id,
            'method' => Endpoint::METHOD_GET,
            'url' => $endpoint->full_url,
            'status' => 'completed',
            'status_code' => 200,
            'response_time_ms' => 123,
            'content_type' => 'application/json',
            'risk_level' => Endpoint::RISK_LOW,
        ]);

        $snapshotA = Snapshot::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'name' => 'A',
            'endpoint_count' => 1,
        ]);
        $snapshotA->items()->create([
            'method' => Endpoint::METHOD_GET,
            'path' => '/todos/1',
            'auth_required' => false,
            'risk_level' => Endpoint::RISK_LOW,
            'status_code' => 200,
        ]);
        $snapshotB = Snapshot::query()->create([
            'project_id' => $project->id,
            'created_by' => $admin->id,
            'name' => 'B',
            'endpoint_count' => 1,
        ]);
        $compareRun = $project->compareRuns()->create([
            'snapshot_a_id' => $snapshotA->id,
            'snapshot_b_id' => $snapshotB->id,
            'created_by' => $admin->id,
            'summary_json' => ['total_changes' => 1, 'new_count' => 1],
        ]);
        $compareRun->items()->create([
            'change_type' => CompareItem::TYPE_NEW,
            'method' => Endpoint::METHOD_GET,
            'path' => '/new',
            'severity' => CompareItem::SEVERITY_INFO,
            'old_value' => 'missing',
            'new_value' => 'Low',
        ]);

        $this->actingAs($admin)
            ->get(route('projects.reports.scans.markdown', [$project, $scanRun]))
            ->assertOk()
            ->assertSee('# Aptoria Scan Report', false)
            ->assertSee('| Assertion | Regression |', false);

        $this->actingAs($admin)
            ->get(route('projects.reports.snapshots.json', [$project, $snapshotA]))
            ->assertOk()
            ->assertSee('"snapshot"', false)
            ->assertSee('"assertion_status"', false);

        $this->actingAs($admin)
            ->get(route('projects.reports.compares.markdown', [$project, $compareRun]))
            ->assertOk()
            ->assertSee('# Aptoria Snapshot Compare Report', false)
            ->assertSee('Regression status', false);
    }
}
