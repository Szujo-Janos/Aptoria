@extends('layouts.auth')

@section('title', __('messages.setup.title'))
@section('body_class', 'auth-bg aptoria-setup-page')

@section('content')
<div class="aptoria-setup-background" aria-hidden="true"></div>
<div id="aptoria-setup-page-loader" class="aptoria-setup-page-loader" role="status" aria-live="polite">
    <div class="aptoria-setup-loader-card">
        <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-loader-logo">
        <div class="aptoria-setup-loader-mark"><span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span></div>
        <strong>{{ __('messages.setup.page_loading_title') }}</strong>
        <span>{{ __('messages.setup.page_loading_text') }}</span>
    </div>
</div>

<div id="aptoria-install-progress-overlay" class="aptoria-install-progress-overlay" role="status" aria-live="polite" aria-hidden="true">
    <div class="aptoria-install-progress-card">
        <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-progress-logo">
        <div class="aptoria-install-progress-spinner"><span class="spinner-border text-primary" aria-hidden="true"></span></div>
        <h3>{{ __('messages.setup.install_progress_title') }}</h3>
        <p id="aptoria-install-progress-line">{{ __('messages.setup.install_progress_waiting') }}</p>
        <div class="progress aptoria-install-progress-bar"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div></div>
        <small>{{ __('messages.setup.install_progress_help') }}</small>
    </div>
</div>

