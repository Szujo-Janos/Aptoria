# Aptoria

## Aptoria v1.0.86

Export Credit Setting Runtime Hotfix.


**Aptoria** is a self-hosted Laravel application for API QA, endpoint visibility, regression monitoring, release evidence and lightweight security review.

Current release: **v1.0.86 - Export Credit Setting Runtime Hotfix**  
Product status: **post-MVP / early beta**

The application is designed for teams or individual QA engineers who want to keep API endpoint inventories, safe scan evidence, assertions, snapshots, findings, test cases and release readiness decisions in one self-hosted workspace.

> Repository status note: this branch is prepared for public GitHub presentation as a source-available project. Public visibility does not make Aptoria open-source; see `LICENSE`, `NOTICE.md`, `CREDITS.md` and `THIRD_PARTY_NOTICES.md`.

---

## What Aptoria does

Aptoria helps you answer practical QA questions before a release:

- Which API endpoints are in the project?
- Which endpoints were scanned safely with GET/HEAD requests?
- Which endpoints changed compared with a previous snapshot?
- Which assertions are failing?
- Which endpoints have linked test cases?
- Are OpenAPI contract checks failing?
- Are there open critical or high findings?
- Is the project ready for release, warning-only, blocked or failed?
- Did scheduled monitors create state-change alerts or webhook notifications?
- Which QA tasks, retests, release checkpoints and alert follow-ups are due next?
- Can we export QA evidence and calendar milestones for sign-off?

The goal is not to replace Postman, Playwright, OWASP tools or a full test management platform. Aptoria is a self-hosted evidence and workflow layer for API QA and release review.

## v1.0.86 Export Credit Setting Runtime Hotfix

This release adds professional Aptoria attribution metadata to generated reports and downloadable exports. Markdown reports now include a consistent footer with product, version, repository, author and source-available license summary. JSON exports include structured `generated_by` metadata, calendar exports include Aptoria calendar/product metadata, and QA Evidence Pack ZIP exports include `APTORIA_CREDITS.txt`.

---

## v1.0.83 User Profile Center

This release adds the first authenticated user profile center to Aptoria. Users can update their name, e-mail, interface language and timezone, and can change their password through a separate current-password verified form. The profile page also includes account information and activity summary panels.

---

## v1.0.76 Aptoria Rebrand Polish Pass

This release completes the first public rebrand polish pass after the Aptoria rename. It aligns README, installation notes, server installer notes, QA checklist, release builder validation and rebrand regression tests with the current Aptoria package. It also removes leftover Radar UI documentation filenames so public documentation no longer exposes the old product identity.

No product feature behavior was intentionally changed in this release.

---

## v1.0.75 Logo Icon Crop Hotfix

This release fixes the standalone Aptoria icon crop and regenerates the favicon / launcher icon derivatives so the wordmark does not bleed into icon-only assets.

---

## v1.0.74 Aptoria Rebrand Pass

This release rebrands the application from its former API-focused identity to Aptoria across source code, views, settings, tests, documentation, public assets and release metadata.

---

## v1.0.72 Settings Functional Audit Hotfix

The Settings Center was audited so visible global and project settings are saveable, validated, persisted and consumed at runtime. Non-functional assertion default controls were removed, project notes now appear on the project detail page, UI/session switches have regression coverage, and clean ZIP runtime folders are preserved.

See `docs/SETTINGS_FUNCTIONAL_AUDIT.md` for the current activation notes.

---

## v1.0.68 Settings Activation Test Hotfix

The Settings Center still shows product controls only. v1.0.68 fixes the two test regressions found after v1.0.67: endpoints without explicit assertion rules remain `not_configured`, and the SweetAlert runtime asset no longer contains the forbidden literal `window.confirm(` call. Assertion default Settings stay active as rule-creation defaults instead of hidden synthetic runtime rules.

See `docs/SETTINGS_FUNCTIONAL_AUDIT.md` for the current activation notes.

---

## Current feature set

### Project and endpoint inventory

- Project management
- Environment management
- Auth profiles with bearer/basic/custom header support
- Endpoint inventory with method, path, risk and metadata
- CSV, JSON and OpenAPI import flows
- Import preview before saving endpoints
- Path parameter test values at project and endpoint level

### Safe API scanning

- Safe GET/HEAD-only scan workflow
- Single endpoint probe
- Response metadata capture
- Response preview capture with size limits
- Security header and HTTPS checks
- SSRF-style private/internal target blocking defaults

### Assertions and regression evidence

- Endpoint and project-level assertion rules
- Status code, response time, response size, redirect, HTTPS and header assertions
- Response body assertions
- JSON path value/type/count checks
- Snapshot creation
- Snapshot compare reports
- Regression evaluation
- Scheduled monitor state-change alerts and optional webhook JSON delivery

### QA workflow

