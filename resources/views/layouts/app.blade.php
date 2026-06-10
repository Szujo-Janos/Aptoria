<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $aptoriaTitleSettings = $aptoriaTitleSettings ?? app(\App\Services\Settings\SettingService::class);
        $aptoriaAppName = $aptoriaAppName ?? $aptoriaTitleSettings->string('app.name', 'Aptoria');
    @endphp
    <title>@yield('title', __('messages.dashboard.title')) | {{ $aptoriaAppName }}</title>
    <link rel="icon" href="{{ asset('assets/aptoria/img/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/aptoria/img/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/aptoria/img/apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/fontawesome/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/animate/animate.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/metisMenu/css/metisMenu.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/datatables/css/dataTables.bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/toastr/css/toastr.min.css') }}">
    @if($aptoriaEnableSweetalert ?? true)
        <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/sweetalert/css/sweet-alert.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/vendor/iCheck/skins/square/green.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria-ui/css/static_custom.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/aptoria/css/app.css') }}?v={{ config('aptoria.version') }}">
    @stack('styles')
</head>
@php
    $aptoriaUiSettings = $aptoriaUiSettings ?? app(\App\Services\Settings\SettingService::class);
    $aptoriaAppName = $aptoriaAppName ?? $aptoriaUiSettings->string('app.name', 'Aptoria');
    $aptoriaShowLogo = $aptoriaShowLogo ?? $aptoriaUiSettings->boolean('ui.show_header_logo', true);
    $aptoriaSidebarState = $aptoriaSidebarState ?? ($aptoriaUiSettings->string('ui.default_sidebar_state', 'expanded') ?: 'expanded');
    $aptoriaCompactDashboard = $aptoriaCompactDashboard ?? $aptoriaUiSettings->boolean('ui.compact_dashboard', false);
    $aptoriaDashboardDensity = $aptoriaDashboardDensity ?? ($aptoriaUiSettings->string('ui.dashboard_density', 'comfortable') ?: 'comfortable');
    $aptoriaTableDensity = $aptoriaTableDensity ?? ($aptoriaUiSettings->string('ui.table_density', 'comfortable') ?: 'comfortable');
    $aptoriaTheme = $aptoriaTheme ?? ($aptoriaUiSettings->string('ui.theme', 'light') ?: 'light');
    $aptoriaEnableSweetalert = $aptoriaEnableSweetalert ?? $aptoriaUiSettings->boolean('ui.enable_sweetalert', true);
    $aptoriaProjectNavActive = $aptoriaProjectNavActive ?? request()->routeIs(
        'projects.index',
        'projects.create',
        'projects.show',
        'projects.edit',
        'projects.wizard.*',
        'projects.environments.*',
        'projects.auth-profiles.*',
        'projects.settings.*',
        'projects.assertion-rules.*',
        'projects.test-suites.*',
        'projects.test-cases.*',
        'projects.test-execution.*',
        'projects.qa-coverage.*',
        'projects.qa-evidence.*',
        'projects.contract-validations.*',
        'projects.findings.*',
        'projects.endpoint-inventory.*',
        'projects.endpoints.*',
        'projects.scans.*',
        'projects.snapshots.*',
        'projects.monitors.*',
        'projects.calendar.*',
        'projects.audit-log.*',
        'projects.reports.*',
        'projects.release-readiness.*',
        'projects.release-gates.*'
    );
    $aptoriaOperationsNavActive = $aptoriaOperationsNavActive ?? request()->routeIs('monitors.*', 'calendar.*');
    $aptoriaReleaseNavActive = $aptoriaReleaseNavActive ?? request()->routeIs('reports.*', 'release-readiness.*');
    $aptoriaAdminNavActive = $aptoriaAdminNavActive ?? request()->routeIs('audit-log.*', 'demo-project.*', 'system.health.*', 'settings.*');
    $aptoriaSupportNavActive = $aptoriaSupportNavActive ?? request()->routeIs('how-it-works', 'help.*');
    $aptoriaProjectMenuActive = $aptoriaProjectMenuActive ?? fn (string ...$patterns): string => request()->routeIs(...$patterns) ? 'active' : '';
    $aptoriaCurrentProject = $aptoriaCurrentProject ?? request()->route('project');
    $aptoriaCurrentProject = $aptoriaCurrentProject instanceof \App\Models\Project ? $aptoriaCurrentProject : null;
    $aptoriaPageTitle = $aptoriaPageTitle ?? trim((string) $__env->yieldContent('title', __('messages.dashboard.title')));
    $aptoriaRouteName = $aptoriaRouteName ?? (request()->route()?->getName() ?? 'dashboard');
    $aptoriaRouteLabel = $aptoriaRouteLabel ?? \Illuminate\Support\Str::headline(str_replace(['projects.', '.', '-'], ['', ' ', ' '], $aptoriaRouteName));
