@extends('layouts.app')

@section('title', __('messages.findings.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.findings.create', $project) }}" class="btn btn-xs btn-danger">{{ __('messages.findings.create') }}</a>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.findings.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <div class="row text-center m-b-md">
                    <div class="col-sm-3"><h3>{{ $summary['total'] }}</h3><small>{{ __('messages.findings.total') }}</small></div>
                    <div class="col-sm-3"><h3>{{ $summary['open'] }}</h3><small>{{ __('messages.findings.open_findings') }}</small></div>
                    <div class="col-sm-3"><h3>{{ $summary['critical'] }}</h3><small>{{ __('messages.findings.critical_open') }}</small></div>
                    <div class="col-sm-3"><h3>{{ $summary['high'] }}</h3><small>{{ __('messages.findings.high_open') }}</small></div>
                </div>
                <div class="row text-center m-b-md">
                    <div class="col-sm-3"><span class="label label-success">{{ $summary['fixed'] }}</span><br><small>{{ __('messages.findings.statuses.fixed') }}</small></div>
                    <div class="col-sm-3"><span class="label label-warning">{{ $summary['accepted_risk'] }}</span><br><small>{{ __('messages.findings.statuses.accepted_risk') }}</small></div>
                    <div class="col-sm-3"><span class="label label-default">{{ $summary['false_positive'] }}</span><br><small>{{ __('messages.findings.statuses.false_positive') }}</small></div>
                    <div class="col-sm-3"><span class="label label-danger">{{ $summary['reopened'] }}</span><br><small>{{ __('messages.findings.statuses.reopened') }}</small></div>
                </div>

                <form method="GET" action="{{ route('projects.findings.index', $project) }}" class="row m-b-md">
                    <div class="col-sm-3">
                        <select name="status" class="form-control">
                            <option value="open" @selected($status === 'open')>{{ __('messages.findings.open_findings') }}</option>
                            <option value="all" @selected($status === 'all')>{{ __('messages.findings.all_findings') }}</option>
                            @foreach(\App\Models\Finding::LIFECYCLE_STATUSES as $rowStatus)
                                <option value="{{ $rowStatus }}" @selected($status === $rowStatus)>{{ __('messages.findings.statuses.'.$rowStatus) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <select name="severity" class="form-control">
                            <option value="">{{ __('messages.findings.all_severities') }}</option>
                            @foreach(\App\Models\Finding::SEVERITIES as $rowSeverity)
                                <option value="{{ $rowSeverity }}" @selected($severity === $rowSeverity)>{{ __('messages.findings.severities.'.$rowSeverity) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <select name="source" class="form-control">
                            <option value="">{{ __('messages.findings.all_sources') }}</option>
                            @foreach(\App\Models\Finding::SOURCES as $rowSource)
                                <option value="{{ $rowSource }}" @selected($source === $rowSource)>{{ __('messages.findings.sources.'.$rowSource) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <button type="submit" class="btn btn-primary btn-block">{{ __('messages.common.filter') }}</button>
                    </div>
                </form>

                @if($findings->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.findings.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.findings.empty_help') }}</p>
                        <a href="{{ route('projects.findings.create', $project) }}" class="btn btn-danger">{{ __('messages.findings.create') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.findings.title_field') }}</th>
                                    <th>{{ __('messages.findings.severity') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.findings.source') }}</th>
                                    <th>{{ __('messages.endpoints.title') }}</th>
                                    <th>{{ __('messages.findings.evidence') }}</th>
                                    <th>{{ __('messages.findings.lifecycle.last_changed') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($findings as $finding)
                                <tr>
                                    <td><strong>{{ $finding->title }}</strong><br><small class="text-muted">{{ \Illuminate\Support\Str::limit($finding->description, 120) }}</small></td>
                                    <td><span class="label label-{{ $finding->severity_css }}">{{ $finding->severity_label }}</span></td>
                                    <td><span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span></td>
                                    <td>{{ $finding->source_label }}</td>
                                    <td>@if($finding->endpoint)<a href="{{ route('projects.endpoints.show', [$project, $finding->endpoint]) }}"><code>{{ $finding->endpoint->method }} {{ $finding->endpoint->path }}</code></a>@else<span class="text-muted">{{ __('messages.common.none') }}</span>@endif</td>
                                    <td>{{ $finding->evidence->count() }}</td>
                                    <td>{{ $finding->lifecycle_changed_at?->format('Y-m-d H:i') ?: __('messages.common.not_available') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('projects.findings.show', [$project, $finding]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a>
                                        <a href="{{ route('projects.findings.edit', [$project, $finding]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $findings->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
