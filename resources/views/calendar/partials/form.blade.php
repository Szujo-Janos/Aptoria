@php
    $event = $event ?? null;
@endphp
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">{{ __('messages.calendar.title_field') }}</label>
        <input type="text" name="title" class="form-control" required maxlength="180" value="{{ old('title', $event?->title) }}" placeholder="{{ __('messages.form_plugin.placeholders.calendar.title') }}">
        <div class="form-text">{{ __('messages.calendar.title_help') }}</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.calendar.priority') }}</label>
        <select name="priority" class="form-select" required>
            @foreach(\App\Models\CalendarEvent::PRIORITIES as $priority)
                <option value="{{ $priority }}" @selected(old('priority', $event?->priority ?? 'normal') === $priority)>{{ __('messages.calendar.priorities.'.$priority) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.calendar.type') }}</label>
        <select name="event_type" class="form-select" required>
            @foreach(\App\Models\CalendarEvent::TYPES as $type)
                <option value="{{ $type }}" @selected(old('event_type', $event?->event_type ?? 'manual_qa_task') === $type)>{{ __('messages.calendar.types.'.$type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.common.status') }}</label>
        <select name="status" class="form-select" required>
            @foreach(\App\Models\CalendarEvent::STATUSES as $status)
                <option value="{{ $status }}" @selected(old('status', $event?->status ?? 'planned') === $status)>{{ __('messages.calendar.statuses.'.$status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.calendar.start_at') }}</label>
        <input type="datetime-local" name="start_at" class="form-control" value="{{ old('start_at', $event?->start_at?->format('Y-m-d\\TH:i')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.calendar.end_at') }}</label>
        <input type="datetime-local" name="end_at" class="form-control" value="{{ old('end_at', $event?->end_at?->format('Y-m-d\\TH:i')) }}">
    </div>
    <div class="col-md-8">
        <label class="form-label">{{ __('messages.calendar.location') }}</label>
        <input type="text" name="location" class="form-control" value="{{ old('location', $event?->location) }}" placeholder="{{ __('messages.form_plugin.placeholders.calendar.location') }}">
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_all_day" value="1" @checked(old('is_all_day', $event?->is_all_day))><span class="form-check-label">{{ __('messages.calendar.all_day') }}</span></label>
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('messages.calendar.description') }}</label>
        <textarea name="description" class="form-control" rows="4" placeholder="{{ __('messages.form_plugin.placeholders.calendar.description') }}">{{ old('description', $event?->description) }}</textarea>
        <div class="form-text">{{ __('messages.calendar.description_help') }}</div>
    </div>
</div>
