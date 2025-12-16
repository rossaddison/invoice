@echo off
REM NetBeans VS Code Sync Script for Windows

echo === NetBeans VS Code Sync Check ===

set "PROJECT_ROOT=C:\wamp64\www\invoice"
set "TARGET_FILE=%1"

if "%TARGET_FILE%"=="" (
    echo Usage: sync-check.bat "path\to\file.php"
    echo Example: sync-check.bat "src\Widget\Button.php"
    goto :end
)

set "FULL_PATH=%PROJECT_ROOT%\%TARGET_FILE%"

if exist "%FULL_PATH%" (
    echo File exists: %FULL_PATH%
    echo File size: 
    for %%A in ("%FULL_PATH%") do echo %%~zA bytes
    echo Last modified:
    forfiles /m "%TARGET_FILE%" /s /c "cmd /c echo @fdate @ftime" 2>nul
    
    echo.
    echo === First 10 lines ===
    powershell "Get-Content '%FULL_PATH%' -TotalCount 10"
    
    echo.
    echo === Last 10 lines ===
    powershell "Get-Content '%FULL_PATH%' -Tail 10"
) else (
    echo File not found: %FULL_PATH%
    echo.
    echo Available files in directory:
    dir "%PROJECT_ROOT%\%~dp1" 2>nul
)

:end
pause