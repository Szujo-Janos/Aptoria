@extends('layouts.auth')

@section('body_class', 'aptoria-landing-body min-vh-100')

@section('title', 'Privacy Notice – Aptoria')
@section('meta_description', 'Aptoria privacy notice covering the public website, demo environment, license authority, support channels, cookies and self-hosted deployments.')
@section('robots', 'index,follow')
@section('canonical_url', 'https://aptoria.dev/privacy')
@section('og_title', 'Privacy Notice – Aptoria')
@section('og_description', 'How Aptoria handles website, demo, license authority, support and cookie-related data.')
@section('og_url', 'https://aptoria.dev/privacy')

@section('content')
<div class="aptoria-landing-scene">
    <div class="aptoria-landing-backdrop"></div>
    <div class="aptoria-landing-grid"></div>
    <div class="aptoria-landing-shell py-4 py-xl-5">
        <div class="aptoria-landing-frame">
            <header class="aptoria-landing-nav mb-4">
                <a href="{{ url('/') }}" class="aptoria-landing-nav-brand" aria-label="Aptoria">
                    <img src="{{ asset('assets/aptoria-ui/assets/images/logo-color.svg') }}" alt="Aptoria" class="aptoria-brand-logo">
                </a>
                <nav class="aptoria-landing-nav-links" aria-label="Aptoria legal navigation">
                    <a href="{{ url('/') }}">Home</a>
                    <a href="{{ url('/demo-guide') }}">Demo guide</a>
                    <a href="{{ url('/terms') }}">Terms</a>
                    <a href="#cookies">Cookies</a>
                    <a href="https://github.com/Szujo-Janos/Aptoria/issues" target="_blank" rel="noopener">Contact</a>
                </nav>
            </header>

            <main class="aptoria-landing-panel aptoria-legal-page">
                <small class="aptoria-landing-eyebrow">Legal</small>
                <h1>Privacy Notice</h1>
                <p class="aptoria-legal-lead">
                    This notice explains how Aptoria handles personal data in connection with the public website,
                    public demo, license authority, support channels and downloadable self-hosted software.
                </p>
                <p class="aptoria-legal-muted">Last updated: 2026-06-28</p>

                <section id="controller">
                    <h2>1. Who is responsible for this notice</h2>
                    <p>
                        Aptoria is currently operated as a product project by its maintainer. For privacy-related
                        questions, corrections or access requests, use the public contact route on GitHub Issues or
                        the contact information published in the Aptoria repository.
                    </p>
                    <p>
                        When you install and operate Aptoria in your own environment, you or your organization are
                        responsible for the data you store in that local installation.
                    </p>
                </section>

                <section id="scope">
                    <h2>2. Scope</h2>
                    <p>This notice covers:</p>
                    <ul>
                        <li>the public website at <code>aptoria.dev</code>;</li>
                        <li>the public demo environment at <code>demo.aptoria.dev</code>;</li>
                        <li>the license authority endpoints at <code>license.aptoria.dev</code>;</li>
                        <li>public support/contact activity through GitHub or linked resources;</li>
                        <li>cookie and local-storage preferences used by the public pages.</li>
                    </ul>
                    <p>
                        It does not automatically cover third-party websites, GitHub pages or self-hosted installations
                        operated by another person or organization.
                    </p>
                </section>

                <section id="data-handling">
                    <h2>3. What data may be processed</h2>
                    <p><strong>Public website.</strong> The landing page is designed to present Aptoria, documentation,
                        demo access, download links and support routes. Normal server logs may include IP address,
                        request URL, date/time, user agent, referrer, security events and error logs.</p>
                    <p><strong>Cookie preferences.</strong> Aptoria stores your cookie choice so the site can remember
                        whether you accepted all cookies or chose essential-only settings.</p>
                    <p><strong>Demo environment.</strong> The demo may process login/session data, demo account activity,
                        sandbox requests, rate-limit/security logs and sample workflow data. Do not submit production
                        credentials, secrets, private customer data, confidential API payloads or sensitive personal data
                        into the public demo.</p>
                    <p><strong>License authority.</strong> License checks may process license identifiers, package/runtime
                        status, installation or environment fingerprints where enabled, timestamps, request metadata and
                        security logs needed to validate an Aptoria runtime.</p>
                    <p><strong>Support/contact.</strong> If you contact the project through GitHub or another public support
                        channel, the information you submit there may be processed according to the rules of that platform
                        and the public nature of the support channel.</p>
                </section>

                <section id="self-hosted">
                    <h2>4. Self-hosted installations</h2>
                    <p>
                        Aptoria is designed as a self-hosted QA workspace. A self-hosted installation may contain API
                        endpoints, imported artifacts, findings, evidence, report files, audit logs and user accounts.
                        That data stays under the control of the person or organization operating that installation,
                        unless they separately connect it to external services or license authority endpoints.
                    </p>
                </section>

                <section id="purposes">
                    <h2>5. Why data is processed</h2>
                    <ul>
                        <li>to provide the public website, demo and documentation links;</li>
                        <li>to protect the service against abuse, unauthorized access and technical attacks;</li>
                        <li>to operate demo sessions, sandbox API examples and rate limits;</li>
                        <li>to validate licenses and prevent unauthorized packaged runtime usage;</li>
                        <li>to answer support, correction or privacy requests;</li>
                        <li>to remember cookie preferences.</li>
                    </ul>
                </section>

                <section id="legal-bases">
                    <h2>6. Legal bases</h2>
                    <p>
                        Depending on the context, processing may rely on legitimate interests such as security,
                        service operation, fraud prevention and product support; contractual or pre-contractual steps
                        for licensing/support; legal obligations where applicable; and consent for optional cookies or
                        optional analytics if they are enabled in the future.
                    </p>
                </section>

                <section id="cookies">
                    <h2>7. Cookies and local storage</h2>
                    <p>
                        Aptoria uses essential storage for security, session handling and remembering your cookie choice.
                        Optional analytics or marketing cookies are not required for the service and should only be enabled
                        after a clear user choice. You can update your choice at any time using the cookie settings link in
                        the footer.
                    </p>
                    <div class="aptoria-legal-cookie-table">
                        <div><strong>Essential</strong><span>Security, session handling and remembering your consent choice. Always active.</span></div>
                        <div><strong>Analytics</strong><span>Optional product/site measurement if enabled later. Off unless accepted.</span></div>
                        <div><strong>Marketing</strong><span>Optional campaign or retargeting cookies if enabled later. Off unless accepted.</span></div>
                    </div>
                    <p><a href="#cookie-settings" class="aptoria-cookie-settings-link">Open cookie settings</a></p>
                </section>

                <section id="retention">
                    <h2>8. Retention</h2>
                    <p>
                        Data is kept only as long as reasonably needed for the purpose for which it was collected.
                        Public demo data may be reset or removed at any time. Security and access logs may be kept for
                        operational, abuse-prevention and troubleshooting purposes. Public GitHub records are retained
                        according to GitHub and repository settings.
                    </p>
                </section>

                <section id="sharing">
                    <h2>9. Service providers and external links</h2>
                    <p>
                        Aptoria may rely on hosting, DNS/CDN/security, repository hosting and support platforms to operate
                        the public website, demo, downloads, code repository and support channels. External links, including
                        GitHub links, are controlled by their own providers and policies.
                    </p>
                </section>

                <section id="rights">
                    <h2>10. Your rights</h2>
                    <p>
                        Depending on your location and the applicable law, you may have rights to request access,
                        correction, deletion, restriction, objection, portability and withdrawal of consent. You may also
                        have the right to complain to a competent data protection authority.
                    </p>
                </section>

                <section id="security">
                    <h2>11. Security</h2>
                    <p>
                        Aptoria applies practical security measures such as domain separation, HTTPS, security headers,
                        demo guardrails, license checks and access controls. No system is perfectly secure, so the public
                        demo must not be used for secrets, production credentials or confidential data.
                    </p>
                </section>

                <section id="changes">
                    <h2>12. Changes</h2>
                    <p>
                        This notice may be updated as Aptoria changes. The latest version is published on this page.
                    </p>
                </section>
            </main>

            @include('partials.public-footer')
        </div>
    </div>
</div>
@endsection
