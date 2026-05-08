# Future Peppol Integration — MENA (Middle East & North Africa)

## Overview

The Arab world is undergoing one of the fastest regional e-invoicing transformations
globally. The GCC countries (Saudi Arabia, UAE, Oman, Bahrain, Kuwait, Qatar) are
converging on Peppol-based standards, while North African countries (Egypt, Morocco,
Tunisia, Algeria) are at varying stages of digital tax modernisation.

The region broadly divides into two groups:

- **GCC** — Peppol-aligned, PINT-based, 5-corner model, Tier 1–1.5
- **North Africa / Levant** — proprietary or emerging standards, Tier 2–3

This document covers each country's status, tier classification, and integration
priority relative to the existing Peppol BIS Billing 3.0 architecture.

---

## Regional Tier Classification

| Country | Model | Tier | Priority |
|---|---|---|---|
| Saudi Arabia | ZATCA FATOORAH — clearance, proprietary JSON | 1.5 | High — live now |
| UAE | Peppol PINT-AE — 5-corner DCTCE | 1 | High — pilot July 2026 |
| Oman | Peppol 5-corner — OTA accredited ASPs | 1 | Medium — pilot 2026 |
| Bahrain | Real-time reporting (RTR) — early stage | 2 | Monitor |
| Kuwait | Exploring — linked to VAT implementation | 3 | Watch |
| Qatar | Exploring — linked to VAT implementation | 3 | Watch |
| Egypt | Proprietary ETA platform — B2B mature, B2C rolling out | 2 | Medium |
| Morocco | Mandatory 2026 large enterprises — DGI platform | 2 | Monitor |
| Tunisia | National platform updated Feb 2026, EU-aligned | 2 | Watch |
| Algeria | Large taxpayer focus — energy sector first | 3 | Watch |

---

## Saudi Arabia — FATOORAH (ZATCA)

### Status
Saudi Arabia is the most advanced Arab country and was the regional pioneer.
The FATOORAH project has been live since December 2021 and is in continuous
mandatory rollout by revenue threshold.

### Current Mandatory Thresholds (VATable income 2022/2023)

| Threshold | Mandatory Date |
|---|---|
| ≥ SAR 7 million | January 2025 |
| ≥ SAR 5 million | February 2025 |
| ≥ SAR 4 million | March 2025 |
| ≥ SAR 3 million | April 2025 |
| ≥ SAR 2.5 million | July 2025 |
| ≥ SAR 1.25 million | December 2025 |
| ≥ SAR 1 million | January 2026 |
| ≥ SAR 750,000 | March 2026 |
| ≥ SAR 350,000 | June 2026 |

The mandate covers B2B, B2C, and B2G transactions. Non-resident businesses
are currently exempt.

### Technical Model
Saudi Arabia uses a **centralised clearance model** — not a Peppol transport
model. This places it at **Tier 1.5**:

- Invoices submitted to ZATCA's FATOORAH platform via API
- ZATCA validates and returns a **Cryptographic Stamp (CSID)** and **UUID**
- QR code mandatory on all invoices
- Format: **UBL XML** with ZATCA-specific extensions (not standard Peppol PINT)

### Tier Classification
**Tier 1.5** — UBL XML basis (compatible with existing generation) but ZATCA
clearance hub is proprietary, not Peppol transport. A dedicated ZATCA adapter
is required.

### Adapter Requirements
```php
interface ZATCAPeppolAPInterface
{
    // Submit UBL to ZATCA for clearance
    public function submit(string $ublXml): ClearanceResponse;

    // Handle CSID + UUID + QR code response
    public function handleClearanceResponse(
        ClearanceResponse $response
    ): DocumentStatus;

    // Simplified invoice reporting (B2C)
    public function reportSimplified(string $ublXml): ReportingResponse;
}
```

---

## UAE — PINT-AE (FTA / Ministry of Finance)

### Status
The UAE has adopted the most architecturally clean Peppol model in the Arab world
— a decentralised 5-corner DCTCE (Decentralised CTC and Exchange) model using
**Peppol PINT-AE**, the UAE-specific PINT profile. Technical specifications were
published on 23 February 2026.

### Timeline

| Milestone | Date |
|---|---|
| Technical specifications published | February 2026 |
| Voluntary pilot opens | July 1, 2026 |
| Large businesses (≥ AED 50m revenue) appoint ASP | July 31, 2026 |
| Large businesses go live | January 1, 2027 |
| Smaller businesses appoint ASP | March 31, 2027 |
| Smaller businesses go live | July 1, 2027 |
| Government entities | October 1, 2027 |
| B2C | TBD — currently excluded |

