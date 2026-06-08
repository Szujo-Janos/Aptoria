@extends('layouts.app')

@section('title', __('messages.monitors.alert_history'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.monitors.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                    <a href="{{ route('projects.monitors.edit', [$project, $monitor]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                </div>
                {{ __('messages.monitors.alert_history') }} — {{ $monitor->name }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.monitors.alert_history_intro') }}</p>
                <div class="row">
                    <div class="col-md-3">
                        <strong>{{ __('messages.nav.projects') }}</strong><br>
                        <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('messages.environments.title') }}</strong><br>
                        {{ $monitor->environment?->name ?: __('messages.endpoints.project_default') }}
                    </div>
                    <div class="col-md-2">
                        <strong>{{ __('messages.monitors.last_alert') }}</strong><br>
                        {{ $monitor->last_alert_at?->format('Y-m-d H:i') ?: __('messages.common.not_available') }}
                    </div>
                    <div class="col-md-2">
                        <strong>{{ __('messages.monitors.open_alerts') }}</strong><br>
                        <span class="label label-{{ ($monitor->open_alert_events_count ?? 0) > 0 ? 'warning' : 'success' }}">{{ $monitor->open_alert_events_count ?? 0 }}</span>
                    </div>
                    <div class="col-md-2">
                        <strong>{{ __('messages.monitors.alert_channels') }}</strong><br>
                        @if($monitor->notify_dashboard)<span class="label label-default">dashboard</span>@endif
                        @if($monitor->alert_email)<span class="label label-info">email</span>@endif
                        @if($monitor->alert_webhook_url)<span class="label label-primary">webhook</span>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.monitors.alert_events') }}</div>
            <div class="panel-body no-padding">
                @if($alerts->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.monitors.no_alerts_title') }}</h4>
                        <p class="text-muted">{{ __('messages.monitors.no_alerts_help') }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead>
                            <tr>
                                <th>{{ __('messages.common.created') }}</th>
                                <th>{{ __('messages.monitors.channel') }}</th>
                                <th>{{ __('messages.monitors.severity') }}</th>
                                <th>{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.monitors.delivery_status') }}</th>
                                <th>{{ __('messages.monitors.message') }}</th>
                                <th>{{ __('messages.monitors.delivered_at') }}</th>
                                <th>{{ __('messages.monitors.acknowledgement') }}</th>
                                <th>{{ __('messages.common.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($alerts as $alert)
                                <tr>
                                    <td>{{ $alert->created_at?->format('Y-m-d H:i:s') }}</td>
                                    <td><span class="label label-default">{{ $alert->channel }}</span></td>
                                    <td>{{ $alert->severity }}</td>
                                    <td>
                                        <span class="label label-{{ $alert->severity === 'critical' ? 'danger' : ($alert->severity === 'warning' ? 'warning' : 'success') }}">{{ $alert->status }}</span>
                                        @if($alert->previous_status)
                                            <br><small class="text-muted">{{ __('messages.monitors.previous_status') }}: {{ $alert->previous_status }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $alert->delivery_status }}</strong>
                                        @if($alert->delivery_message)
                                            <br><small class="text-muted">{{ $alert->delivery_message }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $alert->message ?: __('messages.common.not_available') }}</td>
                                    <td>{{ $alert->delivered_at?->format('Y-m-d H:i:s') ?: __('messages.common.not_available') }}</td>
                                    <td>
                                        @if($alert->acknowledged_at)
                                            <span class="label label-success">{{ __('messages.monitors.acknowledged') }}</span><br>
                                            <small class="text-muted">
                                                {{ $alert->acknowledged_at->format('Y-m-d H:i:s') }}
                                                @if($alert->acknowledger)
                                                    · {{ $alert->acknowledger->name }}
                                                @endif
                                            </small>
                                            @if($alert->acknowledgement_note)
                                                <br><small>{{ $alert->acknowledgement_note }}</small>
                                            @endif
                                        @else
                                            <span class="label label-warning">{{ __('messages.monitors.open_alert') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @unless($alert->acknowledged_at)
                                            <form method="POST" action="{{ route('projects.monitors.alerts.acknowledge', [$project, $monitor, $alert]) }}" class="m-b-xs">
                                                @csrf
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="acknowledgement_note" class="form-control" maxlength="1000" placeholder="{{ __('messages.monitors.acknowledgement_note_placeholder') }}">
                                                    <span class="input-group-btn">
                                                        <button type="submit" class="btn btn-primary">{{ __('messages.monitors.acknowledge') }}</button>
                                                    </span>
                                                </div>
                                            </form>
                                            <form method="POST" action="{{ route('projects.monitors.alerts.follow-up', [$project, $monitor, $alert]) }}">
                                                @csrf
                                                <div class="input-group input-group-sm">
                                                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ now()->addDay()->format('Y-m-d\TH:i') }}" required>
                                                    <input type="hidden" name="priority" value="high">
                                                    <span class="input-group-btn">
                                                        <button type="submit" class="btn btn-warning">{{ __('messages.calendar.follow_up') }}</button>
                                                    </span>
                                                </div>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('projects.monitors.alerts.follow-up', [$project, $monitor, $alert]) }}">
                                                @csrf
                                                <div class="input-group input-group-sm">
                                                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ now()->addDay()->format('Y-m-d\TH:i') }}" required>
                                                    <input type="hidden" name="priority" value="normal">
                                                    <span class="input-group-btn">
                                                        <button type="submit" class="btn btn-default">{{ __('messages.calendar.follow_up') }}</button>
                                                    </span>
                                                </div>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="panel-footer">{{ $alerts->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
