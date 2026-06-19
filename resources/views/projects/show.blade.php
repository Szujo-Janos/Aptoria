@extends('layouts.app')
@section('title', $project->name)
@section('page_title', $project->name)
@section('page_actions')
    <a href="{{ route('projects.switch', $project) }}" class="btn btn-success"><i data-lucide="folder-open" class="me-1"></i>{{ __('messages.workspace.activate_workspace') }}</a>
    @if ($canManageMembers ?? false)
        <a href="{{ route('projects.members.index', $project) }}" class="btn btn-light"><i data-lucide="shield-check" class="me-1"></i>{{ __('messages.nav.project_members') }}</a>
    @endif
    @if ($canManageProject ?? false)
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-light"><i data-lucide="pencil" class="me-1"></i>{{ __('messages.common.edit') }}</a>
    @endif
@endsection
@section('content')
@php
    $metrics = $workspaceSummary['metrics'];
    $progress = $workspaceSummary['progress'];
    $checklist = $workspaceSummary['checklist'];
    $modules = $workspaceSummary['modules'];
    $latestAudit = $workspaceSummary['latest_audit'];
    $defaults = $workspaceSummary['defaults'] ?? [];
@endphp
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                    <div class="min-w-0">
                        <span class="badge badge-soft-{{ $project->status_tone }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ $project->status_label }}</span>
                        <h2 class="mb-2 fw-normal text-truncate">{{ $project->name }}</h2>
                        <p class="text-muted mb-0">{{ $project->description ?: __('messages.projects.no_description') }}</p>
                    </div>
                    <div class="aptoria-project-score text-center">
                        <div class="aptoria-score-ring" style="--aptoria-score: {{ $progress }}%;">{{ $progress }}%</div>
                        <small class="text-muted">{{ __('messages.workspace.workspace_readiness') }}</small>
                    </div>
                </div>
                <div class="progress progress-lg mt-4 mb-2"><div class="progress-bar" style="width: {{ $progress }}%;"></div></div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>{{ __('messages.workspace.profile') }}</span><span>{{ __('messages.nav.environments') }}</span><span>{{ __('messages.nav.auth_profiles') }}</span><span>{{ __('messages.workspace.release_gate_short') }}</span>
                </div>
            </div>
            <div class="card-footer text-muted aptoria-card-footer-subtle">
                <div class="d-flex justify-content-between flex-wrap gap-2">
                    <span>{{ __('messages.projects.base_url') }}: {{ $project->base_url ?: '—' }}</span>
                    <span>{{ __('messages.projects.environment') }}: {{ $defaults['environment']?->name ?? $project->environment_label ?: '—' }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.workspace.project_context') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.projects.base_url') }}</span><strong class="text-truncate">{{ $project->base_url ?: '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.nav.environments') }}</span><strong>{{ $metrics['environments'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.nav.auth_profiles') }}</span><strong>{{ $metrics['auth_profiles'] ?? 0 }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.environments.default_environment') }}</span><strong>{{ $defaults['environment']?->name ?? '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.projects.qa_owner') }}</span><strong>{{ $project->qa_owner ?: '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.workspace.owner') }}</span><strong>{{ $project->owner?->name ?: '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.project_members.your_role') }}</span><strong>{{ $currentProjectRole ?? '—' }}</strong></div>
                    <div class="list-group-item d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.project_members.members') }}</span><strong>{{ $project->memberships->count() }}</strong></div>
                </div>
            </div>
            <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.workspace.project_context') }}</div>
        </div>
    </div>
</div>

