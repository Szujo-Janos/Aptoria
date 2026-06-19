@extends('layouts.app')
@section('title', __('messages.auth_profiles.title') . ' · ' . $project->name)
@section('page_title', __('messages.auth_profiles.title'))
@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.common.back') }}</a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#authProfileCreateModal"><i data-lucide="plus" class="me-1"></i>{{ __('messages.auth_profiles.new') }}</button>
@endsection


@section('content')
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card card-h-100 aptoria-project-dashboard-hero">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
                    <div class="d-flex align-items-start gap-3 min-w-0">
                        <span class="avatar avatar-lg rounded text-bg-warning"><span class="avatar-title"><i data-lucide="key-round"></i></span></span>
                        <div class="min-w-0">
                            <span class="badge badge-soft-warning badge-label mb-2"><i class="ti ti-point-filled"></i>v0.0.3</span>
                            <h2 class="mb-1 fw-normal">{{ __('messages.auth_profiles.heading') }}</h2>
                            <p class="text-muted mb-0">{{ __('messages.auth_profiles.copy') }}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted small">{{ __('messages.auth_profiles.default_auth_profile') }}</div>
                        <h5 class="mb-0">{{ $defaultAuthProfile?->name ?? __('messages.auth_profiles.no_default') }}</h5>
                        <small class="text-muted">{{ $defaultAuthProfile?->masked_preview ?? __('messages.auth_profiles.optional_for_public_apis') }}</small>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted d-flex justify-content-between flex-wrap gap-2">
                <span>{{ __('messages.workspace.current_project') }}: {{ $project->name }}</span>
                <span>{{ __('messages.auth_profiles.count') }}: {{ $authProfiles->count() }}</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">{{ __('messages.auth_profiles.security_card') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.auth_profiles.secrets_masked') }}</span><span class="badge badge-soft-success">OK</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.auth_profiles.encrypted_storage') }}</span><span class="badge badge-soft-success">OK</span></div>
                    <div class="list-group-item d-flex justify-content-between align-items-center"><span>{{ __('messages.auth_profiles.has_default') }}</span><span class="badge {{ $defaultAuthProfile ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ $defaultAuthProfile ? 'OK' : __('messages.common.optional') }}</span></div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.auth_profiles.security_help') }}</div>
        </div>
    </div>
</div>


