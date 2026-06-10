@extends('layouts.app')

@section('title', __('messages.monitors.overview_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('monitors.alerts.index', ['open' => 1]) }}" class="btn btn-xs btn-warning">{{ __('messages.monitors.open_alerts') }}</a>
                    <a href="{{ route('projects.index') }}" class="btn btn-xs btn-success">{{ __('messages.monitors.create_from_project') }}</a>
                </div>
                <i class="fa fa-clock-o"></i> {{ __('messages.monitors.overview_title') }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.monitors.overview_intro') }}</p>
                <div class="alert alert-info m-b-none">
                    <strong>{{ __('messages.monitors.scheduler_command') }}</strong><br>
                    <code>C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --limit=50 --save-json</code><br><code>php artisan aptoria:run-monitors --dry-run --json</code>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.monitors.all_monitors') }}</div>
            <div class="panel-body no-padding">
                @if($monitors->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.monitors.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.monitors.empty_help_global') }}</p>
                        <a href="{{ route('projects.index') }}" class="btn btn-success">{{ __('messages.nav.all_projects') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead>
                            <tr>
                                <th>{{ __('messages.common.name') }}</th>
                                <th>{{ __('messages.nav.projects') }}</th>
                                <th>{{ __('messages.monitors.frequency') }}</th>
                                <th>{{ __('messages.environments.title') }}</th>
                                <th>{{ __('messages.monitors.test_suite') }}</th>
                                <th>{{ __('messages.monitors.next_run') }}</th>
                                <th>{{ __('messages.monitors.last_run') }}</th>
                                <th>{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.monitors.last_alert') }}</th>
                                <th class="text-right">{{ __('messages.common.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($monitors as $monitor)
                                <tr>
                                    <td><strong>{{ $monitor->name }}</strong><br><small class="text-muted">{{ $monitor->is_enabled ? __('messages.common.active') : __('messages.common.inactive') }}</small></td>
                                    <td>@if($monitor->project)<a href="{{ route('projects.show', $monitor->project) }}">{{ $monitor->project->name }}</a>@else {{ __('messages.common.not_available') }} @endif</td>
                                    <td>{{ $monitor->frequency_label }}</td>
                                    <td>{{ $monitor->environment?->name ?: __('messages.endpoints.project_default') }}</td>
                                    <td>{{ $monitor->suite_label }}</td>
                                    <td>{{ $monitor->next_run_label }}</td>
                                    <td>{{ $monitor->last_run_label }}</td>
                                    <td><span class="label label-{{ $monitor->last_status_css }}">{{ $monitor->last_status_label }}</span></td>
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
                                        @if($monitor->project)
                                            <a href="{{ route('projects.monitors.index', $monitor->project) }}" class="btn btn-xs btn-primary">{{ __('messages.monitors.open_project_monitors') }}</a>
                                            <a href="{{ route('projects.monitors.alerts', [$monitor->project, $monitor]) }}" class="btn btn-xs btn-default">{{ __('messages.monitors.alert_history_short') }}</a>
                                            <a href="{{ route('projects.monitors.edit', [$monitor->project, $monitor]) }}" class="btn btn-xs btn-default">{{ __('messages.common.edit') }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-footer">{{ $monitors->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
