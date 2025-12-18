@echo off
echo Testing Extension Checker Integration...
echo.

echo [1/3] Testing direct PHP execution...
php scripts\extension-checker.php --silent
if %errorlevel% equ 0 (
    echo ✓ Direct execution successful
) else (
    echo ✗ Direct execution failed
)

echo.
echo [2/3] Testing m.bat integration...
echo Simulating installation menu choice 0x...
echo (In actual use, run m.bat and choose 0 then 0x)

echo.
echo [3/3] Testing Makefile integration...
echo Running: make ext-check
make ext-check

echo.
echo ============================================
echo Extension Checker Integration Test Complete
echo ============================================
echo.
echo Usage Instructions:
echo.
echo 1. Via m.bat:
echo    - Run m.bat
echo    - Choose [0] Installation Menu  
echo    - Choose [0x] Check PHP Extensions
echo.
echo 2. Via Makefile:
echo    - make ext-check       (Full report)
echo    - make ext-json        (JSON output)  
echo    - make ext-silent      (Silent check)
echo.
echo 3. Direct execution:
echo    - php scripts\extension-checker.php
echo    - php scripts\extension-checker.php --json
echo    - php scripts\extension-checker.php --silent
echo.

pause