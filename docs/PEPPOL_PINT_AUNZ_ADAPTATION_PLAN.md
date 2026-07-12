# Adapting Peppol Output to PINT A-NZ (Australia/New Zealand)

Unlike the UK mandate work, this is not waiting on anything — PINT A-NZ has been the **only**
supported Peppol invoice format for Australia and New Zealand since **15 May 2025**, live and
mandatory today, with a free public validator and no ambiguity about the target spec. This doc is
a concrete, code-grounded scope for making this repo's UBL 2.4 output PINT A-NZ compliant, found by
checking the actual current source against the real PINT A-NZ spec (`docs.peppol.eu/poac/aunz/pint-aunz/bis/`).

---

## What "adapting to Australia's system" concretely means

Two things, confirmed by research, worth being upfront about before any code changes:

1. **Document format (PINT A-NZ)** — this is what's scoped below. Fully achievable with this
   repo's existing architecture; no new infrastructure, just profile-aware values in places that
   are currently hardcoded to core BIS 3.0.
2. **Real network transmission** — the ATO is the Peppol Authority for Australia and accredits
   Access Points, same accreditation-gate dynamic discussed for the UK. Generating a correct PINT
   A-NZ document is independent of whether transmission goes through an ATO-accredited AP, a
   private bilateral node (this repo's existing `StaticAs4SmpResolver`), or Storecove/Tickstar/
   Pagero. This plan only covers #1 — getting the *document* right. #2 is a separate, later
   decision with no new information changing the earlier analysis of that trade-off.

---

## Confirmed technical delta: PINT A-NZ vs. core BIS 3.0

| Aspect | Core BIS 3.0 (current) | PINT A-NZ (target) |
|---|---|---|
| `CustomizationID` | `urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0` | `urn:peppol:pint:billing-1@aunz-1` |
| `ProfileID` | `urn:fdc:peppol.eu:2017:poacc:billing:01:1.0` | `urn:peppol:bis:billing` |
| Tax scheme ID | `VAT` | `GST` |
| Standard rate | varies | AU 10%, NZ 15% (fixed) |
| Tax category codes | full UNCL5305 | subset: S, E, Z, G, O |
| Party tax ID scheme | generic | AU: ABN, `schemeID="0151"` (11 digits); NZ: GST number (9 digits) |
| Decimal precision | — | tax category taxable/tax amount, line net amount ≤ 2dp, ±1.00 tolerance |
| Seller/buyer tax ID | conditional | mandatory (ibt-031 / ibt-048) |

---

## Where this repo currently hardcodes the core-BIS-3.0-only assumption

Found by grepping the actual source, not guessing:

1. **`src/Invoice/Ubl/Invoice.php:19`** — `$customizationID` defaults to the core BIS 3.0 URN as a
   hardcoded property initializer. `$profileID` is written out at line 161 alongside it.
2. **`src/Invoice/Helpers/Peppol/PeppolHelper.php:86`** — `public const string TAX_CATEGORY_VAT = 'VAT';`
   — hardcoded, used wherever the tax scheme ID is emitted.
3. **`src/Invoice/Helpers/Peppol/PeppolValidator.php:56`** — 
   `XPATH_TAX_SCHEME_VAT = "cac:PartyTaxScheme[cac:TaxScheme/cbc:ID='VAT']/"` — hardcoded XPath
   literal. A PINT A-NZ document (tax scheme `GST`) would silently fail to locate the supplier/
   customer tax ID with this validator as-is (lines 194, 201, 216 all depend on it).
4. **`src/Invoice/Helpers/Peppol/Validator/DocumentLevelValidator.php:68`** —
   `$requiredStart = 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0'`
   — this is an active **rejection**, not just a missed opportunity: `validateCustomizationID()`
   would flag a correctly-formed PINT A-NZ document as invalid right now, since its
   `CustomizationID` doesn't start with the core-BIS-3.0 string.
5. **`src/Invoice/Helpers/Peppol/Stylesheet/stylesheet-ubl.xslt`** — both the `Invoice` and
   `CreditNote` template matches key off `starts-with(cbc:CustomizationID, 'urn:cen.eu:en16931...')`
   — the human-readable HTML rendering of a PINT A-NZ document wouldn't match either template and
   would fall through to whatever the default/unstyled case is.

None of these are architectural blockers — they're all hardcoded literals in places that are
already structurally the right place for a profile-aware value. This is a "make it configurable"
task, not a redesign.

---

## Proposed approach

### 1. A `PeppolProfile` concept

```php
enum PeppolProfile: string
{
    case CoreBis3 = 'core-bis3';
    case PintAuNz = 'pint-aunz';
}
```

Driven by a new setting (`peppol_profile`, alongside the existing Peppol settings tab), read once
per invoice generation and threaded through `PeppolHelper` the same way `SettingRepository` already
is. Each profile supplies: `customizationId`, `profileId`, `taxSchemeId` (`VAT`/`GST`), and the
allowed tax category code subset.

### 2. `PeppolHelper` / `Invoice.php`

Replace the hardcoded `TAX_CATEGORY_VAT` constant and `Invoice.php`'s hardcoded
`$customizationID`/`$profileID` defaults with values sourced from the active `PeppolProfile`.

### 3. `PeppolValidator` / `DocumentLevelValidator`

`XPATH_TAX_SCHEME_VAT` becomes profile-aware (`'VAT'` or `'GST'` depending on what's being
validated — read from the document's own `CustomizationID`, not from settings, so a validator run
against an arbitrary XML file works regardless of what generated it). Same for
`DocumentLevelValidator::validateCustomizationID()` — `$requiredStart` becomes a check against a
list of two known-valid prefixes instead of one.

### 4. `stylesheet-ubl.xslt`

Add a second `<xsl:template match="...">` guarded on the PINT A-NZ `CustomizationID`, alongside
the existing core-BIS-3.0 one — same rendering logic, just matched on either prefix.

### 5. Party identifier scheme (ABN / NZ GST number)

Check whether `ClientPeppol::getIdentificationidSchemeid()` / `getEndpointidSchemeid()` are
free-text (in which case `0151` for ABN just works today) or constrained against the ISO 6523 ICD
code-list already loaded via `PeppolArrays`/`loadVefaCodeList()` (see
[PEPPOL_XML_CODE_LIST_LOADERS.md](PEPPOL_XML_CODE_LIST_LOADERS.md)) — if constrained, confirm `0151`
is already present in that VEFA-sourced list (it's a real, current ISO 6523 ICD entry, so it likely
already is, since that list is sourced from the upstream OpenPEPPOL data rather than hand-picked).

### 6. Decimal/tolerance rules

`InvAmount`/`InvItemAmount` rounding already targets 2dp throughout this codebase for currency
values (existing convention, not new work) — worth a quick audit against the ±1.00 document-level
tax tolerance rule specifically, but not expected to require new rounding logic.

### 7. Test fixtures

`src/Invoice/Helpers/Peppol/EcosioTestFiles/` already holds real core-BIS-3.0 sample documents used
by the validator test suite. A parallel `PintAuNzTestFiles/` directory, seeded from the free PINT
A-NZ validator's own sample invoices, gives the validator changes something concrete to test
against without needing network access or an AU/NZ account.

---

## What's explicitly out of scope for this pass

- Self-billing (RCTI/BCTI) — separate PINT A-NZ sub-specification, optional, no current use case
  in this repo.
- BPAY or other AU/NZ-local payment-means codes — the spec itself defers to external guidance
  rather than embedding them; not needed for document validity.
- Real AS4 transmission to an ATO-accredited AP — separate decision, see the note at the top.

---

## Effort estimate

| Step | Estimate |
|---|---|
| `PeppolProfile` enum + `peppol_profile` setting + settings-tab UI | 1.5 h |
| `PeppolHelper`/`Invoice.php` profile-aware CustomizationID/ProfileID/tax scheme | 2 h |
| `PeppolValidator`/`DocumentLevelValidator` dual-profile support | 2 h |
| `stylesheet-ubl.xslt` second template match | 1 h |
| ABN/GST-number identifier scheme check (§5) | 1 h |
| `PintAuNzTestFiles/` fixtures + PHPUnit coverage | 2 h |
| Psalm errorLevel 1 | 30 min |

**Total: ~10 hours**

---

## References

- [PINT A-NZ Billing BIS spec](https://docs.peppol.eu/poac/aunz/pint-aunz/bis/)
- [ATO — Peppol](https://www.ato.gov.au/businesses-and-organisations/einvoicing/peppol)
- [PEPPOL_XML_CODE_LIST_LOADERS.md](PEPPOL_XML_CODE_LIST_LOADERS.md)
- [AS4_BILATERAL_ROADMAP.md](AS4_BILATERAL_ROADMAP.md) — the transmission-layer question this plan deliberately doesn't touch
