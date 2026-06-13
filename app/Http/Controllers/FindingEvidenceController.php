<?php

namespace App\Http\Controllers;

use App\Http\Requests\FindingEvidenceRequest;
use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FindingEvidenceController extends Controller
{
    public function store(FindingEvidenceRequest $request, Project $project, Finding $finding): RedirectResponse
    {
        $this->ensureFindingBelongsToProject($project, $finding);
        $this->authorizeProject($project, 'evidence.manage');

        $validated = $request->validated();
        $attachment = $request->file('attachment');
        unset($validated['attachment']);

        $payload = [
            ...$validated,
            'project_id' => $project->id,
            'captured_at' => $validated['captured_at'] ?? now(),
            'captured_by_user_id' => $request->user()?->id,
        ];

        if ($attachment) {
            $directory = 'private/finding-evidence/'.$project->id.'/'.$finding->id;
            $filename = now()->format('YmdHis').'-'.Str::random(10).'.'.$attachment->getClientOriginalExtension();
            $path = $attachment->storeAs($directory, $filename, 'local');
            $absolutePath = Storage::disk('local')->path($path);

            $payload = [
                ...$payload,
                'attachment_disk' => 'local',
                'attachment_path' => $path,
                'attachment_original_name' => $attachment->getClientOriginalName(),
                'attachment_mime_type' => $attachment->getClientMimeType(),
                'attachment_size' => $attachment->getSize(),
                'attachment_sha256' => is_file($absolutePath) ? hash_file('sha256', $absolutePath) : null,
            ];
        }

        $finding->evidence()->create($payload);

        return redirect()
            ->route('projects.findings.show', [$project, $finding])
            ->with('success', __('messages.findings.evidence_created'));
    }

    public function download(Project $project, Finding $finding, FindingEvidence $evidence): StreamedResponse
    {
        $this->ensureEvidenceBelongsToFinding($project, $finding, $evidence);
        $this->authorizeProject($project, 'exports.download');
        abort_unless($evidence->attachment_path, 404);
        abort_unless(Storage::disk($evidence->attachment_disk ?: 'local')->exists($evidence->attachment_path), 404);

        return Storage::disk($evidence->attachment_disk ?: 'local')->download(
            $evidence->attachment_path,
            $evidence->attachment_original_name ?: basename($evidence->attachment_path)
        );
    }

    public function destroy(Project $project, Finding $finding, FindingEvidence $evidence): RedirectResponse
    {
        $this->ensureEvidenceBelongsToFinding($project, $finding, $evidence);
        $this->authorizeProject($project, 'evidence.manage');
        $evidence->deleteAttachmentFile();
        $evidence->delete();

        return redirect()
            ->route('projects.findings.show', [$project, $finding])
            ->with('success', __('messages.findings.evidence_deleted'));
    }

    private function ensureFindingBelongsToProject(Project $project, Finding $finding): void
    {
        abort_unless($finding->project_id === $project->id, 404);
    }

    private function ensureEvidenceBelongsToFinding(Project $project, Finding $finding, FindingEvidence $evidence): void
    {
        $this->ensureFindingBelongsToProject($project, $finding);
        abort_unless($evidence->finding_id === $finding->id && $evidence->project_id === $project->id, 404);
    }
}
