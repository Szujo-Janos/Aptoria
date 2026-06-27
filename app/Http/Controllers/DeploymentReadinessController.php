<?php

namespace App\Http\Controllers;

use App\Services\DeploymentReadinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DeploymentReadinessController extends Controller
{
    public function index(DeploymentReadinessService $readiness): View
    {
        return view('deployment_readiness.index', [
            'readiness' => $readiness->run('runtime'),
        ]);
    }

    public function json(DeploymentReadinessService $readiness): JsonResponse
    {
        return response()->json($readiness->run('runtime'), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
