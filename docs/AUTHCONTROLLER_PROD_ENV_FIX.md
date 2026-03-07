# AuthController Production Environment Fix

**Date:** March 2026  
**Issue:** AuthController error when changing YII_ENV from 'dev' to 'prod'  
**Location:** `src/Auth/Trait/Oauth2.php`

## Problem Description

When switching the `.env` file's `YII_ENV` setting from `dev` to `prod`, the application threw an error related to the `AuthController`. The root cause was in the `initializeOauth2IdentityProviderDualUrls()` method within the `Oauth2` trait.

## Root Cause

In `src/Auth/Trait/Oauth2.php`, the `initializeOauth2IdentityProviderDualUrls()` method had a variable scope issue:

```php
private function initializeOauth2IdentityProviderDualUrls(): void
{
    if ($this->sR->getEnv() == 'dev') {
        $authChoice = AuthChoice::widget();
        $developerSandboxHmrc = $authChoice->getClient('developersandboxhmrc');
        /** @psalm-var \App\Auth\Client\DeveloperSandboxHmrc $developerSandboxHmrc */
        $developerSandboxHmrc->setEnvironment('dev');
    } else {
        /** @psalm-var \App\Auth\Client\DeveloperSandboxHmrc $developerSandboxHmrc */
        $developerSandboxHmrc->setEnvironment('prod');  // ❌ Variable undefined!
    }
}
```

**The Issue:**
- When `YII_ENV=dev`: The code initialized `$authChoice` and `$developerSandboxHmrc` within the if block
- When `YII_ENV=prod`: The else block attempted to use `$developerSandboxHmrc` without initializing it first, resulting in an undefined variable error

## Solution

The fix moved the initialization of `$authChoice` and `$developerSandboxHmrc` outside the conditional block, making them available to both branches:

```php
private function initializeOauth2IdentityProviderDualUrls(): void
{
    $authChoice = AuthChoice::widget();
    $developerSandboxHmrc = $authChoice->getClient('developersandboxhmrc');
    /** @psalm-var \App\Auth\Client\DeveloperSandboxHmrc $developerSandboxHmrc */
    
    if ($this->sR->getEnv() == 'dev') {
        $developerSandboxHmrc->setEnvironment('dev');
    } else {
        $developerSandboxHmrc->setEnvironment('prod');  // ✅ Variable now defined!
    }
}
```

## Impact

- **Before:** Switching to production environment (`YII_ENV=prod`) caused AuthController to fail
- **After:** Application works correctly in both development and production environments

## Testing

To verify the fix:

1. Set `YII_ENV=dev` in `.env` file - application should work
2. Set `YII_ENV=prod` in `.env` file - application should work without errors
3. Verify OAuth2 authentication flows function correctly in both environments

## Related Files

- `src/Auth/Trait/Oauth2.php` - Contains the fix
- `src/Auth/Controller/AuthController.php` - Uses the Oauth2 trait
- `src/Auth/Controller/SignupController.php` - Also uses the Oauth2 trait
- `.env` - Environment configuration file

## Notes

This method is called during controller initialization and is responsible for configuring the Developer Sandbox HMRC OAuth2 client based on the current environment. The HMRC client requires different URLs for sandbox (dev) versus production environments.