@endphp
<body class="fixed-navbar fixed-sidebar sidebar-scroll aptoria-pro-ui aptoria-js-loading aptoria-sidebar-{{ $aptoriaSidebarState ?? 'expanded' }} aptoria-dashboard-{{ $aptoriaDashboardDensity ?? 'comfortable' }} aptoria-table-{{ $aptoriaTableDensity ?? 'comfortable' }} {{ ($aptoriaCompactDashboard ?? false) ? 'aptoria-compact-dashboard' : '' }} aptoria-theme-{{ $aptoriaTheme ?? 'light' }}" data-aptoria-sweetalert="{{ ($aptoriaEnableSweetalert ?? true) ? 'enabled' : 'disabled' }}">

<div id="header" class="aptoria-header aptoria-ui-header-polish">
    <div class="color-line"></div>
    <div id="logo" class="light-version aptoria-logo-block">
        <a href="{{ route('dashboard') }}" class="aptoria-logo-link" aria-label="{{ $aptoriaAppName }} dashboard">
            @if($aptoriaShowLogo)
                <img src="{{ asset('assets/aptoria/img/aptoria-logo-horizontal.png') }}" alt="{{ $aptoriaAppName }}" class="aptoria-header-logo">
            @else
                <span class="text-primary font-extra-bold">{{ $aptoriaAppName }}</span>
            @endif
        </a>
    </div>
    <nav role="navigation" class="aptoria-topnav">
        <div class="header-link hide-menu" title="{{ __('messages.header.toggle_menu') }}">
            <i class="fa fa-bars"></i>
        </div>
        <div class="small-logo aptoria-small-brand">
            @if($aptoriaShowLogo)
                <img src="{{ asset('assets/aptoria/img/aptoria-logo-icon.png') }}" alt="" class="aptoria-small-logo-icon">
            @endif
            <span class="text-primary">{{ $aptoriaAppName }}</span>
        </div>

        <form role="search" class="navbar-form-custom aptoria-header-search" method="GET" action="{{ route('help.index') }}">
            <div class="form-group">
                <input type="text"
                       class="form-control"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="{{ __('messages.header.search_placeholder') }}">
            </div>
        </form>

        <div class="navbar-right aptoria-navbar-right">
            <ul class="nav navbar-nav no-borders">
                <li class="dropdown aptoria-quick-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="{{ __('messages.header.quick_navigation') }}">
                        <i class="fa fa-th"></i>
                    </a>
                    <div class="dropdown-menu hdropdown bigmenu animated flipInX aptoria-mega-menu">
                        <div class="row">
                            <div class="col-xs-4">
                                <a href="{{ route('projects.index') }}">
                                    <i class="fa fa-briefcase text-info"></i>
                                    <span>{{ __('messages.nav.projects') }}</span>
                                </a>
                            </div>
                            <div class="col-xs-4">
                                <a href="{{ route('projects.wizard.create') }}">
                                    <i class="fa fa-magic text-success"></i>
                                    <span>{{ __('messages.nav.guided_project') }}</span>
                                </a>
                            </div>
                            <div class="col-xs-4">
                                <a href="{{ route('monitors.index') }}">
                                    <i class="fa fa-clock-o text-warning"></i>
                                    <span>{{ __('messages.nav.monitors') }}</span>
                                </a>
                            </div>
                        </div>
                        <div class="row m-t-sm">
                            <div class="col-xs-4">
                                <a href="{{ route('release-readiness.index') }}">
                                    <i class="fa fa-check-circle text-primary"></i>
                                    <span>{{ __('messages.nav.release_readiness') }}</span>
                                </a>
                            </div>
                            <div class="col-xs-4">
                                <a href="{{ route('reports.index') }}">
                                    <i class="fa fa-file-text-o text-danger"></i>
                                    <span>{{ __('messages.nav.reports') }}</span>
                                </a>
                            </div>
                            <div class="col-xs-4">
                                <a href="{{ route('help.index') }}">
                                    <i class="fa fa-question-circle text-success"></i>
                                    <span>{{ __('messages.nav.help') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </li>

                <li>
                    <a href="{{ route('how-it-works') }}" title="{{ __('messages.nav.how_it_works') }}">
                        <i class="fa fa-map-o"></i>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.index') }}" title="{{ __('messages.nav.reports') }}">
                        <i class="fa fa-file-text-o"></i>
                    </a>
                </li>
                <li>
                    <a href="{{ route('system.health.index') }}" title="{{ __('messages.nav.system_health') }}">
                        <i class="fa fa-heartbeat"></i>
                    </a>
                </li>
                <li>
                    <a href="{{ route('settings.index') }}" title="{{ __('messages.nav.settings') }}">
                        <i class="fa fa-cog"></i>
                    </a>
                </li>

                <li class="dropdown aptoria-language-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="{{ __('messages.language.label') }}">
                        <i class="fa fa-globe"></i>
                        <span class="aptoria-menu-text">{{ config('aptoria.supported_locales.'.app()->getLocale(), strtoupper(app()->getLocale())) }}</span>
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu hdropdown animated flipInX">
                        @foreach(config('aptoria.supported_locales') as $localeCode => $localeName)
                            <li class="{{ app()->getLocale() === $localeCode ? 'active' : '' }}">
                                <a href="{{ route('language.switch', $localeCode) }}">
                                    <i class="fa fa-language"></i> {{ $localeName }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>

                <li class="dropdown aptoria-user-menu aptoria-account-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="{{ __('messages.header.account_menu') }}">
                        <i class="fa fa-user-circle"></i>
                        <span class="aptoria-menu-text">{{ auth()->user()->name ?? __('messages.auth.default_user_name') }}</span>
                        <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu hdropdown animated flipInX aptoria-clean-profile-menu">
                        <li class="title aptoria-profile-menu-title">
                            <strong>{{ auth()->user()->name ?? __('messages.auth.default_user_name') }}</strong><br>
                            <small>{{ auth()->user()->email ?? __('messages.header.signed_in') }}</small>
                        </li>
                        <li><a href="{{ route('profile.show') }}"><i class="fa fa-user"></i> {{ __('messages.nav.my_profile') }}</a></li>
                        <li><a href="{{ route('profile.show') }}#default-report-identity"><i class="fa fa-id-card-o"></i> {{ __('messages.nav.default_report_identity') }}</a></li>
                        <li><a href="{{ route('settings.index') }}"><i class="fa fa-cog"></i> {{ __('messages.nav.settings') }}</a></li>
                        <li><a href="{{ route('help.index') }}"><i class="fa fa-life-ring"></i> {{ __('messages.nav.help') }}</a></li>
                        <li class="divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" class="menu-form">
                                @csrf
                                <button type="submit" class="btn btn-link btn-block text-left">
                                    <i class="fa fa-sign-out"></i> {{ __('messages.auth.logout') }}
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</div>

<aside id="menu">
    <div id="navigation">
        <div class="profile-picture text-center">
            @if($aptoriaShowLogo)
                <div class="aptoria-avatar">
                    <img src="{{ asset('assets/aptoria/img/aptoria-logo-icon.png') }}" alt="{{ $aptoriaAppName }}">
                </div>
            @endif
            <div class="stats-label text-color aptoria-brand-copy">
                <span class="font-extra-bold font-uppercase">Aptoria</span>
                <div class="dropdown small m-t-xs">
                    <span class="text-muted">v{{ config('aptoria.version') }}</span>
                </div>
            </div>
        </div>

        <ul class="nav aptoria-ia-sidebar" id="side-menu">
            <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}"><i class="fa fa-dashboard"></i> <span class="nav-label">{{ __('messages.nav.dashboard') }}</span></a>
            </li>

            <li class="{{ $aptoriaProjectNavActive ? 'active' : '' }}">
                <a href="#"><i class="fa fa-briefcase"></i> <span class="nav-label">{{ __('messages.nav.projects') }}</span><span class="fa arrow"></span></a>
                <ul class="nav nav-second-level aptoria-nav-grouped">
                    <li class="{{ $aptoriaProjectMenuActive('projects.index') }}"><a href="{{ route('projects.index') }}"><i class="fa fa-list-ul"></i> {{ __('messages.nav.all_projects') }}</a></li>
                    <li class="{{ $aptoriaProjectMenuActive('projects.create') }}"><a href="{{ route('projects.create') }}"><i class="fa fa-plus-circle"></i> {{ __('messages.nav.create_project') }}</a></li>
                    <li class="{{ $aptoriaProjectMenuActive('projects.wizard.*') }}"><a href="{{ route('projects.wizard.create') }}"><i class="fa fa-magic"></i> {{ __('messages.nav.guided_project') }}</a></li>

                    @if($aptoriaCurrentProject)
                        <li class="nav-header"><span>{{ __('messages.nav.current_project') }}</span></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.show', 'projects.edit') }}"><a href="{{ route('projects.show', $aptoriaCurrentProject) }}"><i class="fa fa-info-circle"></i> {{ __('messages.projects.details') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.settings.*') }}"><a href="{{ route('projects.settings.edit', $aptoriaCurrentProject) }}"><i class="fa fa-sliders"></i> {{ __('messages.nav.project_settings') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.environments.*', 'projects.auth-profiles.*') }}"><a href="{{ route('projects.environments.index', $aptoriaCurrentProject) }}"><i class="fa fa-server"></i> {{ __('messages.nav.environments_auth_profiles') }}</a></li>

                        <li class="nav-header"><span>{{ __('messages.nav.api_inventory') }}</span></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.endpoint-inventory.*') }}"><a href="{{ route('projects.endpoint-inventory.index', $aptoriaCurrentProject) }}"><i class="fa fa-list-alt"></i> {{ __('messages.nav.endpoint_inventory') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.endpoints.*', 'projects.assertion-rules.*') }}"><a href="{{ route('projects.endpoints.index', $aptoriaCurrentProject) }}"><i class="fa fa-sitemap"></i> {{ __('messages.nav.endpoints') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.contract-validations.*') }}"><a href="{{ route('projects.contract-validations.index', $aptoriaCurrentProject) }}"><i class="fa fa-code"></i> {{ __('messages.contract_validations.short_title') }}</a></li>

                        <li class="nav-header"><span>{{ __('messages.nav.quality_workflow') }}</span></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.scans.*', 'projects.snapshots.*') }}"><a href="{{ route('projects.scans.index', $aptoriaCurrentProject) }}"><i class="fa fa-crosshairs"></i> {{ __('messages.nav.scans_snapshots') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.test-suites.*') }}"><a href="{{ route('projects.test-suites.index', $aptoriaCurrentProject) }}"><i class="fa fa-folder-open"></i> {{ __('messages.test_suites.title') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.test-cases.*') }}"><a href="{{ route('projects.test-cases.index', $aptoriaCurrentProject) }}"><i class="fa fa-check-square-o"></i> {{ __('messages.test_cases.title') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.test-execution.*') }}"><a href="{{ route('projects.test-execution.index', $aptoriaCurrentProject) }}"><i class="fa fa-play-circle"></i> {{ __('messages.test_execution.short_title') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.qa-coverage.*') }}"><a href="{{ route('projects.qa-coverage.index', $aptoriaCurrentProject) }}"><i class="fa fa-pie-chart"></i> {{ __('messages.qa_coverage.short_title') }}</a></li>

                        <li class="nav-header"><span>{{ __('messages.nav.risk_evidence') }}</span></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.findings.*') }}"><a href="{{ route('projects.findings.index', $aptoriaCurrentProject) }}"><i class="fa fa-bug"></i> {{ __('messages.findings.title') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.qa-evidence.*') }}"><a href="{{ route('projects.qa-evidence.index', $aptoriaCurrentProject) }}"><i class="fa fa-archive"></i> {{ __('messages.qa_evidence.short_title') }}</a></li>

                        <li class="nav-header"><span>{{ __('messages.nav.release_reporting') }}</span></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.release-readiness.*') }}"><a href="{{ route('projects.release-readiness.show', $aptoriaCurrentProject) }}"><i class="fa fa-check-circle"></i> {{ __('messages.nav.release_readiness_short') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.release-gates.*') }}"><a href="{{ route('projects.release-gates.index', $aptoriaCurrentProject) }}"><i class="fa fa-flag-checkered"></i> {{ __('messages.release_gates.short_title') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.reports.index') }}"><a href="{{ route('projects.reports.index', $aptoriaCurrentProject) }}"><i class="fa fa-file-text-o"></i> {{ __('messages.nav.reports') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.reports.builder.*') }}"><a href="{{ route('projects.reports.builder.create', $aptoriaCurrentProject) }}"><i class="fa fa-pencil-square-o"></i> {{ __('messages.report_builder.short_title') }}</a></li>

                        <li class="nav-header"><span>{{ __('messages.nav.automation_audit') }}</span></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.monitors.*') }}"><a href="{{ route('projects.monitors.index', $aptoriaCurrentProject) }}"><i class="fa fa-clock-o"></i> {{ __('messages.nav.monitors') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.calendar.*') }}"><a href="{{ route('projects.calendar.index', $aptoriaCurrentProject) }}"><i class="fa fa-calendar"></i> {{ __('messages.nav.calendar') }}</a></li>
                        <li class="{{ $aptoriaProjectMenuActive('projects.audit-log.*') }}"><a href="{{ route('projects.audit-log.index', $aptoriaCurrentProject) }}"><i class="fa fa-history"></i> {{ __('messages.nav.audit_log') }}</a></li>
                    @endif
                </ul>
            </li>

            <li class="{{ $aptoriaReleaseNavActive ? 'active' : '' }}">
                <a href="#"><i class="fa fa-flag-checkered"></i> <span class="nav-label">{{ __('messages.nav.release_reporting') }}</span><span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li class="{{ request()->routeIs('release-readiness.*') ? 'active' : '' }}"><a href="{{ route('release-readiness.index') }}"><i class="fa fa-check-circle"></i> {{ __('messages.nav.release_readiness') }}</a></li>
                    <li class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"><a href="{{ route('reports.index') }}"><i class="fa fa-file-text-o"></i> {{ __('messages.nav.reports') }}</a></li>
                </ul>
            </li>

            <li class="{{ $aptoriaOperationsNavActive ? 'active' : '' }}">
                <a href="#"><i class="fa fa-tasks"></i> <span class="nav-label">{{ __('messages.nav.operations') }}</span><span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li class="{{ request()->routeIs('monitors.index') ? 'active' : '' }}"><a href="{{ route('monitors.index') }}"><i class="fa fa-clock-o"></i> {{ __('messages.nav.monitors') }}</a></li>
                    <li class="{{ request()->routeIs('monitors.alerts.*') ? 'active' : '' }}"><a href="{{ route('monitors.alerts.index') }}"><i class="fa fa-bell-o"></i> {{ __('messages.nav.monitor_alerts') }}</a></li>
                    <li class="{{ request()->routeIs('calendar.*') ? 'active' : '' }}"><a href="{{ route('calendar.index') }}"><i class="fa fa-calendar"></i> {{ __('messages.nav.calendar') }}</a></li>
                </ul>
            </li>

            <li class="{{ $aptoriaAdminNavActive ? 'active' : '' }}">
                <a href="#"><i class="fa fa-shield"></i> <span class="nav-label">{{ __('messages.nav.audit_admin') }}</span><span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li class="{{ request()->routeIs('audit-log.*') ? 'active' : '' }}"><a href="{{ route('audit-log.index') }}"><i class="fa fa-history"></i> {{ __('messages.nav.audit_log') }}</a></li>
                    <li class="{{ request()->routeIs('system.health.*') ? 'active' : '' }}"><a href="{{ route('system.health.index') }}"><i class="fa fa-heartbeat"></i> {{ __('messages.nav.system_health') }}</a></li>
                    <li class="{{ request()->routeIs('settings.*') ? 'active' : '' }}"><a href="{{ route('settings.index') }}"><i class="fa fa-cog"></i> {{ __('messages.nav.settings') }}</a></li>
                    <li class="{{ request()->routeIs('demo-project.*') ? 'active' : '' }}"><a href="{{ route('demo-project.index') }}"><i class="fa fa-flask"></i> {{ __('messages.nav.demo_project') }}</a></li>
                </ul>
            </li>

            <li class="{{ $aptoriaSupportNavActive ? 'active' : '' }}">
                <a href="#"><i class="fa fa-life-ring"></i> <span class="nav-label">{{ __('messages.nav.learning_support') }}</span><span class="fa arrow"></span></a>
                <ul class="nav nav-second-level">
                    <li class="{{ request()->routeIs('how-it-works') ? 'active' : '' }}"><a href="{{ route('how-it-works') }}"><i class="fa fa-map-o"></i> {{ __('messages.nav.how_it_works') }}</a></li>
                    <li class="{{ request()->routeIs('help.*') ? 'active' : '' }}"><a href="{{ route('help.index') }}"><i class="fa fa-question-circle"></i> {{ __('messages.nav.help') }}</a></li>
                </ul>
            </li>
        </ul>
    </div>
