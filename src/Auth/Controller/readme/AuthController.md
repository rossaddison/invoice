# AuthController.php Overview

`AuthController.php` is the central component for handling **user authentication** within the application.  
It manages everything from traditional logins to complex multi-factor and social sign-on processes, ensuring a secure and seamless sign-in experience for users.

---

## Core Functionality

### Login/Logout

- **login():**  
  Handles the primary login form, checks user credentials, and manages subsequent authentication steps.  
  Prepares necessary URLs for various OAuth2 providers.

- **logout():**  
  Securely logs the user out, clears the session, and invalidates authentication tokens.

---

### Two-Factor Authentication (TFA/2FA)

- Implements a full Time-based One-Time Password (TOTP) flow.
- **showSetup():**  
  Generates a QR code and secret key for setting up TFA with an authenticator app (e.g., Google Authenticator, Aegis).
- **verifySetup():**  
  Validates the first TFA code to confirm device configuration.
- **verifyLogin():**  
  Verifies the TFA code during each login attempt for users with TFA enabled.
- **regenerateCodes():**  
  Allows users to generate new backup recovery codes.

---

### OAuth2 Social Login

- Integrates with third-party authentication providers (Google, GitHub, Facebook, Microsoft, LinkedIn, etc.).
- The `login()` method generates authentication URLs for each enabled provider.
- Utilizes Callback and Oauth2 traits to manage OAuth2 redirect flow, token exchange, and user profile retrieval after third-party authentication.

---

## Security Features

- **Rate Limiting:**  
  `checkRateLimit()` prevents brute-force attacks on login and TFA endpoints by limiting attempts from a single IP.
- **CSRF Protection:**  
  `blockInvalidState()` validates the OAuth2 state parameter to prevent Cross-Site Request Forgery attacks.
- **Secure Session Management:**  
  Regenerates the session ID upon login and securely clears sensitive data from session and memory during logout or after use.
- **Access Control:**  
  Dynamically adjusts user permissions during the TFA process, ensuring users cannot access the main application until authentication is complete.

---

## Other Responsibilities

- **User Activation:**  
  Handles user activation status, distinguishing between admin-activated accounts and those verified via email, especially for external provider sign-ups.
- **Dependency Injection:**  
  Relies heavily on dependency injection for authentication, database access, session management, and more—following modern PHP best practices.

---

## Summary

`AuthController.php` is a robust, security-conscious gateway that orchestrates the entire user sign-in experience—whether through simple password entry, multi-factor verification, or social login.  
It combines advanced security measures, flexible authentication mechanisms, and modern development practices to keep user data safe and authentication seamless.