- QA operations calendar
- Manual QA tasks, regression retests, release checkpoints and maintenance windows
- Alert follow-up scheduling from monitor alert history
- Calendar JSON feed and .ics export
- Test suites
- Test cases
- Endpoint-linked test cases
- Test execution dashboard
- QA coverage matrix
- Findings & Evidence Center
- OpenAPI contract validation
- Release readiness dashboard
- QA evidence pack export
- QA release gate with stored decision

### Reporting and exports

- Full project QA Markdown report
- Custom QA report builder
- Release readiness Markdown report
- Endpoint CSV export
- Snapshot JSON export
- Snapshot compare Markdown export
- QA evidence pack ZIP export

### User account

- Authenticated profile page
- Name and e-mail update
- Personal language and timezone preference
- Password change form
- Account and activity summary

### Application foundation

- First-run setup wizard
- Windows/XAMPP helper scripts
- Linux install helper script
- English default UI
- Hungarian selectable UI
- Login and admin-only area
- Basic deployment hardening checks
- Aptoria UI/Bootstrap Blade interface with Roboto typography overlay

---

## Tech stack

- PHP / Laravel
- Blade templates
- SQLite by default for local/self-hosted use
- Aptoria UI/Bootstrap admin UI assets
- PHPUnit feature test suite
- Windows/XAMPP support scripts

---

## Repository license and public status

Aptoria is distributed as a **source-available** project. The code may be visible in a public GitHub repository for review, portfolio and local evaluation purposes, but it is not an open-source grant for commercial redistribution or hosted resale.

- Aptoria application code: see `LICENSE`.
- PHP dependencies: see `composer.json` and `composer.lock`.
- Bundled frontend libraries: see `THIRD_PARTY_NOTICES.md`.
- Public push checklist: see `docs/PUBLIC_REPOSITORY_CHECKLIST.md`.

## Requirements

Recommended local development/runtime stack:

- PHP 8.2 or newer
- Composer
- SQLite extension enabled
- OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, Fileinfo extensions
- XAMPP on Windows, or a standard PHP/Linux host

---

## Windows/XAMPP installation

The release ZIP intentionally does **not** include `vendor/`, `.env`, `database/database.sqlite` or local setup locks. Dependencies and runtime folders are prepared locally by the Windows/XAMPP helper script.

Use this exact PowerShell template for the v1.0.86 release ZIP:

```powershell
$ZipPath = "E:\GitHub projects\Aptoria\aptoria-1.0.86.zip"
$TempPath = "E:\GitHub projects\Aptoria\_temp_aptoria_1.0.86"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue

Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force

Copy-Item "$TempPath\aptoria-1.0.86\*" $ProjectRoot -Recurse -Force

cd $ProjectRoot
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass

.\scripts\update-windows-xampp.ps1

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate

C:\xampp\php\php.exe artisan test

C:\xampp\php\php.exe artisan serve
```

Then open the local URL shown by `artisan serve`, or use the project through your XAMPP virtual host / htdocs path.

### Public GitHub clone workflow

For a public GitHub checkout, use the same project root but clone first instead of expanding a ZIP:

```powershell
$ProjectRoot = "C:\xampp\htdocs\aptoria"

git clone https://github.com/Szujo-Janos/Aptoria.git $ProjectRoot
cd $ProjectRoot
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass

.\scripts\update-windows-xampp.ps1

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan serve
```

## First-run setup

On a clean installation, Aptoria redirects normal application pages to `/setup` until installation is completed.

The setup wizard can help with:

- environment status checks
- `.env` creation
- SQLite database file creation
- application key generation
- database migrations
- first admin user creation
- optional demo QA project import
- setup lock creation

The setup lock file is:

```text
storage/app/installed.lock
```

This file is generated locally and must not be committed or shipped in release ZIPs.

---

## Scheduled monitor runner

Run this command every 5–15 minutes from Windows Task Scheduler:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --limit=50
```

Before enabling the live task, verify matching monitors without sending API requests:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --dry-run
```

