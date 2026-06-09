<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ReleaseReadinessService;
use App\Services\Reports\ReportPresentationService;
use App\Services\Settings\SettingService;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Str;

class ReleaseReadinessController extends Controller
{
    public function index(ReleaseReadinessService $readiness, SettingService $settings): View
    {
        $projects = Project::query()
            ->with(['endpoints.latestScanResult', 'scanRuns', 'snapshots', 'compareRuns.items', 'apiMonitors'])
            ->withCount(['endpoints', 'scanRuns', 'snapshots', 'compareRuns', 'apiMonitors'])
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        $summaries = $projects->getCollection()
            ->mapWithKeys(fn (Project $project): array => [$project->id => $readiness->summarize($project)]);

        return view('reports.release-readiness-index', compact('projects', 'summaries'));
    }

    public function show(Project $project, ReleaseReadinessService $readiness): View
    {
        $summary = $readiness->summarize($project);

        return view('reports.release-readiness', compact('project', 'summary'));
    }

    public function markdown(Project $project, ReleaseReadinessService $readiness): Response
    {
        $filename = Str::slug((string) ($project->slug ?: $project->id))
            .'-release-readiness-'.now()->format('Ymd-His').'.md';

        return response($readiness->markdown($project), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function html(Project $project, ReleaseReadinessService $readiness, ReportPresentationService $presentation): Response
    {
        $filename = Str::slug((string) ($project->slug ?: $project->id))
            .'-release-readiness-'.now()->format('Ymd-His').'.html';
        $markdown = $readiness->markdown($project);

        return response($presentation->htmlFromMarkdown($markdown, 'Release Readiness Report', $project), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function pdf(Project $project, ReleaseReadinessService $readiness, ReportPresentationService $presentation): Response
    {
        $filename = Str::slug((string) ($project->slug ?: $project->id))
            .'-release-readiness-'.now()->format('Ymd-His').'.pdf';
        $markdown = $readiness->markdown($project);

        return response($presentation->pdfFromMarkdown($markdown, 'Release Readiness Report', $project), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
