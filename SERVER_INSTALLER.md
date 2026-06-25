# Aptoria Server First-Run Installer

Current version: **v0.0.63**

Aptoria contains a first-run setup flow for fresh deployments. Until the application is installed and locked, normal web pages redirect to `/setup`.

> [!IMPORTANT]
> The `v0.0.63` package is a fresh replacement for the legacy `1.1.34` code line. It is not an in-place database upgrade.

## Installer overview

| Area | Handled by setup |
| --- | --- |
| Environment diagnostics | Checks the local runtime before installation. |
| `.env` creation | Creates the application environment file. |
| SQLite database | Creates the local SQLite database file. |
| Application key | Generates `APP_KEY`. |
| Database migrations | Runs required Laravel migrations. |
| First admin user | Creates the first local administrator. |
| Demo data | Optional demo QA project import. |
| Setup lock | Creates the local install lock. |

The setup lock is stored locally at:

```text
storage/app/installed.lock
```

This file must never be included in release ZIPs or committed to Git.

## Recommended prerequisites

| Requirement | Recommended value |
| --- | --- |
| PHP | 8.2 or newer |
| Runtime | XAMPP on Windows or a standard PHP host |
| Database | SQLite for the documented local/self-hosted flow |
| Composer | Required for Laravel dependencies |
| Extensions | OpenSSL, PDO, SQLite, Mbstring, Tokenizer, XML, Ctype, JSON, Fileinfo |

## Windows/XAMPP recommended replacement flow

Use a clean target folder so old migrations, cached files and views cannot remain in `C:\xampp\htdocs\aptoria`.

```powershell
$ZipPath = "E:\Aptoria\aptoria-0.0.63.zip"
$TempPath = "E:\Aptoria\_temp_aptoria_0.0.63"
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

## Health diagnostics

Run this after migration and before real QA work:

```powershell
C:\xampp\php\php.exe artisan aptoria:health
```

Machine-readable output:

```powershell
C:\xampp\php\php.exe artisan aptoria:health --json
```

## Maintenance backup recommendation

Before server moves, hard resets or major replacements, export any old database you still need to keep. Keep the downloaded database export together with the server `.env` backup, because encrypted auth/profile values require the same `APP_KEY` after restore.

Recommended backup set:

```text
.env
database export
storage evidence files, if needed
generated customer reports, if intentionally retained
license files, if the runtime uses license enforcement
```

## Security notes

- Keep `APP_DEBUG=false` in production.
- Use HTTPS in production.
- Replace default/temporary admin credentials immediately.
- Do not expose `.env`, SQLite database files or storage internals publicly.
- Keep setup locked after installation.
- Use a setup token only for controlled recovery/install flows.
- Do not commit `bootstrap/cache`; it is created locally by install/update scripts.

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

## Troubleshooting quick checks

| Symptom | Check |
| --- | --- |
| Blank page / old view appears | Run `optimize:clear`, `view:clear`, `config:clear` and `route:clear`. |
| Setup keeps redirecting | Check whether `storage/app/installed.lock` exists after setup. |
| Database errors | Confirm SQLite exists and migrations were run. |
| Missing assets | Confirm `public/assets/aptoria-ui/vendor` and bundled runtime assets are present. |
| Auth/profile encrypted values fail after restore | Restore the matching `.env` and `APP_KEY`. |
| Tests fail after ZIP replacement | Use a clean target folder and rerun the update script. |
