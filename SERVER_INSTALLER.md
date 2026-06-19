# Aptoria Server First-Run Installer

Current version: **v0.0.53**

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

After setup and migrations, run:

```powershell
C:\xampp\php\php.exe artisan aptoria:health
```

The setup lock is stored locally at:

```text
storage/app/installed.lock
```

This file must never be included in release ZIPs or committed to Git.

---

## Windows/XAMPP recommended replacement flow

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

---

## Maintenance backup recommendation

Before server moves, hard resets or major replacements, export any old database you still need to keep. Keep the downloaded database export together with the server `.env` backup, because encrypted auth/profile values require the same `APP_KEY` after restore.

The v0.0.53 package is a **fresh replacement** for the legacy 1.1.34 code line, not an in-place database upgrade.

---

## Security notes

- Keep `APP_DEBUG=false` in production.
- Use HTTPS in production.
- Replace default/temporary admin credentials immediately.
- Do not expose `.env`, SQLite database files or storage internals publicly.
- Keep setup locked after installation.
- Use a setup token only for controlled recovery/install flows.
- Do not commit `bootstrap/cache`; it is created locally by install/update scripts.

---

## System health diagnostics

Run this after migration and before real QA work:

```powershell
C:\xampp\php\php.exe artisan aptoria:health
```

Machine-readable output:

```powershell
C:\xampp\php\php.exe artisan aptoria:health --json
```

---

## Post-install smoke test

1. Open `/setup` and complete installation.
2. Sign in with the created admin user.
3. Change any temporary password immediately.
4. Create or open a project.
5. Add endpoint inventory.
6. Capture safe scan or native test evidence.
7. Verify at least one evidence item.
8. Open QA Cockpit.
9. Create a Release Gate.
10. Generate a Release Gate Decision Package export.
