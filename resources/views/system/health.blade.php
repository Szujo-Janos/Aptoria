@extends('layouts.app')

@section('title', __('messages.system_health.heading'))

@section('content')
@php
    $summary = $report['summary'];
    $categories = $report['categories'];
    $nextSteps = $report['next_steps'];
    $systemInfo = $report['system_info'];
    $statusLabels = [
        'ok' => __('messages.system_health.status.ok'),
        'warning' => __('messages.system_health.status.warning'),
        'fail' => __('messages.system_health.status.fail'),
        'info' => __('messages.system_health.status.info'),
    ];
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel h{{ $summary['css'] === 'success' ? 'green' : ($summary['css'] === 'warning' ? 'yellow' : 'red') }}">
            <div class="panel-heading hbuilt">
                <div class="pull-right">
                    <a href="{{ route('system.health.json') }}" class="btn btn-xs btn-default">
                        <i class="fa fa-download"></i> {{ __('messages.system_health.export_json') }}
                    </a>
                </div>
                {{ __('messages.system_health.heading') }}
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <h3 class="m-t-none">{{ __('messages.system_health.headline') }}</h3>
                        <p class="text-muted">{{ __('messages.system_health.intro') }}</p>
                        <p>
                            <span class="label label-{{ $summary['css'] }}">{{ strtoupper($statusLabels[$summary['status']] ?? $summary['status']) }}</span>
                            <span class="m-l-sm text-muted">{{ __('messages.system_health.generated_at') }}: {{ \Illuminate\Support\Carbon::parse($report['generated_at'])->format('Y-m-d H:i:s') }}</span>
                        </p>
                    </div>
                    <div class="col-md-4 text-right">
                        <div class="aptoria-score-ring m-t-sm" style="display:inline-block; min-width: 140px; padding: 18px; border: 1px solid #e7eaec; border-radius: 6px; background: #fafafa;">
                            <div class="font-extra-bold" style="font-size: 34px; line-height: 1;">{{ $summary['score'] }}%</div>
                            <small class="text-muted">{{ __('messages.system_health.health_score') }}</small>
                        </div>
                    </div>
                </div>

                <div class="row text-center m-t-md">
                    <div class="col-sm-3">
                        <div class="stat-percent font-extra-bold text-success">{{ $summary['ok'] }}</div>
                        <small>{{ __('messages.system_health.summary.ok') }}</small>
                    </div>
                    <div class="col-sm-3">
                        <div class="stat-percent font-extra-bold text-warning">{{ $summary['warnings'] }}</div>
                        <small>{{ __('messages.system_health.summary.warnings') }}</small>
                    </div>
                    <div class="col-sm-3">
                        <div class="stat-percent font-extra-bold text-danger">{{ $summary['failed'] }}</div>
                        <small>{{ __('messages.system_health.summary.failed') }}</small>
                    </div>
                    <div class="col-sm-3">
                        <div class="stat-percent font-extra-bold text-info">{{ $summary['info'] }}</div>
                        <small>{{ __('messages.system_health.summary.info') }}</small>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                {{ __('messages.system_health.footer_note') }}
            </div>
        </div>
    </div>
</div>

@if(! empty($nextSteps))
    <div class="row">
        <div class="col-lg-12">
            <div class="hpanel hyellow">
                <div class="panel-heading hbuilt">{{ __('messages.system_health.next_steps_title') }}</div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.system_health.check') }}</th>
                                    <th>{{ __('messages.system_health.recommended_fix') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($nextSteps as $step)
                                <tr>
                                    <td><span class="label label-{{ $step['status'] === 'fail' ? 'danger' : 'warning' }}">{{ strtoupper($statusLabels[$step['status']] ?? $step['status']) }}</span></td>
                                    <td><strong>{{ $step['label'] }}</strong></td>
                                    <td>{{ $step['fix'] }}</td>
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

<div class="row">
    @foreach($categories as $category)
        <div class="col-lg-6">
            <div class="hpanel h{{ $category['summary']['css'] === 'success' ? 'green' : ($category['summary']['css'] === 'warning' ? 'yellow' : 'red') }}">
                <div class="panel-heading hbuilt">
                    <div class="pull-right">
                        <span class="label label-{{ $category['summary']['css'] }}">{{ strtoupper($statusLabels[$category['summary']['status']] ?? $category['summary']['status']) }}</span>
                    </div>
                    {{ __('messages.system_health.categories.'.$category['key']) }}
                </div>
                <div class="panel-body no-padding">
                    <div class="table-responsive">
                        <table class="table table-condensed table-striped m-b-none">
                            <thead>
                                <tr>
                                    <th style="width: 28%;">{{ __('messages.system_health.check') }}</th>
                                    <th style="width: 16%;">{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.common.details') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($category['checks'] as $check)
                                <tr>
                                    <td><strong>{{ $check['label'] }}</strong></td>
                                    <td><span class="label label-{{ $check['css'] }}">{{ strtoupper($statusLabels[$check['status']] ?? $check['status']) }}</span></td>
                                    <td>
                                        {{ $check['detail'] }}
                                        @if(! empty($check['fix']))
                                            <br><small class="text-muted"><strong>{{ __('messages.common.needs_fix') }}</strong> {{ $check['fix'] }}</small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="panel-footer">
                    {{ $category['summary']['ok'] }} {{ __('messages.system_health.summary.ok') }},
                    {{ $category['summary']['warnings'] }} {{ __('messages.system_health.summary.warnings') }},
                    {{ $category['summary']['failed'] }} {{ __('messages.system_health.summary.failed') }}
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.system_health.system_info_title') }}</div>
            <div class="panel-body no-padding">
                <div class="table-responsive">
                    <table class="table table-striped m-b-none">
                        <tbody>
                        @foreach($systemInfo as $label => $value)
                            <tr>
                                <th style="width: 260px;">{{ $label }}</th>
                                <td><code>{{ $value }}</code></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
