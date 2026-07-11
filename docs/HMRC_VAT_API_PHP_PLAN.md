# HMRC VAT MTD API — PHP Library Plan (`rossaddison/vat-api-php`)

Extract the inline HMRC VAT API calls from `HmrcController` into a standalone,
reusable Composer package that mirrors the models and routes of HMRC's own Scala
microservice (`hmrc/vat-api`), so Yii3-i and other PHP projects can integrate with
Making Tax Digital VAT cleanly.

---

## Background

HMRC's `hmrc/vat-api` (Apache 2.0, Scala/Play) is the internal microservice that
proxies between the MTD API gateway and HMRC's downstream DES/ETMP system. Its
routes, request/response models, and error codes are the authoritative reference for
what the public MTD VAT API accepts and returns.

Currently in Yii3-i, all HMRC API calls are made inline inside
`src/Backend/Controller/HmrcController.php` using raw Guzzle with no typed DTOs.
The goal is to extract this into a proper Composer package with typed value objects,
a PSR-18 HTTP client wrapper, and comprehensive error handling matching the Scala
error catalogue — then replace the inline code in `HmrcController` with library calls.

---

## Scala source — API surface (`hmrc/vat-api`)

### Routes (`conf/v1.routes`)

| Method | Path | Scala Controller |
|---|---|---|
| `GET` | `/:vrn/obligations` | `ObligationsController.retrieveObligations` |
| `POST` | `/:vrn/returns` | `SubmitReturnController.submitReturn` |
| `GET` | `/:vrn/returns/:periodKey` | `ViewReturnController.viewReturn` |
| `GET` | `/:vrn/liabilities` | `LiabilitiesController.retrieveLiabilities` |
| `GET` | `/:vrn/payments` | `PaymentsController.retrievePayments` |

Base URL (production): `https://api.service.hmrc.gov.uk/organisations/vat`

### Key Scala model — `SubmitRequestBody` (VAT Return, boxes 1–9)

```scala
case class SubmitRequestBody(
  periodKey:                    Option[String],
  vatDueSales:                  Option[BigDecimal],   // Box 1
  vatDueAcquisitions:           Option[BigDecimal],   // Box 2
  totalVatDue:                  Option[BigDecimal],   // Box 3 (= Box1 + Box2)
  vatReclaimedCurrPeriod:       Option[BigDecimal],   // Box 4
  netVatDue:                    Option[BigDecimal],   // Box 5 (|Box3 – Box4|)
  totalValueSalesExVAT:         Option[BigDecimal],   // Box 6
  totalValuePurchasesExVAT:     Option[BigDecimal],   // Box 7
  totalValueGoodsSuppliedExVAT: Option[BigDecimal],   // Box 8
  totalAcquisitionsExVAT:       Option[BigDecimal],   // Box 9
  finalised:                    Option[Boolean],
  receivedAt:                   Option[String] = None,
  agentReference:               Option[String] = None
)
```

Note: the Scala `writes` serialiser renames some fields for DES:
`totalVatDue` → `vatDueTotal`, `netVatDue` → `vatDueNet`,
`totalAcquisitionsExVAT` → `totalAllAcquisitionsExVAT`.

### MTD error codes (from `mtdErrors.scala`)

| PHP constant | Code | Message |
|---|---|---|
| `VRN_INVALID` | `VRN_INVALID` | The provided VRN is invalid |
| `VRN_NOT_FOUND` | `VRN_NOT_FOUND` | The provided VRN was not found |
| `RULE_INCORRECT_OR_EMPTY_BODY` | `RULE_INCORRECT_OR_EMPTY_BODY_SUBMITTED` | An empty or non-matching body was submitted |
| `RULE_INSOLVENT_TRADER` | `RULE_INSOLVENT_TRADER` | The remote endpoint has indicated that the Trader is insolvent |
| `NOT_FOUND` | `MATCHING_RESOURCE_NOT_FOUND` | Matching resource not found |
| `INTERNAL_SERVER_ERROR` | `INTERNAL_SERVER_ERROR` | An internal server error occurred |
| `INVALID_REQUEST` | `INVALID_REQUEST` | Invalid request |
| `NRS_FAILURE` | `NRS_SUBMISSION_FAILURE` | The submission to NRS from MDTP failed |

