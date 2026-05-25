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
echo [1]  Run PHP Psalm (Full)                      [5a]  PHPUnit Tests (Entity)
echo [2]  Psalm on File                             [5aa] PHPUnit Tests (All Unit)
echo [2a] Psalm on Directory                        [5b]  Rector See Changes
echo [2b] Clear Psalm's Cache                       [5c]  Rector Make Changes
echo [2c] Psalm: Show Config/Plugins                [5d]  PHP-CS-Fixer Dry Run
echo [2d] Public Assets Clear (Safe)                [5e]  PHP-CS-Fixer Fix
echo [3]  Composer Outdated                         [5f]  Snyk Security Check (Quick)
echo [3a] Composer why-not                          [5g]  Snyk Security Check (Full)
echo [3b] Composer Cache with Lock                  [5h]  Snyk Security Dependencies
echo [3c] Composer Validate                         [5i]  Snyk Security Code File Check
echo [3d] Composer Dump Autoload                    [5j]  Snyk Security Summary (Issues Count)
echo [3e] Composer Audit                            [5k]  Snyk Security JSON Output
echo [4]  Composer Update                           [5l]  PHPCS: Check 85-char line length
echo [4a] Node Modules Update                       [5m]  PHPCS: Check specific file
echo [4b] nvm-windows Install/Update                [5n]  PHPCS: Check specific directory
echo [4c] Node: Audit, Clean, List                  [5o]  PHPCS: Detailed report
echo [4d] npm: Check Outdated                       [5p]  PHPUnit: Functional/Integration
echo [4e] npm: Safe Update (patch only)             [5q]  Codeception: Functional Suite
echo [4f] npm: Minor Update (minor versions)        [5r]  Codeception: Acceptance Suite
echo [4g] npm: Major Update (interactive)           [5s]  Codeception: All Suites
echo [4h] npm: ES2024 Feature Verification          [6]   PHP Built-in 'serve'
echo [4i] TypeScript Build (Production)             [7]   user/create username password
echo [4j] TypeScript Build (Development)            [8]   user/assignRole role userId
echo [4k] TypeScript Watch Mode                     [9]   router/list
echo [4l] TypeScript Type Check                     [10]  translator/translate
echo [4m] TypeScript Lint                           [11]  invoice/items
echo [4n] TypeScript Format                         [12]  invoice/setting/truncate
echo [4o] npm run build                             [13]  invoice/generator/truncate
echo [4p] Angular: Install Dependencies             [14]  invoice/inv/truncate1
echo [4q] Angular: Serve Development                [4r]  Angular: Build Production
echo [4s] Angular: Generate Component               [4t]  Angular: Lint Check
echo [5]  Require Checker                           [15]  invoice/quote/truncate2
echo [25] Performance Benchmarks                    [16]  invoice/salesorder/truncate3
echo [99] System Info / Diagnostics                 [17]  invoice/nonuserrelated/truncate4
echo                                                [18]  invoice/userrelated/truncate5
echo                                                [19]  invoice/autoincrementsettooneafter/truncate6
echo                                                [20]  GitHub CLI: Install
echo                                                [21]  GitHub CLI: Auth Status
echo                                                [22]  GitHub CLI: Copilot Version
echo                                                [23]  Exit
echo                                                [24]  Exit to Current Directory
echo =================================
set /p choice="Enter your choice [0-25,99]: "

