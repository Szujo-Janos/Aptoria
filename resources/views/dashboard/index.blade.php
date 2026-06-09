@extends('layouts.app')

@section('title', __('messages.dashboard.title'))

@section('content')
<div class="aptoria-dashboard-root"></div>
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue aptoria-project-panel aptoria-dashboard-project-style-panel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.wizard.create') }}" class="btn btn-xs btn-success"><i class="fa fa-magic"></i> {{ __('messages.wizard.short_title') }}</a>
                    <a href="{{ route('projects.index') }}" class="btn btn-xs btn-info"><i class="fa fa-briefcase"></i> {{ __('messages.nav.projects') }}</a>
                    <a href="{{ route('calendar.index') }}" class="btn btn-xs btn-primary"><i class="fa fa-calendar"></i> {{ __('messages.nav.calendar') }}</a>
                    <a href="{{ route('reports.index') }}" class="btn btn-xs btn-default"><i class="fa fa-file-text-o"></i> {{ __('messages.nav.reports') }}</a>
                </div>
                {{ __('messages.dashboard.panel_title') }}
            </div>
            <div class="panel-body">
                <div class="row aptoria-project-redesign-row">
                    <div class="col-lg-8">
                        <div class="aptoria-project-overview-card aptoria-dashboard-overview-card">
                            <div class="aptoria-project-overview-copy">
                                <span class="label label-success">{{ __('messages.app.mvp') }} {{ config('aptoria.version') }}</span>
                                <h2 class="m-t-md m-b-xs">{{ __('messages.dashboard.hero_title') }}</h2>
                                <p class="text-muted aptoria-project-summary">{{ __('messages.dashboard.hero_subtitle') }}</p>
                                <div class="aptoria-project-badges">
                                    <span class="label label-success"><i class="fa fa-shield"></i> {{ __('messages.dashboard.safe_by_design') }}</span>
                                    <span class="label label-info"><i class="fa fa-calendar"></i> {{ __('messages.calendar.preview_title') }}</span>
                                    <span class="label label-default"><i class="fa fa-code"></i> GET / HEAD</span>
                                </div>
                            </div>

                            <div class="row aptoria-project-overview-details">
                                <div class="col-md-6">
                                    <div class="aptoria-project-info-card">
                                        <h5 class="aptoria-card-title">{{ __('messages.dashboard.operational_health') }}</h5>
                                        <dl class="dl-horizontal aptoria-project-dl">
                                            <dt>{{ __('messages.nav.projects') }}</dt><dd>{{ $projectCount }} / {{ $activeProjectCount }} {{ __('messages.dashboard.active_projects') }}</dd>
                                            <dt>{{ __('messages.nav.endpoints') }}</dt><dd>{{ $endpointCount }} · {{ $probeableEndpointCount }} {{ __('messages.dashboard.probeable_endpoints') }}</dd>
                                            <dt>{{ __('messages.nav.monitors') }}</dt><dd>{{ $enabledMonitorCount }} / {{ $monitorCount }}</dd>
                                            <dt>{{ __('messages.release_readiness.score') }}</dt><dd>{{ $averageReadinessScore }}</dd>
                                        </dl>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="aptoria-project-info-card">
                                        <h5 class="aptoria-card-title">{{ __('messages.dashboard.safe_by_design') }}</h5>
                                        <ul class="list-unstyled aptoria-health-listing m-b-none">
                                            <li><span>{{ __('messages.dashboard.get_head_only') }}</span><strong><i class="fa fa-check text-success"></i></strong></li>
                                            <li><span>{{ __('messages.dashboard.destructive_skipped') }}</span><strong><i class="fa fa-check text-success"></i></strong></li>
                                            <li><span>{{ __('messages.dashboard.secret_masking') }}</span><strong><i class="fa fa-check text-success"></i></strong></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="row aptoria-project-widget-grid">
                            <div class="col-sm-6">
                                <div class="hpanel aptoria-project-widget">
                                    <div class="panel-body">
                                        <div class="aptoria-widget-icon"><i class="fa fa-briefcase"></i></div>
                                        <div class="aptoria-widget-title">{{ __('messages.nav.projects') }}</div>
                                        <h2>{{ $projectCount }}</h2>
                                        <div class="text-muted">{{ $activeProjectCount }} {{ __('messages.dashboard.active_projects') }}</div>
                                    </div>
                                    <div class="panel-footer">{{ __('messages.projects.open_workspace') }}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="hpanel aptoria-project-widget">
                                    <div class="panel-body">
                                        <div class="aptoria-widget-icon"><i class="fa fa-sitemap"></i></div>
                                        <div class="aptoria-widget-title">{{ __('messages.nav.endpoints') }}</div>
                                        <h2>{{ $endpointCount }}</h2>
                                        <div class="text-muted">{{ $probeableEndpointCount }} {{ __('messages.dashboard.probeable_endpoints') }}</div>
                                    </div>
                                    <div class="panel-footer">{{ __('messages.dashboard.safe_coverage_focus') }}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="hpanel aptoria-project-widget">
                                    <div class="panel-body">
                                        <div class="aptoria-widget-icon"><i class="fa fa-warning"></i></div>
                                        <div class="aptoria-widget-title">{{ __('messages.dashboard.risk_review_queue') }}</div>
                                        <h2>{{ $criticalEndpointCount + $highEndpointCount + $reviewEndpointCount }}</h2>
                                        <div class="text-muted">{{ $criticalEndpointCount }} {{ __('messages.dashboard.critical') }} · {{ $highEndpointCount }} {{ __('messages.dashboard.high') }}</div>
                                    </div>
                                    <div class="panel-footer">{{ __('messages.dashboard.assertion_signals') }}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="hpanel aptoria-project-widget">
                                    <div class="panel-body">
                                        <div class="aptoria-widget-icon"><i class="fa fa-calendar"></i></div>
                                        <div class="aptoria-widget-title">{{ __('messages.nav.calendar') }}</div>
                                        <h2>{{ $calendarPreviewSummary['open'] ?? 0 }}</h2>
                                        <div class="text-muted">{{ $calendarPreviewSummary['due_today'] ?? 0 }} {{ __('messages.calendar.due_today') }}</div>
                                    </div>
                                    <div class="panel-footer">{{ __('messages.calendar.preview_title') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($showScanSummaryCards ?? true)
<div class="row aptoria-kpi-row">
    <div class="col-md-3 col-sm-6">
        <div class="hpanel hgreen aptoria-kpi-card">
            <div class="panel-body">
                <div class="aptoria-kpi-icon"><i class="fa fa-briefcase"></i></div>
                <h2>{{ $projectCount }}</h2>
                <div class="font-bold">{{ __('messages.nav.projects') }}</div>
                <small>{{ $activeProjectCount }} {{ __('messages.dashboard.active_projects') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="hpanel hblue aptoria-kpi-card">
            <div class="panel-body">
                <div class="aptoria-kpi-icon"><i class="fa fa-sitemap"></i></div>
                <h2>{{ $endpointCount }}</h2>
                <div class="font-bold">{{ __('messages.nav.endpoints') }}</div>
                <small>{{ $probeableEndpointCount }} {{ __('messages.dashboard.probeable_endpoints') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="hpanel horange aptoria-kpi-card">
            <div class="panel-body">
                <div class="aptoria-kpi-icon"><i class="fa fa-warning"></i></div>
                <h2>{{ $criticalEndpointCount + $highEndpointCount + $reviewEndpointCount }}</h2>
                <div class="font-bold">{{ __('messages.dashboard.risk_review_queue') }}</div>
                <small>{{ $criticalEndpointCount }} {{ __('messages.dashboard.critical') }} / {{ $highEndpointCount }} {{ __('messages.dashboard.high') }} / {{ $reviewEndpointCount }} {{ __('messages.dashboard.review') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="hpanel hviolet aptoria-kpi-card">
            <div class="panel-body">
                <div class="aptoria-kpi-icon"><i class="fa fa-camera-retro"></i></div>
                <h2>{{ $snapshotCount }}</h2>
                <div class="font-bold">{{ __('messages.nav.snapshots') }}</div>
                <small>{{ $compareCount }} {{ __('messages.dashboard.compare_runs') }}</small>
            </div>
        </div>
    </div>
</div>
@endif

@if($showDashboardCalendarPreview ?? true)
<div class="row">
    <div class="col-lg-12">
        @include('calendar._preview', ['events' => $calendarPreviewEvents, 'summary' => $calendarPreviewSummary])
    </div>
</div>
@endif

@if($showScanSummaryCards ?? true)
<div class="row aptoria-widget-row">
    <div class="col-lg-3 col-sm-6">
        <div class="hpanel aptoria-metric-widget">
            <div class="panel-body">
                <div class="aptoria-widget-icon"><i class="fa fa-rss"></i></div>
                <div class="aptoria-widget-title">{{ __('messages.dashboard.scan_activity') }}</div>
                <h2>{{ $scanRunCount }}</h2>
                <div class="text-muted">{{ $completedScanCount }} {{ __('messages.scans.statuses.completed') }}</div>
                <div class="progress m-t-sm full progress-small">
                    @php($scanCompletionPercent = $scanRunCount > 0 ? (int) round(($completedScanCount / max($scanRunCount, 1)) * 100) : 0)
                    <div style="width: {{ $scanCompletionPercent }}%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="{{ $scanCompletionPercent }}" role="progressbar" class="progress-bar progress-bar-success">
                        <span class="sr-only">{{ $scanCompletionPercent }}%</span>
                    </div>
                </div>
            </div>
            <div class="panel-footer">{{ __('messages.dashboard.last_7_days') }} · {{ count($scanTrendData) }} {{ __('messages.dashboard.data_points') }}</div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="hpanel aptoria-metric-widget">
            <div class="panel-body">
                <div class="aptoria-widget-icon"><i class="fa fa-sitemap"></i></div>
                <div class="aptoria-widget-title">{{ __('messages.dashboard.method_mix') }}</div>
                <h2>{{ $endpointCount }}</h2>
                <div class="text-muted">{{ $probeableEndpointCount }} {{ __('messages.dashboard.probeable_endpoints') }}</div>
                <div class="progress m-t-sm full progress-small">
                    @php($probePercent = $endpointCount > 0 ? (int) round(($probeableEndpointCount / max($endpointCount, 1)) * 100) : 0)
                    <div style="width: {{ $probePercent }}%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="{{ $probePercent }}" role="progressbar" class="progress-bar progress-bar-info">
                        <span class="sr-only">{{ $probePercent }}%</span>
                    </div>
                </div>
            </div>
            <div class="panel-footer">{{ __('messages.dashboard.safe_coverage_focus') }}</div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="hpanel aptoria-metric-widget">
            <div class="panel-body">
                <div class="aptoria-widget-icon"><i class="fa fa-warning"></i></div>
                <div class="aptoria-widget-title">{{ __('messages.dashboard.assertion_failures') }}</div>
                <h2>{{ $assertionFailCount + $assertionWarningCount }}</h2>
                <div class="text-muted">{{ $regressionDetectedCount }} {{ __('messages.regressions.detected_count') }}</div>
                <div class="progress m-t-sm full progress-small">
                    @php($riskPressure = ($criticalEndpointCount + $highEndpointCount + $reviewEndpointCount) > 0 ? 100 : 0)
                    <div style="width: {{ $riskPressure }}%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="{{ $riskPressure }}" role="progressbar" class="progress-bar progress-bar-warning">
                        <span class="sr-only">{{ $riskPressure }}%</span>
                    </div>
                </div>
            </div>
            <div class="panel-footer">{{ $criticalEndpointCount }} {{ __('messages.dashboard.critical') }} · {{ $highEndpointCount }} {{ __('messages.dashboard.high') }} · {{ $reviewEndpointCount }} {{ __('messages.dashboard.review') }}</div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="hpanel aptoria-metric-widget">
            <div class="panel-body">
                <div class="aptoria-widget-icon"><i class="fa fa-flag-checkered"></i></div>
                <div class="aptoria-widget-title">{{ __('messages.release_readiness.dashboard_title') }}</div>
                <h2>{{ $averageReadinessScore }}</h2>
                <div class="text-muted">{{ $enabledMonitorCount }} {{ __('messages.monitors.enabled_monitors') }}</div>
                <div class="progress m-t-sm full progress-small">
                    <div style="width: {{ min(100, max(0, $averageReadinessScore)) }}%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="{{ min(100, max(0, $averageReadinessScore)) }}" role="progressbar" class="progress-bar progress-bar-success">
                        <span class="sr-only">{{ $averageReadinessScore }}%</span>
                    </div>
                </div>
            </div>
            <div class="panel-footer">{{ __('messages.dashboard.readiness_footer', ['pass' => $readinessPassCount, 'warning' => $readinessWarningCount, 'fail' => $readinessFailCount]) }}</div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-8">
        <div class="hpanel aptoria-workflow-panel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><span class="label label-info">{{ __('messages.dashboard.method_label') }}</span></div>
                {{ __('messages.dashboard.qa_workflow') }}
            </div>
            <div class="panel-body">
                <div class="row text-center aptoria-workflow">
                    <div class="col-sm-2 col-xs-6">
                        <div class="aptoria-workflow-badge"><i class="fa fa-briefcase"></i></div>
                        <strong>{{ __('messages.dashboard.workflow_projects') }}</strong>
                        <small>{{ $projectCount }}</small>
                    </div>
                    <div class="col-sm-2 col-xs-6">
                        <div class="aptoria-workflow-badge"><i class="fa fa-sitemap"></i></div>
                        <strong>{{ __('messages.dashboard.workflow_inventory') }}</strong>
                        <small>{{ $endpointCount }}</small>
                    </div>
                    <div class="col-sm-2 col-xs-6">
                        <div class="aptoria-workflow-badge"><i class="fa fa-crosshairs"></i></div>
                        <strong>{{ __('messages.dashboard.workflow_probe') }}</strong>
                        <small>{{ $completedScanCount }}</small>
                    </div>
                    <div class="col-sm-2 col-xs-6">
                        <div class="aptoria-workflow-badge"><i class="fa fa-shield"></i></div>
                        <strong>{{ __('messages.dashboard.workflow_risk') }}</strong>
                        <small>{{ $criticalEndpointCount + $highEndpointCount + $reviewEndpointCount }}</small>
                    </div>
                    <div class="col-sm-2 col-xs-6">
                        <div class="aptoria-workflow-badge"><i class="fa fa-camera-retro"></i></div>
                        <strong>{{ __('messages.dashboard.workflow_snapshot') }}</strong>
                        <small>{{ $snapshotCount }}</small>
                    </div>
                    <div class="col-sm-2 col-xs-6">
                        <div class="aptoria-workflow-badge"><i class="fa fa-file-text-o"></i></div>
                        <strong>{{ __('messages.dashboard.workflow_export') }}</strong>
                        <small>CSV / MD / JSON</small>
                    </div>
                </div>
                <p class="text-muted text-center m-t-md m-b-none">{{ __('messages.dashboard.qa_workflow_hint') }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hnavyblue">
            <div class="panel-heading hbuilt">{{ __('messages.dashboard.operational_health') }}</div>
            <div class="panel-body aptoria-health-list">
                <div><span class="label label-success"><i class="fa fa-check"></i></span> {{ __('messages.dashboard.get_head_only') }}</div>
                <div><span class="label label-success"><i class="fa fa-check"></i></span> {{ __('messages.dashboard.destructive_skipped') }}</div>
                <div><span class="label label-success"><i class="fa fa-check"></i></span> {{ __('messages.dashboard.private_network_blocked') }}</div>
                <div><span class="label label-success"><i class="fa fa-check"></i></span> {{ __('messages.dashboard.secret_masking') }}</div>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-8">
        <div class="hpanel hgreen aptoria-activity-panel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><span class="label label-success">{{ __('messages.dashboard.live_workspace') }}</span></div>
                Recent activity stream
            </div>
            <div class="panel-body no-padding">
                @if($latestScanRuns->isEmpty() && ! $latestSnapshot && ! $latestCompareRun)
                    <div class="text-center p-lg">
                        <div class="aptoria-empty-icon"><i class="fa fa-clock-o"></i></div>
                        <p class="text-muted m-b-none">{{ __('messages.dashboard.no_recent_activity') }}</p>
                    </div>
                @else
                    <ul class="list-group clear-list aptoria-activity-list m-b-none">
                        @foreach($latestScanRuns as $scan)
                            <li class="list-group-item">
                                <span class="label label-{{ $scan->status_css }} pull-right">{{ $scan->status_label }}</span>
                                <h5 class="m-b-xs"><i class="fa fa-rss text-success"></i> Scan run · {{ $scan->project?->name }}</h5>
                                <small class="text-muted">{{ $scan->created_at?->diffForHumans() }} · {{ $scan->environment?->name ?: 'n/a' }} · {{ $scan->duration_label }}</small>
                            </li>
                        @endforeach
                        @if($latestSnapshot)
                            <li class="list-group-item">
                                <span class="label label-info pull-right">{{ __('messages.dashboard.snapshot_activity') }}</span>
                                <h5 class="m-b-xs"><i class="fa fa-camera-retro text-info"></i> {{ $latestSnapshot->name }}</h5>
                                <small class="text-muted">{{ $latestSnapshot->project?->name }} · {{ $latestSnapshot->created_at?->diffForHumans() }}</small>
                            </li>
                        @endif
                        @if($latestCompareRun)
                            <li class="list-group-item">
                                <span class="label label-primary pull-right">{{ __('messages.dashboard.compare_activity') }}</span>
                                <h5 class="m-b-xs"><i class="fa fa-exchange text-primary"></i> Snapshot compare · {{ $latestCompareRun->project?->name }}</h5>
                                <small class="text-muted">{{ $latestCompareRun->created_at?->diffForHumans() }}</small>
                            </li>
                        @endif
                    </ul>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hblue aptoria-appview-panel">
            <div class="panel-heading hbuilt">{{ __('messages.dashboard.app_views_title') }}</div>
            <div class="panel-body">
                <div class="aptoria-appview-grid">
                    <a href="{{ route('projects.index') }}" class="aptoria-appview-card">
                        <div class="icon"><i class="fa fa-briefcase"></i></div>
                        <strong>{{ __('messages.nav.projects') }}</strong>
                        <small>{{ __('messages.dashboard.projects_shortcut_help') }}</small>
                    </a>
                    <a href="{{ route('reports.index') }}" class="aptoria-appview-card">
                        <div class="icon"><i class="fa fa-file-text-o"></i></div>
                        <strong>{{ __('messages.reports.title') }}</strong>
                        <small>{{ __('messages.dashboard.reports_shortcut_help') }}</small>
                    </a>
                    <a href="{{ route('help.index') }}" class="aptoria-appview-card">
                        <div class="icon"><i class="fa fa-life-ring"></i></div>
                        <strong>{{ __('messages.nav.help') }}</strong>
                        <small>{{ __('messages.dashboard.help_shortcut_help') }}</small>
                    </a>
                    <a href="{{ route('settings.index') }}" class="aptoria-appview-card">
                        <div class="icon"><i class="fa fa-sliders"></i></div>
                        <strong>{{ __('messages.nav.settings') }}</strong>
                        <small>{{ __('messages.dashboard.settings_shortcut_help') }}</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.index') }}" class="btn btn-xs btn-default">{{ __('messages.dashboard.view_all') }}</a></div>
                {{ __('messages.monitors.dashboard_title') }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-4"><h3>{{ $monitorCount }}</h3><small>{{ __('messages.monitors.total_monitors') }}</small></div>
                    <div class="col-sm-4"><h3>{{ $enabledMonitorCount }}</h3><small>{{ __('messages.monitors.enabled_monitors') }}</small></div>
                    <div class="col-sm-4"><h3>{{ $monitorAlertCount }}</h3><small><span class="label label-danger">{{ __('messages.monitors.alerts') }}</span></small></div>
                </div>
                @if($latestMonitors->isNotEmpty())
                    <hr>
                    <table class="table table-striped table-condensed m-b-none">
                        <thead><tr><th>{{ __('messages.common.name') }}</th><th>{{ __('messages.nav.projects') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.monitors.last_run') }}</th></tr></thead>
                        <tbody>
                        @foreach($latestMonitors as $monitor)
                            <tr>
                                <td>{{ $monitor->name }}</td>
                                <td><a href="{{ route('projects.monitors.index', $monitor->project) }}">{{ $monitor->project?->name }}</a></td>
                                <td><span class="label label-{{ $monitor->last_status_css }}">{{ $monitor->last_status_label }}</span></td>
                                <td>{{ $monitor->last_run_label }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted text-center m-t-md m-b-none">{{ __('messages.monitors.no_dashboard_monitors') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

@if($showReleaseReadinessWidget ?? true)
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('reports.index') }}" class="btn btn-xs btn-default">{{ __('messages.reports.title') }}</a></div>
                {{ __('messages.release_readiness.dashboard_title') }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-3"><h3>{{ $averageReadinessScore }}</h3><small>{{ __('messages.release_readiness.average_score') }}</small></div>
                    <div class="col-sm-3"><h3>{{ $readinessPassCount }}</h3><small><span class="label label-success">{{ __('messages.release_readiness.statuses.pass') }}</span></small></div>
                    <div class="col-sm-3"><h3>{{ $readinessWarningCount }}</h3><small><span class="label label-warning">{{ __('messages.release_readiness.statuses.warning') }}</span></small></div>
                    <div class="col-sm-3"><h3>{{ $readinessFailCount }}</h3><small><span class="label label-danger">{{ __('messages.release_readiness.statuses.fail') }}</span></small></div>
                </div>
                @if($readinessProjects->isNotEmpty())
                    <hr>
                    <table class="table table-striped table-condensed m-b-none">
                        <thead><tr><th>{{ __('messages.nav.projects') }}</th><th>{{ __('messages.release_readiness.score') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.release_readiness.coverage') }}</th><th>{{ __('messages.release_readiness.blocking_issues') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($readinessProjects as $row)
                            <tr>
                                <td><strong>{{ $row['project']->name }}</strong></td>
                                <td>{{ $row['readiness']['score'] }}</td>
                                <td><span class="label label-{{ $row['readiness']['css'] }}">{{ $row['readiness']['label'] }}</span></td>
                                <td>{{ $row['readiness']['coverage_percent'] }}%</td>
                                <td>{{ count($row['readiness']['blocking_issues']) }}</td>
                                <td class="text-right"><a href="{{ route('projects.release-readiness.show', $row['project']) }}" class="btn btn-xs btn-default">{{ __('messages.release_readiness.open_dashboard') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted text-center m-t-md m-b-none">{{ __('messages.release_readiness.no_projects') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@if($showQaEvidenceWidget ?? true)
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.index') }}" class="btn btn-xs btn-default">{{ __('messages.dashboard.view_all') }}</a></div>
                {{ __('messages.dashboard.assertion_failures') }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-4"><h3>{{ $assertionFailCount }}</h3><small><span class="label label-danger">{{ __('messages.assertions.statuses.fail') }}</span></small></div>
                    <div class="col-sm-4"><h3>{{ $assertionWarningCount }}</h3><small><span class="label label-warning">{{ __('messages.assertions.statuses.warning') }}</span></small></div>
                    <div class="col-sm-4"><h3>{{ $regressionDetectedCount }}</h3><small><span class="label label-danger">{{ __('messages.regressions.statuses.detected') }}</span></small></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-4">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.dashboard.risk_distribution') }}</div>
            <div class="panel-body">
                <div class="aptoria-chart-box">
                    <canvas class="aptoria-chart" id="aptoria-risk-chart" data-chart-type="doughnut" data-labels='@json(array_column($riskChartData, "label"))' data-values='@json(array_column($riskChartData, "value"))'></canvas>
                </div>
                <div class="aptoria-risk-legend m-t-md">
                    <span class="label label-danger">{{ $criticalEndpointCount }} Critical</span>
                    <span class="label label-warning">{{ $highEndpointCount }} High</span>
                    <span class="label label-default">{{ $reviewEndpointCount }} Review</span>
                    <span class="label label-info">{{ $publicEndpointCount }} Public</span>
                    <span class="label label-success">{{ $lowEndpointCount }} Low</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.dashboard.method_mix') }}</div>
            <div class="panel-body">
                <div class="aptoria-chart-box">
                    <canvas class="aptoria-chart" id="aptoria-method-chart" data-chart-type="bar" data-labels='@json(array_column($methodChartData, "label"))' data-values='@json(array_column($methodChartData, "value"))'></canvas>
                </div>
                <p class="text-muted text-center m-t-md m-b-none">{{ __('messages.dashboard.method_mix_hint') }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.dashboard.scan_activity') }}</div>
            <div class="panel-body">
                <div class="aptoria-chart-box">
                    <canvas class="aptoria-chart" id="aptoria-scan-trend-chart" data-chart-type="line" data-labels='@json(array_column($scanTrendData, "label"))' data-values='@json(array_column($scanTrendData, "value"))'></canvas>
                </div>
                <p class="text-muted text-center m-t-md m-b-none">{{ __('messages.dashboard.last_7_days') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.index') }}" class="btn btn-xs btn-default">{{ __('messages.dashboard.view_all') }}</a></div>
                {{ __('messages.dashboard.latest_projects') }}
            </div>
            <div class="panel-body no-padding">
                @if($latestProjects->isEmpty())
                    <div class="text-center p-xl">
                        <div class="aptoria-empty-icon"><i class="fa fa-briefcase"></i></div>
                        <h4>{{ __('messages.dashboard.no_project') }}</h4>
                        <p class="text-muted">{{ __('messages.dashboard.no_project_help') }}</p>
                        <a href="{{ route('projects.wizard.create') }}" class="btn btn-success"><i class="fa fa-magic"></i> {{ __('messages.wizard.short_title') }}</a>
                        <a href="{{ route('projects.create') }}" class="btn btn-default m-l-sm">{{ __('messages.projects.create_title') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover m-b-none">
                            <thead><tr><th>{{ __('messages.common.name') }}</th><th>{{ __('messages.common.base_url') }}</th><th>Env</th><th>Auth</th><th>{{ __('messages.nav.endpoints') }}</th><th>{{ __('messages.nav.scans') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($latestProjects as $project)
                                <tr>
                                    <td><strong>{{ $project->name }}</strong><br><small class="text-muted">{{ $project->slug }}</small></td>
                                    <td><code>{{ $project->display_base_url }}</code></td>
                                    <td><span class="badge badge-info">{{ $project->environments_count }}</span></td>
                                    <td><span class="badge badge-primary">{{ $project->auth_profiles_count }}</span></td>
                                    <td><span class="badge badge-success">{{ $project->endpoints_count }}</span></td>
                                    <td><span class="badge badge-default">{{ $project->scan_runs_count }}</span></td>
                                    <td class="text-right"><a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.dashboard.open') }}</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel horange">
            <div class="panel-heading hbuilt">{{ __('messages.dashboard.top_risky') }}</div>
            <div class="panel-body no-padding">
                @if($topRiskyEndpoints->isEmpty())
                    <div class="text-center p-lg">
                        <div class="aptoria-empty-icon"><i class="fa fa-check-circle"></i></div>
                        <p class="text-muted m-b-none">{{ __('messages.dashboard.no_risky') }}</p>
                    </div>
                @else
                    <ul class="list-group clear-list aptoria-risk-list">
                        @foreach($topRiskyEndpoints as $endpoint)
                            <li class="list-group-item">
                                <span class="label label-{{ $endpoint->risk_css }} pull-right">{{ $endpoint->risk_label }}</span>
                                <strong>{{ $endpoint->method }} {{ $endpoint->path }}</strong><br>
                                <small class="text-muted">{{ $endpoint->project?->name }} · {{ $endpoint->latestScanResult?->status_code ?? __('messages.common.not_available') }}</small>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.dashboard.latest_scan') }}</div>
            <div class="panel-body aptoria-mini-status">
                @if($latestScanRun)
                    <h4>{{ $latestScanRun->project?->name }}</h4>
                    <p><span class="label label-{{ $latestScanRun->status_css }}">{{ $latestScanRun->status_label }}</span></p>
                    <p class="text-muted m-b-none">{{ $latestScanRun->created_at?->diffForHumans() }} · {{ $latestScanRun->duration_label }}</p>
                @else
                    <p class="text-muted m-b-none">{{ __('messages.dashboard.no_scans') }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="hpanel hviolet">
            <div class="panel-heading hbuilt">{{ __('messages.dashboard.latest_snapshot') }}</div>
            <div class="panel-body aptoria-mini-status">
                @if($latestSnapshot)
                    <h4>{{ $latestSnapshot->name }}</h4>
                    <p><span class="label label-primary">{{ $latestSnapshot->project?->name }}</span></p>
                    <p class="text-muted m-b-none">{{ $latestSnapshot->created_at?->diffForHumans() }}</p>
                @else
                    <p class="text-muted m-b-none">{{ __('messages.dashboard.no_snapshots') }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.dashboard.latest_compare') }}</div>
            <div class="panel-body aptoria-mini-status">
                @if($latestCompareRun)
                    <h4>{{ $latestCompareRun->project?->name }}</h4>
                    <p><span class="label label-success">{{ __('messages.nav.snapshots') }}</span></p>
                    <p class="text-muted m-b-none">{{ $latestCompareRun->created_at?->diffForHumans() }}</p>
                @else
                    <p class="text-muted m-b-none">{{ __('messages.dashboard.no_compares') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