### Technical Model
The UAE 5-corner model operates as follows:

```
Supplier App (PINT-AE UBL XML)
    └── Supplier ASP (Corner 2 — validation + transmission)
        └── FTA Repository (Corner 5 — receives copy, no pre-clearance)
            └── Buyer ASP (Corner 3)
                └── Buyer App (Corner 4)
```

No pre-clearance is required from the FTA — invoices are validated by the
supplier's ASP and transmitted directly to the buyer. The FTA receives a
copy for audit purposes.

### PINT-AE Specifics
- Participant identifier: first 10 digits of the corporate tax registration (TIN)
- Format: Peppol PINT-AE XML — a PINT profile with UAE-specific extensions
- Export invoices: must be reported but not transmitted via Peppol
- Archiving: 7 years mandatory, stored within the UAE

### Tier Classification
**Tier 1** — true Peppol 5-corner model. Existing UBL generation requires
extension to PINT-AE profile. Schematron CIUS rules pending publication.

### Penalties
- AED 5,000 per month — failure to implement or appoint ASP
- AED 100 per non-conforming invoice (capped AED 5,000/month)
- AED 1,000 per day — failure to report system malfunction

### Adapter Requirements
UAE uses standard Peppol AP transmission — existing Storecove/Tickstar
integration applies once PINT-AE schematron is added. Verify ASP
accreditation status with provider.

---

## Oman — OTA 5-Corner Model

### Status
After several delays, Oman's e-invoicing project is officially back on track
under the Oman Tax Authority (OTA), which signed an agreement with Omantel
to develop the national infrastructure.

### Timeline

| Milestone | Date |
|---|---|
| Pilot — B2G focus | Early 2026 |
| Top 100 largest taxpayers pilot | August 2026 |
| Other large taxpayers | February 2027 |
| Broader rollout | TBD |

### Technical Model
Similar to the UAE — Peppol 5-corner model with OTA-accredited ASPs only.
B2G transactions are prioritised in the first phase before B2B expansion.

### Tier Classification
**Tier 1** — Peppol 5-corner. CIUS/schematron not yet published.

---

## Bahrain, Kuwait, Qatar

These three GCC countries are at early stages:

- **Bahrain** — moving toward Real-Time Reporting (RTR) to close VAT gap;
  no Peppol commitment yet confirmed
- **Kuwait** — exploring e-invoicing linked to its ongoing VAT implementation;
  no mandate timeline confirmed
- **Qatar** — similar position to Kuwait; regional momentum likely to
  accelerate adoption

All three are classified **Tier 2–3** and should be monitored rather than
actively developed for now.

---

## Egypt — ETA Platform

### Status
Egypt operates a mature proprietary e-invoicing platform via the Egyptian
Tax Authority (ETA). B2B e-invoicing has been mandatory for large taxpayers
since 2021 and is expanding. A separate **e-receipt system** (B2C) is now
rolling out nationally.

Integration between the e-invoicing system and the **Nafeza** customs platform
is finalised, unifying import, export, and tax data into a centralised
risk-analysis engine.

### Technical Model
- Proprietary ETA platform — not Peppol-based
- JSON format submitted to ETA API
- Digital signature required (PKI-based)
- UUID and long ID returned on clearance
- No Peppol alignment confirmed as of Q1 2026

### Tier Classification
**Tier 2** — format transformation required. No UBL reuse possible without
significant mapping. Lower priority unless specific Egypt market need exists.

---

## Morocco

Mandatory enforcement began in 2026 for large enterprises under the
Direction Générale des Impôts (DGI) roadmap. The primary driver is curbing
the informal economy. Technical standard is DGI-proprietary — no Peppol
alignment confirmed.

**Tier 2** — Monitor.

---

## Tunisia and Algeria

- **Tunisia** — national platform updated February 2026 to increase capacity
  and align with EU standards; structured format but not Peppol
- **Algeria** — focusing on Large Taxpayer Office (LGE) and energy sector;
  commercial sector expansion expected 2027

Both classified **Tier 3** — Watch only.

---

## CIUS / Schematron Considerations

| Country | PINT Profile | CIUS Published | Schematron Available |
|---|---|---|---|
| UAE | PINT-AE | Yes (Feb 2026) | Pending |
| Oman | PINT-OM (expected) | No | No |
| Saudi Arabia | ZATCA UBL extensions | Partial | No |
| Others | N/A | No | No |

Placeholder directories:

