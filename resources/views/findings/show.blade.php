@extends('layouts.app')

@section('title', $finding->title)

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel hred">
            <div class="panel-heading hbuilt">
                <div class="panel-tools">
                    <a href="{{ route('projects.findings.edit', [$project, $finding]) }}" class="btn btn-xs btn-primary">{{ __('messages.common.edit') }}</a>
                    <a href="{{ route('projects.findings.index', $project) }}" class="btn btn-xs btn-default">{{ __('messages.common.back') }}</a>
                </div>
                {{ __('messages.findings.detail_title') }}
            </div>
            <div class="panel-body">
                <h2 class="m-t-none">{{ $finding->title }}</h2>
                <p class="text-muted">{{ $finding->description ?: __('messages.common.not_available') }}</p>

                <div class="row m-b-md">
                    <div class="col-sm-3"><strong>{{ __('messages.findings.severity') }}</strong><br><span class="label label-{{ $finding->severity_css }}">{{ $finding->severity_label }}</span></div>
                    <div class="col-sm-3"><strong>{{ __('messages.findings.priority') }}</strong><br><span class="label label-{{ $finding->priority_css }}">{{ $finding->priority_label }}</span></div>
                    <div class="col-sm-3"><strong>{{ __('messages.common.status') }}</strong><br><span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span></div>
                    <div class="col-sm-3"><strong>{{ __('messages.findings.detected_at') }}</strong><br>{{ $finding->detected_at?->format('Y-m-d H:i') ?: $finding->created_at->format('Y-m-d H:i') }}</div>
                </div>

                <div class="row m-b-md">
                    <div class="col-sm-6">
                        <strong>{{ __('messages.endpoints.title') }}</strong><br>
                        @if($finding->endpoint)
                            <a href="{{ route('projects.endpoints.show', [$project, $finding->endpoint]) }}"><code>{{ $finding->endpoint->method }} {{ $finding->endpoint->path }}</code></a>
                        @else
                            <span class="text-muted">{{ __('messages.common.none') }}</span>
                        @endif
                    </div>
                    <div class="col-sm-6">
                        <strong>{{ __('messages.test_cases.title') }}</strong><br>
                        @if($finding->testCase)
                            <a href="{{ route('projects.test-cases.show', [$project, $finding->testCase]) }}">{{ $finding->testCase->title }}</a>
                        @else
                            <span class="text-muted">{{ __('messages.common.none') }}</span>
                        @endif
                    </div>
                </div>

                <hr>
                <h4>{{ __('messages.findings.reproduction_steps') }}</h4>
                <div class="well well-sm">{!! nl2br(e($finding->reproduction_steps ?: __('messages.common.not_available'))) !!}</div>

                <div class="row">
                    <div class="col-sm-6">
                        <h4>{{ __('messages.test_cases.expected_result') }}</h4>
                        <div class="well well-sm">{!! nl2br(e($finding->expected_result ?: __('messages.common.not_available'))) !!}</div>
                    </div>
                    <div class="col-sm-6">
                        <h4>{{ __('messages.test_cases.actual_result') }}</h4>
                        <div class="well well-sm">{!! nl2br(e($finding->actual_result ?: __('messages.common.not_available'))) !!}</div>
                    </div>
                </div>

                <h4>{{ __('messages.findings.recommendation') }}</h4>
                <div class="well well-sm">{!! nl2br(e($finding->recommendation ?: __('messages.common.not_available'))) !!}</div>
            </div>
            <div class="panel-footer">
                <form method="POST" action="{{ route('projects.findings.destroy', [$project, $finding]) }}" style="display:inline" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.findings.confirm_delete') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">{{ __('messages.common.delete') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.findings.ownership.title') }}</div>
            <div class="panel-body">
                <p><strong>{{ __('messages.findings.owner') }}:</strong> {{ $finding->owner?->name ?: __('messages.findings.unassigned') }}</p>
                <p><strong>{{ __('messages.findings.due_date') }}:</strong> {{ $finding->due_date?->format('Y-m-d H:i') ?: __('messages.common.not_available') }}</p>
                @if($finding->is_overdue)
                    <p><span class="label label-danger">{{ __('messages.findings.overdue') }}</span></p>
                @endif
                <p><strong>{{ __('messages.findings.linked_release_gate') }}:</strong>
                    @if($finding->linkedReleaseGate)
                        #{{ $finding->linkedReleaseGate->id }} — {{ $finding->linkedReleaseGate->release_name }}
                    @else
                        <span class="text-muted">{{ __('messages.common.none') }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.findings.verification.title') }}</div>
            <div class="panel-body">
                <p><strong>{{ __('messages.findings.verification_status') }}:</strong> <span class="label label-{{ $finding->verification_status_css }}">{{ $finding->verification_status_label }}</span></p>
                <p><strong>{{ __('messages.findings.retest_required') }}:</strong> {{ $finding->retest_required ? __('messages.common.yes') : __('messages.common.no') }}</p>
                <p><strong>{{ __('messages.findings.retest_result') }}:</strong> <span class="label label-{{ $finding->retest_result_css }}">{{ $finding->retest_result_label }}</span></p>
                <p><strong>{{ __('messages.findings.last_retest_at') }}:</strong> {{ $finding->last_retest_at?->format('Y-m-d H:i') ?: __('messages.common.not_available') }}</p>
                <p><strong>{{ __('messages.findings.fix_evidence_required') }}:</strong> {{ $finding->fix_evidence_required ? __('messages.common.yes') : __('messages.common.no') }}</p>
                <p><strong>{{ __('messages.findings.retest_evidence') }}:</strong> {{ $finding->has_retest_evidence ? __('messages.findings.retest_evidence_present') : __('messages.findings.retest_evidence_missing') }}</p>
                <p><strong>{{ __('messages.findings.verified_by') }}:</strong> {{ $finding->verifiedBy?->name ?: __('messages.common.not_available') }}</p>
                <p><strong>{{ __('messages.findings.verified_at') }}:</strong> {{ $finding->verified_at?->format('Y-m-d H:i') ?: __('messages.common.not_available') }}</p>
            </div>
        </div>

        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.findings.linked_evidence') }}</div>
            <div class="panel-body">
                <p><strong>{{ __('messages.scans.title') }}:</strong>
                    @if($finding->scanRun)
                        <a href="{{ route('projects.scans.show', [$project, $finding->scanRun]) }}">#{{ $finding->scanRun->id }}</a>
                    @else
                        <span class="text-muted">{{ __('messages.common.none') }}</span>
                    @endif
                </p>
                <p><strong>{{ __('messages.findings.scan_result') }}:</strong>
                    @if($finding->scanResult && $finding->scanResult->scanRun)
                        <a href="{{ route('projects.scans.show', [$project, $finding->scanResult->scanRun]) }}">#{{ $finding->scanResult->id }}</a>
                    @else
                        <span class="text-muted">{{ __('messages.common.none') }}</span>
                    @endif
                </p>
                <p><strong>{{ __('messages.findings.contract_result') }}:</strong>
                    @if($finding->contractValidationResult && $finding->contractValidationResult->run)
                        <a href="{{ route('projects.contract-validations.show', [$project, $finding->contractValidationResult->run]) }}">#{{ $finding->contractValidationResult->id }}</a>
                    @else
                        <span class="text-muted">{{ __('messages.common.none') }}</span>
                    @endif
                </p>
                @if($finding->resolved_at)
                    <p><strong>{{ __('messages.findings.resolved_at') }}:</strong> {{ $finding->resolved_at->format('Y-m-d H:i') }}</p>
                @endif
                @if($finding->status === \App\Models\Finding::STATUS_ACCEPTED_RISK || $finding->accepted_risk_expires_at || $finding->accepted_risk_note)
                    <hr>
                    <p><strong>{{ __('messages.findings.accepted_risk_expires_at') }}:</strong> {{ $finding->accepted_risk_expires_at?->format('Y-m-d H:i') ?: __('messages.common.not_available') }}</p>
                    @if($finding->accepted_risk_note)
                        <div class="well well-sm m-b-none">{!! nl2br(e($finding->accepted_risk_note)) !!}</div>
                    @endif
                @endif
            </div>
        </div>


<div class="hpanel hred">
    <div class="panel-heading hbuilt">
        <div class="panel-tools">
            <a href="{{ route('projects.risk-acceptances.index', $project) }}" class="btn btn-xs btn-default"><i class="fa fa-balance-scale"></i> {{ __('messages.risk_acceptances.open_ledger') }}</a>
        </div>
        {{ __('messages.risk_acceptances.panel_title') }}
    </div>
    <div class="panel-body">
        @if($finding->riskAcceptances->isNotEmpty())
            <div class="table-responsive m-b-md">
                <table class="table table-condensed table-striped m-b-none">
                    <thead><tr><th>{{ __('messages.common.status') }}</th><th>{{ __('messages.risk_acceptances.accepted_until') }}</th><th>{{ __('messages.risk_acceptances.accepted_by') }}</th></tr></thead>
                    <tbody>
                    @foreach($finding->riskAcceptances->take(5) as $acceptance)
                        <tr>
                            <td><span class="label label-{{ $acceptance->status_css }}">{{ __('messages.risk_acceptances.statuses.'.$acceptance->computed_status) }}</span></td>
                            <td>{{ $acceptance->accepted_until?->format('Y-m-d') ?: __('messages.common.not_available') }}</td>
                            <td>{{ $acceptance->acceptedBy?->name ?: __('messages.common.not_available') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted">{{ __('messages.risk_acceptances.no_finding_items') }}</p>
        @endif

        <form method="POST" action="{{ route('projects.findings.risk-acceptances.store', [$project, $finding]) }}">
            @csrf
            <div class="form-group">
                <label for="accepted_until">{{ __('messages.risk_acceptances.accepted_until') }}</label>
                <input type="date" name="accepted_until" id="accepted_until" class="form-control">
                <span class="help-block">{{ __('messages.risk_acceptances.accepted_until_help') }}</span>
            </div>
            <div class="form-group">
                <label for="reason">{{ __('messages.risk_acceptances.reason') }}</label>
                <textarea name="reason" id="reason" class="form-control" rows="3" required placeholder="{{ __('messages.risk_acceptances.reason_placeholder') }}"></textarea>
            </div>
            <div class="form-group">
                <label for="business_justification">{{ __('messages.risk_acceptances.business_justification') }}</label>
                <textarea name="business_justification" id="business_justification" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="mitigation_note">{{ __('messages.risk_acceptances.mitigation_note') }}</label>
                <textarea name="mitigation_note" id="mitigation_note" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="evidence_requirement">{{ __('messages.risk_acceptances.evidence_requirement') }}</label>
                <textarea name="evidence_requirement" id="evidence_requirement" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="release_scope">{{ __('messages.risk_acceptances.release_scope') }}</label>
                <input type="text" name="release_scope" id="release_scope" class="form-control" maxlength="180" placeholder="RC-1 / v2026.06 / client UAT">
            </div>
            <div class="form-group">
                <label for="expiry_action">{{ __('messages.risk_acceptances.expiry_action') }}</label>
                <select name="expiry_action" id="expiry_action" class="form-control" required>
                    @foreach(\App\Models\RiskAcceptance::EXPIRY_ACTIONS as $action)
                        <option value="{{ $action }}">{{ __('messages.risk_acceptances.expiry_actions.'.$action) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-danger btn-block"><i class="fa fa-balance-scale"></i> {{ __('messages.risk_acceptances.accept_risk') }}</button>
        </form>
    </div>
</div>

        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.findings.lifecycle.title') }}</div>
            <div class="panel-body">
                <p><strong>{{ __('messages.findings.lifecycle.current_status') }}:</strong> <span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span></p>
                @if($finding->lifecycle_changed_at)
                    <p><strong>{{ __('messages.findings.lifecycle.last_changed') }}:</strong> {{ $finding->lifecycle_changed_at->format('Y-m-d H:i') }}</p>
                @endif
                @if($finding->lifecycleChangedBy)
                    <p><strong>{{ __('messages.findings.lifecycle.changed_by') }}:</strong> {{ $finding->lifecycleChangedBy->name }}</p>
                @endif
                <p><strong>{{ __('messages.findings.lifecycle.reopened_count') }}:</strong> {{ (int) $finding->reopened_count }}</p>
                @if($finding->lifecycle_note)
                    <div class="well well-sm m-b-md">{!! nl2br(e($finding->lifecycle_note)) !!}</div>
                @endif

                @if(count($finding->availableLifecycleTransitions()) > 0)
                    <form method="POST" action="{{ route('projects.findings.lifecycle.update', [$project, $finding]) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label for="lifecycle_status">{{ __('messages.findings.lifecycle.next_status') }}</label>
                            <select name="status" id="lifecycle_status" class="form-control" required>
                                @foreach($finding->availableLifecycleTransitions() as $targetStatus => $actionLabel)
                                    <option value="{{ $targetStatus }}">{{ $actionLabel }} → {{ __('messages.findings.statuses.'.$targetStatus) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="lifecycle_note">{{ __('messages.findings.lifecycle.note') }}</label>
                            <textarea name="note" id="lifecycle_note" class="form-control" rows="3" placeholder="{{ __('messages.findings.lifecycle.note_placeholder') }}"></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning btn-block">{{ __('messages.findings.lifecycle.update_status') }}</button>
                    </form>
                @else
                    <p class="text-muted m-b-none">{{ __('messages.findings.lifecycle.no_transitions') }}</p>
                @endif
            </div>
        </div>

        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.findings.add_evidence') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.findings.evidence_help') }}</p>
                <form method="POST" action="{{ route('projects.findings.evidence.store', [$project, $finding]) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="type">{{ __('messages.common.type') }}</label>
                        <select name="type" id="type" class="form-control" required>
                            @foreach(\App\Models\FindingEvidence::ACTIVE_TYPES as $type)
                                <option value="{{ $type }}" @selected($type === \App\Models\FindingEvidence::TYPE_RETEST && $finding->retest_required)>{{ __('messages.findings.evidence_types.'.$type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="source_label">{{ __('messages.findings.source_label') }}</label>
                        <input type="text" name="source_label" id="source_label" class="form-control" maxlength="160" placeholder="{{ __('messages.findings.source_label_placeholder') }}">
                    </div>
                    <div class="form-group">
                        <label for="captured_at">{{ __('messages.findings.captured_at') }}</label>
                        <input type="datetime-local" name="captured_at" id="captured_at" class="form-control">
                        <span class="help-block">{{ __('messages.findings.captured_at_help') }}</span>
                    </div>
                    <div class="form-group">
                        <label for="url">{{ __('messages.findings.url') }}</label>
                        <input type="url" name="url" id="url" class="form-control" placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label for="attachment">{{ __('messages.findings.attachment') }}</label>
                        <input type="file" name="attachment" id="attachment" class="form-control">
                        <span class="help-block">{{ __('messages.findings.attachment_help') }}</span>
                    </div>
                    <div class="form-group">
                        <label for="content">{{ __('messages.findings.evidence_content') }}</label>
                        <textarea name="content" id="content" class="form-control" rows="4" placeholder="{{ __('messages.findings.evidence_content_placeholder') }}"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="request_excerpt">{{ __('messages.findings.request_excerpt') }}</label>
                        <textarea name="request_excerpt" id="request_excerpt" class="form-control" rows="4" placeholder="GET /api/users/1 HTTP/1.1"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="response_excerpt">{{ __('messages.findings.response_excerpt') }}</label>
                        <textarea name="response_excerpt" id="response_excerpt" class="form-control" rows="4" placeholder="HTTP/1.1 500 Internal Server Error"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="curl_command">{{ __('messages.findings.curl_command') }}</label>
                        <textarea name="curl_command" id="curl_command" class="form-control" rows="3" placeholder="curl -i https://api.example.test/..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">{{ __('messages.findings.add_evidence') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.findings.lifecycle.history') }}</div>
            <div class="panel-body">
                @if($finding->lifecycleEvents->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.findings.lifecycle.no_history') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered m-b-none">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.findings.lifecycle.changed_at') }}</th>
                                    <th>{{ __('messages.findings.lifecycle.transition') }}</th>
                                    <th>{{ __('messages.findings.lifecycle.changed_by') }}</th>
                                    <th>{{ __('messages.findings.lifecycle.note') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($finding->lifecycleEvents as $event)
                                <tr>
                                    <td>{{ $event->changed_at?->format('Y-m-d H:i') ?: $event->created_at->format('Y-m-d H:i') }}</td>
                                    <td><span class="label label-default">{{ $event->from_status_label }}</span> → <span class="label label-info">{{ $event->to_status_label }}</span></td>
                                    <td>{{ $event->user?->name ?: __('messages.common.not_available') }}</td>
                                    <td>{!! nl2br(e($event->note ?: __('messages.common.not_available'))) !!}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.findings.comments') }}</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('projects.findings.comments.store', [$project, $finding]) }}" class="m-b-md">
                    @csrf
                    <div class="row">
                        <div class="col-sm-4">
                            <select name="type" class="form-control" required>
                                @foreach(\App\Models\FindingComment::TYPES as $commentType)
                                    <option value="{{ $commentType }}">{{ __('messages.findings.comment_types.'.$commentType) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-8">
                            <textarea name="body" class="form-control" rows="3" required maxlength="5000" placeholder="{{ __('messages.findings.comment_body_placeholder') }}"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-block m-t-sm">{{ __('messages.findings.add_comment') }}</button>
                </form>
                @if($finding->comments->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.findings.no_comments') }}</p>
                @else
                    @foreach($finding->comments as $comment)
                        <div class="well well-sm">
                            <strong>{{ $comment->type_label }}</strong>
                            <small class="text-muted">— {{ $comment->user?->name ?: __('messages.common.not_available') }} · {{ $comment->created_at->format('Y-m-d H:i') }}</small>
                            <div class="m-t-xs">{!! nl2br(e($comment->body)) !!}</div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="hpanel">
            <div class="panel-heading hbuilt">{{ __('messages.findings.evidence') }}</div>
            <div class="panel-body">
                @if($finding->evidence->isEmpty())
                    <p class="text-muted m-b-none">{{ __('messages.findings.no_evidence') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.common.type') }}</th>
                                    <th>{{ __('messages.findings.source_label') }}</th>
                                    <th>{{ __('messages.findings.evidence_summary') }}</th>
                                    <th>{{ __('messages.findings.attachment') }}</th>
                                    <th>{{ __('messages.findings.captured_at') }}</th>
                                    <th>{{ __('messages.findings.captured_by') }}</th>
                                    <th class="text-right">{{ __('messages.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($finding->evidence as $evidence)
                                <tr>
                                    <td>{{ $evidence->type_label }}</td>
                                    <td>{{ $evidence->source_label ?: __('messages.common.not_available') }}</td>
                                    <td>
                                        {!! nl2br(e($evidence->summary)) !!}
                                        @if($evidence->url)
                                            <div><a href="{{ $evidence->url }}" target="_blank" rel="noopener">{{ __('messages.findings.open_evidence_link') }}</a></div>
                                        @endif
                                        @if($evidence->request_excerpt)
                                            <details class="m-t-xs"><summary>{{ __('messages.findings.request_excerpt') }}</summary><pre class="code-block"><code>{{ $evidence->request_excerpt }}</code></pre></details>
                                        @endif
                                        @if($evidence->response_excerpt)
                                            <details class="m-t-xs"><summary>{{ __('messages.findings.response_excerpt') }}</summary><pre class="code-block"><code>{{ $evidence->response_excerpt }}</code></pre></details>
                                        @endif
                                        @if($evidence->curl_command)
                                            <details class="m-t-xs"><summary>{{ __('messages.findings.curl_command') }}</summary><pre class="code-block"><code>{{ $evidence->curl_command }}</code></pre></details>
                                        @endif
                                        @if($evidence->attachment_sha256)
                                            <small class="text-muted">SHA-256: <code>{{ $evidence->attachment_sha256 }}</code></small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($evidence->has_attachment)
                                            <a href="{{ route('projects.findings.evidence.download', [$project, $finding, $evidence]) }}" class="btn btn-xs btn-default">{{ __('messages.findings.download_attachment') }}</a>
                                            <div><small>{{ $evidence->attachment_original_name }}</small></div>
                                            <div><small class="text-muted">{{ $evidence->attachment_mime_type ?: __('messages.common.not_available') }} · {{ $evidence->attachment_size_label }}</small></div>
                                        @else
                                            <span class="text-muted">{{ __('messages.common.none') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $evidence->captured_at?->format('Y-m-d H:i') ?: $evidence->created_at->format('Y-m-d H:i') }}</td>
                                    <td>{{ $evidence->capturedBy?->name ?: __('messages.common.not_available') }}</td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('projects.findings.evidence.destroy', [$project, $finding, $evidence]) }}" style="display:inline" data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.common.confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.findings.confirm_delete_evidence') }}" data-aptoria-confirm-type="warning" data-aptoria-confirm-button="{{ __('messages.common.delete') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger">{{ __('messages.common.delete') }}</button>
                                        </form>
                                    </td>
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
@endsection
