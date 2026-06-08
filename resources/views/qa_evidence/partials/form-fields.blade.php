<div class="form-group">
    <label class="col-sm-4 control-label">{{ __('messages.qa_evidence.baseline_snapshot') }}</label>
    <div class="col-sm-8">
        <select name="baseline_snapshot_id" class="form-control">
            <option value="">{{ __('messages.common.none') }}</option>
            @foreach($snapshots as $snapshot)
                <option value="{{ $snapshot->id }}" @selected((int) ($defaults['baseline_snapshot_id'] ?? 0) === $snapshot->id)>#{{ $snapshot->id }} — {{ $snapshot->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-4 control-label">{{ __('messages.qa_evidence.validation_snapshot') }}</label>
    <div class="col-sm-8">
        <select name="validation_snapshot_id" class="form-control">
            <option value="">{{ __('messages.common.none') }}</option>
            @foreach($snapshots as $snapshot)
                <option value="{{ $snapshot->id }}" @selected((int) ($defaults['validation_snapshot_id'] ?? 0) === $snapshot->id)>#{{ $snapshot->id }} — {{ $snapshot->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-4 control-label">{{ __('messages.qa_evidence.negative_snapshot') }}</label>
    <div class="col-sm-8">
        <select name="negative_snapshot_id" class="form-control">
            <option value="">{{ __('messages.common.none') }}</option>
            @foreach($snapshots as $snapshot)
                <option value="{{ $snapshot->id }}" @selected((int) ($defaults['negative_snapshot_id'] ?? 0) === $snapshot->id)>#{{ $snapshot->id }} — {{ $snapshot->name }}</option>
            @endforeach
        </select>
        <span class="help-block">{{ __('messages.qa_evidence.negative_control_warning') }}</span>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-4 control-label">{{ __('messages.qa_evidence.recovery_snapshot') }}</label>
    <div class="col-sm-8">
        <select name="recovery_snapshot_id" class="form-control">
            <option value="">{{ __('messages.common.none') }}</option>
            @foreach($snapshots as $snapshot)
                <option value="{{ $snapshot->id }}" @selected((int) ($defaults['recovery_snapshot_id'] ?? 0) === $snapshot->id)>#{{ $snapshot->id }} — {{ $snapshot->name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-4 control-label">{{ __('messages.qa_evidence.compare_runs') }}</label>
    <div class="col-sm-8">
        @if($compareRuns->isEmpty())
            <p class="form-control-static text-muted">{{ __('messages.snapshots.no_compares') }}</p>
        @else
            <div class="well well-sm m-b-none" style="max-height: 220px; overflow-y: auto;">
                @foreach($compareRuns as $compareRun)
                    <div class="checkbox m-t-xs m-b-xs">
                        <label>
                            <input type="checkbox" name="compare_run_ids[]" value="{{ $compareRun->id }}" @checked(in_array($compareRun->id, $defaults['compare_run_ids'] ?? [], true))>
                            #{{ $compareRun->id }} — {{ $compareRun->snapshotA?->name ?: 'n/a' }} → {{ $compareRun->snapshotB?->name ?: 'n/a' }}
                        </label>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
<div class="form-group">
    <label class="col-sm-4 control-label">{{ __('messages.qa_evidence.final_decision') }}</label>
    <div class="col-sm-8">
        <select name="final_decision" class="form-control">
            <option value="">{{ __('messages.qa_evidence.use_auto_decision') }}: {{ $context['final_decision_label'] }}</option>
            <option value="pass">PASS</option>
            <option value="pass_with_warning">PASS WITH WARNING</option>
            <option value="fail">FAIL</option>
            <option value="blocked">BLOCKED</option>
        </select>
    </div>
</div>
<div class="form-group">
    <label class="col-sm-4 control-label">{{ __('messages.qa_evidence.decision_reason') }}</label>
    <div class="col-sm-8">
        <textarea name="decision_reason" class="form-control" rows="4" placeholder="{{ __('messages.qa_evidence.decision_reason_placeholder') }}">{{ $context['decision_reason'] }}</textarea>
    </div>
</div>
