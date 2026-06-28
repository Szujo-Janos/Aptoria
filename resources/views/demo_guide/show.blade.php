@extends($layout)

@section('title', $publicMode ? 'Aptoria Demo Guide – Public Showcase Workspace' : 'Aptoria Demo Guide · Full Showcase Workspace')
@section('meta_description', 'Aptoria public demo guide for the full showcase QA workspace, sandbox API endpoints, demo credentials, import artifacts and guided review scenarios.')
@section('robots', $publicMode ? 'index,follow,max-image-preview:large' : 'noindex,nofollow,noarchive')
@section('canonical_url', $publicMode ? 'https://aptoria.dev/demo-guide' : url()->current())
@section('og_title', 'Aptoria Demo Guide – Public Showcase Workspace')
@section('og_description', 'Walk through Aptoria with a complete prebuilt API QA project, sandbox endpoints, evidence flows, imports and release-readiness examples.')
@section('og_url', 'https://aptoria.dev/demo-guide')
@section('page_title', 'Aptoria Demo Guide')
@section('body_class', $publicMode ? 'aptoria-landing-body min-vh-100' : 'auth-bg min-vh-100 py-4')

@section('page_actions')
    @if (!$publicMode && $project)
        <a href="{{ route('projects.qa-cockpit.show', $project) }}" class="btn btn-primary"><i data-lucide="scan-search" class="me-1"></i>{{ __('messages.nav.qa_cockpit') }}</a>
        <a href="{{ route('projects.safe-scans.index', $project) }}" class="btn btn-light"><i data-lucide="radar" class="me-1"></i>{{ __('messages.nav.safe_scan') }}</a>
    @endif
@endsection

@section('content')
@php
    $landingUrl = rtrim((string) ($landingUrl ?? config('aptoria.domain.landing_url', 'https://aptoria.dev')), '/') ?: 'https://aptoria.dev';
    $demoUrl = rtrim((string) ($demoUrl ?? config('aptoria.domain.demo_url', 'https://demo.aptoria.dev')), '/') ?: 'https://demo.aptoria.dev';
    $guideRoute = $publicMode ? $landingUrl.'/demo-guide' : route('projects.demo-guide.show', $project);
    $workspaceUrl = $publicMode ? $demoUrl.'/demo-workspace' : route('demo-workspace');
    $loginUrl = $publicMode ? $demoUrl.'/login' : route('login');
    $apiHealthUrl = $publicMode ? $baseUrl.'/health' : route('demo-api.health');

    $demoMap = [
        ['icon' => 'layout-dashboard', 'title' => 'Dashboard', 'copy' => 'Readiness, risk, coverage, blind spots and recent activity.'],
        ['icon' => 'server-cog', 'title' => 'Environments & auth', 'copy' => 'Production, staging, sandbox, bearer token, API key, basic auth and public profiles.'],
        ['icon' => 'route', 'title' => 'Endpoint inventory', 'copy' => 'Healthy, slow, protected, deprecated, failing and sensitive-data examples.'],
        ['icon' => 'radar', 'title' => 'Safe scan history', 'copy' => 'Successful, warning, failed, protected and slow response scan examples.'],
        ['icon' => 'folder-check', 'title' => 'Evidence repository', 'copy' => 'Response bodies, headers, imported evidence, manual QA evidence and archived evidence.'],
        ['icon' => 'bug', 'title' => 'Finding triage', 'copy' => 'Critical to info findings, duplicates, merged items, accepted risk and reopened states.'],
        ['icon' => 'brackets-contain', 'title' => 'Import Center', 'copy' => 'OpenAPI, Postman, Newman, Jira CSV, HAR and conflict-preview examples.'],
        ['icon' => 'shield-check', 'title' => 'Release readiness', 'copy' => 'Profiles, simulations, blocker items, warnings and final decision package.'],
        ['icon' => 'file-bar-chart', 'title' => 'Reports', 'copy' => 'HTML reports, evidence packs and release decision report previews.'],
        ['icon' => 'history', 'title' => 'Audit log', 'copy' => 'A traceable timeline for scans, imports, findings, evidence and gates.'],
    ];
@endphp

