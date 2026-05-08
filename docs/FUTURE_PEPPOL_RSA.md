# Future Peppol Integration — South Africa (SARS E-Invoicing)

## Overview

South Africa is at an early but significant stage of mandatory e-invoicing adoption.
SARS (South African Revenue Service) is actively consulting on a framework that is
expected to align with a **Peppol-based 5-corner model**, making it a strategic early
integration target for a Peppol BIS Billing 3.0 compliant invoicing application.

This document is intentionally forward-looking — the South African mandate is not yet
finalised and this file should be treated as a living document updated as SARS
consultations progress.

---

## Current Status (as of Q1 2026)

| Component | Status |
|---|---|
| SARS e-invoicing mandate | Voluntary — mandatory phased from 2026 |
| Format standard | Under consultation |
| Peppol alignment | Proposed 5-corner model — not yet confirmed |
| AP certification pathway | Not yet published |
| Legislative basis | Draft TALAB (Tax Administration Laws Amendment Bill) |
| VAT gap driving urgency | Estimated R800 billion annually |

---

## Why South Africa is a Priority Watch

South Africa is less established than EU or UK mandates, which creates a strategic
opportunity to integrate early and shape usage patterns before the standard rigidifies.

Key factors:

- **Peppol 5-corner model proposed** — directly compatible with existing UBL output
- **Phased rollout** — large VAT taxpayers first (2026–2029), broader rollout after
- **Active SARS consultation** — technical input from compliant software vendors is welcomed
- **English-language legal framework** — lower barrier to compliance documentation
- **Growing Peppol Africa presence** — regional momentum building

---

## SARS E-Invoicing Architecture (Proposed)

SARS is considering a **hybrid centralised model** combining:

- A **Central Tax Hub (CTH)** for real-time validation and clearance
- A **decentralised 5-corner Peppol model** for transmission between trading partners

```
Supplier App (Peppol UBL)
    └── Certified Peppol AP (Corner 2)
        └── SARS Central Tax Hub (clearance + validation)
            └── Recipient AP (Corner 3)
                └── Buyer App (Corner 4)
```

This places South Africa between the EU Peppol CIUS tier (pure transmission) and the
clearance model tier (Brazil, India) — effectively a **Tier 1.5** integration.

---

## Country Tier Classification

| Tier | Model | Examples |
|---|---|---|
| Tier 1 | Peppol CIUS — schematron extension only | DE, NL, AU, NZ |
| Tier 1.5 | Peppol + clearance hub | South Africa (proposed), France |
| Tier 2 | Peppol-adjacent — different transport, UBL basis | Italy (SDI) |
| Tier 3 | Clearance model — format transformation required | Brazil (NF-e), India (IRP) |
| Tier 4 | Proprietary/restricted | China (Golden Tax), Russia |

South Africa's Tier 1.5 classification means:

- Existing UBL generation requires **no format transformation**
- A **SARS clearance response handler** will be required (similar to Italy SDI)
- AP certification may follow the standard OpenPeppol pathway

---

## Draft TALAB — Key Definitions

The Draft Tax Administration Laws Amendment Bill introduces three components relevant
to this integration:

1. **E-invoice** — a structured electronic format permitting automatic processing
2. **E-reporting** — electronic submission of tax data to SARS
3. **Interoperability framework** — governs decentralised exchange between parties

These definitions deliberately align with Peppol terminology, reinforcing the likelihood
of a Peppol-based technical standard.

---

## Phased Rollout Timeline

| Phase | Target | Period |
|---|---|---|
| System design and pilot | Large VAT taxpayers, early adopters | 2026 |
| Mandatory onboarding | Large VAT taxpayers | 2026–2029 |
| Broader rollout | SMEs and wider VAT base | 2029+ |
| Full mandate | TBD | TBD |

Early adopter participation during the pilot phase is strategically valuable — it
provides direct feedback channel to SARS and positions compliant software ahead of
the mandatory curve.

---

## Adapter Architecture

Given the proposed clearance hub model, the SARS adapter will require:

```php
interface SARSPeppolAPInterface
{
    // Submit UBL to certified AP for transmission
    public function submit(string $ublXml): SubmissionResponse;

    // Handle SARS Central Tax Hub clearance response
    public function handleClearanceResponse(
        string $documentId,
        ClearanceResponse $response
    ): DocumentStatus;

    // Retrieve obligations (VAT periods)
    public function getObligations(string $vatNumber): array;

    // Query document status at SARS hub
    public function getDocumentStatus(string $documentId): DocumentStatus;
}
```

