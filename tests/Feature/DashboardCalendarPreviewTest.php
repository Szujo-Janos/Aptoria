<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardCalendarPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_project_style_shell_and_calendar_preview(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Calendar Preview API',
            'slug' => 'calendar-preview-api',
            'base_url' => 'https://example.test',
            'is_active' => true,
        ]);

        CalendarEvent::query()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => 'Dashboard smoke retest',
            'event_type' => CalendarEvent::TYPE_REGRESSION_RETEST,
            'status' => CalendarEvent::STATUS_PLANNED,
            'priority' => CalendarEvent::PRIORITY_HIGH,
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aptoria-dashboard-project-style-panel', false)
            ->assertSee('Calendar preview')
            ->assertSee('Dashboard smoke retest');
    }

    public function test_project_details_render_project_calendar_preview(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Project Calendar API',
            'slug' => 'project-calendar-api',
            'base_url' => 'https://project-calendar.test',
            'is_active' => true,
        ]);

        CalendarEvent::query()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => 'Project detail release checkpoint',
            'event_type' => CalendarEvent::TYPE_RELEASE_CHECKPOINT,
            'status' => CalendarEvent::STATUS_PLANNED,
            'priority' => CalendarEvent::PRIORITY_HIGH,
            'starts_at' => now()->addDays(2),
        ]);

        $this->actingAs($user)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Calendar preview')
            ->assertSee('Project detail release checkpoint')
            ->assertSee(route('projects.calendar.index', $project), false);
    }
}
