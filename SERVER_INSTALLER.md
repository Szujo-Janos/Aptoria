# Aptoria Server First-Run Installer

Current version: **v1.1.28**

Aptoria contains a first-run setup flow for fresh deployments. Until the application is installed and locked, normal web pages redirect to `/setup`.

---

## What the installer handles

The setup flow can help with:

- environment diagnostics;
- `.env` creation;
- SQLite database file creation;
- `APP_KEY` generation;
- database migrations;
- first admin user creation;
- optional demo QA project import;
- setup lock creation.

After setup and migrations, open **System Health** or run `php artisan aptoria:health` to verify runtime, storage, database, security and automation readiness. Then either use **Demo Project** to import a complete synthetic sample workspace, or use **Projects → Guided Project** to create the first project, environment, auth profile, endpoint, safe scan, snapshot and report readiness flow in one guided pass.

The setup lock is stored locally at:

```text
storage/app/installed.lock
```

This file must never be included in release ZIPs or committed to Git.

---

## Windows/XAMPP recommended update flow

```powershell
$ZipPath = "E:\GitHub projects\Aptoria\aptoria-1.1.28.zip"
$TempPath = "E:\GitHub projects\Aptoria\_temp_aptoria_1.1.28"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue

Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force

Copy-Item "$TempPath\aptoria-1.1.28\*" $ProjectRoot -Recurse -Force

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

---

## Maintenance backup recommendation

Before server moves, hard resets or major upgrades, sign in as admin and use **Settings → Database maintenance → Export full database**. Keep the downloaded JSON together with the server `.env` backup, because encrypted auth profile values require the same APP_KEY after restore.

## Security notes

- Keep `APP_DEBUG=false` in production.
- Use HTTPS in production.
- Replace default admin credentials immediately.
- Do not expose `.env`, SQLite database files or storage internals publicly.
- Keep setup locked after installation.
- Use a setup token only for controlled recovery/install flows.



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

Aptoria does not need a long-running worker for scheduled monitors. Configure the operating system to call the artisan runner regularly.

Windows/XAMPP command:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --limit=50
```

Safe dry-run verification:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\aptoria\artisan aptoria:run-monitors --dry-run
```


## System health diagnostics

Run this after migration and before real QA work:

```powershell
C:\xampp\php\php.exe artisan aptoria:health
```

Admin users can also open **System Health** in the sidebar. The JSON endpoint is available at `/system/health.json` for machine-readable checks.


## Post-install endpoint inventory smoke test

After installation and migration, create or import a project, then open **Project → Endpoint Inventory** to confirm endpoint catalogue rendering, filters and localization.


## v1.1.6 Baseline Diff Viewer

After deployment, clear caches, run migrations and open Project → Snapshots. Compare two snapshots and verify the richer baseline/current diff categories.

## v1.1.6 Schema Drift Detector

Run `php artisan migrate` after deployment. The release adds schema drift metadata columns to scan results and uses them during safe probes, Endpoint Inventory filtering and report generation.

## v1.1.7 Regression Test Suite Builder check

After installing v1.1.7 or later, open **Project → Test Suites → Suite Builder**, create a regression suite from selected endpoints, confirm generated test cases and assertion rules, then run the suite from the suite detail page and verify test results are recorded.



## v1.1.8 Release Readiness Score check

After installing v1.1.8 or later, open **Project → Release Readiness**, confirm the weighted score breakdown is visible, then export the readiness report and check that the component table is present.


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

## v1.1.28 QA Cockpit

After installing v1.1.28 or later, run migrations and open **Project → QA Cockpit**. Confirm the daily QA queues show blockers, retest work, expiring accepted risks, stale scan/report evidence, endpoint evidence gaps and release candidates needing a saved decision.
