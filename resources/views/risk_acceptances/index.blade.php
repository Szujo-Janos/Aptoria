@extends('layouts.app')

@section('title', __('messages.risk_acceptances.title'))

@section('content')
@php
    $counts = $summary['summary'] ?? [];
    $cards = [
        ['label' => __('messages.risk_acceptances.metrics.active'), 'value' => $counts['active'] ?? 0, 'panel' => 'hgreen', 'icon' => 'fa-check-circle'],
        ['label' => __('messages.risk_acceptances.metrics.high_or_critical'), 'value' => $counts['active_high_or_critical'] ?? 0, 'panel' => 'hyellow', 'icon' => 'fa-warning'],
        ['label' => __('messages.risk_acceptances.metrics.without_expiry'), 'value' => $counts['without_expiry'] ?? 0, 'panel' => 'hyellow', 'icon' => 'fa-calendar-o'],
        ['label' => __('messages.risk_acceptances.metrics.expiring_soon'), 'value' => $counts['expiring_soon'] ?? 0, 'panel' => 'hblue', 'icon' => 'fa-hourglass-half'],
        ['label' => __('messages.risk_acceptances.metrics.expired'), 'value' => $counts['expired'] ?? 0, 'panel' => 'hred', 'icon' => 'fa-calendar-times-o'],
        ['label' => __('messages.risk_acceptances.metrics.total'), 'value' => $counts['total'] ?? 0, 'panel' => 'hblue', 'icon' => 'fa-balance-scale'],
    ];
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.findings.index', $project) }}" class="btn btn-xs btn-default"><i class="fa fa-bug"></i> {{ __('messages.findings.title') }}</a>
                    <a href="{{ route('projects.release-readiness.show', $project) }}" class="btn btn-xs btn-primary"><i class="fa fa-check-circle"></i> {{ __('messages.nav.release_readiness_short') }}</a>
                </div>
                {{ __('messages.risk_acceptances.heading') }}
            </div>
            <div class="panel-body">
                <p class="text-muted m-b-none">{{ __('messages.risk_acceptances.intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    @foreach($cards as $card)
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
            <div class="panel-heading hbuilt">{{ __('messages.risk_acceptances.filters.title') }}</div>
            <div class="panel-body">
                <form method="GET" class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">{{ __('messages.common.status') }}</label>
                            <select name="status" id="status" class="form-control">
                                <option value="all" @selected($status === 'all')>{{ __('messages.common.all') }}</option>
                                @foreach(\App\Models\RiskAcceptance::STATUSES as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ __('messages.risk_acceptances.statuses.'.$statusOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="severity">{{ __('messages.findings.severity') }}</label>
                            <select name="severity" id="severity" class="form-control">
                                <option value="">{{ __('messages.risk_acceptances.filters.all_severities') }}</option>
                                @foreach(\App\Models\Finding::SEVERITIES as $severityOption)
                                    <option value="{{ $severityOption }}" @selected($severity === $severityOption)>{{ __('messages.findings.severities.'.$severityOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="expiry">{{ __('messages.risk_acceptances.expiry_filter') }}</label>
                            <select name="expiry" id="expiry" class="form-control">
                                <option value="" @selected($expiry === '')>{{ __('messages.common.all') }}</option>
                                <option value="missing" @selected($expiry === 'missing')>{{ __('messages.risk_acceptances.without_expiry') }}</option>
                                <option value="soon" @selected($expiry === 'soon')>{{ __('messages.risk_acceptances.expiring_soon') }}</option>
                                <option value="expired" @selected($expiry === 'expired')>{{ __('messages.risk_acceptances.expired') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label><br>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> {{ __('messages.common.filter') }}</button>
                        <a href="{{ route('projects.risk-acceptances.index', $project) }}" class="btn btn-default btn-sm">{{ __('messages.common.reset') }}</a>
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
                {{ __('messages.risk_acceptances.table_title') }}
                <span class="label label-info pull-right">{{ $acceptances->total() }}</span>
            </div>
            <div class="panel-body">
                @if($acceptances->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.risk_acceptances.no_items') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.findings.title') }}</th>
                                    <th>{{ __('messages.findings.severity') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.risk_acceptances.accepted_by') }}</th>
                                    <th>{{ __('messages.risk_acceptances.accepted_until') }}</th>
                                    <th>{{ __('messages.risk_acceptances.release_scope') }}</th>
                                    <th>{{ __('messages.risk_acceptances.expiry_action') }}</th>
                                    <th>{{ __('messages.risk_acceptances.reason') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($acceptances as $acceptance)
                                <tr>
                                    <td>
                                        @if($acceptance->finding)
                                            <a href="{{ route('projects.findings.show', [$project, $acceptance->finding]) }}">{{ $acceptance->finding->title }}</a>
                                            @if($acceptance->finding->endpoint)
                                                <div><small class="text-muted"><code>{{ $acceptance->finding->endpoint->method }} {{ $acceptance->finding->endpoint->path }}</code></small></div>
                                            @endif
                                        @else
                                            <span class="text-muted">{{ __('messages.common.not_available') }}</span>
                                        @endif
                                    </td>
                                    <td><span class="label label-{{ $acceptance->finding?->severity_css ?: 'default' }}">{{ $acceptance->finding?->severity_label ?: __('messages.common.not_available') }}</span></td>
                                    <td>
                                        <span class="label label-{{ $acceptance->status_css }}">{{ __('messages.risk_acceptances.statuses.'.$acceptance->computed_status) }}</span>
                                        @if($acceptance->status === \App\Models\RiskAcceptance::STATUS_ACTIVE && ! $acceptance->has_expiry)
                                            <div><small class="text-warning">{{ __('messages.risk_acceptances.without_expiry') }}</small></div>
                                        @elseif($acceptance->expires_soon)
                                            <div><small class="text-warning">{{ __('messages.risk_acceptances.expiring_soon') }}</small></div>
                                        @endif
                                    </td>
                                    <td>{{ $acceptance->acceptedBy?->name ?: __('messages.common.not_available') }}</td>
                                    <td>{{ $acceptance->accepted_until?->format('Y-m-d') ?: __('messages.common.not_available') }}</td>
                                    <td>{{ $acceptance->release_scope ?: __('messages.common.not_available') }}</td>
                                    <td>{{ $acceptance->expiry_action_label }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($acceptance->reason, 140) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center m-t-md">{{ $acceptances->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
