@php
    $projectWorkspaceBadgeLabel = ($currentProject && $currentProject->isSandbox()) ? __('messages.workspace_mode.sandbox_short') : __('messages.workspace_mode.live_short');
    $projectWorkspaceBadgeClass = ($currentProject && $currentProject->isSandbox()) ? 'text-bg-warning' : 'text-bg-success';
    $projectWorkspaceInfoBadgeClass = ($currentProject && $currentProject->isSandbox()) ? 'text-bg-warning' : 'text-bg-info';
@endphp
<div class="sidenav-menu">
    <div class="scrollbar" data-simplebar>
        <div class="sidenav-user text-center text-nowrap">
            <a href="{{ route('dashboard') }}" class="sidenav-user-name text-center">
                <span class="d-block">{{ $appName }}</span>
                <span class="fs-11 text-muted d-block">{{ __('messages.product.tagline') }}</span>
            </a>
        </div>

        @if ($currentProject)
            <div class="aptoria-sidebar-project mx-3 mb-3">
                <small class="text-muted d-block mb-1">{{ __('messages.workspace.current_project') }}</small>
                <a href="{{ route('projects.show', $currentProject) }}" class="d-flex align-items-start gap-2 text-decoration-none">
                    <span class="avatar avatar-xs rounded text-bg-primary"><span class="avatar-title"><i data-lucide="folder-kanban"></i></span></span>
                    <span class="min-w-0">
                        <span class="d-block text-truncate text-body">{{ $currentProject->name }}</span>
                        <span class="badge badge-label {{ $projectWorkspaceBadgeClass }} mb-1">{{ $projectWorkspaceBadgeLabel }}</span>
                        <small class="text-muted d-block text-truncate">{{ $currentProject->environment_label ?: __('messages.workspace.no_environment') }}</small>
                    </span>
                </a>
            </div>
        @else
            <div class="aptoria-sidebar-project aptoria-sidebar-project-empty mx-3 mb-3">
                <small class="text-muted d-block mb-1">{{ __('messages.workspace.current_project') }}</small>
                <div class="d-flex align-items-start gap-2">
                    <span class="avatar avatar-xs rounded text-bg-warning"><span class="avatar-title"><i data-lucide="folder-plus"></i></span></span>
                    <span class="min-w-0">
                        <span class="d-block text-body">{{ __('messages.workspace.no_current_project') }}</span>
                        <small class="text-muted d-block">{{ __('messages.workspace.create_project_hint_short') }}</small>
                    </span>
                </div>
                <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm w-100 mt-2"><i data-lucide="plus" class="me-1"></i>{{ __('messages.projects.new') }}</a>
            </div>
        @endif

        <ul class="side-nav">
            <li class="side-nav-item">
                <a href="{{ route('dashboard') }}" class="side-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="gauge"></i></span>
                    <span class="menu-text">{{ __('messages.nav.dashboard') }}</span>
                </a>
            </li>
            <li class="side-nav-item">
                <a href="{{ route('projects.index') }}" class="side-nav-link {{ (request()->routeIs('projects.index') || request()->routeIs('projects.create') || request()->routeIs('projects.edit')) ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="folder-kanban"></i></span>
                    <span class="menu-text">{{ __('messages.nav.projects') }}</span>
                </a>
            </li>

            @if ($currentProject)
                <li class="side-nav-item">
                    <a href="{{ route('projects.show', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.show') ? 'active' : '' }}">
                        <span class="menu-icon"><i data-lucide="folder-open"></i></span>
                        <span class="menu-text">{{ __('messages.workspace.workspace_overview') }}</span>
                    </a>
                </li>
                <li class="side-nav-item">
                    <a href="{{ route('projects.qa-cockpit.show', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.qa-cockpit.*') ? 'active' : '' }}">
                        <span class="menu-icon"><i data-lucide="scan-search"></i></span>
                        <span class="menu-text">{{ __('messages.nav.qa_cockpit') }}</span>
                        <span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span>
                    </a>
                </li>
                <li class="side-nav-item">
                    <a href="{{ route('projects.demo-guide.show', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.demo-guide.*') ? 'active' : '' }}">
                        <span class="menu-icon"><i data-lucide="map"></i></span>
                        <span class="menu-text">{{ __('messages.nav.demo_guide') }}</span>
                        <span class="badge badge-label {{ $projectWorkspaceInfoBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span>
                    </a>
                </li>

                <li class="side-nav-title">{{ __('messages.nav.qa_workspace') }}</li>
                <li class="side-nav-item"><a href="{{ route('projects.environments.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.environments.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="globe"></i></span><span class="menu-text">{{ __('messages.nav.environments') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.auth-profiles.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.auth-profiles.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="key-round"></i></span><span class="menu-text">{{ __('messages.nav.auth_profiles') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.endpoints.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.endpoints.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="plug-connected"></i></span><span class="menu-text">{{ __('messages.nav.endpoint_inventory') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.safe-scans.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.safe-scans.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="radar"></i></span><span class="menu-text">{{ __('messages.nav.safe_scan') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.assertions.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.assertions.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="checklist"></i></span><span class="menu-text">{{ __('messages.nav.assertions') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.contract-validation.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.contract-validation.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="file-check-2"></i></span><span class="menu-text">{{ __('messages.nav.contract_validation') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.import-center.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.import-center.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="brackets-contain"></i></span><span class="menu-text">{{ __('messages.nav.import_center') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.tests.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.tests.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="flask-conical"></i></span><span class="menu-text">{{ __('messages.nav.native_tests') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.snapshots.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.snapshots.*') || request()->routeIs('projects.snapshot-compares.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="camera"></i></span><span class="menu-text">{{ __('messages.nav.snapshots') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.findings.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.findings.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="bug"></i></span><span class="menu-text">{{ __('messages.nav.findings') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.evidence.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.evidence.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="folder-check"></i></span><span class="menu-text">{{ __('messages.nav.evidence') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>

                <li class="side-nav-title">{{ __('messages.nav.release') }}</li>
                <li class="side-nav-item"><a href="{{ route('projects.release-readiness.index', $currentProject) }}" class="side-nav-link {{ (request()->routeIs('projects.release-readiness.*') || request()->routeIs('projects.release-decisions.*')) ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="shield-chevron"></i></span><span class="menu-text">{{ __('messages.nav.release_readiness') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.release-gates.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.release-gates.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="workflow"></i></span><span class="menu-text">{{ __('messages.nav.release_gates') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.evidence-packs.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.evidence-packs.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="archive"></i></span><span class="menu-text">{{ __('messages.nav.evidence_packs') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.reports.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.reports.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="report-analytics"></i></span><span class="menu-text">{{ __('messages.nav.reports') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
                <li class="side-nav-item"><a href="{{ route('projects.client-portal.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.client-portal.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="door-open"></i></span><span class="menu-text">{{ __('messages.nav.client_portal') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>

                <li class="side-nav-title">{{ __('messages.nav.operations') }}</li>
                <li class="side-nav-item"><a href="{{ route('projects.calendar.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.calendar.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="calendar-stats"></i></span><span class="menu-text">{{ __('messages.nav.calendar') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
            @endif

            <li class="side-nav-item"><a href="{{ route('audit.index') }}" class="side-nav-link {{ request()->routeIs('audit.index') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="file-delta"></i></span><span class="menu-text">{{ __('messages.nav.audit_log') }}</span></a></li>

            <li class="side-nav-title">{{ __('messages.nav.settings') }}</li>
            @if (auth()->user()?->isAdmin())
                <li class="side-nav-item"><a href="{{ route('users.index') }}" class="side-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="user-cog"></i></span><span class="menu-text">{{ __('messages.nav.users') }}</span><span class="badge badge-label text-bg-success">{{ __('messages.workspace_mode.live_short') }}</span></a></li>
            @endif
            @if ($currentProject && (in_array('*', $currentProjectPermissions ?? [], true) || in_array('members.view', $currentProjectPermissions ?? [], true)))
                <li class="side-nav-item"><a href="{{ route('projects.members.index', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.members.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="shield-check"></i></span><span class="menu-text">{{ __('messages.nav.project_members') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
            @endif
            @if ($currentProject)
                <li class="side-nav-item"><a href="{{ route('projects.settings.edit', $currentProject) }}" class="side-nav-link {{ request()->routeIs('projects.settings.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="folder-settings"></i></span><span class="menu-text">{{ __('messages.nav.project_settings') }}</span><span class="badge badge-label {{ $projectWorkspaceBadgeClass }}">{{ $projectWorkspaceBadgeLabel }}</span></a></li>
            @endif
            <li class="side-nav-item"><a href="{{ route('program-settings.edit') }}" class="side-nav-link {{ request()->routeIs('program-settings.edit') || request()->routeIs('program-settings.update') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="tool"></i></span><span class="menu-text">{{ __('messages.nav.program_settings') }}</span></a></li>
            @if (auth()->user()?->isAdmin())
                <li class="side-nav-item"><a href="{{ route('deployment-readiness.index') }}" class="side-nav-link {{ request()->routeIs('deployment-readiness.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="rocket"></i></span><span class="menu-text">Deployment readiness</span><span class="badge badge-label text-bg-warning">Deploy</span></a></li>
                <li class="side-nav-item"><a href="{{ route('subdomain-deployment.index') }}" class="side-nav-link {{ request()->routeIs('subdomain-deployment.*') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="network"></i></span><span class="menu-text">Subdomain deployment</span><span class="badge badge-label text-bg-info">Smoke</span></a></li>
                <li class="side-nav-item"><a href="{{ route('program-settings.license') }}" class="side-nav-link {{ (request()->routeIs('program-settings.license') || request()->routeIs('program-settings.license.upload') || request()->routeIs('program-settings.license.public-key')) ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="key-round"></i></span><span class="menu-text">{{ __('messages.nav.license_management') }}</span><span class="badge badge-label text-bg-success">{{ __('messages.workspace_mode.live_short') }}</span></a></li>
            @endif
            <li class="side-nav-item"><a href="{{ route('help.how_it_works') }}" class="side-nav-link {{ request()->routeIs('help.how_it_works') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="sitemap"></i></span><span class="menu-text">{{ __('messages.nav.how_it_works') }}</span></a></li>
            <li class="side-nav-item"><a href="{{ route('help.index') }}" class="side-nav-link {{ request()->routeIs('help.index') ? 'active' : '' }}"><span class="menu-icon"><i data-lucide="help-circle"></i></span><span class="menu-text">{{ __('messages.nav.help') }}</span></a></li>
        </ul>

        @php
            $licenseValid = (bool) ($licenseStatus['valid'] ?? false);
            $licenseToneClass = $licenseValid ? 'is-valid' : 'is-invalid';
            $licenseStateLabel = $licenseStatus['label'] ?? __('messages.license.status_unknown');
            $licenseStateMessage = $licenseStatus['message'] ?? __('messages.license.status_unknown');
        @endphp
        <div class="aptoria-sidebar-license mx-3 mt-3 mb-3 {{ $licenseToneClass }}">
            <div class="aptoria-sidebar-license-head">{{ __('messages.license.dashboard_title') }}</div>
            <div class="aptoria-sidebar-license-state">
                <span class="aptoria-sidebar-license-dot"></span>
                <strong>{{ $licenseStateLabel }}</strong>
            </div>
            <small>{{ $licenseStateMessage }}</small>
        </div>

    </div>
</div>
