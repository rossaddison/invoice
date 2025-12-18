@echo off
setlocal enabledelayedexpansion

echo.
echo ========================================
echo      PSALM VALIDATION CHECKER
echo ========================================
echo.

REM Check if psalm is installed
if not exist "vendor\bin\psalm.bat" (
    echo [ERROR] Psalm not found at vendor\bin\psalm.bat
    echo Please run: composer install
    pause
    exit /b 1
)

REM Ask user if they want to run psalm validation
set /p "run_psalm=🔍 Do you want to run Psalm validation? (y/n): "

if /i "%run_psalm%"=="n" (
    echo [SKIP] Psalm validation skipped.
    echo.
    pause
    exit /b 0
)

if /i "%run_psalm%"=="y" (
    echo.
    echo 🔄 Running Psalm Level 1 strict validation...
    echo.
    
    REM Check if specific file argument provided
    if not "%~1"=="" (
        echo 📁 Validating file: %~1
        vendor\bin\psalm.bat --level=1 --strict "%~1"
    ) else (
        echo 📁 Validating entire project...
        vendor\bin\psalm.bat --level=1 --strict
    )
    
    set psalm_exit=%ERRORLEVEL%
    echo.
    
    if !psalm_exit! equ 0 (
        echo ✅ SUCCESS: No Psalm errors found!
        echo 🎉 Code is Psalm Level 1 compliant
    ) else (
        echo [ERROR] Psalm found errors ^(Exit code: !psalm_exit!^)
        echo [FIX] Please fix all issues before proceeding
    )
    
    echo.
    echo ========================================
    pause
    exit /b !psalm_exit!
) else (
    echo ❓ Invalid input. Please enter 'y' or 'n'
    pause
    exit /b 1
)