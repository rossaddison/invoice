@echo off
:: Enhanced Invoice System Command Menu Batch Script

:: Set window title and ensure correct directory
title Invoice System Command Menu (Enhanced)
cd /d "%~dp0"

REM ================== ENVIRONMENT CHECKS ==================
:check_env
where php >nul 2>nul || (
    echo [ERROR] PHP not found in PATH. Please install PHP and retry.
    pause
    exit /b
)
where composer >nul 2>nul || (
    echo [ERROR] Composer not found in PATH. Please install Composer and retry.
    pause
    exit /b
)
where npm >nul 2>nul || (
    echo [ERROR] npm not found in PATH. Please install Node.js and retry.
    pause
    exit /b
)
goto menu

REM ================== MAIN MENU ==================
:menu
cls
echo ===============================================================
echo           INVOICE SYSTEM MAIN MENU
echo ===============================================================
echo [0]  Installation Menu             [6]   PHP Built-in 'serve'
echo [1]  Run PHP Psalm (Full)          [7]   user/create username password
echo [2]  Psalm on File                 [8]   user/assignRole role userId
echo [2a] Psalm on Directory            [9]   router/list
echo [2b] Clear Psalm's Cache           [10]  translator/translate
echo [2c] Psalm: Show Config/Plugins    [11]  invoice/items
echo [3]  Composer Outdated             [12]  invoice/setting/truncate
echo [3a] Composer why-not              [13]  invoice/generator/truncate
echo [3b] Composer Cache with Lock      [14]  invoice/inv/truncate1
echo [3c] Composer Validate             [15]  invoice/quote/truncate2
echo [3d] Composer Dump Autoload        [16]  invoice/salesorder/truncate3
echo [4]  Composer Update               [17]  invoice/nonuserrelated/truncate4
echo [4a] Node Modules Update           [18]  invoice/userrelated/truncate5
echo [4b] nvm-windows Install/Update    [19]  invoice/autoincrementsettooneafter/truncate6
echo [4c] Node: Audit, Clean, List      [20]  Exit
echo [5]  Require Checker               [21]  Exit to Current Directory
echo [5a] Codeception Tests
echo [5aa] Codeception Build
echo [5b] Rector See Changes
echo [5c] Rector Make Changes
echo [5d] PHP-CS-Fixer Dry Run
echo [5e] PHP-CS-Fixer Fix
echo [99] System Info / Diagnostics
echo =================================
set /p choice="Enter your choice [0-21,99]: "

REM ======== MENU COMMAND ROUTING ========
if "%choice%"=="0" goto installation_menu 
if "%choice%"=="1" goto psalm
if "%choice%"=="2" goto psalm_file
if "%choice%"=="2a" goto psalm_directory
if "%choice%"=="2b" goto psalm_clear_cache
if "%choice%"=="2c" goto psalm_config
if "%choice%"=="3" goto outdated
if "%choice%"=="3a" goto composerwhynot
if "%choice%"=="3b" goto composer_clear_cache_and_resolve_lock_conflicts
if "%choice%"=="3c" goto composer_validate
if "%choice%"=="3d" goto composer_dumpautoload
if "%choice%"=="4" goto composer_update
if "%choice%"=="4a" goto node_modules_update
if "%choice%"=="4b" goto nvm_install_or_update
if "%choice%"=="4c" goto node_audit
if "%choice%"=="5" goto require_checker
if "%choice%"=="5a" goto codeception_tests
if "%choice%"=="5aa" goto codeception_build
if "%choice%"=="5b" goto rector_see_changes
if "%choice%"=="5c" goto rector_make_changes
if "%choice%"=="5d" goto code_style_suggest_changes
if "%choice%"=="5e" goto code_style_make_changes
if "%choice%"=="6" goto serve
if "%choice%"=="7" goto user_create
if "%choice%"=="8" goto user_assignRole
if "%choice%"=="9" goto router_list
if "%choice%"=="10" goto translator_translate
if "%choice%"=="11" goto invoice_items
if "%choice%"=="12" goto confirm_warning_12
if "%choice%"=="13" goto confirm_warning_13
if "%choice%"=="14" goto confirm_warning_14
if "%choice%"=="15" goto confirm_warning_15
if "%choice%"=="16" goto confirm_warning_16
if "%choice%"=="17" goto confirm_warning_17
if "%choice%"=="18" goto confirm_warning_18
if "%choice%"=="19" goto confirm_warning_19
if "%choice%"=="20" goto exit
if "%choice%"=="21" goto exit_to_directory
if "%choice%"=="99" goto diagnostics
echo Invalid choice. Please try again.
pause
goto menu

