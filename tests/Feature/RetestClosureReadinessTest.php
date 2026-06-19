<?php

namespace Tests\Feature;

use App\Models\Finding;
use App\Models\Project;
use App\Models\User;
use App\Services\ReleaseReadinessService;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetestClosureReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_retest_blocks_retest_closure_readiness(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        Finding::factory()->create([
            'project_id' => $project->id,
            'source' => 'regression',
            'status' => 'retest_failed',
            'retest_required' => true,
            'retest_status' => 'failed',
        ]);

        $evaluation = app(ReleaseReadinessService::class)->evaluate($project);
        $closure = $evaluation['retest_closure'];
        $closureCheck = collect($evaluation['checks'])->firstWhere('key', 'retest_closure_clean');
        $regressionCheck = collect($evaluation['checks'])->firstWhere('key', 'regression_retest_closure');

        $this->assertSame('blocked', $closure['status']);
        $this->assertSame(1, $closure['failed']);
        $this->assertSame('blocker', $closureCheck['level']);
        $this->assertSame('blocker', $regressionCheck['level']);
    }

    public function test_passed_retest_is_counted_as_closed(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        Finding::factory()->create([
            'project_id' => $project->id,
            'source' => 'regression',
            'status' => 'verified',
            'retest_required' => false,
            'retest_status' => 'passed',
        ]);

        $evaluation = app(ReleaseReadinessService::class)->evaluate($project);
        $closure = $evaluation['retest_closure'];

        $this->assertSame('closed', $closure['status']);
        $this->assertSame(100, $closure['closure_rate']);
        $this->assertSame(1, $closure['passed']);
        $this->assertSame(0, $closure['open']);
    }
}