### Clearance Response Handling
The CTH clearance step will return one of:

- **Cleared** — invoice is legally valid for VAT deduction
- **Rejected** — validation failure, resubmission required
- **Pending** — hub processing, poll for status

A **submission status flag** on each invoice record is required:

```
draft → pending → submitted → hub_pending → cleared → rejected
```

Store the SARS clearance reference against every cleared invoice — equivalent to
HMRC's `correlationId`.

---

## CIUS / Schematron Considerations

Until SARS publishes a formal CIUS, validation should apply:

1. Core Peppol BIS 3.0 schematron rules (already implemented)
2. EN16931 base rules (already implemented)
3. SARS-specific rules — to be added when published

A placeholder CIUS directory should be created now:

```
/schematron
    /peppol-bis-3.0/
    /xrechnung/
    /nlcius/
    /sars/
        /pending/        ← placeholder, populate when SARS publishes
        README.md        ← link to SARS consultation page
```

---

## GitHub Actions — Maintenance Strategy

South Africa's consultation process does not publish updates via GitHub. A combination
of RSS feed monitoring and scheduled web scraping is required.

```yaml
name: Monitor SARS E-Invoicing Updates
on:
  schedule:
    - cron: '0 8 * * 1'  # Weekly Monday
jobs:
  check-sars:
    steps:
      - name: Check SARS e-invoicing consultation page
        # Fetch https://www.sars.gov.za/types-of-tax/value-added-tax/e-invoicing/
      - name: Check National Treasury TALAB updates
        # Fetch https://www.treasury.gov.za/legislation/bills/
      - name: Create issue if content changed
```

### Key Sources to Watch

| Source | URL | Method |
|---|---|---|
| SARS e-invoicing | `https://www.sars.gov.za` | RSS / page scrape |
| National Treasury TALAB | `https://www.treasury.gov.za` | RSS / page scrape |
| OpenPeppol Africa updates | `https://peppol.org/news/` | RSS feed |
| ITPSA (IT industry body) | `https://www.itpsa.co.za` | RSS feed |

Note: Unlike EU and UK authorities, SARS does not publish schematron or technical
specifications via GitHub. All updates require direct monitoring of the SARS portal.

---

## Comparison with UK MTD Integration

| Aspect | UK MTD | South Africa |
|---|---|---|
| Format | HMRC proprietary JSON | Peppol UBL (proposed) |
| Transport | HMRC REST API | Peppol AP + SARS hub |
| Auth | OAuth 2.0 | TBD — likely OAuth 2.0 |
| Fraud headers | Mandatory (FPH) | TBD |
| Schematron CIUS | Not applicable | Pending publication |
| Mandate date | April 2029 (e-invoicing) | 2029+ (full rollout) |
| Current readiness | OAuth + FPH complete | Monitor only |

---

## Risks and Uncertainties

| Risk | Likelihood | Mitigation |
|---|---|---|
| SARS adopts proprietary format instead of Peppol | Low-Medium | Monitor consultation closely |
| AP certification is national-only, not OpenPeppol | Medium | Engage with SARS pilot programme |
| Timeline slips beyond 2029 | Medium | Low cost to maintain placeholder |
| CTH clearance model changes significantly | Medium | Adapter pattern isolates blast radius |

---

## Recommended Next Actions

1. **Monitor** second SARS technical consultation (expected late 2026)
2. **Create** placeholder `/schematron/sars/` directory with README
3. **Watch** whether Storecove or Tickstar announce SARS AP certification
4. **Engage** SARS pilot programme when open to software vendors
5. **Update** this document following each SARS consultation release

---

## References

- SARS E-Invoicing: `https://www.sars.gov.za/types-of-tax/value-added-tax/e-invoicing/`
- National Treasury TALAB: `https://www.treasury.gov.za/legislation/bills/`
- OpenPeppol: `https://peppol.org`
- Peppol BIS Billing 3.0: `https://docs.peppol.eu/poacc/billing/3.0/`
- OpenPeppol GitHub: `https://github.com/OpenPeppol`
