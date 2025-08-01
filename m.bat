@echo off
:: This batch script provides a menu to run common commands for the Invoice System project.
:: Ensure that the file is saved in Windows (CRLF) format e.g. Netbeans bottom right corner

title Invoice System Command Menu
cd /d "%~dp0"

:menu
cls
echo =======================================
echo         INVOICE SYSTEM MENU
echo =======================================
echo [0] Goto Installation Menu
echo [1] Run PHP Psalm
echo [2] Run PHP Psalm on a Specific File
echo [2a] Run PHP Psalm on a Specific Directory
echo [2b] Clear Psalm's cache (in the event of stubborn errors)
echo [3] Check Composer Outdated
echo [3a] Composer why-not {repository eg. yiisoft/yii-demo} {patch/minor version e.g. 1.1.1}
echo [3b] Clear Composer Cache and Resolve Lock File Conflicts without updating actual packages
echo [4] Run Composer Update
echo [4a] Run Node Modules Update
echo [4b] Run Install or update nvm-windows to the latest version
echo [5] Run Composer Require Checker
echo [5a] Run Codeception Tests
echo [5b] Run Rector See Potential Changes ... Tip: Always post validate with [1] Run PHP Psalm
echo [5c] Run Rector Make Changes ... Caution! May conflict with [1] Run PHP Psalm
echo [5d] Run Code Style Fixer with a dry-run to see potential changes
echo [5e] Run Code Style Fixer and actually change the coding style of the files
echo [6] Run 'serve' Command
echo [7] Run 'user/create' username password
echo [8] Run 'user/assignRole' role userId 
echo [9] Run 'router/list' Command
echo [10] Run 'translator/translate' Command
echo [11] Run 'invoice/items' Command
echo [12] Run 'invoice/setting/truncate' Command
echo [13] Run 'invoice/generator/truncate' Command
echo [14] Run 'invoice/inv/truncate1' Command
echo [15] Run 'invoice/quote/truncate2' Command
echo [16] Run 'invoice/salesorder/truncate3' Command
echo [17] Run 'invoice/nonuserrelated/truncate4' Command
echo [18] Run 'invoice/userrelated/truncate5' Command
echo [19] Run 'invoice/autoincrementsettooneafter/truncate6' Command
echo [20] Exit
echo [21] Exit to Current Directory
echo =======================================
set /p choice="Enter your choice [0-21]: "

if "%choice%"=="0" goto installation_menu 
if "%choice%"=="1" goto psalm
if "%choice%"=="2" goto psalm_file
if "%choice%"=="2a" goto psalm_directory
if "%choice%"=="2b" goto psalm_clear_cache
if "%choice%"=="3" goto outdated
if "%choice%"=="3a" goto composerwhynot
if "%choice%"=="3b" goto composer_clear_cache_and_resolve_lock_conflicts
if "%choice%"=="4" goto composer_update
if "%choice%"=="4a" goto node_modules_update
if "%choice%"=="4b" goto nvm_install_or_update
if "%choice%"=="5" goto require_checker
if "%choice%"=="5a" goto codeception_tests
if "%choice%"=="5b" goto rector_see_changes
if "%choice%"=="5c" goto rector_make_changes
if "%choice%"=="5d" goto code_style_suggest_changes
if "%choice%"=="5e" goto code_style_make_changes
if "%choice%"=="6" goto serve
if "%choice%"=="7" goto user_create
if "%choice%"=="7a" goto user_create
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
echo Invalid choice. Please try again.
pause
goto menu

:code_style_suggest_changes
echo Suggested changes to the Coding Style 
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --dry-run --diff 
pause
goto menu

:code_style_make_changes
echo Make the changes that were suggested to the Coding Style 
php vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php 
pause
goto menu

:confirm_warning_12
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_setting_truncate
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_13
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_generator_truncate
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_14
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_inv_truncate1
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_15
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_quote_truncate2
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_16
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_salesorder_truncate3
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_17
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_nonuserrelated_truncate4
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_18
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_userrelated_truncate5
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_19
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_autoincrementsettooneafter_truncate6
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:installation_menu
echo Installation menu...
install.bat
pause
goto menu

:psalm
echo Running PHP Psalm...
php vendor/bin/psalm
pause
goto menu

:psalm_file
echo Running PHP Psalm on a specific file...
set /p file="Enter the path to the file (relative to the project root): "
if "%file%"=="" (
    echo No file specified. Returning to the menu.
    pause
    goto menu
)
php vendor/bin/psalm "%file%"
pause
goto menu

:psalm_directory
echo Running PHP Psalm on a specific directory...

if "%~1"=="" (
    set /p DIR="Enter the path to the directory (relative to the project root): "
    if "%DIR%"=="" (
        echo No directory specified. Returning to the menu.
        pause
        exit /b
    )
    set TARGET=%DIR%
) else (
    set TARGET=%~1
)

