<?php

namespace App\Http\Controllers;

use App\Models\AuthProfile;
use App\Models\Environment;
use App\Models\Project;
use App\Models\ProjectSetting;
use App\Models\ScanRun;
use App\Services\AuditLogger;
use App\Services\SafeProbeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SafeScanController extends Controller
{
    public function index(Project $project): View
    {
        $scanRuns = $project->scanRuns()
            ->with(['environment', 'authProfile'])
            ->latest()
            ->limit(25)
            ->get();

        $safeEndpoints = $project->endpoints()
            ->whereIn('method', ['GET', 'HEAD'])
            ->where('is_active', true)
            ->where('excluded_from_scan', false)
            ->count();

        $totalEndpoints = $project->endpoints()->count();
        $lastRun = $scanRuns->first();

        return view('safe_scans.index', [
            'project' => $project,
            'scanRuns' => $scanRuns,
            'lastRun' => $lastRun,
            'metrics' => [
                'total_endpoints' => $totalEndpoints,
                'safe_endpoints' => $safeEndpoints,
                'runs' => $scanRuns->count(),
                'last_passed' => (int) ($lastRun?->summary_value['passed'] ?? 0),
                'last_warnings' => (int) ($lastRun?->summary_value['warning'] ?? 0),
                'last_failed' => (int) ($lastRun?->summary_value['failed'] ?? 0),
                'last_skipped' => (int) ($lastRun?->summary_value['skipped'] ?? 0),
            ],
            'environments' => $project->environments()->orderByDesc('is_default')->orderBy('name')->get(),
            'authProfiles' => $project->authProfiles()->orderByDesc('is_default')->orderBy('name')->get(),
            'settings' => [
                'require_confirmation' => ProjectSetting::get($project, 'scan.require_confirmation', '1') !== '0',
                'safe_methods_only' => ProjectSetting::get($project, 'scan.safe_methods_only', '1') !== '0',
                'allow_private_networks' => ProjectSetting::get($project, 'scan.allow_private_networks', '0') === '1',
            ],
        ]);
    }

    public function store(Request $request, Project $project, SafeProbeService $safeProbeService, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'environment_id' => ['nullable', 'integer'],
            'auth_profile_id' => ['nullable', 'integer'],
            'confirm_safe_scan' => ['accepted'],
        ]);

        $environment = null;
        if (! empty($data['environment_id'])) {
            $environment = $project->environments()->whereKey($data['environment_id'])->firstOrFail();
        }

        $authProfile = null;
        if (! empty($data['auth_profile_id'])) {
            $authProfile = $project->authProfiles()->whereKey($data['auth_profile_id'])->firstOrFail();
        }

        $run = $safeProbeService->run($project, $environment, $authProfile);

        $auditLogger->record('scan_run_completed', __('messages.audit_messages.safe_scan_run_completed'), $project, [
            'scan_run_id' => $run->id,
            'summary' => $run->summary_value,
            'environment_id' => $environment?->id,
            'auth_profile_id' => $authProfile?->id,
        ], 'scan');

        return redirect()->route('projects.safe-scans.show', [$project, $run])->with('status', __('messages.safe_scan.completed'));
    }

    public function show(Project $project, ScanRun $scanRun): View
    {
        abort_unless((int) $scanRun->project_id === (int) $project->id, 404);

        $scanRun->load(['environment', 'authProfile', 'results.endpoint', 'results.environment', 'results.authProfile']);

        return view('safe_scans.show', [
            'project' => $project,
            'scanRun' => $scanRun,
            'results' => $scanRun->results()->with(['endpoint', 'environment', 'authProfile'])->orderBy('id')->get(),
        ]);
    }
}
