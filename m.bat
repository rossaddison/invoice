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
echo ======================================================================================
echo                               INVOICE SYSTEM MAIN MENU
echo ======================================================================================
echo [0]  Installation Menu                         [5a]  Codeception Tests
echo [1]  Run PHP Psalm (Full)                      [5aa] Codeception Build
echo [2]  Psalm on File                             [5b]  Rector See Changes
echo [2a] Psalm on Directory                        [5c]  Rector Make Changes
echo [2b] Clear Psalm's Cache                       [5d]  PHP-CS-Fixer Dry Run
echo [2c] Psalm: Show Config/Plugins                [5e]  PHP-CS-Fixer Fix
echo [2d] Public Assets Clear (Safe)                [5f]  Snyk Security Check (Quick)
echo [3]  Composer Outdated                         [5g]  Snyk Security Check (Full)
echo [3a] Composer why-not                          [5h]  Snyk Security Dependencies
echo [3b] Composer Cache with Lock                  [5i]  Snyk Security Code File Check
echo [3c] Composer Validate                         [5j]  Snyk Security Summary (Issues Count)
echo [3d] Composer Dump Autoload                    [5k]  Snyk Security JSON Output			
echo [3e] Composer Audit                            [5l]  PHPCS: Check 85-char line length
echo [4]  Composer Update                           [5m]  PHPCS: Check specific file
echo [4a] Node Modules Update                       [5n]  PHPCS: Check specific directory
echo [4b] nvm-windows Install/Update                [5o]  PHPCS: Detailed report
echo [4c] Node: Audit, Clean, List                  [6]   PHP Built-in 'serve'
echo [4d] npm: Check Outdated                       [7]   user/create username password
echo [4e] npm: Safe Update (patch only)             [8]   user/assignRole role userId
echo [4f] npm: Minor Update (minor versions)        [9]   router/list
echo [4g] npm: Major Update (interactive)           [10]  translator/translate
echo [4h] npm: ES2024 Feature Verification          [11]  invoice/items
echo [4i] TypeScript Build (Production)             [12]  invoice/setting/truncate
echo [4j] TypeScript Build (Development)            [13]  invoice/generator/truncate
echo [4k] TypeScript Watch Mode                     [14]  invoice/inv/truncate1
echo [4l] TypeScript Type Check                     [15]  invoice/quote/truncate2
echo [4m] TypeScript Lint                           [16]  invoice/salesorder/truncate3
echo [4n] TypeScript Format                         [17]  invoice/nonuserrelated/truncate4
echo [4o] npm run build                             [18]  invoice/userrelated/truncate5
echo [4p] Angular: Install Dependencies             [19]  invoice/autoincrementsettooneafter/truncate6
echo [4q] Angular: Serve Development                [4r]  Angular: Build Production
echo [4s] Angular: Generate Component               [4t]  Angular: Lint Check
echo [5]  Require Checker                           [20]  GitHub CLI: Install
echo [99] System Info / Diagnostics                 [21]  GitHub CLI: Auth Status
echo                                                 [22]  GitHub CLI: Copilot Version
echo                                                 [23]  Exit
echo                                                 [24]  Exit to Current Directory
echo =================================                     
set /p choice="Enter your choice [0-24,99]: "

