@csrf

<div class="row">
    <div class="col-md-8">
        <div class="form-group">
            <label for="title">{{ __('messages.calendar.title_field') }}</label>
            <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $calendarEvent->title) }}" required maxlength="220" placeholder="{{ __('messages.calendar.title_placeholder') }}">
            @error('title')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="project_id">{{ __('messages.nav.projects') }}</label>
            <select name="project_id" id="project_id" class="form-control">
                <option value="">{{ __('messages.calendar.no_project') }}</option>
                @foreach($projects as $projectOption)
                    <option value="{{ $projectOption->id }}" @selected((string) old('project_id', $calendarEvent->project_id) === (string) $projectOption->id)>{{ $projectOption->name }}</option>
                @endforeach
            </select>
            @error('project_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="event_type">{{ __('messages.calendar.event_type') }}</label>
            <select name="event_type" id="event_type" class="form-control" required>
                @foreach(\App\Models\CalendarEvent::MANUAL_TYPES as $type)
                    <option value="{{ $type }}" @selected(old('event_type', $calendarEvent->event_type) === $type)>{{ __('messages.calendar.types.'.$type) }}</option>
                @endforeach
            </select>
            @error('event_type')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="status">{{ __('messages.common.status') }}</label>
            <select name="status" id="status" class="form-control" required>
                @foreach(\App\Models\CalendarEvent::STATUSES as $status)
                    <option value="{{ $status }}" @selected(old('status', $calendarEvent->status) === $status)>{{ __('messages.calendar.statuses.'.$status) }}</option>
                @endforeach
            </select>
            @error('status')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="priority">{{ __('messages.calendar.priority') }}</label>
            <select name="priority" id="priority" class="form-control" required>
                @foreach(\App\Models\CalendarEvent::PRIORITIES as $priority)
                    <option value="{{ $priority }}" @selected(old('priority', $calendarEvent->priority) === $priority)>{{ __('messages.calendar.priorities.'.$priority) }}</option>
                @endforeach
            </select>
            @error('priority')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="starts_at">{{ __('messages.calendar.starts_at') }}</label>
            <input type="datetime-local" name="starts_at" id="starts_at" class="form-control" value="{{ old('starts_at', optional($calendarEvent->starts_at)->format('Y-m-d\TH:i')) }}" required>
            @error('starts_at')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="ends_at">{{ __('messages.calendar.ends_at') }}</label>
            <input type="datetime-local" name="ends_at" id="ends_at" class="form-control" value="{{ old('ends_at', optional($calendarEvent->ends_at)->format('Y-m-d\TH:i')) }}">
            @error('ends_at')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>&nbsp;</label>
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="all_day" value="1" @checked(old('all_day', $calendarEvent->all_day))>
                    {{ __('messages.calendar.all_day') }}
                </label>
            </div>
            @error('all_day')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label for="description">{{ __('messages.common.description') }}</label>
    <textarea name="description" id="description" class="form-control" rows="4" placeholder="{{ __('messages.calendar.description_placeholder') }}">{{ old('description', $calendarEvent->description) }}</textarea>
    @error('description')<span class="text-danger">{{ $message }}</span>@enderror
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label for="endpoint_id">{{ __('messages.endpoints.title') }}</label>
            <select name="endpoint_id" id="endpoint_id" class="form-control">
                <option value="">{{ __('messages.common.none') }}</option>
                @foreach($endpoints as $endpoint)
                    <option value="{{ $endpoint->id }}" @selected((string) old('endpoint_id', $calendarEvent->endpoint_id) === (string) $endpoint->id)>{{ $endpoint->project?->name }} · {{ $endpoint->method }} {{ $endpoint->path }}</option>
                @endforeach
            </select>
            @error('endpoint_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="api_monitor_id">{{ __('messages.monitors.title') }}</label>
            <select name="api_monitor_id" id="api_monitor_id" class="form-control">
                <option value="">{{ __('messages.common.none') }}</option>
                @foreach($monitors as $monitor)
                    <option value="{{ $monitor->id }}" @selected((string) old('api_monitor_id', $calendarEvent->api_monitor_id) === (string) $monitor->id)>{{ $monitor->project?->name }} · {{ $monitor->name }}</option>
                @endforeach
            </select>
            @error('api_monitor_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="monitor_alert_event_id">{{ __('messages.calendar.alert_event') }}</label>
            <select name="monitor_alert_event_id" id="monitor_alert_event_id" class="form-control">
                <option value="">{{ __('messages.common.none') }}</option>
                @foreach($alerts as $alert)
                    <option value="{{ $alert->id }}" @selected((string) old('monitor_alert_event_id', $calendarEvent->monitor_alert_event_id) === (string) $alert->id)>{{ $alert->project?->name }} · {{ $alert->status }} · {{ $alert->created_at?->format('Y-m-d H:i') }}</option>
                @endforeach
            </select>
            @error('monitor_alert_event_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label for="qa_release_gate_id">{{ __('messages.release_gates.title') }}</label>
            <select name="qa_release_gate_id" id="qa_release_gate_id" class="form-control">
                <option value="">{{ __('messages.common.none') }}</option>
                @foreach($releaseGates as $gate)
                    <option value="{{ $gate->id }}" @selected((string) old('qa_release_gate_id', $calendarEvent->qa_release_gate_id) === (string) $gate->id)>{{ $gate->project?->name }} · {{ $gate->release_name }}</option>
                @endforeach
            </select>
            @error('qa_release_gate_id')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
</div>

<button type="submit" class="btn btn-success">{{ __('messages.common.save') }}</button>
<a href="{{ route('calendar.index', ['project_id' => old('project_id', $calendarEvent->project_id)]) }}" class="btn btn-default">{{ __('messages.common.cancel') }}</a>
