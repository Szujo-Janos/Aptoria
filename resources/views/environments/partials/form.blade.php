@php
    $selectedType = old('environment_type', $environment?->environment_type ?? 'dev');
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.environments.name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $environment?->name) }}" placeholder="{{ __('messages.environments.name_placeholder') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.environments.type') }}</label>
        <select name="environment_type" class="form-select" required>
            @foreach (['local','dev','staging','production','custom'] as $type)
                <option value="{{ $type }}" @selected($selectedType === $type)>{{ __('messages.environments.types.'.$type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('messages.environments.base_url') }}</label>
        <input type="url" name="base_url" class="form-control" value="{{ old('base_url', $environment?->base_url) }}" placeholder="{{ __('messages.environments.base_url_placeholder') }}" required>
        <div class="form-text">{{ __('messages.environments.base_url_help') }}</div>
    </div>
    <div class="col-md-6">
        <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="is_default" value="1" id="envDefault{{ $environment?->id ?? 'new' }}" @checked(old('is_default', $environment?->is_default))>
            <label for="envDefault{{ $environment?->id ?? 'new' }}" class="form-check-label">{{ __('messages.environments.is_default') }}</label>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-check form-switch">
            <input type="checkbox" class="form-check-input" name="is_production" value="1" id="envProduction{{ $environment?->id ?? 'new' }}" @checked(old('is_production', $environment?->is_production))>
            <label for="envProduction{{ $environment?->id ?? 'new' }}" class="form-check-label">{{ __('messages.environments.is_production') }}</label>
        </div>
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('messages.common.notes') }}</label>
        <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('messages.environments.notes_placeholder') }}">{{ old('notes', $environment?->notes) }}</textarea>
    </div>
</div>
