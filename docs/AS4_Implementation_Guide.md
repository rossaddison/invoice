# AS4 Implementation Guide for eDelivery 2.0

Complete XML and PHP implementation of eDelivery AS4 2.0 specification.

## Overview

This package provides a full AS4 Common Profile implementation for Peppol e-invoicing:
- **SOAP 1.2** envelope structure
- **ebMS3** headers (UserMessage, SignalMessage)
- **WS-Security 1.1.1** with Ed25519 signing and X25519 encryption
- **HKDF** key derivation per RFC 9231
- **Profile Enhancements**: Four Corner Topology, Dynamic Sender/Receiver, Pull, Large Message Splitting

**Reference:** https://ec.europa.eu/digital-building-blocks/sites/spaces/DIGITAL/pages/845480153/eDelivery+AS4+-+2.0

---

## Files Overview

### PHP Classes

#### `As4Constants.php`
Comprehensive namespace URIs and algorithm identifiers per specification.

**Key constants:**
- **Namespaces**: SOAP, ebMS3, WSS, XML signatures/encryption
- **MEPs**: One-Way, Two-Way, Pull modes
- **Algorithms**: Ed25519 signing, X25519 key agreement, AES-128-GCM
- **Error codes**: EBMS:0202, EBMS:0301, EBMS:0303, etc.
- **Party ID types**: GLN (0088), DUNS (0060), VAT (0007)

**Usage:**
```php
use Invoice\As4\As4Constants;

echo As4Constants::SIGNATURE_ALGORITHM;    // Ed25519
echo As4Constants::ENCRYPTION_ALGORITHM;   // AES-128-GCM
echo As4Constants::MEP_ONE_WAY;            // Peppol standard MEP
```

#### `As4MessageBuilder.php`
Constructs SOAP 1.2 + ebMS3 messages programmatically.

**Methods:**
- `addUserMessage()` - Add business message with metadata
- `addPayloadInfo()` - Link MIME payload attachments
- `addSignalMessage()` - Add receipt or error response
- `addTimestamp()` - WS-Security timestamp
- `addBinarySecurityToken()` - Include X.509 certificate

**Example:**
```php
$builder = new As4MessageBuilder();

$userMsg = $builder->addUserMessage(
    messageId: 'uuid-1234@seller.com',
    conversationId: 'conv-001',
    service: 'urn:service:invoice:transmission',
    action: 'SendInvoice',
    senderPartyId: '5412345000016',
    senderRole: 'Seller',
    receiverPartyId: '5412345000023',
    receiverRole: 'Buyer'
);

// Add MIME payload references
$builder->addPayloadInfo($userMsg, [
    [
        'contentId' => 'invoice-001@seller.com',
        'mimeType' => 'application/xml',
        'charset' => 'utf-8',
        'compressed' => true,  // Gzip applied
    ],
]);

$xml = $builder->getXml();
```

#### `PMode.php`
Processing Mode configuration container per section 3.5.

**Properties:**
- **Initiator/Responder**: Party identifiers, endpoints, roles
- **Business Info**: Service, Action, MEP/MEP binding
- **Security**: Algorithms, certificates, TLS version
- **Receipts**: Non-repudiation, reply pattern
- **Reliability**: Retry count, interval, duplicate detection
- **Compression**: Gzip enabled/disabled
- **Four Corner**: Profile enhancement flag

**Example:**
```php
$pmode = new PMode(
    initiatorParty: '5412345000016',    // Seller GLN
    responderParty: '5412345000023',    // Buyer GLN
    responderProtocolAddress: 'https://buyer-as4.example.com/as4',
    service: 'urn:service:invoice:transmission',
    action: 'SendInvoice'
);

$pmode
    ->setInitiatorRole('Seller')
    ->setResponderRole('Buyer')
    ->setMep(As4Constants::MEP_ONE_WAY)
    ->setSignCertificate($sellerCert)
    ->setEncryptCertificate($buyerCert)
    ->setMaxRetries(3)
    ->setRetryIntervalSeconds(300);

$config = $pmode->toArray();  // Serialize for storage
```

---

### XML Templates

#### `AS4_UserMessage_Example.xml`
Complete **ebMS3 UserMessage** structure.

**Sections:**
- `MessageInfo`: Timestamp, MessageId, RefToMessageId (for responses)
- `PartyInfo`: From/To with ISO 6523 GLN identifiers and roles
- `CollaborationInfo`: ConversationId, Service, Action
- `MessageProperties`: Metadata (Four Corner Topology properties)
- `PayloadInfo`: References to MIME attachments with compression metadata

**Key points:**
- SOAP Body **must be empty** per section 3.2.3
- All payload content is in MIME parts
- MessageId must be RFC 2822 compliant
- ConversationId is mandatory per section 3.2.2

---

#### `AS4_Receipt_Example.xml`
**Non-Repudiation Receipt** signal message per section 3.3.2.

**Structure:**
- Receiver sends to confirm successful processing
- Contains digest of original message
- Signed with receiver's certificate for non-repudiation
- References original via `RefToMessageId`

