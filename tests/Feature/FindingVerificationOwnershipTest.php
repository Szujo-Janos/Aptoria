<?php

namespace Tests\Feature;

use App\Models\Finding;
use App\Models\FindingComment;
use App\Models\FindingEvidence;
use App\Models\Project;
use App\Models\User;
use App\Services\ReleaseReadinessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindingVerificationOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_due_date_and_verification_fields_are_saved_and_filterable(): void
    {
        [$admin, $project] = $this->seedProject('ownership-api');
        $owner = User::query()->create([
            'name' => 'QA Owner',
            'email' => 'qa-owner@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->post(route('projects.findings.store', $project), [
                'title' => 'Payment callback needs retest',
                'source' => Finding::SOURCE_MANUAL,
                'severity' => Finding::SEVERITY_HIGH,
                'priority' => Finding::PRIORITY_CRITICAL,
                'status' => Finding::STATUS_READY_FOR_RETEST,
                'verification_status' => Finding::VERIFICATION_READY_FOR_RETEST,
                'owner_user_id' => $owner->id,
                'due_date' => now()->subDay()->format('Y-m-d\TH:i'),
                'retest_required' => '1',
                'fix_evidence_required' => '1',
            ])
            ->assertRedirect();

        $finding = Finding::query()->where('title', 'Payment callback needs retest')->firstOrFail();

        $this->assertSame($owner->id, $finding->owner_user_id);
        $this->assertSame(Finding::PRIORITY_CRITICAL, $finding->priority);
        $this->assertSame(Finding::STATUS_READY_FOR_RETEST, $finding->status);
        $this->assertSame(Finding::VERIFICATION_READY_FOR_RETEST, $finding->verification_status);
        $this->assertTrue($finding->retest_required);
        $this->assertTrue($finding->fix_evidence_required);
        $this->assertTrue($finding->is_overdue);

        $this->actingAs($admin)
            ->get(route('projects.findings.index', [$project, 'verification' => Finding::VERIFICATION_READY_FOR_RETEST, 'owner' => $owner->id, 'due' => 'overdue']))
            ->assertOk()
            ->assertSee('Payment callback needs retest')
            ->assertSee(__('messages.findings.statuses.ready_for_retest'))
            ->assertSee(__('messages.findings.overdue'));
    }

    public function test_ready_for_retest_retest_failed_verified_and_reopened_workflow_updates_release_readiness(): void
    {
        [$admin, $project] = $this->seedProject('verification-api');

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'title' => 'Token refresh regression',
            'source' => Finding::SOURCE_REGRESSION,
            'severity' => Finding::SEVERITY_HIGH,
            'priority' => Finding::PRIORITY_HIGH,
            'status' => Finding::STATUS_FIXED,
            'verification_status' => Finding::VERIFICATION_PENDING,
        ]);

        $this->actingAs($admin)
            ->patch(route('projects.findings.lifecycle.update', [$project, $finding]), [
                'status' => Finding::STATUS_READY_FOR_RETEST,
                'note' => 'Developer marked this as ready for QA retest.',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $finding->refresh();
        $this->assertSame(Finding::STATUS_READY_FOR_RETEST, $finding->status);
        $this->assertSame(Finding::VERIFICATION_READY_FOR_RETEST, $finding->verification_status);
        $this->assertTrue($finding->retest_required);

        $this->actingAs($admin)
            ->patch(route('projects.findings.lifecycle.update', [$project, $finding]), [
                'status' => Finding::STATUS_RETEST_FAILED,
                'note' => 'The bug is still reproducible.',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $finding->refresh();
        $this->assertSame(Finding::STATUS_RETEST_FAILED, $finding->status);
        $this->assertSame(Finding::VERIFICATION_RETEST_FAILED, $finding->verification_status);
        $this->assertSame(Finding::RETEST_FAIL, $finding->retest_result);
        $this->assertNotNull($finding->last_retest_at);

        $summary = app(ReleaseReadinessService::class)->summarize($project->fresh());
        $this->assertSame(1, $summary['finding_counts']['retest_failed']);
        $this->assertTrue(collect($summary['blocking_issues'])->contains(__('messages.release_readiness.issues.retest_failed_findings', ['count' => 1])));

        $this->actingAs($admin)
            ->patch(route('projects.findings.lifecycle.update', [$project, $finding]), [
                'status' => Finding::STATUS_FIXED,
                'note' => 'Fix was updated after failed retest.',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $this->actingAs($admin)
            ->patch(route('projects.findings.lifecycle.update', [$project, $finding]), [
                'status' => Finding::STATUS_VERIFIED,
                'note' => 'Retest passed with fresh evidence.',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $finding->refresh();
        $this->assertSame(Finding::STATUS_VERIFIED, $finding->status);
        $this->assertSame(Finding::VERIFICATION_VERIFIED, $finding->verification_status);
        $this->assertSame(Finding::RETEST_PASS, $finding->retest_result);
        $this->assertSame($admin->id, $finding->verified_by_user_id);
        $this->assertNotNull($finding->verified_at);

        $this->actingAs($admin)
            ->patch(route('projects.findings.lifecycle.update', [$project, $finding]), [
                'status' => Finding::STATUS_REOPENED,
                'note' => 'Regression returned after verification.',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $this->assertSame(Finding::STATUS_REOPENED, $finding->fresh()->status);
        $this->assertSame(1, $finding->fresh()->reopened_count);
    }

    public function test_comments_retest_evidence_and_reports_include_verification_context(): void
    {
        [$admin, $project] = $this->seedProject('verification-report-api');

        $finding = Finding::query()->create([
            'project_id' => $project->id,
            'owner_user_id' => $admin->id,
            'title' => 'Invoice endpoint fixed but awaiting QA verification',
            'source' => Finding::SOURCE_MANUAL,
            'severity' => Finding::SEVERITY_MEDIUM,
            'priority' => Finding::PRIORITY_HIGH,
            'status' => Finding::STATUS_READY_FOR_RETEST,
            'verification_status' => Finding::VERIFICATION_READY_FOR_RETEST,
            'retest_required' => true,
            'fix_evidence_required' => true,
        ]);

        FindingEvidence::query()->create([
            'project_id' => $project->id,
            'finding_id' => $finding->id,
            'type' => FindingEvidence::TYPE_RETEST,
            'source_label' => 'QA retest evidence',
            'content' => 'Retest request returned HTTP 200.',
        ]);

        $this->actingAs($admin)
            ->post(route('projects.findings.comments.store', [$project, $finding]), [
                'type' => FindingComment::TYPE_RETEST_NOTE,
                'body' => 'Retest queued for release candidate 2.',
            ])
            ->assertRedirect(route('projects.findings.show', [$project, $finding]));

        $this->assertDatabaseHas('finding_comments', [
            'finding_id' => $finding->id,
            'type' => FindingComment::TYPE_RETEST_NOTE,
            'body' => 'Retest queued for release candidate 2.',
        ]);
        $this->assertTrue($finding->fresh()->has_retest_evidence);

        $this->actingAs($admin)
            ->get(route('projects.findings.show', [$project, $finding]))
            ->assertOk()
            ->assertSee(__('messages.findings.verification.title'))
            ->assertSee(__('messages.findings.retest_evidence_present'))
            ->assertSee('Retest queued for release candidate 2.');

        $this->actingAs($admin)
            ->post(route('projects.reports.builder.markdown', $project), [
                'title' => 'Verification QA Report',
                'audience' => 'release',
                'decision' => 'conditional',
                'sections' => ['release_readiness', 'findings_evidence'],
                'finding_limit' => 25,
                'endpoint_limit' => 25,
                'test_case_limit' => 25,
                'contract_result_limit' => 25,
                'include_evidence_details' => '1',
            ])
            ->assertOk()
            ->assertSee('Finding Verification Summary', false)
            ->assertSee('Invoice endpoint fixed but awaiting QA verification', false)
            ->assertSee('QA retest evidence', false);
    }

    /** @return array{0: User, 1: Project} */
    private function seedProject(string $slug): array
    {
        $admin = User::query()->create([
            'name' => 'Verification Admin',
            'email' => $slug.'@example.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $project = Project::query()->create([
            'user_id' => $admin->id,
            'name' => 'Verification API '.$slug,
            'slug' => $slug,
            'base_url' => 'https://api.example.test',
            'is_active' => true,
        ]);

        return [$admin, $project];
    }
}
