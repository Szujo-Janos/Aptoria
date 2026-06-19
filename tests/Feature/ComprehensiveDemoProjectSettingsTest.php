<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\ComprehensiveDemoProjectService;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveDemoProjectSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_settings_page_exposes_comprehensive_demo_project_action(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);

        $this->actingAs($user)
            ->get(route('program-settings.edit'))
            ->assertOk()
            ->assertSee(__('messages.program_settings.demo_project_title'))
            ->assertSee(__('messages.program_settings.demo_project_button'))
            ->assertSee(route('program-settings.demo-project'), false);
    }

    public function test_comprehensive_demo_project_button_builds_full_workspace_and_redirects_to_project(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);

        $response = $this->actingAs($user)->post(route('program-settings.demo-project'));

        $project = Project::query()->where('slug', ComprehensiveDemoProjectService::DEMO_SLUG)->firstOrFail();

        $response->assertRedirect(route('projects.show', $project));
        $this->assertSame($project->id, session('current_project_id'));

        $this->assertSame(2, $project->environments()->count());
        $this->assertSame(3, $project->authProfiles()->count());
        $this->assertSame(7, $project->endpoints()->count());
        $this->assertSame(1, $project->scanRuns()->count());
        $this->assertSame(7, $project->endpointTestRuns()->count());
        $this->assertSame(5, $project->assertionRules()->count());
        $this->assertSame(2, $project->endpointSnapshots()->count());
        $this->assertSame(1, $project->endpointSnapshotCompares()->count());
        $this->assertSame(4, $project->findings()->count());
        $this->assertSame(4, $project->evidence()->count());
        $this->assertSame(1, $project->riskAcceptances()->count());
        $this->assertSame(1, $project->contractValidationRuns()->count());
        $this->assertSame(1, $project->externalImportRuns()->count());
        $this->assertSame(1, $project->releaseReadinessRuns()->count());
        $this->assertSame(1, $project->releaseDecisionSnapshots()->count());
        $this->assertSame(1, $project->reportVersions()->where('status', 'approved')->count());
        $this->assertSame(1, $project->clientPortalAccesses()->count());
        $this->assertSame(1, $project->clientPortalAcknowledgements()->count());
        $this->assertSame(4, $project->calendarEvents()->count());

        $this->assertDatabaseHas('release_readiness_runs', [
            'project_id' => $project->id,
            'status' => 'blocked',
            'grade' => 'C',
        ]);

        $this->assertDatabaseHas('client_portal_acknowledgements', [
            'project_id' => $project->id,
            'decision_status' => 'needs_changes',
        ]);
    }

    public function test_comprehensive_demo_project_rebuild_replaces_only_demo_workspace(): void
    {
        app(SetupStateService::class)->markInstalled();

        $user = User::factory()->create(['password_change_required' => false]);
        $otherProject = Project::factory()->create(['user_id' => $user->id, 'slug' => 'real-customer-project']);

        $this->actingAs($user)->post(route('program-settings.demo-project'));
        $firstDemoId = Project::query()->where('slug', ComprehensiveDemoProjectService::DEMO_SLUG)->value('id');

        $this->actingAs($user)->post(route('program-settings.demo-project'));
        $secondDemoId = Project::query()->where('slug', ComprehensiveDemoProjectService::DEMO_SLUG)->value('id');

        $this->assertNotSame($firstDemoId, $secondDemoId);
        $this->assertDatabaseHas('projects', ['id' => $otherProject->id, 'slug' => 'real-customer-project']);
        $this->assertSame(1, Project::query()->where('slug', ComprehensiveDemoProjectService::DEMO_SLUG)->count());
    }
}