**Error codes for missing receipt:**
- `EBMS:0301` - MissingReceipt (Reception Awareness error)
- Triggers retry loop per section 3.3.2

---

#### `AS4_Error_Example.xml`
**Error Signal Message** per section 3.2.5.

**Common error codes:**
- `EBMS:0202` - DeliveryFailure
- `EBMS:0301` - MissingReceipt
- `EBMS:0303` - DecompressionFailure

**Attributes:**
- `category`: Communication | Processing | Unpackaging
- `refToMessageInError`: References failed message
- `errorCode`: Code from spec

---

#### `AS4_WSSecurity_Header_Example.xml`
Complete **WS-Security 1.1.1** header with signing + encryption.

**Signing (Section 3.2.6.2.2):**
1. Ed25519 signature algorithm (mandatory per 2.0)
2. SHA-256 digest for message parts
3. Covers: empty SOAP body + eb:Messaging header + MIME parts
4. Uses `ds:CanonicalizationMethod` for predictable XML canonicalization

**Encryption (Section 3.2.6.2.3):**
1. X25519 ephemeral-static key agreement
2. HKDF key derivation with SHA-256 PRF
3. AES-128-GCM content encryption
4. Only payload MIME parts encrypted (eb:Messaging header not encrypted)
5. Originator ephemeral key in DER format per RFC 8410

**Sequence:**
```
1. Sign first:       Compress → Sign (covers original + headers)
2. Then encrypt:     Encrypt payload parts (not headers)
3. Both mandatory:   No option to skip either
```

---

### Configuration Examples

#### `AS4_PMode_Examples.php`
Five real-world P-Mode configurations:

1. **Invoice Transmission** - Standard One-Way/Push
2. **Two-Way Request-Response** - Purchase order + response
3. **Four Corner Topology** - With intermediaries (C2→C3)
4. **Pull Feature** - For unreliable receivers
5. **Dynamic Sender** - With SMP discovery

Each example shows:
- Party identification (GLN numbers)
- Service and action values
- Certificate configuration
- Compression settings
- Retry and reliability parameters

---

## Integration Checklist

### Phase 1: Message Construction
- [ ] Import `As4MessageBuilder` class
- [ ] Generate SOAP 1.2 envelope with ebMS3 headers
- [ ] Set MessageId (RFC 2822 format with UUID)
- [ ] Add PartyInfo with ISO 6523 type attributes
- [ ] Reference MIME payload parts via ContentId
- [ ] Add compression metadata for gzip payloads

