# Peppol Send via Oxalis

End-to-end implementation of the **"Send via Peppol (Oxalis)"** menu option on
the invoice view. Clicking the option generates a UBL 2.4 XML document and
transmits it to the Peppol network through a local Oxalis AS4 gateway (or a
WireMock stub during development).

---

## Architecture Overview

```
Invoice View (options dropdown)
        │  GET inv/peppolSend/{id}
        ▼
InvController → Trait\Peppol::peppolSend()
        │  builds UBL XML (reuses PeppolHelper pipeline)
        │  reads recipient from ClientPeppol entity
        ▼
PeppolSendService::send()
        │  1. writes PeppolMessage (status = QUEUED) — crash-safe audit trail
        │  2. POST /outbound/send → Oxalis REST API (or WireMock on :8181)
        │  3. updates PeppolMessage → SENT + messageId  |  FAILED + error
        ▼
peppol_message table  (Cycle ORM)
        ▼
flash message → redirect inv/view
```

---

## New Files

| File | Purpose |
|------|---------|
| `src/Infrastructure/Persistence/PeppolMessage/PeppolMessage.php` | Cycle ORM entity — audit record for every send attempt |
| `src/Invoice/Peppol/PeppolMessageRepository.php` | Cycle ORM repository — save, delete, find by id/inv/status |
| `src/Invoice/Peppol/PeppolSendService.php` | PSR-18 HTTP client wrapper — QUEUED → SENT / FAILED lifecycle |
| `config/common/di/peppol.php` | Yii3 DI binding — wires `oxalisBaseUrl` from `OXALIS_BASE_URL` env var |
| `oxalis-mock/mappings/outbound-send.json` | WireMock stub — returns `{"messageId":"mock-msg-00000001","status":"ok"}` |

---

## Modified Files

| File | Change |
|------|--------|
| `src/Invoice/Inv/Trait/Peppol.php` | Added `peppolSend()` action |
| `config/common/routes/routes.php` | Added `inv/peppolSend` route |
| `resources/views/invoice/inv/view.php` | Added "Send via Peppol (Oxalis)" menu item (non-draft only) |
| `resources/messages/en/app.php` | Added `peppol.send.via.oxalis` translation key |
| `docker-compose.yml` | Added `oxalis-mock` WireMock service on port 8181 |
| `.gitignore` | Added `oxalis-conf/`, `*.p12`, `*.jks`, `*.keystore`, `*.pfx` |

---

## PeppolMessage Entity

**Table:** `peppol_message`  
**Namespace:** `App\Infrastructure\Persistence\PeppolMessage`

| Column | Type | Notes |
|--------|------|-------|
| `id` | `primary` | Auto-increment PK |
| `inv_id` | `integer(11)` | Foreign key to `inv` |
| `message_id` | `string(100)` | Returned by Oxalis on success |
| `recipient_id` | `string(255)` | Peppol participant ID e.g. `0088:1234567890123` |
| `document_type_id` | `string(255)` | Defaults to BIS Billing 3.0 invoice URN |
| `process_id` | `string(255)` | Defaults to BIS Billing 3.0 process URN |
| `status` | `string(20)` | `QUEUED` → `SENT` / `FAILED` → `RETRYING` |
| `sent_at` | `datetime` | Set on successful HTTP response |
| `delivered_at` | `datetime` | Set by inbound Oxalis delivery callback (Phase 3) |
| `error_message` | `string(1000)` | Populated on `FAILED` |
| `retry_count` | `integer(11)` | Incremented by `PeppolSendService::retry()` |
| `created_at` | `datetime` | Set by `Behavior\CreatedAt` |

---

## Status Lifecycle

```
QUEUED  ──► SENT ──► (DELIVERED — future inbound callback)
   │
   └──► FAILED ──► RETRYING ──► SENT / FAILED
```

A `PeppolMessage` row is written with status `QUEUED` **before** the HTTP
call. This means a PHP crash between QUEUED and SENT leaves a recoverable
audit record. `PeppolSendService::retry()` increments `retry_count`, sets
`RETRYING`, saves, then delegates to `send()`.

---

## Route

```
GET|POST  /inv/peppolSend/{id}
          middleware: AccessChecker → permission pEI (editInvoice)
          action:     InvController::peppolSend
          name:       inv/peppolSend
```

---

## peppolSend() Action — Key Steps

Located in `src/Invoice/Inv/Trait/Peppol.php`:

1. Guard: guest → 404
2. Load invoice with `repoInvLoadInvAmountquery($id)`
3. Assert client Peppol setup is complete via `peppolClientFullySetup()`
4. Run the full `PeppolHelper` XML generation pipeline (same pipeline as
   `inv/peppol` screen view)
5. Write XML to a temp file and read it back as a string
6. Build `$recipientId` from `ClientPeppol::getEndpointidSchemeid() . ':' . getEndpointid()`
7. Call `PeppolSendService::send($id, $ublXml, $recipientId)`
8. Flash `info` on `SENT` (includes messageId), `warning` on any other status
9. Redirect to `inv/view`

---

## DI Configuration

`config/common/di/peppol.php` is auto-loaded by the `common/di/*.php` glob in
`config/.merge-plan.php`. It binds `PeppolSendService::$oxalisBaseUrl` from
the `OXALIS_BASE_URL` environment variable, defaulting to `http://localhost:8181`
(the WireMock port used in Phase A development).

```php
// .env — Phase A (WireMock mock)
OXALIS_BASE_URL=http://localhost:8181

// .env — Phase B (real Oxalis container)
OXALIS_BASE_URL=http://localhost:8080
```

The other constructor dependencies (`ClientInterface`, `RequestFactoryInterface`,
`StreamFactoryInterface`, `PeppolMessageRepository`) are auto-wired by the
container from `psr17.php`, `psr18.php`, and the Cycle ORM schema respectively.

---

## Development Setup (Phase A — WireMock)

WireMock stands in for Oxalis before real AP certificates are available.

```bash
# Start WireMock (Docker Desktop must be installed)
docker compose up oxalis-mock -d

# Verify stub loaded
curl http://localhost:8181/__admin/mappings

# Smoke test the endpoint
curl -X POST http://localhost:8181/outbound/send \
  -H "Content-Type: application/json" \
  -d '{"recipient":"0088:test","payload":"dGVzdA=="}'
# → {"messageId":"mock-msg-00000001","status":"ok"}
```

The WireMock mapping is at `oxalis-mock/mappings/outbound-send.json`.

---

## Production Setup (Phase B — Real Oxalis)

Once an OpenPeppol AP certificate has been issued:

1. Place the certificate keystore in `oxalis-conf/` (never committed to git)
2. Add the real Oxalis service to `docker-compose.yml`, mounting `oxalis-conf/`
3. Set `OXALIS_BASE_URL=http://localhost:8080` in `.env`
4. Remove or comment out the `oxalis-mock` service block

See [OXALIS_INTEGRATION.md](OXALIS_INTEGRATION.md) for the full phased plan and
cost breakdown.

---

## Menu Visibility

The "Send via Peppol (Oxalis)" option in the invoice options dropdown is
**hidden for draft invoices** (`status_id === 1`). It appears for sent, viewed,
paid, and all other non-draft statuses, matching the behaviour of other
transmission options (email, Telegram).

---

## Related Documentation

- [OXALIS_INTEGRATION.md](OXALIS_INTEGRATION.md) — phased delivery plan and cost comparison
- [PEPPOL_ACCESS_POINT_PHP_GUIDE.md](PEPPOL_ACCESS_POINT_PHP_GUIDE.md) — AS4/WS-Security architecture
