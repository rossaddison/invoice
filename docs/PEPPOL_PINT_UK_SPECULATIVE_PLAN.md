# A Speculative PINT-UK Plan

**Status: speculative.** No official "PINT-UK" specification exists as of this writing. The UK
government confirmed on 23 June 2026 that Peppol (4-corner, no e-reporting obligation yet) will be
the interoperability framework for the April 2029 B2B e-invoicing mandate, with a detailed
implementation roadmap due at the **November 2026 Budget** (see
[project memory: `project_hmrc_2029_peppol_confirmation`]) — but no UK-specific document profile
has been named or published. This plan is an informed extrapolation from every real PINT variant
currently published (A-NZ, SG, MY, JP, AE, EU) applied to what's actually distinctive about UK VAT,
written so there's a concrete starting point to revise the moment the real spec lands, rather than
starting from zero in November. Companion to
[PEPPOL_PINT_AUNZ_ADAPTATION_PLAN.md](PEPPOL_PINT_AUNZ_ADAPTATION_PLAN.md), which covers the one
PINT variant that's actually live today.

---

## What's confirmed vs. what's guessed

| | Status |
|---|---|
| Peppol as the network | Confirmed, 23 June 2026 |
| 4-corner model | Confirmed |
| No e-reporting obligation at launch | Confirmed |
| April 2029 mandate date | Confirmed |
| A UK-specific document profile will exist | **Not confirmed** — inferred from the fact that every other non-EU Peppol adopter (A-NZ, SG, MY, JP, AE) ended up with one; core BIS 3.0 unmodified has never been the long-run outcome anywhere else |
| Everything in the tables below | Speculative, based on UK VAT rules + the PINT pattern |

---

## Where PINT-UK would be simpler than most variants

Unlike A-NZ, SG (GST) or MY (SST), **UK VAT never needed relabeling** — `TaxScheme/cbc:ID = 'VAT'`
already matches what core BIS 3.0 emits today. This is the one place PINT-UK would look more like
PINT-EU (near-identical to core BIS 3.0) than PINT A-NZ (a real terminology swap) — even though the
UK sits outside the EU VAT area entirely.

**Identifier scheme is already solved.** UK VAT numbers have an assigned ISO 6523 ICD code —
`9932`, "United Kingdom VAT number" — already present in this repo's own loaded code list at
`src/Invoice/Helpers/Peppol/DownloadedXml/eas.xml:241-242`. Confirmed by direct grep, not assumed.
Nothing to add here; a UK profile just points `schemeID` at a code that already exists.

**No reporting hook.** The 23 June confirmation was explicit — 4-corner, no RTR at launch. So a
launch-day PINT-UK would be pure document-format work, closer in overall shape to PINT A-NZ/EU
than to PINT AE (which bundles real-time FTA reporting into the spec itself).

---

## Where PINT-UK would be harder than the AU/NZ case

### Multiple tax categories, not one relabel

| Category | Rate | Notes |
|---|---|---|
| Standard | 20% | |
| Reduced | 5% | Specific goods/services (e.g. domestic fuel) |
| Zero-rated | 0% | Distinct from Exempt — zero-rated supplies still count toward taxable turnover |
| Exempt | — | No VAT charged, no input VAT recovery |
| Outside the scope | — | Not a UK VAT supply at all |

This is structurally closer to Malaysia's multi-tax-type problem (SST + TTx + LVG + HVGT) than to
Australia's single-scheme swap — five real category codes a UK invoice needs to be able to express,
not a find-and-replace on one tax scheme string. This confirms the recommendation already made in
the AU/NZ plan: `PeppolProfile` needs `taxCategories: array<TaxCategorySpec>` (code, rate, label),
not a single scalar `taxSchemeId`, designed in from the start rather than retrofitted after a
second jurisdiction proves the single-string version insufficient.

### Two UK-specific mechanisms, not just rates

- **Domestic reverse charge** (construction industry, telecoms, and others) — the invoice carries
  specific wording/coding rather than a charged VAT amount; the buyer self-accounts for the VAT.
  This is a document *behavior*, not a rate — needs its own tax category code and a note-field
  convention, not just a percentage.
- **Postponed VAT Accounting (PVA)** — import VAT handling introduced post-Brexit, relevant to any
  UK business importing goods. Also a procedural flag, not a rate.

### Northern Ireland — a wrinkle no other PINT variant has

Per the Windsor Framework, NI *goods* trade stays VAT-aligned with the EU in ways Great Britain's
doesn't (flagged as a genuinely open legal question earlier — worth re-verifying against current
guidance before treating as settled). None of A-NZ, SG, MY, JP, or AE have an internal dual-VAT-regime
problem like this. A real PINT-UK would plausibly need an invoice-level flag distinguishing
GB-only transactions from NI ones, since NI may need to carry EU-style intra-community VAT
treatment on the same document format GB uses for ordinary domestic VAT.

