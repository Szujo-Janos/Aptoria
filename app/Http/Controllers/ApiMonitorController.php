<?php

namespace App\Http\Controllers;

use App\Models\ApiMonitor;
use App\Models\Environment;
use App\Models\MonitorAlertEvent;
use App\Models\Project;
use App\Models\Snapshot;
use App\Services\Monitors\MonitorAlertService;
use App\Services\Monitors\ScheduledMonitorService;
use App\Services\Settings\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApiMonitorController extends Controller
{
    public function globalIndex(SettingService $settings): View
    {
        $monitors = ApiMonitor::query()
            ->with(['project', 'environment', 'baselineSnapshot', 'testSuite', 'lastScanRun', 'lastSnapshot', 'lastCompareRun'])
            ->withCount(['alertEvents', 'openAlertEvents'])
            ->latest('updated_at')
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('monitors.global-index', compact('monitors'));
    }

    public function globalAlerts(Request $request, SettingService $settings): View
    {
        $query = MonitorAlertEvent::query()
            ->with(['project', 'monitor', 'acknowledger'])
            ->latest();

        if ($request->filled('channel')) {
            $query->where('channel', (string) $request->query('channel'));
        }

        if ($request->filled('severity')) {
            $query->where('severity', (string) $request->query('severity'));
        }

        if ($request->boolean('open')) {
            $query->whereNull('acknowledged_at');
        }

        $alerts = $query->paginate($settings->integer('app.items_per_page', 25))->withQueryString();

        return view('monitors.global-alerts', compact('alerts'));
    }

    public function index(Project $project, SettingService $settings): View
    {
        $monitors = $project->apiMonitors()
            ->with(['environment', 'baselineSnapshot', 'testSuite', 'lastScanRun', 'lastSnapshot', 'lastCompareRun'])
            ->withCount(['alertEvents', 'openAlertEvents'])
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('monitors.index', compact('project', 'monitors'));
    }

    public function create(Project $project, ScheduledMonitorService $monitorService): View
    {
        return view('monitors.create', [
            'project' => $project,
            'monitor' => new ApiMonitor([
                'frequency' => ApiMonitor::FREQUENCY_DAILY,
                'is_enabled' => true,
                'auto_snapshot' => true,
                'auto_compare' => true,
                'notify_dashboard' => true,
                'alert_on_recovery' => true,
                'alert_on_critical_finding' => true,
                'alert_on_high_finding' => true,
                'alert_on_http_5xx' => true,
                'alert_on_sensitive_data' => true,
                'alert_on_broken_auth' => true,
                'alert_on_schema_drift' => true,
                'next_run_at' => $monitorService->nextRunAt(ApiMonitor::FREQUENCY_DAILY),
            ]),
            'environments' => $project->environments()->orderBy('name')->get(),
            'snapshots' => $project->snapshots()->latest()->limit(100)->get(),
            'testSuites' => $project->testSuites()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Project $project, ScheduledMonitorService $monitorService): RedirectResponse
    {
        $validated = $this->validated($request, $project);
        $frequency = $validated['frequency'] ?? ApiMonitor::FREQUENCY_DAILY;

        $monitor = $project->apiMonitors()->create([
            ...$validated,
            'created_by' => $request->user()?->id,
            'is_enabled' => $request->boolean('is_enabled'),
            'auto_snapshot' => $request->boolean('auto_snapshot'),
            'auto_compare' => $request->boolean('auto_compare'),
            'notify_dashboard' => $request->boolean('notify_dashboard'),
            'alert_on_recovery' => $request->boolean('alert_on_recovery'),
            'alert_on_critical_finding' => $request->boolean('alert_on_critical_finding'),
            'alert_on_high_finding' => $request->boolean('alert_on_high_finding'),
            'alert_on_http_5xx' => $request->boolean('alert_on_http_5xx'),
            'alert_on_sensitive_data' => $request->boolean('alert_on_sensitive_data'),
            'alert_on_broken_auth' => $request->boolean('alert_on_broken_auth'),
            'alert_on_schema_drift' => $request->boolean('alert_on_schema_drift'),
            'next_run_at' => $request->boolean('is_enabled') ? $monitorService->nextRunAt($frequency) : null,
            'last_status' => ApiMonitor::STATUS_NEVER_RUN,
        ]);

        return redirect()
            ->route('projects.monitors.index', $project)
            ->with('success', __('messages.monitors.created', ['name' => $monitor->name]));
    }

    public function edit(Project $project, ApiMonitor $monitor): View
    {
        $this->ensureMonitorBelongsToProject($project, $monitor);

        return view('monitors.edit', [
            'project' => $project,
            'monitor' => $monitor,
            'environments' => $project->environments()->orderBy('name')->get(),
            'snapshots' => $project->snapshots()->latest()->limit(100)->get(),
            'testSuites' => $project->testSuites()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Project $project, ApiMonitor $monitor, ScheduledMonitorService $monitorService): RedirectResponse
    {
        $this->ensureMonitorBelongsToProject($project, $monitor);

        $validated = $this->validated($request, $project);
        $frequencyChanged = ($validated['frequency'] ?? $monitor->frequency) !== $monitor->frequency;
        $isEnabled = $request->boolean('is_enabled');

        $monitor->update([
            ...$validated,
            'is_enabled' => $isEnabled,
            'auto_snapshot' => $request->boolean('auto_snapshot'),
            'auto_compare' => $request->boolean('auto_compare'),
            'notify_dashboard' => $request->boolean('notify_dashboard'),
            'alert_on_recovery' => $request->boolean('alert_on_recovery'),
            'alert_on_critical_finding' => $request->boolean('alert_on_critical_finding'),
            'alert_on_high_finding' => $request->boolean('alert_on_high_finding'),
            'alert_on_http_5xx' => $request->boolean('alert_on_http_5xx'),
            'alert_on_sensitive_data' => $request->boolean('alert_on_sensitive_data'),
            'alert_on_broken_auth' => $request->boolean('alert_on_broken_auth'),
            'alert_on_schema_drift' => $request->boolean('alert_on_schema_drift'),
            'next_run_at' => $isEnabled
                ? ($frequencyChanged || $monitor->next_run_at === null ? $monitorService->nextRunAt($validated['frequency']) : $monitor->next_run_at)
                : null,
        ]);

        return redirect()
            ->route('projects.monitors.index', $project)
            ->with('success', __('messages.monitors.updated', ['name' => $monitor->name]));
    }

    public function destroy(Project $project, ApiMonitor $monitor): RedirectResponse
    {
        $this->ensureMonitorBelongsToProject($project, $monitor);
        $monitor->delete();

        return redirect()
            ->route('projects.monitors.index', $project)
            ->with('success', __('messages.monitors.deleted'));
    }


    public function alerts(Project $project, ApiMonitor $monitor, SettingService $settings): View
    {
        $this->ensureMonitorBelongsToProject($project, $monitor);

        $monitor->loadMissing(['project', 'environment']);
        $monitor->loadCount(['alertEvents', 'openAlertEvents']);
        $alerts = $monitor->alertEvents()
            ->with('acknowledger')
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('monitors.alerts', compact('project', 'monitor', 'alerts'));
    }


    public function acknowledge(Request $request, Project $project, ApiMonitor $monitor, MonitorAlertEvent $alert): RedirectResponse
    {
        $this->ensureMonitorBelongsToProject($project, $monitor);
        abort_unless($alert->api_monitor_id === $monitor->id && $alert->project_id === $project->id, 404);

        $validated = $request->validate([
            'acknowledgement_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $alert->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $request->user()?->id,
            'acknowledgement_note' => $validated['acknowledgement_note'] ?? null,
        ]);

        return redirect()
            ->route('projects.monitors.alerts', [$project, $monitor])
            ->with('success', __('messages.monitors.alert_acknowledged'));
    }

    public function testNotification(Project $project, ApiMonitor $monitor, MonitorAlertService $alerts): RedirectResponse
    {
        $this->ensureMonitorBelongsToProject($project, $monitor);

        $events = $alerts->sendTest($monitor);
        $failed = collect($events)->where('delivery_status', MonitorAlertEvent::DELIVERY_FAILED)->count();

        return redirect()
            ->route('projects.monitors.alerts', [$project, $monitor])
            ->with($failed > 0 ? 'error' : 'success', __('messages.monitors.test_notification_sent', ['count' => count($events), 'failed' => $failed]));
    }

    public function run(Request $request, Project $project, ApiMonitor $monitor, ScheduledMonitorService $monitorService): RedirectResponse
    {
        $this->ensureMonitorBelongsToProject($project, $monitor);

        $result = $monitorService->runMonitor($monitor, $request->user());

        return redirect()
            ->route('projects.monitors.index', $project)
            ->with(($result['status'] ?? null) === ApiMonitor::STATUS_FAILED ? 'error' : 'success', __('messages.monitors.run_now_completed', ['message' => $result['message'] ?? __('messages.common.done')]));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, Project $project): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'frequency' => ['required', 'string', Rule::in(ApiMonitor::FREQUENCIES)],
            'environment_id' => [
                'nullable',
                'integer',
                Rule::exists('environments', 'id')->where('project_id', $project->id),
            ],
            'baseline_snapshot_id' => [
                'nullable',
                'integer',
                Rule::exists('snapshots', 'id')->where('project_id', $project->id),
            ],
            'test_suite_id' => [
                'nullable',
                'integer',
                Rule::exists('test_suites', 'id')->where('project_id', $project->id),
            ],
            'alert_email' => ['nullable', 'email', 'max:180'],
            'alert_webhook_url' => ['nullable', 'url', 'max:2048'],
        ]);
    }

    private function ensureMonitorBelongsToProject(Project $project, ApiMonitor $monitor): void
    {
        abort_unless($monitor->project_id === $project->id, 404);
    }
}
