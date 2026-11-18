# Two-factor and inactive-account gates (November 2026)

Two gaps were found in the login flow by reading the code and then confirmed on the live site. Both are now
closed at the permission layer, so they hold on every route.

## 1. A password alone was enough

`CurrentUser::login()` puts the identity into the session at the **password** step, before the one-time
code is entered. The session also holds `tfa_verified`: `false` once the password is accepted and the code
is pending, `true` after the code. But nothing used the flag to withhold permissions, so a session that had
only passed the password step could open pages the role allowed.

`App\User\UserService::hasPermission()` now returns `false` while `tfa_verified === false`. Only an
**explicit** `false` blocks: a session that never went through the code step has no such value and is
unaffected (remember-me cookie, OAuth, 2FA switched off). The route gate (`RoutePermission`,
`AccessChecker`) goes through `UserService`, so every protected route is covered.

## 2. An inactive account kept its session

An account that is not active (`userinv.active = 0`, set to 1 when the emailed link is confirmed) was sent
to a "not verified" page after the password step, but its identity stayed in the session. Two changes:

- **At login.** `AuthController` (and the OAuth callback) now call `logout()` and `regenerateId()` before
  redirecting a non-admin inactive account to the public `site/emailnotverified` or
  `site/adminmustmakeactive` page. Nothing is lost, because both pages are public.
- **During a session.** `UserService::hasPermission()` also returns `false` when the user's `user_inv` row is
  inactive and the user is not an administrator, so an administrator who deactivates an account sees it
  lose access on its next request. The row is read once per user per request.

Administrators are exempt from the inactive rule, exactly as they are at login, because activation never
locks them out. An account with no `user_inv` row is left to the role check as before.

## A trap: asking whether the user has the permission `admin`

The first version of the exemption asked the access checker `userHasPermission($id, 'admin')`. That always
answers **false** in this project: the RBAC `Manager` is built with `includeRolesInAccessChecks = false`, so
a *role* name is never a permission. The exemption never exempted anyone, and an administrator whose
`userinv.active` was 0 was refused every page after logging in. It was caught on the live site, not by the
unit tests (they had mocked the call).

`UserService::isAdmin()` now reads `Manager::getRolesByUserId()`, as `AuthController` does, and the tests'
access checker answers `false` for role names like the real one.

## Known limit

A deactivated user who is already logged in gets `403` responses; they are not signed out. That is safe, and
a request-pipeline sign-out for such users was left out as a separate change.

## What was verified live

- administrator: password then code works; the password step alone gets `403` on `/invoice`;
- an inactive non-admin is signed out at login, on both public pages;
- deactivating a logged-in non-admin gives `403` on its next request (use two separate windows: tabs of one
  browser share a single session).

## Related

- [Remember-me cookie fix](REMEMBER_ME_COOKIE_FIX_NOVEMBER_2026.md)
- [Signup hardening](SIGNUP_BOT_HARDENING_NOVEMBER_2026.md)
- [Live test checklist](SIGNUP_AND_LOGIN_LIVE_TEST_CHECKLIST_NOVEMBER_2026.md)
- [WWW-Authenticate vs session auth](WWW_AUTHENTICATE_VS_SESSION_AUTH_AUGUST_2026.md)
