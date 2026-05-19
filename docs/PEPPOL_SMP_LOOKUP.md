# Peppol SMP Lookup

Implementation of **Service Metadata Publisher (SMP) lookup** — the Peppol
participant discovery mechanism that resolves a recipient's AS4 endpoint URL
and certificate before a document is transmitted.

---

## What SMP Lookup Does

Before sending an invoice on the Peppol network, the sender must know:

- **Where** to deliver the document (the recipient's AS4 endpoint URL)
- **Which certificate** to use to encrypt and sign the payload

This information is stored in the recipient's SMP (a publicly accessible HTTP
service). Finding the right SMP for a given participant involves a DNS query
against the SML (Service Metadata Locator), a central DNS zone maintained by
OpenPeppol.

---

## Lookup Chain

```text
participantId  (e.g. 0088:1234567890123)
        │
        │  1. SML DNS lookup
        │     hash   = md5(lowercase("iso6523-actorid-upis::0088:1234567890123"))
        │     cname  = B-{hash}.iso6523-actorid-upis.{smlZone}
        │     target = SMP hostname  (CNAME record)
        ▼
SMP HTTP GET
  http://{smpHost}/iso6523-actorid-upis%3A%3A{participantId}/services/{documentTypeId}
        │
        │  2. Parse ServiceMetadata XML
        │     Supports PEPPOL SMP 1.0  (busdox.org namespace)
        │     Supports BDX SMP 1.0     (oasis-open.org namespace)
        ▼
SmpEndpoint
  endpointUrl      https://ap.recipient.com/as4
  certificate      MIIHAj... (base64 DER)
  transportProfile peppol-as4-2.0
```

---

## New Files

| File | Purpose |
|------|---------|
| `src/Invoice/Peppol/SmpEndpoint.php` | Readonly value object — `endpointUrl`, `certificate`, `transportProfile` |
| `src/Invoice/Peppol/SmpLookupException.php` | Domain exception thrown on any lookup failure |
| `src/Invoice/Peppol/SmpResolverInterface.php` | Contract — `resolve()` and `isRegistered()` |
| `src/Invoice/Peppol/SmpResolver.php` | Implementation — DNS → HTTP → XML parse |
| `Tests/Unit/Invoice/Peppol/SmpResolverTest.php` | 10 tests, 24 assertions |

---

## Configuration

Two environment variables control the resolver, both optional:

| Variable | Default | Purpose |
|----------|---------|---------|
| `PEPPOL_SML_ZONE` | `edelivery.tech.ec.europa.eu` | DNS zone for the SML. Use `acc.edelivery.tech.ec.europa.eu` for the acceptance (test) network. |
| `PEPPOL_SMP_BASE_URL` | _(none)_ | Bypass DNS entirely and point directly at an SMP host, e.g. `http://smp.example.com`. Intended for development and tests. |

Add to `.env`:

```dotenv
# Production (default — omit to use the default)
PEPPOL_SML_ZONE=edelivery.tech.ec.europa.eu

# Acceptance / test network
# PEPPOL_SML_ZONE=acc.edelivery.tech.ec.europa.eu

# Dev override — skip DNS and hit a known SMP directly
# PEPPOL_SMP_BASE_URL=http://smp.example.com
```

---

## SmpResolver

```php
final class SmpResolver implements SmpResolverInterface
{
    public function __construct(
        private readonly ClientInterface        $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly string  $smlZone    = 'edelivery.tech.ec.europa.eu',
        private readonly ?string $smpBaseUrl = null,
    ) {}

    public function resolve(string $participantId, string $documentTypeId): SmpEndpoint;
    public function isRegistered(string $participantId, string $documentTypeId): bool;
}
```

`isRegistered()` is a safe wrapper around `resolve()` — it catches
`SmpLookupException` and returns `false` instead of throwing.

---

## SmpEndpoint

```php
final readonly class SmpEndpoint
{
    public string $endpointUrl;      // e.g. https://ap.example.com/as4
    public string $certificate;      // base64-encoded DER certificate
    public string $transportProfile; // e.g. peppol-as4-2.0
}
```

---

## Error Handling

All failures surface as `SmpLookupException`:

| Cause | Message |
|-------|---------|
| Participant not in SML (DNS miss) | `Participant not found in SML: {participantId}` |
| Network error to SMP | `SMP HTTP request failed: {message}` |
| HTTP 404 from SMP | `Document type not registered for participant` |
| HTTP 4xx/5xx from SMP | `SMP returned HTTP {code}` |
| Malformed SMP XML | `Invalid SMP XML: {detail}` |
| No AS4 endpoint in response | `No AS4 endpoint found in SMP response for transport profile: peppol-as4-2.0` |

---

## XML Namespace Support

The resolver tries the **PEPPOL SMP 1.0** namespace first, then falls back to
**BDX SMP 1.0**:

| Standard | Namespace |
|----------|-----------|
| PEPPOL SMP 1.0 | `http://busdox.org/serviceMetadata/publishing/1.0/` |
| BDX SMP 1.0 (OASIS) | `http://docs.oasis-open.org/bdxr/ns/SMP/2016/05` |

---

## DI Wiring

`config/common/di/peppol.php` binds the interface to the implementation and
injects the two scalar constructor arguments from environment variables:

```php
SmpResolverInterface::class => SmpResolver::class,

SmpResolver::class => [
    'class'        => SmpResolver::class,
    '__construct()' => [
        'smlZone'    => $_ENV['PEPPOL_SML_ZONE']    ?? 'edelivery.tech.ec.europa.eu',
        'smpBaseUrl' => $_ENV['PEPPOL_SMP_BASE_URL'] ?? null,
    ],
],
```

`ClientInterface` (Guzzle) and `RequestFactoryInterface` (`GuzzleHttp\Psr7\HttpFactory`)
are already autowired by `config/common/di/psr18.php` and `config/common/di/psr17.php`.

---

## Relationship to PeppolSendService

`PeppolSendService` delegates AS4 transport to Oxalis, and Oxalis performs its
own SMP lookup before delivery. `SmpResolver` is therefore used for
**pre-flight checks** in the application layer:

- Confirm a recipient is registered before showing the "Send via Peppol" option
- Display the recipient's endpoint URL and certificate in the client Peppol settings
- Validate the document type is supported prior to generating UBL XML

---

## Phase Status

| Phase | Item | Status |
|-------|------|--------|
| 1 | Outbound sending via Oxalis | ✅ |
| 1 | Message lifecycle persistence | ✅ |
| 1 | Inbound delivery callback | ✅ |
| 1 | Retries | ✅ |
| 1 | UBL XML generation | ✅ |
| 1 | BIS Billing 3.0 validation | ✅ |
| **1** | **SMP lookup** | **✅ (this document)** |
| 2 | Async queues | ⬜ |
| 2 | Monitoring / metrics | ⬜ |
| 2 | Audit logging | ⬜ |
| 3 | Interoperability testing | ⬜ |
| 3 | OpenPeppol certification | ⬜ |
