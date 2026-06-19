@extends('layouts.app')
@section('title', __('messages.environments.title') . ' · ' . $project->name)
@section('page_title', __('messages.environments.title'))
@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#environmentCreateModal"><i data-lucide="plus" class="me-1"></i>{{ __('messages.environments.new') }}</button>
@endsection


@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-h-100 aptoria-project-dashboard-hero">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <span class="avatar avatar-lg rounded text-bg-success"><span class="avatar-title"><i data-lucide="globe"></i></span></span>
                        <div class="min-w-0">
                            <span class="badge badge-soft-success badge-label mb-2"><i class="ti ti-point-filled"></i>v0.0.3</span>
                            <h2 class="mb-1 fw-normal">{{ __('messages.environments.heading') }}</h2>
                            <p class="text-muted mb-0">{{ __('messages.environments.copy') }}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('messages.environments.default_environment') }}</div>
                        <h5 class="mb-0">{{ $defaultEnvironment?->name ?? '—' }}</h5>
                        @if ($defaultEnvironment?->is_production)
                            <span class="badge badge-soft-danger mt-2">{{ __('messages.environments.production_warning') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
                <span>{{ __('messages.workspace.current_project') }}: {{ $project->name }}</span>
                <span>{{ __('messages.environments.count') }}: {{ $environments->count() }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.environments.readiness_card') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.environments.has_environment') }}</span><span class="badge {{ $environments->isNotEmpty() ? 'badge-soft-success' : 'badge-soft-warning' }}">{{ $environments->isNotEmpty() ? 'OK' : 'Next' }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.environments.has_default') }}</span><span class="badge {{ $defaultEnvironment ? 'badge-soft-success' : 'badge-soft-warning' }}">{{ $defaultEnvironment ? 'OK' : 'Next' }}</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.environments.production_review') }}</span><span class="badge {{ $environments->where('is_production', true)->isNotEmpty() ? 'badge-soft-danger' : 'badge-soft-secondary' }}">{{ $environments->where('is_production', true)->count() }}</span></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.environments.readiness_help') }}</div>
        </div>
    </div>
</div>

<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.environments.table_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.environments.table_copy') }}</p>
        </div>
        <div class="card-action">
            <span class="badge badge-soft-primary badge-label">{{ __('messages.environments.count') }}: {{ $environments->count() }}</span>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#environmentCreateModal"><i data-lucide="plus" class="me-1"></i>{{ __('messages.environments.new') }}</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="environments" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-environments-table">
                <thead class="align-middle thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.environments.name') }}</th>
                        <th data-priority="4">{{ __('messages.environments.type') }}</th>
                        <th data-priority="2">{{ __('messages.environments.base_url') }}</th>
                        <th data-priority="5">{{ __('messages.environments.flags') }}</th>
                        <th data-priority="6">{{ __('messages.common.updated') }}</th>
                        <th data-priority="3" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($environments as $environment)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <span class="avatar avatar-sm rounded text-bg-{{ $environment->tone }}"><span class="avatar-title"><i data-lucide="server"></i></span></span>
                                    <div class="min-w-0">
                                        <span class="d-block text-truncate">{{ $environment->name }}</span>
                                        <small class="text-muted d-block text-truncate">{{ $environment->notes ?: __('messages.environments.no_notes') }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-{{ $environment->tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $environment->type_label }}</span></td>
                            <td><span class="text-muted d-inline-block text-truncate aptoria-url-cell">{{ $environment->base_url }}</span></td>
                            <td>
                                @if ($environment->is_default)<span class="badge badge-soft-success me-1">{{ __('messages.common.default') }}</span>@endif
                                @if ($environment->is_production)<span class="badge badge-soft-danger">{{ __('messages.environments.production') }}</span>@endif
                                @if (! $environment->is_default && ! $environment->is_production)<span class="text-muted">—</span>@endif
                            </td>
                            <td><small class="text-muted">{{ $environment->updated_at?->diffForHumans() }}</small></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#environmentPreviewModal{{ $environment->id }}"><i data-lucide="eye" class="me-2"></i>{{ __('messages.projects.quick_preview') }}</button>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#environmentEditModal{{ $environment->id }}"><i data-lucide="pencil" class="me-2"></i>{{ __('messages.common.edit') }}</button>
                                        @unless ($environment->is_default)
                                            <form method="POST" action="{{ route('projects.environments.default', [$project, $environment]) }}">@csrf<button class="dropdown-item" type="submit"><i data-lucide="star" class="me-2"></i>{{ __('messages.environments.make_default') }}</button></form>
                                        @endunless
                                        <div class="dropdown-divider"></div>
                                        <form method="POST" action="{{ route('projects.environments.destroy', [$project, $environment]) }}" data-aptoria-confirm="delete" data-confirm-title="{{ __('messages.environments.delete_title') }}" data-confirm-text="{{ __('messages.environments.delete_text') }}" data-confirm-button="{{ __('messages.common.delete') }}">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit"><i data-lucide="trash-2" class="me-2"></i>{{ __('messages.common.delete') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">{{ __('messages.environments.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.environments.footer') }}</div>
</div>

@include('environments.partials.modals')
@endsection

