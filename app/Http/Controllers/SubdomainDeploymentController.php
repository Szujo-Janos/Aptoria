<?php

namespace App\Http\Controllers;

use App\Services\SubdomainSmokeResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class SubdomainDeploymentController extends Controller
{
    public function index(SubdomainSmokeResultService $smokeResults): View
    {
        return view('subdomain_deployment.index', [
            'dashboard' => $smokeResults->dashboard(),
            'expectedMatrix' => $smokeResults->expectedMatrix(),
        ]);
    }

    public function json(SubdomainSmokeResultService $smokeResults): JsonResponse
    {
        return response()->json($smokeResults->dashboard(), 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function result(string $id, SubdomainSmokeResultService $smokeResults): JsonResponse
    {
        $result = $smokeResults->find($id);
        abort_unless($result, 404);

        return response()->json($result, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function import(Request $request, SubdomainSmokeResultService $smokeResults): RedirectResponse
    {
        $request->validate([
            'smoke_result_file' => ['nullable', 'file', 'max:512', 'mimetypes:application/json,text/plain,text/json,application/octet-stream'],
            'smoke_result_json' => ['nullable', 'string', 'max:524288'],
        ]);

        try {
            if ($request->hasFile('smoke_result_file')) {
                $result = $smokeResults->importFromUploadedFile($request->file('smoke_result_file'), 'ui-upload');
            } else {
                $result = $smokeResults->importFromString((string) $request->input('smoke_result_json', ''), 'ui-paste', 'pasted-json');
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('subdomain-deployment.index')
            ->with('success', 'Subdomain smoke result imported: '.$result['summary']['passed'].' passed, '.$result['summary']['failed'].' failed.');
    }
}
