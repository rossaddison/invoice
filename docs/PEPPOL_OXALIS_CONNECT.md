# Connecting to a Real Oxalis Access Point

Companion to [PEPPOL_SEND_OXALIS.md](PEPPOL_SEND_OXALIS.md) (Phase A WireMock mock setup).  
This document covers Phase B: switching `PeppolSendService` from the WireMock mock to a real Oxalis AS4 gateway.

---

## What changed in `PeppolSendService` (May 2026)

| Aspect | Before (mock) | After (real Oxalis) |
|--------|---------------|---------------------|
| Body format | JSON with base64-encoded XML | `multipart/form-data` — raw XML as `file` part |
| Participant ID | stored short form `0088:123…` | prefixed: `iso6523-actorid-upis::0088:123…` |
| Process ID | stored short form `urn:fdc:…` | prefixed: `cenbii-procid-ubl::urn:fdc:…` |
| Sender ID | not sent | `SenderId` part added when `PEPPOL_SENDER_ID` is set |
| HTTP errors | not checked | HTTP 4xx/5xx sets `PeppolMessage.status = FAILED` |
| Response field | `messageId` only | `messageId` OR `instanceIdentifier` (version-dependent) |
| Library | `StreamFactoryInterface` | `GuzzleHttp\Psr7\MultipartStream` (already in project) |

Prefix helpers are idempotent — if a value already starts with `iso6523-actorid-upis::` it is not doubled.

---

## Multipart fields sent to Oxalis

```
POST /outbound/send
Content-Type: multipart/form-data; boundary=<generated>

--<boundary>
Content-Disposition: form-data; name="file"; filename="invoice.xml"
Content-Type: application/xml

<?xml version="1.0"?>…UBL XML…
--<boundary>
Content-Disposition: form-data; name="RecipientId"

iso6523-actorid-upis::0088:1234567890123
--<boundary>
Content-Disposition: form-data; name="DocumentTypeId"

urn:oasis:names:specification:ubl:schema:xsd:Invoice-2
--<boundary>
Content-Disposition: form-data; name="ProcessTypeId"

cenbii-procid-ubl::urn:fdc:peppol.eu:2017:poacc:billing:01:1.0
--<boundary>
Content-Disposition: form-data; name="SenderId"

iso6523-actorid-upis::0088:youriln
--<boundary>--
```

---

## `.env` variables

```ini
# Phase A (WireMock mock — default)
OXALIS_BASE_URL=http://localhost:8181

# Phase B (real Oxalis)
OXALIS_BASE_URL=http://your-oxalis-host:8080

# Your Peppol participant ID.
# Leave blank to omit SenderId — Oxalis may derive it from your certificate.
PEPPOL_SENDER_ID=0088:1234567890123
```

Both are wired via `config/common/di/peppol.php` and injected into `PeppolSendService` at boot time. No code change is needed to switch phases — only the `.env` values.

---

## DocumentTypeId caveat

`PeppolMessage` stores the short UBL namespace URN:

```
urn:oasis:names:specification:ubl:schema:xsd:Invoice-2
```

Some Oxalis versions accept this. Others require the full busdox format:

```
busdox-docid-qns::urn:oasis:names:specification:ubl:schema:xsd:Invoice-2::Invoice##urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0::2.1
```

If Oxalis returns HTTP 400 on first send, inspect the response body in `peppol_message.error_message` — it will contain the Oxalis rejection reason. Add a `toOxalisDocumentTypeId()` private method to `PeppolSendService` if a full mapping is needed.

---

## Inbound delivery callback

When the recipient's access point acknowledges receipt, Oxalis calls:

```
POST /peppol/inbound/delivery
Content-Type: application/json
X-Peppol-Callback-Secret: <PEPPOL_INBOUND_SECRET>

{ "messageId": "<the-id-returned-by-/outbound/send>" }
```

`PeppolInboundController::delivery()` handles this: looks up the `PeppolMessage` by `messageId`, sets `status = DELIVERED` and `delivered_at = now()`, and saves. Configure the callback URL in your `oxalis.conf`:

```
oxalis.as4.receipt.callback.url = https://yourdomain.com/peppol/inbound/delivery?secret=<PEPPOL_INBOUND_SECRET>
```

The route has no login (Oxalis calls it with no session), so it is secured by a **shared secret** (`PeppolInboundGuard`):

- Set `PEPPOL_INBOUND_SECRET` in `.env` (32 or more random characters).
- The caller sends it as the `X-Peppol-Callback-Secret` header or, if Oxalis can only be given a URL, as `?secret=`. A header is preferred: a URL ends up in access logs.
- **With no secret set every call is refused (403)**, so deliveries are not marked `DELIVERED` until it is set. Before this, anyone who knew a `messageId` could mark a message delivered.

---

## Phase B checklist

- [ ] Oxalis deployed and reachable from the PHP host
- [ ] Peppol certificate installed in Oxalis keystore (`oxalis-conf/` — never commit)
- [ ] `OXALIS_BASE_URL` set to real Oxalis base URL in `.env`
- [ ] `PEPPOL_SENDER_ID` set to your Peppol participant ID in `.env`
- [ ] `PEPPOL_INBOUND_SECRET` set in `.env`
- [ ] `oxalis.as4.receipt.callback.url` pointing to `/peppol/inbound/delivery` with the same secret
- [ ] SMP registration complete (your AP endpoint published in Peppol directory)
- [ ] First send attempted — inspect `peppol_message` row for `SENT` status and non-empty `message_id`
- [ ] Delivery confirmation received — `peppol_message.status` transitions to `DELIVERED`

---

## Related docs

- [PEPPOL_SEND_OXALIS.md](PEPPOL_SEND_OXALIS.md) — Phase A: WireMock mock, `PeppolMessage` entity, route, DI setup
- [OXALIS_INTEGRATION.md](OXALIS_INTEGRATION.md) — phased integration plan, cost comparison, SMP/certification
- [PEPPOL_ACCESS_POINT_PHP_GUIDE.md](PEPPOL_ACCESS_POINT_PHP_GUIDE.md) — building a PHP access point from scratch
