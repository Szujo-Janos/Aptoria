@extends('layouts.app')

@section('title', __('messages.project_members.title'))

@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> {{ __('messages.common.back') }}</a>
@endsection

@push('styles')
<style>
    .aptoria-members-hero .panel-body { padding: 24px 28px; }
    .aptoria-members-eyebrow { display:inline-block; margin-bottom:8px; }
    .aptoria-members-title { margin:0 0 6px; font-weight:400; }
    .aptoria-members-subtitle { max-width:760px; }
    .aptoria-members-stat { border-left:1px solid #e7edf3; padding:8px 10px; }
    .aptoria-members-stat:first-child { border-left:0; }
    .aptoria-members-stat strong { display:block; font-size:24px; line-height:1.1; font-weight:400; color:#34495e; }
    .aptoria-members-stat small { color:#7b8794; }
    .aptoria-access-flow { display:flex; gap:16px; flex-wrap:wrap; }
    .aptoria-access-step { flex:1 1 210px; border:1px solid #e7edf3; border-radius:8px; padding:14px 16px; background:#fbfcfe; }
    .aptoria-access-step .step-number { width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; background:#edf6ff; color:#3498db; margin-right:8px; font-weight:600; }
    .aptoria-member-card { border:1px solid #e7edf3; border-radius:8px; padding:14px; margin-bottom:12px; background:#fff; }
    .aptoria-member-card:hover { border-color:#c9d8e6; }
    .aptoria-member-card-title { font-weight:600; color:#34495e; margin-bottom:2px; }
    .aptoria-member-card-meta { color:#7b8794; font-size:12px; }
    .aptoria-member-card-form { margin-top:12px; }
    .aptoria-create-user-panel .form-group { margin-bottom:14px; }
    .aptoria-role-chip { display:inline-block; border:1px solid #e7edf3; border-radius:16px; padding:4px 10px; margin:0 4px 6px 0; background:#fbfcfe; color:#52616f; font-size:12px; }
    .aptoria-permission-list { columns:2; -webkit-columns:2; -moz-columns:2; }
    .aptoria-permission-row { break-inside:avoid; margin-bottom:7px; }
    .aptoria-muted-panel { background:#fbfcfe; border:1px solid #e7edf3; border-radius:8px; padding:12px 14px; }
    @media (max-width: 991px) {
        .aptoria-members-stat { border-left:0; border-top:1px solid #e7edf3; margin-top:10px; }
        .aptoria-members-stat:first-child { border-top:0; }
        .aptoria-permission-list { columns:1; -webkit-columns:1; -moz-columns:1; }
    }
</style>
@endpush

@section('content')
@php
    $defaultRole = \App\Models\ProjectMembership::ROLE_QA_ENGINEER;
@endphp

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue aptoria-members-hero">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-7">
                        <span class="label label-info aptoria-members-eyebrow"><i class="fa fa-users"></i> {{ __('messages.project_members.short_title') }}</span>
                        <h2 class="aptoria-members-title">{{ __('messages.project_members.dashboard.title') }}</h2>
                        <p class="text-muted aptoria-members-subtitle m-b-none">{{ __('messages.project_members.dashboard.subtitle') }}</p>
                    </div>
                    <div class="col-md-5">
                        <div class="row text-center">
                            <div class="col-xs-4 aptoria-members-stat">
                                <strong>{{ $memberCount }}</strong>
                                <small>{{ __('messages.project_members.stats.project_members') }}</small>
                            </div>
                            <div class="col-xs-4 aptoria-members-stat">
                                <strong>{{ $availableUsersCount }}</strong>
                                <small>{{ __('messages.project_members.stats.available_users') }}</small>
                            </div>
                            <div class="col-xs-4 aptoria-members-stat">
                                <strong>{{ $totalUsers }}</strong>
                                <small>{{ __('messages.project_members.stats.internal_users') }}</small>
                            </div>
                        </div>
                        <div class="m-t-md text-right">
                            <span class="label label-default"><i class="fa fa-key"></i> {{ $currentProjectRoleLabel }}</span>
                            <span class="label {{ $canManageMembers ? 'label-success' : 'label-default' }}">
                                {{ $canManageMembers ? __('messages.project_members.stats.can_manage_yes') : __('messages.project_members.stats.can_manage_no') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt"><i class="fa fa-map-signs"></i> {{ __('messages.project_members.flow.title') }}</div>
            <div class="panel-body">
                <div class="aptoria-access-flow">
                    <div class="aptoria-access-step">
                        <h4><span class="step-number">1</span>{{ __('messages.project_members.flow.step_user_title') }}</h4>
                        <p class="text-muted m-b-none">{{ __('messages.project_members.flow.step_user_body') }}</p>
                    </div>
                    <div class="aptoria-access-step">
                        <h4><span class="step-number">2</span>{{ __('messages.project_members.flow.step_member_title') }}</h4>
                        <p class="text-muted m-b-none">{{ __('messages.project_members.flow.step_member_body') }}</p>
                    </div>
                    <div class="aptoria-access-step">
                        <h4><span class="step-number">3</span>{{ __('messages.project_members.flow.step_role_title') }}</h4>
                        <p class="text-muted m-b-none">{{ __('messages.project_members.flow.step_role_body') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($canManageMembers)
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <i class="fa fa-user-plus"></i> {{ __('messages.project_members.existing_user.title') }}
                <span class="pull-right text-muted">{{ __('messages.project_members.existing_user.count_label', ['count' => $availableUsersCount]) }}</span>
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.project_members.existing_user.help') }}</p>

                <form method="GET" action="{{ route('projects.members.index', $project) }}" class="row m-b-md">
                    <div class="col-sm-8">
                        <input type="text" name="user_q" value="{{ $userSearch }}" class="form-control" placeholder="{{ __('messages.project_members.existing_user.search_placeholder') }}">
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-default"><i class="fa fa-search"></i> {{ __('messages.common.search') }}</button>
                        @if($userSearch !== '')
                            <a href="{{ route('projects.members.index', $project) }}" class="btn btn-link">{{ __('messages.project_members.existing_user.clear_search') }}</a>
                        @endif
                    </div>
                </form>

                @if($availableUsers->isEmpty())
                    <div class="alert alert-info m-b-none">
                        <strong>{{ __('messages.project_members.existing_user.empty_title') }}</strong><br>
                        {{ $userSearch !== '' ? __('messages.project_members.existing_user.empty_search') : __('messages.project_members.existing_user.empty_all_members') }}
                    </div>
                @else
                    <div class="row">
                        @foreach($availableUsers as $availableUser)
                            <div class="col-md-6">
                                <div class="aptoria-member-card">
                                    <div class="aptoria-member-card-title"><i class="fa fa-user text-muted"></i> {{ $availableUser->name }}</div>
                                    <div class="aptoria-member-card-meta"><i class="fa fa-envelope-o"></i> {{ $availableUser->email }}</div>
                                    <div class="m-t-xs">
                                        <span class="label {{ $availableUser->isSystemAdmin() ? 'label-primary' : 'label-default' }}">
                                            {{ $availableUser->isSystemAdmin() ? __('messages.project_members.global_roles.admin') : __('messages.project_members.global_roles.user') }}
                                        </span>
                                        <span class="text-muted small m-l-xs">
                                            {{ $availableUser->last_login_at ? __('messages.project_members.existing_user.last_seen', ['date' => $availableUser->last_login_at->format('Y-m-d H:i')]) : __('messages.project_members.existing_user.never_logged_in') }}
                                        </span>
                                    </div>
                                    <form method="POST" action="{{ route('projects.members.store', $project) }}" class="aptoria-member-card-form">
                                        @csrf
                                        <input type="hidden" name="email" value="{{ $availableUser->email }}">
                                        <div class="row">
                                            <div class="col-sm-7">
                                                <select name="role" class="form-control input-sm" aria-label="{{ __('messages.project_members.role') }}">
                                                    @foreach($roleOptions as $value => $label)
                                                        <option value="{{ $value }}" @selected($value === $defaultRole)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-sm-5">
                                                <button type="submit" class="btn btn-success btn-sm btn-block"><i class="fa fa-plus"></i> {{ __('messages.project_members.existing_user.add_to_project') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($availableUsersCount > $availableUsers->count())
                        <p class="small text-muted m-t-sm m-b-none">{{ __('messages.project_members.existing_user.result_limit', ['shown' => $availableUsers->count(), 'total' => $availableUsersCount]) }}</p>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="hpanel hblue aptoria-create-user-panel">
            <div class="panel-heading hbuilt"><i class="fa fa-user-plus"></i> {{ __('messages.project_members.create_user.title') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.project_members.create_user.help') }}</p>
                <form method="POST" action="{{ route('projects.members.store', $project) }}">
                    @csrf
                    <input type="hidden" name="create_user" value="1">
                    <div class="form-group">
                        <label>{{ __('messages.project_members.create_user.name') }}</label>
                        <input type="text" name="new_user_name" class="form-control" value="{{ old('new_user_name') }}" placeholder="{{ __('messages.project_members.new_user_name_placeholder') }}" required>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.auth.email') }}</label>
                        <input type="email" name="email" class="form-control" placeholder="qa@example.com" value="{{ old('email') }}" required>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.project_members.new_user_password') }}</label>
                        <input type="password" name="new_user_password" class="form-control" placeholder="{{ __('messages.project_members.new_user_password_placeholder') }}" required>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.project_members.new_user_password_confirmation') }}</label>
                        <input type="password" name="new_user_password_confirmation" class="form-control" placeholder="{{ __('messages.project_members.new_user_password_confirmation_placeholder') }}" required>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.project_members.role') }}</label>
                        <select name="role" class="form-control" required>
                            @foreach($roleOptions as $value => $label)
                                <option value="{{ $value }}" @selected($value === $defaultRole)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.common.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('messages.project_members.notes_placeholder') }}">{{ old('notes') }}</textarea>
                    </div>
                    <div class="aptoria-muted-panel m-b-md">
                        <i class="fa fa-lock text-muted"></i> {{ __('messages.project_members.create_user.password_notice') }}
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-user-plus"></i> {{ __('messages.project_members.create_user.submit') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@else
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt"><i class="fa fa-lock"></i> {{ __('messages.project_members.restricted_title') }}</div>
            <div class="panel-body">
                <p class="text-muted m-b-none">{{ __('messages.project_members.restricted_help') }}</p>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <i class="fa fa-address-card-o"></i> {{ __('messages.project_members.current_members.title') }}
                <span class="pull-right text-muted">{{ __('messages.project_members.current_members.count_label', ['count' => $memberCount]) }}</span>
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.project_members.current_members.help') }}</p>
                @if($memberships->isEmpty())
                    <div class="alert alert-info m-b-none">{{ __('messages.project_members.empty') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.project_members.current_members.member') }}</th>
                                    <th>{{ __('messages.project_members.role') }}</th>
                                    <th>{{ __('messages.project_members.permissions') }}</th>
                                    <th>{{ __('messages.project_members.added_by') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($memberships as $membership)
                                    <tr>
                                        <td>
                                            <strong>{{ $membership->user?->name ?: __('messages.common.not_available') }}</strong>
                                            @if($membership->user_id === $project->user_id)
                                                <span class="label label-primary m-l-xs">{{ __('messages.project_members.owner') }}</span>
                                            @endif
                                            <br><small class="text-muted"><i class="fa fa-envelope-o"></i> {{ $membership->user?->email ?: __('messages.common.not_available') }}</small>
                                        </td>
                                        <td><span class="label label-default">{{ $membership->translated_role_label }}</span></td>
                                        <td>
                                            @foreach(array_slice($membership->permissions(), 0, 4) as $permission)
                                                <span class="aptoria-role-chip">{{ \App\Models\ProjectMembership::translatedPermissionLabel($permission) }}</span>
                                            @endforeach
                                            @if(count($membership->permissions()) > 4)
                                                <span class="text-muted small">{{ __('messages.project_members.current_members.more_permissions', ['count' => count($membership->permissions()) - 4]) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $membership->invitedBy?->name ?: __('messages.common.none') }}<br>
                                            <small class="text-muted">{{ $membership->created_at?->format('Y-m-d H:i') }}</small>
                                        </td>
                                        <td class="text-right">
                                            @if($canManageMembers)
                                                <button type="button" class="btn btn-xs btn-primary" data-toggle="collapse" data-target="#membership-edit-{{ $membership->id }}">
                                                    <i class="fa fa-pencil"></i> {{ __('messages.common.edit') }}
                                                </button>
                                                @if($membership->user_id !== $project->user_id)
                                                    <form method="POST" action="{{ route('projects.members.destroy', [$project, $membership]) }}" class="inline-form d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-danger aptoria-confirm" data-confirm-message="{{ __('messages.project_members.delete_confirm') }}">
                                                            <i class="fa fa-trash"></i> {{ __('messages.common.delete') }}
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                <span class="text-muted">{{ __('messages.project_members.manage_restricted') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if($canManageMembers)
                                        <tr id="membership-edit-{{ $membership->id }}" class="collapse">
                                            <td colspan="5">
                                                <form method="POST" action="{{ route('projects.members.update', [$project, $membership]) }}" class="row">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="col-sm-4">
                                                        <label>{{ __('messages.project_members.role') }}</label>
                                                        <select name="role" class="form-control">
                                                            @foreach($roleOptions as $value => $label)
                                                                <option value="{{ $value }}" @selected($membership->role === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label>{{ __('messages.common.notes') }}</label>
                                                        <input type="text" name="notes" class="form-control" value="{{ $membership->notes }}" placeholder="{{ __('messages.project_members.notes_placeholder') }}">
                                                    </div>
                                                    <div class="col-sm-2 m-t-lg">
                                                        <button type="submit" class="btn btn-success btn-block"><i class="fa fa-check"></i> {{ __('messages.common.update') }}</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $memberships->links() }}
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="hpanel">
            <div class="panel-heading hbuilt"><i class="fa fa-shield"></i> {{ __('messages.project_members.role_matrix') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.project_members.role_matrix_help') }}</p>
                <div class="row">
                    @foreach($roleOptions as $role => $label)
                        <div class="col-md-6 m-b-md">
                            <h5 class="m-b-xs"><span class="label label-default">{{ $label }}</span></h5>
                            <p class="small text-muted m-b-xs">{{ collect($rolePermissions[$role] ?? [])->map(fn ($permission) => \App\Models\ProjectMembership::translatedPermissionLabel($permission))->implode(', ') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt"><i class="fa fa-key"></i> {{ __('messages.project_members.my_permissions') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.project_members.my_permissions_help') }}</p>
                <div class="aptoria-permission-list">
                    @foreach($currentPermissionMap as $ability => $granted)
                        <div class="aptoria-permission-row">
                            <span class="label {{ $granted ? 'label-success' : 'label-default' }}">{{ $granted ? __('messages.project_members.permission_status.granted') : __('messages.project_members.permission_status.denied') }}</span>
                            <span class="m-l-xs">{{ \App\Models\ProjectMembership::translatedPermissionLabel($ability) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
