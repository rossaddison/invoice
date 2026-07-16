# Turnstile Widget Silently Broken by CSP — Missing `challenges.cloudflare.com`

## Symptom

Login stopped working immediately after configuring a real Cloudflare
Turnstile Site Key and Secret Key in Settings → Cloudflare Turnstile. No
error appeared on the widget itself — it simply never rendered, and every
login attempt was rejected.

## Root cause

The CSP policy's `script-src`, `frame-src`, and `child-src` directives
never included Cloudflare's Turnstile domain
(`https://challenges.cloudflare.com`). The widget's script — and the
iframe it renders its challenge inside — were silently blocked by the
browser, so the hidden `cf-turnstile-response` form field was never
populated.

This was invisible before a secret key was configured, because
`verifyTurnstile()` ([src/Auth/Trait/TurnstileVerification.php:20-23](../src/Auth/Trait/TurnstileVerification.php#L20-L23))
bypasses verification entirely when the secret is empty:

```php
$secret = $this->sR->getSetting('turnstile_secret_key');
if ($secret === '' || $secret === '0') {
    return true;
}
if ($token === '') {
    return false;
}
```

The moment a real secret is set, that bypass switches off. Verification
becomes real, `$token` is permanently `''` (the widget never loaded), and
`verifyTurnstile()` always returns `false` — blocking every login attempt,
with no indication anywhere that the cause was a CSP header rather than
the Turnstile configuration itself.

## Fix

Added `https://challenges.cloudflare.com` to four CSP directives, matching
exactly how Stripe/Braintree already appear across the same four
directives for other embedded payment widgets:

- `script-src` — loads the Turnstile widget JS
- `frame-src` — the widget renders its challenge inside an iframe
- `child-src` — legacy/fallback equivalent of `frame-src` for older browsers
- `connect-src` — the widget's own internal requests

Applied identically in both places the policy is defined — they must stay
in sync per the existing comment in `config/web/params.php`:

- `config/web/params.php` — the PHP-level header, sent via
  `App\Middleware\ContentSecurityPolicyMiddleware`, which reads the policy
  string via DI rather than hardcoding it (confirmed while investigating —
  no third copy of the policy exists to also update)
- `public/.htaccess` — the mirrored Apache-level header

## Verification

`php -l` on `config/web/params.php`; the `.htaccess` edit mirrors the
identical, already-proven pattern used for every other domain in that
file. No PHP logic changed — this was a CSP allow-list gap only.
