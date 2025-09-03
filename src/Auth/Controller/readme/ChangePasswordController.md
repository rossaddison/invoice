# ChangePasswordController.php Overview

`ChangePasswordController.php` is responsible for handling the process of a logged-in user changing their own password.

---

## Core Purpose

The controller's single public method, `change()`, orchestrates the password change process.  
It ensures that only authenticated users have access to this functionality and manages the form display, data validation, and the final password update.

---

## Key Components

### `__construct()`
The constructor uses dependency injection to load essential services:
- **Session and Flash:** Manage user session data and display temporary "flash" messages (e.g., "Password changed successfully").
- **Translator:** Provide multi-language support for messages.
- **CurrentUser:** Retrieve information about the currently authenticated user.
- **WebControllerService:** Helper for creating redirect responses.
- **ViewRenderer:** Render the HTML page for the change password form.

### `change()` Method

The main action of the controller:
1. **Authentication Check:**  
   Verifies that the user is logged in. If not, redirects to the homepage.
2. **Form Handling:**  
   - On GET requests: Renders the change view, passing a `ChangePasswordForm` model.
   - On POST requests: Populates `ChangePasswordForm` with submitted data (old password, new password, confirmation).
3. **Validation and Update:**  
   Calls the `change()` method on the form model, which performs validation (checks old password, matches new passwords, enforces complexity) and updates the database.
4. **Security Logout:**  
   If the password change is successful, immediately calls `$authService->logout()`.  
   This critical security measure invalidates the session and any "remember me" cookies, forcing the user to log in again with the new password.
5. **Redirect:**  
   Sets a success flash message and redirects the user to the main site index.

---

## Helper Methods

- **flashMessage():**  
  Private utility to simplify adding flash messages to the session.
- **redirectToMain():**  
  Private utility to centralize the redirection logic to the homepage.

---

## Summary

This controller provides a secure and straightforward workflow for users to update their passwords.  
It delegates complex validation and database interaction logic to `ChangePasswordForm` and enforces a key security practice by logging the user out immediately after a password change.