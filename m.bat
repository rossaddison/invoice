@echo off
:: This batch script provides a menu to run common commands for the Invoice System project.
:: It allows users to execute PHP Psalm, check for outdated Composer dependencies, 
:: and run the Composer Require Checker, all from the directory where the script is located
:: and has been built with the assistance of Copilot in 5 minutes!

title Invoice System Command Menu
cd /d "%~dp0"

:menu
cls
echo =======================================
echo         INVOICE SYSTEM MENU
echo =======================================
echo [1] Run PHP Psalm
echo [2] Check Composer Outdated
echo [3] Run Composer Require Checker
echo [4] Exit
echo =======================================
set /p choice="Enter your choice [1-4]: "

if "%choice%"=="1" goto psalm
if "%choice%"=="2" goto outdated
if "%choice%"=="3" goto require_checker
if "%choice%"=="4" goto exit
echo Invalid choice. Please try again.
pause
goto menu

:psalm
echo Running PHP Psalm...
php vendor/bin/psalm
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

:exit
echo Exiting. Goodbye!
pause
exit
