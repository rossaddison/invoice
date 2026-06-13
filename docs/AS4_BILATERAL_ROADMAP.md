# AS4 Access Point — Bilateral & Peppol Roadmap

**Date:** June 2026

This document tracks the phased build-out of a native AS4 Access Point within
Yii3-i. It is a living document: update the status column and add notes as work
progresses.

---

## Current State — Outbound AS4 Complete

The `as4` branch contains a fully operational outbound AS4 stack. No external
Oxalis container is required for outbound messages — the PHP layer handles SOAP
envelope construction, MIME packaging, retry policy, and receipt tracking
directly.

### Built so far

| Class / Interface | Location | Responsibility |
|---|---|---|
| `As4Message` | `src/Infrastructure/Persistence/As4Message/` | Cycle ORM entity; lifecycle state machine (`pending → sent → receiptReceived / failed`) |
| `CycleOrmAs4MessageRepository` | `src/Infrastructure/Persistence/As4Message/` | Persistence + atomic `claimForRetry()` (raw SQL CAS, 600 s TTL) |
| `As4MessageRepositoryInterface` | `src/Invoice/As4/` | Domain interface; decouples retry engine from ORM |
| `As4MessageState` | `src/Invoice/As4/` | PHP 8.4 backed enum: `pending`, `sent`, `receiptReceived`, `failed` |
| `As4RetryEngine` | `src/Invoice/As4/` | Reception Awareness: `processRetries()`, `detectMissingReceipts()`, `getNextRetryDelay()` |
| `As4RetryPolicyInterface` | `src/Invoice/As4/` | `delaySeconds(int $attempt, int $base): int` |
| `As4FixedIntervalRetryPolicy` | `src/Invoice/As4/` | Fixed delay between retries |
| `As4ExponentialBackoffRetryPolicy` | `src/Invoice/As4/` | `min(base × multiplier^(n−1), maxDelay) + jitter` |
| `As4SenderInterface` | `src/Invoice/As4/` | `send(string $endpoint, DOMDocument $envelope, array $parts): As4HttpResponse` |
| `As4Sender` | `src/Invoice/As4/` | PSR-18 HTTP client implementation |
| `As4HttpResponse` | `src/Invoice/As4/` | Value object: `statusCode`, `body`, `contentType` |
| `As4ReceiptParserInterface` | `src/Invoice/As4/` | Parses synchronous ebMS3 signal from the HTTP response body |
| `As4ReceiptSignal` | `src/Invoice/As4/` | Value object: confirmed delivery (EBMS Receipt) |
| `As4ErrorSignal` | `src/Invoice/As4/` | Value object: receiver rejection or warning |
| `As4ErrorSeverity` | `src/Invoice/As4/` | Enum: `Failure` (reject), `Warning` (accepted with caveat) |
| `As4ErrorCategory` | `src/Invoice/As4/` | Enum: `Processing`, `Unpackaging`, `Communication`, `InternalProcess` |

### Signal detection logic

HTTP 200/202 does not mean delivery. `As4RetryEngine::handleSuccessResponse()` parses
the body via `As4ReceiptParserInterface` and branches:

| Signal | Action |
|---|---|
| `As4ReceiptSignal` | `markReceiptReceived()` + save |
| `As4ErrorSignal` (Failure) | `markFailed()` + save |
| `As4ErrorSignal` (Warning) | log warning, leave as `sent` |
| `null` (async 202) | log info, leave as `sent` |

### Concurrency protection

`processRetries()` calls `claimForRetry()` — a single-round-trip `UPDATE … WHERE
locked_at IS NULL OR locked_at < expiry` — before dispatching. A false return means
another worker owns the message; the engine skips it silently without incrementing any
counter.

### Test coverage

`Tests/Unit/Invoice/As4/As4RetryEngineTest.php` — 15 tests; no `@psalm-suppress`;
Psalm errorLevel 1 clean.

---

## Phase 1 — Inbound Pipeline (Bilateral AS4)

Bilateral AS4 means two installations that both run this repo can exchange UBL 2.4
invoices directly, without Peppol PKI, SMP, or SML. This is the recommended
development and integration-testing strategy before pursuing OpenPeppol accreditation.

