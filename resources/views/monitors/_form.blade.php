@csrf

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>{{ __('messages.common.name') }}</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $monitor->name) }}" required maxlength="180" placeholder="{{ __('messages.monitors.name_placeholder') }}">
            @error('name')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>{{ __('messages.monitors.frequency') }}</label>
            <select name="frequency" class="form-control" required>
                @foreach(\App\Models\ApiMonitor::FREQUENCIES as $frequency)
                    <option value="{{ $frequency }}" @selected(old('frequency', $monitor->frequency) === $frequency)>{{ __('messages.monitors.frequencies.'.$frequency) }}</option>
                @endforeach
            </select>
            @error('frequency')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>{{ __('messages.environments.title') }}</label>
            <select name="environment_id" class="form-control">
                <option value="">{{ __('messages.endpoints.project_default') }}</option>
                @foreach($environments as $environment)
                    <option value="{{ $environment->id }}" @selected((string) old('environment_id', $monitor->environment_id) === (string) $environment->id)>{{ $environment->name }} — {{ $environment->base_url }}</option>
                @endforeach
            </select>
            @error('environment_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>{{ __('messages.monitors.baseline_snapshot') }}</label>
            <select name="baseline_snapshot_id" class="form-control">
                <option value="">{{ __('messages.monitors.use_previous_snapshot') }}</option>
                @foreach($snapshots as $snapshot)
                    <option value="{{ $snapshot->id }}" @selected((string) old('baseline_snapshot_id', $monitor->baseline_snapshot_id) === (string) $snapshot->id)>#{{ $snapshot->id }} — {{ $snapshot->name }} — {{ $snapshot->created_at->format('Y-m-d H:i') }}</option>
                @endforeach
            </select>
            <small class="text-muted">{{ __('messages.monitors.baseline_help') }}</small>
            @error('baseline_snapshot_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>{{ __('messages.monitors.test_suite') }}</label>
            <select name="test_suite_id" class="form-control">
                <option value="">{{ __('messages.monitors.all_project_endpoints') }}</option>
                @foreach($testSuites as $testSuite)
                    <option value="{{ $testSuite->id }}" @selected((string) old('test_suite_id', $monitor->test_suite_id) === (string) $testSuite->id)>{{ $testSuite->name }}</option>
                @endforeach
            </select>
            <small class="text-muted">{{ __('messages.monitors.test_suite_help') }}</small>
            @error('test_suite_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <label>{{ __('messages.monitors.behaviour') }}</label>
        <div class="checkbox"><label><input type="checkbox" name="is_enabled" value="1" @checked(old('is_enabled', $monitor->is_enabled))> {{ __('messages.monitors.enabled') }}</label></div>
        <div class="checkbox"><label><input type="checkbox" name="auto_snapshot" value="1" @checked(old('auto_snapshot', $monitor->auto_snapshot))> {{ __('messages.monitors.auto_snapshot') }}</label></div>
        <div class="checkbox"><label><input type="checkbox" name="auto_compare" value="1" @checked(old('auto_compare', $monitor->auto_compare))> {{ __('messages.monitors.auto_compare') }}</label></div>
        <div class="checkbox"><label><input type="checkbox" name="notify_dashboard" value="1" @checked(old('notify_dashboard', $monitor->notify_dashboard))> {{ __('messages.monitors.notify_dashboard') }}</label></div>
        <div class="checkbox"><label><input type="checkbox" name="alert_on_recovery" value="1" @checked(old('alert_on_recovery', $monitor->alert_on_recovery ?? true))> {{ __('messages.monitors.alert_on_recovery') }}</label></div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.monitors.notification_triggers_title') }}</div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.monitors.notification_triggers_help') }}</p>
                <div class="row">
                    <div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="alert_on_critical_finding" value="1" @checked(old('alert_on_critical_finding', $monitor->alert_on_critical_finding ?? true))> {{ __('messages.monitors.notification_triggers.critical_findings') }}</label></div></div>
                    <div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="alert_on_high_finding" value="1" @checked(old('alert_on_high_finding', $monitor->alert_on_high_finding ?? true))> {{ __('messages.monitors.notification_triggers.high_findings') }}</label></div></div>
                    <div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="alert_on_http_5xx" value="1" @checked(old('alert_on_http_5xx', $monitor->alert_on_http_5xx ?? true))> {{ __('messages.monitors.notification_triggers.http_5xx') }}</label></div></div>
                    <div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="alert_on_sensitive_data" value="1" @checked(old('alert_on_sensitive_data', $monitor->alert_on_sensitive_data ?? true))> {{ __('messages.monitors.notification_triggers.sensitive_data') }}</label></div></div>
                    <div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="alert_on_broken_auth" value="1" @checked(old('alert_on_broken_auth', $monitor->alert_on_broken_auth ?? true))> {{ __('messages.monitors.notification_triggers.broken_auth') }}</label></div></div>
                    <div class="col-md-4"><div class="checkbox"><label><input type="checkbox" name="alert_on_schema_drift" value="1" @checked(old('alert_on_schema_drift', $monitor->alert_on_schema_drift ?? true))> {{ __('messages.monitors.notification_triggers.schema_drift') }}</label></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>{{ __('messages.monitors.alert_email') }}</label>
            <input type="email" name="alert_email" class="form-control" value="{{ old('alert_email', $monitor->alert_email) }}" maxlength="180" placeholder="qa@example.com">
            <small class="text-muted">{{ __('messages.monitors.alert_email_help') }}</small>
            @error('alert_email')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>{{ __('messages.monitors.alert_webhook_url') }}</label>
            <input type="url" name="alert_webhook_url" class="form-control" value="{{ old('alert_webhook_url', $monitor->alert_webhook_url) }}" maxlength="2048" placeholder="https://hooks.example.test/aptoria">
            <small class="text-muted">{{ __('messages.monitors.alert_webhook_help') }}</small>
            @error('alert_webhook_url')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="alert alert-info">
    <strong>{{ __('messages.monitors.scheduler_command') }}</strong><br>
    <code>C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --limit=50 --save-json</code><br><code>php artisan aptoria:run-monitors --project={{ $project->slug }} --dry-run --json</code>
    <p class="m-t-sm m-b-none">{{ __('messages.monitors.scheduler_help') }}</p>
</div>

<button type="submit" class="btn btn-success">{{ __('messages.common.save') }}</button>
<a href="{{ route('projects.monitors.index', $project) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