REM ================== SYSTEM DIAGNOSTICS ==================
:diagnostics
echo .......... SYSTEM DIAGNOSTICS ..........
php -v
composer --version
npm -v
node -v
echo ------------ Composer Platform Check ------------
composer check-platform-reqs
echo ------------ Node List ------------
npm list --depth=0
pause
goto menu

REM ================== INSTALLATION MENU ==================
:installation_menu
echo Installation menu...
if exist install.bat (
    call install.bat
) else (
    echo [INFO] No install.bat found. Running 'composer install' and 'npm install'.
    composer install
    npm install
)
pause
goto menu

REM ================== PSALM ==================
:psalm
echo Running PHP Psalm...
php vendor/bin/psalm
pause
goto menu

:psalm_file
echo Running PHP Psalm on a specific file...
set /p file="File path (relative to root): "
if "%file%"=="" (echo No file specified.& pause& goto menu)
php vendor/bin/psalm "%file%"
pause
goto menu

:psalm_directory
echo Running PHP Psalm on a directory...
set /p DIR="Directory path (relative to root): "
if "%DIR%"=="" (echo No directory specified.& pause& goto menu)
php vendor/bin/psalm "%DIR%"
pause
goto menu

:psalm_clear_cache
echo Clearing Psalm's cache...
php vendor/bin/psalm --clear-cache
pause
goto menu

:psalm_config
echo Psalm Config & Plugins:
php vendor/bin/psalm --show-info || echo Psalm version does not support --show-info
pause
goto menu

REM ================== COMPOSER ==================
:outdated
echo Checking Composer Outdated...
composer outdated
pause
goto menu

:composerwhynot
set /p repo="Package name (e.g. vendor/package): "
set /p version="Version (e.g. 1.0.0): "
composer why-not %repo% %version%
pause
goto menu

:composer_clear_cache_and_resolve_lock_conflicts
echo Clearing Composer cache and resolving lock file conflicts...
composer clear-cache
composer update --lock
pause
goto menu

:composer_validate
echo Validating composer.json and composer.lock...
composer validate
pause
goto menu

:composer_dumpautoload
echo Regenerating Composer autoload files...
composer dump-autoload -o
pause
goto menu

:composer_update
echo Updating Composer...
composer update
pause
goto menu

REM ================== NODE/NPM TASKS ==================
:node_modules_update
echo Updating Node modules...
npx npm-check-updates -u
npm install
pause
goto menu

:nvm_install_or_update
echo Downloading latest nvm-windows installer...
powershell -Command "Invoke-WebRequest -Uri https://github.com/coreybutler/nvm-windows/releases/latest/download/nvm-setup.exe -OutFile nvm-setup.exe"
start /wait nvm-setup.exe /SILENT
del nvm-setup.exe
echo nvm-windows install/update complete.
pause
goto menu

:node_audit
echo Running npm audit...
npm audit
echo Running npm cache clean...
npm cache clean --force
echo Listing top-level npm packages...
npm list --depth=0
pause
goto menu

REM ================== COMPOSER/REQUIRE/CHECKER ==================
:require_checker
echo Running Composer Require Checker...
php -d memory_limit=512M vendor/bin/composer-require-checker
pause
goto menu

REM ================== CODECEPTION TESTS ==================
:codeception_tests
echo Running Codeception Tests...
php vendor/bin/codecept run
pause
goto menu

