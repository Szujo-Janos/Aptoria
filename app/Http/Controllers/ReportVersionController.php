<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReportVersion;
use App\Services\Reports\ReportPresentationService;
use App\Services\Reports\ReportVersioningService;
use App\Services\Settings\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportVersionController extends Controller
{
    public function index(Project $project, SettingService $settings): View
    {
        $versions = $project->reportVersions()
            ->with(['generatedBy', 'approvedBy'])
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('report_versions.index', compact('project', 'versions'));
    }

    public function store(Project $project, Request $request, ReportVersioningService $versioning): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'report_type' => ['required', 'string', Rule::in(ReportVersion::TYPES)],
        ]);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $version = $versioning->create($project, $validated, $user);

        return redirect()
            ->route('projects.report-versions.show', [$project, $version])
            ->with('success', __('messages.report_versions.created'));
    }

    public function show(Project $project, ReportVersion $reportVersion): View
    {
        $this->ensureVersionBelongsToProject($project, $reportVersion);
        $reportVersion->load(['generatedBy', 'approvedBy']);

        return view('report_versions.show', compact('project', 'reportVersion'));
    }

    public function markReviewed(Project $project, ReportVersion $reportVersion, ReportVersioningService $versioning): RedirectResponse
    {
        $this->ensureVersionBelongsToProject($project, $reportVersion);
        $versioning->markReviewed($reportVersion, Auth::user());

        return redirect()
            ->route('projects.report-versions.show', [$project, $reportVersion])
            ->with('success', __('messages.report_versions.reviewed'));
    }

    public function approve(Project $project, ReportVersion $reportVersion, ReportVersioningService $versioning): RedirectResponse
    {
        $this->ensureVersionBelongsToProject($project, $reportVersion);
        $versioning->approve($reportVersion, Auth::user());

        return redirect()
            ->route('projects.report-versions.show', [$project, $reportVersion])
            ->with('success', __('messages.report_versions.approved'));
    }

    public function archive(Project $project, ReportVersion $reportVersion, ReportVersioningService $versioning): RedirectResponse
    {
        $this->ensureVersionBelongsToProject($project, $reportVersion);
        $versioning->archive($reportVersion);

        return redirect()
            ->route('projects.report-versions.show', [$project, $reportVersion])
            ->with('success', __('messages.report_versions.archived'));
    }

    public function markdown(Project $project, ReportVersion $reportVersion): Response
    {
        $this->ensureVersionBelongsToProject($project, $reportVersion);

        return response((string) $reportVersion->markdown_content, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="report-version-'.$reportVersion->id.'.md"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function html(Project $project, ReportVersion $reportVersion, ReportPresentationService $presentation): Response
    {
        $this->ensureVersionBelongsToProject($project, $reportVersion);

        return response($presentation->htmlFromMarkdown((string) $reportVersion->markdown_content, $reportVersion->title, $project), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="report-version-'.$reportVersion->id.'.html"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function pdf(Project $project, ReportVersion $reportVersion, ReportPresentationService $presentation): Response
    {
        $this->ensureVersionBelongsToProject($project, $reportVersion);

        return response($presentation->pdfFromMarkdown((string) $reportVersion->markdown_content, $reportVersion->title, $project), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="report-version-'.$reportVersion->id.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function json(Project $project, ReportVersion $reportVersion, ReportVersioningService $versioning): JsonResponse
    {
        $this->ensureVersionBelongsToProject($project, $reportVersion);

        return response()->json($versioning->jsonPackage($reportVersion), 200, ['X-Content-Type-Options' => 'nosniff']);
    }

    private function ensureVersionBelongsToProject(Project $project, ReportVersion $reportVersion): void
    {
        abort_unless($reportVersion->project_id === $project->id, 404);
    }
}
