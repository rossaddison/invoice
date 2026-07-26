# Login Denial Message — Distinguish "Email Not Verified" from "Contact Administrator"

## Problem

`AuthController::handleNonTfaPath()` treats every inactive `UserInv` account the
same way at login: redirect to `site/adminmustmakeactive`, showing "This user is
marked as inactive. Please contact the system administrator." That message is
correct for an admin-deactivated account, but wrong and confusing for a HomeCare
signup customer who received their confirmation email and simply hasn't clicked
the link yet — "contact the system administrator" sends them down the wrong path
when the real fix is in their own inbox.

## Fix

`handleNonTfaPath()` now checks, before falling back to the generic message,
whether a live (unclicked) email-verification `Token` still exists for the
identity — covering both token types used across the two signup flows:
`email-verification` (generic `/signup`) and `homecare-email-verification`
(`/homecare-signup`). A token counts as "live" if it exists and its value has not
been overwritten with the `already_used_token_<timestamp>` marker that
`confirm()`/`processValidToken()` write once the link is clicked.

- If a live token is found → redirect to new route `site/emailnotverified`,
  showing **"Access Denied: Click on the verification link sent to your email
  address."**
- Otherwise → unchanged, redirects to `site/adminmustmakeactive` with the
  original "contact the system administrator" message.

New pieces, mirroring the existing `adminmustmakeactive` page exactly (route →
controller action → view → `CommonViewInjection` translation binding):

| Piece | Location |
|---|---|
| Translation key | `loginalert.user.emailnotverified` in `resources/messages/en/app.php` |
| View injection | `CommonViewInjection::webParameters()` → `'emailnotverified'` key |
| View | `resources/views/site/emailnotverified.php` (Bootstrap `Alert`, `AlertVariant::WARNING`) |
| Route | `GET/POST /emailnotverified` → `site/emailnotverified` in `config/common/routes/routes.php` |
| Controller action | `SiteController::emailnotverified()` |
| Check + redirect | `AuthController::hasUnusedEmailVerificationToken()` / `redirectToEmailNotVerified()` |

## Known follow-up gap — not fixed here

`handleNonTfaPath()` unconditionally calls `disableToken($tR, $userId,
'email-verification')` on this same code path, for an unrelated reason: it exists
to invalidate a leftover token for a console-created "observer" user who was never
sent a real verification email (see the inline comment referencing
[discussion #215](https://github.com/rossaddison/invoice/discussions/215)). Its
side effect: for a **generic** (non-HomeCare) signup, the very first login
attempt before clicking the link silently invalidates that user's real,
still-unclicked verification token — so the new "check your email" message only
displays correctly on that first attempt; a second attempt falls through to the
generic "contact administrator" message, and the emailed link itself no longer
works even if clicked afterward.

HomeCare signups are unaffected — their token type is `homecare-email-verification`,
which this `disableToken()` call never touches — so the new message keeps showing
correctly across repeated login attempts. Fixing the generic-signup case properly
requires reworking why `disableToken()` runs unconditionally on this path rather
than only for the actual console/observer scenario the comment describes; flagged
for a follow-up pass rather than bundled into this change.

(July 2026)
