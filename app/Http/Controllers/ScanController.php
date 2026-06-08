<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\Environment;
use App\Models\Project;
use App\Models\ScanResult;
use App\Models\ScanRun;
use App\Services\AssertionEvaluationService;
use App\Services\RegressionEvaluationService;
use App\Services\Risk\RiskAnalyzer;
use App\Services\SafeProbeService;
use App\Services\Settings\ProjectSettingService;
use App\Services\Settings\SettingService;
use App\Services\Settings\SettingsRuntimeService;
use App\Services\Snapshots\SnapshotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function index(Project $project, SettingService $settings): View
    {
        $scanRuns = $project->scanRuns()
            ->with(['environment', 'creator'])
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('scans.index', compact('project', 'scanRuns'));
    }

    public function create(Project $project, SettingService $settings, SettingsRuntimeService $runtime, ProjectSettingService $projectSettings): View
    {
        $project->load(['environments', 'endpoints', 'projectSettings']);

        return view('scans.create', [
            'project' => $project,
            'requireConfirmation' => $projectSettings->boolean($project, 'scan.require_confirmation', $settings->boolean('scan.require_confirmation', true)),
            'requireProductionConfirmation' => $settings->boolean('scan.require_production_confirmation', true),
            'defaultEnvironmentId' => $projectSettings->get($project, 'scan.default_environment_id', ''),
            'projectScanSettings' => $projectSettings->grouped($project),
            'scanProfiles' => $runtime->enabledScanProfiles(),
            'defaultScanProfile' => $runtime->defaultScanProfile(),
            'productionConfirmationPhrase' => $settings->string('probe.production_confirmation_phrase', 'SCAN PRODUCTION'),
            'requireTypedProductionConfirmation' => $settings->boolean('probe.require_typed_production_confirmation', true),
        ]);
    }

    public function store(Request $request, Project $project, SafeProbeService $safeProbeService, SettingService $settings, ProjectSettingService $projectSettings, SnapshotService $snapshots, AssertionEvaluationService $assertions): RedirectResponse
    {
        $rules = [
            'environment_id' => ['nullable', 'integer'],
            'scan_profile' => ['nullable', 'string'],
        ];

        if ($projectSettings->boolean($project, 'scan.require_confirmation', $settings->boolean('scan.require_confirmation', true))) {
            $rules['confirm_safe_scan'] = ['accepted'];
        }

        $validated = $request->validate($rules, [
            'confirm_safe_scan.accepted' => __('messages.scans.validation.confirm_safe_scan'),
            'confirm_production_scan.accepted' => __('messages.scans.validation.confirm_production_scan'),
        ]);

        $environment = null;
        if (! empty($validated['environment_id'])) {
            $environment = Environment::query()
                ->where('project_id', $project->id)
                ->findOrFail($validated['environment_id']);
        }

        if ($environment?->is_production && $settings->boolean('scan.require_production_confirmation', true)) {
            $request->validate([
                'confirm_production_scan' => ['accepted'],
                'production_confirmation_phrase' => ['nullable', 'string'],
            ], [
                'confirm_production_scan.accepted' => __('messages.scans.validation.confirm_production_scan'),
            ]);

            if ($settings->boolean('probe.require_typed_production_confirmation', true)) {
                $phrase = $settings->string('probe.production_confirmation_phrase', 'SCAN PRODUCTION');
                if ($phrase !== '' && trim((string) $request->input('production_confirmation_phrase')) !== $phrase) {
                    return back()->withInput()->withErrors(['production_confirmation_phrase' => 'Type '.$phrase.' to confirm production scan.']);
                }
            }
        }

        $scanRun = $safeProbeService->runProject($project, $environment, $request->user(), (string) ($validated['scan_profile'] ?? ''));

        if (($settings->boolean('snapshots.auto_save', false) || $settings->boolean('snapshots.auto_baseline_after_successful_scan', false)) && $scanRun->status === ScanRun::STATUS_COMPLETED && ! $scanRun->snapshot) {
            $scanRun->loadMissing('results.endpoint');
            $canSnapshot = true;

            if ($settings->boolean('snapshots.auto_snapshot_only_if_assertions_pass', true)) {
                foreach ($scanRun->results as $result) {
                    if (! $result->endpoint) {
                        continue;
                    }

                    $evaluation = $assertions->evaluate($result->endpoint, $result);
                    if ($evaluation['status'] === AssertionEvaluationService::STATUS_FAIL) {
                        $canSnapshot = false;
                        break;
                    }
                    if ($evaluation['status'] === AssertionEvaluationService::STATUS_WARNING && ! $settings->boolean('snapshots.auto_snapshot_allow_warnings', true)) {
                        $canSnapshot = false;
                        break;
                    }
                }
            }

            if ($canSnapshot) {
                $snapshots->createFromScanRun($scanRun, $request->user());
            }
        }

        return redirect()
            ->route('projects.scans.show', [$project, $scanRun])
            ->with('success', __('messages.scans.completed'));
    }

    public function show(
        Project $project,
        ScanRun $scanRun,
        RiskAnalyzer $riskAnalyzer,
        AssertionEvaluationService $assertions,
        RegressionEvaluationService $regressions
    ): View
    {
        $this->ensureScanBelongsToProject($project, $scanRun);

        $scanRun->load([
            'project',
            'environment',
            'creator',
            'snapshot',
            'results' => fn ($query) => $query->with(['endpoint', 'authProfile'])->orderBy('status')->orderBy('method')->orderBy('url'),
        ]);

        $resultAnalyses = $scanRun->results
            ->mapWithKeys(fn (ScanResult $result): array => [
                $result->id => $riskAnalyzer->analyze($result->endpoint, $result),
            ])
            ->all();

        $assertionEvaluations = $scanRun->results
            ->filter(fn (ScanResult $result): bool => $result->endpoint !== null)
            ->mapWithKeys(fn (ScanResult $result): array => [
                $result->id => $assertions->evaluate($result->endpoint, $result),
            ])
            ->all();

        $regressionEvaluations = $scanRun->results
            ->filter(fn (ScanResult $result): bool => $result->endpoint !== null)
            ->mapWithKeys(fn (ScanResult $result): array => [
                $result->id => $regressions->latestForEndpoint($result->endpoint),
            ])
            ->all();

        $riskSummary = collect(Endpoint::RISKS)
            ->mapWithKeys(fn (string $level): array => [$level => 0])
            ->all();

        foreach ($resultAnalyses as $analysis) {
            $riskSummary[$analysis['final_level']]++;
        }

        $topRiskyResults = $scanRun->results
            ->sortByDesc(fn (ScanResult $result): int => $resultAnalyses[$result->id]['score'])
            ->take(5);

        return view('scans.show', compact(
            'project',
            'scanRun',
            'resultAnalyses',
            'assertionEvaluations',
            'regressionEvaluations',
            'riskSummary',
            'topRiskyResults'
        ));
    }

    public function probeEndpoint(Request $request, Project $project, Endpoint $endpoint, SafeProbeService $safeProbeService): RedirectResponse
    {
        abort_unless($endpoint->project_id === $project->id, 404);

        $scanRun = $safeProbeService->runEndpoint($project, $endpoint, $request->user());

        return redirect()
            ->route('projects.scans.show', [$project, $scanRun])
            ->with('success', __('messages.scans.single_completed'));
    }

    private function ensureScanBelongsToProject(Project $project, ScanRun $scanRun): void
    {
        abort_unless($scanRun->project_id === $project->id, 404);
    }
}
