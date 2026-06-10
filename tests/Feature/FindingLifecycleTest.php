<?php

namespace Tests\Feature;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_transition_finding_lifecycle_and_record_history(): void
    {
        $admin = User::query()->create([
            'name' => 'QA Admin',
            'email' => 'qa-lifecycle@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Lifecycle API',
            'slug' => 'lifecycle-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $endpoint = Endpoint::query()->create([
            'project_id' => $project->id,
            'method' => Endpoint::METHOD_GET,
            'path' => '/users/{id}',
            'name' => 'Show user',
            'auth_required' => true,
            'expected_status' => 200,
            'risk_level' => Endpoint::RISK_HIGH,
            'is_active' => true,
            'excluded_from_scan' => false,
        ]);

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'endpoint_id' => $endpoint->id,
            'title' => 'User endpoint leaks data',
            'source' => Finding::SOURCE_SCAN,
            'severity' => Finding::SEVERITY_HIGH,
            'status' => Finding::STATUS_OPEN,
        ]);

        $this->actingAs($admin)
            ->patch(route('projects.findings.lifecycle.update', [$project, $finding]), [
                'status' => Finding::STATUS_CONFIRMED,
                'note' => 'Reviewed and confirmed during QA triage.',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $finding->refresh();

        $this->assertSame(Finding::STATUS_CONFIRMED, $finding->status);
        $this->assertSame($admin->id, $finding->lifecycle_changed_by_user_id);
        $this->assertNotNull($finding->lifecycle_changed_at);
        $this->assertDatabaseHas('finding_lifecycle_events', [
            'finding_id' => $finding->id,
            'from_status' => Finding::STATUS_OPEN,
            'to_status' => Finding::STATUS_CONFIRMED,
        ]);

        $this->actingAs($admin)
            ->get(route('projects.findings.show', [$project, $finding]))
            ->assertOk()
            ->assertSee(__('messages.findings.lifecycle.title'))
            ->assertSee(__('messages.findings.statuses.confirmed'))
            ->assertSee('Reviewed and confirmed during QA triage.');
    }

    public function test_fixed_finding_can_be_reopened_and_counts_as_open(): void
    {
        $admin = User::query()->create([
            'name' => 'QA Admin',
            'email' => 'qa-reopen@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Reopen API',
            'slug' => 'reopen-api',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'title' => 'Regression came back',
            'source' => Finding::SOURCE_REGRESSION,
            'severity' => Finding::SEVERITY_CRITICAL,
            'status' => Finding::STATUS_FIXED,
        ]);

        $this->actingAs($admin)
            ->patch(route('projects.findings.lifecycle.update', [$project, $finding]), [
                'status' => Finding::STATUS_REOPENED,
                'note' => 'Issue returned after regression run.',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $finding->refresh();

        $this->assertSame(Finding::STATUS_REOPENED, $finding->status);
        $this->assertSame(1, $finding->reopened_count);
        $this->assertTrue($finding->is_open);
    }
}
