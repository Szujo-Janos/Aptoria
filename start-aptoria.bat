@echo off
setlocal EnableExtensions EnableDelayedExpansion
cd /d "%~dp0"

title Aptoria Installer

set "INSTALLER_VERSION=1.0.0"
set "HOST=127.0.0.1"
set "PORT="
set "APP_URL="
set "PHP_EXE="
set "COMPOSER_EXE="
set "LOG_FILE=%CD%\install-aptoria.log"

> "%LOG_FILE%" echo Aptoria Installer Log
>> "%LOG_FILE%" echo Version: %INSTALLER_VERSION%
>> "%LOG_FILE%" echo Started: %DATE% %TIME%
>> "%LOG_FILE%" echo Folder: %CD%
>> "%LOG_FILE%" echo.

echo.
echo ========================================
echo  Aptoria Installer %INSTALLER_VERSION%
echo ========================================
echo.
echo  Ez a script ellenorzi es beallitja a
echo  szukseges futtatokornyzetet, majd
echo  elinditja az Aptoriat.
echo.
echo  Log: %LOG_FILE%
echo ========================================
echo.

REM ----------------------------------------
echo [1/9] Projektfajlok ellenorzese...
REM ----------------------------------------

if not exist "artisan" (
    call :fail "artisan nem talalhato. Futtasd ezt a BAT-ot az Aptoria projekt gyokermappajabol."
)
if not exist "composer.json" (
    call :fail "composer.json nem talalhato. Ez nem az Aptoria projekt gyokere."
)
if not exist ".env.example" (
    call :fail ".env.example nem talalhato. Hianyzik a konfiguracios sablon."
)

echo OK: Projektfajlok megvannak.
>> "%LOG_FILE%" echo [1/9] Projektfajlok: OK


REM ----------------------------------------
echo.
echo [2/9] PHP keresese...
REM ----------------------------------------

if exist "%CD%\php\php.exe" (
    set "PHP_EXE=%CD%\php\php.exe"
    echo Talalt: beagyazott PHP ^(%CD%\php\php.exe^)
    goto :php_found
)

if exist "C:\xampp\php\php.exe" (
    set "PHP_EXE=C:\xampp\php\php.exe"
    echo Talalt: XAMPP PHP ^(C:\xampp\php\php.exe^)
    goto :php_found
)

if exist "C:\laragon\bin\php" (
    for /d %%D in ("C:\laragon\bin\php\php-*") do (
        if exist "%%~fD\php.exe" if not defined PHP_EXE (
            set "PHP_EXE=%%~fD\php.exe"
            echo Talalt: Laragon PHP ^(%%~fD\php.exe^)
        )
    )
    if defined PHP_EXE goto :php_found
)

for /f "delims=" %%P in ('where php 2^>nul') do (
    if not defined PHP_EXE (
        set "PHP_EXE=%%P"
        echo Talalt: globalis PHP ^(%%P^)
    )
)

if not defined PHP_EXE (
    echo.
    echo HIBA: PHP nem talalhato a szamitogepen.
    echo.
    echo Telepitesi lehetosegek:
    echo   - XAMPP: https://www.apachefriends.org/
    echo   - Laragon: https://laragon.org/
    echo   - PHP kozvetlenul: https://windows.php.net/download/
    echo.
    echo Telepites utan futtasd ujra ezt a BAT-ot.
    call :fail "PHP nem talalhato."
)

:php_found
>> "%LOG_FILE%" echo [2/9] PHP: %PHP_EXE%

"%PHP_EXE%" -v >nul 2>&1
if errorlevel 1 (
    call :fail "PHP megtalalhato, de nem futtatható: %PHP_EXE%"
)

set "PHP_VER_TMP=%TEMP%\aptoria_phpver_%RANDOM%.txt"
"%PHP_EXE%" -r "echo PHP_VERSION_ID;" > "%PHP_VER_TMP%" 2>nul
set /p PHP_VER_ID=<"%PHP_VER_TMP%"
del "%PHP_VER_TMP%" >nul 2>&1

if not defined PHP_VER_ID (
    set "PHP_VER_TMP2=%TEMP%\aptoria_phpver2_%RANDOM%.txt"
    "%PHP_EXE%" -v > "%PHP_VER_TMP2%" 2>nul
    for /f "tokens=2 delims= " %%V in ('findstr /B /C:"PHP " "%PHP_VER_TMP2%"') do (
        if not defined PHP_VER set "PHP_VER=%%V"
    )
    del "%PHP_VER_TMP2%" >nul 2>&1
    if not defined PHP_VER (
        call :fail "PHP verzio nem olvasható. Ellenorizd a XAMPP telepitest."
    )
    echo OK: PHP %PHP_VER%
    goto :php_ver_ok
)

if %PHP_VER_ID% LSS 80200 (
    call :fail "Az Aptoria PHP 8.2+ verziót igenyel. Telepits ujabb PHP-t."
)

