@extends('layouts.app')
@section('title', $gate->title)
@section('page_title', $gate->title)
@section('page_actions')
    <a href="{{ route('projects.release-gates.index', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.release_gates.back_to_gates') }}</a>
    <div class="dropdown d-inline-block">
        <button type="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i data-lucide="package-check" class="me-1"></i>{{ __('messages.release_gates.package.downloads') }}</button>
        <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item" href="{{ route('projects.release-gates.download', [$project, $gate, 'html']) }}"><i data-lucide="code-xml" class="me-2"></i>HTML</a>
            <a class="dropdown-item" href="{{ route('projects.release-gates.download', [$project, $gate, 'pdf']) }}"><i data-lucide="file-type-pdf" class="me-2"></i>PDF</a>
            <a class="dropdown-item" href="{{ route('projects.release-gates.download', [$project, $gate, 'json']) }}"><i data-lucide="braces" class="me-2"></i>JSON</a>
            <a class="dropdown-item" href="{{ route('projects.release-gates.download', [$project, $gate, 'zip']) }}"><i data-lucide="archive" class="me-2"></i>ZIP</a>
        </div>
    </div>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createDecisionPackageModal"><i data-lucide="file-check-2" class="me-1"></i>{{ __('messages.release_gates.package.create_report') }}</button>
    @if($gate->final_decision === 'pending')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#finalizeGateModal"><i data-lucide="badge-check" class="me-1"></i>{{ __('messages.release_gates.finalize_gate') }}</button>
    @endif
