<?php

namespace App\Http\Controllers;

use App\Services\HostingProfileDiagnosticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class RuntimeDiagnosticsController extends Controller
{
    public function index(HostingProfileDiagnosticsService $diagnostics): View
    {
        return view('runtime_diagnostics.index', [
            'diagnostics' => $diagnostics->run(),
        ]);
    }

    public function json(HostingProfileDiagnosticsService $diagnostics): JsonResponse
    {
        return response()->json($diagnostics->run(), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
