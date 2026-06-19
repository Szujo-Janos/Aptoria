@extends('layouts.app')
@section('title', __('messages.findings.title'))
@section('page_title', __('messages.findings.title'))
@section('page_actions')
    <a href="{{ route('projects.findings.dedup.index', $project) }}" class="btn btn-light"><i data-lucide="combine" class="me-1"></i>{{ __('messages.finding_dedup.title') }}</a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#findingCreateModal"><i data-lucide="plus" class="me-1"></i>{{ __('messages.findings.new') }}</button>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar avatar-md rounded text-bg-danger"><span class="avatar-title"><i data-lucide="bug"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.findings.open_findings') }}</p><h3 class="mb-0 fw-light">{{ $metrics['open'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar avatar-md rounded text-bg-danger"><span class="avatar-title"><i data-lucide="alert-triangle"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.findings.critical') }}</p><h3 class="mb-0 fw-light">{{ $metrics['critical'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar avatar-md rounded text-bg-warning"><span class="avatar-title"><i data-lucide="flame"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.findings.high') }}</p><h3 class="mb-0 fw-light">{{ $metrics['high'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar avatar-md rounded text-bg-primary"><span class="avatar-title"><i data-lucide="certificate"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.findings.needs_evidence') }}</p><h3 class="mb-0 fw-light">{{ $metrics['needs_evidence'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar avatar-md rounded text-bg-info"><span class="avatar-title"><i data-lucide="rotate-ccw"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.findings.retest_ready') }}</p><h3 class="mb-0 fw-light">{{ $metrics['retest_ready'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar avatar-md rounded text-bg-danger"><span class="avatar-title"><i data-lucide="shield-x"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.findings.retest_failed') }}</p><h3 class="mb-0 fw-light">{{ $metrics['retest_failed'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar avatar-md rounded text-bg-success"><span class="avatar-title"><i data-lucide="shield-check"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.risk_acceptance.accepted_risk') }}</p><h3 class="mb-0 fw-light">{{ $metrics['accepted_risk'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar avatar-md rounded text-bg-warning"><span class="avatar-title"><i data-lucide="calendar-clock"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.risk_acceptance.statuses.expiring_soon') }}</p><h3 class="mb-0 fw-light">{{ $metrics['accepted_risk_expiring_soon'] }}</h3></div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><span class="avatar avatar-md rounded text-bg-danger"><span class="avatar-title"><i data-lucide="shield-alert"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.risk_acceptance.statuses.expired') }}</p><h3 class="mb-0 fw-light">{{ $metrics['accepted_risk_expired'] }}</h3></div></div></div></div>
</div>

<div class="card aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.findings.table_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.findings.copy') }}</p>
        </div>
        <span class="badge badge-soft-primary badge-label">{{ $findings->count() }} {{ __('messages.nav.findings') }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="findings" data-aptoria-paging="true" data-aptoria-order-column="6" data-aptoria-order-dir="desc" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                <thead class="align-middle thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.findings.finding') }}</th>
                        <th data-priority="2">{{ __('messages.endpoints.endpoint') }}</th>
                        <th data-priority="3">{{ __('messages.findings.severity') }}</th>
                        <th data-priority="4">{{ __('messages.findings.status') }}</th>
                        <th data-priority="5">{{ __('messages.findings.retest') }}</th>
                        <th data-priority="5">{{ __('messages.nav.evidence') }}</th>
                        <th data-priority="6">{{ __('messages.common.updated') }}</th>
                        <th data-priority="1" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($findings as $finding)
                        <tr>
                            <td><div class="d-flex align-items-center gap-2 min-w-0"><span class="avatar avatar-xs rounded text-bg-{{ $finding->severity_tone }}"><span class="avatar-title"><i data-lucide="bug"></i></span></span><div class="min-w-0"><a href="{{ route('projects.findings.show', [$project, $finding]) }}" class="fw-medium text-body text-truncate d-block aptoria-endpoint-name-cell">{{ $finding->title }}</a><small class="text-muted">{{ $finding->source_label }}</small>@if($finding->active_risk_acceptance)<span class="badge badge-soft-{{ $finding->active_risk_acceptance->status_tone }} ms-2"><i data-lucide="shield-check" class="me-1"></i>{{ $finding->active_risk_acceptance->status_label }}</span>@elseif($finding->latest_risk_acceptance?->display_status === 'expired')<span class="badge badge-soft-danger ms-2"><i data-lucide="shield-alert" class="me-1"></i>{{ __('messages.risk_acceptance.statuses.expired') }}</span>@endif</div></div></td>
                            <td><span class="badge text-bg-{{ $finding->endpoint?->method_tone ?? 'secondary' }} me-1">{{ $finding->endpoint?->method ?? '—' }}</span><code class="aptoria-endpoint-path-cell">{{ $finding->endpoint?->path ?? '—' }}</code></td>
                            <td><span class="badge badge-soft-{{ $finding->severity_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $finding->severity_label }}</span></td>
                            <td><span class="badge badge-soft-{{ $finding->status_tone }}">{{ $finding->status_label }}</span></td>
                            <td><span class="badge badge-soft-{{ $finding->retest_status_tone }}">{{ $finding->retest_status_label }}</span></td>
                            <td><div class="d-flex align-items-center gap-2"><span class="badge text-bg-light">{{ $finding->evidence_count }}</span>@if($finding->evidence_required && $finding->evidence_count === 0)<span class="badge badge-soft-warning">{{ __('messages.findings.evidence_required') }}</span>@endif</div></td>
                            <td><small class="text-muted">{{ $finding->updated_at?->format('Y-m-d H:i') }}</small></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="{{ route('projects.findings.show', [$project, $finding]) }}" class="dropdown-item"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.open') }}</a>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#findingEditModal{{ $finding->id }}"><i data-lucide="pencil" class="me-2"></i>{{ __('messages.common.edit') }}</button>
                                        <form method="POST" action="{{ route('projects.findings.destroy', [$project, $finding]) }}" data-aptoria-confirm="{{ __('messages.findings.confirm_delete') }}">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit"><i data-lucide="trash-2" class="me-2"></i>{{ __('messages.common.delete') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">{{ __('messages.findings.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.findings.footer') }}</div>
</div>

@include('findings.partials.form-modal', ['modalId' => 'findingCreateModal', 'action' => route('projects.findings.store', $project), 'method' => 'POST', 'finding' => null, 'project' => $project, 'endpoints' => $endpoints, 'scanResults' => $scanResults])
@foreach ($findings as $finding)
    @include('findings.partials.form-modal', ['modalId' => 'findingEditModal'.$finding->id, 'action' => route('projects.findings.update', [$project, $finding]), 'method' => 'PUT', 'finding' => $finding, 'project' => $project, 'endpoints' => $endpoints, 'scanResults' => $scanResults])
@endforeach
@endsection
