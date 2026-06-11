<?php

namespace App\Http\Controllers;

use App\Models\Finding;
use App\Models\FindingComment;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FindingCommentController extends Controller
{
    public function store(Request $request, Project $project, Finding $finding): RedirectResponse
    {
        abort_unless($finding->project_id === $project->id, 404);

        $data = $request->validate([
            'type' => ['required', Rule::in(FindingComment::TYPES)],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $finding->comments()->create([
            'project_id' => $project->id,
            'user_id' => $request->user()?->id,
            'type' => $data['type'],
            'body' => $data['body'],
        ]);

        return redirect()
            ->route('projects.findings.show', [$project, $finding])
            ->with('success', __('messages.findings.comment_created'));
    }
}