**Target topology:** `localhost` (dev) ↔ `yii3i.online` (staging)

### Components to build

| Class / Interface | Location (proposed) | Responsibility | Status |
|---|---|---|---|
| `As4InboundMessage` | `src/Invoice/As4/` | Value object: parsed inbound message (UserMessage / Receipt / Error) | ✅ done |
| `As4ParseException` | `src/Invoice/As4/` | Dedicated exception for inbound parse failures (replaces generic `\Exception`) | ✅ done |
| `As4Receiver` | `src/Invoice/As4/` | Boundary detection, CID→part mapping, DOM parse of inbound SOAP | ✅ done |
| `As4SignatureVerifierInterface` | `src/Invoice/As4/` | Verify WS-Security XML-DSIG on the SOAP header | ✅ done (Ed25519 XML-DSIG in `As4SecurityHandler::verifySignatureElement()`) |
| `As4DuplicateDetectorInterface` | `src/Invoice/As4/` | Idempotency check on `eb:MessageId` | ✅ done |
| `As4DuplicateDetector` | `src/Invoice/As4/` | Uses `As4MessageRepositoryInterface::findByMessageId()` | ✅ done |
| `As4ReceiptGeneratorInterface` | `src/Invoice/As4/` | Build an ebMS3 `eb:Receipt` signal SOAP response | ✅ done |
| `As4ReceiptGenerator` | `src/Invoice/As4/` | SHA-256 NRR digest embedded in `eb:Receipt` via `As4MessageBuilder` | ✅ done |
| `As4ReceiveController` | `src/Invoice/As4/` | `POST /as4/receive` — Receiver → DuplicateDetector → store → ReceiptGenerator | ✅ done |
| `As4UserMessageHandlerInterface` | `src/Invoice/As4/` | Orchestrate duplicate check, save, payload dispatch, receipt for a UserMessage | ✅ done |
| `As4UserMessageHandlerService` | `src/Invoice/As4/` | Concrete handler; calls `As4PayloadHandlerInterface` for each payload | ✅ done |
| `As4PayloadHandlerInterface` | `src/Invoice/As4/` | Integration seam for UBL invoice import; `NullAs4PayloadHandler` is the default | ✅ done (null impl; real importer TBD) |

### Inbound processing flow

```
POST /as4/receive
    ↓
As4Receiver::parse($rawBody, $contentType)
    → As4InboundMessage {envelope: DOMDocument, parts: As4MimePart[]}
    ↓
As4SignatureVerifier::verify($envelope)                 → throws if invalid
    ↓
As4DuplicateDetector::isDuplicate($messageId)           → if true: return cached Receipt
    ↓
As4PayloadHandlerInterface::handle($payloadXml, $senderPartyId, $action)
    → NullAs4PayloadHandler (default) or real UBL importer when registered
    ↓
As4ReceiptGenerator::generate($inboundMessageId)
    → DOMDocument (SOAP envelope containing eb:Receipt)
    ↓
HTTP 200  Content-Type: application/soap+xml
```

### Bilateral setup — localhost ↔ yii3i.online

No Peppol PKI is required. Agreement between the two parties covers:

- Shared service identifier strings (`eb:Service`, `eb:Action`)
- Self-signed or Let's Encrypt TLS on both endpoints
- Static endpoint URL in each party's config (no SMP lookup needed)
- Optional shared secret for HMAC-based message authentication (simpler than WS-Security XML-DSIG in dev)

**Why bilateral first:** rounds out the inbound pipeline with a real HTTP peer,
surfaces MIME boundary edge cases, and lets the receipt round-trip be tested
end-to-end. All of this carries forward to the Peppol 4-corner model — the
only deltas are certificate authority and dynamic endpoint discovery.

---

## Phase 2 — Peppol 4-Corner Model

The 4-corner model adds three requirements on top of the bilateral foundation.

