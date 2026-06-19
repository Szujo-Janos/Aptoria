<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReportVersion;
use App\Services\AuditLogger;
use App\Services\ReportBuilderService;
use App\Services\ReleaseDecisionReportVersionService;
use App\Services\ReportDeliveryService;
use App\Services\ReportVisualStandardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Project $project): View
    {
        $reports = $project->reportVersions()
            ->with(['generatedBy', 'releaseReadinessRun', 'releaseDecisionSnapshot', 'releaseGate'])
            ->latest('generated_at')
            ->latest()
            ->get();

        $latestReadiness = $project->releaseReadinessRuns()->latest()->first();

        return view('reports.index', [
            'project' => $project,
            'reports' => $reports,
            'latestReadiness' => $latestReadiness,
            'metrics' => [
                'reports' => $reports->count(),
                'approved' => $reports->where('status', 'approved')->count(),
                'signed_off' => $reports->filter(fn ($report) => $report->has_approval_signoff)->count(),
                'deliverable' => $reports->where('status', 'approved')->count(),
                'release_decision_reports' => $reports->where('type', 'release_decision')->count(),
                'evidence' => $project->evidence()->count(),
                'open_findings' => $project->findings()->whereNotIn('status', ['verified'])->count(),
            ],
        ]);
    }

    public function store(Request $request, Project $project, ReportBuilderService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_values(array_diff(ReportVersion::TYPES, ['release_decision'])))],
            'title' => ['nullable', 'string', 'max:220'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'confirm_report' => ['accepted'],
        ]);

        $report = $service->createVersion(
            $project,
            $request->user(),
            $data['type'],
            $data['title'] ?? null,
            $data['notes'] ?? null,
        );

        $auditLogger->record('report_generated', __('messages.audit_messages.report_generated'), $project, [
            'report_version_id' => $report->id,
            'type' => $report->type,
            'checksum' => $report->checksum,
        ], 'report');

        return redirect()->route('projects.reports.show', [$project, $report])->with('status', __('messages.reports.generated'));
    }

    public function show(Project $project, ReportVersion $reportVersion): View
    {
        $this->ensureBelongsToProject($project, $reportVersion);
        $reportVersion->load(['generatedBy', 'reviewedBy', 'approvedBy', 'archivedBy', 'releaseReadinessRun', 'releaseDecisionSnapshot', 'releaseGate', 'clientPortalAccesses' => fn ($query) => $query->with('latestAcknowledgement')->latest()->limit(6)]);

        return view('reports.show', [
            'project' => $project,
            'report' => $reportVersion,
            'data' => is_array($reportVersion->data_json) ? $reportVersion->data_json : [],
        ]);
    }

    public function download(Project $project, ReportVersion $reportVersion, string $format, ReleaseDecisionReportVersionService $releaseDecisionReportService, ReportVisualStandardService $reportVisualStandardService): Response
    {
        $this->ensureBelongsToProject($project, $reportVersion);
        abort_unless(in_array($format, ['md', 'html', 'pdf', 'json'], true), 404);

        $slug = Str::slug($project->name.'-'.$reportVersion->type.'-'.$reportVersion->id);

        if ($format === 'html') {
            return response($reportVisualStandardService->exportHtml($reportVersion), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$slug.'.html"',
            ]);
        }

        if ($format === 'pdf') {
            return response($releaseDecisionReportService->exportPdf($reportVersion), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$slug.'.pdf"',
            ]);
        }

        if ($format === 'json') {
            $payload = [
                'report' => $reportVersion->data_json ?? [],
                'approval' => $reportVersion->approvalSummary(),
            ];

            return response(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$slug.'.json"',
            ]);
        }

        return response($reportVersion->content_markdown ?: '', 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$slug.'.md"',
        ]);
    }



    public function deliveryLink(Request $request, Project $project, ReportVersion $reportVersion, ReportDeliveryService $deliveryService, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $reportVersion);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:160'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'acknowledge_required' => ['nullable', 'boolean'],
            'confirm_delivery' => ['accepted'],
        ]);

        $access = $deliveryService->createDeliveryLink($project, $reportVersion, $request->user(), [
            'name' => $data['name'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'acknowledge_required' => $request->boolean('acknowledge_required'),
        ]);

        $auditLogger->record('approved_report_delivery_link_created', __('messages.audit_messages.approved_report_delivery_link_created'), $project, [
            'report_version_id' => $reportVersion->id,
            'client_portal_access_id' => $access->id,
            'checksum' => $reportVersion->checksum,
        ], 'client_portal');

        return redirect()->route('projects.reports.show', [$project, $reportVersion])->with('status', __('messages.client_portal.delivery_link_created'));
    }

    public function status(Request $request, Project $project, ReportVersion $reportVersion, ReleaseDecisionReportVersionService $releaseDecisionReportService, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $reportVersion);

        $data = $request->validate([
            'status' => ['required', Rule::in(ReportVersion::STATUSES)],
            'review_note' => ['nullable', 'string', 'max:3000'],
            'approval_note' => ['nullable', 'string', 'max:3000'],
            'approval_signoff_name' => ['nullable', 'string', 'max:160'],
            'approval_signoff_role' => ['nullable', 'string', 'max:160'],
            'approval_signoff_statement' => ['nullable', 'string', 'max:3000'],
            'archive_note' => ['nullable', 'string', 'max:3000'],
            'confirm_status' => ['accepted'],
        ]);

        if ($data['status'] === 'approved') {
            $request->validate([
                'approval_signoff_name' => ['required', 'string', 'max:160'],
                'approval_signoff_statement' => ['required', 'string', 'max:3000'],
            ]);
        }

        $releaseDecisionReportService->updateStatus($reportVersion, $data['status'], $request->user(), $data);

        $auditLogger->record('report_status_updated', __('messages.audit_messages.report_status_updated'), $project, [
            'report_version_id' => $reportVersion->id,
            'status' => $reportVersion->status,
            'type' => $reportVersion->type,
            'review_note_present' => filled($reportVersion->review_note),
            'approval_note_present' => filled($reportVersion->approval_note),
            'approval_signoff_name' => $reportVersion->approval_signoff_name,
            'approval_signed_at' => $reportVersion->approval_signed_at?->toDateTimeString(),
            'archive_note_present' => filled($reportVersion->archive_note),
        ], 'report');

        return redirect()->route('projects.reports.show', [$project, $reportVersion])->with('status', __('messages.reports.status_updated'));
    }

    private function ensureBelongsToProject(Project $project, ReportVersion $reportVersion): void
    {
        abort_unless((int) $reportVersion->project_id === (int) $project->id, 404);
    }
}
