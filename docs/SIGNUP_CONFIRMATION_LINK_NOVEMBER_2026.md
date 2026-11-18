# The signup confirmation link (November 2026)

After signing up, the new user is emailed a link. Opening it used to confirm the account at once. It now
shows a page with a button, and only pressing the button confirms.

## Why

Mail scanners, link previewers and some security gateways fetch every link in a message with a GET request.
If a GET both spent the one-time token and activated the account, a scanner could confirm accounts nobody
meant to confirm, or burn the token before the user reached it. A GET must therefore change nothing.

## How it works

The link points at `/userinv/signup/{language}/{token}/{tokenType}` (generic signup) or
`homecare/signup-confirm` (HomeCare signup).

| Request | What happens |
|---|---|
| `GET` | renders `resources/views/site/signupconfirm.php`: "Confirm my account" and a button. Nothing is read from the token, nothing is changed |
| `POST` (the button) | unmasks the token, strips the timestamp, then `UserInvController::processValidToken()` |

`processValidToken()` in order:

1. finds the identity by the token text and type, and the matching user-inv row;
2. **burns the token first**: it is renamed `already_used_token_<time>` and saved, before anything is
   activated, so a second attempt cannot find it;
3. checks the age against the server's own `Token::getCreatedAt()` plus `SIGNUP_TOKEN_LIFETIME_SECONDS`
   (3600, one hour). The timestamp at the end of the link is **not** trusted for expiry, because anyone
   holding the link can rewrite it;
4. sets `userinv.active` and, when *Assign a client to user on signup* is on in Settings, creates and
   assigns the client.

An expired token is burned too, so it cannot be tried again.

## The language segment

The link carries a language name (for example `English`). It is rewritable, so
`App\Auth\SignupLanguage::resolve()` prefers the language stored on the user-inv row when the signup
started, then a value from the known locale list, and falls back to `English`. An arbitrary string can no
longer be stored on the new client.

## Throttling

The confirm routes throttle **GET as well**, because the emailed link is a GET:
`RateLimiter::globalAnyMethod(120)` and `RateLimiter::perIpAnyMethod(10, ...)`, 10 per 60 seconds per real
IP and 120 across everyone. They answer a plain `429` themselves. The ordinary limiters ignore GET and
redirect back to the same URL, which would loop forever for a throttled GET (a real browser hit
`ERR_TOO_MANY_REDIRECTS` before this was separated).

## Known limits

- **An old link still shows its button.** The page does not look at the token, so a link that has already
  been used shows the button and only the press fails (with a "No token" warning, nothing changes).
  Showing "already used" on the page would tell anyone holding a link whether it is still live, so this is
  accepted as is.
- A link clicked after it has expired burns its token, so the account is not listed by the
  [cleanup command](UNCONFIRMED_SIGNUP_CLEANUP_NOVEMBER_2026.md), which only lists links never clicked.
- The page text exists in English only (`signup.confirm.account`, `signup.confirm.account.intro`).

## Related

- [Signup hardening](SIGNUP_BOT_HARDENING_NOVEMBER_2026.md)
- [The confirmation email](SIGNUP_CONFIRMATION_EMAIL_AND_MAIL_TO_FILE_NOVEMBER_2026.md)
- [The user-inv extension row](USERINV_EXTENSION_ROW_AND_COLUMN_TYPES_NOVEMBER_2026.md)
