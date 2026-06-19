@extends('layouts.app')

@section('title', __('messages.profile.title'))
@section('page_title', __('messages.profile.title'))

@section('page_actions')
    <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm">
        <i class="ti ti-arrow-left me-1"></i>{{ __('messages.profile.back_to_dashboard') }}
    </a>
@endsection

@section('content')
@if ($user->password_change_required)
    <div class="alert alert-warning d-flex align-items-start gap-2 mt-3" role="alert">
        <i class="ti ti-alert-triangle fs-3 flex-shrink-0"></i>
        <div>
            <h5 class="alert-heading mb-1">{{ __('messages.profile.password_required_title') }}</h5>
            <p class="mb-0">{{ __('messages.profile.password_required_copy') }}</p>
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-12">
        <div class="card aptoria-profile-summary-card">
            <div class="card-body">
                <div class="row align-items-center g-4">
                    <div class="col-xl-7">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-xl rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center fs-2 fw-semibold">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                    <h3 class="mb-0 text-truncate">{{ $user->name }}</h3>
                                    <span class="badge badge-soft-primary badge-label">{{ $user->role_label }}</span>
                                    <span class="badge badge-soft-success badge-label">{{ __('messages.profile.active') }}</span>
                                </div>
                                <p class="text-muted mb-2">{{ $user->email }}</p>
                                <div class="d-flex flex-wrap gap-2 small text-muted">
                                    <span><i class="ti ti-language me-1"></i>{{ strtoupper($user->locale) }}</span>
                                    <span><i class="ti ti-clock me-1"></i>{{ $user->timezone }}</span>
                                    <span><i class="ti ti-login me-1"></i>{{ __('messages.profile.last_login') }}: {{ $user->last_login_at?->diffForHumans() ?? __('messages.profile.never') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-5">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small">{{ __('messages.profile.identity_completeness') }}</span>
                                        <span class="fw-semibold">{{ $identityCompleteness }}%</span>
                                    </div>
                                    <div class="progress progress-sm">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $identityCompleteness }}%" aria-valuenow="{{ $identityCompleteness }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted small mb-2">{{ __('messages.profile.password_status') }}</div>
                                    @if ($user->password_change_required)
                                        <span class="badge badge-soft-warning badge-label">{{ __('messages.profile.update_required') }}</span>
                                    @else
                                        <span class="badge badge-soft-success badge-label">{{ __('messages.profile.password_ok') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer aptoria-card-footer-subtle d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="small text-muted">{{ __('messages.profile.summary_footer') }}</span>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#passwordModal">
                    <i class="ti ti-lock me-1"></i>{{ __('messages.profile.change_password') }}
                </button>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4 aptoria-profile-wizard-card">
    <div class="card-header">
        <div class="flex-grow-1">
            <h5 class="card-title mb-0">{{ __('messages.profile.wizard_title') }}</h5>
            <p class="text-muted small mb-0">{{ __('messages.profile.wizard_copy') }}</p>
        </div>
        <span class="badge badge-soft-success badge-label fs-xxs py-1">{{ __('messages.profile.vertical_wizard') }}</span>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('profile.update') }}" data-wizard-validation data-aptoria-form-scope="profile" data-aptoria-form-plugin>
            @csrf
            @method('PUT')

            <div class="ins-wizard" data-wizard data-wizard-animation>
                <div class="progress progress-sm mb-4">
                    <div class="progress-bar bg-primary" data-wizard-progress role="progressbar" style="width: 0%" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-4 col-xl-3">
                        <ul class="nav flex-column wizard-bordered wizard-tabs nav-pills" data-wizard-nav role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#profileStepAccount" role="tab">
                                    <span class="d-flex align-items-center">
                                        <i class="ti ti-user-circle fs-32"></i>
                                        <span class="flex-grow-1 ms-2 text-truncate">
                                            <span class="mb-0 lh-base d-block fw-semibold text-body fs-base">{{ __('messages.profile.step_account') }}</span>
                                            <span class="fs-xxs mb-0">{{ __('messages.profile.step_account_hint') }}</span>
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#profileStepSecurity" role="tab">
                                    <span class="d-flex align-items-center">
                                        <i class="ti ti-shield-lock fs-32"></i>
                                        <span class="flex-grow-1 ms-2 text-truncate">
                                            <span class="mb-0 lh-base d-block fw-semibold text-body fs-base">{{ __('messages.profile.step_security') }}</span>
                                            <span class="fs-xxs mb-0">{{ __('messages.profile.step_security_hint') }}</span>
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#profileStepReport" role="tab">
                                    <span class="d-flex align-items-center">
                                        <i class="ti ti-file-certificate fs-32"></i>
                                        <span class="flex-grow-1 ms-2 text-truncate">
                                            <span class="mb-0 lh-base d-block fw-semibold text-body fs-base">{{ __('messages.profile.step_report') }}</span>
                                            <span class="fs-xxs mb-0">{{ __('messages.profile.step_report_hint') }}</span>
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#profileStepFinish" role="tab">
                                    <span class="d-flex align-items-center">
                                        <i class="ti ti-activity fs-32"></i>
                                        <span class="flex-grow-1 ms-2 text-truncate">
                                            <span class="mb-0 lh-base d-block fw-semibold text-body fs-base">{{ __('messages.profile.step_finish') }}</span>
                                            <span class="fs-xxs mb-0">{{ __('messages.profile.step_finish_hint') }}</span>
                                        </span>
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="col-lg-8 col-xl-9">
                        <div class="tab-content border border-dashed rounded p-4" data-wizard-content>
                            <div class="tab-pane fade show active" id="profileStepAccount" role="tabpanel">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <h5 class="mb-1">{{ __('messages.profile.account_settings') }}</h5>
                                        <p class="text-muted mb-0 small">{{ __('messages.profile.account_settings_copy') }}</p>
                                    </div>
                                    <span class="badge badge-soft-primary badge-label">{{ __('messages.profile.required_step') }}</span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-xl-6">
                                        <label class="form-label" for="profileName">{{ __('messages.profile.name') }}</label>
                                        <input id="profileName" type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label" for="profileEmail">{{ __('messages.profile.email') }}</label>
                                        <input id="profileEmail" type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label" for="profileLocale">{{ __('messages.profile.language') }}</label>
                                        <select id="profileLocale" name="locale" class="form-select @error('locale') is-invalid @enderror" required>
                                            @foreach ($locales as $value => $label)
                                                <option value="{{ $value }}" @selected(old('locale', $user->locale) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('locale')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-xl-6">
                                        <label class="form-label" for="profileTimezone">{{ __('messages.profile.timezone') }}</label>
                                        <select id="profileTimezone" name="timezone" class="form-select @error('timezone') is-invalid @enderror" required>
                                            @foreach ($timezones as $timezone)
                                                <option value="{{ $timezone }}" @selected(old('timezone', $user->timezone) === $timezone)>{{ $timezone }}</option>
                                            @endforeach
                                        </select>
                                        @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <button type="button" class="btn btn-primary" data-wizard-next>{{ __('messages.profile.next_security') }} <i class="ti ti-arrow-right ms-1"></i></button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="profileStepSecurity" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-xl-7">
                                        <h5 class="mb-1">{{ __('messages.profile.security_panel') }}</h5>
                                        <p class="text-muted small">{{ __('messages.profile.security_copy') }}</p>

                                        <div class="list-group list-group-flush border rounded">
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="ti ti-key me-2 text-muted"></i>{{ __('messages.profile.password_status') }}</span>
                                                @if ($user->password_change_required)
                                                    <span class="badge badge-soft-warning badge-label">{{ __('messages.profile.update_required') }}</span>
                                                @else
                                                    <span class="badge badge-soft-success badge-label">{{ __('messages.profile.password_ok') }}</span>
                                                @endif
                                            </div>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="ti ti-login me-2 text-muted"></i>{{ __('messages.profile.first_login') }}</span>
                                                <span class="text-muted small">{{ $user->first_login_at?->format('Y-m-d H:i') ?? __('messages.profile.never') }}</span>
                                            </div>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <span><i class="ti ti-clock me-2 text-muted"></i>{{ __('messages.profile.last_login') }}</span>
                                                <span class="text-muted small">{{ $user->last_login_at?->format('Y-m-d H:i') ?? __('messages.profile.never') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-5">
                                        <div class="card border mb-0 h-100 shadow-none">
                                            <div class="card-body d-flex flex-column">
                                                <div class="avatar-md rounded bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center mb-3">
                                                    <i class="ti ti-shield-lock fs-3"></i>
                                                </div>
                                                <h5>{{ __('messages.profile.password_box_title') }}</h5>
                                                <p class="text-muted small flex-grow-1">{{ __('messages.profile.password_box_copy') }}</p>
                                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#passwordModal">
                                                    <i class="ti ti-lock me-1"></i>{{ __('messages.profile.change_password') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-light" data-wizard-prev><i class="ti ti-arrow-left me-1"></i>{{ __('messages.common.back') }}</button>
                                    <button type="button" class="btn btn-primary" data-wizard-next>{{ __('messages.profile.next_report') }} <i class="ti ti-arrow-right ms-1"></i></button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="profileStepReport" role="tabpanel">
                                <div class="row g-4">
                                    <div class="col-xl-7">
                                        <h5 class="mb-1">{{ __('messages.profile.report_identity') }}</h5>
                                        <p class="text-muted small">{{ __('messages.profile.report_identity_copy') }}</p>

                                        <div class="row g-3">
                                            <div class="col-xl-6">
                                                <label class="form-label" for="reportOrganization">{{ __('messages.profile.report_organization') }}</label>
                                                <input id="reportOrganization" type="text" name="report_organization" value="{{ old('report_organization', $user->report_organization) }}" class="form-control @error('report_organization') is-invalid @enderror">
                                                @error('report_organization')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-label" for="reportPreparedBy">{{ __('messages.profile.report_prepared_by') }}</label>
                                                <input id="reportPreparedBy" type="text" name="report_prepared_by" value="{{ old('report_prepared_by', $user->report_prepared_by ?: $user->name) }}" class="form-control @error('report_prepared_by') is-invalid @enderror">
                                                @error('report_prepared_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-label" for="reportRoleTitle">{{ __('messages.profile.report_role_title') }}</label>
                                                <input id="reportRoleTitle" type="text" name="report_role_title" value="{{ old('report_role_title', $user->report_role_title) }}" class="form-control @error('report_role_title') is-invalid @enderror">
                                                @error('report_role_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-xl-6">
                                                <label class="form-label" for="reportConfidentiality">{{ __('messages.profile.report_confidentiality_label') }}</label>
                                                <input id="reportConfidentiality" type="text" name="report_confidentiality_label" value="{{ old('report_confidentiality_label', $user->report_confidentiality_label ?: __('messages.profile.default_confidentiality')) }}" class="form-control @error('report_confidentiality_label') is-invalid @enderror">
                                                @error('report_confidentiality_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="reportDisclaimer">{{ __('messages.profile.report_disclaimer') }}</label>
                                                <textarea id="reportDisclaimer" name="report_disclaimer" rows="4" class="form-control @error('report_disclaimer') is-invalid @enderror">{{ old('report_disclaimer', $user->report_disclaimer) }}</textarea>
                                                @error('report_disclaimer')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-5">
                                        <div class="card border mb-0 shadow-none">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">{{ __('messages.profile.report_preview') }}</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex align-items-center gap-2 mb-3">
                                                    <span class="avatar-sm bg-primary-subtle text-primary rounded d-inline-flex align-items-center justify-content-center"><i class="ti ti-file-analytics"></i></span>
                                                    <div>
                                                        <div class="fw-semibold">{{ __('messages.profile.report_preview_title') }}</div>
                                                        <small class="text-muted">{{ __('messages.profile.preview_subtitle') }}</small>
                                                    </div>
                                                </div>
                                                <dl class="row small mb-0">
                                                    <dt class="col-5 text-muted">{{ __('messages.profile.report_organization') }}</dt>
                                                    <dd class="col-7">{{ old('report_organization', $user->report_organization) ?: '—' }}</dd>
                                                    <dt class="col-5 text-muted">{{ __('messages.profile.report_prepared_by') }}</dt>
                                                    <dd class="col-7">{{ old('report_prepared_by', $user->report_prepared_by ?: $user->name) }}</dd>
                                                    <dt class="col-5 text-muted">{{ __('messages.profile.report_role_title') }}</dt>
                                                    <dd class="col-7">{{ old('report_role_title', $user->report_role_title) ?: '—' }}</dd>
                                                    <dt class="col-5 text-muted">{{ __('messages.profile.report_confidentiality_label') }}</dt>
                                                    <dd class="col-7"><span class="badge badge-soft-secondary badge-label">{{ old('report_confidentiality_label', $user->report_confidentiality_label ?: __('messages.profile.default_confidentiality')) }}</span></dd>
                                                </dl>
                                            </div>
                                            <div class="card-footer aptoria-card-footer-subtle small text-muted">
                                                {{ __('messages.profile.report_preview_footer') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-light" data-wizard-prev><i class="ti ti-arrow-left me-1"></i>{{ __('messages.common.back') }}</button>
                                    <button type="button" class="btn btn-primary" data-wizard-next>{{ __('messages.profile.next_finish') }} <i class="ti ti-arrow-right ms-1"></i></button>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="profileStepFinish" role="tabpanel">
                                <div class="row g-4">
                                    <div class="col-xl-5">
                                        <h5 class="mb-1">{{ __('messages.profile.finish_title') }}</h5>
                                        <p class="text-muted small">{{ __('messages.profile.finish_copy') }}</p>
                                        <div class="border rounded p-3 mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="text-muted small">{{ __('messages.profile.identity_completeness') }}</span>
                                                <span class="fw-semibold">{{ $identityCompleteness }}%</span>
                                            </div>
                                            <div class="progress progress-sm">
                                                <div class="progress-bar bg-success" style="width: {{ $identityCompleteness }}%"></div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="ti ti-device-floppy me-1"></i>{{ __('messages.profile.save_profile') }}
                                        </button>
                                    </div>
                                    <div class="col-xl-7">
                                        <div class="card border mb-0 shadow-none">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">{{ __('messages.profile.recent_activity') }}</h6>
                                            </div>
                                            <div class="card-body p-0">
                                                <div class="list-group list-group-flush">
                                                    @forelse ($recentActivity as $activity)
                                                        <div class="list-group-item d-flex align-items-start gap-3">
                                                            <span class="avatar-sm bg-light text-muted rounded d-inline-flex align-items-center justify-content-center flex-shrink-0"><i class="ti ti-activity"></i></span>
                                                            <div class="min-w-0 flex-grow-1">
                                                                <div class="d-flex justify-content-between gap-2">
                                                                    <span class="fw-semibold text-truncate">{{ $activity->summary }}</span>
                                                                    <small class="text-muted flex-shrink-0">{{ $activity->created_at?->diffForHumans() }}</small>
                                                                </div>
                                                                <small class="text-muted">{{ $activity->action }}</small>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="list-group-item text-center text-muted py-4">
                                                            <i class="ti ti-activity-heartbeat fs-3 d-block mb-2"></i>
                                                            {{ __('messages.profile.no_activity') }}
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-light" data-wizard-prev><i class="ti ti-arrow-left me-1"></i>{{ __('messages.common.back') }}</button>
                                    <button type="submit" class="btn btn-success"><i class="ti ti-device-floppy me-1"></i>{{ __('messages.profile.save_profile') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('profile.password.update') }}" id="profilePasswordForm" data-aptoria-form-scope="profile_password" data-aptoria-form-plugin>
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="passwordModalLabel">{{ __('messages.profile.change_password') }}</h5>
                        <p class="text-muted small mb-0">{{ __('messages.profile.password_modal_copy') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="currentPassword">{{ __('messages.profile.current_password') }}</label>
                        <input id="currentPassword" class="form-control @error('current_password') is-invalid @enderror" name="current_password" type="password" required autocomplete="current-password">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="newPassword">{{ __('messages.profile.new_password') }}</label>
                        <input id="newPassword" class="form-control @error('password') is-invalid @enderror" name="password" type="password" required autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="progress progress-sm mt-2">
                            <div class="progress-bar" id="passwordStrengthBar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted" id="passwordStrengthText">{{ __('messages.profile.password_strength_hint') }}</small>
                        <div class="aptoria-password-policy-list" aria-label="{{ __('messages.security.password_policy_title') }}">
                            <span><i class="ti ti-circle-check"></i>{{ __('messages.security.password_policy_length') }}</span>
                            <span><i class="ti ti-circle-check"></i>{{ __('messages.security.password_policy_complexity') }}</span>
                            <span><i class="ti ti-circle-check"></i>{{ __('messages.security.password_policy_not_reused') }}</span>
                            <span><i class="ti ti-circle-check"></i>{{ __('messages.security.password_policy_not_default') }}</span>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="passwordConfirmation">{{ __('messages.profile.confirm_password') }}</label>
                        <input id="passwordConfirmation" class="form-control" name="password_confirmation" type="password" required autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                    <button type="submit" class="btn btn-warning"><i class="ti ti-lock me-1"></i>{{ __('messages.profile.save_password') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/aptoria-ui/assets/js/pages/form-wizard.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var passwordInput = document.getElementById('newPassword');
    var strengthBar = document.getElementById('passwordStrengthBar');
    var strengthText = document.getElementById('passwordStrengthText');

    if (passwordInput && strengthBar && strengthText) {
        passwordInput.addEventListener('input', function () {
            var value = passwordInput.value || '';
            var score = 0;
            if (value.length >= 12) score += 25;
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score += 25;
            if (/[0-9]/.test(value)) score += 25;
            if (/[^A-Za-z0-9]/.test(value)) score += 25;

            strengthBar.style.width = score + '%';
            strengthBar.className = 'progress-bar ' + (score < 50 ? 'bg-danger' : (score < 75 ? 'bg-warning' : 'bg-success'));
            strengthText.textContent = score < 50
                ? @json(__('messages.profile.password_strength_weak'))
                : (score < 75 ? @json(__('messages.profile.password_strength_ok')) : @json(__('messages.profile.password_strength_strong')));
        });
    }

    var passwordForm = document.getElementById('profilePasswordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function (event) {
            if (passwordForm.dataset.confirmed === '1') {
                return;
            }

            event.preventDefault();
            if (window.Swal) {
                Swal.fire({
                    title: @json(__('messages.profile.password_confirm_title')),
                    text: @json(__('messages.profile.password_confirm_text')),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: @json(__('messages.profile.password_confirm_button')),
                    cancelButtonText: @json(__('messages.common.cancel')),
                    customClass: { confirmButton: 'btn btn-warning me-2', cancelButton: 'btn btn-light' },
                    buttonsStyling: false
                }).then(function (result) {
                    if (result.isConfirmed) {
                        passwordForm.dataset.confirmed = '1';
                        passwordForm.submit();
                    }
                });
            } else if (confirm(@json(__('messages.profile.password_confirm_text')))) {
                passwordForm.dataset.confirmed = '1';
                passwordForm.submit();
            }
        });
    }

    @if ($errors->has('current_password') || $errors->has('password'))
        var modal = document.getElementById('passwordModal');
        if (modal && window.bootstrap) {
            new bootstrap.Modal(modal).show();
        }
    @endif
});
</script>
@endpush