### Phase 2: Security Headers
- [ ] Instantiate `PMode` with certificates
- [ ] Load signing certificate (seller's Ed25519/ECDSA)
- [ ] Load encryption certificate (buyer's public key)
- [ ] Create WS-Security header structure
- [ ] Add BinarySecurityToken (X.509 certificate)
- [ ] Implement XML signature with Ed25519
- [ ] Implement XML encryption with X25519/HKDF

### Phase 3: Reliable Messaging
- [ ] Store message in database for retry tracking
- [ ] Implement reception awareness retry loop
  - Retry limit: per PMode
  - Interval: per PMode (e.g., 5 minutes)
  - Max attempts: typically 3
- [ ] Handle MissingReceipt error (EBMS:0301)
- [ ] Implement duplicate detection before re-attempting

### Phase 4: Signal Handling
- [ ] Accept Receipt signals (non-repudiation)
- [ ] Accept Error signals (delivery failures)
- [ ] Match signals to original message via RefToMessageId
- [ ] Update message state (received, failed, etc.)
- [ ] Notify producer/consumer of outcomes

### Phase 5: Oxalis Integration
- [ ] Configure Oxalis REST endpoint (HTTP POST)
- [ ] Wrap AS4 message + MIME parts for transmission
- [ ] Oxalis signs/encrypts with AP certificates
- [ ] Oxalis performs SMP lookup for recipient
- [ ] Oxalis transmits AS4 message via HTTPS
- [ ] Receive inbound messages via webhook/callback

---

## Security Best Practices

### Certificate Management
- **Signing certificates**: Ed25519 (2024+) or ECDSA P-256/P-384/P-521
- **Encryption keys**: X25519 (ephemeral) or ECDH-ES with curves above
- **Key lengths**:
  - RSA: >3000 bits (deprecated, use ECC)
  - ECDSA: >250 bits
  - Ed25519: 256 bits (modern standard)

### TLS Requirements (Section 3.2.6.1)
- **Minimum**: TLS 1.2 (RFC 5246)
- **Recommended**: TLS 1.3 (RFC 8446)
- **Cipher suites**: Follow NIST 800-52r2 or BSI TR-02102-2
- **Curves**: secp256r1, secp384r1, secp521r1, x25519, x448

### Non-Repudiation
- **Signing**: Provides proof sender created the message
- **Receipt**: Provides proof receiver successfully processed it
- **Retention**: Store messages, receipts, and certificates per business/legal policy
- **Dispute resolution**: Requires complete audit trail

---

## Common Issues & Solutions

### Issue: "Message must have unique MessageId"
**Solution**: Generate RFC 2822 compliant UUID per section 3.2.2:
```php
$messageId = sprintf(
    '%s@%s',
    str_replace('-', '', Uuid::uuid4()->toString()),
    parse_url($config['endpoint'], PHP_URL_HOST)
);
```

### Issue: "SOAP Body must be empty"
**Solution**: Never put payload XML in `<soap:Body>`. All content → MIME parts.

### Issue: "Missing encryption certificate"
**Solution**: Ensure receiver's public encryption certificate is loaded in PMode.
For dynamic discovery, query SMP before creating message.

### Issue: "Receipt not received after 15 minutes"
**Solution**: Check retry configuration:
```php
$pmode->setRetryIntervalSeconds(300);    // 5 minutes
$pmode->setMaxRetries(3);                 // Try 3 times
// Total wait: ~15 minutes
```

### Issue: "Signature verification failed"
**Solution**: Ensure signing includes:
1. Empty SOAP body (references `#body-id`)
2. eb:Messaging header (references `#messaging-id`)
3. All MIME parts (references `cid:...`)

---

## P-Mode Parameter Reference (Section 3.5)

| Parameter | One-Way/Push | Two-Way/Push-and-Push | Pull |
|-----------|--------------|----------------------|------|
| **MEP** | oneWay | twoWay | oneWay |
| **MEPBinding** | push | pushAndPush | pull |
| **Compression** | RECOMMENDED: gzip | RECOMMENDED: gzip | RECOMMENDED: gzip |
| **Sign** | REQUIRED (Ed25519) | REQUIRED | REQUIRED |
| **Encrypt** | REQUIRED (AES-128-GCM) | REQUIRED | REQUIRED |
| **SendReceipt** | true (sync) | true (sync leg 1) | true (async callback) |
| **ReportAsResponse** | true | true | false |
| **Reception Awareness** | REQUIRED | REQUIRED | REQUIRED |
| **Duplicate Detection** | REQUIRED | REQUIRED | REQUIRED |
| **ReplyPattern** | Response | Response (leg 1) | Callback |

---

## Four Corner Topology (Section 4.1)

### Architecture
```
C1 (Seller)
    ↓
C2 (Seller AP) → AS4 HTTPS → C3 (Buyer AP)
    ↑                             ↓
    ←────────── Receipt ←─── C4 (Buyer)
```

### Message Properties (Section 4.1.2)
```xml
<eb:MessageProperties>
  <eb:Property name="originalSender">5412345000016</eb:Property>
  <eb:Property name="finalRecipient">5412345000023</eb:Property>
  <eb:Property name="trackingIdentifier">track-001</eb:Property>
</eb:MessageProperties>
```

### eb:From/eb:To
- Identify **access points** (C2, C3), not original parties
- C1 and C4 are in message properties only
- Allows routing without modifying headers

---

## Dynamic Sender (Section 4.3)

For sender not knowing receiver in advance:

1. **Create P-Mode template** without responder details
2. **Query SMP** with `finalRecipient` party ID
3. **Discover**:
   - Receiver endpoint URL
   - Receiver encryption certificate
   - Receiver AS4 profile capabilities
4. **Complete P-Mode** with discovered values
5. **Send message**

---

## Testing Checklist

- [ ] MessageId is unique RFC 2822 format
- [ ] ConversationId is consistent within flow
- [ ] PartyId has type attribute (`urn:oasis:names:tc:ebcore:partyid-type:iso6523:0088`)
- [ ] SOAP Body is empty
- [ ] All payload parts referenced in PayloadInfo
- [ ] Signature covers body + header + all MIME parts
- [ ] Encryption applied only to payload parts (not headers)
- [ ] Receipt received within retry interval
- [ ] Error handling retries after failure
- [ ] Duplicate detection blocks re-sent messages
- [ ] Four Corner properties included (if enabled)
- [ ] TLS certificate chain validates
- [ ] Compression ratio > 0 for XML payloads

---

## References

- **Specification**: eDelivery AS4 2.0 (Dec 2024)
- **OASIS ebMS3**: Part 1 Core, Part 2 Advanced Features
- **WS-Security**: Version 1.1.1
- **XML Signature**: W3C XMLDSIG 1.1
- **XML Encryption**: W3C XMLENC 1.1
- **HKDF**: RFC 9231 (XML Security URIs)
- **Ed25519/X25519**: RFC 8410, RFC 9231bis
- **Peppol**: SBDH, UBL 2.4 document standards

---

## Files Generated

```
src/Invoice/As4/
├── As4Constants.php          # Namespace URIs and algorithm identifiers
├── As4MessageBuilder.php     # SOAP/ebMS3 message construction
└── PMode.php                 # Processing mode configuration

docs/
├── AS4_UserMessage_Example.xml        # ebMS3 UserMessage structure
├── AS4_Receipt_Example.xml            # Non-repudiation receipt
├── AS4_Error_Example.xml              # Error signal message
├── AS4_WSSecurity_Header_Example.xml  # Signing + encryption
├── AS4_PMode_Examples.php             # Five config scenarios
└── AS4_Implementation_Guide.md        # This file
```
