# OpenPeppol Service Provider Certification — Preparation Guide

## Overview

To join the Peppol network as a **certified Access Point (AP)** and **SMP operator**
you must complete OpenPeppol's Service Provider Accreditation process.
This document captures every step needed, cross-referenced to the code already in place.

---

## 1. Membership

| Step | Status | Notes |
|------|--------|-------|
| Register at https://peppol.org/join/ | ☐ | Choose "Service Provider" membership |
| Sign OpenPeppol Transport Infrastructure Agreement (TIA) | ☐ | Legal document |
| Pay annual membership fee | ☐ | Depends on membership tier |

---

## 2. Test Environment — PEPPOL Pilot

### 2a. Get a TEST PKI certificate

OpenPeppol issues **test** certificates from their Test-CA. These are free and
separate from production.

1. Generate a CSR: see `docs/PEPPOL_PKI_CERTIFICATE_REQUEST.md`
2. Submit via the **OpenPeppol Member Portal** (https://openpeppol.atlassian.net)
3. Receive `TEST_AP_YIII_<serial>.crt` (PEM format)

### 2b. Configure the access point

```
/etc/as4/certificates/
├── test-signing-cert.pem       # from OpenPeppol Test-CA
├── test-signing-key.pem        # your private key (never leave the server)
├── test-encrypt-cert.pem       # same cert (or separate if required)
└── peppol-test-root-ca.pem     # OpenPeppol Test Root CA bundle
```

Set in your environment / DI config (adjust paths):

```bash
AS4_SIGNING_CERT=/etc/as4/certificates/test-signing-cert.pem
AS4_SIGNING_KEY=/etc/as4/certificates/test-signing-key.pem
AS4_ENCRYPT_CERT=/etc/as4/certificates/test-encrypt-cert.pem
```

Monitor expiry (test certs expire every 12 months):

```bash
php yii as4/monitor --signing-cert=$AS4_SIGNING_CERT --warn-days=30
```

### 2c. Register in Peppol Test Directory (SMK)

- URL: https://test-smk.peppol.eu
- Add your AP endpoint for at least one test participant
- Document type: `urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::Invoice##urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0::2.1`
- Transport profile: `peppol-transport-as4-v2_0`

### 2d. Interoperability Testing

OpenPeppol requires a pilot test with the **Peppol Test Corner**. Contact them at
testcorner@openpeppol.org.

Items they will test:

| Test | What They Check | Our Implementation |
|------|-----------------|--------------------|
| Outbound — send invoice | AS4 envelope, receipt signal | `As4MessageDispatcher` |
| Inbound — receive invoice | Webhook at `/as4/receive` | `As4ReceiveController` |
| Retry on transient failure | Retry with exponential back-off | `As4RetryEngine` |
| Duplicate detection | Same MessageId rejected | `As4DuplicateDetector` |
| Receipt NRR | Non-repudiation of receipt | `As4NrrValidator` |
| SMP lookup | Dynamic endpoint discovery | `As4SmpResolver` |

Run the full AS4 test suite before the pilot:

```bash
vendor/bin/phpunit Tests/Unit/As4/
```

---

## 3. Production Certification

After the pilot passes:

| Step | Status |
|------|--------|
| Submit conformance test results to OpenPeppol | ☐ |
| Request production PKI certificate (see CSR template) | ☐ |
| Replace test cert paths with production cert paths | ☐ |
| Switch SMP hostname to `smk.peppol.eu` (production SMP) | ☐ |
| Register AP endpoint in production Peppol Directory | ☐ |
| Notify OpenPeppol of go-live date | ☐ |

---

## 4. Post-Certification Obligations

| Obligation | How to Fulfil |
|-----------|---------------|
| Certificate renewal (annual) | `php yii as4/monitor --warn-days=30` in cron |
| Report AS4 incidents within 24 h | Email incident-report@peppol.eu |
| Participate in quarterly audits | Keep AS4 message logs (60-day minimum) |
| Apply OpenPeppol PKI CRL/OCSP | Validate receipts against revocation list |
| UK-PINT profile (from April 2029) | See `docs/UK-E-INVOICING-MANDATE.md` |

---

## 5. Useful Resources

| Resource | URL |
|----------|-----|
| OpenPeppol Member Portal | https://openpeppol.atlassian.net |
| Peppol BIS Billing 3.0 spec | https://docs.peppol.eu/poacc/billing/3.0/ |
| eDelivery AS4 2.0 profile | https://docs.peppol.eu/edelivery/as4/profile/ |
| Peppol PKI policy | https://peppol.org/pki |
| UK HMRC e-invoicing mandate | https://www.gov.uk/government/publications/e-invoicing |

---

## 6. Certificate Lifecycle Cron

Add to server crontab (`crontab -e`):

```cron
# AS4 queue — every 5 minutes
*/5 * * * * php /var/www/invoice/yii as4/retry >> /var/log/as4-retry.log 2>&1

# AS4 health monitor — every hour, alert if exit code ≠ 0
0 * * * * php /var/www/invoice/yii as4/monitor \
  --signing-cert=/etc/as4/certificates/signing-cert.pem \
  --encrypt-cert=/etc/as4/certificates/encrypt-cert.pem \
  --warn-days=30 \
  || mail -s "AS4 monitor alert on $(hostname)" ops@example.com
```
