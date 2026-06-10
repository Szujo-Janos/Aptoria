<?php

namespace App\Services\Demo;

use App\Models\AuditLog;
use App\Models\Project;
use App\Services\Audit\AuditLogService;
use App\Services\ReleaseReadinessService;
use Database\Seeders\DemoQaProjectSeeder;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DemoProjectService
{
    public function __construct(
        private readonly ReleaseReadinessService $readiness,
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function project(): ?Project
    {
        return Project::query()
            ->where('slug', DemoQaProjectSeeder::PROJECT_SLUG)
            ->first();
    }

    /** @return array<string, mixed> */
    public function summary(): array
    {
        if (! Schema::hasTable('projects')) {
            return [
                'exists' => false,
                'project' => null,
                'slug' => DemoQaProjectSeeder::PROJECT_SLUG,
                'counts' => $this->emptyCounts(),
                'readiness' => null,
                'migrations_ready' => false,
            ];
        }

        $project = Project::query()
            ->withCount([
                'environments',
                'authProfiles',
                'endpoints',
                'assertionRules',
                'pathParameters',
                'scanRuns',
                'snapshots',
                'compareRuns',
                'testSuites',
                'testCases',
                'testCaseResults',
                'contractValidationRuns',
                'contractValidationResults',
                'findings',
                'findingEvidence',
                'qaReleaseGates',
                'apiMonitors',
                'monitorAlertEvents',
                'calendarEvents',
            ])
            ->where('slug', DemoQaProjectSeeder::PROJECT_SLUG)
            ->first();

        if (! $project) {
            return [
                'exists' => false,
                'project' => null,
                'slug' => DemoQaProjectSeeder::PROJECT_SLUG,
                'counts' => $this->emptyCounts(),
                'readiness' => null,
                'migrations_ready' => true,
            ];
        }

        return [
            'exists' => true,
            'project' => $project,
            'slug' => DemoQaProjectSeeder::PROJECT_SLUG,
            'counts' => [
                'environments' => (int) $project->environments_count,
                'auth_profiles' => (int) $project->auth_profiles_count,
                'endpoints' => (int) $project->endpoints_count,
                'assertion_rules' => (int) $project->assertion_rules_count,
                'path_parameters' => (int) $project->path_parameters_count,
                'scan_runs' => (int) $project->scan_runs_count,
                'snapshots' => (int) $project->snapshots_count,
                'compare_runs' => (int) $project->compare_runs_count,
                'test_suites' => (int) $project->test_suites_count,
                'test_cases' => (int) $project->test_cases_count,
                'test_case_results' => (int) $project->test_case_results_count,
                'contract_validation_runs' => (int) $project->contract_validation_runs_count,
                'contract_validation_results' => (int) $project->contract_validation_results_count,
                'findings' => (int) $project->findings_count,
                'finding_evidence' => (int) $project->finding_evidence_count,
                'release_gates' => (int) $project->qa_release_gates_count,
                'monitors' => (int) $project->api_monitors_count,
                'monitor_alerts' => (int) $project->monitor_alert_events_count,
                'calendar_events' => (int) $project->calendar_events_count,
            ],
            'readiness' => $this->readiness->summarize($project->fresh([
                'endpoints.latestScanResult',
                'scanRuns',
                'snapshots',
                'compareRuns.items',
                'apiMonitors',
                'findings',
            ]) ?: $project),
            'migrations_ready' => true,
        ];
    }

    /** @return array<string, mixed> */
    public function import(): array
    {
        if (! Schema::hasTable('projects')) {
            throw new RuntimeException('Run migrations before importing the Aptoria demo project.');
        }

        app(DemoQaProjectSeeder::class)->run();

        $summary = $this->summary();
        /** @var Project|null $project */
        $project = $summary['project'] ?? null;

        $this->auditLog->record([
            'project_id' => $project?->id,
            'event_type' => AuditLog::EVENT_SYSTEM,
            'action' => AuditLog::ACTION_IMPORTED,
            'severity' => AuditLog::SEVERITY_INFO,
            'subject_label' => 'demo project',
            'subject_name' => $project?->name ?: DemoQaProjectSeeder::PROJECT_SLUG,
            'summary' => 'Imported Aptoria comprehensive demo project sample data.',
            'metadata' => [
                'slug' => DemoQaProjectSeeder::PROJECT_SLUG,
                'counts' => $summary['counts'] ?? [],
                'version' => config('aptoria.version'),
            ],
        ]);

        return $summary;
    }

    /** @return array<string, mixed> */
    public function remove(): array
    {
        $project = $this->project();
        $name = $project?->name ?: DemoQaProjectSeeder::PROJECT_SLUG;
        $projectId = $project?->id;

        if ($project) {
            $project->delete();
        }

        $this->auditLog->record([
            'project_id' => null,
            'event_type' => AuditLog::EVENT_SYSTEM,
            'action' => AuditLog::ACTION_DELETED,
            'severity' => AuditLog::SEVERITY_WARNING,
            'subject_label' => 'demo project',
            'subject_name' => $name,
            'summary' => 'Removed Aptoria demo project sample data.',
            'metadata' => [
                'project_id' => $projectId,
                'slug' => DemoQaProjectSeeder::PROJECT_SLUG,
                'version' => config('aptoria.version'),
            ],
        ]);

        return $this->summary();
    }

    /** @return array<string, int> */
    private function emptyCounts(): array
    {
        return [
            'environments' => 0,
            'auth_profiles' => 0,
            'endpoints' => 0,
            'assertion_rules' => 0,
            'path_parameters' => 0,
            'scan_runs' => 0,
            'snapshots' => 0,
            'compare_runs' => 0,
            'test_suites' => 0,
            'test_cases' => 0,
            'test_case_results' => 0,
            'contract_validation_runs' => 0,
            'contract_validation_results' => 0,
            'findings' => 0,
            'finding_evidence' => 0,
            'release_gates' => 0,
            'monitors' => 0,
            'monitor_alerts' => 0,
            'calendar_events' => 0,
        ];
    }
}