| Requirement | Delta from bilateral | Status |
|---|---|---|
| SMP endpoint discovery | Replace static URL with `As4SmpQuery` + `As4SmpEndpoint` DNS→HTTP lookup | `As4SmpQuery` / `As4SmpEndpoint` built; see [Peppol SMP Lookup](PEPPOL_SMP_LOOKUP.md) |
| Peppol-issued AP certificate | Obtain from an approved CA (BusDox, OpenPeppol test PKI) | ⬜ todo |
| SML registration | Register `iso6523-actorid-upis::0088:…` in the Peppol SML DNS zone | ⬜ todo |
| OpenPeppol conformance (EFTIA) | Pass EFTIA interoperability test suite | ⬜ todo |
| Peppol BIS Billing 3.0 validation | Pre-dispatch Schematron check | Done — see [Peppol Schematron Validator](PEPPOL_SCHEMATRON_VALIDATOR_ROUTE1.md) |
| UBL 2.4 generation | Compliant document build | Done |

### Corner roles

```
Corner 1 (Seller)  ──→  Corner 2 (Seller AP / this repo)
                                      ↓  AS4 + WS-Security + Peppol PKI
                         Corner 3 (Buyer AP)  ──→  Corner 4 (Buyer)
```

Corner 2 and Corner 3 are both Peppol Access Points. The bilateral phase exercises
the Corner 2 → Corner 3 leg without Peppol-issued certificates and without SML
registration. Everything else (SOAP envelope structure, ebMS3 signal handling, retry
policy, receipt storage) is identical.

---

## Deferred Work

| Item | Note | Status |
|---|---|---|
| DI container wiring — all interfaces | `config/common/di/as4.php` | ✅ done |
| `As4ErrorSignalTest`, `As4ReceiptSignalTest` | Value object tests | ✅ done |
| `As4MimePartTest`, `As4SmpEndpointTest`, `As4SmpQueryTest` | Value object tests | ✅ done |
| `As4DispatchRequestTest`, `As4DispatchResultTest` | Value object tests | ✅ done |
| `As4UserMessageHandlerServiceTest` | 8 tests: duplicate/new-message branches, payload handler args | ✅ done |
| `CycleOrmAs4MessageRepositoryTest` | `claimForRetry()` SQL CAS unit tests + `save()` delegation; find*() deferred to a future integration suite (needs live ORM) | ✅ done (10 tests) |
| Real `As4PayloadHandlerInterface` implementation | UBL XML → invoice records | ✅ done (`As4InvoiceImportService`; `UblXmlParser` uses `Schema` namespace constants; 32 tests) |
| `As4SecurityHandlerTest` | Sign+verify round-trip with pre-generated Ed25519 fixtures | ✅ done (10 tests; C14N orphan bug in `signMessage()` fixed) |

---

## Update Log

| Date | Change |
|---|---|
| June 2026 | Initial roadmap created; outbound AS4 stack complete on `as4` branch |
| June 2026 | Inbound pipeline complete: `As4InboundMessage`, `As4ParseException`, `As4ReceiptGenerator`, `As4DuplicateDetector`, `As4ReceiveController`; `POST /as4/receive` route wired; DI config in `config/common/di/as4.php`; `As4MessageState::received` added; `As4Message::fromInbound()` factory added |
| June 2026 | Inbound pipeline tests complete: `As4ReceiveControllerTest` (12 tests), `As4DuplicateDetectorTest` (3 tests), `As4ReceiptGeneratorTest` (7 tests); all Psalm errorLevel 1 clean |
| June 2026 | Ed25519 XML-DSIG signature verification implemented in `As4SecurityHandler::verifySignatureElement()`; `canonicalizeXml()` fixed to use Exclusive C14N |
| June 2026 | `As4PayloadHandlerInterface` + `NullAs4PayloadHandler` + `As4UserMessageHandlerService` added; controller S107-compliant (6 params); DI config updated; 348 tests all pass |
| June 2026 | `As4SecurityHandlerTest` (10 tests): C14N orphan bug fixed in `signMessage()` — `DOMNode::C14N()` returns empty string for nodes not yet in the document tree; fix: append to tree before canonicalising |
| June 2026 | `As4InvoiceImportService` + `UblXmlParser` added; `Schema` namespace URI constants centralised; `ClientPeppolRepository::findByEndpointId()` added; DI wired to real handler; 32 tests |
| June 2026 | `CycleOrmAs4MessageRepositoryTest` (10 tests): `claimForRetry()` SQL CAS logic, `isPersisted()` guard, `save()` delegation to `EntityWriter`; all deferred work complete |
