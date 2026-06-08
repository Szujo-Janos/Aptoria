@extends('layouts.app')

@section('title', __('messages.calendar.day_view'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('calendar.index', ['project_id' => $projectId, 'month' => $month]) }}" class="btn btn-xs btn-default"><i class="fa fa-arrow-left"></i> {{ __('messages.calendar.back_to_month') }}</a>
                    <a href="{{ route('calendar.create', ['project_id' => $projectId, 'starts_at' => $day->format('Y-m-d H:i:s')]) }}" class="btn btn-xs btn-success"><i class="fa fa-plus-circle"></i> {{ __('messages.calendar.create') }}</a>
                </div>
                <i class="fa fa-calendar-o"></i> {{ __('messages.calendar.events_on_day', ['date' => $day->format('Y-m-d')]) }}
            </div>
            <div class="panel-body">
                <form method="GET" action="{{ route('calendar.day') }}" class="row m-b-md">
                    <input type="hidden" name="date" value="{{ $day->format('Y-m-d') }}">
                    <div class="col-sm-3">
                        <label>{{ __('messages.nav.projects') }}</label>
                        <select name="project_id" class="form-control">
                            <option value="">{{ __('messages.calendar.all_projects') }}</option>
                            @foreach($projects as $projectOption)
                                <option value="{{ $projectOption->id }}" @selected((string) $projectId === (string) $projectOption->id)>{{ $projectOption->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <label>{{ __('messages.calendar.event_type') }}</label>
                        <select name="event_type" class="form-control">
                            <option value="">{{ __('messages.calendar.all_types') }}</option>
                            @foreach(\App\Models\CalendarEvent::TYPES as $type)
                                <option value="{{ $type }}" @selected($eventType === $type)>{{ __('messages.calendar.types.'.$type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
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
                            <a href="{{ route('calendar.day', ['date' => $day->format('Y-m-d')]) }}" class="btn btn-default">{{ __('messages.common.reset') }}</a>
                        </div>
                    </div>
                </form>

                <div class="aptoria-calendar-legend m-b-md">
                    @foreach(['created', 'updated', 'deleted', 'alert', 'monitor', 'release', 'maintenance', 'security'] as $tone)
                        <span class="aptoria-calendar-legend-item aptoria-calendar-tone-{{ $tone }}"><i></i>{{ __('messages.calendar.tones.'.$tone) }}</span>
                    @endforeach
                </div>

                @if($events->isEmpty() && $monitorRuns->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.calendar.no_events_on_day') }}</h4>
                        <p class="text-muted">{{ __('messages.calendar.empty_help') }}</p>
                        <a href="{{ route('calendar.create', ['project_id' => $projectId, 'starts_at' => $day->format('Y-m-d H:i:s')]) }}" class="btn btn-success">{{ __('messages.calendar.create') }}</a>
                    </div>
                @else
                    <div class="aptoria-calendar-day-timeline">
                        @foreach($events as $event)
                            <div class="aptoria-calendar-day-entry aptoria-calendar-row-tone-{{ $event->tone_css }}">
                                <div class="aptoria-calendar-day-entry-time">
                                    @if($event->all_day)
                                        {{ __('messages.calendar.all_day') }}
                                    @else
                                        {{ $event->starts_at?->format('H:i') }}
                                        @if($event->ends_at)
                                            – {{ $event->ends_at->format($event->starts_at?->isSameDay($event->ends_at) ? 'H:i' : 'Y-m-d H:i') }}
                                        @endif
                                    @endif
                                </div>
                                <div class="aptoria-calendar-day-entry-body">
                                    <span class="aptoria-calendar-type-pill aptoria-calendar-tone-{{ $event->tone_css }}">{{ $event->tone_label }}</span>
                                    <strong><i class="fa {{ $event->type_icon }}"></i> {{ $event->display_title }}</strong>
                                    @if($event->spansMultipleDays())
                                        <span class="label label-info">{{ __('messages.calendar.multi_day') }}</span>
                                    @endif
                                    @if($event->is_system_locked)
                                        <span class="label label-default"><i class="fa fa-lock"></i> {{ __('messages.calendar.activity_locked_badge') }}</span>
                                    @endif
                                    @if($event->display_description)
                                        <div><small class="text-muted">{{ $event->display_description }}</small></div>
                                    @endif
                                    @if($event->project)
                                        <div><small><i class="fa fa-folder-open"></i> <a href="{{ route('projects.show', $event->project) }}">{{ $event->project->name }}</a></small></div>
                                    @endif
                                    @unless($event->is_system_locked)
                                        <div class="m-t-xs">
                                            <a href="{{ route('calendar.edit', $event) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                                            @if($event->status !== \App\Models\CalendarEvent::STATUS_COMPLETED)
                                                <form method="POST" action="{{ route('calendar.complete', $event) }}" style="display:inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-xs btn-success" type="submit">{{ __('messages.calendar.complete') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endunless
                                </div>
                            </div>
                        @endforeach
                        @foreach($monitorRuns as $monitor)
                            <div class="aptoria-calendar-day-entry aptoria-calendar-row-tone-monitor">
                                <div class="aptoria-calendar-day-entry-time">{{ $monitor->next_run_at?->format('H:i') }}</div>
                                <div class="aptoria-calendar-day-entry-body">
                                    <span class="aptoria-calendar-type-pill aptoria-calendar-tone-monitor">{{ __('messages.calendar.tones.monitor') }}</span>
                                    <strong><i class="fa fa-clock-o"></i> {{ __('messages.calendar.monitor_run_title', ['monitor' => $monitor->name]) }}</strong>
                                    <div><small class="text-muted">{{ $monitor->project?->name }} · {{ $monitor->environment?->name ?: __('messages.endpoints.project_default') }}</small></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
