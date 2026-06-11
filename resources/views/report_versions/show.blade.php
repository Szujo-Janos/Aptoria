@extends('layouts.app')

@section('title', __('messages.report_versions.detail_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.report-versions.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.report_versions.detail_title') }} #{{ $reportVersion->id }}
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-8">
                        <h3 class="m-t-none">{{ $reportVersion->title }}</h3>
                        <p class="text-muted">{{ __('messages.report_versions.detail_intro') }}</p>
                    </div>
                    <div class="col-md-4 text-right">
                        <span class="label label-{{ $reportVersion->status_css }}">{{ $reportVersion->status_label }}</span>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-sm-3"><h4>{{ $reportVersion->type_label }}</h4><small>{{ __('messages.report_versions.report_type') }}</small></div>
                    <div class="col-sm-3"><h4><code>{{ $reportVersion->short_checksum }}</code></h4><small>{{ __('messages.report_versions.checksum') }}</small></div>
                    <div class="col-sm-3"><h4>{{ count($reportVersion->source_scan_ids ?? []) }}</h4><small>{{ __('messages.report_versions.source_scans') }}</small></div>
                    <div class="col-sm-3"><h4>{{ count($reportVersion->source_finding_state ?? []) }}</h4><small>{{ __('messages.report_versions.source_findings') }}</small></div>
                </div>
            </div>
            <div class="panel-footer report-version-toolbar">
                <div class="row">
                    <div class="col-md-6">
                        <div class="report-version-action-group">
                            <div class="text-muted report-version-action-label">{{ __('messages.report_versions.export_actions') }}</div>
                            <div class="btn-group" role="group" aria-label="{{ __('messages.report_versions.export_actions') }}">
                                <a href="{{ route('projects.report-versions.markdown', [$project, $reportVersion]) }}" class="btn btn-primary">MD</a>
                                <a href="{{ route('projects.report-versions.html', [$project, $reportVersion]) }}" class="btn btn-default">HTML</a>
                                <a href="{{ route('projects.report-versions.pdf', [$project, $reportVersion]) }}" class="btn btn-default">PDF</a>
                                <a href="{{ route('projects.report-versions.json', [$project, $reportVersion]) }}" class="btn btn-default">JSON</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        @if($reportVersion->status !== \App\Models\ReportVersion::STATUS_ARCHIVED)
                            <div class="report-version-action-group">
                                <div class="text-muted report-version-action-label">{{ __('messages.report_versions.workflow_actions') }}</div>
                                <div class="report-version-workflow-actions">
                                    <form method="POST" action="{{ route('projects.report-versions.review', [$project, $reportVersion]) }}" class="report-version-inline-form">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-info">{{ __('messages.report_versions.mark_reviewed') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('projects.report-versions.approve', [$project, $reportVersion]) }}" class="report-version-inline-form">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-success">{{ __('messages.report_versions.approve') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('projects.report-versions.archive', [$project, $reportVersion]) }}" class="report-version-inline-form">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-warning">{{ __('messages.report_versions.archive') }}</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="text-muted report-version-archived-note">{{ __('messages.report_versions.archived_note') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.report_versions.approval') }}</div>
            <div class="panel-body">
                <dl class="dl-horizontal m-b-none">
                    <dt>{{ __('messages.report_versions.generated_by') }}</dt><dd>{{ $reportVersion->generatedBy?->name ?: '—' }}</dd>
                    <dt>{{ __('messages.report_versions.generated_at') }}</dt><dd>{{ $reportVersion->generated_at?->format('Y-m-d H:i') ?: '—' }}</dd>
                    <dt>{{ __('messages.report_versions.reviewed_at') }}</dt><dd>{{ $reportVersion->reviewed_at?->format('Y-m-d H:i') ?: '—' }}</dd>
                    <dt>{{ __('messages.report_versions.approved_by') }}</dt><dd>{{ $reportVersion->approvedBy?->name ?: '—' }}</dd>
                    <dt>{{ __('messages.report_versions.approved_at') }}</dt><dd>{{ $reportVersion->approved_at?->format('Y-m-d H:i') ?: '—' }}</dd>
                    <dt>{{ __('messages.report_versions.archived_at') }}</dt><dd>{{ $reportVersion->archived_at?->format('Y-m-d H:i') ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.report_versions.sources') }}</div>
            <div class="panel-body">
                <dl class="dl-horizontal m-b-none">
                    <dt>{{ __('messages.report_versions.source_scans') }}</dt><dd>{{ implode(', ', $reportVersion->source_scan_ids ?? []) ?: '—' }}</dd>
                    <dt>{{ __('messages.report_versions.source_snapshots') }}</dt><dd>{{ implode(', ', $reportVersion->source_snapshot_ids ?? []) ?: '—' }}</dd>
                    <dt>{{ __('messages.report_versions.source_compares') }}</dt><dd>{{ implode(', ', $reportVersion->source_compare_ids ?? []) ?: '—' }}</dd>
                    <dt>{{ __('messages.report_versions.source_gates') }}</dt><dd>{{ implode(', ', $reportVersion->source_release_gate_ids ?? []) ?: '—' }}</dd>
                    <dt>{{ __('messages.report_versions.source_decisions') }}</dt><dd>{{ implode(', ', $reportVersion->source_release_decision_ids ?? []) ?: '—' }}</dd>
                    <dt>{{ __('messages.report_versions.source_evidence') }}</dt><dd>{{ implode(', ', $reportVersion->source_evidence_ids ?? []) ?: '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.report_versions.markdown_preview') }}</div>
            <div class="panel-body">
                <pre class="pre-scrollable">{{ \Illuminate\Support\Str::limit((string) $reportVersion->markdown_content, 8000) }}</pre>
            </div>
        </div>
    </div>
</div>
@endsection


@push('styles')
<style>
    .report-version-toolbar {
        background: #f7f9fb;
        border-top: 1px solid #e7eaec;
        padding: 14px 16px;
    }

    .report-version-action-group {
        margin: 0;
    }

    .report-version-action-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: .04em;
        margin-bottom: 7px;
        text-transform: uppercase;
    }

    .report-version-workflow-actions {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: flex-end;
    }

    .report-version-inline-form {
        display: inline-block;
        margin: 0;
    }

    .report-version-archived-note {
        padding-top: 26px;
    }

    @media (max-width: 991px) {
        .report-version-toolbar .text-right {
            margin-top: 14px;
            text-align: left;
        }

        .report-version-workflow-actions {
            justify-content: flex-start;
        }
    }
</style>
@endpush
