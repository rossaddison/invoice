@echo off
echo === NetBeans File Monitor ===
echo This will monitor file changes and report them
echo Press Ctrl+C to stop monitoring

:monitor
timeout /t 2 /nobreak >nul
echo [%date% %time%] Checking for file changes...

REM Check key files for changes
for %%f in (
    "src\Widget\Button.php"
    "src\Invoice\Asset\InvoiceAsset.php" 
    "src\typescript\settings.ts"
    "src\Invoice\Asset\rebuild\js\invoice-typescript-iife.js"
) do (
    if exist "%%f" (
        for %%A in ("%%f") do (
            echo %%f - Size: %%~zA bytes - Modified: %%~tA
        )
    )
)

echo.
goto monitor