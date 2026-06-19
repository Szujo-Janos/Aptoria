@extends('layouts.app')
@section('title', __('messages.project_members.title'))
@section('page_title', __('messages.project_members.title'))
@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.workspace.back_to_workspace') }}</a>
    @if ($canManageMembers)
        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addProjectMemberModal"><i data-lucide="user-check" class="me-1"></i>{{ __('messages.project_members.add_existing_member') }}</button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProjectUserModal"><i data-lucide="user-plus" class="me-1"></i>{{ __('messages.project_members.create_user_and_add') }}</button>
    @endif
@endsection
@section('content')
@if (session('temporary_password'))
    <div class="alert alert-warning d-flex align-items-start gap-3 mt-3" role="alert">
        <span class="avatar avatar-sm rounded text-bg-warning"><span class="avatar-title"><i data-lucide="key-round"></i></span></span>
        <div class="flex-grow-1">
            <h5 class="alert-heading mb-1">{{ __('messages.users.temporary_password_title') }}</h5>
            <p class="mb-2">{{ __('messages.users.temporary_password_copy', ['email' => session('temporary_user_email')]) }}</p>
            <code class="d-inline-block p-2 rounded bg-body text-body border">{{ session('temporary_password') }}</code>
            <div class="small text-muted mt-2">{{ __('messages.users.temporary_password_warning') }}</div>
        </div>
    </div>
