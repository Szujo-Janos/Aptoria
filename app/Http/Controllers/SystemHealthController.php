<?php

namespace App\Http\Controllers;

use App\Services\System\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class SystemHealthController extends Controller
{
    public function index(SystemHealthService $systemHealth): View
    {
        return view('system.health', [
            'report' => $systemHealth->report(),
        ]);
    }

    public function json(SystemHealthService $systemHealth): JsonResponse
    {
        return response()->json($systemHealth->report());
    }
}
