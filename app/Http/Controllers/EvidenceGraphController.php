<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\Finding;
use App\Models\Project;
use App\Services\Evidence\EvidenceGraphService;
use Illuminate\Contracts\View\View;

class EvidenceGraphController extends Controller
{
    public function index(Project $project, EvidenceGraphService $graph): View
    {
        $data = $graph->summarize($project);

        return view('evidence_graph.index', [
            'project' => $project,
            ...$data,
        ]);
    }

    public function endpoint(Project $project, Endpoint $endpoint, EvidenceGraphService $graph): View
    {
        abort_unless($endpoint->project_id === $project->id, 404);

        return view('evidence_graph.endpoint', [
            'project' => $project,
            'endpoint' => $endpoint,
            'map' => $graph->endpointMap($endpoint),
        ]);
    }

    public function finding(Project $project, Finding $finding, EvidenceGraphService $graph): View
    {
        abort_unless($finding->project_id === $project->id, 404);

        return view('evidence_graph.finding', [
            'project' => $project,
            'finding' => $finding,
            'chain' => $graph->findingChain($finding),
        ]);
    }

    public function release(Project $project, EvidenceGraphService $graph): View
    {
        $data = $graph->summarize($project);

        return view('evidence_graph.release', [
            'project' => $project,
            'releaseGraph' => $data['release_graph'],
            'summary' => $data['summary'],
            'missingLinks' => $data['missing_links'],
        ]);
    }
}
