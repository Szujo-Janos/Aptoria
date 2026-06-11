<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Contracts\ContractRealityService;
use Illuminate\View\View;

class ContractRealityController extends Controller
{
    public function __invoke(Project $project, ContractRealityService $service): View
    {
        $reality = $service->summarize($project);

        return view('contract_reality.index', compact('project', 'reality'));
    }
}
