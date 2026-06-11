@extends('layouts.app')

@section('title', __('messages.qa_cockpit.title'))

@section('content')
@php
    $queueLabels = [
        'blockers' => __('messages.qa_cockpit.queues.blockers'),
        'fixes_waiting_for_retest' => __('messages.qa_cockpit.queues.fixes_waiting_for_retest'),
        'accepted_risks_expiring' => __('messages.qa_cockpit.queues.accepted_risks_expiring'),
        'stale_scan_evidence' => __('messages.qa_cockpit.queues.stale_scan_evidence'),
        'stale_reports' => __('messages.qa_cockpit.queues.stale_reports'),
        'endpoints_without_evidence' => __('messages.qa_cockpit.queues.endpoints_without_evidence'),
        'release_candidates_needing_decision' => __('messages.qa_cockpit.queues.release_candidates_needing_decision'),
        'monitor_alerts' => __('messages.qa_cockpit.queues.monitor_alerts'),
    ];
@endphp
<div class="normalheader">
    <div class="hpanel">
        <div class="panel-body">
            <div class="pull-right">
                <a href="{{ route('projects.release-readiness.show', $project) }}" class="btn btn-primary btn-sm"><i class="fa fa-check-circle"></i> {{ __('messages.nav.release_readiness_short') }}</a>
                <a href="{{ route('projects.blind-spots.index', $project) }}" class="btn btn-default btn-sm"><i class="fa fa-eye-slash"></i> {{ __('messages.blind_spots.short_title') }}</a>
            </div>
            <h2 class="m-t-none">{{ __('messages.qa_cockpit.title') }}</h2>
            <p class="text-muted m-b-none">{{ __('messages.qa_cockpit.subtitle') }}</p>
        </div>
    </div>
</div>

