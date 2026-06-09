<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Endpoints\EndpointInventoryService;
use App\Services\Settings\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class EndpointInventoryController extends Controller
{
    public function __invoke(Request $request, Project $project, EndpointInventoryService $inventory, SettingService $settings): View
    {
        $data = $inventory->index(
            $project,
            $request->only(['q', 'method', 'risk', 'environment', 'auth', 'scan', 'findings', 'coverage', 'source', 'status', 'sort']),
            $settings->integer('app.items_per_page', 25)
        );

        return view('endpoints.inventory', [
            'project' => $project,
            'summary' => $data['summary'],
            'endpoints' => $data['endpoints'],
            'filters' => $data['filters'],
            'filterOptions' => $data['filter_options'],
        ]);
    }
}
