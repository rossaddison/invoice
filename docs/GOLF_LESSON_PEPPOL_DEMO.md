# Golf Lessons & the Peppol Network — A Treasurer's Guide

> *You don't need to know what an Access Point is to benefit from one.*
> *But if you've ever wondered why your pro still emails you a PDF invoice,*
> *this guide is for you.*

---

## 1. The Situation

You are the treasurer of a golf club. Every month your club engages PGA
professionals to run group lessons, junior academies, and ladies' coaching
sessions. Each pro sends you an invoice. You type the figures into your
accounts package. The pro chases you when it's late. You chase your bank
when you've already paid. Sound familiar?

Now imagine the pro's system and your club's system talked to each other
directly — securely, automatically, with a digital signature and a
delivery receipt — the same way your club submits green-fee returns to
the county union. No email. No PDF. No re-keying.

That is Peppol.

---

## 2. The Problem Today

```
Pro finishes lessons
    → opens Word, creates invoice PDF
        → emails it to treasurer@golfclub.co.uk
            → treasurer downloads PDF
                → types figures into Xero / Sage / spreadsheet
                    → pays
                        → emails remittance advice
                            → pro wonders if it arrived
```

Every step is a chance for error, delay, or fraud (a spoofed invoice PDF
is trivially easy to produce). There is no proof of delivery. There is no
standard format — every pro lays out their invoice differently.

---

## 3. Enter Peppol — the Golf Analogy

Think of **Peppol** as the **England Golf network** for business documents.

England Golf maintains a central register of affiliated clubs (the **SML** —
Service Metadata Locator). Each affiliated club is listed in a directory
that says which services they support (the **SMP** — Service Metadata
Publisher). When a county union wants to send an official document to your
club, it looks up your club in that directory, finds your official "inbox"
address, and delivers the document there — securely, with a receipt.

Peppol does exactly the same thing for invoices, purchase orders, and
credit notes between businesses.

| Golf world | Peppol world |
|---|---|
| England Golf central register | SML — Service Metadata Locator |
| Club directory listing | SMP — Service Metadata Publisher |
| Club's official mailroom | Access Point (AP) |
| Club membership number | Peppol Participant ID (e.g. `0088:1234567890123`) |
| Official scorecard format | UBL 2.4 XML document |
| Recorded-delivery envelope | AS4 message (digitally signed) |
| Postmark + signature on receipt | AS4 Receipt signal |
| County handicap administrator | Peppol Authority (OpenPeppol) |

---

## 4. The Four Corners of a Peppol Transaction

Every Peppol document travels through four corners. Here is the golf lesson
version:

```
┌──────────────────┐   AS4 message    ┌──────────────────┐
│  Corner 1        │ ─────────────── ▶│  Corner 2        │
│  PGA Pro         │                  │  Pro's Access    │
│  (Yii3-i)        │                  │  Point           │
│  yii3i.online    │                  │  (same server    │
│                  │◀─────────────── ─│   in our demo)   │
└──────────────────┘   Receipt        └──────────────────┘
                                               │  Peppol
                                               │  network
                                               ▼
┌──────────────────┐   AS4 message    ┌──────────────────┐
│  Corner 4        │◀─────────────── ─│  Corner 3        │
│  Golf Club       │                  │  Club's Access   │
│  Finance System  │                  │  Point           │
│  (treasurer's    │ ─────────────── ▶│  (Yii3-i on      │
│   inbox)         │   Receipt        │   localhost)     │
└──────────────────┘                  └──────────────────┘
```

In the **bilateral demo** between `localhost` and `yii3i.online`, corners 2
and 3 are the same two Yii3-i installations talking directly to each other —
no Peppol network in the middle, no PKI certificates from OpenPeppol. This
is how you test the full pipeline before going live.

---

## 5. The Document Journey: Booking a Golf Lesson

### Step A — The Club Raises a Purchase Order

The club treasurer opens Yii3-i, creates a **Quote** for 10 group lessons
at £45 per person per session, and converts it to a **Sales Order** (the
club's Purchase Order). Yii3-i serialises this as a **UBL 2.4 Order
document** and sends it via AS4 to the pro's Access Point.

```
Treasurer clicks "Send PO via Peppol"
    → Yii3-i builds UBL Order XML
        → Signs with club's AS4 certificate (WS-Security)
            → Wraps in SOAP envelope (ebMS3)
                → HTTP POST to pro's /as4/receive
                    → Pro's Yii3-i logs the message, creates draft invoice
```

The pro receives a notification: *"New booking request from Fairway Golf
Club — 10 lessons, 6 Saturdays in August."*

### Step B — The Pro Accepts (Order Response)

The pro reviews the booking in Yii3-i and clicks **Accept**. Yii3-i sends
a **UBL Order Response** back to the club. The club's system marks the
Sales Order as confirmed. No phone call required.

### Step C — Lessons Happen

Golf is played. Lessons are delivered. Rain may or may not cooperate.

### Step D — The Pro Sends the Invoice

After the final session the pro clicks **Send Invoice via Peppol** on the
invoice view in Yii3-i. The system:

1. Generates a **UBL 2.4 Invoice** (the same XML format used across the
   entire Peppol network — readable by Xero, Sage, SAP, and thousands of
   others)
2. Looks up the club's Access Point endpoint via SMP (or in the bilateral
   demo, reads `AS4_PEER_ENDPOINT` directly from `.env`)
