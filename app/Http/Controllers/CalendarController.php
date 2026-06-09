<?php

namespace App\Http\Controllers;

use App\Models\ApiMonitor;
use App\Models\CalendarEvent;
use App\Services\Exports\ExportCreditService;
use App\Models\Endpoint;
use App\Models\MonitorAlertEvent;
use App\Models\Project;
use App\Models\QaReleaseGate;
use App\Services\Settings\SettingService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request, SettingService $settings): View
    {
        [$startsAt, $endsAt] = $this->range($request);
        $projectId = $request->integer('project_id') ?: null;
        $status = trim((string) $request->input('status', ''));
        $eventType = trim((string) $request->input('event_type', ''));
        $gridStartsAt = $startsAt->copy()->startOfMonth()->startOfWeek();
        $gridEndsAt = $endsAt->copy()->endOfMonth()->endOfWeek();

        $events = $this->calendarEventQuery($projectId, $status, $eventType)
            ->where(function (Builder $query) use ($startsAt, $endsAt): void {
                $this->overlaps($query, $startsAt, $endsAt);
            })
            ->orderBy('starts_at')
            ->paginate($settings->integer('app.items_per_page', 25))
            ->withQueryString();

        $calendarRangeEvents = $this->calendarEventQuery($projectId, $status, $eventType)
            ->where(function (Builder $query) use ($gridStartsAt, $gridEndsAt): void {
                $this->overlaps($query, $gridStartsAt, $gridEndsAt);
            })
            ->orderBy('starts_at')
            ->get();

        $calendarEvents = $this->eventsByDay($calendarRangeEvents, $gridStartsAt, $gridEndsAt);

        $monitorRuns = ApiMonitor::query()
            ->with(['project', 'environment'])
            ->where('is_enabled', true)
            ->whereBetween('next_run_at', [$startsAt, $endsAt])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->orderBy('next_run_at')
            ->limit(100)
            ->get();

        $projects = Project::query()->orderBy('name')->get();
        $days = collect(CarbonPeriod::create($gridStartsAt, $gridEndsAt))
            ->map(fn (Carbon $day) => $day->copy());

        $summary = [
            'open' => CalendarEvent::query()->whereNull('completed_at')->whereIn('status', [CalendarEvent::STATUS_PLANNED, CalendarEvent::STATUS_IN_PROGRESS])->count(),
            'due_today' => CalendarEvent::query()->whereDate('starts_at', now()->toDateString())->whereIn('status', [CalendarEvent::STATUS_PLANNED, CalendarEvent::STATUS_IN_PROGRESS])->count(),
            'overdue' => CalendarEvent::query()->where('starts_at', '<', now())->whereIn('status', [CalendarEvent::STATUS_PLANNED, CalendarEvent::STATUS_IN_PROGRESS])->count(),
            'monitor_runs' => $monitorRuns->count(),
        ];

        return view('calendar.index', compact('events', 'calendarEvents', 'monitorRuns', 'projects', 'days', 'summary', 'startsAt', 'endsAt', 'projectId', 'status', 'eventType'));
    }

    public function project(Project $project, Request $request, SettingService $settings): View
    {
        $request->merge(['project_id' => $project->id]);

        return $this->index($request, $settings);
    }

    public function day(Request $request): View
    {
        try {
            $day = $request->filled('date') ? Carbon::parse($request->input('date'))->startOfDay() : now()->startOfDay();
        } catch (\Throwable) {
            $day = now()->startOfDay();
        }

        $projectId = $request->integer('project_id') ?: null;
        $status = trim((string) $request->input('status', ''));
        $eventType = trim((string) $request->input('event_type', ''));
        $dayEndsAt = $day->copy()->endOfDay();

        $events = $this->calendarEventQuery($projectId, $status, $eventType)
            ->where(function (Builder $query) use ($day, $dayEndsAt): void {
                $this->overlaps($query, $day, $dayEndsAt);
            })
            ->orderBy('starts_at')
            ->get();

        $monitorRuns = ApiMonitor::query()
            ->with(['project', 'environment'])
            ->where('is_enabled', true)
            ->whereBetween('next_run_at', [$day, $dayEndsAt])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->orderBy('next_run_at')
            ->get();

        $projects = Project::query()->orderBy('name')->get();
        $month = $day->format('Y-m');

        return view('calendar.day', compact('day', 'events', 'monitorRuns', 'projects', 'projectId', 'status', 'eventType', 'month'));
    }

    public function create(Request $request): View
    {
        try {
            $defaultStartsAt = $request->filled('starts_at') ? Carbon::parse($request->input('starts_at')) : now()->addDay()->minute(0)->second(0);
        } catch (\Throwable) {
            $defaultStartsAt = now()->addDay()->minute(0)->second(0);
        }

        return view('calendar.create', $this->formData(new CalendarEvent([
            'project_id' => $request->integer('project_id') ?: null,
            'api_monitor_id' => $request->integer('api_monitor_id') ?: null,
            'monitor_alert_event_id' => $request->integer('monitor_alert_event_id') ?: null,
            'event_type' => $request->input('event_type', CalendarEvent::TYPE_MANUAL_QA_TASK),
            'status' => CalendarEvent::STATUS_PLANNED,
            'priority' => $request->input('priority', CalendarEvent::PRIORITY_NORMAL),
            'starts_at' => $defaultStartsAt,
            'all_day' => false,
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->payload($request);
        $payload['created_by'] = $request->user()?->id;

        $event = CalendarEvent::query()->create($payload);

        return redirect()
            ->route('calendar.index', ['project_id' => $event->project_id])
            ->with('success', __('messages.calendar.created'));
    }

    public function edit(CalendarEvent $calendarEvent): View
    {
        abort_if($calendarEvent->is_system_locked, 403);

        return view('calendar.edit', $this->formData($calendarEvent));
    }

    public function update(Request $request, CalendarEvent $calendarEvent): RedirectResponse
    {
        abort_if($calendarEvent->is_system_locked, 403);

        $calendarEvent->update($this->payload($request));

        return redirect()
            ->route('calendar.index', ['project_id' => $calendarEvent->project_id])
            ->with('success', __('messages.calendar.updated'));
    }

    public function destroy(CalendarEvent $calendarEvent): RedirectResponse
    {
        if ($calendarEvent->is_system_locked) {
            return redirect()
                ->route('calendar.index', ['project_id' => $calendarEvent->project_id])
                ->with('error', __('messages.calendar.activity_locked'));
        }

        $projectId = $calendarEvent->project_id;
        $calendarEvent->delete();

        return redirect()
            ->route('calendar.index', ['project_id' => $projectId])
            ->with('success', __('messages.calendar.deleted'));
    }

    public function complete(CalendarEvent $calendarEvent): RedirectResponse
    {
        abort_if($calendarEvent->is_system_locked, 403);

        $calendarEvent->update([
            'status' => CalendarEvent::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return redirect()
            ->route('calendar.index', ['project_id' => $calendarEvent->project_id])
            ->with('success', __('messages.calendar.completed'));
    }

    public function storeAlertFollowUp(Request $request, Project $project, ApiMonitor $monitor, MonitorAlertEvent $alert): RedirectResponse
    {
        abort_unless($monitor->project_id === $project->id, 404);
        abort_unless($alert->api_monitor_id === $monitor->id && $alert->project_id === $project->id, 404);

        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:220'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::in(CalendarEvent::PRIORITIES)],
        ]);

        CalendarEvent::query()->create([
            'project_id' => $project->id,
            'api_monitor_id' => $monitor->id,
            'monitor_alert_event_id' => $alert->id,
            'created_by' => $request->user()?->id,
            'title' => trim((string) ($validated['title'] ?? '')) ?: __('messages.calendar.default_alert_follow_up_title', ['monitor' => $monitor->name]),
            'description' => trim((string) ($validated['description'] ?? '')) ?: $alert->message,
            'event_type' => CalendarEvent::TYPE_ALERT_FOLLOW_UP,
            'status' => CalendarEvent::STATUS_PLANNED,
            'priority' => $validated['priority'],
            'starts_at' => Carbon::parse($validated['starts_at']),
            'all_day' => false,
        ]);

        return redirect()
            ->route('projects.monitors.alerts', [$project, $monitor])
            ->with('success', __('messages.calendar.follow_up_created'));
    }

    public function feed(Request $request): JsonResponse
    {
        [$startsAt, $endsAt] = $this->range($request);
        $projectId = $request->integer('project_id') ?: null;

        $events = CalendarEvent::query()
            ->with(['project', 'monitor'])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->where(function (Builder $query) use ($startsAt, $endsAt): void {
                $this->overlaps($query, $startsAt, $endsAt);
            })
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $event): array => [
                'id' => $event->id,
                'title' => $event->display_title,
                'event_type' => $event->event_type,
                'tone' => $event->tone_css,
                'status' => $event->status,
                'priority' => $event->priority,
                'project' => $event->project?->name,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'all_day' => $event->all_day,
                'url' => $event->is_system_locked ? null : route('calendar.edit', $event),
            ]);

        $monitorRuns = ApiMonitor::query()
            ->with('project')
            ->where('is_enabled', true)
            ->whereBetween('next_run_at', [$startsAt, $endsAt])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->orderBy('next_run_at')
            ->get()
            ->map(fn (ApiMonitor $monitor): array => [
                'id' => 'monitor-'.$monitor->id,
                'title' => __('messages.calendar.monitor_run_title', ['monitor' => $monitor->name]),
                'event_type' => CalendarEvent::TYPE_MONITOR_RUN,
                'tone' => 'monitor',
                'status' => CalendarEvent::STATUS_PLANNED,
                'priority' => CalendarEvent::PRIORITY_NORMAL,
                'project' => $monitor->project?->name,
                'starts_at' => $monitor->next_run_at?->toIso8601String(),
                'ends_at' => null,
                'all_day' => false,
                'url' => $monitor->project ? route('projects.monitors.edit', [$monitor->project, $monitor]) : null,
            ]);

        return response()->json([
            'range' => ['from' => $startsAt->toIso8601String(), 'to' => $endsAt->toIso8601String()],
            'events' => $events->concat($monitorRuns)->values(),
        ]);
    }

    public function ics(Request $request, ExportCreditService $credits): Response
    {
        [$startsAt, $endsAt] = $this->range($request);
        $projectId = $request->integer('project_id') ?: null;

        $events = CalendarEvent::query()
            ->with(['project'])
            ->when($projectId, fn ($query) => $query->where('project_id', $projectId))
            ->where(function (Builder $query) use ($startsAt, $endsAt): void {
                $this->overlaps($query, $startsAt, $endsAt);
            })
            ->orderBy('starts_at')
            ->get();

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Aptoria//QA Operations Calendar//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:Aptoria QA Operations Calendar',
            'X-WR-CALDESC:'.$this->icsEscape($credits->shortLine()),
        ];

        foreach ($events as $event) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:aptoria-calendar-event-'.$event->id.'@aptoria.local';
            $lines[] = 'DTSTAMP:'.now()->utc()->format('Ymd\\THis\\Z');
            $lines[] = 'DTSTART:'.$this->icsDate($event->starts_at, $event->all_day);
            if ($event->ends_at) {
                $lines[] = 'DTEND:'.$this->icsDate($event->ends_at, $event->all_day);
            }
            $lines[] = 'SUMMARY:'.$this->icsEscape($event->display_title);
            $description = trim(($event->display_description ?: '').($event->project ? "\nProject: ".$event->project->name : ''));
            $description = trim($description."\n\n".$credits->shortLine());
            $lines[] = 'DESCRIPTION:'.$this->icsEscape($description);
            $lines[] = 'CATEGORIES:'.$this->icsEscape($event->event_type.','.$event->priority.','.$event->status.','.$event->tone_css);
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="aptoria-calendar.ics"',
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(Request $request): array
    {
        $month = trim((string) $request->input('month', '')) ?: now()->format('Y-m');

        try {
            $startsAt = $request->filled('from')
                ? Carbon::parse($request->input('from'))->startOfDay()
                : Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        } catch (\Throwable) {
            $startsAt = now()->startOfMonth();
        }

        try {
            $endsAt = $request->filled('to')
                ? Carbon::parse($request->input('to'))->endOfDay()
                : $startsAt->copy()->endOfMonth();
        } catch (\Throwable) {
            $endsAt = $startsAt->copy()->endOfMonth();
        }

        return [$startsAt, $endsAt];
    }

    /** @return array<string, mixed> */
    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'endpoint_id' => ['nullable', 'integer', 'exists:endpoints,id'],
            'api_monitor_id' => ['nullable', 'integer', 'exists:api_monitors,id'],
            'monitor_alert_event_id' => ['nullable', 'integer', 'exists:monitor_alert_events,id'],
            'qa_release_gate_id' => ['nullable', 'integer', 'exists:qa_release_gates,id'],
            'title' => ['required', 'string', 'max:220'],
            'description' => ['nullable', 'string'],
            'event_type' => ['required', Rule::in(CalendarEvent::MANUAL_TYPES)],
            'status' => ['required', Rule::in(CalendarEvent::STATUSES)],
            'priority' => ['required', Rule::in(CalendarEvent::PRIORITIES)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'all_day' => ['nullable', 'boolean'],
        ]);

        $projectId = $validated['project_id'] ?? null;
        $this->assertBelongsToProject($projectId, $validated);

        return [
            'project_id' => $projectId,
            'endpoint_id' => $validated['endpoint_id'] ?? null,
            'api_monitor_id' => $validated['api_monitor_id'] ?? null,
            'monitor_alert_event_id' => $validated['monitor_alert_event_id'] ?? null,
            'qa_release_gate_id' => $validated['qa_release_gate_id'] ?? null,
            'title' => $validated['title'],
            'description' => trim((string) ($validated['description'] ?? '')) ?: null,
            'event_type' => $validated['event_type'],
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'starts_at' => Carbon::parse($validated['starts_at']),
            'ends_at' => ! empty($validated['ends_at']) ? Carbon::parse($validated['ends_at']) : null,
            'all_day' => $request->boolean('all_day'),
        ];
    }

    /** @param array<string, mixed> $validated */
    private function assertBelongsToProject(?int $projectId, array $validated): void
    {
        if ($projectId === null) {
            return;
        }

        foreach ([
            Endpoint::class => 'endpoint_id',
            ApiMonitor::class => 'api_monitor_id',
            MonitorAlertEvent::class => 'monitor_alert_event_id',
            QaReleaseGate::class => 'qa_release_gate_id',
        ] as $class => $key) {
            if (empty($validated[$key])) {
                continue;
            }

            $record = $class::query()->find((int) $validated[$key]);
            abort_unless($record && (int) $record->project_id === $projectId, 422);
        }
    }

    /** @return array<string, mixed> */
    private function formData(CalendarEvent $calendarEvent): array
    {
        return [
            'calendarEvent' => $calendarEvent,
            'projects' => Project::query()->orderBy('name')->get(),
            'endpoints' => Endpoint::query()->with('project')->orderBy('method')->orderBy('path')->limit(500)->get(),
            'monitors' => ApiMonitor::query()->with('project')->orderBy('name')->limit(500)->get(),
            'alerts' => MonitorAlertEvent::query()->with(['project', 'monitor'])->latest()->limit(200)->get(),
            'releaseGates' => QaReleaseGate::query()->with('project')->latest()->limit(200)->get(),
        ];
    }

    private function calendarEventQuery(?int $projectId, string $status = '', string $eventType = ''): Builder
    {
        return CalendarEvent::query()
            ->with(['project', 'endpoint', 'monitor', 'alertEvent', 'releaseGate'])
            ->when($projectId, fn (Builder $query) => $query->where('project_id', $projectId))
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($eventType !== '', fn (Builder $query) => $query->where('event_type', $eventType));
    }

    private function overlaps(Builder $query, Carbon $startsAt, Carbon $endsAt): void
    {
        $query->where('starts_at', '<=', $endsAt)
            ->where(function (Builder $query) use ($startsAt): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $startsAt);
            });
    }

    /**
     * @param Collection<int, CalendarEvent> $events
     * @return Collection<string, Collection<int, CalendarEvent>>
     */
    private function eventsByDay(Collection $events, Carbon $gridStartsAt, Carbon $gridEndsAt): Collection
    {
        $byDay = collect();

        foreach ($events as $event) {
            $eventStart = $event->starts_at?->copy()->startOfDay() ?: $gridStartsAt->copy();
            $eventEnd = ($event->ends_at ?: $event->starts_at)?->copy()->startOfDay() ?: $eventStart->copy();
            $from = $eventStart->greaterThan($gridStartsAt) ? $eventStart : $gridStartsAt->copy();
            $to = $eventEnd->lessThan($gridEndsAt) ? $eventEnd : $gridEndsAt->copy();

            foreach (CarbonPeriod::create($from, $to) as $day) {
                $key = $day->format('Y-m-d');
                if (! $byDay->has($key)) {
                    $byDay->put($key, collect());
                }
                $byDay->get($key)->push($event);
            }
        }

        return $byDay;
    }

    private function icsDate(?Carbon $date, bool $allDay): string
    {
        if (! $date) {
            return now()->utc()->format('Ymd\\THis\\Z');
        }

        return $allDay ? 'VALUE=DATE:'.$date->format('Ymd') : $date->copy()->utc()->format('Ymd\\THis\\Z');
    }

    private function icsEscape(string $value): string
    {
        return str_replace(["\\", ";", ",", "\n", "\r"], ["\\\\", "\\;", "\\,", "\\n", ''], $value);
    }
}
