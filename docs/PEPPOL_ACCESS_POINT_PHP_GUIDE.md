# Building a PEPPOL Access Point in PHP

## Overview

Building a PEPPOL access point in PHP is entirely possible,
but it is a serious interoperability and security project rather than a
typical web API.

At a high level, a PEPPOL access point must implement:

1. AS4 messaging
2. PEPPOL certificate and security handling
3. SMP/SML participant discovery
4. XML validation and business rules
5. Reliable messaging and retries
6. PEPPOL operational compliance

A practical architecture in PHP usually combines:

- PHP for orchestration and business logic
- Existing AS4/message broker components where possible
- XML/XAdES libraries
- Queueing and persistence
- Containerized infrastructure

---

# High-Level Architecture

```text
                ┌────────────────────┐
                │ Customer ERP/API   │
                └─────────┬──────────┘
                          │ REST/API
                          ▼
                ┌────────────────────┐
                │ PHP Application    │
                │ Laravel/Symfony    │
                └─────────┬──────────┘
                          │
         ┌────────────────┼────────────────┐
         ▼                ▼                ▼
 ┌────────────┐   ┌──────────────┐  ┌─────────────┐
 │ XML Engine │   │ SMP Resolver │  │ Validation  │
 └────────────┘   └──────────────┘  └─────────────┘
         │
         ▼
 ┌────────────────────┐
 │ AS4 Messaging      │
 │ Signing/Encryption │
 └─────────┬──────────┘
           ▼
    PEPPOL Network
```

---

# Main Components

## 1. Participant Discovery (SMP/SML)

Before sending a document, you must determine:

- Whether the recipient exists
- Supported document types
- Recipient endpoint URL
- Recipient certificates

Typical flow:

```text
participant ID
    ↓
SML lookup
    ↓
SMP URL
    ↓
Service Metadata XML
    ↓
Endpoint + cert
```

### PHP Implementation

Recommended tools:

- Guzzle HTTP client
- XML parsing libraries
- DNS lookup support

Useful libraries:

- Guzzle
- Symfony XML tools

---

## 2. XML Document Handling

PEPPOL documents are XML-based UBL documents.

You need:

- XML generation
- XSD schema validation
- Schematron validation
- Business rule validation

### Important Standards

Common standards include:

- UBL 2.1
- PEPPOL BIS Billing 3.0
- EN16931

### Example Validation Code

```php
$dom = new DOMDocument();
$dom->loadXML($xml);

if (!$dom->schemaValidate('UBL-Invoice.xsd')) {
    throw new Exception('Invalid XML');
}
```

### Schematron Validation

You can:

- Use `XSLTProcessor`
- Invoke Saxon externally
- Run validation as a separate Java microservice

Many teams use Java-based validation services because PEPPOL validation tooling is more mature in Java.

---

## 3. AS4 Implementation (Most Difficult Part)

This is the hardest component.

PEPPOL uses:

- AS4
- ebMS3
- SOAP
- WS-Security
- XML Digital Signatures

This involves:

- SOAP envelopes
- MIME multipart packaging
- Message receipts
- Signed payloads
- Encrypted payloads
- Retries
- Non-repudiation

### Recommendation

Do not implement raw AS4 yourself from scratch unless you have deep XML security
expertise.

### Better Approach

Use an external AS4 gateway or embed an existing AS4 server.

Examples:

- Holodeck B2B
- Domibus
- Oxalis

Let PHP orchestrate around it.

---

# Recommended Production Architecture

```text
PHP API Platform
    ↓
Queue (RabbitMQ/SQS)
    ↓
AS4 Gateway (Domibus/Oxalis)
    ↓
PEPPOL Network
```

PHP handles:

- Customer APIs
- Onboarding
- Persistence
- Billing
- Validation
- Monitoring
- Retries
- Analytics

The AS4 gateway handles:

- Transport
- WS-Security
- Receipts
- Encryption
- Signing

This drastically reduces implementation risk.

---

## 4. Certificates and Trust

PEPPOL requires PKI certificates.

You must manage:

- Access point certificates
- TLS certificates
- Trust stores
- Certificate rollover

You will likely use:

- OpenSSL
- X509 handling

PHP provides reasonable OpenSSL support.

---

## 5. Message Lifecycle

You need reliable state handling.

Typical statuses:

