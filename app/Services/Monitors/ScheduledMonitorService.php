<?php

namespace App\Services\Monitors;

use App\Models\ApiMonitor;
use App\Models\CompareRun;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Models\User;
use App\Services\RegressionEvaluationService;
use App\Services\SafeProbeService;
use App\Services\Snapshots\SnapshotService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScheduledMonitorService
{
    public function __construct(
        private readonly SafeProbeService $safeProbe,
        private readonly SnapshotService $snapshots,
        private readonly RegressionEvaluationService $regressions,
        private readonly MonitorAlertService $alerts,
    ) {
    }

    /**
     * Run due monitors.
     *
     * Supported options:
     * - project: project id or slug
     * - monitor: monitor id
     * - force: run matching enabled monitors even when next_run_at is in the future
     * - dry_run: list matching monitors without executing safe scans
     *
     * @param array{project?:string|int|null, monitor?:string|int|null, force?:bool, dry_run?:bool} $options
     * @return array{checked:int,due:int,ran:int,failed:int,warnings:int,regressions:int,skipped:int,alerts:int,alert_failures:int,dry_run:bool,monitors:array<int, array<string, mixed>>}
     */
    public function runDue(int $limit = 50, array $options = []): array
    {
        $limit = max(1, $limit);
        $force = (bool) ($options['force'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $monitors = ApiMonitor::query()
            ->with(['project', 'environment', 'baselineSnapshot', 'lastSnapshot'])
            ->where('is_enabled', true)
            ->when(! $force, function (Builder $query): void {
                $query->where(function (Builder $due): void {
                    $due->whereNull('next_run_at')->orWhere('next_run_at', '<=', now());
                });
            })
            ->when($options['project'] ?? null, function (Builder $query, string|int $project): void {
                $query->whereHas('project', function (Builder $projectQuery) use ($project): void {
                    $projectQuery->where('id', $project)->orWhere('slug', $project);
                });
            })
            ->when($options['monitor'] ?? null, function (Builder $query, string|int $monitorId): void {
                $query->whereKey((int) $monitorId);
            })
            ->orderByRaw('next_run_at IS NULL DESC')
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get();

        $summary = [
            'checked' => $monitors->count(),
            'due' => $monitors->count(),
            'ran' => 0,
            'failed' => 0,
            'warnings' => 0,
            'regressions' => 0,
            'skipped' => 0,
            'alerts' => 0,
            'alert_failures' => 0,
            'dry_run' => $dryRun,
            'monitors' => [],
        ];

        foreach ($monitors as $monitor) {
            if ($dryRun) {
                $summary['skipped']++;
                $summary['monitors'][] = $this->previewMonitor($monitor);
                continue;
            }

            $summary['ran']++;
            $result = $this->runMonitor($monitor);
            $status = $result['status'] ?? null;

            if ($status === ApiMonitor::STATUS_FAILED) {
                $summary['failed']++;
            }

            if ($status === ApiMonitor::STATUS_WARNING) {
                $summary['warnings']++;
            }

            if ($status === ApiMonitor::STATUS_REGRESSION) {
                $summary['regressions']++;
            }

            $summary['alerts'] += (int) ($result['alert_events_count'] ?? 0);
            $summary['alert_failures'] += (int) ($result['alert_failures_count'] ?? 0);

            $summary['monitors'][] = $result;
        }

        return $summary;
    }

    /** @return array<string, mixed> */
    public function runMonitor(ApiMonitor $monitor, ?User $user = null): array
    {
        $monitor->loadMissing(['project', 'environment', 'baselineSnapshot', 'lastSnapshot']);
        $previousStatus = $monitor->last_status;
        $project = $monitor->project;
        $previousSnapshot = $monitor->lastSnapshot;

        if (! $project || ! $project->is_active) {
            return $this->markFailure($monitor, __('messages.monitors.messages.project_inactive'), $previousStatus);
        }

        try {
            $scanRun = $this->safeProbe->runProject($project, $monitor->environment, $user);
            $snapshot = null;
            $compareRun = null;
            $regression = null;
            $status = ApiMonitor::STATUS_HEALTHY;
            $message = __('messages.monitors.messages.healthy');

            if ($scanRun->status !== ScanRun::STATUS_COMPLETED) {
                $status = ApiMonitor::STATUS_FAILED;
                $message = $scanRun->error_message ?: __('messages.monitors.messages.scan_failed');
            }

            if ($scanRun->status === ScanRun::STATUS_COMPLETED && $monitor->auto_snapshot) {
                $snapshot = $this->snapshots->createFromScanRun(
                    $scanRun,
                    $user,
                    __('messages.monitors.snapshot_name', ['name' => $monitor->name, 'date' => now()->format('Y-m-d H:i')]),
                    __('messages.monitors.snapshot_description')
                );
            }

            if ($snapshot instanceof Snapshot && $monitor->auto_compare) {
                $baseline = $monitor->baselineSnapshot ?: $previousSnapshot;

                if ($baseline instanceof Snapshot && $baseline->id !== $snapshot->id) {
                    $compareRun = $this->snapshots->compare($baseline, $snapshot, $user);
                    $regression = $this->regressions->evaluateCompare($compareRun);

                    if (($regression['status'] ?? null) === RegressionEvaluationService::STATUS_DETECTED) {
                        $status = ApiMonitor::STATUS_REGRESSION;
                        $message = __('messages.monitors.messages.regression_detected', ['count' => $regression['detected_count'] ?? 0]);
                    } elseif (($regression['status'] ?? null) === RegressionEvaluationService::STATUS_WARNING) {
                        $status = ApiMonitor::STATUS_WARNING;
                        $message = __('messages.monitors.messages.regression_warning', ['count' => $regression['warning_count'] ?? 0]);
                    } elseif ($status === ApiMonitor::STATUS_HEALTHY) {
                        $message = __('messages.monitors.messages.no_regression');
                    }
                } elseif ($status === ApiMonitor::STATUS_HEALTHY) {
                    $message = __('messages.monitors.messages.no_baseline');
                }
            }

            $summary = [
                'scan_status' => $scanRun->status,
                'total_endpoints' => $scanRun->total_endpoints,
                'scanned_count' => $scanRun->scanned_count,
                'skipped_count' => $scanRun->skipped_count,
                'success_count' => $scanRun->success_count,
                'warning_count' => $scanRun->warning_count,
                'error_count' => $scanRun->error_count,
                'snapshot_id' => $snapshot?->id,
                'compare_run_id' => $compareRun?->id,
                'regression_status' => $regression['status'] ?? RegressionEvaluationService::STATUS_NONE,
                'regression_detected_count' => $regression['detected_count'] ?? 0,
                'regression_warning_count' => $regression['warning_count'] ?? 0,
                'regression_recovered_count' => $regression['recovered_count'] ?? 0,
                'regression_improved_count' => $regression['improved_count'] ?? 0,
            ];

            $monitor->update([
                'last_run_at' => now(),
                'next_run_at' => $this->nextRunAt($monitor->frequency),
                'last_scan_run_id' => $scanRun->id,
                'last_snapshot_id' => $snapshot?->id ?: $monitor->last_snapshot_id,
                'last_compare_run_id' => $compareRun?->id,
                'last_status' => $status,
                'last_message' => $message,
                'summary_json' => $summary,
            ]);

            $monitor->refresh();
            $result = [
                'monitor_id' => $monitor->id,
                'project_id' => $monitor->project_id,
                'project' => $project->name,
                'name' => $monitor->name,
                'status' => $status,
                'previous_status' => $previousStatus,
                'message' => $message,
                'scan_run_id' => $scanRun->id,
                'snapshot_id' => $snapshot?->id,
                'compare_run_id' => $compareRun?->id,
                'next_run_at' => $monitor->next_run_at?->toDateTimeString(),
            ];

            $alertEvents = $this->alerts->notify($monitor, $result, $previousStatus);
            $result['alert_events_count'] = count($alertEvents);
            $result['alert_failures_count'] = collect($alertEvents)
                ->where('delivery_status', \App\Models\MonitorAlertEvent::DELIVERY_FAILED)
                ->count();

            return $result;
        } catch (Throwable $exception) {
            Log::warning('Aptoria monitor failed', [
                'monitor_id' => $monitor->id,
                'exception' => $exception->getMessage(),
            ]);

            return $this->markFailure($monitor, $exception->getMessage(), $previousStatus);
        }
    }

    public function nextRunAt(string $frequency, ?Carbon $from = null): Carbon
    {
        $from ??= now();

        return match ($frequency) {
            ApiMonitor::FREQUENCY_HOURLY => $from->copy()->addHour(),
            ApiMonitor::FREQUENCY_WEEKLY => $from->copy()->addWeek(),
            default => $from->copy()->addDay(),
        };
    }

    /** @return array<string, mixed> */
    private function markFailure(ApiMonitor $monitor, string $message, ?string $previousStatus = null): array
    {
        $previousStatus ??= $monitor->last_status;

        $monitor->update([
            'last_run_at' => now(),
            'next_run_at' => $this->nextRunAt($monitor->frequency),
            'last_status' => ApiMonitor::STATUS_FAILED,
            'last_message' => $message,
            'summary_json' => [
                'error' => $message,
            ],
        ]);

        $monitor->refresh();
        $result = [
            'monitor_id' => $monitor->id,
            'project_id' => $monitor->project_id,
            'project' => $monitor->project?->name,
            'name' => $monitor->name,
            'status' => ApiMonitor::STATUS_FAILED,
            'previous_status' => $previousStatus,
            'message' => $message,
            'next_run_at' => $monitor->next_run_at?->toDateTimeString(),
        ];

        $alertEvents = $this->alerts->notify($monitor, $result, $previousStatus);
        $result['alert_events_count'] = count($alertEvents);
        $result['alert_failures_count'] = collect($alertEvents)
            ->where('delivery_status', \App\Models\MonitorAlertEvent::DELIVERY_FAILED)
            ->count();

        return $result;
    }

    /** @return array<string, mixed> */
    private function previewMonitor(ApiMonitor $monitor): array
    {
        return [
            'monitor_id' => $monitor->id,
            'project_id' => $monitor->project_id,
            'project' => $monitor->project?->name,
            'name' => $monitor->name,
            'status' => 'due',
            'message' => 'Dry run only. No scan was executed.',
            'next_run_at' => $monitor->next_run_at?->toDateTimeString(),
            'frequency' => $monitor->frequency,
        ];
    }
}