php vendor/bin/psalm %TARGET%
pause
goto menu

:psalm_clear_cache
echo Running PHP Psalm...
php vendor/bin/psalm --clear-cache
pause
goto menu

:outdated
echo Checking Composer Outdated...
composer outdated
pause
goto menu

:composerwhynot
@echo off
set /p repo="Enter the package name (e.g. vendor/package): "
set /p version="Enter the version (e.g. 1.0.0): "
composer why-not %repo% %version%
pause
goto menu

:composer_clear_cache_and_resolve_lock_conflicts
echo Clearing composer cache and resolving lock file conflicts without updating any packages
composer clear-cache
composer update --lock
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

:rector_see_changes
echo See changes that Rector Proposes ... Caution! Changes may conflict with Psalm
php vendor/bin/rector process --dry-run --output-format=console
pause
goto menu

:rector_make_changes
echo Make changes that Rector Proposed 
php vendor/bin/rector
pause
goto menu

:composer_update
echo Running Composer Update...
composer update
pause
goto menu

:node_modules_update
pushd node_modules
echo Running Node Modules Update...
npx npm-check-updates -u
npm install
popd
cd ..
pause
goto menu

:nvm_install_or_update
echo Downloading the latest nvm-windows installer...
powershell -Command "Invoke-WebRequest -Uri https://github.com/coreybutler/nvm-windows/releases/latest/download/nvm-setup.exe -OutFile nvm-setup.exe"
echo Running the nvm-windows installer...
start /wait nvm-setup.exe /SILENT
del nvm-setup.exe
echo nvm-windows installation/update complete.
pause
goto menu

:serve
echo Running 'serve' Command...
php yii serve
pause
goto menu

:user_create
echo Creating a new user...
set /p username="Enter the username: e.g. admin / observer: " %1
set /p password="Enter the password: e.g. admin / observer: " %2
if "%username%"=="" (
    echo No username provided. Returning to the menu.
    pause
    goto menu
)
if "%password%"=="" (
    echo No password provided. Returning to the menu.
    pause
    goto menu
)
php yii user/create "%username%" "%password%"
pause
goto menu

:user_assignRole
echo Assigning a role to a user...
set /p role="Enter the role e.g. admin or observer: " %1
set /p userId="Enter the user ID e.g 1 or 2: " %2
if "%role%"=="" (
    echo No role provided. Returning to the menu.
    pause
    goto menu
)
if "%userId%"=="" (
    echo No user ID provided. Returning to the menu.
    pause
    goto menu
)
php yii user/assignRole "%role%" "%userId%"
pause
goto menu

:router_list
echo Running 'router/list' Command...
php yii router/list
pause
goto menu

:translator_translate
echo Running 'translator/translate' Command...
set /p sourceText="Enter the source text to translate: "
set /p targetLanguage="Enter the target language code (e.g., 'fr' for French): "
if "%sourceText%"=="" (
    echo No source text provided. Returning to the menu.
    pause
    goto menu
)
if "%targetLanguage%"=="" (
    echo No target language provided. Returning to the menu.
    pause
    goto menu
)
php yii translator/translate "%sourceText%" "%targetLanguage%"
pause
goto menu

:invoice_items
echo Running 'invoice/items' Command...
php yii invoice/items
pause
goto menu

:invoice_setting_truncate
echo Running 'invoice/setting/truncate' Command...
php yii invoice/setting/truncate
pause
goto menu

:invoice_generator_truncate
echo Running 'invoice/generator/truncate' Command...
php yii invoice/generator/truncate
pause
goto menu

:invoice_inv_truncate1
echo Running 'invoice/inv/truncate1' Command...
php yii invoice/inv/truncate1
pause
goto menu

:invoice_quote_truncate2
echo Running 'invoice/quote/truncate2' Command...
php yii invoice/quote/truncate2
pause
goto menu

:invoice_salesorder_truncate3
echo Running 'invoice/salesorder/truncate3' Command...
php yii invoice/salesorder/truncate3
pause
goto menu

:invoice_nonuserrelated_truncate4
echo Running 'invoice/nonuserrelated/truncate4' Command...
php yii invoice/nonuserrelated/truncate4
pause
goto menu

:invoice_userrelated_truncate5
echo Running 'invoice/userrelated/truncate5' Command...
php yii invoice/userrelated/truncate5
pause
goto menu

:invoice_autoincrementsettooneafter_truncate6
echo Running 'invoice/autoincrementsettooneafter/truncate6' Command...
php yii invoice/autoincrementsettooneafter/truncate6
pause
goto menu

:exit_to_directory
echo Returning to the current directory. Goodbye!
cmd

:exit
echo Exiting. Goodbye!
pause
exit