# src/Invoice/Peppol — Network Transport Layer

## Connecting to an Oxalis Access Point — Step-by-Step

### Step 1 — Install and start Oxalis

Oxalis is the open-source Peppol AS4 access point.  
Download: https://github.com/OxalisCommunity/oxalis

Start it so its REST API is reachable (default port 8080):

```
http://localhost:8080
```

You can smoke-test the mock stub on port 8181 first (see Step 2).

---

### Step 2 — Set environment variables in `.env`

Copy the block from `.env.example` (the Peppol section at the bottom) into your
`.env` and fill in the values:

```dotenv
# Point at your running Oxalis instance
OXALIS_BASE_URL=http://localhost:8080

# Your Peppol participant ID  (scheme:identifier)
# Example: GS1 GLN scheme = 0088, Norwegian org = 0192, UK = 0060
PEPPOL_SENDER_ID=0088:1234567890123

# Use the acceptance environment while testing
PEPPOL_SML_ZONE=acc.edelivery.tech.ec.europa.eu

# Leave blank — SmpResolver will use live SML/SMP DNS
PEPPOL_SMP_BASE_URL=
```

`config/common/di/peppol.php` reads these vars and wires them into
`PeppolSendService` and `SmpResolver` automatically via Yii3 DI.

---

### Step 3 — Enable Peppol in application settings

In the browser: **Settings → enable_peppol → 1**

This unhides the Peppol toolbar buttons on the invoice view and edit pages.

---

### Step 4 — Add Peppol details for the recipient client

Navigate to the client record and open **Client Peppol** (`clientpeppol/add`).

Fill in at minimum:

| Field | Example | Source |
|-------|---------|--------|
| Endpoint ID | `ross@example.com` | Recipient's Peppol email / GLN |
| Endpoint Scheme ID | `0088` | EAS dropdown — `DownloadedXml/eas.xml` |
| Legal entity company ID | `0088` | ISO 6523 ICD dropdown — `DownloadedXml/icd.xml` |
| Tax scheme ID | region/country row | `StoreCoveArrays::storeCoveReceiverIdentifierArray()` |

---

### Step 5 — Create the invoice via the Peppol workflow

The recommended path is **Quote → Sales Order → Invoice**:

1. Create a Quote for the client
2. Convert to Sales Order (observer/guest submits PO details)
3. Convert to Invoice — the invoice will have `so_id` and `quote_id` set,
   shown as the **🔀 Peppol** badge on `inv/index`

A direct invoice also works; the badge will show 📄 (standard) but can still be
sent over Peppol as long as the client has Peppol details.

---

### Step 6 — Send the invoice

Open the invoice view (`inv/view/{id}`) and click the **Peppol Send** button
(only visible when `enable_peppol = 1`).

This hits route `inv/peppolSend/{id}` → `InvController::peppolSend()` → `PeppolSendService::send()`.

What happens internally:

```
1. SmpResolver looks up recipient's AS4 endpoint via SML DNS + SMP HTTP
2. PeppolSendService persists a PeppolMessage with status QUEUED
3. POST multipart/form-data to {OXALIS_BASE_URL}/outbound/send
4. On success  → status updated to SENT
   On failure  → status FAILED (retryable via console command below)
```

---

### Step 7 — Expose the inbound delivery callback to Oxalis

Oxalis calls back your application when delivery is confirmed:

```
POST /peppol/inbound/delivery
Body: { "messageId": "<uuid>" }
```

This endpoint is registered in `config/common/routes/routes.php` and handled
by `PeppolInboundController::delivery()`.  It marks the matching `PeppolMessage`
as **DELIVERED**.

Make sure Oxalis can reach your application's public URL on this path.  
In development, use a tunnel such as `ngrok http 80` and configure the callback
URL in Oxalis's `oxalis.conf`.

---

### Step 8 — Retry failed sends (optional)

If a send fails (network error, Oxalis down), a console command retries all
`FAILED` / `RETRYING` messages:

```bash
php yii peppol/retry-failed
```

Source: `src/Invoice/Peppol/Console/RetryFailedCommand.php`

---

## Acceptance vs Production

| | Acceptance | Production |
|---|---|---|
| `PEPPOL_SML_ZONE` | `acc.edelivery.tech.ec.europa.eu` | `edelivery.tech.ec.europa.eu` |
| Participant IDs | Test IDs from your Access Point provider | Live registered IDs |
| Oxalis keystore | Test certificate | Production certificate from your AP provider |

Always validate in the acceptance environment before switching to production.

---

This folder handles **sending invoices over the Peppol network** and **receiving
delivery confirmations** back from the access point.  It knows nothing about UBL
XML structure, validation, or code lists — those live in
`src/Invoice/Helpers/Peppol/`.

## What each file does

| File | Role |
|------|------|
| `PeppolSendService.php` | Sends a UBL 2.4 XML document to Oxalis via its REST API. Persists a `PeppolMessage` record *before* the HTTP call so a crash leaves a recoverable audit trail. |
| `PeppolInboundController.php` | Receives delivery callbacks from Oxalis (`POST /peppol/inbound/delivery`). Marks the matching `PeppolMessage` as DELIVERED. Not user-facing — Oxalis calls it directly. |
| `PeppolMessageRepository.php` | Cycle ORM repository for `PeppolMessage` persistence (`save`, `repoByMessageId`, `repoByStatus`). |
| `PeppolMessageRepositoryInterface.php` | Interface for the repository — allows swapping in a test double. |
| `SmpResolver.php` | Looks up a recipient's AS4 endpoint URL and certificate via the Peppol SML/SMP chain (DNS hash → CNAME → SMP HTTP query). |
| `SmpResolverInterface.php` | Interface for `SmpResolver` — `resolve()` and `isRegistered()`. |
| `SmpEndpoint.php` | Readonly value object returned by `SmpResolver`: `endpointUrl`, `certificate`, `transportProfile`. |
| `SmpLookupException.php` | Thrown when SML/SMP lookup fails (participant not registered, document type not supported, unparseable response). |

## Message status lifecycle

```
QUEUED  (persisted before HTTP call)
  │
  ▼
SENT    (Oxalis accepted the document)
  │
  ├──► DELIVERED  (Oxalis callback hits PeppolInboundController)
  │
  └──► FAILED / RETRYING  (HTTP error or Oxalis rejection)
```

## SML/SMP lookup (SmpResolver)

```
participantId  →  md5(lowercase("iso6523-actorid-upis::{id}"))
             →  DNS CNAME  B-{hash}.iso6523-actorid-upis.{smlZone}
             →  SMP host   GET http://{smpHost}/{participantId}/services/{docTypeId}
             →  SmpEndpoint { endpointUrl, certificate, transportProfile }
```

Set `$smpBaseUrl` in the constructor to bypass DNS — useful in development and tests.

## What this folder is NOT

- UBL XML construction → `src/Invoice/Helpers/Peppol/PeppolHelper.php`
- Peppol business-rule validation → `src/Invoice/Helpers/Peppol/PeppolValidator.php`
- Code-list dropdown data → `src/Invoice/Helpers/Peppol/PeppolArrays.php`
- Code-list validation membership → `src/Invoice/Helpers/Peppol/CodeList.php`
- Client Peppol identity (endpoint ID, scheme, legal entity) → `src/Invoice/ClientPeppol/`