---

## Proposed design

Extends the `PeppolProfile` mechanism from the AU/NZ plan rather than introducing a separate one:

```php
enum PeppolProfile: string
{
    case CoreBis3 = 'core-bis3';
    case PintAuNz = 'pint-aunz';
    case PintUk   = 'pint-uk';   // speculative — revise once the real spec exists
}

/** @param list<TaxCategorySpec> $taxCategories */
final readonly class TaxCategorySpec
{
    public function __construct(
        public string $code,        // UNCL5305 subset: S, Z, E, O, plus reverse-charge/PVA handling
        public ?float $rate,        // null for Exempt / Outside-scope / reverse-charge
        public string $label,       // 'Standard', 'Reduced', 'Zero-rated', 'Exempt', 'Outside scope'
    ) {}
}
```

`Invoice.php`'s `CustomizationID` default would need a UK entry (placeholder pattern only —
`urn:peppol:pint:billing-1@gb-1`, mirroring `@aunz-1`; the real identifier will be whatever
OpenPeppol actually publishes) alongside the same dual-prefix accommodation already planned for
`DocumentLevelValidator::validateCustomizationID()` in the AU/NZ plan — that validator change
should be designed to accept a *list* of valid prefixes from the start, not extended one-off per
profile, since PINT-UK would be (at least) the third entry after core BIS 3.0 and PINT A-NZ.

### NI flag

A `deliveryRegion` or similar field on the invoice/delivery-location model, defaulting to GB,
switchable to NI — gates which tax-category rules apply. Exact mechanics depend entirely on
whatever the real November 2026 roadmap specifies; not designable further than "the seam needs to
exist" until then.

---

## Connection to `vat-api-php` — design these together, not separately

This repo already has a `vat-api-php` plan (separate branch, this session) modeling HMRC's VAT
return: Box 1 (VAT due on sales), Box 4 (input VAT reclaimed), Box 6/7 (net sales/purchase values
ex-VAT), etc. A PINT-UK invoice's tax-category breakdown and a `VatReturn`'s box values are two
views of the same underlying UK VAT domain data — they should share a tax-category vocabulary
rather than being designed independently and reconciled later. Concretely: the `TaxCategorySpec`
list above should map directly onto which VAT100 box each category rolls into, reusing the
`PurchaseEntryVatAggregator` pattern already in this repo for Box 4/7 purchase-side summation
(see [MTD_VAT_PURCHASE_ENTRIES.md](MTD_VAT_PURCHASE_ENTRIES.md)) rather than inventing a second,
parallel aggregation path for the sales side.

---

## What's explicitly out of scope / unknowable right now

- The actual `CustomizationID`/`ProfileID` URNs — will be whatever OpenPeppol/HMRC publish;
  anything here is a placeholder.
- Real-time reporting — explicitly deferred by the UK government; out of scope until (if) it's
  added in a future spec revision.
- NI mechanics beyond "the seam needs to exist" — genuinely unknown, possibly a legal question
  more than a technical one.
- AP accreditation requirements for actually transmitting PINT-UK documents — separate question,
  same as the AU/NZ plan's transmission-layer caveat.

---

## Effort estimate

Heavily caveated — this is building against a spec that doesn't exist yet, so the estimate is for
"build the extensible mechanism and the parts that are already knowable," not "implement PINT-UK."

| Step | Estimate |
|---|---|
| `TaxCategorySpec` + multi-category `PeppolProfile` redesign (also benefits the AU/NZ plan) | 3 h |
| UK `TaxCategorySpec` table (5 categories + reverse-charge + PVA flags) | 2 h |
| Multi-prefix `DocumentLevelValidator` (also benefits AU/NZ plan) | 2 h |
| NI `deliveryRegion` seam (structure only, no real business rules yet) | 1.5 h |
| Shared tax-category vocabulary with `vat-api-php`'s `VatReturn` boxes | 2 h |
| Rework once the real Nov 2026 spec publishes | **Unknown — likely most of the above gets revised** |

**Total (pre-spec groundwork only): ~10.5 hours**, with the explicit understanding that the
November 2026 roadmap will require revisiting most line items above.

---

## References

- [PEPPOL_PINT_AUNZ_ADAPTATION_PLAN.md](PEPPOL_PINT_AUNZ_ADAPTATION_PLAN.md) — the live, non-speculative sibling of this plan
- [HMRC_VAT_API_PHP_PLAN.md](HMRC_VAT_API_PHP_PLAN.md) — `vat-api-php`, the VAT-return side of the same domain
- [MTD_VAT_PURCHASE_ENTRIES.md](MTD_VAT_PURCHASE_ENTRIES.md) — existing `PurchaseEntryVatAggregator` pattern
- Project memory: `project_hmrc_2029_peppol_confirmation` — source for the 23 June 2026 confirmation
