# Aptoria Installation

Current version: **v1.1.32**

Aptoria is built for self-hosted Laravel deployment. The primary tested local workflow is Windows/XAMPP, with Linux/VPS scripts provided as helpers.

---

## Release ZIP principle

Release ZIPs are cumulative application packages, but they intentionally exclude local runtime state.

The release ZIP does not include:

- `vendor/`
- `.env`
- `database/database.sqlite`
- `storage/app/installed.lock`
- `storage/app/setup-token.txt`
- runtime cache/session/log files

The update script installs dependencies and prepares local runtime folders.

---

## Windows/XAMPP update or install

Use this exact PowerShell template for ZIP-based local updates:

```powershell
$ZipPath = "E:\GitHub projects\Aptoria\aptoria-1.1.32.zip"
$TempPath = "E:\GitHub projects\Aptoria\_temp_aptoria_1.1.32"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue

Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force

Copy-Item "$TempPath\aptoria-1.1.32\*" $ProjectRoot -Recurse -Force

cd $ProjectRoot
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass

.\scripts\update-windows-xampp.ps1

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate

C:\xampp\php\php.exe artisan aptoria:health
C:\xampp\php\php.exe artisan aptoria:security-audit
C:\xampp\php\php.exe artisan aptoria:demo-project --json

C:\xampp\php\php.exe artisan test

C:\xampp\php\php.exe artisan serve
```

### Public GitHub clone install

If the source was cloned from GitHub instead of installed from a release ZIP, run the local preparation and test commands from the cloned project root:

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
C:\xampp\php\php.exe artisan aptoria:security-audit
C:\xampp\php\php.exe artisan serve
```

## First-run setup

Open the application in the browser. If it is not installed yet, normal pages and login attempts redirect to `/setup`.

Aptoria treats setup as complete only after the setup lock exists. A migrated database or an existing admin user without `storage/app/installed.lock` still keeps the application behind the setup guard. After setup is locked, `/setup` redirects back to the normal login/profile flow.

The setup page can check or prepare:

- PHP runtime requirements;
- `.env` file;
- SQLite database file;
- `APP_KEY`;
- database migrations;
- first admin account;
- optional demo QA data;
- setup lock.

After setup is complete, this file is created locally:

```text
storage/app/installed.lock
```

Do not commit or release this file.

On the first successful login after creating the admin account, Aptoria redirects to **My Profile** so the interface language, timezone and Report identity fields can be reviewed before generated reports are used.

---

## First project onboarding

After profile review, open **Projects → Guided Project**. The v1.1.6 onboarding wizard creates the first usable project workspace in one pass:

- project name and base URL;
- first environment;
- default auth profile;
- endpoint import from CSV, JSON or OpenAPI;
- default assertion rules;
- first safe GET/HEAD scan for non-production environments;
- first baseline snapshot;
- first full project report readiness check;
- completion page with links to the project, scan, snapshot and Markdown/HTML/PDF reports.

If the selected environment is marked as production, the wizard creates the project but skips the automatic scan. Start production scans later from the scan screen with explicit confirmation.

---

## Local test command

```powershell
cd "C:\xampp\htdocs\aptoria"
C:\xampp\php\php.exe artisan test
```

---

## Database maintenance after installation

After setup and first login, open **Settings → Database maintenance** for full database backup/restore operations.

- Use **Export full database** before upgrades, server moves or hard reset.
- Use **Import database** only with a matching Aptoria JSON export from the same schema/version.
- Use **Hard reset system** only when the installation should be wiped and returned to `/setup`.

The database export does not include `.env`, APP_KEY, vendor files, uploaded storage files or setup lock files. See `docs/DATABASE_MAINTENANCE_OPERATIONS.md`.

## Troubleshooting

### Composer/vendor missing

Run:

```powershell
.\scripts\update-windows-xampp.ps1
```

### Cached Laravel state after update

Run:

```powershell
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
```

### Setup keeps appearing

Check whether setup was completed and whether this file exists:

```text
storage/app/installed.lock
```

### SQLite database missing

Use setup wizard or create the database file locally, then run:

```powershell
C:\xampp\php\php.exe artisan migrate
```



## Deployment/security audit

After migration and tests, run:

```powershell
C:\xampp\php\php.exe artisan aptoria:security-audit
```

For a stricter release gate:

```powershell
C:\xampp\php\php.exe artisan aptoria:security-audit --fail-on-warning
```

---

## Scheduled monitor runner

For Windows Task Scheduler, run this command every 5–15 minutes:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --limit=50
```

Verify the scheduler setup without sending API requests:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --dry-run
```

Machine-readable output is available with:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --dry-run --json
```

See `docs/SCHEDULED_MONITORING_OPERATIONS.md` for all options.


---

## System health diagnostics

After migrations, open **System Health** from the sidebar or run:

```powershell
C:\xampp\php\php.exe artisan aptoria:health
```

For deployment automation or CI output, use:

```powershell
C:\xampp\php\php.exe artisan aptoria:health --json
C:\xampp\php\php.exe artisan aptoria:health --fail-on-warning
```

The page and command check PHP extensions, storage permissions, database connectivity, setup lock state, security posture, database export readiness and scheduled monitor command availability.


## API collection import smoke check

After creating a project, open **Project → Endpoints → Import Endpoints**. Use the Postman sample button, preview the collection, then confirm the import. Verify that endpoint rows are created and that imported request metadata is visible on endpoint detail pages.


## Endpoint inventory check

