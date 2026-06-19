@extends('layouts.app')
@section('title', __('messages.nav.projects'))
@section('page_title', __('messages.nav.projects'))
@section('page_actions')
    <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#workspaceGuideModal"><i data-lucide="map" class="me-1"></i>{{ __('messages.projects.workspace_guide') }}</button>
    <a href="{{ route('projects.create') }}" class="btn btn-primary"><i data-lucide="plus" class="me-1"></i>{{ __('messages.projects.new') }}</a>
@endsection
@section('content')
<div class="card mb-3 aptoria-panel-card">
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-xl-7">
                <span class="badge badge-soft-primary badge-label mb-2"><i class="ti ti-point-filled"></i>v0.0.3</span>
                <h3 class="mb-2 fw-normal">{{ __('messages.projects.projects_dashboard_title') }}</h3>
                <p class="text-muted mb-0">{{ __('messages.projects.projects_dashboard_copy') }}</p>
            </div>
            <div class="col-xl-5">
                <div class="d-flex flex-wrap justify-content-xl-end gap-2">
                    <span class="aptoria-ui-chip"><i data-lucide="table-2" class="fs-15"></i>{{ __('messages.common.ui_datatables') }}</span>
                    <span class="aptoria-ui-chip"><i data-lucide="bell-ring" class="fs-15"></i>{{ __('messages.common.ui_toast') }}</span>
                    <span class="aptoria-ui-chip"><i data-lucide="message-square-warning" class="fs-15"></i>{{ __('messages.common.ui_sweetalert') }}</span>
                    <span class="aptoria-ui-chip"><i data-lucide="panel-top-open" class="fs-15"></i>{{ __('messages.common.ui_modal') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.projects.ui_components_copy') }}</div>
</div>

<div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 mb-3">
    <div class="col">
        <div class="card card-h-100 aptoria-widget-card"><div class="card-body d-flex justify-content-between align-items-start"><div><h5 class="fw-normal text-uppercase mb-3">{{ __('messages.projects.title') }}</h5><h2 class="fw-light mb-0">{{ $projectCount }}</h2></div><i data-lucide="folder-kanban" class="text-muted fs-42 svg-sw-10"></i></div><div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.projects.filter_all') }}</div></div>
    </div>
    <div class="col">
        <div class="card card-h-100 aptoria-widget-card"><div class="card-body d-flex justify-content-between align-items-start"><div><h5 class="fw-normal text-uppercase mb-3">{{ __('messages.projects.filter_active') }}</h5><h2 class="fw-light mb-0">{{ $activeProjectCount }}</h2></div><i data-lucide="folder-kanban" class="text-muted fs-42 svg-sw-10"></i></div><div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.workspace.open_workspace') }}</div></div>
    </div>
    <div class="col">
        <div class="card card-h-100 aptoria-widget-card"><div class="card-body d-flex justify-content-between align-items-start"><div><h5 class="fw-normal text-uppercase mb-3">{{ __('messages.projects.filter_draft') }}</h5><h2 class="fw-light mb-0">{{ $draftProjectCount }}</h2></div><i data-lucide="file-clock" class="text-muted fs-42 svg-sw-10"></i></div><div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.workspace.profile') }}</div></div>
    </div>
    <div class="col">
        <div class="card card-h-100 aptoria-widget-card"><div class="card-body d-flex justify-content-between align-items-start"><div><h5 class="fw-normal text-uppercase mb-3">{{ __('messages.projects.filter_paused') }}</h5><h2 class="fw-light mb-0">{{ $pausedProjectCount }}</h2></div><i data-lucide="pause-circle" class="text-muted fs-42 svg-sw-10"></i></div><div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.common.actions') }}</div></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">{{ __('messages.projects.quick_quality_path') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.projects.workspace_guide_copy') }}</p>
                </div>
                <span class="badge badge-soft-primary badge-label">{{ __('messages.common.mvp') }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="d-flex gap-3 h-100">
                            <span class="avatar avatar-md rounded text-bg-primary"><span class="avatar-title"><i data-lucide="folder-plus"></i></span></span>
                            <div><h6>{{ __('messages.projects.quick_quality_step_1') }}</h6><div class="progress aptoria-readiness-mini mt-2"><div class="progress-bar" style="width: 33%"></div></div></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-3 h-100">
                            <span class="avatar avatar-md rounded text-bg-success"><span class="avatar-title"><i data-lucide="clipboard-check"></i></span></span>
                            <div><h6>{{ __('messages.projects.quick_quality_step_2') }}</h6><div class="progress aptoria-readiness-mini mt-2"><div class="progress-bar bg-success" style="width: 66%"></div></div></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-3 h-100">
                            <span class="avatar avatar-md rounded text-bg-info"><span class="avatar-title"><i data-lucide="radar"></i></span></span>
                            <div><h6>{{ __('messages.projects.quick_quality_step_3') }}</h6><div class="progress aptoria-readiness-mini mt-2"><div class="progress-bar bg-info" style="width: 100%"></div></div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.projects.workspace_guide_title') }}</div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="alert alert-info alert-dismissible fade show h-100 mb-0" role="alert">
            <div class="d-flex gap-3">
                <span class="avatar avatar-sm rounded text-bg-info"><span class="avatar-title"><i data-lucide="sparkles"></i></span></span>
                <div>
                    <h6 class="alert-heading mb-1">{{ __('messages.projects.ui_components_used') }}</h6>
                    <p class="mb-0 small">{{ __('messages.projects.ui_components_copy') }}</p>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.common.close') }}"></button>
        </div>
    </div>
</div>

<div class="card mb-3 aptoria-panel-card">
    <div class="card-body py-2">
        <ul class="nav nav-pills aptoria-status-filter gap-1">
            <li class="nav-item"><a class="nav-link {{ $statusFilter === null ? 'active' : '' }}" href="{{ route('projects.index') }}">{{ __('messages.projects.filter_all') }} <span class="badge text-bg-light ms-1">{{ $projectCount }}</span></a></li>
            <li class="nav-item"><a class="nav-link {{ $statusFilter === 'active' ? 'active' : '' }}" href="{{ route('projects.index', ['status' => 'active']) }}">{{ __('messages.projects.filter_active') }} <span class="badge text-bg-light ms-1">{{ $activeProjectCount }}</span></a></li>
            <li class="nav-item"><a class="nav-link {{ $statusFilter === 'draft' ? 'active' : '' }}" href="{{ route('projects.index', ['status' => 'draft']) }}">{{ __('messages.projects.filter_draft') }} <span class="badge text-bg-light ms-1">{{ $draftProjectCount }}</span></a></li>
            <li class="nav-item"><a class="nav-link {{ $statusFilter === 'paused' ? 'active' : '' }}" href="{{ route('projects.index', ['status' => 'paused']) }}">{{ __('messages.projects.filter_paused') }} <span class="badge text-bg-light ms-1">{{ $pausedProjectCount }}</span></a></li>
        </ul>
    </div>
</div>

@if ($projects->count())
    <div class="card aptoria-table-card aptoria-panel-card">
        <div class="card-header border-light justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-1">{{ __('messages.projects.project_workspaces') }}</h5>
                <p class="text-muted mb-0 small">{{ __('messages.projects.project_workspaces_copy') }}</p>
            </div>
            <div class="card-action">
                <span class="badge badge-soft-primary badge-label"><i class="ti ti-table me-1"></i>{{ __('messages.common.ui_datatables') }}</span>
                <a href="#!" class="card-action-item" data-action="card-toggle"><i class="ti ti-chevron-up"></i></a>
                <a href="#!" class="card-action-item" data-action="card-refresh"><i class="ti ti-refresh"></i></a>
            </div>
        </div>
        <div class="card-body">
            <table data-tables="projects" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-projects-table">
                <thead class="thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.projects.name') }}</th>
                        <th data-priority="3">{{ __('messages.projects.base_url') }}</th>
                        <th data-priority="4">{{ __('messages.projects.environment') }}</th>
                        <th data-priority="2">{{ __('messages.projects.status') }}</th>
                        <th data-priority="5">{{ __('messages.workspace.workspace_readiness') }}</th>
                        <th data-priority="6">{{ __('messages.workspace.audit_events_short') }}</th>
                        <th class="text-end aptoria-actions-cell no-sort" data-priority="1">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($projects as $project)
                    @php
                        $projectReadinessItems = [filled($project->description), filled($project->base_url), filled($project->environment_label), filled($project->release_goal), ($project->audit_logs_count ?? 0) > 0];
                        $projectReadiness = collect($projectReadinessItems)->filter()->count() * 20;
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2 min-w-0 aptoria-project-cell">
                                <span class="avatar avatar-sm rounded text-bg-light"><span class="avatar-title"><i data-lucide="folder"></i></span></span>
                                <div class="min-w-0">
                                    <a href="{{ route('projects.show', $project) }}" class="link-reset d-block text-truncate aptoria-project-name-cell">{{ $project->name }}</a>
                                    <small class="text-muted d-block text-truncate">{{ $project->qa_owner ?: __('messages.workspace.no_owner') }}</small>
                                    <small class="text-muted d-block text-truncate"><i data-lucide="shield-check" class="me-1"></i>{{ $projectAccess->roleLabel($projectAccess->roleFor(auth()->user(), $project)) }}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="text-muted d-inline-block text-truncate aptoria-url-cell">{{ $project->base_url ?: '—' }}</span></td>
                        <td>{{ $project->environment_label ?: '—' }}</td>
                        <td><span class="badge badge-soft-{{ $project->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $project->status_label }}</span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1 aptoria-readiness-mini" style="min-width: 92px;"><div class="progress-bar" style="width: {{ $projectReadiness }}%"></div></div>
                                <span class="text-muted small">{{ $projectReadiness }}%</span>
                            </div>
                        </td>
                        <td><span class="badge badge-soft-secondary badge-label">{{ $project->audit_logs_count }}</span></td>
                        <td class="text-end aptoria-actions-cell">
                            <div class="dropdown">
                                <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#projectPreview{{ $project->id }}"><i data-lucide="panel-top-open" class="me-2"></i>{{ __('messages.projects.quick_preview') }}</button>
                                    <a href="{{ route('projects.switch', $project) }}" class="dropdown-item"><i data-lucide="folder-open" class="me-2"></i>{{ __('messages.workspace.activate_workspace') }}</a>
                                    <a href="{{ route('projects.show', $project) }}" class="dropdown-item"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.open') }}</a>
                                    @if ($projectAccess->can(auth()->user(), $project, 'members.manage'))
                                        <a href="{{ route('projects.members.index', $project) }}" class="dropdown-item"><i data-lucide="shield-check" class="me-2"></i>{{ __('messages.nav.project_members') }}</a>
                                    @endif
                                    @if ($projectAccess->can(auth()->user(), $project, 'project.manage'))
                                        <a href="{{ route('projects.edit', $project) }}" class="dropdown-item"><i data-lucide="pencil" class="me-2"></i>{{ __('messages.common.edit') }}</a>
                                    @endif
                                    @if ($projectAccess->can(auth()->user(), $project, 'project.manage'))
                                        <div class="dropdown-divider"></div>
                                        <form method="POST" action="{{ route('projects.destroy', $project) }}" data-aptoria-confirm="delete" data-confirm-title="{{ __('messages.projects.delete_title') }}" data-confirm-text="{{ __('messages.projects.delete_text') }}" data-confirm-button="{{ __('messages.projects.delete_confirm_button') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit"><i data-lucide="trash-2" class="me-2"></i>{{ __('messages.common.delete') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer text-muted aptoria-card-footer-subtle">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>{{ __('messages.projects.project_workspaces_copy') }}</span>
                <span>{{ $projects->links() }}</span>
            </div>
        </div>
    </div>

    @foreach ($projects as $project)
        <div class="modal fade" id="projectPreview{{ $project->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">{{ __('messages.projects.preview_title') }}</h5>
                            <p class="text-muted mb-0 small">{{ $project->name }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="avatar avatar-lg rounded text-bg-{{ $project->status_tone }}"><span class="avatar-title"><i data-lucide="folder-kanban"></i></span></span>
                            <div class="min-w-0">
                                <h5 class="mb-1 text-truncate">{{ $project->name }}</h5>
                                <p class="text-muted mb-0">{{ $project->description ?: __('messages.projects.no_description') }}</p>
                            </div>
                        </div>
                        <div class="list-group list-group-flush border rounded">
                            <div class="list-group-item d-flex justify-content-between gap-3"><span>{{ __('messages.projects.base_url') }}</span><strong class="text-truncate">{{ $project->base_url ?: '—' }}</strong></div>
                            <div class="list-group-item d-flex justify-content-between gap-3"><span>{{ __('messages.projects.environment') }}</span><strong>{{ $project->environment_label ?: '—' }}</strong></div>
                            <div class="list-group-item d-flex justify-content-between gap-3"><span>{{ __('messages.projects.qa_owner') }}</span><strong>{{ $project->qa_owner ?: '—' }}</strong></div>
                            <div class="list-group-item d-flex justify-content-between gap-3"><span>{{ __('messages.workspace.audit_events_short') }}</span><strong>{{ $project->audit_logs_count }}</strong></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                        <a href="{{ route('projects.show', $project) }}" class="btn btn-primary"><i data-lucide="eye" class="me-1"></i>{{ __('messages.common.open') }}</a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="card">
        <div class="card-body text-center py-5">
            <span class="avatar avatar-xl rounded text-bg-primary mx-auto mb-3"><span class="avatar-title"><i data-lucide="folder-plus"></i></span></span>
            <h3>{{ __('messages.projects.empty_title') }}</h3>
            <p class="text-muted mx-auto" style="max-width: 620px;">{{ __('messages.projects.empty_copy') }}</p>
            <button type="button" class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#workspaceGuideModal"><i data-lucide="map" class="me-1"></i>{{ __('messages.projects.workspace_guide') }}</button>
            <a href="{{ route('projects.create') }}" class="btn btn-primary"><i data-lucide="plus" class="me-1"></i>{{ __('messages.projects.new') }}</a>
        </div>
    </div>
@endif

<div class="modal fade" id="workspaceGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ __('messages.projects.workspace_guide_title') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.projects.workspace_guide_copy') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4"><div class="card h-100 mb-0"><div class="card-body"><span class="avatar avatar-md rounded text-bg-primary mb-2"><span class="avatar-title"><i data-lucide="folder-kanban"></i></span></span><h6>{{ __('messages.projects.quick_quality_step_1') }}</h6><p class="text-muted small mb-0">{{ __('messages.projects.projects_dashboard_copy') }}</p></div></div></div>
                    <div class="col-md-4"><div class="card h-100 mb-0"><div class="card-body"><span class="avatar avatar-md rounded text-bg-success mb-2"><span class="avatar-title"><i data-lucide="clipboard-check"></i></span></span><h6>{{ __('messages.projects.quick_quality_step_2') }}</h6><p class="text-muted small mb-0">{{ __('messages.workspace.module_grid_copy') }}</p></div></div></div>
                    <div class="col-md-4"><div class="card h-100 mb-0"><div class="card-body"><span class="avatar avatar-md rounded text-bg-info mb-2"><span class="avatar-title"><i data-lucide="radar"></i></span></span><h6>{{ __('messages.projects.quick_quality_step_3') }}</h6><p class="text-muted small mb-0">{{ __('messages.product.positioning') }}</p></div></div></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                <a href="{{ route('projects.create') }}" class="btn btn-primary"><i data-lucide="plus" class="me-1"></i>{{ __('messages.projects.new') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection

