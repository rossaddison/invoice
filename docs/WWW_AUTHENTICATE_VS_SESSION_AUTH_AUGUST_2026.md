# `WWW-Authenticate` vs. Session Auth — Plain-English Explainer

## Background

Written while investigating the [Authentication DI crash fix](AUTHENTICATION_DI_CRASH_FIX_AUGUST_2026.md)
and reading through `yiisoft/auth`'s active interface-segregation work
([issue #113](https://github.com/yiisoft/auth/issues/113),
[PR #115](https://github.com/yiisoft/auth/pull/115),
[PR #125](https://github.com/yiisoft/auth/pull/125)). The exception this
app actually hit —

```
No definition or class found for "Yiisoft\Auth\Middleware\Authentication" ID.
— Yiisoft\Auth\Di\NotFoundException (Code #0)

No definition or class found or resolvable for "Yiisoft\Auth\AuthenticatorInterface"
while building "Yiisoft\Auth\Middleware\Authentication" -> "Yiisoft\Auth\AuthenticatorInterface".

Ensure that either a service with ID "Yiisoft\Auth\Middleware\Authentication" is defined
or such class exists and is autoloadable.
```

— is technically correct but actively misleading: its own remediation
sentence says to check that the `Authentication` class "exists and is
autoloadable," which was never the problem. The real cause is one
dependency deeper (`AuthenticatorInterface` has no bound implementation),
and the message never says so, nor offers an example fix.

## Proposed replacement message

```
Cannot construct Yiisoft\Auth\Middleware\Authentication: no implementation is bound
for Yiisoft\Auth\AuthenticatorInterface.

This middleware needs a concrete authenticator (e.g. HttpBasic, HttpBearer, Composite)
wired to Yiisoft\Auth\AuthenticatorInterface in your DI configuration before it can be
used — e.g. in config/web/di/auth.php:

    AuthenticatorInterface::class => HttpBearer::class,

If you don't need HTTP-challenge-style authentication (session/cookie login instead),
don't apply this middleware at all.
```

This names the actual missing binding by its real interface name, gives a
copy-pasteable one-line fix, and — the part the current message can't
offer because it doesn't know the caller's intent — names the equally
valid alternative: don't apply the middleware at all, which is what this
app's own fix ultimately was.

## The underlying confusion, explained plainly

The deeper reason this trips people up isn't the wording of one exception
— it's that `yiisoft/auth`'s "Authentication" and `yiisoft/user`'s
session-based login solve two genuinely different problems that share a
name. Once the difference clicks, the exception (and the fix) is obvious.

### What

**Session-based login** is like a **festival wristband**. You show up
once, someone checks your ID at the gate and puts a wristband on you
(that's "logging in" — the wristband is a cookie). After that, every time
you walk up to any stage, the guard just glances at your wrist — no ID,
no questions. If you're not wearing one, the guard doesn't interrogate you
on the spot; he points you back to the check-in booth (a redirect to a
login *page*).

**HTTP-challenge-style authentication** (`WWW-Authenticate`, Basic/Bearer)
is like a **bouncer with no wristbands at all**. Every single door, every
single time, you show ID right there. There's no check-in booth to walk
you to — the bouncer just states the exact rule on the spot: *"I only
accept passport or driver's licence."* That sentence — the exact list of
ID formats he'll accept — is literally what the `WWW-Authenticate` header
is. You then hand over that exact ID on your very next attempt.

### Why

Session-based login exists because a **human sitting in a browser,
clicking around**, doesn't want to retype a password on every click. A
wristband remembers them for a while. When they're not wearing one, the
sensible fix is a human-friendly action: walk them to a desk with a form
on it.

HTTP-challenge-style authentication exists because the "visitor" isn't a
human with hands to fill in a form — it's a **program**: a script, another
company's server, a phone app talking straight to an API. A program can't
walk itself to a login page and click a mouse. It just needs a flat,
machine-readable instruction — *"attach a Bearer token"* — so it can retry
the exact same request correctly, with no human involved anywhere.

### When

Ask one question: **is a human clicking through pages in a browser, or is
a program making one standalone request?**

- **Human in a browser, multi-page session → session-based.** That's
  every page in this app today, correctly.
- **Program, one-shot request, no browser involved → HTTP-challenge-style.**
  This app has exactly one endpoint shaped like that right now: the
  recurring-invoice cron trigger (`InvRecurringController::cron()`) — a
  script (`curl` from a cron scheduler) hitting a URL once, no human, no
  browser. It currently does the wrong thing for its own shape: it checks
  a secret glued into the URL query string instead of the
  ID-on-request pattern challenge-style auth was built for.

The trigger for this app ever needing `WWW-Authenticate` for real isn't
"`yiisoft/auth` is installed" — it's the day this app grows **any**
feature where a program, not a person, talks to it directly and expects a
standard machine-readable retry instruction back. Until that day, the
mechanism has nothing to do here, which is exactly why removing
`Authentication::class` from `RoutePermission::invoiceGroup()` was the
correct fix rather than trying to configure it.