3. Signs the SOAP envelope with the pro's private key (`WsSecuritySigner`)
4. Posts it to the club's `/as4/receive` endpoint
5. Waits for an **AS4 Receipt signal** — cryptographic proof the message
   arrived

```php
// What php yii as4/test-send does in the demo:
php yii as4/test-send \
    --receiver-id=bilateral:golfclub \
    --endpoint=https://localhost/as4/receive \
    --payload=path/to/lesson-invoice.xml
```

### Step E — The Club Receives & Pays

The club's Yii3-i (or any Peppol-connected accounts package) receives the
invoice automatically. The treasurer sees it in their inbox — structured
data, not a PDF to squint at. One click to approve. Payment goes out.
A **Receipt Advice** document travels back to the pro confirming payment.

The pro's dashboard shows:

```
Invoice #1042 — Fairway Golf Club
State:  ✅ delivered
Receipt: 2026-08-15T14:32:01Z
```

No chasing. No "did you get my email?"

---

## 6. What Yii3-i Does in This Picture

Yii3-i **is** the Access Point. It handles both sides of the exchange:

### Outbound (Pro → Club)

| Component | Role |
|---|---|
| `InvController` → `As4MessageDispatcher` | Triggered when pro clicks "Send via Peppol" |
| `SoapEnvelopeBuilder` | Wraps the UBL XML in an ebMS3 SOAP envelope |
| `WsSecuritySigner` | Signs with the pro's private key (X.509 certificate) |
| `As4HttpClient` | HTTP POST to the club's endpoint |
| `As4ReceiptParser` | Reads the synchronous receipt signal if returned |
| `CycleOrmAs4MessageRepository` | Persists the message + state for the dashboard |
| `As4RetryEngine` | Retries on failure — cron every 5 minutes |

### Inbound (Club → Pro)

| Component | Role |
|---|---|
| `As4ReceiveController` → `/as4/receive` | Public endpoint; accepts POST from any AP |
| `As4Receiver` | Parses the MIME multipart / SOAP envelope |
| `As4DuplicateDetector` | Rejects replayed messages |
| `As4ReceiptGenerator` | Returns a signed receipt to the sender |
| `As4InvoiceImportService` | Converts the incoming UBL payload to a draft invoice |

### The Dashboard

Visit `/as4/messages` to see every message in flight:

| State | Meaning | Badge |
|---|---|---|
| `pending` | Queued, not yet sent | Grey |
| `sent` | Delivered, awaiting async receipt | Blue |
| `receiptReceived` | Synchronous receipt confirmed | Teal |
| `delivered` | Fully acknowledged end-to-end | Green |
| `failed` | All retries exhausted | Red |
| `duplicate` | Rejected — already received | Yellow |
| `received` | Inbound message logged | Purple |

---

## 7. The Bilateral Demo: `localhost` ↔ `yii3i.online`

This is how you test the full pipeline without Peppol network membership
or OpenPeppol certificates. Two Yii3-i installations talk directly.

### On `localhost` (the Golf Club)

```bash
# Generate a self-signed cert for the club
openssl genrsa -out /etc/as4/signing-key.pem 4096
openssl req -new -x509 -key /etc/as4/signing-key.pem \
  -out /etc/as4/signing-cert.pem -days 365 \
  -subj "/C=GB/O=Fairway Golf Club/CN=bilateral-club"
```

`.env` on `localhost`:
```
AS4_SENDER_PARTY_ID=bilateral:golfclub
AS4_PEER_PARTY_ID=bilateral:pro
AS4_PEER_ENDPOINT=https://yii3i.online/as4/receive
AS4_SIGNING_KEY_PATH=/etc/as4/signing-key.pem
AS4_SIGNING_CERT_PATH=/etc/as4/signing-cert.pem
AS4_PEER_CERT_PATH=/etc/as4/peer-cert.pem   # pro's cert, copied over
```

### On `yii3i.online` (the PGA Pro)

```bash
# Generate a self-signed cert for the pro
openssl genrsa -out /etc/as4/signing-key.pem 4096
openssl req -new -x509 -key /etc/as4/signing-key.pem \
  -out /etc/as4/signing-cert.pem -days 365 \
  -subj "/C=GB/O=Ross Addison PGA Pro/CN=bilateral-pro"
```