<div class="row g-3 mt-1">
    <div class="col-xl-7">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-header border-light justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-1">{{ __('messages.auth_profiles.test_title') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.auth_profiles.test_copy') }}</p>
                </div>
                <span class="badge badge-soft-primary badge-label"><i class="ti ti-point-filled"></i>{{ __('messages.common.live') }}</span>
            </div>
            <form method="POST" action="{{ route('projects.auth-profiles.test', $project) }}" data-aptoria-form-scope="auth_profile_test" data-aptoria-form-plugin data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.auth_profiles.test_confirm_title') }}" data-confirm-text="{{ __('messages.auth_profiles.test_confirm_text') }}" data-confirm-button="{{ __('messages.auth_profiles.run_test') }}">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.environments.title') }}</label>
                            <select name="environment_id" class="form-select">
                                <option value="">{{ __('messages.auth_profiles.project_base_url') }}</option>
                                @foreach ($environments as $environment)
                                    <option value="{{ $environment->id }}" @selected($defaultEnvironment?->id === $environment->id)>{{ $environment->name }} · {{ $environment->base_url }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('messages.auth_profiles.test_environment_help') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.auth_profiles.title') }}</label>
                            <select name="auth_profile_id" class="form-select">
                                <option value="">{{ __('messages.auth_profiles.no_auth_preview') }}</option>
                                @foreach ($authProfiles as $profile)
                                    <option value="{{ $profile->id }}" @selected($defaultAuthProfile?->id === $profile->id)>{{ $profile->name }} · {{ $profile->type_label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('messages.auth_profiles.test_profile_help') }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('messages.endpoints.method') }}</label>
                            <select name="method" class="form-select" required>
                                <option value="GET">GET</option>
                                <option value="HEAD">HEAD</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.auth_profiles.test_path') }}</label>
                            <input type="text" name="test_path" class="form-control" placeholder="{{ __('messages.auth_profiles.test_path_placeholder') }}" value="/health" required>
                            <div class="form-text">{{ __('messages.auth_profiles.test_path_help') }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('messages.auth_profiles.expected_status') }}</label>
                            <input type="number" name="expected_status" class="form-control" min="100" max="599" placeholder="200">
                        </div>
                    </div>
                </div>
                <div class="card-footer aptoria-card-footer-subtle d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="text-muted small">{{ __('messages.auth_profiles.test_safety_note') }}</span>
                    <button class="btn btn-primary" type="submit"><i data-lucide="test-tube" class="me-1"></i>{{ __('messages.auth_profiles.run_test') }}</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100 aptoria-panel-card">
            <div class="card-header border-light">
                <h5 class="card-title mb-0">{{ __('messages.auth_profiles.test_result_title') }}</h5>
            </div>
            <div class="card-body">
                @if ($authTestResult)
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <span class="avatar avatar-lg rounded text-bg-{{ $authTestResult['tone'] ?? 'secondary' }}"><span class="avatar-title"><i data-lucide="test-tube"></i></span></span>
                        <div>
                            <span class="badge badge-soft-{{ $authTestResult['tone'] ?? 'secondary' }} badge-label mb-2"><i class="ti ti-point-filled"></i>{{ strtoupper($authTestResult['state'] ?? 'review') }}</span>
                            <h5 class="mb-1 fw-normal">{{ $authTestResult['message'] ?? __('messages.auth_profiles.test_review') }}</h5>
                            <p class="text-muted mb-0 small">{{ $authTestResult['checked_at'] ?? '' }}</p>
                        </div>
                    </div>
                    <div class="list-group list-group-flush mb-3">
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.common.url') }}</span><code class="text-break text-end">{{ $authTestResult['url'] ?? '—' }}</code></div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.endpoints.method') }}</span><strong>{{ $authTestResult['method'] ?? '—' }}</strong></div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.common.status') }}</span><strong>{{ $authTestResult['status_code'] ?? '—' }}@if(!empty($authTestResult['expected_status'])) / {{ $authTestResult['expected_status'] }} @endif</strong></div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.safe_scan.response_time') }}</span><strong>{{ $authTestResult['response_time_ms'] ?? '—' }} {{ __('messages.common.milliseconds') }}</strong></div>
                        <div class="list-group-item px-0 d-flex justify-content-between gap-3"><span class="text-muted">{{ __('messages.safe_scan.content_type') }}</span><span class="text-end">{{ $authTestResult['content_type'] ?? '—' }}</span></div>
                    </div>
                    @if (!empty($authTestResult['body_preview']))
                        <div class="bg-light border rounded p-2 small aptoria-result-preview"><pre class="mb-0 text-wrap">{{ $authTestResult['body_preview'] }}</pre></div>
                    @endif
                @else
                    <div class="text-center text-muted py-4">
                        <span class="avatar avatar-lg rounded bg-light text-muted mb-3"><span class="avatar-title"><i data-lucide="test-tube"></i></span></span>
                        <h6 class="fw-normal">{{ __('messages.auth_profiles.no_test_yet') }}</h6>
                        <p class="small mb-0">{{ __('messages.auth_profiles.no_test_yet_copy') }}</p>
                    </div>
                @endif
            </div>
            <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.auth_profiles.test_footer') }}</div>
        </div>
    </div>
</div>

