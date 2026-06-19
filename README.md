<p align="center">
  <img src="public/assets/aptoria-ui/assets/images/logo-color.svg" alt="Aptoria logo" width="320">
</p>

# Aptoria

<p align="center">
  Self-hosted API QA evidence, coverage and release-decision platform.
</p>

**Aptoria** is a self-hosted, evidence-first API QA and release decision platform built with Laravel.

Current release: **v0.0.53**  
Repository line: **0.0.x evidence-first rebuild**  
Legacy line replaced: **v1.1.34**

Aptoria is not a Postman, Newman, Jira, Datadog or full test-management clone. Its role is to collect and normalize the release-critical evidence those tools often leave scattered: endpoint inventory, safe scan proof, imported QA artifacts, native test evidence, findings, verified evidence, release gates and checksum-backed decision packages.

> Repository status note: this branch is prepared for public GitHub presentation as a source-available project. Public visibility does not make Aptoria open-source; see `LICENSE`, `NOTICE.md`, `CREDITS.md` and `THIRD_PARTY_NOTICES.md`.

---

## What Aptoria helps you answer

- Which API endpoints are in release scope?
- Which endpoints have safe scan evidence?
- Which external QA artifacts became normalized Aptoria findings, assertions, tests or evidence?
- Which native test cases and test runs support the release decision?
- Which evidence items are verified and checksum-backed?
- Which high/critical findings still block release?
- Which blind spots remain before sign-off?
- Can the release gate be frozen into an auditable decision package?
- Can the result be exported as HTML/PDF/JSON/Markdown/ZIP and later delivered through a client portal?

The goal is not to replace Postman, Newman, Jira, Playwright, OWASP tools or a full test-management platform. Aptoria is a self-hosted evidence and workflow layer for API QA and release review.

Release history is tracked in [`CHANGELOG.md`](CHANGELOG.md).

---

## Current v0.0.53 feature line

### Project and access foundation

- First-run setup wizard
- Standalone security hardening
- Local user onboarding with temporary passwords
- Project access and membership foundation
- Project roles: Project admin, QA engineer, Reviewer, Release approver and Read-only viewer
- Project-scoped route/access checks
- English default UI with Hungarian selectable UI direction

### Endpoint and safe QA evidence

- Project management
- Environment and auth profile foundations
- Endpoint inventory foundation
- Safe scan evidence foundation
- Assertion and endpoint snapshot foundations
- Finding workflow with deduplication / merge support

### Evidence Repository

- Project-level evidence repository
- SHA-256 evidence checksums
- Evidence lifecycle events
- Active / verified / archived repository states
- Archive instead of hard-delete behavior
- Evidence Pack HTML/PDF/JSON/Markdown/ZIP export paths

### Import Adapter Layer

- Normalized adapter direction for Postman/Newman/Jira/OpenAPI JSON/QA CSV/HAR-style inputs
- External artifacts are converted into Aptoria endpoints, assertions, findings, tests and repository evidence instead of being treated as raw imports
- OpenAPI JSON contract normalization foundation
- QA CSV test-result normalization foundation

### Native Test Evidence

- Native test suites
- Native test cases
- Native test runs
- Pass / fail / blocked / skipped states
- Every recorded run can create checksum-backed repository evidence
- Failed runs can create linked findings

### QA Cockpit and release decisions

- QA Cockpit with confidence score
- Coverage signals
- Blind spot detection
- Endpoint coverage matrix
- Release Gate Workflow foundation
- Release Gate Report & Decision Package exports
- Report Visual Standard for professional evidence documents

---

## Legacy replacement notice

This repository package replaces the old `aptoria-1.1.34` code line. The old branch is treated as archived historical code. The 0.0.x line is a rebuild with a cleaner evidence-first product direction, new UI rules and different database migrations.

This is a **fresh replacement**, not an in-place database upgrade.

Read before replacing an existing repository:

- `TRANSITION_SUMMARY.md`
- `docs/UPGRADE_FROM_1.1.34_TO_0.0.53.md`
- `docs/LEGACY_1.1.34_VS_0.0.53_COMPARISON.md`
- `docs/GITHUB_REPLACEMENT_CHECKLIST.md`
- `docs/ARCHITECTURE_TRANSITION_MAP.md`

---

## Requirements

Recommended local development/runtime stack:

- PHP 8.2 or newer
- Composer
- SQLite extension enabled
- OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON and Fileinfo extensions
- XAMPP on Windows, or a standard PHP/Linux host

SQLite is the default local/self-hosted database target. Other SQL databases may be possible through Laravel's database layer, but the public v0.0.53 workflow is documented around SQLite.

---

## Release ZIP / GitHub repository hygiene

The repository intentionally excludes machine-specific runtime state.

Do **not** commit or ship:

```text
vendor/
node_modules/
.env
database/database.sqlite
storage/app/installed.lock
storage/app/setup-token.txt
public/storage/
bootstrap/cache/
storage runtime files
```

Required public/release files include:

```text
.env.example
.env.testing
composer.json
artisan
scripts/update-windows-xampp.ps1
scripts/update-linux.sh
public/assets/aptoria-ui/vendor
README.md
LICENSE
NOTICE.md
CREDITS.md
THIRD_PARTY_NOTICES.md
SECURITY.md
```

The `bootstrap/cache` directory is created locally by the install/update scripts. It is intentionally not tracked because the public hygiene workflow treats it as runtime state.

---

## Windows/XAMPP installation or replacement from ZIP

