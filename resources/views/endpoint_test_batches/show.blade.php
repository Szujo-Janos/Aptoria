@extends('layouts.app')
@section('title', __('messages.endpoints.batch_test_detail_title') . ' · ' . $project->name)
@section('page_title', __('messages.endpoints.batch_test_detail_title'))
@section('page_actions')
    <form method="POST" action="{{ route('projects.snapshots.store', $project) }}" class="d-inline" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.snapshots.create_confirm_title') }}" data-confirm-text="{{ __('messages.snapshots.create_confirm_text') }}" data-confirm-button="{{ __('messages.snapshots.create') }}">
        @csrf
        <input type="hidden" name="endpoint_test_batch_id" value="{{ $batch->id }}">
        <input type="hidden" name="confirm_snapshot" value="1">
        <button type="submit" class="btn btn-primary"><i data-lucide="camera" class="me-1"></i>{{ __('messages.snapshots.create_from_batch') }}</button>
    </form>
    <a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.endpoints.title') }}</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-h-100 aptoria-project-dashboard-hero border-{{ $batch->tone }}">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <span class="avatar avatar-lg rounded text-bg-{{ $batch->tone }}"><span class="avatar-title"><i data-lucide="package-check"></i></span></span>
                        <div class="min-w-0">
                            <span class="badge badge-soft-{{ $batch->tone }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $batch->state_label }}</span>
                            <h2 class="mb-1 fw-normal">{{ __('messages.endpoints.batch_test_evidence') }}</h2>
                            <p class="text-muted mb-0">{{ __('messages.endpoints.batch_test_detail_copy') }}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('messages.endpoints.batch_test_total') }}</div>
                        <h3 class="mb-0 fw-light">{{ $batch->total }}</h3>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
                <span>{{ __('messages.workspace.current_project') }}: {{ $project->name }}</span>
                <span>{{ __('messages.endpoints.completed_at') }}: {{ $batch->completed_at?->toDateTimeString() ?? __('messages.common.not_available') }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.endpoints.batch_test_summary') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.batch_test_passed') }}</span><span class="badge badge-soft-success">{{ $batch->passed }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.batch_test_warning') }}</span><span class="badge badge-soft-warning">{{ $batch->warning }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.batch_test_failed') }}</span><span class="badge badge-soft-danger">{{ $batch->failed }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.batch_test_skipped') }}</span><span class="badge badge-soft-secondary">{{ $batch->skipped }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.duration') }}</span><span class="badge badge-soft-info">{{ $batch->duration_label }}</span></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.endpoints.batch_test_detail_footer') }}</div>
        </div>
    </div>
</div>

<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.endpoints.batch_test_runs_detail') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.endpoints.batch_test_runs_detail_copy') }}</p>
        </div>
        <span class="badge badge-soft-primary badge-label">{{ __('messages.endpoints.quick_test_runs') }}: {{ $runs->count() }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="endpoint-test-batch-runs" data-aptoria-actions="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-endpoint-test-runs-table">
                <thead class="align-middle thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.endpoints.endpoint') }}</th>
                        <th data-priority="2">{{ __('messages.common.status') }}</th>
                        <th data-priority="3">{{ __('messages.endpoints.quick_test_http_result') }}</th>
                        <th data-priority="5">{{ __('messages.endpoints.quick_test_target') }}</th>
                        <th data-priority="4">{{ __('messages.endpoints.checked_at') }}</th>
                        <th data-priority="6" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($runs as $run)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <span class="badge text-bg-{{ in_array($run->method, ['GET', 'HEAD'], true) ? 'success' : 'warning' }}">{{ $run->method }}</span>
                                    <div class="min-w-0">
                                        <span class="d-block text-truncate aptoria-endpoint-name-cell">{{ $run->endpoint?->name ?: __('messages.endpoints.unnamed') }}</span>
                                        <small class="text-muted d-block text-truncate">{{ $run->endpoint?->path ?? $run->url }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-{{ $run->tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $run->state_label }}</span></td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span>{{ $run->status_summary }}</span>
                                    <small class="text-muted">{{ $run->response_time_ms ?? '—' }} {{ __('messages.common.milliseconds') }} · {{ $run->content_type ?? '—' }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1 min-w-0">
                                    <code class="aptoria-endpoint-path-cell text-truncate">{{ $run->url ?? '—' }}</code>
                                    <small class="text-muted text-truncate">{{ $run->environment?->name ?? __('messages.auth_profiles.project_base_url') }} · {{ $run->authProfile?->name ?? __('messages.auth_profiles.no_auth_preview') }}</small>
                                </div>
                            </td>
                            <td><small class="text-muted">{{ $run->checked_at?->diffForHumans() ?? '—' }}</small></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="{{ route('projects.endpoint-test-runs.show', [$project, $run]) }}" class="dropdown-item"><i data-lucide="file-check-2" class="me-2"></i>{{ __('messages.endpoints.view_evidence') }}</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">{{ __('messages.endpoints.quick_test_history_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
        <span>{{ __('messages.endpoints.batch_test_runs_footer') }}</span>
        <span>{{ __('messages.endpoints.table_standard') }}</span>
    </div>
</div>

<div class="card mt-3 aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.endpoints.copyable_batch_evidence') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.endpoints.copyable_batch_evidence_copy') }}</p>
        </div>
        <button type="button" class="btn btn-sm btn-primary" data-aptoria-copy-target="#endpointBatchEvidenceMarkdown"><i data-lucide="copy" class="me-1"></i>{{ __('messages.endpoints.copy_evidence') }}</button>
    </div>
    <div class="card-body">
        <textarea id="endpointBatchEvidenceMarkdown" class="form-control font-monospace small" rows="14" readonly>{{ $evidenceMarkdown }}</textarea>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
        <span>{{ __('messages.endpoints.copyable_evidence_footer') }}</span>
        <a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-sm btn-light"><i data-lucide="plug-connected" class="me-1"></i>{{ __('messages.endpoints.title') }}</a>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="aptoriaCopyToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">{{ __('messages.endpoints.evidence_copied') }}</div>
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
            var copied = false;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(target.value).then(function () {
                    var toastEl = document.getElementById('aptoriaCopyToast');
                    if (window.bootstrap && toastEl) { new bootstrap.Toast(toastEl).show(); }
                });
                copied = true;
            }
            if (!copied) {
                document.execCommand('copy');
                var toastEl = document.getElementById('aptoriaCopyToast');
                if (window.bootstrap && toastEl) { new bootstrap.Toast(toastEl).show(); }
            }
        });
    });
});
</script>
@endpush
