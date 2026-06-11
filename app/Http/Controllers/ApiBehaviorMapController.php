<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Behavior\ApiBehaviorMapService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ApiBehaviorMapController extends Controller
{
    public function __construct(private readonly ApiBehaviorMapService $behaviorMap)
    {
    }

    public function index(Project $project): View
    {
        $map = $this->behaviorMap->summarize($project);

        return view('api_behavior.index', [
            'project' => $project,
            'summary' => $map['summary'],
            'endpoints' => $map['endpoints'],
            'links' => $map['links'],
            'resourceGroups' => $map['resource_groups'],
            'sequences' => $map['sequences'],
        ]);
    }

    public function refresh(Project $project): RedirectResponse
    {
        $this->behaviorMap->summarize($project, true);

        return redirect()
            ->route('projects.api-behavior.index', $project)
            ->with('status', __('messages.api_behavior.refreshed'));
    }
}
