<?php

namespace App\Services;

class DemoScenarioTemplateService
{
    /** @return array<int,array<string,mixed>> */
    public function all(): array
    {
        return [
            $this->scenario(
                'first-smoke-scan',
                'radar',
                'success',
                __('messages.demo_guide.scenarios.first_smoke_scan.title'),
                __('messages.demo_guide.scenarios.first_smoke_scan.badge'),
                __('messages.demo_guide.scenarios.first_smoke_scan.summary'),
                __('messages.demo_guide.scenarios.first_smoke_scan.objective'),
                __('messages.demo_guide.scenarios.first_smoke_scan.expected_result'),
                __('messages.demo_guide.scenarios.first_smoke_scan.duration'),
                ['/health', '/users', '/reports/summary'],
                ['OpenAPI JSON', 'Postman Collection'],
                [
                    ['icon' => 'globe', 'title' => __('messages.demo_guide.scenarios.first_smoke_scan.steps.1.title'), 'copy' => __('messages.demo_guide.scenarios.first_smoke_scan.steps.1.copy'), 'action' => 'safe_scan'],
                    ['icon' => 'activity', 'title' => __('messages.demo_guide.scenarios.first_smoke_scan.steps.2.title'), 'copy' => __('messages.demo_guide.scenarios.first_smoke_scan.steps.2.copy'), 'action' => 'endpoints'],
                    ['icon' => 'folder-check', 'title' => __('messages.demo_guide.scenarios.first_smoke_scan.steps.3.title'), 'copy' => __('messages.demo_guide.scenarios.first_smoke_scan.steps.3.copy'), 'action' => 'evidence'],
                    ['icon' => 'scan-search', 'title' => __('messages.demo_guide.scenarios.first_smoke_scan.steps.4.title'), 'copy' => __('messages.demo_guide.scenarios.first_smoke_scan.steps.4.copy'), 'action' => 'qa_cockpit'],
                ]
            ),
            $this->scenario(
                'security-leak-review',
                'shield-alert',
                'danger',
                __('messages.demo_guide.scenarios.security_leak_review.title'),
                __('messages.demo_guide.scenarios.security_leak_review.badge'),
                __('messages.demo_guide.scenarios.security_leak_review.summary'),
                __('messages.demo_guide.scenarios.security_leak_review.objective'),
                __('messages.demo_guide.scenarios.security_leak_review.expected_result'),
                __('messages.demo_guide.scenarios.security_leak_review.duration'),
                ['/security/public-profile', '/security/private-account', '/security/leaky-token-example'],
                ['QA CSV', 'Jira CSV'],
                [
                    ['icon' => 'key-round', 'title' => __('messages.demo_guide.scenarios.security_leak_review.steps.1.title'), 'copy' => __('messages.demo_guide.scenarios.security_leak_review.steps.1.copy'), 'action' => 'auth_profiles'],
                    ['icon' => 'bug', 'title' => __('messages.demo_guide.scenarios.security_leak_review.steps.2.title'), 'copy' => __('messages.demo_guide.scenarios.security_leak_review.steps.2.copy'), 'action' => 'findings'],
                    ['icon' => 'folder-check', 'title' => __('messages.demo_guide.scenarios.security_leak_review.steps.3.title'), 'copy' => __('messages.demo_guide.scenarios.security_leak_review.steps.3.copy'), 'action' => 'evidence'],
                    ['icon' => 'shield-chevron', 'title' => __('messages.demo_guide.scenarios.security_leak_review.steps.4.title'), 'copy' => __('messages.demo_guide.scenarios.security_leak_review.steps.4.copy'), 'action' => 'release_readiness'],
                ]
            ),
            $this->scenario(
                'artifact-import-trace',
                'brackets-contain',
                'info',
                __('messages.demo_guide.scenarios.artifact_import_trace.title'),
                __('messages.demo_guide.scenarios.artifact_import_trace.badge'),
                __('messages.demo_guide.scenarios.artifact_import_trace.summary'),
                __('messages.demo_guide.scenarios.artifact_import_trace.objective'),
                __('messages.demo_guide.scenarios.artifact_import_trace.expected_result'),
                __('messages.demo_guide.scenarios.artifact_import_trace.duration'),
                ['/artifacts/openapi.json', '/artifacts/postman-collection.json', '/artifacts/qa-results.csv', '/artifacts/browser-network.har'],
                ['OpenAPI JSON', 'Postman Collection', 'QA CSV', 'HAR'],
                [
                    ['icon' => 'download', 'title' => __('messages.demo_guide.scenarios.artifact_import_trace.steps.1.title'), 'copy' => __('messages.demo_guide.scenarios.artifact_import_trace.steps.1.copy'), 'action' => 'artifacts'],
                    ['icon' => 'table-export', 'title' => __('messages.demo_guide.scenarios.artifact_import_trace.steps.2.title'), 'copy' => __('messages.demo_guide.scenarios.artifact_import_trace.steps.2.copy'), 'action' => 'import_center'],
                    ['icon' => 'link', 'title' => __('messages.demo_guide.scenarios.artifact_import_trace.steps.3.title'), 'copy' => __('messages.demo_guide.scenarios.artifact_import_trace.steps.3.copy'), 'action' => 'evidence'],
                    ['icon' => 'archive', 'title' => __('messages.demo_guide.scenarios.artifact_import_trace.steps.4.title'), 'copy' => __('messages.demo_guide.scenarios.artifact_import_trace.steps.4.copy'), 'action' => 'evidence_packs'],
                ]
            ),
            $this->scenario(
                'release-gate-decision',
                'workflow',
                'warning',
                __('messages.demo_guide.scenarios.release_gate_decision.title'),
                __('messages.demo_guide.scenarios.release_gate_decision.badge'),
                __('messages.demo_guide.scenarios.release_gate_decision.summary'),
                __('messages.demo_guide.scenarios.release_gate_decision.objective'),
                __('messages.demo_guide.scenarios.release_gate_decision.expected_result'),
                __('messages.demo_guide.scenarios.release_gate_decision.duration'),
                ['/reports/summary', '/errors/server-error', '/errors/slow-response'],
                ['QA CSV', 'Jira CSV', 'Scenario Templates JSON'],
                [
                    ['icon' => 'scan-search', 'title' => __('messages.demo_guide.scenarios.release_gate_decision.steps.1.title'), 'copy' => __('messages.demo_guide.scenarios.release_gate_decision.steps.1.copy'), 'action' => 'qa_cockpit'],
                    ['icon' => 'shield-chevron', 'title' => __('messages.demo_guide.scenarios.release_gate_decision.steps.2.title'), 'copy' => __('messages.demo_guide.scenarios.release_gate_decision.steps.2.copy'), 'action' => 'release_readiness'],
                    ['icon' => 'workflow', 'title' => __('messages.demo_guide.scenarios.release_gate_decision.steps.3.title'), 'copy' => __('messages.demo_guide.scenarios.release_gate_decision.steps.3.copy'), 'action' => 'release_gates'],
                    ['icon' => 'report-analytics', 'title' => __('messages.demo_guide.scenarios.release_gate_decision.steps.4.title'), 'copy' => __('messages.demo_guide.scenarios.release_gate_decision.steps.4.copy'), 'action' => 'reports'],
                ]
            ),
        ];
    }