<div class="content animate-panel">
    <div class="row">
        @foreach([
            ['key' => 'open_blockers', 'icon' => 'exclamation-triangle', 'css' => 'hred'],
            ['key' => 'fixes_waiting_for_retest', 'icon' => 'refresh', 'css' => 'hyellow'],
            ['key' => 'accepted_risks_expiring', 'icon' => 'balance-scale', 'css' => 'hyellow'],
            ['key' => 'stale_scans', 'icon' => 'clock-o', 'css' => 'hblue'],
            ['key' => 'stale_reports', 'icon' => 'file-text-o', 'css' => 'hblue'],
            ['key' => 'endpoints_without_evidence', 'icon' => 'sitemap', 'css' => 'hgreen'],
        ] as $card)
            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="hpanel {{ $card['css'] }}">
                    <div class="panel-body text-center">
                        <i class="fa fa-{{ $card['icon'] }} fa-2x text-muted"></i>
                        <h2 class="m-xs">{{ $metrics[$card['key']] }}</h2>
                        <small>{{ __('messages.qa_cockpit.metrics.'.$card['key']) }}</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="hpanel hred">
                <div class="panel-heading hbuilt">{{ __('messages.qa_cockpit.priority_queue') }}</div>
                <div class="panel-body p-none">
                    @if($queues['priority']->isEmpty())
                        <div class="p-md text-muted">{{ __('messages.qa_cockpit.empty_priority_queue') }}</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped m-b-none">
                                <thead>
                                <tr>
                                    <th>{{ __('messages.qa_cockpit.item') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.qa_cockpit.due_or_age') }}</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($queues['priority'] as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item['label'] }}</strong><br>
                                            <small class="text-muted">{{ $item['meta'] }}</small>
                                        </td>
                                        <td><span class="label label-{{ $item['css'] }}">{{ __('messages.qa_cockpit.kinds.'.$item['kind']) }}</span></td>
                                        <td><small>{{ $item['due_at'] instanceof \Carbon\CarbonInterface ? $item['due_at']->format('Y-m-d') : '—' }}</small></td>
                                        <td class="text-right"><a href="{{ $item['url'] }}" class="btn btn-xs btn-default">{{ __('messages.common.open') }}</a></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="row">
                @foreach(['blockers', 'fixes_waiting_for_retest', 'release_candidates_needing_decision', 'endpoints_without_evidence'] as $queueKey)
                    <div class="col-md-6">
                        <div class="hpanel hblue">
                            <div class="panel-heading hbuilt">{{ $queueLabels[$queueKey] }}</div>
                            <div class="panel-body">
                                @forelse($queues[$queueKey] as $item)
                                    <p class="border-bottom p-xs m-b-xs">
                                        <span class="label label-{{ $item['css'] }} pull-right">{{ __('messages.qa_cockpit.kinds.'.$item['kind']) }}</span>
                                        <a href="{{ $item['url'] }}"><strong>{{ $item['label'] }}</strong></a><br>
                                        <small class="text-muted">{{ $item['meta'] }}</small>
                                    </p>
                                @empty
                                    <p class="text-muted m-b-none">{{ __('messages.qa_cockpit.empty_queue') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-md-5">
            <div class="hpanel hgreen">
                <div class="panel-heading hbuilt">{{ __('messages.qa_cockpit.release_snapshot') }}</div>
                <div class="panel-body">
                    <div class="row text-center m-b-md">
                        <div class="col-xs-4"><h3>{{ $readiness['score'] }}</h3><small>{{ __('messages.release_readiness.score') }}</small></div>
                        <div class="col-xs-4"><h3>{{ is_countable($readiness['blocking_issues'] ?? null) ? count($readiness['blocking_issues']) : 0 }}</h3><small>{{ __('messages.release_readiness.blocking_issues') }}</small></div>
                        <div class="col-xs-4"><h3>{{ $blind_spots['summary']['release_blockers'] ?? 0 }}</h3><small>{{ __('messages.blind_spots.release_blockers') }}</small></div>
                    </div>
                    <dl class="dl-horizontal m-b-none">
                        <dt>{{ __('messages.scans.latest') }}</dt><dd>@if($latest['scan']) #{{ $latest['scan']->id }} — {{ $latest['scan']->status_label }} @else {{ __('messages.common.not_available') }} @endif</dd>
                        <dt>{{ __('messages.report_versions.latest_approved') }}</dt><dd>@if($latest['approved_report']) {{ $latest['approved_report']->title }} @else {{ __('messages.common.not_available') }} @endif</dd>
                        <dt>{{ __('messages.release_decisions.latest_decision') }}</dt><dd>@if($latest['release_decision']) {{ $latest['release_decision']->status_label }} @else {{ __('messages.common.not_available') }} @endif</dd>
                    </dl>
                </div>
            </div>

            <div class="hpanel hyellow">
                <div class="panel-heading hbuilt">{{ __('messages.qa_cockpit.top_blind_spots') }}</div>
                <div class="panel-body">
                    @forelse($queues['top_blind_spots'] as $blindSpot)
                        <p class="border-bottom p-xs m-b-xs">
                            <span class="label label-{{ $blindSpot['severity_css'] }} pull-right">{{ $blindSpot['severity_label'] }}</span>
                            <strong>{{ $blindSpot['type_label'] }}</strong><br>
                            <small class="text-muted">{{ $blindSpot['related_label'] }}</small>
                        </p>
                    @empty
                        <p class="text-muted m-b-none">{{ __('messages.qa_cockpit.no_blind_spots') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="hpanel hblue">
                <div class="panel-heading hbuilt">{{ __('messages.qa_cockpit.quick_actions.title') }}</div>
                <div class="panel-body">
                    @foreach($quick_actions as $action)
                        <a href="{{ $action['url'] }}" class="btn btn-default btn-sm m-r-xs m-b-xs"><i class="fa fa-{{ $action['icon'] }}"></i> {{ $action['label'] }}</a>
                    @endforeach
                </div>
            </div>

            <div class="hpanel hblue">
                <div class="panel-heading hbuilt">{{ __('messages.qa_cockpit.recent_risk_endpoints') }}</div>
                <div class="panel-body">
                    @forelse($queues['recent_risk_endpoints'] as $item)
                        <p class="border-bottom p-xs m-b-xs">
                            <span class="label label-{{ $item['css'] }} pull-right">{{ $item['meta'] }}</span>
                            <a href="{{ $item['url'] }}"><strong>{{ $item['label'] }}</strong></a>
                        </p>
                    @empty
                        <p class="text-muted m-b-none">{{ __('messages.qa_cockpit.empty_queue') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
