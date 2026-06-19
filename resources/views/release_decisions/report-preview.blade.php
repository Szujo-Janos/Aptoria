@extends('layouts.app')
@section('title', __('messages.release_decisions.report.preview_title'))
@section('page_title', __('messages.release_decisions.report.preview_title'))
@section('page_actions')
    <form method="POST" action="{{ route('projects.release-decisions.report-version.store', [$project, $snapshot]) }}" class="d-inline" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.reports.release_decision_confirm_title') }}" data-confirm-text="{{ __('messages.reports.release_decision_confirm_text') }}" data-confirm-button="{{ __('messages.reports.create_report_version') }}">
        @csrf
        <input type="hidden" name="confirm_report_version" value="1">
        <button type="submit" class="btn btn-success"><i data-lucide="file-plus" class="me-1"></i>{{ __('messages.reports.create_report_version') }}</button>
    </form>
    @if ($latestReportVersion)
        <a href="{{ route('projects.reports.show', [$project, $latestReportVersion]) }}" class="btn btn-light"><i data-lucide="git-fork" class="me-1"></i>{{ __('messages.reports.open_latest_report_version') }}</a>
    @endif
    <div class="btn-group">
        <a href="{{ route('projects.release-decisions.download', [$project, $snapshot, 'pdf']) }}" class="btn btn-primary"><i data-lucide="file-type-pdf" class="me-1"></i>{{ __('messages.release_decisions.report.export_pdf') }}</a>
        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"><span class="visually-hidden">{{ __('messages.common.actions') }}</span></button>
        <div class="dropdown-menu dropdown-menu-end">
            <a href="{{ route('projects.release-decisions.download', [$project, $snapshot, 'html']) }}" class="dropdown-item"><i data-lucide="file-code-2" class="me-2"></i>{{ __('messages.release_decisions.report.export_html') }}</a>
            <a href="{{ route('projects.release-decisions.download', [$project, $snapshot, 'md']) }}" class="dropdown-item"><i data-lucide="markdown" class="me-2"></i>{{ __('messages.release_decisions.report.export_md') }}</a>
        </div>
    </div>
    <a href="{{ route('projects.release-decisions.show', [$project, $snapshot]) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.release_decisions.view_snapshot') }}</a>
@endsection

