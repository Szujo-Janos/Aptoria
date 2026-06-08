<?php

namespace App\Services\Monitors;

use App\Mail\MonitorAlertMail;
use App\Models\ApiMonitor;
use App\Models\MonitorAlertEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MonitorAlertService
{
    private const PROBLEM_STATUSES = [
        ApiMonitor::STATUS_FAILED,
        ApiMonitor::STATUS_WARNING,
        ApiMonitor::STATUS_REGRESSION,
    ];

    /**
     * Create dashboard/webhook alert records for meaningful monitor state changes.
     *
     * Alerts are intentionally state-change based to avoid repeated notification spam:
     * - healthy -> warning/regression/failed
     * - warning/regression/failed -> another non-healthy state
     * - warning/regression/failed -> healthy, when recovery alerts are enabled
     *
     * @param array<string, mixed> $result
     * @return array<int, MonitorAlertEvent>
     */
    public function notify(ApiMonitor $monitor, array $result, ?string $previousStatus): array
    {
        $status = (string) ($result['status'] ?? '');

        if (! $this->shouldAlert($monitor, $status, $previousStatus)) {
            return [];
        }

        $monitor->loadMissing(['project', 'environment']);
        $payload = $this->payload($monitor, $result, $previousStatus);
        $events = [];

        if ($monitor->notify_dashboard) {
            $events[] = MonitorAlertEvent::query()->create([
                'api_monitor_id' => $monitor->id,
                'project_id' => $monitor->project_id,
                'channel' => MonitorAlertEvent::CHANNEL_DASHBOARD,
                'severity' => $this->severity($status),
                'status' => $status,
                'previous_status' => $previousStatus,
                'message' => $payload['message'] ?? null,
                'payload_json' => $payload,
                'delivery_status' => MonitorAlertEvent::DELIVERY_RECORDED,
                'delivery_message' => 'Recorded for dashboard review.',
                'delivered_at' => now(),
            ]);
        }

        if ($this->validWebhookUrl($monitor->alert_webhook_url)) {
            $events[] = $this->sendWebhook($monitor, $payload, $status, $previousStatus);
        } elseif (filled($monitor->alert_webhook_url)) {
            $events[] = MonitorAlertEvent::query()->create([
                'api_monitor_id' => $monitor->id,
                'project_id' => $monitor->project_id,
                'channel' => MonitorAlertEvent::CHANNEL_WEBHOOK,
                'severity' => $this->severity($status),
                'status' => $status,
                'previous_status' => $previousStatus,
                'message' => $payload['message'] ?? null,
                'payload_json' => $payload,
                'delivery_status' => MonitorAlertEvent::DELIVERY_SKIPPED,
                'delivery_message' => 'Webhook URL is not a valid http(s) URL.',
            ]);
        }

        if ($this->validEmail($monitor->alert_email)) {
            $events[] = $this->sendEmail($monitor, $payload, $status, $previousStatus);
        } elseif (filled($monitor->alert_email)) {
            $events[] = MonitorAlertEvent::query()->create([
                'api_monitor_id' => $monitor->id,
                'project_id' => $monitor->project_id,
                'channel' => MonitorAlertEvent::CHANNEL_EMAIL,
                'severity' => $this->severity($status),
                'status' => $status,
                'previous_status' => $previousStatus,
                'message' => $payload['message'] ?? null,
                'payload_json' => $payload,
                'delivery_status' => MonitorAlertEvent::DELIVERY_SKIPPED,
                'delivery_message' => 'Alert email is not a valid email address.',
            ]);
        }

        if ($events !== []) {
            $monitor->update([
                'last_alert_at' => now(),
                'last_alert_status' => $status,
            ]);
        }

        return $events;
    }

    public function shouldAlert(ApiMonitor $monitor, string $status, ?string $previousStatus): bool
    {
        if ($status === '' || $status === ApiMonitor::STATUS_NEVER_RUN) {
            return false;
        }

        $previousStatus = $previousStatus ?: ApiMonitor::STATUS_NEVER_RUN;
        $isProblem = in_array($status, self::PROBLEM_STATUSES, true);
        $wasProblem = in_array($previousStatus, self::PROBLEM_STATUSES, true);
        $isRecovery = $status === ApiMonitor::STATUS_HEALTHY && $wasProblem && $monitor->alert_on_recovery;

        if (! $isProblem && ! $isRecovery) {
            return false;
        }

        return $status !== $previousStatus;
    }

    /** @param array<string, mixed> $result */
    private function payload(ApiMonitor $monitor, array $result, ?string $previousStatus): array
    {
        return [
            'app' => 'Aptoria',
            'version' => config('aptoria.version'),
            'event' => 'monitor_status_changed',
            'monitor_id' => $monitor->id,
            'monitor' => $monitor->name,
            'project_id' => $monitor->project_id,
            'project' => $monitor->project?->name,
            'environment' => $monitor->environment?->name,
            'status' => $result['status'] ?? null,
            'previous_status' => $previousStatus,
            'severity' => $this->severity((string) ($result['status'] ?? '')),
            'message' => $result['message'] ?? null,
            'scan_run_id' => $result['scan_run_id'] ?? null,
            'snapshot_id' => $result['snapshot_id'] ?? null,
            'compare_run_id' => $result['compare_run_id'] ?? null,
            'next_run_at' => $result['next_run_at'] ?? null,
            'alert_email' => $monitor->alert_email,
            'triggered_at' => now()->toIso8601String(),
        ];
    }

    private function severity(string $status): string
    {
        return match ($status) {
            ApiMonitor::STATUS_FAILED, ApiMonitor::STATUS_REGRESSION => 'critical',
            ApiMonitor::STATUS_WARNING => 'warning',
            ApiMonitor::STATUS_HEALTHY => 'recovery',
            default => 'info',
        };
    }

    private function validWebhookUrl(?string $url): bool
    {
        if (! filled($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true);
    }

    private function validEmail(?string $email): bool
    {
        return filled($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** @param array<string, mixed> $payload */
    private function sendEmail(ApiMonitor $monitor, array $payload, string $status, ?string $previousStatus): MonitorAlertEvent
    {
        $event = MonitorAlertEvent::query()->create([
            'api_monitor_id' => $monitor->id,
            'project_id' => $monitor->project_id,
            'channel' => MonitorAlertEvent::CHANNEL_EMAIL,
            'severity' => $this->severity($status),
            'status' => $status,
            'previous_status' => $previousStatus,
            'message' => $payload['message'] ?? null,
            'payload_json' => $payload,
            'delivery_status' => MonitorAlertEvent::DELIVERY_RECORDED,
            'delivery_message' => 'Email delivery queued.',
        ]);

        try {
            Mail::to((string) $monitor->alert_email)->send(new MonitorAlertMail($event, $payload));

            $event->update([
                'delivery_status' => MonitorAlertEvent::DELIVERY_SENT,
                'delivery_message' => 'Email sent to '.$monitor->alert_email.'.',
                'delivered_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Aptoria monitor email alert failed', [
                'monitor_id' => $monitor->id,
                'exception' => $exception->getMessage(),
            ]);

            $event->update([
                'delivery_status' => MonitorAlertEvent::DELIVERY_FAILED,
                'delivery_message' => $exception->getMessage(),
            ]);
        }

        return $event->fresh();
    }

    /** @param array<string, mixed> $payload */
    private function sendWebhook(ApiMonitor $monitor, array $payload, string $status, ?string $previousStatus): MonitorAlertEvent
    {
        $event = MonitorAlertEvent::query()->create([
            'api_monitor_id' => $monitor->id,
            'project_id' => $monitor->project_id,
            'channel' => MonitorAlertEvent::CHANNEL_WEBHOOK,
            'severity' => $this->severity($status),
            'status' => $status,
            'previous_status' => $previousStatus,
            'message' => $payload['message'] ?? null,
            'payload_json' => $payload,
            'delivery_status' => MonitorAlertEvent::DELIVERY_RECORDED,
            'delivery_message' => 'Webhook delivery queued.',
        ]);

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->asJson()
                ->post((string) $monitor->alert_webhook_url, $payload);

            $event->update([
                'delivery_status' => $response->successful() ? MonitorAlertEvent::DELIVERY_SENT : MonitorAlertEvent::DELIVERY_FAILED,
                'delivery_message' => 'HTTP '.$response->status(),
                'delivered_at' => $response->successful() ? now() : null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Aptoria monitor webhook alert failed', [
                'monitor_id' => $monitor->id,
                'exception' => $exception->getMessage(),
            ]);

            $event->update([
                'delivery_status' => MonitorAlertEvent::DELIVERY_FAILED,
                'delivery_message' => $exception->getMessage(),
            ]);
        }

        return $event->fresh();
    }
}
