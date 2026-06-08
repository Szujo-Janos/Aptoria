@extends('layouts.app')

@section('title', __('messages.qa_evidence.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.reports.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.qa_evidence.title') }}
            </div>
            <div class="panel-body">
                <h3 class="m-t-none">{{ $project->name }}</h3>
                <p class="text-muted">{{ __('messages.qa_evidence.intro') }}</p>
                <div class="row text-center">
                    <div class="col-sm-2"><h3>{{ $project->endpoints_count ?? $project->endpoints()->count() }}</h3><small>{{ __('messages.projects.endpoints') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $project->scan_runs_count ?? $project->scanRuns()->count() }}</h3><small>{{ __('messages.projects.scans') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $project->snapshots_count ?? $project->snapshots()->count() }}</h3><small>{{ __('messages.nav.snapshots') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $project->compare_runs_count ?? $project->compareRuns()->count() }}</h3><small>{{ __('messages.dashboard.compare_runs') }}</small></div>
                    <div class="col-sm-2"><h3><span class="label label-{{ $context['final_decision'] === 'pass' ? 'success' : ($context['final_decision'] === 'pass_with_warning' ? 'warning' : 'danger') }}">{{ $context['final_decision_label'] }}</span></h3><small>{{ __('messages.qa_evidence.auto_decision') }}</small></div>
                    <div class="col-sm-2"><h3>{{ $context['finding_summary']['open'] ?? 0 }}</h3><small>{{ __('messages.findings.open_findings') }}</small></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.qa_evidence.build_pack') }}</div>
            <div class="panel-body">
                <form method="GET" action="{{ route('projects.qa-evidence.zip', $project) }}" class="form-horizontal" id="qa-evidence-form">
                    @include('qa_evidence.partials.form-fields')
                    <div class="hr-line-dashed"></div>
                    <div class="form-group">
                        <div class="col-sm-8 col-sm-offset-4">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-file-archive-o"></i> {{ __('messages.qa_evidence.download_zip') }}</button>
                            <button type="submit" formaction="{{ route('projects.qa-evidence.notes', $project) }}" class="btn btn-success"><i class="fa fa-file-text-o"></i> {{ __('messages.qa_evidence.download_notes') }}</button>
                            <button type="submit" formaction="{{ route('projects.qa-evidence.summary', $project) }}" class="btn btn-default"><i class="fa fa-code"></i> {{ __('messages.qa_evidence.download_summary') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.qa_evidence.snapshot_roles') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.qa_evidence.roles_help') }}</p>
                <ul class="m-b-none">
                    <li><strong>{{ __('messages.qa_evidence.baseline_snapshot') }}:</strong> {{ __('messages.qa_evidence.baseline_help') }}</li>
                    <li><strong>{{ __('messages.qa_evidence.validation_snapshot') }}:</strong> {{ __('messages.qa_evidence.validation_help') }}</li>
                    <li><strong>{{ __('messages.qa_evidence.negative_snapshot') }}:</strong> {{ __('messages.qa_evidence.negative_help') }}</li>
                    <li><strong>{{ __('messages.qa_evidence.recovery_snapshot') }}:</strong> {{ __('messages.qa_evidence.recovery_help') }}</li>
                </ul>
            </div>
        </div>
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.qa_evidence.generated_files') }}</div>
            <div class="panel-body">
                <ul class="m-b-none">
                    <li><code>qa-notes.md</code></li>
                    <li><code>summary.json</code></li>
                    <li><code>snapshots/*.json</code></li>
                    <li><code>compares/*.md</code></li>
                    <li><code>findings/open-findings.md</code></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
