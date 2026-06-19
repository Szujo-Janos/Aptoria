<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.profile.name') }}</label>
        <div class="input-group"><span class="input-group-text"><i data-lucide="id-card"></i></span><input type="text" name="name" class="form-control" required maxlength="255" placeholder="{{ __('messages.users.name_placeholder') }}" value="{{ old('name', $managedUser?->name) }}"></div>
        <div class="form-text">{{ __('messages.users.name_help') }}</div>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('messages.auth.email') }}</label>
        <div class="input-group"><span class="input-group-text"><i data-lucide="mail"></i></span><input type="email" name="email" class="form-control" required maxlength="255" placeholder="{{ __('messages.users.email_placeholder') }}" value="{{ old('email', $managedUser?->email) }}"></div>
        <div class="form-text">{{ __('messages.users.email_help') }}</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.users.system_role') }}</label>
        <select class="form-select" name="role" required>
            @foreach ($systemRoles as $systemRole)
                <option value="{{ $systemRole }}" @selected(old('role', $managedUser?->role ?? 'user') === $systemRole)>{{ __('messages.profile.roles.'.$systemRole) }}</option>
            @endforeach
        </select>
        <div class="form-text">{{ __('messages.users.system_role_help') }}</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.profile.language') }}</label>
        <select class="form-select" name="locale" required>
            @foreach ($supportedLocales as $locale => $label)
                <option value="{{ $locale }}" @selected(old('locale', $managedUser?->locale ?? app()->getLocale()) === $locale)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="form-text">{{ __('messages.users.locale_help') }}</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.profile.timezone') }}</label>
        <select class="form-select" name="timezone" required>
            @foreach ($supportedTimezones as $timezone)
                <option value="{{ $timezone }}" @selected(old('timezone', $managedUser?->timezone ?? config('app.timezone', 'Europe/Budapest')) === $timezone)>{{ $timezone }}</option>
            @endforeach
        </select>
        <div class="form-text">{{ __('messages.users.timezone_help') }}</div>
    </div>
</div>
