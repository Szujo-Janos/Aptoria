@extends('layouts.app')

@section('title', __('messages.client_portal.title'))

@section('content')
@if(session('client_portal_url'))
    <div class="alert alert-success">
        <strong>{{ __('messages.client_portal.new_link') }}:</strong>
        <code>{{ session('client_portal_url') }}</code>
    </div>
@endif
<div class="row">
    <div class="col-md-5">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.client_portal.create_access') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.client_portal.intro') }}</p>
                <form method="POST" action="{{ route('projects.client-portal.store', $project) }}">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('messages.client_portal.label') }}</label>
                        <input type="text" name="label" class="form-control" value="{{ old('label', $project->name.' client audit portal') }}" required maxlength="160">
                    </div>
                    <div class="row">
                        <div class="col-sm-6"><div class="form-group"><label>{{ __('messages.client_portal.contact_name') }}</label><input type="text" name="contact_name" class="form-control" value="{{ old('contact_name') }}" maxlength="160"></div></div>
                        <div class="col-sm-6"><div class="form-group"><label>{{ __('messages.client_portal.contact_email') }}</label><input type="email" name="contact_email" class="form-control" value="{{ old('contact_email') }}" maxlength="190"></div></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6"><div class="form-group"><label>{{ __('messages.client_portal.role') }}</label><select name="role" id="client_portal_role" class="form-control">
                            @foreach(\App\Models\ClientPortalAccess::ROLES as $role)
                                <option value="{{ $role }}" @selected(old('role', \App\Models\ClientPortalAccess::ROLE_CLIENT_VIEWER) === $role)>{{ __('messages.client_portal.roles.'.$role) }}</option>
                            @endforeach
                        </select></div></div>
                        <div class="col-sm-6"><div class="form-group"><label>{{ __('messages.client_portal.expires_at') }}</label><input type="date" name="expires_at" class="form-control" value="{{ old('expires_at') }}"></div></div>
                    </div>
                    <hr>
                    <h5>{{ __('messages.client_portal.permissions') }}</h5>
                    <div class="row">
                        @foreach(\App\Models\ClientPortalAccess::PERMISSIONS as $permission)
                            <div class="col-sm-6 m-b-xs">
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="{{ $permission }}" value="1" class="client-portal-permission-checkbox" data-permission="{{ $permission }}" @checked(old($permission, $defaults[$permission] ?? false))>
                                    {{ __('messages.client_portal.permission_labels.'.$permission) }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-link"></i> {{ __('messages.client_portal.create_button') }}</button>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-default">{{ __('messages.common.back') }}</a>
                </form>
            </div>
        </div>

        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.client_portal.role_defaults') }}</div>
            <div class="panel-body p-none">
                <div class="table-responsive">
                    <table class="table table-condensed m-b-none">
                        <thead>
                        <tr>
                            <th>{{ __('messages.client_portal.role') }}</th>
                            <th>{{ __('messages.client_portal.permissions') }}</th>
                            <th>{{ __('messages.client_portal.approval_permissions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($roleDefaults as $role => $permissions)
                            @php
                                $contentPermissions = collect($permissions)->filter(fn ($enabled, $permission) => $enabled && ! in_array($permission, [\App\Models\ClientPortalAccess::PERMISSION_APPROVE_REPORTS, \App\Models\ClientPortalAccess::PERMISSION_ACKNOWLEDGE_RELEASE, \App\Models\ClientPortalAccess::PERMISSION_APPROVE_RISKS], true));
                                $approvalPermissions = collect($permissions)->filter(fn ($enabled, $permission) => $enabled && in_array($permission, [\App\Models\ClientPortalAccess::PERMISSION_APPROVE_REPORTS, \App\Models\ClientPortalAccess::PERMISSION_ACKNOWLEDGE_RELEASE, \App\Models\ClientPortalAccess::PERMISSION_APPROVE_RISKS], true));
                            @endphp
                            <tr>
                                <td><strong>{{ __('messages.client_portal.roles.'.$role) }}</strong></td>
                                <td>
                                    @forelse($contentPermissions as $permission => $enabled)
                                        <span class="label label-success m-r-xs">{{ __('messages.client_portal.permission_labels.'.$permission) }}</span>
                                    @empty
                                        <span class="text-muted">{{ __('messages.common.none') }}</span>
                                    @endforelse
                                </td>
                                <td>
                                    @forelse($approvalPermissions as $permission => $enabled)
                                        <span class="label label-warning m-r-xs">{{ __('messages.client_portal.permission_labels.'.$permission) }}</span>
                                    @empty
                                        <span class="text-muted">{{ __('messages.common.none') }}</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-sm text-muted">{{ __('messages.client_portal.role_defaults_hint') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.client_portal.active_links') }}</div>
            <div class="panel-body p-none">
                @if($accesses->isEmpty())
                    <div class="p-md text-muted">{{ __('messages.client_portal.empty') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover m-b-none">
                            <thead><tr><th>{{ __('messages.client_portal.label') }}</th><th>{{ __('messages.client_portal.role') }}</th><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.client_portal.expires_at') }}</th><th>{{ __('messages.client_portal.last_viewed') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($accesses as $access)
                                <tr>
                                    <td><strong>{{ $access->label }}</strong><br><small><code>{{ $access->portal_url }}</code></small></td>
                                    <td>{{ $access->role_label }}</td>
                                    <td><span class="label label-{{ $access->status_css }}">{{ $access->status_label }}</span></td>
                                    <td>{{ $access->expires_at?->format('Y-m-d') ?: '—' }}</td>
                                    <td>{{ $access->last_viewed_at?->format('Y-m-d H:i') ?: '—' }}</td>
                                    <td class="text-right">
                                        <a href="{{ $access->portal_url }}" class="btn btn-xs btn-default" target="_blank" rel="noopener">{{ __('messages.client_portal.open_portal') }}</a>
                                        @if($access->status === \App\Models\ClientPortalAccess::STATUS_ACTIVE)
                                            <form method="POST" action="{{ route('projects.client-portal.revoke', [$project, $access]) }}" style="display:inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="btn btn-xs btn-danger">{{ __('messages.client_portal.revoke') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-sm">{{ $accesses->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var roleSelect = document.getElementById('client_portal_role');
    var defaults = @json($roleDefaults);
    var boxes = document.querySelectorAll('.client-portal-permission-checkbox');

    if (!roleSelect || !defaults) {
        return;
    }

    roleSelect.addEventListener('change', function () {
        var roleDefaults = defaults[roleSelect.value] || {};
        boxes.forEach(function (box) {
            var permission = box.getAttribute('data-permission');
            box.checked = !!roleDefaults[permission];
        });
    });
});
</script>

@endsection