REM ======== MENU COMMAND ROUTING ========
if "%choice%"=="1" goto c01
if "%choice%"=="2" goto c02
if "%choice%"=="2a" goto c02a
if "%choice%"=="2b" goto c02b
if "%choice%"=="2c" goto c02c
if "%choice%"=="2d" goto c02d
if "%choice%"=="3" goto c03
if "%choice%"=="3a" goto c03a
if "%choice%"=="3b" goto c03b
if "%choice%"=="3c" goto c03c
if "%choice%"=="3d" goto c03d
if "%choice%"=="3e" goto c03e
if "%choice%"=="4" goto c04
if "%choice%"=="4a" goto c04a
if "%choice%"=="4b" goto c04b
if "%choice%"=="4c" goto c04c
if "%choice%"=="4d" goto c04d
if "%choice%"=="4e" goto c04e
if "%choice%"=="4f" goto c04f
if "%choice%"=="4g" goto c04g
if "%choice%"=="4h" goto c04h
if "%choice%"=="4i" goto c04i
if "%choice%"=="4j" goto c04j
if "%choice%"=="4k" goto c04k
if "%choice%"=="4l" goto c04l
if "%choice%"=="4m" goto c04m
if "%choice%"=="4n" goto c04n
if "%choice%"=="4o" goto c04o
if "%choice%"=="4p" goto c04p
if "%choice%"=="4q" goto c04q
if "%choice%"=="4r" goto c04r
if "%choice%"=="4s" goto c04s
if "%choice%"=="4t" goto c04t
if "%choice%"=="5" goto c05
if "%choice%"=="5a" goto c05a
if "%choice%"=="5aa" goto c05aa
if "%choice%"=="5b" goto c05b
if "%choice%"=="5c" goto c05c
if "%choice%"=="5d" goto c05d
if "%choice%"=="5e" goto c05e
if "%choice%"=="5f" goto c05f
if "%choice%"=="5g" goto c05g
if "%choice%"=="5h" goto c05h
if "%choice%"=="5i" goto c05i
if "%choice%"=="5j" goto c05j
if "%choice%"=="5k" goto c05k
if "%choice%"=="5l" goto c05l
if "%choice%"=="5m" goto c05m
if "%choice%"=="5n" goto c05n
if "%choice%"=="5o" goto c05o
if "%choice%"=="5p" goto c05p
if "%choice%"=="5q" goto c05q
if "%choice%"=="5r" goto c05r
if "%choice%"=="5s" goto c05s
if "%choice%"=="6" goto c06
if "%choice%"=="7" goto c07
if "%choice%"=="8" goto c08
if "%choice%"=="9" goto c09
if "%choice%"=="10" goto c10
if "%choice%"=="11" goto c11
if "%choice%"=="12" goto c12
if "%choice%"=="13" goto c13
if "%choice%"=="14" goto c14
if "%choice%"=="15" goto c15
if "%choice%"=="16" goto c16
if "%choice%"=="17" goto c17
if "%choice%"=="18" goto c18
if "%choice%"=="19" goto c19
if "%choice%"=="20" goto c20
if "%choice%"=="21" goto c21
if "%choice%"=="22" goto c22
if "%choice%"=="23" goto c23
if "%choice%"=="24" goto c24
if "%choice%"=="25" goto c25
if "%choice%"=="99" goto c99
echo Invalid choice. Please try again.
pause
goto menu

REM ======== HANDLERS (alphabetical by label: c01..c99, then check_*, confirm_*, install_*, shipmonk_*) ========

:c01
echo Running PHP Psalm...
php vendor/bin/psalm --force-jit
pause
goto menu

:c02
echo Running PHP Psalm on a specific file...
set /p file="File path (relative to root): "
if "%file%"=="" (echo No file specified.& pause& goto menu)
php vendor/bin/psalm "%file%"
pause
goto menu

:c02a
echo Running PHP Psalm on a directory...
set /p DIR="Directory path (relative to root): "
if "%DIR%"=="" (echo No directory specified.& pause& goto menu)
php vendor/bin/psalm "%DIR%"
pause
goto menu

:c02b
echo Clearing Psalm's cache...
php vendor/bin/psalm --clear-cache
pause
goto menu

:c02c
echo Psalm Config & Plugins:
php vendor/bin/psalm --show-info || echo Psalm version does not support --show-info
pause
goto menu

:c02d
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

:c03
echo Checking Composer Outdated...
composer outdated
pause
goto menu

:c03a
set /p repo="Package name (e.g. vendor/package): "
set /p version="Version (e.g. 1.0.0): "
composer why-not %repo% %version%
pause
goto menu

:c03b
echo Clearing Composer cache and resolving lock file conflicts...
composer clear-cache
composer update --lock
pause
goto menu

:c03c
echo Validating composer.json and composer.lock...
composer validate --ansi --strict
pause
goto menu

:c03d
echo Regenerating Composer autoload files...
composer dump-autoload -o
pause
goto menu

:c03e
echo Validating composer.json and composer.lock...
composer audit --ansi
pause
goto menu

:c04
echo Updating Composer...
composer update
pause
goto menu

:c04a
echo Updating Node modules...
npx npm-check-updates -u
npm install
pause
goto menu

:c04b
echo Downloading latest nvm-windows installer...
powershell -Command "Invoke-WebRequest -Uri https://github.com/coreybutler/nvm-windows/releases/latest/download/nvm-setup.exe -OutFile nvm-setup.exe"
start /wait nvm-setup.exe /SILENT
del nvm-setup.exe
echo nvm-windows install/update complete.
pause
goto menu

