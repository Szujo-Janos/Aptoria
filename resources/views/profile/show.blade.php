@extends('layouts.app')

@section('title', __('messages.profile.title'))

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">
                <i class="fa fa-user-circle"></i> {{ __('messages.profile.title') }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.profile.intro') }}</p>

                <form method="POST" action="{{ route('profile.update') }}" class="m-t-md">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group @error('name') has-error @enderror">
                                <label>{{ __('messages.profile.name') }}</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" maxlength="120" required>
                                @error('name')<span class="help-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group @error('email') has-error @enderror">
                                <label>{{ __('messages.profile.email') }}</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" maxlength="255" required>
                                @error('email')<span class="help-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group @error('locale') has-error @enderror">
                                <label>{{ __('messages.profile.locale') }}</label>
                                <select name="locale" class="form-control" required>
                                    @foreach($supportedLocales as $localeCode => $localeName)
                                        <option value="{{ $localeCode }}" @selected(old('locale', $currentLocale) === $localeCode)>{{ $localeName }}</option>
                                    @endforeach
                                </select>
                                @error('locale')<span class="help-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group @error('timezone') has-error @enderror">
                                <label>{{ __('messages.profile.timezone') }}</label>
                                <input type="text" name="timezone" class="form-control" value="{{ old('timezone', $currentTimezone) }}" placeholder="Europe/Budapest">
                                <span class="help-block">{{ __('messages.profile.timezone_help') }}</span>
                                @error('timezone')<span class="help-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <hr>
                    <h4 class="m-t-md"><i class="fa fa-id-card-o"></i> {{ __('messages.profile.report_identity_title') }}</h4>
                    <p class="text-muted">{{ __('messages.profile.report_identity_intro') }}</p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group @error('report_display_name') has-error @enderror">
                                <label>{{ __('messages.profile.report_display_name') }}</label>
                                <input type="text" name="report_display_name" class="form-control" value="{{ old('report_display_name', $user->report_display_name ?: $user->name) }}" maxlength="120">
                                <span class="help-block">{{ __('messages.profile.report_display_name_help') }}</span>
                                @error('report_display_name')<span class="help-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group @error('report_role_title') has-error @enderror">
                                <label>{{ __('messages.profile.report_role_title') }}</label>
                                <input type="text" name="report_role_title" class="form-control" value="{{ old('report_role_title', $user->report_role_title) }}" maxlength="160" placeholder="QA Engineer / Developer">
                                @error('report_role_title')<span class="help-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group @error('report_organization') has-error @enderror">
                                <label>{{ __('messages.profile.report_organization') }}</label>
                                <input type="text" name="report_organization" class="form-control" value="{{ old('report_organization', $user->report_organization) }}" maxlength="160" placeholder="Portfolio QA Lab / Client name">
                                <span class="help-block">{{ __('messages.profile.report_organization_help') }}</span>
                                @error('report_organization')<span class="help-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group @error('report_github_url') has-error @enderror">
                                <label>{{ __('messages.profile.report_github_url') }}</label>
                                <input type="url" name="report_github_url" class="form-control" value="{{ old('report_github_url', $user->report_github_url) }}" maxlength="255" placeholder="https://github.com/Szujo-Janos">
                                @error('report_github_url')<span class="help-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group @error('report_website_url') has-error @enderror">
                        <label>{{ __('messages.profile.report_website_url') }}</label>
                        <input type="url" name="report_website_url" class="form-control" value="{{ old('report_website_url', $user->report_website_url) }}" maxlength="255" placeholder="https://example.com">
                        @error('report_website_url')<span class="help-block">{{ $message }}</span>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ __('messages.profile.save_profile') }}</button>
                </form>
            </div>
        </div>

        <div class="hpanel hyellow">
            <div class="panel-heading hbuilt">
                <i class="fa fa-lock"></i> {{ __('messages.profile.password_title') }}
            </div>
            <div class="panel-body">
                <p class="text-muted">{{ __('messages.profile.password_intro') }}</p>

                <form method="POST" action="{{ route('profile.password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group @error('current_password') has-error @enderror">
                        <label>{{ __('messages.profile.current_password') }}</label>
                        <input type="password" name="current_password" class="form-control" autocomplete="current-password" required>
                        @error('current_password')<span class="help-block">{{ $message }}</span>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group @error('password') has-error @enderror">
                                <label>{{ __('messages.profile.new_password') }}</label>
                                <input type="password" name="password" class="form-control" autocomplete="new-password" required>
                                @error('password')<span class="help-block">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('messages.profile.confirm_password') }}</label>
                                <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning"><i class="fa fa-key"></i> {{ __('messages.profile.change_password') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="hpanel hgreen">
            <div class="panel-heading hbuilt">{{ __('messages.profile.account_info') }}</div>
            <div class="panel-body">
                <dl class="dl-horizontal m-b-none">
                    <dt>{{ __('messages.profile.role') }}</dt><dd><span class="label label-info">{{ ucfirst((string) $user->role) }}</span></dd>
                    <dt>{{ __('messages.profile.created_at') }}</dt><dd>{{ optional($user->created_at)->format('Y-m-d H:i') }}</dd>
                    <dt>{{ __('messages.profile.updated_at') }}</dt><dd>{{ optional($user->updated_at)->format('Y-m-d H:i') }}</dd>
                    <dt>{{ __('messages.profile.session_timeout') }}</dt><dd>{{ $sessionTimeoutMinutes }} {{ __('messages.profile.minutes') }}</dd>
                    <dt>{{ __('messages.profile.setup_completed') }}</dt><dd>{{ $setupCompleted ? __('messages.common.yes') : __('messages.common.no') }}</dd>
                    <dt>{{ __('messages.profile.version') }}</dt><dd>Aptoria v{{ config('aptoria.version') }}</dd>
                </dl>
            </div>
        </div>

        <div class="hpanel hblue">
            <div class="panel-heading hbuilt">{{ __('messages.profile.activity_summary') }}</div>
            <div class="panel-body">
                <div class="row text-center">
                    <div class="col-xs-6">
                        <h3 class="m-b-xs">{{ $activity['projects'] }}</h3>
                        <small>{{ __('messages.profile.projects') }}</small>
                    </div>
                    <div class="col-xs-6">
                        <h3 class="m-b-xs">{{ $activity['open_findings'] }}</h3>
                        <small>{{ __('messages.profile.open_findings') }}</small>
                    </div>
                </div>
                <hr>
                <p><strong>{{ __('messages.profile.latest_scan') }}:</strong><br>{{ $activity['latest_scan'] ? $activity['latest_scan']->format('Y-m-d H:i') : __('messages.common.not_available') }}</p>
                <p><strong>{{ __('messages.profile.latest_release_gate') }}:</strong><br>{{ $activity['latest_release_gate'] ? $activity['latest_release_gate']->format('Y-m-d H:i') : __('messages.common.not_available') }}</p>
                <p class="m-b-none"><strong>{{ __('messages.profile.latest_release_decision') }}:</strong><br>{{ $activity['latest_release_decision'] ? __('messages.release_gates.decisions.'.$activity['latest_release_decision']) : __('messages.common.not_available') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