---

## Current state in Yii3-i

### `HmrcController.php` — what it does today (inline Guzzle)

| Method | Calls | Status |
|---|---|---|
| `vatObligations()` | GET `…/vat/{vrn}/obligations?status=O` | ✅ works, no DTO |
| `vatReturnPrepare()` | No API call — reads local DB | ✅ works |
| `vatReturnSubmit()` | POST `…/vat/{vrn}/returns` | ✅ works, raw array |
| `fphValidate()` | GET fraud-prevention-headers/validate | ✅ works |
| `fphFeedback()` | POST fraud-prevention-headers/feedback | ✅ works |
| `selfEmploymentBusinesses()` | GET individuals/business/self-employment | ✅ works |
| `createTestUserIndividual()` | POST create-test-user/individuals | ✅ works |

**Missing endpoints** (exist in Scala, not yet in PHP):

| Endpoint | Description |
|---|---|
| `GET /:vrn/returns/:periodKey` | View a previously submitted return |
| `GET /:vrn/liabilities` | Retrieve VAT liabilities |
| `GET /:vrn/payments` | Retrieve VAT payments |

### Problems with the current inline approach

1. No typed DTOs — `vatReturnSubmit()` assembles a raw PHP array inline
2. No validation of box values before submission (Box 3 = Box 1 + Box 2, Box 5 = |Box 3 − Box 4|)
3. Error responses decoded as raw JSON — no mapping to MTD error codes
4. VRN validation is absent (regex: `^\d{9}$`)
5. Not reusable outside `HmrcController`
6. No PHPUnit coverage of the HTTP interaction

---

## `rossaddison/vat-api-php` — library structure

### Composer package

```json
{
  "name": "rossaddison/vat-api-php",
  "description": "PHP client for the HMRC Making Tax Digital VAT API",
  "license": "MIT",
  "require": {
    "php": "^8.4",
    "psr/http-client": "^1.0",
    "psr/http-factory": "^1.1",
    "psr/http-message": "^2.0",
    "psr/log": "^3.0"
  },
  "autoload": {
    "psr-4": { "Rossaddison\\VatApi\\": "src/" }
  }
}
```

No Guzzle dependency — uses PSR-18 so any HTTP client works. Yii3-i injects its
existing Guzzle via DI.

---

### Directory structure

```
rossaddison/vat-api-php/
├── src/
│   ├── Client/
│   │   └── VatApiClient.php          ← main entry point
│   ├── Request/
│   │   ├── VatReturn.php             ← SubmitRequestBody equivalent
│   │   ├── ObligationsRequest.php    ← from/to/status query params
│   │   ├── PeriodRequest.php         ← from/to query params (liabilities/payments)
│   │   └── Vrn.php                   ← validated VRN value object
│   ├── Response/
│   │   ├── ObligationsResponse.php
│   │   ├── Obligation.php
│   │   ├── SubmitReturnResponse.php
│   │   ├── ViewReturnResponse.php
│   │   ├── LiabilitiesResponse.php
│   │   ├── Liability.php
│   │   ├── PaymentsResponse.php
│   │   └── Payment.php
│   ├── Error/
│   │   ├── MtdError.php              ← typed error value object
│   │   ├── MtdErrorCode.php          ← enum of all MTD error codes
│   │   └── VatApiException.php       ← thrown on 4xx/5xx with MtdError
│   └── Config/
│       └── VatApiConfig.php          ← base URL, env (sandbox/prod), token
├── Tests/
└── composer.json
```

