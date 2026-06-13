@extends('layouts.app')

@section('title', __('messages.release_decisions.title'))

@section('content')
@php
    $projectPermissions = app(\App\Services\Access\ProjectAccessService::class)->permissionMap($project, request()->user());
    $summary = $roomSummary['summary'];
    $latestDecision = $roomSummary['latest_decision'] ?? null;
    $package = $roomSummary['current_package'];
    $readiness = $package['release_readiness'] ?? [];
    $acceptedRiskSummary = $package['accepted_risk_summary'] ?? [];
    $blindSpotSummary = $package['blind_spot_summary'] ?? [];
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel h{{ $roomSummary['recommended_status_css'] === 'danger' ? 'red' : ($roomSummary['recommended_status_css'] === 'warning' ? 'yellow' : 'blue') }}">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.release-readiness.show', $project) }}" class="btn btn-xs btn-default"><i class="fa fa-check-circle"></i> {{ __('messages.nav.release_readiness_short') }}</a>
                    <a href="{{ route('projects.release-gates.index', $project) }}" class="btn btn-xs btn-default"><i class="fa fa-flag-checkered"></i> {{ __('messages.release_gates.short_title') }}</a>
                </div>
                {{ __('messages.release_decisions.heading') }} — {{ $project->name }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-2"><h2><span class="label label-{{ $roomSummary['recommended_status_css'] }}">{{ $roomSummary['recommended_status_label'] }}</span></h2><small>{{ __('messages.release_decisions.recommended_decision') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $summary['score'] ?? 0 }}</h2><small>{{ __('messages.release_decisions.release_score') }}</small></div>
                    <div class="col-sm-2"><h2>{{ count($summary['blocking_issues'] ?? []) }}</h2><small>{{ __('messages.release_decisions.blockers') }}</small></div>
                    <div class="col-sm-2"><h2>{{ count($summary['warnings'] ?? []) }}</h2><small>{{ __('messages.release_decisions.warnings') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $blindSpotSummary['total'] ?? 0 }}</h2><small>{{ __('messages.release_decisions.blind_spots') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $acceptedRiskSummary['active'] ?? 0 }}</h2><small>{{ __('messages.release_decisions.accepted_risks') }}</small></div>
                </div>
                <hr>
                <p class="text-muted m-b-none">{{ __('messages.release_decisions.intro') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.release_decisions.finalize_title') }}</div>
            <div class="panel-body">
                @if(($projectPermissions['release.finalize'] ?? false))
                <form method="POST" action="{{ route('projects.release-decisions.store', $project) }}">
                    @csrf
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ __('messages.release_decisions.release_name') }}</label>
                                <input type="text" class="form-control" name="release_name" value="{{ old('release_name', $project->name.' '.now()->format('Y-m-d').' release decision') }}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ __('messages.release_decisions.target_environment') }}</label>
                                <input type="text" class="form-control" name="target_environment" value="{{ old('target_environment', $project->defaultEnvironment()?->name) }}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.release_decisions.decision_status') }}</label>
                        <div class="row">
                            @foreach($roomSummary['status_options'] as $status)
                                <div class="col-sm-4 m-b-sm">
                                    <label class="radio-inline">
                                        <input type="radio" name="decision_status" value="{{ $status }}" {{ old('decision_status', $roomSummary['recommended_status']) === $status ? 'checked' : '' }}>
                                        {{ __('messages.release_decisions.statuses.'.$status) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group">
                        <label>{{ __('messages.release_decisions.decision_notes') }}</label>
                        <textarea class="form-control" name="decision_notes" rows="5" placeholder="{{ __('messages.release_decisions.decision_notes_placeholder') }}">{{ old('decision_notes') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-lock"></i> {{ __('messages.release_decisions.finalize_button') }}</button>
                    <span class="text-muted m-l-sm">{{ __('messages.release_decisions.package_checksum') }}: <code>{{ \Illuminate\Support\Str::limit($roomSummary['package_checksum'], 16, '') }}</code></span>
                </form>
                @else
                    <div class="alert alert-warning m-b-none">{{ __('messages.project_members.manage_restricted') }}</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.release_decisions.latest_decision') }}</div>
            <div class="panel-body">
                @php($latestDecision = $latestDecision ?? ($roomSummary['latest_decision'] ?? null))
                @if($latestDecision)
                    <h4><span class="label label-{{ $latestDecision->status_css }}">{{ $latestDecision->status_label }}</span></h4>
                    <p><strong>{{ __('messages.release_decisions.release_name') }}:</strong> {{ $latestDecision->release_name ?: __('messages.common.not_available') }}</p>
                    <p><strong>{{ __('messages.release_decisions.decision_owner') }}:</strong> {{ $latestDecision->owner?->name ?: __('messages.common.not_available') }}</p>
                    <p><strong>{{ __('messages.release_decisions.decision_timestamp') }}:</strong> {{ $latestDecision->decided_at?->format('Y-m-d H:i') ?: __('messages.release_decisions.pending') }}</p>
                    <a class="btn btn-xs btn-primary" href="{{ route('projects.release-decisions.show', [$project, $latestDecision]) }}">{{ __('messages.common.details') }}</a>
                @else
                    <p class="text-muted m-b-none">{{ __('messages.release_decisions.no_decisions') }}</p>
                @endif
            </div>
        </div>
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.release_decisions.evidence_chain') }}</div>
            <div class="panel-body">
                <dl class="dl-horizontal m-b-none">
                    <dt>{{ __('messages.release_decisions.latest_scan') }}</dt><dd>#{{ $package['evidence_ids']['latest_scan_run_id'] ?? 'n/a' }}</dd>
                    <dt>{{ __('messages.release_decisions.latest_snapshot') }}</dt><dd>#{{ $package['evidence_ids']['latest_snapshot_id'] ?? 'n/a' }}</dd>
                    <dt>{{ __('messages.release_decisions.latest_compare') }}</dt><dd>#{{ $package['evidence_ids']['latest_compare_run_id'] ?? 'n/a' }}</dd>
                    <dt>{{ __('messages.release_decisions.latest_contract') }}</dt><dd>#{{ $package['evidence_ids']['latest_contract_validation_run_id'] ?? 'n/a' }}</dd>
                    <dt>{{ __('messages.release_decisions.latest_gate') }}</dt><dd>#{{ $package['evidence_ids']['latest_release_gate_id'] ?? 'n/a' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.release_decisions.decision_inputs') }}</div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>{{ __('messages.release_decisions.blockers') }}</h4>
                        @forelse(($summary['blocking_issues'] ?? []) as $issue)
                            <div class="alert alert-danger p-xs">{{ $issue }}</div>
                        @empty
                            <p class="text-muted">{{ __('messages.release_decisions.no_blockers') }}</p>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <h4>{{ __('messages.release_decisions.warnings') }}</h4>
                        @forelse(($summary['warnings'] ?? []) as $warning)
                            <div class="alert alert-warning p-xs">{{ $warning }}</div>
                        @empty
                            <p class="text-muted">{{ __('messages.release_decisions.no_warnings') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.release_decisions.history') }}</div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-condensed m-b-none">
                        <thead>
                        <tr>
                            <th>{{ __('messages.release_decisions.decision_status') }}</th>
                            <th>{{ __('messages.release_decisions.release_name') }}</th>
                            <th>{{ __('messages.release_decisions.decision_owner') }}</th>
                            <th>{{ __('messages.release_decisions.release_score') }}</th>
                            <th>{{ __('messages.release_decisions.blockers') }}</th>
                            <th>{{ __('messages.release_decisions.decision_timestamp') }}</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($decisions as $decision)
                            <tr>
                                <td><span class="label label-{{ $decision->status_css }}">{{ $decision->status_label }}</span></td>
                                <td>{{ $decision->release_name }}</td>
                                <td>{{ $decision->owner?->name ?: __('messages.common.not_available') }}</td>
                                <td>{{ $decision->release_score }}/100</td>
                                <td>{{ $decision->blocker_count }}</td>
                                <td>{{ $decision->decided_at?->format('Y-m-d H:i') ?: __('messages.release_decisions.pending') }}</td>
                                <td><a href="{{ route('projects.release-decisions.show', [$project, $decision]) }}" class="btn btn-xs btn-default">{{ __('messages.common.details') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">{{ __('messages.release_decisions.no_decisions') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $decisions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
