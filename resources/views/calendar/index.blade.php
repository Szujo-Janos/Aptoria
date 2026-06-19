@extends('layouts.app')
@section('title', __('messages.calendar.title') . ' · ' . $project->name)
@section('page_title', __('messages.calendar.title'))
@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    <button type="button" class="btn btn-primary btn-new-event"><i data-lucide="calendar-plus" class="me-1"></i>{{ __('messages.calendar.new') }}</button>
@endsection

@push('styles')
<style>
    .aptoria-calendar-shell .outlook-box { min-height: 0; }
    .aptoria-calendar-shell #aptoriaFullCalendar { min-height: 0; }
    .aptoria-calendar-shell .external-event { cursor: grab; margin-bottom: .55rem; padding: .65rem .75rem; border-radius: .6rem; }
    .aptoria-calendar-shell .fc .fc-toolbar-title { font-size: 1.15rem; font-weight: 600; }
    .aptoria-calendar-shell .fc-event { border-radius: .45rem; padding: .12rem .25rem; font-weight: 600; }
    .aptoria-calendar-shell .fc-daygrid-event { white-space: normal; }
    .aptoria-calendar-shell .fc-event.aptoria-system-log-event { opacity: .85; cursor: help; }
    .aptoria-day-timeline { max-height: 520px; overflow: auto; }
    .aptoria-calendar-legend-dot { width: .65rem; height: .65rem; display: inline-block; border-radius: 999px; }
</style>
@endpush

