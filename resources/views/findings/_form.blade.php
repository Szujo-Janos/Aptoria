@csrf

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="endpoint_id">{{ __('messages.endpoints.title') }}</label>
            <select name="endpoint_id" id="endpoint_id" class="form-control">
                <option value="">{{ __('messages.findings.no_endpoint_link') }}</option>
                @foreach($project->endpoints as $endpoint)
                    <option value="{{ $endpoint->id }}" @selected((string) old('endpoint_id', $finding->endpoint_id) === (string) $endpoint->id)>{{ $endpoint->method }} {{ $endpoint->path }}</option>
                @endforeach
            </select>
            @error('endpoint_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="test_case_id">{{ __('messages.test_cases.title') }}</label>
            <select name="test_case_id" id="test_case_id" class="form-control">
                <option value="">{{ __('messages.findings.no_test_case_link') }}</option>
                @foreach($project->testCases as $case)
                    <option value="{{ $case->id }}" @selected((string) old('test_case_id', $finding->test_case_id) === (string) $case->id)>{{ $case->testSuite?->name }} — {{ $case->title }}</option>
                @endforeach
            </select>
            @error('test_case_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="title">{{ __('messages.findings.title_field') }}</label>
    <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $finding->title) }}" required maxlength="220" placeholder="{{ __('messages.findings.title_placeholder') }}">
    @error('title')<span class="text-danger">{{ $message }}</span>@enderror
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="source">{{ __('messages.findings.source') }}</label>
            <select name="source" id="source" class="form-control" required>
                @foreach(\App\Models\Finding::SOURCES as $source)
                    <option value="{{ $source }}" @selected(old('source', $finding->source ?: \App\Models\Finding::SOURCE_MANUAL) === $source)>{{ __('messages.findings.sources.'.$source) }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="severity">{{ __('messages.findings.severity') }}</label>
            <select name="severity" id="severity" class="form-control" required>
                @foreach(\App\Models\Finding::SEVERITIES as $severity)
                    <option value="{{ $severity }}" @selected(old('severity', $finding->severity ?: \App\Models\Finding::SEVERITY_MEDIUM) === $severity)>{{ __('messages.findings.severities.'.$severity) }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="priority">{{ __('messages.findings.priority') }}</label>
            <select name="priority" id="priority" class="form-control" required>
                @foreach(\App\Models\Finding::PRIORITIES as $priority)
                    <option value="{{ $priority }}" @selected(old('priority', $finding->priority ?: \App\Models\Finding::PRIORITY_MEDIUM) === $priority)>{{ __('messages.findings.priorities.'.$priority) }}</option>
                @endforeach
            </select>
            @error('priority')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="status">{{ __('messages.common.status') }}</label>
            <select name="status" id="status" class="form-control" required>
                @foreach(\App\Models\Finding::LIFECYCLE_STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $finding->status ?: \App\Models\Finding::STATUS_OPEN) === $status)>{{ __('messages.findings.statuses.'.$status) }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="hpanel hblue">
    <div class="panel-heading hbuilt">{{ __('messages.findings.ownership.title') }}</div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="owner_user_id">{{ __('messages.findings.owner') }}</label>
                    <select name="owner_user_id" id="owner_user_id" class="form-control">
                        <option value="">{{ __('messages.findings.unassigned') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('owner_user_id', $finding->owner_user_id) === (string) $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                        @endforeach
                    </select>
                    @error('owner_user_id')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="due_date">{{ __('messages.findings.due_date') }}</label>
                    <input type="datetime-local" name="due_date" id="due_date" class="form-control" value="{{ old('due_date', $finding->due_date?->format('Y-m-d\TH:i')) }}">
                    @error('due_date')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="linked_release_gate_id">{{ __('messages.findings.linked_release_gate') }}</label>
                    <select name="linked_release_gate_id" id="linked_release_gate_id" class="form-control">
                        <option value="">{{ __('messages.common.none') }}</option>
                        @foreach($releaseGates as $gate)
                            <option value="{{ $gate->id }}" @selected((string) old('linked_release_gate_id', $finding->linked_release_gate_id) === (string) $gate->id)>#{{ $gate->id }} — {{ $gate->release_name }}</option>
                        @endforeach
                    </select>
                    @error('linked_release_gate_id')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
    </div>
</div>

<div class="hpanel hyellow">
    <div class="panel-heading hbuilt">{{ __('messages.findings.verification.title') }}</div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="verification_status">{{ __('messages.findings.verification_status') }}</label>
                    <select name="verification_status" id="verification_status" class="form-control" required>
                        @foreach(\App\Models\Finding::VERIFICATION_STATUSES as $verificationStatus)
                            <option value="{{ $verificationStatus }}" @selected(old('verification_status', $finding->verification_status ?: \App\Models\Finding::VERIFICATION_PENDING) === $verificationStatus)>{{ __('messages.findings.verification_statuses.'.$verificationStatus) }}</option>
                        @endforeach
                    </select>
                    @error('verification_status')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="retest_result">{{ __('messages.findings.retest_result') }}</label>
                    <select name="retest_result" id="retest_result" class="form-control">
                        <option value="">{{ __('messages.common.none') }}</option>
                        @foreach(\App\Models\Finding::RETEST_RESULTS as $retestResult)
                            <option value="{{ $retestResult }}" @selected(old('retest_result', $finding->retest_result) === $retestResult)>{{ __('messages.findings.retest_results.'.$retestResult) }}</option>
                        @endforeach
                    </select>
                    @error('retest_result')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="last_retest_at">{{ __('messages.findings.last_retest_at') }}</label>
                    <input type="datetime-local" name="last_retest_at" id="last_retest_at" class="form-control" value="{{ old('last_retest_at', $finding->last_retest_at?->format('Y-m-d\TH:i')) }}">
                    @error('last_retest_at')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="checkbox checkbox-primary">
                    <input type="hidden" name="retest_required" value="0">
                    <input type="checkbox" name="retest_required" id="retest_required" value="1" @checked(old('retest_required', $finding->retest_required))>
                    <label for="retest_required">{{ __('messages.findings.retest_required') }}</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="checkbox checkbox-primary">
                    <input type="hidden" name="fix_evidence_required" value="0">
                    <input type="checkbox" name="fix_evidence_required" id="fix_evidence_required" value="1" @checked(old('fix_evidence_required', $finding->fix_evidence_required))>
                    <label for="fix_evidence_required">{{ __('messages.findings.fix_evidence_required') }}</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="verified_at">{{ __('messages.findings.verified_at') }}</label>
                    <input type="datetime-local" name="verified_at" id="verified_at" class="form-control" value="{{ old('verified_at', $finding->verified_at?->format('Y-m-d\TH:i')) }}">
                    @error('verified_at')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
        <div class="form-group">
            <label for="verified_by_user_id">{{ __('messages.findings.verified_by') }}</label>
            <select name="verified_by_user_id" id="verified_by_user_id" class="form-control">
                <option value="">{{ __('messages.common.none') }}</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected((string) old('verified_by_user_id', $finding->verified_by_user_id) === (string) $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                @endforeach
            </select>
            @error('verified_by_user_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="accepted_risk_expires_at">{{ __('messages.findings.accepted_risk_expires_at') }}</label>
            <input type="datetime-local" name="accepted_risk_expires_at" id="accepted_risk_expires_at" class="form-control" value="{{ old('accepted_risk_expires_at', $finding->accepted_risk_expires_at?->format('Y-m-d\TH:i')) }}">
            <span class="help-block">{{ __('messages.findings.accepted_risk_expires_at_help') }}</span>
            @error('accepted_risk_expires_at')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="accepted_risk_note">{{ __('messages.findings.accepted_risk_note') }}</label>
            <textarea name="accepted_risk_note" id="accepted_risk_note" class="form-control" rows="3" placeholder="{{ __('messages.findings.accepted_risk_note_placeholder') }}">{{ old('accepted_risk_note', $finding->accepted_risk_note) }}</textarea>
            @error('accepted_risk_note')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="description">{{ __('messages.common.description') }}</label>
    <textarea name="description" id="description" class="form-control" rows="3" placeholder="{{ __('messages.findings.description_placeholder') }}">{{ old('description', $finding->description) }}</textarea>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="scan_run_id">{{ __('messages.scans.title') }}</label>
            <select name="scan_run_id" id="scan_run_id" class="form-control">
                <option value="">{{ __('messages.common.none') }}</option>
                @foreach($project->scanRuns as $scanRun)
                    <option value="{{ $scanRun->id }}" @selected((string) old('scan_run_id', $finding->scan_run_id) === (string) $scanRun->id)>#{{ $scanRun->id }} — {{ $scanRun->created_at->format('Y-m-d H:i') }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="scan_result_id">{{ __('messages.findings.scan_result') }}</label>
            <input type="number" min="1" name="scan_result_id" id="scan_result_id" class="form-control" value="{{ old('scan_result_id', $finding->scan_result_id) }}" placeholder="{{ __('messages.findings.scan_result_placeholder') }}">
        </div>
    </div>
</div>

<div class="form-group">
    <label for="contract_validation_result_id">{{ __('messages.findings.contract_result') }}</label>
    <select name="contract_validation_result_id" id="contract_validation_result_id" class="form-control">
        <option value="">{{ __('messages.common.none') }}</option>
        @foreach($project->contractValidationResults as $result)
            <option value="{{ $result->id }}" @selected((string) old('contract_validation_result_id', $finding->contract_validation_result_id) === (string) $result->id)>#{{ $result->id }} — {{ $result->check_type_label }} — {{ $result->method }} {{ $result->path }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="reproduction_steps">{{ __('messages.findings.reproduction_steps') }}</label>
    <textarea name="reproduction_steps" id="reproduction_steps" class="form-control" rows="5" placeholder="{{ __('messages.findings.reproduction_steps_placeholder') }}">{{ old('reproduction_steps', $finding->reproduction_steps) }}</textarea>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="expected_result">{{ __('messages.test_cases.expected_result') }}</label>
            <textarea name="expected_result" id="expected_result" class="form-control" rows="4">{{ old('expected_result', $finding->expected_result) }}</textarea>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="actual_result">{{ __('messages.test_cases.actual_result') }}</label>
            <textarea name="actual_result" id="actual_result" class="form-control" rows="4">{{ old('actual_result', $finding->actual_result) }}</textarea>
        </div>
    </div>
</div>

<div class="form-group">
    <label for="recommendation">{{ __('messages.findings.recommendation') }}</label>
    <textarea name="recommendation" id="recommendation" class="form-control" rows="4" placeholder="{{ __('messages.findings.recommendation_placeholder') }}">{{ old('recommendation', $finding->recommendation) }}</textarea>
</div>

<button type="submit" class="btn btn-success">{{ __('messages.common.save') }}</button>
<a href="{{ route('projects.findings.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
