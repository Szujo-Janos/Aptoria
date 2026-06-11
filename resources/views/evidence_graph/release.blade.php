@extends('layouts.app')

@section('title', __('messages.evidence_graph.release_graph'))

@section('content')
<div class="row"><div class="col-lg-12"><div class="hpanel hblue"><div class="panel-heading hbuilt"><div class="panel-tools"><a href="{{ route('projects.evidence-graph.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a><a href="{{ route('projects.release-decisions.index', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.release_decisions.short_title') }}</a></div>{{ __('messages.evidence_graph.release_graph') }}</div><div class="panel-body"><h3 class="m-t-none">{{ $project->name }}</h3><p class="text-muted m-b-none">{{ __('messages.evidence_graph.release_graph_intro') }}</p></div></div></div></div>

<div class="row">@foreach($releaseGraph['graph_nodes'] as $node)<div class="col-md-2"><div class="hpanel"><div class="panel-body text-center"><span class="label label-{{ $node['css'] }}">{{ $node['type'] }}</span><h5>{{ $node['label'] }}</h5></div></div></div>@endforeach</div>

<div class="row">
    <div class="col-lg-6"><div class="hpanel"><div class="panel-heading hbuilt">{{ __('messages.evidence_graph.release_scope_label') }}</div><div class="panel-body"><dl class="dl-horizontal"><dt>{{ __('messages.scans.latest') }}</dt><dd>{{ $releaseGraph['latest_scan']?->id ?: __('messages.common.not_available') }}</dd><dt>{{ __('messages.nav.snapshots') }}</dt><dd>{{ $releaseGraph['latest_snapshot']?->name ?: __('messages.common.not_available') }}</dd><dt>{{ __('messages.dashboard.compare_runs') }}</dt><dd>{{ $releaseGraph['latest_compare']?->id ?: __('messages.common.not_available') }}</dd><dt>{{ __('messages.release_gates.title') }}</dt><dd>{{ $releaseGraph['latest_gate']?->release_name ?: __('messages.common.not_available') }}</dd><dt>{{ __('messages.release_decisions.short_title') }}</dt><dd>{{ $releaseGraph['latest_decision']?->status_label ?: __('messages.common.not_available') }}</dd></dl></div></div></div>
    <div class="col-lg-6"><div class="hpanel hyellow"><div class="panel-heading hbuilt">{{ __('messages.evidence_graph.missing_links') }}</div><div class="panel-body">@if($releaseGraph['missing_links']->isEmpty())<p class="text-muted m-b-none">{{ __('messages.evidence_graph.no_missing_links') }}</p>@else<ul class="m-b-none">@foreach($releaseGraph['missing_links'] as $missing)<li>{{ $missing }}</li>@endforeach</ul>@endif</div></div></div>
</div>
@endsection
