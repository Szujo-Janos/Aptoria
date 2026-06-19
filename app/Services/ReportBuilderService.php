<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ReportVersion;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReportBuilderService
{
    public function __construct(private readonly ReportVisualStandardService $visualStandardService)
    {
    }

    public function createVersion(Project $project, ?User $user, string $type, ?string $title = null, ?string $notes = null): ReportVersion
    {
        $type = in_array($type, ReportVersion::TYPES, true) ? $type : 'full_project';
        $data = $this->buildData($project, $type);
        $markdown = $this->markdown($project, $data, $type, $notes);
        $html = $this->visualStandardService->inlineHtmlFromMarkdown($markdown);
        $checksum = hash('sha256', $markdown.'|'.json_encode($data));

        return $project->reportVersions()->create([
            'generated_by_user_id' => $user?->id,
            'release_readiness_run_id' => $data['latest_readiness']['id'] ?? null,
            'type' => $type,
            'status' => 'draft',
            'title' => $title ?: $this->defaultTitle($project, $type),
            'content_markdown' => $markdown,
            'content_html' => $html,
            'data_json' => $data,
            'checksum' => $checksum,
            'notes' => $notes,
            'generated_at' => now(),
        ]);
    }

    public function buildData(Project $project, string $type = 'full_project'): array
    {
        $latestScan = Schema::hasTable('scan_runs') ? $project->scanRuns()->latest()->first() : null;
        $latestReadiness = Schema::hasTable('release_readiness_runs') ? $project->releaseReadinessRuns()->latest()->first() : null;
        $defaultEnvironment = Schema::hasTable('environments') ? $project->defaultEnvironment() : null;
        $defaultAuthProfile = Schema::hasTable('auth_profiles') ? $project->defaultAuthProfile() : null;

        $findingsBySeverity = Schema::hasTable('findings') ? $project->findings()
            ->selectRaw('severity, count(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity')
            ->toArray() : [];

        $findingsByStatus = Schema::hasTable('findings') ? $project->findings()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->toArray() : [];

        $evidenceByType = Schema::hasTable('finding_evidence') ? $project->evidence()
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type')
            ->toArray() : [];

        $openFindings = Schema::hasTable('findings') ? $project->findings()
            ->whereNotIn('status', ['verified'])
            ->with(['endpoint'])
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn ($finding) => [
                'id' => $finding->id,
                'title' => $finding->title,
                'severity' => $finding->severity,
                'status' => $finding->status,
                'priority' => $finding->priority,
                'endpoint' => trim(($finding->endpoint?->method ?? '').' '.($finding->endpoint?->path ?? '')),
            ])->values()->all() : [];

        $recentEvidence = Schema::hasTable('finding_evidence') ? $project->evidence()
            ->with(['finding', 'endpoint'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($evidence) => [
                'id' => $evidence->id,
                'title' => $evidence->title,
                'type' => $evidence->type,
                'finding' => $evidence->finding?->title,
                'endpoint' => trim(($evidence->endpoint?->method ?? '').' '.($evidence->endpoint?->path ?? '')),
            ])->values()->all() : [];

        return [
            'type' => $type,
            'generated_at' => now()->toDateTimeString(),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'base_url' => $project->base_url,
                'environment_label' => $project->environment_label,
                'release_goal' => $project->release_goal,
            ],
            'defaults' => [
                'environment' => $defaultEnvironment?->name,
                'environment_url' => $defaultEnvironment?->base_url,
                'auth_profile' => $defaultAuthProfile?->name,
                'auth_type' => $defaultAuthProfile?->type,
            ],
            'metrics' => [
                'endpoints' => Schema::hasTable('endpoints') ? $project->endpoints()->count() : 0,
                'safe_endpoints' => Schema::hasTable('endpoints') ? $project->endpoints()->whereIn('method', ['GET', 'HEAD'])->where('is_active', true)->where('excluded_from_scan', false)->count() : 0,
                'scan_runs' => Schema::hasTable('scan_runs') ? $project->scanRuns()->count() : 0,
                'scan_results' => Schema::hasTable('scan_results') ? $project->scanResults()->count() : 0,
                'findings' => Schema::hasTable('findings') ? $project->findings()->count() : 0,
                'open_findings' => Schema::hasTable('findings') ? $project->findings()->whereNotIn('status', ['verified'])->count() : 0,
                'evidence' => Schema::hasTable('finding_evidence') ? $project->evidence()->count() : 0,
                'readiness_snapshots' => Schema::hasTable('release_readiness_runs') ? $project->releaseReadinessRuns()->count() : 0,
            ],
            'latest_scan' => $latestScan ? [
                'id' => $latestScan->id,
                'status' => $latestScan->status,
                'started_at' => $latestScan->started_at?->toDateTimeString(),
                'completed_at' => $latestScan->completed_at?->toDateTimeString(),
                'summary' => $latestScan->summary_json,
            ] : null,
            'latest_readiness' => $latestReadiness ? [
                'id' => $latestReadiness->id,
                'status' => $latestReadiness->status,
                'score' => $latestReadiness->score,
                'grade' => $latestReadiness->grade,
                'blocker_count' => $latestReadiness->blocker_count,
                'warning_count' => $latestReadiness->warning_count,
                'generated_at' => $latestReadiness->generated_at?->toDateTimeString(),
            ] : null,
            'findings_by_severity' => $findingsBySeverity,
            'findings_by_status' => $findingsByStatus,
            'evidence_by_type' => $evidenceByType,
            'open_findings' => $openFindings,
            'recent_evidence' => $recentEvidence,
        ];
    }

    public function defaultTitle(Project $project, string $type): string
    {
        return $project->name.' - '.__('messages.reports.types.'.$type).' - '.now()->format('Y-m-d H:i');
    }

    private function markdown(Project $project, array $data, string $type, ?string $notes): string
    {
        $lines = [];
        $lines[] = '# '.$project->name.' — '.__('messages.reports.types.'.$type);
        $lines[] = '';
        $lines[] = '**'.__('messages.reports.generated_at').':** '.$data['generated_at'];
        $lines[] = '**'.__('messages.projects.base_url').':** '.($data['project']['base_url'] ?: '—');
        $lines[] = '**'.__('messages.projects.release_goal').':** '.($data['project']['release_goal'] ?: '—');
        if (filled($notes)) {
            $lines[] = '';
            $lines[] = '## '.__('messages.reports.notes');
            $lines[] = $notes;
        }
        $lines[] = '';
        $lines[] = '## '.__('messages.reports.metrics');
        foreach ($data['metrics'] as $key => $value) {
            $lines[] = '- '.__('messages.reports.metric_labels.'.$key).': '.$value;
        }
        $lines[] = '';
        $lines[] = '## '.__('messages.reports.latest_readiness');
        if ($data['latest_readiness']) {
            $lines[] = '- '.__('messages.common.status').': '.$data['latest_readiness']['status'];
            $lines[] = '- '.__('messages.release_readiness.score').': '.$data['latest_readiness']['score'].' / 100';
            $lines[] = '- '.__('messages.release_readiness.blockers').': '.$data['latest_readiness']['blocker_count'];
            $lines[] = '- '.__('messages.release_readiness.warnings').': '.$data['latest_readiness']['warning_count'];
        } else {
            $lines[] = __('messages.reports.no_readiness_snapshot');
        }
        $lines[] = '';
        $lines[] = '## '.__('messages.findings.open_findings');
        if ($data['open_findings']) {
            foreach ($data['open_findings'] as $finding) {
                $lines[] = '- ['.$finding['severity'].' / '.$finding['status'].'] '.$finding['title'].' — '.($finding['endpoint'] ?: '—');
            }
        } else {
            $lines[] = __('messages.reports.no_open_findings');
        }
        $lines[] = '';
        $lines[] = '## '.__('messages.evidence.title');
        if ($data['recent_evidence']) {
            foreach ($data['recent_evidence'] as $evidence) {
                $lines[] = '- ['.$evidence['type'].'] '.$evidence['title'];
            }
        } else {
            $lines[] = __('messages.reports.no_evidence');
        }
        $lines[] = '';
        $lines[] = '---';
        $lines[] = __('messages.reports.checksum_notice');

        return implode("\n", $lines)."\n";
    }

    private function html(string $markdown): string
    {
        $html = e($markdown);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^\*\*(.+?)\*\*: (.+)$/m', '<p><strong>$1:</strong> $2</p>', $html);
        $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
        $html = nl2br($html);
        return '<article class="aptoria-report-html">'.$html.'</article>';
    }
}
