<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CalendarEvent;
use App\Models\Project;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request, Project $project): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', 'in:'.implode(',', CalendarEvent::STATUSES)],
            'event_type' => ['nullable', 'in:'.implode(',', CalendarEvent::TYPES)],
            'priority' => ['nullable', 'in:'.implode(',', CalendarEvent::PRIORITIES)],
        ]);

        $eventsQuery = $project->calendarEvents()->with('createdBy')->orderByRaw('start_at is null, start_at asc')->latest('id');

        if (! empty($filters['q'])) {
            $term = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
            $eventsQuery->where(function ($query) use ($term): void {
                $query->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('location', 'like', $term);
            });
        }

        if (! empty($filters['status'])) {
            $eventsQuery->where('status', $filters['status']);
        }

        if (! empty($filters['event_type'])) {
            $eventsQuery->where('event_type', $filters['event_type']);
        }

        if (! empty($filters['priority'])) {
            $eventsQuery->where('priority', $filters['priority']);
        }

        $events = $eventsQuery->paginate(15)->withQueryString();
        $upcoming = $project->calendarEvents()->whereNotIn('status', ['completed', 'cancelled'])->whereNotNull('start_at')->where('start_at', '>=', now()->subDay())->orderBy('start_at')->limit(6)->get();

        return view('calendar.index', [
            'project' => $project,
            'events' => $events,
            'upcoming' => $upcoming,
            'filters' => $filters,
            'metrics' => [
                'total' => $project->calendarEvents()->count(),
                'planned' => $project->calendarEvents()->where('status', 'planned')->count(),
                'in_progress' => $project->calendarEvents()->where('status', 'in_progress')->count(),
                'critical' => $project->calendarEvents()->where('priority', 'critical')->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'logs' => $project->auditLogs()->count(),
            ],
        ]);
    }

    public function events(Request $request, Project $project): JsonResponse
    {
        $start = $request->date('start')?->startOfDay() ?? now()->startOfMonth()->startOfWeek();
        $end = $request->date('end')?->endOfDay() ?? now()->endOfMonth()->endOfWeek();

        $calendarEvents = $project->calendarEvents()
            ->with('createdBy')
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end])
                    ->orWhere(function ($nested) use ($start, $end): void {
                        $nested->where('start_at', '<=', $start)->where('end_at', '>=', $end);
                    });
            })
            ->get()
            ->map(fn (CalendarEvent $event): array => $this->calendarEventPayload($event))
            ->values();

        $auditEvents = $project->auditLogs()
            ->with('user')
            ->whereBetween('created_at', [$start, $end])
            ->latest('created_at')
            ->limit(250)
            ->get()
            ->map(fn (AuditLog $log): array => $this->auditLogPayload($log))
            ->values();

        return response()->json($calendarEvents->merge($auditEvents)->values());
    }

    public function day(Request $request, Project $project): JsonResponse
    {
        $date = Carbon::parse((string) $request->query('date', now()->toDateString()));
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $events = $project->calendarEvents()
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end])
                    ->orWhere(function ($nested) use ($start, $end): void {
                        $nested->where('start_at', '<=', $start)->where(function ($endQuery) use ($end): void {
                            $endQuery->whereNull('end_at')->orWhere('end_at', '>=', $end);
                        });
                    });
            })
            ->orderByRaw('start_at is null, start_at asc')
            ->get()
            ->map(fn (CalendarEvent $event): array => [
                'id' => 'event-'.$event->id,
                'title' => e($event->title),
                'time' => $event->start_at?->format($event->is_all_day ? 'Y-m-d' : 'H:i') ?? '—',
                'meta' => e($event->type_label.' · '.$event->status_label.' · '.$event->priority_label),
                'summary' => e((string) ($event->description ?: $event->location ?: '')),
                'tone' => $event->type_tone,
                'icon' => 'calendar-stats',
                'locked' => false,
            ]);

        $logs = $project->auditLogs()
            ->with('user')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at')
            ->limit(150)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'id' => 'log-'.$log->id,
                'title' => e(__('messages.calendar.system_log')),
                'time' => $log->created_at?->format('H:i') ?? '—',
                'meta' => e($this->auditActionLabel($log).' · '.$this->auditSeverityLabel($log)),
                'summary' => e((string) $log->summary),
                'tone' => $this->auditTone($log),
                'icon' => 'shield-chevron',
                'locked' => true,
            ]);

        return response()->json([
            'date' => $date->toDateString(),
            'label' => $date->translatedFormat('Y. F j.'),
            'items' => $events->merge($logs)->values(),
        ]);
    }

    public function store(Request $request, Project $project, AuditLogger $auditLogger): RedirectResponse
    {
        $event = $project->calendarEvents()->create($this->validated($request) + [
            'created_by_user_id' => Auth::id(),
            'is_all_day' => $request->boolean('is_all_day'),
            'metadata' => ['source' => 'manual'],
        ]);

        $auditLogger->record('created', __('messages.audit_messages.calendar_event_created'), $project, [
            'calendar_event_id' => $event->id,
            'title' => $event->title,
            'event_type' => $event->event_type,
        ], 'calendar');

        return redirect()->route('projects.calendar.index', $project)->with('status', __('messages.calendar.created'));
    }

    public function update(Request $request, Project $project, CalendarEvent $calendarEvent, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureEditable($project, $calendarEvent);
        $calendarEvent->update($this->validated($request) + ['is_all_day' => $request->boolean('is_all_day')]);

        $auditLogger->record('updated', __('messages.audit_messages.calendar_event_updated'), $project, [
            'calendar_event_id' => $calendarEvent->id,
            'title' => $calendarEvent->title,
            'status' => $calendarEvent->status,
        ], 'calendar');

        return redirect()->route('projects.calendar.index', $project)->with('status', __('messages.calendar.updated'));
    }

    public function move(Request $request, Project $project, CalendarEvent $calendarEvent, AuditLogger $auditLogger): JsonResponse
    {
        $this->ensureEditable($project, $calendarEvent);

        $data = $request->validate([
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'is_all_day' => ['nullable', 'boolean'],
        ]);

        $calendarEvent->update([
            'start_at' => Carbon::parse($data['start_at']),
            'end_at' => ! empty($data['end_at']) ? Carbon::parse($data['end_at']) : null,
            'is_all_day' => (bool) ($data['is_all_day'] ?? false),
        ]);

        $auditLogger->record('updated', __('messages.audit_messages.calendar_event_updated'), $project, [
            'calendar_event_id' => $calendarEvent->id,
            'title' => $calendarEvent->title,
            'moved_from_calendar' => true,
        ], 'calendar');

        return response()->json(['ok' => true, 'event' => $this->calendarEventPayload($calendarEvent->fresh())]);
    }

    public function complete(Project $project, CalendarEvent $calendarEvent, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureEditable($project, $calendarEvent);
        $calendarEvent->update(['status' => 'completed']);

        $auditLogger->record('completed', __('messages.audit_messages.calendar_event_completed'), $project, [
            'calendar_event_id' => $calendarEvent->id,
            'title' => $calendarEvent->title,
        ], 'calendar');

        return redirect()->route('projects.calendar.index', $project)->with('status', __('messages.calendar.completed'));
    }

    public function destroy(Project $project, CalendarEvent $calendarEvent, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureEditable($project, $calendarEvent);
        $title = $calendarEvent->title;
        $calendarEvent->delete();

        $auditLogger->record('deleted', __('messages.audit_messages.calendar_event_deleted'), $project, [
            'title' => $title,
        ], 'calendar', 'warning');

        return redirect()->route('projects.calendar.index', $project)->with('status', __('messages.calendar.deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'event_type' => ['required', 'in:'.implode(',', CalendarEvent::TYPES)],
            'status' => ['required', 'in:'.implode(',', CalendarEvent::STATUSES)],
            'priority' => ['required', 'in:'.implode(',', CalendarEvent::PRIORITIES)],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'location' => ['nullable', 'string', 'max:180'],
        ]);
    }

    private function ensureEditable(Project $project, CalendarEvent $calendarEvent): void
    {
        abort_unless((int) $calendarEvent->project_id === (int) $project->id, 404);
        abort_if(($calendarEvent->metadata['source'] ?? null) === 'system_log' || ($calendarEvent->metadata['locked'] ?? false), 403, __('messages.calendar.system_locked'));
    }

    private function calendarEventPayload(CalendarEvent $event): array
    {
        return [
            'id' => 'event-'.$event->id,
            'title' => $event->title,
            'start' => $event->start_at?->toIso8601String(),
            'end' => $event->end_at?->toIso8601String(),
            'allDay' => (bool) $event->is_all_day,
            'classNames' => $this->calendarClassNames($event),
            'editable' => true,
            'durationEditable' => true,
            'startEditable' => true,
            'extendedProps' => [
                'numericId' => $event->id,
                'source' => 'manual',
                'event_type' => $event->event_type,
                'type_label' => $event->type_label,
                'status' => $event->status,
                'status_label' => $event->status_label,
                'priority' => $event->priority,
                'priority_label' => $event->priority_label,
                'description' => $event->description,
                'location' => $event->location,
                'tone' => $event->type_tone,
                'locked' => false,
            ],
        ];
    }

    private function auditLogPayload(AuditLog $log): array
    {
        return [
            'id' => 'log-'.$log->id,
            'title' => __('messages.calendar.system_log_short').': '.$this->auditActionLabel($log),
            'start' => $log->created_at?->toIso8601String(),
            'allDay' => false,
            'classNames' => ['aptoria-system-log-event', 'bg-'.$this->auditTone($log).'-subtle', 'text-'.$this->auditTone($log), 'border-start', 'border-3', 'border-'.$this->auditTone($log)],
            'editable' => false,
            'durationEditable' => false,
            'startEditable' => false,
            'extendedProps' => [
                'numericId' => $log->id,
                'source' => 'system_log',
                'locked' => true,
                'summary' => $log->summary,
                'severity' => $log->severity,
                'severity_label' => $this->auditSeverityLabel($log),
                'action' => $log->action,
                'action_label' => $this->auditActionLabel($log),
                'event_type' => $log->event_type,
                'user' => $log->user?->name,
                'created_at' => $log->created_at?->format('Y-m-d H:i'),
                'tone' => $this->auditTone($log),
            ],
        ];
    }

    private function calendarClassNames(CalendarEvent $event): array
    {
        $tone = match ($event->event_type) {
            'regression_retest' => 'warning',
            'release_checkpoint' => 'primary',
            'maintenance_window' => 'secondary',
            'alert_follow_up' => 'danger',
            'security_review' => 'info',
            'monitor_run' => 'success',
            'activity_log' => 'dark',
            default => 'success',
        };

        return ['aptoria-calendar-event', 'bg-'.$tone.'-subtle', 'text-'.$tone, 'border-start', 'border-3', 'border-'.$tone];
    }

    private function auditTone(AuditLog $log): string
    {
        return match ($log->severity) {
            'critical', 'error' => 'danger',
            'warning' => 'warning',
            'success' => 'success',
            default => 'secondary',
        };
    }

    private function auditSeverityLabel(AuditLog $log): string
    {
        return __('messages.audit.severities.'.($log->severity ?: 'info'));
    }

    private function auditActionLabel(AuditLog $log): string
    {
        return str($log->action ?: 'system')->replace('_', ' ')->headline()->toString();
    }
}
