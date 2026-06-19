<?php

namespace App\Http\Controllers;

use App\Models\FindingDuplicateCandidate;
use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\FindingDeduplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FindingMergeController extends Controller
{
    public function index(Project $project): View
    {
        return view('findings.dedup', [
            'project' => $project,
            'candidates' => $project->findingDuplicateCandidates()->with(['primaryFinding.endpoint', 'duplicateFinding.endpoint'])->latest()->get(),
            'mergedFindings' => $project->findings()->whereNotNull('merged_into_finding_id')->with('mergedInto')->latest('merged_at')->limit(25)->get(),
        ]);
    }

    public function scan(Project $project, FindingDeduplicationService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $summary = $service->scan($project);
        $auditLogger->record('finding_duplicate_scan_completed', __('messages.audit_messages.finding_duplicate_scan_completed'), $project, $summary, 'finding', 'info');
        return redirect()->route('projects.findings.dedup.index', $project)->with('status', __('messages.finding_dedup.scan_completed'));
    }

    public function merge(Request $request, Project $project, FindingDuplicateCandidate $candidate, FindingDeduplicationService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate(['merge_note' => ['nullable', 'string', 'max:2000']]);
        $primary = $service->merge($project, $candidate, $request->user(), $data['merge_note'] ?? null);
        $auditLogger->record('finding_duplicate_merged', __('messages.audit_messages.finding_duplicate_merged'), $project, ['candidate_id' => $candidate->id, 'primary_finding_id' => $primary->id], 'finding', 'warning');
        return redirect()->route('projects.findings.show', [$project, $primary])->with('status', __('messages.finding_dedup.merged'));
    }

    public function dismiss(Project $project, FindingDuplicateCandidate $candidate, FindingDeduplicationService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $service->dismiss($project, $candidate);
        $auditLogger->record('finding_duplicate_dismissed', __('messages.audit_messages.finding_duplicate_dismissed'), $project, ['candidate_id' => $candidate->id], 'finding', 'info');
        return redirect()->route('projects.findings.dedup.index', $project)->with('status', __('messages.finding_dedup.dismissed'));
    }
}
