<?php

namespace App\Http\Controllers;

use App\Models\CompareRun;
use App\Models\Project;
use App\Models\ScanRun;
use App\Models\Snapshot;
use App\Services\Reports\ReportExportService;
use App\Services\Reports\ReportPresentationService;
use App\Services\Settings\SettingService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(SettingService $settings): View
    {
        $projects = Project::query()
            ->withCount(['endpoints', 'scanRuns', 'snapshots', 'compareRuns', 'testSuites', 'testCases', 'contractValidationRuns', 'qaReleaseGates'])
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('reports.index', compact('projects'));
    }

    public function project(Project $project): View
    {
        $project->loadCount(['endpoints', 'scanRuns', 'snapshots', 'compareRuns', 'testSuites', 'testCases', 'contractValidationRuns', 'qaReleaseGates']);

        $latestScanRuns = $project->scanRuns()
            ->with('environment')
            ->latest()
            ->limit(10)
            ->get();

        $latestSnapshots = $project->snapshots()
            ->with('environment')
            ->latest()
            ->limit(10)
            ->get();

        $latestCompareRuns = $project->compareRuns()
            ->with(['snapshotA', 'snapshotB'])
            ->latest()
            ->limit(10)
            ->get();

        $latestContractValidationRuns = $project->contractValidationRuns()
            ->with('scanRun.environment')
            ->latest()
            ->limit(10)
            ->get();

        $latestReleaseGates = $project->qaReleaseGates()
            ->latest()
            ->limit(10)
            ->get();

        return view('reports.project', compact('project', 'latestScanRuns', 'latestSnapshots', 'latestCompareRuns', 'latestContractValidationRuns', 'latestReleaseGates'));
    }

    public function fullProjectMarkdown(Project $project, ReportExportService $exports): Response
    {
        return $this->download(
            $exports->fullProjectMarkdown($project),
            $exports->filename($project, 'full-project-qa-report', 'md'),
            'text/markdown; charset=UTF-8'
        );
    }

    public function fullProjectHtml(Project $project, ReportExportService $exports, ReportPresentationService $presentation): Response
    {
        $markdown = $exports->fullProjectMarkdown($project);

        return $this->download(
            $presentation->htmlFromMarkdown($markdown, 'Aptoria Full Project QA Report', $project),
            $exports->filename($project, 'full-project-qa-report', 'html'),
            'text/html; charset=UTF-8'
        );
    }

    public function fullProjectPdf(Project $project, ReportExportService $exports, ReportPresentationService $presentation): Response
    {
        $markdown = $exports->fullProjectMarkdown($project);

        return $this->download(
            $presentation->pdfFromMarkdown($markdown, 'Aptoria Full Project QA Report', $project),
            $exports->filename($project, 'full-project-qa-report', 'pdf'),
            'application/pdf'
        );
    }

    public function endpointsCsv(Project $project, ReportExportService $exports): Response
    {
        return $this->download(
            $exports->endpointInventoryCsv($project),
            $exports->filename($project, 'endpoint-inventory', 'csv'),
            'text/csv; charset=UTF-8'
        );
    }

    public function scanMarkdown(Project $project, ScanRun $scanRun, ReportExportService $exports): Response
    {
        $this->ensureScanBelongsToProject($project, $scanRun);

        return $this->download(
            $exports->scanMarkdown($scanRun),
            $exports->filename($project, 'scan-'.$scanRun->id, 'md'),
            'text/markdown; charset=UTF-8'
        );
    }

    public function scanHtml(Project $project, ScanRun $scanRun, ReportExportService $exports, ReportPresentationService $presentation): Response
    {
        $this->ensureScanBelongsToProject($project, $scanRun);
        $markdown = $exports->scanMarkdown($scanRun);

        return $this->download(
            $presentation->htmlFromMarkdown($markdown, 'Aptoria Scan Report #'.$scanRun->id, $project),
            $exports->filename($project, 'scan-'.$scanRun->id, 'html'),
            'text/html; charset=UTF-8'
        );
    }

    public function scanPdf(Project $project, ScanRun $scanRun, ReportExportService $exports, ReportPresentationService $presentation): Response
    {
        $this->ensureScanBelongsToProject($project, $scanRun);
        $markdown = $exports->scanMarkdown($scanRun);

        return $this->download(
            $presentation->pdfFromMarkdown($markdown, 'Aptoria Scan Report #'.$scanRun->id, $project),
            $exports->filename($project, 'scan-'.$scanRun->id, 'pdf'),
            'application/pdf'
        );
    }

    public function snapshotJson(Project $project, Snapshot $snapshot, ReportExportService $exports): Response
    {
        $this->ensureSnapshotBelongsToProject($project, $snapshot);

        return $this->download(
            $exports->snapshotJson($snapshot),
            $exports->filename($project, 'snapshot-'.$snapshot->id, 'json'),
            'application/json; charset=UTF-8'
        );
    }

    public function compareMarkdown(Project $project, CompareRun $compareRun, ReportExportService $exports): Response
    {
        $this->ensureCompareBelongsToProject($project, $compareRun);

        return $this->download(
            $exports->compareMarkdown($compareRun),
            $exports->filename($project, 'compare-'.$compareRun->id, 'md'),
            'text/markdown; charset=UTF-8'
        );
    }

    public function compareHtml(Project $project, CompareRun $compareRun, ReportExportService $exports, ReportPresentationService $presentation): Response
    {
        $this->ensureCompareBelongsToProject($project, $compareRun);
        $markdown = $exports->compareMarkdown($compareRun);

        return $this->download(
            $presentation->htmlFromMarkdown($markdown, 'Aptoria Snapshot Compare Report #'.$compareRun->id, $project),
            $exports->filename($project, 'compare-'.$compareRun->id, 'html'),
            'text/html; charset=UTF-8'
        );
    }

    public function comparePdf(Project $project, CompareRun $compareRun, ReportExportService $exports, ReportPresentationService $presentation): Response
    {
        $this->ensureCompareBelongsToProject($project, $compareRun);
        $markdown = $exports->compareMarkdown($compareRun);

        return $this->download(
            $presentation->pdfFromMarkdown($markdown, 'Aptoria Snapshot Compare Report #'.$compareRun->id, $project),
            $exports->filename($project, 'compare-'.$compareRun->id, 'pdf'),
            'application/pdf'
        );
    }

    private function download(string $content, string $filename, string $contentType): Response
    {
        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ensureScanBelongsToProject(Project $project, ScanRun $scanRun): void
    {
        abort_unless($scanRun->project_id === $project->id, 404);
    }

    private function ensureSnapshotBelongsToProject(Project $project, Snapshot $snapshot): void
    {
        abort_unless($snapshot->project_id === $project->id, 404);
    }

    private function ensureCompareBelongsToProject(Project $project, CompareRun $compareRun): void
    {
        abort_unless($compareRun->project_id === $project->id, 404);
    }
}
