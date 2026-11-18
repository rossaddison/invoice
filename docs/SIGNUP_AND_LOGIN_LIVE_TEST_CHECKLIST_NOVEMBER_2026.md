# Live test checklist: signup, login and confirmation (November 2026)

The unit tests mock their collaborators, and several real faults in this area were found only on a running
site. After deploying these changes, run this checklist once. Each step says what a correct result looks
like.

## Before you start

- Set both Turnstile keys (*Settings, Turnstile*). A blank secret makes every signup refuse, by design.
- Deploy, then restart the web server. APCu is per process pool, so `php yii cache/clear` from the command
  line does not reach the web workers.
- If the site is still on `YII_ENV=dev`, add `MAIL_TO_FILE=0` to `.env` so the signup email is really sent
  ([details](SIGNUP_CONFIRMATION_EMAIL_AND_MAIL_TO_FILE_NOVEMBER_2026.md)).
- Keep an **administrator session open** in one window until the administrator login is verified in a
  fresh one.
- Use a **private window** for each fresh visitor. Tabs of one browser share a single session.

## Login and two-factor

| # | Do | Expected |
|---|---|---|
| 1 | Administrator: password, then the code | lands on the invoice dashboard |
| 2 | Administrator: password only, then open `/en/invoice` | `403` ([why](TWO_FACTOR_AND_INACTIVE_ACCOUNT_GATES_NOVEMBER_2026.md)) |
| 3 | An inactive non-administrator logs in | sent to a public page, **signed out** (the nav shows *Login*) |
| 4 | Log a non-administrator in (separate window), then deactivate it as administrator, then reload | `403` on the next request |

## Remember me

| # | Do | Expected |
|---|---|---|
| 5 | Log in ticked, enter the code, delete only the session cookie, reload `/en/invoice` | still logged in |
| 6 | Log in **not** ticked, delete the session cookie, reload | a guest (`403`) |

See [the cookie fix](REMEMBER_ME_COOKIE_FIX_NOVEMBER_2026.md) for how to look at the cookies.

## Signup and confirmation

Use an inbox you control and a fresh login each time (for example `virtualtest01`). A Gmail address accepts
`name+tag@gmail.com`.

| # | Do | Expected |
|---|---|---|
| 7 | Sign up with a login containing a space | refused, with the login-format message |
| 8 | Sign up validly, with the Turnstile box showing *Success* | "Email successfully sent" |
| 9 | Open the email | the confirm link, and the full address written out beneath it |
| 10 | Open the link | a page with a **Confirm my account** button; nothing is confirmed yet |
| 11 | Press the button | the account is active |
| 12 | Press it again, or use the link again | refused with a "No token" warning; nothing changes (the page still shows the button) |
| 13 | In the admin user list | the account is active, and its RBAC link is green |

If a signup ends on the "email was NOT sent" page, that page is shared by three failures (Turnstile, role
assignment, mail); look in `runtime/logs/app.log` for which.

## Cleanup

| # | Do | Expected |
|---|---|---|
| 14 | `php yii signup/cleanup-unconfirmed` | lists unconfirmed signups older than a day; deletes nothing |
| 15 | After backing up, `... --force`, then the dry run again | the second run lists nothing |

See [the cleanup page](UNCONFIRMED_SIGNUP_CLEANUP_NOVEMBER_2026.md).

## Related

- [Signup hardening](SIGNUP_BOT_HARDENING_NOVEMBER_2026.md)
- [Signup confirmation link](SIGNUP_CONFIRMATION_LINK_NOVEMBER_2026.md)
- [Yii environment, route cache and deploy](YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md)