REM ======== MENU COMMAND ROUTING ========
if "%choice%"=="0" goto installation_menu 
if "%choice%"=="1" goto psalm
if "%choice%"=="2" goto psalm_file
if "%choice%"=="2a" goto psalm_directory
if "%choice%"=="2b" goto psalm_clear_cache
if "%choice%"=="2c" goto psalm_config
if "%choice%"=="2d" goto public_assets_clear
if "%choice%"=="3" goto composer_outdated
if "%choice%"=="3a" goto composer_whynot
if "%choice%"=="3b" goto composer_clear_cache_and_resolve_lock_conflicts
if "%choice%"=="3c" goto composer_validate
if "%choice%"=="3d" goto composer_dumpautoload
if "%choice%"=="3e" goto composer_audit
if "%choice%"=="4" goto composer_update
if "%choice%"=="4a" goto node_modules_update
if "%choice%"=="4b" goto nvm_install_or_update
if "%choice%"=="4c" goto node_audit
if "%choice%"=="4d" goto npm_check_outdated
if "%choice%"=="4e" goto npm_safe_update
if "%choice%"=="4f" goto npm_minor_update
if "%choice%"=="4g" goto npm_major_update
if "%choice%"=="4h" goto npm_es2024_verify
if "%choice%"=="4i" goto typescript_build_prod
if "%choice%"=="4j" goto typescript_build_dev
if "%choice%"=="4k" goto typescript_watch
if "%choice%"=="4l" goto typescript_type_check
if "%choice%"=="4m" goto typescript_lint
if "%choice%"=="4n" goto typescript_format
if "%choice%"=="4o" goto npm_run_build
if "%choice%"=="4p" goto angular_install_deps
if "%choice%"=="4q" goto angular_serve
if "%choice%"=="4r" goto angular_build
if "%choice%"=="4s" goto angular_generate_component
if "%choice%"=="4t" goto angular_lint
if "%choice%"=="5" goto require_checker
if "%choice%"=="5a" goto codeception_tests
if "%choice%"=="5aa" goto codeception_build
if "%choice%"=="5b" goto rector_see_changes
if "%choice%"=="5c" goto rector_make_changes
if "%choice%"=="5d" goto code_style_suggest_changes
if "%choice%"=="5e" goto code_style_make_changes
if "%choice%"=="5f" goto security_quick
if "%choice%"=="5g" goto security_full
if "%choice%"=="5h" goto security_deps
if "%choice%"=="5i" goto security_code_file
if "%choice%"=="5j" goto security_summary
if "%choice%"=="5k" goto security_json
if "%choice%"=="5l" goto phpcs_check
if "%choice%"=="5m" goto phpcs_file
if "%choice%"=="5n" goto phpcs_dir
if "%choice%"=="5o" goto phpcs_report
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
if "%choice%"=="20" goto gh_cli_install
if "%choice%"=="21" goto gh_auth_status
if "%choice%"=="22" goto gh_copilot_version
if "%choice%"=="23" goto exit
if "%choice%"=="24" goto exit_to_directory
if "%choice%"=="99" goto diagnostics
echo Invalid choice. Please try again.
pause
goto menu

:composer_audit
echo Validating composer.json and composer.lock...
composer audit --ansi
pause
goto menu

:composer_clear_cache_and_resolve_lock_conflicts
echo Clearing Composer cache and resolving lock file conflicts...
composer clear-cache
composer update --lock
pause
goto menu

:composer_dumpautoload
echo Regenerating Composer autoload files...
composer dump-autoload -o
pause
goto menu

:composer_outdated
echo Checking Composer Outdated...
composer outdated
pause
goto menu

:composer_update
echo Updating Composer...
composer update
pause
goto menu

:composer_validate
echo Validating composer.json and composer.lock...
composer validate --ansi --strict
pause
goto menu

:composer_whynot
set /p repo="Package name (e.g. vendor/package): "
set /p version="Version (e.g. 1.0.0): "
composer why-not %repo% %version%
pause
goto menu

:diagnostics
echo .......... VERSIONS - PHP, COMPOSER, NODE, TYPESCRIPT ..........
php -v
composer --version
npm -v
node -v
npx tsc --version
echo ------------ Composer Platform Check ------------
composer check-platform-reqs
echo ------------ Node List ------------
npm list --depth=0
pause
goto menu

:installation_menu
cls
echo ======================================================================================
echo                            INSTALLATION MENU
echo ======================================================================================
echo [0x] Check PHP Extensions (Pre-install)    [3] Full Installation 
echo [1]  Check System Requirements             [4] Shipmonk Dependency Analyser
echo [2]  Install Dependencies Only             [5] Back to Main Menu              
echo ======================================================================================
set /p install_choice="Enter your choice [0x-4]: "

if "%install_choice%"=="0x" goto check_extensions
if "%install_choice%"=="1" goto check_requirements  
if "%install_choice%"=="2" goto install_dependencies
if "%install_choice%"=="3" goto full_installation
if "%install_choice%"=="4" goto shipmonk_dependency_analyser
if "%install_choice%"=="5" goto menu
echo Invalid choice. Please try again.
pause
goto installation_menu

:check_extensions
cls
echo ======================================================================================
echo                     PHP EXTENSION CHECKER (Pre-Installation)
echo ======================================================================================
echo Checking required PHP extensions for Invoice System...
echo Based on invoice_build.yml workflow requirements
echo.
php scripts\extension-checker.php
echo.
echo [INFO] If extensions are missing, follow the instructions above.
echo [INFO] You may need to restart WAMP/Apache after making changes.
pause
goto installation_menu

:check_requirements
echo Checking system requirements...
where php >nul 2>nul || echo [ERROR] PHP not found in PATH
where composer >nul 2>nul || echo [ERROR] Composer not found in PATH  
where npm >nul 2>nul || echo [ERROR] npm not found in PATH
php --version
composer --version
npm --version
pause
goto installation_menu

:install_dependencies
echo Installing dependencies only...
composer install --no-dev --optimize-autoloader
npm install --production
pause
goto installation_menu

