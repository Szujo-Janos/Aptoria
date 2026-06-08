<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\Project;
use App\Services\QaCoverageMatrixService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QaCoverageMatrixController extends Controller
{
    public function index(Project $project, Request $request, QaCoverageMatrixService $coverageMatrix): View
    {
        $matrix = $coverageMatrix->summarize($project, [
            'status' => $request->query('status'),
            'gap' => $request->query('gap'),
            'risk' => $request->query('risk'),
        ]);

        return view('qa_coverage.index', [
            'project' => $project,
            'matrix' => $matrix,
            'summary' => $matrix['summary'],
            'rows' => $matrix['rows'],
            'filters' => $matrix['filters'],
            'risks' => Endpoint::RISKS,
        ]);
    }
}
