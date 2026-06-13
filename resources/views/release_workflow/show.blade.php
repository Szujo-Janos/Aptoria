@extends('layouts.app')

@section('title', __('messages.release_workflow.title'))

@section('page_actions')
    <a href="{{ route('projects.qa-cockpit.index', $project) }}" class="btn btn-primary btn-sm"><i class="fa fa-tasks"></i> {{ __('messages.qa_cockpit.short_title') }}</a>
    <a href="{{ route('projects.release-decisions.index', $project) }}" class="btn btn-default btn-sm"><i class="fa fa-gavel"></i> {{ __('messages.release_decisions.short_title') }}</a>
@endsection

@section('content')
@php
    $summary = $workflow['summary'];
    $latest = $workflow['latest'];
    $precheck = $workflow['precheck'];
    $next = $workflow['next_action'];
    $canFinalize = $aptoriaCurrentProjectPermissions['release.finalize'] ?? false;
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-body">
                <h2 class="m-t-none">{{ __('messages.release_workflow.title') }}</h2>
                <p class="text-muted m-b-none">{{ __('messages.release_workflow.subtitle') }}</p>
            </div>
        </div>
    </div>
</div>

    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="hpanel hgreen">
                <div class="panel-body text-center">
                    <h2 class="m-xs">{{ $summary['progress_percent'] }}%</h2>
                    <small>{{ __('messages.release_workflow.summary.progress') }}</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="hpanel h{{ $summary['blocked'] > 0 ? 'red' : 'blue' }}">
                <div class="panel-body text-center">
                    <h2 class="m-xs">{{ $summary['blocked'] }}</h2>
                    <small>{{ __('messages.release_workflow.summary.blocked') }}</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="hpanel hyellow">
                <div class="panel-body text-center">
                    <h2 class="m-xs">{{ $summary['missing_evidence_count'] }}</h2>
                    <small>{{ __('messages.release_workflow.summary.missing_evidence') }}</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="hpanel h{{ $summary['overall_state_css'] === 'danger' ? 'red' : ($summary['overall_state_css'] === 'warning' ? 'yellow' : 'green') }}">
                <div class="panel-body text-center">
                    <h2 class="m-xs">{{ $summary['overall_state_label'] }}</h2>
                    <small>{{ __('messages.release_workflow.summary.overall_state') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="hpanel hblue">
                <div class="panel-heading hbuilt">
                    {{ __('messages.release_workflow.release_flow') }}
                    <span class="pull-right text-muted">{{ $summary['completed'] + $summary['skipped_with_reason'] }} / {{ $summary['total'] }}</span>
                </div>
                <div class="panel-body">
                    <div class="progress m-b-md">
                        <div class="progress-bar progress-bar-success" style="width: {{ $summary['progress_percent'] }}%" role="progressbar" aria-valuenow="{{ $summary['progress_percent'] }}" aria-valuemin="0" aria-valuemax="100">
                            {{ $summary['progress_percent'] }}%
                        </div>
                    </div>
                    <table class="table table-striped m-b-none">
                        <thead>
                        <tr>
                            <th style="width: 130px;">{{ __('messages.common.status') }}</th>
                            <th>{{ __('messages.release_workflow.workflow_step') }}</th>
                            <th style="width: 150px;">{{ __('messages.release_workflow.signal') }}</th>
                            <th style="width: 210px;"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($workflow['steps'] as $step)
                            <tr>
                                <td>
                                    <span class="label label-{{ $step['state_css'] }}">{{ $step['state_label'] }}</span>
                                    @if($step['is_manual'])
                                        <br><small class="text-muted"><i class="fa fa-flag"></i> {{ __('messages.release_workflow.manual_override') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $step['label'] }}</strong><br>
                                    <small class="text-muted">{{ $step['description'] }}</small>
                                    @if(! empty($step['blocker_reasons']))
                                        <ul class="m-t-xs m-b-none text-danger">
                                            @foreach($step['blocker_reasons'] as $reason)
                                                <li>{{ $reason }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if(! empty($step['completion_criteria']))
                                        <div class="m-t-xs">
                                            <small class="text-muted"><strong>{{ __('messages.release_workflow.completion_criteria') }}:</strong> {{ implode(' · ', $step['completion_criteria']) }}</small>
                                        </div>
                                    @endif
                                    @if($step['manual_reason'])
                                        <div class="alert alert-warning m-t-sm m-b-none p-xs">
                                            <strong>{{ __('messages.release_workflow.skip_reason') }}:</strong> {{ $step['manual_reason'] }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $step['value'] }}</strong><br>
                                    <small class="text-muted">{{ $step['required_action'] }}</small>
                                </td>
                                <td class="text-right">
                                    <a class="btn btn-xs btn-default" href="{{ $step['url'] }}"><i class="fa fa-external-link"></i> {{ __('messages.common.open') }}</a>
                                    @if($canFinalize && $step['state'] !== \App\Models\ReleaseWorkflow::STATE_COMPLETED)
                                        @if($step['is_manual'])
                                            <form method="POST" action="{{ route('projects.release-workflow.steps.reopen', [$project, $step['key']]) }}" class="inline-form m-t-xs">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-xs btn-warning"><i class="fa fa-undo"></i> {{ __('messages.release_workflow.reopen_step') }}</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('projects.release-workflow.steps.skip', [$project, $step['key']]) }}" class="m-t-xs">
                                                @csrf
                                                @method('PATCH')
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="reason" class="form-control" placeholder="{{ __('messages.release_workflow.skip_reason_placeholder') }}" required minlength="8">
                                                    <span class="input-group-btn">
                                                        <button type="submit" class="btn btn-warning"><i class="fa fa-forward"></i></button>
                                                    </span>
                                                </div>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="hpanel h{{ $precheck['passed'] ? 'green' : 'red' }}">
                <div class="panel-heading hbuilt">{{ __('messages.release_workflow.precheck_title') }}</div>
                <div class="panel-body">
                    @if($precheck['passed'])
                        <p class="text-success"><i class="fa fa-check-circle"></i> {{ __('messages.release_workflow.precheck_passed') }}</p>
                    @else
                        <p class="text-danger"><i class="fa fa-exclamation-triangle"></i> {{ trans_choice('messages.release_workflow.precheck_failed', $precheck['failure_count'], ['count' => $precheck['failure_count']]) }}</p>
                        <ul class="m-b-none">
                            @foreach(array_slice($precheck['failures'], 0, 6) as $failure)
                                <li><strong>{{ $failure['label'] }}</strong>: {{ $failure['required_action'] }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="hpanel hyellow">
                <div class="panel-heading hbuilt">{{ __('messages.release_workflow.next_best_action') }}</div>
                <div class="panel-body">
                    @if($next)
                        <p><span class="label label-{{ $next['state_css'] }}">{{ $next['state_label'] }}</span></p>
                        <p><strong>{{ $next['label'] }}</strong></p>
                        <p class="text-muted">{{ $next['required_action'] }}</p>
                        <a href="{{ $next['url'] }}" class="btn btn-warning btn-sm"><i class="fa fa-arrow-right"></i> {{ __('messages.release_workflow.continue_here') }}</a>
                    @else
                        <p class="text-muted m-b-none">{{ __('messages.release_workflow.all_clear') }}</p>
                    @endif
                </div>
            </div>

            <div class="hpanel hgreen">
                <div class="panel-heading hbuilt">{{ __('messages.release_workflow.latest_decision_package') }}</div>
                <div class="panel-body">
                    <dl class="dl-horizontal m-b-none">
                        <dt>{{ __('messages.scans.short_title') }}</dt><dd>@if($latest['scan']) #{{ $latest['scan']->id }} — {{ $latest['scan']->status_label }} @else {{ __('messages.common.not_available') }} @endif</dd>
                        <dt>{{ __('messages.release_gates.short_title') }}</dt><dd>@if($latest['release_gate']) {{ $latest['release_gate']->release_name ?: '#'.$latest['release_gate']->id }} — {{ $latest['release_gate']->final_decision_label }} @else {{ __('messages.common.not_available') }} @endif</dd>
                        <dt>{{ __('messages.release_decisions.short_title') }}</dt><dd>@if($latest['release_decision']) {{ $latest['release_decision']->release_name ?: '#'.$latest['release_decision']->id }} — {{ $latest['release_decision']->status_label }} @else {{ __('messages.common.not_available') }} @endif</dd>
                        <dt>{{ __('messages.report_versions.latest_approved') }}</dt><dd>@if($latest['approved_report']) {{ $latest['approved_report']->title }} @else {{ __('messages.common.not_available') }} @endif</dd>
                        <dt>{{ __('messages.client_portal.short_title') }}</dt><dd>@if($latest['client_portal']) {{ $latest['client_portal']->label }} — {{ $latest['client_portal']->status_label }} @else {{ __('messages.common.not_available') }} @endif</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
