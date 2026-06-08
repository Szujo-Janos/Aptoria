@extends('layouts.app')

@section('title', __('messages.report_builder.title'))

@section('content')
<div class="normalheader small-header">
    <div class="hpanel">
        <div class="panel-body">
            <div class="pull-right">
                <a href="{{ route('projects.reports.index', $project) }}" class="btn btn-default btn-sm">{{ __('messages.common.back') }}</a>
                <a href="{{ route('projects.show', $project) }}" class="btn btn-info btn-sm">{{ __('messages.projects.details') }}</a>
            </div>
            <h2 class="font-light m-b-xs">{{ __('messages.report_builder.title') }}</h2>
            <small>{{ __('messages.report_builder.intro') }}</small>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <strong>{{ __('messages.common.needs_fix') }}</strong>
        <ul class="m-b-none">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('projects.reports.builder.markdown', $project) }}">
    @csrf

    <div class="row">
        <div class="col-lg-8">
            <div class="hpanel hgreen">
                <div class="panel-heading hbuilt">
                    <i class="fa fa-file-text-o"></i> {{ __('messages.report_builder.configuration') }}
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for="report-title">{{ __('messages.report_builder.fields.title') }}</label>
                        <input id="report-title" name="title" value="{{ old('title', __('messages.report_builder.default_title')) }}" class="form-control" maxlength="160">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="report-audience">{{ __('messages.report_builder.fields.audience') }}</label>
                                <select id="report-audience" name="audience" class="form-control">
                                    @foreach($audiences as $key => $label)
                                        <option value="{{ $key }}" @selected(old('audience', 'internal') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="report-decision">{{ __('messages.report_builder.fields.decision') }}</label>
                                <select id="report-decision" name="decision" class="form-control">
                                    @foreach($decisions as $key => $label)
                                        <option value="{{ $key }}" @selected(old('decision', 'draft') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="scope-notes">{{ __('messages.report_builder.fields.scope_notes') }}</label>
                        <textarea id="scope-notes" name="scope_notes" class="form-control" rows="4" placeholder="{{ __('messages.report_builder.scope_notes_placeholder') }}">{{ old('scope_notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="hpanel hblue">
                <div class="panel-heading hbuilt">
                    <i class="fa fa-list"></i> {{ __('messages.report_builder.sections_title') }}
                </div>
                <div class="panel-body">
                    <p class="text-muted">{{ __('messages.report_builder.sections_help') }}</p>
                    <div class="row">
                        @foreach($sections as $section)
                            <div class="col-md-6">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="sections[]" value="{{ $section }}" @checked(in_array($section, old('sections', $defaultSections), true))>
                                        <strong>{{ __('messages.report_builder.sections.'.$section) }}</strong><br>
                                        <small class="text-muted">{{ __('messages.report_builder.section_help.'.$section) }}</small>
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="hpanel hyellow">
                <div class="panel-heading hbuilt">
                    <i class="fa fa-filter"></i> {{ __('messages.report_builder.output_options') }}
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('messages.report_builder.fields.endpoint_limit') }}</label>
                                <input type="number" name="endpoint_limit" class="form-control" min="5" max="500" value="{{ old('endpoint_limit', 100) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('messages.report_builder.fields.test_case_limit') }}</label>
                                <input type="number" name="test_case_limit" class="form-control" min="5" max="500" value="{{ old('test_case_limit', 100) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('messages.report_builder.fields.finding_limit') }}</label>
                                <input type="number" name="finding_limit" class="form-control" min="5" max="500" value="{{ old('finding_limit', 100) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('messages.report_builder.fields.contract_result_limit') }}</label>
                                <input type="number" name="contract_result_limit" class="form-control" min="5" max="500" value="{{ old('contract_result_limit', 100) }}">
                            </div>
                        </div>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="problem_endpoints_only" value="1" @checked(old('problem_endpoints_only'))>
                            {{ __('messages.report_builder.fields.problem_endpoints_only') }}
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="include_evidence_details" value="1" @checked(old('include_evidence_details'))>
                            {{ __('messages.report_builder.fields.include_evidence_details') }}
                        </label>
                    </div>
                </div>
                <div class="panel-footer text-right">
                    <a href="{{ route('projects.reports.full-project.markdown', $project) }}" class="btn btn-default">{{ __('messages.reports.download_full_project_markdown') }}</a>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-download"></i> {{ __('messages.report_builder.generate_markdown') }}</button>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="hpanel hred">
                <div class="panel-heading hbuilt">{{ __('messages.release_readiness.title') }}</div>
                <div class="panel-body text-center">
                    <h2><span class="label label-{{ $summary['css'] }}">{{ $summary['label'] }}</span></h2>
                    <h3>{{ $summary['score'] }} / 100</h3>
                    <p class="text-muted m-b-none">{{ __('messages.report_builder.snapshot_note') }}</p>
                </div>
            </div>

            <div class="hpanel hgreen">
                <div class="panel-heading hbuilt">{{ __('messages.report_builder.evidence_snapshot') }}</div>
                <div class="panel-body">
                    <table class="table table-condensed m-b-none">
                        <tr><th>{{ __('messages.projects.endpoints') }}</th><td class="text-right">{{ $project->endpoints_count }}</td></tr>
                        <tr><th>{{ __('messages.test_cases.title') }}</th><td class="text-right">{{ $project->test_cases_count }}</td></tr>
                        <tr><th>{{ __('messages.findings.open_findings') }}</th><td class="text-right">{{ $summary['finding_counts']['open'] }}</td></tr>
                        <tr><th>{{ __('messages.qa_coverage.coverage_percent') }}</th><td class="text-right">{{ $coverage['coverage_percent'] }}%</td></tr>
                        <tr><th>{{ __('messages.test_execution.execution_coverage') }}</th><td class="text-right">{{ $summary['test_execution']['execution_percent'] }}%</td></tr>
                        <tr><th>{{ __('messages.contract_validations.runs') }}</th><td class="text-right">{{ $project->contract_validation_runs_count }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="hpanel hblue">
                <div class="panel-heading hbuilt">{{ __('messages.report_builder.latest_evidence') }}</div>
                <div class="panel-body">
                    <p><strong>{{ __('messages.scans.title') }}:</strong><br>{{ $latestScan ? '#'.$latestScan->id.' — '.$latestScan->status_label : __('messages.common.not_available') }}</p>
                    <p><strong>{{ __('messages.contract_validations.title') }}:</strong><br>{{ $latestContract ? '#'.$latestContract->id.' — '.$latestContract->health_label : __('messages.common.not_available') }}</p>
                    <p class="m-b-none text-muted">{{ __('messages.report_builder.safe_export_note') }}</p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
