<?php

namespace App\Services;

use App\Models\EvidencePack;
use App\Models\Finding;
use App\Models\FindingDuplicateCandidate;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\ReleaseGate;
use App\Models\ReleaseGateEvent;
use App\Models\ReleaseGateItem;
use App\Models\ReleaseReadinessRule;
use App\Models\TestCase;
use App\Models\TestRun;
use App\Models\TestSuite;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DemoShowcaseWorkspaceService
{
    public const DEFAULT_SHOWCASE_SLUG = 'aptoria-full-demo-workspace';
    public const DEMO_OWNER_EMAIL = 'demo-owner@aptoria.dev';

    public function __construct(
        private readonly ComprehensiveDemoProjectService $comprehensiveDemo,
        private readonly ProjectAccessService $projectAccess,
    ) {
    }

    /** @return array<string,mixed> */
    public function ensureForViewer(User $viewer): array
    {
        return DB::transaction(function () use ($viewer): array {
            $project = $this->showcaseProject();

            if (! $project) {
                $owner = $this->resolveOwner($viewer);
                $result = $this->rebuild($owner);
                $project = $result['project'];
            }

            $demoUser = $this->ensureDemoViewer($viewer);
            $this->attachDemoViewer($project, $demoUser, $this->resolveOwner($viewer));
            $this->enrich($project, $this->resolveOwner($viewer));

            return [
                'project' => $project->fresh(),
                'demo_user' => $demoUser->fresh(),
                'created' => false,
                'summary' => $this->summary($project->fresh()),
            ];
        });
    }

    /** @return array<string,mixed> */
    public function rebuild(User $owner): array
    {
        return DB::transaction(function () use ($owner): array {
            $result = $this->comprehensiveDemo->build($owner);
            /** @var Project $project */
            $project = $result['project']->fresh();
            $demoUser = $this->ensureDemoViewer();

            $this->attachDemoViewer($project, $demoUser, $owner);
            $this->enrich($project, $owner);

            return [
                'project' => $project->fresh(),
                'demo_user' => $demoUser->fresh(),
                'deleted_projects' => $result['deleted_projects'] ?? 0,
                'summary' => $this->summary($project->fresh()),
                'highlight_ids' => $result['highlight_ids'] ?? [],
            ];
        });
    }

    public function showcaseProject(): ?Project
    {
        $slug = $this->showcaseSlug();

        return Project::query()->where('slug', $slug)->first();
    }

    public function showcaseSlug(): string
    {
        $slug = trim((string) config('aptoria.demo.showcase_project_slug', self::DEFAULT_SHOWCASE_SLUG));

        return $slug !== '' ? $slug : self::DEFAULT_SHOWCASE_SLUG;
    }

    public function isDemoViewer(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return strtolower(trim((string) $user->email)) === strtolower(trim($this->demoUserEmail()));
    }

    public function shouldAutoOpenFor(?User $user): bool
    {
        return (bool) config('aptoria.demo.mode', false)
            && (string) config('aptoria.demo.viewer_mode', 'readonly') === 'showcase'
            && (bool) config('aptoria.demo.auto_open_showcase', true)
            && $this->isDemoViewer($user);
    }

    public function demoUserEmail(): string
    {
        return (string) config('aptoria.demo.demo_user_email', 'demo@aptoria.dev');
    }

    public function demoUserPassword(): string
    {
        return (string) config('aptoria.demo.demo_user_password', 'aptoria-demo-2026');
    }

    public function ensureDemoViewer(?User $existing = null): User
    {
        $email = $this->demoUserEmail();

        if ($existing && strtolower(trim((string) $existing->email)) === strtolower(trim($email))) {
            $existing->forceFill([
                'name' => $existing->name ?: 'Aptoria Demo Viewer',
                'role' => 'user',
                'locale' => $existing->locale ?: 'en',
                'timezone' => $existing->timezone ?: 'Europe/Budapest',
                'password_change_required' => false,
            ])->save();

            return $existing;
        }

        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Aptoria Demo Viewer',
                'password' => Hash::make($this->demoUserPassword()),
                'role' => 'user',
                'locale' => 'en',
                'timezone' => 'Europe/Budapest',
                'password_change_required' => false,
            ]
        );
    }

    private function resolveOwner(User $actingUser): User
    {
        if ($actingUser->isAdmin()) {
            return $actingUser;
        }

        $owner = User::query()
            ->where('role', 'admin')
            ->where('email', '!=', $this->demoUserEmail())
            ->first();

        if ($owner) {
            return $owner;
        }

        return User::updateOrCreate(
            ['email' => self::DEMO_OWNER_EMAIL],
            [
                'name' => 'Aptoria Demo Owner',
                'password' => Hash::make(Str::random(48).'A1!'),
                'role' => 'admin',
                'locale' => 'en',
                'timezone' => 'Europe/Budapest',
                'password_change_required' => false,
            ]
        );
    }

    private function attachDemoViewer(Project $project, User $demoUser, User $owner): void
    {
        if (! Schema::hasTable('project_memberships')) {
            return;
        }

        ProjectMembership::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $demoUser->id],
            [
                'role' => ProjectMembership::ROLE_READ_ONLY_VIEWER,
                'status' => ProjectMembership::STATUS_ACTIVE,
                'invited_by_user_id' => $owner->id,
                'added_at' => now(),
            ]
        );
    }

    private function enrich(Project $project, User $owner): void
    {
        if (Schema::hasTable('release_readiness_rules')) {
            ReleaseReadinessRule::syncDefaults($project);
        }

        $this->createNativeShowcaseTests($project, $owner);
        $this->createReleaseGateShowcase($project, $owner);
        $this->createEvidencePackShowcase($project, $owner);
        $this->createDuplicateCandidateShowcase($project);
        $this->markShowcaseSettings($project);
    }

    private function createNativeShowcaseTests(Project $project, User $owner): void
    {
        if (! Schema::hasTable('test_suites') || ! Schema::hasTable('test_cases') || ! Schema::hasTable('test_runs')) {
            return;
        }

        if ($project->testSuites()->where('name', 'Showcase regression and release QA suite')->exists()) {
            return;
        }

        $suite = TestSuite::create([
            'project_id' => $project->id,
            'created_by_user_id' => $owner->id,
            'name' => 'Showcase regression and release QA suite',
            'description' => 'Native manual/API QA suite seeded for the public showcase workspace.',
            'status' => 'active',
            'priority' => 'urgent',
            'owner_name' => $owner->name,
            'metadata_json' => ['demo_showcase' => true],
        ]);

        $endpoints = $project->endpoints()->get()->keyBy(fn ($endpoint) => strtolower((string) $endpoint->path));
        $firstEndpoint = $project->endpoints()->first();
        $finding = $project->findings()->whereIn('severity', ['critical', 'high'])->latest()->first();

        $definitions = [
            ['API health smoke check', '/health', 'manual', 'normal', 'pass', 'Validate that the demo health endpoint returns a clean JSON response.'],
            ['Protected account requires auth', '/security/private-account', 'hybrid', 'high', 'blocked', 'Confirm protected resources are represented as guarded evidence.'],
            ['Sensitive token leak triage', '/security/leaky-token-example', 'manual', 'urgent', 'fail', 'Review sensitive field masking and finding evidence traceability.'],
            ['Slow response performance guard', '/errors/slow-response', 'hybrid', 'high', 'fail', 'Demonstrate response-time evidence and release warning behaviour.'],
            ['Scenario template import preview', '/scenarios', 'imported', 'normal', 'pass', 'Show imported QA artifacts mapped into the Aptoria workflow.'],
            ['Release report client handoff', null, 'manual', 'high', 'pass', 'Walk through report, evidence pack and client portal review states.'],
        ];

        foreach ($definitions as [$title, $path, $type, $priority, $status, $description]) {
            $endpoint = $path ? ($endpoints[strtolower($path)] ?? $firstEndpoint) : $firstEndpoint;

            $case = TestCase::create([
                'project_id' => $project->id,
                'test_suite_id' => $suite->id,
                'endpoint_id' => $endpoint?->id,
                'created_by_user_id' => $owner->id,
                'title' => $title,
                'description' => $description,
                'preconditions' => 'Use the seeded Aptoria Full Demo Workspace. Do not touch live customer systems.',
                'steps' => "1. Open the linked demo module.\n2. Review the existing evidence.\n3. Compare finding, test and release gate status.",
                'expected_result' => 'The user can understand the full Aptoria workflow without changing demo data.',
                'type' => $type,
                'priority' => $priority,
                'status' => 'active',
                'tags' => 'demo,showcase,release-readiness',
                'source' => 'demo_showcase',
                'external_reference' => 'SHOWCASE-'.Str::slug($title),
                'last_run_status' => $status,
                'last_run_at' => now()->subHours(rand(1, 12)),
                'run_count' => 3,
                'pass_count' => $status === 'pass' ? 3 : 1,
                'fail_count' => $status === 'fail' ? 2 : 0,
                'metadata_json' => ['demo_showcase' => true, 'path' => $path],
            ]);

            TestRun::create([
                'project_id' => $project->id,
                'test_suite_id' => $suite->id,
                'test_case_id' => $case->id,
                'endpoint_id' => $endpoint?->id,
                'executed_by_user_id' => $owner->id,
                'finding_id' => $status === 'fail' ? $finding?->id : null,
                'status' => $status,
                'executed_at' => now()->subMinutes(rand(45, 360)),
                'duration_ms' => rand(180, 1420),
                'environment_label' => 'staging-demo',
                'actual_result' => $status === 'pass'
                    ? 'Demo evidence is available and reviewable.'
                    : 'Demo shows a controlled failure so release gate behaviour is visible.',
                'failure_summary' => $status === 'fail' ? 'Synthetic showcase failure for finding and release-gate visibility.' : null,
                'evidence_summary' => 'Seeded native test result linked to the showcase workspace.',
                'metadata_json' => ['demo_showcase' => true],
            ]);
        }
    }

    private function createReleaseGateShowcase(Project $project, User $owner): void
    {
        if (! Schema::hasTable('release_gates') || ! Schema::hasTable('release_gate_items')) {
            return;
        }

        if ($project->releaseGates()->where('title', 'Showcase v1.4 RC Release Gate')->exists()) {
            return;
        }

        $readiness = $project->releaseReadinessRuns()->latest()->first();
        $decision = $project->releaseDecisionSnapshots()->latest()->first();

        $gate = ReleaseGate::create([
            'project_id' => $project->id,
            'release_readiness_run_id' => $readiness?->id,
            'release_decision_snapshot_id' => $decision?->id,
            'created_by_user_id' => $owner->id,
            'title' => 'Showcase v1.4 RC Release Gate',
            'release_version' => 'v1.4.0-rc.2',
            'target_environment' => 'Demo Staging',
            'gate_profile' => 'strict',
            'status' => 'blocked',
            'automated_decision' => 'blocked',
            'final_decision' => 'pending',
            'score' => 72,
            'grade' => 'C',
            'blocker_count' => 2,
            'warning_count' => 4,
            'passed_item_count' => 8,
            'total_item_count' => 14,
            'evidence_count' => $project->evidence()->count(),
            'verified_evidence_count' => $project->evidence()->where('repository_status', 'verified')->count(),
            'test_run_count' => $project->testRuns()->count(),
            'failed_test_run_count' => $project->testRuns()->where('status', 'fail')->count(),
            'open_finding_count' => $project->findings()->whereNotIn('status', ['closed', 'dismissed'])->count(),
            'high_critical_open_count' => $project->findings()->whereIn('severity', ['critical', 'high'])->whereNotIn('status', ['closed', 'dismissed'])->count(),
            'summary_json' => [
                'headline' => 'Showcase gate blocks the release because billing and sensitive-data examples still require review.',
                'demo_showcase' => true,
            ],
            'source_state_json' => ['source' => 'DemoShowcaseWorkspaceService', 'version' => config('aptoria.version')],
            'decision_note' => 'Synthetic gate used to demonstrate blocker, warning, pass and review states.',
            'evaluated_at' => now()->subMinutes(55),
        ]);

        $items = [
            ['critical_findings', 'findings', 'Critical finding remains open', 'bug', 'blocked', 'Critical demo finding must be reviewed before approval.', 1],
            ['failed_native_tests', 'tests', 'Native regression failure present', 'test-tube-diagonal', 'blocked', 'Retest billing and slow-response examples.', 2],
            ['evidence_repository', 'evidence', 'Evidence repository populated', 'archive', 'pass', 'Verified repository evidence is available.', 3],
            ['external_import', 'imports', 'External QA import trace exists', 'brackets-contain', 'pass', 'OpenAPI/Postman/Jira/HAR examples are present.', 4],
            ['contract_validation', 'contract', 'Contract validation has drift warnings', 'file-search', 'warning', 'Review schema drift before release.', 5],
            ['accepted_risk', 'risk', 'Accepted risk is nearing expiry', 'shield-alert', 'warning', 'Renew or close accepted risk before final sign-off.', 6],
            ['client_portal', 'review', 'Client portal feedback received', 'message-square-warning', 'warning', 'Client requested retest evidence.', 7],
            ['report_package', 'readiness', 'Decision report generated', 'file-check-2', 'pass', 'Approved demo report package is available.', 8],
        ];

        foreach ($items as [$key, $category, $label, $icon, $state, $action, $order]) {
            ReleaseGateItem::create([
                'project_id' => $project->id,
                'release_gate_id' => $gate->id,
                'reviewed_by_user_id' => $state === 'warning' ? $owner->id : null,
                'item_key' => $key,
                'category' => $category,
                'label' => $label,
                'icon' => $icon,
                'automated_state' => $state,
                'manual_state' => $state === 'warning' ? 'warning' : null,
                'effective_state' => $state,
                'severity' => $state === 'blocked' ? 'blocker' : $state,
                'evidence_count' => max(1, min(4, $project->evidence()->count())),
                'required_action' => $action,
                'reviewer_note' => $state === 'warning' ? 'Showcase reviewer note: visible for demo walkthrough.' : null,
                'sort_order' => $order,
                'metadata_json' => ['demo_showcase' => true],
                'reviewed_at' => $state === 'warning' ? now()->subMinutes(45) : null,
            ]);
        }

        if (Schema::hasTable('release_gate_events')) {
            foreach ([
                ['evaluated', 'Showcase gate evaluated from seeded evidence.', 'info'],
                ['blocked', 'Two blockers detected in the demo release candidate.', 'danger'],
                ['review_note_added', 'Reviewer noted that client retest evidence is required.', 'warning'],
            ] as [$type, $summary, $severity]) {
                ReleaseGateEvent::create([
                    'project_id' => $project->id,
                    'release_gate_id' => $gate->id,
                    'user_id' => $owner->id,
                    'event_type' => $type,
                    'summary' => $summary,
                    'severity' => $severity,
                    'metadata_json' => ['demo_showcase' => true],
                    'occurred_at' => now()->subMinutes(rand(20, 50)),
                ]);
            }
        }
    }

    private function createEvidencePackShowcase(Project $project, User $owner): void
    {
        if (! Schema::hasTable('evidence_packs')) {
            return;
        }

        if ($project->evidencePacks()->where('title', 'Full Showcase Evidence Pack')->exists()) {
            return;
        }

        $readiness = $project->releaseReadinessRuns()->latest()->first();
        $report = $project->reportVersions()->latest('generated_at')->first();
        $markdown = "# Full Showcase Evidence Pack\n\nThis synthetic package demonstrates readiness, findings, evidence, import traceability, native tests, accepted risk and client handoff in one workspace.";

        EvidencePack::create([
            'project_id' => $project->id,
            'created_by_user_id' => $owner->id,
            'release_readiness_run_id' => $readiness?->id,
            'report_version_id' => $report?->id,
            'title' => 'Full Showcase Evidence Pack',
            'pack_type' => 'release_evidence',
            'status' => 'generated',
            'included_sections_json' => EvidencePack::SECTIONS,
            'manifest_json' => [
                'demo_showcase' => true,
                'findings' => $project->findings()->count(),
                'evidence' => $project->evidence()->count(),
                'test_runs' => $project->testRuns()->count(),
                'reports' => $project->reportVersions()->count(),
            ],
            'content_markdown' => $markdown,
            'content_html' => '<h1>Full Showcase Evidence Pack</h1><p>This synthetic package demonstrates Aptoria evidence-first release QA.</p>',
            'checksum' => hash('sha256', $markdown),
            'generated_at' => now()->subMinutes(18),
        ]);
    }

    private function createDuplicateCandidateShowcase(Project $project): void
    {
        if (! Schema::hasTable('finding_duplicate_candidates')) {
            return;
        }

        if ($project->findingDuplicateCandidates()->exists()) {
            return;
        }

        $findings = $project->findings()->latest()->take(2)->get();
        if ($findings->count() < 2) {
            return;
        }

        FindingDuplicateCandidate::create([
            'project_id' => $project->id,
            'primary_finding_id' => $findings[0]->id,
            'duplicate_finding_id' => $findings[1]->id,
            'score' => 86,
            'status' => 'candidate',
            'signals_json' => [
                'same_endpoint_family' => true,
                'similar_title' => true,
                'shared_evidence_type' => true,
                'demo_showcase' => true,
            ],
            'detected_at' => now()->subMinutes(65),
        ]);
    }

    private function markShowcaseSettings(Project $project): void
    {
        if (! Schema::hasTable('project_settings')) {
            return;
        }

        foreach ([
            'demo.showcase' => 'true',
            'demo.showcase_version' => (string) config('aptoria.version'),
            'scan.allow_private_networks' => 'false',
            'release.profile' => 'strict',
        ] as $key => $value) {
            DB::table('project_settings')->updateOrInsert(
                ['project_id' => $project->id, 'key' => $key],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /** @return array<string,int|string> */
    private function summary(Project $project): array
    {
        return [
            'name' => $project->name,
            'slug' => $project->slug,
            'environments' => $project->environments()->count(),
            'auth_profiles' => $project->authProfiles()->count(),
            'endpoints' => $project->endpoints()->count(),
            'scan_runs' => $project->scanRuns()->count(),
            'quick_tests' => $project->endpointTestRuns()->count(),
            'native_test_cases' => Schema::hasTable('test_cases') ? $project->testCases()->count() : 0,
            'native_test_runs' => Schema::hasTable('test_runs') ? $project->testRuns()->count() : 0,
            'findings' => $project->findings()->count(),
            'evidence' => $project->evidence()->count(),
            'risk_acceptances' => $project->riskAcceptances()->count(),
            'contract_validations' => $project->contractValidationRuns()->count(),
            'external_imports' => Schema::hasTable('external_import_runs') ? $project->externalImportRuns()->count() : 0,
            'readiness_runs' => $project->releaseReadinessRuns()->count(),
            'release_gates' => Schema::hasTable('release_gates') ? $project->releaseGates()->count() : 0,
            'evidence_packs' => Schema::hasTable('evidence_packs') ? $project->evidencePacks()->count() : 0,
            'release_decisions' => $project->releaseDecisionSnapshots()->count(),
            'report_versions' => $project->reportVersions()->count(),
            'client_portal_links' => $project->clientPortalAccesses()->count(),
            'calendar_events' => $project->calendarEvents()->count(),
            'audit_logs' => $project->auditLogs()->count(),
            'duplicate_candidates' => Schema::hasTable('finding_duplicate_candidates') ? $project->findingDuplicateCandidates()->count() : 0,
        ];
    }
}