</aside>

<div id="wrapper">
    <div class="content animate-panel aptoria-content">
        <div class="normalheader aptoria-page-titlebar">
            <div class="hpanel hblue">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-sm-8">
                            <div class="aptoria-breadcrumb small text-muted">
                                <a href="{{ route('dashboard') }}"><i class="fa fa-home"></i> Aptoria</a>
                                @if($aptoriaCurrentProject)
                                    <span>/</span>
                                    <a href="{{ route('projects.show', $aptoriaCurrentProject) }}">{{ $aptoriaCurrentProject->name }}</a>
                                @endif
                                <span>/</span>
                                <span>{{ $aptoriaRouteLabel }}</span>
                            </div>
                            <h2 class="font-light m-b-xs">{{ $aptoriaPageTitle ?? $aptoriaRouteLabel ?? __('messages.dashboard.title') }}</h2>
                            @if($aptoriaCurrentProject)
                                <small class="aptoria-page-context">
                                    <span class="label label-info"><i class="fa fa-briefcase"></i> {{ $aptoriaCurrentProject->name }}</span>
                                    @if($aptoriaCurrentProject->base_url)
                                        <span class="text-muted"><i class="fa fa-link"></i> {{ $aptoriaCurrentProject->base_url }}</span>
                                    @endif
                                </small>
                            @else
                                <small class="text-muted">{{ __('messages.app.default_workspace_context') }}</small>
                            @endif
                        </div>
                        <div class="col-sm-4 text-right aptoria-page-actions">
                            <div class="aptoria-mode-chip">
                                <div class="aptoria-mode-chip-title"><i class="fa fa-shield"></i> {{ __('messages.app.safe_qa_mode') }}</div>
                                <small>{{ __('messages.app.safe_qa_subtitle') }}</small>
                            </div>
                            <div class="aptoria-page-action-row">
                                @yield('page_actions')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div id="aptoria-flash-message" class="hidden" data-title="{{ __('messages.common.success_title') }}" data-message="{{ session('success') }}" data-type="success"></div>
        @elseif(session('warning'))
            <div id="aptoria-flash-message" class="hidden" data-title="{{ __('messages.common.needs_fix') }}" data-message="{{ session('warning') }}" data-type="warning"></div>
        @elseif(session('info'))
            <div id="aptoria-flash-message" class="hidden" data-title="{{ __('messages.common.info_title') }}" data-message="{{ session('info') }}" data-type="info"></div>
        @elseif(session('error'))
            <div id="aptoria-flash-message" class="hidden" data-title="{{ __('messages.common.needs_fix') }}" data-message="{{ session('error') }}" data-type="error"></div>
        @elseif($errors->any())
            <div id="aptoria-flash-message" class="hidden" data-title="{{ __('messages.common.needs_fix') }}" data-message="{{ implode(' ', $errors->all()) }}" data-type="error"></div>
        @endif
        @yield('content')
    </div>

    <footer class="footer">
        <span class="pull-right">{{ __('messages.app.footer_tagline') }}</span>
        {{ $aptoriaAppName }} v{{ config('aptoria.version') }} · © 2026 János Szujó
    </footer>
