<?php

namespace App\Http\Controllers;

use App\Models\ClientPortalAccess;
use App\Models\ReportVersion;
use App\Models\ClientPortalAcknowledgement;
use App\Services\AuditLogger;
use App\Services\ClientPortalAcknowledgementService;
use App\Services\ReleaseDecisionReportVersionService;
use App\Services\ReportDeliveryService;
use App\Services\ReportVisualStandardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicClientPortalController extends Controller
{
    public function show(string $token, ReportDeliveryService $deliveryService): View
    {
        $access = $this->resolveAccess($token);
        $access->forceFill(['last_viewed_at' => now()])->save();

        $project = $access->project;
        $reports = collect();
        if ($access->allows('reports')) {
            $reportsQuery = $deliveryService->approvedReports($project);
            if ($access->report_version_id) {
                $reportsQuery->whereKey($access->report_version_id);
            }
            $reports = $reportsQuery->limit(8)->get();
        }
        $latestReadiness = $access->allows('readiness')
            ? $project->releaseReadinessRuns()->latest()->first()
            : null;
        $findings = $access->allows('findings')
            ? $project->findings()->latest()->limit(8)->get()
            : collect();
        $evidence = $access->allows('evidence')
            ? $project->evidence()->latest()->limit(8)->get()
            : collect();

        return view('client_portal.public', [
            'access' => $access->load(['reportVersion', 'createdBy']),
            'project' => $project,
            'reports' => $reports,
            'latestReadiness' => $latestReadiness,
            'findings' => $findings,
            'evidence' => $evidence,
            'featuredReport' => $reports->first(),
        ]);
    }

    public function acknowledge(Request $request, string $token): RedirectResponse
    {
        $access = $this->resolveAccess($token);

        $data = $request->validate([
            'acknowledged_by_name' => ['required', 'string', 'max:160'],
            'acknowledged_by_email' => ['nullable', 'email', 'max:160'],
            'decision_status' => ['required', 'string', Rule::in(ClientPortalAcknowledgement::DECISIONS)],
            'comment' => ['nullable', 'string', 'max:2000'],
            'acknowledge_terms' => ['accepted'],
        ]);

        $acknowledgement = app(ClientPortalAcknowledgementService::class)->record($access, $data, $request);

        app(AuditLogger::class)->record('client_portal_acknowledged', __('messages.audit_messages.client_portal_acknowledged'), $access->project, [
            'client_portal_access_id' => $access->id,
            'client_portal_acknowledgement_id' => $acknowledgement->id,
            'report_version_id' => $access->report_version_id,
            'decision_status' => $acknowledgement->decision_status,
            'acknowledged_by_name' => $acknowledgement->acknowledged_by_name,
        ], 'client_portal');

        return redirect()->route('client-portal.show', $access->token)->with('status', __('messages.client_portal.acknowledged'));
    }

    public function download(string $token, ReportVersion $reportVersion, string $format, ReleaseDecisionReportVersionService $releaseDecisionReportService, ReportDeliveryService $deliveryService, ReportVisualStandardService $reportVisualStandardService): Response
    {
        $access = $this->resolveAccess($token);
        abort_unless($access->allows('reports'), 403);
        abort_unless((int) $reportVersion->project_id === (int) $access->project_id, 404);
        if ($access->report_version_id) {
            abort_unless((int) $access->report_version_id === (int) $reportVersion->id, 403);
        }
        abort_unless(in_array($format, ['md', 'html', 'pdf', 'json'], true), 404);
        abort_unless($deliveryService->isDeliverable($reportVersion), 403);
        $deliveryService->recordPublicDownload($reportVersion, $access, $format);

        $slug = Str::slug($access->project->name.'-'.$reportVersion->type.'-'.$reportVersion->id);

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
            return response(json_encode($reportVersion->data_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 200, [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$slug.'.json"',
            ]);
        }

        return response($reportVersion->content_markdown ?: '', 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$slug.'.md"',
        ]);
    }

    private function resolveAccess(string $token): ClientPortalAccess
    {
        $access = ClientPortalAccess::with(['project', 'reportVersion', 'latestAcknowledgement', 'acknowledgements' => fn ($query) => $query->latest('acknowledged_at')])->where('token', $token)->firstOrFail();
        abort_unless($access->is_usable, 403);

        return $access;
    }
}
