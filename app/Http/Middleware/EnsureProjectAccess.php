<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Services\ProjectAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectAccess
{
    public function __construct(private ProjectAccessService $projectAccess)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $project = $request->route('project');

        if (! $project instanceof Project || ! Auth::check()) {
            return $next($request);
        }

        $user = $request->user();
        $permission = $this->projectAccess->permissionForRoute($request->route()?->getName(), $request->method());

        if (! $this->projectAccess->can($user, $project, $permission)) {
            abort(403, __('messages.project_members.access_denied'));
        }

        foreach ($request->route()?->parameters() ?? [] as $key => $parameter) {
            if ($key === 'project' || ! is_object($parameter)) {
                continue;
            }

            if (! $this->projectAccess->relatedModelBelongsToProject($parameter, $project)) {
                abort(404);
            }
        }

        $request->session()->put('current_project_id', $project->id);

        return $next($request);
    }
}
