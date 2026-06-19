@extends('layouts.app')
@section('title', __('messages.release_decisions.snapshot_detail'))
@section('page_title', __('messages.release_decisions.snapshot_detail'))
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
    <a href="{{ route('projects.release-decisions.report-preview', [$project, $snapshot]) }}" class="btn btn-light"><i data-lucide="file-check" class="me-1"></i>{{ __('messages.release_decisions.report.open_preview') }}</a>
    <a href="{{ route('projects.release-readiness.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.release_readiness.back_to_readiness') }}</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-h-100 aptoria-project-dashboard-hero border-{{ $snapshot->decision_tone }}">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <span class="avatar avatar-lg rounded text-bg-{{ $snapshot->decision_tone }}"><span class="avatar-title"><i data-lucide="clipboard-check"></i></span></span>
                        <div class="min-w-0">
                            <span class="badge badge-soft-{{ $snapshot->decision_tone }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $snapshot->decision_label }}</span>
                            <h2 class="mb-1 fw-normal">{{ __('messages.release_decisions.snapshot') }} #{{ $snapshot->id }}</h2>
                            <p class="text-muted mb-0">{{ __('messages.release_decisions.snapshot_detail_copy') }}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('messages.release_readiness.score') }}</div>
                        <h3 class="mb-0 fw-light">{{ $summary['readiness']['score'] ?? $snapshot->releaseReadinessRun?->score ?? 0 }}%</h3>
                    </div>
                </div>
                @if ($snapshot->decision_note)
                    <div class="alert alert-light border mt-4 mb-0"><strong>{{ __('messages.release_decisions.decision_note') }}</strong><br>{{ $snapshot->decision_note }}</div>
                @endif
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
                <span>{{ __('messages.release_decisions.decided_by') }}: {{ $snapshot->decidedBy?->name ?? '—' }}</span>
                <span>{{ $snapshot->decided_at?->format('Y-m-d H:i') ?? $snapshot->created_at?->format('Y-m-d H:i') }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.release_decisions.decision_summary') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_decisions.decision') }}</span><strong>{{ $snapshot->decision_label }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.title') }}</span><strong>{{ $summary['readiness']['score'] ?? 0 }}%</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.blockers') }}</span><strong class="text-danger">{{ $summary['readiness']['blockers'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_readiness.warnings') }}</span><strong class="text-warning">{{ $summary['readiness']['warnings'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.release_decisions.readiness_snapshot') }}</span><strong>{{ $snapshot->release_readiness_run_id ? '#'.$snapshot->release_readiness_run_id : '—' }}</strong></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-center text-muted">{{ __('messages.release_decisions.snapshot_locked') }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    @foreach ([
        ['quick_tests', 'activity', __('messages.release_decisions.quick_test_state'), ($sourceState['quick_tests']['passed'] ?? 0).' / '.($sourceState['quick_tests']['total'] ?? 0).' '.__('messages.release_decisions.passed_short')],
        ['assertions', 'list-checks', __('messages.release_decisions.assertion_state'), __('messages.safe_scan.run').' #'.($sourceState['assertions']['latest_scan_id'] ?? '—').' · '.($sourceState['assertions']['expectation_failures'] ?? 0).' '.__('messages.release_decisions.expectation_failures_short')],
        ['batch', 'layers', __('messages.release_decisions.batch_state'), __('messages.endpoints.batch_test_evidence').' #'.($sourceState['batch']['latest_batch_id'] ?? '—').' · '.($sourceState['batch']['failed'] ?? 0).' '.__('messages.release_decisions.failed_short')],
        ['risk', 'triangle-alert', __('messages.release_decisions.risk_state'), ($sourceState['risk']['critical_findings'] ?? 0).' critical · '.($sourceState['risk']['missing_evidence'] ?? 0).' missing evidence'],
    ] as [$key, $icon, $label, $copy])
        @php($signal = $summary['signals'][$key] ?? ['tone' => 'secondary', 'label' => __('messages.release_decisions.signal_states.missing')])
        <div class="col-xl-3 col-md-6">
            <div class="card card-h-100 aptoria-widget-card">
                <div class="card-body d-flex justify-content-between align-items-start gap-3">
                    <div class="min-w-0">
                        <h5 class="fw-normal text-uppercase mb-3">{{ $label }}</h5>
                        <span class="badge badge-soft-{{ $signal['tone'] }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $signal['label'] }}</span>
                        <small class="text-muted d-block text-truncate">{{ $copy }}</small>
                    </div>
                    <i data-lucide="{{ $icon }}" class="text-muted fs-42 svg-sw-10"></i>
                </div>
                <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.release_decisions.snapshot_input') }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div><h5 class="card-title mb-1">{{ __('messages.release_readiness.check_table') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_decisions.checks_copy') }}</p></div>
        <span class="badge badge-soft-primary">{{ count($checks) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table data-tables="release-decision-checks" data-aptoria-search="false" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-checks-table">
                <thead class="thead-sm text-uppercase fs-xxs"><tr><th>{{ __('messages.release_readiness.check') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.release_readiness.impact') }}</th></tr></thead>
                <tbody>
                    @foreach ($checks as $check)
                        <tr>
                            <td><div class="d-flex align-items-center gap-2 min-w-0"><span class="avatar avatar-sm rounded text-bg-{{ $check['tone'] ?? 'secondary' }}"><span class="avatar-title"><i data-lucide="{{ $check['icon'] ?? 'check-circle' }}"></i></span></span><div class="min-w-0"><span class="fw-medium d-block text-truncate">{{ $check['label'] ?? '—' }}</span><small class="text-muted d-block text-truncate">{{ $check['hint'] ?? '' }}</small></div></div></td>
                            <td><span class="badge badge-soft-{{ $check['tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.release_readiness.levels.'.($check['level'] ?? 'warning')) }}</span></td>
                            <td><span class="text-muted aptoria-impact-cell">{{ ($check['passed'] ?? false) ? __('messages.release_readiness.no_action') : __('messages.release_readiness.action_required') }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_decisions.checks_footer') }}</div>
</div>


<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div><h5 class="card-title mb-1">{{ __('messages.reports.versioned_release_decision') }}</h5><p class="text-muted mb-0 small">{{ __('messages.reports.versioned_release_decision_copy') }}</p></div>
        <span class="badge badge-soft-secondary">{{ $reportVersions->count() }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="release-decision-report-versions" data-aptoria-search="false" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-report-versions-table">
                <thead class="align-middle thead-sm text-uppercase fs-xxs"><tr><th data-priority="1">{{ __('messages.reports.report') }}</th><th data-priority="3">{{ __('messages.common.status') }}</th><th data-priority="4">{{ __('messages.reports.checksum') }}</th><th data-priority="1" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th></tr></thead>
                <tbody>
                    @forelse ($reportVersions as $reportVersion)
                        <tr>
                            <td><div class="min-w-0 aptoria-report-title-cell"><a href="{{ route('projects.reports.show', [$project, $reportVersion]) }}" class="fw-medium text-decoration-none text-truncate d-block">{{ $reportVersion->title }}</a><small class="text-muted text-nowrap">{{ $reportVersion->generated_at?->format('Y-m-d H:i') ?? '—' }}</small></div></td>
                            <td><span class="badge badge-soft-{{ $reportVersion->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $reportVersion->status_label }}</span></td>
                            <td><code class="small d-block text-truncate aptoria-report-checksum-cell">{{ \Illuminate\Support\Str::limit($reportVersion->checksum, 16) }}</code></td>
                            <td class="text-end aptoria-actions-cell"><div class="dropdown"><button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button><div class="dropdown-menu dropdown-menu-end"><a href="{{ route('projects.reports.show', [$project, $reportVersion]) }}" class="dropdown-item"><i data-lucide="eye" class="me-2"></i>{{ __('messages.reports.view_report_version') }}</a><a href="{{ route('projects.reports.download', [$project, $reportVersion, 'pdf']) }}" class="dropdown-item"><i data-lucide="file-type-pdf" class="me-2"></i>{{ __('messages.release_decisions.report.export_pdf') }}</a><a href="{{ route('projects.reports.download', [$project, $reportVersion, 'html']) }}" class="dropdown-item"><i data-lucide="file-code-2" class="me-2"></i>{{ __('messages.release_decisions.report.export_html') }}</a><a href="{{ route('projects.reports.download', [$project, $reportVersion, 'md']) }}" class="dropdown-item"><i data-lucide="markdown" class="me-2"></i>{{ __('messages.release_decisions.report.export_md') }}</a></div></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ __('messages.reports.no_release_decision_versions') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2"><span>{{ __('messages.reports.versioned_release_decision_footer') }}</span><form method="POST" action="{{ route('projects.release-decisions.report-version.store', [$project, $snapshot]) }}" class="d-inline" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.reports.release_decision_confirm_title') }}" data-confirm-text="{{ __('messages.reports.release_decision_confirm_text') }}" data-confirm-button="{{ __('messages.reports.create_report_version') }}">@csrf<input type="hidden" name="confirm_report_version" value="1"><button type="submit" class="btn btn-sm btn-primary"><i data-lucide="file-plus" class="me-1"></i>{{ __('messages.reports.create_report_version') }}</button></form></div>
</div>

<div class="card mt-3 aptoria-panel-card border-{{ $reportPreview['decision_tone'] }}">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.release_decisions.report.preview_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.release_decisions.report.inline_preview_copy') }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('projects.release-decisions.download', [$project, $snapshot, 'pdf']) }}" class="btn btn-sm btn-primary"><i data-lucide="file-type-pdf" class="me-1"></i>{{ __('messages.release_decisions.report.export_pdf') }}</a>
            <button type="button" class="btn btn-sm btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"><span class="visually-hidden">{{ __('messages.common.actions') }}</span></button>
            <div class="dropdown-menu dropdown-menu-end">
                <a href="{{ route('projects.release-decisions.download', [$project, $snapshot, 'html']) }}" class="dropdown-item"><i data-lucide="file-code-2" class="me-2"></i>{{ __('messages.release_decisions.report.export_html') }}</a>
                <a href="{{ route('projects.release-decisions.download', [$project, $snapshot, 'md']) }}" class="dropdown-item"><i data-lucide="markdown" class="me-2"></i>{{ __('messages.release_decisions.report.export_md') }}</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-stretch">
            <div class="col-xl-4">
                <div class="border rounded-3 p-3 h-100">
                    <span class="badge badge-soft-{{ $reportPreview['decision_tone'] }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $reportPreview['decision_label'] }}</span>
                    <h3 class="fw-light mb-1">{{ $reportPreview['score'] }}%</h3>
                    <p class="text-muted mb-0 small">{{ $reportPreview['headline'] }}</p>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="row row-cols-md-4 row-cols-2 g-2">
                    @foreach ($reportPreview['counters'] as $counter)
                        <div class="col"><div class="border rounded-3 p-3 h-100"><small class="text-muted d-block text-truncate">{{ $counter['label'] }}</small><strong class="text-{{ $counter['tone'] }}">{{ $counter['value'] }}</strong></div></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
        <span>{{ __('messages.release_decisions.report.preview_footer') }}</span>
        <span>{{ __('messages.release_decisions.report.export_ready_note') }}</span>
    </div>
</div>

<div class="card mt-3 aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div><h5 class="card-title mb-1">{{ __('messages.release_decisions.copyable_evidence') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_decisions.copyable_evidence_copy') }}</p></div>
        <button type="button" class="btn btn-sm btn-primary" data-aptoria-copy-target="#releaseDecisionEvidenceMarkdown"><i data-lucide="copy" class="me-1"></i>{{ __('messages.endpoints.copy_evidence') }}</button>
    </div>
    <div class="card-body">
        <textarea id="releaseDecisionEvidenceMarkdown" class="form-control font-monospace small" rows="14" readonly>{{ $snapshot->evidence_summary_markdown }}</textarea>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
        <span>{{ __('messages.release_decisions.copyable_evidence_footer') }}</span>
        @if ($snapshot->release_readiness_run_id)
            <a href="{{ route('projects.release-readiness.show', [$project, $snapshot->release_readiness_run_id]) }}" class="btn btn-sm btn-light"><i data-lucide="shield-chevron" class="me-1"></i>{{ __('messages.release_decisions.view_readiness_snapshot') }}</a>
        @endif
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="aptoriaCopyToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">{{ __('messages.release_decisions.evidence_copied') }}</div>
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
