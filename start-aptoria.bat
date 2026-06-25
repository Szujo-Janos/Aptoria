@echo off
setlocal
cd /d "%~dp0"

echo ========================================
echo Aptoria Portable Runtime
echo ========================================

if not exist "storage" mkdir "storage"
if not exist "storage\app" mkdir "storage\app"
if not exist "storage\framework" mkdir "storage\framework"
if not exist "storage\framework\cache" mkdir "storage\framework\cache"
if not exist "storage\framework\cache\data" mkdir "storage\framework\cache\data"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\logs" mkdir "storage\logs"
if not exist "bootstrap" mkdir "bootstrap"
if not exist "bootstrap\cache" mkdir "bootstrap\cache"
if not exist "database" mkdir "database"
if not exist "database\database.sqlite" type nul > "database\database.sqlite"

set "APTORIA_LICENSE_REQUIRED=true"
if not defined APTORIA_LICENSE_FILE set "APTORIA_LICENSE_FILE=storage/app/aptoria-license.json"
if not defined APTORIA_LICENSE_PUBLIC_KEY_PATH set "APTORIA_LICENSE_PUBLIC_KEY_PATH=storage/app/license-public.pem"

if exist "%~dp0php\php.exe" (
    set "PHP_EXE=%~dp0php\php.exe"
) else if exist "C:\xampp\php\php.exe" (
    set "PHP_EXE=C:\xampp\php\php.exe"
) else (
    set "PHP_EXE=php"
)

echo PHP: %PHP_EXE%
echo License file: %APTORIA_LICENSE_FILE%

if not exist "%APTORIA_LICENSE_FILE%" (
    echo.
    echo WARNING: License file is missing. Aptoria will open the license error page until a valid signed license is installed.
    echo Expected: %APTORIA_LICENSE_FILE%
    echo.
    echo To generate a request for the license issuer run:
    echo get-license-request.bat
    echo.
)

if not exist ".env" if exist ".env.example" copy ".env.example" ".env" > nul

%PHP_EXE% artisan optimize:clear
%PHP_EXE% artisan migrate --force

echo.
echo Starting Aptoria on http://127.0.0.1:8000
echo Close this window to stop the runtime.
echo.
start "" "http://127.0.0.1:8000"
%PHP_EXE% artisan serve --host=127.0.0.1 --port=8000

endlocal
