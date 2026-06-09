# Aptoria Installation

Current version: **v1.1.1**

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
$ZipPath = "E:\GitHub projects\Aptoria\aptoria-1.1.1.zip"
$TempPath = "E:\GitHub projects\Aptoria\_temp_aptoria_1.1.1"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue

Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force

Copy-Item "$TempPath\aptoria-1.1.1\*" $ProjectRoot -Recurse -Force

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

After profile review, open **Projects → Guided Project**. The v1.1.1 onboarding wizard creates the first usable project workspace in one pass:

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
