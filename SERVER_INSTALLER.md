# Aptoria Server First-Run Installer

Current version: **v1.1.1**

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

After setup and migrations, open **System Health** or run `php artisan aptoria:health` to verify runtime, storage, database, security and automation readiness. Then use **Projects → Guided Project** to create the first project, environment, auth profile, endpoint, safe scan, snapshot and report readiness flow in one guided pass.

The setup lock is stored locally at:

```text
storage/app/installed.lock
```

This file must never be included in release ZIPs or committed to Git.

---

## Windows/XAMPP recommended update flow

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