```
/schematron
    /peppol-bis-3.0/
    /xrechnung/
    /nlcius/
    /sars/
    /nigeria/
    /arab/
        /pint-ae/        ← UAE — populate from FTA when published
        /pint-om/        ← Oman — placeholder
        /zatca/          ← Saudi Arabia — proprietary extensions
        README.md
```

---

## AP Provider Coverage — Arab Region

| Provider | Saudi Arabia | UAE | Oman | Notes |
|---|---|---|---|---|
| EDICOM | Yes | Yes (certified ASP) | Tracking | Deep regional expertise |
| Storecove | Verify | Verify | Verify | Check PINT-AE coverage |
| Pagero | Tracking | Tracking | — | Enterprise-focused |
| OrchidaTax | Yes | Yes | Yes | Arab-native provider |

For an open source application, **EDICOM** has the deepest Arab region coverage
and is a certified UAE ASP. However their API model is more enterprise-oriented.
**OrchidaTax** is worth evaluating as an Arab-native provider with regional
expertise.

---

## GitHub Actions — Maintenance Strategy

The Arab region has no single GitHub-based publication channel. A combination
of official portal monitoring and regional compliance feed aggregation is required.

```yaml
name: Monitor Arab E-Invoicing Updates
on:
  schedule:
    - cron: '0 8 * * 1'  # Weekly Monday
jobs:
  check-arab:
    steps:
      - name: Check UAE FTA e-invoicing portal
        # Fetch https://www.tax.gov.ae/en/einvoicing
      - name: Check ZATCA Saudi Arabia
        # Fetch https://zatca.gov.sa/en/E-Invoicing/
      - name: Check Oman Tax Authority
        # Fetch https://tms.taxoman.gov.om
      - name: Check OpenPeppol PINT updates
        # Fetch https://peppol.org/post_type_peppolnews/
      - name: Create issue if content changed
```

### Key Sources to Watch

| Country | Source | URL |
|---|---|---|
| UAE | FTA e-invoicing portal | `https://www.tax.gov.ae/en/einvoicing` |
| UAE | Ministry of Finance | `https://mof.gov.ae` |
| Saudi Arabia | ZATCA FATOORAH | `https://zatca.gov.sa/en/E-Invoicing/` |
| Oman | Oman Tax Authority | `https://tms.taxoman.gov.om` |
| Egypt | ETA portal | `https://eta.gov.eg` |
| Regional | VATupdate | `https://www.vatupdate.com` (tag: GCC) |
| Regional | OpenPeppol PINT | `https://peppol.org` |

---

## Integration Priority Matrix

| Country | Tier | Urgency | Effort | Recommendation |
|---|---|---|---|---|
| UAE | 1 | High — Jan 2027 | Low-Medium | Integrate — PINT-AE schematron + ASP |
| Saudi Arabia | 1.5 | High — live now | Medium | ZATCA adapter — clearance model |
| Oman | 1 | Medium — Aug 2026 pilot | Low-Medium | Monitor — PINT profile expected |
| Nigeria | 1 | Urgent — live Nov 2025 | Medium | See `future_peppol_nigeria.md` (Africa suite) |
| Egypt | 2 | Low-Medium | High | Defer — proprietary format |
| Bahrain | 2 | Low | Medium | Monitor |
| Morocco | 2 | Low | High | Monitor |
| Kuwait/Qatar | 3 | Very Low | High | Watch only |
| Tunisia/Algeria | 3 | Very Low | High | Watch only |

---

## Recommended Next Actions

1. **UAE** — add PINT-AE schematron when published; verify Storecove UAE ASP
   accreditation; design PINT-AE UBL profile extension
2. **Saudi Arabia** — evaluate ZATCA adapter effort; EDICOM is the most
   viable existing AP with Saudi certification
3. **Oman** — create `/schematron/arab/pint-om/` placeholder; monitor OTA
   pilot launch August 2026
4. **All** — set up GitHub Actions monitoring for FTA and ZATCA portals
5. **Update** this document following each GCC regulatory update

---

## References

- UAE FTA E-Invoicing: `https://www.tax.gov.ae/en/einvoicing`
- UAE Electronic Invoicing Guidelines V1.0 (Feb 2026): Ministry of Finance
- ZATCA FATOORAH: `https://zatca.gov.sa/en/E-Invoicing/`
- Oman Tax Authority: `https://tms.taxoman.gov.om`
- OpenPeppol PINT: `https://docs.peppol.eu/poac/pint/`
- Peppol BIS Billing 3.0: `https://docs.peppol.eu/poacc/billing/3.0/`
- VATupdate GCC: `https://www.vatupdate.com`
