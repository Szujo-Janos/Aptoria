<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoQaProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DemoQaProjectSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_comprehensive_demo_import_covers_the_main_qa_workflows_and_is_repeatable(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->seed(DemoQaProjectSeeder::class);
        $this->assertDemoCoverage();

        $this->seed(DemoQaProjectSeeder::class);
        $this->assertDemoCoverage();
    }

    public function test_setup_wizard_can_import_the_comprehensive_demo(): void
    {
        $response = $this->post(route('setup.demo'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('projects', ['slug' => DemoQaProjectSeeder::PROJECT_SLUG]);
    }

    private function assertDemoCoverage(): void
    {
        $projectId = DB::table('projects')
            ->where('slug', DemoQaProjectSeeder::PROJECT_SLUG)
            ->value('id');

        $this->assertNotNull($projectId);
        $this->assertSame(1, DB::table('projects')->where('slug', DemoQaProjectSeeder::PROJECT_SLUG)->count());

        foreach ([
            'environments' => 2,
            'auth_profiles' => 3,
            'endpoints' => 8,
            'scan_runs' => 2,
            'snapshots' => 2,
            'compare_runs' => 1,
            'api_monitors' => 1,
            'test_suites' => 1,
            'test_cases' => 4,
            'test_case_results' => 4,
            'contract_validation_runs' => 1,
            'contract_validation_results' => 5,
            'findings' => 4,
            'finding_evidence' => 4,
            'qa_release_gates' => 1,
            'qa_release_gate_items' => 5,
        ] as $table => $expectedCount) {
            $this->assertSame(
                $expectedCount,
                DB::table($table)->where('project_id', $projectId)->count(),
                "Unexpected demo record count in {$table}."
            );
        }

        $scanRunIds = DB::table('scan_runs')->where('project_id', $projectId)->pluck('id');
        $snapshotIds = DB::table('snapshots')->where('project_id', $projectId)->pluck('id');
        $compareRunIds = DB::table('compare_runs')->where('project_id', $projectId)->pluck('id');

        $this->assertSame(16, DB::table('scan_results')->whereIn('scan_run_id', $scanRunIds)->count());
        $this->assertSame(16, DB::table('snapshot_items')->whereIn('snapshot_id', $snapshotIds)->count());
        $this->assertSame(4, DB::table('compare_items')->whereIn('compare_run_id', $compareRunIds)->count());
        $this->assertSame(6, DB::table('scan_results')->whereIn('scan_run_id', $scanRunIds)->where('status', 'skipped')->count());
        $this->assertSame(4, DB::table('scan_results')->whereIn('scan_run_id', $scanRunIds)->where('risk_reason', 'like', '%excluded from safe scan%')->count());
        $this->assertSame(2, DB::table('scan_results')->whereIn('scan_run_id', $scanRunIds)->where('risk_reason', 'like', '%explicitly excluded%')->count());
        $this->assertSame('blocked', DB::table('qa_release_gates')->where('project_id', $projectId)->value('final_decision'));
    }
}
