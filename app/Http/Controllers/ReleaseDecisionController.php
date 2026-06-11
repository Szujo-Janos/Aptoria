<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ReleaseDecision;
use App\Services\ReleaseDecisions\ReleaseDecisionRoomService;
use App\Services\Reports\ReportPresentationService;
use App\Services\Settings\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReleaseDecisionController extends Controller
{
    public function index(Project $project, ReleaseDecisionRoomService $room, SettingService $settings): View
    {
        $roomSummary = $room->summarize($project);
        $decisions = $project->releaseDecisions()
            ->with(['owner', 'releaseGate'])
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('release_decisions.index', compact('project', 'roomSummary', 'decisions'));
    }

    public function store(Project $project, Request $request, ReleaseDecisionRoomService $room): RedirectResponse
    {
        $validated = $request->validate([
            'release_name' => ['nullable', 'string', 'max:255'],
            'target_environment' => ['nullable', 'string', 'max:255'],
            'decision_status' => ['required', 'string', Rule::in(ReleaseDecision::STATUSES)],
            'decision_notes' => ['nullable', 'string', 'max:10000'],
        ]);

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $decision = $room->createDecision($project, $validated, $user);

        return redirect()
            ->route('projects.release-decisions.show', [$project, $decision])
            ->with('success', __('messages.release_decisions.created'));
    }

    public function show(Project $project, ReleaseDecision $releaseDecision): View
    {
        $this->ensureDecisionBelongsToProject($project, $releaseDecision);
        $releaseDecision->load(['owner', 'releaseGate', 'project']);

        return view('release_decisions.show', compact('project', 'releaseDecision'));
    }

    public function markdown(Project $project, ReleaseDecision $releaseDecision, ReleaseDecisionRoomService $room): Response
    {
        $this->ensureDecisionBelongsToProject($project, $releaseDecision);
        $filename = Str::slug((string) ($project->slug ?: $project->id)).'-release-decision-'.$releaseDecision->id.'-'.now()->format('Ymd-His').'.md';

        return response($room->markdown($releaseDecision), 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function html(Project $project, ReleaseDecision $releaseDecision, ReleaseDecisionRoomService $room, ReportPresentationService $presentation): Response
    {
        $this->ensureDecisionBelongsToProject($project, $releaseDecision);
        $filename = Str::slug((string) ($project->slug ?: $project->id)).'-release-decision-'.$releaseDecision->id.'-'.now()->format('Ymd-His').'.html';
        $markdown = $room->markdown($releaseDecision);

        return response($presentation->htmlFromMarkdown($markdown, 'Release Decision Package #'.$releaseDecision->id, $project), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function pdf(Project $project, ReleaseDecision $releaseDecision, ReleaseDecisionRoomService $room, ReportPresentationService $presentation): Response
    {
        $this->ensureDecisionBelongsToProject($project, $releaseDecision);
        $filename = Str::slug((string) ($project->slug ?: $project->id)).'-release-decision-'.$releaseDecision->id.'-'.now()->format('Ymd-His').'.pdf';
        $markdown = $room->markdown($releaseDecision);

        return response($presentation->pdfFromMarkdown($markdown, 'Release Decision Package #'.$releaseDecision->id, $project), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function json(Project $project, ReleaseDecision $releaseDecision): JsonResponse
    {
        $this->ensureDecisionBelongsToProject($project, $releaseDecision);

        return response()->json([
            'id' => $releaseDecision->id,
            'project_id' => $project->id,
            'decision_status' => $releaseDecision->decision_status,
            'decision_label' => $releaseDecision->status_label,
            'package_checksum' => $releaseDecision->package_checksum,
            'decision_package' => $releaseDecision->decision_package_json,
        ], 200, ['X-Content-Type-Options' => 'nosniff']);
    }

    private function ensureDecisionBelongsToProject(Project $project, ReleaseDecision $releaseDecision): void
    {
        abort_unless($releaseDecision->project_id === $project->id, 404);
    }
}
