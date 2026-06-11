@extends('layouts.app')

@section('title', __('messages.evidence_graph.finding_chain_title'))

@section('content')
<div class="row"><div class="col-lg-12"><div class="hpanel hblue"><div class="panel-heading hbuilt"><div class="panel-tools"><a href="{{ route('projects.evidence-graph.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a></div>{{ __('messages.evidence_graph.finding_chain_title') }}</div><div class="panel-body"><h3 class="m-t-none">{{ $finding->title }}</h3><p><span class="label label-{{ $finding->severity_css }}">{{ $finding->severity_label }}</span> <span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span></p><p class="text-muted m-b-none">{{ __('messages.evidence_graph.finding_chain_intro') }}</p></div></div></div></div>

<div class="row">@foreach($chain['graph_nodes'] as $node)<div class="col-md-2"><div class="hpanel"><div class="panel-body text-center"><span class="label label-{{ $node['css'] }}">{{ $node['type'] }}</span><h5>{{ $node['label'] }}</h5></div></div></div>@endforeach</div>

<div class="row">
    <div class="col-lg-7"><div class="hpanel"><div class="panel-heading hbuilt">{{ __('messages.evidence_graph.linked_evidence') }}</div><div class="panel-body"><dl class="dl-horizontal"><dt>{{ __('messages.evidence_graph.endpoint') }}</dt><dd>{{ $finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path : __('messages.common.not_available') }}</dd><dt>{{ __('messages.scans.title') }}</dt><dd>{{ $chain['has_scan_link'] ? __('messages.common.yes') : __('messages.common.no') }}</dd><dt>{{ __('messages.contract_validations.title') }}</dt><dd>{{ $chain['has_contract_link'] ? __('messages.common.yes') : __('messages.common.no') }}</dd><dt>{{ __('messages.release_gates.title') }}</dt><dd>{{ $chain['has_release_gate_link'] ? __('messages.common.yes') : __('messages.common.no') }}</dd><dt>{{ __('messages.risk_acceptances.title') }}</dt><dd>{{ $chain['has_active_risk_acceptance'] ? __('messages.common.yes') : __('messages.common.no') }}</dd><dt>{{ __('messages.evidence_graph.metrics.evidence') }}</dt><dd>{{ $chain['evidence_count'] }}</dd></dl></div></div></div>
    <div class="col-lg-5"><div class="hpanel hyellow"><div class="panel-heading hbuilt">{{ __('messages.evidence_graph.missing_links') }}</div><div class="panel-body">@if($chain['missing_links']->isEmpty())<p class="text-muted m-b-none">{{ __('messages.evidence_graph.no_missing_links') }}</p>@else<ul class="m-b-none">@foreach($chain['missing_links'] as $missing)<li>{{ $missing }}</li>@endforeach</ul>@endif</div></div></div>
</div>

<div class="row">
    <div class="col-lg-12"><div class="hpanel"><div class="panel-heading hbuilt">{{ __('messages.findings.evidence') }}</div><div class="panel-body">@if($finding->evidence->isEmpty())<p class="text-muted m-b-none">{{ __('messages.findings.no_evidence') }}</p>@else<div class="table-responsive"><table class="table table-striped table-bordered table-hover"><thead><tr><th>{{ __('messages.common.type') }}</th><th>{{ __('messages.findings.evidence_summary') }}</th><th>{{ __('messages.findings.captured_at') }}</th></tr></thead><tbody>@foreach($finding->evidence as $evidence)<tr><td>{{ $evidence->type_label }}</td><td>{{ $evidence->summary }}</td><td>{{ $evidence->captured_at?->format('Y-m-d H:i') ?: $evidence->created_at->format('Y-m-d H:i') }}</td></tr>@endforeach</tbody></table></div>@endif</div></div></div>
</div>
@endsection
