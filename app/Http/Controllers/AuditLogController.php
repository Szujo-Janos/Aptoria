<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Settings\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request, SettingService $settings, AuditLogService $audit): View
    {
        $query = $this->filteredQuery($request)
            ->with(['project', 'user'])
            ->latest('occurred_at');

        $logs = $query->paginate($settings->integer('app.items_per_page', 25))->withQueryString();

        return view('audit_logs.index', [
            'logs' => $logs,
            'summary' => $audit->summary(),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'filters' => $request->only(['project_id', 'user_id', 'event_type', 'action', 'severity', 'q', 'date_from', 'date_to']),
            'project' => null,
        ]);
    }

    public function project(Request $request, Project $project, SettingService $settings, AuditLogService $audit): View
    {
        $query = $this->filteredQuery($request)
            ->where('project_id', $project->id)
            ->with(['project', 'user'])
            ->latest('occurred_at');

        $logs = $query->paginate($settings->integer('app.items_per_page', 25))->withQueryString();

        return view('audit_logs.index', [
            'logs' => $logs,
            'summary' => $audit->summary($project),
            'projects' => collect([$project]),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'filters' => $request->only(['user_id', 'event_type', 'action', 'severity', 'q', 'date_from', 'date_to']),
            'project' => $project,
        ]);
    }

    public function json(Request $request, ?Project $project = null): JsonResponse
    {
        $query = $this->filteredQuery($request);
        if ($project) {
            $query->where('project_id', $project->id);
        }

        $logs = $query
            ->with(['project', 'user'])
            ->latest('occurred_at')
            ->limit(500)
            ->get()
            ->map(fn (AuditLog $log): array => $this->serialize($log))
            ->values();

        return response()->json([
            'version' => config('aptoria.version'),
            'generated_at' => now()->toIso8601String(),
            'count' => $logs->count(),
            'filters' => $request->query(),
            'audit_logs' => $logs,
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = AuditLog::query();

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->integer('project_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        foreach (['event_type', 'action', 'severity'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, (string) $request->query($field));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('occurred_at', '>=', (string) $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('occurred_at', '<=', (string) $request->query('date_to'));
        }

        if ($request->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $request->query('q')).'%';
            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('summary', 'like', $term)
                    ->orWhere('subject_name', 'like', $term)
                    ->orWhere('subject_label', 'like', $term)
                    ->orWhere('route_name', 'like', $term)
                    ->orWhere('url', 'like', $term);
            });
        }

        return $query;
    }

    private function serialize(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'occurred_at' => $log->occurred_at?->toIso8601String(),
            'event_type' => $log->event_type,
            'action' => $log->action,
            'severity' => $log->severity,
            'summary' => $log->summary,
            'project' => $log->project ? ['id' => $log->project->id, 'name' => $log->project->name] : null,
            'user' => $log->user ? ['id' => $log->user->id, 'name' => $log->user->name, 'email' => $log->user->email] : null,
            'subject' => [
                'type' => $log->auditable_type,
                'id' => $log->auditable_id,
                'label' => $log->subject_label,
                'name' => $log->subject_name,
            ],
            'request' => [
                'route' => $log->route_name,
                'method' => $log->http_method,
                'url' => $log->url,
                'ip' => $log->ip_address,
            ],
            'before_values' => $log->before_values,
            'after_values' => $log->after_values,
            'metadata' => $log->metadata,
        ];
    }
}
