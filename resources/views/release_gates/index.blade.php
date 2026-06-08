@extends('layouts.app')

@section('title', __('messages.release_gates.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel h{{ $evaluation['automated_css'] === 'danger' ? 'red' : ($evaluation['automated_css'] === 'warning' ? 'yellow' : 'green') }}">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.release-gates.create', $project) }}" class="btn btn-xs btn-danger">{{ __('messages.release_gates.create') }}</a>
                    <a href="{{ route('projects.release-readiness.show', $project) }}" class="btn btn-xs btn-default">{{ __('messages.release_readiness.title') }}</a>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-xs btn-info">{{ __('messages.projects.details') }}</a>
                </div>
                {{ __('messages.release_gates.title') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.release_gates.intro') }}</p>
                <div class="row text-center">
                    <div class="col-sm-2"><h2><span class="label label-{{ $evaluation['automated_css'] }}">{{ $evaluation['automated_label'] }}</span></h2><small>{{ __('messages.release_gates.current_gate') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $evaluation['summary']['score'] ?? 0 }}</h2><small>{{ __('messages.release_readiness.score') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $evaluation['summary']['qa_coverage']['coverage_percent'] ?? 0 }}%</h2><small>{{ __('messages.qa_coverage.short_title') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $evaluation['summary']['test_execution']['execution_percent'] ?? 0 }}%</h2><small>{{ __('messages.test_execution.execution_coverage') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $evaluation['counts']['blockers'] }}</h2><small>{{ __('messages.release_gates.blockers') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $evaluation['counts']['warnings'] }}</h2><small>{{ __('messages.release_gates.warnings') }}</small></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <strong>{{ __('messages.release_gates.blockers') }}</strong>
                        @if(empty($evaluation['blockers']))
                            <p class="text-muted">{{ __('messages.release_gates.no_blockers') }}</p>
                        @else
                            <ul>
                                @foreach(array_slice($evaluation['blockers'], 0, 5) as $item)
                                    <li>{{ $item['message'] ?: $item['title'] }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <strong>{{ __('messages.release_gates.warnings') }}</strong>
                        @if(empty($evaluation['warnings']))
                            <p class="text-muted">{{ __('messages.release_gates.no_warnings') }}</p>
                        @else
                            <ul>
                                @foreach(array_slice($evaluation['warnings'], 0, 5) as $item)
                                    <li>{{ $item['message'] ?: $item['title'] }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
                <a href="{{ route('projects.release-gates.create', $project) }}" class="btn btn-danger btn-sm">{{ __('messages.release_gates.save_gate_snapshot') }}</a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.release_gates.history') }}</div>
            <div class="panel-body no-padding">
                @if($releaseGates->isEmpty())
                    <div class="p-md text-center">
                        <p class="text-muted">{{ __('messages.release_gates.empty') }}</p>
                        <a href="{{ route('projects.release-gates.create', $project) }}" class="btn btn-danger btn-sm">{{ __('messages.release_gates.create') }}</a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed m-b-none">
                            <thead><tr><th>{{ __('messages.release_gates.release_name') }}</th><th>{{ __('messages.release_gates.automated_status') }}</th><th>{{ __('messages.release_gates.final_decision') }}</th><th>{{ __('messages.release_readiness.score') }}</th><th>{{ __('messages.release_gates.blockers') }}</th><th>{{ __('messages.release_gates.warnings') }}</th><th>{{ __('messages.common.created') }}</th><th></th></tr></thead>
                            <tbody>
                            @foreach($releaseGates as $gate)
                                <tr>
                                    <td><strong>{{ $gate->release_name }}</strong><br><small class="text-muted">{{ $gate->target_environment ?: __('messages.common.none') }}</small></td>
                                    <td><span class="label label-{{ $gate->automated_status_css }}">{{ $gate->automated_status_label }}</span></td>
                                    <td><span class="label label-{{ $gate->final_decision_css }}">{{ $gate->final_decision_label }}</span></td>
                                    <td>{{ $gate->score }}</td>
                                    <td>{{ $gate->blocker_count }}</td>
                                    <td>{{ $gate->warning_count }}</td>
                                    <td>{{ $gate->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="text-right"><a href="{{ route('projects.release-gates.show', [$project, $gate]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            @if($releaseGates->hasPages())
                <div class="panel-footer">{{ $releaseGates->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
