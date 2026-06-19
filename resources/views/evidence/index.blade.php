@extends('layouts.app')
@section('title', __('messages.evidence.title'))
@section('page_title', __('messages.evidence.title'))
@section('page_actions')
    <a href="{{ route('projects.evidence.create', $project) }}" class="btn btn-primary">
        <i data-lucide="file-plus" class="me-1"></i>{{ __('messages.evidence.add') }}
    </a>
@endsection
@section('content')
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body d-flex gap-3 align-items-center">
            <span class="avatar avatar-md rounded text-bg-primary"><span class="avatar-title"><i data-lucide="folder-check"></i></span></span>
            <div><p class="text-muted mb-1">{{ __('messages.evidence.total') }}</p><h3 class="mb-0 fw-light">{{ $metrics['total'] }}</h3></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body d-flex gap-3 align-items-center">
            <span class="avatar avatar-md rounded text-bg-success"><span class="avatar-title"><i data-lucide="badge-check"></i></span></span>
            <div><p class="text-muted mb-1">{{ __('messages.evidence.verified_metric') }}</p><h3 class="mb-0 fw-light">{{ $metrics['verified'] }}</h3></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body d-flex gap-3 align-items-center">
            <span class="avatar avatar-md rounded text-bg-info"><span class="avatar-title"><i data-lucide="git-fork"></i></span></span>
            <div><p class="text-muted mb-1">{{ __('messages.evidence.linked') }}</p><h3 class="mb-0 fw-light">{{ $metrics['linked'] }}</h3></div>
        </div></div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body d-flex gap-3 align-items-center">
            <span class="avatar avatar-md rounded text-bg-{{ $metrics['integrity_changed'] > 0 ? 'danger' : 'success' }}"><span class="avatar-title"><i data-lucide="fingerprint"></i></span></span>
            <div><p class="text-muted mb-1">{{ __('messages.evidence.integrity_attention') }}</p><h3 class="mb-0 fw-light">{{ $metrics['integrity_changed'] }}</h3></div>
        </div></div>
    </div>
</div>

