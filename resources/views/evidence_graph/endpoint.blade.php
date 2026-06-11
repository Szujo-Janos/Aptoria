@extends('layouts.app')

@section('title', __('messages.evidence_graph.endpoint_map_title'))

@section('content')
<div class="row"><div class="col-lg-12"><div class="hpanel hblue"><div class="panel-heading hbuilt"><div class="panel-tools"><a href="{{ route('projects.evidence-graph.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a></div>{{ __('messages.evidence_graph.endpoint_map_title') }}</div><div class="panel-body"><h3 class="m-t-none"><span class="label label-default">{{ $endpoint->method }}</span> <code>{{ $endpoint->path }}</code></h3><p class="text-muted m-b-none">{{ __('messages.evidence_graph.endpoint_map_intro') }}</p></div></div></div></div>

<div class="row">
    @foreach($map['graph_nodes'] as $node)
        <div class="col-md-2"><div class="hpanel"><div class="panel-body text-center"><span class="label label-{{ $node['css'] }}">{{ $node['type'] }}</span><h5>{{ $node['label'] }}</h5></div></div></div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-6"><div class="hpanel"><div class="panel-heading hbuilt">{{ __('messages.evidence_graph.linked_evidence') }}</div><div class="panel-body"><dl class="dl-horizontal"><dt>{{ __('messages.evidence_graph.metrics.scan_results') }}</dt><dd>{{ $map['scan_results_count'] }}</dd><dt>{{ __('messages.assertions.title') }}</dt><dd>{{ $map['assertion_rules_count'] }}</dd><dt>{{ __('messages.test_cases.title') }}</dt><dd>{{ $map['test_cases_count'] }}</dd><dt>{{ __('messages.contract_validations.title') }}</dt><dd>{{ $map['contract_results_count'] }}</dd><dt>{{ __('messages.findings.title') }}</dt><dd>{{ $map['findings_count'] }}</dd><dt>{{ __('messages.evidence_graph.metrics.evidence') }}</dt><dd>{{ $map['evidence_count'] }}</dd></dl></div></div></div>
    <div class="col-lg-6"><div class="hpanel hyellow"><div class="panel-heading hbuilt">{{ __('messages.evidence_graph.missing_links') }}</div><div class="panel-body">@if($map['missing_links']->isEmpty())<p class="text-muted m-b-none">{{ __('messages.evidence_graph.no_missing_links') }}</p>@else<ul class="m-b-none">@foreach($map['missing_links'] as $missing)<li>{{ $missing }}</li>@endforeach</ul>@endif</div></div></div>
</div>
@endsection