<div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 mt-1">
    @foreach ([['endpoints','route','primary',__('messages.workspace.metric_endpoints')], ['environments','server','success',__('messages.workspace.metric_environments')], ['auth_profiles','key-round','warning',__('messages.workspace.metric_auth_profiles')], ['audit_events','clipboard-list','info',__('messages.workspace.metric_audit_events')]] as [$key, $icon, $tone, $label])
        <div class="col">
            <div class="card card-h-100 aptoria-widget-card"><div class="card-body d-flex justify-content-between align-items-start"><div><h5 class="fw-normal text-uppercase mb-3">{{ $label }}</h5><h2 class="fw-light mb-0">{{ $metrics[$key] ?? 0 }}</h2></div><i data-lucide="{{ $icon }}" class="text-muted fs-42 svg-sw-10"></i></div><div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.workspace.workspace_layer') }}</div></div>
        </div>
    @endforeach
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-8">
        <div class="card aptoria-table-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div><h5 class="card-title mb-1">{{ __('messages.workspace.module_grid') }}</h5><p class="text-muted mb-0 small">{{ __('messages.workspace.module_grid_copy') }}</p></div>
                <span class="badge badge-soft-primary badge-label">v0.0.3</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table data-tables="project-modules" data-aptoria-search="false" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-module-table">
                        <thead class="align-middle thead-sm text-uppercase fs-xxs">
                            <tr>
                                <th>{{ __('messages.workspace.module_stack') }}</th>
                                <th>{{ __('messages.projects.status') }}</th>
                                <th>{{ __('messages.workspace.audit_events_short') }}</th>
                                <th class="text-end aptoria-actions-cell no-sort">{{ __('messages.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($modules as $module)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <span class="avatar avatar-sm rounded text-bg-{{ $module['tone'] }}"><span class="avatar-title"><i data-lucide="{{ $module['icon'] }}"></i></span></span>
                                            <div class="min-w-0">
                                                <a href="{{ $module['url'] ?? route('projects.modules.show', [$project, $module['slug']]) }}" class="link-reset d-block text-truncate">{{ $module['title'] }}</a>
                                                <small class="text-muted d-block text-truncate">{{ $module['description'] }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-soft-{{ $module['tone'] }} badge-label"><i class="ti ti-point-filled"></i>{{ $module['phase'] }}</span></td>
                                    <td><span class="badge badge-soft-secondary badge-label">0</span></td>
                                    <td class="text-end"><a href="{{ $module['url'] ?? route('projects.modules.show', [$project, $module['slug']]) }}" class="btn btn-light btn-icon btn-sm rounded-circle"><i class="ti ti-arrow-up-right"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.workspace.module_grid_copy') }}</div>
        </div>

        <div class="card mt-3">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.workspace.release_goal_card') }}</h5></div>
            <div class="card-body"><p class="text-muted mb-0">{{ $project->release_goal ?: __('messages.workspace.no_release_goal') }}</p></div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card aptoria-guide-card aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ __('messages.projects.workspace_guide') }}</h5>
                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#projectWorkspaceGuideModal"><i data-lucide="panel-top-open" class="me-1"></i>{{ __('messages.projects.quick_preview') }}</button>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush aptoria-guide-list">
                    @foreach ($checklist as $item)
                        <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                            <div class="d-flex gap-3 min-w-0">
                                <span class="avatar avatar-xs rounded {{ $item['ready'] ? 'text-bg-success' : 'text-bg-light' }}"><span class="avatar-title"><i data-lucide="{{ $item['ready'] ? 'check' : 'circle' }}"></i></span></span>
                                <div class="min-w-0"><div class="text-body">{{ $item['label'] }}</div><small class="text-muted">{{ $item['hint'] }}</small></div>
                            </div>
                            <span class="badge {{ $item['ready'] ? 'badge-soft-success' : 'badge-soft-warning' }} fs-xxs">{{ $item['ready'] ? 'OK' : 'Next' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.projects.workspace_guide_copy') }}</div>
        </div>

        <div class="card mt-3">
            <div class="card-header border-light justify-content-between align-items-center"><h5 class="card-title mb-0">{{ __('messages.dashboard.latest_audit') }}</h5><a href="{{ route('audit.index') }}" class="btn btn-sm btn-light">{{ __('messages.dashboard.view_all') }}</a></div>
            <div class="card-body p-0">
                @forelse ($latestAudit as $log)
                    <div class="aptoria-timeline-item"><span class="avatar avatar-xs rounded text-bg-light"><span class="avatar-title"><i data-lucide="file-delta"></i></span></span><div class="min-w-0"><div class="text-body">{{ $log->summary }}</div><small class="text-muted">{{ $log->created_at?->diffForHumans() }} · {{ $log->action }}</small></div></div>
                @empty
                    <div class="text-center text-muted py-4">{{ __('messages.dashboard.no_audit') }}</div>
                @endforelse
            </div>
        </div>

        @if ($canManageProject ?? false)
            <form method="POST" action="{{ route('projects.destroy', $project) }}" class="mt-3" data-aptoria-confirm="delete" data-confirm-title="{{ __('messages.projects.delete_title') }}" data-confirm-text="{{ __('messages.projects.delete_text') }}" data-confirm-button="{{ __('messages.projects.delete_confirm_button') }}">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger w-100" type="submit"><i data-lucide="trash-2" class="me-1"></i>{{ __('messages.projects.delete_workspace') }}</button>
            </form>
        @endif
    </div>
</div>

<div class="modal fade" id="projectWorkspaceGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><div><h5 class="modal-title">{{ __('messages.projects.workspace_guide_title') }}</h5><p class="text-muted mb-0 small">{{ __('messages.projects.workspace_guide_copy') }}</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button></div><div class="modal-body"><div class="row g-3">@foreach ($checklist as $item)<div class="col-md-6"><div class="card h-100 mb-0"><div class="card-body d-flex gap-3"><span class="avatar avatar-md rounded {{ $item['ready'] ? 'text-bg-success' : 'text-bg-light' }}"><span class="avatar-title"><i data-lucide="{{ $item['ready'] ? 'check' : 'circle' }}"></i></span></span><div><h6 class="mb-1">{{ $item['label'] }}</h6><p class="text-muted small mb-0">{{ $item['hint'] }}</p></div></div></div></div>@endforeach</div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>@if ($canManageProject ?? false)<a href="{{ route('projects.edit', $project) }}" class="btn btn-primary"><i data-lucide="pencil" class="me-1"></i>{{ __('messages.common.edit') }}</a>@endif</div></div></div>
</div>
@endsection
