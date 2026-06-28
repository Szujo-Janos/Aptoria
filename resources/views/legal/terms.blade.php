@extends('layouts.auth')

@section('body_class', 'aptoria-landing-body min-vh-100')

@section('title', 'Terms of Use – Aptoria')
@section('meta_description', 'Aptoria terms of use for the public website, demo environment, downloads, licenses, acceptable use and support channels.')
@section('robots', 'index,follow')
@section('canonical_url', 'https://aptoria.dev/terms')
@section('og_title', 'Terms of Use – Aptoria')
@section('og_description', 'Terms for using the Aptoria website, public demo, downloadable software and related resources.')
@section('og_url', 'https://aptoria.dev/terms')

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
                    <a href="{{ url('/privacy') }}">Privacy</a>
                    <a href="https://github.com/Szujo-Janos/Aptoria/issues" target="_blank" rel="noopener">Contact</a>
                </nav>
            </header>

            <main class="aptoria-landing-panel aptoria-legal-page">
                <small class="aptoria-landing-eyebrow">Legal</small>
                <h1>Terms of Use</h1>
                <p class="aptoria-legal-lead">
                    These terms describe the rules for using the Aptoria public website, demo environment,
                    downloadable software, license-related services and linked support resources.
                </p>
                <p class="aptoria-legal-muted">Last updated: 2026-06-28</p>

                <section id="overview">
                    <h2>1. What Aptoria is</h2>
                    <p>
                        Aptoria is an evidence-first API QA workspace for endpoint review, imported artifacts,
                        finding triage, evidence packs, release-readiness checks, audit logs and QA decision support.
                    </p>
                    <p>
                        Aptoria is not a public attack scanner, exploit platform, vulnerability marketplace or guarantee
                        that a system is secure, compliant or release-ready.
                    </p>
                </section>

                <section id="acceptance">
                    <h2>2. Acceptance of these terms</h2>
                    <p>
                        By using the public website, demo environment, downloads or related resources, you agree to use
                        them responsibly and in line with these terms. If you use Aptoria on behalf of an organization,
                        you confirm that you are authorized to do so.
                    </p>
                </section>

                <section id="acceptable-use">
                    <h2>3. Acceptable use</h2>
                    <ul>
                        <li>Use Aptoria only for systems, APIs, data and environments you own or are authorized to review.</li>
                        <li>Do not use Aptoria to attack, overload, bypass, scrape, exfiltrate or interfere with third-party systems.</li>
                        <li>Do not upload secrets, production credentials, private customer data or confidential payloads into the public demo.</li>
                        <li>Do not attempt to bypass demo guardrails, license checks, rate limits or access controls.</li>
                        <li>Do not misrepresent Aptoria reports as independent certification or legal/compliance approval.</li>
                    </ul>
                </section>

                <section id="demo">
                    <h2>4. Public demo</h2>
                    <p>
                        The public demo is provided for evaluation and product understanding. It may use sample data,
                        limited permissions, reset schedules, rate limits, disabled administrative features and restricted
                        sandbox targets. Demo availability, content and credentials may change without notice.
                    </p>
                </section>

                <section id="downloads">
                    <h2>5. Downloads and self-hosted operation</h2>
                    <p>
                        Downloaded Aptoria instances are operated by the installing party. You are responsible for your
                        own installation, configuration, credentials, users, backups, storage, access controls, evidence
                        artifacts, imported files and legal/compliance use of the software.
                    </p>
                    <p>
                        Self-hosted usage may be subject to the open-source license published in the repository and, where
                        applicable, runtime/package license controls for specific packaged distributions.
                    </p>
                </section>

                <section id="license">
                    <h2>6. Licenses and license authority</h2>
                    <p>
                        Some Aptoria packages may validate a license, runtime lease, installation fingerprint or similar
                        authorization through a license authority. You must not tamper with, bypass or disable license
                        enforcement unless the applicable license expressly allows it.
                    </p>
                </section>

                <section id="content">
                    <h2>7. Product information and reports</h2>
                    <p>
                        Aptoria helps organize QA evidence and review decisions. Reports, evidence packs, readiness scores,
                        findings and summaries are decision-support materials. They are not legal advice, security
                        certification, compliance certification or a substitute for professional review.
                    </p>
                </section>

                <section id="third-party">
                    <h2>8. Third-party services and links</h2>
                    <p>
                        Aptoria may link to GitHub, documentation, support pages, downloads and third-party product names
                        such as API tools or issue trackers. Those services are controlled by their own providers and terms.
                        Third-party brand names remain the property of their respective owners.
                    </p>
                </section>

                <section id="availability">
                    <h2>9. Availability and changes</h2>
                    <p>
                        The public website, demo, license authority, documentation, downloads and support routes may be
                        changed, limited, suspended or removed. Unless a separate written agreement says otherwise, Aptoria
                        is provided without a public uptime commitment or service-level agreement.
                    </p>
                </section>

                <section id="warranty">
                    <h2>10. No warranty</h2>
                    <p>
                        To the maximum extent permitted by applicable law, Aptoria and its public resources are provided
                        “as is” and “as available”, without warranties that they will be uninterrupted, error-free,
                        secure, compatible with your environment or fit for a particular purpose.
                    </p>
                </section>

                <section id="liability">
                    <h2>11. Limitation of liability</h2>
                    <p>
                        To the maximum extent permitted by applicable law, Aptoria is not liable for indirect, incidental,
                        special, consequential or punitive damages, loss of profits, loss of data, business interruption,
                        security incidents in your environment or decisions made solely on automated output without human review.
                    </p>
                </section>

                <section id="termination">
                    <h2>12. Suspension or termination</h2>
                    <p>
                        Access to the public demo, support channels or license-related services may be limited or blocked
                        if there is abuse, attempted bypass, security risk, unauthorized testing or other harmful activity.
                    </p>
                </section>

                <section id="changes">
                    <h2>13. Changes to these terms</h2>
                    <p>
                        These terms may be updated as Aptoria changes. The latest version is published on this page.
                    </p>
                </section>
            </main>

            @include('partials.public-footer')
        </div>
    </div>
</div>
@endsection
