@extends('layouts.app')
@section('title', __('messages.nav.audit_log'))
@section('page_title', __('messages.nav.audit_log'))
@section('content')
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card aptoria-panel-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar-md rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                    <i data-lucide="file-delta"></i>
                </div>
                <div>
                    <p class="text-muted mb-1 small">{{ __('messages.audit.total_events') }}</p>
                    <h3 class="mb-0">{{ $stats['total'] }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card aptoria-panel-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar-md rounded bg-warning-subtle text-warning d-flex align-items-center justify-content-center">
                    <i data-lucide="bug"></i>
                </div>
                <div>
                    <p class="text-muted mb-1 small">{{ __('messages.audit.attention_events') }}</p>
                    <h3 class="mb-0">{{ $stats['attention'] }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card aptoria-panel-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar-md rounded bg-info-subtle text-info d-flex align-items-center justify-content-center">
                    <i data-lucide="calendar-clock"></i>
                </div>
                <div>
                    <p class="text-muted mb-1 small">{{ __('messages.audit.today_events') }}</p>
                    <h3 class="mb-0">{{ $stats['today'] }}</h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card aptoria-panel-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="avatar-md rounded bg-success-subtle text-success d-flex align-items-center justify-content-center">
                    <i data-lucide="folder-git-2"></i>
                </div>
                <div>
                    <p class="text-muted mb-1 small">{{ __('messages.audit.project_events') }}</p>
                    <h3 class="mb-0">{{ $stats['project_events'] }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card aptoria-panel-card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="card-title mb-0">{{ __('messages.audit.filters') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.audit.filters_copy') }}</p>
        </div>
        @if(array_filter($filters))
            <a href="{{ route('audit.index') }}" class="btn btn-light btn-sm">
                <i data-lucide="x"></i> {{ __('messages.audit.clear_filters') }}
            </a>
        @endif
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('audit.index') }}" class="row g-2 align-items-end">
            <div class="col-xl-4 col-lg-6">
                <label class="form-label" for="audit-q">{{ __('messages.audit.search') }}</label>
                <div class="input-group">
                    <span class="input-group-text"><i data-lucide="search"></i></span>
                    <input id="audit-q" type="search" class="form-control" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('messages.audit.search_placeholder') }}">
                </div>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-6">
                <label class="form-label" for="audit-severity">{{ __('messages.audit.severity') }}</label>
                <select id="audit-severity" name="severity" class="form-select">
                    <option value="">{{ __('messages.audit.all_severities') }}</option>
                    @foreach($severityOptions as $severity)
                        <option value="{{ $severity }}" @selected(($filters['severity'] ?? '') === $severity)>{{ trans()->has('messages.audit.severities.' . $severity) ? __('messages.audit.severities.' . $severity) : ucfirst(str_replace('_', ' ', $severity)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-lg-3 col-md-6">
                <label class="form-label" for="audit-event-type">{{ __('messages.audit.event_type') }}</label>
                <select id="audit-event-type" name="event_type" class="form-select">
                    <option value="">{{ __('messages.audit.all_event_types') }}</option>
                    @foreach($eventTypeOptions as $eventType)
                        <option value="{{ $eventType }}" @selected(($filters['event_type'] ?? '') === $eventType)>{{ trans()->has('messages.audit.event_types.' . $eventType) ? __('messages.audit.event_types.' . $eventType) : ucfirst(str_replace('_', ' ', $eventType)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-lg-6 col-md-6">
                <label class="form-label" for="audit-action">{{ __('messages.audit.action') }}</label>
                <select id="audit-action" name="action" class="form-select">
                    <option value="">{{ __('messages.audit.all_actions') }}</option>
                    @foreach($actionOptions as $action)
                        <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-lg-6 col-md-6">
                <label class="form-label" for="audit-project">{{ __('messages.audit.project') }}</label>
                <select id="audit-project" name="project_id" class="form-select">
                    <option value="">{{ __('messages.audit.all_projects') }}</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" @selected((string)($filters['project_id'] ?? '') === (string)$project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="filter"></i> {{ __('messages.audit.apply_filters') }}
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card aptoria-table-card aptoria-panel-card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="card-title mb-0">{{ __('messages.nav.audit_log') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.workspace.audit_log_copy') }}</p>
        </div>
        <span class="badge text-bg-light badge-label">{{ $logs->total() }} {{ __('messages.workspace.events') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-striped table-centered mb-0 w-100 aptoria-resource-table aptoria-audit-log-table">
                <colgroup>
                    <col class="aptoria-col-time">
                    <col class="aptoria-col-severity">
                    <col class="aptoria-col-event">
                    <col class="aptoria-col-action">
                    <col class="aptoria-col-summary">
                    <col class="aptoria-col-user">
                    <col class="aptoria-col-project">
                </colgroup>
                <thead class="thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th>{{ __('messages.workspace.time') }}</th>
                        <th>{{ __('messages.workspace.severity') }}</th>
                        <th>{{ __('messages.audit.event_type') }}</th>
                        <th>{{ __('messages.workspace.action') }}</th>
                        <th>{{ __('messages.workspace.summary') }}</th>
                        <th>{{ __('messages.workspace.user') }}</th>
                        <th>{{ __('messages.workspace.project') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    @php
                        $severityClass = match ($log->severity) {
                            'critical', 'error' => 'danger',
                            'warning' => 'warning',
                            default => 'light',
                        };
                    @endphp
                    <tr>
                        <td>
                            <span class="aptoria-audit-time" title="{{ $log->created_at?->format('Y-m-d H:i:s') }}">
                                {{ $log->created_at?->format('Y-m-d H:i') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $severityClass }} badge-label aptoria-audit-severity">
                                {{ trans()->has('messages.audit.severities.' . $log->severity) ? __('messages.audit.severities.' . $log->severity) : ucfirst(str_replace('_', ' ', $log->severity)) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge text-bg-secondary-subtle text-secondary badge-label aptoria-audit-event" title="{{ $log->event_type }}">
                                {{ trans()->has('messages.audit.event_types.' . $log->event_type) ? __('messages.audit.event_types.' . $log->event_type) : ucfirst(str_replace('_', ' ', $log->event_type)) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge text-bg-primary-subtle text-primary badge-label aptoria-audit-action" title="{{ $log->action }}">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td>
                            <span class="aptoria-audit-summary" title="{{ $log->summary }}">{{ $log->summary }}</span>
                            @if($log->subject_label)
                                <small class="d-block text-muted aptoria-audit-subject" title="{{ $log->subject_label }}">{{ $log->subject_label }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="aptoria-audit-user" title="{{ $log->user?->email ?: '—' }}">{{ $log->user?->email ?: '—' }}</span>
                        </td>
                        <td>
                            <span class="aptoria-audit-project" title="{{ $log->project?->name ?: '—' }}">{{ $log->project?->name ?: '—' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <div class="avatar-lg rounded bg-light mx-auto mb-2 d-flex align-items-center justify-content-center">
                                <i data-lucide="file-delta"></i>
                            </div>
                            <h6>{{ __('messages.audit.no_events_title') }}</h6>
                            <p class="mb-0 small">{{ __('messages.audit.no_events_copy') }}</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span class="text-muted small">
            {{ __('messages.audit.showing', ['from' => $logs->firstItem() ?? 0, 'to' => $logs->lastItem() ?? 0, 'total' => $logs->total()]) }}
        </span>
        <div class="aptoria-pagination-wrap">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
