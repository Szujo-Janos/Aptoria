<?php

namespace App\Http\Controllers;

use App\Models\ContractValidationRun;
use App\Models\Project;
use App\Models\ScanRun;
use App\Services\Contracts\OpenApiContractValidationService;
use App\Services\Settings\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractValidationController extends Controller
{
    public function index(Project $project, SettingService $settings): View
    {
        $runs = $project->contractValidationRuns()
            ->with('scanRun.environment')
            ->latest()
            ->paginate($settings->integer('app.items_per_page', 25));

        return view('contract_validations.index', compact('project', 'runs'));
    }

    public function create(Project $project): View
    {
        $scanRuns = $project->scanRuns()
            ->with('environment')
            ->latest()
            ->limit(20)
            ->get();

        return view('contract_validations.create', compact('project', 'scanRuns'));
    }

    public function store(Request $request, Project $project, OpenApiContractValidationService $validator): RedirectResponse
    {
        $validated = $request->validate([
            'source_name' => ['nullable', 'string', 'max:180'],
            'scan_run_id' => ['nullable', 'integer'],
            'contract_payload' => ['required', 'string', 'max:1000000'],
        ]);

        $scanRun = null;
        if (! empty($validated['scan_run_id'])) {
            $scanRun = ScanRun::query()
                ->where('project_id', $project->id)
                ->findOrFail((int) $validated['scan_run_id']);
        }

        $run = $validator->validate(
            $project,
            $validated['contract_payload'],
            $scanRun,
            $validated['source_name'] ?? null
        );

        $message = $run->status === ContractValidationRun::STATUS_FAILED
            ? __('messages.contract_validations.run_failed')
            : __('messages.contract_validations.completed');

        return redirect()
            ->route('projects.contract-validations.show', [$project, $run])
            ->with('success', $message);
    }

    public function show(Project $project, ContractValidationRun $contractValidation): View
    {
        $this->ensureRunBelongsToProject($project, $contractValidation);

        $contractValidation->load([
            'scanRun.environment',
            'results' => fn ($query) => $query->with(['endpoint', 'scanResult'])->orderBy('status')->orderBy('severity')->orderBy('method')->orderBy('path'),
        ]);

        $summary = [
            'pass' => $contractValidation->results->where('status', 'pass')->count(),
            'warning' => $contractValidation->results->where('status', 'warning')->count(),
            'fail' => $contractValidation->results->where('status', 'fail')->count(),
            'skipped' => $contractValidation->results->where('status', 'skipped')->count(),
        ];

        return view('contract_validations.show', compact('project', 'contractValidation', 'summary'));
    }

    private function ensureRunBelongsToProject(Project $project, ContractValidationRun $run): void
    {
        abort_unless($run->project_id === $project->id, 404);
    }
}
