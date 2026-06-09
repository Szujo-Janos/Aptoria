# Aptoria Installation

Current version: **v1.0.82**

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
$ZipPath = "E:\GitHub projects\Aptoria\aptoria-1.0.82.zip"
$TempPath = "E:\GitHub projects\Aptoria\_temp_aptoria_1.0.82"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue

Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force

Copy-Item "$TempPath\aptoria-1.0.82\*" $ProjectRoot -Recurse -Force

cd $ProjectRoot
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass

.\scripts\update-windows-xampp.ps1

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate

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
C:\xampp\php\php.exe artisan test
C:\xampp\php\php.exe artisan aptoria:security-audit
C:\xampp\php\php.exe artisan serve
```

## First-run setup

Open the application in the browser. If it is not installed yet, normal pages redirect to `/setup`.

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

---

## Local test command

```powershell
cd "C:\xampp\htdocs\aptoria"
C:\xampp\php\php.exe artisan test
```

---

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