:codeception_build
echo Running Codeception Build...
php vendor/bin/codecept build
pause
goto menu

REM ================== PHP-CS-FIXER ==================
:code_style_suggest_changes
echo PHP-CS-Fixer Dry Run (see potential changes)...
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --dry-run --diff 
pause
goto menu

:code_style_make_changes
echo PHP-CS-Fixer Fix (apply changes)...
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php 
pause
goto menu

REM ================== RECTOR ==================
:rector_see_changes
echo Rector Dry Run (see proposed changes)...
php vendor/bin/rector process --dry-run --output-format=console
pause
goto menu

:rector_make_changes
echo Rector Make Changes (apply changes)...
php vendor/bin/rector
pause
goto menu

REM ================== YII COMMANDS ==================
:serve
echo Running PHP built-in server via Yii...
php yii serve
pause
goto menu

:user_create
set /p username="Username (e.g. admin): "
set /p password="Password (e.g. admin): "
if "%username%"=="" (echo No username specified.& pause& goto menu)
if "%password%"=="" (echo No password specified.& pause& goto menu)
php yii user/create "%username%" "%password%"
pause
goto menu

:user_assignRole
set /p role="Role (e.g. admin): "
set /p userId="User ID (e.g. 1): "
if "%role%"=="" (echo No role specified.& pause& goto menu)
if "%userId%"=="" (echo No user ID specified.& pause& goto menu)
php yii user/assignRole "%role%" "%userId%"
pause
goto menu

:router_list
php yii router/list
pause
goto menu

:translator_translate
set /p sourceText="Source text: "
set /p targetLanguage="Target language code (e.g. fr): "
if "%sourceText%"=="" (echo No source text specified.& pause& goto menu)
if "%targetLanguage%"=="" (echo No target language specified.& pause& goto menu)
php yii translator/translate "%sourceText%" "%targetLanguage%"
pause
goto menu

:invoice_items
php yii invoice/items
pause
goto menu

REM ================== DANGEROUS COMMANDS (Confirmation) ==================
:confirm_warning_12
call :confirm_delete "invoice_setting_truncate"
goto menu
:confirm_warning_13
call :confirm_delete "invoice_generator_truncate"
goto menu
:confirm_warning_14
call :confirm_delete "invoice_inv_truncate1"
goto menu
:confirm_warning_15
call :confirm_delete "invoice_quote_truncate2"
goto menu
:confirm_warning_16
call :confirm_delete "invoice_salesorder_truncate3"
goto menu
:confirm_warning_17
call :confirm_delete "invoice_nonuserrelated_truncate4"
goto menu
:confirm_warning_18
call :confirm_delete "invoice_userrelated_truncate5"
goto menu
:confirm_warning_19
call :confirm_delete "invoice_autoincrementsettooneafter_truncate6"
goto menu

:confirm_delete
echo You are about to delete sensitive data! Are you sure? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto %1
echo Cancelled.
pause
goto menu

REM ================== ACTUAL TRUNCATE COMMANDS ==================
:invoice_setting_truncate
php yii invoice/setting/truncate
pause
goto menu
:invoice_generator_truncate
php yii invoice/generator/truncate
pause
goto menu
:invoice_inv_truncate1
php yii invoice/inv/truncate1
pause
goto menu
:invoice_quote_truncate2
php yii invoice/quote/truncate2
pause
goto menu
:invoice_salesorder_truncate3
php yii invoice/salesorder/truncate3
pause
goto menu
:invoice_nonuserrelated_truncate4
php yii invoice/nonuserrelated/truncate4
pause
goto menu
:invoice_userrelated_truncate5
php yii invoice/userrelated/truncate5
pause
goto menu
:invoice_autoincrementsettooneafter_truncate6
php yii invoice/autoincrementsettooneafter/truncate6
pause
goto menu

REM ================== EXIT HANDLERS ==================
:exit_to_directory
echo Returning to the current directory. Goodbye!
cmd

:exit
echo Exiting. Goodbye!
pause
exit