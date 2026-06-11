$ZipPath = "E:\GitHub projects\Aptoria\aptoria-1.1.27.zip"
$TempPath = "E:\GitHub projects\Aptoria\_temp_aptoria_1.1.27"
$ProjectRoot = "C:\xampp\htdocs\aptoria"

Remove-Item $TempPath -Recurse -Force -ErrorAction SilentlyContinue

Expand-Archive -Path $ZipPath -DestinationPath $TempPath -Force

Copy-Item "$TempPath\aptoria-1.1.27\*" $ProjectRoot -Recurse -Force

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