<div class="card mt-3 aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1">{{ __('messages.auth_profiles.table_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.auth_profiles.table_copy') }}</p>
        </div>
        <div class="card-action">
            <span class="badge badge-soft-primary badge-label">{{ __('messages.auth_profiles.count') }}: {{ $authProfiles->count() }}</span>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#authProfileCreateModal"><i data-lucide="plus" class="me-1"></i>{{ __('messages.auth_profiles.new') }}</button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table data-tables="auth-profiles" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table aptoria-auth-profiles-table">
                <thead class="align-middle thead-sm text-uppercase fs-xxs">
                    <tr>
                        <th data-priority="1">{{ __('messages.auth_profiles.name') }}</th>
                        <th data-priority="2">{{ __('messages.auth_profiles.type') }}</th>
                        <th data-priority="4">{{ __('messages.auth_profiles.masked_preview') }}</th>
                        <th data-priority="5">{{ __('messages.auth_profiles.flags') }}</th>
                        <th data-priority="6">{{ __('messages.common.updated') }}</th>
                        <th data-priority="3" class="text-end aptoria-actions-cell">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($authProfiles as $profile)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2 min-w-0">
                                    <span class="avatar avatar-sm rounded text-bg-{{ $profile->tone }}"><span class="avatar-title"><i data-lucide="key-round"></i></span></span>
                                    <div class="min-w-0">
                                        <span class="d-block text-truncate">{{ $profile->name }}</span>
                                        <small class="text-muted d-block text-truncate">{{ $profile->notes ?: __('messages.auth_profiles.no_notes') }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-soft-{{ $profile->tone }} badge-label"><i class="ti ti-point-filled"></i>{{ $profile->type_label }}</span></td>
                            <td><code class="small">{{ $profile->masked_preview }}</code></td>
                            <td>
                                @if ($profile->is_default)<span class="badge badge-soft-success me-1">{{ __('messages.common.default') }}</span>@endif
                                @if ($profile->secret_needs_rotation)
                                    <span class="badge badge-soft-warning">{{ __('messages.auth_profiles.secret_needs_rotation') }}</span>
                                @else
                                    <span class="badge badge-soft-secondary">{{ __('messages.auth_profiles.secure') }}</span>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $profile->updated_at?->diffForHumans() }}</small></td>
                            <td class="text-end aptoria-actions-cell">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#authProfilePreviewModal{{ $profile->id }}"><i data-lucide="eye" class="me-2"></i>{{ __('messages.projects.quick_preview') }}</button>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#authProfileEditModal{{ $profile->id }}"><i data-lucide="pencil" class="me-2"></i>{{ __('messages.common.edit') }}</button>
                                        @unless ($profile->is_default)
                                            <form method="POST" action="{{ route('projects.auth-profiles.default', [$project, $profile]) }}">@csrf<button class="dropdown-item" type="submit"><i data-lucide="star" class="me-2"></i>{{ __('messages.auth_profiles.make_default') }}</button></form>
                                        @endunless
                                        <div class="dropdown-divider"></div>
                                        <form method="POST" action="{{ route('projects.auth-profiles.destroy', [$project, $profile]) }}" data-aptoria-confirm="delete" data-confirm-title="{{ __('messages.auth_profiles.delete_title') }}" data-confirm-text="{{ __('messages.auth_profiles.delete_text') }}" data-confirm-button="{{ __('messages.common.delete') }}">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger" type="submit"><i data-lucide="trash-2" class="me-2"></i>{{ __('messages.common.delete') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">{{ __('messages.auth_profiles.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer aptoria-card-footer-subtle text-muted text-center">{{ __('messages.auth_profiles.footer') }}</div>
</div>

@include('auth_profiles.partials.modals')
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function refreshAuthFields(scope) {
                var select = scope.querySelector('.aptoria-auth-type-select');
                if (!select) { return; }
                var type = select.value;
                scope.querySelectorAll('.aptoria-auth-fields').forEach(function (field) { field.classList.add('d-none'); });
                scope.querySelectorAll('.aptoria-auth-' + type).forEach(function (field) { field.classList.remove('d-none'); });
            }

            document.querySelectorAll('.aptoria-auth-profile-form').forEach(function (form) {
                refreshAuthFields(form);
                var select = form.querySelector('.aptoria-auth-type-select');
                if (select) { select.addEventListener('change', function () { refreshAuthFields(form); }); }
            });
        });
    </script>
@endpush