:c04c
echo Running npm audit...
npm audit
echo Running npm cache clean...
npm cache clean --force
echo Listing top-level npm packages...
npm list --depth=0
pause
goto menu

:c04d
echo Checking npm packages for updates (like 'composer outdated')...
npm run upgrade:check
pause
goto menu

:c04e
echo Running safe npm update (patch versions only)...
npm run upgrade:safe
pause
goto menu

:c04f
echo Running npm minor version updates...
npm run upgrade:minor
pause
goto menu

:c04g
echo Running npm major version updates (interactive)...
npm run upgrade:major
pause
goto menu

:c04h
echo Verifying ES2024 features are available...
npm run es2024:verify
pause
goto menu

:c04i
echo Building TypeScript (Production - Minified)...
npm run build:prod
pause
goto menu

:c04j
echo Building TypeScript (Development - with Source Maps)...
npm run build:dev
pause
goto menu

:c04k
echo Starting TypeScript Watch Mode (Development)...
echo Press Ctrl+C to stop watching...
npm run build:watch
pause
goto menu

:c04l
echo Running TypeScript Type Check...
npm run type-check
pause
goto menu

:c04m
echo Running TypeScript Lint Check...
npm run lint
pause
goto menu

:c04n
echo Running TypeScript Format Check...
npm run format:check
echo.
echo Running TypeScript Format Fix...
npm run format
pause
goto menu

:c04o
echo Running npm run build...
npm run build
pause
goto menu

:c04p
echo ======== ANGULAR DEPENDENCY INSTALLATION WARNING ========
echo WARNING: This will install Angular dependencies!
echo This may modify existing TypeScript/ESLint configuration
echo Ensure you have reviewed package.json and tsconfig files
echo BACKUP your current setup before proceeding!
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

:c04q
echo Starting Angular development server...
echo This runs Angular in development mode
echo Angular components will be available at http://localhost:4200
echo In production, Angular integrates with Yii3 PHP layout
npm run ng:serve
pause
goto menu

:c04r
echo Building Angular for production...
echo This builds Angular components for integration with Yii3
npm run ng:build
pause
goto menu

:c04s
echo Generating Angular component...
set /p componentName="Component name (e.g. dashboard, user-profile): "
if "%componentName%"=="" (echo No component name specified.& pause& goto menu)
echo Generating component: %componentName%
npm run angular:generate-component %componentName%
pause
goto menu

:c04t
echo Running Angular-specific linting...
echo This checks Angular components and templates
npm run lint:angular
pause
goto menu

:c05
echo Running Composer Require Checker...
php -d memory_limit=512M vendor/bin/composer-require-checker
pause
goto menu

:c05a
echo Running PHPUnit Tests (Tests/Unit/Invoice/Entity/)...
php vendor/bin/phpunit Tests/Unit/Invoice/Entity/ --no-coverage --testdox
pause
goto menu

:c05aa
echo Running PHPUnit Tests (Tests/Unit/)...
php vendor/bin/phpunit Tests/Unit/ --no-coverage --testdox
pause
goto menu

:c05b
echo Rector Dry Run (see proposed changes)...
php vendor/bin/rector process --dry-run --output-format=console
pause
goto menu

:c05c
echo Rector Make Changes (apply changes)...
php vendor/bin/rector
pause
goto menu

:c05d
echo PHP-CS-Fixer Dry Run (see potential changes)...
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --dry-run --show-progress=bar --verbose
pause
goto menu

:c05e
echo PHP-CS-Fixer Fix (apply changes)...
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php
pause
goto menu

:c05f
echo Running Snyk Security Check (High Severity Issues Only)...
npm run security:quick
pause
goto menu

:c05g
echo Running Snyk Full Security Analysis (Code + Dependencies)...
npm run security:full
pause
goto menu

:c05h
echo Running Snyk Security Check on Dependencies...
npm run security:deps
pause
goto menu

:c05i
echo Running Snyk Code Security Check on Specific File...
set /p file="File path (relative to root, e.g. src/Invoice/Inv/InvController.php): "
if "%file%"=="" (echo No file specified.& pause& goto menu)
snyk code test --file="%file%"
pause
goto menu

:c05j
echo Running Snyk Security Summary (Total Issues Count Only)...
snyk code test | findstr /C:"Total issues"
pause
goto menu

