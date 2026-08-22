# 2FA Setup — Generic Authenticator Wording, Not Aegis-Branded (August 2026)

## Why

The 2FA setup page (`resources/views/auth/setup.php`), the login page's
"2FA enabled" badge (`resources/views/auth/login.php`), and the
verify-at-login page's field label all told the user, explicitly, to use
**Aegis** — an Aegis logo, a link to `getaegis.app`, "Scan this QR code
with your Aegis app," and a badge labelled "Aegis Two Factor
Authentication."

None of that is actually true at the protocol level. 2FA setup here
(`App\Auth\Trait\TwoFactorAuth`) uses `OTPHP\TOTP::create()` —
`spomky-labs/otphp`, plain RFC 6238 TOTP. Any authenticator app can
scan the same QR code and produce valid codes; nothing about the secret
or the QR payload is Aegis-specific. The branding was a presentation
choice, not a technical requirement — and most people already have
*some* authenticator on their phone (Google Authenticator and Microsoft
Authenticator being by far the two most common), so telling them to
download a specific, different, unfamiliar app added real friction for
no reason.

## What changed

- **`two.factor.authentication.scan`**: "Scan this QR code with your
  Aegis app:" → "Scan this QR code with your authenticator app:"
- **New `two.factor.authentication.compatible.apps`**: a short,
  generic sentence naming several recognizable apps — Google
  Authenticator, Microsoft Authenticator, Authy, 1Password, Bitwarden,
  **Yandex ID**, and Aegis — so a user sees the one they already have
  installed rather than being pointed at just one. Yandex ID (the
  rebranded Yandex Key) is included deliberately: this app already
  integrates YooKassa and Robokassa for the Russian market, where
  Yandex ID is a genuinely common authenticator, confirmed still
  actively maintained (APK updated January 2026) before adding it.
- **`two.factor.authentication.qr.code.enter.manually`**: dropped
  "into the android app" (also wrong — plenty of these apps are iOS)
  for "into your authenticator app."
- **`two.factor.authentication.enabled.aegis`** renamed to
  **`.enabled.badge`**, value "Aegis Two Factor Authentication" →
  "Two Factor Authentication Enabled" — the login page's status badge
  when TFA is required system-wide.
- **`layout.password.otp.6.first`** (setup page's code field label)
  and **`layout.password.otp.6.8`** (login-time verify page's field
  label, which accepts either a 6-digit OTP or an 8-digit backup
  recovery code) — both had "Aegis Generated" in the label text; now
  "Enter the 6-digit code from your authenticator app" and "...or an
  8-digit backup recovery code" respectively.
- Removed the Aegis logo + `getaegis.app` link entirely from both
  `setup.php` and `login.php`, replaced with the plain-text compatible-
  apps sentence — no single app gets visual priority over the others.
  `public/img/aegis.png` is now unreferenced; left in place rather than
  deleted (out of scope for a copy fix).
- `App\Auth\Trait\TwoFactorAuth::showSetup()`'s own docblock (developer-
  facing, not user-visible) updated to match — "Download Aegis 2FA app"
  → "Install any TOTP authenticator app... this is standard RFC 6238
  TOTP, not tied to any one app."

## Deliberately not touched

The six-box auto-advancing code-entry UX (`AuthAegisTotpKeypadAsset`,
`keypad-copy-to-clipboard.ts`) that previously shipped, broke production
login, and was reverted (see
[`TFA_OTP_BOXES_REVERT_AUGUST_2026.md`](TFA_OTP_BOXES_REVERT_AUGUST_2026.md))
is a separate, deliberately-deferred piece of work — this change is
copy/label only, doesn't go anywhere near that code, and doesn't need to
wait for it. The internal asset class name `AuthAegisTotpKeypadAsset`
itself is left as-is too — it's about the keypad's visual style, never
shown to a user as text, and renaming it would be pure code churn
unrelated to this fix.

## Verification

- `vendor/bin/psalm --no-cache`: No errors found! (project-wide).
- `vendor/bin/testo --suite=Unit`: 899/899 (unchanged — no test
  referenced the old strings/keys, confirmed via grep before changing
  them).
- Live-verified against the real running app: temporarily flipped the
  `enable_tfa` Setting on in the local DB, confirmed the login page's
  badge actually renders "Two Factor Authentication Enabled" followed
  by the new compatible-apps sentence — no Aegis logo, no
  `getaegis.app` link — then restored the setting to its original
  value. `php -l` clean on every changed file.
- Not separately click-through-verified: the setup page itself
  (`setup.php`) requires an authenticated session with no TFA
  configured yet, which wasn't practical to reach via a quick local
  smoke test — the same template/translator pattern as the login badge
  (already live-verified), so lower risk, but worth a real click-through
  with an actual authenticator app before relying on it.
