# Storecove Go-Live Testing — 9 Real Bugs Found and Fixed Live, First Confirmed End-to-End Send — August 2026

## Summary

Following on from the earlier Storecove/Oxalis settings relocation and
the `rossaddison/storecove-client` package pivot, the user had a real
Storecove API key and a real sandbox Legal Entity for the first time
and started actually trying to send a real invoice via Storecove. Every
bug below was found the same way — click send, hit a cryptic failure,
report it, get a live patch — nine rounds of that for one successful
send, which is itself what prompted the deeper error-UX work at the end
of this document.

## The nine bugs

1. **Stale onboarding nav label/link** (PR #1116) — the top-nav Peppol
   dropdown's onboarding checklist still said the API key goes under
   "Online Payment" and linked to the generic Settings page instead of
   deep-linking to the Storecove tab.
2. **`enable_client_peppol_defaults` setting-key mismatch** (PR #1117) —
   `InvoiceInstallTrait` seeded `enable_peppol_client_defaults`
   (word-swapped) but `ClientPeppolController` read
   `enable_client_peppol_defaults` — the "Fill Client Peppol Form with
   OpenPeppol defaults" feature had silently never worked on any fresh
   install.
3. **Peppol Send label hardcoded "(Oxalis)"** (PR #1118) — the real send
   button had routed through `PeppolSendServiceRouter` (defaulting to
   Storecove) since that work shipped, but the label never updated,
   telling every user the wrong provider.
4. **`taxschemeid` dropdown submitted its list position, not a real
   code** (PR #1120) — built via `array_map(fn, array_keys($x), $x)`,
   which always re-indexes 0,1,2,... regardless of the source array's
   real keys, so `<option value>` was the row's *position*, not its Tax
   scheme. Rebuilt via plain `foreach`, storing the real code string.
5. **`UnitPeppolForm` crash on add/edit** (PR #1121) — the view rendered
   a hidden `id` field the form never declared, throwing
   `UndefinedObjectPropertyException` on every load.
6. **`ProductForm` phantom `product_tariff` field blocking every save**
   (PR #1122) — the view referenced a form property that didn't exist
   anywhere; combined with client-side HTML5 `required`, the browser
   refused to submit at all.
7. **`ProductForm` error summary swallowed every field-level error** (PR
   #1123) — `Field::errorSummary()->onlyProperties(...)->onlyCommonErrors()`
   — the second, immutable call unconditionally overwrote the first,
   showing zero feedback for a genuinely-failing save.
8. **Password-type setting's first save stored plaintext, not
   encrypted** (PR #1124) — a brand-new setting row skipped
   `saveOneSetting()`'s encryption branch entirely on its very first
   save, corrupting `storecove_api_key` into garbage the moment it was
   later decrypted for the Storecove Authorization header.
9. **`ClientPeppol.endpointid` hardcoded as an email format** (PR
   #1125) — a `Yiisoft\Validator\Rule\Email` rule, an `<input
   type="email">`, and a label reading "Email Address" — but
   `cbc:EndpointID`'s real format depends on the selected scheme, mostly
   numeric. Invisible until the OpenPeppol-defaults feature (bug #2,
   now fixed) pre-filled scheme `0192` (Norway org number) with example
   value `joe.bloggs@web.com`, copied verbatim from an Ecosio test
   fixture. Every real send using the defaults failed with a Storecove
   422 (`does not match format '^\d{9}$'`).

## Deep-linked error UX + catch-all exception handling (PR #1126/#1127)

Fixing #9 immediately surfaced the same bug class again on a different
scheme (`0106` KVK, `joe.bloggs@web.com` again) — proving the real fix
wasn't one more scheme-specific patch but a general one: **no real
Peppol EAS/ICD scheme uses an email-address format**, so an `@` in
Endpoint ID is now flagged as wrong regardless of which scheme is
selected, plus a stronger checksum check for the five schemes
`EndpointSchemeValidator` already validates (`0088`, `0192`, `0208`,
`0007`, `0151`).

The user's own framing of the whole session — "riddled with
inconveniances" — drove the rest of this: every ClientPeppol setup
problem now surfaces as one flash message with a bullet list of exactly
the broken fields, each a clickable link straight to
`clientpeppol/edit/{client_id}#{field}` (the property name doubles as
the field's HTML id) — not a wall of raw `$cp->getX() ` debug output.
The same treatment was applied to a missing delivery location, a
missing ClientPeppol record entirely, and `PeppolTaxCategoryCodeNotFoundException`
(which now carries the offending `TaxRate`'s id, linking to
`taxrate/edit/{id}#peppol_tax_rate_code`).

Separately, `peppol()` (the XML-preview action) had **no try/catch at
all** around UBL generation, and `peppolSend()` only caught
`RuntimeException` — meaning some failures reached a flash message and
others reached Yii3's generic "an error has occurred" page, with no
way to tell which in advance unless `YII_DEBUG=true` happened to be on.
Both now catch `\Throwable`, always `error_log()` the full exception,
and always flash a friendly, deep-linked message either way.

## Smaller fixes found along the way

- **Peppol Tax Rate Code required/optional mismatch** (PR #1128) — the
  field's hint literally said "not required," but
  `PeppolHelperTaxTrait`/`StoreCoveInvoiceLineBuilder` both throw the
  moment an invoice using that tax rate is sent via Peppol. Deliberately
  *not* made `#[Required]` — the requirement is conditional (only rates
  actually used on a Peppol invoice need it). Fixed with a field-specific
  hint plus a `taxrate/index` "⚠️ Not set" flag for any row missing it.
- **`StoreCoveArrays` garbled cells** (PR #1129) — rows 0 (US), 54
  (JP), and 59 (World/Other) had a cell with an internal comma split
  across the following column(s) by the original html-to-json
  conversion — not just cosmetic, since that array's `tax` value is
  submitted verbatim as `cac:TaxScheme/cbc:ID`. Corrected values
  cross-checked against the parallel, uncorrupted
  `storeCoveSenderIdentifierArray()`.
- **`README.md`'s own Endpoint ID example** (PR #1130) — had the same
  email/GLN mismatch as bug #9. Replaced with a real, checksum-valid
  GLN (`4001234500126`).

## Real end-to-end send confirmed

Using the now-fixed ClientPeppol form with Endpoint ID `27375186` /
Endpoint Scheme ID `0106` (Netherlands Chamber of Commerce / KVK) —
Storecove B.V.'s own real, registered, network-discoverable Peppol
identifier (taken from the old debug trait's hardcoded example, sourced
from Storecove's own "Send your first invoice" docs) — the send
returned a genuine `SENT` `PeppolMessage` with a real `peppol.message.id`,
confirmed twice in the same session: once immediately after the
endpoint-format fix, and once more after the deep-linked-error UX work,
via the full loop (bad data → deep-linked error → user fixed the field
via the link → retried → second genuine `SENT`).

## Verification

Every PR individually: `vendor/bin/psalm --no-cache` (full project) 0
errors, `php -l` clean on all changed files. No automated test suite
covers this thread specifically — verification was live, against the
real Storecove sandbox, which is the only way to confirm an external
API integration actually works end to end.
