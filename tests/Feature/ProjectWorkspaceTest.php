<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\SetupStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_can_be_selected_as_current_workspace(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('projects.switch', $project))
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame($project->id, session('current_project_id'));
    }

    public function test_project_scoped_placeholder_is_available(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('projects.modules.show', [$project, 'endpoint-inventory']))
            ->assertOk()
            ->assertSee($project->name);
    }

    public function test_no_project_module_placeholder_guides_user_to_create_project(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);

        $this->actingAs($user)
            ->get(route('modules.show', 'environments'))
            ->assertOk()
            ->assertSee(__('messages.workspace.no_current_project'))
            ->assertSee(__('messages.projects.new'));
    }

    public function test_auth_profile_tester_runs_safe_get_request(): void
    {
        app(SetupStateService::class)->markInstalled();
        $user = User::factory()->create(['password_change_required' => false]);
        $project = Project::factory()->create(['user_id' => $user->id, 'base_url' => 'https://api.example.test']);
        $profile = $project->authProfiles()->create([
            'name' => 'Regression bearer',
            'type' => 'bearer',
            'encrypted_token' => 'secret-token',
            'is_default' => true,
        ]);

        Http::fake([
            'https://api.example.test/api/me' => Http::response(['ok' => true], 200, ['Content-Type' => 'application/json']),
        ]);

        $this->actingAs($user)
            ->post(route('projects.auth-profiles.test', $project), [
                'auth_profile_id' => $profile->id,
                'method' => 'GET',
                'test_path' => '/api/me',
                'expected_status' => 200,
            ])
            ->assertRedirect(route('projects.auth-profiles.index', $project))
            ->assertSessionHas('auth_profile_test_result', function (array $result): bool {
                return ($result['state'] ?? null) === 'passed'
                    && ($result['status_code'] ?? null) === 200
                    && ($result['auth_profile_name'] ?? null) === 'Regression bearer';
            });
    }

}
