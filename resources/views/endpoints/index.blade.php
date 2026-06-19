@extends('layouts.app')
@section('title', __('messages.endpoints.title') . ' · ' . $project->name)
@section('page_title', __('messages.endpoints.title'))
@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    <form method="POST" action="{{ route('projects.endpoints.test-all', $project) }}" class="d-inline" data-aptoria-form-scope="endpoint_batch_quick_test" data-aptoria-form-plugin data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.endpoints.batch_test_confirm_title') }}" data-confirm-text="{{ __('messages.endpoints.batch_test_confirm_text') }}" data-confirm-button="{{ __('messages.endpoints.run_batch_quick_test') }}">
        @csrf
        <button type="submit" class="btn btn-light"><i data-lucide="brackets-contain" class="me-1"></i>{{ __('messages.endpoints.run_batch_quick_test') }}</button>
    </form>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#endpointCreateModal"><i data-lucide="plus" class="me-1"></i>{{ __('messages.endpoints.new') }}</button>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-h-100 aptoria-project-dashboard-hero">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <span class="avatar avatar-lg rounded text-bg-primary"><span class="avatar-title"><i data-lucide="plug-connected"></i></span></span>
                        <div class="min-w-0">
                            <span class="badge badge-soft-primary badge-label mb-2"><i class="ti ti-point-filled"></i>v0.0.18</span>
                            <h2 class="mb-1 fw-normal">{{ __('messages.endpoints.heading') }}</h2>
                            <p class="text-muted mb-0">{{ __('messages.endpoints.copy') }}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('messages.endpoints.total_endpoints') }}</div>
                        <h3 class="mb-0 fw-light">{{ $metrics['total'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
                <span>{{ __('messages.workspace.current_project') }}: {{ $project->name }}</span>
                <span>{{ __('messages.endpoints.safe_scan_ready') }}: {{ $metrics['safe'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.endpoints.readiness_card') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.has_endpoint') }}</span><span class="badge {{ $metrics['total'] > 0 ? 'badge-soft-success' : 'badge-soft-warning' }}">{{ $metrics['total'] > 0 ? 'OK' : 'Next' }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.safe_methods') }}</span><span class="badge badge-soft-success">{{ $metrics['safe'] }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.auth_required') }}</span><span class="badge badge-soft-warning">{{ $metrics['auth_required'] }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.review_risk') }}</span><span class="badge {{ $metrics['review'] > 0 ? 'badge-soft-danger' : 'badge-soft-secondary' }}">{{ $metrics['review'] }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.quick_tested') }}</span><span class="badge {{ $metrics['tested'] > 0 ? 'badge-soft-info' : 'badge-soft-secondary' }}">{{ $metrics['tested'] }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.endpoints.latest_test_risks') }}</span><span class="badge {{ ($metrics['latest_failed'] + $metrics['latest_warning']) > 0 ? 'badge-soft-warning' : 'badge-soft-success' }}">{{ $metrics['latest_failed'] + $metrics['latest_warning'] }}</span></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.endpoints.readiness_help') }}</div>
        </div>
    </div>
</div>

