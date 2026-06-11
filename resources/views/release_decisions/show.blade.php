@extends('layouts.app')

@section('title', __('messages.release_decisions.package_title'))

@section('content')
@php
    $package = $releaseDecision->decision_package_json ?? [];
    $readiness = $package['release_readiness'] ?? [];
    $ids = $package['evidence_ids'] ?? [];
    $findingState = $package['finding_state_snapshot'] ?? [];
    $blindSpots = $package['blind_spots'] ?? [];
    $riskRows = $package['accepted_risk_ledger'] ?? [];
@endphp
<div class="row">
    <div class="col-lg-12">
        <div class="hpanel h{{ $releaseDecision->status_css === 'danger' ? 'red' : ($releaseDecision->status_css === 'warning' ? 'yellow' : 'green') }}">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.release-decisions.index', $project) }}" class="btn btn-xs btn-default"><i class="fa fa-arrow-left"></i> {{ __('messages.release_decisions.back_to_room') }}</a>
                    <a href="{{ route('projects.release-decisions.markdown', [$project, $releaseDecision]) }}" class="btn btn-xs btn-primary">MD</a>
                    <a href="{{ route('projects.release-decisions.html', [$project, $releaseDecision]) }}" class="btn btn-xs btn-info">HTML</a>
                    <a href="{{ route('projects.release-decisions.pdf', [$project, $releaseDecision]) }}" class="btn btn-xs btn-danger">PDF</a>
                    <a href="{{ route('projects.release-decisions.json', [$project, $releaseDecision]) }}" class="btn btn-xs btn-default">JSON</a>
                </div>
                {{ __('messages.release_decisions.package_title') }} #{{ $releaseDecision->id }}
            </div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-sm-2"><h2><span class="label label-{{ $releaseDecision->status_css }}">{{ $releaseDecision->status_label }}</span></h2><small>{{ __('messages.release_decisions.decision_status') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $releaseDecision->release_score }}</h2><small>{{ __('messages.release_decisions.release_score') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $releaseDecision->blocker_count }}</h2><small>{{ __('messages.release_decisions.blockers') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $releaseDecision->warning_count }}</h2><small>{{ __('messages.release_decisions.warnings') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $releaseDecision->blind_spot_count }}</h2><small>{{ __('messages.release_decisions.blind_spots') }}</small></div>
                    <div class="col-sm-2"><h2>{{ $releaseDecision->accepted_risk_count }}</h2><small>{{ __('messages.release_decisions.accepted_risks') }}</small></div>
                </div>
                <hr>
                <dl class="dl-horizontal">
                    <dt>{{ __('messages.release_decisions.release_name') }}</dt><dd>{{ $releaseDecision->release_name ?: __('messages.common.not_available') }}</dd>
                    <dt>{{ __('messages.release_decisions.target_environment') }}</dt><dd>{{ $releaseDecision->target_environment ?: __('messages.common.not_available') }}</dd>
                    <dt>{{ __('messages.release_decisions.decision_owner') }}</dt><dd>{{ $releaseDecision->owner?->name ?: __('messages.common.not_available') }}</dd>
                    <dt>{{ __('messages.release_decisions.decision_timestamp') }}</dt><dd>{{ $releaseDecision->decided_at?->format('Y-m-d H:i:s') ?: __('messages.release_decisions.pending') }}</dd>
                    <dt>{{ __('messages.release_decisions.package_checksum') }}</dt><dd><code>{{ $releaseDecision->package_checksum }}</code></dd>
                </dl>
                @if($releaseDecision->decision_notes)
                    <hr>
                    <h4>{{ __('messages.release_decisions.decision_notes') }}</h4>
                    <p>{!! nl2br(e($releaseDecision->decision_notes)) !!}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.release_decisions.evidence_chain') }}</div>
            <div class="panel-body">
                <table class="table table-condensed m-b-none">
                    <tbody>
                    <tr><th>{{ __('messages.release_decisions.latest_scan') }}</th><td>#{{ $ids['latest_scan_run_id'] ?? 'n/a' }}</td></tr>
                    <tr><th>{{ __('messages.release_decisions.latest_snapshot') }}</th><td>#{{ $ids['latest_snapshot_id'] ?? 'n/a' }}</td></tr>
                    <tr><th>{{ __('messages.release_decisions.latest_compare') }}</th><td>#{{ $ids['latest_compare_run_id'] ?? 'n/a' }}</td></tr>
                    <tr><th>{{ __('messages.release_decisions.latest_contract') }}</th><td>#{{ $ids['latest_contract_validation_run_id'] ?? 'n/a' }}</td></tr>
                    <tr><th>{{ __('messages.release_decisions.latest_gate') }}</th><td>#{{ $ids['latest_release_gate_id'] ?? 'n/a' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.release_decisions.finding_state_snapshot') }}</div>
            <div class="panel-body">
                <table class="table table-condensed m-b-none">
                    <tbody>
                    @foreach($findingState as $status => $count)
                        <tr><th>{{ __('messages.findings.statuses.'.$status) }}</th><td>{{ $count }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
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
                        @forelse(($readiness['blocking_issues'] ?? []) as $issue)
                            <div class="alert alert-danger p-xs">{{ $issue }}</div>
                        @empty
                            <p class="text-muted">{{ __('messages.release_decisions.no_blockers') }}</p>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <h4>{{ __('messages.release_decisions.warnings') }}</h4>
                        @forelse(($readiness['warnings'] ?? []) as $warning)
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
    <div class="col-md-6">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.blind_spots.report_summary_title') }}</div>
            <div class="panel-body">
                @forelse($blindSpots as $item)
                    <p><span class="label label-default">{{ $item['severity_label'] ?? '' }}</span> <strong>{{ $item['type_label'] ?? '' }}</strong><br><small>{{ $item['related_label'] ?? '' }} — {{ $item['suggested_action'] ?? '' }}</small></p>
                @empty
                    <p class="text-muted m-b-none">{{ __('messages.blind_spots.no_items') }}</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.risk_acceptances.report_summary_title') }}</div>
            <div class="panel-body">
                @forelse($riskRows as $risk)
                    <p><span class="label label-default">{{ $risk['status'] ?? '' }}</span> <strong>{{ $risk['finding'] ?? __('messages.common.not_available') }}</strong><br><small>{{ $risk['reason'] ?? '' }}</small></p>
                @empty
                    <p class="text-muted m-b-none">{{ __('messages.risk_acceptances.no_items') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