<div class="aptoria-guided-setup" data-initial-step="{{ $activeSetupStep }}">
    <div class="aptoria-setup-shell">
        <header class="aptoria-setup-brandbar">
            <div class="aptoria-setup-brand-actions">
                <button class="topbar-link light-dark-mode aptoria-setup-control-pill" type="button" aria-label="{{ __('messages.topbar.theme_toggle') }}">
                    <i class="ti ti-moon fs-xl mode-light-moon"></i>
                    <i class="ti ti-sun fs-xl mode-light-sun"></i>
                    <span>{{ __('messages.setup.theme_light') }}</span>
                </button>
                <div class="dropdown">
                    <button class="topbar-link aptoria-setup-control-pill" data-bs-toggle="dropdown" type="button" aria-expanded="false">
                        <i class="ti ti-world fs-xl me-1"></i>{{ config('aptoria.supported_locale_names.'.app()->getLocale(), strtoupper(app()->getLocale())) }}
                        <i class="ti ti-chevron-down ms-1"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @foreach(config('aptoria.supported_locale_names', ['en' => 'English', 'hu' => 'Magyar']) as $localeCode => $localeName)
                            <a href="{{ route('language.switch', $localeCode) }}" class="dropdown-item {{ app()->getLocale() === $localeCode ? 'active' : '' }}">{{ $localeName }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
            <a href="{{ route('setup.index') }}" class="aptoria-setup-brand text-decoration-none" aria-label="Aptoria setup">
                <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-setup-brand-logo">
            </a>
            <div class="aptoria-setup-brand-subtitle">{{ __('messages.setup.setup_wizard') }} <span aria-hidden="true">•</span> v{{ config('aptoria.version') }}</div>
        </header>

        <main class="aptoria-setup-viewport">
            @if($errors->any())
                <div class="alert alert-danger aptoria-setup-alert">
                    <strong>{{ __('messages.common.validation_error') }}</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success aptoria-setup-alert">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning aptoria-setup-alert">{{ session('warning') }}</div>
            @endif

            <div class="card border-0 aptoria-setup-card">
                <div class="aptoria-setup-grid">
                    <aside class="aptoria-setup-stepper">
                        <div class="aptoria-setup-stepper-list" aria-label="{{ __('messages.setup.setup_wizard') }}">
                            <button type="button" class="aptoria-stepper-item" data-step-target="welcome" data-step-index="1">
                                <span class="aptoria-stepper-line" aria-hidden="true"></span>
                                <span class="aptoria-stepper-number"><i class="ti ti-circle-check aptoria-stepper-check"></i><span>1</span></span>
                                <span class="aptoria-stepper-copy"><strong>{{ __('messages.setup.step_welcome') }}</strong><small>{{ __('messages.setup.step_welcome_hint') }}</small></span>
                            </button>
                            <button type="button" class="aptoria-stepper-item" data-step-target="environment" data-step-index="2">
                                <span class="aptoria-stepper-line" aria-hidden="true"></span>
                                <span class="aptoria-stepper-number"><i class="ti ti-server-cog aptoria-stepper-check"></i><span>2</span></span>
                                <span class="aptoria-stepper-copy"><strong>{{ __('messages.setup.step_environment') }}</strong><small>{{ __('messages.setup.step_environment_hint') }}</small></span>
                            </button>
                            <button type="button" class="aptoria-stepper-item" data-step-target="config" data-step-index="3">
                                <span class="aptoria-stepper-line" aria-hidden="true"></span>
                                <span class="aptoria-stepper-number"><i class="ti ti-database-cog aptoria-stepper-check"></i><span>3</span></span>
                                <span class="aptoria-stepper-copy"><strong>{{ __('messages.setup.step_config') }}</strong><small>{{ __('messages.setup.step_config_hint') }}</small></span>
                            </button>
                            <button type="button" class="aptoria-stepper-item" data-step-target="admin" data-step-index="4">
                                <span class="aptoria-stepper-line" aria-hidden="true"></span>
                                <span class="aptoria-stepper-number"><i class="ti ti-user-cog aptoria-stepper-check"></i><span>4</span></span>
                                <span class="aptoria-stepper-copy"><strong>{{ __('messages.setup.step_admin_default') }}</strong><small>{{ __('messages.setup.step_admin_default_hint') }}</small></span>
                            </button>
                            <button type="button" class="aptoria-stepper-item" data-step-target="install" data-step-index="5">
                                <span class="aptoria-stepper-line" aria-hidden="true"></span>
                                <span class="aptoria-stepper-number"><i class="ti ti-rocket aptoria-stepper-check"></i><span>5</span></span>
                                <span class="aptoria-stepper-copy"><strong>{{ __('messages.setup.step_finalize') }}</strong><small>{{ __('messages.setup.step_finalize_hint') }}</small></span>
                            </button>
                        </div>

                        <div class="aptoria-setup-help-box">
                            <div class="aptoria-setup-help-icon"><i class="ti ti-help-circle"></i></div>
                            <div>
                                <strong>{{ __('messages.setup.need_help_title') }}</strong>
                                <p>{{ __('messages.setup.need_help_text') }}</p>
                                <span class="aptoria-setup-help-link">{{ __('messages.setup.view_documentation') }} <i class="ti ti-arrow-up-right"></i></span>
                            </div>
                        </div>
                    </aside>

                    <section class="aptoria-setup-main">
                        <div class="aptoria-setup-panel" data-step="welcome">
                            <div class="aptoria-setup-section-header aptoria-setup-section-header-lg">
                                <div class="aptoria-setup-title-group">
                                    <span class="aptoria-setup-icon-bubble"><i class="ti ti-sparkles"></i></span>
                                    <div>
                                        <span class="aptoria-setup-step-pill">{{ __('messages.setup.step_1_label') }}</span>
                                        <h1>{{ __('messages.setup.welcome_title') }}</h1>
                                        <p class="text-muted">{{ __('messages.setup.welcome_lead') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 aptoria-setup-info-cards">
                                <div class="col-md-4">
                                    <div class="aptoria-setup-feature-card"><div class="aptoria-feature-icon text-success bg-success-subtle"><i class="ti ti-shield-check"></i></div><h4>{{ __('messages.setup.welcome_card_safe_title') }}</h4><p>{{ __('messages.setup.welcome_card_safe_text') }}</p></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="aptoria-setup-feature-card"><div class="aptoria-feature-icon text-primary bg-primary-subtle"><i class="ti ti-database"></i></div><h4>{{ __('messages.setup.welcome_card_db_title') }}</h4><p>{{ __('messages.setup.welcome_card_db_text') }}</p></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="aptoria-setup-feature-card"><div class="aptoria-feature-icon text-warning bg-warning-subtle"><i class="ti ti-user-cog"></i></div><h4>{{ __('messages.setup.welcome_card_admin_title') }}</h4><p>{{ __('messages.setup.welcome_card_admin_text') }}</p></div>
                                </div>
                            </div>

                            <div class="aptoria-security-hardening-panel mt-4">
                                <div class="aptoria-security-hardening-head">
                                    <span class="aptoria-feature-icon text-primary bg-primary-subtle"><i class="ti ti-shield-lock"></i></span>
                                    <div>
                                        <h4>{{ __('messages.security.setup_panel_title') }}</h4>
                                        <p>{{ __('messages.security.setup_panel_copy') }}</p>
                                    </div>
                                </div>
                                <div class="aptoria-security-hardening-grid">
                                    @foreach($securityChecklist as $securityItem)
                                        <div class="aptoria-security-hardening-item">
                                            <span class="aptoria-security-hardening-icon text-{{ $securityItem['tone'] }} bg-{{ $securityItem['tone'] }}-subtle"><i class="ti ti-{{ $securityItem['icon'] }}"></i></span>
                                            <span class="aptoria-security-hardening-copy"><strong>{{ $securityItem['label'] }}</strong><small>{{ $securityItem['detail'] }}</small></span>
                                            <span class="badge text-bg-{{ $securityItem['tone'] }}">{{ $securityItem['status'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="aptoria-setup-note mt-4">
                                <i class="ti ti-key"></i>
                                <div><strong>{{ __('messages.setup.default_admin_title') }}</strong> {{ __('messages.setup.default_admin_intro') }} <code>{{ $defaultAdminEmail }}</code> / <code>{{ $defaultAdminPassword }}</code></div>
                            </div>

                            <div class="aptoria-setup-bottom-bar">
                                <span></span>
                                <div class="aptoria-setup-step-dots" data-dot-group></div>
                                <button type="button" class="btn btn-primary aptoria-setup-next" data-step-target="environment">{{ __('messages.setup.start_environment_check') }} <i class="ti ti-arrow-right"></i></button>
                            </div>
                        </div>

                        <div class="aptoria-setup-panel" data-step="environment">
                            <div class="aptoria-setup-section-header">
                                <div class="aptoria-setup-title-group">
                                    <span class="aptoria-setup-icon-bubble"><i class="ti ti-server-cog"></i></span>
                                    <div>
                                        <h2>{{ __('messages.setup.environment_checks') }}</h2>
                                        <p class="text-muted">{{ __('messages.setup.environment_intro') }}</p>
                                    </div>
                                </div>
                                <div class="aptoria-setup-check-status-pill"><span>{{ __('messages.setup.checking_count', ['current' => min(4, count($report['checks'])), 'total' => count($report['checks'])]) }}</span><i></i><i></i><i></i></div>
                            </div>

                            <div class="aptoria-env-checks">
                                @php
                                    $checkIconFallbacks = ['php' => 'brand-php', 'sqlite' => 'database', 'storage' => 'folder', 'cache' => 'settings-cog', 'env' => 'file-text', 'database' => 'file-database', 'lock' => 'lock-check', 'debug' => 'bug-off', 'session' => 'clock-shield', 'default' => 'circle-check'];
                                @endphp
                                @foreach($report['checks'] as $check)
                                    @php
                                        $labelForIcon = strtolower((string) $check['label']);
                                        $checkIcon = $checkIconFallbacks['default'];
                                        foreach ($checkIconFallbacks as $needle => $icon) {
                                            if ($needle !== 'default' && str_contains($labelForIcon, $needle)) { $checkIcon = $icon; break; }
                                        }
                                    @endphp
                                    <div class="aptoria-env-check-row" data-check-status="{{ $check['status'] }}" tabindex="-1">
                                        <div class="aptoria-env-check-icon"><i class="ti ti-{{ $checkIcon }}"></i></div>
                                        <div class="aptoria-env-check-body">
                                            <div class="aptoria-env-check-title">{{ $check['label'] }}</div>
                                            <div class="aptoria-env-check-detail">{{ $check['detail'] }}</div>
                                            @if($check['fix'])
                                                <div class="aptoria-env-check-fix">{{ $check['fix'] }}</div>
                                            @endif
                                        </div>
                                        <div class="aptoria-env-check-progress"><span></span></div>
                                        <div class="aptoria-env-check-result"><span class="badge text-bg-light">{{ __('messages.setup.pending') }}</span></div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="aptoria-setup-inline-info mt-3"><i class="ti ti-info-circle"></i><span>{{ __('messages.setup.process_may_take') }}</span></div>

                            @if(! $report['summary']['can_continue'])
                                <div class="alert alert-warning mt-3 mb-0">{{ __('messages.setup.environment_blocked_help') }}</div>
                            @endif

                            <div class="aptoria-setup-bottom-bar">
                                <button type="button" class="btn btn-light" data-step-target="welcome"><i class="ti ti-arrow-left"></i> {{ __('messages.common.back') }}</button>
                                <div class="aptoria-setup-step-dots" data-dot-group></div>
                                <button type="button" class="btn btn-primary aptoria-setup-next" data-step-target="config" data-env-continue>{{ __('messages.setup.continue_to_config') }} <i class="ti ti-arrow-right"></i></button>
                            </div>
                        </div>

                        <div class="aptoria-setup-panel" data-step="config">
                            <div class="aptoria-setup-section-header">
                                <div class="aptoria-setup-title-group">
                                    <span class="aptoria-setup-icon-bubble"><i class="ti ti-database-cog"></i></span>
                                    <div>
                                        <h2>{{ __('messages.setup.config_title') }}</h2>
                                        <p class="text-muted">{{ __('messages.setup.config_intro') }}</p>
                                    </div>
                                </div>
                                <span class="badge text-bg-info">{{ __('messages.setup.automatic_actions_badge') }}</span>
                            </div>

                            @php
                                $automaticActions = [
                                    ['key' => 'env', 'icon' => 'file-text', 'title' => 'create_env', 'help' => 'create_env_help'],
                                    ['key' => 'sqlite', 'icon' => 'file-database', 'title' => 'create_sqlite', 'help' => 'create_sqlite_help'],
                                    ['key' => 'key', 'icon' => 'key', 'title' => 'generate_key', 'help' => 'generate_key_help'],
                                    ['key' => 'migrations', 'icon' => 'route', 'title' => 'run_migrations', 'help' => 'run_migrations_help'],
                                    ['key' => 'settings', 'icon' => 'settings-cog', 'title' => 'seed_settings', 'help' => 'seed_settings_help'],
                                    ['key' => 'lock', 'icon' => 'lock-check', 'title' => 'write_setup_lock', 'help' => 'write_setup_lock_help'],
                                ];
                            @endphp
                            <div class="aptoria-auto-action-list">
                                @foreach($automaticActions as $automaticAction)
                                    @php $isReady = (bool) ($automaticActionStatuses[$automaticAction['key']] ?? false); @endphp
                                    <div class="aptoria-auto-action-row {{ $isReady ? 'is-ready' : 'is-pending' }}">
                                        <div class="aptoria-auto-action-icon"><i class="ti ti-{{ $automaticAction['icon'] }}"></i></div>
                                        <div class="aptoria-auto-action-body"><strong>{{ __('messages.setup.'.$automaticAction['title']) }}</strong><span>{{ __('messages.setup.'.$automaticAction['help']) }}</span></div>
                                        <div class="aptoria-auto-action-status">
                                            @if($isReady)
                                                <span class="badge text-bg-success"><i class="ti ti-circle-check me-1"></i>{{ __('messages.setup.auto_ready') }}</span>
                                            @else
                                                <span class="badge text-bg-light"><i class="ti ti-sparkles me-1"></i>{{ __('messages.setup.auto_will_handle') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="aptoria-setup-note mt-3"><i class="ti ti-database"></i><div><strong>{{ __('messages.setup.demo_after_install_title') }}</strong> {{ __('messages.setup.demo_after_install_help') }}</div></div>

                            <div class="aptoria-setup-bottom-bar">
                                <button type="button" class="btn btn-light" data-step-target="environment"><i class="ti ti-arrow-left"></i> {{ __('messages.common.back') }}</button>
                                <div class="aptoria-setup-step-dots" data-dot-group></div>
                                <button type="button" class="btn btn-primary aptoria-setup-next" data-step-target="admin">{{ __('messages.setup.continue_to_admin') }} <i class="ti ti-arrow-right"></i></button>
                            </div>
                        </div>

                        <div class="aptoria-setup-panel" data-step="admin">
                            <div class="aptoria-setup-section-header">
                                <div class="aptoria-setup-title-group">
                                    <span class="aptoria-setup-icon-bubble"><i class="ti ti-user-cog"></i></span>
                                    <div>
                                        <h2>{{ __('messages.setup.admin_preview_title') }}</h2>
                                        <p class="text-muted">{{ __('messages.setup.admin_preview_intro') }}</p>
                                    </div>
                                </div>
                                <span class="badge text-bg-warning">{{ __('messages.setup.temporary_credentials') }}</span>
                            </div>

                            <div class="aptoria-default-admin-card aptoria-setup-premium-card">
                                <div class="aptoria-default-admin-head">
                                    <div class="aptoria-feature-icon text-warning bg-warning-subtle"><i class="ti ti-shield-exclamation"></i></div>
                                    <div><h4>{{ __('messages.setup.default_admin_title') }}</h4><p>{{ __('messages.setup.default_admin_password_change_required') }}</p></div>
                                </div>
                                <div class="aptoria-admin-credential-list">
                                    <div class="aptoria-admin-credential-row"><span>{{ __('messages.setup.admin_name') }}</span><strong>{{ $defaultAdminName }}</strong></div>
                                    <div class="aptoria-admin-credential-row"><span>{{ __('messages.setup.admin_email') }}</span><code>{{ $defaultAdminEmail }}</code></div>
                                    <div class="aptoria-admin-credential-row"><span>{{ __('messages.setup.admin_password') }}</span><code>{{ $defaultAdminPassword }}</code></div>
                                </div>
                            </div>

                            <div class="aptoria-setup-bottom-bar">
                                <button type="button" class="btn btn-light" data-step-target="config"><i class="ti ti-arrow-left"></i> {{ __('messages.common.back') }}</button>
                                <div class="aptoria-setup-step-dots" data-dot-group></div>
                                <button type="button" class="btn btn-primary aptoria-setup-next" data-step-target="install">{{ __('messages.setup.continue_to_install') }} <i class="ti ti-arrow-right"></i></button>
                            </div>
                        </div>

                        <div class="aptoria-setup-panel" data-step="install">
                            <div class="aptoria-setup-section-header">
                                <div class="aptoria-setup-title-group">
                                    <span class="aptoria-setup-icon-bubble"><i class="ti ti-rocket"></i></span>
                                    <div>
                                        <h2>{{ __('messages.setup.install_title') }}</h2>
                                        <p class="text-muted">{{ __('messages.setup.install_intro') }}</p>
                                    </div>
                                </div>
                            </div>

                            @php
                                $installProgressSteps = [
                                    __('messages.setup.install_progress_runtime'),
                                    __('messages.setup.install_progress_migrations'),
                                    __('messages.setup.install_progress_settings'),
                                    __('messages.setup.install_progress_admin'),
                                    __('messages.setup.install_progress_lock'),
                                    __('messages.setup.install_progress_redirect'),
                                ];
                            @endphp
                            <div class="aptoria-install-stack">
                                <div class="aptoria-install-flow-card aptoria-setup-premium-card">
                                    <ol class="aptoria-install-steps mb-0">
                                        <li>{{ __('messages.setup.install_task_migrate') }}</li>
                                        <li>{{ __('messages.setup.install_task_settings') }}</li>
                                        <li>{{ __('messages.setup.install_task_admin') }}</li>
                                        <li>{{ __('messages.setup.install_task_lock') }}</li>
                                    </ol>
                                </div>

                                <form method="POST" action="{{ route('setup.install') }}" class="aptoria-install-action-card aptoria-install-lock-form" data-aptoria-form-scope="setup" data-aptoria-form-plugin data-aptoria-confirm="true" data-aptoria-confirm-title="{{ __('messages.setup.install_confirm_title') }}" data-aptoria-confirm-text="{{ __('messages.setup.install_confirm_text') }}" data-aptoria-confirm-type="question" data-aptoria-confirm-button="{{ __('messages.setup.install_confirm_button') }}" data-aptoria-cancel-button="{{ __('messages.common.cancel') }}" data-aptoria-progress="installer" data-aptoria-progress-steps='{{ e(json_encode($installProgressSteps)) }}' data-aptoria-submit-delay="650">
                                    @csrf
                                    <div class="aptoria-install-action-icon" aria-hidden="true"><i class="ti ti-lock"></i></div>
                                    <div class="aptoria-install-action-content">
                                        <h4>{{ __('messages.setup.ready_to_install') }}</h4>
                                        <p>{{ __('messages.setup.install_confirm_text') }}</p>
                                        <label class="aptoria-install-confirm">
                                            <input type="checkbox" name="confirm" value="1" required>
                                            <span>{{ __('messages.setup.install_confirm_checkbox') }}</span>
                                        </label>
                                    </div>
                                    <div class="aptoria-install-action-buttons">
                                        <button type="button" class="btn btn-light" data-step-target="admin"><i class="ti ti-arrow-left"></i> {{ __('messages.common.back') }}</button>
                                        <button class="btn btn-success aptoria-setup-lock-button" type="submit" data-aptoria-submit-label="{{ __('messages.setup.installing') }}">
                                            <i class="ti ti-rocket"></i><span>{{ __('messages.setup.install_button') }}</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            @if(! empty($setupLockBlockers))
                                <div class="alert alert-warning mt-3">
                                    <strong>{{ __('messages.setup.setup_lock_blocked_title') }}</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach($setupLockBlockers as $setupLockBlocker)
                                            <li>{{ $setupLockBlocker }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    var root = document.querySelector('.aptoria-guided-setup');
    if (!root) { return; }

    var steps = ['welcome', 'environment', 'config', 'admin', 'install'];
    var legacyStepMap = { quick: 'config' };
    var buttons = Array.prototype.slice.call(root.querySelectorAll('[data-step-target]'));
    var panels = Array.prototype.slice.call(root.querySelectorAll('[data-step]'));
    var activeStep = legacyStepMap[root.getAttribute('data-initial-step')] || root.getAttribute('data-initial-step') || 'welcome';
    var checkAnimationRun = 0;
    var installProgressTimer = null;

    function hideSetupPageLoader() {
        var loader = document.getElementById('aptoria-setup-page-loader');
        if (!loader) { return; }
        loader.classList.add('is-hidden');
        window.setTimeout(function () { if (loader && loader.parentNode) { loader.parentNode.removeChild(loader); } }, 320);
    }

    function readProgressSteps(form) {
        var fallback = [
            @json(__('messages.setup.install_progress_runtime')),
            @json(__('messages.setup.install_progress_migrations')),
            @json(__('messages.setup.install_progress_settings')),
            @json(__('messages.setup.install_progress_admin')),
            @json(__('messages.setup.install_progress_lock')),
            @json(__('messages.setup.install_progress_redirect'))
        ];
        try {
            var parsed = JSON.parse(form.getAttribute('data-aptoria-progress-steps') || '[]');
            return parsed && parsed.length ? parsed : fallback;
        } catch (e) { return fallback; }
    }

    function showInstallProgress(form) {
        var overlay = document.getElementById('aptoria-install-progress-overlay');
        var line = document.getElementById('aptoria-install-progress-line');
        if (!overlay || !line) { return; }
        var steps = readProgressSteps(form);
        var index = 0;
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('aptoria-install-progress-active');
        function renderStep() {
            line.classList.remove('is-visible');
            window.setTimeout(function () {
                line.textContent = steps[Math.min(index, steps.length - 1)];
                line.classList.add('is-visible');
                if (index < steps.length - 1) { index += 1; }
            }, 150);
        }
        renderStep();
        window.clearInterval(installProgressTimer);
        installProgressTimer = window.setInterval(renderStep, 1150);
    }

    function confirmedSubmit(form) {
        window.dispatchEvent(new CustomEvent('aptoria:confirmed-submit', { detail: { form: form } }));
        var button = form.querySelector('[type="submit"]');
        if (button) {
            var label = button.getAttribute('data-aptoria-submit-label');
            if (label) { button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + label; }
            button.disabled = true;
        }
        window.setTimeout(function () { form.submit(); }, parseInt(form.getAttribute('data-aptoria-submit-delay') || '0', 10));
    }

    Array.prototype.slice.call(document.querySelectorAll('form[data-aptoria-confirm="true"]')).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.getAttribute('data-aptoria-confirmed') === 'true') { return; }
            event.preventDefault();
            var title = form.getAttribute('data-aptoria-confirm-title') || 'Confirm';
            var text = form.getAttribute('data-aptoria-confirm-text') || '';
            var confirmButton = form.getAttribute('data-aptoria-confirm-button') || 'Continue';
            var cancelButton = form.getAttribute('data-aptoria-cancel-button') || 'Cancel';
            function go() { form.setAttribute('data-aptoria-confirmed', 'true'); confirmedSubmit(form); }
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: title,
                    text: text,
                    icon: form.getAttribute('data-aptoria-confirm-type') || 'question',
                    showCancelButton: true,
                    confirmButtonText: confirmButton,
                    cancelButtonText: cancelButton,
                    reverseButtons: true,
                    focusCancel: true,
                    buttonsStyling: false,
                    customClass: {
                        popup: 'aptoria-swal-popup aptoria-setup-swal-popup',
                        confirmButton: 'btn btn-primary aptoria-swal-confirm',
                        cancelButton: 'btn btn-light aptoria-swal-cancel me-2',
                        actions: 'aptoria-swal-actions'
                    }
                }).then(function (result) { if (result.isConfirmed) { go(); } });
            } else if (window.confirm(title + '\n\n' + text)) { go(); }
        });
    });

    window.addEventListener('aptoria:confirmed-submit', function (event) {
        var form = event.detail && event.detail.form ? event.detail.form : null;
        if (!form || form.getAttribute('data-aptoria-progress') !== 'installer') { return; }
        showInstallProgress(form);
    });


    function refreshSetupIcons() {
        if (window.AptoriaIcons && typeof window.AptoriaIcons.refresh === 'function') {
            window.AptoriaIcons.refresh(root);
        } else if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }
    }

    function renderDots() {
        Array.prototype.slice.call(root.querySelectorAll('[data-dot-group]')).forEach(function (group) {
            group.innerHTML = steps.map(function (step) { return '<span data-dot="' + step + '"></span>'; }).join('');
        });
    }

    function activateStep(step) {
        step = legacyStepMap[step] || step;
        if (steps.indexOf(step) === -1) { step = 'welcome'; }
        activeStep = step;
        var activeIndex = steps.indexOf(step);
        panels.forEach(function (panel) { panel.classList.toggle('is-active', panel.getAttribute('data-step') === step); });
        buttons.forEach(function (button) {
            var target = legacyStepMap[button.getAttribute('data-step-target')] || button.getAttribute('data-step-target');
            var buttonIndex = steps.indexOf(target);
            button.classList.toggle('is-active', target === step);
            button.classList.toggle('is-complete', button.hasAttribute('data-step-index') && buttonIndex > -1 && buttonIndex < activeIndex);
        });
        Array.prototype.slice.call(root.querySelectorAll('[data-dot]')).forEach(function (dot) {
            dot.classList.toggle('is-active', dot.getAttribute('data-dot') === step);
            dot.classList.toggle('is-complete', steps.indexOf(dot.getAttribute('data-dot')) < activeIndex);
        });
        if (step === 'environment') { runChecksAnimation(); }
        refreshSetupIcons();
    }

    buttons.forEach(function (button) { button.addEventListener('click', function () { if (!button.disabled) { activateStep(button.getAttribute('data-step-target')); } }); });
    Array.prototype.slice.call(root.querySelectorAll('[data-aptoria-run-checks]')).forEach(function (button) { button.addEventListener('click', runChecksAnimation); });

    function runChecksAnimation() {
        var rows = Array.prototype.slice.call(root.querySelectorAll('.aptoria-env-check-row'));
        var continueButtons = Array.prototype.slice.call(root.querySelectorAll('[data-env-continue]'));
        var currentRun = ++checkAnimationRun;
        continueButtons.forEach(function (button) { button.disabled = true; button.classList.remove('is-ready'); });
        rows.forEach(function (row) {
            var iconWrap = row.querySelector('.aptoria-env-check-icon');
            var result = row.querySelector('.aptoria-env-check-result');
            var progress = row.querySelector('.aptoria-env-check-progress span');
            var originalIcon = iconWrap ? iconWrap.getAttribute('data-original-tabler-icon') : '';
            if (iconWrap && !originalIcon) {
                var icon = iconWrap.querySelector('i.ti');
                var iconClass = icon ? Array.prototype.slice.call(icon.classList).find(function (className) { return className.indexOf('ti-') === 0 && className !== 'ti'; }) : '';
                originalIcon = iconClass ? iconClass.replace(/^ti-/, '') : 'circle-check';
                iconWrap.setAttribute('data-original-tabler-icon', originalIcon);
            }
            row.classList.remove('is-visible', 'is-complete', 'is-current');
            if (progress) { progress.style.width = '0%'; }
            if (iconWrap) { iconWrap.innerHTML = '<i class="ti ti-' + (originalIcon || 'circle-check') + '"></i>'; }
            if (result) { result.innerHTML = '<span class="aptoria-mini-spinner" aria-hidden="true"></span>'; }
        });
        if (!rows.length) { enableEnvironmentContinue(continueButtons); return; }
        rows.forEach(function (row, index) {
            window.setTimeout(function () {
                if (currentRun !== checkAnimationRun) { return; }
                rows.forEach(function (otherRow) { otherRow.classList.remove('is-current'); });
                var iconWrap = row.querySelector('.aptoria-env-check-icon');
                var result = row.querySelector('.aptoria-env-check-result');
                var progress = row.querySelector('.aptoria-env-check-progress span');
                var status = row.getAttribute('data-check-status') || 'ok';
                var badge = status === 'ok' ? 'text-bg-success' : (status === 'warning' ? 'text-bg-warning' : (status === 'info' ? 'text-bg-info' : 'text-bg-danger'));
                var iconName = status === 'ok' ? 'circle-check' : (status === 'warning' ? 'alert-triangle' : (status === 'info' ? 'info-circle' : 'circle-x'));
                var tone = status === 'ok' ? 'text-success' : (status === 'warning' ? 'text-warning' : (status === 'info' ? 'text-info' : 'text-danger'));
                row.classList.add('is-visible', 'is-current');
                if (progress) { progress.style.width = '76%'; }
                if (iconWrap) { iconWrap.innerHTML = '<span class="aptoria-mini-spinner" aria-hidden="true"></span>'; }
                refreshSetupIcons();
                window.setTimeout(function () {
                    if (progress) { progress.style.width = '100%'; }
                    if (iconWrap) { iconWrap.innerHTML = '<i class="ti ti-' + iconName + ' ' + tone + '"></i>'; }
                    if (result) { result.innerHTML = '<span class="badge ' + badge + '">' + status.toUpperCase() + '</span>'; }
                    row.classList.add('is-complete');
                    refreshSetupIcons();
                    if (index === rows.length - 1) { row.classList.remove('is-current'); enableEnvironmentContinue(continueButtons); }
                }, 260);
            }, 140 + index * 190);
        });
    }

    function enableEnvironmentContinue(buttons) {
        buttons.forEach(function (button) { button.disabled = false; button.classList.add('is-ready'); });
    }

    renderDots();
    if (document.readyState === 'complete') { window.setTimeout(hideSetupPageLoader, 180); }
    else { window.addEventListener('load', function () { window.setTimeout(hideSetupPageLoader, 180); }); window.setTimeout(hideSetupPageLoader, 2500); }

    activateStep(activeStep);
})();
</script>
@endpush