:c05k
echo Running Snyk Security Analysis with JSON Output...
snyk code test --json
pause
goto menu

:c05l
echo PHP CodeSniffer: Checking 85-character line length...
php vendor/bin/phpcs -d memory_limit=1024M --standard=phpcs.xml.dist
pause
goto menu

:c05m
set /p filepath="Enter file path (e.g., src/Invoice/Invoice.php): "
echo Checking %filepath% for 85-character line length...
php vendor/bin/phpcs -d memory_limit=1024M --standard=Generic --sniffs=Generic.Files.LineLength --runtime-set lineLimit 85 --runtime-set absoluteLineLimit 85 %filepath%
pause
goto menu

:c05n
set /p dirpath="Enter directory path (e.g., src/Invoice/): "
echo Checking %dirpath% for 85-character line length...
php vendor/bin/phpcs -d memory_limit=1024M --standard=Generic --sniffs=Generic.Files.LineLength --runtime-set lineLimit 85 --runtime-set absoluteLineLimit 85 %dirpath%
pause
goto menu

:c05o
echo Running detailed PHPCS line length report...
php vendor/bin/phpcs -d memory_limit=1024M --standard=phpcs.xml.dist --report=full --report-width=120
pause
goto menu

:c05p
echo Running PHPUnit Tests (Tests/Functional/ Tests/Integration/ Tests/PHPUnit/)...
php vendor/bin/phpunit Tests/Functional/ Tests/Integration/ Tests/PHPUnit/ --no-coverage --testdox
pause
goto menu

:c05q
echo Running Codeception Functional Suite...
php vendor/bin/codecept run Functional
pause
goto menu

:c05r
echo Running Codeception Acceptance Suite...
echo [INFO] Requires: php yii serve (running) and a browser driver (Selenium/Playwright)
php vendor/bin/codecept run Acceptance
pause
goto menu

:c05s
echo Running all Codeception Suites...
echo [INFO] Acceptance suite requires a running server and browser driver
php vendor/bin/codecept run
pause
goto menu

:c06
echo Running PHP built-in server via Yii...
php yii serve
pause
goto menu

:c07
set /p username="Username (e.g. admin): "
set /p password="Password (e.g. admin): "
if "%username%"=="" (echo No username specified.& pause& goto menu)
if "%password%"=="" (echo No password specified.& pause& goto menu)
php yii user/create "%username%" "%password%"
pause
goto menu

:c08
set /p role="Role (e.g. admin): "
set /p userId="User ID (e.g. 1): "
if "%role%"=="" (echo No role specified.& pause& goto menu)
if "%userId%"=="" (echo No user ID specified.& pause& goto menu)
php yii user/assignRole "%role%" "%userId%"
pause
goto menu

:c09
php yii router/list
pause
goto menu

:c10
set /p sourceText="Source text: "
set /p targetLanguage="Target language code (e.g. fr): "
if "%sourceText%"=="" (echo No source text specified.& pause& goto menu)
if "%targetLanguage%"=="" (echo No target language specified.& pause& goto menu)
php yii translator/translate "%sourceText%" "%targetLanguage%"
pause
goto menu

:c11
php yii invoice/items
pause
goto menu

:c12
call :confirm_delete "c12x"
goto menu

:c12x
php yii invoice/setting/truncate
pause
goto menu

:c13
call :confirm_delete "c13x"
goto menu

:c13x
php yii invoice/generator/truncate
pause
goto menu

:c14
call :confirm_delete "c14x"
goto menu

:c14x
php yii invoice/inv/truncate1
pause
goto menu

:c15
call :confirm_delete "c15x"
goto menu

:c15x
php yii invoice/quote/truncate2
pause
goto menu

:c16
call :confirm_delete "c16x"
goto menu

:c16x
php yii invoice/salesorder/truncate3
pause
goto menu

:c17
call :confirm_delete "c17x"
goto menu

:c17x
php yii invoice/nonuserrelated/truncate4
pause
goto menu

:c18
call :confirm_delete "c18x"
goto menu

:c18x
php yii invoice/userrelated/truncate5
pause
goto menu

:c19
call :confirm_delete "c19x"
goto menu

:c19x
php yii invoice/autoincrementsettooneafter/truncate6
pause
goto menu

:c20
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
set "PATH=%PATH%;%LOCALAPPDATA%\Microsoft\WinGet\Links;%ProgramFiles%\GitHub CLI;%LOCALAPPDATA%\Programs\GitHub CLI"
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

