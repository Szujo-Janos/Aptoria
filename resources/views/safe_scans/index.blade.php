@extends('layouts.app')
@section('title', __('messages.safe_scan.title') . ' · ' . $project->name)
@section('page_title', __('messages.safe_scan.title'))
@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#safeScanRunModal"><i data-lucide="radar" class="me-1"></i>{{ __('messages.safe_scan.run_scan') }}</button>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-h-100 aptoria-project-dashboard-hero">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <span class="avatar avatar-lg rounded text-bg-warning"><span class="avatar-title"><i data-lucide="radar"></i></span></span>
                        <div class="min-w-0">
                            <span class="badge badge-soft-warning badge-label mb-2"><i class="ti ti-point-filled"></i>v0.0.6</span>
                            <h2 class="mb-1 fw-normal">{{ __('messages.safe_scan.heading') }}</h2>
                            <p class="text-muted mb-0">{{ __('messages.safe_scan.copy') }}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('messages.safe_scan.safe_endpoints') }}</div>
                        <h3 class="mb-0 fw-light">{{ $metrics['safe_endpoints'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle d-flex justify-content-between flex-wrap gap-2 text-muted">
                <span>{{ __('messages.workspace.current_project') }}: {{ $project->name }}</span>
                <span>{{ __('messages.safe_scan.last_run') }}: {{ $lastRun?->created_at?->diffForHumans() ?? __('messages.safe_scan.never_run') }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.safe_scan.safety_policy') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <span><span class="d-block text-body">{{ __('messages.project_settings.safe_methods_only') }}</span><small class="text-muted">GET / HEAD</small></span>
                        <span class="badge {{ $settings['safe_methods_only'] ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ $settings['safe_methods_only'] ? 'ON' : 'OFF' }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <span><span class="d-block text-body">{{ __('messages.project_settings.require_confirmation') }}</span><small class="text-muted">{{ __('messages.safe_scan.confirmation_help') }}</small></span>
                        <span class="badge {{ $settings['require_confirmation'] ? 'badge-soft-success' : 'badge-soft-warning' }}">{{ $settings['require_confirmation'] ? 'ON' : 'OFF' }}</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
                        <span><span class="d-block text-body">{{ __('messages.project_settings.allow_private_networks') }}</span><small class="text-muted">{{ __('messages.safe_scan.private_network_help') }}</small></span>
                        <span class="badge {{ $settings['allow_private_networks'] ? 'badge-soft-danger' : 'badge-soft-success' }}">{{ $settings['allow_private_networks'] ? 'ON' : 'OFF' }}</span>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-center text-muted">{{ __('messages.safe_scan.policy_footer') }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center gap-3"><span class="avatar rounded text-bg-primary"><span class="avatar-title"><i data-lucide="plug-connected"></i></span></span><div><span class="text-muted small">{{ __('messages.safe_scan.total_endpoints') }}</span><h4 class="mb-0 fw-light">{{ $metrics['total_endpoints'] }}</h4></div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center gap-3"><span class="avatar rounded text-bg-success"><span class="avatar-title"><i data-lucide="shield-check"></i></span></span><div><span class="text-muted small">{{ __('messages.safe_scan.last_passed') }}</span><h4 class="mb-0 fw-light">{{ $metrics['last_passed'] }}</h4></div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center gap-3"><span class="avatar rounded text-bg-warning"><span class="avatar-title"><i data-lucide="bug"></i></span></span><div><span class="text-muted small">{{ __('messages.safe_scan.last_warnings') }}</span><h4 class="mb-0 fw-light">{{ $metrics['last_warnings'] }}</h4></div></div></div>
    </div>
    <div class="col-md-3">
        <div class="card"><div class="card-body d-flex align-items-center gap-3"><span class="avatar rounded text-bg-secondary"><span class="avatar-title"><i data-lucide="pause-circle"></i></span></span><div><span class="text-muted small">{{ __('messages.safe_scan.last_skipped') }}</span><h4 class="mb-0 fw-light">{{ $metrics['last_skipped'] }}</h4></div></div></div>
    </div>
</div>

<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.safe_scan.runs_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.safe_scan.runs_copy') }}</p>
        </div>
        <div class="card-action">
            <span class="badge badge-soft-primary badge-label">{{ __('messages.safe_scan.runs') }}: {{ $scanRuns->count() }}</span>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#safeScanRunModal"><i data-lucide="play" class="me-1"></i>{{ __('messages.safe_scan.run_scan') }}</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="safe-scans" data-aptoria-paging="true" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
                <thead class="align-middle thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.safe_scan.run') }}</th>
                        <th data-priority="3">{{ __('messages.nav.environments') }}</th>
                        <th data-priority="4">{{ __('messages.nav.auth_profiles') }}</th>
                        <th data-priority="2">{{ __('messages.safe_scan.summary') }}</th>
                        <th data-priority="5">{{ __('messages.safe_scan.duration') }}</th>
                        <th data-priority="6">{{ __('messages.common.updated') }}</th>
                        <th data-priority="1" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($scanRuns as $run)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="avatar avatar-xs rounded text-bg-{{ $run->status_tone }}"><span class="avatar-title"><i data-lucide="radar"></i></span></span>
                                    <div><span class="d-block">#{{ $run->id }}</span><small class="text-muted">{{ $run->status_label }}</small></div>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-{{ $run->environment?->tone ?? 'secondary' }}">{{ $run->environment?->name ?? __('messages.safe_scan.auto_target') }}</span></td>
                            <td><span class="badge badge-soft-{{ $run->authProfile?->tone ?? 'secondary' }}">{{ $run->authProfile?->name ?? __('messages.auth_profiles.no_auth_preview') }}</span></td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <span class="badge badge-soft-success">{{ __('messages.safe_scan.passed') }} {{ $run->summary_value['passed'] ?? 0 }}</span>
                                    <span class="badge badge-soft-warning">{{ __('messages.safe_scan.warning') }} {{ $run->summary_value['warning'] ?? 0 }}</span>
                                    <span class="badge badge-soft-danger">{{ __('messages.safe_scan.failed') }} {{ $run->summary_value['failed'] ?? 0 }}</span>
                                    <span class="badge badge-soft-secondary">{{ __('messages.safe_scan.skipped') }} {{ $run->summary_value['skipped'] ?? 0 }}</span>
                                </div>
                            </td>
                            <td><small class="text-muted">{{ $run->duration_ms ? $run->duration_ms.' ms' : '—' }}</small></td>
                            <td><small class="text-muted">{{ $run->created_at?->diffForHumans() }}</small></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a href="{{ route('projects.safe-scans.show', [$project, $run]) }}" class="dropdown-item"><i data-lucide="eye" class="me-2"></i>{{ __('messages.common.open') }}</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">{{ __('messages.safe_scan.empty_runs') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
        <span>{{ __('messages.safe_scan.table_footer') }}</span>
        <span>{{ __('messages.safe_scan.table_standard') }}</span>
    </div>
</div>

<div class="modal fade" id="safeScanRunModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('projects.safe-scans.store', $project) }}" class="modal-content" data-aptoria-form-scope="safe_scan" data-aptoria-form-plugin data-aptoria-confirm="safe-scan" data-confirm-title="{{ __('messages.safe_scan.confirm_title') }}" data-confirm-text="{{ __('messages.safe_scan.confirm_text') }}" data-confirm-button="{{ __('messages.safe_scan.run_scan') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i data-lucide="radar" class="me-2"></i>{{ __('messages.safe_scan.run_modal_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex gap-2 align-items-start">
                    <i data-lucide="shield-alert" class="mt-1"></i>
                    <div><strong>{{ __('messages.safe_scan.safe_only') }}</strong><br><span class="small">{{ __('messages.safe_scan.safe_only_copy') }}</span></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.nav.environments') }}</label>
                        <select name="environment_id" class="form-select">
                            <option value="">{{ __('messages.safe_scan.use_endpoint_or_default_environment') }}</option>
                            @foreach ($environments as $environment)
                                <option value="{{ $environment->id }}" @selected($environment->is_default)>{{ $environment->name }} · {{ $environment->type_label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('messages.nav.auth_profiles') }}</label>
                        <select name="auth_profile_id" class="form-select">
                            <option value="">{{ __('messages.safe_scan.use_endpoint_or_default_auth') }}</option>
                            @foreach ($authProfiles as $profile)
                                <option value="{{ $profile->id }}" @selected($profile->is_default)>{{ $profile->name }} · {{ $profile->type_label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <label class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="confirm_safe_scan" value="1" required>
                    <span class="form-check-label">{{ __('messages.safe_scan.confirm_checkbox') }}</span>
                </label>
            </div>
            <div class="modal-footer aptoria-card-footer-subtle">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="play" class="me-1"></i>{{ __('messages.safe_scan.run_scan') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
