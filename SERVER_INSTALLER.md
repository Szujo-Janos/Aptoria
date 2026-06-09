# Aptoria Server First-Run Installer

Current version: **v1.0.87**

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

The setup lock is stored locally at:

```text
storage/app/installed.lock
```

This file must never be included in release ZIPs or committed to Git.

---

## Windows/XAMPP recommended update flow

```powershell
$ZipPath = "E:\GitHub projects\Aptoria\aptoria-1.0.87.zip"
$TempPath = "E:\GitHub projects\Aptoria\_temp_aptoria_1.0.87"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue

Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force

Copy-Item "$TempPath\aptoria-1.0.87\*" $ProjectRoot -Recurse -Force

cd $ProjectRoot
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass

.\scripts\update-windows-xampp.ps1

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate

C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan aptoria:security-audit

C:\xampp\php\php.exe artisan serve
```

---

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
