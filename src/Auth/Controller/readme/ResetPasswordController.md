# ResetPasswordController.php Overview

`ResetPasswordController.php` is responsible for the final and most critical step in the "forgot password" process.  
It handles requests from users who click the unique password reset link sent to their email, ensuring secure password recovery.

---

## Core Purpose

The main goal of this controller is to verify the password reset token from the URL and, if valid, allow the user to set a new password for their account.

---

## Key Components

### `__construct()`
Initializes the controller with essential services:
- **WebControllerService:** For creating redirect responses to success or failure pages.
- **ViewRenderer:** Renders the HTML form for entering a new password.
- **UrlGenerator:** Generates URLs within the application.
- **Translator:** Provides multi-language support for messages.
- **Logger:** Logs any errors during the process.

### `resetpassword()` Method

The single action method with the following workflow:

1. **Token Processing:**  
   Receives a `$maskedToken` from the URL, unmasks it to extract a unique random string and timestamp.

2. **Token Validation:**  
   - **Expiration Check:** Compares the token timestamp with the current server time (valid for 1 hour/3600 seconds).
   - **Database Verification:** If not expired, queries the database for a user identity matching the random string and reset token type.

3. **Password Form Handling:**  
   - If a valid user is found, displays the reset password view with a form for entering and confirming the new password.
   - On form submission, populates and validates the `ResetPasswordForm` (ensuring passwords match and meet complexity requirements).

4. **Security-Critical Updates:**  
   If the form is valid:
   - **Set New Password:** Updates the user's password in the database (securely hashed).
   - **Invalidate Reset Token:** Clears the password reset token from the database to prevent reuse.
   - **Generate New Auth Key:** Issues a new authentication key, invalidating all active "remember me" cookies and login sessions across devices.

5. **Redirection:**  
   After a successful reset, redirects the user to a "reset password success" page.

### Error Handling

- If the token is expired, invalid, or does not correspond to any user, logs an error and redirects the user to a "reset password failed" page.

---

## Summary

`ResetPasswordController.php` securely manages the conclusion of the password reset workflow.  
It validates the one-time-use token, provides an interface for setting a new password, and performs vital security cleanup by invalidating both the reset token and all active login sessions.