Useful operational options:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --project=project-slug
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --monitor=12
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --force
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --json
```

See `docs/SCHEDULED_MONITORING_OPERATIONS.md` for Windows Task Scheduler and Linux cron details.

---

## Running tests

```powershell
cd "C:\xampp\htdocs\aptoria"
C:\xampp\php\php.exe artisan test
```

Useful focused checks:

```powershell
C:\xampp\php\php.exe artisan test --filter=SelfInstallerTest
C:\xampp\php\php.exe artisan test --filter=DemoQaProjectSeederTest
C:\xampp\php\php.exe artisan test --filter=TestSuitesAndCasesTest
C:\xampp\php\php.exe artisan test --filter=SecurityHardeningTest
C:\xampp\php\php.exe artisan test --filter=ScheduledMonitorCommandTest
```

Deployment/security readiness check:

```powershell
C:\xampp\php\php.exe artisan aptoria:security-audit
```

Scheduled monitor runner checks:

```powershell
C:\xampp\php\php.exe artisan test --filter=ScheduledMonitorCommandTest
C:\xampp\php\php.exe artisan aptoria:run-monitors --dry-run
C:\xampp\php\php.exe artisan aptoria:run-monitors --dry-run --json
```

The test suite uses the `testing` environment and in-memory SQLite configuration.

---

## Release ZIP rules

Release ZIPs must remain cumulative, but must not include machine-specific runtime state.

Excluded from release ZIPs:

```text
vendor/
.env
database/database.sqlite
storage/app/installed.lock
storage/app/setup-token.txt
bootstrap/cache/*.php
storage runtime files
public/storage
```

Required in release ZIPs:

```text
.env.example
.env.production.example
.env.testing
composer.json
composer.lock
artisan
scripts/*.ps1
scripts/install-linux.sh
public/assets/aptoria-ui/vendor
```

---

## Portfolio showcase

For a public portfolio-friendly overview, see:

```text
docs/PORTFOLIO_SHOWCASE.md
```

That document summarizes the product idea, target users, feature areas, suggested screenshots, QA workflow narrative and public demo positioning without exposing private customer data or credentials.

Suggested public screenshots:

```text
docs/assets/screenshots/dashboard.png
docs/assets/screenshots/project-details.png
docs/assets/screenshots/reports.png
docs/assets/screenshots/release-readiness.png
docs/assets/screenshots/qa-evidence.png
```

## Credits and copyright

Aptoria is designed and maintained by **János Szujó**.

Copyright © 2026 János Szujó. All rights reserved.

This repository is source-available for review, evaluation, portfolio presentation and non-commercial local testing. It is not an open-source project unless a separate written agreement says otherwise.

For ownership, credits and third-party dependency details, see:

- `LICENSE`
- `NOTICE.md`
- `CREDITS.md`
- `THIRD_PARTY_NOTICES.md`

---

## GitHub Actions QA gate

The public repository includes a GitHub Actions workflow at:

```text
.github/workflows/php.yml
```

The workflow checks public release hygiene, validates Composer metadata, runs PHP syntax checks, installs dependencies in CI, prepares the testing environment and runs the PHPUnit suite. It is intended as a public confidence signal and a safety net against accidentally committing local runtime files.

## GitHub publishing guidance

### Private repository

Use a private repository for active experiments, unfinished branches and internal notes.

Recommended pre-push checks:

```powershell
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate
C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan aptoria:security-audit
```

### Public repository

The v1.0.86 line is prepared for public GitHub presentation as a source-available project with aligned installation instructions, automated QA gate metadata, credits and copyright notices. Public visibility is intentional source visibility, not an open-source license grant.

Before pushing public, review:

1. `LICENSE`
2. `NOTICE.md`
3. `CREDITS.md`
4. `THIRD_PARTY_NOTICES.md`
5. `docs/PUBLIC_REPOSITORY_CHECKLIST.md`
6. local test and security-audit results
7. screenshots/sample data for private URLs or credentials

---

## Documentation map

- `docs/INSTALLATION.md` – installation notes
- `docs/QA_CHECKLIST.md` – current release QA checklist
- `docs/MVP_PLAN.md` – current product status and roadmap
- `docs/PORTFOLIO_SHOWCASE.md` – portfolio/showcase overview
- `docs/SYSTEM_AUDIT_v1.0.86.md` – current system audit
- `docs/GITHUB_REPOSITORY_CHECKLIST.md` – GitHub preparation checklist
- `docs/APTORIA_UI_TEMPLATE_AUDIT.md` – Aptoria UI template integration audit
- `docs/APTORIA_UI_UX_REFRESH.md` – Aptoria UI/UX refresh notes
- `docs/PUBLIC_REPOSITORY_CHECKLIST.md` – public GitHub pre-push checklist
- `LICENSE` – source-available project license
- `NOTICE.md` – copyright and ownership notice
- `CREDITS.md` – project ownership and development credits
- `THIRD_PARTY_NOTICES.md` – frontend/backend dependency notices
- `docs/DEPLOYMENT_SECURITY_CHECKLIST.md` – deployment hardening checklist
- `docs/SCHEDULED_MONITORING_OPERATIONS.md`
- `docs/MONITOR_ALERTING_OPERATIONS.md` – scheduler/monitor alerting operations
- `docs/MONITOR_ALERT_TRIAGE_OPERATIONS.md` – alert acknowledgement and triage workflow
- `docs/MONITOR_EMAIL_DELIVERY_OPERATIONS.md` – SMTP/mail alert delivery setup
- `SERVER_INSTALLER.md` – first-run server installer notes

---

## Development direction

The immediate priority is not more features. The next priorities are:

1. keep the PHPUnit suite green;
2. keep documentation aligned with the current release;
3. decide private vs public GitHub strategy;
4. harden production deployment guidance;
5. only then continue with new product features.

---

## License and third-party assets

Aptoria application code is source-available under `LICENSE`. Third-party dependencies and bundled frontend assets keep their own licenses; review `THIRD_PARTY_NOTICES.md` before redistribution or commercial packaging.
