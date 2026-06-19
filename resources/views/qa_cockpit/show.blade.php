@extends('layouts.app')
@section('title', __('messages.qa_cockpit.title'))
@section('page_title', __('messages.qa_cockpit.title'))
@section('page_actions')
    <a href="{{ route('projects.release-gates.index', $project) }}" class="btn btn-primary"><i data-lucide="workflow" class="me-1"></i>{{ __('messages.qa_cockpit.actions.create_gate') }}</a>
    <a href="{{ route('projects.evidence-packs.index', $project) }}" class="btn btn-light"><i data-lucide="archive" class="me-1"></i>{{ __('messages.qa_cockpit.actions.create_export') }}</a>
@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-body d-flex gap-3 align-items-center">
                <span class="avatar avatar-lg rounded text-bg-{{ $cockpit['score_tone'] }}"><span class="avatar-title"><i data-lucide="scan-search"></i></span></span>
                <div>
                    <p class="text-muted mb-1">{{ __('messages.qa_cockpit.confidence_score') }}</p>
                    <h2 class="mb-0 fw-light">{{ $cockpit['score'] }}%</h2>
                    <small class="text-muted">{{ __('messages.qa_cockpit.confidence_copy') }}</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-2"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-primary"><span class="avatar-title"><i data-lucide="route"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.qa_cockpit.metrics.endpoints') }}</p><h3 class="mb-0 fw-light">{{ $cockpit['metrics']['endpoints'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-lg-2"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-info"><span class="avatar-title"><i data-lucide="scan-eye"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.qa_cockpit.metrics.scanned') }}</p><h3 class="mb-0 fw-light">{{ $cockpit['coverage']['scan'] }}%</h3></div></div></div></div>
    <div class="col-sm-6 col-lg-2"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-success"><span class="avatar-title"><i data-lucide="flask-conical"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.qa_cockpit.metrics.tests') }}</p><h3 class="mb-0 fw-light">{{ max($cockpit['coverage']['native_test'], $cockpit['coverage']['quick_test']) }}%</h3></div></div></div></div>
    <div class="col-sm-6 col-lg-2"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-{{ $cockpit['metrics']['high_critical_open'] > 0 ? 'danger' : 'success' }}"><span class="avatar-title"><i data-lucide="octagon-alert"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.qa_cockpit.metrics.blockers') }}</p><h3 class="mb-0 fw-light">{{ $cockpit['metrics']['high_critical_open'] }}</h3></div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card aptoria-panel-card h-100">
            <div class="card-header border-light justify-content-between align-items-center">
                <div class="d-flex gap-3 align-items-center">
                    <span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="bar-chart-3"></i></span></span>
                    <div><h5 class="card-title mb-1">{{ __('messages.qa_cockpit.coverage_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.qa_cockpit.coverage_copy') }}</p></div>
                </div>
                <span class="badge badge-soft-{{ $cockpit['score_tone'] }}">{{ $cockpit['score'] }}%</span>
            </div>
            <div class="card-body">
                @foreach([
                    ['key' => 'scan', 'icon' => 'scan-eye', 'label' => __('messages.qa_cockpit.coverage.scan')],
                    ['key' => 'quick_test', 'icon' => 'play', 'label' => __('messages.qa_cockpit.coverage.quick_test')],
                    ['key' => 'native_test', 'icon' => 'flask-conical', 'label' => __('messages.qa_cockpit.coverage.native_test')],
                    ['key' => 'evidence', 'icon' => 'folder-check', 'label' => __('messages.qa_cockpit.coverage.evidence')],
                    ['key' => 'verified_evidence', 'icon' => 'fingerprint', 'label' => __('messages.qa_cockpit.coverage.verified_evidence')],
                ] as $row)
                    @php($value = $cockpit['coverage'][$row['key']] ?? 0)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-medium"><i data-lucide="{{ $row['icon'] }}" class="me-1"></i>{{ $row['label'] }}</span>
                            <span class="badge badge-soft-{{ $value >= 75 ? 'success' : ($value >= 40 ? 'warning' : 'danger') }}">{{ $value }}%</span>
                        </div>
                        <div class="progress progress-sm"><div class="progress-bar bg-{{ $value >= 75 ? 'success' : ($value >= 40 ? 'warning' : 'danger') }}" style="width: {{ $value }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card h-100">
            <div class="card-header border-light justify-content-between align-items-center">
                <div class="d-flex gap-3 align-items-center">
                    <span class="avatar avatar-sm rounded text-bg-warning"><span class="avatar-title"><i data-lucide="radar"></i></span></span>
                    <div><h5 class="card-title mb-1">{{ __('messages.qa_cockpit.blind_spots_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.qa_cockpit.blind_spots_copy') }}</p></div>
                </div>
                <span class="badge badge-soft-secondary">{{ count($cockpit['blind_spots']) }}</span>
            </div>
            <div class="card-body">
                @forelse($cockpit['blind_spots'] as $spot)
                    <div class="d-flex gap-2 mb-3 pb-3 border-bottom">
                        <span class="avatar avatar-xs rounded text-bg-{{ $spot['severity'] === 'blocker' ? 'danger' : ($spot['severity'] === 'warning' ? 'warning' : 'info') }}"><span class="avatar-title"><i data-lucide="{{ $spot['icon'] }}"></i></span></span>
                        <div class="flex-grow-1">
                            <p class="mb-1 fw-medium">{{ $spot['title'] }}</p>
                            <a href="{{ $spot['url'] }}" class="small text-decoration-none"><i data-lucide="arrow-up-right" class="me-1"></i>{{ $spot['action'] }}</a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i data-lucide="badge-check" class="fs-1 mb-2"></i>
                        <p class="mb-0">{{ __('messages.qa_cockpit.no_blind_spots') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="card aptoria-table-card aptoria-panel-card mb-3">
    <div class="card-header border-light justify-content-between align-items-center">
        <div class="d-flex gap-3 align-items-center">
            <span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="table-2"></i></span></span>
            <div><h5 class="card-title mb-1">{{ __('messages.qa_cockpit.matrix_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.qa_cockpit.matrix_copy') }}</p></div>
        </div>
        <span class="badge badge-soft-primary">{{ $cockpit['coverage_rows']->count() }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="qa-cockpit-matrix" data-aptoria-paging="true" class="table table-custom table-striped table-centered mb-0 w-100 aptoria-resource-table aptoria-coverage-matrix-table">
                <thead class="thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th>{{ __('messages.qa_cockpit.matrix.endpoint') }}</th>
                        <th>{{ __('messages.qa_cockpit.matrix.score') }}</th>
                        <th>{{ __('messages.qa_cockpit.matrix.scan') }}</th>
                        <th>{{ __('messages.qa_cockpit.matrix.tests') }}</th>
                        <th>{{ __('messages.qa_cockpit.matrix.evidence') }}</th>
                        <th>{{ __('messages.qa_cockpit.matrix.findings') }}</th>
                        <th class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cockpit['coverage_rows'] as $row)
                        @php($endpoint = $row['endpoint'])
                        <tr>
                            <td><div class="d-flex gap-2 align-items-start"><span class="badge badge-soft-{{ $endpoint->method_tone }}">{{ $endpoint->method }}</span><div><a class="fw-medium text-body text-truncate aptoria-endpoint-path-cell d-block" href="{{ route('projects.endpoints.index', $project) }}">{{ $endpoint->path }}</a><small class="text-muted d-block text-truncate">{{ $endpoint->name ?: __('messages.common.not_available') }}</small></div></div></td>
                            <td><span class="badge badge-soft-{{ $row['tone'] }}">{{ $row['score'] }}%</span></td>
                            <td><span class="badge badge-soft-{{ $row['signals']['scan'] ? 'success' : 'warning' }}"><i data-lucide="scan-eye" class="me-1"></i>{{ $endpoint->scan_results_count }}</span></td>
                            <td><span class="badge badge-soft-{{ ($row['signals']['native_test'] || $row['signals']['quick_test']) ? 'success' : 'warning' }}"><i data-lucide="flask-conical" class="me-1"></i>{{ $row['native_test_count'] }} / {{ $endpoint->test_runs_count }}</span></td>
                            <td><span class="badge badge-soft-{{ $row['signals']['verified'] ? 'success' : ($row['signals']['evidence'] ? 'warning' : 'danger') }}"><i data-lucide="fingerprint" class="me-1"></i>{{ $row['verified_evidence_count'] }} / {{ $endpoint->evidence_count }}</span></td>
                            <td><span class="badge badge-soft-{{ $endpoint->findings_count > 0 ? 'danger' : 'success' }}"><i data-lucide="bug" class="me-1"></i>{{ $endpoint->findings_count }}</span></td>
                            <td class="text-end"><div class="dropdown"><button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"><i data-lucide="ellipsis" class="me-1"></i>{{ __('messages.common.actions') }}</button><div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="{{ route('projects.endpoints.index', $project) }}"><i data-lucide="route" class="me-2"></i>{{ __('messages.nav.endpoint_inventory') }}</a><a class="dropdown-item" href="{{ route('projects.evidence.index', ['project' => $project, 'endpoint' => $endpoint->id]) }}"><i data-lucide="folder-check" class="me-2"></i>{{ __('messages.nav.evidence') }}</a><a class="dropdown-item" href="{{ route('projects.tests.index', $project) }}"><i data-lucide="flask-conical" class="me-2"></i>{{ __('messages.nav.native_tests') }}</a></div></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">{{ __('messages.qa_cockpit.no_matrix_rows') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6"><div class="card aptoria-panel-card h-100"><div class="card-header border-light"><div class="d-flex gap-3 align-items-center"><span class="avatar avatar-sm rounded text-bg-info"><span class="avatar-title"><i data-lucide="shield-chevron"></i></span></span><div><h5 class="card-title mb-1">{{ __('messages.qa_cockpit.latest_readiness') }}</h5><p class="text-muted mb-0 small">{{ __('messages.qa_cockpit.latest_readiness_copy') }}</p></div></div></div><div class="card-body">@if($cockpit['latest_readiness'])<div class="d-flex justify-content-between align-items-center"><div><strong>{{ $cockpit['latest_readiness']->score }}%</strong><span class="badge badge-soft-{{ $cockpit['latest_readiness']->status_tone }} ms-2">{{ $cockpit['latest_readiness']->status_label }}</span></div><a href="{{ route('projects.release-readiness.show', [$project, $cockpit['latest_readiness']]) }}" class="btn btn-light btn-sm"><i data-lucide="eye" class="me-1"></i>{{ __('messages.common.view') }}</a></div>@else<p class="text-muted mb-0">{{ __('messages.qa_cockpit.no_readiness') }}</p>@endif</div></div></div>
    <div class="col-md-6"><div class="card aptoria-panel-card h-100"><div class="card-header border-light"><div class="d-flex gap-3 align-items-center"><span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="workflow"></i></span></span><div><h5 class="card-title mb-1">{{ __('messages.qa_cockpit.latest_gate') }}</h5><p class="text-muted mb-0 small">{{ __('messages.qa_cockpit.latest_gate_copy') }}</p></div></div></div><div class="card-body">@if($cockpit['latest_gate'])<div class="d-flex justify-content-between align-items-center"><div><strong>{{ $cockpit['latest_gate']->title }}</strong><span class="badge badge-soft-{{ $cockpit['latest_gate']->status_tone }} ms-2">{{ $cockpit['latest_gate']->status_label }}</span></div><a href="{{ route('projects.release-gates.show', [$project, $cockpit['latest_gate']]) }}" class="btn btn-light btn-sm"><i data-lucide="eye" class="me-1"></i>{{ __('messages.common.view') }}</a></div>@else<p class="text-muted mb-0">{{ __('messages.qa_cockpit.no_gate') }}</p>@endif</div></div></div>
</div>
@endsection
