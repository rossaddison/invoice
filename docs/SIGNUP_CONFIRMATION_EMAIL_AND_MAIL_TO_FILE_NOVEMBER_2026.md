# The signup confirmation email, and `MAIL_TO_FILE` (November 2026)

Two separate problems showed up when the signup email was tested through a real inbox on a live server.

## 1. The email could look empty

The confirmation email was a single HTML link (`<body><a>...</a></body>`) with no plain-text part and no
document wrapper. A mail viewer that shows only the text part, or hides a message whose HTML is nothing but
one link, left the new user with nothing to click, and nothing to copy either.

`App\Auth\SignupConfirmationEmail` now builds both bodies, for the generic and the HomeCare signup:

| Part | Content |
|---|---|
| HTML | a complete document (`<!DOCTYPE html>`, charset) with the link, and **the address written out as text** underneath. The address is escaped |
| Plain text | the link text, a blank line, then the full address |

The subject, link text and link target are unchanged. If a client hides the link, the address is still
there to copy into a browser.

## 2. In dev mode the email never left the server

`config/web/di/mailer.php` points the mailer at `FileMailer` when `YII_ENV=dev`, so a developer never needs
a real inbox: every email is written to `runtime/mail/*.eml`. But `FileMailer` saves only the headers and
the plain-text part, and this app's emails were HTML only, so a saved `.eml` was about 37 bytes and never
held the confirm link. A server still on `YII_ENV=dev` therefore could not send, or even show, the email.
The signup page said "Email successfully sent" in both cases.

`MAIL_TO_FILE` in `.env` keeps the real transport under dev:

| `.env` | Under `YII_ENV=dev` |
|---|---|
| unset, blank, or any other value | emails are written to `runtime/mail/*.eml` (as before) |
| `0`, `false`, `no`, `off` (any case) | emails are sent through the configured SMTP transport |

Other environments (`prod`, `test`) never use the file mailer, with or without the setting. The real
transport takes its host, port, username and password from `SYMFONY_MAILER_*` and `SENDER_EMAIL` in `.env`;
Gmail needs an app password for SMTP.

## Testing the email

- Use a real inbox you control. A Gmail address accepts `name+anything@gmail.com`, which lands in the same
  inbox and counts as a different address to the signup form, so every test can use a fresh tag. Gmail's
  *Show original* shows the raw message.
- Disposable inbox sites often hide HTML-only messages and may not show a source. Use them only for
  throwaway signups.
- Each test signup is a real, unconfirmed account. The
  [cleanup command](UNCONFIRMED_SIGNUP_CLEANUP_NOVEMBER_2026.md) removes them.

## Known limits

- The failure page `site/signupfailed` is shared: it appears for a failed Turnstile check, a role that did
  not persist, **and** a mail exception, with the same fixed text. Read `runtime/logs/app.log` to see which.
- The email subject is still `login: <address>`. A plain subject would read better; it was left as it was.

## Related

- [Signup confirmation link](SIGNUP_CONFIRMATION_LINK_NOVEMBER_2026.md)
- [Signup hardening](SIGNUP_BOT_HARDENING_NOVEMBER_2026.md)
- [Live test checklist](SIGNUP_AND_LOGIN_LIVE_TEST_CHECKLIST_NOVEMBER_2026.md)
- [Yii environment, route cache and deploy](YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md)
