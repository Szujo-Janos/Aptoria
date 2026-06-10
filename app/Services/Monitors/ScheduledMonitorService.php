<?php

namespace App\Services\Monitors;

use App\Models\ApiMonitor;
use App\Models\CompareRun;
use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Models\TestSuite;
use App\Models\User;
use App\Services\AssertionEvaluationService;
use App\Services\RegressionEvaluationService;
use App\Services\SafeProbeService;
use App\Services\Snapshots\SnapshotService;
use App\Services\TestSuites\RegressionSuiteBuilderService;
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
        private readonly RegressionSuiteBuilderService $suiteBuilder,
        private readonly AssertionEvaluationService $assertions,
        private readonly MonitorAlertService $alerts,
    ) {
    }

    /**
     * Run due monitors.
     *
     * Supported options:
     * - project: project id or slug
     * - monitor: monitor id
     * - environment: environment id/name/type or 'default' for project default target
     * - suite: test suite id/name or 'all' for whole-project monitors
     * - force: run matching enabled monitors even when next_run_at is in the future
     * - dry_run: list matching monitors without executing safe scans
     *
     * @param array{project?:string|int|null, monitor?:string|int|null, environment?:string|int|null, suite?:string|int|null, force?:bool, dry_run?:bool} $options
     * @return array{checked:int,due:int,ran:int,failed:int,warnings:int,regressions:int,skipped:int,alerts:int,alert_failures:int,dry_run:bool,monitors:array<int, array<string, mixed>>}
     */
    public function runDue(int $limit = 50, array $options = []): array
    {
        $limit = max(1, $limit);
        $force = (bool) ($options['force'] ?? false);
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $monitors = ApiMonitor::query()
            ->with(['project', 'environment', 'baselineSnapshot', 'lastSnapshot', 'testSuite'])
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
            ->when($options['environment'] ?? null, function (Builder $query, string|int $environment): void {
                $this->applyEnvironmentFilter($query, $environment);
            })
            ->when($options['suite'] ?? null, function (Builder $query, string|int $suite): void {
                $this->applySuiteFilter($query, $suite);
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
        $monitor->loadMissing(['project', 'environment', 'baselineSnapshot', 'lastSnapshot', 'testSuite']);
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

            $suiteSummary = null;
            $suite = $monitor->testSuite;
            if ($scanRun->status === ScanRun::STATUS_COMPLETED && $suite instanceof TestSuite) {
                $suiteSummary = $this->suiteBuilder->runSuite($project, $suite, $user, $this->safeProbe, $this->assertions);

                if (($suiteSummary['fail'] ?? 0) > 0) {
                    $status = ApiMonitor::STATUS_REGRESSION;
                    $message = __('messages.monitors.messages.suite_failed', ['count' => $suiteSummary['fail'], 'suite' => $suite->name]);
                } elseif (($suiteSummary['blocked'] ?? 0) > 0 && $status === ApiMonitor::STATUS_HEALTHY) {
                    $status = ApiMonitor::STATUS_WARNING;
                    $message = __('messages.monitors.messages.suite_blocked', ['count' => $suiteSummary['blocked'], 'suite' => $suite->name]);
                } elseif ($status === ApiMonitor::STATUS_HEALTHY) {
                    $message = __('messages.monitors.messages.suite_passed', ['suite' => $suite->name]);
                }
            }

            $scanAlertSummary = $this->scanAlertSummary($monitor, $scanRun);
            $alertTriggers = $scanAlertSummary['triggers'] ?? [];
            $alertTriggerSummary = $this->alertTriggerSummary($scanAlertSummary);

            if (($scanAlertSummary['critical_signal_count'] ?? 0) > 0 && $status !== ApiMonitor::STATUS_REGRESSION) {
                $status = ApiMonitor::STATUS_FAILED;
                $message = __('messages.monitors.messages.notification_critical_signals', ['count' => $scanAlertSummary['critical_signal_count']]);
            } elseif (($scanAlertSummary['warning_signal_count'] ?? 0) > 0 && $status === ApiMonitor::STATUS_HEALTHY) {
                $status = ApiMonitor::STATUS_WARNING;
                $message = __('messages.monitors.messages.notification_warning_signals', ['count' => $scanAlertSummary['warning_signal_count']]);
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
                'test_suite_id' => $suite?->id,
                'test_suite_name' => $suite?->name,
                'suite_summary' => $suiteSummary,
                'alert_triggers' => $alertTriggers,
                'alert_trigger_summary' => $alertTriggerSummary,
                'scan_alert_summary' => $scanAlertSummary,
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
                'environment' => $monitor->environment?->name ?: __('messages.endpoints.project_default'),
                'suite' => $suite?->name ?: __('messages.monitors.all_project_endpoints'),
                'status' => $status,
                'previous_status' => $previousStatus,
                'message' => $message,
                'scan_run_id' => $scanRun->id,
                'snapshot_id' => $snapshot?->id,
                'compare_run_id' => $compareRun?->id,
                'suite_summary' => $suiteSummary,
                'alert_triggers' => $alertTriggers,
                'alert_trigger_summary' => $alertTriggerSummary,
                'scan_alert_summary' => $scanAlertSummary,
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


    /** @return array<string, mixed> */
    private function scanAlertSummary(ApiMonitor $monitor, ScanRun $scanRun): array
    {
        $resultQuery = $scanRun->results();
        $findingQuery = Finding::query()
            ->where('project_id', $monitor->project_id)
            ->where('scan_run_id', $scanRun->id)
            ->whereIn('status', Finding::OPEN_STATUSES);

        $summary = [
            'critical_findings' => $monitor->alert_on_critical_finding ? (clone $findingQuery)->where('severity', Finding::SEVERITY_CRITICAL)->count() : 0,
            'high_findings' => $monitor->alert_on_high_finding ? (clone $findingQuery)->where('severity', Finding::SEVERITY_HIGH)->count() : 0,
            'http_5xx' => $monitor->alert_on_http_5xx ? (clone $resultQuery)->whereBetween('status_code', [500, 599])->count() : 0,
            'sensitive_data' => $monitor->alert_on_sensitive_data ? (clone $resultQuery)->where('sensitive_data_detected', true)->count() : 0,
            'broken_auth' => $monitor->alert_on_broken_auth ? (clone $resultQuery)->where('broken_auth_detected', true)->count() : 0,
            'schema_drift' => $monitor->alert_on_schema_drift ? (clone $resultQuery)->where('schema_drift_detected', true)->count() : 0,
        ];

        $triggers = [];
        foreach ($summary as $key => $count) {
            if ($count > 0) {
                $triggers[] = $key;
            }
        }

        $summary['triggers'] = $triggers;
        $summary['critical_signal_count'] = (int) ($summary['critical_findings'] + $summary['http_5xx'] + $summary['broken_auth']);
        $summary['warning_signal_count'] = (int) ($summary['high_findings'] + $summary['sensitive_data'] + $summary['schema_drift']);
        $summary['enabled_triggers'] = [
            'critical_findings' => (bool) $monitor->alert_on_critical_finding,
            'high_findings' => (bool) $monitor->alert_on_high_finding,
            'http_5xx' => (bool) $monitor->alert_on_http_5xx,
            'sensitive_data' => (bool) $monitor->alert_on_sensitive_data,
            'broken_auth' => (bool) $monitor->alert_on_broken_auth,
            'schema_drift' => (bool) $monitor->alert_on_schema_drift,
        ];

        return $summary;
    }

    /** @param array<string, mixed> $summary */
    private function alertTriggerSummary(array $summary): ?string
    {
        $parts = [];
        $labels = [
            'critical_findings' => __('messages.monitors.notification_triggers.critical_findings'),
            'high_findings' => __('messages.monitors.notification_triggers.high_findings'),
            'http_5xx' => __('messages.monitors.notification_triggers.http_5xx'),
            'sensitive_data' => __('messages.monitors.notification_triggers.sensitive_data'),
            'broken_auth' => __('messages.monitors.notification_triggers.broken_auth'),
            'schema_drift' => __('messages.monitors.notification_triggers.schema_drift'),
        ];

        foreach ($labels as $key => $label) {
            $count = (int) ($summary[$key] ?? 0);
            if ($count > 0) {
                $parts[] = $label.': '.$count;
            }
        }

        return $parts === [] ? null : implode(' | ', $parts);
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
            'environment' => $monitor->environment?->name ?: __('messages.endpoints.project_default'),
            'suite' => $monitor->testSuite?->name ?: __('messages.monitors.all_project_endpoints'),
            'status' => ApiMonitor::STATUS_FAILED,
            'previous_status' => $previousStatus,
            'message' => $message,
            'alert_triggers' => ['scheduled_monitor_failure'],
            'alert_trigger_summary' => $message,
            'scan_alert_summary' => ['scheduled_monitor_failure' => 1, 'triggers' => ['scheduled_monitor_failure'], 'critical_signal_count' => 1, 'warning_signal_count' => 0],
            'next_run_at' => $monitor->next_run_at?->toDateTimeString(),
        ];

        $alertEvents = $this->alerts->notify($monitor, $result, $previousStatus);
        $result['alert_events_count'] = count($alertEvents);
        $result['alert_failures_count'] = collect($alertEvents)
            ->where('delivery_status', \App\Models\MonitorAlertEvent::DELIVERY_FAILED)
            ->count();

        return $result;
    }


    private function applyEnvironmentFilter(Builder $query, string|int $environment): void
    {
        $needle = trim((string) $environment);

        if (in_array(strtolower($needle), ['default', 'project-default', 'project_default', 'none'], true)) {
            $query->whereNull('environment_id');
            return;
        }

        if (ctype_digit($needle)) {
            $query->where('environment_id', (int) $needle);
            return;
        }

        $query->whereHas('environment', function (Builder $environmentQuery) use ($needle): void {
            $environmentQuery->where('name', $needle)
                ->orWhere('environment_type', $needle);
        });
    }

    private function applySuiteFilter(Builder $query, string|int $suite): void
    {
        $needle = trim((string) $suite);

        if (in_array(strtolower($needle), ['all', 'project', 'project-wide', 'whole-project', 'none'], true)) {
            $query->whereNull('test_suite_id');
            return;
        }

        if (ctype_digit($needle)) {
            $query->where('test_suite_id', (int) $needle);
            return;
        }

        $query->whereHas('testSuite', function (Builder $suiteQuery) use ($needle): void {
            $suiteQuery->where('name', $needle);
        });
    }

    /** @return array<string, mixed> */
    private function previewMonitor(ApiMonitor $monitor): array
    {
        return [
            'monitor_id' => $monitor->id,
            'project_id' => $monitor->project_id,
            'project' => $monitor->project?->name,
            'name' => $monitor->name,
            'environment' => $monitor->environment?->name ?: __('messages.endpoints.project_default'),
            'suite' => $monitor->testSuite?->name ?: __('messages.monitors.all_project_endpoints'),
            'status' => 'due',
            'message' => 'Dry run only. No scan was executed.',
            'next_run_at' => $monitor->next_run_at?->toDateTimeString(),
            'frequency' => $monitor->frequency,
        ];
    }
}
