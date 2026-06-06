# Oxalis Integration Plan

## Context

This project already generates and validates OpenPeppol UBL 2.4 Invoice 3.0.15 XML
(see `src/Invoice/Libraries/PeppolUblXml.php`, `src/Invoice/Inv/Trait/Peppol.php`,
and `src/Invoice/Helpers/Peppol/`).  The missing piece is **AS4 transport** — the
ability to actually transmit those documents over the Peppol network and receive
inbound documents from trading partners.

Oxalis is the reference open-source Peppol AS4 access point.  Rather than implement
raw AS4, WS-Security, and XML Digital Signatures in PHP (high risk, see
[PEPPOL_ACCESS_POINT_PHP_GUIDE.md](PEPPOL_ACCESS_POINT_PHP_GUIDE.md)), Oxalis runs
as a sidecar Java service and this PHP/Yii3 application communicates with it over
HTTP.

**Oxalis repository:** https://github.com/OxalisPeppol/oxalis

> **Note:** A managed alternative to self-hosted Oxalis is Storecove, which is
> already partially integrated in this project (see README — "StoreCove API
> connector with JSON invoice").  See the [cost comparison](#cost-comparison)
> section before committing to the self-hosted route.

---

## Integration Architecture

```text
┌─────────────────────────────────────────────┐
│  Yii3 Invoice (PHP)                         │
│                                             │
│  PeppolUblXml  ──generates──►  UBL 2.4 XML  │
│  PeppolSendService ──POST──►  Oxalis REST   │
│  PeppolInboundController ◄──callback──      │
│  PeppolMessage (Cycle ORM)  (persists state)│
└─────────────────────────────────────────────┘
           ▲  │
    inbound│  │ outbound
           │  ▼
┌─────────────────────────────────────────────┐
│  Oxalis (Java / Docker)                     │
│                                             │
│  AS4 transport                              │
│  WS-Security / XMLDSig                      │
│  PEPPOL certificate store                   │
│  SMP lookup                                 │
│  Non-repudiation receipts                   │
└─────────────────────────────────────────────┘
           │
           │  AS4 over HTTPS
           ▼
    PEPPOL Network
```

PHP owns: document generation, validation, business logic, state persistence,
user-facing UI, and retry scheduling.

Oxalis owns: AS4 transport, WS-Security signing/encryption, SMP discovery, and
receipt handling.

---

## Cost Comparison

Before committing to self-hosted Oxalis, weigh it against managed AP services.

### Option A — Self-Hosted Oxalis

| Cost Item | One-off | Recurring | Notes |
|---|---|---|---|
| Oxalis software | Free | Free | Open source (EUPL) |
| Peppol AP test certificate | Free | Free | From OpenPeppol test PKI |
| Peppol AP production certificate | ~€200–500 | ~€200–500 /yr | Issued by a Peppol Authority (varies by country) |
| OpenPeppol Service Provider membership | ~€1,000–3,000 | ~€1,000–3,000 /yr | Required to operate a certified AP commercially; waived for self-use only |
| Managed SMP (e.g. B2Brouter, Tickstar) | — | ~€50–200 /mo | Needed to register participant IDs; alternatively self-host phoss SMP (free, but adds complexity) |
| Conformance testing fee (CTF) | ~€500–1,500 | — | Charged by the Peppol Authority on initial certification; retesting free if re-using same AP version |
| Interoperability testing partner | Varies | — | Some providers charge a small fee to run interop tests |
| VPS / container resources for Oxalis | — | ~€20–50 /mo | Java app needs ≥1 GB RAM; add to existing server or separate container |
| Developer time (Phases 1–3) | ~4–8 weeks | — | Internal estimate; varies with team Peppol experience |
| Developer time (Phase 5 certification) | ~2–4 weeks | — | Interop testing and CTF submission |
| **Realistic total Year 1** | **~€5,000–15,000** | **~€2,000–6,000 /yr** | Lower bound = self-use only (no commercial SP membership) |

Costs are indicative. The Peppol Authority for your country (e.g. HMRC for the UK,
OpenPeppol for pan-European) sets the definitive fees.

### Option B — Managed AP (Storecove / B2Brouter / Tickstar)

| Cost Item | Notes |
|---|---|
| Per-document fee | Storecove: ~€0.10–0.30 per sent document; B2Brouter: subscription from ~€30/mo |
| SMP registration | Usually included in the managed service |
| No certificate management | Handled by the provider |
| No Peppol Authority membership | Not required — you use the provider's AP |
| No conformance testing | Provider is already certified |
| Integration effort | Typically 1–2 weeks (REST API) |
| **At low volume** | **Managed is almost always cheaper** |
| **At high volume (1000+ docs/mo)** | **Self-hosted becomes cost-competitive** |

**Recommendation:** For most SME-scale invoice SaaS deployments, start with the
Storecove integration already in this project and move to self-hosted Oxalis only if
volume justifies it or if regulatory requirements demand full AP control.

---

## Phase 1 — Infrastructure

### 1.1 Containerise Oxalis

Add an `oxalis` service to `docker-compose.yml`:

```yaml
oxalis:
  image: oxalispeppol/oxalis:latest
  ports:
    - "8080:8080"   # inbound AS4 endpoint (public-facing)
    - "8181:8181"   # outbound REST API (internal only)
  volumes:
    - ./oxalis-conf:/oxalis-conf   # certificates, keystore, config
  environment:
    OXALIS_HOME: /oxalis-conf
    OXALIS_KEYSTORE_PASSWORD: ${OXALIS_KEYSTORE_PASSWORD}
    OXALIS_KEY_PASSWORD: ${OXALIS_KEY_PASSWORD}
```

### 1.2 Obtain Peppol Test Certificates

- Register with the OpenPeppol test environment (free)
- Receive AP certificate (`.p12` keystore) and CA trust store
- Place in `oxalis-conf/` — never commit to git; add to `.gitignore`

**Cost at this stage: £0** — the test environment is free.

### 1.3 Configure Oxalis

`oxalis-conf/oxalis.conf`:

```hocon
oxalis.keystore {
  path     = "/oxalis-conf/ap-keystore.p12"
  password = ${OXALIS_KEYSTORE_PASSWORD}
  key.alias    = "ap"
  key.password = ${OXALIS_KEY_PASSWORD}
}

oxalis.inbound.enabled = true

# Deliver received documents back to PHP
oxalis.inbound.http.url = "http://php-app:80/peppol/inbound"
```

### 1.4 Verify Connectivity

```bash
curl -X POST http://localhost:8181/outbound/send \
  -H "Content-Type: application/json" \
  -d '{"test": true}'
```

---

## Phase 2 — Outbound Sending

### 2.1 New Infrastructure Class: `PeppolMessage`

`src/Infrastructure/Persistence/PeppolMessage/PeppolMessage.php`

| Field | Type | Notes |
|---|---|---|
| `id` | int | PK |
| `inv_id` | int | FK → `inv` |
| `message_id` | string | AS4 message UUID |
| `recipient_id` | string | Peppol participant ID |
| `document_type_id` | string | e.g. BIS Billing 3.0 URN |
| `status` | string | `QUEUED` / `SENT` / `DELIVERED` / `FAILED` / `RETRYING` |
| `sent_at` | DateTimeImmutable\|null | |
| `delivered_at` | DateTimeImmutable\|null | |
| `error_message` | string\|null | Last failure reason |
| `retry_count` | int | |
| `created_at` | DateTimeImmutable | |

### 2.2 New Service: `PeppolSendService`

`src/Invoice/Peppol/PeppolSendService.php`

```php
final class PeppolSendService
{
    public function __construct(
        private readonly ClientInterface $httpClient,  // PSR-18
        private readonly string $oxalisBaseUrl,        // http://oxalis:8181
        private readonly PeppolMessageRepository $pmR,
    ) {}

    public function send(int $invId, string $ublXml, string $recipientId): PeppolMessage
    {
        $message = new PeppolMessage();
        $message->setInvId($invId);
        $message->setRecipientId($recipientId);
        $message->setStatus('QUEUED');
        $this->pmR->save($message);           // persist before network call

        try {
            $response = $this->httpClient->sendRequest(
                new Request('POST', $this->oxalisBaseUrl . '/outbound/send', [
                    'Content-Type' => 'application/json',
                ], json_encode([
                    'recipient'    => $recipientId,
                    'documentType' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
                    'processId'    => 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
                    'payload'      => base64_encode($ublXml),
                ]))
            );
            $body = json_decode((string) $response->getBody(), true);
            $message->setStatus('SENT');
            $message->setMessageId($body['messageId'] ?? '');
            $message->setSentAt(new \DateTimeImmutable());
        } catch (\Throwable $e) {
            $message->setStatus('FAILED');
            $message->setErrorMessage($e->getMessage());
        }

        $this->pmR->save($message);
        return $message;
    }
}
```

### 2.3 New Route and Action

Add route `inv/peppolSend/{id}` wired to a new action in
`src/Invoice/Inv/Trait/Peppol.php` that:

1. Generates UBL XML using the existing `PeppolUblXml` pipeline
2. Looks up `ClientPeppol` for the recipient's Peppol participant ID
3. Calls `PeppolSendService::send()`
4. Redirects to `inv/view/{id}` with a flash message showing the `PeppolMessage`
   status

### 2.4 Async Queue (recommended for production)

Wrap the `PeppolSendService::send()` call in a `yiisoft/yii-queue` job to avoid
blocking the HTTP request.  A database-backed driver works with the existing MySQL
setup and requires no additional infrastructure.

---

## Phase 3 — Inbound Processing

### 3.1 New Controller: `PeppolInboundController`

`src/Invoice/Peppol/PeppolInboundController.php`

Oxalis posts the received document as JSON to `POST /peppol/inbound`:

```php
public function receive(Request $request): Response
{
    $body     = json_decode((string) $request->getBody(), true);
    $xml      = base64_decode($body['payload']);
    $msgId    = $body['messageId'];
    $senderId = $body['sender'];

    $message = new PeppolMessage();
    $message->setMessageId($msgId);
    $message->setRecipientId($senderId);
    $message->setStatus('RECEIVED');
    $this->pmR->save($message);

    $this->peppolInboundProcessor->process($xml, $message);

    return $this->responseFactory->createResponse(200);
}
```

### 3.2 Secure the inbound endpoint

The Oxalis callback URL must only be reachable from within the Docker network.
At the nginx/Apache level, restrict `/peppol/inbound` to the Oxalis container IP.
Add a shared-secret header for defence in depth.

---

## Phase 4 — SMP Registration

To receive documents, the access point must be published in the Peppol SMP so
trading partners can discover the endpoint.

Options:

| SMP Provider | Cost | Notes |
|---|---|---|
| Self-hosted phoss SMP | Free software, ~€10–20/mo hosting | Full control; adds operational overhead |
| B2Brouter managed SMP | ~€50–100/mo | Includes participant ID management UI |
| Tickstar | Subscription, contact for pricing | Used by many Nordic Peppol providers |
| OpenPeppol test SMP | Free | Test network only |

Steps (operational, not code):

1. Register supported document type IDs (BIS Billing 3.0 Invoice, Credit Note, etc.)
2. Publish participant IDs for each company using the access point
3. Point the SMP service metadata endpoint at the Oxalis inbound URL

---

## Phase 5 — Certification

| Step | Cost (indicative) | Notes |
|---|---|---|
| OpenPeppol AP test network testing | Free | testbed.peppol.eu |
| Conformance Test Framework (CTF) submission | ~€500–1,500 | Charged by Peppol Authority |
| Interoperability testing with a certified AP | Varies | Some charge a fee; some do it free as a community service |
| OpenPeppol Service Provider membership (if offering AP commercially) | ~€1,000–3,000 /yr | Not required for internal/self-use |
| Production certificate issuance | ~€200–500 + annual renewal | Issued by Peppol Authority |

---

## New Files Summary

| File | Purpose |
|---|---|
| `src/Infrastructure/Persistence/PeppolMessage/PeppolMessage.php` | Cycle ORM entity — AS4 message state |
| `src/Invoice/Peppol/PeppolMessageRepository.php` | Repository for `PeppolMessage` |
| `src/Invoice/Peppol/PeppolSendService.php` | HTTP client wrapper around Oxalis outbound REST API |
| `src/Invoice/Peppol/PeppolInboundController.php` | Webhook receiver for inbound Oxalis callbacks |
| `src/Invoice/Peppol/PeppolInboundProcessor.php` | Parses inbound UBL and routes to business logic |
| `docker-compose.yml` | Add `oxalis` service |
| `oxalis-conf/oxalis.conf` | Oxalis keystore and inbound handler config (not committed) |
| `config/routes/peppol.php` | Routes for `inv/peppolSend/{id}` and `peppol/inbound` |

---

## Key Decisions

**Oxalis as a sidecar, not embedded** — Oxalis is a Java application; it runs in its
own Docker container alongside the PHP app.  PHP communicates with it over HTTP.
This keeps the two runtimes fully isolated and allows independent upgrades.

**Oxalis handles all AS4 complexity** — XML Digital Signatures, WS-Security,
MIME multipart, receipt acknowledgement, and certificate trust are all delegated.
PHP never touches raw AS4.

**`PeppolMessage` as audit trail** — Every send attempt is persisted before the
HTTP call to Oxalis, so a crash between `QUEUED` and `SENT` is recoverable.  The
status lifecycle mirrors the guide's recommended state machine.

**Reuse existing XML generation** — `PeppolUblXml` and `Inv/Trait/Peppol.php`
already produce valid, Ecosio-validated UBL 2.4.  `PeppolSendService` takes the
generated string and passes it to Oxalis — no duplication.

**Test network first** — All development and CI runs against the Peppol test
SML/SMP (free).  Production certificates are never committed to git.

**Managed AP first, Oxalis when volume justifies** — If monthly document volume
is below ~500 invoices, Storecove or a comparable managed AP is cheaper than
operating self-hosted Oxalis when total infrastructure and certification costs are
included.
