<?php

namespace App\Http\Controllers;

use App\Services\DemoShowcaseWorkspaceService;
use App\Services\WorkspaceModeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DemoWorkspaceController extends Controller
{
    public function __invoke(Request $request, DemoShowcaseWorkspaceService $showcase): RedirectResponse
    {
        abort_unless((bool) config('aptoria.demo.mode', false), 404);
        abort_unless((string) config('aptoria.demo.viewer_mode', 'readonly') === 'showcase', 404);

        $result = $showcase->ensureForViewer($request->user());
        $request->session()->put('workspace_mode', WorkspaceModeService::SANDBOX);
        $request->session()->put('current_project_id', $result['project']->id);

        return redirect()->route('projects.show', $result['project']);
    }
}
