# Future Peppol Integration — Nigeria (FIRS / NRS E-Invoicing)

## Overview

Nigeria has undergone the most significant digital tax transformation in Africa and is
now an **official Peppol Authority** as of October 2025. Unlike South Africa which is
still in consultation, Nigeria's mandatory e-invoicing regime is **already live** for
large taxpayers and expanding rapidly through 2026–2028.

Nigeria is classified as **Tier 1** in the country integration model — Peppol BIS
Billing 3.0 UBL XML is the mandated format, and the four-corner AP model applies
directly. Existing UBL generation and schematron validation require no format
transformation.

---

## Current Status (as of Q1 2026)

| Component | Status |
|---|---|
| Peppol Authority status | Confirmed — FIRS listed by OpenPeppol October 2025 |
| Large taxpayer mandate (≥ ₦5 billion) | Mandatory from November 2025 |
| Medium taxpayer mandate (₦1b–₦5b) | Mandatory from July 2026 |
| Small taxpayer mandate (< ₦1b) | Mandatory from July 2027 |
| All VAT-registered businesses | Full enforcement from 2028 |
| Non-resident suppliers | Under review for 2026 inclusion |
| Format | Peppol BIS Billing 3.0 UBL/XML |
| Transport | Four-corner Peppol AP model |
| Clearance model | Pre-clearance (B2B/B2G), near-real-time reporting (B2C) |
| Legislative basis | Nigeria Tax Administration Act 2025 (NTAA 2025) |
| Regulatory body | FIRS (transitioning to NRS — Nigerian Revenue Service) |
| AP accreditation | Via NITDA (National IT Development Agency) |

---

## Why Nigeria is a Tier 1 Integration

FIRS was confirmed as a national Peppol Authority on 26 September 2025, responsible
for governing the local Peppol network and onboarding Access Point Providers in
Nigeria. This makes Nigeria the first sub-Saharan African country to achieve
full Peppol Authority status.

Invoices must be issued in structured UBL/XML format adhering to international
standards, with FIRS adopting Peppol network conventions. The MBS system follows the
four-corner model — supplier, supplier access point, buyer access point, buyer.

This directly maps to the existing application architecture with no format
transformation required.

---

## The FIRSMBS / EFS Platform

The national e-invoicing platform is the **Merchant-Buyer Solution (MBS)**, operating
through the **Electronic Fiscal System (EFS)**. Key characteristics:

- Supplier submits UBL XML via certified Access Point Provider (APP)
- FIRS/NRS validates and digitally signs the invoice
- Cleared invoice receives an **Invoice Reference Number (IRN)** and
  **Cryptographic Stamp Identifier (CSID)**
- QR code is returned and must appear on the invoice presented to the buyer
- B2C invoices above ₦50,000 must be reported within 24 hours of issuance

---

## Transaction Models

| Transaction Type | Compliance Model | Timing |
|---|---|---|
| B2B | Pre-clearance — FIRS validates before invoice issued to buyer | Real-time |
| B2G | Pre-clearance — same as B2B | Real-time |
| B2C > ₦50,000 | Post-issuance reporting | Within 24 hours |
| B2C ≤ ₦50,000 | Currently exempt | N/A |

---

## Rollout Timeline

The phased rollout proceeded as follows: a pilot began in late 2024 with selected
large companies; large taxpayers (≥ ₦5 billion turnover) went mandatory November 2025;
medium taxpayers (₦1 billion to ₦5 billion) begin pilot onboarding in 2026 with
mandatory go-live by July 2026; small taxpayers (below ₦1 billion) are slated for
2027 with full enforcement by 2028.

| Phase | Taxpayer Category | Turnover Threshold | Mandatory Date |
|---|---|---|---|
| 1 | Large taxpayers | ≥ ₦5 billion | November 2025 |
| 2 | Medium taxpayers | ₦1b – ₦5b | July 2026 |
| 3 | Small taxpayers | < ₦1b | July 2027 |
| 4 | Full enforcement | All VAT-registered | 2028 |

---

## Legal Framework

The Nigeria Tax Administration Act 2025 explicitly requires businesses to fiscalise
transactions via an approved Electronic Fiscal System. Section 103 imposes a penalty
of ₦1 million for the first day of non-compliance with NRS technology deployment, plus
₦10,000 for each subsequent day. Section 104 stipulates that failure to process taxable
supplies through the system results in a ₦200,000 penalty plus 100% of tax due plus
interest at the prevailing CBN monetary policy rate.

The penalty regime makes Nigeria one of the most aggressively enforced e-invoicing
mandates globally.

---

## Adapter Architecture

Nigeria sits at **Tier 1** but with a **clearance response handler** required —
similar in concept to Italy SDI but using Peppol transport.

```
Invoice Data
    └── UBL XML Generation (existing)
        └── Peppol BIS 3.0 Schematron Validation (existing)
            └── Nigeria CIUS Validation (pending FIRS publication)
                └── NITDA-Accredited AP Submission
                    └── FIRS/NRS Clearance
                        └── IRN + CSID + QR Code Response Handler
                            └── Invoice Status Update (cleared/rejected)
```

### Interface Design

```php
interface NigeriaPeppolAPInterface
{
    // Submit UBL to NITDA-accredited AP for FIRS clearance
    public function submit(string $ublXml): SubmissionResponse;

    // Handle FIRS clearance response — IRN, CSID, QR code
    public function handleClearanceResponse(
        string $documentId,
        ClearanceResponse $response
    ): DocumentStatus;

    // B2C near-real-time reporting (within 24 hours)
    public function reportB2C(string $ublXml): ReportingResponse;

    // Query document status at FIRS
    public function getDocumentStatus(string $documentId): DocumentStatus;
}
```