    public function find(string $slug): ?array
    {
        foreach ($this->all() as $scenario) {
            if ($scenario['slug'] === $slug) {
                return $scenario;
            }
        }

        return null;
    }

    /** @return array<string,mixed> */
    public function evidencePayload(string $slug): ?array
    {
        $scenario = $this->find($slug);

        if (! $scenario) {
            return null;
        }

        return [
            'scenario' => [
                'slug' => $scenario['slug'],
                'title' => $scenario['title'],
                'objective' => $scenario['objective'],
                'expected_result' => $scenario['expected_result'],
            ],
            'evidence_type' => 'demo_scenario_run_sheet',
            'source' => 'aptoria-live-demo-scenario-template',
            'recommended_endpoints' => $scenario['endpoints'],
            'recommended_artifacts' => $scenario['artifacts'],
            'review_steps' => array_map(fn (array $step): array => [
                'title' => $step['title'],
                'review_note' => $step['copy'],
            ], $scenario['steps']),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param array<int,string> $endpoints
     * @param array<int,string> $artifacts
     * @param array<int,array<string,string>> $steps
     * @return array<string,mixed>
     */
    private function scenario(string $slug, string $icon, string $tone, string $title, string $badge, string $summary, string $objective, string $expectedResult, string $duration, array $endpoints, array $artifacts, array $steps): array
    {
        return [
            'slug' => $slug,
            'icon' => $icon,
            'tone' => $tone,
            'title' => $title,
            'badge' => $badge,
            'summary' => $summary,
            'objective' => $objective,
            'expected_result' => $expectedResult,
            'duration' => $duration,
            'endpoints' => $endpoints,
            'artifacts' => $artifacts,
            'steps' => $steps,
        ];
    }
}