:full_installation
echo Running full installation...
if exist install.bat (
    call install.bat
) else (
    echo [INFO] No install.bat found. Running 'composer install' and 'npm install'.
    composer install
    npm install
)
pause
goto installation_menu

:shipmonk_dependency_analyser
echo Running Shipmonk Composer Dependency Analyser (https://github.com/shipmonk-rnd/composer-dependency-analyser)...
php vendor/bin/composer-dependency-analyser
pause
goto installation_menu

:node_audit
echo Running npm audit...
npm audit
echo Running npm cache clean...
npm cache clean --force
echo Listing top-level npm packages...
npm list --depth=0
pause
goto menu

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

:npm_check_outdated
echo Checking npm packages for updates (like 'composer outdated')...
npm run upgrade:check
pause
goto menu

:npm_safe_update
echo Running safe npm update (patch versions only)...
npm run upgrade:safe
pause
goto menu

:npm_minor_update
echo Running npm minor version updates...
npm run upgrade:minor
pause
goto menu

:npm_major_update
echo Running npm major version updates (interactive)...
npm run upgrade:major
pause
goto menu

:npm_es2024_verify
echo Verifying ES2024 features are available...
npm run es2024:verify
pause
goto menu

:psalm
echo Running PHP Psalm...
php vendor/bin/psalm
pause
goto menu

:public_assets_clear
echo Clearing Assets Cache (Safe - preserves .gitignore)...
if exist "public\assets" (
    echo Clearing assets cache while preserving .gitignore...
    powershell -Command "Get-ChildItem -Path 'public/assets' -Exclude '.gitignore' | Remove-Item -Recurse -Force"
    echo Assets cache cleared successfully (preserved .gitignore)
) else (
    echo No assets directory found to clear.
)
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

:psalm_directory
echo Running PHP Psalm on a directory...
set /p DIR="Directory path (relative to root): "
if "%DIR%"=="" (echo No directory specified.& pause& goto menu)
php vendor/bin/psalm "%DIR%"
pause
goto menu

:psalm_file
echo Running PHP Psalm on a specific file...
set /p file="File path (relative to root): "
if "%file%"=="" (echo No file specified.& pause& goto menu)
php vendor/bin/psalm "%file%"
pause
goto menu

:typescript_build_prod
echo Building TypeScript (Production - Minified)...
npm run build:prod
pause
goto menu

:typescript_build_dev
echo Building TypeScript (Development - with Source Maps)...
npm run build:dev
pause
goto menu

:typescript_watch
echo Starting TypeScript Watch Mode (Development)...
echo Press Ctrl+C to stop watching...
npm run build:watch
pause
goto menu

:typescript_type_check
echo Running TypeScript Type Check...
npm run type-check
pause
goto menu

:typescript_lint
echo Running TypeScript Lint Check...
npm run lint
pause
goto menu

:typescript_format
echo Running TypeScript Format Check...
npm run format:check
echo.
echo Running TypeScript Format Fix...
npm run format
pause
goto menu

:npm_run_build
echo Running npm run build...
npm run build
pause
goto menu

:angular_install_deps
echo ======== ANGULAR DEPENDENCY INSTALLATION WARNING ========
echo ⚠️  WARNING: This will install Angular dependencies!
echo 📝 This may modify existing TypeScript/ESLint configuration
echo 🔄 Ensure you have reviewed package.json and tsconfig files
echo 🚨 BACKUP your current setup before proceeding!
echo ==========================================================
set /p confirm="Continue with Angular dependency installation? (Y/N): "
if /i "%confirm%"=="Y" (
    echo Installing Angular dependencies...
    npm install
    echo Angular dependencies installed. Check for any conflicts.
) else (
    echo Angular installation cancelled.
)
pause
goto menu

:angular_serve
echo Starting Angular development server...
echo ⚠️  This runs Angular in development mode
echo 📝 Angular components will be available at http://localhost:4200
echo 🔄 In production, Angular integrates with Yii3 PHP layout
npm run ng:serve
pause
goto menu

:angular_build
echo Building Angular for production...
echo 📝 This builds Angular components for integration with Yii3
npm run ng:build
pause
goto menu

:angular_generate_component
echo Generating Angular component...
set /p componentName="Component name (e.g. dashboard, user-profile): "
if "%componentName%"=="" (echo No component name specified.& pause& goto menu)
echo Generating component: %componentName%
npm run angular:generate-component %componentName%
pause
goto menu

:angular_lint
echo Running Angular-specific linting...
echo 📝 This checks Angular components and templates
npm run lint:angular
pause
goto menu

:require_checker
echo Running Composer Require Checker...
php -d memory_limit=512M vendor/bin/composer-require-checker
pause
goto menu

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

:code_style_suggest_changes
echo PHP-CS-Fixer Dry Run (see potential changes)...
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --dry-run --show-progress=bar --verbose
pause
goto menu

:code_style_make_changes
echo PHP-CS-Fixer Fix (apply changes)...
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php 
pause
goto menu

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

:security_quick
echo Running Snyk Security Check (High Severity Issues Only)...
npm run security:quick
pause
goto menu

:security_full
echo Running Snyk Full Security Analysis (Code + Dependencies)...
npm run security:full
pause
goto menu

:security_deps
echo Running Snyk Security Check on Dependencies...
npm run security:deps
pause
goto menu

:security_code_file
echo Running Snyk Code Security Check on Specific File...
set /p file="File path (relative to root, e.g. src/Invoice/Inv/InvController.php): "
if "%file%"=="" (echo No file specified.& pause& goto menu)
snyk code test --file="%file%"
pause
goto menu

:security_summary
echo Running Snyk Security Summary (Total Issues Count Only)...
snyk code test | findstr /C:"Total issues"
pause
goto menu

:security_json
echo Running Snyk Security Analysis with JSON Output...
snyk code test --json
pause
goto menu

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

:phpcs_check
echo PHP CodeSniffer: Checking 85-character line length...
php vendor/bin/phpcs -d memory_limit=1024M --standard=phpcs.xml.dist
pause
goto menu

:phpcs_file
set /p filepath="Enter file path (e.g., src/Invoice/Invoice.php): "
echo Checking %filepath% for 85-character line length...
php vendor/bin/phpcs -d memory_limit=1024M --standard=Generic --sniffs=Generic.Files.LineLength --runtime-set lineLimit 85 --runtime-set absoluteLineLimit 85 %filepath%
pause
goto menu

:phpcs_dir
set /p dirpath="Enter directory path (e.g., src/Invoice/): "
echo Checking %dirpath% for 85-character line length...
php vendor/bin/phpcs -d memory_limit=1024M --standard=Generic --sniffs=Generic.Files.LineLength --runtime-set lineLimit 85 --runtime-set absoluteLineLimit 85 %dirpath%
pause
goto menu

:phpcs_report
echo Running detailed PHPCS line length report...
php vendor/bin/phpcs -d memory_limit=1024M --standard=phpcs.xml.dist --report=full --report-width=120
pause
goto menu

:gh_cli_install
echo Installing GitHub CLI via winget...
echo Checking if GitHub CLI is already installed...
where gh >nul 2>nul && (
    echo [INFO] GitHub CLI is already installed.
    gh --version
    echo.
    set /p reinstall="Reinstall anyway? (Y/N): "
    if /i not "%reinstall%"=="Y" (
        echo Installation cancelled.
        pause
        goto menu
    )
)
echo.
echo Installing GitHub CLI using winget...
winget install --id GitHub.cli
echo.
echo Adding GitHub CLI to PATH for current session...
REM Add common GitHub CLI installation paths to current session PATH
set "PATH=%PATH%;%ProgramFiles%\GitHub CLI;%LOCALAPPDATA%\Programs\GitHub CLI"
echo.
echo Installation complete!
echo Verifying installation...
where gh >nul 2>nul && (
    echo [SUCCESS] GitHub CLI is now available.
    gh --version
    echo.
    echo You can now run option [21] to authenticate with GitHub.
) || (
    echo [WARNING] GitHub CLI installed but not yet available in current session.
    echo Please restart your terminal and run option [21] to authenticate.
)
pause
goto menu

:gh_auth_status
echo Checking GitHub CLI authentication status...
where gh >nul 2>nul || (
    echo [ERROR] GitHub CLI not found in PATH.
    echo.
    echo If you just installed it, please close and reopen this terminal.
    echo.
    echo Otherwise, please install it first using option [20].
    pause
    goto menu
)
echo.
gh auth status
echo.
echo [INFO] If not authenticated, run: gh auth login
pause
goto menu

:gh_copilot_version
echo Checking GitHub Copilot access...
where gh >nul 2>nul || (
    echo [ERROR] GitHub CLI not found in PATH.
    echo.
    echo If you just installed it, please close and reopen this terminal.
    echo.
    echo Otherwise, please install it first using option [20].
    pause
    goto menu
)
echo.
echo Checking Copilot seat details...
gh api user/copilot_seat_details 2>nul && (
    echo.
    echo [SUCCESS] You have GitHub Copilot access!
) || (
    echo.
    echo [INFO] No active Copilot subscription found.
    echo Visit https://github.com/settings/copilot to check your access.
)
echo.
echo Checking GitHub CLI version...
gh --version
pause
goto menu

:exit_to_directory
echo Returning to the current directory. Goodbye!
cmd

:exit
echo Exiting. Goodbye!
pause
exit