:c21
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

:c22
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
gh api user/copilot_seat_details >nul 2>nul && (
    echo [SUCCESS] You have GitHub Copilot access!
    echo.
    echo Manage your subscription: https://github.com/settings/copilot
) || (
    echo [INFO] No active Copilot subscription found via API.
    echo.
    echo If you have a subscription but it's not detected:
    echo 1. Check your authenticated account with: gh auth status
    echo 2. Verify subscription at: https://github.com/settings/copilot
    echo 3. Try re-authenticating with: gh auth login
    echo.
    echo If you need Copilot access:
    echo - Individual: https://github.com/features/copilot
    echo - Organization: Contact your GitHub admin
)
echo.
echo Checking GitHub CLI version...
gh --version
pause
goto menu

:c23
echo Exiting. Goodbye!
pause
exit

:c24
echo Returning to the current directory. Goodbye!
cmd

:c25
cls
echo ======================================================================================
echo                         PERFORMANCE BENCHMARKS  (Yii3-i)
echo ======================================================================================
echo  Results accumulate in: benchmarks/results/history.json
echo  Dashboard served at:   http://localhost:8080  (option 7)
echo ======================================================================================
echo  [1]  Run All Suites          - DI + Injector + Router + Strings (saves result)
echo  [2]  DI Container Suite      - singleton cache, dependency chains, container build
echo  [3]  Injector Suite          - auto-wire (cached vs uncached reflection), make()
echo  [4]  Router Suite            - FastRoute: static, parametrised, worst-case, 404
echo  [5]  String Helpers Suite    - StringHelper, Inflector, WildcardPattern, Regexp
echo  [6]  Dry Run (All Suites)    - print table but do NOT write to history.json
echo  [7]  Serve Dashboard         - start PHP server + open browser at localhost:8080
echo  [0]  Back to Main Menu
echo ======================================================================================
set /p bench_choice="Benchmark choice [0-7]: "

if "%bench_choice%"=="0" goto menu
if "%bench_choice%"=="1" goto bench_all
if "%bench_choice%"=="2" goto bench_di
if "%bench_choice%"=="3" goto bench_injector
if "%bench_choice%"=="4" goto bench_router
if "%bench_choice%"=="5" goto bench_strings
if "%bench_choice%"=="6" goto bench_dry
if "%bench_choice%"=="7" goto bench_dashboard
echo Invalid choice.
pause
goto c25

:bench_all
echo.
echo Running all benchmark suites...
php benchmarks/run.php
pause
goto c25

:bench_di
echo.
echo Running DI Container suite...
php benchmarks/run.php --suite=di
pause
goto c25

:bench_injector
echo.
echo Running Injector suite...
php benchmarks/run.php --suite=injector
pause
goto c25

:bench_router
echo.
echo Running Router suite...
php benchmarks/run.php --suite=router
pause
goto c25

:bench_strings
echo.
echo Running String Helpers suite...
php benchmarks/run.php --suite=strings
pause
goto c25

:bench_dry
echo.
echo Running all suites (dry run - result NOT saved to history.json)...
php benchmarks/run.php --dry-run
pause
goto c25

:bench_dashboard
echo.
echo Starting PHP dashboard server at http://localhost:8080 ...
start "Yii3-i Benchmark Dashboard" cmd /c "php -S localhost:8080 -t benchmarks"
timeout /t 2 /nobreak >nul
start http://localhost:8080/dashboard/
echo.
echo Dashboard running in background window.
echo Close the "Yii3-i Benchmark Dashboard" window to stop the server.
pause
goto c25

:c99
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

REM ======== INSTALLATION / LEGACY HANDLERS ========

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
goto menu

:check_requirements
echo Checking system requirements...
where php >nul 2>nul || echo [ERROR] PHP not found in PATH
where composer >nul 2>nul || echo [ERROR] Composer not found in PATH
where npm >nul 2>nul || echo [ERROR] npm not found in PATH
php --version
composer --version
npm --version
pause
goto menu

:confirm_delete
echo You are about to delete sensitive data! Are you sure? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto %1
echo Cancelled.
pause
goto menu

:install_dependencies
echo Installing dependencies only...
composer install --no-dev --optimize-autoloader
npm install --production
pause
goto menu

:shipmonk_dependency_analyser
echo Running Shipmonk Composer Dependency Analyser...
php vendor/bin/composer-dependency-analyser
pause
goto menu