@endif
<div class="card mb-3 aptoria-panel-card">
    <div class="card-body">
        <div class="row align-items-center g-3">
            <div class="col-xl-8">
                <span class="badge badge-soft-primary badge-label mb-2"><i class="ti ti-point-filled"></i>{{ __('messages.project_members.foundation_badge') }}</span>
                <h3 class="mb-2 fw-normal">{{ __('messages.project_members.page_title') }}</h3>
                <p class="text-muted mb-0">{{ __('messages.project_members.page_copy') }}</p>
            </div>
            <div class="col-xl-4">
                <div class="d-flex flex-wrap justify-content-xl-end gap-2">
                    <span class="aptoria-ui-chip"><i data-lucide="shield-check" class="fs-15"></i>{{ __('messages.project_members.your_role') }}: {{ $currentRole }}</span>
                    <span class="aptoria-ui-chip"><i data-lucide="hierarchy" class="fs-15"></i>{{ $memberships->count() }} {{ __('messages.project_members.members') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.project_members.foundation_copy') }}</div>
</div>

<div class="row row-cols-xxl-5 row-cols-md-2 row-cols-1 g-3 mb-3">
    @foreach ([
        ['project_admin', 'user-cog', 'primary'],
        ['qa_engineer', 'test-tube', 'success'],
        ['reviewer', 'clipboard-search', 'warning'],
        ['release_approver', 'badge-check', 'info'],
        ['read_only_viewer', 'eye', 'secondary'],
    ] as [$roleKey, $icon, $tone])
        <div class="col">
            <div class="card card-h-100 aptoria-widget-card">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-normal text-uppercase mb-3">{{ __('messages.project_members.roles.'.$roleKey) }}</h5>
                        <h2 class="fw-light mb-0">{{ $memberships->where('role', $roleKey)->where('status', 'active')->count() }}</h2>
                    </div>
                    <i data-lucide="{{ $icon }}" class="text-muted fs-42 svg-sw-10"></i>
                </div>
                <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.project_members.role_scope_'.$roleKey) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="card aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><i data-lucide="shield-check" class="me-1"></i>{{ __('messages.project_members.table_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.project_members.table_copy') }}</p>
        </div>
        <div class="card-action">
            <span class="badge badge-soft-primary badge-label"><i class="ti ti-table me-1"></i>{{ __('messages.common.ui_datatables') }}</span>
            <a href="#!" class="card-action-item" data-action="card-toggle"><i class="ti ti-chevron-up"></i></a>
            <a href="#!" class="card-action-item" data-action="card-refresh"><i class="ti ti-refresh"></i></a>
        </div>
    </div>
    <div class="card-body">
        <table data-tables="project-members" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
            <thead class="thead-sm text-uppercase fs-xxs">
                <tr>
                    <th data-priority="1">{{ __('messages.project_members.member') }}</th>
                    <th data-priority="2">{{ __('messages.project_members.role') }}</th>
                    <th data-priority="3">{{ __('messages.common.status') }}</th>
                    <th data-priority="5">{{ __('messages.project_members.added_by') }}</th>
                    <th data-priority="4">{{ __('messages.common.updated') }}</th>
                    @if ($canManageMembers)
                        <th class="text-end aptoria-actions-cell no-sort" data-priority="1">{{ __('messages.common.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($memberships as $membership)
                    @php
                        $isOwner = (int) $membership->user_id === (int) $project->user_id;
                        $memberIconMap = [
                            'project_admin' => 'user-cog',
                            'qa_engineer' => 'test-tube',
                            'reviewer' => 'clipboard-search',
                            'release_approver' => 'badge-check',
                            'read_only_viewer' => 'eye',
                        ];
                        $memberIcon = $isOwner ? 'shield-check' : ($memberIconMap[$membership->role] ?? 'circle-user-round');
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="avatar avatar-sm rounded text-bg-light"><span class="avatar-title"><i data-lucide="{{ $memberIcon }}"></i></span></span>
                                <div class="min-w-0">
                                    <span class="d-block text-truncate">{{ $membership->user?->name ?? __('messages.common.not_available') }}</span>
                                    <small class="text-muted d-block text-truncate">{{ $membership->user?->email ?? __('messages.common.not_available') }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-soft-{{ $isOwner ? 'primary' : $membership->role_tone }} badge-label"><i data-lucide="{{ $memberIcon }}" class="me-1"></i>{{ $isOwner ? __('messages.project_members.project_owner') : $membership->role_label }}</span>
                        </td>
                        <td><span class="badge badge-soft-{{ $membership->status_tone }} badge-label"><i data-lucide="{{ $membership->status === 'active' ? 'badge-check' : 'circle-x' }}" class="me-1"></i>{{ $membership->status_label }}</span></td>
                        <td>{{ $membership->invitedBy?->name ?? '—' }}</td>
                        <td>{{ $membership->updated_at?->format('Y-m-d H:i') }}</td>
                        @if ($canManageMembers)
                            <td class="text-end aptoria-actions-cell">
                                @if ($isOwner)
                                    <span class="badge badge-soft-primary badge-label"><i data-lucide="lock-keyhole" class="me-1"></i>{{ __('messages.project_members.owner_locked_badge') }}</span>
                                @else
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editMembership{{ $membership->id }}"><i data-lucide="pencil" class="me-2"></i>{{ __('messages.common.edit') }}</button>
                                            <div class="dropdown-divider"></div>
                                            <form method="POST" action="{{ route('projects.members.destroy', [$project, $membership]) }}" data-aptoria-confirm="delete" data-confirm-title="{{ __('messages.project_members.remove_title') }}" data-confirm-text="{{ __('messages.project_members.remove_text') }}" data-confirm-button="{{ __('messages.project_members.remove_confirm') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button class="dropdown-item text-danger" type="submit"><i data-lucide="user-x" class="me-2"></i>{{ __('messages.project_members.remove') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.project_members.table_footer') }}</div>
</div>

@if ($canManageMembers)
    <div class="modal fade" id="addProjectMemberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('projects.members.store', $project) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ __('messages.project_members.add_existing_member') }}</h5>
                        <p class="text-muted mb-0 small">{{ __('messages.project_members.add_existing_member_copy') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('messages.auth.email') }}</label>
                        <div class="input-group"><span class="input-group-text"><i data-lucide="mail"></i></span><input type="email" name="email" class="form-control" required placeholder="{{ __('messages.project_members.email_placeholder') }}" value="{{ old('email') }}"></div>
                        <div class="form-text">{{ __('messages.project_members.email_help') }}</div>
                    </div>
                    <div>
                        <label class="form-label">{{ __('messages.project_members.role') }}</label>
                        <select class="form-select" name="role" required>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}" @selected(old('role', 'qa_engineer') === $role)>{{ __('messages.project_members.roles.'.$role) }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">{{ __('messages.project_members.role_help') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="user-check" class="me-1"></i>{{ __('messages.project_members.add_existing_member') }}</button>
                </div>
            </form>
        </div>
    </div>


    <div class="modal fade" id="createProjectUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('projects.members.create-user', $project) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ __('messages.project_members.create_user_and_add') }}</h5>
                        <p class="text-muted mb-0 small">{{ __('messages.project_members.create_user_and_add_copy') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.profile.name') }}</label>
                            <div class="input-group"><span class="input-group-text"><i data-lucide="id-card"></i></span><input type="text" name="name" class="form-control" required maxlength="255" placeholder="{{ __('messages.users.name_placeholder') }}" value="{{ old('name') }}"></div>
                            <div class="form-text">{{ __('messages.users.name_help') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('messages.auth.email') }}</label>
                            <div class="input-group"><span class="input-group-text"><i data-lucide="mail"></i></span><input type="email" name="email" class="form-control" required maxlength="255" placeholder="{{ __('messages.users.email_placeholder') }}" value="{{ old('email') }}"></div>
                            <div class="form-text">{{ __('messages.project_members.create_user_email_help') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('messages.project_members.role') }}</label>
                            <select class="form-select" name="role" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected(old('role', 'qa_engineer') === $role)>{{ __('messages.project_members.roles.'.$role) }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('messages.project_members.role_help') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('messages.profile.language') }}</label>
                            <select class="form-select" name="locale" required>
                                @foreach ($supportedLocales as $locale => $label)
                                    <option value="{{ $locale }}" @selected(old('locale', app()->getLocale()) === $locale)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('messages.users.locale_help') }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('messages.profile.timezone') }}</label>
                            <select class="form-select" name="timezone" required>
                                @foreach ($supportedTimezones as $timezone)
                                    <option value="{{ $timezone }}" @selected(old('timezone', config('app.timezone', 'Europe/Budapest')) === $timezone)>{{ $timezone }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('messages.users.timezone_help') }}</div>
                        </div>
                    </div>
                    <div class="alert alert-info d-flex gap-2 mt-3 mb-0" role="alert">
                        <i data-lucide="key-round" class="mt-1"></i>
                        <div><strong>{{ __('messages.users.temporary_password_title') }}</strong><br><span class="small">{{ __('messages.project_members.create_user_password_help') }}</span></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="user-plus" class="me-1"></i>{{ __('messages.project_members.create_user_and_add') }}</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($memberships as $membership)
        @continue((int) $membership->user_id === (int) $project->user_id)
        <div class="modal fade" id="editMembership{{ $membership->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('projects.members.update', [$project, $membership]) }}" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">{{ __('messages.project_members.edit_member') }}</h5>
                            <p class="text-muted mb-0 small">{{ $membership->user?->email }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.project_members.role') }}</label>
                            <select class="form-select" name="role" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role }}" @selected($membership->role === $role)>{{ __('messages.project_members.roles.'.$role) }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('messages.project_members.role_help') }}</div>
                        </div>
                        <div>
                            <label class="form-label">{{ __('messages.common.status') }}</label>
                            <select class="form-select" name="status" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected($membership->status === $status)>{{ __('messages.project_members.statuses.'.$status) }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('messages.project_members.status_help') }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endif
@endsection
