<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\QaCockpitService;
use Illuminate\View\View;

class QaCockpitController extends Controller
{
    public function __construct(private readonly QaCockpitService $cockpit)
    {
    }

    public function show(Project $project): View
    {
        return view('qa_cockpit.show', [
            'project' => $project,
            'cockpit' => $this->cockpit->snapshot($project),
        ]);
    }
}
