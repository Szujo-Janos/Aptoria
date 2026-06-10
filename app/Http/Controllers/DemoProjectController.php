<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Demo\DemoProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DemoProjectController extends Controller
{
    public function index(DemoProjectService $demo): View
    {
        return view('demo.index', [
            'summary' => $demo->summary(),
        ]);
    }

    public function store(DemoProjectService $demo): RedirectResponse
    {
        try {
            $summary = $demo->import();
        } catch (Throwable $exception) {
            return back()->withErrors(['demo' => $exception->getMessage()]);
        }

        $project = $summary['project'] ?? null;

        if ($project instanceof Project) {
            return redirect()
                ->route('projects.show', $project)
                ->with('success', __('messages.demo_project.imported'));
        }

        return back()->with('success', __('messages.demo_project.imported'));
    }

    public function destroy(Request $request, DemoProjectService $demo): RedirectResponse
    {
        if (! $request->boolean('confirm')) {
            return back()->withErrors(['demo' => __('messages.demo_project.remove_confirm_required')]);
        }

        try {
            $demo->remove();
        } catch (Throwable $exception) {
            return back()->withErrors(['demo' => $exception->getMessage()]);
        }

        return redirect()
            ->route('demo-project.index')
            ->with('success', __('messages.demo_project.removed'));
    }
}
