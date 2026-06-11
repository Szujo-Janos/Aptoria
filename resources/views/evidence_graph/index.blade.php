@extends('layouts.app')

@section('title', __('messages.evidence_graph.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.evidence-graph.release', $project) }}" class="btn btn-xs btn-primary"><i class="fa fa-flag-checkered"></i> {{ __('messages.evidence_graph.release_graph') }}</a>
                    <a href="{{ route('projects.reports.builder.create', $project) }}" class="btn btn-xs btn-default"><i class="fa fa-file-text-o"></i> {{ __('messages.report_builder.short_title') }}</a>
                </div>
                {{ __('messages.evidence_graph.heading') }}
            </div>
            <div class="panel-body">
                <h3 class="m-t-none">{{ $project->name }}</h3>
                <p class="text-muted m-b-none">{{ __('messages.evidence_graph.intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @foreach([
        ['label' => __('messages.evidence_graph.metrics.endpoints'), 'value' => $summary['endpoints'], 'panel' => 'hblue', 'icon' => 'fa-plug'],
        ['label' => __('messages.evidence_graph.metrics.findings'), 'value' => $summary['findings'], 'panel' => 'hyellow', 'icon' => 'fa-bug'],
        ['label' => __('messages.evidence_graph.metrics.evidence'), 'value' => $summary['finding_evidence'], 'panel' => 'hgreen', 'icon' => 'fa-paperclip'],
        ['label' => __('messages.evidence_graph.metrics.release_decisions'), 'value' => $summary['release_decisions'], 'panel' => 'hviolet', 'icon' => 'fa-gavel'],
        ['label' => __('messages.evidence_graph.metrics.blind_spots'), 'value' => $summary['blind_spots'], 'panel' => $summary['blind_spots'] > 0 ? 'hred' : 'hgreen', 'icon' => 'fa-eye-slash'],
        ['label' => __('messages.evidence_graph.metrics.missing_links'), 'value' => $summary['missing_links'], 'panel' => $summary['missing_links'] > 0 ? 'hyellow' : 'hgreen', 'icon' => 'fa-chain-broken'],
    ] as $card)
        <div class="col-md-2">
            <div class="hpanel {{ $card['panel'] }}">
                <div class="panel-body text-center">
                    <i class="fa {{ $card['icon'] }} fa-2x m-b-sm"></i>
                    <h3>{{ $card['value'] }}</h3>
                    <small>{{ $card['label'] }}</small>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.evidence_graph.endpoint_maps') }}</div>
            <div class="panel-body">
                @if($endpoint_maps->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.endpoints.empty_title') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.endpoints.method') }}</th>
                                    <th>{{ __('messages.endpoints.path') }}</th>
                                    <th>{{ __('messages.evidence_graph.metrics.scan_results') }}</th>
                                    <th>{{ __('messages.findings.title') }}</th>
                                    <th>{{ __('messages.evidence_graph.metrics.evidence') }}</th>
                                    <th>{{ __('messages.evidence_graph.missing_links') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($endpoint_maps as $map)
                                @php($endpoint = $map['endpoint'])
                                <tr>
                                    <td><span class="label label-default">{{ $endpoint->method }}</span></td>
                                    <td><a href="{{ route('projects.endpoints.show', [$project, $endpoint]) }}">{{ $endpoint->path }}</a></td>
                                    <td>{{ $map['scan_results_count'] }}</td>
                                    <td>{{ $map['findings_count'] }}</td>
                                    <td>{{ $map['evidence_count'] }}</td>
                                    <td>
                                        @if($map['missing_links']->isEmpty())
                                            <span class="label label-success">{{ __('messages.evidence_graph.complete') }}</span>
                                        @else
                                            <span class="label label-warning">{{ $map['missing_links']->count() }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right"><a href="{{ route('projects.evidence-graph.endpoint', [$project, $endpoint]) }}" class="btn btn-xs btn-default">{{ __('messages.evidence_graph.open_map') }}</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.evidence_graph.missing_links') }}</div>
            <div class="panel-body">
                @if($missing_links->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.evidence_graph.no_missing_links') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead><tr><th>{{ __('messages.evidence_graph.scope') }}</th><th>{{ __('messages.evidence_graph.related') }}</th><th>{{ __('messages.evidence_graph.missing_label') }}</th></tr></thead>
                            <tbody>
                            @foreach($missing_links->take(20) as $missing)
                                <tr>
                                    <td><span class="label label-{{ $missing['severity_css'] }}">{{ $missing['scope'] }}</span></td>
                                    <td>{{ $missing['related'] }}</td>
                                    <td>{{ $missing['missing'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.evidence_graph.finding_chains') }}</div>
            <div class="panel-body">
                @if($finding_chains->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.findings.empty_title') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead><tr><th>{{ __('messages.findings.title_field') }}</th><th>{{ __('messages.findings.severity') }}</th><th>{{ __('messages.findings.status') }}</th><th>{{ __('messages.evidence_graph.endpoint') }}</th><th>{{ __('messages.evidence_graph.metrics.evidence') }}</th><th>{{ __('messages.evidence_graph.retest') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($finding_chains as $chain)
                                @php($finding = $chain['finding'])
                                <tr>
                                    <td><a href="{{ route('projects.findings.show', [$project, $finding]) }}">{{ $finding->title }}</a></td>
                                    <td><span class="label label-{{ $finding->severity_css }}">{{ $finding->severity_label }}</span></td>
                                    <td><span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span></td>
                                    <td>{{ $finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path : __('messages.common.not_available') }}</td>
                                    <td>{{ $chain['evidence_count'] }}</td>
                                    <td>{{ $chain['has_retest_evidence'] ? __('messages.common.yes') : __('messages.common.no') }}</td>
                                    <td class="text-right"><a href="{{ route('projects.evidence-graph.finding', [$project, $finding]) }}" class="btn btn-xs btn-default">{{ __('messages.evidence_graph.open_chain') }}</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
