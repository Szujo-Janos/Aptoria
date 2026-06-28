@extends('layouts.auth')

@section('title', 'Aptoria – API QA Evidence & Release Readiness Workspace')
@section('meta_description', 'Aptoria is a self-hosted QA workspace for API evidence, Postman and Newman imports, Jira QA context, OpenAPI review, finding triage, release gates and audit-ready reports.')
@section('robots', 'index,follow,max-image-preview:large')
@section('canonical_url', 'https://aptoria.dev/')
@section('og_title', 'Aptoria – API QA Evidence & Release Readiness Workspace')
@section('og_description', 'Turn API testing into release evidence with a self-hosted QA workspace for imports, findings, release gates and audit-ready reports.')
@section('og_url', 'https://aptoria.dev/')
@section('og_image', 'https://aptoria.dev/assets/aptoria-ui/assets/images/og-aptoria.png')
@section('twitter_title', 'Aptoria – API QA Evidence & Release Readiness Workspace')
@section('twitter_description', 'Self-hosted QA evidence, API finding triage, Postman/Newman imports, release gates and audit-ready reports.')
@section('twitter_image', 'https://aptoria.dev/assets/aptoria-ui/assets/images/og-aptoria.png')
@section('body_class', 'aptoria-landing-body min-vh-100')

