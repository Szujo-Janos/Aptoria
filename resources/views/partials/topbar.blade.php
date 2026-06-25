<header class="app-topbar">
    <div class="topbar-menu">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('dashboard') }}" class="logo-topbar d-flex align-items-center text-decoration-none">
                <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-brand-logo aptoria-brand-logo-topbar" height="42">
            </a>

            <div class="topbar-item d-none d-xl-flex">
                <div class="dropdown">
                    <button class="topbar-link dropdown-toggle drop-arrow-none px-3" type="button" data-bs-toggle="dropdown" data-bs-offset="0,12" aria-expanded="false">
                        <i data-lucide="layout-grid" class="fs-xxl me-1"></i>
                        <span>{{ __('messages.topbar.mega_menu') }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-start dropdown-menu-xxl p-0">
                        <div class="p-3 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <span class="avatar avatar-lg rounded text-bg-primary"><span class="avatar-title"><i data-lucide="workflow"></i></span></span>
                                <div>
                                    <h5 class="mb-1">{{ __('messages.topbar.mega_title') }}</h5>
                                    <p class="text-muted mb-0 small">{{ __('messages.topbar.mega_note') }}</p>
                                </div>
                            </div>
                            @unless ($currentProject)
                                <div class="alert alert-light border d-flex align-items-center gap-2 mt-3 mb-0 py-2 small text-muted">
                                    <i data-lucide="info" class="fs-16"></i>
                                    <span>{{ __('messages.workspace.create_project_hint_short') }}</span>
                                </div>
                            @endunless
                        </div>
                        <div class="row g-0">
                            <div class="col-md-3 border-end">
                                <div class="p-3">
                                    <h6 class="dropdown-header px-0">{{ __('messages.topbar.workspace') }}</h6>
                                    <a href="{{ route('dashboard') }}" class="dropdown-item"><i data-lucide="gauge" class="me-2 fs-16"></i>{{ __('messages.nav.dashboard') }}</a>
                                    <a href="{{ route('projects.index') }}" class="dropdown-item"><i data-lucide="folder-kanban" class="me-2 fs-16"></i>{{ __('messages.nav.projects') }}</a>
                                    @if ($currentProject)
                                        <a href="{{ route('projects.show', $currentProject) }}" class="dropdown-item"><i data-lucide="folder-open" class="me-2 fs-16"></i>{{ __('messages.workspace.workspace_overview') }}</a>
                                        <a href="{{ route('projects.environments.index', $currentProject) }}" class="dropdown-item"><i data-lucide="globe" class="me-2 fs-16"></i>{{ __('messages.nav.environments') }}</a>
                                        <a href="{{ route('projects.auth-profiles.index', $currentProject) }}" class="dropdown-item"><i data-lucide="key-round" class="me-2 fs-16"></i>{{ __('messages.nav.auth_profiles') }}</a>
                                        @if (in_array('*', $currentProjectPermissions ?? [], true) || in_array('members.view', $currentProjectPermissions ?? [], true))
                                            <a href="{{ route('projects.members.index', $currentProject) }}" class="dropdown-item"><i data-lucide="shield-check" class="me-2 fs-16"></i>{{ __('messages.nav.project_members') }}</a>
                                        @endif
                                        <a href="{{ route('projects.settings.edit', $currentProject) }}" class="dropdown-item"><i data-lucide="folder-settings" class="me-2 fs-16"></i>{{ __('messages.nav.project_settings') }}</a>
                                    @else
                                        <a href="{{ route('projects.create') }}" class="dropdown-item"><i data-lucide="plus-circle" class="me-2 fs-16"></i>{{ __('messages.projects.new') }}</a>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3 border-end">
                                <div class="p-3">
                                    <h6 class="dropdown-header px-0">{{ __('messages.topbar.evidence_quality') }}</h6>
                                    @if ($currentProject)
                                        <a href="{{ route('projects.qa-cockpit.show', $currentProject) }}" class="dropdown-item"><i data-lucide="scan-search" class="me-2 fs-16"></i>{{ __('messages.nav.qa_cockpit') }}</a>
                                        <a href="{{ route('projects.demo-guide.show', $currentProject) }}" class="dropdown-item"><i data-lucide="map" class="me-2 fs-16"></i>{{ __('messages.nav.demo_guide') }}</a>
                                        <a href="{{ route('projects.endpoints.index', $currentProject) }}" class="dropdown-item"><i data-lucide="plug-connected" class="me-2 fs-16"></i>{{ __('messages.nav.endpoint_inventory') }}</a>
                                        <a href="{{ route('projects.safe-scans.index', $currentProject) }}" class="dropdown-item"><i data-lucide="radar" class="me-2 fs-16"></i>{{ __('messages.nav.safe_scan') }}</a>
                                        <a href="{{ route('projects.assertions.index', $currentProject) }}" class="dropdown-item"><i data-lucide="checklist" class="me-2 fs-16"></i>{{ __('messages.nav.assertions') }}</a>
                                        <a href="{{ route('projects.contract-validation.index', $currentProject) }}" class="dropdown-item"><i data-lucide="file-check-2" class="me-2 fs-16"></i>{{ __('messages.nav.contract_validation') }}</a>
                                        <a href="{{ route('projects.import-center.index', $currentProject) }}" class="dropdown-item"><i data-lucide="brackets-contain" class="me-2 fs-16"></i>{{ __('messages.nav.import_center') }}</a>
                                        <a href="{{ route('projects.tests.index', $currentProject) }}" class="dropdown-item"><i data-lucide="flask-conical" class="me-2 fs-16"></i>{{ __('messages.nav.native_tests') }}</a>
                                        <a href="{{ route('projects.snapshots.index', $currentProject) }}" class="dropdown-item"><i data-lucide="camera" class="me-2 fs-16"></i>{{ __('messages.nav.snapshots') }}</a>
                                        <a href="{{ route('projects.findings.index', $currentProject) }}" class="dropdown-item"><i data-lucide="bug" class="me-2 fs-16"></i>{{ __('messages.nav.findings') }}</a>
                                        <a href="{{ route('projects.evidence.index', $currentProject) }}" class="dropdown-item"><i data-lucide="folder-check" class="me-2 fs-16"></i>{{ __('messages.nav.evidence') }}</a>
                                    @else
                                        <div class="dropdown-item-text text-muted small"><i data-lucide="lock" class="me-2 fs-16"></i>{{ __('messages.workspace.create_project_hint_short') }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-3 border-end">
                                <div class="p-3">
                                    <h6 class="dropdown-header px-0">{{ __('messages.topbar.release_ops') }}</h6>
                                    @if ($currentProject)
                                        <a href="{{ route('projects.release-readiness.index', $currentProject) }}" class="dropdown-item"><i data-lucide="shield-chevron" class="me-2 fs-16"></i>{{ __('messages.nav.release_readiness') }}</a>
                                        <a href="{{ route('projects.release-gates.index', $currentProject) }}" class="dropdown-item"><i data-lucide="workflow" class="me-2 fs-16"></i>{{ __('messages.nav.release_gates') }}</a>
                                        <a href="{{ route('projects.evidence-packs.index', $currentProject) }}" class="dropdown-item"><i data-lucide="archive" class="me-2 fs-16"></i>{{ __('messages.nav.evidence_packs') }}</a>
                                        <a href="{{ route('projects.reports.index', $currentProject) }}" class="dropdown-item"><i data-lucide="report-analytics" class="me-2 fs-16"></i>{{ __('messages.nav.reports') }}</a>
                                        <a href="{{ route('projects.calendar.index', $currentProject) }}" class="dropdown-item"><i data-lucide="calendar-stats" class="me-2 fs-16"></i>{{ __('messages.nav.calendar') }}</a>
                                        <a href="{{ route('projects.client-portal.index', $currentProject) }}" class="dropdown-item"><i data-lucide="door-open" class="me-2 fs-16"></i>{{ __('messages.nav.client_portal') }}</a>
                                    @else
                                        <div class="dropdown-item-text text-muted small"><i data-lucide="lock" class="me-2 fs-16"></i>{{ __('messages.workspace.create_project_hint_short') }}</div>
                                    @endif
                                    <a href="{{ route('audit.index') }}" class="dropdown-item"><i data-lucide="file-delta" class="me-2 fs-16"></i>{{ __('messages.nav.audit_log') }}</a>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <h6 class="dropdown-header px-0">{{ __('messages.nav.settings') }}</h6>
                                    @if (auth()->user()?->isAdmin())
                                        <a href="{{ route('users.index') }}" class="dropdown-item"><i data-lucide="user-cog" class="me-2 fs-16"></i>{{ __('messages.nav.users') }}</a>
                                    @endif
                                    <a href="{{ route('program-settings.edit') }}" class="dropdown-item"><i data-lucide="tool" class="me-2 fs-16"></i>{{ __('messages.nav.program_settings') }}</a>
                                    @if (auth()->user()?->isAdmin())
                                        <a href="{{ route('program-settings.license') }}" class="dropdown-item"><i data-lucide="key-round" class="me-2 fs-16"></i>{{ __('messages.nav.license_management') }}</a>
                                        <a href="{{ route('program-settings.license-issuer') }}" class="dropdown-item"><i data-lucide="certificate" class="me-2 fs-16"></i>{{ __('messages.nav.license_issuer') }}</a>
                                    @endif
                                    <a href="{{ route('help.how_it_works') }}" class="dropdown-item"><i data-lucide="sitemap" class="me-2 fs-16"></i>{{ __('messages.nav.how_it_works') }}</a>
                                    <a href="{{ route('help.index') }}" class="dropdown-item"><i data-lucide="help-circle" class="me-2 fs-16"></i>{{ __('messages.nav.help') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="topbar-item d-none d-lg-flex">
                <div class="dropdown">
                    <button class="topbar-link dropdown-toggle drop-arrow-none aptoria-project-switcher" type="button" data-bs-toggle="dropdown" data-bs-offset="0,12" aria-expanded="false">
                        <span class="avatar avatar-xs rounded {{ $currentProject ? 'text-bg-success' : 'text-bg-warning' }} me-2"><span class="avatar-title"><i data-lucide="{{ $currentProject ? 'folder-dot' : 'folder-plus' }}"></i></span></span>
                        <span class="text-truncate" style="max-width: 210px;">{{ $currentProject?->name ?? __('messages.workspace.no_current_project') }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-start p-0" style="min-width: 320px;">
                        <div class="px-3 py-2 border-bottom">
                            <span class="small text-muted">{{ __('messages.workspace.project_switcher') }}</span>
                        </div>
                        <div class="aptoria-project-switch-list">
                            @forelse ($projectMenuItems as $projectItem)
                                <a href="{{ route('projects.switch', $projectItem) }}" class="dropdown-item d-flex align-items-start gap-2 py-2 {{ $currentProject && $currentProject->id === $projectItem->id ? 'active' : '' }}">
                                    <span class="avatar avatar-xs rounded text-bg-light"><span class="avatar-title"><i data-lucide="folder"></i></span></span>
                                    <span class="flex-grow-1 text-truncate">
                                        <span class="d-block text-truncate">{{ $projectItem->name }}</span>
                                        <small class="text-muted d-block text-truncate">{{ $projectItem->base_url ?: __('messages.workspace.no_base_url') }}</small>
                                    </span>
                                </a>
                            @empty
                                <div class="px-3 py-3 text-muted small">
                                    <div class="fw-medium text-body mb-1">{{ __('messages.workspace.no_project_title') }}</div>
                                    <div>{{ __('messages.workspace_mode.no_projects_in_mode') }}</div>
                                </div>
                            @endforelse
                        </div>
                        <div class="border-top p-2 d-flex gap-2">
                            <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm flex-fill"><i data-lucide="plus" class="me-1"></i>{{ __('messages.projects.new') }}</a>
                            <a href="{{ route('projects.context.clear') }}" class="btn btn-light btn-sm"><i data-lucide="x" class="me-1"></i>{{ __('messages.common.clear') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="aptoria-workspace-mode-switch d-none d-lg-flex align-items-center gap-1 me-1" aria-label="{{ __('messages.workspace_mode.switch_label') }}">
                <form method="POST" action="{{ route('workspace.mode', 'live') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $currentWorkspaceMode === 'live' ? 'btn-success' : 'btn-light' }} aptoria-mode-toggle">
                        <i data-lucide="shield-check" class="me-1"></i>{{ __('messages.workspace_mode.live_short') }}
                        <span class="badge text-bg-light ms-1">{{ $liveProjectCount ?? 0 }}</span>
                    </button>
                </form>
                <form method="POST" action="{{ route('workspace.mode', 'sandbox') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $currentWorkspaceMode === 'sandbox' ? 'btn-warning' : 'btn-light' }} aptoria-mode-toggle">
                        <i data-lucide="flask-conical" class="me-1"></i>{{ __('messages.workspace_mode.sandbox_short') }}
                        <span class="badge text-bg-light ms-1">{{ $sandboxProjectCount ?? 0 }}</span>
                    </button>
                </form>
            </div>

            <div class="topbar-item">
                <a href="{{ route('language.switch', app()->getLocale() === 'hu' ? 'en' : 'hu') }}" class="topbar-link fw-semibold text-decoration-none px-3">
                    {{ strtoupper(app()->getLocale() === 'hu' ? 'EN' : 'HU') }}
                </a>
            </div>

            <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link" id="light-dark-mode" type="button" title="{{ __('messages.topbar.theme_toggle') }}">
                    <i data-lucide="moon" class="fs-xxl mode-light-moon"></i>
                    <i data-lucide="sun" class="fs-xxl mode-light-sun"></i>
                </button>
            </div>

            <div class="topbar-item d-none d-sm-flex">
                <a href="{{ route('audit.index') }}" class="topbar-link position-relative text-decoration-none" title="{{ __('messages.nav.audit_log') }}">
                    <i data-lucide="file-delta" class="fs-xxl"></i>
                    <span class="badge badge-square text-bg-success topbar-badge">{{ min($auditCount ?? 0, 9) }}</span>
                </a>
            </div>

            <div class="topbar-item nav-user">
                <div class="dropdown">
                    <a class="topbar-link dropdown-toggle drop-arrow-none px-2 text-decoration-none" data-bs-toggle="dropdown" data-bs-offset="0,13" href="#" aria-haspopup="false" aria-expanded="false">
                        <span class="avatar avatar-xs rounded-circle text-bg-light me-lg-2">
                            <span class="avatar-title"><i data-lucide="user" class="fs-md"></i></span>
                        </span>
                        <span class="d-none d-lg-inline text-body">{{ auth()->user()->name }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">{{ $appName }}</h6>
                        </div>
                        <a href="{{ route('profile.show') }}" class="dropdown-item">
                            <i data-lucide="user" class="me-2 fs-17 align-middle"></i>
                            <span class="align-middle">{{ __('messages.profile.title') }}</span>
                        </a>
                        @if ($currentProject && (in_array('*', $currentProjectPermissions ?? [], true) || in_array('members.view', $currentProjectPermissions ?? [], true)))
                            <a href="{{ route('projects.members.index', $currentProject) }}" class="dropdown-item">
                                <i data-lucide="shield-check" class="me-2 fs-17 align-middle"></i>
                                <span class="align-middle">{{ __('messages.nav.project_members') }}</span>
                            </a>
                        @endif
                        @if ($currentProject)
                            <a href="{{ route('projects.settings.edit', $currentProject) }}" class="dropdown-item">
                                <i data-lucide="folder-settings" class="me-2 fs-17 align-middle"></i>
                                <span class="align-middle">{{ __('messages.nav.project_settings') }}</span>
                            </a>
                        @endif
                        @if (auth()->user()?->isAdmin())
                            <a href="{{ route('users.index') }}" class="dropdown-item">
                                <i data-lucide="user-cog" class="me-2 fs-17 align-middle"></i>
                                <span class="align-middle">{{ __('messages.nav.users') }}</span>
                            </a>
                        @endif
                        <a href="{{ route('program-settings.edit') }}" class="dropdown-item">
                            <i data-lucide="tool" class="me-2 fs-17 align-middle"></i>
                            <span class="align-middle">{{ __('messages.nav.program_settings') }}</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">
                                <i data-lucide="log-out" class="me-2 fs-17 align-middle"></i>
                                <span class="align-middle">{{ __('messages.auth.logout') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
