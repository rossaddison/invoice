# SignupController.php Overview

`SignupController.php` manages the user registration process for the application.  
It supports both traditional signups using email and password, and registration via third-party OAuth2 providers (such as Google, GitHub, etc.).

---

## Core Purpose

The controller's primary goal is to allow new users to create an account.  
Upon successful registration, it initiates an email verification process to ensure the user's email address is valid.

---

## Key Components

### `__construct()`
The constructor sets up the controller by injecting a range of services:
- **RBAC (`Manager`)**: Initializes the Role-Based Access Control manager to assign roles to new users.
- **OAuth2 Clients**: Loads credentials for various social login providers (Google, Facebook, GitHub, etc.) using the Oauth2 trait.
- **Core Services**: Injects services for sending emails (`MailerInterface`), managing sessions and flash messages (`SessionInterface`, `Flash`), handling translations (`Translator`), generating URLs (`UrlGenerator`), and logging errors (`LoggerInterface`).

### `signup()` Method

The main and only public action in the controller. Handles both displaying and processing the signup form.

1. **Guest Check**:  
   Ensures the current user is not already logged in. If they are, redirects them away from the signup page.
2. **Form Display (`GET` request)**:  
   Renders the signup view, passing the `SignupForm` model and authentication URLs for all OAuth2 providers so users can choose their preferred signup method.
3. **Form Processing (`POST` request)**:  
   - Populates and validates the `SignupForm` model (email format, password complexity, password match).
   - If validation passes, creates a new user and saves it to the database.
4. **Post-Registration Steps**:
   - **Role Assignment**: The first user to sign up is assigned the admin role; all subsequent users get the observer role.
   - **Email Verification**: Generates a unique, time-stamped token for email verification and saves it to the database.
   - **UserInv Record**: Creates a related `UserInv` record and sets its status to inactive pending email verification.
   - **Send Confirmation Email**: Sends an HTML email with a unique verification link to the user.
   - **Redirection**: Redirects user to "signup success" page; if email fails to send, redirects to "signup failed" page.

---

## Helper Methods

- **`htmlBodyWithMaskedRandomAndTimeTokenLink()`**:  
  Builds the HTML content for the verification email, including a clickable confirmation link with a masked token.
- **`getEmailVerificationToken()`**:  
  Generates and persists the email verification token for the new user.

---

## Summary

`SignupController.php` is the central hub for user registration.  
It securely handles new user creation, assigns appropriate roles, enforces an email verification workflow, and integrates seamlessly with social login options.