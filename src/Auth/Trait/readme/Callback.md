# Callback.php Overview

`src/Auth/Trait/Callback.php` is a PHP trait designed for code reuse in single-inheritance languages like PHP.  
The methods defined in this trait are used within the `AuthController` to handle the server-side part of an OAuth2 login flow.

---

## Core Purpose

When a user attempts to log in using a third-party service (such as Google, GitHub, Facebook, etc.), they are redirected to that service's website for approval.  
After approval, the service redirects the user back to a specific callback URL within this application.  
`Callback.php` contains the controller methods that handle these incoming redirects.

The primary responsibilities of each callback method are:
1. **Securely validate the response** from the OAuth2 provider.
2. **Exchange the temporary code** for a permanent `access_token`.
3. **Fetch the user's profile information** (ID, email, name) using the `access_token`.
4. **Log the user in** if they already have an account, or **create a new account** if they don't.
5. **Integrate with application systems** such as Two-Factor Authentication (TFA) and Role-Based Access Control (RBAC).

---

## Workflow of a Typical Callback Method (e.g., `callbackGithub`)

Each method in the trait (e.g., `callbackGithub`, `callbackGoogle`, `callbackFacebook`) follows a standardized pattern:

1. **Receive Parameters:**  
   Receives `code` and `state` query parameters from the OAuth2 provider.

2. **Security Validation:**  
   - Checks if `code` and `state` are present; returns error if missing.
   - Calls `blockInvalidState('github', $state)` to prevent Cross-Site Request Forgery (CSRF) attacks by verifying the `state` parameter against the user's session.

3. **Fetch Access Token:**  
   Exchanges the `$code` for an `access_token` via a secure server-to-server API call.

4. **Fetch User Profile:**  
   Uses the `access_token` to retrieve the user's profile details from the provider (e.g., GitHub ID, login name, email).

5. **Log In or Sign Up:**  
   - Creates a unique login identifier (e.g., `github + GitHub ID + GitHub login`).
   - Checks if the user exists via `$this->authService->oauthLogin($login)`.
   - If the user exists: Calls `tfaCheckBeforeRedirects()` to handle TFA if enabled.
   - If the user does not exist: Creates a new user record with the fetched details and a randomly generated password.

6. **Assign Role:**  
   Assigns a role to new users: the first user is made an admin, subsequent users become observers.

7. **Finalize:**  
   - For new users: Renders a "Proceed" page to finalize account setup.
   - For existing users (after TFA check): Redirects to the main application index.

---

## Helper Methods

- **`tfaCheckBeforeRedirects()`:**  
  After a successful social login, checks if TFA is enabled for the user.  
  Redirects to TFA verification if necessary, otherwise logs the user in and redirects to the main page.

- **Redirect Helpers:**  
  Includes methods for redirecting to specific error pages, such as `redirectToOauth2AuthError()` and `redirectToUserCancelledOauth2()`.

---

## Summary

This trait is a collection of endpoints serving as the final destination for social logins.  
It securely manages OAuth2 data exchange and integrates external user identities into the local application's user system, including support for TFA and RBAC.