@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-lg-4"><div class="card h-100 aptoria-panel-card"><div class="card-body"><div class="d-flex gap-3 align-items-start"><span class="avatar avatar-md rounded text-bg-{{ $gate->status_tone }}"><span class="avatar-title"><i data-lucide="workflow"></i></span></span><div><h5 class="mb-1">{{ $gate->title }}</h5><p class="text-muted mb-3">{{ $gate->release_version ?: __('messages.common.not_available') }} · {{ $gate->target_environment ?: __('messages.common.not_available') }}</p><span class="badge badge-soft-{{ $gate->status_tone }} me-1">{{ $gate->status_label }}</span><span class="badge badge-soft-{{ $gate->automated_decision_tone }}">{{ $gate->automated_decision_label }}</span></div></div></div><div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.release_gates.created_by') }}: {{ $gate->createdBy?->name ?? __('messages.common.not_available') }}</div></div></div>
    <div class="col-lg-8"><div class="row g-3"><div class="col-md-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-primary"><span class="avatar-title"><i data-lucide="shield-chevron"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.release_readiness.score') }}</p><h3 class="mb-0 fw-light">{{ $gate->score }}%</h3><small class="text-muted">{{ $gate->grade }}</small></div></div></div></div><div class="col-md-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-danger"><span class="avatar-title"><i data-lucide="octagon-alert"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.release_gates.metrics.blockers') }}</p><h3 class="mb-0 fw-light">{{ $gate->blocker_count }}</h3></div></div></div></div><div class="col-md-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-warning"><span class="avatar-title"><i data-lucide="triangle-alert"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.release_gates.metrics.warnings') }}</p><h3 class="mb-0 fw-light">{{ $gate->warning_count }}</h3></div></div></div></div><div class="col-md-3"><div class="card h-100"><div class="card-body d-flex gap-3 align-items-center"><span class="avatar avatar-md rounded text-bg-success"><span class="avatar-title"><i data-lucide="fingerprint"></i></span></span><div><p class="text-muted mb-1">{{ __('messages.release_gates.metrics.verified_evidence') }}</p><h3 class="mb-0 fw-light">{{ $gate->verified_evidence_count }}</h3><small class="text-muted">/ {{ $gate->evidence_count }}</small></div></div></div></div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card aptoria-table-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center"><div class="d-flex gap-3 align-items-center"><span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="workflow"></i></span></span><div><h5 class="card-title mb-1">{{ __('messages.release_gates.items_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_gates.items_copy') }}</p></div></div><span class="badge badge-soft-secondary">{{ $gate->total_item_count }}</span></div>
            <div class="card-body">
                @foreach($itemsByCategory as $category => $items)
                    <div class="mb-4">
                        <h6 class="text-uppercase text-muted fs-xxs mb-2"><i data-lucide="{{ match($category) { 'evidence' => 'folder-check', 'tests' => 'flask-conical', 'findings' => 'bug', 'imports' => 'brackets-contain', 'contract' => 'file-check-2', 'risk' => 'shield-alert', default => 'workflow' } }}" class="me-1"></i>{{ __('messages.release_gates.categories.'.$category) }}</h6>
                        <div class="table-responsive">
                            <table class="table table-custom table-striped table-centered mb-0 w-100 aptoria-resource-table aptoria-release-gate-items-table">
                                <thead class="thead-sm text-uppercase fs-xxs"><tr><th>{{ __('messages.release_gates.item') }}</th><th>{{ __('messages.release_gates.automated_state') }}</th><th>{{ __('messages.release_gates.effective_state') }}</th><th>{{ __('messages.release_gates.required_action') }}</th><th class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th></tr></thead>
                                <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td><div class="d-flex gap-2 align-items-start"><span class="avatar avatar-xs rounded text-bg-{{ $item->effective_state_tone }}"><span class="avatar-title"><i data-lucide="{{ $item->icon }}"></i></span></span><div><span class="fw-medium text-body">{{ $item->label }}</span><small class="text-muted d-block">{{ $item->category_label }}</small>@if($item->reviewer_note)<small class="text-muted d-block text-truncate">{{ $item->reviewer_note }}</small>@endif</div></div></td>
                                        <td><span class="badge badge-soft-{{ $item->automated_state === 'blocked' ? 'danger' : ($item->automated_state === 'pass' ? 'success' : 'warning') }}">{{ __('messages.release_gates.item_states.'.$item->automated_state) }}</span></td>
                                        <td><span class="badge badge-soft-{{ $item->effective_state_tone }}">{{ $item->effective_state_label }}</span>@if($item->manual_state)<small class="text-muted d-block">{{ __('messages.release_gates.manual_override') }}</small>@endif</td>
                                        <td><small class="text-muted text-wrap d-block">{{ $item->required_action ?: __('messages.release_gates.no_required_action') }}</small></td>
                                        <td class="text-end"><button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#reviewGateItemModal{{ $item->id }}"><i data-lucide="clipboard-search" class="me-1"></i>{{ __('messages.release_gates.review_item') }}</button></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light"><div class="d-flex gap-3 align-items-center"><span class="avatar avatar-sm rounded text-bg-info"><span class="avatar-title"><i data-lucide="folder-check"></i></span></span><div><h5 class="card-title mb-1">{{ __('messages.release_gates.source_state_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_gates.source_state_copy') }}</p></div></div></div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ __('messages.nav.evidence') }}</span><strong>{{ $gate->verified_evidence_count }} / {{ $gate->evidence_count }}</strong></div>
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ __('messages.nav.native_tests') }}</span><strong>{{ $gate->failed_test_run_count }} / {{ $gate->test_run_count }}</strong></div>
                <div class="d-flex justify-content-between border-bottom py-2"><span>{{ __('messages.nav.findings') }}</span><strong>{{ $gate->high_critical_open_count }} / {{ $gate->open_finding_count }}</strong></div>
                <div class="d-flex justify-content-between py-2"><span>{{ __('messages.release_gates.final_decision') }}</span><span class="badge badge-soft-{{ $gate->final_decision_tone }}">{{ $gate->final_decision_label }}</span></div>
            </div>
        </div>
        <div class="card aptoria-panel-card mb-3">
            <div class="card-header border-light"><div class="d-flex gap-3 align-items-center"><span class="avatar avatar-sm rounded text-bg-success"><span class="avatar-title"><i data-lucide="package-check"></i></span></span><div><h5 class="card-title mb-1">{{ __('messages.release_gates.package.title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_gates.package.copy') }}</p></div></div></div>
            <div class="card-body">
                @if($latestDecisionPackageReport)
                    <div class="d-flex justify-content-between border-bottom py-2"><span>{{ __('messages.nav.reports') }}</span><a href="{{ route('projects.reports.show', [$project, $latestDecisionPackageReport]) }}">#{{ $latestDecisionPackageReport->id }}</a></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span>{{ __('messages.reports.status') }}</span><span class="badge badge-soft-{{ $latestDecisionPackageReport->status_tone }}">{{ $latestDecisionPackageReport->status_label }}</span></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span>{{ __('messages.reports.checksum') }}</span><code class="small text-truncate ms-2">{{ \Illuminate\Support\Str::limit($latestDecisionPackageReport->checksum, 16, '') }}</code></div>
                    <div class="d-flex gap-2 flex-wrap pt-3">
                        <a class="btn btn-light btn-sm" href="{{ route('projects.reports.download', [$project, $latestDecisionPackageReport, 'html']) }}"><i data-lucide="code-xml" class="me-1"></i>HTML</a>
                        <a class="btn btn-light btn-sm" href="{{ route('projects.reports.download', [$project, $latestDecisionPackageReport, 'pdf']) }}"><i data-lucide="file-type-pdf" class="me-1"></i>PDF</a>
                        <a class="btn btn-light btn-sm" href="{{ route('projects.release-gates.download', [$project, $gate, 'zip']) }}"><i data-lucide="archive" class="me-1"></i>ZIP</a>
                    </div>
                @else
                    <p class="text-muted mb-3">{{ __('messages.release_gates.package.empty') }}</p>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createDecisionPackageModal"><i data-lucide="file-check-2" class="me-1"></i>{{ __('messages.release_gates.package.create_report') }}</button>
                @endif
            </div>
        </div>
        <div class="card aptoria-panel-card">
            <div class="card-header border-light"><div class="d-flex gap-3 align-items-center"><span class="avatar avatar-sm rounded text-bg-secondary"><span class="avatar-title"><i data-lucide="file-delta"></i></span></span><div><h5 class="card-title mb-1">{{ __('messages.release_gates.timeline_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.release_gates.timeline_copy') }}</p></div></div></div>
            <div class="card-body">
                @forelse($gate->events as $event)
                    <div class="d-flex gap-2 mb-3"><span class="avatar avatar-xs rounded text-bg-{{ $event->severity === 'warning' ? 'warning' : 'light' }}"><span class="avatar-title"><i data-lucide="file-delta"></i></span></span><div><p class="mb-1">{{ $event->summary }}</p><small class="text-muted">{{ $event->occurred_at?->format('Y-m-d H:i') ?? $event->created_at?->format('Y-m-d H:i') }} · {{ $event->user?->name ?? __('messages.common.system') }}</small></div></div>
                @empty
                    <p class="text-muted mb-0">{{ __('messages.release_gates.no_events') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@foreach($gate->items as $item)
    @include('release_gates.partials.review-item-modal', ['project' => $project, 'gate' => $gate, 'item' => $item, 'modalId' => 'reviewGateItemModal'.$item->id])
@endforeach
@include('release_gates.partials.finalize-modal', ['project' => $project, 'gate' => $gate, 'modalId' => 'finalizeGateModal'])
@include('release_gates.partials.decision-package-modal', ['project' => $project, 'gate' => $gate, 'modalId' => 'createDecisionPackageModal'])
@include('release_gates.partials.reopen-modal-script')
@endsection
