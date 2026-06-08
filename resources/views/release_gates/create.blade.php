@extends('layouts.app')

@section('title', __('messages.release_gates.create'))

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools"><a href="{{ route('projects.release-gates.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a></div>
                {{ __('messages.release_gates.create') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.release-gates.store', $project) }}">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('messages.release_gates.release_name') }}</label>
                        <input type="text" name="release_name" value="{{ old('release_name', $releaseGate->release_name) }}" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('messages.release_gates.target_environment') }}</label>
                                <input type="text" name="target_environment" value="{{ old('target_environment', $releaseGate->target_environment) }}" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('messages.release_gates.gate_profile') }}</label>
                                <select name="gate_profile" class="form-control">
                                    @foreach(\App\Models\QaReleaseGate::PROFILES as $profile)
                                        <option value="{{ $profile }}" @selected(old('gate_profile', $releaseGate->gate_profile) === $profile)>{{ __('messages.release_gates.profiles.'.$profile) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('messages.release_gates.final_decision') }}</label>
                                <select name="final_decision" class="form-control">
                                    @foreach(\App\Models\QaReleaseGate::DECISIONS as $decision)
                                        <option value="{{ $decision }}" @selected(old('final_decision', $releaseGate->final_decision) === $decision)>{{ __('messages.release_gates.decisions.'.$decision) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('messages.release_gates.reviewed_by') }}</label>
                                <input type="text" name="reviewed_by" value="{{ old('reviewed_by') }}" class="form-control" placeholder="{{ __('messages.release_gates.reviewer_placeholder') }}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.release_gates.decision_notes') }}</label>
                        <textarea name="decision_notes" class="form-control" rows="5" placeholder="{{ __('messages.release_gates.decision_notes_placeholder') }}">{{ old('decision_notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">{{ __('messages.release_gates.save_gate_snapshot') }}</button>
                    <a href="{{ route('projects.release-gates.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="hpanel h{{ $evaluation['automated_css'] === 'danger' ? 'red' : ($evaluation['automated_css'] === 'warning' ? 'yellow' : 'green') }}">
            <div class="panel-heading hbuilt">{{ __('messages.release_gates.preview') }}</div>
            <div class="panel-body text-center">
                <h2><span class="label label-{{ $evaluation['automated_css'] }}">{{ $evaluation['automated_label'] }}</span></h2>
                <p class="text-muted">{{ __('messages.release_gates.preview_help') }}</p>
                <div class="row">
                    <div class="col-xs-4"><h3>{{ $evaluation['summary']['score'] ?? 0 }}</h3><small>{{ __('messages.release_readiness.score') }}</small></div>
                    <div class="col-xs-4"><h3>{{ $evaluation['counts']['blockers'] }}</h3><small>{{ __('messages.release_gates.blockers') }}</small></div>
                    <div class="col-xs-4"><h3>{{ $evaluation['counts']['warnings'] }}</h3><small>{{ __('messages.release_gates.warnings') }}</small></div>
                </div>
            </div>
        </div>
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.release_gates.evidence_snapshot') }}</div>
            <div class="panel-body">
                <ul class="m-b-none">
                    @foreach($evaluation['evidence'] as $item)
                        <li>{{ $item['message'] ?: $item['title'] }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
