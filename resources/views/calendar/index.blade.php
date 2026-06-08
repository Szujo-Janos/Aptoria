@extends('layouts.app')

@section('title', __('messages.calendar.title'))


@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="hpanel hblue">
            <div class="panel-body text-center">
                <h2 class="m-xs">{{ $summary['open'] }}</h2>
                <small>{{ __('messages.calendar.open_items') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hpanel hgreen">
            <div class="panel-body text-center">
                <h2 class="m-xs">{{ $summary['due_today'] }}</h2>
                <small>{{ __('messages.calendar.due_today') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hpanel horange">
            <div class="panel-body text-center">
                <h2 class="m-xs">{{ $summary['overdue'] }}</h2>
                <small>{{ __('messages.calendar.overdue') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hpanel hviolet">
            <div class="panel-body text-center">
                <h2 class="m-xs">{{ $summary['monitor_runs'] }}</h2>
                <small>{{ __('messages.calendar.monitor_runs_in_range') }}</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('calendar.create', ['project_id' => $projectId]) }}" class="btn btn-xs btn-success"><i class="fa fa-plus-circle"></i> {{ __('messages.calendar.create') }}</a>
                    <a href="{{ route('calendar.ics', request()->query()) }}" class="btn btn-xs btn-info"><i class="fa fa-calendar-plus-o"></i> {{ __('messages.calendar.export_ics') }}</a>
                    <a href="{{ route('calendar.feed', request()->query()) }}" class="btn btn-xs btn-default">{{ __('messages.calendar.json_feed') }}</a>
                </div>
                <i class="fa fa-calendar"></i> {{ __('messages.calendar.title') }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.calendar.intro') }}</p>
                <form method="GET" action="{{ route('calendar.index') }}" class="row m-b-md">
                    <div class="col-sm-3">
                        <label>{{ __('messages.nav.projects') }}</label>
                        <select name="project_id" class="form-control">
                            <option value="">{{ __('messages.calendar.all_projects') }}</option>
                            @foreach($projects as $projectOption)
                                <option value="{{ $projectOption->id }}" @selected((string) $projectId === (string) $projectOption->id)>{{ $projectOption->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label>{{ __('messages.calendar.month') }}</label>
                        <input type="month" name="month" class="form-control" value="{{ request('month', $startsAt->format('Y-m')) }}">
                    </div>
                    <div class="col-sm-2">
                        <label>{{ __('messages.calendar.event_type') }}</label>
                        <select name="event_type" class="form-control">
                            <option value="">{{ __('messages.calendar.all_types') }}</option>
                            @foreach(\App\Models\CalendarEvent::TYPES as $type)
                                <option value="{{ $type }}" @selected($eventType === $type)>{{ __('messages.calendar.types.'.$type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <label>{{ __('messages.common.status') }}</label>
                        <select name="status" class="form-control">
                            <option value="">{{ __('messages.calendar.all_statuses') }}</option>
                            @foreach(\App\Models\CalendarEvent::STATUSES as $calendarStatus)
                                <option value="{{ $calendarStatus }}" @selected($status === $calendarStatus)>{{ __('messages.calendar.statuses.'.$calendarStatus) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">{{ __('messages.common.filter') }}</button>
                            <a href="{{ route('calendar.index') }}" class="btn btn-default">{{ __('messages.common.reset') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.calendar.month_view') }} — {{ $startsAt->format('F Y') }}</div>
            <div class="panel-body">
                <div class="aptoria-calendar-legend m-b-md">
                    @foreach(['created', 'updated', 'deleted', 'alert', 'monitor', 'release', 'maintenance', 'security'] as $tone)
                        <span class="aptoria-calendar-legend-item aptoria-calendar-tone-{{ $tone }}"><i></i>{{ __('messages.calendar.tones.'.$tone) }}</span>
                    @endforeach
                </div>
                <div class="aptoria-calendar-grid">
                    @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dayName)
                        <div class="aptoria-calendar-weekday">{{ __('messages.calendar.weekdays.'.$dayName) }}</div>
                    @endforeach
                    @foreach($days as $day)
                        @php($dayEvents = $calendarEvents->get($day->format('Y-m-d'), collect()))
                        <div class="aptoria-calendar-day {{ $day->month !== $startsAt->month ? 'is-muted' : '' }} {{ $day->isToday() ? 'is-today' : '' }}">
                            <a class="aptoria-calendar-day-number" href="{{ route('calendar.day', ['date' => $day->format('Y-m-d'), 'project_id' => $projectId, 'month' => $startsAt->format('Y-m')]) }}" title="{{ __('messages.calendar.view_day') }}">{{ $day->format('j') }}</a>
                            @foreach($dayEvents->take(5) as $event)
                                @php($segmentClass = $event->segmentClassFor($day))
                                @if($event->is_system_locked)
                                    <span class="aptoria-calendar-chip aptoria-calendar-tone-{{ $event->tone_css }} {{ $segmentClass }}" title="{{ $event->display_title }}">
                                        <i class="fa {{ $event->type_icon }}"></i> {{ \Illuminate\Support\Str::limit($event->display_title, 30) }}
                                    </span>
                                @else
                                    <a href="{{ route('calendar.edit', $event) }}" class="aptoria-calendar-chip aptoria-calendar-tone-{{ $event->tone_css }} {{ $segmentClass }}" title="{{ $event->display_title }}">
                                        <i class="fa {{ $event->type_icon }}"></i> {{ \Illuminate\Support\Str::limit($event->display_title, 30) }}
                                    </a>
                                @endif
                            @endforeach
                            @if($dayEvents->count() > 5)
                                <a class="aptoria-calendar-more" href="{{ route('calendar.day', ['date' => $day->format('Y-m-d'), 'project_id' => $projectId, 'month' => $startsAt->format('Y-m')]) }}">+{{ $dayEvents->count() - 5 }} {{ __('messages.calendar.more') }}</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.calendar.upcoming_events') }}</div>
            <div class="panel-body no-padding">
                @if($events->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.calendar.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.calendar.empty_help') }}</p>
                        <a href="{{ route('calendar.create', ['project_id' => $projectId]) }}" class="btn btn-success">{{ __('messages.calendar.create') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead>
                            <tr>
                                <th>{{ __('messages.calendar.starts_at') }}</th>
                                <th>{{ __('messages.calendar.title_field') }}</th>
                                <th>{{ __('messages.calendar.event_type') }}</th>
                                <th>{{ __('messages.nav.projects') }}</th>
                                <th>{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.calendar.priority') }}</th>
                                <th class="text-right">{{ __('messages.common.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($events as $event)
                                <tr class="aptoria-calendar-table-row aptoria-calendar-row-tone-{{ $event->tone_css }}">
                                    <td>{{ $event->all_day ? $event->starts_at?->format('Y-m-d') : $event->starts_at?->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <span class="aptoria-calendar-row-marker aptoria-calendar-tone-{{ $event->tone_css }}"></span><strong><i class="fa {{ $event->type_icon }}"></i> {{ $event->display_title }}</strong>
                                        @if($event->is_system_locked)
                                            <br><span class="label label-default"><i class="fa fa-lock"></i> {{ __('messages.calendar.activity_locked_badge') }}</span>
                                        @endif
                                        @if($event->display_description)
                                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($event->display_description, 120) }}</small>
                                        @endif
                                        @if($event->monitor)
                                            <br><small><i class="fa fa-clock-o"></i> {{ $event->monitor->name }}</small>
                                        @endif
                                    </td>
                                    <td><span class="aptoria-calendar-type-pill aptoria-calendar-tone-{{ $event->tone_css }}">{{ $event->type_label }}</span></td>
                                    <td>@if($event->project)<a href="{{ route('projects.show', $event->project) }}">{{ $event->project->name }}</a>@else<span class="text-muted">{{ __('messages.common.none') }}</span>@endif</td>
                                    <td><span class="label label-{{ $event->status_css }}">{{ $event->status_label }}</span></td>
                                    <td><span class="label label-{{ $event->priority_css }}">{{ $event->priority_label }}</span></td>
                                    <td class="text-right">
                                        @if($event->is_system_locked)
                                            <span class="label label-default"><i class="fa fa-lock"></i> {{ __('messages.calendar.activity_locked_badge') }}</span>
                                        @else
                                            @if($event->status !== \App\Models\CalendarEvent::STATUS_COMPLETED)
                                                <form method="POST" action="{{ route('calendar.complete', $event) }}" style="display:inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-xs btn-success" type="submit">{{ __('messages.calendar.complete') }}</button>
                                                </form>
                                            @endif
                                            <a href="{{ route('calendar.edit', $event) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                                            <form method="POST" action="{{ route('calendar.destroy', $event) }}" style="display:inline" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.calendar.delete_confirm') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-xs btn-danger" type="submit">{{ __('messages.common.delete') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-footer">{{ $events->links() }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.calendar.monitor_run_preview') }}</div>
            <div class="panel-body">
                @if($monitorRuns->isEmpty())
                    <p class="text-muted">{{ __('messages.calendar.no_monitor_runs') }}</p>
                @else
                    <ul class="list-group clear-list">
                        @foreach($monitorRuns as $monitor)
                            <li class="list-group-item">
                                <span class="pull-right text-muted">{{ $monitor->next_run_at?->format('Y-m-d H:i') }}</span>
                                <strong>{{ $monitor->name }}</strong><br>
                                <small>{{ $monitor->project?->name }} · {{ $monitor->environment?->name ?: __('messages.endpoints.project_default') }}</small>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
