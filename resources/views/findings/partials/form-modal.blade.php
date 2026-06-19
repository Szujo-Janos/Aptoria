<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable aptoria-finding-dialog">
        <div class="modal-content aptoria-finding-modal-content">
            <form method="POST" action="{{ $action }}" data-aptoria-form-scope="finding" data-aptoria-form-plugin>
                @csrf
                @if($method !== 'POST') @method($method) @endif
                <div class="modal-header aptoria-finding-modal-header">
                    <div><h5 class="modal-title"><i data-lucide="bug" class="me-2"></i>{{ $finding ? __('messages.findings.edit') : __('messages.findings.new') }}</h5><p class="text-muted mb-0 small">{{ __('messages.findings.form_copy') }}</p></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.common.close') }}"></button>
                </div>
                <div class="modal-body aptoria-finding-modal-body">
                    <div class="alert alert-light border d-flex align-items-start gap-2 mb-3">
                        <i data-lucide="info" class="text-primary mt-1"></i>
                        <div class="small text-muted">{{ __('messages.findings.scroll_hint') }}</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <label class="form-label">{{ __('messages.findings.title_field') }}</label>
                            <input type="text" name="title" value="{{ old('title', $finding?->title) }}" class="form-control" required>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('messages.findings.owner') }}</label>
                            <input type="text" name="owner_name" value="{{ old('owner_name', $finding?->owner_name) }}" class="form-control">
                        </div>
                        <div class="col-md-3"><label class="form-label">{{ __('messages.findings.source') }}</label><select name="source" class="form-select">@foreach(\App\Models\Finding::SOURCES as $source)<option value="{{ $source }}" @selected(old('source', $finding?->source ?? 'manual') === $source)>{{ __('messages.findings.sources.'.$source) }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label">{{ __('messages.findings.severity') }}</label><select name="severity" class="form-select">@foreach(\App\Models\Finding::SEVERITIES as $severity)<option value="{{ $severity }}" @selected(old('severity', $finding?->severity ?? 'medium') === $severity)>{{ __('messages.findings.severities.'.$severity) }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label">{{ __('messages.findings.status') }}</label><select name="status" class="form-select">@foreach(\App\Models\Finding::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', $finding?->status ?? 'open') === $status)>{{ __('messages.findings.statuses.'.$status) }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label">{{ __('messages.findings.priority') }}</label><select name="priority" class="form-select">@foreach(\App\Models\Finding::PRIORITIES as $priority)<option value="{{ $priority }}" @selected(old('priority', $finding?->priority ?? 'normal') === $priority)>{{ __('messages.findings.priorities.'.$priority) }}</option>@endforeach</select></div>
                        <div class="col-md-6"><label class="form-label">{{ __('messages.endpoints.endpoint') }}</label><select name="endpoint_id" class="form-select"><option value="">—</option>@foreach($endpoints as $endpoint)<option value="{{ $endpoint->id }}" @selected((int) old('endpoint_id', $finding?->endpoint_id) === (int) $endpoint->id)>{{ $endpoint->method }} {{ $endpoint->path }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">{{ __('messages.findings.scan_result') }}</label><select name="scan_result_id" class="form-select"><option value="">—</option>@foreach($scanResults as $scanResult)<option value="{{ $scanResult->id }}" @selected((int) old('scan_result_id', $finding?->scan_result_id) === (int) $scanResult->id)>#{{ $scanResult->scan_run_id }} · {{ $scanResult->status_code ?? $scanResult->status_label }} · {{ $scanResult->endpoint?->path ?? $scanResult->url }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label">{{ __('messages.findings.due_date') }}</label><input type="date" name="due_date" value="{{ old('due_date', $finding?->due_date?->format('Y-m-d')) }}" class="form-control"></div>
                        <div class="col-12"><label class="form-label">{{ __('messages.findings.summary') }}</label><textarea name="summary" rows="2" class="form-control">{{ old('summary', $finding?->summary) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">{{ __('messages.findings.expected_result') }}</label><textarea name="expected_result" rows="2" class="form-control">{{ old('expected_result', $finding?->expected_result) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">{{ __('messages.findings.actual_result') }}</label><textarea name="actual_result" rows="2" class="form-control">{{ old('actual_result', $finding?->actual_result) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">{{ __('messages.findings.reproduction_steps') }}</label><textarea name="reproduction_steps" rows="2" class="form-control">{{ old('reproduction_steps', $finding?->reproduction_steps) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">{{ __('messages.findings.recommendation') }}</label><textarea name="recommendation" rows="2" class="form-control">{{ old('recommendation', $finding?->recommendation) }}</textarea></div>
                        <div class="col-md-4"><div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="evidence_required" value="1" id="evidence_required_{{ $modalId }}" @checked(old('evidence_required', $finding?->evidence_required ?? true))><label class="form-check-label" for="evidence_required_{{ $modalId }}">{{ __('messages.findings.evidence_required') }}</label></div></div>
                        <div class="col-md-4"><div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="retest_required" value="1" id="retest_required_{{ $modalId }}" @checked(old('retest_required', $finding?->retest_required ?? false))><label class="form-check-label" for="retest_required_{{ $modalId }}">{{ __('messages.findings.retest_required') }}</label></div></div>
                    </div>
                </div>
                <div class="modal-footer aptoria-card-footer-subtle aptoria-finding-modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('messages.common.cancel') }}</button><button type="submit" class="btn btn-primary"><i data-lucide="save" class="me-1"></i>{{ __('messages.common.save') }}</button></div>
            </form>
        </div>
    </div>
</div>
