<?php

namespace Tests\Feature;

use App\Models\AuthProfile;
use App\Models\Endpoint;
use App\Models\Environment;
use App\Models\Project;
use App\Models\ProjectSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvironmentManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_environment_manager_renders_project_environments_and_default_marker(): void
    {
        $admin = User::query()->create([
            'name' => 'Environment Admin',
            'email' => 'environment@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Environment API',
            'slug' => 'environment-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $authProfile = AuthProfile::query()->create([
            'project_id' => $project->id,
            'name' => 'Bearer staging',
            'type' => AuthProfile::TYPE_BEARER,
            'encrypted_token' => 'masked-token',
            'is_default' => true,
        ]);

        $staging = Environment::query()->create([
            'project_id' => $project->id,
            'name' => 'Staging',
            'environment_type' => Environment::TYPE_STAGING,
            'base_url' => 'https://staging.example.test',
            'auth_profile_id' => $authProfile->id,
            'is_production' => false,
        ]);

        Environment::query()->create([
            'project_id' => $project->id,
            'name' => 'Production',
            'environment_type' => Environment::TYPE_PRODUCTION,
            'base_url' => 'https://api.example.test',
            'is_production' => true,
        ]);

        Endpoint::query()->create([
            'project_id' => $project->id,
            'environment_id' => $staging->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/health',
            'name' => 'Health',
            'risk_level' => Endpoint::RISK_LOW,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        ProjectSetting::query()->create([
            'project_id' => $project->id,
            'key' => 'scan.default_environment_id',
            'value' => (string) $staging->id,
            'type' => 'string',
            'group' => 'scan_defaults',
        ]);

        $this->actingAs($admin)
            ->get(route('projects.environments.index', $project))
            ->assertOk()
            ->assertSee(__('messages.environments.manager_title'))
            ->assertSee('Staging')
            ->assertSee('Production')
            ->assertSee('https://staging.example.test')
            ->assertSee(__('messages.environments.default_environment'))
            ->assertSee('Bearer staging');
    }

    public function test_environment_can_be_created_and_marked_as_default(): void
    {
        $admin = User::query()->create([
            'name' => 'Environment Create Admin',
            'email' => 'environment-create@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Environment Create API',
            'slug' => 'environment-create-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('projects.environments.store', $project), [
                'name' => 'Dev',
                'environment_type' => Environment::TYPE_DEV,
                'base_url' => 'https://dev.example.test',
                'make_default' => '1',
            ])
            ->assertRedirect(route('projects.environments.index', $project));

        $environment = Environment::query()->where('project_id', $project->id)->where('name', 'Dev')->firstOrFail();

        $this->assertSame(Environment::TYPE_DEV, $environment->environment_type);
        $this->assertFalse((bool) $environment->is_production);
        $this->assertSame((string) $environment->id, ProjectSetting::query()->where('project_id', $project->id)->where('key', 'scan.default_environment_id')->value('value'));
    }

    public function test_environment_default_can_be_changed_from_manager(): void
    {
        $admin = User::query()->create([
            'name' => 'Environment Default Admin',
            'email' => 'environment-default@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Environment Default API',
            'slug' => 'environment-default-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        Environment::query()->create([
            'project_id' => $project->id,
            'name' => 'Staging',
            'environment_type' => Environment::TYPE_STAGING,
            'base_url' => 'https://staging.example.test',
            'is_production' => false,
        ]);

        $production = Environment::query()->create([
            'project_id' => $project->id,
            'name' => 'Production',
            'environment_type' => Environment::TYPE_PRODUCTION,
            'base_url' => 'https://api.example.test',
            'is_production' => true,
        ]);

        $this->actingAs($admin)
            ->patch(route('projects.environments.default', [$project, $production]))
            ->assertRedirect(route('projects.environments.index', $project));

        $this->assertSame((string) $production->id, ProjectSetting::query()->where('project_id', $project->id)->where('key', 'scan.default_environment_id')->value('value'));
    }
}