set "PHP_VER_TMP3=%TEMP%\aptoria_phpver3_%RANDOM%.txt"
"%PHP_EXE%" -r "echo PHP_VERSION;" > "%PHP_VER_TMP3%" 2>nul
set /p PHP_VER=<"%PHP_VER_TMP3%"
del "%PHP_VER_TMP3%" >nul 2>&1
echo OK: PHP %PHP_VER%

:php_ver_ok


REM ----------------------------------------
echo.
echo [3/9] PHP extensionok ellenorzese...
REM ----------------------------------------

set "PHP_MODULES_TMP=%TEMP%\aptoria_modules_%RANDOM%.txt"
set "MISSING_EXT="

"%PHP_EXE%" -m > "%PHP_MODULES_TMP%" 2>nul

for %%E in (openssl pdo pdo_sqlite sqlite3 mbstring tokenizer xml ctype fileinfo json dom session filter) do (
    findstr /I /X /C:"%%E" "%PHP_MODULES_TMP%" >nul 2>&1
    if errorlevel 1 (
        set "MISSING_EXT=!MISSING_EXT! %%E"
    )
)

del "%PHP_MODULES_TMP%" >nul 2>&1

if defined MISSING_EXT (
    echo.
    echo HIBA: Hianyzó PHP extension^(ok^):!MISSING_EXT!
    echo.
    echo Engedélyezd oket a php.ini fajlban, majd futtasd ujra ezt a BAT-ot.
    echo PHP.ini helye: 
    "%PHP_EXE%" -r "echo php_ini_loaded_file();"
    call :fail "Hianyzó PHP extensionok."
)

echo OK: Minden szukseges PHP extension megvan.
>> "%LOG_FILE%" echo [3/9] PHP extensionok: OK


REM ----------------------------------------
echo.
echo [4/9] Composer keresese / letoltese...
REM ----------------------------------------

for /f "delims=" %%C in ('where composer 2^>nul') do (
    if not defined COMPOSER_EXE set "COMPOSER_EXE=%%C"
)

if defined COMPOSER_EXE (
    echo OK: Globalis Composer talalhato: %COMPOSER_EXE%
    >> "%LOG_FILE%" echo [4/9] Composer: %COMPOSER_EXE%
    goto :composer_found
)

if exist "composer.phar" (
    echo OK: Helyi composer.phar talalhato.
    set "COMPOSER_EXE=phar"
    >> "%LOG_FILE%" echo [4/9] Composer: helyi composer.phar
    goto :composer_found
)

echo Composer nem talalhato. Letoltes folyamatban...
>> "%LOG_FILE%" echo [4/9] Composer letoltese...

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -UseBasicParsing -Uri 'https://getcomposer.org/download/latest-stable/composer.phar' -OutFile 'composer.phar'"

if errorlevel 1 (
    call :fail "Composer letoltese sikertelen. Ellenorizd az internetkapcsolatot."
)

if not exist "composer.phar" (
    call :fail "Composer letoltes utan sem talalhato a composer.phar."
)

echo OK: composer.phar sikeresen letoltve.
set "COMPOSER_EXE=phar"
>> "%LOG_FILE%" echo [4/9] Composer: letoltott composer.phar

:composer_found


REM ----------------------------------------
echo.
echo [5/9] Composer fuggosegek telepitese...
REM ----------------------------------------

if exist "vendor\autoload.php" (
    echo OK: vendor/autoload.php mar letezik, kihagyjuk.
    >> "%LOG_FILE%" echo [5/9] Composer: mar telepitve
    goto :composer_done
)

echo vendor/autoload.php nem talalhato. Composer install fut...
echo Ez par percig is eltarthat elso alkalommal.
echo.

if "%COMPOSER_EXE%"=="phar" (
    "%PHP_EXE%" composer.phar install --no-interaction --prefer-dist --no-dev --optimize-autoloader
) else (
    call "%COMPOSER_EXE%" install --no-interaction --prefer-dist --no-dev --optimize-autoloader
)

if errorlevel 1 (
    call :fail "composer install sikertelen. Ellenorizd az internetkapcsolatot es a PHP verziót."
)

if not exist "vendor\autoload.php" (
    call :fail "Composer lefutott, de a vendor/autoload.php meg mindig hianyzik."
)

echo OK: Composer fuggosegek telepitve.
>> "%LOG_FILE%" echo [5/9] Composer install: OK

:composer_done


REM ----------------------------------------
echo.
echo [6/9] Futtatokornyzet elokeszitese...
REM ----------------------------------------

for %%D in (
    "storage"
    "storage\app"
    "storage\framework"
    "storage\framework\cache"
    "storage\framework\cache\data"
    "storage\framework\sessions"
    "storage\framework\views"
    "storage\logs"
    "bootstrap"
    "bootstrap\cache"
    "database"
) do (
    if not exist "%%~D" (
        mkdir "%%~D"
        >> "%LOG_FILE%" echo Letrehozva: %%~D
    )
)

echo OK: Mappak elokeszitve.
>> "%LOG_FILE%" echo [6/9] Mappak: OK


