# Oauth2.php Overview

`src/Auth/Trait/Oauth2.php` is a PHP trait that provides helper functions and initialization logic for the OAuth2 social login system.  
Its code is reused by other classes—primarily `AuthController` and `SignupController`—to avoid duplication and centralize configuration.

---

## Core Purpose

The Oauth2 trait has two main responsibilities:

1. **Configuration & Initialization:**  
   Reads credentials (Client ID, Client Secret) and settings from the application's configuration and applies them to each third-party authentication client (e.g., Google, GitHub, Facebook).

2. **Token Generation:**  
   Provides a standardized way to generate and store a database token that signifies a user has authenticated through a specific social provider.

---

## Key Methods

### `initializeOauth2IdentityProviderCredentials()`
- Central setup method, called from controller constructors needing social login.
- Accepts all OAuth2 client objects (e.g., DeveloperSandboxHmrc, Facebook, GitHub, Google).
- Retrieves sensitive credentials (Client ID, Client Secret, Return URL) from secure app settings (via `SettingRepository`, not hardcoded).
- Configures each client object, making them ready for authentication requests.
- Contains specific logic for providers (e.g., Microsoft Online) that require additional configuration like tenant IDs.

### `initializeOauth2IdentityProviderDualUrls()`
- Helper method for the DeveloperSandboxHmrc client.
- Sets API URLs based on the application's environment (dev or prod), allowing sandbox/testing during development and live service in production.

### `getAccessToken()`
- Generic private helper method for creating provider-specific tokens.
- Called when a user authenticates via a social provider.
- Creates a new Token record in the database, with the token type set to a unique provider string (e.g., `github-access`), linked to the user's ID.
- Serves as a permanent record of social provider authentication.

### Provider-Specific Token Methods
- `getDeveloperSandboxHmrcAccessToken()`, `getGithubAccessToken()`, `getGoogleAccessToken()`, etc.
- Public wrappers for `getAccessToken()`, each specifying the correct token type constant for the provider.
- Used by callback methods (e.g., `callbackGithub` in the Callback trait) to create provider-specific tokens.

---

## Summary

The Oauth2 trait is a crucial configuration and utility layer for the social login system.  
It neatly separates credential loading and token creation from the main authentication workflow, making controllers cleaner and the system easier to manage and extend with new providers.