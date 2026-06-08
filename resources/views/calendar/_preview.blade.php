@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\CalendarEvent> $events */
    $events = $events ?? collect();
    $summary = $summary ?? ['open' => 0, 'due_today' => 0, 'overdue' => 0];
    $previewProject = $project ?? null;
    $calendarUrl = $previewProject ? route('projects.calendar.index', $previewProject) : route('calendar.index');
    $createUrl = route('calendar.create', array_filter(['project_id' => $previewProject?->id]));
@endphp

<div class="hpanel hgreen aptoria-calendar-preview-panel">
    <div class="panel-heading hbuilt">
        <div class="panel-tools">
            <a href="{{ $createUrl }}" class="btn btn-xs btn-success"><i class="fa fa-plus"></i> {{ __('messages.calendar.create') }}</a>
            <a href="{{ $calendarUrl }}" class="btn btn-xs btn-default"><i class="fa fa-calendar"></i> {{ __('messages.calendar.open_calendar') }}</a>
        </div>
        <i class="fa fa-calendar"></i> {{ __('messages.calendar.preview_title') }}
    </div>
    <div class="panel-body">
        <p class="text-muted aptoria-section-subtitle">{{ __('messages.calendar.preview_help') }}</p>
        <div class="row text-center aptoria-calendar-preview-summary">
            <div class="col-xs-4">
                <h4>{{ $summary['open'] ?? 0 }}</h4>
                <small>{{ __('messages.calendar.open_items') }}</small>
            </div>
            <div class="col-xs-4">
                <h4>{{ $summary['due_today'] ?? 0 }}</h4>
                <small>{{ __('messages.calendar.due_today') }}</small>
            </div>
            <div class="col-xs-4">
                <h4>{{ $summary['overdue'] ?? 0 }}</h4>
                <small>{{ __('messages.calendar.overdue') }}</small>
            </div>
        </div>

        @if($events->isEmpty())
            <div class="aptoria-calendar-preview-empty">
                <div class="aptoria-empty-icon"><i class="fa fa-calendar-o"></i></div>
                <strong>{{ __('messages.calendar.empty_title') }}</strong>
                <p class="text-muted m-b-none">{{ __('messages.calendar.empty_help') }}</p>
            </div>
        @else
            <div class="aptoria-calendar-preview-list">
                @foreach($events as $event)
                    <a class="aptoria-calendar-preview-item aptoria-calendar-row-tone-{{ $event->tone_css }}" href="{{ route('calendar.day', ['date' => $event->starts_at?->format('Y-m-d'), 'project_id' => $event->project_id]) }}">
                        <span class="aptoria-calendar-row-marker"></span>
                        <span class="aptoria-calendar-preview-date">
                            {{ $event->starts_at?->format($event->all_day ? 'Y-m-d' : 'Y-m-d H:i') }}
                            @if($event->spansMultipleDays())
                                <span class="label label-default">{{ __('messages.calendar.multi_day') }}</span>
                            @endif
                        </span>
                        <span class="aptoria-calendar-preview-copy">
                            <strong><i class="fa {{ $event->type_icon }}"></i> {{ $event->display_title }}</strong>
                            <small>
                                <span class="label label-{{ $event->status_css }}">{{ $event->status_label }}</span>
                                <span class="label label-{{ $event->priority_css }}">{{ $event->priority_label }}</span>
                                <span class="text-muted">{{ $event->project?->name ?: __('messages.calendar.no_project') }}</span>
                            </small>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