@if ($publicMode)
    <div class="aptoria-landing-scene aptoria-demo-guide-scene">
        <div class="aptoria-landing-backdrop"></div>
        <div class="aptoria-landing-grid"></div>

        <section class="aptoria-landing-shell py-4 py-xl-5">
            <div class="aptoria-landing-frame">
                <header class="aptoria-landing-nav mb-4 mb-xl-5">
                    <a href="{{ $landingUrl }}" class="aptoria-landing-nav-brand" aria-label="Aptoria">
                        <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-brand-logo">
                    </a>
                    <nav class="aptoria-landing-nav-links" aria-label="Demo navigation">
                        <a href="{{ $guideRoute }}">Demo guide</a>
                        <a href="{{ $loginUrl }}">Login</a>
                        <a href="{{ $apiHealthUrl }}" target="_blank" rel="noopener">API health</a>
                        <a href="{{ $landingUrl }}">Aptoria</a>
                    </nav>
                </header>

                <main class="aptoria-demo-guide-main">
                    <section class="aptoria-landing-panel aptoria-demo-guide-hero">
                        <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-brand-logo aptoria-demo-guide-logo">
                        <div class="d-inline-flex align-items-center gap-2 aptoria-landing-badge mb-4">
                            <span class="aptoria-landing-badge-dot"></span>
                            <span>Full showcase demo workspace</span>
                        </div>
                        <h1 class="aptoria-landing-title">Walk through Aptoria with a complete prebuilt QA project.</h1>
                        <p class="aptoria-landing-lead">
                            The demo workspace is loaded with realistic endpoints, imports, findings, evidence, release gates,
                            reports and audit history. You can see what the system does without exposing real customer data
                            or allowing destructive public actions.
                        </p>
                        <div class="aptoria-landing-cta-row">
                            <a href="{{ $loginUrl }}" class="btn btn-primary btn-lg"><i data-lucide="log-in" class="me-1"></i>Sign in</a>
                            <a href="{{ $workspaceUrl }}" class="btn btn-light btn-lg text-dark"><i data-lucide="folder-kanban" class="me-1"></i>Open workspace</a>
                            <a href="{{ $apiHealthUrl }}" target="_blank" class="btn btn-outline-info btn-lg"><i data-lucide="braces" class="me-1"></i>Try JSON API</a>
                        </div>
                    </section>

                    @if (config('aptoria.demo.mode'))
                        <div class="aptoria-demo-alert d-flex gap-2 align-items-start">
                            <i data-lucide="shield-check" class="mt-1"></i>
                            <div>
                                <strong>Public demo safeguards are active.</strong>
                                <div class="small mt-1">Admin, license, setup, user-management, destructive writes and unrestricted external scans remain blocked.</div>
                            </div>
                        </div>
                    @endif

                    <div class="aptoria-demo-grid">
                        @if ($selectedScenario)
                            <section class="aptoria-landing-panel aptoria-demo-section aptoria-demo-span-5">
                                <div class="aptoria-demo-section-head">
                                    <div>
                                        <small class="aptoria-landing-eyebrow">Guided scenarios</small>
                                        <h2>Scenario templates</h2>
                                        <p>Select a realistic review flow and follow the expected evidence path.</p>
                                    </div>
                                </div>
                                <div class="aptoria-demo-card-grid">
                                    @foreach ($scenarios as $scenario)
                                        <a href="{{ $guideRoute }}?scenario={{ $scenario['slug'] }}" class="aptoria-demo-tile {{ $selectedScenario['slug'] === $scenario['slug'] ? 'is-active' : '' }}">
                                            <i data-lucide="{{ $scenario['icon'] }}"></i>
                                            <strong>{{ $scenario['title'] }}</strong>
                                            <span>{{ $scenario['summary'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </section>

                            <section class="aptoria-landing-panel aptoria-demo-section aptoria-demo-span-7">
                                <div class="aptoria-demo-section-head">
                                    <div>
                                        <small class="aptoria-landing-eyebrow">{{ $selectedScenario['badge'] }}</small>
                                        <h2>{{ $selectedScenario['title'] }}</h2>
                                        <p>{{ $selectedScenario['objective'] }}</p>
                                    </div>
                                    <span class="aptoria-landing-chip">{{ $selectedScenario['duration'] }}</span>
                                </div>
                                <div class="aptoria-demo-alert mb-3">
                                    <strong>Expected result</strong>
                                    <div class="small mt-1">{{ $selectedScenario['expected_result'] }}</div>
                                </div>
                                <div class="aptoria-demo-card-grid">
                                    @foreach ($selectedScenario['steps'] as $index => $step)
                                        @php($actionUrl = $scenarioActions[$step['action']] ?? null)
                                        <div class="aptoria-demo-tile">
                                            <i data-lucide="{{ $step['icon'] }}"></i>
                                            <strong>{{ $index + 1 }}. {{ $step['title'] }}</strong>
                                            <span>{{ $step['copy'] }}</span>
                                            @if ($actionUrl)
                                                <a href="{{ $actionUrl }}" class="small fw-semibold mt-2 d-inline-block">Open <i data-lucide="arrow-up-right" class="ms-1"></i></a>
                                            @else
                                                <span class="small text-muted mt-2">Sign in to open this workspace action.</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        <section class="aptoria-landing-panel aptoria-demo-section aptoria-demo-span-4">
                            <div class="aptoria-demo-section-head">
                                <div>
                                    <small class="aptoria-landing-eyebrow">Access</small>
                                    <h2>Demo credentials</h2>
                                    <p>Use this account to open the showcase project.</p>
                                </div>
                            </div>
                            <div class="aptoria-demo-console">
                                <div class="aptoria-demo-console-row"><small>Email</small><code>{{ $demoUserEmail }}</code></div>
                                <div class="aptoria-demo-console-row"><small>Password</small><code>{{ $demoUserPassword }}</code></div>
                                <div class="aptoria-demo-console-row"><small>Base URL</small><code>{{ $baseUrl }}</code></div>
                            </div>
                            <div class="mt-3">
                                <a href="{{ $loginUrl }}" class="btn btn-primary"><i data-lucide="log-in" class="me-1"></i>Open demo login</a>
                            </div>
                        </section>

                        <section class="aptoria-landing-panel aptoria-demo-section aptoria-demo-span-8">
                            <div class="aptoria-demo-section-head">
                                <div>
                                    <small class="aptoria-landing-eyebrow">Full product coverage</small>
                                    <h2>What the showcase project contains</h2>
                                    <p>The demo is not an empty sandbox. It is a complete reviewable Aptoria project.</p>
                                </div>
                            </div>
                            <div class="aptoria-demo-card-grid is-three">
                                @foreach ($demoMap as $item)
                                    <div class="aptoria-demo-tile">
                                        <i data-lucide="{{ $item['icon'] }}"></i>
                                        <strong>{{ $item['title'] }}</strong>
                                        <span>{{ $item['copy'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <section class="aptoria-landing-panel aptoria-demo-section aptoria-demo-span-7">
                            <div class="aptoria-demo-section-head">
                                <div>
                                    <small class="aptoria-landing-eyebrow">Sandbox API</small>
                                    <h2>Sandbox API endpoints</h2>
                                    <p>Safe endpoints for evidence, scan and API behavior review.</p>
                                </div>
                            </div>
                            <div class="aptoria-demo-endpoint-list">
                                @foreach ($endpoints as $endpoint)
                                    <div class="aptoria-demo-endpoint">
                                        <span class="badge badge-soft-secondary badge-label">{{ $endpoint['method'] }}</span>
                                        <div>
                                            <code>{{ $endpoint['path'] }}</code>
                                            <p>{{ $endpoint['purpose'] }}</p>
                                        </div>
                                        <a href="{{ $baseUrl.$endpoint['path'] }}" target="_blank" class="btn btn-sm btn-light text-dark"><i data-lucide="external-link" class="me-1"></i>Open</a>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <section class="aptoria-landing-panel aptoria-demo-section aptoria-demo-span-5">
                            <div class="aptoria-demo-section-head">
                                <div>
                                    <small class="aptoria-landing-eyebrow">Import sources</small>
                                    <h2>Import artifacts</h2>
                                    <p>Open sample artifacts that demonstrate the adapter layer.</p>
                                </div>
                            </div>
                            <div class="aptoria-demo-artifact-list">
                                @foreach ($artifacts as $artifact)
                                    <a href="{{ $artifact['url'] }}" target="_blank" class="aptoria-demo-artifact">
                                        <span><i data-lucide="{{ $artifact['icon'] }}" class="me-2"></i>{{ $artifact['label'] }}</span>
                                        <i data-lucide="download"></i>
                                    </a>
                                @endforeach
                            </div>
                            <div class="aptoria-demo-alert mt-3">
                                <strong>Sandbox guardrails</strong>
                                <div class="small mt-1">Public demo scans are restricted to configured Aptoria demo hosts.</div>
                                <div class="mt-2">
                                    @forelse ($allowedTargets as $target)
                                        <span class="badge badge-soft-primary badge-label me-1"><i data-lucide="globe" class="me-1"></i>{{ $target }}</span>
                                    @empty
                                        <span class="badge badge-soft-warning badge-label"><i data-lucide="triangle-alert" class="me-1"></i>No allowed targets configured.</span>
                                    @endforelse
                                </div>
                            </div>
                        </section>
                    </div>
                </main>

                @include('partials.public-footer')
            </div>
        </section>
    </div>
@else
    <div class="col-12">
        @if ($project)
            <div class="alert alert-info d-flex gap-2 align-items-start">
                <i data-lucide="map" class="mt-1"></i>
                <div>
                    <strong>Aptoria Demo Guide</strong>
                    <div class="small mt-1">Use this guide to inspect the selected project workflow.</div>
                </div>
            </div>
        @endif
        <div class="row g-3">
            @foreach ($workflow as $step)
                <div class="col-lg-6">
                    <div class="card aptoria-panel-card h-100">
                        <div class="card-body">
                            <h5><i data-lucide="{{ $step['icon'] }}" class="me-1"></i>{{ $step['title'] }}</h5>
                            <p class="text-muted small mb-2">{{ $step['copy'] }}</p>
                            @if ($step['route'])
                                <a href="{{ $step['route'] }}" class="btn btn-sm btn-primary">{{ __('messages.common.open') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
