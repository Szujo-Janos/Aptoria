<?php

namespace App\Http\Controllers;

use App\Models\ClientPortalAccess;
use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\ClientPortalDecisionHandoffService;
use App\Services\ReportDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientPortalController extends Controller
{
    public function index(Project $project, ReportDeliveryService $deliveryService, ClientPortalDecisionHandoffService $handoffService): View
    {
        $accesses = Schema::hasTable('client_portal_accesses')
            ? $project->clientPortalAccesses()->with(['reportVersion', 'createdBy', 'latestAcknowledgement'])->latest()->get()
            : collect();

        $reports = Schema::hasTable('report_versions')
            ? $deliveryService->approvedReports($project)->get()
            : collect();

        $latestReadiness = Schema::hasTable('release_readiness_runs')
            ? $project->releaseReadinessRuns()->latest()->first()
            : null;

        $decisionPackages = $handoffService->approvedDecisionPackages($project);
        $decisionMatrix = $handoffService->publicMatrix($decisionPackages);

        $acknowledgements = Schema::hasTable('client_portal_acknowledgements')
            ? $project->clientPortalAcknowledgements()->with(['access', 'reportVersion'])->latest('acknowledged_at')->limit(12)->get()
            : collect();

        return view('client_portal.index', [
            'project' => $project,
            'accesses' => $accesses,
            'reports' => $reports,
            'decisionPackages' => $decisionPackages,
            'decisionMatrix' => $decisionMatrix,
            'latestReadiness' => $latestReadiness,
            'acknowledgements' => $acknowledgements,
            'metrics' => [
                'links' => $accesses->count(),
                'active' => $accesses->filter(fn (ClientPortalAccess $access) => $access->is_usable)->count(),
                'viewed' => $accesses->whereNotNull('last_viewed_at')->count(),
                'acknowledged' => $accesses->whereNotNull('acknowledged_at')->count(),
                'ack_pending' => $accesses->filter(fn (ClientPortalAccess $access) => $access->acknowledge_required && ! $access->acknowledged_at)->count(),
                'approved_acknowledgements' => $accesses->where('acknowledgement_decision', 'approved')->count(),
                'deliverable_reports' => $reports->count(),
                'decision_packages' => $decisionPackages->count(),
                'decision_package_blockers' => $decisionMatrix['total_blockers'],
            ],
        ]);
    }

    public function store(Request $request, Project $project, AuditLogger $auditLogger, ReportDeliveryService $deliveryService): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'role' => ['required', Rule::in(ClientPortalAccess::ROLES)],
            'report_version_id' => ['nullable', 'integer'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(ClientPortalAccess::PERMISSIONS)],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'acknowledge_required' => ['nullable', 'boolean'],
        ]);

        $reportId = $data['report_version_id'] ?? null;
        $selectedReport = null;
        if ($reportId) {
            $selectedReport = $project->reportVersions()->whereKey($reportId)->firstOrFail();
            $deliveryService->ensureDeliverable($selectedReport);
        }

        $permissions = array_values(array_intersect($data['permissions'] ?? ['decision_package', 'reports', 'readiness'], ClientPortalAccess::PERMISSIONS));
        if ($selectedReport) {
            $reportData = is_array($selectedReport->data_json) ? $selectedReport->data_json : [];
            if ((bool) $selectedReport->release_gate_id || data_get($reportData, 'source.type') === 'release_gate_decision_package') {
                $permissions[] = 'decision_package';
            }
        }

        $permissions = array_values(array_unique(array_intersect($permissions, ClientPortalAccess::PERMISSIONS)));

        if ($permissions === []) {
            $permissions = ['reports'];
        }

        $access = $project->clientPortalAccesses()->create([
            'report_version_id' => $reportId,
            'created_by_user_id' => $request->user()?->id,
            'name' => $data['name'],
            'role' => $data['role'],
            'permissions_json' => $permissions,
            'is_active' => true,
            'acknowledge_required' => $request->boolean('acknowledge_required'),
            'acknowledgement_status' => $request->boolean('acknowledge_required') ? 'pending' : 'not_required',
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        if ($selectedReport) {
            $deliveryService->markDelivered($selectedReport, $access);
        }

        $auditLogger->record('client_portal_link_created', __('messages.audit_messages.client_portal_link_created'), $project, [
            'client_portal_access_id' => $access->id,
            'report_version_id' => $selectedReport?->id,
            'role' => $access->role,
            'permissions' => $permissions,
        ], 'client_portal');

        return redirect()->route('projects.client-portal.index', $project)->with('status', __('messages.client_portal.created'));
    }

    public function toggle(Project $project, ClientPortalAccess $clientPortalAccess, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $clientPortalAccess);

        $clientPortalAccess->update(['is_active' => ! $clientPortalAccess->is_active]);

        $auditLogger->record('client_portal_link_toggled', __('messages.audit_messages.client_portal_link_toggled'), $project, [
            'client_portal_access_id' => $clientPortalAccess->id,
            'is_active' => $clientPortalAccess->is_active,
        ], 'client_portal');

        return redirect()->route('projects.client-portal.index', $project)->with('status', __('messages.client_portal.updated'));
    }

    public function destroy(Project $project, ClientPortalAccess $clientPortalAccess, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureBelongsToProject($project, $clientPortalAccess);

        $auditLogger->record('client_portal_link_deleted', __('messages.audit_messages.client_portal_link_deleted'), $project, [
            'client_portal_access_id' => $clientPortalAccess->id,
            'name' => $clientPortalAccess->name,
        ], 'client_portal', 'warning');

        $clientPortalAccess->delete();

        return redirect()->route('projects.client-portal.index', $project)->with('status', __('messages.client_portal.deleted'));
    }

    private function ensureBelongsToProject(Project $project, ClientPortalAccess $clientPortalAccess): void
    {
        abort_unless((int) $clientPortalAccess->project_id === (int) $project->id, 404);
    }
}
