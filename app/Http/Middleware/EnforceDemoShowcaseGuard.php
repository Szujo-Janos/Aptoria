<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Services\DemoShowcaseWorkspaceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceDemoShowcaseGuard
{
    /** @var list<string> */
    private array $allowedWriteRoutes = [
        'logout',
        'login.store',
        'workspace.mode',
    ];

    public function __construct(private readonly DemoShowcaseWorkspaceService $showcase)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isEnabled()) {
            return $next($request);
        }

        $user = $request->user();
        if (! $this->showcase->isDemoViewer($user)) {
            return $next($request);
        }

        if ($this->hasWrongProjectContext($request)) {
            abort(403, __('messages.demo_mode.showcase_project_only'));
        }

        if ($this->isRead($request) || $this->isAllowedWrite($request)) {
            return $next($request);
        }

        abort(403, __('messages.demo_mode.showcase_write_blocked'));
    }

    private function isEnabled(): bool
    {
        return (bool) config('aptoria.demo.mode', false)
            && (string) config('aptoria.demo.viewer_mode', 'readonly') === 'showcase';
    }

    private function isRead(Request $request): bool
    {
        return in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true);
    }

    private function isAllowedWrite(Request $request): bool
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        return in_array($routeName, $this->allowedWriteRoutes, true);
    }

    private function hasWrongProjectContext(Request $request): bool
    {
        $project = $request->route('project');

        if (! $project instanceof Project) {
            return false;
        }

        return $project->slug !== $this->showcase->showcaseSlug();
    }
}
