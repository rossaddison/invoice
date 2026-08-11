# Adyen Webhook HMAC Key — Root Cause Was an Unsaved Dashboard Change (August 2026)

## The symptom

Real Adyen sandbox payments (Pay by Bank via Tink Demo Bank) consistently
failed to mark their invoice paid. Every real webhook delivery came back
`401 Unauthorized` with body `invalid signature`, even after the original,
separate bug — a malformed (base64, not hex) key stored in Settings — was
found and fixed (see the "No webhook is configured" → webhook creation
saga earlier in this session's history). Regenerating the HMAC key from
Adyen's Customer Area and re-pasting it into this app's Settings did not
fix it either, on the first attempt.

## Diagnosis

Static/code-level debugging fully exonerated this app's own code:

- A temporary debug endpoint decrypted the actual stored key directly from
  the `setting` table and confirmed it was valid 64-character hex
  (`ctype_xdigit()` true), matching what `AdyenPaymentService::hmacKey()`
  resolves via the real `SettingRepository` — no decode()-type-mismatch
  bug (unlike the earlier PayPal `webhookId` incident), no stale-cache
  discrepancy between the DB and the running application.
- A self-signed test payload (signed with that exact stored key, POSTed to
  the real live `/paymentinformation/adyenWebhook` route) was correctly
  accepted (`200 [accepted]`) — proving `AdyenWebhookHandler`'s real,
  production code path correctly validates a payload genuinely signed
  with the currently-stored key.
- Temporary logging added directly to `AdyenPaymentService::isValidNotificationHmac()`
  (the only place where a clean signature *mismatch* — as opposed to a
  malformed-key exception — was previously silent, logging nothing at
  all) showed every single field going into the signature computation
  (`pspReference`, `merchantAccountCode`, `merchantReference`, amount,
  currency, `eventCode`, and — ruling out a PHP boolean-vs-string
  `implode()` gotcha specifically — `success` confirmed as the literal
  string `'true'`, not a JSON boolean) was correct, yet the computed
  signature never matched what Adyen actually sent.

The final, decisive check: independently computing the stored key's own
**KCV** (Key Check Value — `HMAC-SHA256("00000000", key)`, last 3 bytes,
uppercase hex) and comparing it against the KCV Adyen's own Customer Area
displays for that webhook's HMAC key. They didn't match. Since the key
was cryptographically confirmed *not* to be the one Adyen was actually
signing with, despite passing every format check, the conclusion was
unambiguous: the value stored in Settings was simply the wrong key,
correctly *shaped* but not the *right one*.

## Root cause

**A classic Adyen dashboard trap, not a code defect.** Adyen shows a
freshly-generated HMAC key's raw value exactly once, immediately after
clicking "Generate new HMAC key" — it can never be retrieved again
afterward (only its KCV/ID remain visible). Generating a key and copying
its value does not itself activate it; the webhook's configuration page
must still be explicitly **saved** for that key to actually take effect
on Adyen's side. In this incident, the key was generated and copied
correctly, but the configuration page was navigated away from without
clicking Save — so Adyen kept signing real webhooks with whatever key
had been active *before* that regeneration, while this app's Settings
held the newly-copied (correct-looking, never-activated) value. No
combination of code-side debugging could have caught this, since nothing
on this app's side was ever wrong.

## Fix

No code change. Re-generated the HMAC key one more time, copied it, and
this time explicitly saved the webhook's configuration page before
leaving it. Independently verified via the KCV cross-check *before*
spending another test payment cycle: computed `738233` locally against
the newly-stored key, matching Adyen's own displayed KCV for it exactly.
A subsequent real Adyen sandbox payment (Pay by Bank / Tink Demo Bank)
then correctly flipped its invoice to paid on the very next webhook
delivery.

## Advice for next time (and for other gateways with a similar "shown once" key)

- **After generating any one-time-displayed secret in a third-party
  dashboard (HMAC keys, webhook secrets, API keys), always explicitly
  save/apply the containing form before considering the value active** —
  copying the displayed value is not the same action as saving the page
  that generated it, and dashboards make this easy to conflate under the
  understandable urgency of "this will never be shown again, copy it
  now."
- **When a signature/HMAC validation persistently fails despite the key
  looking completely correct** (right length, right format, right KCV
  *as displayed*), independently compute the key's own KCV from the
  actual stored value and compare it against the provider's displayed
  KCV, rather than assuming a dashboard's own summary view is currently
  accurate. This is what actually broke the stalemate here — treating
  "the value is well-formed" and "the value is the *correct* one" as two
  separate, both-necessary things to verify, not one.
- This debugging pass also independently confirmed the KCV algorithm
  Adyen actually uses in practice: `HMAC-SHA256("00000000", key)` with
  the hex key **packed as raw binary** (`pack("H*", $hmacKey)` in PHP,
  matching exactly how the key is used for real signature computation),
  not the raw hex string used directly as the HMAC key — worth knowing
  precisely if this ever needs debugging again.

## Verification

Real, live, end-to-end confirmation: a genuine Adyen sandbox payment (Pay
by Bank via Tink Demo Bank) against a fresh invoice correctly flipped to
paid on its first real webhook delivery after the key was properly saved.
No code changes were made in this pass — `AdyenPaymentService.php` and
`AdyenWebhookHandler.php` are unchanged from what's already committed;
every temporary debug script and in-place logging addition used during
diagnosis was reverted (`git checkout --`) before this write-up.
