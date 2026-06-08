<?php

use App\Models\CalendarEvent;
use App\Models\Project;
use App\Services\Monitors\ScheduledMonitorService;
use App\Services\Security\SecurityStatusService;
use Illuminate\Support\Facades\Artisan;

Artisan::command('aptoria:version', function (): void {
    $this->info('Aptoria '.config('aptoria.version'));
})->purpose('Display the Aptoria version.');

Artisan::command('aptoria:security-audit {--fail-on-warning}', function (): int {
    $service = app(SecurityStatusService::class);
    $checks = $service->checks();
    $summary = $service->summary($checks);

    $this->info('Aptoria security audit');
    $this->line('Version: '.config('aptoria.version'));
    $this->line('Status: '.strtoupper($summary['status']).' | Failed: '.$summary['failed'].' | Warnings: '.$summary['warnings'].' | Total: '.$summary['total']);
    $this->newLine();

    $this->table(
        ['Status', 'Check', 'Detail'],
        array_map(static fn (array $check): array => [strtoupper($check['status']), $check['label'], $check['detail']], $checks)
    );

    if (($summary['failed'] ?? 0) > 0) {
        return 1;
    }

    if ($this->option('fail-on-warning') && ($summary['warnings'] ?? 0) > 0) {
        return 1;
    }

    return 0;
})->purpose('Run the Aptoria deployment/security readiness audit.');

Artisan::command(
    'aptoria:run-monitors {--limit=50 : Maximum number of enabled matching monitors to inspect.} {--project= : Optional project id or slug filter.} {--monitor= : Optional monitor id filter.} {--force : Run matching enabled monitors even when next_run_at is in the future.} {--dry-run : List matching monitors without executing scans.} {--json : Print machine-readable JSON output.} {--fail-on-warning : Return exit code 1 when monitor warnings are detected.} {--fail-on-regression : Return exit code 1 when regressions are detected.}',
    function (): int {
        $summary = app(ScheduledMonitorService::class)->runDue((int) $this->option('limit'), [
            'project' => $this->option('project') ?: null,
            'monitor' => $this->option('monitor') ?: null,
            'force' => (bool) $this->option('force'),
            'dry_run' => (bool) $this->option('dry-run'),
        ]);

        if ($this->option('json')) {
            $this->line((string) json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info('Aptoria scheduled monitor runner');
            $this->line('Version: '.config('aptoria.version'));
            $this->line('Mode: '.(($summary['dry_run'] ?? false) ? 'dry-run' : 'execute'));
            $this->line('Checked: '.$summary['checked'].' | Due: '.$summary['due'].' | Ran: '.$summary['ran'].' | Failed: '.$summary['failed'].' | Warnings: '.$summary['warnings'].' | Regressions: '.$summary['regressions'].' | Alerts: '.$summary['alerts'].' | Alert failures: '.$summary['alert_failures'].' | Skipped: '.$summary['skipped']);
            $this->newLine();

            if (empty($summary['monitors'])) {
                $this->line('No enabled matching monitors are due.');
            } else {
                $this->table(
                    ['ID', 'Project', 'Monitor', 'Status', 'Next run', 'Message'],
                    array_map(static fn (array $monitor): array => [
                        $monitor['monitor_id'] ?? '?',
                        $monitor['project'] ?? '-',
                        $monitor['name'] ?? 'monitor',
                        $monitor['status'] ?? 'unknown',
                        $monitor['next_run_at'] ?? '-',
                        $monitor['message'] ?? '',
                    ], $summary['monitors'])
                );
            }
        }

        if (($summary['failed'] ?? 0) > 0) {
            return 1;
        }

        if ($this->option('fail-on-regression') && ($summary['regressions'] ?? 0) > 0) {
            return 1;
        }

        if ($this->option('fail-on-warning') && (($summary['warnings'] ?? 0) > 0 || ($summary['regressions'] ?? 0) > 0 || ($summary['alert_failures'] ?? 0) > 0)) {
            return 1;
        }

        return 0;
    }
)->purpose('Run due Aptoria scheduled monitors with optional project, monitor, dry-run and JSON output filters.');


Artisan::command('aptoria:calendar-cleanup-setup-noise {--force : Delete matching setup/demo noise entries.}', function (): int {
    $technicalSubjects = [
        App\Models\User::class,
        App\Models\ProjectSetting::class,
        App\Models\Setting::class,
    ];

    $demoProjectIds = Project::query()
        ->whereIn('slug', ['demo-public-api', 'northstar-commerce-demo-review'])
        ->pluck('id')
        ->all();

    $demoTechnicalSubjects = [
        App\Models\Environment::class,
        App\Models\AuthProfile::class,
        App\Models\Endpoint::class,
        App\Models\EndpointAssertionRule::class,
        App\Models\EndpointPathParameter::class,
        App\Models\ProjectSetting::class,
        App\Models\ApiMonitor::class,
        App\Models\TestSuite::class,
        App\Models\TestCase::class,
        App\Models\TestCaseResult::class,
        App\Models\ScanRun::class,
        App\Models\ScanResult::class,
        App\Models\Snapshot::class,
        App\Models\Finding::class,
        App\Models\FindingEvidence::class,
        App\Models\QaReleaseGate::class,
    ];

    $query = CalendarEvent::query()
        ->where('is_system_locked', true)
        ->where('event_type', CalendarEvent::TYPE_ACTIVITY_LOG)
        ->where(function ($query) use ($technicalSubjects, $demoProjectIds, $demoTechnicalSubjects): void {
            $query->whereIn('activity_subject_type', $technicalSubjects);

            if (! empty($demoProjectIds)) {
                $query->orWhere(function ($query) use ($demoProjectIds, $demoTechnicalSubjects): void {
                    $query->whereIn('project_id', $demoProjectIds)
                        ->whereIn('activity_subject_type', $demoTechnicalSubjects);
                });
            }
        });

    $count = (clone $query)->count();

    if (! $this->option('force')) {
        $this->info('Matching setup/demo calendar noise entries: '.$count);
        $this->line('Run again with --force to delete them.');

        return 0;
    }

    $deleted = $query->delete();
    $this->info('Deleted setup/demo calendar noise entries: '.$deleted);

    return 0;
})->purpose('Remove previously generated setup/demo technical calendar activity noise.');
