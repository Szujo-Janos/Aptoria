# Install / update on Windows XAMPP

Use this pattern for local installation or replacement from the ZIP package.

```powershell
$ZipPath = "E:\Aptoria\aptoria-0.0.80.zip"
$TempPath = "E:\Aptoria\_temp_aptoria_0.0.80"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue
Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force
Copy-Item "$TempPath\aptoria-0.0.80\*" $ProjectRoot -Recurse -Force

cd $ProjectRoot
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\scripts\update-windows-xampp.ps1

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate

C:\xampp\php\php.exe artisan test

C:\xampp\php\php.exe artisan serve
```

Then open:

```text
http://127.0.0.1:8000/setup
```

Complete setup. The installer creates `storage/app/installed.lock` after setup.

## Release ZIP exclusions

Do not commit or ship runtime/private files:

```text
.env
vendor/
database/database.sqlite
storage/app/installed.lock
storage/app/setup-token.txt
storage/app/aptoria-license.json
storage/app/license-*.pem
storage/app/*lease*.json
storage/app/license-install-id
bootstrap/cache/
public/storage/
```
