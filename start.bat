@echo off
rem ===========================================================================
rem  MAT LEGAL NETWORK - one-click local launcher for Windows 10/11
rem
rem  Double-click this file (or run "start.bat" in a terminal). It will:
rem    1. use your PHP 8.3+ if it has the needed extensions, otherwise download
rem       a private copy of PHP into .tools\php (nothing is installed system-wide)
rem    2. download Composer into .tools and install the PHP packages
rem    3. create .env, an SQLite database, run migrations and add demo data
rem    4. start the site and open it in your browser
rem
rem  Running it again is safe: finished steps are skipped.
rem
rem    start.bat          start the site
rem    start.bat reset    wipe the local database, add fresh demo data, start
rem
rem  Optional settings (set before running):  MAT_PORT=8000  MAT_NO_BROWSER=1
rem  MAT_NO_PAUSE=1  MAT_PORTABLE_PHP=1 (always use the private PHP copy)
rem
rem  AI spam screening of enquiries (optional): put ANTHROPIC_API_KEY=sk-ant-...
rem  in .env (never commit it) and run start.bat again. See README.md.
rem ===========================================================================
setlocal EnableExtensions DisableDelayedExpansion
cd /d "%~dp0"
title MAT LEGAL NETWORK - local server

set "ROOT=%~dp0"
set "TOOLS=%ROOT%.tools"
set "PHP_DIR=%TOOLS%\php"
set "COMPOSER_PHAR=%TOOLS%\composer.phar"
set "PHP="
if not defined MAT_PORT set "MAT_PORT=8000"
set "MODE=%~1"

echo.
echo  MAT LEGAL NETWORK - local launcher
echo  ==================================
echo.

where curl.exe >nul 2>&1 || (set "ERR=curl.exe was not found. Windows 10 version 1803 or newer is required." & goto :fail)
where tar.exe  >nul 2>&1 || (set "ERR=tar.exe was not found. Windows 10 version 1803 or newer is required." & goto :fail)
if not exist "%TOOLS%" mkdir "%TOOLS%"

rem ---------------------------------------------------------------- 1. PHP --
echo [1/5] Checking PHP...
if exist "%PHP_DIR%\php.exe" (
    set "PHP=%PHP_DIR%\php.exe"
    goto :php_check
)
if "%MAT_PORTABLE_PHP%"=="1" goto :php_download
where php >nul 2>&1 || goto :php_download
for /f "delims=" %%P in ('where php') do (
    set "PHP=%%P"
    goto :php_system
)
:php_system
call :check_php "%PHP%" >nul 2>&1 && goto :php_ok
echo       Your PHP at "%PHP%" is older than 8.3 or misses extensions.
echo       Using a private PHP copy instead (your installed PHP is not changed).
set "PHP="

:php_download
echo       Downloading PHP 8.4 for Windows (about 30 MB, first run only)...
if exist "%TOOLS%\php.zip" del /q "%TOOLS%\php.zip"
curl.exe -fL --retry 3 -o "%TOOLS%\php.zip" "https://windows.php.net/downloads/releases/latest/php-8.4-nts-Win32-vs17-x64-latest.zip"
if errorlevel 1 (
    echo       PHP 8.4 download failed, trying PHP 8.3...
    curl.exe -fL --retry 3 -o "%TOOLS%\php.zip" "https://windows.php.net/downloads/releases/latest/php-8.3-nts-Win32-vs16-x64-latest.zip"
)
if errorlevel 1 (set "ERR=Could not download PHP. Check your internet connection and run start.bat again." & goto :fail)
if exist "%PHP_DIR%" rmdir /s /q "%PHP_DIR%"
mkdir "%PHP_DIR%"
tar.exe -xf "%TOOLS%\php.zip" -C "%PHP_DIR%"
if errorlevel 1 (set "ERR=Could not unpack the PHP download. Delete the .tools folder and try again." & goto :fail)
del /q "%TOOLS%\php.zip"
(
    echo ; Written by start.bat for local development.
    echo extension_dir="%PHP_DIR%\ext"
    echo extension=curl
    echo extension=fileinfo
    echo extension=gd
    echo extension=intl
    echo extension=mbstring
    echo extension=openssl
    echo extension=pdo_mysql
    echo extension=pdo_sqlite
    echo extension=sqlite3
    echo extension=zip
    echo memory_limit=512M
    echo upload_max_filesize=10M
    echo post_max_size=20M
    echo max_execution_time=120
    echo date.timezone=Asia/Kolkata
) > "%PHP_DIR%\php.ini"
set "PHP=%PHP_DIR%\php.exe"

