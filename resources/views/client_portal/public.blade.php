@extends('layouts.auth')

@section('title', __('messages.client_portal.public_title'))

@section('content')
@php
    $project = $dashboard['project'];
    $permissions = $access->permissions ?: [];
    $snapshot = $dashboard['current_snapshot'];
@endphp
<style>
    body.aptoria-auth-layout {
        background:
            linear-gradient(180deg, rgba(242,247,244,0.95) 0%, rgba(231,237,245,0.96) 100%),
            url('{{ asset('assets/aptoria/img/patterns/shattered-dark.png') }}') center top repeat;
        background-attachment: fixed;
        min-height: 100vh;
    }
    .client-portal-shell {
        padding: 88px 0 96px;
    }
    .client-portal-fixed-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1040;
        background: rgba(255,255,255,0.96);
        border-bottom: 1px solid #d9e7dd;
        box-shadow: 0 4px 18px rgba(24, 39, 75, 0.08);
        backdrop-filter: blur(6px);
    }
    .client-portal-fixed-header .container {
        max-width: 1180px;
        padding-top: 12px;
        padding-bottom: 12px;
    }
    .client-portal-fixed-header .portal-title {
        font-size: 22px;
        font-weight: 500;
        color: #2f4050;
        margin: 0;
        line-height: 1.2;
    }
    .client-portal-fixed-header .portal-subtitle {
        margin: 4px 0 0;
        color: #6d7b88;
        font-size: 13px;
    }
    .client-portal-fixed-footer {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1035;
        background: rgba(255,255,255,0.97);
        border-top: 1px solid #d9e7dd;
        box-shadow: 0 -4px 18px rgba(24, 39, 75, 0.08);
        backdrop-filter: blur(6px);
    }
    .client-portal-fixed-footer .container {
        max-width: 1180px;
        padding-top: 10px;
        padding-bottom: 10px;
    }
    .client-portal-credit {
        color: #6d7b88;
        font-size: 12px;
        margin: 0;
    }
    .client-portal-credit strong {
        color: #2f4050;
        font-weight: 500;
    }
    .client-portal-actions .btn {
        margin-right: 8px;
        margin-bottom: 8px;
    }

    .client-portal-brand {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .client-portal-brand img {
        display: block;
        height: 36px;
        max-width: 172px;
        object-fit: contain;
    }
    .client-portal-brand-copy {
        min-width: 0;
    }
    .client-portal-role-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }
    .client-portal-capability {
        border: 1px solid #e4e9ef;
        border-radius: 4px;
        padding: 8px 10px;
        background: #fbfcfd;
        min-height: 42px;
    }
    .client-portal-capability.is-enabled {
        border-color: #d8ead0;
        background: #f7fbf5;
    }
    .client-portal-capability.is-disabled {
        color: #8d99a6;
        background: #f7f8fa;
    }
    .client-portal-capability small {
        display: block;
        margin-top: 2px;
    }
    @media (max-width: 767px) {
        .client-portal-shell {
            padding-top: 110px;
            padding-bottom: 110px;
        }
        .client-portal-fixed-header .text-right,
        .client-portal-fixed-footer .text-right {
            text-align: left;
            margin-top: 8px;
        }
        .client-portal-brand {
            align-items: flex-start;
        }
        .client-portal-brand img {
            height: 30px;
            max-width: 142px;
        }
        .client-portal-role-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="client-portal-fixed-header">
    <div class="container">
        <div class="row">
            <div class="col-sm-8">
                <div class="client-portal-brand">
                    <img src="{{ asset('assets/aptoria/img/aptoria-logo-horizontal.png') }}" alt="Aptoria">
                    <div class="client-portal-brand-copy">
                        <h1 class="portal-title">{{ __('messages.client_portal.public_title') }}</h1>
                        <p class="portal-subtitle">{{ $project->name }} · {{ __('messages.client_portal.header_subtitle') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 text-right">
                <span class="label label-{{ $access->status_css }}">{{ $access->role_label }}</span>
            </div>
        </div>
    </div>
</div>

<div class="client-portal-shell">
<div class="container" style="max-width:1180px;">
    <div class="hpanel hblue">
        <div class="panel-heading hbuilt">
            <span class="label label-{{ $access->status_css }} pull-right">{{ $access->role_label }}</span>
            {{ __('messages.client_portal.public_title') }}
        </div>
        <div class="panel-body">
            <h2 class="m-t-none">{{ $project->name }}</h2>
            <p class="text-muted">{{ __('messages.client_portal.public_intro') }}</p>
            <div class="row text-center">
                <div class="col-sm-2"><h3>{{ $dashboard['metrics']['approved_reports'] }}</h3><small>{{ __('messages.client_portal.approved_reports') }}</small></div>
                <div class="col-sm-2"><h3>{{ $dashboard['metrics']['release_decisions'] }}</h3><small>{{ __('messages.client_portal.release_decisions') }}</small></div>
                <div class="col-sm-2"><h3>{{ $dashboard['metrics']['accepted_risks'] }}</h3><small>{{ __('messages.client_portal.accepted_risks') }}</small></div>
                <div class="col-sm-2"><h3>{{ $dashboard['metrics']['open_findings'] }}</h3><small>{{ __('messages.client_portal.open_findings') }}</small></div>
                <div class="col-sm-2"><h3>{{ $project->endpoints_count }}</h3><small>{{ __('messages.nav.endpoints') }}</small></div>
                <div class="col-sm-2"><h3>{{ $project->scan_runs_count }}</h3><small>{{ __('messages.nav.scans_snapshots') }}</small></div>
            </div>
        </div>
    </div>

    <div class="hpanel hgreen">
        <div class="panel-heading hbuilt">{{ __('messages.client_portal.current_snapshot') }}</div>
        <div class="panel-body">
            <div class="row text-center m-b-md">
                <div class="col-sm-2"><h3><span class="label label-{{ $snapshot['css'] }}">{{ $snapshot['label'] }}</span></h3><small>{{ __('messages.release_readiness.overall_status') }}</small></div>
                <div class="col-sm-2"><h3>{{ $snapshot['score'] }}</h3><small>{{ __('messages.release_readiness.score') }} / 100</small></div>
                <div class="col-sm-2"><h3>{{ $snapshot['coverage_percent'] }}%</h3><small>{{ __('messages.release_readiness.endpoint_coverage') }}</small></div>
                <div class="col-sm-2"><h3>{{ $snapshot['blocker_count'] }}</h3><small>{{ __('messages.release_readiness.blocking_issues') }}</small></div>
                <div class="col-sm-2"><h3>{{ $snapshot['warning_count'] }}</h3><small>{{ __('messages.release_readiness.warning_items') }}</small></div>
                <div class="col-sm-2"><h3>{{ $snapshot['blind_spot_count'] }}</h3><small>{{ __('messages.blind_spots.title') }}</small></div>
            </div>
            <div class="row">
                <div class="col-md-7">
                    <dl class="dl-horizontal m-b-none">
                        <dt>{{ __('messages.scans.latest') }}</dt><dd>@if($snapshot['latest_scan']) #{{ $snapshot['latest_scan']->id }} — {{ $snapshot['latest_scan']->status_label }} @else {{ __('messages.common.not_available') }} @endif</dd>
                        <dt>{{ __('messages.client_portal.latest_approved_report') }}</dt><dd>@if($snapshot['latest_approved_report']) {{ $snapshot['latest_approved_report']->title }} @else {{ __('messages.common.not_available') }} @endif</dd>
                        <dt>{{ __('messages.client_portal.latest_release_decision') }}</dt><dd>@if($snapshot['latest_decision']) {{ $snapshot['latest_decision']->release_name ?: '#' . $snapshot['latest_decision']->id }} — {{ $snapshot['latest_decision']->status_label }} @else {{ __('messages.common.not_available') }} @endif</dd>
                    </dl>
                </div>
                <div class="col-md-5">
                    @if($snapshot['is_client_handoff_ready'])
                        <div class="alert alert-success m-b-none"><strong>{{ __('messages.client_portal.handoff_ready') }}</strong><br>{{ __('messages.client_portal.handoff_ready_help') }}</div>
                    @else
                        <div class="alert alert-warning m-b-none"><strong>{{ __('messages.client_portal.handoff_gaps') }}</strong>
                            <ul class="m-b-none m-t-xs">
                                @foreach($snapshot['gaps'] as $gap)
                                    <li>{{ $gap }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="hpanel hblue">
        <div class="panel-heading hbuilt">{{ __('messages.client_portal.role_access_summary') }}</div>
        <div class="panel-body">
            <p class="text-muted">{{ __('messages.client_portal.role_access_intro') }}</p>
            @if(($dashboard['metrics']['visible_sections'] ?? 0) === 0)
                <div class="alert alert-warning">
                    <strong>{{ __('messages.client_portal.no_visible_sections_title') }}</strong><br>
                    {{ __('messages.client_portal.no_visible_sections_help') }}
                </div>
            @endif
            <div class="client-portal-role-grid">
                @foreach($dashboard['role_capabilities'] as $capability)
                    <div class="client-portal-capability {{ $capability['enabled'] ? 'is-enabled' : 'is-disabled' }}">
                        <span class="label label-{{ $capability['enabled'] ? 'success' : 'default' }} pull-right">{{ $capability['enabled'] ? __('messages.client_portal.visible') : __('messages.client_portal.restricted') }}</span>
                        <strong>{{ $capability['label'] }}</strong>
                        <small>{{ $capability['approval'] ? __('messages.client_portal.approval_capability') : __('messages.client_portal.content_capability') }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-md-7">
            @if($access->allows(\App\Models\ClientPortalAccess::PERMISSION_REPORTS))
            <div class="hpanel hblue"><div class="panel-heading hbuilt">{{ __('messages.client_portal.approved_reports') }}</div><div class="panel-body p-none">
                @if($dashboard['approved_reports']->isEmpty())
                    <div class="p-md text-muted">
                        <p>{{ __('messages.client_portal.no_approved_reports') }}</p>
                        <p class="m-b-none"><i class="fa fa-info-circle"></i> {{ __('messages.client_portal.approved_reports_hint') }}</p>
                    </div>
                @else
                <table class="table table-striped m-b-none"><thead><tr><th>{{ __('messages.report_versions.report_type') }}</th><th>{{ __('messages.report_versions.checksum') }}</th><th>{{ __('messages.report_versions.approved_at') }}</th><th></th></tr></thead><tbody>
                    @foreach($dashboard['approved_reports'] as $report)
                    <tr><td><strong>{{ $report->title }}</strong><br><small>{{ $report->type_label }}</small></td><td><code>{{ $report->short_checksum }}</code></td><td>{{ $report->approved_at?->format('Y-m-d H:i') ?: '—' }}</td><td class="text-right"><a class="btn btn-xs btn-primary" href="{{ route('client-portal.reports.markdown', [$access, $report]) }}">MD</a> <a class="btn btn-xs btn-default" href="{{ route('client-portal.reports.html', [$access, $report]) }}">HTML</a> <a class="btn btn-xs btn-default" href="{{ route('client-portal.reports.json', [$access, $report]) }}">JSON</a>
                        @if($access->allows(\App\Models\ClientPortalAccess::PERMISSION_APPROVE_REPORTS))
                        <form method="POST" action="{{ route('client-portal.acknowledgements.store', $access) }}" style="display:inline">@csrf<input type="hidden" name="acknowledgement_type" value="{{ \App\Models\ClientPortalAcknowledgement::TYPE_REPORT_APPROVAL }}"><input type="hidden" name="report_version_id" value="{{ $report->id }}"><button class="btn btn-xs btn-success" type="submit">{{ __('messages.client_portal.acknowledge') }}</button></form>
                        @endif
                    </td></tr>
                    @endforeach
                </tbody></table>
                @endif
            </div></div>
            @endif

            @if($access->allows(\App\Models\ClientPortalAccess::PERMISSION_RELEASE_DECISIONS))
            <div class="hpanel hblue"><div class="panel-heading hbuilt">{{ __('messages.client_portal.release_decisions') }}</div><div class="panel-body p-none">
                @if($dashboard['release_decisions']->isEmpty())
                    <div class="p-md text-muted">
                        <p>{{ __('messages.release_decisions.no_decisions') }}</p>
                        <div class="well well-sm m-b-none">
                            <strong>{{ __('messages.client_portal.current_readiness_fallback') }}</strong><br>
                            {{ __('messages.release_readiness.score') }}: {{ $snapshot['score'] }} / 100 ·
                            {{ __('messages.release_readiness.blocking_issues') }}: {{ $snapshot['blocker_count'] }} ·
                            {{ __('messages.release_readiness.warning_items') }}: {{ $snapshot['warning_count'] }}
                        </div>
                    </div>
                @else
                <table class="table table-striped m-b-none"><thead><tr><th>{{ __('messages.release_decisions.release_name') }}</th><th>{{ __('messages.release_decisions.decision_status') }}</th><th>{{ __('messages.release_decisions.release_score') }}</th><th></th></tr></thead><tbody>
                    @foreach($dashboard['release_decisions'] as $decision)
                    <tr><td>{{ $decision->release_name ?: '—' }}<br><small>{{ $decision->decided_at?->format('Y-m-d H:i') ?: '—' }}</small></td><td><span class="label label-{{ $decision->status_css }}">{{ $decision->status_label }}</span></td><td>{{ $decision->release_score }}</td><td class="text-right"><a class="btn btn-xs btn-default" href="{{ route('client-portal.release-decisions.json', [$access, $decision]) }}">JSON</a>
                    @if($access->allows(\App\Models\ClientPortalAccess::PERMISSION_ACKNOWLEDGE_RELEASE))<form method="POST" action="{{ route('client-portal.acknowledgements.store', $access) }}" style="display:inline">@csrf<input type="hidden" name="acknowledgement_type" value="{{ \App\Models\ClientPortalAcknowledgement::TYPE_RELEASE_ACKNOWLEDGEMENT }}"><input type="hidden" name="release_decision_id" value="{{ $decision->id }}"><button class="btn btn-xs btn-success" type="submit">{{ __('messages.client_portal.acknowledge') }}</button></form>@endif
                    </td></tr>
                    @endforeach
                </tbody></table>
                @endif
            </div></div>
            @endif
        </div>
        <div class="col-md-5">
            @if($access->allows(\App\Models\ClientPortalAccess::PERMISSION_EVIDENCE_PACKAGE))
            <div class="hpanel hgreen"><div class="panel-heading hbuilt">{{ __('messages.client_portal.evidence_package') }}</div><div class="panel-body">
                <p class="text-muted">{{ __('messages.client_portal.evidence_package_intro') }}</p>
                <div class="client-portal-actions">
                    <a class="btn btn-primary" href="{{ route('client-portal.evidence.summary', $access) }}">{{ __('messages.client_portal.download_summary') }}</a>
                    <a class="btn btn-default" href="{{ route('client-portal.evidence.zip', $access) }}">{{ __('messages.client_portal.download_package') }}</a>
                </div>
            </div></div>
            @endif

            @if($access->allows(\App\Models\ClientPortalAccess::PERMISSION_ACCEPTED_RISKS))
            <div class="hpanel hyellow"><div class="panel-heading hbuilt">{{ __('messages.client_portal.accepted_risks') }}</div><div class="panel-body">
                @forelse($dashboard['accepted_risks'] as $risk)
                    <div class="border-bottom p-xs m-b-xs"><strong>{{ $risk->finding?->title ?: __('messages.risk_acceptances.title') }}</strong><br><small>{{ $risk->accepted_until?->format('Y-m-d') ?: '—' }}</small>
                    @if($access->allows(\App\Models\ClientPortalAccess::PERMISSION_APPROVE_RISKS))<form method="POST" action="{{ route('client-portal.acknowledgements.store', $access) }}" class="m-t-xs">@csrf<input type="hidden" name="acknowledgement_type" value="{{ \App\Models\ClientPortalAcknowledgement::TYPE_RISK_ACCEPTANCE_ACKNOWLEDGEMENT }}"><input type="hidden" name="risk_acceptance_id" value="{{ $risk->id }}"><button class="btn btn-xs btn-warning" type="submit">{{ __('messages.client_portal.acknowledge') }}</button></form>@endif
                    </div>
                @empty
                    <p class="text-muted m-b-none">{{ __('messages.client_portal.no_accepted_risks') }}</p>
                @endforelse
            </div></div>
            @endif

            @if($access->allows(\App\Models\ClientPortalAccess::PERMISSION_FINDINGS))
            <div class="hpanel hblue"><div class="panel-heading hbuilt">{{ __('messages.client_portal.finding_summary') }}</div><div class="panel-body">
                <dl class="dl-horizontal"><dt>{{ __('messages.common.total') }}</dt><dd>{{ $dashboard['finding_summary']['total'] }}</dd><dt>{{ __('messages.client_portal.open_findings') }}</dt><dd>{{ $dashboard['finding_summary']['open'] }}</dd><dt>{{ __('messages.findings.statuses.verified') }}</dt><dd>{{ $dashboard['finding_summary']['verified'] }}</dd><dt>{{ __('messages.findings.statuses.accepted_risk') }}</dt><dd>{{ $dashboard['finding_summary']['accepted_risk'] }}</dd></dl>
                @if($dashboard['finding_summary']['recent']->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-condensed m-b-none">
                            <thead><tr><th>{{ __('messages.findings.title') }}</th><th>{{ __('messages.common.status') }}</th></tr></thead>
                            <tbody>
                            @foreach($dashboard['finding_summary']['recent'] as $finding)
                                <tr>
                                    <td><strong>{{ $finding->title }}</strong><br><small>{{ $finding->endpoint ? $finding->endpoint->method.' '.$finding->endpoint->path : '—' }}</small></td>
                                    <td><span class="label label-{{ $finding->severity_css }}">{{ $finding->severity_label }}</span><br><small>{{ $finding->status_label }}</small></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div></div>
            @endif

            <div class="hpanel hblue"><div class="panel-heading hbuilt">{{ __('messages.client_portal.acknowledgements') }}</div><div class="panel-body">
                @forelse($dashboard['acknowledgements'] as $ack)
                    <p><strong>{{ $ack->type_label }}</strong><br><small>{{ $ack->acknowledged_at?->format('Y-m-d H:i') }}</small></p>
                @empty
                    <p class="text-muted m-b-none">{{ __('messages.client_portal.no_acknowledgements') }}</p>
                @endforelse
            </div></div>
        </div>
    </div>
</div>
</div>

<div class="client-portal-fixed-footer">
    <div class="container">
        <div class="row">
            <div class="col-sm-8">
                <p class="client-portal-credit"><strong>Aptoria</strong> · {{ __('messages.client_portal.footer_credit') }}</p>
            </div>
            <div class="col-sm-4 text-right">
                <p class="client-portal-credit m-b-none">{{ __('messages.client_portal.public_title') }} · {{ $project->name }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