</div>


<div class="modal fade aptoria-scan-modal" id="aptoria-scan-modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="aptoria-loader" aria-hidden="true">
                    <div class="aptoria-loader-grid"></div>
                    <div class="aptoria-loader-sweep"></div>
                    <div class="aptoria-loader-pulse"></div>
                    <div class="aptoria-loader-core">
                        @if($aptoriaShowLogo)
                            <img src="{{ asset('assets/aptoria/img/aptoria-logo-icon.png') }}" alt="" class="aptoria-loader-logo">
                        @else
                            <i class="fa fa-crosshairs text-primary"></i>
                        @endif
                    </div>
                </div>
                <h3 class="m-t-md m-b-xs">{{ __('messages.scans.modal_title') }}</h3>
                <p class="text-muted m-b-lg">{{ __('messages.scans.modal_subtitle') }}</p>
                <div class="aptoria-scan-steps text-left">
                    <div><span class="fa fa-check-circle text-success"></span> {{ __('messages.scans.modal_step_safe') }}</div>
                    <div><span class="fa fa-shield text-info"></span> {{ __('messages.scans.modal_step_masking') }}</div>
                    <div><span class="fa fa-clock-o text-warning"></span> {{ __('messages.scans.modal_step_limits') }}</div>
                    <div><span class="fa fa-file-text-o text-primary"></span> {{ __('messages.scans.modal_step_report') }}</div>
                </div>
                <p class="small text-muted m-t-md m-b-none">{{ __('messages.scans.modal_wait') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade aptoria-scan-modal aptoria-suite-run-modal" id="aptoria-suite-run-modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="aptoria-loader" aria-hidden="true">
                    <div class="aptoria-loader-grid"></div>
                    <div class="aptoria-loader-sweep"></div>
                    <div class="aptoria-loader-pulse"></div>
                    <div class="aptoria-loader-core">
                        @if($aptoriaShowLogo)
                            <img src="{{ asset('assets/aptoria/img/aptoria-logo-icon.png') }}" alt="" class="aptoria-loader-logo">
                        @else
                            <i class="fa fa-play text-primary"></i>
                        @endif
                    </div>
                </div>
                <h3 class="m-t-md m-b-xs">{{ __('messages.regression_builder.modal_title') }}</h3>
                <p class="text-muted m-b-lg">{{ __('messages.regression_builder.modal_subtitle') }}</p>
                <div class="aptoria-scan-steps text-left">
                    <div><span class="fa fa-check-circle text-success"></span> {{ __('messages.regression_builder.modal_step_safe') }}</div>
                    <div><span class="fa fa-list-ol text-info"></span> {{ __('messages.regression_builder.modal_step_order') }}</div>
                    <div><span class="fa fa-shield text-warning"></span> {{ __('messages.regression_builder.modal_step_assertions') }}</div>
                    <div><span class="fa fa-file-text-o text-primary"></span> {{ __('messages.regression_builder.modal_step_results') }}</div>
                </div>
                <p class="small text-muted m-t-md m-b-none">{{ __('messages.regression_builder.modal_wait') }}</p>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/aptoria-ui/vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('assets/aptoria-ui/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/aptoria-ui/vendor/metisMenu/js/metisMenu.min.js') }}"></script>
<script src="{{ asset('assets/aptoria-ui/vendor/slimScroll/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('assets/aptoria-ui/vendor/iCheck/icheck.min.js') }}"></script>
<script src="{{ asset('assets/aptoria-ui/vendor/sparkline/index.js') }}"></script>
<script src="{{ asset('assets/aptoria-ui/vendor/datatables/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/aptoria-ui/vendor/datatables/js/dataTables.bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/aptoria-ui/vendor/chartjs/Chart.min.js') }}"></script>
<script src="{{ asset('assets/aptoria-ui/vendor/toastr/js/toastr.min.js') }}"></script>
@if($aptoriaEnableSweetalert ?? true)
<script src="{{ asset('assets/aptoria-ui/vendor/sweetalert/js/sweet-alert.min.js') }}"></script>
@endif
<script src="{{ asset('assets/aptoria-ui/js/aptoria-ui.js') }}"></script>
<script src="{{ asset('assets/aptoria/js/app.js') }}?v={{ config('aptoria.version') }}"></script>
@stack('scripts')
</body>
</html>
