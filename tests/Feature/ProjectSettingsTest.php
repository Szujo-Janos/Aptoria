<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectSetting;
use App\Models\User;
use App\Services\Settings\ProjectSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_settings_page_renders(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('projects.settings.edit', $project))
            ->assertOk()
            ->assertSee('Project Settings')
            ->assertSee('Scan Defaults')
            ->assertSee('Risk Overrides');
    }

    public function test_project_settings_can_be_saved_and_exported(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        $environment = $project->environments()->firstOrFail();
        $authProfile = $project->authProfiles()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('projects.settings.update', $project), [
                'scan_enabled' => '1',
                'scan_default_environment_id' => $environment->id,
                'scan_default_auth_profile_id' => $authProfile->id,
                'scan_max_endpoints_per_scan' => 12,
                'scan_require_confirmation' => '1',
                'scan_store_response_body_preview' => '1',
                'risk_sensitive_keywords' => "billing\nsecret",
                'risk_internal_keywords' => 'ops,diagnostic',
                'project_notes' => 'QA staging profile.',
            ])
            ->assertRedirect(route('projects.settings.edit', $project));

        $this->assertSame('12', ProjectSetting::query()->where('project_id', $project->id)->where('key', 'scan.max_endpoints_per_scan')->value('value'));
        $this->assertSame('billing,secret', ProjectSetting::query()->where('project_id', $project->id)->where('key', 'risk.sensitive_keywords')->value('value'));

        $this->actingAs($admin)
            ->getJson(route('projects.settings.export', $project))
            ->assertOk()
            ->assertJsonFragment(['scan.max_endpoints_per_scan' => 12]);

        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('QA staging profile.');
    }

    public function test_project_setting_defaults_fallback_without_rows(): void
    {
        $this->seed();
        $project = Project::query()->where('slug', 'demo-public-api')->firstOrFail();
        ProjectSetting::query()->where('project_id', $project->id)->delete();

        $this->assertTrue(app(ProjectSettingService::class)->boolean($project, 'scan.enabled', false));
        $this->assertSame(100, app(ProjectSettingService::class)->integer($project, 'scan.max_endpoints_per_scan', 0));
    }
}