@push('styles')
<style>
@media print {
    .leftside-menu, .topbar, .page-title-head, .footer, .aptoria-no-print, .toast-container { display: none !important; }
    .content-page { margin-left: 0 !important; padding-top: 0 !important; }
    .container-fluid { max-width: 100% !important; padding: 0 !important; }
    .card { break-inside: avoid; box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    body { background: #fff !important; }
    .aptoria-report-print-title { display: block !important; }
}
.aptoria-report-print-title { display: none; }
</style>
@endpush

@section('content')

@if ($latestReportVersion)
    <div class="alert alert-success d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div><strong>{{ __('messages.reports.latest_report_version') }}:</strong> {{ $latestReportVersion->title }}<br><small>{{ __('messages.reports.checksum') }}: <code>{{ $latestReportVersion->checksum }}</code></small></div>
        <a href="{{ route('projects.reports.show', [$project, $latestReportVersion]) }}" class="btn btn-sm btn-light"><i data-lucide="eye" class="me-1"></i>{{ __('messages.reports.view_report_version') }}</a>
    </div>
@else
    <div class="alert alert-info d-flex justify-content-between align-items-center gap-3 flex-wrap">
        <div><strong>{{ __('messages.reports.no_release_decision_versions') }}</strong><br><small>{{ __('messages.reports.create_report_version_copy') }}</small></div>
        <form method="POST" action="{{ route('projects.release-decisions.report-version.store', [$project, $snapshot]) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.reports.release_decision_confirm_title') }}" data-confirm-text="{{ __('messages.reports.release_decision_confirm_text') }}" data-confirm-button="{{ __('messages.reports.create_report_version') }}">@csrf<input type="hidden" name="confirm_report_version" value="1"><button type="submit" class="btn btn-sm btn-primary"><i data-lucide="file-plus" class="me-1"></i>{{ __('messages.reports.create_report_version') }}</button></form>
    </div>
@endif

<div class="aptoria-report-print-title mb-3">
    <h1>{{ $reportPreview['title'] }}</h1>
    <p>{{ $reportPreview['subtitle'] }}</p>
</div>

<div class="card aptoria-panel-card aptoria-project-dashboard-hero border-{{ $reportPreview['decision_tone'] }}">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div class="d-flex align-items-start gap-3 min-w-0">
                <span class="avatar avatar-lg rounded text-bg-{{ $reportPreview['decision_tone'] }}"><span class="avatar-title"><i data-lucide="file-check"></i></span></span>
                <div class="min-w-0">
                    <span class="badge badge-soft-{{ $reportPreview['decision_tone'] }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $reportPreview['decision_label'] }}</span>
                    <h2 class="mb-1 fw-normal">{{ $reportPreview['title'] }}</h2>
                    <p class="text-muted mb-0">{{ $reportPreview['headline'] }}</p>
                </div>
            </div>
            <div class="text-end">
                <div class="text-muted small">{{ __('messages.release_readiness.score') }}</div>
                <h3 class="mb-0 fw-light">{{ $reportPreview['score'] }}%</h3>
                <small class="text-muted">{{ $reportPreview['grade'] }} · {{ $reportPreview['status_label'] }}</small>
            </div>
        </div>
        <div class="progress progress-lg mt-4 mb-0"><div class="progress-bar bg-{{ $reportPreview['decision_tone'] }}" style="width: {{ $reportPreview['score'] }}%;"></div></div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
        <span>{{ __('messages.release_decisions.snapshot') }} #{{ $snapshot->id }}</span>
        <span>{{ __('messages.release_decisions.report.preview_footer') }}</span>
    </div>
</div>

<div class="row row-cols-xl-4 row-cols-md-2 row-cols-1 g-3 mt-1">
    @foreach ($reportPreview['counters'] as $counter)
        <div class="col">
            <div class="card card-h-100 aptoria-widget-card">
                <div class="card-body d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <h5 class="fw-normal text-uppercase mb-3">{{ $counter['label'] }}</h5>
                        <h2 class="fw-light mb-0 text-{{ $counter['tone'] }}">{{ $counter['value'] }}</h2>
                    </div>
                    <i data-lucide="bar-chart-3" class="text-muted fs-42 svg-sw-10"></i>
                </div>
                <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.release_decisions.report.report_counter') }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-7">
        <div class="card aptoria-panel-card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.release_decisions.report.executive_summary') }}</h5></div>
            <div class="card-body">
                <p class="text-muted mb-3">{{ $reportPreview['subtitle'] }}</p>
                <div class="alert alert-light border mb-0">
                    <strong>{{ __('messages.release_decisions.decision') }}: {{ $reportPreview['decision_label'] }}</strong><br>
                    <span class="text-muted">{{ $reportPreview['headline'] }}</span>
                </div>
                @if ($snapshot->decision_note)
                    <div class="alert alert-info mt-3 mb-0"><strong>{{ __('messages.release_decisions.decision_note') }}</strong><br>{{ $snapshot->decision_note }}</div>
                @endif
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_decisions.report.executive_footer') }}</div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card aptoria-panel-card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.release_decisions.report.metadata') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach ($reportPreview['meta'] as $label => $value)
                        <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ $label }}</span><strong class="text-end">{{ $value }}</strong></div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.release_decisions.report.metadata_footer') }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    @foreach ($reportPreview['signals'] as $signal)
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100 aptoria-widget-card">
                <div class="card-body d-flex justify-content-between align-items-start gap-3">
                    <div class="min-w-0">
                        <h5 class="fw-normal text-uppercase mb-3">{{ $signal['label'] }}</h5>
                        <span class="badge badge-soft-{{ $signal['tone'] }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $signal['state_label'] }}</span>
                        <small class="text-muted d-block">{{ $signal['summary'] }}</small>
                        <small class="text-muted d-block">{{ $signal['details'] }}</small>
                    </div>
                    <i data-lucide="{{ $signal['icon'] }}" class="text-muted fs-42 svg-sw-10"></i>
                </div>
                <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.release_decisions.report.evidence_signal') }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-6">
        <div class="card aptoria-table-card aptoria-panel-card h-100">
            <div class="card-header border-light justify-content-between align-items-center"><div><h5 class="card-title mb-1">{{ __('messages.release_decisions.report.blocking_checks') }}</h5><p class="text-muted small mb-0">{{ __('messages.release_decisions.report.blocking_checks_copy') }}</p></div><span class="badge badge-soft-danger">{{ count($reportPreview['blocking_checks']) }}</span></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                        <thead class="thead-sm text-uppercase fs-xxs"><tr><th>{{ __('messages.release_readiness.check') }}</th><th>{{ __('messages.release_readiness.impact') }}</th></tr></thead>
                        <tbody>
                            @forelse ($reportPreview['blocking_checks'] as $check)
                                <tr><td><span class="fw-medium d-block text-truncate">{{ $check['label'] ?? '—' }}</span><small class="text-muted d-block text-truncate">{{ $check['hint'] ?? '' }}</small></td><td><span class="badge badge-soft-danger badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.release_readiness.levels.blocker') }}</span></td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">{{ __('messages.release_decisions.report.no_blocking_checks') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_decisions.report.blocking_footer') }}</div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card aptoria-table-card aptoria-panel-card h-100">
            <div class="card-header border-light justify-content-between align-items-center"><div><h5 class="card-title mb-1">{{ __('messages.release_decisions.report.warning_checks') }}</h5><p class="text-muted small mb-0">{{ __('messages.release_decisions.report.warning_checks_copy') }}</p></div><span class="badge badge-soft-warning">{{ count($reportPreview['warning_checks']) }}</span></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                        <thead class="thead-sm text-uppercase fs-xxs"><tr><th>{{ __('messages.release_readiness.check') }}</th><th>{{ __('messages.release_readiness.impact') }}</th></tr></thead>
                        <tbody>
                            @forelse ($reportPreview['warning_checks'] as $check)
                                <tr><td><span class="fw-medium d-block text-truncate">{{ $check['label'] ?? '—' }}</span><small class="text-muted d-block text-truncate">{{ $check['hint'] ?? '' }}</small></td><td><span class="badge badge-soft-warning badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.release_readiness.levels.warning') }}</span></td></tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-4">{{ __('messages.release_decisions.report.no_warning_checks') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_decisions.report.warning_footer') }}</div>
        </div>
    </div>
</div>

<div class="card mt-3 aptoria-panel-card aptoria-no-print">
    <div class="card-header border-light justify-content-between align-items-center">
        <div><h5 class="card-title mb-1">{{ __('messages.release_decisions.report.copyable_report') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_decisions.report.copyable_report_copy') }}</p></div>
        <button type="button" class="btn btn-sm btn-primary" data-aptoria-copy-target="#releaseDecisionReportMarkdown"><i data-lucide="copy" class="me-1"></i>{{ __('messages.endpoints.copy_evidence') }}</button>
    </div>
    <div class="card-body"><textarea id="releaseDecisionReportMarkdown" class="form-control font-monospace small" rows="16" readonly>{{ $reportPreview['markdown'] }}</textarea></div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_decisions.report.copyable_report_footer') }}</div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3 aptoria-no-print">
    <div id="aptoriaCopyToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">{{ __('messages.release_decisions.report.report_copied') }}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="{{ __('messages.common.close') }}"></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-aptoria-copy-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.querySelector(button.getAttribute('data-aptoria-copy-target'));
            if (!target) { return; }
            target.select();
            target.setSelectionRange(0, target.value.length);
            var showToast = function () {
                var toastEl = document.getElementById('aptoriaCopyToast');
                if (window.bootstrap && toastEl) { new bootstrap.Toast(toastEl).show(); }
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(target.value).then(showToast);
            } else {
                document.execCommand('copy');
                showToast();
            }
        });
    });
});
</script>
@endpush
