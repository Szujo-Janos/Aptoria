<?php

namespace App\Http\Controllers;

use App\Models\ContractValidationRun;
use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\OpenApiContractValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContractValidationController extends Controller
{
    public function index(Project $project, OpenApiContractValidationService $service): View
    {
        $runs = $project->contractValidationRuns()
            ->with('validatedBy')
            ->withCount('results')
            ->latest('validated_at')
            ->latest()
            ->limit(25)
            ->get();

        return view('contract_validation.index', [
            'project' => $project,
            'runs' => $runs,
            'latestRun' => $runs->first(),
            'summary' => $service->summary($project),
        ]);
    }

    public function store(Request $request, Project $project, OpenApiContractValidationService $service, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->validate([
            'source_name' => ['nullable', 'string', 'max:180'],
            'source_version' => ['nullable', 'string', 'max:120'],
            'contract_content' => ['required', 'string', 'max:200000'],
            'confirm_validation' => ['accepted'],
        ]);

        $run = $service->validate($project, $data, $request->user());

        $auditLogger->record('contract_validation_completed', __('messages.audit_messages.contract_validation_completed'), $project, [
            'contract_validation_run_id' => $run->id,
            'status' => $run->status,
            'documented_operations' => $run->documented_operations,
            'inventory_operations' => $run->inventory_operations,
            'matched_operations' => $run->matched_operations,
            'undocumented_inventory_operations' => $run->undocumented_inventory_operations,
            'missing_inventory_operations' => $run->missing_inventory_operations,
            'blockers' => $run->blocker_count,
            'warnings' => $run->warning_count,
        ], 'contract', $run->status === 'blocked' ? 'warning' : 'info');

        return redirect()->route('projects.contract-validation.show', [$project, $run])->with('status', __('messages.contract_validation.created'));
    }

    public function show(Project $project, ContractValidationRun $contractValidationRun, OpenApiContractValidationService $service): View
    {
        abort_unless((int) $contractValidationRun->project_id === (int) $project->id, 404);

        $contractValidationRun->load(['validatedBy', 'results.endpoint']);

        return view('contract_validation.show', [
            'project' => $project,
            'run' => $contractValidationRun,
            'results' => $contractValidationRun->results()
                ->with('endpoint')
                ->orderByRaw("CASE severity WHEN 'blocker' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")
                ->orderBy('method')
                ->orderBy('path')
                ->get(),
            'markdownEvidence' => $service->markdownEvidence($contractValidationRun),
        ]);
    }
}