---

### PHP equivalents of Scala models

#### `VatReturn` (→ Scala `SubmitRequestBody`)

```php
// src/Request/VatReturn.php
final readonly class VatReturn
{
    public function __construct(
        public string  $periodKey,
        public float   $vatDueSales,                  // Box 1
        public float   $vatDueAcquisitions,            // Box 2
        public float   $totalVatDue,                   // Box 3 = Box1 + Box2
        public float   $vatReclaimedCurrPeriod,        // Box 4
        public float   $netVatDue,                     // Box 5 = |Box3 - Box4|
        public float   $totalValueSalesExVAT,          // Box 6
        public float   $totalValuePurchasesExVAT,      // Box 7
        public float   $totalValueGoodsSuppliedExVAT,  // Box 8
        public float   $totalAcquisitionsExVAT,        // Box 9
        public bool    $finalised,
        public ?string $agentReference = null,
    ) {
        // Enforce MTD cross-field rules (matches Scala validation layer)
        $computed3 = round($vatDueSales + $vatDueAcquisitions, 2);
        if (abs($totalVatDue - $computed3) > 0.01) {
            throw new \InvalidArgumentException(
                "Box 3 must equal Box 1 + Box 2 ({$computed3}); got {$totalVatDue}."
            );
        }
        $computed5 = round(abs($totalVatDue - $vatReclaimedCurrPeriod), 2);
        if (abs($netVatDue - $computed5) > 0.01) {
            throw new \InvalidArgumentException(
                "Box 5 must equal |Box 3 − Box 4| ({$computed5}); got {$netVatDue}."
            );
        }
    }

    /** @return array<string, mixed> DES-format payload (matches Scala OWrites) */
    public function toDesArray(): array
    {
        return array_filter([
            'periodKey'                      => $this->periodKey,
            'vatDueSales'                    => $this->vatDueSales,
            'vatDueAcquisitions'             => $this->vatDueAcquisitions,
            'vatDueTotal'                    => $this->totalVatDue,        // renamed for DES
            'vatReclaimedCurrPeriod'         => $this->vatReclaimedCurrPeriod,
            'vatDueNet'                      => $this->netVatDue,          // renamed for DES
            'totalValueSalesExVAT'           => $this->totalValueSalesExVAT,
            'totalValuePurchasesExVAT'       => $this->totalValuePurchasesExVAT,
            'totalValueGoodsSuppliedExVAT'   => $this->totalValueGoodsSuppliedExVAT,
            'totalAllAcquisitionsExVAT'      => $this->totalAcquisitionsExVAT, // renamed
            'finalised'                      => $this->finalised,
            'agentReferenceNumber'           => $this->agentReference,
        ], fn($v) => $v !== null);
    }
}
```

#### `Vrn` (→ Scala `Vrn` domain object)

```php
// src/Request/Vrn.php
final readonly class Vrn
{
    public string $value;

    public function __construct(string $vrn)
    {
        if (!preg_match('/^\d{9}$/', $vrn)) {
            throw new \InvalidArgumentException("VRN_INVALID: '{$vrn}' must be 9 digits.");
        }
        $this->value = $vrn;
    }

    public function __toString(): string { return $this->value; }
}
```

#### `MtdErrorCode` (→ Scala `mtdErrors.scala`)

```php
// src/Error/MtdErrorCode.php
enum MtdErrorCode: string
{
    case VrnInvalid               = 'VRN_INVALID';
    case VrnNotFound              = 'VRN_NOT_FOUND';
    case RuleIncorrectOrEmptyBody = 'RULE_INCORRECT_OR_EMPTY_BODY_SUBMITTED';
    case RuleInsolventTrader      = 'RULE_INSOLVENT_TRADER';
    case NotFound                 = 'MATCHING_RESOURCE_NOT_FOUND';
    case InternalServerError      = 'INTERNAL_SERVER_ERROR';
    case InvalidRequest           = 'INVALID_REQUEST';
    case NrsFailure               = 'NRS_SUBMISSION_FAILURE';
}
```

