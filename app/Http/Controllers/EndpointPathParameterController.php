<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\Project;
use App\Services\Endpoints\PathParameterResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EndpointPathParameterController extends Controller
{
    public function update(Request $request, Project $project, Endpoint $endpoint, PathParameterResolver $resolver): RedirectResponse
    {
        abort_unless((int) $endpoint->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'path_parameter_overrides' => ['nullable', 'string', 'max:5000'],
        ]);

        $resolver->updateEndpointOverridesFromText($endpoint, (string) ($validated['path_parameter_overrides'] ?? ''));

        return redirect()
            ->route('projects.endpoints.show', [$project, $endpoint])
            ->with('success', __('messages.path_parameters.endpoint_saved'));
    }
}
