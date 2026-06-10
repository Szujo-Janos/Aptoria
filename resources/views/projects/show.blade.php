@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue aptoria-project-panel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.environments.create', $project) }}" class="btn btn-xs btn-success"><i class="fa fa-plus"></i> {{ __('messages.projects.add_environment') }}</a>
                    <a href="{{ route('projects.auth-profiles.create', $project) }}" class="btn btn-xs btn-info"><i class="fa fa-plus"></i> {{ __('messages.projects.add_auth_profile') }}</a>
                    <a href="{{ route('projects.endpoints.create', $project) }}" class="btn btn-xs btn-warning"><i class="fa fa-plus"></i> {{ __('messages.endpoints.new') }}</a>
                    <a href="{{ route('projects.scans.create', $project) }}" class="btn btn-xs btn-success"><i class="fa fa-plus"></i> {{ __('messages.scans.new') }}</a>
                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i> {{ __('messages.projects.edit_project') }}</a>
                </div>
                {{ __('messages.projects.details') }}
            </div>
            <div class="panel-body">
                <div class="row aptoria-project-redesign-row">
                    <div class="col-lg-8">
                        <div class="aptoria-project-overview-card">
                            <div class="aptoria-project-overview-copy">
                                <h2 class="m-t-none">{{ $project->name }}</h2>
                                <p class="text-muted aptoria-project-summary">{{ $project->description ?: __('messages.projects.no_description') }}</p>
                                <div class="aptoria-project-badges">
                                    <span class="label label-success"><i class="fa fa-shield"></i> {{ __('messages.projects.safe_qa_label') }}</span>
                                    <span class="label label-info"><i class="fa fa-globe"></i> {{ $project->display_base_url }}</span>
                                    <span class="label label-default"><i class="fa fa-link"></i> {{ $project->slug }}</span>
                                </div>
                            </div>

                            <div class="row aptoria-project-overview-details">
                                <div class="col-md-6">
                                    <div class="aptoria-project-info-card">
                                        <h5 class="aptoria-card-title">{{ __('messages.projects.overview') }}</h5>
                                        <dl class="dl-horizontal aptoria-project-dl">
                                            <dt>{{ __('messages.common.base_url') }}</dt><dd><code>{{ $project->display_base_url }}</code></dd>
                                            <dt>{{ __('messages.projects.slug') }}</dt><dd>{{ $project->slug }}</dd>
                                            <dt>{{ __('messages.common.status') }}</dt><dd>{!! $project->is_active ? '<span class="label label-success">'.__('messages.common.active').'</span>' : '<span class="label label-default">'.__('messages.common.inactive').'</span>' !!}</dd>
                                            <dt>{{ __('messages.projects.environments') }}</dt><dd>{{ $project->environments->count() }}</dd>
                                            <dt>{{ __('messages.projects.auth_profiles') }}</dt><dd>{{ $project->authProfiles->count() }}</dd>
                                        </dl>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="aptoria-project-info-card">
                                        <h5 class="aptoria-card-title">{{ __('messages.projects.operational_summary') }}</h5>
                                        <dl class="dl-horizontal aptoria-project-dl">
                                            <dt>{{ __('messages.projects.endpoints') }}</dt><dd><a href="{{ route('projects.endpoint-inventory.index', $project) }}">{{ $project->endpoints_count }}</a></dd>
                                            <dt>{{ __('messages.projects.scans') }}</dt><dd><a href="{{ route('projects.scans.index', $project) }}">{{ $project->scan_runs_count }}</a></dd>
                                            <dt>{{ __('messages.monitors.title') }}</dt><dd><a href="{{ route('projects.monitors.index', $project) }}">{{ $project->api_monitors_count }}</a></dd>
                                            <dt>{{ __('messages.test_suites.title') }}</dt><dd><a href="{{ route('projects.test-suites.index', $project) }}">{{ $project->test_suites_count }}</a></dd>
                                            <dt>{{ __('messages.test_cases.total') }}</dt><dd><a href="{{ route('projects.test-cases.index', $project) }}">{{ $project->test_cases_count }}</a></dd>
                                            <dt>{{ __('messages.findings.title') }}</dt><dd><a href="{{ route('projects.findings.index', $project) }}">{{ $project->findings_count }}</a></dd>
                                        </dl>
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
                                        <div class="aptoria-widget-icon"><i class="fa fa-server"></i></div>
                                        <div class="aptoria-widget-title">{{ __('messages.projects.environments') }}</div>
                                        <h2>{{ $project->environments->count() }}</h2>
                                        <div class="text-muted">{{ $project->authProfiles->count() }} {{ __('messages.projects.auth_profiles') }}</div>
                                    </div>
                                    <div class="panel-footer">{{ __('messages.projects.add_environment') }} / {{ __('messages.projects.add_auth_profile') }}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="hpanel aptoria-project-widget">
                                    <div class="panel-body">
                                        <div class="aptoria-widget-icon"><i class="fa fa-sitemap"></i></div>
                                        <div class="aptoria-widget-title">{{ __('messages.projects.endpoints') }}</div>
                                        <h2>{{ $project->endpoints_count }}</h2>
                                        <div class="text-muted">{{ $project->scan_runs_count }} {{ __('messages.projects.scans') }}</div>
                                    </div>
                                    <div class="panel-footer">{{ __('messages.endpoints.new') }} / {{ __('messages.scans.new') }}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="hpanel aptoria-project-widget">
                                    <div class="panel-body">
                                        <div class="aptoria-widget-icon"><i class="fa fa-pie-chart"></i></div>
                                        <div class="aptoria-widget-title">{{ __('messages.qa_coverage.short_title') }}</div>
                                        <h2>{{ $qaCoverage['coverage_percent'] ?? 0 }}%</h2>
                                        <div class="text-muted">{{ $project->test_cases_count }} {{ __('messages.test_cases.title') }}</div>
                                        <div class="progress m-t-sm full progress-small">
                                            <div style="width: {{ $qaCoverage['coverage_percent'] ?? 0 }}%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="{{ $qaCoverage['coverage_percent'] ?? 0 }}" role="progressbar" class="progress-bar progress-bar-warning"></div>
                                        </div>
                                    </div>
                                    <div class="panel-footer">{{ __('messages.projects.open_workspace') }}: {{ __('messages.qa_coverage.short_title') }}</div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="hpanel aptoria-project-widget">
                                    <div class="panel-body">
                                        <div class="aptoria-widget-icon"><i class="fa fa-flag-checkered"></i></div>
                                        <div class="aptoria-widget-title">{{ __('messages.release_gates.short_title') }}</div>
                                        <h2>{{ $project->qa_release_gates_count }}</h2>
                                        <div class="text-muted">{{ $releaseReadiness['score'] }} {{ __('messages.release_readiness.score') }}</div>
                                    </div>
                                    <div class="panel-footer">{{ __('messages.release_readiness.title') }} · {{ $releaseReadiness['label'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="aptoria-project-module-section aptoria-workspace-button-section">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 class="aptoria-section-title">{{ __('messages.projects.workspace_modules') }}</h4>
                            <p class="text-muted aptoria-section-subtitle">{{ __('messages.projects.workspace_modules_help') }}</p>
                        </div>
                        <div class="col-md-4 text-right hidden-xs hidden-sm">
                            <span class="label label-info"><i class="fa fa-th-large"></i> {{ __('messages.projects.compact_workspace') }}</span>
                        </div>
                    </div>
                    <div class="row aptoria-workspace-button-grid">
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('projects.monitors.index', $project) }}" class="btn btn-default btn-block aptoria-workspace-button">
                                <span class="aptoria-workspace-icon text-success"><i class="fa fa-clock-o"></i></span>
                                <span class="aptoria-workspace-title">{{ __('messages.monitors.title') }}</span>
                                <span class="badge pull-right">{{ $project->api_monitors_count }}</span>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('projects.test-suites.index', $project) }}" class="btn btn-default btn-block aptoria-workspace-button">
                                <span class="aptoria-workspace-icon text-info"><i class="fa fa-list-alt"></i></span>
                                <span class="aptoria-workspace-title">{{ __('messages.test_suites.title') }}</span>
                                <span class="badge pull-right">{{ $project->test_suites_count }}</span>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('projects.test-execution.index', $project) }}" class="btn btn-default btn-block aptoria-workspace-button">
                                <span class="aptoria-workspace-icon text-warning"><i class="fa fa-play-circle"></i></span>
                                <span class="aptoria-workspace-title">{{ __('messages.test_execution.short_title') }}</span>
                                <span class="badge pull-right">{{ $project->test_cases_count }}</span>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('projects.qa-coverage.index', $project) }}" class="btn btn-default btn-block aptoria-workspace-button">
                                <span class="aptoria-workspace-icon text-primary"><i class="fa fa-pie-chart"></i></span>
                                <span class="aptoria-workspace-title">{{ __('messages.qa_coverage.short_title') }}</span>
                                <span class="badge pull-right">{{ $qaCoverage['coverage_percent'] ?? 0 }}%</span>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('projects.qa-evidence.index', $project) }}" class="btn btn-default btn-block aptoria-workspace-button">
                                <span class="aptoria-workspace-icon text-violet"><i class="fa fa-archive"></i></span>
                                <span class="aptoria-workspace-title">{{ __('messages.qa_evidence.short_title') }}</span>
                                <span class="badge pull-right">{{ $project->snapshots_count }}</span>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('projects.contract-validations.index', $project) }}" class="btn btn-default btn-block aptoria-workspace-button">
                                <span class="aptoria-workspace-icon text-success"><i class="fa fa-code"></i></span>
                                <span class="aptoria-workspace-title">{{ __('messages.contract_validations.short_title') }}</span>
                                <span class="badge pull-right">{{ $project->contract_validation_runs_count }}</span>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('projects.settings.edit', $project) }}" class="btn btn-default btn-block aptoria-workspace-button">
                                <span class="aptoria-workspace-icon text-muted"><i class="fa fa-cog"></i></span>
                                <span class="aptoria-workspace-title">{{ __('messages.project_settings.title') }}</span>
                                <span class="badge pull-right"><i class="fa fa-angle-right"></i></span>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('projects.reports.index', $project) }}" class="btn btn-default btn-block aptoria-workspace-button">
                                <span class="aptoria-workspace-icon text-info"><i class="fa fa-file-text-o"></i></span>
                                <span class="aptoria-workspace-title">{{ __('messages.reports.title') }}</span>
                                <span class="badge pull-right">MD</span>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('projects.release-readiness.show', $project) }}" class="btn btn-default btn-block aptoria-workspace-button">
                                <span class="aptoria-workspace-icon text-warning"><i class="fa fa-check-circle"></i></span>
                                <span class="aptoria-workspace-title">{{ __('messages.release_readiness.title') }}</span>
                                <span class="badge pull-right">{{ $releaseReadiness['score'] }}</span>
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <a href="{{ route('projects.release-gates.index', $project) }}" class="btn btn-default btn-block aptoria-workspace-button">
                                <span class="aptoria-workspace-icon text-primary"><i class="fa fa-flag-checkered"></i></span>
                                <span class="aptoria-workspace-title">{{ __('messages.release_gates.short_title') }}</span>
                                <span class="badge pull-right">{{ $project->qa_release_gates_count }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@if($showProjectCalendarPreview ?? true)
<div class="row">
    <div class="col-lg-12">
        @include('calendar._preview', ['events' => $projectCalendarEvents, 'summary' => $projectCalendarSummary, 'project' => $project])
    </div>
</div>
@endif
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel h{{ $projectHealth['css'] === 'danger' ? 'red' : ($projectHealth['css'] === 'warning' ? 'yellow' : 'green') }} aptoria-project-health-panel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.scans.create', $project) }}" class="btn btn-xs btn-success"><i class="fa fa-plus"></i> {{ __('messages.scans.new') }}</a>
                    <a href="{{ route('projects.reports.index', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.reports.title') }}</a>
                </div>
                {{ __('messages.project_health.title') }}
            </div>
            <div class="panel-body">
                <div class="row aptoria-health-widget-grid">
                    <div class="col-md-3 col-sm-6">
                        <div class="hpanel aptoria-health-widget">
                            <div class="panel-body">
                                <div class="aptoria-widget-icon"><i class="fa fa-heartbeat"></i></div>
                                <div class="aptoria-widget-title">{{ __('messages.project_health.overall') }}</div>
                                <h3><span class="label label-{{ $projectHealth['css'] }}">{{ $projectHealth['label'] }}</span></h3>
                                <div class="text-muted">{{ $projectHealth['endpoint_count'] }} {{ __('messages.projects.endpoints') }}</div>
                            </div>
                            <div class="panel-footer">{{ __('messages.regressions.regression_status') }}: {{ $projectHealth['regression']['label'] ?? __('messages.regressions.statuses.none') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="hpanel aptoria-health-widget">
                            <div class="panel-body">
                                <div class="aptoria-widget-icon"><i class="fa fa-check-circle"></i></div>
                                <div class="aptoria-widget-title">{{ __('messages.project_health.assertion_signals') }}</div>
                                <h2>{{ $projectHealth['assertion_pass_count'] }}</h2>
                                <div class="text-muted">{{ $projectHealth['assertion_warning_count'] }} {{ __('messages.project_health.warnings') }} · {{ $projectHealth['assertion_fail_count'] }} {{ __('messages.project_health.failing') }}</div>
                            </div>
                            <div class="panel-footer">{{ __('messages.project_health.assertion_trend') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="hpanel aptoria-health-widget">
                            <div class="panel-body">
                                <div class="aptoria-widget-icon"><i class="fa fa-play-circle"></i></div>
                                <div class="aptoria-widget-title">{{ __('messages.test_execution.short_title') }}</div>
                                <h2>{{ $projectHealth['test_cases']['execution_percent'] ?? 0 }}%</h2>
                                <div class="text-muted">{{ __('messages.test_execution.pass_rate') }}: {{ $projectHealth['test_cases']['pass_rate'] ?? 0 }}%</div>
                                <div class="progress m-t-sm full progress-small">
                                    <div style="width: {{ $projectHealth['test_cases']['execution_percent'] ?? 0 }}%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="{{ $projectHealth['test_cases']['execution_percent'] ?? 0 }}" role="progressbar" class="progress-bar progress-bar-success"></div>
                                </div>
                            </div>
                            <div class="panel-footer">{{ $projectHealth['test_cases']['total'] ?? 0 }} {{ __('messages.test_cases.title') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="hpanel aptoria-health-widget">
                            <div class="panel-body">
                                <div class="aptoria-widget-icon"><i class="fa fa-bug"></i></div>
                                <div class="aptoria-widget-title">{{ __('messages.findings.title') }}</div>
                                <h2>{{ $projectHealth['findings']['open'] ?? 0 }}</h2>
                                <div class="text-muted">{{ $projectHealth['findings']['critical_open'] ?? 0 }} {{ __('messages.dashboard.critical') }} · {{ $projectHealth['findings']['high_open'] ?? 0 }} {{ __('messages.dashboard.high') }}</div>
                            </div>
                            <div class="panel-footer">{{ $projectHealth['regression']['detected_count'] ?? 0 }} {{ __('messages.regressions.detected_count') }}</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="hpanel aptoria-health-card">
                            <div class="panel-heading hbuilt">{{ __('messages.project_health.execution_quality') }}</div>
                            <div class="panel-body">
                                <div class="row text-center aptoria-mini-metrics">
                                    <div class="col-xs-4"><h4>{{ $projectHealth['test_cases'][\App\Models\TestCase::RUN_PASS] ?? 0 }}</h4><small>{{ __('messages.test_cases.run_statuses.pass') }}</small></div>
                                    <div class="col-xs-4"><h4>{{ $projectHealth['test_cases'][\App\Models\TestCase::RUN_FAIL] ?? 0 }}</h4><small>{{ __('messages.test_cases.run_statuses.fail') }}</small></div>
                                    <div class="col-xs-4"><h4>{{ $projectHealth['test_cases'][\App\Models\TestCase::RUN_BLOCKED] ?? 0 }}</h4><small>{{ __('messages.test_cases.run_statuses.blocked') }}</small></div>
                                </div>
                                <hr>
                                <div class="aptoria-health-progress-row">
                                    <div class="clearfix">
                                        <span class="pull-left">{{ __('messages.test_execution.execution_coverage') }}</span>
                                        <span class="pull-right">{{ $projectHealth['test_cases']['execution_percent'] ?? 0 }}%</span>
                                    </div>
                                    <div class="progress progress-small m-t-xs">
                                        <div style="width: {{ $projectHealth['test_cases']['execution_percent'] ?? 0 }}%" class="progress-bar progress-bar-info"></div>
                                    </div>
                                </div>
                                <div class="aptoria-health-progress-row">
                                    <div class="clearfix">
                                        <span class="pull-left">{{ __('messages.test_execution.pass_rate') }}</span>
                                        <span class="pull-right">{{ $projectHealth['test_cases']['pass_rate'] ?? 0 }}%</span>
                                    </div>
                                    <div class="progress progress-small m-t-xs">
                                        <div style="width: {{ $projectHealth['test_cases']['pass_rate'] ?? 0 }}%" class="progress-bar progress-bar-success"></div>
                                    </div>
                                </div>
                                <div class="row text-center m-t-md aptoria-mini-metrics">
                                    <div class="col-xs-6"><h4>{{ $projectHealth['test_cases'][\App\Models\TestCase::RUN_SKIPPED] ?? 0 }}</h4><small>{{ __('messages.test_cases.run_statuses.skipped') }}</small></div>
                                    <div class="col-xs-6"><h4>{{ $projectHealth['test_cases'][\App\Models\TestCase::RUN_NOT_RUN] ?? 0 }}</h4><small>{{ __('messages.test_cases.run_statuses.not_run') }}</small></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="hpanel aptoria-health-card">
                            <div class="panel-heading hbuilt">{{ __('messages.project_health.contract_quality') }}</div>
                            <div class="panel-body">
                                <div class="row text-center aptoria-mini-metrics">
                                    <div class="col-xs-4"><h4>{{ $projectHealth['contract']['total_runs'] ?? 0 }}</h4><small>{{ __('messages.contract_validations.runs') }}</small></div>
                                    <div class="col-xs-4"><h4>{{ $projectHealth['contract']['failed_count'] ?? 0 }}</h4><small>{{ __('messages.contract_validations.failed') }}</small></div>
                                    <div class="col-xs-4"><h4>{{ $projectHealth['contract']['warning_count'] ?? 0 }}</h4><small>{{ __('messages.contract_validations.warnings') }}</small></div>
                                </div>
                                <hr>
                                <ul class="list-unstyled aptoria-health-listing m-b-none">
                                    <li><span>{{ __('messages.contract_validations.breaking') }}</span><strong>{{ $projectHealth['contract']['breaking_count'] ?? 0 }}</strong></li>
                                    <li><span>{{ __('messages.contract_validations.missing_endpoints') }}</span><strong>{{ $projectHealth['contract']['missing_endpoint_count'] ?? 0 }}</strong></li>
                                    <li><span>{{ __('messages.contract_validations.undocumented_endpoints') }}</span><strong>{{ $projectHealth['contract']['undocumented_endpoint_count'] ?? 0 }}</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="hpanel aptoria-health-card">
                            <div class="panel-heading hbuilt">{{ __('messages.project_health.finding_pressure') }}</div>
                            <div class="panel-body">
                                <div class="row text-center aptoria-mini-metrics">
                                    <div class="col-xs-3"><h4>{{ $projectHealth['findings']['total'] ?? 0 }}</h4><small>{{ __('messages.findings.total') }}</small></div>
                                    <div class="col-xs-3"><h4>{{ $projectHealth['findings']['open'] ?? 0 }}</h4><small>{{ __('messages.findings.open_findings') }}</small></div>
                                    <div class="col-xs-3"><h4>{{ $projectHealth['findings']['critical_open'] ?? 0 }}</h4><small>{{ __('messages.findings.critical_open') }}</small></div>
                                    <div class="col-xs-3"><h4>{{ $projectHealth['findings']['high_open'] ?? 0 }}</h4><small>{{ __('messages.findings.high_open') }}</small></div>
                                </div>
                                <hr>
                                <div class="aptoria-health-evidence">
                                    <strong>{{ __('messages.project_health.latest_scan') }}</strong>
                                    <p class="text-muted">
                                        @if($projectHealth['latest_scan'])
                                            {{ $projectHealth['latest_scan']->started_at?->format('Y-m-d H:i') ?: $projectHealth['latest_scan']->created_at->format('Y-m-d H:i') }}
                                            — {{ __('messages.scans.scanned') }}: {{ $projectHealth['scanned_count'] }},
                                            {{ __('messages.scans.success') }}: {{ $projectHealth['success_count'] }},
                                            {{ __('messages.scans.warnings') }}: {{ $projectHealth['warning_count'] }},
                                            {{ __('messages.scans.errors') }}: {{ $projectHealth['error_count'] }}.
                                        @else
                                            {{ __('messages.project_health.no_scan_yet') }}
                                        @endif
                                    </p>
                                    <span class="label label-{{ $projectHealth['regression']['css'] ?? 'success' }}">{{ $projectHealth['regression']['label'] ?? __('messages.regressions.statuses.none') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if(trim($projectNotes ?? '') !== '')
                    <hr>
                    <strong>{{ __('messages.common.notes') }}</strong>
                    <p class="m-b-none">{{ $projectNotes }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel h{{ $releaseReadiness['css'] === 'danger' ? 'red' : ($releaseReadiness['css'] === 'warning' ? 'yellow' : 'green') }}">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.release-readiness.show', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.release_readiness.open_dashboard') }}</a>
                    <a href="{{ route('projects.reports.release-readiness.markdown', $project) }}" class="btn btn-xs btn-default">{{ __('messages.release_readiness.download_report') }}</a>
                </div>
                {{ __('messages.release_readiness.title') }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-2"><h2 class="m-t-xs"><span class="label label-{{ $releaseReadiness['css'] }}">{{ $releaseReadiness['label'] }}</span></h2><small>{{ __('messages.release_readiness.overall_status') }}</small></div>
                    <div class="col-sm-2"><h2 class="m-t-xs">{{ $releaseReadiness['score'] }}</h2><small>{{ __('messages.release_readiness.score') }}</small></div>
                    <div class="col-sm-2"><h2 class="m-t-xs">{{ $releaseReadiness['coverage_percent'] }}%</h2><small>{{ __('messages.release_readiness.coverage') }}</small></div>
                    <div class="col-sm-2"><h2 class="m-t-xs">{{ count($releaseReadiness['blocking_issues']) }}</h2><small>{{ __('messages.release_readiness.blocking_issues') }}</small></div>
                    <div class="col-sm-2"><h2 class="m-t-xs">{{ $releaseReadiness['assertion_counts']['fail'] ?? 0 }}</h2><small>{{ __('messages.assertions.statuses.fail') }}</small></div>
                    <div class="col-sm-2"><h2 class="m-t-xs">{{ $releaseReadiness['regression']['detected_count'] ?? 0 }}</h2><small>{{ __('messages.regressions.detected_count') }}</small></div>
                </div>
                <hr>
                @if(count($releaseReadiness['blocking_issues']) > 0)
                    <strong>{{ __('messages.release_readiness.blocking_issues') }}</strong>
                    <ul class="m-b-none">
                        @foreach(array_slice($releaseReadiness['blocking_issues'], 0, 3) as $issue)
                            <li>{{ $issue }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted m-b-none">{{ __('messages.release_readiness.no_blockers') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.release-gates.create', $project) }}" class="btn btn-xs btn-danger"><i class="fa fa-plus"></i> {{ __('messages.release_gates.create') }}</a>
                    <a href="{{ route('projects.release-gates.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.release_gates.history') }}</a>
                </div>
                {{ __('messages.release_gates.title') }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-2"><h3>{{ $project->qa_release_gates_count }}</h3><small>{{ __('messages.release_gates.saved_gates') }}</small></div>
                    <div class="col-sm-2"><h3><span class="label label-{{ $releaseReadiness['css'] }}">{{ $releaseReadiness['label'] }}</span></h3><small>{{ __('messages.release_gates.live_recommendation') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $releaseReadiness['score'] }}</h3><small>{{ __('messages.release_readiness.score') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $releaseReadiness['qa_coverage']['coverage_percent'] ?? 0 }}%</h3><small>{{ __('messages.qa_coverage.short_title') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $releaseReadiness['test_execution']['execution_percent'] ?? 0 }}%</h3><small>{{ __('messages.test_execution.execution_coverage') }}</small></div>
                    <div class="col-sm-2"><h3>{{ count($releaseReadiness['blocking_issues']) }}</h3><small>{{ __('messages.release_gates.blockers') }}</small></div>
                </div>
                <hr>
                @if($project->qaReleaseGates->isEmpty())
                    <p class="text-muted">{{ __('messages.release_gates.empty') }}</p>
                @else
                    <table class="table table-striped table-condensed m-b-none">
                        <thead><tr><th>{{ __('messages.release_gates.release_name') }}</th><th>{{ __('messages.release_gates.automated_status') }}</th><th>{{ __('messages.release_gates.final_decision') }}</th><th>{{ __('messages.common.created') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($project->qaReleaseGates as $gate)
                            <tr>
                                <td><strong>{{ $gate->release_name }}</strong></td>
                                <td><span class="label label-{{ $gate->automated_status_css }}">{{ $gate->automated_status_label }}</span></td>
                                <td><span class="label label-{{ $gate->final_decision_css }}">{{ $gate->final_decision_label }}</span></td>
                                <td>{{ $gate->created_at->format('Y-m-d H:i') }}</td>
                                <td class="text-right"><a href="{{ route('projects.release-gates.show', [$project, $gate]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.qa-coverage.index', $project) }}" class="btn btn-xs btn-success">{{ __('messages.qa_coverage.open_matrix') }}</a>
                </div>
                {{ __('messages.qa_coverage.summary_title') }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-2"><h3>{{ $qaCoverage['coverage_percent'] ?? 0 }}%</h3><small>{{ __('messages.qa_coverage.coverage_percent') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $qaCoverage['fully_covered'] ?? 0 }}</h3><small>{{ __('messages.qa_coverage.fully_covered') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $qaCoverage['missing_tests'] ?? 0 }}</h3><small>{{ __('messages.qa_coverage.gap_filters.missing_test_cases') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $qaCoverage['missing_assertions'] ?? 0 }}</h3><small>{{ __('messages.qa_coverage.gap_filters.missing_assertions') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $qaCoverage['not_scanned'] ?? 0 }}</h3><small>{{ __('messages.qa_coverage.gap_filters.not_scanned') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $qaCoverage['blocked'] ?? 0 }}</h3><small>{{ __('messages.qa_coverage.statuses.blocked') }}</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.findings.create', $project) }}" class="btn btn-xs btn-danger">{{ __('messages.findings.create') }}</a>
                    <a href="{{ route('projects.findings.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.findings.view_all') }}</a>
                </div>
                {{ __('messages.findings.title') }}
            </div>
            <div class="panel-body">
                @if($project->findings->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.findings.empty_help') }}</p>
                        <a href="{{ route('projects.findings.create', $project) }}" class="btn btn-danger btn-sm">{{ __('messages.findings.create') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed">
                            <thead><tr><th>{{ __('messages.findings.title_field') }}</th><th>{{ __('messages.findings.severity') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.endpoints.title') }}</th><th>{{ __('messages.findings.evidence') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($project->findings as $finding)
                                <tr>
                                    <td><strong>{{ $finding->title }}</strong><br><small class="text-muted">{{ \Illuminate\Support\Str::limit($finding->description, 100) }}</small></td>
                                    <td><span class="label label-{{ $finding->severity_css }}">{{ $finding->severity_label }}</span></td>
                                    <td><span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span></td>
                                    <td>@if($finding->endpoint)<a href="{{ route('projects.endpoints.show', [$project, $finding->endpoint]) }}"><code>{{ $finding->endpoint->method }} {{ $finding->endpoint->path }}</code></a>@else<span class="text-muted">{{ __('messages.common.none') }}</span>@endif</td>
                                    <td>{{ $finding->evidence->count() }}</td>
                                    <td class="text-right"><a href="{{ route('projects.findings.show', [$project, $finding]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
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
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.settings.edit', $project) }}" class="btn btn-xs btn-info">{{ __('messages.project_settings.edit') }}</a></div>
                {{ __('messages.project_settings.summary_title') }}
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>{{ __('messages.project_settings.fields.scan_enabled') }}</strong><br>
                        <span class="label label-{{ ($projectSettingSummary['scan_defaults']['scan.enabled']['value'] ?? true) ? 'success' : 'default' }}">
                            {{ ($projectSettingSummary['scan_defaults']['scan.enabled']['value'] ?? true) ? __('messages.common.yes') : __('messages.common.no') }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('messages.project_settings.fields.max_endpoints_per_scan') }}</strong><br>
                        {{ $projectSettingSummary['scan_defaults']['scan.max_endpoints_per_scan']['value'] ?? 100 }}
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('messages.project_settings.fields.allow_private_networks') }}</strong><br>
                        <span class="label label-{{ ($projectSettingSummary['scan_safety']['scan.allow_private_networks']['value'] ?? false) ? 'warning' : 'success' }}">
                            {{ ($projectSettingSummary['scan_safety']['scan.allow_private_networks']['value'] ?? false) ? __('messages.common.yes') : __('messages.common.no') }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>{{ __('messages.project_settings.fields.store_response_body_preview') }}</strong><br>
                        {{ ($projectSettingSummary['data_retention']['scan.store_response_body_preview']['value'] ?? true) ? __('messages.common.yes') : __('messages.common.no') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.environments.index', $project) }}" class="btn btn-xs btn-info">{{ __('messages.environments.manager_short') }}</a>
                    <a href="{{ route('projects.environments.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.environments.new') }}</a>
                </div>
                {{ __('messages.environments.title') }}
            </div>
            <div class="panel-body">
                @if($project->environments->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.environments.empty') }}</p>
                        <a href="{{ route('projects.environments.create', $project) }}" class="btn btn-success btn-sm">{{ __('messages.environments.create_button') }}</a>
                    </div>
                @else
                    <table class="table table-striped table-condensed">
                        <thead><tr><th>{{ __('messages.common.name') }}</th><th>{{ __('messages.common.base_url') }}</th><th>{{ __('messages.auth_profiles.title') }}</th><th>{{ __('messages.common.type') }}</th><th>{{ __('messages.environments.default_environment') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($project->environments as $environment)
                            <tr>
                                <td><strong>{{ $environment->name }}</strong></td>
                                <td><code>{{ $environment->base_url }}</code></td>
                                <td>{{ $environment->authProfile?->name ?: __('messages.environments.use_project_default_auth') }}</td>
                                <td><span class="label label-{{ $environment->environment_type_css }}">{{ $environment->environment_type_label }}</span></td>
                                <td>
                                    @if((string) ($defaultEnvironmentId ?? '') === (string) $environment->id)
                                        <span class="label label-success"><i class="fa fa-check"></i> {{ __('messages.common.yes') }}</span>
                                    @else
                                        <span class="text-muted">{{ __('messages.common.no') }}</span>
                                    @endif
                                </td>
                                <td class="text-right"><a href="{{ route('projects.environments.edit', [$project, $environment]) }}" class="btn btn-xs btn-default">{{ __('messages.common.edit') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.auth-profiles.create', $project) }}" class="btn btn-xs btn-info">{{ __('messages.auth_profiles.new') }}</a></div>
                {{ __('messages.auth_profiles.title') }}
            </div>
            <div class="panel-body">
                @if($project->authProfiles->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.auth_profiles.empty') }}</p>
                        <a href="{{ route('projects.auth-profiles.create', $project) }}" class="btn btn-info btn-sm">{{ __('messages.auth_profiles.create_button') }}</a>
                    </div>
                @else
                    <table class="table table-striped table-condensed">
                        <thead><tr><th>{{ __('messages.common.name') }}</th><th>{{ __('messages.common.type') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.common.summary') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($project->authProfiles as $authProfile)
                            <tr>
                                <td>
                                    <strong>{{ $authProfile->name }}</strong>
                                    @if($authProfile->is_default)
                                        <span class="label label-success">{{ __('messages.auth_profiles.default') }}</span>
                                    @endif
                                </td>
                                <td>{{ $authProfile->type_label }}</td>
                                <td><span class="label label-{{ $authProfile->scan_ready_css }}">{{ $authProfile->scan_ready_label }}</span></td>
                                <td><code>{{ $authProfile->masked_summary }}</code></td>
                                <td class="text-right"><a href="{{ route('projects.auth-profiles.edit', [$project, $authProfile]) }}" class="btn btn-xs btn-default">{{ __('messages.common.edit') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.endpoint-inventory.index', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.endpoint_inventory.short_title') }}</a>
                    <a href="{{ route('projects.endpoints.import.form', $project) }}" class="btn btn-xs btn-info">{{ __('messages.endpoints.import_title') }}</a>
                    <a href="{{ route('projects.endpoints.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.endpoints.new') }}</a>
                    <a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.endpoints.view_all') }}</a>
                </div>
                {{ __('messages.endpoints.inventory') }}
            </div>
            <div class="panel-body">
                @if($project->endpoints->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.endpoints.empty_help') }}</p>
                        <a href="{{ route('projects.endpoints.create', $project) }}" class="btn btn-success btn-sm">{{ __('messages.endpoints.create_button') }}</a>
                    </div>
                @else
                    <table class="table table-striped table-condensed">
                        <thead><tr><th>{{ __('messages.endpoints.method') }}</th><th>{{ __('messages.endpoints.path') }}</th><th>{{ __('messages.endpoints.risk_level') }}</th><th>{{ __('messages.endpoints.auth') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($project->endpoints as $endpoint)
                            <tr>
                                <td><span class="label label-default">{{ $endpoint->method }}</span></td>
                                <td><code>{{ $endpoint->path }}</code></td>
                                <td><span class="label label-{{ $endpoint->risk_css }}">{{ $endpoint->risk_label }}</span></td>
                                <td>{{ $endpoint->auth_required ? __('messages.common.yes') : __('messages.common.no') }}</td>
                                <td class="text-right"><a href="{{ route('projects.endpoints.show', [$project, $endpoint]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.test-suites.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.test_suites.create') }}</a>
                    <a href="{{ route('projects.test-cases.create', $project) }}" class="btn btn-xs btn-info">{{ __('messages.test_cases.create') }}</a>
                    <a href="{{ route('projects.test-execution.index', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.test_execution.open_dashboard') }}</a>
                    <a href="{{ route('projects.test-suites.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.test_suites.view_all') }}</a>
                </div>
                {{ __('messages.test_suites.title') }}
            </div>
            <div class="panel-body">
                @if($project->testSuites->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.test_suites.empty_help') }}</p>
                        <a href="{{ route('projects.test-suites.create', $project) }}" class="btn btn-success btn-sm">{{ __('messages.test_suites.create') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed">
                            <thead><tr><th>{{ __('messages.common.name') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.test_cases.title') }}</th><th>{{ __('messages.test_cases.latest_result') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($project->testSuites as $suite)
                                @php($latestCase = $project->testCases->where('test_suite_id', $suite->id)->first())
                                <tr>
                                    <td><strong>{{ $suite->name }}</strong></td>
                                    <td><span class="label label-{{ $suite->status_css }}">{{ $suite->status_label }}</span></td>
                                    <td>{{ $suite->test_cases_count }}</td>
                                    <td>
                                        @if($latestCase)
                                            <span class="label label-{{ $latestCase->last_run_status_css }}">{{ $latestCase->last_run_status_label }}</span>
                                        @else
                                            <span class="text-muted">{{ __('messages.common.not_available') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right"><a href="{{ route('projects.test-suites.show', [$project, $suite]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
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
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.scans.create', $project) }}" class="btn btn-xs btn-success"><i class="fa fa-plus"></i> {{ __('messages.scans.new') }}</a>
                    <a href="{{ route('projects.scans.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.scans.view_all') }}</a>
                </div>
                {{ __('messages.scans.safe_probe_engine') }}
            </div>
            <div class="panel-body">
                @if($project->scanRuns->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.scans.empty_help') }}</p>
                        <a href="{{ route('projects.scans.create', $project) }}" class="btn btn-success btn-sm">{{ __('messages.scans.run_scan') }}</a>
                    </div>
                @else
                    <table class="table table-striped table-condensed">
                        <thead><tr><th>{{ __('messages.scans.started_at') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.scans.scanned') }}</th><th>{{ __('messages.scans.skipped') }}</th><th>{{ __('messages.scans.duration') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($project->scanRuns as $scanRun)
                            <tr>
                                <td>{{ $scanRun->started_at?->format('Y-m-d H:i:s') ?: $scanRun->created_at->format('Y-m-d H:i:s') }}</td>
                                <td><span class="label label-{{ $scanRun->status_css }}">{{ $scanRun->status_label }}</span></td>
                                <td>{{ $scanRun->scanned_count }}</td>
                                <td>{{ $scanRun->skipped_count }}</td>
                                <td>{{ $scanRun->duration_label }}</td>
                                <td class="text-right"><a href="{{ route('projects.scans.show', [$project, $scanRun]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.monitors.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.monitors.create') }}</a>
                    <a href="{{ route('projects.monitors.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.monitors.view_all') }}</a>
                </div>
                {{ __('messages.monitors.title') }}
            </div>
            <div class="panel-body">
                @if($project->apiMonitors->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.monitors.empty_help') }}</p>
                        <a href="{{ route('projects.monitors.create', $project) }}" class="btn btn-success btn-sm">{{ __('messages.monitors.create') }}</a>
                    </div>
                @else
                    <table class="table table-striped table-condensed">
                        <thead><tr><th>{{ __('messages.common.name') }}</th><th>{{ __('messages.monitors.frequency') }}</th><th>{{ __('messages.monitors.next_run') }}</th><th>{{ __('messages.common.status') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($project->apiMonitors as $monitor)
                            <tr>
                                <td><strong>{{ $monitor->name }}</strong></td>
                                <td>{{ $monitor->frequency_label }}</td>
                                <td>{{ $monitor->next_run_label }}</td>
                                <td><span class="label label-{{ $monitor->last_status_css }}">{{ $monitor->last_status_label }}</span></td>
                                <td class="text-right"><a href="{{ route('projects.monitors.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.snapshots.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.snapshots.view_all') }}</a>
                </div>
                {{ __('messages.snapshots.title') }}
            </div>
            <div class="panel-body">
                @if($project->snapshots->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.snapshots.empty_help') }}</p>
                        <a href="{{ route('projects.scans.index', $project) }}" class="btn btn-success btn-sm">{{ __('messages.scans.view_all') }}</a>
                    </div>
                @else
                    <table class="table table-striped table-condensed">
                        <thead><tr><th>{{ __('messages.common.name') }}</th><th>{{ __('messages.environments.title') }}</th><th>{{ __('messages.snapshots.endpoint_count') }}</th><th>{{ __('messages.common.created') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($project->snapshots as $snapshot)
                            <tr>
                                <td>{{ $snapshot->name }}</td>
                                <td>{{ $snapshot->environment?->name ?: __('messages.endpoints.project_default') }}</td>
                                <td>{{ $snapshot->endpoint_count }}</td>
                                <td>{{ $snapshot->created_at->format('Y-m-d H:i') }}</td>
                                <td class="text-right"><a href="{{ route('projects.snapshots.show', [$project, $snapshot]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>



<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.contract-validations.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.contract_validations.new') }}</a>
                    <a href="{{ route('projects.contract-validations.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.contract_validations.view_all') }}</a>
                </div>
                {{ __('messages.contract_validations.title') }}
            </div>
            <div class="panel-body">
                @if($project->contractValidationRuns->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.contract_validations.empty_help') }}</p>
                        <a href="{{ route('projects.contract-validations.create', $project) }}" class="btn btn-success btn-sm">{{ __('messages.contract_validations.new') }}</a>
                    </div>
                @else
                    <table class="table table-striped table-condensed">
                        <thead><tr><th>{{ __('messages.common.created') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.contract_validations.breaking') }}</th><th>{{ __('messages.contract_validations.failed') }}</th><th>{{ __('messages.contract_validations.warnings') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($project->contractValidationRuns as $run)
                            <tr>
                                <td>{{ $run->created_at->format('Y-m-d H:i') }}</td>
                                <td><span class="label label-{{ $run->status_css }}">{{ $run->health_label }}</span></td>
                                <td>{{ $run->breaking_count }}</td>
                                <td>{{ $run->failed_count }}</td>
                                <td>{{ $run->warning_count }}</td>
                                <td class="text-right"><a href="{{ route('projects.contract-validations.show', [$project, $run]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.reports.index', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.reports.open_center') }}</a>
                    <a href="{{ route('projects.reports.builder.create', $project) }}" class="btn btn-xs btn-success">{{ __('messages.report_builder.short_title') }}</a>
                    <a href="{{ route('projects.reports.full-project.markdown', $project) }}" class="btn btn-xs btn-info">Full MD</a>
                    <a href="{{ route('projects.reports.full-project.html', $project) }}" class="btn btn-xs btn-default">Full HTML</a>
                    <a href="{{ route('projects.reports.full-project.pdf', $project) }}" class="btn btn-xs btn-default">Full PDF</a>
                    <a href="{{ route('projects.reports.endpoints.csv', $project) }}" class="btn btn-xs btn-default">{{ __('messages.reports.endpoint_csv') }}</a>
                </div>
                {{ __('messages.reports.title') }}
            </div>
            <div class="panel-body">
                <p class="text-muted m-b-none">{{ __('messages.reports.project_intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.projects.status_panel_title') }}</div>
            <div class="panel-body">
                <p><strong>{{ __('messages.projects.done') }}</strong> {{ __('messages.projects.done_text') }}</p>
                <p><strong>{{ __('messages.projects.next') }}</strong> {{ __('messages.projects.next_text') }}</p>
                <p class="m-b-none"><strong>{{ __('messages.projects.important') }}</strong> {{ __('messages.projects.important_text') }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.projects.danger_zone') }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.destroy', $project) }}" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.common.confirm_delete_project') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">{{ __('messages.projects.delete_project') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