REM ----------------------------------------
echo.
echo [7/9] Port keresese...
REM ----------------------------------------

for /L %%P in (8000,1,8010) do (
    if not defined PORT (
        netstat -ano | findstr /R /C:":%%P .*LISTENING" >nul 2>&1
        if errorlevel 1 set "PORT=%%P"
    )
)

if not defined PORT (
    call :fail "Nem talalhato szabad port 8000-8010 kozott."
)

set "APP_URL=http://%HOST%:%PORT%"
echo OK: Port: %PORT% ^(%APP_URL%^)
>> "%LOG_FILE%" echo [7/9] Port: %PORT%


REM ----------------------------------------
echo.
echo [8/9] .env es adatbazis elokeszitese...
REM ----------------------------------------

set "DB_NEW=0"

if not exist "database\database.sqlite" (
    type nul > "database\database.sqlite"
    set "DB_NEW=1"
    echo SQLite adatbazis letrehozva.
) else (
    for %%A in ("database\database.sqlite") do (
        if %%~zA EQU 0 set "DB_NEW=1"
    )
    echo SQLite adatbazis mar letezik.
)

set "DB_PATH=%CD%\database\database.sqlite"

if not exist ".env" (
    echo .env letrehozasa .env.example alapjan...
    copy ".env.example" ".env" >nul
    
    REM APP_URL beallitasa
    powershell -NoProfile -ExecutionPolicy Bypass -Command ^
        "(Get-Content '.env') -replace '^APP_URL=.*', 'APP_URL=%APP_URL%' | Set-Content '.env'"
    
    REM DB_DATABASE beallitasa abszolut utvonalra
    set "DB_PATH_FWD=%DB_PATH:\=/%"
    powershell -NoProfile -ExecutionPolicy Bypass -Command ^
        "(Get-Content '.env') -replace '^DB_DATABASE=.*', 'DB_DATABASE=\"!DB_PATH_FWD!\"' | Set-Content '.env'"

    echo OK: .env letrehozva.
    >> "%LOG_FILE%" echo [8/9] .env: uj fajl letrehozva
) else (
    echo .env mar letezik, APP_URL frissitese...
    powershell -NoProfile -ExecutionPolicy Bypass -Command ^
        "(Get-Content '.env') -replace '^APP_URL=.*', 'APP_URL=%APP_URL%' | Set-Content '.env'"
    >> "%LOG_FILE%" echo [8/9] .env: mar letezett, APP_URL frissitve
)

REM APP_KEY generalas ha hianyzik
findstr /B /C:"APP_KEY=base64:" ".env" >nul 2>&1
if errorlevel 1 (
    echo APP_KEY generalasa...
    "%PHP_EXE%" artisan key:generate --force
    if errorlevel 1 call :fail "APP_KEY generalis sikertelen."
) else (
    echo APP_KEY: mar be van allitva.
)

REM Cache tisztitas
echo Cache tisztitasa...
"%PHP_EXE%" artisan optimize:clear >nul 2>&1

REM Migracio
echo Adatbazis migracioк futtatasa...
"%PHP_EXE%" artisan migrate --force
if errorlevel 1 call :fail "Adatbazis migracio sikertelen."

REM Seed csak uj adatbazisnál
if "%DB_NEW%"=="1" (
    echo Kezdeti adatok betoltese ^(seed^)...
    "%PHP_EXE%" artisan db:seed --force
    if errorlevel 1 (
        echo FIGYELMEZETES: Seed sikertelen. Az app futhat, de demo adatok hianyzhatnak.
        >> "%LOG_FILE%" echo WARN: db:seed sikertelen
    )
)

REM installed.lock
if not exist "storage\app\installed.lock" (
    > "storage\app\installed.lock" echo installed_at=%DATE% %TIME%
    >> "%LOG_FILE%" echo installed.lock letrehozva
)

REM Storage link
"%PHP_EXE%" artisan storage:link >nul 2>&1

echo OK: Kornyzet elokeszitve.
>> "%LOG_FILE%" echo [8/9] Kornyzet: OK


REM ----------------------------------------
echo.
echo [9/9] Aptoria inditasa...
REM ----------------------------------------

echo.
echo ========================================
echo  Aptoria kesz!
echo ========================================
echo.
echo  URL: %APP_URL%
echo.
echo  A bongeszo par masodperc mulva megnyilik.
echo  A szerver leallitasahoz zard be ezt az ablakot.
echo ========================================
echo.

>> "%LOG_FILE%" echo Aptoria indul: %APP_URL%

start "" /b cmd /c "timeout /t 3 >nul & start """" ""%APP_URL%"""

"%PHP_EXE%" artisan serve --host=%HOST% --port=%PORT%

echo.
echo Aptoria szerver leall.
pause
endlocal
exit /b 0


:fail
echo.
echo ========================================
echo  HIBA
echo ========================================
echo  %~1
echo.
echo  Log fajl: %LOG_FILE%
echo ========================================
echo.
>> "%LOG_FILE%" echo HIBA: %~1
pause
endlocal
exit /b 1
