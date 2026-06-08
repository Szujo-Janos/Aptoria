@extends('layouts.app')

@section('title', __('messages.release_gates.title'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel h{{ $releaseGate->automated_status_css === 'danger' ? 'red' : ($releaseGate->automated_status_css === 'warning' ? 'yellow' : 'green') }}">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.release-gates.markdown', [$project, $releaseGate]) }}" class="btn btn-xs btn-primary">{{ __('messages.release_gates.download_markdown') }}</a>
                    <a href="{{ route('projects.release-gates.create', $project) }}" class="btn btn-xs btn-danger">{{ __('messages.release_gates.create') }}</a>
                    <a href="{{ route('projects.release-gates.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.release_gates.title') }} — {{ $releaseGate->release_name }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-2"><h2><span class="label label-{{ $releaseGate->automated_status_css }}">{{ $releaseGate->automated_status_label }}</span></h2><small>{{ __('messages.release_gates.automated_status') }}</small></div>
                    <div class="col-sm-2"><h2><span class="label label-{{ $releaseGate->final_decision_css }}">{{ $releaseGate->final_decision_label }}</span></h2><small>{{ __('messages.release_gates.final_decision') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $releaseGate->score }}</h2><small>{{ __('messages.release_readiness.score') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $releaseGate->blocker_count }}</h2><small>{{ __('messages.release_gates.blockers') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $releaseGate->warning_count }}</h2><small>{{ __('messages.release_gates.warnings') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $releaseGate->evidence_count }}</h2><small>{{ __('messages.release_gates.evidence_items') }}</small></div>
                </div>
                <hr>
                <dl class="dl-horizontal">
                    <dt>{{ __('messages.release_gates.target_environment') }}</dt><dd>{{ $releaseGate->target_environment ?: __('messages.common.none') }}</dd>
                    <dt>{{ __('messages.release_gates.gate_profile') }}</dt><dd>{{ $releaseGate->profile_label }}</dd>
                    <dt>{{ __('messages.release_gates.reviewed_by') }}</dt><dd>{{ $releaseGate->reviewed_by ?: __('messages.common.none') }}</dd>
                    <dt>{{ __('messages.release_gates.reviewed_at') }}</dt><dd>{{ $releaseGate->reviewed_at?->format('Y-m-d H:i') ?: __('messages.common.none') }}</dd>
                    <dt>{{ __('messages.common.created') }}</dt><dd>{{ $releaseGate->created_at->format('Y-m-d H:i') }}</dd>
                </dl>
                @if($releaseGate->decision_notes)
                    <div class="alert alert-info"><strong>{{ __('messages.release_gates.decision_notes') }}:</strong><br>{{ $releaseGate->decision_notes }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.release_gates.update_decision') }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.release-gates.decision.update', [$project, $releaseGate]) }}">
                    @csrf
                    @method('PATCH')
                    <div class="row">
                        <div class="col-md-3">
                            <label>{{ __('messages.release_gates.final_decision') }}</label>
                            <select name="final_decision" class="form-control">
                                @foreach(\App\Models\QaReleaseGate::DECISIONS as $decision)
                                    <option value="{{ $decision }}" @selected(old('final_decision', $releaseGate->final_decision) === $decision)>{{ __('messages.release_gates.decisions.'.$decision) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>{{ __('messages.release_gates.reviewed_by') }}</label>
                            <input type="text" name="reviewed_by" value="{{ old('reviewed_by', $releaseGate->reviewed_by) }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>{{ __('messages.release_gates.decision_notes') }}</label>
                            <textarea name="decision_notes" class="form-control" rows="2">{{ old('decision_notes', $releaseGate->decision_notes) }}</textarea>
                        </div>
                    </div>
                    <br>
                    <button class="btn btn-primary" type="submit">{{ __('messages.common.update') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach(['blockers' => 'hred', 'warnings' => 'hyellow', 'evidence' => 'hblue', 'recommendations' => 'hgreen'] as $relation => $panelClass)
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel {{ $panelClass }}">
            <div class="panel-heading hbuilt">{{ __('messages.release_gates.sections.'.$relation) }}</div>
            <div class="panel-body no-padding">
                @php($items = $releaseGate->{$relation})
                @if($items->isEmpty())
                    <div class="p-md"><p class="text-muted m-b-none">{{ __('messages.release_gates.no_items') }}</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed m-b-none">
                            <thead><tr><th>{{ __('messages.release_gates.severity') }}</th><th>{{ __('messages.release_gates.source') }}</th><th>{{ __('messages.common.summary') }}</th><th>{{ __('messages.endpoints.title') }}</th></tr></thead>
                            <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td><span class="label label-{{ $item->severity_css }}">{{ $item->severity_label }}</span></td>
                                    <td>{{ $item->source }}</td>
                                    <td><strong>{{ $item->title }}</strong><br><small class="text-muted">{{ $item->message }}</small></td>
                                    <td>@if($item->endpoint)<a href="{{ route('projects.endpoints.show', [$project, $item->endpoint]) }}"><code>{{ $item->endpoint->method }} {{ $item->endpoint->path }}</code></a>@else<span class="text-muted">{{ __('messages.common.none') }}</span>@endif</td>
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
@endforeach
@endsection
