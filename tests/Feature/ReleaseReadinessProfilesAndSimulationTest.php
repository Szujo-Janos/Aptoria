<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ReleaseReadinessRule;
use App\Models\User;
use App\Services\ReleaseReadinessProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleaseReadinessProfilesAndSimulationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_application_updates_readiness_rules(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        ReleaseReadinessRule::syncDefaults($project);

        app(ReleaseReadinessProfileService::class)->apply($project, 'strict');

        $this->assertSame('strict', app(ReleaseReadinessProfileService::class)->currentProfile($project));
        $this->assertSame('blocker', $project->releaseReadinessRules()->where('rule_key', 'high_findings')->value('failure_level'));
    }

    public function test_simulation_route_accepts_form_method_spoofed_put_from_rule_builder_form(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        ReleaseReadinessRule::syncDefaults($project);

        $payload = ['_method' => 'PUT', 'rules' => []];
        foreach ($project->releaseReadinessRules as $rule) {
            $payload['rules'][$rule->id] = [
                'enabled' => (string) (int) $rule->enabled,
                'failure_level' => $rule->failure_level,
            ];
        }

        $this->actingAs($user)
            ->post(route('projects.release-readiness.rules.simulate', $project), $payload)
            ->assertOk()
            ->assertSee(__('messages.release_readiness.simulation_title'));
    }
}
