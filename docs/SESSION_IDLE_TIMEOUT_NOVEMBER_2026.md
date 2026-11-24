# Session idle timeout is a setting, and an expired session goes to the login page — November 2026

## In one paragraph

How long a signed-in session could sit unused was whatever PHP's defaults gave, and an expired session made every staff
page answer a **blank "Access denied" 403** that the visitor could not act on. There is now a setting, **Settings →
General → Session Idle Timeout (Minutes)** (`session_idle_minutes`, default **30**), enforced by the application, and a
visitor with no valid session who opens a page is sent to the **login page** with "Your session has expired". Both were
checked on the live site on 24 November.

## The setting

- Stored as `session_idle_minutes`; first-run default 30 (`InvoiceInstallTrait`); a tooltip explains it; the label is
  `session.idle.minutes` (English only).
- **An existing install has no row** until the General tab is saved once. A missing, zero, negative or non-numeric value
  behaves as 30 (`SettingRepository::sessionIdleMinutes()`).
- It applies to everyone, staff and guests.

## How it works

`SessionIdleTimeout` runs directly after `SessionMiddleware`:

1. It keeps the time of the last request in the session (`last_activity`) and clears the session when the gap is longer
   than the setting. That is the same wipe a logout does, so the visitor is signed out.
2. It raises PHP's `session.gc_maxlifetime` to the same length (never lowers it), so PHP does not delete the session file
   sooner than the setting says. PHP's own value only says when an old file *may* be deleted, on a random chance, so it is
   not a timeout anyone can rely on.
3. A request with **no session cookie** is left alone, so bots, webhooks and the Peppol endpoints do not start a session.
4. The time is rewritten at most once a minute, so a page does not cost a session write per request.

A valid **Remember Me** cookie (`autoLogin`, 30 days by default) still signs the visitor straight back in; that is a
separate, longer-lived choice made at login.

## The expired-session redirect

`AccessChecker` (the route gate) used to answer an empty 403 to anyone without the permission. For a signed-out visitor
opening a page (**GET or HEAD**) it now redirects to `auth/login?reason=session-expired`, which already shows "Your
session has expired" (the same notice `CsrfFailureHandler` uses for a stale CSRF token). It still answers **403** for a
form post, for htmx and XHR requests (a redirect would be swapped into the page), and for a **signed-in** user who lacks
the permission, including one waiting at the second-factor step.

## Known limits

- The once-a-minute rewrite means the time can be up to a minute stale, so with a **very short** timeout a visitor can be
  signed out up to a minute early. At 30 minutes it is negligible. Scaling the interval with the setting was considered
  and left for later.
- Any request carrying the session cookie counts as activity, including background requests.
- PHP's server-side default is not read from this install: on a development machine's CLI it is 1440 seconds; the web
  and live values were not checked.

## Tests

`SessionIdleTimeoutTest` (6), two `SettingRepositoryTest` cases, and four new `UserServiceGateTest` cases (a signed-out
page request redirects; a signed-out POST, a signed-out htmx/XHR request, and a signed-in user without permission keep the
403). Removing the idle clear, or the redirect, makes the matching test fail.

## Try it

Set the timeout to 1, sign in without Remember Me, open a staff page, wait about two minutes without touching anything,
and reload: the login page appears with the notice. To see the redirect alone, delete the session cookie in the browser
(and the `autoLogin` cookie if you ticked Remember Me) and reload.