<div class="card aptoria-panel-card mb-3">
    <div class="card-header border-light d-flex justify-content-between align-items-center">
        <div class="d-flex gap-3 align-items-center">
            <span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="fingerprint"></i></span></span>
            <div>
                <h5 class="card-title mb-1">{{ __('messages.evidence.repository_assurance') }}</h5>
                <p class="text-muted mb-0 small">{{ __('messages.evidence.repository_assurance_copy') }}</p>
            </div>
        </div>
        <span class="badge badge-soft-success"><i data-lucide="file-delta" class="me-1"></i>{{ __('messages.evidence.lifecycle_audited') }}</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('projects.evidence.index', $project) }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.evidence.filter_status') }}</label>
                <select name="status" class="form-select">
                    @foreach(['open', 'active', 'verified', 'archived', 'all'] as $option)
                        <option value="{{ $option }}" @selected(($filters['status'] ?? 'open') === $option)>{{ __('messages.evidence.filters.status_'.$option) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.evidence.filter_type') }}</label>
                <select name="type" class="form-select">
                    <option value="">{{ __('messages.common.all') }}</option>
                    @foreach(\App\Models\FindingEvidence::TYPES as $type)
                        <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ __('messages.evidence.types.'.$type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('messages.evidence.filter_integrity') }}</label>
                <select name="integrity" class="form-select">
                    <option value="">{{ __('messages.common.all') }}</option>
                    <option value="current" @selected(($filters['integrity'] ?? '') === 'current')>{{ __('messages.evidence.integrity.current') }}</option>
                    <option value="changed" @selected(($filters['integrity'] ?? '') === 'changed')>{{ __('messages.evidence.integrity.changed') }}</option>
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-light" type="submit"><i data-lucide="filter" class="me-1"></i>{{ __('messages.common.filter') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="card aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div class="d-flex gap-3 align-items-center">
            <span class="avatar avatar-sm rounded text-bg-primary"><span class="avatar-title"><i data-lucide="folder-check"></i></span></span>
            <div><h5 class="card-title mb-1">{{ __('messages.evidence.table_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.evidence.copy') }}</p></div>
        </div>
        <span class="badge badge-soft-primary">{{ $evidenceItems->count() }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="evidence" data-aptoria-paging="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                <thead class="thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.evidence.evidence') }}</th>
                        <th data-priority="2">{{ __('messages.evidence.links') }}</th>
                        <th data-priority="3">{{ __('messages.evidence.type') }}</th>
                        <th data-priority="4">{{ __('messages.evidence.repository_state') }}</th>
                        <th data-priority="5">{{ __('messages.evidence.captured_by') }}</th>
                        <th data-priority="6">{{ __('messages.evidence.captured_at') }}</th>
                        <th data-priority="1" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($evidenceItems as $item)
                        <tr>
                            <td>
                                <div class="d-flex gap-2 align-items-start">
                                    <span class="avatar avatar-xs rounded text-bg-{{ $item->type_tone }}"><span class="avatar-title"><i data-lucide="{{ $item->type_icon }}"></i></span></span>
                                    <div>
                                        <a href="{{ route('projects.evidence.show', [$project, $item]) }}" class="fw-medium text-body text-truncate aptoria-endpoint-name-cell d-block">{{ $item->title }}</a>
                                        <small class="text-muted text-truncate d-block">{{ $item->source_label ?: $item->url ?: __('messages.evidence.no_source') }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($item->finding)<a href="{{ route('projects.findings.show', [$project, $item->finding]) }}" class="badge badge-soft-danger"><i data-lucide="bug" class="me-1"></i>{{ __('messages.findings.finding') }}</a>@endif
                                @if($item->endpoint)<span class="badge badge-soft-primary"><i data-lucide="route" class="me-1"></i>{{ $item->endpoint->method }} {{ $item->endpoint->path }}</span>@endif
                                @if($item->testCase)<a href="{{ route('projects.tests.cases.show', [$project, $item->testCase]) }}" class="badge badge-soft-info"><i data-lucide="clipboard-list" class="me-1"></i>{{ __('messages.native_tests.case') }}</a>@endif
                                @unless($item->finding || $item->endpoint || $item->testCase)<span class="text-muted">—</span>@endunless
                            </td>
                            <td><span class="badge badge-soft-{{ $item->type_tone }}"><i data-lucide="{{ $item->type_icon }}" class="me-1"></i>{{ $item->type_label }}</span></td>
                            <td>
                                <span class="badge badge-soft-{{ $item->repository_status_tone }}"><i data-lucide="{{ $item->repository_status_icon }}" class="me-1"></i>{{ $item->repository_status_label }}</span>
                                <span class="badge badge-soft-{{ $item->integrity_status_tone }}"><i data-lucide="fingerprint" class="me-1"></i>{{ $item->integrity_status_label }}</span>
                            </td>
                            <td><small class="text-muted">{{ $item->capturedBy?->name ?? '—' }}</small></td>
                            <td><small class="text-muted">{{ $item->captured_at?->format('Y-m-d H:i') ?? $item->created_at?->format('Y-m-d H:i') }}</small></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" type="button" data-bs-toggle="dropdown"><i data-lucide="ellipsis"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="{{ route('projects.evidence.show', [$project, $item]) }}"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.view') }}</a>
                                        @if($item->repository_status !== \App\Models\FindingEvidence::STATUS_ARCHIVED)
                                            <form method="POST" action="{{ route('projects.evidence.verify', [$project, $item]) }}">@csrf<button class="dropdown-item" type="submit"><i data-lucide="badge-check" class="me-2"></i>{{ __('messages.evidence.verify') }}</button></form>
                                            <form method="POST" action="{{ route('projects.evidence.archive', [$project, $item]) }}" data-aptoria-confirm="{{ __('messages.evidence.confirm_archive') }}">@csrf<button class="dropdown-item text-warning" type="submit"><i data-lucide="archive" class="me-2"></i>{{ __('messages.evidence.archive') }}</button></form>
                                        @else
                                            <form method="POST" action="{{ route('projects.evidence.restore', [$project, $item]) }}">@csrf<button class="dropdown-item" type="submit"><i data-lucide="archive-restore" class="me-2"></i>{{ __('messages.evidence.restore') }}</button></form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">{{ __('messages.evidence.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted">{{ __('messages.evidence.footer') }}</div>
</div>
@endsection