```text
RECEIVED
VALIDATED
QUEUED
SENT
DELIVERED
FAILED
RETRYING
REJECTED
```

This should be persisted in PostgreSQL or MySQL.

Queues are essential.

Recommended options:

- RabbitMQ
- Redis queues
- AWS SQS

---

## 6. Inbound Processing

You must:

- Expose AS4 endpoints
- Receive messages
- Validate signatures
- Verify certificates
- Parse XML
- Validate payloads
- Route to customer systems

---

## 7. PEPPOL Compliance

To become production-certified:

- Pass conformance testing
- Complete interoperability testing
- Meet operational requirements
- Meet security requirements
- Implement audit logging

Important resources:

- OpenPeppol specifications
- PEPPOL transport infrastructure agreements

---

# Suggested PHP Stack

## Framework

Recommended:

- Symfony
- Laravel
- Yii3

### Symfony Advantages

- Strong XML tooling
- Enterprise middleware capabilities
- Long-running service support

### Laravel Advantages

- Faster API development
- Strong ecosystem
- Easier onboarding for developers

---

# Useful PHP Libraries

## XML

- sabre/xml
- laminas/laminas-xml

## SOAP

- Native SoapClient
- Laminas SOAP

## Security

Critical library:

- xmlseclibs

Used for:

- XML signatures
- Encryption
- WS-Security primitives

---

# Real-World Implementation Strategy

## Phase 1 — Minimal Access Point

Build:

- Outbound invoice sending only
- SMP lookup
- XML validation
- Single document type
- External AS4 gateway

This is realistic and achievable.

---

## Phase 2 — Production Readiness

Add:

- Retries
- Async queues
- Inbound support
- Monitoring
- Metrics
- Onboarding APIs
- Rate limiting
- Audit logging

---

## Phase 3 — Certification

Then implement:

- Interoperability testing
- PEPPOL authority onboarding
- Production certificates
- Operational support processes

---

# Biggest Engineering Challenges

## 1. XML Security

Canonicalization bugs and XML signature interoperability can consume significant engineering time.

---

## 2. Schematron Validation

PEPPOL validation rules are extensive and strict.

---

## 3. AS4 Interoperability

Different access points can behave differently.

Common edge cases:

- MIME formatting
- Receipt handling
- Clock skew
- Retry timing
- SOAP faults

---

## 4. Operational Reliability

You are effectively building financial infrastructure.

Requirements include:

- Message durability
- Auditability
- Idempotency
- Replay protection

---

# Strong Recommendation

If your goal is:

- Business enablement
- Integration platform
- E-invoicing SaaS

Then the best approach is usually:

- Build a PHP orchestration platform
- Outsource the AS4 transport layer

This is the architecture most mature providers use internally.

---

# Fully PHP-Native Stack Example

A fully PHP-native stack might include:

- PHP 8.3+
- Symfony
- PostgreSQL
- RabbitMQ
- xmlseclibs
- libxml
- OpenSSL
- Docker/Kubernetes
- Nginx
- Redis

The AS4 and WS-Security implementation remains the highest-risk area.

---

# Recommended Learning Order

1. UBL 2.1
2. EN16931
3. PEPPOL BIS Billing 3.0
4. SMP/SML
5. AS4
6. WS-Security
7. XMLDSig/XAdES
8. PEPPOL policy documents

---

# Useful References

## Official Documentation

- https://docs.peppol.eu
- https://peppol.org

## AS4 Projects

- https://ec.europa.eu/digital-building-blocks/sites/display/DIGITAL/Domibus
- https://github.com/NordicSmartGovernment/oxalis

## Security Libraries

- https://github.com/robrichards/xmlseclibs

---

# Suggested Next Steps

If continuing this project, recommended next steps would be:

1. Build SMP lookup support
2. Build XML validation pipeline
3. Integrate Domibus or Oxalis
4. Create outbound invoice APIs
5. Implement async queueing
6. Add inbound processing
7. Prepare for interoperability testing

---

# Final Thoughts

Building a PEPPOL access point is less about creating a standard API and more about building secure, interoperable financial messaging infrastructure.

The most successful implementations usually:

- Keep PHP focused on orchestration and business logic
- Reuse mature AS4 infrastructure
- Invest heavily in validation and operational tooling
- Prioritize reliability and auditability from day one