@php
    $terminalCommands = [
        'aptoria safe-scan run --target sandbox-api --profile smoke',
        'aptoria import preview --source postman --file collection.json',
        'aptoria newman:ingest --run results.json --link-evidence',
        'aptoria jira:map --file issues.csv --triage-ready',
        'aptoria release-gate evaluate --project payments-api --evidence latest',
    ];

    $terminalOutputs = [
        '✓ 38 endpoints reviewed · 6 findings linked · evidence pack ready',
        '✓ Postman collection parsed · 14 requests mapped · 2 conflicts flagged',
        '✓ Newman run imported · assertions linked to endpoints and findings',
        '✓ Jira issues normalized · owners, severity and evidence references attached',
        '✓ Release gate evaluated · 1 blocker · 3 warnings · decision package generated',
    ];

    $domainRole = strtolower((string) config('aptoria.domain.role', 'local'));
    $isLandingOnly = $domainRole === 'landing';
    $showLocalRuntimeActions = ! in_array($domainRole, ['landing', 'demo'], true);

    $landingUrl = rtrim((string) config('aptoria.domain.landing_url'), '/');
    $demoUrl = rtrim((string) config('aptoria.domain.demo_url'), '/');
    $docsUrl = 'https://github.com/Szujo-Janos/Aptoria/tree/main/docs';
    $githubUrl = 'https://github.com/Szujo-Janos/Aptoria';
    $downloadUrl = 'https://github.com/Szujo-Janos/Aptoria/releases';
    $supportUrl = 'https://github.com/Szujo-Janos/Aptoria/issues';
    $licenseAuthorityUrl = rtrim((string) config('aptoria.domain.license_url', 'https://license.aptoria.dev'), '/');
    $securityUrl = 'https://github.com/Szujo-Janos/Aptoria/blob/main/SECURITY.md';
    $privacyUrl = $isLandingOnly ? ($landingUrl ?: 'https://aptoria.dev').'/privacy' : url('/privacy');
    $termsUrl = $isLandingOnly ? ($landingUrl ?: 'https://aptoria.dev').'/terms' : url('/terms');
    $licenseTextUrl = 'https://github.com/Szujo-Janos/Aptoria/blob/main/LICENSE';
    $changelogUrl = 'https://github.com/Szujo-Janos/Aptoria/blob/main/CHANGELOG.md';
    $githubIconUrl = asset('assets/aptoria-ui/assets/images/integrations/github.svg');

    $demoGuideUrl = ($landingUrl ?: 'https://aptoria.dev').'/demo-guide';

    $footerColumns = [
        [
            'title' => 'Product',
            'links' => [
                ['label' => 'Overview', 'href' => '#platform'],
                ['label' => 'Demo', 'href' => $demoGuideUrl],
                ['label' => 'Download', 'href' => $downloadUrl, 'external' => true],
                ['label' => 'GitHub', 'href' => $githubUrl, 'external' => true],
                ['label' => 'Changelog', 'href' => $changelogUrl, 'external' => true],
            ],
        ],
        [
            'title' => 'Use cases',
            'links' => [
                ['label' => 'API QA review', 'href' => '#platform'],
                ['label' => 'Endpoint evidence', 'href' => '#integrations'],
                ['label' => 'Release readiness', 'href' => '#trust'],
                ['label' => 'Import validation', 'href' => '#integrations'],
                ['label' => 'QA reporting', 'href' => '#platform'],
            ],
        ],
        [
            'title' => 'Resources',
            'links' => [
                ['label' => 'How it works', 'href' => $demoGuideUrl],
                ['label' => 'Help', 'href' => $supportUrl, 'external' => true],
                ['label' => 'Documentation', 'href' => $docsUrl, 'external' => true],
                ['label' => 'Installation guide', 'href' => $docsUrl, 'external' => true],
                ['label' => 'Troubleshooting', 'href' => $supportUrl, 'external' => true],
            ],
        ],
        [
            'title' => 'Trust',
            'links' => [
                ['label' => 'Security', 'href' => $securityUrl, 'external' => true],
                ['label' => 'License authority', 'href' => $licenseAuthorityUrl, 'external' => true],
                ['label' => 'Demo environment', 'href' => $demoUrl, 'external' => true],
                ['label' => 'Data handling', 'href' => '#trust'],
                ['label' => 'Status', 'href' => $licenseAuthorityUrl.'/license/status.json', 'external' => true],
            ],
        ],
        [
            'title' => 'Legal',
            'links' => [
                ['label' => 'Privacy', 'href' => $privacyUrl],
                ['label' => 'Terms', 'href' => $termsUrl],
                ['label' => 'License', 'href' => $licenseTextUrl, 'external' => true],
                ['label' => 'Cookie settings', 'href' => '#cookie-settings', 'cookie' => true],
                ['label' => 'Contact', 'href' => $supportUrl, 'external' => true],
            ],
        ],
    ];

    $integrations = [
        [
            'name' => 'Postman',
            'label' => 'Collections',
            'class' => 'is-postman',
            'logo' => asset('assets/aptoria-ui/assets/images/integrations/postman.svg'),
        ],
        [
            'name' => 'Newman',
            'label' => 'Postman CLI results',
            'class' => 'is-newman',
            'logo' => asset('assets/aptoria-ui/assets/images/integrations/newman.svg'),
        ],
        [
            'name' => 'Jira',
            'label' => 'Issue CSV',
            'class' => 'is-jira',
            'logo' => asset('assets/aptoria-ui/assets/images/integrations/jira.svg'),
        ],
        [
            'name' => 'OpenAPI',
            'label' => 'Contracts',
            'class' => 'is-openapi',
            'logo' => asset('assets/aptoria-ui/assets/images/integrations/openapi.svg'),
        ],
        [
            'name' => 'HAR',
            'label' => 'Browser network',
            'class' => 'is-har',
            'logo' => asset('assets/aptoria-ui/assets/images/integrations/har.svg'),
        ],
        [
            'name' => 'CSV',
            'label' => 'Manual QA imports',
            'class' => 'is-csv',
            'logo' => asset('assets/aptoria-ui/assets/images/integrations/csv.svg'),
        ],
    ];

    $capabilities = [
        ['icon' => 'radar', 'title' => 'Safe API scanning', 'copy' => 'Run controlled GET/HEAD reviews with timeouts, private-network protection and target guardrails.'],
        ['icon' => 'folder-check', 'title' => 'Evidence repository', 'copy' => 'Keep response previews, headers, imports, manual notes and audit-ready references in one place.'],
        ['icon' => 'bug', 'title' => 'Finding triage', 'copy' => 'Track severity, owner, due date, duplicate candidates, merge workflow, dismissal and accepted risk.'],
        ['icon' => 'brackets-contain', 'title' => 'Import adapter layer', 'copy' => 'Preview and normalize Postman, Newman, Jira CSV, OpenAPI, HAR and QA spreadsheet artifacts.'],
        ['icon' => 'shield-check', 'title' => 'Release readiness', 'copy' => 'Evaluate rules, profiles, blockers, warnings and release decision packages before handoff.'],
        ['icon' => 'history', 'title' => 'Audit trail', 'copy' => 'Preserve who did what, what changed, which evidence was used and why a decision was made.'],
    ];

    $trustCards = [
        [
            'icon' => 'check-circle-2',
            'title' => 'What Aptoria is',
            'copy' => 'A downloadable QA review workspace that connects API checks, imported artifacts, findings, evidence packs and release decisions.',
        ],
        [
            'icon' => 'ban',
            'title' => 'What it is not',
            'copy' => 'It is not a public attack scanner, not an exploit tool and not a replacement for responsible manual QA review.',
        ],
        [
            'icon' => 'clipboard-check',
            'title' => 'Why evidence-first matters',
            'copy' => 'A release decision is easier to defend when every finding, warning and accepted risk points back to reviewable evidence.',
        ],
    ];


    $organizationSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Aptoria',
        'url' => 'https://aptoria.dev/',
        'logo' => 'https://aptoria.dev/assets/aptoria-ui/assets/images/logo-color.svg',
        'sameAs' => ['https://github.com/Szujo-Janos/Aptoria'],
        'founder' => [
            '@type' => 'Person',
            'name' => 'János Szujó',
            'url' => 'https://github.com/Szujo-Janos/Aptoria',
        ],
    ];

    $softwareSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Aptoria',
        'applicationCategory' => 'DeveloperApplication',
        'operatingSystem' => 'Windows, Linux, macOS',
        'url' => 'https://aptoria.dev/',
        'downloadUrl' => 'https://github.com/Szujo-Janos/Aptoria/releases',
        'softwareVersion' => config('aptoria.version'),
        'description' => 'A self-hosted QA workspace for API evidence, Postman and Newman imports, Jira QA context, OpenAPI review, finding triage, release gates and audit-ready reports.',
        'creator' => [
            '@type' => 'Person',
            'name' => 'János Szujó',
            'url' => 'https://github.com/Szujo-Janos/Aptoria',
        ],
        'sameAs' => ['https://github.com/Szujo-Janos/Aptoria'],
    ];
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/ld+json">{!! json_encode($softwareSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<div class="aptoria-landing-scene">
    <div class="aptoria-landing-backdrop"></div>
    <div class="aptoria-landing-grid"></div>

    <section class="aptoria-landing-shell py-4 py-xl-5">
        <div class="aptoria-landing-frame">
            <header class="aptoria-landing-nav mb-4 mb-xl-5">
                <a href="{{ $landingUrl ?: url('/') }}" class="aptoria-landing-nav-brand" aria-label="Aptoria">
                    <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-brand-logo">
                </a>
                <nav class="aptoria-landing-nav-links" aria-label="Aptoria navigation">
                    <a href="#platform">Platform</a>
                    <a href="#trust">Trust layer</a>
                    <a href="#integrations">Integrations</a>
                    <a href="{{ $demoGuideUrl }}">Demo</a>
                    <a href="{{ $docsUrl }}" target="_blank" rel="noopener">Docs</a>
                    <a href="{{ $githubUrl }}" target="_blank" rel="noopener">GitHub</a>
                </nav>
            </header>

            <div class="row g-4 g-xxl-5 align-items-stretch" id="platform">
                <div class="col-xl-6 d-flex">
                    <div class="aptoria-landing-copy w-100">
                        <div class="d-inline-flex align-items-center gap-2 aptoria-landing-badge mb-4">
                            <span class="aptoria-landing-badge-dot"></span>
                            <span>API QA evidence · release readiness · audit trail</span>
                        </div>

                        <h1 class="aptoria-landing-title">Turn API testing into release evidence.</h1>
                        <p class="aptoria-landing-lead">
                            Aptoria is a QA-focused workspace for API review, evidence collection, finding triage,
                            external test imports and release decision handoff. It helps teams move from scattered
                            test artifacts to a structured, reviewable readiness picture.
                        </p>

                        <div class="aptoria-landing-chip-row mb-4">
                            <span class="aptoria-landing-chip">Safe scans</span>
                            <span class="aptoria-landing-chip">Evidence packs</span>
                            <span class="aptoria-landing-chip">Finding triage</span>
                            <span class="aptoria-landing-chip">Release gates</span>
                            <span class="aptoria-landing-chip">Import previews</span>
                        </div>

                        <div class="aptoria-landing-value-list mb-4">
                            <div class="aptoria-landing-value-item">
                                <i data-lucide="scan-search"></i>
                                <div>
                                    <strong>Review APIs without turning the tool into a weapon.</strong>
                                    <span>Sandbox target restrictions, private-network blocking and safe request methods keep public demos and customer reviews controlled.</span>
                                </div>
                            </div>
                            <div class="aptoria-landing-value-item">
                                <i data-lucide="folder-check"></i>
                                <div>
                                    <strong>Keep the proof behind every QA decision.</strong>
                                    <span>Evidence, findings, imports, assertions, release rules and reports stay connected instead of disappearing into screenshots and chat threads.</span>
                                </div>
                            </div>
                            <div class="aptoria-landing-value-item">
                                <i data-lucide="workflow"></i>
                                <div>
                                    <strong>Bridge QA, developers and release owners.</strong>
                                    <span>Use one project workspace to see what failed, what changed, what is blocked and what can be released with confidence.</span>
                                </div>
                            </div>
                        </div>

                        <div class="aptoria-landing-cta-row mb-4">
                            <a href="{{ $demoGuideUrl }}" class="btn btn-primary btn-lg">
                                <i data-lucide="play-circle" class="me-1"></i>Explore the demo
                            </a>
                            <a href="{{ $downloadUrl }}" class="btn btn-outline-light btn-lg" target="_blank" rel="noopener">
                                <i data-lucide="download" class="me-1"></i>Download
                            </a>
                            <a href="{{ $githubUrl }}" class="btn btn-outline-info btn-lg" target="_blank" rel="noopener">
                                <img src="{{ $githubIconUrl }}" alt="" class="aptoria-btn-brand-icon me-1">GitHub
                            </a>
                            <a href="{{ $docsUrl }}" class="btn btn-light btn-lg text-dark" target="_blank" rel="noopener">
                                <i data-lucide="book-open" class="me-1"></i>Docs
                            </a>
                        </div>

                        @if ($showLocalRuntimeActions)
                            <div class="aptoria-landing-local-actions mb-4">
                                <span>Local runtime</span>
                                <a href="{{ route('login') }}">Login</a>
                                <a href="{{ route('setup.index') }}">Setup</a>
                            </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="aptoria-landing-stat-card">
                                    <small>Review scope</small>
                                    <strong>API + QA</strong>
                                    <span>Endpoints, auth, environments, findings, reports and evidence in one workspace.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="aptoria-landing-stat-card">
                                    <small>Import layer</small>
                                    <strong>6 sources</strong>
                                    <span>Postman, Newman, OpenAPI, Jira CSV, HAR and manual QA artifacts.</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="aptoria-landing-stat-card">
                                    <small>Output</small>
                                    <strong>Decision pack</strong>
                                    <span>Evidence packs, release gate reports and auditable handoff material.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 d-flex">
                    <div class="aptoria-landing-console-wrap w-100">
                        <div class="aptoria-landing-panel aptoria-landing-terminal-panel mb-4">
                            <div class="aptoria-console-toolbar">
                                <span class="aptoria-console-dot is-danger"></span>
                                <span class="aptoria-console-dot is-warning"></span>
                                <span class="aptoria-console-dot is-success"></span>
                                <span class="aptoria-console-toolbar-label">APTORIA // QA COMMAND STREAM</span>
                            </div>
                            <div class="aptoria-console-screen">
                                <div class="aptoria-console-section-label">Live review flow</div>
                                <div class="aptoria-console-command-line">
                                    <span class="aptoria-console-prompt">$</span>
                                    <span id="aptoriaTypewriterText"></span>
                                    <span class="aptoria-console-caret"></span>
                                </div>
                                <div class="aptoria-console-output" id="aptoriaTypewriterOutput">{{ $terminalOutputs[0] }}</div>

                                <div class="aptoria-console-metrics row g-3 mt-1">
                                    <div class="col-sm-6">
                                        <div class="aptoria-console-mini-card">
                                            <span>Evidence model</span>
                                            <strong>Traceable</strong>
                                            <small>Every finding can point back to a scan, import, assertion or manual QA artifact.</small>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="aptoria-console-mini-card">
                                            <span>Release guard</span>
                                            <strong>Rule based</strong>
                                            <small>Blockers, warnings and accepted risks are visible before the release decision.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="aptoria-landing-panel aptoria-landing-integrations-card mb-4" id="integrations">
                            <small class="aptoria-landing-eyebrow">Works with common QA artifacts</small>
                            <h2 class="h4 mb-2">Bring existing API and QA work into one reviewable system.</h2>
                            <p class="mb-3 text-muted">
                                Aptoria is not trying to replace every tool. It collects their output, normalizes it,
                                links it to evidence and turns it into release-readiness context. Import Postman collections,
                                Newman CLI results, Jira issue exports, OpenAPI contracts, browser HAR files and CSV-based QA evidence into one reviewable workspace.
                            </p>
                            <div class="aptoria-integration-rail">
                                @foreach ($integrations as $integration)
                                    <div class="aptoria-integration-logo {{ $integration['class'] }}">
                                        <span class="aptoria-integration-brand-row">
                                            <img src="{{ $integration['logo'] }}" alt="{{ $integration['name'] }} logo" class="aptoria-integration-img">
                                            <strong>{{ $integration['name'] }}</strong>
                                        </span>
                                        <small>{{ $integration['label'] }}</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="aptoria-landing-panel aptoria-landing-note-card h-100">
                                    <small class="aptoria-landing-eyebrow">For QA teams</small>
                                    <h2 class="h4 mb-2">Less chasing. More evidence.</h2>
                                    <p class="mb-0 text-muted">Capture what was tested, what failed, why it matters and what evidence supports the decision.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="aptoria-landing-panel aptoria-landing-note-card h-100">
                                    <small class="aptoria-landing-eyebrow">For developers</small>
                                    <h2 class="h4 mb-2">Clearer handoff.</h2>
                                    <p class="mb-0 text-muted">See endpoint context, reproduced behavior, import source, severity, expected result and linked artifacts.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <section class="aptoria-landing-panel aptoria-landing-public-section mt-4 mt-xl-5" id="trust">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-4">
                        <small class="aptoria-landing-eyebrow">Public trust layer</small>
                        <h2 class="h3 mb-2">Clear scope, no fake magic.</h2>
                        <p class="mb-0 text-muted">
                            Aptoria is a review and decision workspace. It helps organize evidence instead of pretending that automation alone is proof.
                        </p>
                    </div>
                    <div class="col-lg-8">
                        <div class="aptoria-capability-grid">
                            @foreach ($trustCards as $card)
                                <div class="aptoria-capability-card">
                                    <i data-lucide="{{ $card['icon'] }}"></i>
                                    <strong>{{ $card['title'] }}</strong>
                                    <span>{{ $card['copy'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>


            <section class="aptoria-landing-panel aptoria-landing-public-section mt-4">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-4">
                        <small class="aptoria-landing-eyebrow">Platform coverage</small>
                        <h2 class="h3 mb-2">From endpoint inventory to release decision.</h2>
                        <p class="mb-0 text-muted">
                            A single Aptoria project can show the complete QA chain: environments, auth profiles,
                            API inventory, safe scans, imported artifacts, findings, native tests, reports and audit history.
                        </p>
                    </div>
                    <div class="col-lg-8">
                        <div class="aptoria-capability-grid">
                            @foreach ($capabilities as $capability)
                                <div class="aptoria-capability-card">
                                    <i data-lucide="{{ $capability['icon'] }}"></i>
                                    <strong>{{ $capability['title'] }}</strong>
                                    <span>{{ $capability['copy'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="aptoria-landing-panel aptoria-landing-public-section mt-4">
                <div class="row g-4 align-items-start">
                    <div class="col-lg-4">
                        <small class="aptoria-landing-eyebrow">Downloadable QA workspace</small>
                        <h2 class="h3 mb-2">Run Aptoria where your QA evidence belongs.</h2>
                        <p class="mb-0 text-muted">
                            Aptoria is designed as a downloadable, self-hosted QA review system. Use it in your own
                            environment, keep review artifacts under your control and turn API testing activity into
                            evidence-backed release decisions.
                        </p>
                    </div>
                    <div class="col-lg-8">
                        <div class="aptoria-landing-route-grid">
                            <div class="aptoria-landing-route-card">
                                <i data-lucide="package-check"></i>
                                <strong>Download and install</strong>
                                <span>Deploy the Aptoria application into your own QA, staging or internal review environment.</span>
                            </div>
                            <div class="aptoria-landing-route-card">
                                <i data-lucide="database-zap"></i>
                                <strong>Keep evidence local</strong>
                                <span>Store endpoint evidence, imports, findings, audit history and reports inside your own project workspace.</span>
                            </div>
                            <div class="aptoria-landing-route-card">
                                <i data-lucide="key-round"></i>
                                <strong>License controlled usage</strong>
                                <span>Activate the downloaded system with a license instead of exposing public admin surfaces to end users.</span>
                            </div>
                            <div class="aptoria-landing-route-card">
                                <i data-lucide="shield-check"></i>
                                <strong>Demo before commitment</strong>
                                <span>Use the online showcase only to understand the product before downloading and running your own instance.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <footer class="aptoria-landing-footer mt-4 mt-xl-5" aria-label="Aptoria footer">
                <div class="aptoria-landing-footer-main">
                    <div class="aptoria-landing-footer-brand">
                        <a href="{{ $landingUrl ?: url('/') }}" class="aptoria-landing-footer-logo" aria-label="Aptoria home">
                            <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria">
                        </a>
                        <p>
                            Evidence-first API QA workspace for endpoint review, imported artifacts,
                            evidence packs, release readiness and audit-ready QA decisions.
                        </p>
                    </div>

                    <div class="aptoria-landing-footer-nav">
                        @foreach ($footerColumns as $column)
                            <div class="aptoria-landing-footer-column">
                                <strong>{{ $column['title'] }}</strong>
                                @foreach ($column['links'] as $link)
                                    <a href="{{ $link['href'] }}" @if($link['cookie'] ?? false) class="aptoria-cookie-settings-link" @endif @if($link['external'] ?? false) target="_blank" rel="noopener" @endif>{{ $link['label'] }}</a>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="aptoria-landing-footer-bottom">
                    <span>© 2026 Aptoria. All rights reserved.</span>
                    <span>Evidence-first API QA · v{{ config('aptoria.version') }}</span>
                </div>
            </footer>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        const commands = @json($terminalCommands);
        const outputs = @json($terminalOutputs);
        const textEl = document.getElementById('aptoriaTypewriterText');
        const outputEl = document.getElementById('aptoriaTypewriterOutput');

        if (!textEl || !outputEl || !commands.length) {
            return;
        }

        let commandIndex = 0;
        let charIndex = 0;
        let deleting = false;

        const type = () => {
            const currentCommand = commands[commandIndex] ?? '';
            const nextLength = deleting ? charIndex - 1 : charIndex + 1;
            charIndex = Math.max(0, Math.min(currentCommand.length, nextLength));
            textEl.textContent = currentCommand.slice(0, charIndex);

            if (!deleting && charIndex === currentCommand.length) {
                outputEl.textContent = outputs[commandIndex] ?? '';
                deleting = true;
                setTimeout(type, 1700);
                return;
            }

            if (deleting && charIndex === 0) {
                deleting = false;
                commandIndex = (commandIndex + 1) % commands.length;
                setTimeout(type, 300);
                return;
            }

            const delay = deleting ? 28 : 46;
            setTimeout(type, delay);
        };

        type();
    })();
</script>
@endpush