#### `Obligation` and `ObligationsResponse`

```php
// src/Response/Obligation.php
final readonly class Obligation
{
    public function __construct(
        public string  $start,        // YYYY-MM-DD
        public string  $end,          // YYYY-MM-DD
        public string  $due,          // YYYY-MM-DD
        public string  $status,       // 'O' (open) or 'F' (fulfilled)
        public ?string $periodKey,
        public ?string $received,     // YYYY-MM-DD, set when fulfilled
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            start:     $data['start'],
            end:       $data['end'],
            due:       $data['due'],
            status:    $data['status'],
            periodKey: $data['periodKey'] ?? null,
            received:  $data['received'] ?? null,
        );
    }
}
```

---

### `VatApiClient` — the main client class

```php
// src/Client/VatApiClient.php
final class VatApiClient
{
    public function __construct(
        private readonly \Psr\Http\Client\ClientInterface        $httpClient,
        private readonly \Psr\Http\Message\RequestFactoryInterface $requestFactory,
        private readonly VatApiConfig                            $config,
        private readonly \Psr\Log\LoggerInterface                $logger,
    ) {}

    /** @return Obligation[] */
    public function getObligations(Vrn $vrn, string $from, string $to, string $status = 'O'): array

    public function submitReturn(Vrn $vrn, VatReturn $return): SubmitReturnResponse

    public function viewReturn(Vrn $vrn, string $periodKey): ViewReturnResponse

    /** @return Liability[] */
    public function getLiabilities(Vrn $vrn, string $from, string $to): array

    /** @return Payment[] */
    public function getPayments(Vrn $vrn, string $from, string $to): array
}
```

Each method:
1. Builds a PSR-7 request with `Authorization: Bearer {token}` + fraud-prevention headers
2. Sends via `$this->httpClient->sendRequest()`
3. On 4xx/5xx: decodes `{ "code": "...", "message": "..." }` → throws `VatApiException(MtdErrorCode)`
4. On 2xx: deserialises JSON → typed response object

---

## What changes in Yii3-i (`HmrcController.php`)

### Before (current inline approach)

```php
// Inline Guzzle in HmrcController::vatReturnSubmit()
$returnData = [
    'periodKey'                      => $body['periodKey'],
    'vatDueSales'                    => (float) $body['vatDueSales'],
    // ... 8 more raw array keys
    'finalised'                      => true,
];
$response = $this->httpClient->post(
    "https://api.service.hmrc.gov.uk/organisations/vat/{$vrn}/returns",
    ['json' => $returnData, 'headers' => ['Authorization' => "Bearer {$token}"]]
);
```

### After (library call)

```php
// HmrcController::vatReturnSubmit() after refactor
use Rossaddison\VatApi\Client\VatApiClient;
use Rossaddison\VatApi\Request\{Vrn, VatReturn};

$return = new VatReturn(
    periodKey:                    $body['periodKey'],
    vatDueSales:                  (float) $body['vatDueSales'],       // Box 1
    vatDueAcquisitions:           (float) $body['vatDueAcquisitions'],// Box 2
    totalVatDue:                  (float) $body['totalVatDue'],       // Box 3
    vatReclaimedCurrPeriod:       (float) $body['vatReclaimedCurrPeriod'], // Box 4
    netVatDue:                    (float) $body['netVatDue'],         // Box 5
    totalValueSalesExVAT:         (float) $body['totalValueSalesExVAT'],   // Box 6
    totalValuePurchasesExVAT:     (float) $body['totalValuePurchasesExVAT'], // Box 7
    totalValueGoodsSuppliedExVAT: (float) $body['totalValueGoodsSuppliedExVAT'], // Box 8
    totalAcquisitionsExVAT:       (float) $body['totalAcquisitionsExVAT'],   // Box 9
    finalised:                    true,
);

try {
    $result = $this->vatApiClient->submitReturn(new Vrn($vrn), $return);
    // $result->processingDate available as typed string
} catch (VatApiException $e) {
    // $e->errorCode is a typed MtdErrorCode enum case
}
```

