@extends('layouts.app')

@section('title', __('messages.contract_reality.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel h{{ $reality['css'] === 'danger' ? 'red' : ($reality['css'] === 'warning' ? 'yellow' : ($reality['css'] === 'success' ? 'green' : 'default')) }}">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.contract-validations.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.contract_reality.run_validation') }}</a>
                    @if($reality['run'])
                        <a href="{{ route('projects.contract-validations.show', [$project, $reality['run']]) }}" class="btn btn-xs btn-default">{{ __('messages.contract_reality.open_validation') }}</a>
                    @endif
                </div>
                {{ __('messages.contract_reality.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.contract_reality.intro') }}</p>
                <div class="row text-center">
                    <div class="col-sm-2"><h3><span class="label label-{{ $reality['css'] }}">{{ $reality['label'] }}</span></h3><small>{{ __('messages.common.status') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $reality['summary']['breaking_contract_mismatch'] }}</h3><small>{{ __('messages.contract_reality.blocking') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $reality['summary']['auth_contract_mismatch'] }}</h3><small>{{ __('messages.contract_reality.auth_contract_mismatch') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $reality['summary']['undocumented_response'] }}</h3><small>{{ __('messages.contract_reality.undocumented_response') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $reality['summary']['missing_documented_endpoint'] }}</h3><small>{{ __('messages.contract_reality.missing_documented_endpoint') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $reality['summary']['undocumented_endpoint'] }}</h3><small>{{ __('messages.contract_reality.undocumented_endpoint') }}</small></div>
                </div>
                <hr>
                @if(! $reality['run'])
                    <p class="text-muted m-b-none">{{ __('messages.contract_reality.no_run') }}</p>
                @else
                    <dl class="dl-horizontal m-b-none">
                        <dt>{{ __('messages.contract_reality.latest_run') }}</dt>
                        <dd><a href="{{ route('projects.contract-validations.show', [$project, $reality['run']]) }}">#{{ $reality['run']->id }}</a> — {{ $reality['run']->health_label }}</dd>
                        <dt>{{ __('messages.contract_validations.source_name') }}</dt>
                        <dd>{{ $reality['run']->source_name ?: __('messages.contract_validations.manual_source') }}</dd>
                        <dt>{{ __('messages.common.created') }}</dt>
                        <dd>{{ $reality['run']->created_at->format('Y-m-d H:i:s') }}</dd>
                    </dl>
                @endif
            </div>
        </div>
    </div>
</div>

@if($reality['run'])
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.contract_reality.summary_title') }}</div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <tbody>
                            <tr><th>{{ __('messages.contract_reality.matches_contract') }}</th><td class="text-right">{{ $reality['summary']['matches_contract'] }}</td></tr>
                            <tr><th>{{ __('messages.contract_reality.contract_drift') }}</th><td class="text-right">{{ $reality['summary']['contract_drift'] }}</td></tr>
                            <tr><th>{{ __('messages.contract_reality.auth_contract_mismatch') }}</th><td class="text-right">{{ $reality['summary']['auth_contract_mismatch'] }}</td></tr>
                            <tr><th>{{ __('messages.contract_reality.undocumented_response') }}</th><td class="text-right">{{ $reality['summary']['undocumented_response'] }}</td></tr>
                            <tr><th>{{ __('messages.contract_reality.missing_documented_endpoint') }}</th><td class="text-right">{{ $reality['summary']['missing_documented_endpoint'] }}</td></tr>
                            <tr><th>{{ __('messages.contract_reality.undocumented_endpoint') }}</th><td class="text-right">{{ $reality['summary']['undocumented_endpoint'] }}</td></tr>
                            <tr><th>{{ __('messages.contract_reality.breaking_contract_mismatch') }}</th><td class="text-right">{{ $reality['summary']['breaking_contract_mismatch'] }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.contract_reality.results_title') }}</div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('messages.endpoints.method') }}</th>
                                <th>{{ __('messages.endpoints.path') }}</th>
                                <th>{{ __('messages.contract_reality.type') }}</th>
                                <th>{{ __('messages.contract_validations.check_type') }}</th>
                                <th>{{ __('messages.contract_validations.severity') }}</th>
                                <th>{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.contract_validations.message') }}</th>
                                <th>{{ __('messages.contract_validations.expected_label') }}</th>
                                <th>{{ __('messages.contract_validations.actual_label') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($reality['rows'] as $row)
                            <tr>
                                <td>@if($row['method'])<span class="label label-default">{{ $row['method'] }}</span>@endif</td>
                                <td>
                                    @if($row['endpoint'])
                                        <a href="{{ route('projects.endpoints.show', [$project, $row['endpoint']]) }}"><code>{{ $row['path'] }}</code></a>
                                    @else
                                        <code>{{ $row['path'] ?: __('messages.common.not_available') }}</code>
                                    @endif
                                </td>
                                <td>{{ $row['reality_label'] }}</td>
                                <td>{{ $row['check_type_label'] }}</td>
                                <td><span class="label label-{{ $row['severity_css'] }}">{{ $row['severity_label'] }}</span></td>
                                <td><span class="label label-{{ $row['status_css'] }}">{{ $row['status_label'] }}</span></td>
                                <td>{{ $row['message'] }}</td>
                                <td><small>{{ $row['expected'] ?: __('messages.common.not_available') }}</small></td>
                                <td><small>{{ $row['actual'] ?: __('messages.common.not_available') }}</small></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