`.env` on `yii3i.online`:
```
AS4_SENDER_PARTY_ID=bilateral:pro
AS4_PEER_PARTY_ID=bilateral:golfclub
AS4_PEER_ENDPOINT=https://<ngrok-id>.ngrok-free.app/as4/receive
AS4_SIGNING_KEY_PATH=/etc/as4/signing-key.pem
AS4_SIGNING_CERT_PATH=/etc/as4/signing-cert.pem
AS4_PEER_CERT_PATH=/etc/as4/peer-cert.pem   # club's cert, copied over
```

> **Why ngrok?** Your localhost isn't publicly reachable from yii3i.online.
> `ngrok http 80` gives you a temporary public URL that tunnels to your
> local Yii3-i. For a permanent bilateral setup, both nodes need public URLs.

### Fire the Test

```bash
# From yii3i.online — pro sends a lesson invoice ping to the club
php yii as4/test-send \
    --receiver-id=bilateral:golfclub \
    --endpoint=https://<ngrok-id>.ngrok-free.app/as4/receive

# Check the club received it (on localhost)
php yii as4/status
# → received: 1
```

---

## 8. Under the Hood (For the Curious)

A Peppol AS4 message is a **SOAP 1.2 envelope** inside a **MIME multipart
body**, posted over **HTTPS**. The envelope contains:

- **ebMS3 messaging header** — sender, receiver, document type, process ID,
  message ID, timestamp
- **WS-Security header** — XML digital signature over the message header and
  payload, using the sender's X.509 certificate
- **SOAP body** — references the payload attachment
- **MIME attachment** — the actual UBL 2.4 XML invoice

When the receiver's Access Point gets this:

1. It verifies the digital signature against the sender's certificate
2. It checks the message ID hasn't been seen before (duplicate detection)
3. It extracts and stores the UBL payload
4. It sends back a **Receipt signal** — a signed SOAP response that gives
   the sender cryptographic proof the message arrived intact

This is significantly more secure than an email with a PDF attachment, where
there is no proof of delivery, no tamper detection, and no standard format.

---

## 9. From Demo to Real Peppol — What Changes?

The bilateral demo swaps out exactly **one component**:

| Demo | Production |
|---|---|
| `StaticAs4SmpResolver` (reads `AS4_PEER_ENDPOINT` from `.env`) | `As4SmpResolver` (DNS → SMP → endpoint lookup) |
| Self-signed X.509 certificate | OpenPeppol-issued AP certificate |
| Any party ID string | Registered Peppol Participant ID (`0088:...`) |

Everything else — the envelope builder, the signer, the HTTP transport, the
retry engine, the dashboard, the receipt parser — is **identical**. The
bilateral demo is a genuine dress rehearsal.

See [OPENPEPPOL_CERTIFICATION_PREP.md](OPENPEPPOL_CERTIFICATION_PREP.md) for
the certification path and [PEPPOL_PKI_CERTIFICATE_REQUEST.md](PEPPOL_PKI_CERTIFICATE_REQUEST.md)
for the CSR template.

---

## 10. Glossary: Golf ↔ Peppol

| If you know golf... | ...you already understand Peppol |
|---|---|
| England Golf central register | SML (Service Metadata Locator) |
| Club directory / club finder | SMP (Service Metadata Publisher) |
| Club's official mailroom | Access Point (AP) |
| Club membership number | Peppol Participant ID |
| Official scorecard (R&A standard) | UBL 2.4 XML document |
| Recorded-delivery signed envelope | AS4 message (WS-Security signed) |
| Postmark proving it was delivered | AS4 Receipt signal |
| County handicap administrator | OpenPeppol Authority |
| Club-to-club handicap transfer form | UBL Order / Invoice / Credit Note |
| Junior's handicap index going live | SML registration (going live on Peppol) |
| County union rules you must follow | Peppol BIS Billing 3.0 specification |
| Playing off the wrong handicap | Sending a non-compliant UBL document |
| Signing your scorecard | Signing the SOAP envelope (WS-Security) |
| Club captain countersigning | Receipt signal from the receiving AP |

---

## Summary

A golf club treasurer does not need to know what an Access Point is.
They need to know that:

1. Their pro's software and their club's software can exchange booking
   confirmations and invoices **automatically** — no email, no PDF, no
   re-keying.

2. Every message is **signed and receipted** — there is cryptographic proof
   it arrived, untampered.

3. The format is **universal** — the same UBL 2.4 invoice the pro sends to
   the golf club can be sent to any Peppol-connected accounts package in
   the world.

4. Yii3-i is the Access Point for both of them — **the mailroom that speaks
   golf and speaks Peppol at the same time**.
