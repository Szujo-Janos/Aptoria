<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use App\Services\ProjectHealthService;
use App\Services\QaCoverageMatrixService;
use App\Services\ReleaseReadinessService;
use App\Services\Settings\ProjectSettingService;
use App\Services\Settings\SettingService;
use App\Services\Settings\SettingsRuntimeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(SettingService $settings): View
    {
        $projects = Project::query()
            ->withCount(['environments', 'authProfiles', 'endpoints', 'scanRuns', 'snapshots'])
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('projects.create', ['project' => new Project(['is_active' => true])]);
    }

    public function store(ProjectRequest $request, ProjectSettingService $projectSettings, SettingsRuntimeService $runtime): RedirectResponse
    {
        $project = Project::query()->create([
            ...$request->validated(),
            'user_id' => Auth::id(),
            'is_active' => $request->boolean('is_active'),
        ]);

        Model::withoutEvents(function () use ($project, $projectSettings): void {
            $project->environments()->create([
                'name' => 'staging',
                'base_url' => $project->base_url,
                'is_production' => false,
            ]);

            $project->authProfiles()->create([
                'name' => 'No Auth',
                'type' => 'none',
                'is_default' => true,
                'notes' => __('messages.auth_profiles.no_auth_summary'),
            ]);

            $projectSettings->seedDefaults($project);
        });

        return redirect()->route($runtime->defaultProjectViewRouteName(), $project)->with('success', __('messages.projects.created'));
    }

    public function show(Project $project, ProjectSettingService $projectSettings, ProjectHealthService $projectHealthService, ReleaseReadinessService $releaseReadinessService, QaCoverageMatrixService $qaCoverageMatrixService, SettingService $settings): View
    {
        $project->load([
            'environments.authProfile',
            'authProfiles',
            'endpoints' => fn ($query) => $query->with(['environment', 'authProfile', 'latestScanResult'])->latest()->limit(10),
            'scanRuns' => fn ($query) => $query->with('environment')->latest()->limit(5),
            'snapshots' => fn ($query) => $query->with(['environment', 'scanRun'])->latest()->limit(5),
            'apiMonitors' => fn ($query) => $query->with(['environment', 'baselineSnapshot', 'lastScanRun', 'lastCompareRun'])->latest()->limit(5),
            'testSuites' => fn ($query) => $query->withCount('testCases')->latest()->limit(5),
            'testCases' => fn ($query) => $query->with(['testSuite', 'endpoint', 'latestResult'])->latest()->limit(8),
            'contractValidationRuns' => fn ($query) => $query->with('scanRun.environment')->latest()->limit(5),
            'findings' => fn ($query) => $query->with(['endpoint', 'evidence'])->latest('detected_at')->limit(8),
            'qaReleaseGates' => fn ($query) => $query->latest()->limit(5),
        ]);
        $project->loadCount(['endpoints', 'scanRuns', 'snapshots', 'apiMonitors', 'testSuites', 'testCases', 'contractValidationRuns', 'findings', 'qaReleaseGates']);
        Model::withoutEvents(fn () => $projectSettings->seedDefaults($project));
        $projectSettingSummary = $projectSettings->grouped($project);
        $projectNotes = (string) $projectSettings->get($project, 'project.notes', '');
        $projectHealth = $projectHealthService->summarize($project);
        $releaseReadiness = $releaseReadinessService->summarize($project);
        $qaCoverage = $qaCoverageMatrixService->summarize($project)['summary'];
        $projectCalendarPreviewStartsAt = now()->startOfDay();
        $projectCalendarPreviewEndsAt = now()->addDays(14)->endOfDay();
        $projectCalendarEvents = CalendarEvent::query()
            ->with(['project', 'monitor'])
            ->where('project_id', $project->id)
            ->where('starts_at', '<=', $projectCalendarPreviewEndsAt)
            ->where(function ($query) use ($projectCalendarPreviewStartsAt): void {
                $query->where(function ($inner) use ($projectCalendarPreviewStartsAt): void {
                    $inner->whereNull('ends_at')->where('starts_at', '>=', $projectCalendarPreviewStartsAt);
                })->orWhere('ends_at', '>=', $projectCalendarPreviewStartsAt);
            })
            ->orderBy('starts_at')
            ->limit(6)
            ->get();
        $projectCalendarSummary = [
            'open' => CalendarEvent::query()->where('project_id', $project->id)->whereNull('completed_at')->whereIn('status', [CalendarEvent::STATUS_PLANNED, CalendarEvent::STATUS_IN_PROGRESS])->count(),
            'due_today' => CalendarEvent::query()->where('project_id', $project->id)->whereDate('starts_at', now()->toDateString())->whereIn('status', [CalendarEvent::STATUS_PLANNED, CalendarEvent::STATUS_IN_PROGRESS])->count(),
            'overdue' => CalendarEvent::query()->where('project_id', $project->id)->where('starts_at', '<', now())->whereIn('status', [CalendarEvent::STATUS_PLANNED, CalendarEvent::STATUS_IN_PROGRESS])->count(),
        ];

        $showProjectCalendarPreview = $settings->boolean('ui.show_project_calendar_preview', true);

        return view('projects.show', compact('project', 'projectSettingSummary', 'projectNotes', 'projectHealth', 'releaseReadiness', 'qaCoverage', 'projectCalendarEvents', 'projectCalendarSummary', 'showProjectCalendarPreview'));
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', compact('project'));
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('projects.show', $project)->with('success', __('messages.projects.updated'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', __('messages.projects.deleted'));
    }
}
