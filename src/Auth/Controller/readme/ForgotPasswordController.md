# ForgotPasswordController.php Overview

`ForgotPasswordController.php` is the first step in the "forgot password" workflow.  
Its job is to take an email address from a user, verify that the user exists, and then generate and email them a unique, secure link to reset their password.

---

## Core Purpose

The controller provides a form for users who have forgotten their password.  
When a user submits their email, this controller generates a time-sensitive, single-use token and emails a reset link containing this token to the user.

---

## Key Components

### `__construct()`
Sets up the controller by injecting necessary services:
- **WebControllerService:** Handles redirects to various pages (success or failure).
- **ViewRenderer:** Renders the HTML form where the user enters their email.
- **MailerInterface:** Sends the actual email.
- **sR (SettingRepository):** Checks system settings, ensuring email functionality is enabled and configured.
- **Translator, UrlGenerator, Logger:** For handling translations, creating the reset link, and logging errors.

### `forgot()` Method

The main and only action in the controller:

1. **Authentication Check:**  
   Ensures the user is not already logged in. Logged-in users should use "Change Password" instead.
2. **Mailer Check:**  
   Verifies that the application's email system is configured. Redirects to an error page if unable to send a reset link.
3. **Form Display (`GET` request):**  
   Displays the form asking for the user's email address.
4. **Form Submission (`POST` request):**  
   - Validates the submitted email address.
   - Searches the database for a user with that email.
   - **Token Generation:**
     - If user found, checks for a valid (non-expired) password reset token (valid for 1 hour/3600 seconds).
     - If a valid token exists, re-sends the link with that token.
     - If no valid token exists, generates a new unique token, saves it to the database with a timestamp.
   - **Email Sending:**  
     - Builds an HTML email with the password reset link, including a masked token for security.
     - Sends the email to the user.
5. **Security Obfuscation:**  
   Regardless of whether a user was found, redirects to a generic confirmation page (site/forgotalert).  
   This prevents attackers from discovering which email addresses are registered (prevents "user enumeration" attacks).

---

## Helper Methods

- **`requestPasswordResetToken()`:**  
  Creates a new Token record in the database, generates a random string, associates it with the user's ID, and returns the combined token (random string + timestamp).
- **`htmlBodyWithMaskedRandomAndTimeTokenLink()`:**  
  Builds the HTML content for the email, creating the full clickable reset link with the masked token.

---

## Summary

`ForgotPasswordController.php` securely initiates the password reset process.  
It validates the user's request, generates a time-limited and secure token, emails the reset link, and protects against common vulnerabilities such as user enumeration.