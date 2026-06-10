<?php

namespace Tests\Feature;

use App\Models\ApiMonitor;
use App\Models\Environment;
use App\Models\Project;
use App\Models\TestSuite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ScheduledMonitorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_runner_dry_run_lists_due_monitors_without_running_scans(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Scheduler Demo',
            'slug' => 'scheduler-demo',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $monitor = ApiMonitor::query()->create([
            'project_id' => $project->id,
            'name' => 'Daily scheduler smoke',
            'frequency' => ApiMonitor::FREQUENCY_DAILY,
            'is_enabled' => true,
            'auto_snapshot' => true,
            'auto_compare' => true,
            'notify_dashboard' => true,
            'next_run_at' => now()->subMinute(),
        ]);

        $this->artisan('aptoria:run-monitors', ['--dry-run' => true])
            ->expectsOutputToContain('Aptoria scheduled monitor runner')
            ->expectsOutputToContain('Mode: dry-run')
            ->expectsOutputToContain('Scheduler Demo')
            ->assertExitCode(0);

        $this->assertNull($monitor->fresh()->last_run_at);
    }

    public function test_monitor_runner_project_filter_limits_matching_monitors(): void
    {
        $user = User::factory()->create();
        $included = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Included Project',
            'slug' => 'included-project',
            'base_url' => 'https://included.example.test',
            'is_active' => true,
        ]);
        $excluded = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Excluded Project',
            'slug' => 'excluded-project',
            'base_url' => 'https://excluded.example.test',
            'is_active' => true,
        ]);

        foreach ([$included, $excluded] as $project) {
            ApiMonitor::query()->create([
                'project_id' => $project->id,
                'name' => $project->name.' monitor',
                'frequency' => ApiMonitor::FREQUENCY_DAILY,
                'is_enabled' => true,
                'auto_snapshot' => true,
                'auto_compare' => true,
                'notify_dashboard' => true,
                'next_run_at' => now()->subMinute(),
            ]);
        }

        $this->artisan('aptoria:run-monitors', [
            '--dry-run' => true,
            '--project' => 'included-project',
        ])
            ->expectsOutputToContain('Included Project')
            ->doesntExpectOutputToContain('Excluded Project')
            ->assertExitCode(0);
    }


    public function test_monitor_runner_filters_by_environment_and_suite_and_saves_json_summary(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Cron Runner Project',
            'slug' => 'cron-runner-project',
            'base_url' => 'https://cron.example.test',
            'is_active' => true,
        ]);

        $environment = Environment::query()->create([
            'project_id' => $project->id,
            'name' => 'Staging',
            'base_url' => 'https://staging.example.test',
            'environment_type' => Environment::TYPE_STAGING,
            'is_production' => false,
        ]);

        $suite = TestSuite::query()->create([
            'project_id' => $project->id,
            'name' => 'Smoke Regression',
            'status' => TestSuite::STATUS_ACTIVE,
        ]);

        ApiMonitor::query()->create([
            'project_id' => $project->id,
            'environment_id' => $environment->id,
            'test_suite_id' => $suite->id,
            'name' => 'Staging smoke monitor',
            'frequency' => ApiMonitor::FREQUENCY_DAILY,
            'is_enabled' => true,
            'auto_snapshot' => true,
            'auto_compare' => true,
            'notify_dashboard' => true,
            'next_run_at' => now()->subMinute(),
        ]);

        ApiMonitor::query()->create([
            'project_id' => $project->id,
            'name' => 'Whole project monitor',
            'frequency' => ApiMonitor::FREQUENCY_DAILY,
            'is_enabled' => true,
            'auto_snapshot' => true,
            'auto_compare' => true,
            'notify_dashboard' => true,
            'next_run_at' => now()->subMinute(),
        ]);

        $output = storage_path('app/monitor-runs/cron-runner-test.json');
        File::delete($output);

        $this->artisan('aptoria:run-monitors', [
            '--dry-run' => true,
            '--project' => 'cron-runner-project',
            '--environment' => 'staging',
            '--suite' => 'Smoke Regression',
            '--output' => $output,
        ])
            ->assertExitCode(0);

        $this->assertFileExists($output);
        $payload = json_decode((string) file_get_contents($output), true);

        $this->assertSame('cron-runner-project', $payload['filters']['project']);
        $this->assertSame('staging', $payload['filters']['environment']);
        // The suite assertion is checked from the saved JSON summary instead of
        // console text so the test is independent from Symfony table formatting.
        $this->assertSame('Smoke Regression', $payload['filters']['suite']);
        $this->assertSame('Staging', $payload['monitors'][0]['environment']);
        $this->assertSame('Smoke Regression', $payload['monitors'][0]['suite']);
    }

    public function test_monitor_runner_marks_inactive_project_monitor_failed_without_network_call(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Inactive Scheduler Project',
            'slug' => 'inactive-scheduler-project',
            'base_url' => 'https://inactive.example.test',
            'is_active' => false,
        ]);

        $monitor = ApiMonitor::query()->create([
            'project_id' => $project->id,
            'name' => 'Inactive monitor',
            'frequency' => ApiMonitor::FREQUENCY_HOURLY,
            'is_enabled' => true,
            'auto_snapshot' => true,
            'auto_compare' => true,
            'notify_dashboard' => true,
            'next_run_at' => now()->subMinute(),
        ]);

        $this->artisan('aptoria:run-monitors', ['--monitor' => $monitor->id])
            ->expectsOutputToContain('Failed: 1')
            ->assertExitCode(1);

        $monitor->refresh();

        $this->assertSame(ApiMonitor::STATUS_FAILED, $monitor->last_status);
        $this->assertNotNull($monitor->last_run_at);
        $this->assertNotNull($monitor->next_run_at);
    }
}
