@extends('layouts.app')
@section('title', __('messages.release_gates.title'))
@section('page_title', __('messages.release_gates.title'))
@section('page_actions')
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createReleaseGateModal"><i data-lucide="workflow" class="me-1"></i>{{ __('messages.release_gates.new_gate') }}</button>
@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-primary"><span class="avatar-title"><i data-lucide="workflow"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.release_gates.metrics.gates') }}</p><h3 class="mb-0 fw-light">{{ $summary['gate_count'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-warning"><span class="avatar-title"><i data-lucide="clock-alert"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.release_gates.metrics.open') }}</p><h3 class="mb-0 fw-light">{{ $summary['open_gate_count'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-success"><span class="avatar-title"><i data-lucide="badge-check"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.release_gates.metrics.approved') }}</p><h3 class="mb-0 fw-light">{{ $summary['approved_count'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-info"><span class="avatar-title"><i data-lucide="shield-chevron"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.release_gates.metrics.latest_score') }}</p><h3 class="mb-0 fw-light">{{ $summary['latest_gate']?->score ?? 0 }}%</h3></div></div></div></div>
</div>

<div class="card aptoria-panel-card mb-3">
    <div class="card-header border-light d-flex justify-content-between align-items-center">
        <div class="d-flex gap-3 align-items-center"><span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="workflow"></i></span></span><div><h5 class="card-title mb-1">{{ __('messages.release_gates.foundation_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_gates.foundation_copy') }}</p></div></div>
        <span class="badge badge-soft-primary"><i data-lucide="folder-check" class="me-1"></i>{{ __('messages.release_gates.evidence_based') }}</span>
    </div>
</div>

<div class="card aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div class="d-flex gap-3 align-items-center"><span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="workflow"></i></span></span><div><h5 class="card-title mb-1">{{ __('messages.release_gates.gates_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_gates.gates_copy') }}</p></div></div>
        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createReleaseGateModal"><i data-lucide="plus" class="me-1"></i>{{ __('messages.release_gates.new_gate') }}</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="release-gates" data-aptoria-paging="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                <thead class="thead-sm text-uppercase fs-xxs"><tr><th>{{ __('messages.release_gates.gate') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.release_gates.final_decision') }}</th><th>{{ __('messages.release_readiness.score') }}</th><th>{{ __('messages.release_gates.blockers_warnings') }}</th><th class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th></tr></thead>
                <tbody>
                    @forelse($gates as $gate)
                        <tr>
                            <td><div class="d-flex gap-2 align-items-start"><span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title"><i data-lucide="workflow"></i></span></span><div><a class="fw-medium text-body" href="{{ route('projects.release-gates.show', [$project, $gate]) }}">{{ $gate->title }}</a><small class="text-muted d-block">{{ $gate->release_version ?: __('messages.common.not_available') }} · {{ $gate->target_environment ?: __('messages.common.not_available') }}</small></div></div></td>
                            <td><span class="badge badge-soft-{{ $gate->status_tone }}">{{ $gate->status_label }}</span><small class="text-muted d-block">{{ $gate->profile_label }}</small></td>
                            <td><span class="badge badge-soft-{{ $gate->final_decision_tone }}">{{ $gate->final_decision_label }}</span></td>
                            <td><div class="d-flex align-items-center gap-2"><strong>{{ $gate->score }}%</strong><div class="progress progress-sm flex-grow-1"><div class="progress-bar bg-{{ $gate->status_tone }}" style="width: {{ $gate->score }}%;"></div></div></div><small class="text-muted">{{ $gate->grade }}</small></td>
                            <td><span class="badge badge-soft-danger me-1"><i data-lucide="octagon-alert" class="me-1"></i>{{ $gate->blocker_count }}</span><span class="badge badge-soft-warning"><i data-lucide="triangle-alert" class="me-1"></i>{{ $gate->warning_count }}</span></td>
                            <td class="text-end"><div class="dropdown"><button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">{{ __('messages.common.actions') }}</button><div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="{{ route('projects.release-gates.show', [$project, $gate]) }}"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.view') }}</a>@if($gate->release_readiness_run_id)<a class="dropdown-item" href="{{ route('projects.release-readiness.show', [$project, $gate->release_readiness_run_id]) }}"><i data-lucide="shield-chevron" class="me-2"></i>{{ __('messages.release_gates.view_readiness_run') }}</a>@endif</div></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">{{ __('messages.release_gates.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('release_gates.partials.create-gate-modal', ['project' => $project, 'modalId' => 'createReleaseGateModal'])
@include('release_gates.partials.reopen-modal-script')
@endsection
