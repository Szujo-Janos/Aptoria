<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\DemoScenarioTemplateService;
use App\Services\LiveDemoApiSandboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DemoGuideController extends Controller
{
    public function public(Request $request, LiveDemoApiSandboxService $sandbox, DemoScenarioTemplateService $scenarioTemplates): View|RedirectResponse
    {
        if ($this->shouldRedirectToPublicLanding($request)) {
            return $this->redirectToPublicGuide();
        }

        return view('demo_guide.show', $this->payload($request, $sandbox, $scenarioTemplates, null, true));
    }

    public function show(Request $request, Project $project, LiveDemoApiSandboxService $sandbox, DemoScenarioTemplateService $scenarioTemplates): RedirectResponse
    {
        return $this->redirectToPublicGuide();
    }

    private function shouldRedirectToPublicLanding(Request $request): bool
    {
        $host = strtolower($request->getHost());
        $landingHost = strtolower(parse_url((string) config('aptoria.domain.landing_url', 'https://aptoria.dev'), PHP_URL_HOST) ?: 'aptoria.dev');

        if ($host !== '' && $host !== $landingHost) {
            return true;
        }

        return config('aptoria.domain.role') === 'demo';
    }

    private function redirectToPublicGuide(): RedirectResponse
    {
        $landingUrl = rtrim((string) config('aptoria.domain.landing_url', 'https://aptoria.dev'), '/');

        if ($landingUrl === '') {
            $landingUrl = 'https://aptoria.dev';
        }

        return redirect()->away($landingUrl.'/demo-guide', 302);
    }

    /** @return array<string,mixed> */
    private function payload(Request $request, LiveDemoApiSandboxService $sandbox, DemoScenarioTemplateService $scenarioTemplates, ?Project $project, bool $public): array
    {
        $demoUrl = rtrim((string) config('aptoria.domain.demo_url', 'https://demo.aptoria.dev'), '/');
        $baseUrl = $public ? $demoUrl.'/demo-api' : rtrim((string) config('aptoria.demo.api_base_url'), '/');
        $endpoints = [
            ['method' => 'GET', 'path' => '/health', 'tone' => 'success', 'purpose' => __('messages.demo_guide.endpoint_health')],
            ['method' => 'GET', 'path' => '/users', 'tone' => 'info', 'purpose' => __('messages.demo_guide.endpoint_json')],
            ['method' => 'GET', 'path' => '/security/private-account', 'tone' => 'warning', 'purpose' => __('messages.demo_guide.endpoint_auth')],
            ['method' => 'GET', 'path' => '/security/leaky-token-example', 'tone' => 'danger', 'purpose' => __('messages.demo_guide.endpoint_sensitive')],
            ['method' => 'GET', 'path' => '/errors/server-error', 'tone' => 'danger', 'purpose' => __('messages.demo_guide.endpoint_error')],
            ['method' => 'GET', 'path' => '/errors/slow-response', 'tone' => 'warning', 'purpose' => __('messages.demo_guide.endpoint_slow')],
            ['method' => 'GET', 'path' => '/scenarios', 'tone' => 'info', 'purpose' => __('messages.demo_guide.endpoint_scenarios')],
        ];

        $artifactUrl = fn (string $path): string => $public ? $baseUrl.$path : url('/demo-api'.$path);
        $artifacts = [
            ['label' => 'OpenAPI JSON', 'icon' => 'brackets-contain', 'url' => $artifactUrl('/artifacts/openapi.json')],
            ['label' => 'Postman Collection', 'icon' => 'file-code-2', 'url' => $artifactUrl('/artifacts/postman-collection.json')],
            ['label' => 'QA CSV', 'icon' => 'table-export', 'url' => $artifactUrl('/artifacts/qa-results.csv')],
            ['label' => 'Jira CSV', 'icon' => 'clipboard-search', 'url' => $artifactUrl('/artifacts/jira-issues.csv')],
            ['label' => 'HAR', 'icon' => 'scan-eye', 'url' => $artifactUrl('/artifacts/browser-network.har')],
            ['label' => 'Scenario Templates JSON', 'icon' => 'map', 'url' => $artifactUrl('/artifacts/scenario-templates.json')],
        ];

        $scenarios = $scenarioTemplates->all();
        $requestedScenario = (string) $request->query('scenario', $scenarios[0]['slug'] ?? '');
        $selectedScenario = $scenarioTemplates->find($requestedScenario) ?? ($scenarios[0] ?? null);
        $scenarioActions = $project ? $this->scenarioActions($project) : [];

        $workflow = [
            ['icon' => 'radar', 'title' => __('messages.demo_guide.step_scan'), 'copy' => __('messages.demo_guide.step_scan_copy'), 'route' => $project ? route('projects.safe-scans.index', $project) : null],
            ['icon' => 'brackets-contain', 'title' => __('messages.demo_guide.step_import'), 'copy' => __('messages.demo_guide.step_import_copy'), 'route' => $project ? route('projects.import-center.index', $project) : null],
            ['icon' => 'folder-check', 'title' => __('messages.demo_guide.step_evidence'), 'copy' => __('messages.demo_guide.step_evidence_copy'), 'route' => $project ? route('projects.evidence.index', $project) : null],
            ['icon' => 'scan-search', 'title' => __('messages.demo_guide.step_cockpit'), 'copy' => __('messages.demo_guide.step_cockpit_copy'), 'route' => $project ? route('projects.qa-cockpit.show', $project) : null],
            ['icon' => 'workflow', 'title' => __('messages.demo_guide.step_gate'), 'copy' => __('messages.demo_guide.step_gate_copy'), 'route' => $project ? route('projects.release-gates.index', $project) : null],
        ];

        return [
            'layout' => $public ? 'layouts.auth' : 'layouts.app',
            'publicMode' => $public,
            'project' => $project,
            'baseUrl' => $baseUrl,
            'demoUrl' => $demoUrl,
            'landingUrl' => rtrim((string) config('aptoria.domain.landing_url', 'https://aptoria.dev'), '/'),
            'demoUserEmail' => $sandbox->demoUserEmail(),
            'demoUserPassword' => $sandbox->demoUserPassword(),
            'demoModeEnabled' => (bool) config('aptoria.demo.mode', false),
            'allowedTargets' => (array) config('aptoria.demo.allowed_targets', []),
            'endpoints' => $endpoints,
            'artifacts' => $artifacts,
            'scenarios' => $scenarios,
            'selectedScenario' => $selectedScenario,
            'scenarioActions' => $scenarioActions,
            'workflow' => $workflow,
        ];
    }

    /** @return array<string,string> */
    private function scenarioActions(Project $project): array
    {
        return [
            'safe_scan' => route('projects.safe-scans.index', $project),
            'endpoints' => route('projects.endpoints.index', $project),
            'auth_profiles' => route('projects.auth-profiles.index', $project),
            'findings' => route('projects.findings.index', $project),
            'evidence' => route('projects.evidence.index', $project),
            'evidence_packs' => route('projects.evidence-packs.index', $project),
            'import_center' => route('projects.import-center.index', $project),
            'qa_cockpit' => route('projects.qa-cockpit.show', $project),
            'release_readiness' => route('projects.release-readiness.index', $project),
            'release_gates' => route('projects.release-gates.index', $project),
            'reports' => route('projects.reports.index', $project),
            'artifacts' => route('demo-api.artifacts.scenarios'),
        ];
    }
}
