<?php

namespace App\Http\Controllers;

use App\Http\Requests\QaReleaseGateDecisionRequest;
use App\Http\Requests\QaReleaseGateRequest;
use App\Models\Project;
use App\Models\QaReleaseGate;
use App\Services\ReleaseGates\QaReleaseGateService;
use App\Services\Reports\ReportPresentationService;
use App\Services\Settings\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QaReleaseGateController extends Controller
{
    public function index(Project $project, SettingService $settings, QaReleaseGateService $gates): View
    {
        $evaluation = $gates->evaluate($project);
        $releaseGates = $project->qaReleaseGates()
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('release_gates.index', compact('project', 'evaluation', 'releaseGates'));
    }

    public function create(Project $project, QaReleaseGateService $gates): View
    {
        $evaluation = $gates->evaluate($project);
        $releaseGate = new QaReleaseGate([
            'release_name' => $project->name.' '.now()->format('Y-m-d').' release gate',
            'target_environment' => $project->defaultEnvironment()?->name,
            'gate_profile' => QaReleaseGate::PROFILE_STANDARD,
            'final_decision' => $evaluation['default_decision'],
        ]);

        return view('release_gates.create', compact('project', 'releaseGate', 'evaluation'));
    }

    public function store(Project $project, QaReleaseGateRequest $request, QaReleaseGateService $gates): RedirectResponse
    {
        $releaseGate = $gates->createGate($project, $request->validated());

        return redirect()
            ->route('projects.release-gates.show', [$project, $releaseGate])
            ->with('success', __('messages.release_gates.created'));
    }

    public function show(Project $project, QaReleaseGate $releaseGate): View
    {
        $this->ensureGateBelongsToProject($project, $releaseGate);
        $releaseGate->load(['items.endpoint', 'blockers.endpoint', 'warnings.endpoint', 'evidence.endpoint', 'recommendations.endpoint']);

        return view('release_gates.show', compact('project', 'releaseGate'));
    }

    public function updateDecision(Project $project, QaReleaseGate $releaseGate, QaReleaseGateDecisionRequest $request, QaReleaseGateService $gates): RedirectResponse
    {
        $this->ensureGateBelongsToProject($project, $releaseGate);
        $gates->updateDecision($releaseGate, $request->validated());

        return redirect()
            ->route('projects.release-gates.show', [$project, $releaseGate])
            ->with('success', __('messages.release_gates.decision_updated'));
    }

    public function markdown(Project $project, QaReleaseGate $releaseGate, QaReleaseGateService $gates): Response
    {
        $this->ensureGateBelongsToProject($project, $releaseGate);
        $filename = Str::slug((string) ($project->slug ?: $project->id)).'-release-gate-'.$releaseGate->id.'-'.now()->format('Ymd-His').'.md';

        return response($gates->markdown($releaseGate), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function html(Project $project, QaReleaseGate $releaseGate, QaReleaseGateService $gates, ReportPresentationService $presentation): Response
    {
        $this->ensureGateBelongsToProject($project, $releaseGate);
        $filename = Str::slug((string) ($project->slug ?: $project->id)).'-release-gate-'.$releaseGate->id.'-'.now()->format('Ymd-His').'.html';
        $markdown = $gates->markdown($releaseGate);

        return response($presentation->htmlFromMarkdown($markdown, 'QA Release Gate #'.$releaseGate->id, $project), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function pdf(Project $project, QaReleaseGate $releaseGate, QaReleaseGateService $gates, ReportPresentationService $presentation): Response
    {
        $this->ensureGateBelongsToProject($project, $releaseGate);
        $filename = Str::slug((string) ($project->slug ?: $project->id)).'-release-gate-'.$releaseGate->id.'-'.now()->format('Ymd-His').'.pdf';
        $markdown = $gates->markdown($releaseGate);

        return response($presentation->pdfFromMarkdown($markdown, 'QA Release Gate #'.$releaseGate->id, $project), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function ensureGateBelongsToProject(Project $project, QaReleaseGate $releaseGate): void
    {
        abort_unless($releaseGate->project_id === $project->id, 404);
    }
}
