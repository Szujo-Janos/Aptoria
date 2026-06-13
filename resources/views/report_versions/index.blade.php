@extends('layouts.app')

@section('title', __('messages.report_versions.title'))

@section('content')
@php($projectPermissions = app(\App\Services\Access\ProjectAccessService::class)->permissionMap($project, request()->user()))
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.reports.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.report_versions.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.report_versions.intro') }}</p>
                @if(($projectPermissions['report.generate'] ?? false))
                    <form method="POST" action="{{ route('projects.report-versions.store', $project) }}" class="form-inline">
                        @csrf
                        <div class="form-group m-r-sm">
                            <label class="sr-only" for="title">{{ __('messages.common.title') }}</label>
                            <input type="text" id="title" name="title" value="{{ old('title') }}" class="form-control" placeholder="{{ __('messages.report_versions.title_placeholder') }}">
                        </div>
                        <div class="form-group m-r-sm">
                            <label class="sr-only" for="report_type">{{ __('messages.report_versions.report_type') }}</label>
                            <select name="report_type" id="report_type" class="form-control">
                                @foreach(\App\Models\ReportVersion::TYPES as $type)
                                    <option value="{{ $type }}">{{ __('messages.report_versions.types.'.$type) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('messages.report_versions.create_draft') }}</button>
                    </form>
                @else
                    <div class="alert alert-warning m-b-none">{{ __('messages.project_members.manage_restricted') }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.report_versions.history') }}</div>
            <div class="panel-body no-padding">
                @if($versions->isEmpty())
                    <div class="p-md"><p class="text-muted m-b-none">{{ __('messages.report_versions.empty') }}</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed m-b-none">
                            <thead>
                            <tr>
                                <th>{{ __('messages.common.title') }}</th>
                                <th>{{ __('messages.report_versions.report_type') }}</th>
                                <th>{{ __('messages.common.status') }}</th>
                                <th>{{ __('messages.report_versions.checksum') }}</th>
                                <th>{{ __('messages.report_versions.generated_by') }}</th>
                                <th>{{ __('messages.common.created') }}</th>
                                <th class="text-right">{{ __('messages.common.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($versions as $version)
                                <tr>
                                    <td><strong>{{ $version->title }}</strong></td>
                                    <td>{{ $version->type_label }}</td>
                                    <td><span class="label label-{{ $version->status_css }}">{{ $version->status_label }}</span></td>
                                    <td><code>{{ $version->short_checksum }}</code></td>
                                    <td>{{ $version->generatedBy?->name ?: '—' }}</td>
                                    <td>{{ $version->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="text-right"><a href="{{ route('projects.report-versions.show', [$project, $version]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.details') }}</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="p-sm">{{ $versions->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
