@extends('layouts.app')

@section('title', __('messages.environments.manager_title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.environments.create', $project) }}" class="btn btn-xs btn-success"><i class="fa fa-plus"></i> {{ __('messages.environments.new') }}</a>
                    <a href="{{ route('projects.settings.edit', $project) }}" class="btn btn-xs btn-default"><i class="fa fa-cog"></i> {{ __('messages.project_settings.title') }}</a>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.environments.manager_title') }}
            </div>
            <div class="panel-body">
                <p class="text-muted m-b-none">{{ __('messages.environments.manager_intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row text-center">
    <div class="col-sm-3">
        <div class="hpanel hgreen"><div class="panel-body"><h2>{{ $project->environments->count() }}</h2><small>{{ __('messages.environments.total') }}</small></div></div>
    </div>
    <div class="col-sm-3">
        <div class="hpanel hblue"><div class="panel-body"><h2>{{ $project->environments->where('is_production', false)->count() }}</h2><small>{{ __('messages.environments.non_production') }}</small></div></div>
    </div>
    <div class="col-sm-3">
        <div class="hpanel hred"><div class="panel-body"><h2>{{ $project->environments->where('is_production', true)->count() }}</h2><small>{{ __('messages.environments.production') }}</small></div></div>
    </div>
    <div class="col-sm-3">
        <div class="hpanel hyellow"><div class="panel-body"><h2>{{ $project->environments->whereNotNull('auth_profile_id')->count() }}</h2><small>{{ __('messages.environments.with_auth') }}</small></div></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.environments.environment_matrix') }}</div>
            <div class="panel-body">
                @if($project->environments->isEmpty())
                    <div class="text-center p-lg">
                        <p class="text-muted">{{ __('messages.environments.empty') }}</p>
                        <a href="{{ route('projects.environments.create', $project) }}" class="btn btn-success btn-sm">{{ __('messages.environments.create_button') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.common.name') }}</th>
                                    <th>{{ __('messages.environments.environment_type') }}</th>
                                    <th>{{ __('messages.common.base_url') }}</th>
                                    <th>{{ __('messages.environments.auth_profile') }}</th>
                                    <th>{{ __('messages.environments.default_environment') }}</th>
                                    <th>{{ __('messages.projects.endpoints') }}</th>
                                    <th>{{ __('messages.projects.scans') }}</th>
                                    <th>{{ __('messages.snapshots.title') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($project->environments as $environment)
                                    @php($isDefault = (string) $defaultEnvironmentId === (string) $environment->id)
                                    <tr>
                                        <td><strong>{{ $environment->name }}</strong></td>
                                        <td>
                                            <span class="label label-{{ $environment->environment_type_css }}">{{ $environment->environment_type_label }}</span>
                                            @if($environment->is_production)
                                                <span class="label label-danger">{{ __('messages.environments.production') }}</span>
                                            @endif
                                        </td>
                                        <td><code>{{ $environment->display_base_url }}</code></td>
                                        <td>{{ $environment->authProfile?->name ?: __('messages.environments.use_project_default_auth') }}</td>
                                        <td>
                                            @if($isDefault)
                                                <span class="label label-success"><i class="fa fa-check"></i> {{ __('messages.environments.default_environment') }}</span>
                                            @else
                                                <form method="POST" action="{{ route('projects.environments.default', [$project, $environment]) }}" class="inline-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-xs btn-default">{{ __('messages.environments.make_default') }}</button>
                                                </form>
                                            @endif
                                        </td>
                                        <td>{{ $environment->endpoints_count }}</td>
                                        <td>{{ $environment->scan_runs_count }}</td>
                                        <td>{{ $environment->snapshots_count }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('projects.environments.edit', [$project, $environment]) }}" class="btn btn-xs btn-default">{{ __('messages.common.edit') }}</a>
                                            <a href="{{ route('projects.endpoint-inventory.index', ['project' => $project, 'environment' => $environment->id]) }}" class="btn btn-xs btn-info">{{ __('messages.nav.endpoint_inventory') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