After importing endpoints or creating a guided project, open **Project → Endpoint Inventory**. This page should show a consolidated audit catalogue with scan coverage, risk, auth state, findings and coverage gaps.


## Environment Manager check

After installing v1.1.6, open **Project → Environments** and confirm that local/dev/staging/production/custom environments, default environment selection and environment-level auth profile display work as expected.


## v1.1.6 Sensitive Data Detector check

After installing v1.1.6, run a safe GET/HEAD probe against a non-production endpoint that returns sample personal data or token-like fields. Scan results and Endpoint Inventory should show sensitive data flags, and an open finding with masked evidence should be created.

## v1.1.6 Broken Auth Comparison check

After installing v1.1.6, run a safe GET/HEAD probe against a non-production endpoint marked as auth-required with a complete auth profile. Aptoria should compare the authenticated request with a no-auth request, mark 401/403 as protected, and create a finding/evidence when a no-auth 2xx/3xx response is returned.


## v1.1.6 Baseline Diff Viewer check

After installing v1.1.6, create or reuse two snapshots for a project, run a snapshot comparison and confirm that the diff viewer separates status, performance, header, body, schema and security changes. The full project report should include latest compare breaking/schema/header/body summary metrics.

## v1.1.6 Schema Drift Detector check

After installing v1.1.6, run a safe GET/HEAD probe twice against the same JSON endpoint after changing the response shape. Aptoria should store the response schema, flag schema drift on the second run, create a regression finding with evidence when enabled, and expose the schema drift flag in Endpoint Inventory.

## v1.1.7 Regression Test Suite Builder check

After installing v1.1.7 or later, open **Project → Test Suites → Suite Builder**, create a regression suite from selected endpoints, confirm generated test cases and assertion rules, then run the suite from the suite detail page and verify test results are recorded.



## v1.1.8 Release Readiness Score check

After installing v1.1.8 or later, open **Project → Release Readiness** and confirm the score breakdown table shows the weighted components, earned points, max points and status labels. Export the readiness Markdown report and confirm the score breakdown section is included.


## v1.1.15 System Health Diagnostics check

After installing v1.1.15 or later, open **System Health** from the global navigation or run `php artisan aptoria:health`. Confirm the report includes runtime, application, storage, cache, database, security, import/export, reporting/evidence, automation and queue categories. Export `/system/health.json` or run `php artisan aptoria:health --json` for machine-readable deployment evidence.

## v1.1.11 Executive / Technical Report Split check

After installing v1.1.11, open **Project → Reports** and export both the **Executive Report** and **Technical Report** in Markdown, HTML and PDF formats. Confirm the executive report stays decision-focused with release readiness, main risks and recommendations, while the technical report includes endpoint inventory, findings, evidence, contract validation and request/response evidence.

## v1.1.16 Audit Log Activity Timeline check

After installing v1.1.16 or later, open **Audit Log** from the global navigation. Create or edit a project, then confirm the timeline records the event with user, project, action, route and before/after metadata. Open a project-specific audit log and export JSON evidence from `/audit-log.json` or `projects/{project}/audit-log.json`.



## v1.1.18 Navigation & Profile Menu Cleanup check

After installing v1.1.18, review the sidebar and profile dropdown. Confirm global workflows are grouped into Release & reports, Operations, Audit & admin, and Help & workflow; project modules are grouped by task area; project monitors are available from the current project menu; and the profile dropdown contains only account-level actions with no empty rows.


## v1.1.19 QA Blind Spot Detector check

After installing v1.1.19 or later, open **Project → Blind Spots** and confirm missing scan, missing assertion, missing auth comparison, unverified fix, accepted risk expiry and stale evidence warnings are detected. Then open **Project → Release Readiness** and export executive/technical reports to confirm the Blind Spot Summary is included.


## v1.1.20 Finding Verification & Ownership check

After installing v1.1.20 or later, open **Project → Findings**, create or edit a finding, assign an owner, due date, priority, verification status and retest requirement. Move the finding through **Ready for retest → Retest failed → Fixed → Verified**, add retest evidence and a finding comment, then confirm Release Readiness and full QA reports show the verification summary.

## v1.1.21 Risk Acceptance Ledger check

After installing v1.1.21 or later, run migrations, open a finding, record an accepted risk, then open **Project → Risk Ledger**. Confirm missing expiry, expiring soon and expired accepted risk decisions are visible and that Release Readiness includes the Risk Acceptance Ledger Summary.

## v1.1.32 Release Workflow State Machine

After installing v1.1.32 or later, run migrations and open **Project → Release Workflow**. Confirm the 15-step state machine is visible, blocker / evidence counts are persisted, the release pre-check panel highlights incomplete steps and a Project admin or Release approver can skip a step only with a reason and reopen it afterwards.

## v1.1.30 Client Portal Handoff Visibility Polish

After installing v1.1.30 or later, run migrations and open **Project → Client Portal**. Confirm the role default permission matrix is visible and switching the role selector updates the permission checkboxes, then open viewer, approver and restricted public portal links. The public portal should show the Aptoria logo in the fixed header, a current client-safe release snapshot and a role access summary that marks visible and restricted sections clearly.

## v1.1.29 Workflow Consolidation & Permission Hardening

After installing v1.1.29 or later, run migrations and open **Project → Release Workflow**. Confirm the guided flow connects QA Cockpit, Blind Spots, Release Readiness, Release Gate, Release Decision, Report Approval and Client Portal handoff. Also verify restricted Client Portal links cannot download evidence packages or post acknowledgements without the matching permission.
