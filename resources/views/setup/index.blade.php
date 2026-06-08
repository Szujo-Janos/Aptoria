@extends('layouts.auth')

@section('title', __('messages.setup.title'))

@section('content')
<div class="container" style="max-width: 1100px; margin-top: 40px; margin-bottom: 40px;">
    <div class="row">
        <div class="col-md-12 text-center m-b-lg">
            <img src="{{ asset('assets/aptoria/img/aptoria-logo-horizontal.png') }}" alt="Aptoria" style="max-width: 260px; width: 100%; height: auto;">
            <h2 class="m-t-md">{{ __('messages.setup.title') }}</h2>
            <p class="text-muted">{{ __('messages.setup.subtitle') }}</p>
            <div class="btn-group btn-group-xs m-t-sm">
                @foreach(config('aptoria.supported_locales') as $localeCode => $localeName)
                    <a href="{{ route('language.switch', $localeCode) }}" class="btn {{ app()->getLocale() === $localeCode ? 'btn-primary' : 'btn-default' }}">{{ $localeName }}</a>
                @endforeach
            </div>
        </div>
    </div>

    @if(! $setupState->isLocked())
        <div class="alert alert-info">{{ __('messages.setup.first_run_help') }}</div>
    @endif

    <div class="row">
        <div class="col-md-4">
            <div class="hpanel">
                <div class="panel-heading hbuilt">{{ __('messages.setup.summary') }}</div>
                <div class="panel-body">
                    <div class="row text-center">
                        <div class="col-xs-3"><h3 class="text-success">{{ $report['summary']['ok'] }}</h3><small>{{ __('messages.setup.ok_status') }}</small></div>
                        <div class="col-xs-3"><h3 class="text-warning">{{ $report['summary']['warnings'] }}</h3><small>{{ __('messages.setup.warnings') }}</small></div>
                        <div class="col-xs-3"><h3 class="text-danger">{{ $report['summary']['failed'] }}</h3><small>{{ __('messages.setup.failed') }}</small></div>
                        <div class="col-xs-3"><h3 class="text-info">{{ $report['summary']['info'] }}</h3><small>{{ __('messages.setup.info') }}</small></div>
                    </div>
                    <hr>
                    <p><strong>{{ __('messages.setup.status') }}:</strong>
                        @if($setupState->isLocked())
                            <span class="label label-success">{{ __('messages.setup.status_locked') }}</span>
                        @elseif($isInstalled)
                            <span class="label label-info">{{ __('messages.setup.status_installed_without_lock') }}</span>
                        @else
                            <span class="label label-warning">{{ __('messages.setup.status_not_installed') }}</span>
                        @endif
                    </p>
                    <p class="text-muted small">{{ __('messages.setup.summary_help') }}</p>
                </div>
            </div>

            <div class="hpanel">
                <div class="panel-heading hbuilt">{{ __('messages.setup.quick_actions') }}</div>
                <div class="panel-body">
                    @if($setupState->isLocked())
                        <div class="alert alert-success m-b-none">{{ __('messages.setup.locked_help') }}</div>
                    @else
                        <form method="POST" action="{{ route('setup.env') }}" class="m-b-xs">@csrf <button class="btn btn-default btn-block" type="submit">{{ __('messages.setup.create_env') }}</button></form>
                        <form method="POST" action="{{ route('setup.sqlite') }}" class="m-b-xs">@csrf <button class="btn btn-default btn-block" type="submit">{{ __('messages.setup.create_sqlite') }}</button></form>
                        <form method="POST" action="{{ route('setup.key') }}" class="m-b-xs">@csrf <button class="btn btn-warning btn-block" type="submit">{{ __('messages.setup.generate_key') }}</button></form>
                        <form method="POST" action="{{ route('setup.migrate') }}" class="m-b-xs">@csrf <button class="btn btn-primary btn-block" type="submit">{{ __('messages.setup.run_migrations') }}</button></form>
                        <form method="POST" action="{{ route('setup.demo') }}" class="m-b-xs">
                            @csrf
                            <button class="btn btn-info btn-block" type="submit" {{ ! $migrationsReady ? 'disabled' : '' }}>
                                {{ $demoImported ? __('messages.setup.reimport_demo') : __('messages.setup.import_demo') }}
                            </button>
                        </form>
                        <p class="text-muted small m-b-none">{{ __('messages.setup.demo_help') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="hpanel">
                <div class="panel-heading hbuilt">{{ __('messages.setup.environment_checks') }}</div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-condensed">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.setup.check') }}</th>
                                    <th>{{ __('messages.setup.result') }}</th>
                                    <th>{{ __('messages.setup.detail') }}</th>
                                    <th>{{ __('messages.setup.fix') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($report['checks'] as $check)
                                @php
                                    $labelClass = match($check['status']) {
                                        'ok' => 'label-success',
                                        'warning' => 'label-warning',
                                        'info' => 'label-info',
                                        default => 'label-danger',
                                    };
                                @endphp
                                <tr>
                                    <td><strong>{{ $check['label'] }}</strong><br><small class="text-muted">{{ $check['key'] }}</small></td>
                                    <td><span class="label {{ $labelClass }}">{{ strtoupper($check['status']) }}</span></td>
                                    <td><small>{{ $check['detail'] }}</small></td>
                                    <td><small class="text-muted">{{ $check['fix'] ?: '—' }}</small></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if(! $setupState->isLocked())
                <div class="row">
                    <div class="col-md-6">
                        <div class="hpanel">
                            <div class="panel-heading hbuilt">{{ __('messages.setup.admin_user') }}</div>
                            <div class="panel-body">
                                <form method="POST" action="{{ route('setup.admin') }}">
                                    @csrf
                                    <div class="form-group">
                                        <label>{{ __('messages.setup.admin_name') }}</label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name', 'Aptoria Admin') }}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('messages.setup.admin_email') }}</label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email', config('aptoria.default_admin.email')) }}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('messages.setup.admin_password') }}</label>
                                        <input type="password" name="password" class="form-control" required minlength="8">
                                    </div>
                                    <div class="form-group">
                                        <label>{{ __('messages.setup.admin_password_confirmation') }}</label>
                                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                                    </div>
                                    <button class="btn btn-success btn-block" type="submit">{{ __('messages.setup.save_admin') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="hpanel">
                            <div class="panel-heading hbuilt">{{ __('messages.setup.finish_title') }}</div>
                            <div class="panel-body">
                                <p class="text-muted">{{ __('messages.setup.finish_help') }}</p>
                                <form method="POST" action="{{ route('setup.finish') }}">
                                    @csrf
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="confirm" value="1"> {{ __('messages.setup.finish_confirm') }}</label>
                                    </div>
                                    <button class="btn btn-primary btn-block" type="submit">{{ __('messages.setup.finish_button') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center m-b-lg">
                    <a href="{{ route('login') }}" class="btn btn-success">{{ __('messages.setup.go_login') }}</a>
                    <a href="{{ route('landing') }}" class="btn btn-default">{{ __('messages.setup.go_landing') }}</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
