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

<style>
    .aptoria-health-shell {
        border: 1px solid #e7eaec;
        border-radius: 10px;
        background: #ffffff;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }
    .aptoria-health-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 14px 18px;
        border-bottom: 1px solid #edf0f2;
        background: #ffffff;
    }
    .aptoria-health-title {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: #1f2d3d;
    }
    .aptoria-health-hero {
        padding: 22px 22px 18px;
        background: linear-gradient(135deg, #fbfcfd 0%, #f4f7fa 100%);
        border-left: 4px solid #d9534f;
    }
    .aptoria-health-hero.is-success { border-left-color: #62cb31; }
    .aptoria-health-hero.is-warning { border-left-color: #ffb606; }
    .aptoria-health-eyebrow {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 10px;
    }
    .aptoria-health-headline {
        margin: 0 0 8px;
        font-size: 24px;
        line-height: 1.25;
        font-weight: 400;
        color: #263544;
    }
    .aptoria-health-intro {
        max-width: 920px;
        margin-bottom: 0;
        color: #6a7885;
    }
    .aptoria-health-score-card {
        max-width: 190px;
        margin-left: auto;
        padding: 18px 18px 16px;
        border: 1px solid #e3e8ee;
        border-radius: 12px;
        background: #ffffff;
        text-align: center;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
    }
    .aptoria-health-score-card strong {
        display: block;
        font-size: 38px;
        line-height: 1;
        font-weight: 600;
        color: #2f4050;
        letter-spacing: -.5px;
    }
    .aptoria-health-score-card small {
        display: block;
        margin-top: 6px;
        color: #7a8793;
    }
    .aptoria-health-metrics {
        margin-top: 18px;
    }
    .aptoria-health-metric {
        min-height: 76px;
        padding: 14px 16px;
        border: 1px solid #e7eaec;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .03);
    }
    .aptoria-health-metric .metric-value {
        display: block;
        font-size: 24px;
        line-height: 1;
        font-weight: 600;
        color: #2f4050;
    }
    .aptoria-health-metric .metric-label {
        display: block;
        margin-top: 8px;
        color: #7a8793;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .aptoria-health-metric.ok { border-top: 3px solid #62cb31; }
    .aptoria-health-metric.warning { border-top: 3px solid #ffb606; }
    .aptoria-health-metric.fail { border-top: 3px solid #d9534f; }
    .aptoria-health-metric.info { border-top: 3px solid #3498db; }
    .aptoria-health-note {
        padding: 12px 18px;
        border-top: 1px solid #edf0f2;
        background: #fbfcfd;
        color: #6a7885;
    }
    @media (max-width: 991px) {
        .aptoria-health-score-card {
            max-width: none;
            margin: 18px 0 0;
        }
    }
</style>

<div class="row">
    <div class="col-lg-12">
        <div class="aptoria-health-shell m-b-lg">
            <div class="aptoria-health-topbar">
                <h2 class="aptoria-health-title">{{ __('messages.system_health.heading') }}</h2>
                <a href="{{ route('system.health.json') }}" class="btn btn-xs btn-default">
                    <i class="fa fa-download"></i> {{ __('messages.system_health.export_json') }}
                </a>
            </div>

            <div class="aptoria-health-hero {{ $summary['css'] === 'success' ? 'is-success' : ($summary['css'] === 'warning' ? 'is-warning' : 'is-fail') }}">
                <div class="row">
                    <div class="col-md-9">
                        <div class="aptoria-health-eyebrow">
                            <span class="label label-{{ $summary['css'] }}">{{ strtoupper($statusLabels[$summary['status']] ?? $summary['status']) }}</span>
                            <span class="text-muted">{{ __('messages.system_health.generated_at') }}: {{ \Illuminate\Support\Carbon::parse($report['generated_at'])->format('Y-m-d H:i:s') }}</span>
                        </div>
                        <h3 class="aptoria-health-headline">{{ __('messages.system_health.headline') }}</h3>
                        <p class="aptoria-health-intro">{{ __('messages.system_health.intro') }}</p>
                    </div>
                    <div class="col-md-3">
                        <div class="aptoria-health-score-card">
                            <strong>{{ $summary['score'] }}%</strong>
                            <small>{{ __('messages.system_health.health_score') }}</small>
                        </div>
                    </div>
                </div>

                <div class="row aptoria-health-metrics">
                    <div class="col-sm-3 m-b-sm">
                        <div class="aptoria-health-metric ok">
                            <span class="metric-value text-success">{{ $summary['ok'] }}</span>
                            <span class="metric-label">{{ __('messages.system_health.summary.ok') }}</span>
                        </div>
                    </div>
                    <div class="col-sm-3 m-b-sm">
                        <div class="aptoria-health-metric warning">
                            <span class="metric-value text-warning">{{ $summary['warnings'] }}</span>
                            <span class="metric-label">{{ __('messages.system_health.summary.warnings') }}</span>
                        </div>
                    </div>
                    <div class="col-sm-3 m-b-sm">
                        <div class="aptoria-health-metric fail">
                            <span class="metric-value text-danger">{{ $summary['failed'] }}</span>
                            <span class="metric-label">{{ __('messages.system_health.summary.failed') }}</span>
                        </div>
                    </div>
                    <div class="col-sm-3 m-b-sm">
                        <div class="aptoria-health-metric info">
                            <span class="metric-value text-info">{{ $summary['info'] }}</span>
                            <span class="metric-label">{{ __('messages.system_health.summary.info') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="aptoria-health-note">
                <i class="fa fa-info-circle"></i> {{ __('messages.system_health.footer_note') }}
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
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.system_health.cli_title') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.system_health.cli_intro') }}</p>
                <div class="row">
                    <div class="col-md-4">
                        <strong>{{ __('messages.system_health.health_command') }}</strong>
                        <pre class="m-t-xs m-b-none">C:\xampp\php\php.exe artisan aptoria:health</pre>
                    </div>
                    <div class="col-md-4">
                        <strong>{{ __('messages.system_health.json_command') }}</strong>
                        <pre class="m-t-xs m-b-none">C:\xampp\php\php.exe artisan aptoria:health --json</pre>
                    </div>
                    <div class="col-md-4">
                        <strong>{{ __('messages.system_health.scheduler_command') }}</strong>
                        <pre class="m-t-xs m-b-none">C:\xampp\php\php.exe artisan schedule:run</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
