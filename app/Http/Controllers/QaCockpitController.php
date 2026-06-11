<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Cockpit\QaCockpitService;
use Illuminate\Contracts\View\View;

class QaCockpitController extends Controller
{
    public function __invoke(Project $project, QaCockpitService $cockpit): View
    {
        return view('qa_cockpit.index', [
            'project' => $project,
            ...$cockpit->summarize($project),
        ]);
    }
}
