<?php

namespace Tests\Feature;

use App\Mail\MonitorAlertMail;
use App\Models\ApiMonitor;
use App\Models\MonitorAlertEvent;
use App\Models\Project;
use App\Models\User;
use App\Services\Monitors\MonitorAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MonitorAlertingTest extends TestCase
{
    use RefreshDatabase;

    public function test_monitor_alert_service_records_dashboard_alert_on_state_change(): void
    {
        $monitor = $this->monitor([
            'last_status' => ApiMonitor::STATUS_HEALTHY,
            'notify_dashboard' => true,
        ]);

        $events = app(MonitorAlertService::class)->notify($monitor, [
            'status' => ApiMonitor::STATUS_FAILED,
            'message' => 'Project is inactive.',
        ], ApiMonitor::STATUS_HEALTHY);

        $this->assertCount(1, $events);
        $this->assertDatabaseHas('monitor_alert_events', [
            'api_monitor_id' => $monitor->id,
            'channel' => MonitorAlertEvent::CHANNEL_DASHBOARD,
            'status' => ApiMonitor::STATUS_FAILED,
            'previous_status' => ApiMonitor::STATUS_HEALTHY,
            'delivery_status' => MonitorAlertEvent::DELIVERY_RECORDED,
        ]);
        $this->assertSame(ApiMonitor::STATUS_FAILED, $monitor->fresh()->last_alert_status);
    }

    public function test_monitor_alert_service_posts_webhook_payload_on_state_change(): void
    {
        Http::fake([
            'https://hooks.example.test/*' => Http::response(['ok' => true], 200),
        ]);

        $monitor = $this->monitor([
            'last_status' => ApiMonitor::STATUS_HEALTHY,
            'alert_webhook_url' => 'https://hooks.example.test/aptoria',
        ]);

        app(MonitorAlertService::class)->notify($monitor, [
            'status' => ApiMonitor::STATUS_REGRESSION,
            'message' => 'Regression detected on 2 endpoint(s).',
        ], ApiMonitor::STATUS_HEALTHY);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://hooks.example.test/aptoria'
            && $request['event'] === 'monitor_status_changed'
            && $request['status'] === ApiMonitor::STATUS_REGRESSION
            && $request['monitor_id'] === $monitor->id);

        $this->assertDatabaseHas('monitor_alert_events', [
            'api_monitor_id' => $monitor->id,
            'channel' => MonitorAlertEvent::CHANNEL_WEBHOOK,
            'delivery_status' => MonitorAlertEvent::DELIVERY_SENT,
        ]);
    }


    public function test_monitor_alert_service_sends_email_alert_on_state_change(): void
    {
        Mail::fake();

        $monitor = $this->monitor([
            'last_status' => ApiMonitor::STATUS_HEALTHY,
            'alert_email' => 'qa@example.test',
        ]);

        app(MonitorAlertService::class)->notify($monitor, [
            'status' => ApiMonitor::STATUS_FAILED,
            'message' => 'Monitor scan failed.',
        ], ApiMonitor::STATUS_HEALTHY);

        Mail::assertSent(MonitorAlertMail::class, fn (MonitorAlertMail $mail): bool => $mail->hasTo('qa@example.test')
            && ($mail->payload['status'] ?? null) === ApiMonitor::STATUS_FAILED
            && ($mail->payload['monitor_id'] ?? null) === $monitor->id);

        $this->assertDatabaseHas('monitor_alert_events', [
            'api_monitor_id' => $monitor->id,
            'channel' => MonitorAlertEvent::CHANNEL_EMAIL,
            'delivery_status' => MonitorAlertEvent::DELIVERY_SENT,
        ]);
    }

    public function test_monitor_alert_history_page_renders_recorded_events(): void
    {
        $monitor = $this->monitor([
            'last_status' => ApiMonitor::STATUS_HEALTHY,
        ]);

        MonitorAlertEvent::query()->create([
            'api_monitor_id' => $monitor->id,
            'project_id' => $monitor->project_id,
            'channel' => MonitorAlertEvent::CHANNEL_EMAIL,
            'severity' => 'critical',
            'status' => ApiMonitor::STATUS_FAILED,
            'previous_status' => ApiMonitor::STATUS_HEALTHY,
            'message' => 'Monitor scan failed.',
            'delivery_status' => MonitorAlertEvent::DELIVERY_SENT,
            'delivery_message' => 'Email sent to qa@example.test.',
            'delivered_at' => now(),
        ]);

        $this->actingAs(User::query()->first())
            ->get(route('projects.monitors.alerts', [$monitor->project, $monitor]))
            ->assertOk()
            ->assertSee('Monitor alert history')
            ->assertSee('email')
            ->assertSee('Monitor scan failed.');
    }


    public function test_monitor_alert_can_be_acknowledged_from_history_page(): void
    {
        $monitor = $this->monitor([
            'last_status' => ApiMonitor::STATUS_HEALTHY,
        ]);
        $user = User::query()->first();

        $alert = MonitorAlertEvent::query()->create([
            'api_monitor_id' => $monitor->id,
            'project_id' => $monitor->project_id,
            'channel' => MonitorAlertEvent::CHANNEL_DASHBOARD,
            'severity' => 'critical',
            'status' => ApiMonitor::STATUS_FAILED,
            'previous_status' => ApiMonitor::STATUS_HEALTHY,
            'message' => 'Monitor scan failed.',
            'delivery_status' => MonitorAlertEvent::DELIVERY_RECORDED,
        ]);

        $this->actingAs($user)
            ->post(route('projects.monitors.alerts.acknowledge', [$monitor->project, $monitor, $alert]), [
                'acknowledgement_note' => 'Investigated by QA.',
            ])
            ->assertRedirect(route('projects.monitors.alerts', [$monitor->project, $monitor]));

        $this->assertDatabaseHas('monitor_alert_events', [
            'id' => $alert->id,
            'acknowledged_by' => $user->id,
            'acknowledgement_note' => 'Investigated by QA.',
        ]);
        $this->assertNotNull($alert->fresh()->acknowledged_at);
    }

    public function test_monitor_alert_service_does_not_repeat_same_problem_status(): void
    {
        $monitor = $this->monitor([
            'last_status' => ApiMonitor::STATUS_FAILED,
            'notify_dashboard' => true,
        ]);

        $events = app(MonitorAlertService::class)->notify($monitor, [
            'status' => ApiMonitor::STATUS_FAILED,
            'message' => 'Still failing.',
        ], ApiMonitor::STATUS_FAILED);

        $this->assertSame([], $events);
        $this->assertDatabaseCount('monitor_alert_events', 0);
    }

    public function test_monitor_alert_service_records_recovery_when_enabled(): void
    {
        $monitor = $this->monitor([
            'last_status' => ApiMonitor::STATUS_FAILED,
            'alert_on_recovery' => true,
        ]);

        app(MonitorAlertService::class)->notify($monitor, [
            'status' => ApiMonitor::STATUS_HEALTHY,
            'message' => 'Monitor recovered.',
        ], ApiMonitor::STATUS_FAILED);

        $this->assertDatabaseHas('monitor_alert_events', [
            'api_monitor_id' => $monitor->id,
            'severity' => 'recovery',
            'status' => ApiMonitor::STATUS_HEALTHY,
            'previous_status' => ApiMonitor::STATUS_FAILED,
        ]);
    }


    public function test_monitor_alert_service_sends_new_alert_when_problem_trigger_fingerprint_changes(): void
    {
        $monitor = $this->monitor([
            'last_status' => ApiMonitor::STATUS_FAILED,
            'notify_dashboard' => true,
        ]);

        app(MonitorAlertService::class)->notify($monitor, [
            'status' => ApiMonitor::STATUS_FAILED,
            'message' => 'Critical API risk detected.',
            'alert_triggers' => ['http_5xx'],
            'alert_trigger_summary' => 'HTTP 5xx responses: 1',
            'scan_alert_summary' => ['http_5xx' => 1, 'triggers' => ['http_5xx']],
        ], ApiMonitor::STATUS_FAILED);

        $this->assertDatabaseHas('monitor_alert_events', [
            'api_monitor_id' => $monitor->id,
            'channel' => MonitorAlertEvent::CHANNEL_DASHBOARD,
            'status' => ApiMonitor::STATUS_FAILED,
            'message' => 'Critical API risk detected.',
        ]);
        $this->assertNotNull($monitor->fresh()->last_alert_fingerprint);
    }

    public function test_monitor_test_notification_records_dashboard_and_sends_configured_channels(): void
    {
        Mail::fake();
        Http::fake([
            'https://hooks.example.test/*' => Http::response(['ok' => true], 200),
        ]);

        $monitor = $this->monitor([
            'notify_dashboard' => true,
            'alert_email' => 'qa@example.test',
            'alert_webhook_url' => 'https://hooks.example.test/aptoria-test',
        ]);

        $events = app(MonitorAlertService::class)->sendTest($monitor);

        $this->assertCount(3, $events);
        Mail::assertSent(MonitorAlertMail::class, fn (MonitorAlertMail $mail): bool => $mail->hasTo('qa@example.test')
            && ($mail->payload['event'] ?? null) === 'monitor_test_notification');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://hooks.example.test/aptoria-test'
            && $request['event'] === 'monitor_test_notification');
        $this->assertDatabaseHas('monitor_alert_events', [
            'api_monitor_id' => $monitor->id,
            'status' => MonitorAlertEvent::STATUS_TEST,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function monitor(array $attributes = []): ApiMonitor
    {
        $user = User::factory()->create();
        $project = Project::query()->create([
            'user_id' => $user->id,
            'name' => 'Alerting Demo',
            'slug' => 'alerting-demo',
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        return ApiMonitor::query()->create(array_merge([
            'project_id' => $project->id,
            'name' => 'Alerting monitor',
            'frequency' => ApiMonitor::FREQUENCY_DAILY,
            'is_enabled' => true,
            'auto_snapshot' => true,
            'auto_compare' => true,
            'notify_dashboard' => true,
            'alert_on_recovery' => true,
            'next_run_at' => now()->subMinute(),
        ], $attributes));
    }
}
