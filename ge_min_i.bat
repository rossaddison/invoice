@echo off
REM ==========================================
REM Google Gemini CLI Menu Batch Script
REM ==========================================

:menu
cls
echo ==========================================
echo         Google Gemini CLI Menu
echo ==========================================
echo 1.  Install Google Gemini CLI globally using command ... npm install -g @google/gemini-cli
echo 2.  Run Google Gemini CLI (interactively) ... You will initially be asked to login to Google to authenticate
echo 0.  Exit to current directory (or press Ctrl+C twice to exit Gemini)
echo ==========================================
echo.
echo Examples of Gemini CLI usage: 
echo --------------------------------------------------------------------------------------------------------------------------------------------
echo Title                                                       ^| Command (Copy and paste a command into Gemini once it is running to test)
echo --------------------------------------------------------------------------------------------------------------------------------------------
echo Summarize the Invoice entity/model code                     ^| summarize_entity Inv
echo Summarize the InvoiceController logic                       ^| summarize_controller InvoiceController
echo Summarize the InvController logic                           ^| summarize_controller InvController
echo Summarize the QuoteController logic                         ^| summarize_controller QuoteController
echo Summarize all views related to Invoice                      ^| summarize_views Invoice
echo Generate index + summaries for entity, controller, views    ^| summarize_all Invoice
echo Explain specific code file GeneratorController.php          ^| explain_code src/Invoice/Generator/GeneratorController.php
echo Explain specific code file PaymentInformationController.php ^| explain_code src/Invoice/PaymentInformation/PaymentInformationController.php
echo Explain specific code file AuthController.php               ^| explain_code src/Auth/Controller/AuthController.php
echo Explain specific code file ChangePasswordController.php     ^| explain_code src/Auth/Controller/ChangePasswordController.php
echo Explain specific code file ForgotPasswordController.php     ^| explain_code src/Auth/Controller/ForgotPasswordController.php
echo Explain specific code file ResetPasswordController.php      ^| explain_code src/Auth/Controller/ResetPasswordController.php
echo Explain specific code file SignupController.php             ^| explain_code src/Auth/Controller/SignupController.php
echo Generate documentation for Invoice entity                   ^| generate_doc Invoice
echo Translate "Hello World" to French                           ^| translate_text "Hello World" "French"
echo Open a chat for Q and A about the Invoice entity            ^| chat_about_entity Invoice
echo Suggest code improvements/refactorings for a file           ^| refactor_code src/Invoice/Generator/GeneratorController.php
echo --------------------------------------------------------------------------------------------------------------------------------------------
echo.

set /p choice=Select an option (1-2, or 0 to exit):

if "%choice%"=="1" (
    echo Installing Google Gemini CLI globally...
    npm install -g @google/gemini-cli
    pause
    goto menu
)
if "%choice%"=="2" (
    echo Running Google Gemini CLI...
    gemini
    pause
    goto menu
)
if "%choice%"=="0" (
    echo Exiting...
    exit /b
)
echo Invalid selection. Please try again.
pause
goto menu