# Install / update on Windows XAMPP

Use this pattern for local installation. For the clean rebuild phase, delete the old target folder before copying the new ZIP so no old migrations/views remain.

```powershell
$ZipPath = "E:\Aptoria\aptoria-0.0.63.zip"
$TempPath = "E:\Aptoria\_temp_aptoria_0.0.63"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item $ProjectRoot -Recurse -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Path $ProjectRoot -Force

Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force
Copy-Item "$TempPath\aptoria-0.0.63\*" $ProjectRoot -Recurse -Force

cd $ProjectRoot
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\scripts\update-windows-xampp.ps1

C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan view:clear
C:\xampp\php\php.exe artisan config:clear
C:\xampp\php\php.exe artisan route:clear
C:\xampp\php\php.exe artisan migrate --force
C:\xampp\php\php.exe artisan serve
```

Then open:

```text
http://127.0.0.1:8000/setup
```

Complete setup. The installer creates `storage/app/installed.lock` after setup.
