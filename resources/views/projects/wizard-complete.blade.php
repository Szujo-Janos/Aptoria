@extends('layouts.app')

@section('title', __('messages.wizard.complete_title'))

@section('page_actions')
    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-primary"><i class="fa fa-briefcase"></i> {{ __('messages.wizard.view_project') }}</a>
    <a href="{{ route('projects.reports.full-project.html', $project) }}" class="btn btn-sm btn-default"><i class="fa fa-file-code-o"></i> HTML</a>
    <a href="{{ route('projects.reports.full-project.pdf', $project) }}" class="btn btn-sm btn-default"><i class="fa fa-file-pdf-o"></i> PDF</a>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">
                <span class="label label-success pull-right">{{ __('messages.wizard.completion_badge') }}</span>
                {{ __('messages.wizard.complete_title') }}
            </div>
            <div class="panel-body">
                <h3 class="m-t-none">{{ __('messages.wizard.complete_heading') }}</h3>
                <p class="text-muted">{{ __('messages.wizard.complete_intro') }}</p>

                <div class="row text-center m-t-md">
                    <div class="col-md-3 col-sm-6 m-b-sm">
                        <div class="well well-sm m-b-none">
                            <h3 class="m-xs">{{ $project->endpoints_count }}</h3>
                            <small>{{ __('messages.wizard.summary_endpoints') }}</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 m-b-sm">
                        <div class="well well-sm m-b-none">
                            <h3 class="m-xs">{{ $project->environments->count() }}</h3>
                            <small>{{ __('messages.wizard.summary_environments') }}</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 m-b-sm">
                        <div class="well well-sm m-b-none">
                            <h3 class="m-xs">{{ $project->authProfiles->count() }}</h3>
                            <small>{{ __('messages.wizard.summary_auth_profiles') }}</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 m-b-sm">
                        <div class="well well-sm m-b-none">
                            <h3 class="m-xs">{{ $importedEndpoints ?: $project->endpoints_count }}</h3>
                            <small>{{ __('messages.wizard.summary_imported') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.wizard.onboarding_results') }}</div>
            <div class="panel-body no-padding">
                <div class="table-responsive">
                    <table class="table table-striped m-b-none">
                        <tbody>
                            <tr>
                                <td><strong>{{ __('messages.wizard.first_scan') }}</strong></td>
                                <td>
                                    @if($scanRun)
                                        <span class="label label-{{ $scanRun->status_css }}">{{ $scanRun->status_label }}</span>
                                        <span class="text-muted">#{{ $scanRun->id }} · {{ $scanRun->scanned_count ?? 0 }} / {{ $scanRun->total_endpoints ?? 0 }}</span>
                                    @else
                                        <span class="label label-default">{{ __('messages.wizard.not_created') }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($scanRun)
                                        <a href="{{ route('projects.scans.show', [$project, $scanRun]) }}" class="btn btn-xs btn-default"><i class="fa fa-eye"></i> {{ __('messages.wizard.view_scan') }}</a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('messages.wizard.first_snapshot') }}</strong></td>
                                <td>
                                    @if($snapshot)
                                        <span class="label label-info">{{ __('messages.wizard.created_status') }}</span>
                                        <span class="text-muted">#{{ $snapshot->id }} · {{ $snapshot->endpoint_count }} {{ __('messages.nav.endpoints') }}</span>
                                    @else
                                        <span class="label label-default">{{ __('messages.wizard.not_created') }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($snapshot)
                                        <a href="{{ route('projects.snapshots.show', [$project, $snapshot]) }}" class="btn btn-xs btn-default"><i class="fa fa-camera"></i> {{ __('messages.wizard.view_snapshot') }}</a>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td><strong>{{ __('messages.wizard.first_report') }}</strong></td>
                                <td>
                                    @if($reportGenerated)
                                        <span class="label label-success">{{ __('messages.wizard.report_generated') }}</span>
                                        <span class="text-muted">{{ __('messages.wizard.report_ready') }}</span>
                                    @else
                                        <span class="label label-default">{{ __('messages.wizard.not_generated') }}</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="btn-group">
                                        <a href="{{ route('projects.reports.full-project.markdown', $project) }}" class="btn btn-xs btn-default">MD</a>
                                        <a href="{{ route('projects.reports.full-project.html', $project) }}" class="btn btn-xs btn-default">HTML</a>
                                        <a href="{{ route('projects.reports.full-project.pdf', $project) }}" class="btn btn-xs btn-default">PDF</a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.wizard.next_steps') }}</div>
            <div class="panel-body">
                <ol class="m-b-none">
                    @foreach(__('messages.wizard.next_steps_items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.wizard.quick_actions') }}</div>
            <div class="panel-body">
                <a href="{{ route('projects.show', $project) }}" class="btn btn-primary btn-block"><i class="fa fa-briefcase"></i> {{ __('messages.wizard.view_project') }}</a>
                <a href="{{ route('projects.endpoints.index', $project) }}" class="btn btn-default btn-block"><i class="fa fa-sitemap"></i> {{ __('messages.nav.endpoints') }}</a>
                <a href="{{ route('projects.scans.index', $project) }}" class="btn btn-default btn-block"><i class="fa fa-crosshairs"></i> {{ __('messages.nav.scans') }}</a>
                <a href="{{ route('projects.snapshots.index', $project) }}" class="btn btn-default btn-block"><i class="fa fa-camera"></i> {{ __('messages.snapshots.title') }}</a>
                <a href="{{ route('projects.reports.index', $project) }}" class="btn btn-default btn-block"><i class="fa fa-file-text-o"></i> {{ __('messages.nav.reports') }}</a>
            </div>
        </div>

        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.wizard.safety_note_title') }}</div>
            <div class="panel-body">
                <p class="m-b-none text-muted">{{ __('messages.wizard.safety_note_body') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
