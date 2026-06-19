@extends('layouts.app')

@section('title', __('messages.nav.dashboard') . ' · ' . $appName)
@section('page_title', __('messages.nav.dashboard'))

@section('page_actions')
    <a href="{{ route('projects.create') }}" class="btn btn-success"><i data-lucide="plus" class="me-1"></i>{{ __('messages.projects.new') }}</a>
@endsection


@section('content')
@php
    $metrics = $workspaceSummary['metrics'] ?? [];
    $progress = $workspaceSummary['progress'] ?? 0;
    $checklist = $workspaceSummary['checklist'] ?? [];
    $defaults = $workspaceSummary['defaults'] ?? [];
@endphp

<div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3">
    <div class="col">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-normal text-uppercase mb-3">{{ __('messages.dashboard.active_projects') }}</h5>
                        <h2 class="fw-light mb-1">{{ $activeProjects }}</h2>
                        <p class="text-muted mb-0">{{ __('messages.workspace.project_foundation') }}</p>
                    </div>
                    <i data-lucide="folder-kanban" class="text-muted fs-42 svg-sw-10"></i>
                </div>
            </div>
            <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.workspace.current_project') }}: {{ $currentProject?->name ?? __('messages.workspace.no_current_project') }}</div>
        </div>
    </div>
    <div class="col">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-normal text-uppercase mb-3">{{ __('messages.dashboard.audit_events') }}</h5>
                        <h2 class="fw-light mb-1">{{ $auditCount }}</h2>
                        <p class="text-muted mb-0">{{ __('messages.workspace.audit_trace_ready') }}</p>
                    </div>
                    <i data-lucide="scroll-text" class="text-muted fs-42 svg-sw-10"></i>
                </div>
            </div>
            <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.workspace.audit_events_short') }}</div>
        </div>
    </div>
    <div class="col">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="min-w-0">
                        <h5 class="fw-normal text-uppercase mb-3">{{ __('messages.workspace.current_project') }}</h5>
                        <h2 class="fw-light mb-1 text-truncate">{{ $currentProject?->name ?? '—' }}</h2>
                        <p class="text-muted mb-0 text-truncate">{{ $defaults['environment']?->name ?? $currentProject?->environment_label ?: __('messages.workspace.no_environment') }}</p>
                    </div>
                    <i data-lucide="folder-open" class="text-muted fs-42 svg-sw-10"></i>
                </div>
            </div>
            <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ $currentProject?->base_url ?: __('messages.workspace.no_base_url') }}</div>
        </div>
    </div>
    <div class="col">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-normal text-uppercase mb-3">{{ __('messages.workspace.workspace_readiness') }}</h5>
                        <h2 class="fw-light mb-1">{{ $progress }}%</h2>
                        <p class="text-muted mb-2">{{ __('messages.workspace.profile_completeness') }}</p>
                    </div>
                    <i data-lucide="shield-chevron" class="text-muted fs-42 svg-sw-10"></i>
                </div>
                <div class="progress progress-lg">
                    <div class="progress-bar" style="width: {{ $progress }}%;" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
            <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.workspace.release_gate_short') }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-xxl-8 col-xl-7">
        <div class="card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">{{ __('messages.workspace.command_center') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.workspace.command_center_copy') }}</p>
                </div>
                <div class="card-action">
                    @if ($currentProject)
                        <a href="{{ route('projects.show', $currentProject) }}" class="btn btn-sm btn-primary"><i data-lucide="arrow-up-right" class="me-1"></i>{{ __('messages.workspace.open_workspace') }}</a>
                    @else
                        <a href="{{ route('projects.create') }}" class="btn btn-sm btn-primary"><i data-lucide="plus" class="me-1"></i>{{ __('messages.projects.new') }}</a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if ($currentProject)
                    <div class="row align-items-center g-3">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-start gap-3">
                                <span class="avatar avatar-lg rounded text-bg-{{ $currentProject->status_tone }}"><span class="avatar-title"><i data-lucide="folder-kanban"></i></span></span>
                                <div class="min-w-0">
                                    <span class="badge badge-soft-{{ $currentProject->status_tone }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $currentProject->status_label }}</span>
                                    <h3 class="mb-1 text-truncate">{{ $currentProject->name }}</h3>
                                    <p class="text-muted mb-0">{{ $currentProject->description ?: __('messages.projects.no_description') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="text-lg-end">
                                <div class="text-muted small">{{ __('messages.projects.base_url') }}</div>
                                <div class="text-body text-truncate">{{ $currentProject->base_url ?: '—' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="progress progress-lg mt-4 mb-2">
                        <div class="progress-bar" style="width: {{ $progress }}%;" role="progressbar"></div>
                    </div>
                    <div class="d-flex justify-content-between small text-muted">
                        <span>{{ __('messages.workspace.profile') }}</span>
                        <span>{{ __('messages.workspace.endpoint_inventory_short') }}</span>
                        <span>{{ __('messages.workspace.evidence_short') }}</span>
                        <span>{{ __('messages.workspace.release_gate_short') }}</span>
                    </div>
                @else
                    <x-no-project-state class="border-0 shadow-none mb-0" />
                @endif
            </div>
            <div class="card-footer text-muted text-center aptoria-card-footer-subtle">
                {{ __('messages.product.positioning') }}
            </div>
        </div>

        <div class="card mt-3 aptoria-table-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('messages.projects.title') }}</h5>
                <div class="card-action">
                    <a href="#!" class="card-action-item" data-action="card-toggle"><i class="ti ti-chevron-up"></i></a>
                    <a href="#!" class="card-action-item" data-action="card-refresh"><i class="ti ti-refresh"></i></a>
                    <a href="{{ route('projects.index') }}" class="btn btn-sm btn-light">{{ __('messages.dashboard.view_all') }} <i class="ti ti-send-2"></i></a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table data-tables="dashboard-projects" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-dashboard-table">
                        <thead class="align-middle thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th data-priority="1">{{ __('messages.projects.name') }}</th>
                                <th data-priority="3">{{ __('messages.projects.base_url') }}</th>
                                <th data-priority="4">{{ __('messages.projects.environment') }}</th>
                                <th data-priority="2">{{ __('messages.projects.status') }}</th>
                                <th class="text-end aptoria-actions-cell no-sort" data-priority="1">{{ __('messages.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($projects as $project)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        <span class="avatar avatar-sm rounded text-bg-light"><span class="avatar-title"><i data-lucide="folder"></i></span></span>
                                        <div class="min-w-0">
                                            <a href="{{ route('projects.show', $project) }}" class="link-reset d-block text-truncate aptoria-project-name-cell">{{ $project->name }}</a>
                                            <small class="text-muted d-block text-truncate">{{ $project->qa_owner ?: __('messages.workspace.no_owner') }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-muted d-inline-block text-truncate aptoria-url-cell">{{ $project->base_url ?: '—' }}</span></td>
                                <td>{{ $project->environment_label ?: '—' }}</td>
                                <td><span class="badge badge-soft-{{ $project->status_tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $project->status_label }}</span></td>
                                <td class="text-end aptoria-actions-cell">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="{{ route('projects.switch', $project) }}" class="dropdown-item"><i data-lucide="folder-open" class="me-2"></i>{{ __('messages.workspace.activate_workspace') }}</a>
                                            <a href="{{ route('projects.show', $project) }}" class="dropdown-item"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.open') }}</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('messages.projects.empty') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xxl-4 col-xl-5">
        <div class="card aptoria-guide-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('messages.projects.workspace_guide') }}</h5>
                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#dashboardWorkspaceGuideModal"><i data-lucide="panel-top-open" class="me-1"></i>{{ __('messages.projects.quick_preview') }}</button>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush aptoria-guide-list">
                    @forelse ($checklist as $item)
                        <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                            <div class="d-flex gap-3 min-w-0">
                                <span class="avatar avatar-xs rounded {{ $item['ready'] ? 'text-bg-success' : 'text-bg-light' }}"><span class="avatar-title"><i data-lucide="{{ $item['ready'] ? 'check' : 'circle' }}"></i></span></span>
                                <div class="min-w-0">
                                    <div class="text-body">{{ $item['label'] }}</div>
                                    <small class="text-muted">{{ $item['hint'] }}</small>
                                </div>
                            </div>
                            <span class="badge {{ $item['ready'] ? 'badge-soft-success' : 'badge-soft-warning' }} fs-xxs">{{ $item['ready'] ? 'OK' : 'Next' }}</span>
                        </div>
                    @empty
                        <div class="list-group-item text-muted">{{ __('messages.projects.workspace_guide_copy') }}</div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.projects.workspace_guide_copy') }}</div>
        </div>

        <div class="card mt-3">
            <div class="card-header border-light justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('messages.dashboard.latest_audit') }}</h5>
                <a href="{{ route('audit.index') }}" class="btn btn-sm btn-light"><i data-lucide="scroll-text" class="me-1"></i>{{ __('messages.nav.audit_log') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="aptoria-timeline-list" data-simplebar style="max-height: 420px;">
                    @forelse ($latestAudit as $log)
                        <div class="aptoria-timeline-item">
                            <span class="avatar avatar-xs rounded text-bg-light"><span class="avatar-title"><i data-lucide="file-delta"></i></span></span>
                            <div class="min-w-0 w-100">
                                <div class="d-flex justify-content-between gap-2">
                                    <span class="text-body text-truncate">{{ $log->summary }}</span>
                                    <small class="text-muted text-nowrap">{{ $log->created_at?->diffForHumans() }}</small>
                                </div>
                                <small class="text-muted">{{ $log->project?->name ?: '—' }} · {{ $log->action }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">{{ __('messages.dashboard.no_audit') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.dashboard.gaps_title') }}</h5></div>
            <div class="list-group list-group-flush">
                <div class="list-group-item d-flex gap-3"><i data-lucide="share-2" class="text-primary"></i><span>{{ __('messages.dashboard.gap_postman') }}</span></div>
                <div class="list-group-item d-flex gap-3"><i data-lucide="ticket" class="text-warning"></i><span>{{ __('messages.dashboard.gap_jira') }}</span></div>
                <div class="list-group-item d-flex gap-3"><i data-lucide="file-code-2" class="text-info"></i><span>{{ __('messages.dashboard.gap_openapi') }}</span></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dashboardWorkspaceGuideModal" tabindex="-1" aria-hidden="true">
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
                    @foreach ($checklist as $item)
                        <div class="col-md-6">
                            <div class="card h-100 mb-0">
                                <div class="card-body d-flex gap-3">
                                    <span class="avatar avatar-md rounded {{ $item['ready'] ? 'text-bg-success' : 'text-bg-light' }}"><span class="avatar-title"><i data-lucide="{{ $item['ready'] ? 'check' : 'circle' }}"></i></span></span>
                                    <div>
                                        <h6 class="mb-1">{{ $item['label'] }}</h6>
                                        <p class="text-muted small mb-0">{{ $item['hint'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                <a href="{{ route('projects.index') }}" class="btn btn-primary"><i data-lucide="folder-kanban" class="me-1"></i>{{ __('messages.nav.projects') }}</a>
            </div>
        </div>
    </div>
</div>
@endsection

