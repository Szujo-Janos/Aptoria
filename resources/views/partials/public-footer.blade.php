@php
    $publicLandingUrl = rtrim((string) config('aptoria.domain.landing_url', 'https://aptoria.dev'), '/') ?: 'https://aptoria.dev';
    $publicDemoUrl = rtrim((string) config('aptoria.domain.demo_url', 'https://demo.aptoria.dev'), '/') ?: 'https://demo.aptoria.dev';
    $publicLicenseUrl = rtrim((string) config('aptoria.domain.license_url', 'https://license.aptoria.dev'), '/') ?: 'https://license.aptoria.dev';
    $publicGithubUrl = rtrim((string) config('aptoria.links.github_url', 'https://github.com/Szujo-Janos/Aptoria'), '/');
    $publicDownloadUrl = rtrim((string) config('aptoria.links.download_url', $publicGithubUrl.'/releases'), '/');
    $publicDocsUrl = rtrim((string) config('aptoria.links.docs_url', $publicGithubUrl.'/tree/main/docs'), '/');
    $publicSupportUrl = rtrim((string) config('aptoria.links.support_url', $publicGithubUrl.'/issues'), '/');
    $publicSecurityUrl = $publicGithubUrl.'/blob/main/SECURITY.md';
    $publicLicenseTextUrl = $publicGithubUrl.'/blob/main/LICENSE';
    $publicChangelogUrl = $publicGithubUrl.'/blob/main/CHANGELOG.md';
    $publicDemoGuideUrl = $publicLandingUrl.'/demo-guide';

    $publicFooterColumns = $publicFooterColumns ?? [
        [
            'title' => 'Product',
            'links' => [
                ['label' => 'Overview', 'href' => $publicLandingUrl.'/#platform'],
                ['label' => 'Demo guide', 'href' => $publicDemoGuideUrl],
                ['label' => 'Live demo', 'href' => $publicDemoUrl, 'external' => true],
                ['label' => 'Download', 'href' => $publicDownloadUrl, 'external' => true],
                ['label' => 'GitHub', 'href' => $publicGithubUrl, 'external' => true],
            ],
        ],
        [
            'title' => 'Use cases',
            'links' => [
                ['label' => 'API QA review', 'href' => $publicLandingUrl.'/#platform'],
                ['label' => 'Endpoint evidence', 'href' => $publicLandingUrl.'/#integrations'],
                ['label' => 'Release readiness', 'href' => $publicLandingUrl.'/#trust'],
                ['label' => 'Import validation', 'href' => $publicLandingUrl.'/#integrations'],
                ['label' => 'QA reporting', 'href' => $publicLandingUrl.'/#platform'],
            ],
        ],
        [
            'title' => 'Resources',
            'links' => [
                ['label' => 'How it works', 'href' => $publicDemoGuideUrl],
                ['label' => 'Documentation', 'href' => $publicDocsUrl, 'external' => true],
                ['label' => 'Changelog', 'href' => $publicChangelogUrl, 'external' => true],
                ['label' => 'Troubleshooting', 'href' => $publicSupportUrl, 'external' => true],
                ['label' => 'Contact', 'href' => $publicSupportUrl, 'external' => true],
            ],
        ],
        [
            'title' => 'Trust',
            'links' => [
                ['label' => 'Security', 'href' => $publicSecurityUrl, 'external' => true],
                ['label' => 'License authority', 'href' => $publicLicenseUrl, 'external' => true],
                ['label' => 'Demo environment', 'href' => $publicDemoUrl, 'external' => true],
                ['label' => 'Data handling', 'href' => $publicLandingUrl.'/privacy#data-handling'],
                ['label' => 'Status', 'href' => $publicLicenseUrl.'/license/status.json', 'external' => true],
            ],
        ],
        [
            'title' => 'Legal',
            'links' => [
                ['label' => 'Privacy', 'href' => $publicLandingUrl.'/privacy'],
                ['label' => 'Terms', 'href' => $publicLandingUrl.'/terms'],
                ['label' => 'Cookie settings', 'href' => '#cookie-settings', 'cookie' => true],
                ['label' => 'License', 'href' => $publicLicenseTextUrl, 'external' => true],
            ],
        ],
    ];
@endphp

<footer class="aptoria-landing-footer mt-4 mt-xl-5" aria-label="Aptoria footer">
    <div class="aptoria-landing-footer-main">
        <div class="aptoria-landing-footer-brand">
            <a href="{{ $publicLandingUrl }}" class="aptoria-landing-footer-logo" aria-label="Aptoria home">
                <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria">
            </a>
            <p>
                Evidence-first API QA workspace for endpoint review, imported artifacts,
                evidence packs, release readiness and audit-ready QA decisions.
            </p>
        </div>

        <div class="aptoria-landing-footer-nav">
            @foreach ($publicFooterColumns as $column)
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
