@extends('layouts.app')
@section('title', __('messages.users.title'))
@section('page_title', __('messages.users.title'))
@section('page_actions')
    <a href="{{ route('program-settings.edit') }}" class="btn btn-light"><i data-lucide="arrow-left" class="me-1"></i>{{ __('messages.nav.program_settings') }}</a>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal"><i data-lucide="user-plus" class="me-1"></i>{{ __('messages.users.create_user') }}</button>
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
                <span class="badge badge-soft-primary badge-label mb-2"><i class="ti ti-point-filled"></i>{{ __('messages.users.foundation_badge') }}</span>
                <h3 class="mb-2 fw-normal">{{ __('messages.users.page_title') }}</h3>
                <p class="text-muted mb-0">{{ __('messages.users.page_copy') }}</p>
            </div>
            <div class="col-xl-4">
                <div class="d-flex flex-wrap justify-content-xl-end gap-2">
                    <span class="aptoria-ui-chip"><i data-lucide="users-round" class="fs-15"></i>{{ $users->count() }} {{ __('messages.users.accounts') }}</span>
                    <span class="aptoria-ui-chip"><i data-lucide="user-cog" class="fs-15"></i>{{ $adminCount }} {{ __('messages.users.admins') }}</span>
                    <span class="aptoria-ui-chip"><i data-lucide="key-round" class="fs-15"></i>{{ $passwordChangeRequiredCount }} {{ __('messages.users.password_pending') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.users.foundation_copy') }}</div>
</div>

<div class="card aptoria-table-card aptoria-panel-card">
    <div class="card-header border-light justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-1"><i data-lucide="user-cog" class="me-1"></i>{{ __('messages.users.table_title') }}</h5>
            <p class="text-muted mb-0 small">{{ __('messages.users.table_copy') }}</p>
        </div>
        <div class="card-action">
            <span class="badge badge-soft-primary badge-label"><i class="ti ti-table me-1"></i>{{ __('messages.common.ui_datatables') }}</span>
            <a href="#!" class="card-action-item" data-action="card-toggle"><i class="ti ti-chevron-up"></i></a>
            <a href="#!" class="card-action-item" data-action="card-refresh"><i class="ti ti-refresh"></i></a>
        </div>
    </div>
    <div class="card-body">
        <table data-tables="users" class="table table-custom table-striped table-nowrap table-centered mb-0 w-100 aptoria-resource-table">
            <thead class="thead-sm text-uppercase fs-xxs">
                <tr>
                    <th data-priority="1">{{ __('messages.users.user') }}</th>
                    <th data-priority="2">{{ __('messages.users.system_role') }}</th>
                    <th data-priority="3">{{ __('messages.users.security') }}</th>
                    <th data-priority="4">{{ __('messages.users.projects') }}</th>
                    <th data-priority="5">{{ __('messages.common.updated') }}</th>
                    <th class="text-end aptoria-actions-cell no-sort" data-priority="1">{{ __('messages.common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $managedUser)
                    @php($managedUserIcon = $managedUser->isAdmin() ? 'user-cog' : ($managedUser->password_change_required ? 'key-round' : 'circle-user-round'))
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="avatar avatar-sm rounded text-bg-light"><span class="avatar-title"><i data-lucide="{{ $managedUserIcon }}"></i></span></span>
                                <div class="min-w-0">
                                    <span class="d-block text-truncate">{{ $managedUser->name }}</span>
                                    <small class="text-muted d-block text-truncate">{{ $managedUser->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-soft-{{ $managedUser->isAdmin() ? 'primary' : 'secondary' }} badge-label"><i data-lucide="{{ $managedUser->isAdmin() ? 'user-cog' : 'circle-user-round' }}" class="me-1"></i>{{ __('messages.profile.roles.'.($managedUser->role ?: 'user')) }}</span></td>
                        <td>
                            @if ($managedUser->password_change_required)
                                <span class="badge badge-soft-warning badge-label"><i data-lucide="key-round" class="me-1"></i>{{ __('messages.profile.update_required') }}</span>
                            @else
                                <span class="badge badge-soft-success badge-label"><i data-lucide="shield-check" class="me-1"></i>{{ __('messages.profile.password_ok') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-soft-primary badge-label"><i data-lucide="shield-check" class="me-1"></i>{{ $managedUser->project_memberships_count }} {{ __('messages.project_members.members') }}</span>
                            @if ($managedUser->owned_projects_count)
                                <span class="badge badge-soft-info badge-label"><i data-lucide="folder-kanban" class="me-1"></i>{{ $managedUser->owned_projects_count }} {{ __('messages.users.owned') }}</span>
                            @endif
                        </td>
                        <td>{{ $managedUser->updated_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-end aptoria-actions-cell">
                            <div class="dropdown">
                                <button class="btn btn-light btn-icon btn-sm rounded-circle" data-bs-toggle="dropdown" data-bs-boundary="viewport" type="button" aria-label="{{ __('messages.common.actions') }}"><i class="ti ti-dots"></i></button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editUser{{ $managedUser->id }}"><i data-lucide="pencil" class="me-2"></i>{{ __('messages.common.edit') }}</button>
                                    <form method="POST" action="{{ route('users.temporary-password', $managedUser) }}" data-aptoria-confirm="warning" data-confirm-title="{{ __('messages.users.reset_password_title') }}" data-confirm-text="{{ __('messages.users.reset_password_text') }}" data-confirm-button="{{ __('messages.users.reset_password') }}">
                                        @csrf
                                        <button class="dropdown-item" type="submit"><i data-lucide="key-round" class="me-2"></i>{{ __('messages.users.reset_password') }}</button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted text-center aptoria-card-footer-subtle">{{ __('messages.users.table_footer') }}</div>
</div>

<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('users.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ __('messages.users.create_user') }}</h5>
                    <p class="text-muted mb-0 small">{{ __('messages.users.create_user_copy') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
            </div>
            <div class="modal-body">
                @include('users.partials.user_form', ['managedUser' => null, 'systemRoles' => $systemRoles, 'supportedLocales' => $supportedLocales, 'supportedTimezones' => $supportedTimezones])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                <button type="submit" class="btn btn-primary"><i data-lucide="user-plus" class="me-1"></i>{{ __('messages.users.create_user') }}</button>
            </div>
        </form>
    </div>
</div>

@foreach ($users as $managedUser)
    <div class="modal fade" id="editUser{{ $managedUser->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('users.update', $managedUser) }}" class="modal-content">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">{{ __('messages.users.edit_user') }}</h5>
                        <p class="text-muted mb-0 small">{{ $managedUser->email }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body">
                    @include('users.partials.user_form', ['managedUser' => $managedUser, 'systemRoles' => $systemRoles, 'supportedLocales' => $supportedLocales, 'supportedTimezones' => $supportedTimezones])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
