# The "Remember me" cookie (November 2026)

Ticking *Remember me* never produced an `autoLogin` cookie, with two-factor authentication on or off. Two
independent bugs were behind it, found one after the other on the live site.

## Bug 1: the tick was never read

`FormHydrator` fills only the properties that have **validation rules**, unless it is given a map.
`LoginForm` validates `login` and `password` only, so `rememberMe` was never filled in and stayed `false`.
The login action then stored `remember_me = false` in the session for the code step, and
`TwoFactorAuth::applyRememberMe()` correctly did nothing.

Evidence on the server: after ticking the box and entering the password, the PHP session file held
`remember_me|b:0;`, and the response to the code step carried a `Set-Cookie` for `PHPSESSID` only.

**Fix:** `LoginForm::HYDRATED_PROPERTIES = ['rememberMe' => 'rememberMe']`, passed to
`populateFromPostAndValidate()` in the login action. It adds no validation rule, so it cannot make a login
fail. The browser posts the checkbox as a hidden `0` followed by the ticked `1`; PHP keeps the last value.

## Bug 2: a remember-me session got a 500 on every page

Once the cookie existed, a visit with only that cookie hit a second, older bug. `BaseController::
initializeViewRenderer()` chose the view folder and layout only when `tfa_verified === true`. A session
restored from the cookie never goes through the code step, so the key is absent, no branch applied, no
controller name was set, and `render('index')` looked for `resources/views/index.php`, which does not exist:
`The view file ".../resources/views/index.php" does not exist`.

**Fix:** only an explicit `false` (code still pending) keeps the layout unset; an absent value is a normal
session. This matches the gate in [`UserService`](TWO_FACTOR_AND_INACTIVE_ACCOUNT_GATES_NOVEMBER_2026.md).

## How it behaves now

| Login | `PHPSESSID` deleted, `autoLogin` kept | Result |
|---|---|---|
| ticked | yes | `/en/invoice` loads without a code prompt |
| not ticked | n/a (no cookie is set) | a guest: `/invoice` gives `403` |

The cookie lifetime is *Remember Me Duration (Days)* under *Settings, General* (default 30), read when the
container is built in `config/web/di/auth.php`.

## Checking it yourself

1. In a private window, log in with *Remember me* ticked and enter the code.
2. In the browser's developer tools, open the cookies for the site: `autoLogin` should be listed (refresh
   the list, it does not update by itself).
3. Delete only the session cookie, then reload `/en/invoice`: it should load without a code prompt.
4. Repeat without the tick: after deleting the session cookie you should be a guest.

Closing and reopening a browser is **not** a reliable test, because a browser set to *continue where you
left off* restores session cookies as well.

## Related

- [Two-factor and inactive-account gates](TWO_FACTOR_AND_INACTIVE_ACCOUNT_GATES_NOVEMBER_2026.md)
- [Live test checklist](SIGNUP_AND_LOGIN_LIVE_TEST_CHECKLIST_NOVEMBER_2026.md)
- [Test mock typing and CI Psalm](TEST_MOCK_TYPING_AND_CI_PSALM_NOVEMBER_2026.md)
