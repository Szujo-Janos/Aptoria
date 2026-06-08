@extends('layouts.app')

@section('title', __('messages.reports.project_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('reports.index') }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-info">{{ __('messages.projects.details') }}</a>
                </div>
                {{ __('messages.reports.project_title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.reports.project_intro') }}</p>
                <div class="row text-center">
                    <div class="col-sm-2"><h3>{{ $project->endpoints_count }}</h3><small>{{ __('messages.projects.endpoints') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $project->scan_runs_count }}</h3><small>{{ __('messages.projects.scans') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $project->snapshots_count }}</h3><small>{{ __('messages.snapshots.saved_snapshots') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $project->compare_runs_count }}</h3><small>{{ __('messages.snapshots.recent_compares') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $project->test_suites_count }}</h3><small>{{ __('messages.test_suites.title') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $project->test_cases_count }}</h3><small>{{ __('messages.test_cases.title') }}</small></div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-sm-6"><h3>{{ $project->contract_validation_runs_count }}</h3><small>{{ __('messages.contract_validations.runs') }}</small></div>
                    <div class="col-sm-6"><h3>{{ $project->qa_release_gates_count }}</h3><small>{{ __('messages.release_gates.saved_gates') }}</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.release_readiness.title') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.release_readiness.report_help') }}</p>
                <a href="{{ route('projects.release-readiness.show', $project) }}" class="btn btn-danger btn-block">
                    {{ __('messages.release_readiness.open_dashboard') }}
                </a>
                <a href="{{ route('projects.reports.release-readiness.markdown', $project) }}" class="btn btn-default btn-block">
                    {{ __('messages.release_readiness.download_report') }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.release_gates.short_title') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.release_gates.report_help') }}</p>
                <a href="{{ route('projects.release-gates.create', $project) }}" class="btn btn-danger btn-block">{{ __('messages.release_gates.create') }}</a>
                <a href="{{ route('projects.release-gates.index', $project) }}" class="btn btn-default btn-block">{{ __('messages.release_gates.history') }}</a>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.reports.custom_report_builder') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.reports.custom_report_builder_help') }}</p>
                <a href="{{ route('projects.reports.builder.create', $project) }}" class="btn btn-success btn-block">
                    {{ __('messages.reports.open_report_builder') }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.reports.full_project_report') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.reports.full_project_report_help') }}</p>
                <a href="{{ route('projects.reports.full-project.markdown', $project) }}" class="btn btn-primary btn-block">
                    {{ __('messages.reports.download_full_project_markdown') }}
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.qa_evidence.title') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.qa_evidence.report_panel_help') }}</p>
                <a href="{{ route('projects.qa-evidence.index', $project) }}" class="btn btn-primary">{{ __('messages.qa_evidence.open_builder') }}</a>
                <a href="{{ route('projects.qa-evidence.notes', $project) }}" class="btn btn-success">{{ __('messages.qa_evidence.download_notes') }}</a>
                <a href="{{ route('projects.qa-evidence.zip', $project) }}" class="btn btn-default">{{ __('messages.qa_evidence.download_zip') }}</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.reports.endpoint_inventory_exports') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.reports.endpoint_csv_help') }}</p>
                <a href="{{ route('projects.reports.endpoints.csv', $project) }}" class="btn btn-success btn-block">
                    {{ __('messages.reports.download_endpoint_csv') }}
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.reports.snapshot_exports') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.reports.snapshot_json_help') }}</p>
                @if($latestSnapshots->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.snapshots.empty_help') }}</p>
                @else
                    <div class="list-group m-b-none">
                        @foreach($latestSnapshots as $snapshot)
                            <a class="list-group-item" href="{{ route('projects.reports.snapshots.json', [$project, $snapshot]) }}">
                                <span class="pull-right label label-info">JSON</span>
                                #{{ $snapshot->id }} — {{ $snapshot->name }}<br>
                                <small class="text-muted">{{ $snapshot->created_at->format('Y-m-d H:i') }}</small>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.reports.markdown_reports') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.reports.markdown_help') }}</p>
                <a href="{{ route('projects.scans.index', $project) }}" class="btn btn-default btn-block">{{ __('messages.scans.view_all') }}</a>
                <a href="{{ route('projects.snapshots.index', $project) }}" class="btn btn-default btn-block">{{ __('messages.snapshots.view_all') }}</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">{{ __('messages.release_gates.history') }}</div>
            <div class="panel-body no-padding">
                @if($latestReleaseGates->isEmpty())
                    <div class="p-md"><p class="text-muted m-b-none">{{ __('messages.release_gates.empty') }}</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed m-b-none">
                            <thead><tr><th>{{ __('messages.release_gates.release_name') }}</th><th>{{ __('messages.release_gates.automated_status') }}</th><th>{{ __('messages.release_gates.final_decision') }}</th><th>{{ __('messages.release_readiness.score') }}</th><th>{{ __('messages.common.created') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($latestReleaseGates as $gate)
                                <tr>
                                    <td><strong>{{ $gate->release_name }}</strong></td>
                                    <td><span class="label label-{{ $gate->automated_status_css }}">{{ $gate->automated_status_label }}</span></td>
                                    <td><span class="label label-{{ $gate->final_decision_css }}">{{ $gate->final_decision_label }}</span></td>
                                    <td>{{ $gate->score }}</td>
                                    <td>{{ $gate->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="text-right"><a href="{{ route('projects.release-gates.markdown', [$project, $gate]) }}" class="btn btn-xs btn-primary">{{ __('messages.release_gates.download_markdown') }}</a></td>
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
    <div class="col-lg-6">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.reports.recent_scan_reports') }}</div>
            <div class="panel-body">
                @if($latestScanRuns->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.scans.empty_help') }}</p>
                @else
                    <table class="table table-striped table-condensed">
                        <thead><tr><th>{{ __('messages.scans.started_at') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.scans.scanned') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($latestScanRuns as $scanRun)
                            <tr>
                                <td>{{ $scanRun->started_at?->format('Y-m-d H:i') ?: $scanRun->created_at->format('Y-m-d H:i') }}</td>
                                <td><span class="label label-{{ $scanRun->status_css }}">{{ $scanRun->status_label }}</span></td>
                                <td>{{ $scanRun->scanned_count }}</td>
                                <td class="text-right"><a href="{{ route('projects.reports.scans.markdown', [$project, $scanRun]) }}" class="btn btn-xs btn-primary">{{ __('messages.reports.download_markdown') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.reports.recent_compare_reports') }}</div>
            <div class="panel-body">
                @if($latestCompareRuns->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.snapshots.no_compares') }}</p>
                @else
                    <table class="table table-striped table-condensed">
                        <thead><tr><th>{{ __('messages.snapshots.baseline_snapshot') }}</th><th>{{ __('messages.snapshots.target_snapshot') }}</th><th>{{ __('messages.snapshots.total_changes') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($latestCompareRuns as $compareRun)
                            <tr>
                                <td>{{ $compareRun->snapshotA?->name }}</td>
                                <td>{{ $compareRun->snapshotB?->name }}</td>
                                <td>{{ $compareRun->summary_json['total_changes'] ?? 0 }}</td>
                                <td class="text-right"><a href="{{ route('projects.reports.compares.markdown', [$project, $compareRun]) }}" class="btn btn-xs btn-primary">{{ __('messages.reports.download_markdown') }}</a></td>
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
                {{ __('messages.contract_validations.recent_runs') }}
            </div>
            <div class="panel-body">
                @if($latestContractValidationRuns->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.contract_validations.empty_help') }}</p>
                @else
                    <table class="table table-striped table-condensed">
                        <thead><tr><th>{{ __('messages.common.created') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.contract_validations.breaking') }}</th><th>{{ __('messages.contract_validations.failed') }}</th><th>{{ __('messages.contract_validations.warnings') }}</th><th></th></tr></thead>
                        <tbody>
                        @foreach($latestContractValidationRuns as $run)
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
@endsection
