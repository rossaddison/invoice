@echo off
:: This batch script provides a menu to run common commands for the Invoice System project.

title Invoice System Command Menu
cd /d "%~dp0"

:menu
cls
echo =======================================
echo         INVOICE SYSTEM MENU
echo =======================================
echo [1] Run PHP Psalm
echo [2] Run PHP Psalm on a Specific File
echo [3] Check Composer Outdated
echo [4] Run Composer Require Checker
echo [5] Run 'serve' Command
echo [6] Run 'user/create' Command
echo [7] Run 'user/assignRole' Command
echo [8] Run 'router/list' Command
echo [9] Run 'translator/translate' Command
echo [10] Run 'invoice/items' Command
echo [11] Run 'invoice/setting/truncate' Command
echo [12] Run 'invoice/generator/truncate' Command
echo [13] Run 'invoice/inv/truncate1' Command
echo [14] Run 'invoice/quote/truncate2' Command
echo [15] Run 'invoice/salesorder/truncate3' Command
echo [16] Run 'invoice/nonuserrelated/truncate4' Command
echo [17] Exit
echo [18] Exit to Current Directory
echo =======================================
set /p choice="Enter your choice [1-18]: "

if "%choice%"=="1" goto psalm
if "%choice%"=="2" goto psalm_file
if "%choice%"=="3" goto outdated
if "%choice%"=="4" goto require_checker
if "%choice%"=="5" goto serve
if "%choice%"=="6" goto user_create
if "%choice%"=="7" goto user_assignRole
if "%choice%"=="8" goto router_list
if "%choice%"=="9" goto translator_translate
if "%choice%"=="10" goto invoice_items
if "%choice%"=="11" goto confirm_warning_11
if "%choice%"=="12" goto confirm_warning_12
if "%choice%"=="13" goto confirm_warning_13
if "%choice%"=="14" goto confirm_warning_14
if "%choice%"=="15" goto confirm_warning_15
if "%choice%"=="16" goto confirm_warning_16
if "%choice%"=="17" goto exit
if "%choice%"=="18" goto exit_to_directory
echo Invalid choice. Please try again.
pause
goto menu

:confirm_warning_11
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_setting_truncate
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_12
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_generator_truncate
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_13
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_inv_truncate1
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_14
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_quote_truncate2
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_15
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_salesorder_truncate3
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
pause
goto menu

:confirm_warning_16
echo You are about to delete sensitive data! Are you sure you want to continue? (Y/N)
set /p confirm=""
if /i "%confirm%"=="Y" goto invoice_nonuserrelated_truncate4
if /i "%confirm%"=="N" goto menu
echo Invalid input. Returning to the menu.
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

:outdated
echo Checking Composer Outdated...
composer outdated
pause
goto menu

:require_checker
echo Running Composer Require Checker...
php vendor/bin/composer-require-checker
pause
goto menu

:serve
echo Running 'serve' Command...
php yii serve
pause
goto menu

:user_create
echo Running 'user/create' Command...
php yii user/create
pause
goto menu

:user_assignRole
echo Running 'user/assignRole' Command...
php yii user/assignRole
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

:exit
echo Exiting. Goodbye!
pause
exit

:exit_to_directory
echo Returning to the current directory. Goodbye!
cmd