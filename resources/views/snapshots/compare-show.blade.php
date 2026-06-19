@extends('layouts.app')
@section('title', __('messages.snapshots.compare_detail') . ' #' . $compare->id)
@section('page_title', __('messages.snapshots.compare_detail'))
@php
    $regressionFindingCandidateCount = (int) $compare->regressed_count + (int) $compare->removed_count;
@endphp
@section('page_actions')
    <div class="d-flex align-items-center gap-2 flex-wrap">
        @if($regressionFindingCandidateCount > 0)
            <form method="POST" action="{{ route('projects.snapshot-compares.regression-findings', [$project, $compare]) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.snapshots.regression_findings_confirm_title') }}" data-confirm-text="{{ __('messages.snapshots.regression_findings_confirm_text') }}" data-confirm-button="{{ __('messages.snapshots.create_regression_findings') }}">
                @csrf
                <button type="submit" class="btn btn-warning"><i data-lucide="bug" class="me-1"></i>{{ __('messages.snapshots.create_regression_findings') }}</button>
            </form>
        @endif
        <a href="{{ route('projects.snapshots.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.snapshots.title') }}</a>
    </div>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card aptoria-project-dashboard-hero border-{{ $compare->tone }}">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <span class="avatar avatar-lg rounded text-bg-{{ $compare->tone }}"><span class="avatar-title"><i data-lucide="arrows-diff"></i></span></span>
                        <div class="min-w-0">
                            <span class="badge badge-soft-{{ $compare->tone }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $compare->status_label }}</span>
                            <h2 class="mb-1 fw-normal">#{{ $compare->baseline_snapshot_id }} → #{{ $compare->target_snapshot_id }}</h2>
                            <p class="text-muted mb-0">{{ $compare->summary_json['headline'] ?? __('messages.snapshots.compare_detail_copy') }}</p>
                        </div>
                    </div>
                    <div class="text-end"><div class="text-muted small">{{ __('messages.snapshots.regressions') }}</div><h3 class="fw-light mb-0">{{ $compare->regressed_count }}</h3></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
                <span>{{ __('messages.snapshots.baseline') }}: {{ $compare->baselineSnapshot->title }}</span>
                <span>{{ __('messages.snapshots.target') }}: {{ $compare->targetSnapshot->title }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0"><i data-lucide="bar-chart-3" class="me-2"></i>{{ __('messages.snapshots.compare_summary') }}</h5></div>
            <div class="card-body p-0"><div class="list-group list-group-flush">
                <div class="list-group-item d-flex justify-content-between"><span>{{ __('messages.snapshots.unchanged') }}</span><span class="badge badge-soft-secondary">{{ $compare->unchanged_count }}</span></div>
                <div class="list-group-item d-flex justify-content-between"><span>{{ __('messages.snapshots.changed') }}</span><span class="badge badge-soft-warning">{{ $compare->changed_count }}</span></div>
                <div class="list-group-item d-flex justify-content-between"><span>{{ __('messages.snapshots.added') }}</span><span class="badge badge-soft-warning">{{ $compare->added_count }}</span></div>
                <div class="list-group-item d-flex justify-content-between"><span>{{ __('messages.snapshots.removed') }}</span><span class="badge badge-soft-danger">{{ $compare->removed_count }}</span></div>
                <div class="list-group-item d-flex justify-content-between"><span>{{ __('messages.snapshots.regressions') }}</span><span class="badge badge-soft-danger">{{ $compare->regressed_count }}</span></div>
                <div class="list-group-item d-flex justify-content-between"><span>{{ __('messages.snapshots.improved') }}</span><span class="badge badge-soft-success">{{ $compare->improved_count }}</span></div>
            </div></div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.snapshots.compare_fixed_footer') }}</div>
        </div>
        <div class="card mt-3 aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0"><i data-lucide="bug" class="me-2"></i>{{ __('messages.snapshots.regression_triage') }}</h5></div>
            <div class="card-body">
                <p class="text-muted small">{{ __('messages.snapshots.regression_triage_copy') }}</p>
                <div class="d-flex justify-content-between align-items-center mb-2"><span>{{ __('messages.snapshots.triage_candidates') }}</span><span class="badge badge-soft-danger">{{ $regressionFindingCandidateCount }}</span></div>
                <div class="d-flex justify-content-between align-items-center mb-2"><span>{{ __('messages.snapshots.linked_findings') }}</span><span class="badge badge-soft-primary">{{ $compare->regression_finding_count ?? 0 }}</span></div>
                <div class="d-flex justify-content-between align-items-center"><span>{{ __('messages.snapshots.generated_at') }}</span><small class="text-muted">{{ $compare->regression_findings_generated_at?->format('Y-m-d H:i') ?? '—' }}</small></div>
                @if($regressionFindingCandidateCount > 0)
                    <form method="POST" action="{{ route('projects.snapshot-compares.regression-findings', [$project, $compare]) }}" class="mt-3" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.snapshots.regression_findings_confirm_title') }}" data-confirm-text="{{ __('messages.snapshots.regression_findings_confirm_text') }}" data-confirm-button="{{ __('messages.snapshots.create_regression_findings') }}">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100"><i data-lucide="bug" class="me-1"></i>{{ __('messages.snapshots.create_regression_findings') }}</button>
                    </form>
                @endif
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.snapshots.regression_triage_footer') }}</div>
        </div>
    </div>
</div>

<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center"><div><h5 class="card-title mb-1"><i data-lucide="git-compare" class="me-2"></i>{{ __('messages.snapshots.compare_items') }}</h5><p class="text-muted mb-0 small">{{ __('messages.snapshots.compare_items_copy') }}</p></div><span class="badge badge-soft-primary">{{ $items->count() }}</span></div>
    <div class="card-body"><div class="table-responsive"><table data-tables="endpoint-snapshot-compare-items" data-aptoria-paging="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table"><thead class="align-middle thead-sm text-uppercase fs-xxs"><tr><th data-priority="1">{{ __('messages.endpoints.endpoint') }}</th><th data-priority="2">{{ __('messages.snapshots.change_type') }}</th><th data-priority="3">{{ __('messages.snapshots.baseline') }}</th><th data-priority="4">{{ __('messages.snapshots.target') }}</th><th data-priority="5">{{ __('messages.endpoints.quick_test_http_result') }}</th></tr></thead><tbody>
        @forelse($items as $item)
            <tr><td><div class="d-flex align-items-center gap-2 min-w-0"><span class="badge text-bg-light">{{ $item->method }}</span><div class="min-w-0"><span class="d-block text-truncate aptoria-endpoint-name-cell">{{ $item->endpoint_signature }}</span><small class="text-muted d-block text-truncate">{{ $item->path }}</small></div></div></td><td><span class="badge badge-soft-{{ $item->tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $item->change_type_label }}</span></td><td><small class="text-muted">{{ $item->baseline_state ?? '—' }}</small></td><td><small class="text-muted">{{ $item->target_state ?? '—' }}</small></td><td><small class="text-muted">{{ $item->baseline_status_code ?? '—' }} → {{ $item->target_status_code ?? '—' }}</small></td></tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-5">{{ __('messages.snapshots.compare_items_empty') }}</td></tr>
        @endforelse
    </tbody></table></div></div>
</div>

<div class="card mt-3 aptoria-panel-card"><div class="card-header border-light justify-content-between align-items-center"><div><h5 class="card-title mb-1"><i data-lucide="markdown" class="me-2"></i>{{ __('messages.snapshots.copyable_compare') }}</h5><p class="text-muted mb-0 small">{{ __('messages.snapshots.copyable_compare_copy') }}</p></div><button type="button" class="btn btn-sm btn-primary" data-aptoria-copy-target="#endpointSnapshotCompareMarkdown"><i data-lucide="copy" class="me-1"></i>{{ __('messages.endpoints.copy_evidence') }}</button></div><div class="card-body"><textarea id="endpointSnapshotCompareMarkdown" class="form-control font-monospace small" rows="14" readonly>{{ $compareMarkdown }}</textarea></div></div>

<div class="toast-container position-fixed top-0 end-0 p-3"><div id="aptoriaCopyToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">{{ __('messages.snapshots.copied') }}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="{{ __('messages.common.close') }}"></button></div></div></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () { document.querySelectorAll('[data-aptoria-copy-target]').forEach(function (button) { button.addEventListener('click', function () { var target = document.querySelector(button.getAttribute('data-aptoria-copy-target')); if (!target) { return; } target.select(); target.setSelectionRange(0, target.value.length); var showToast = function () { var toastEl = document.getElementById('aptoriaCopyToast'); if (window.bootstrap && toastEl) { new bootstrap.Toast(toastEl).show(); } }; if (navigator.clipboard && navigator.clipboard.writeText) { navigator.clipboard.writeText(target.value).then(showToast); } else { document.execCommand('copy'); showToast(); } }); }); });
</script>
@endpush