@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-primary"><span class="avatar-title"><i data-lucide="calendar-stats"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.calendar.total') }}</p><h3 class="mb-0 fw-light">{{ $metrics['total'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-warning"><span class="avatar-title"><i data-lucide="clock"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.calendar.planned') }}</p><h3 class="mb-0 fw-light">{{ $metrics['planned'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-danger"><span class="avatar-title"><i data-lucide="bug"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.calendar.critical_open') }}</p><h3 class="mb-0 fw-light">{{ $metrics['critical'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card aptoria-panel-card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-secondary"><span class="avatar-title"><i data-lucide="file-delta"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.calendar.system_logs') }}</p><h3 class="mb-0 fw-light">{{ $metrics['logs'] }}</h3></div></div></div></div>
</div>

<div class="aptoria-calendar-shell">
    <div class="outlook-box gap-3 mb-3">
        <div class="card mb-0 d-none d-lg-flex rounded-end-0 aptoria-panel-card" style="min-width: 260px; max-width: 285px;">
            <div class="card-body">
                <button class="btn btn-primary w-100 btn-new-event" type="button">
                    <i class="ti ti-plus me-2 align-middle"></i>{{ __('messages.calendar.new') }}
                </button>

                <div id="external-events" class="mt-3">
                    <p class="text-muted fst-italic fs-xs mb-3">{{ __('messages.calendar.drag_hint') }}</p>
                    @foreach([
                        ['manual_qa_task','primary','normal','clipboard-check'],
                        ['regression_retest','warning','high','repeat'],
                        ['release_checkpoint','success','high','shield-chevron'],
                        ['alert_follow_up','danger','critical','triangle-alert'],
                        ['security_review','info','high','scan-eye'],
                        ['maintenance_window','secondary','normal','wrench'],
                    ] as [$type,$tone,$priority,$icon])
                        <div class="external-event fc-event bg-{{ $tone }}-subtle text-{{ $tone }} border-start border-3 border-{{ $tone }} fw-semibold"
                             data-event-type="{{ $type }}"
                             data-priority="{{ $priority }}"
                             data-title="{{ __('messages.calendar.types.'.$type) }}"
                             data-class="bg-{{ $tone }}-subtle text-{{ $tone }} border-start border-3 border-{{ $tone }}">
                            <i data-lucide="{{ $icon }}" class="me-2"></i>{{ __('messages.calendar.types.'.$type) }}
                        </div>
                    @endforeach
                </div>

                <hr>
                <div class="small text-muted mb-2">{{ __('messages.calendar.legend') }}</div>
                <div class="d-flex flex-column gap-2 small">
                    <span><span class="aptoria-calendar-legend-dot bg-success me-2"></span>{{ __('messages.calendar.legend_manual') }}</span>
                    <span><span class="aptoria-calendar-legend-dot bg-primary me-2"></span>{{ __('messages.calendar.legend_release') }}</span>
                    <span><span class="aptoria-calendar-legend-dot bg-secondary me-2"></span>{{ __('messages.calendar.legend_logs') }}</span>
                </div>
            </div>
        </div>

        <div class="card h-100 mb-0 rounded-start-0 flex-grow-1 border-start-0 aptoria-panel-card">
            <div class="d-lg-none d-inline-flex card-header">
                <button class="btn btn-primary btn-new-event" type="button"><i class="ti ti-plus me-2 align-middle"></i>{{ __('messages.calendar.new') }}</button>
            </div>
            <div class="card-body">
                <div id="aptoriaFullCalendar"
                     data-csrf="{{ csrf_token() }}"
                     data-events-url="{{ route('projects.calendar.events', $project) }}"
                     data-day-url="{{ route('projects.calendar.day', $project) }}"
                     data-store-url="{{ route('projects.calendar.store', $project) }}"
                     data-update-url-template="{{ url('/projects/'.$project->id.'/calendar/__EVENT_ID__') }}"
                     data-move-url-template="{{ url('/projects/'.$project->id.'/calendar/__EVENT_ID__/move') }}"
                     data-delete-url-template="{{ url('/projects/'.$project->id.'/calendar/__EVENT_ID__') }}"
                     data-complete-url-template="{{ url('/projects/'.$project->id.'/calendar/__EVENT_ID__/complete') }}"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-table-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">{{ __('messages.calendar.events') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.calendar.events_copy') }}</p>
                </div>
                <span class="badge badge-soft-primary">{{ $events->total() }}</span>
            </div>
            <div class="card-body border-bottom">
                <form method="GET" class="row g-2 align-items-end" data-aptoria-form-plugin data-aptoria-form-scope="calendar_filters">
                    <div class="col-md-4"><label class="form-label">{{ __('messages.common.search') }}</label><input type="search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('messages.form_plugin.placeholders.calendar.search') }}"></div>
                    <div class="col-md-2"><label class="form-label">{{ __('messages.common.status') }}</label><select name="status" class="form-select"><option value="">{{ __('messages.common.all') }}</option>@foreach(\App\Models\CalendarEvent::STATUSES as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ __('messages.calendar.statuses.'.$status) }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">{{ __('messages.calendar.type') }}</label><select name="event_type" class="form-select"><option value="">{{ __('messages.common.all') }}</option>@foreach(\App\Models\CalendarEvent::TYPES as $type)<option value="{{ $type }}" @selected(($filters['event_type'] ?? '') === $type)>{{ __('messages.calendar.types.'.$type) }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label class="form-label">{{ __('messages.calendar.priority') }}</label><select name="priority" class="form-select"><option value="">{{ __('messages.common.all') }}</option>@foreach(\App\Models\CalendarEvent::PRIORITIES as $priority)<option value="{{ $priority }}" @selected(($filters['priority'] ?? '') === $priority)>{{ __('messages.calendar.priorities.'.$priority) }}</option>@endforeach</select></div>
                    <div class="col-md-1 d-grid"><button class="btn btn-primary" type="submit"><i data-lucide="filter"></i></button></div>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-calendar-table">
                        <thead class="thead-sm text-uppercase fs-xxs"><tr><th>{{ __('messages.calendar.event') }}</th><th>{{ __('messages.calendar.when') }}</th><th>{{ __('messages.calendar.type') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.calendar.priority') }}</th><th class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th></tr></thead>
                        <tbody>
                            @forelse ($events as $event)
                                <tr>
                                    <td><div class="d-flex align-items-center gap-2 min-w-0"><span class="avatar avatar-sm rounded text-bg-{{ $event->type_tone }}"><span class="avatar-title"><i data-lucide="calendar-stats"></i></span></span><div class="min-w-0"><span class="fw-medium d-block text-truncate aptoria-calendar-title-cell">{{ $event->title }}</span><small class="text-muted d-block text-truncate">{{ $event->description ?: __('messages.calendar.no_description') }}</small></div></div></td>
                                    <td><span class="text-muted">{{ $event->start_at?->format('Y-m-d H:i') ?? '—' }}</span>@if($event->end_at)<small class="d-block text-muted">{{ __('messages.calendar.until') }} {{ $event->end_at->format('Y-m-d H:i') }}</small>@endif</td>
                                    <td><span class="badge badge-soft-{{ $event->type_tone }}">{{ $event->type_label }}</span></td>
                                    <td><span class="badge badge-soft-{{ $event->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $event->status_label }}</span></td>
                                    <td><span class="badge badge-soft-{{ $event->priority_tone }}">{{ $event->priority_label }}</span></td>
                                    <td class="text-end aptoria-actions-cell"><button type="button" class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="modal" data-bs-target="#calendarPreviewModal{{ $event->id }}"><i data-lucide="eye"></i></button></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-5">{{ __('messages.calendar.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle d-flex justify-content-between align-items-center gap-2 flex-wrap"><span class="text-muted small">{{ __('messages.calendar.footer') }}</span>{{ $events->links() }}</div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light justify-content-between align-items-center"><h5 class="card-title mb-0">{{ __('messages.calendar.day_timeline') }}</h5><span class="badge badge-soft-primary" id="calendar-day-title">{{ now()->toDateString() }}</span></div>
            <div class="card-body p-0"><div class="list-group list-group-flush aptoria-day-timeline" id="calendar-day-timeline"><div class="list-group-item text-muted">{{ __('messages.calendar.loading') }}</div></div></div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.calendar.day_timeline_footer') }}</div>
        </div>
        <div class="card aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.calendar.upcoming') }}</h5></div>
            <div class="card-body p-0"><div class="list-group list-group-flush">
                @forelse ($upcoming as $event)
                    <div class="list-group-item d-flex align-items-start gap-2"><span class="avatar avatar-sm rounded text-bg-{{ $event->priority_tone }}"><span class="avatar-title"><i data-lucide="clock"></i></span></span><div class="min-w-0 flex-grow-1"><strong class="d-block text-truncate">{{ $event->title }}</strong><small class="text-muted d-block">{{ $event->start_at?->format('Y-m-d H:i') }} · {{ $event->type_label }}</small></div><span class="badge badge-soft-{{ $event->status_tone }}">{{ $event->status_label }}</span></div>
                @empty
                    <div class="list-group-item text-muted">{{ __('messages.calendar.no_upcoming') }}</div>
                @endforelse
            </div></div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.calendar.upcoming_footer') }}</div>
        </div>
    </div>
</div>

@include('calendar.partials.modals')
@endsection

@push('scripts')
@php
    $aptoriaCalendarLabels = [
        'newTitle' => __('messages.calendar.new'),
        'editTitle' => __('messages.calendar.edit'),
        'loading' => __('messages.calendar.loading'),
        'noDayEvents' => __('messages.calendar.no_day_events'),
        'dayLoadFailed' => __('messages.calendar.day_load_failed'),
        'locked' => __('messages.calendar.locked'),
        'moveFailedTitle' => __('messages.calendar.move_failed_title'),
        'moveFailedText' => __('messages.calendar.move_failed_text'),
        'buttonText' => [
            'today' => __('messages.calendar.buttons.today'),
            'month' => __('messages.calendar.buttons.month'),
            'week' => __('messages.calendar.buttons.week'),
            'day' => __('messages.calendar.buttons.day'),
            'list' => __('messages.calendar.buttons.list'),
            'prev' => __('messages.calendar.buttons.prev'),
            'next' => __('messages.calendar.buttons.next'),
        ],
    ];
@endphp
<script src="{{ asset('assets/aptoria-ui/assets/plugins/fullcalendar/index.global.min.js') }}"></script>
<script>
    window.AptoriaCalendar = @json($aptoriaCalendarLabels);
</script>
<script src="{{ asset('assets/aptoria-ui/assets/js/aptoria-calendar.js') }}"></script>
@endpush
