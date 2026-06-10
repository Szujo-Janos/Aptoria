<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\DemoQaProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DemoProjectGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_project_page_imports_comprehensive_sample_workspace(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('demo-project.index'))
            ->assertOk()
            ->assertSee(__('messages.demo_project.heading'))
            ->assertSee(__('messages.demo_project.import_button'));

        $this->actingAs($user)
            ->post(route('demo-project.import'))
            ->assertRedirect();

        $project = Project::query()->where('slug', DemoQaProjectSeeder::PROJECT_SLUG)->firstOrFail();

        $this->assertSame('Northstar Commerce API - Full QA Demo', $project->name);
        $this->assertGreaterThanOrEqual(2, $project->environments()->count());
        $this->assertGreaterThanOrEqual(3, $project->authProfiles()->count());
        $this->assertGreaterThanOrEqual(8, $project->endpoints()->count());
        $this->assertGreaterThanOrEqual(2, $project->scanRuns()->count());
        $this->assertGreaterThanOrEqual(2, $project->snapshots()->count());
        $this->assertGreaterThanOrEqual(4, $project->findings()->count());
        $this->assertGreaterThanOrEqual(1, $project->testSuites()->count());
        $this->assertGreaterThanOrEqual(1, $project->qaReleaseGates()->count());

        $this->assertDatabaseHas('audit_logs', [
            'project_id' => $project->id,
            'event_type' => AuditLog::EVENT_SYSTEM,
            'action' => AuditLog::ACTION_IMPORTED,
            'subject_name' => $project->name,
        ]);

        $this->actingAs($user)
            ->get(route('demo-project.index'))
            ->assertOk()
            ->assertSee(__('messages.demo_project.status_imported'))
            ->assertSee('Northstar Commerce API - Full QA Demo');
    }

    public function test_demo_project_can_be_removed_with_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('demo-project.import'))->assertRedirect();
        $this->assertDatabaseHas('projects', ['slug' => DemoQaProjectSeeder::PROJECT_SLUG]);

        $this->actingAs($user)
            ->delete(route('demo-project.remove'), ['confirm' => '1'])
            ->assertRedirect(route('demo-project.index'));

        $this->assertDatabaseMissing('projects', ['slug' => DemoQaProjectSeeder::PROJECT_SLUG]);
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => AuditLog::EVENT_SYSTEM,
            'action' => AuditLog::ACTION_DELETED,
            'subject_name' => 'Northstar Commerce API - Full QA Demo',
        ]);
    }

    public function test_demo_project_artisan_command_imports_and_removes_demo_data(): void
    {
        $importExitCode = Artisan::call('aptoria:demo-project', ['--json' => true]);
        $this->assertSame(0, $importExitCode);

        $importPayload = json_decode(Artisan::output(), true);
        $this->assertIsArray($importPayload);
        $this->assertSame('imported', $importPayload['action'] ?? null);
        $this->assertSame(DemoQaProjectSeeder::PROJECT_SLUG, $importPayload['slug'] ?? null);

        $this->assertDatabaseHas('projects', ['slug' => DemoQaProjectSeeder::PROJECT_SLUG]);

        $removeExitCode = Artisan::call('aptoria:demo-project', ['--remove' => true, '--json' => true]);
        $this->assertSame(0, $removeExitCode);

        $removePayload = json_decode(Artisan::output(), true);
        $this->assertIsArray($removePayload);
        $this->assertSame('removed', $removePayload['action'] ?? null);
        $this->assertSame(DemoQaProjectSeeder::PROJECT_SLUG, $removePayload['slug'] ?? null);

        $this->assertDatabaseMissing('projects', ['slug' => DemoQaProjectSeeder::PROJECT_SLUG]);
    }
}