@if ($endpointTestResult)
    <div class="card mt-3 aptoria-panel-card border-{{ $endpointTestResult['tone'] ?? 'secondary' }}">
        <div class="card-header border-light justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1">{{ __('messages.endpoints.quick_test_result') }}</h5>
                <p class="text-muted mb-0 small">{{ __('messages.endpoints.quick_test_result_copy') }}</p>
            </div>
            <span class="badge badge-soft-{{ $endpointTestResult['tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ ucfirst($endpointTestResult['state'] ?? 'unknown') }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-stretch">
                <div class="col-xl-4">
                    <div class="p-3 rounded border h-100">
                        <div class="text-muted small mb-1">{{ __('messages.endpoints.endpoint') }}</div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge text-bg-{{ in_array(($endpointTestResult['endpoint_method'] ?? 'GET'), ['GET', 'HEAD'], true) ? 'success' : 'warning' }}">{{ $endpointTestResult['endpoint_method'] ?? 'GET' }}</span>
                            <span class="fw-normal text-truncate">{{ $endpointTestResult['endpoint_name'] ?? __('messages.endpoints.unnamed') }}</span>
                        </div>
                        <code class="aptoria-endpoint-path-cell">{{ $endpointTestResult['endpoint_path'] ?? '—' }}</code>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="p-3 rounded border h-100">
                        <div class="text-muted small mb-1">{{ __('messages.endpoints.quick_test_target') }}</div>
                        <div class="text-truncate mb-2"><code>{{ $endpointTestResult['url'] ?? '—' }}</code></div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge badge-soft-secondary">{{ $endpointTestResult['environment_name'] ?? __('messages.auth_profiles.project_base_url') }}</span>
                            <span class="badge badge-soft-secondary">{{ $endpointTestResult['auth_profile_name'] ?? __('messages.auth_profiles.no_auth_preview') }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="p-3 rounded border h-100">
                        <div class="text-muted small mb-1">{{ __('messages.endpoints.quick_test_http_result') }}</div>
                        <div class="d-flex gap-2 flex-wrap mb-2">
                            <span class="badge badge-soft-{{ $endpointTestResult['tone'] ?? 'secondary' }}">HTTP {{ $endpointTestResult['status_code'] ?? '—' }}</span>
                            <span class="badge badge-soft-info">{{ $endpointTestResult['response_time_ms'] ?? '—' }} {{ __('messages.common.milliseconds') }}</span>
                            <span class="badge badge-soft-secondary">{{ $endpointTestResult['content_type'] ?? '—' }}</span>
                        </div>
                        <p class="mb-0 text-muted small">{{ $endpointTestResult['message'] ?? __('messages.endpoints.quick_test_no_message') }}</p>
                    </div>
                </div>
            </div>
            @if (! empty($endpointTestResult['expected_status']) || ! empty($endpointTestResult['expected_content_type']))
                <div class="row g-3 mt-0">
                    <div class="col-md-6">
                        <div class="alert alert-{{ ($endpointTestResult['status_matched'] ?? null) === false ? 'warning' : 'light' }} mb-0">
                            <strong>{{ __('messages.endpoints.expected_status') }}:</strong>
                            {{ $endpointTestResult['expected_status'] ?? '—' }}
                            @if (($endpointTestResult['status_matched'] ?? null) === true)
                                <span class="badge badge-soft-success ms-2">{{ __('messages.endpoints.quick_test_matched') }}</span>
                            @elseif (($endpointTestResult['status_matched'] ?? null) === false)
                                <span class="badge badge-soft-warning ms-2">{{ __('messages.endpoints.quick_test_mismatch') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-{{ ($endpointTestResult['content_type_matched'] ?? null) === false ? 'warning' : 'light' }} mb-0">
                            <strong>{{ __('messages.endpoints.content_type') }}:</strong>
                            {{ $endpointTestResult['expected_content_type'] ?? '—' }}
                            @if (($endpointTestResult['content_type_matched'] ?? null) === true)
                                <span class="badge badge-soft-success ms-2">{{ __('messages.endpoints.quick_test_matched') }}</span>
                            @elseif (($endpointTestResult['content_type_matched'] ?? null) === false)
                                <span class="badge badge-soft-warning ms-2">{{ __('messages.endpoints.quick_test_mismatch') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
            @if (! empty($endpointTestResult['body_preview']))
                <div class="mt-3">
                    <div class="text-muted small mb-1">{{ __('messages.auth_profiles.body_preview') }}</div>
                    <div class="bg-light border rounded p-2 small aptoria-result-preview"><pre class="mb-0 text-wrap">{{ $endpointTestResult['body_preview'] }}</pre></div>
                </div>
            @endif
        </div>
        <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
            <span>{{ __('messages.endpoints.quick_test_safe_note') }}</span>
            <span>{{ $endpointTestResult['checked_at'] ?? '' }}</span>
        </div>
    </div>
@endif


@if ($endpointBatchResult)
    <div class="card mt-3 aptoria-panel-card border-{{ $endpointBatchResult['tone'] ?? 'secondary' }}">
        <div class="card-header border-light justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1">{{ __('messages.endpoints.batch_test_result') }}</h5>
                <p class="text-muted mb-0 small">{{ __('messages.endpoints.batch_test_result_copy') }}</p>
            </div>
            <span class="badge badge-soft-{{ $endpointBatchResult['tone'] ?? 'secondary' }} badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.endpoints.batch_test_total') }}: {{ $endpointBatchResult['total'] ?? 0 }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded border text-center h-100">
                        <div class="text-muted small">{{ __('messages.endpoints.batch_test_passed') }}</div>
                        <h3 class="fw-light mb-0 text-success">{{ $endpointBatchResult['passed'] ?? 0 }}</h3>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded border text-center h-100">
                        <div class="text-muted small">{{ __('messages.endpoints.batch_test_warning') }}</div>
                        <h3 class="fw-light mb-0 text-warning">{{ $endpointBatchResult['warning'] ?? 0 }}</h3>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded border text-center h-100">
                        <div class="text-muted small">{{ __('messages.endpoints.batch_test_failed') }}</div>
                        <h3 class="fw-light mb-0 text-danger">{{ $endpointBatchResult['failed'] ?? 0 }}</h3>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="p-3 rounded border text-center h-100">
                        <div class="text-muted small">{{ __('messages.endpoints.batch_test_skipped') }}</div>
                        <h3 class="fw-light mb-0 text-secondary">{{ $endpointBatchResult['skipped'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
            @if (! empty($endpointBatchResult['recent_runs']))
                <div class="mt-3">
                    <div class="text-muted small mb-2">{{ __('messages.endpoints.batch_test_recent') }}</div>
                    <div class="d-flex flex-column gap-2">
                        @foreach ($endpointBatchResult['recent_runs'] as $run)
                            <div class="d-flex align-items-center justify-content-between gap-2 border rounded p-2">
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <span class="badge text-bg-{{ in_array($run['endpoint_method'] ?? 'GET', ['GET', 'HEAD'], true) ? 'success' : 'warning' }}">{{ $run['endpoint_method'] ?? 'GET' }}</span>
                                    <div class="min-w-0">
                                        <span class="d-block text-truncate">{{ $run['endpoint_name'] ?? __('messages.endpoints.unnamed') }}</span>
                                        <small class="text-muted d-block text-truncate">{{ $run['endpoint_path'] ?? '—' }}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <span class="badge badge-soft-{{ $run['tone'] ?? 'secondary' }}">{{ __('messages.endpoints.quick_test_states.'.($run['state'] ?? 'skipped')) }}</span>
                                    <a href="{{ route('projects.endpoint-test-runs.show', [$project, $run['id']]) }}" class="btn btn-sm btn-light"><i data-lucide="file-check-2" class="me-1"></i>{{ __('messages.endpoints.view_evidence') }}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
            <span>{{ __('messages.endpoints.batch_test_footer') }}</span>
            <span>{{ $endpointBatchResult['message'] ?? '' }}</span>
            @if (! empty($endpointBatchResult['batch_id']))
                <a href="{{ route('projects.endpoint-test-batches.show', [$project, $endpointBatchResult['batch_id']]) }}" class="btn btn-sm btn-light"><i data-lucide="file-check-2" class="me-1"></i>{{ __('messages.endpoints.view_batch_evidence') }}</a>
            @endif
        </div>
    </div>
@endif

<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.endpoints.table_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.endpoints.table_copy') }}</p>
        </div>
        <div class="card-action">
            <span class="badge badge-soft-primary badge-label">{{ __('messages.endpoints.total_endpoints') }}: {{ $endpoints->count() }}</span>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#endpointCreateModal"><i data-lucide="plus" class="me-1"></i>{{ __('messages.endpoints.new') }}</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="endpoints" data-aptoria-paging="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-endpoints-table">
                <thead class="align-middle thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.endpoints.endpoint') }}</th>
                        <th data-priority="2">{{ __('messages.endpoints.path') }}</th>
                        <th data-priority="4">{{ __('messages.nav.environments') }}</th>
                        <th data-priority="5">{{ __('messages.nav.auth_profiles') }}</th>
                        <th data-priority="6">{{ __('messages.endpoints.risk') }}</th>
                        <th data-priority="7">{{ __('messages.endpoints.scan_status') }}</th>
                        <th data-priority="8">{{ __('messages.endpoints.latest_quick_test') }}</th>
                        <th data-priority="9">{{ __('messages.common.updated') }}</th>
                        <th data-priority="3" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($endpoints as $endpoint)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <span class="badge text-bg-{{ $endpoint->method_tone }}">{{ $endpoint->method }}</span>
                                    <div class="min-w-0">
                                        <span class="d-block text-truncate aptoria-endpoint-name-cell">{{ $endpoint->name ?: __('messages.endpoints.unnamed') }}</span>
                                        <small class="text-muted d-block text-truncate">{{ $endpoint->tags ?: __('messages.endpoints.no_tags') }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><code class="aptoria-endpoint-path-cell">{{ $endpoint->path }}</code></td>
                            <td><span class="badge badge-soft-{{ $endpoint->environment?->tone ?? 'secondary' }}">{{ $endpoint->environment?->name ?? __('messages.endpoints.default_target') }}</span></td>
                            <td><span class="badge badge-soft-{{ $endpoint->authProfile?->tone ?? 'secondary' }}">{{ $endpoint->authProfile?->name ?? __('messages.auth_profiles.no_auth_preview') }}</span></td>
                            <td><span class="badge badge-soft-{{ $endpoint->risk_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $endpoint->risk_label }}</span></td>
                            <td><span class="badge badge-soft-{{ $endpoint->scan_status_tone }}">{{ $endpoint->scan_status_label }}</span></td>
                            <td>
                                @if ($endpoint->latestTestRun)
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge badge-soft-{{ $endpoint->latestTestRun->tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $endpoint->latestTestRun->state_label }}</span>
                                        <small class="text-muted">{{ $endpoint->latestTestRun->status_summary }} · {{ $endpoint->latestTestRun->checked_at?->diffForHumans() }}</small>
                                        <a href="{{ route('projects.endpoint-test-runs.show', [$project, $endpoint->latestTestRun]) }}" class="small">{{ __('messages.endpoints.view_evidence') }}</a>
                                    </div>
                                @else
                                    <span class="badge badge-soft-secondary">{{ __('messages.endpoints.not_tested') }}</span>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $endpoint->updated_at?->diffForHumans() }}</small></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#endpointPreviewModal{{ $endpoint->id }}"><i data-lucide="eye" class="me-2"></i>{{ __('messages.projects.quick_preview') }}</button>
                                        <form method="POST" action="{{ route('projects.endpoints.test', [$project, $endpoint]) }}" data-aptoria-form-scope="endpoint_quick_test" data-aptoria-form-plugin data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.endpoints.quick_test_confirm_title') }}" data-confirm-text="{{ __('messages.endpoints.quick_test_confirm_text') }}" data-confirm-button="{{ __('messages.endpoints.run_quick_test') }}">@csrf<button class="dropdown-item" type="submit"><i data-lucide="play-circle" class="me-2"></i>{{ __('messages.endpoints.run_quick_test') }}</button></form>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#endpointEditModal{{ $endpoint->id }}"><i data-lucide="pencil" class="me-2"></i>{{ __('messages.common.edit') }}</button>
                                        <div class="dropdown-divider"></div>
                                        <form method="POST" action="{{ route('projects.endpoints.destroy', [$project, $endpoint]) }}" data-aptoria-confirm="delete" data-confirm-title="{{ __('messages.endpoints.delete_title') }}" data-confirm-text="{{ __('messages.endpoints.delete_text') }}" data-confirm-button="{{ __('messages.common.delete') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit"><i data-lucide="trash-2" class="me-2"></i>{{ __('messages.common.delete') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5">{{ __('messages.endpoints.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
        <span>{{ __('messages.endpoints.footer') }}</span>
        <span>{{ __('messages.endpoints.table_standard') }}</span>
    </div>
</div>


<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.endpoints.batch_test_history') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.endpoints.batch_test_history_copy') }}</p>
        </div>
        <div class="card-action">
            <span class="badge badge-soft-primary badge-label">{{ __('messages.endpoints.batch_test_runs') }}: {{ $endpointTestBatches->count() }}</span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="endpoint-test-batches" data-aptoria-actions="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-endpoint-test-batches-table">
                <thead class="align-middle thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.endpoints.batch_test_summary') }}</th>
                        <th data-priority="2">{{ __('messages.common.status') }}</th>
                        <th data-priority="3">{{ __('messages.endpoints.batch_test_result') }}</th>
                        <th data-priority="5">{{ __('messages.endpoints.duration') }}</th>
                        <th data-priority="4">{{ __('messages.endpoints.completed_at') }}</th>
                        <th data-priority="6" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($endpointTestBatches as $batch)
                        <tr>
                            <td>
                                <div class="d-flex flex-column gap-1 min-w-0">
                                    <span class="fw-normal">{{ __('messages.endpoints.batch_test_evidence') }} #{{ $batch->id }}</span>
                                    <small class="text-muted">{{ __('messages.endpoints.quick_test_runs') }}: {{ $batch->test_runs_count ?? $batch->total }}</small>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-{{ $batch->tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $batch->state_label }}</span></td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <span class="badge badge-soft-success">{{ __('messages.endpoints.batch_test_passed') }}: {{ $batch->passed }}</span>
                                    <span class="badge badge-soft-warning">{{ __('messages.endpoints.batch_test_warning') }}: {{ $batch->warning }}</span>
                                    <span class="badge badge-soft-danger">{{ __('messages.endpoints.batch_test_failed') }}: {{ $batch->failed }}</span>
                                    <span class="badge badge-soft-secondary">{{ __('messages.endpoints.batch_test_skipped') }}: {{ $batch->skipped }}</span>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-info">{{ $batch->duration_label }}</span></td>
                            <td><small class="text-muted">{{ $batch->completed_at?->diffForHumans() ?? '—' }}</small></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="{{ route('projects.endpoint-test-batches.show', [$project, $batch]) }}" class="dropdown-item"><i data-lucide="file-check-2" class="me-2"></i>{{ __('messages.endpoints.view_batch_evidence') }}</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">{{ __('messages.endpoints.batch_test_history_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
        <span>{{ __('messages.endpoints.batch_test_history_footer') }}</span>
        <span>{{ __('messages.endpoints.table_standard') }}</span>
    </div>
</div>


<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.endpoints.quick_test_history') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.endpoints.quick_test_history_copy') }}</p>
        </div>
        <div class="card-action">
            <span class="badge badge-soft-primary badge-label">{{ __('messages.endpoints.quick_test_runs') }}: {{ $endpointTestRuns->count() }}</span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="endpoint-test-runs" data-aptoria-actions="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-endpoint-test-runs-table">
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
                    @forelse ($endpointTestRuns as $run)
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
        <span>{{ __('messages.endpoints.quick_test_history_footer') }}</span>
        <span>{{ __('messages.endpoints.table_standard') }}</span>
    </div>
</div>

@include('endpoints.partials.modals')
@endsection
