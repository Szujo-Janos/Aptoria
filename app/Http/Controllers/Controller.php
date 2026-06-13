<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Access\ProjectAccessService;

abstract class Controller
{
    protected function authorizeProject(Project $project, string $ability, ?string $summary = null): void
    {
        $summary ??= (string) __('messages.project_members.audit.project_action_denied', [
            'ability' => \App\Models\ProjectMembership::translatedPermissionLabel($ability),
        ]);

        app(ProjectAccessService::class)->authorize($project, request()->user(), $ability, $summary);
    }
}