:php_check
call :check_php "%PHP%" || (set "ERR=PHP at %PHP% is not usable. Delete the .tools folder and run start.bat again." & goto :fail)

:php_ok
for /f "delims=" %%V in ('""%PHP%" -r "echo PHP_VERSION;""') do echo       Using PHP %%V  ^(%PHP%^)

rem ----------------------------------------------------------- 2. Composer --
echo [2/5] Checking PHP packages...
"%PHP%" scripts\local\setup.php env || (set "ERR=Could not prepare the .env file." & goto :fail)
"%PHP%" scripts\local\setup.php needs-composer
if errorlevel 1 (
    echo       Packages are up to date.
    goto :composer_done
)
if not exist "%COMPOSER_PHAR%" (
    echo       Downloading Composer...
    curl.exe -fL --retry 3 -o "%COMPOSER_PHAR%" "https://getcomposer.org/download/latest-stable/composer.phar"
    if errorlevel 1 (
        if exist "%COMPOSER_PHAR%" del /q "%COMPOSER_PHAR%"
        set "ERR=Could not download Composer. Check your internet connection and run start.bat again."
        goto :fail
    )
)
echo       Installing PHP packages (first run takes a few minutes)...
"%PHP%" "%COMPOSER_PHAR%" install --no-interaction --prefer-dist --no-progress
if errorlevel 1 (set "ERR=composer install failed. See the messages above." & goto :fail)
"%PHP%" scripts\local\setup.php composer-done

:composer_done

rem ----------------------------------------------------------- 3. Database --
echo [3/5] Preparing the database...
if /i "%MODE%"=="reset" (
    "%PHP%" scripts\local\setup.php reset || (set "ERR=Database reset failed. See the messages above." & goto :fail)
) else (
    "%PHP%" scripts\local\setup.php app || (set "ERR=Database setup failed. See the messages above." & goto :fail)
)

rem -------------------------------------------------------------- 4. Port --
echo [4/5] Finding a free port...
set "PORT="
for /f "delims=" %%N in ('""%PHP%" scripts\local\setup.php free-port %MAT_PORT%"') do set "PORT=%%N"
if not defined PORT (set "ERR=No free port found near %MAT_PORT%." & goto :fail)
if not "%PORT%"=="%MAT_PORT%" echo       Port %MAT_PORT% is busy, using %PORT% instead.
set "APP_URL=http://127.0.0.1:%PORT%"

rem ------------------------------------------------------------- 5. Serve --
echo [5/5] Starting the site...
echo.
echo  ---------------------------------------------------------------
echo   Site   : http://127.0.0.1:%PORT%
echo   Login  : http://127.0.0.1:%PORT%/login
echo   Admin  : http://127.0.0.1:%PORT%/admin
echo   Blog   : http://127.0.0.1:%PORT%/blog   (editor login: editor@example.com / Editor@12345)
echo   E-mails are written to storage\logs\laravel.log
echo   Press Ctrl+C in this window to stop the site.
echo  ---------------------------------------------------------------
echo.
if not "%MAT_NO_BROWSER%"=="1" start "" /b "%PHP%" scripts\local\setup.php open %PORT%
"%PHP%" artisan serve --host=127.0.0.1 --port=%PORT%
endlocal
exit /b 0

rem --------------------------------------------------------------- helpers --
:check_php
rem Exit 0 when the given PHP is 8.3+ and has every extension the site needs.
"%~1" -r "if (version_compare(PHP_VERSION, '8.3.0', '<')) { echo 'PHP 8.3+ needed, found '.PHP_VERSION; exit(1); } $m = array_filter(['ctype','curl','dom','fileinfo','filter','intl','mbstring','openssl','pdo_sqlite','session','tokenizer','xml','zip'], fn ($e) => ! extension_loaded($e)); if ($m) { echo 'Missing PHP extensions: '.implode(', ', $m); exit(1); }"
exit /b %errorlevel%

:fail
echo.
echo  ERROR: %ERR%
echo.
if not "%MAT_NO_PAUSE%"=="1" pause
endlocal
exit /b 1
