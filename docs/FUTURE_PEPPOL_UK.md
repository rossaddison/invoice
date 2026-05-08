# Future Peppol Integration — United Kingdom (MTD & E-Invoicing)

## Overview

The UK is pursuing a dual-track digital tax strategy: **Making Tax Digital (MTD)** via HMRC's
proprietary JSON API, and an emerging **e-invoicing mandate** expected by April 2029. This document
outlines the integration architecture, current status, and forward roadmap for a Yii-based
invoicing application that is already Peppol BIS Billing 3.0 compliant.

---

## Current Status (as of Q1 2026)

| Component | Status |
|---|---|
| OAuth 2.0 (HMRC sandbox) | Complete |
| Fraud Prevention Headers (FPH) | Complete |
| Peppol UBL XML generation | Complete |
| Peppol BIS 3.0 schematron validation | Complete |
| HMRC submission pipeline | In progress |
| AP provider integration | Pending |

---

## MTD Pipeline

### Authentication
- OAuth 2.0 with HMRC identity service
- Token refresh handling required — HMRC access tokens are short-lived
- FPH headers mandatory on every API call — capturing device, session and public IP data

### Fraud Prevention Headers (FPH)
The following headers are required on all HMRC API calls:

```
Gov-Client-Public-IP
Gov-Client-Device-ID
Gov-Client-User-IDs
Gov-Vendor-Version
Gov-Client-Timezone
Gov-Client-Screens
Gov-Client-Window-Size
Gov-Client-Browser-Plugins
Gov-Client-Browser-JS-User-Agent
```

FPH requirements differ between server-side and client-side applications. A web-based
invoicing app must capture client-side data via JavaScript and pass it to the server
before making HMRC API calls.

### Submission Pipeline

```
Invoice Data
    └── UBL XML Generation (complete)
        └── Peppol Schematron Validation (complete)
            └── MTD JSON Transformation
                └── HMRC API Submission
                    └── Obligation Status Tracking
                        └── CorrelationId Storage
```

### Key Endpoints

| Endpoint | Purpose |
|---|---|
| `/organisations/vat/{vrn}/obligations` | Retrieve what HMRC expects and by when |
| `/organisations/vat/{vrn}/returns` | Submit VAT return |
| `/organisations/vat/{vrn}/liabilities` | Retrieve liabilities |
| `/organisations/vat/{vrn}/payments` | Retrieve payment history |

### Submission Status Flags
Each invoice/return record should carry a status flag:

```
draft → pending → submitted → accepted → rejected
```

Store HMRC's `correlationId` and timestamp against every submission. HMRC requires
this in the event of a dispute.

---

## MTD Roadmap

| Mandate | Threshold | Date |
|---|---|---|
| VAT MTD | All VAT-registered | Already mandatory |
| ITSA (sole traders/landlords) | Income > £50,000 | April 2026 |
| ITSA (wider rollout) | Income > £30,000 | April 2027 |
| ITSA (full rollout) | Income > £20,000 | April 2028 |
| E-invoicing (all VAT invoices) | All | April 2029 |

### ITSA-Specific Complexity
ITSA introduces **crystallisation** — a strict submission sequence:

1. Quarterly periodic updates (income and expenses per business)
2. End of Period Statement (EOPS) — one per income source per tax year
3. Final Declaration — replaces the old Self Assessment return

These must be submitted in order. The app must enforce this sequencing and track
obligation status per taxpayer per period.

---

## E-Invoicing Mandate (2029)

The UK government has confirmed mandatory e-invoicing for all VAT invoices by April 2029.
The format and transmission model are still under consultation as of Q1 2026.

### Likely Architecture
Given HMRC's existing MTD infrastructure, the expected model is:

```
Supplier App (Peppol UBL)
    └── HMRC Validation Gateway
        └── Buyer App
```

Whether HMRC will adopt Peppol as the transmission standard or maintain a proprietary
JSON model is unconfirmed. The existing Peppol UBL output positions the app well for
either outcome — format transformation to JSON is straightforward if required.

---

## AP Adapter Layer

Since the app generates valid Peppol UBL directly, the Access Point (AP) is a
certified transmission layer only — no format transformation required.

### Recommended AP Providers

| Provider | Notes |
|---|---|
| Storecove | Clean REST API, POST UBL directly, good fit for server-side apps |
| Tickstar | Strong Nordic/EU presence, REST API |
| Pagero | Enterprise-focused, wide country coverage |

### Integration Pattern

```php
interface UKPeppolAPInterface
{
    public function submit(string $ublXml): SubmissionResponse;
    public function getStatus(string $documentId): DocumentStatus;
    public function getObligations(string $vrn): array;
}
```

---

## GitHub Actions — Maintenance Strategy

HMRC API versions and MTD mandate details evolve independently of the Peppol release
cycle. The following automated checks are recommended:

```yaml
name: Monitor HMRC API Changes
on:
  schedule:
    - cron: '0 8 * * 1'  # Weekly Monday
jobs:
  check-hmrc:
    steps:
      - name: Check HMRC Developer Hub changelog
        # Fetch https://developer.service.hmrc.gov.uk/api-documentation
      - name: Create issue if breaking changes detected
```

### Key Sources to Watch
- HMRC Developer Hub: `https://developer.service.hmrc.gov.uk`
- MTD ITSA consultation updates: `https://www.gov.uk/government/collections/making-tax-digital`
- E-invoicing consultation: `https://www.gov.uk/government/consultations`

---

## Sandbox Caveats

- HMRC sandbox test data can be reset without warning
- Some sandbox endpoints return HTTP 200 but do not fully validate payload structure —
  production may reject what sandbox accepted
- Rate limiting is more aggressive in production than sandbox
- Always test against the full FPH header set in staging — partial headers are accepted
  in sandbox but rejected in production

---

## References

- HMRC MTD VAT API: `https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api`
- HMRC MTD ITSA API: `https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/self-assessment-api`
- Peppol BIS Billing 3.0: `https://docs.peppol.eu/poacc/billing/3.0/`
- OpenPeppol GitHub: `https://github.com/OpenPeppol`
