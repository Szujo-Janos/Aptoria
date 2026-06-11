@extends('layouts.app')

@section('title', __('messages.blind_spots.title'))

@section('content')
@php
    $counts = $summary['summary'];
    $metricCards = [
        ['label' => __('messages.blind_spots.summary.critical_blind_spots'), 'value' => $counts['critical'], 'panel' => 'hred', 'icon' => 'fa-exclamation-triangle'],
        ['label' => __('messages.blind_spots.summary.high_blind_spots'), 'value' => $counts['high'], 'panel' => 'hyellow', 'icon' => 'fa-warning'],
        ['label' => __('messages.blind_spots.summary.stale_evidence'), 'value' => $counts['stale_evidence'], 'panel' => 'hblue', 'icon' => 'fa-clock-o'],
        ['label' => __('messages.blind_spots.summary.untested_endpoints'), 'value' => $counts['untested_endpoints'], 'panel' => 'hred', 'icon' => 'fa-plug'],
        ['label' => __('messages.blind_spots.summary.unverified_fixes'), 'value' => $counts['unverified_fixes'], 'panel' => 'hyellow', 'icon' => 'fa-check-square-o'],
        ['label' => __('messages.blind_spots.summary.expiring_accepted_risks'), 'value' => $counts['expired_accepted_risks'] + $counts['risk_without_expiry'], 'panel' => 'hblue', 'icon' => 'fa-calendar-times-o'],
    ];
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.release-readiness.show', $project) }}" class="btn btn-xs btn-default"><i class="fa fa-check-circle"></i> {{ __('messages.nav.release_readiness_short') }}</a>
                </div>
                {{ __('messages.blind_spots.heading') }}
            </div>
            <div class="panel-body">
                <p class="text-muted m-b-none">{{ __('messages.blind_spots.intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @foreach($metricCards as $card)
        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="hpanel {{ $card['panel'] }}">
                <div class="panel-body text-center">
                    <i class="fa {{ $card['icon'] }} fa-2x text-muted"></i>
                    <h2 class="m-xs">{{ $card['value'] }}</h2>
                    <small>{{ $card['label'] }}</small>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.blind_spots.filters.title') }}</div>
            <div class="panel-body">
                <form method="GET" class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="severity">{{ __('messages.blind_spots.severity') }}</label>
                            <select name="severity" id="severity" class="form-control">
                                <option value="">{{ __('messages.blind_spots.filters.all_severities') }}</option>
                                @foreach($filterOptions['severities'] as $severity)
                                    <option value="{{ $severity }}" @selected($filters['severity'] === $severity)>{{ __('messages.blind_spots.severities.'.$severity) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="category">{{ __('messages.blind_spots.category') }}</label>
                            <select name="category" id="category" class="form-control">
                                <option value="">{{ __('messages.blind_spots.filters.all_categories') }}</option>
                                @foreach($filterOptions['categories'] as $category)
                                    <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ __('messages.blind_spots.categories.'.$category) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="module">{{ __('messages.blind_spots.module') }}</label>
                            <select name="module" id="module" class="form-control">
                                <option value="">{{ __('messages.blind_spots.filters.all_modules') }}</option>
                                @foreach($filterOptions['modules'] as $module)
                                    <option value="{{ $module }}" @selected($filters['module'] === $module)>{{ __('messages.blind_spots.modules.'.$module) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="blockers" value="1" @checked($filters['blockers'])> {{ __('messages.blind_spots.filters.only_release_blockers') }}
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> {{ __('messages.common.filter') }}</button>
                        <a href="{{ route('projects.blind-spots.index', $project) }}" class="btn btn-default btn-sm">{{ __('messages.common.reset') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                {{ __('messages.blind_spots.table.title') }}
                <span class="label label-info pull-right">{{ $items->count() }} / {{ $counts['total'] }}</span>
            </div>
            <div class="panel-body">
                @if($items->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.blind_spots.no_items') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.blind_spots.type') }}</th>
                                    <th>{{ __('messages.blind_spots.severity') }}</th>
                                    <th>{{ __('messages.blind_spots.module') }}</th>
                                    <th>{{ __('messages.blind_spots.affected') }}</th>
                                    <th>{{ __('messages.blind_spots.reason') }}</th>
                                    <th>{{ __('messages.blind_spots.suggested_action') }}</th>
                                    <th>{{ __('messages.blind_spots.detected_at') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td>
                                        <strong>{{ $item['type_label'] }}</strong>
                                        <div><small class="text-muted">{{ $item['category_label'] }}</small></div>
                                        @if($item['release_blocker'])
                                            <span class="label label-danger">{{ __('messages.blind_spots.release_blocker') }}</span>
                                        @endif
                                    </td>
                                    <td><span class="label label-{{ $item['severity_css'] }}">{{ $item['severity_label'] }}</span></td>
                                    <td>{{ $item['module_label'] }}</td>
                                    <td>
                                        @if($item['related_endpoint'])
                                            <a href="{{ route('projects.endpoints.show', [$project, $item['related_endpoint']]) }}"><code>{{ $item['related_label'] }}</code></a>
                                        @elseif($item['related_finding'])
                                            <a href="{{ route('projects.findings.show', [$project, $item['related_finding']]) }}">{{ $item['related_label'] }}</a>
                                        @elseif($item['related_scan_run'])
                                            <a href="{{ route('projects.scans.show', [$project, $item['related_scan_run']]) }}">{{ $item['related_label'] }}</a>
                                        @elseif($item['related_release_gate'])
                                            <a href="{{ route('projects.release-gates.show', [$project, $item['related_release_gate']]) }}">{{ $item['related_label'] }}</a>
                                        @else
                                            {{ $item['related_label'] }}
                                        @endif
                                    </td>
                                    <td>{{ $item['reason'] }}</td>
                                    <td>{{ $item['suggested_action'] }}</td>
                                    <td>{{ $item['detected_at']->format('Y-m-d H:i') }}</td>
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
