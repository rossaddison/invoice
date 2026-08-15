# 2FA Six-Box OTP Entry — Shipped, Broke Login on Production, Reverted (August 2026)

## Summary

Earlier the same day, the single free-text 2FA OTP input on `setup.php`
(enrollment) and `verify.php` (every login) was redesigned into six
single-digit auto-advancing boxes (`OtpBoxInput` in
`keypad-copy-to-clipboard.ts`), with `verify.php` additionally growing an
8-character hex box group for backup recovery codes and a toggle link
between the two. All of it was live-verified end-to-end via Playwright
against a real account, full Testo/PHPUnit/vitest suites passed, and it
was merged to `main` as PR #1082.

Once deployed to yii3i.online (production), it locked users out of login
entirely. This document records the incident and the emergency revert;
the box-entry UI itself is not currently in the codebase.

## Symptom

On the live login flow: the six boxes rendered and accepted keystrokes,
but auto-advance to the next box never happened (confirmed by the user —
they had to Tab manually), the "Use a recovery code instead" link did
nothing when clicked, and clicking Submit triggered the browser's native
"Please fill in this field" validation on the real, hidden `#code`
input — meaning none of the typed digits were ever reaching the field
the backend actually validates and submits.

## Investigation

All three symptoms trace to one shared cause: `OtpBoxInput`'s `input`
event handler is what both advances focus *and* syncs each box's value
into the hidden `#code` field in the same call — if it never ran, none
of the three behaviors would work, which matches exactly what was
observed.

Confirmed via direct inspection on the production server (SSH) and the
browser:

- The correct, current build of `keypad-copy-to-clipboard-iife.js` (containing
  the box logic) was present and correct both as the git-tracked source
  file and as the AssetManager's published copy under `public/assets/`.
- `git log` on the server showed `main` already at the correct commit
  (`git pull` reported "Already up to date") — this was never a stale
  deploy.
- Neither the Apache error log nor the application's own Yii3 logger
  (`runtime/logs/app.log`) showed any exception, warning, or crash
  around the time of testing.
- `document.querySelectorAll('script')` in the live page confirmed only
  2 `<script>` tags were actually present in the rendered DOM
  (`authchoice.js`, `bootstrap.bundle.js`) — `AuthAegisTotpKeypadAsset`'s
  JS was genuinely never rendered, despite being registered
  unconditionally in the shared layout (`main.php`) at the identical
  call site as the working `authchoice.js` registration, and despite
  its CSS half (`otp-input.css`) rendering correctly from the same
  bundle.
- Along the way, a real, separate, **pre-existing** bug was found and
  is *not* fixed by this revert: the rendered page reported "Quirks
  Mode" in Chrome DevTools, traced to a stray, duplicated
  `<div data-authchoice id="yii-auth-client">` wrapper appearing
  *before* the page's own `<!doctype html>` — almost certainly from the
  `yiisoft/yii-auth-client` `AuthChoice` widget's rendering. This may or
  may not be related to the missing script tag; it was not proven
  causal before the decision was made to revert rather than keep
  debugging under time pressure with the user locked out of login.

Root cause of the missing `<script>` tag was **not conclusively
identified** before the decision was made to revert.

## Resolution

Reverted `resources/views/auth/{setup,verify}.php` and
`src/Auth/Asset/keypad-copy-to-clipboard.ts` to their exact pre-feature
state, removed the now-unused `otp-input.css`, and restored
`AuthAegisTotpKeypadAsset.php`'s original empty `$css` array — back to
the single wide text input plus the pre-existing digit-pad buttons that
were already working reliably. `AuthAegisTotpKeypadAsset` itself and its
JS file are pre-existing infrastructure (toggle-secret visibility, copy
secret to clipboard, digit-pad append/clear) that predate this feature
entirely and were kept.

Live-verified locally end-to-end: plain `#code` input present, zero
`.otp-box` elements, a real generated TOTP code submitted successfully,
redirected to `/invoice`.

## Next attempt

Revisiting box-based OTP entry is deliberately deferred until after more
payment gateways are registered/onboarded — not abandoned, just
lower-priority than the in-progress gateway work.

## Open items for anyone revisiting box-based OTP entry later

- Find the actual reason `AuthAegisTotpKeypadAsset`'s JS never rendered
  on production while an adjacent, identically-registered bundle did —
  not reproduced locally, so it's specific to the production
  environment (Linux/Apache/PHP 8.4.23) in some way not yet identified.
- Fix the quirks-mode-causing duplicated `data-authchoice` div
  regardless — it's a real bug independent of this feature.
- Consider adding a CI/deploy-time smoke check that fetches a real
  authenticated page and asserts every registered `AssetBundle`'s JS
  files actually appear in the rendered HTML, so a regression like this
  is caught before a real user hits it.

## Verification

- `php -l` clean on every reverted file.
- vitest: 176/176 passing.
- Live Playwright check against local WAMP: plain-input 2FA login
  flow works end-to-end with a real generated TOTP code.
