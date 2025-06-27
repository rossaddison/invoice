@echo off
:: This batch script provides a menu to install the Invoice System project.
:: Ensure that the file is saved in Windows (CRLF) format e.g. Netbeans bottom right corner

title Invoice System Installation Menu
cd /d "%~dp0"

:menu
cls
echo =======================================
echo         INVOICE SYSTEM INSTALLATION MENU
echo =======================================
echo [1] Install with Symfony preinstalled i.e. src\Command\InstallCommand.php
echo [2] Install without Symfony preinstalled 
echo [3] Exit
echo [4] Exit to Current Directory
echo =======================================
set /p choice="Enter your choice [1-4]: "

if "%choice%"=="1" goto symfony
if "%choice%"=="2" goto non_symfony
if "%choice%"=="3" goto exit
if "%choice%"=="4" goto exit_to_directory
echo Invalid choice. Please try again.
pause
goto menu

:symfony
echo Running .. Install with Symfony preinstalled
yii install
pause
goto menu

:non_symfony
echo Running .. Install without Symfony preinstalled
php install.php
pause
goto menu

:exit_to_directory
echo Returning to the current directory. Goodbye!
cmd

:exit
echo Exiting. Goodbye!
pause
exit