### Three new endpoints wired in `HmrcController`

| New method | Library call |
|---|---|
| `vatViewReturn(string $periodKey)` | `$vatApiClient->viewReturn(new Vrn($vrn), $periodKey)` |
| `vatLiabilities()` | `$vatApiClient->getLiabilities(new Vrn($vrn), $from, $to)` |
| `vatPayments()` | `$vatApiClient->getPayments(new Vrn($vrn), $from, $to)` |

---

## DI wiring in Yii3-i

New file: `config/common/di/hmrc.php`

```php
return [
    VatApiConfig::class => static fn(Aliases $aliases) => new VatApiConfig(
        baseUrl: $_ENV['HMRC_ENV'] === 'prod'
            ? 'https://api.service.hmrc.gov.uk/organisations/vat'
            : 'https://test-api.service.hmrc.gov.uk/organisations/vat',
    ),

    VatApiClient::class => static fn(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        VatApiConfig $config,
        LoggerInterface $logger,
    ) => new VatApiClient($httpClient, $requestFactory, $config, $logger),
];
```

New `.env` variable:

```dotenv
HMRC_ENV=sandbox   # or prod
```

`HmrcController` gains `VatApiClient $vatApiClient` in its constructor (added to
`HmrcControllerDeps` if/when S107 is applied to that controller).

---

## Tests

### In `rossaddison/vat-api-php`

- `Tests/Request/VatReturnTest.php` — Box 3 and Box 5 cross-field validation
- `Tests/Request/VrnTest.php` — valid/invalid VRN regex
- `Tests/Error/MtdErrorCodeTest.php` — enum cases match Scala constants
- `Tests/Client/VatApiClientTest.php` — mock HTTP client, assert correct URL/headers/body

### In Yii3-i

- Update `HmrcController` tests to mock `VatApiClient` instead of raw Guzzle
- `Tests/Unit/Hmrc/VatReturnSubmitTest.php` — happy path + `VatApiException` handling

All files: Psalm errorLevel 1 clean before merge.

---

## Effort estimate

| Step | Estimate |
|---|---|
| Create `rossaddison/vat-api-php` repo + composer.json | 30 min |
| `Vrn`, `VatReturn`, `ObligationsRequest`, `PeriodRequest` | 2 h |
| `Obligation`, `ObligationsResponse`, `SubmitReturnResponse`, `ViewReturnResponse`, `Liability`, `Payment` | 2 h |
| `MtdErrorCode` enum + `VatApiException` + `MtdError` | 1 h |
| `VatApiClient` (5 methods, PSR-18) | 2 h |
| `VatApiConfig` + DI wiring in Yii3-i | 30 min |
| Refactor `HmrcController` — replace inline Guzzle with client | 2 h |
| Add 3 missing endpoints (`viewReturn`, `liabilities`, `payments`) | 1 h |
| PHPUnit tests (library) | 2 h |
| PHPUnit tests (Yii3-i controller) | 1 h |
| Psalm on all files | 30 min |

**Total: ~14.5 hours**

---

## References

- [hmrc/vat-api (Scala, Apache 2.0)](https://github.com/hmrc/vat-api)
- [HMRC MTD VAT API — Developer Hub](https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api/1.0)
- [MTD VAT API v1.0 OpenAPI spec](https://developer.service.hmrc.gov.uk/api-documentation/docs/api/service/vat-api)
- Existing Yii3-i HMRC docs: [docs/HMRC_MTD_DEVELOPER_SANDBOX.md](HMRC_MTD_DEVELOPER_SANDBOX.md)
