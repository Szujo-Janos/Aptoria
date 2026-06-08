@extends('layouts.app')

@section('title', __('messages.projects.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.wizard.create') }}" class="btn btn-xs btn-info">{{ __('messages.wizard.short_title') }}</a>
                    <a href="{{ route('projects.create') }}" class="btn btn-xs btn-success">{{ __('messages.dashboard.new_project') }}</a>
                </div>
                {{ __('messages.projects.all') }}
            </div>
            <div class="panel-body">
                @if($projects->isEmpty())
                    <div class="text-center p-xl">
                        <h4>{{ __('messages.projects.empty_title') }}</h4>
                        <p class="text-muted">{{ __('messages.projects.empty_help') }}</p>
                        <a href="{{ route('projects.wizard.create') }}" class="btn btn-info">{{ __('messages.wizard.short_title') }}</a>
                        <a href="{{ route('projects.create') }}" class="btn btn-success">{{ __('messages.projects.create_title') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="projects-table">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.common.name') }}</th>
                                    <th>{{ __('messages.common.base_url') }}</th>
                                    <th>{{ __('messages.projects.environments') }}</th>
                                    <th>{{ __('messages.projects.auth_profiles') }}</th>
                                    <th>{{ __('messages.projects.endpoints') }}</th>
                                    <th>{{ __('messages.common.status') }}</th>
                                    <th>{{ __('messages.common.created') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($projects as $project)
                                    <tr>
                                        <td><strong>{{ $project->name }}</strong><br><small class="text-muted">{{ $project->slug }}</small></td>
                                        <td><code>{{ $project->display_base_url }}</code></td>
                                        <td><span class="badge badge-soft">{{ $project->environments_count }}</span></td>
                                        <td><span class="badge badge-soft">{{ $project->auth_profiles_count }}</span></td>
                                        <td><span class="badge badge-soft">{{ $project->endpoints_count }}</span></td>
                                        <td>{!! $project->is_active ? '<span class="label label-success">'.__('messages.common.active').'</span>' : '<span class="label label-default">'.__('messages.common.inactive').'</span>' !!}</td>
                                        <td>{{ $project->created_at?->format('Y-m-d H:i') }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a>
                                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $projects->links() }}
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        if ($('#projects-table').length) {
            $('#projects-table').DataTable({
                paging: false,
                searching: true,
                info: false,
                order: [[6, 'desc']]
            });
        }
    });
</script>
@endpush
