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
                    <div class="col-sm-3"><strong>{{ __('messages.common.status') }}</strong><br><span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span></div>
                    <div class="col-sm-3"><strong>{{ __('messages.findings.source') }}</strong><br>{{ $finding->source_label }}</div>
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
            </div>
        </div>

        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">{{ __('messages.findings.lifecycle.title') }}</div>
            <div class="panel-body">
                <p>
                    <strong>{{ __('messages.findings.lifecycle.current_status') }}:</strong>
                    <span class="label label-{{ $finding->status_css }}">{{ $finding->status_label }}</span>
                </p>
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
                                <option value="{{ $type }}">{{ __('messages.findings.evidence_types.'.$type) }}</option>
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
    <div class="col-lg-12">
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