For the v0.0.53 replacement line, prefer a clean target folder so old 1.1.34 migrations, cached files and views cannot remain in `C:\xampp\htdocs\aptoria`.

Use this exact PowerShell template:

```powershell
$ZipPath = "E:\GitHub projects\Aptoria\aptoria-0.0.53-github-transition.zip"
$TempPath = "E:\GitHub projects\Aptoria\_temp_aptoria_0.0.53"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item $ProjectRoot -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Path $ProjectRoot -Force

Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force
Copy-Item "$TempPath\aptoria\*" $ProjectRoot -Recurse -Force

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

Then open:

```text
http://127.0.0.1:8000/setup
```

Complete setup in the browser.

---

## Public GitHub clone workflow

For a GitHub checkout, clone first instead of expanding a ZIP:

```powershell
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $ProjectRoot -Recurse -Force -ErrorAction SilentlyContinue
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

---

## First-run setup

On a clean installation, Aptoria redirects normal application pages to `/setup` until installation is completed.

The setup wizard can help with:

- environment diagnostics;
- `.env` creation;
- SQLite database file creation;
- `APP_KEY` generation;
- database migrations;
- first admin user creation;
- optional demo QA project import;
- setup lock creation.

The setup lock file is generated locally at:

```text
storage/app/installed.lock
```

This file must never be committed or included in a release ZIP.

After setup and migrations, run:

```powershell
C:\xampp\php\php.exe artisan aptoria:health
```

The same command supports JSON output:

```powershell
C:\xampp\php\php.exe artisan aptoria:health --json
```

---

## First QA workflow smoke test

After setup:

1. Create or open a project.
2. Create an environment and auth profile when needed.
3. Add or import endpoints.
4. Capture safe scan or test evidence.
5. Create findings and attach evidence.
6. Verify important evidence in the Evidence Repository.
7. Open QA Cockpit and review coverage/blind spots.
8. Create a Release Gate.
9. Review gate items.
10. Generate a Release Gate Decision Package.
11. Export HTML/PDF/JSON/ZIP evidence.

---

## Running tests

```powershell
cd "C:\xampp\htdocs\aptoria"
C:\xampp\php\php.exe artisan test
```

Useful focused checks for the v0.0.x rebuild:

```powershell
C:\xampp\php\php.exe artisan test --filter=StandaloneSecurityHardeningTest
C:\xampp\php\php.exe artisan test --filter=ProjectMembershipAccessTest
C:\xampp\php\php.exe artisan test --filter=EvidenceRepositoryFoundationTest
C:\xampp\php\php.exe artisan test --filter=NativeTestEvidenceModelTest
C:\xampp\php\php.exe artisan test --filter=QaCockpitCoverageFoundationTest
C:\xampp\php\php.exe artisan test --filter=ReleaseGateWorkflowFoundationTest
```

The test suite uses the `testing` environment and SQLite configuration.

---

## Public GitHub Actions QA gate

The public repository includes GitHub Actions metadata under:

```text
.github/workflows/public-hygiene.yml
```

The workflow checks public release hygiene, required public files, Composer metadata and PHP syntax. It intentionally fails when runtime/local paths such as `.env`, `vendor/`, `database/database.sqlite`, `storage/app/installed.lock`, `storage/app/setup-token.txt`, `public/storage` or `bootstrap/cache` are committed.

---

## Documentation map

- `TRANSITION_SUMMARY.md` – short explanation of the 1.1.34 → 0.0.53 replacement
- `docs/UPGRADE_FROM_1.1.34_TO_0.0.53.md` – replacement notes
- `docs/LEGACY_1.1.34_VS_0.0.53_COMPARISON.md` – old vs new comparison
- `docs/GITHUB_REPLACEMENT_CHECKLIST.md` – public replacement checklist
- `docs/ARCHITECTURE_TRANSITION_MAP.md` – architecture transition map
- `docs/INSTALL_WINDOWS_XAMPP.md` – Windows/XAMPP install/update workflow
- `docs/QA_CHECKLIST.md` – current release QA checklist
- `docs/PROJECT_ACCESS_FOUNDATION.md`
- `docs/EVIDENCE_REPOSITORY_FOUNDATION.md`
- `docs/IMPORT_ADAPTER_LAYER.md`
- `docs/NATIVE_TEST_EVIDENCE_MODEL.md`
- `docs/QA_COCKPIT_COVERAGE_BLIND_SPOT_FOUNDATION.md`
- `docs/RELEASE_GATE_WORKFLOW_FOUNDATION.md`
- `docs/RELEASE_GATE_REPORT_DECISION_PACKAGE.md`
- `docs/REPORT_VISUAL_STANDARD.md`
- `SERVER_INSTALLER.md` – first-run installer and operational notes

---

## Security notes

- Keep `APP_DEBUG=false` in production.
- Use HTTPS in production.
- Replace default or temporary admin credentials immediately.
- Do not expose `.env`, SQLite database files or storage internals publicly.
- Keep setup locked after installation.
- Use a setup token only for controlled recovery/install flows.
- Back up `.env` together with database exports because encrypted auth/profile values may depend on the same application key.

---

## Credits and copyright

Aptoria is designed and maintained by **János Szujó**.

Copyright © 2026 János Szujó. All rights reserved.

This repository is source-available for review, evaluation, portfolio presentation and non-commercial local testing. It is not an open-source project unless a separate written agreement says otherwise.

For ownership, credits and third-party dependency details, see:

- `LICENSE`
- `NOTICE.md`
- `CREDITS.md`
- `THIRD_PARTY_NOTICES.md`
