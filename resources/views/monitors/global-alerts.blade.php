@extends('layouts.app')

@section('title', __('messages.monitors.global_alerts'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('monitors.index') }}" class="btn btn-xs btn-default">{{ __('messages.nav.monitors') }}</a>
                </div>
                {{ __('messages.monitors.global_alerts') }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.monitors.global_alerts_intro') }}</p>
                <form method="GET" action="{{ route('monitors.alerts.index') }}" class="row">
                    <div class="col-md-3">
                        <select name="channel" class="form-control">
                            <option value="">{{ __('messages.monitors.all_channels') }}</option>
                            @foreach(['dashboard', 'email', 'webhook'] as $channel)
                                <option value="{{ $channel }}" @selected(request('channel') === $channel)>{{ $channel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="severity" class="form-control">
                            <option value="">{{ __('messages.monitors.all_severities') }}</option>
                            @foreach(['critical', 'warning', 'recovery', 'info'] as $severity)
                                <option value="{{ $severity }}" @selected(request('severity') === $severity)>{{ $severity }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="checkbox-inline"><input type="checkbox" name="open" value="1" @checked(request()->boolean('open'))> {{ __('messages.monitors.only_open_alerts') }}</label>
                    </div>
                    <div class="col-md-3 text-right">
                        <button type="submit" class="btn btn-primary">{{ __('messages.common.filter') }}</button>
                        <a href="{{ route('monitors.alerts.index') }}" class="btn btn-default">{{ __('messages.common.reset') }}</a>
                    </div>
                </form>
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
                                <th>{{ __('messages.nav.projects') }}</th>
                                <th>{{ __('messages.nav.monitors') }}</th>
                                <th>{{ __('messages.monitors.channel') }}</th>
                                <th>{{ __('messages.monitors.severity') }}</th>
                                <th>{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.monitors.message') }}</th>
                                <th>{{ __('messages.monitors.delivery_status') }}</th>
                                <th>{{ __('messages.monitors.acknowledgement') }}</th>
                                <th class="text-right">{{ __('messages.common.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($alerts as $alert)
                                <tr>
                                    <td>{{ $alert->created_at?->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        @if($alert->project)
                                            <a href="{{ route('projects.show', $alert->project) }}">{{ $alert->project->name }}</a>
                                        @else
                                            {{ __('messages.common.not_available') }}
                                        @endif
                                    </td>
                                    <td>{{ $alert->monitor?->name ?: __('messages.common.not_available') }}</td>
                                    <td><span class="label label-default">{{ $alert->channel }}</span></td>
                                    <td>{{ $alert->severity }}</td>
                                    <td>{{ $alert->status }}</td>
                                    <td>
                                        {{ $alert->message ?: __('messages.common.not_available') }}
                                        @if(is_array($alert->payload_json) && filled($alert->payload_json['trigger_summary'] ?? null))
                                            <br><small class="text-muted">{{ __('messages.monitors.trigger_summary') }}: {{ $alert->payload_json['trigger_summary'] }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $alert->delivery_status }}</strong>
                                        @if($alert->delivery_message)<br><small class="text-muted">{{ $alert->delivery_message }}</small>@endif
                                    </td>
                                    <td>
                                        @if($alert->acknowledged_at)
                                            <span class="label label-success">{{ __('messages.monitors.acknowledged') }}</span>
                                        @else
                                            <span class="label label-warning">{{ __('messages.monitors.open_alert') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($alert->project && $alert->monitor)
                                            <a href="{{ route('projects.monitors.alerts', [$alert->project, $alert->monitor]) }}" class="btn btn-xs btn-default">{{ __('messages.monitors.alert_history_short') }}</a>
                                        @endif
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
