# Install / update on Windows XAMPP

This guide applies to the **Aptoria v0.0.53 evidence-first rebuild**.

For replacement from the legacy `1.1.34` line, use a clean target folder. Do not copy the new files over the old folder without deleting old runtime/cache/migration leftovers.

## Clean replacement from ZIP

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

## GitHub clone workflow

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

## What the helper script does

`.\scripts\update-windows-xampp.ps1` prepares local runtime state that must not be committed:

- copies `.env.example` to `.env` when missing;
- creates `bootstrap/cache` locally;
- creates SQLite database file when missing;
- creates required storage/cache/session/view/log folders;
- runs Composer install when `vendor/autoload.php` is missing;
- generates `APP_KEY`;
- clears caches;
- runs migrations;
- runs the basic Aptoria health check.

## First-run setup

Until setup is completed and locked, normal application pages redirect to `/setup`.

The setup lock file is generated locally:

```text
storage/app/installed.lock
```

Never commit this file.

## Runtime files not committed

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
