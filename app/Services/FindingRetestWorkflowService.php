<?php

namespace App\Services;

use App\Models\Finding;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\User;

class FindingRetestWorkflowService
{
    public function requestRetest(Project $project, Finding $finding, ?User $user = null, ?string $note = null): Finding
    {
        $this->ensureProjectFinding($project, $finding);

        $finding->forceFill([
            'retest_required' => true,
            'retest_status' => 'required',
            'retest_note' => $note,
            'retest_requested_at' => now(),
            'ready_for_retest_at' => null,
            'retested_at' => null,
            'retested_by_user_id' => null,
            'status' => in_array($finding->status, ['verified'], true) ? 'ready_for_retest' : $finding->status,
        ])->save();

        return $finding->refresh();
    }

    public function markReady(Project $project, Finding $finding, ?User $user = null, ?string $note = null): Finding
    {
        $this->ensureProjectFinding($project, $finding);

        $finding->forceFill([
            'retest_required' => true,
            'retest_status' => 'ready_for_retest',
            'retest_note' => $note ?: $finding->retest_note,
            'ready_for_retest_at' => now(),
            'status' => 'ready_for_retest',
        ])->save();

        return $finding->refresh();
    }

    public function recordResult(Project $project, Finding $finding, string $result, ?User $user = null, ?string $note = null): Finding
    {
        $this->ensureProjectFinding($project, $finding);
        abort_unless(in_array($result, ['passed', 'failed'], true), 422);

        $evidence = $this->createRetestEvidence($project, $finding, $result, $user, $note);

        $finding->forceFill([
            'retest_required' => $result === 'failed',
            'retest_status' => $result,
            'retest_note' => $note,
            'retested_at' => now(),
            'retested_by_user_id' => $user?->id,
            'retest_evidence_id' => $evidence->id,
            'status' => $result === 'passed' ? 'verified' : 'retest_failed',
        ])->save();

        return $finding->refresh();
    }

    private function createRetestEvidence(Project $project, Finding $finding, string $result, ?User $user, ?string $note): FindingEvidence
    {
        $title = $result === 'passed'
            ? __('messages.findings.retest_passed_evidence_title')
            : __('messages.findings.retest_failed_evidence_title');

        $content = trim($note ?: __('messages.findings.retest_evidence_default_note'));
        $fingerprint = implode('|', [$finding->id, $result, $content, now()->toDateTimeString()]);

        return $finding->evidence()->create([
            'project_id' => $project->id,
            'endpoint_id' => $finding->endpoint_id,
            'scan_result_id' => $finding->scan_result_id,
            'type' => 'retest',
            'title' => $title,
            'source_label' => __('messages.findings.retest_workflow'),
            'content' => $content,
            'captured_at' => now(),
            'captured_by_user_id' => $user?->id,
            'sha256' => hash('sha256', $fingerprint),
            'metadata_json' => [
                'result' => $result,
                'finding_status_before' => $finding->status,
                'retest_status_before' => $finding->retest_status,
            ],
        ]);
    }

    private function ensureProjectFinding(Project $project, Finding $finding): void
    {
        abort_unless((int) $finding->project_id === (int) $project->id, 404);
    }
}
