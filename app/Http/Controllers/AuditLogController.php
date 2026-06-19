<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Project;
use App\Services\ProjectAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __invoke(Request $request, ProjectAccessService $projectAccess): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'severity' => ['nullable', 'string', 'max:50'],
            'event_type' => ['nullable', 'string', 'max:80'],
            'action' => ['nullable', 'string', 'max:120'],
            'project_id' => ['nullable', 'integer'],
        ]);

        $visibleProjectIds = $projectAccess->visibleProjectsQuery($request->user())->pluck('id');
        $logsQuery = AuditLog::query()->with(['user', 'project'])->latest();

        if (! $request->user()->isAdmin()) {
            $logsQuery->where(function ($query) use ($visibleProjectIds): void {
                $query->whereNull('project_id')->orWhereIn('project_id', $visibleProjectIds);
            });
        }

        if (! empty($filters['q'])) {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
            $logsQuery->where(function ($query) use ($term): void {
                $query->where('summary', 'like', $term)
                    ->orWhere('action', 'like', $term)
                    ->orWhere('event_type', 'like', $term)
                    ->orWhere('subject_label', 'like', $term)
                    ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', $term)->orWhere('name', 'like', $term))
                    ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', $term));
            });
        }

        if (! empty($filters['severity'])) {
            $logsQuery->where('severity', $filters['severity']);
        }

        if (! empty($filters['event_type'])) {
            $logsQuery->where('event_type', $filters['event_type']);
        }

        if (! empty($filters['action'])) {
            $logsQuery->where('action', $filters['action']);
        }

        if (! empty($filters['project_id'])) {
            $logsQuery->where('project_id', $filters['project_id']);
        }

        $baseStatsQuery = AuditLog::query();

        if (! $request->user()->isAdmin()) {
            $baseStatsQuery->where(function ($query) use ($visibleProjectIds): void {
                $query->whereNull('project_id')->orWhereIn('project_id', $visibleProjectIds);
            });
        }

        $stats = [
            'total' => (clone $baseStatsQuery)->count(),
            'attention' => (clone $baseStatsQuery)->whereIn('severity', ['warning', 'error', 'critical'])->count(),
            'today' => (clone $baseStatsQuery)->whereDate('created_at', now()->toDateString())->count(),
            'project_events' => (clone $baseStatsQuery)->whereNotNull('project_id')->count(),
        ];

        $severityOptions = AuditLog::query()
            ->select('severity')
            ->whereNotNull('severity')
            ->distinct()
            ->orderBy('severity')
            ->pluck('severity')
            ->filter()
            ->values();

        $eventTypeOptions = AuditLog::query()
            ->select('event_type')
            ->whereNotNull('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type')
            ->filter()
            ->values();

        $actionOptions = AuditLog::query()
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->filter()
            ->values();

        return view('audit.index', [
            'logs' => $logsQuery->paginate(25)->withQueryString(),
            'projects' => $projectAccess->visibleProjectsQuery($request->user())->orderBy('name')->get(['id', 'name']),
            'severityOptions' => $severityOptions,
            'eventTypeOptions' => $eventTypeOptions,
            'actionOptions' => $actionOptions,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }
}
