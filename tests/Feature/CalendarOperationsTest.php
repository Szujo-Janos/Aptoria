<?php

namespace Tests\Feature;

use App\Models\ApiMonitor;
use App\Models\Environment;
use App\Models\AuthProfile;
use App\Models\CalendarEvent;
use App\Models\MonitorAlertEvent;
use App\Models\Project;
use App\Models\ProjectSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_page_renders_and_stores_manual_event(): void
    {
        [$user, $project] = $this->userAndProject();

        $this->actingAs($user)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('QA Operations Calendar');

        $this->actingAs($user)
            ->post(route('calendar.store'), [
                'project_id' => $project->id,
                'title' => 'Release regression checkpoint',
                'description' => 'Review smoke test evidence before release.',
                'event_type' => CalendarEvent::TYPE_RELEASE_CHECKPOINT,
                'status' => CalendarEvent::STATUS_PLANNED,
                'priority' => CalendarEvent::PRIORITY_HIGH,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('calendar.index', ['project_id' => $project->id]));

        $this->assertDatabaseHas('calendar_events', [
            'project_id' => $project->id,
            'title' => 'Release regression checkpoint',
            'event_type' => CalendarEvent::TYPE_RELEASE_CHECKPOINT,
            'priority' => CalendarEvent::PRIORITY_HIGH,
        ]);
    }

    public function test_calendar_event_can_be_completed(): void
    {
        [$user, $project] = $this->userAndProject();
        $event = CalendarEvent::query()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => 'Retest failed endpoint',
            'event_type' => CalendarEvent::TYPE_REGRESSION_RETEST,
            'status' => CalendarEvent::STATUS_PLANNED,
            'priority' => CalendarEvent::PRIORITY_NORMAL,
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->patch(route('calendar.complete', $event))
            ->assertRedirect(route('calendar.index', ['project_id' => $project->id]));

        $event->refresh();
        $this->assertSame(CalendarEvent::STATUS_COMPLETED, $event->status);
        $this->assertNotNull($event->completed_at);
    }

    public function test_monitor_alert_follow_up_creates_linked_calendar_event(): void
    {
        [$user, $project] = $this->userAndProject();
        $monitor = ApiMonitor::query()->create([
            'project_id' => $project->id,
            'name' => 'Daily API monitor',
            'frequency' => ApiMonitor::FREQUENCY_DAILY,
            'is_enabled' => true,
            'auto_snapshot' => true,
            'auto_compare' => true,
            'notify_dashboard' => true,
            'alert_on_recovery' => true,
            'next_run_at' => now()->addHour(),
        ]);
        $alert = MonitorAlertEvent::query()->create([
            'api_monitor_id' => $monitor->id,
            'project_id' => $project->id,
            'channel' => MonitorAlertEvent::CHANNEL_DASHBOARD,
            'severity' => 'critical',
            'status' => ApiMonitor::STATUS_FAILED,
            'previous_status' => ApiMonitor::STATUS_HEALTHY,
            'message' => 'Monitor scan failed.',
            'delivery_status' => MonitorAlertEvent::DELIVERY_RECORDED,
        ]);

        $this->actingAs($user)
            ->post(route('projects.monitors.alerts.follow-up', [$project, $monitor, $alert]), [
                'starts_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'priority' => CalendarEvent::PRIORITY_HIGH,
            ])
            ->assertRedirect(route('projects.monitors.alerts', [$project, $monitor]));

        $this->assertDatabaseHas('calendar_events', [
            'project_id' => $project->id,
            'api_monitor_id' => $monitor->id,
            'monitor_alert_event_id' => $alert->id,
            'event_type' => CalendarEvent::TYPE_ALERT_FOLLOW_UP,
        ]);
    }

    public function test_calendar_json_feed_and_ics_export_are_available(): void
    {
        [$user, $project] = $this->userAndProject();
        CalendarEvent::query()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => 'Security review window',
            'event_type' => CalendarEvent::TYPE_SECURITY_REVIEW,
            'status' => CalendarEvent::STATUS_PLANNED,
            'priority' => CalendarEvent::PRIORITY_CRITICAL,
            'starts_at' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->getJson(route('calendar.feed'))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Security review window']);

        $this->actingAs($user)
            ->get(route('calendar.ics'))
            ->assertOk()
            ->assertSee('BEGIN:VCALENDAR')
            ->assertSee('Security review window');
    }


    public function test_model_changes_are_recorded_as_immutable_calendar_activity_logs(): void
    {
        $user = User::factory()->create();

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Immutable Activity Demo',
            'slug' => 'immutable-activity-demo',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('calendar_events', [
            'activity_action' => 'created',
            'activity_subject_type' => Project::class,
            'activity_subject_id' => $project->id,
            'event_type' => CalendarEvent::TYPE_ACTIVITY_LOG,
            'is_system_locked' => true,
        ]);

        $project->update(['name' => 'Immutable Activity Demo Updated']);

        $this->assertDatabaseHas('calendar_events', [
            'activity_action' => 'updated',
            'activity_subject_type' => Project::class,
            'activity_subject_id' => $project->id,
            'event_type' => CalendarEvent::TYPE_ACTIVITY_LOG,
            'is_system_locked' => true,
        ]);

        $activityLog = CalendarEvent::query()
            ->where('activity_subject_type', Project::class)
            ->where('activity_subject_id', $project->id)
            ->where('activity_action', 'created')
            ->firstOrFail();

        $this->actingAs($user)
            ->delete(route('calendar.destroy', $activityLog))
            ->assertRedirect(route('calendar.index', ['project_id' => $activityLog->project_id]));

        $this->assertDatabaseHas('calendar_events', [
            'id' => $activityLog->id,
            'is_system_locked' => true,
        ]);
    }

    public function test_activity_log_titles_are_localized_at_render_time(): void
    {
        app()->setLocale('hu');

        $user = User::factory()->create();

        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Localized Activity Demo',
            'slug' => 'localized-activity-demo',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $activityLog = CalendarEvent::query()
            ->where('activity_subject_type', Project::class)
            ->where('activity_subject_id', $project->id)
            ->where('activity_action', 'created')
            ->firstOrFail();

        $this->assertStringContainsString('Létrehozva projekt', $activityLog->fresh()->display_title);
        $this->assertStringContainsString('Automatikus naptárnapló bejegyzés', (string) $activityLog->fresh()->display_description);

        app()->setLocale('en');

        $this->assertStringContainsString('Created project', $activityLog->fresh()->display_title);
        $this->assertStringContainsString('Automatic calendar audit entry', (string) $activityLog->fresh()->display_description);
    }

    public function test_project_setting_activity_log_names_are_human_readable_and_localized(): void
    {
        app()->setLocale('hu');

        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Localized Setting Demo',
            'slug' => 'localized-setting-demo',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        $setting = ProjectSetting::query()->create([
            'project_id' => $project->id,
            'key' => 'scan.enabled',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'scan_defaults',
        ]);

        $activityLog = CalendarEvent::query()
            ->where('activity_subject_type', ProjectSetting::class)
            ->where('activity_subject_id', $setting->id)
            ->where('activity_action', 'created')
            ->firstOrFail();

        $this->assertStringContainsString('Létrehozva projektbeállítás: Safe scan engedélyezése', $activityLog->fresh()->display_title);
        $this->assertStringNotContainsString('scan.enabled', $activityLog->fresh()->display_title);

        app()->setLocale('en');

        $this->assertStringContainsString('Created project setting: Safe scan enabled', $activityLog->fresh()->display_title);
        $this->assertStringNotContainsString('scan.enabled', $activityLog->fresh()->display_title);
    }


    public function test_project_create_route_records_single_project_level_calendar_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('projects.store'), [
                'name' => 'Noise Reduced Project',
                'description' => 'Project setup should not flood the calendar.',
                'base_url' => 'https://api.noise-reduced.test',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $project = Project::query()->where('name', 'Noise Reduced Project')->firstOrFail();

        $this->assertDatabaseHas('calendar_events', [
            'activity_action' => 'created',
            'activity_subject_type' => Project::class,
            'activity_subject_id' => $project->id,
            'event_type' => CalendarEvent::TYPE_ACTIVITY_LOG,
            'is_system_locked' => true,
        ]);

        $this->assertDatabaseMissing('calendar_events', [
            'activity_subject_type' => Environment::class,
            'project_id' => $project->id,
        ]);

        $this->assertDatabaseMissing('calendar_events', [
            'activity_subject_type' => AuthProfile::class,
            'project_id' => $project->id,
        ]);

        $this->assertDatabaseMissing('calendar_events', [
            'activity_subject_type' => ProjectSetting::class,
            'project_id' => $project->id,
        ]);
    }

    public function test_calendar_day_view_and_multiday_feed_are_available(): void
    {
        [$user, $project] = $this->userAndProject();
        $startsAt = now()->startOfMonth()->addDays(4)->setTime(9, 0);
        $endsAt = $startsAt->copy()->addDays(2)->setTime(17, 0);

        CalendarEvent::query()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'title' => 'Maintenance window across days',
            'event_type' => CalendarEvent::TYPE_MAINTENANCE_WINDOW,
            'status' => CalendarEvent::STATUS_PLANNED,
            'priority' => CalendarEvent::PRIORITY_HIGH,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        $this->actingAs($user)
            ->get(route('calendar.day', ['date' => $startsAt->copy()->addDay()->format('Y-m-d'), 'project_id' => $project->id]))
            ->assertOk()
            ->assertSee('Maintenance window across days')
            ->assertSee('multi-day');

        $this->actingAs($user)
            ->getJson(route('calendar.feed', ['from' => $startsAt->copy()->addDay()->format('Y-m-d'), 'to' => $startsAt->copy()->addDay()->format('Y-m-d')]))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Maintenance window across days']);
    }

    /** @return array{0: User, 1: Project} */
    private function userAndProject(): array
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Calendar Demo',
            'slug' => 'calendar-demo',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        return [$user, $project];
    }
}