### Submission Status Flags

```
draft → pending → submitted → firs_pending → cleared → rejected
```

Store the **IRN** (Invoice Reference Number) and **CSID** against every cleared
invoice. These are the Nigerian equivalents of HMRC's `correlationId`.

### QR Code Requirement
Cleared B2B/B2G invoices must display the FIRS-issued QR code. The adapter must
return the QR code data and the application must render it on the invoice PDF/output.

---

## CIUS / Schematron Considerations

No Nigerian CIUS schematron has been formally published as of Q1 2026. Apply:

1. Core Peppol BIS 3.0 schematron rules (already implemented)
2. EN16931 base rules (already implemented)
3. Nigeria-specific rules — to be added when FIRS/NITDA publishes

Placeholder directory structure:

```
/schematron
    /peppol-bis-3.0/
    /xrechnung/
    /nlcius/
    /sars/
    /nigeria/
        /pending/        ← placeholder, populate when FIRS publishes
        README.md        ← link to FIRS developer portal
```

---

## Access Point Provider Options

NITDA-accredited Access Point Providers handle the technical complexity of connecting
to the NRS e-invoicing system on behalf of businesses, via RESTful API connections
to FIRS systems.

| Provider | Notes |
|---|---|
| Storecove | Confirm Nigeria coverage — Peppol AP with wide country support |
| Pagero | Listed as tracking Nigeria compliance — enterprise-focused |
| Duplo | NITDA-accredited, Nigeria-native AP provider |
| Taxilla | Tracking Nigeria mandate, integration tooling available |

For an open source application, **Storecove** remains the recommended first choice
given its clean REST API and existing integration pattern. Verify current Nigeria
coverage directly with the provider as accreditation status evolves.

---

## Comparison with South Africa and UK

| Aspect | UK MTD | South Africa | Nigeria |
|---|---|---|---|
| Peppol Authority | No | Proposed | Confirmed (Oct 2025) |
| Format | HMRC JSON | Peppol UBL (proposed) | Peppol UBL/XML (live) |
| Transport | HMRC REST API | Peppol AP + SARS hub | Peppol 4-corner AP |
| Clearance model | No (post-filing) | CTC hub (proposed) | Pre-clearance (B2B/B2G) |
| IRN / CSID | No | TBD | Mandatory |
| QR code on invoice | No | TBD | Mandatory |
| Mandate date | April 2029 | 2028+ | Live — November 2025 |
| Current priority | Build | Monitor | Integrate |
| Tier | MTD-specific | 1.5 | 1 |

---

## GitHub Actions — Maintenance Strategy

Unlike South Africa, Nigeria has a more active developer ecosystem and some technical
publications are available via GitHub and official portals.

```yaml
name: Monitor Nigeria FIRS E-Invoicing Updates
on:
  schedule:
    - cron: '0 8 * * 1'  # Weekly Monday
jobs:
  check-nigeria:
    steps:
      - name: Check FIRS developer portal
        # Fetch https://einvoicing.firs.gov.ng
      - name: Check NITDA guidelines
        # Fetch https://nitda.gov.ng
      - name: Check OpenPeppol Nigeria updates
        # Fetch https://peppol.org/members/peppol-authorities/
      - name: Create issue if content changed
```

### Key Sources to Watch

| Source | URL | Method |
|---|---|---|
| FIRS e-invoicing portal | `https://einvoicing.firs.gov.ng` | Page scrape |
| NITDA guidelines | `https://nitda.gov.ng` | RSS / page scrape |
| OpenPeppol authorities | `https://peppol.org/members/peppol-authorities/` | RSS feed |
| VATupdate Nigeria | `https://www.vatupdate.com` | RSS feed (tag: Nigeria) |

---

## Risks and Considerations

| Risk | Likelihood | Mitigation |
|---|---|---|
| FIRS rebrands to NRS mid-integration | High — already underway | Use interface abstraction, swap implementation |
| NITDA AP accreditation requirements change | Medium | Storecove/Pagero handle accreditation |
| Nigeria CIUS published with breaking changes | Medium | Schematron layer isolates impact |
| IRN/CSID format changes | Low-Medium | Store raw response, parse lazily |
| Non-resident supplier inclusion (2026) | High likelihood | Adapter must support foreign TIN registration |

---

## Recommended Next Actions

1. **Verify** Storecove Nigeria AP coverage and accreditation status
2. **Create** placeholder `/schematron/nigeria/` directory with README
3. **Monitor** FIRS developer portal for CIUS/schematron publication
4. **Design** IRN + CSID storage fields in invoice schema
5. **Design** QR code rendering in invoice PDF output
6. **Watch** non-resident supplier inclusion announcement (expected 2026)
7. **Update** this document following each FIRS regulatory update

---

## References

- FIRS E-Invoicing Portal: `https://einvoicing.firs.gov.ng`
- NITDA Electronic Invoicing Guidelines: `https://nitda.gov.ng`
- OpenPeppol Authorities: `https://peppol.org/members/peppol-authorities/`
- Nigeria Tax Administration Act 2025: `https://firs.gov.ng/legislation`
- Peppol BIS Billing 3.0: `https://docs.peppol.eu/poacc/billing/3.0/`
- OpenPeppol GitHub: `https://github.com/OpenPeppol`
