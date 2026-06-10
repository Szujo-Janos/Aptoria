@extends('layouts.app')

@section('title', __('messages.monitors.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('monitors.alerts.index', ['open' => 1]) }}" class="btn btn-xs btn-warning">{{ __('messages.monitors.open_alerts') }}</a>
                    <a href="{{ route('projects.monitors.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.monitors.create') }}</a>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.monitors.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.monitors.intro') }}</p>
                <div class="alert alert-info">
                    <strong>{{ __('messages.monitors.scheduler_command') }}</strong><br>
                    <code>C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --limit=50 --save-json</code><br><code>php artisan aptoria:run-monitors --project={{ $project->slug }} --dry-run --json</code>
                </div>
                @if($monitors->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.monitors.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.monitors.empty_help') }}</p>
                        <a href="{{ route('projects.monitors.create', $project) }}" class="btn btn-success">{{ __('messages.monitors.create') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.common.name') }}</th>
                                    <th>{{ __('messages.monitors.frequency') }}</th>
                                    <th>{{ __('messages.environments.title') }}</th>
                                    <th>{{ __('messages.monitors.test_suite') }}</th>
                                    <th>{{ __('messages.monitors.next_run') }}</th>
                                    <th>{{ __('messages.monitors.last_run') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.monitors.last_result') }}</th>
                                    <th>{{ __('messages.monitors.last_alert') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($monitors as $monitor)
                                <tr>
                                    <td>
                                        <strong>{{ $monitor->name }}</strong><br>
                                        <small class="text-muted">{{ $monitor->is_enabled ? __('messages.common.active') : __('messages.common.inactive') }}</small>
                                    </td>
                                    <td>{{ $monitor->frequency_label }}</td>
                                    <td>{{ $monitor->environment?->name ?: __('messages.endpoints.project_default') }}</td>
                                    <td>{{ $monitor->suite_label }}</td>
                                    <td>{{ $monitor->next_run_label }}</td>
                                    <td>{{ $monitor->last_run_label }}</td>
                                    <td><span class="label label-{{ $monitor->last_status_css }}">{{ $monitor->last_status_label }}</span></td>
                                    <td>
                                        {{ $monitor->last_message ?: __('messages.common.not_available') }}
                                        @if($monitor->lastScanRun)
                                            <br><a href="{{ route('projects.scans.show', [$project, $monitor->lastScanRun]) }}">{{ __('messages.monitors.open_last_scan') }}</a>
                                        @endif
                                        @if($monitor->lastCompareRun)
                                            · <a href="{{ route('projects.snapshots.compares.show', [$project, $monitor->lastCompareRun]) }}">{{ __('messages.monitors.open_last_compare') }}</a>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $monitor->last_alert_at?->format('Y-m-d H:i') ?: __('messages.common.not_available') }}
                                        @if($monitor->last_alert_status)
                                            <br><small class="text-muted">{{ __('messages.monitors.statuses.'.$monitor->last_alert_status) }}</small>
                                        @endif
                                        @if(($monitor->open_alert_events_count ?? 0) > 0)
                                            <br><span class="label label-warning">{{ __('messages.monitors.open_alerts_count', ['count' => $monitor->open_alert_events_count]) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('projects.monitors.run', [$project, $monitor]) }}" class="aptoria-inline-form" style="display:inline" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.monitors.run_now') }}" data-aptoria-confirm-text="{{ __('messages.monitors.run_now_confirm') }}" data-aptoria-confirm-type="info" data-aptoria-confirm-button="{{ __('messages.monitors.run_now') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-success">{{ __('messages.monitors.run_now') }}</button>
                                        </form>
                                        <a href="{{ route('projects.monitors.alerts', [$project, $monitor]) }}" class="btn btn-xs btn-default">{{ __('messages.monitors.alert_history_short') }}</a>
                                        <a href="{{ route('calendar.create', ['project_id' => $project->id, 'api_monitor_id' => $monitor->id, 'event_type' => \App\Models\CalendarEvent::TYPE_MONITOR_RUN]) }}" class="btn btn-xs btn-default">{{ __('messages.nav.calendar') }}</a>
                                        <a href="{{ route('projects.monitors.edit', [$project, $monitor]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                                        <form method="POST" action="{{ route('projects.monitors.destroy', [$project, $monitor]) }}" style="display:inline" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.monitors.delete_confirm') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger">{{ __('messages.common.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $monitors->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
