@echo off
setlocal
cd /d "%~dp0"

echo ========================================
echo Aptoria License Request
 echo ========================================

if exist "%~dp0php\php.exe" (
    set "PHP_EXE=%~dp0php\php.exe"
) else if exist "C:\xampp\php\php.exe" (
    set "PHP_EXE=C:\xampp\php\php.exe"
) else (
    set "PHP_EXE=php"
)

if not exist ".env" if exist ".env.example" copy ".env.example" ".env" > nul

set "REQUEST_FILE=license-request.json"
%PHP_EXE% artisan aptoria:license-request --output=%REQUEST_FILE%

echo.
echo License request generated:
echo %CD%\%REQUEST_FILE%
echo.
echo Send this file to the license issuer. It is not a license file.
echo.
pause
endlocal
