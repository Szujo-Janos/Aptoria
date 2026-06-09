# Aptoria

**Aptoria** is a self-hosted Laravel application for API QA, endpoint visibility, regression monitoring, release evidence and lightweight security review.

Current release: **v1.1.0**  
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

Release history is tracked in [`CHANGELOG.md`](CHANGELOG.md).

---

## Current feature set

### Project and endpoint inventory

- Project management
- Guided project onboarding wizard with project, environment, auth, endpoint, first safe scan, first snapshot and report readiness flow
- Environment management
- Auth profiles with bearer/basic/custom header support and built-in authentication test requests
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
- Automatic onboarding baseline snapshot from the first guided safe scan
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

- Full project QA Markdown, HTML and PDF reports
- Custom QA report builder with Markdown, HTML and PDF exports
- Release readiness Markdown, HTML and PDF reports
- QA release gate Markdown, HTML and PDF reports
- Scan Markdown, HTML and PDF reports
- Snapshot JSON export
- Snapshot compare Markdown, HTML and PDF exports
- Endpoint CSV export
- QA evidence pack ZIP export
- Full database JSON export/import and hard reset back to first-run setup

### Database maintenance

Admin users can use **Settings → Database maintenance** to export the complete database as JSON, restore a matching Aptoria database export, or perform a hard reset back to first-run setup mode. Full database imports and hard reset actions require typed confirmation. See `docs/DATABASE_MAINTENANCE_OPERATIONS.md`.

### User account

- Authenticated profile page
- Name and e-mail update
- Personal language and timezone preference
- Password change form
- Account and activity summary
- Report identity fields for generated HTML/PDF exports

### Application foundation

- First-run setup wizard
- First-use guided project wizard that avoids empty or half-configured projects
- Windows/XAMPP helper scripts
- Linux install helper script
- English default UI
- Hungarian selectable UI
- Login and admin-only area
- Basic deployment hardening checks
- System health diagnostics page with JSON and artisan command output
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

Fresh installs are guarded by the first-run setup flow. Until `storage/app/installed.lock` exists, normal application pages and login attempts are redirected to `/setup`; creating database users alone is not enough to open the app. After setup is locked, `/setup` is closed and the first successful login sends the admin to **My Profile** so report identity details can be completed before QA work starts.

Use this exact PowerShell template for the v1.1.0 release ZIP:

```powershell
$ZipPath = "E:\GitHub projects\Aptoria\aptoria-1.1.0.zip"
$TempPath = "E:\GitHub projects\Aptoria\_temp_aptoria_1.1.0"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue

Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force

Copy-Item "$TempPath\aptoria-1.1.0\*" $ProjectRoot -Recurse -Force

cd $ProjectRoot
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass

.\scripts\update-windows-xampp.ps1

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate

C:\xampp\php\php.exe artisan aptoria:health

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
C:\xampp\php\php.exe artisan aptoria:health
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

## First project onboarding

After the first login and profile identity setup, use **Projects → Guided Project**. The guided wizard now performs the full first-use flow:

- creates the project basics;
- creates the first environment;
- creates the default auth profile;
- imports at least one endpoint from CSV, JSON or OpenAPI;
- seeds default assertion rules;
- runs the first safe GET/HEAD scan for non-production environments;
- creates the first baseline snapshot from that scan;
- prepares the first full project report exports;
- opens a completion page with links to the project, scan, snapshot and Markdown/HTML/PDF reports.

Production environments are never auto-scanned from the wizard; start those scans later from the scan screen with explicit confirmation.

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
C:\xampp\php\php.exe artisan aptoria:health
C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan aptoria:security-audit
```

### Public repository

This repository is prepared for public GitHub presentation as a source-available project with aligned installation instructions, automated QA gate metadata, credits and copyright notices. Public visibility is intentional source visibility, not an open-source license grant.

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
- `docs/SYSTEM_AUDIT_v1.1.0.md` – current system audit
- `docs/DATABASE_MAINTENANCE_OPERATIONS.md` – database export/import and hard reset guide
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

### API collection import

Aptoria v1.1.0 supports endpoint inventory import from CSV, simple JSON endpoint lists, OpenAPI/Swagger JSON/YAML and Postman Collection JSON. The import flow always renders a preview first, then writes endpoint inventory only after confirmation. Postman imports now support a matching Postman Environment JSON, variable resolution, optional environment creation from `baseUrl`, optional auth profile creation from Postman auth, response example/test-script assertion extraction, and optional folder-to-test-suite mapping. Secrets are masked in preview and stored only in encrypted auth profile fields when an auth profile is explicitly created.

