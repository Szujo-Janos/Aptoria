@extends('layouts.app')

@section('title', __('messages.demo_project.title'))

@section('page_actions')
    @if($summary['exists'] && $summary['project'])
        <a href="{{ route('projects.show', $summary['project']) }}" class="btn btn-primary btn-sm"><i class="fa fa-briefcase"></i> {{ __('messages.demo_project.open_project') }}</a>
        <a href="{{ route('projects.reports.index', $summary['project']) }}" class="btn btn-default btn-sm"><i class="fa fa-file-text-o"></i> {{ __('messages.nav.reports') }}</a>
    @endif
@endsection

@section('content')
@php
    $project = $summary['project'] ?? null;
    $counts = $summary['counts'] ?? [];
    $readiness = $summary['readiness'] ?? null;
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue aptoria-workflow-panel">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="label label-info m-b-sm">{{ __('messages.demo_project.badge') }}</div>
                        <h2 class="font-light m-b-xs">{{ __('messages.demo_project.heading') }}</h2>
                        <p class="text-muted m-b-md">{{ __('messages.demo_project.intro') }}</p>
                        <div class="m-t-sm">
                            @if($summary['exists'])
                                <span class="label label-success"><i class="fa fa-check"></i> {{ __('messages.demo_project.status_imported') }}</span>
                                <span class="text-muted m-l-sm">{{ $summary['slug'] }}</span>
                            @else
                                <span class="label label-default"><i class="fa fa-circle-o"></i> {{ __('messages.demo_project.status_not_imported') }}</span>
                                <span class="text-muted m-l-sm">{{ $summary['slug'] }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        @if($readiness)
                            <div class="aptoria-mode-chip" style="display:inline-block;min-width:160px;">
                                <div class="aptoria-mode-chip-title"><i class="fa fa-flag-checkered"></i> {{ $readiness['score'] ?? 0 }}%</div>
                                <small>{{ __('messages.demo_project.demo_readiness_score') }}</small>
                            </div>
                        @endif
                        <div class="m-t-md">
                            <form method="POST" action="{{ route('demo-project.import') }}" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fa fa-magic"></i> {{ $summary['exists'] ? __('messages.demo_project.reimport_button') : __('messages.demo_project.import_button') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel-footer">
                <i class="fa fa-shield"></i> {{ __('messages.demo_project.safe_note') }}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="hpanel hgreen aptoria-kpi-card">
            <div class="panel-body">
                <div class="aptoria-kpi-icon"><i class="fa fa-sitemap"></i></div>
                <h2>{{ $counts['endpoints'] ?? 0 }}</h2>
                <div class="font-bold">{{ __('messages.nav.endpoints') }}</div>
                <small>{{ $counts['environments'] ?? 0 }} {{ __('messages.environments.title') }} · {{ $counts['auth_profiles'] ?? 0 }} {{ __('messages.auth_profiles.title') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="hpanel hblue aptoria-kpi-card">
            <div class="panel-body">
                <div class="aptoria-kpi-icon"><i class="fa fa-crosshairs"></i></div>
                <h2>{{ $counts['scan_runs'] ?? 0 }}</h2>
                <div class="font-bold">{{ __('messages.nav.scans') }}</div>
                <small>{{ $counts['snapshots'] ?? 0 }} {{ __('messages.nav.snapshots') }} · {{ $counts['compare_runs'] ?? 0 }} compare</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="hpanel horange aptoria-kpi-card">
            <div class="panel-body">
                <div class="aptoria-kpi-icon"><i class="fa fa-bug"></i></div>
                <h2>{{ $counts['findings'] ?? 0 }}</h2>
                <div class="font-bold">{{ __('messages.findings.title') }}</div>
                <small>{{ $counts['finding_evidence'] ?? 0 }} {{ __('messages.qa_evidence.short_title') }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="hpanel hviolet aptoria-kpi-card">
            <div class="panel-body">
                <div class="aptoria-kpi-icon"><i class="fa fa-check-square-o"></i></div>
                <h2>{{ $counts['test_cases'] ?? 0 }}</h2>
                <div class="font-bold">{{ __('messages.test_cases.title') }}</div>
                <small>{{ $counts['test_suites'] ?? 0 }} {{ __('messages.test_suites.title') }} · {{ $counts['test_case_results'] ?? 0 }} results</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><span class="label label-info">{{ __('messages.demo_project.contents_label') }}</span></div>
                {{ __('messages.demo_project.contents_title') }}
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-sm-6">
                        <ul class="list-unstyled aptoria-check-list">
                            <li><i class="fa fa-check text-success"></i> {{ __('messages.demo_project.includes.project') }}</li>
                            <li><i class="fa fa-check text-success"></i> {{ __('messages.demo_project.includes.environments') }}</li>
                            <li><i class="fa fa-check text-success"></i> {{ __('messages.demo_project.includes.auth') }}</li>
                            <li><i class="fa fa-check text-success"></i> {{ __('messages.demo_project.includes.endpoints') }}</li>
                            <li><i class="fa fa-check text-success"></i> {{ __('messages.demo_project.includes.scans') }}</li>
                        </ul>
                    </div>
                    <div class="col-sm-6">
                        <ul class="list-unstyled aptoria-check-list">
                            <li><i class="fa fa-check text-success"></i> {{ __('messages.demo_project.includes.findings') }}</li>
                            <li><i class="fa fa-check text-success"></i> {{ __('messages.demo_project.includes.evidence') }}</li>
                            <li><i class="fa fa-check text-success"></i> {{ __('messages.demo_project.includes.suites') }}</li>
                            <li><i class="fa fa-check text-success"></i> {{ __('messages.demo_project.includes.release_gate') }}</li>
                            <li><i class="fa fa-check text-success"></i> {{ __('messages.demo_project.includes.monitor') }}</li>
                        </ul>
                    </div>
                </div>
                @if($project)
                    <hr>
                    <div class="alert alert-success m-b-md">
                        <strong>{{ $project->name }}</strong><br>
                        <span class="text-muted">{{ __('messages.demo_project.status_imported') }} · {{ $project->slug }}</span>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('projects.show', $project) }}" class="btn btn-primary btn-sm"><i class="fa fa-briefcase"></i> {{ __('messages.demo_project.open_project') }}</a>
                        <a href="{{ route('projects.endpoint-inventory.index', $project) }}" class="btn btn-default btn-sm"><i class="fa fa-list-alt"></i> {{ __('messages.nav.endpoint_inventory') }}</a>
                        <a href="{{ route('projects.release-readiness.show', $project) }}" class="btn btn-default btn-sm"><i class="fa fa-check-circle"></i> {{ __('messages.nav.release_readiness_short') }}</a>
                        <a href="{{ route('projects.reports.index', $project) }}" class="btn btn-default btn-sm"><i class="fa fa-file-text-o"></i> {{ __('messages.nav.reports') }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.demo_project.reset_title') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.demo_project.reset_help') }}</p>
                <form method="POST" action="{{ route('demo-project.remove') }}">
                    @csrf
                    @method('DELETE')
                    <div class="checkbox">
                        <label><input type="checkbox" name="confirm" value="1"> {{ __('messages.demo_project.confirm_remove') }}</label>
                    </div>
                    <button type="submit" class="btn btn-danger btn-block" {{ $summary['exists'] ? '' : 'disabled' }}>
                        <i class="fa fa-trash"></i> {{ __('messages.demo_project.remove_button') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.demo_project.cli_title') }}</div>
            <div class="panel-body">
                <pre class="m-b-sm">php artisan aptoria:demo-project
php artisan aptoria:demo-project --json
php artisan aptoria:demo-project --remove</pre>
                <p class="small text-muted m-b-none">{{ __('messages.demo_project.cli_help') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
