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
echo [1] Post Composer Install using Symfony i.e. src\Command\InstallCommand.php
echo [2] Pre Composer Install without using Symfony 
echo [2a] Install and check for yii path being writable.  
echo [3] Exit
echo [4] Exit to Current Directory
echo =======================================
set /p choice="Enter your choice [1-4]: "

if "%choice%"=="1" goto symfony
if "%choice%"=="2" goto non_symfony
if "%choice%"=="2a" goto writable_check
if "%choice%"=="3" goto exit
if "%choice%"=="4" goto exit_to_directory
echo Invalid choice. Please try again.
pause
goto menu

:symfony
echo Running .. Post Composer Install using Symfony i.e. src\Command\InstallCommand.php
yii install
pause
goto menu

:non_symfony
echo Running .. Pre Composer Install without using Symfony
php install.php
pause
goto menu

:writable_check
echo Running .. Install and check for yii path being writable
php install_writable.php
pause
goto menu


:exit_to_directory
echo Returning to the current directory. Goodbye!
cmd

:exit
echo Exiting. Goodbye!
pause
exit