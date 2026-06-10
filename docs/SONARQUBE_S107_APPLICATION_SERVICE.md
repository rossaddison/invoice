# SonarQube S107 — DDD Application Service Pattern

> [!NOTE]
> S107 flags methods or constructors with **more than 7 parameters**. See also: [Architecture Overview](ARCHITECTURE_DOMAIN_APPLICATION_INFRASTRUCTURE.md)

---

## What changed

| Before | After |
|--------|-------|
| `InvPdfDeps::__construct` — **17 params** | Deleted |
| `PdfHelper::generateInvPdf` — **19 params** | Moved into `InvPdfService` (private) |
| `PdfHelper::generateInvHtml` — **18 params** | Moved into `InvPdfService` (private) |
| `HtmlTrait::html` — **15 params** | `InvPdfService` injected — **2 params** |
| `PdfHelper.php` — 573 lines | ~329 lines (242 lines removed) |

---

## Solution

> [!TIP]
> Group related repos into cohesive `*Deps` classes (≤ 6 params each), then inject the groups into a single Application Service (≤ 7 params). The service exposes one clean use-case method.

**Three sub-deps classes created:**

| Class | Repos grouped | Params |
|-------|--------------|--------|
| `InvPdfCoreDeps` | `iR`, `iaR`, `gR`, `soR`, `ucR`, `uiR` | 6 |
| `InvPdfDocDeps` | `cR`, `cfR`, `cvR`, `dlR`, `icR` | 5 |
| `InvPdfItemDeps` | `aciR`, `iiR`, `aciiR`, `iiaR`, `itrR` | 5 |

**`InvPdfService` constructor — exactly 7 params (compliant):**

```php
final readonly class InvPdfService
{
    public function __construct(
        private SR $s,
        private SessionInterface $session,
        private TranslatorInterface $translator,
        private WebViewRenderer $webViewRenderer,
        private InvPdfCoreDeps $coreDeps,
        private InvPdfDocDeps $docDeps,
        private InvPdfItemDeps $itemDeps,
    ) {}
}
```

**Public API — 3 params, no S107:**

```php
public function generate(int $invId, bool $stream, bool $custom): string
public function generateHtml(int $invId, bool $custom): string
public function findInv(int $invId): ?Inv
public function loadGuestInv(string $urlKey): ?Inv
public function ucR(): UCR
public function uiR(): UIR
```

---

## Yii3 DI — no config required

```php
// Yii3 resolves InvPdfService and all its transitive deps automatically
public function pdf(
    #[RouteArgument('include')] int $include,
    InvPdfService $invPdfService,
): Response { ... }
```

---

## Reusable recipe

1. Identify the violating class (SonarQube or IDE)
2. Group repos by cohesion into `*Deps` classes of ≤ 6 params
3. Create a `final readonly class *Service` with ≤ 7 constructor params
4. Expose one or two use-case methods with ≤ 5 params
5. Inject the service — Yii3 autowires it
6. Delete the old `*Deps` class and the helper methods it replaced

---

---

## Round 2 — `QuotePdfService` + Quote Email Deps (June 2026)

> [!NOTE]
> Applied the same recipe to Quote PDF and email staging. Seven S107 violations eliminated in one pass.

### What changed

| Before | After |
|--------|-------|
| `PdfHelper::generateQuotePdf` — **16 params** | Deleted — moved into `QuotePdfService` (private) |
| `Quote/Trait/PdfTrait::pdf` — **15 params** | `QuotePdfService` injected — **2 params** |
| `Quote/Trait/PdfTrait::pdfDashboardIncludeCf` — **16 params** | **2 params** |
| `Quote/Trait/PdfTrait::pdfDashboardExcludeCf` — **16 params** | **2 params** |
| `Quote/Trait/Email::emailStage0` — **12 params** | `QuoteEmailStage0Deps` injected — **3 params** |
| `Quote/Trait/Email::emailStage1` — **28 params** | `QuoteEmailStage2Deps` + `QuoteEmailStage1Data` — **4 params** |
| `Quote/Trait/Email::emailStage2` — **21 params** | `QuoteEmailStage2Deps` injected — **4 params** |
| `QuoteController` — constructed `PdfHelper` | `$pdfHelper` property and constructor call removed |

### Sub-deps classes created

| Class | Repos grouped | Params |
|-------|--------------|--------|
| `QuotePdfCoreDeps` | `qR`, `qaR`, `gR`, `uiR`, `qcR` | 5 |
| `QuotePdfDocDeps` | `cR`, `cfR`, `cvR`, `dlR` | 4 |
| `QuotePdfItemDeps` | `qiR`, `qiaR`, `acqiR`, `qtrR` | 4 |
| `QuoteEmailStage0Deps` | `ccR`, `cfR`, `cvR`, `etR`, `icR`, `pcR`, `qR`, `qcR`, `socR`, `uiR` | 10 |
| `QuoteEmailStage2Deps` | `ccR`, `cfR`, `cvR`, `gR`, `iaR`, `icR`, `iR`, `pcR`, `qaR`, `qcR`, `qR`, `soR`, `socR`, `uiR` | 14 |

> [!TIP]
> `QuoteEmailStage0Deps` (10p) and `QuoteEmailStage2Deps` (14p) still exceed 7 — they are intentional aggregation objects that consolidate what were previously **28** and **21** method params. Further splitting would require domain re-cohesion analysis.

**`QuotePdfService` constructor — exactly 7 params (compliant):**

```php
final readonly class QuotePdfService
{
    public function __construct(
        private SR $s,
        private SessionInterface $session,
        private TranslatorInterface $translator,
        private WebViewRenderer $webViewRenderer,
        private QuotePdfCoreDeps $coreDeps,
        private QuotePdfDocDeps $docDeps,
        private QuotePdfItemDeps $itemDeps,
    ) {}
}
```

**Public API:**

```php
public function generate(int $quoteId, bool $stream, bool $custom): string
public function findQuote(int $quoteId): ?Quote
public function uiR(): UIR
```

**Value object — `QuoteEmailStage1Data`:**

```php
final class QuoteEmailStage1Data
{
    public function __construct(
        public readonly string $fromEmail,   // typed — not array (Psalm-enforced)
        public readonly string $fromName,
        public readonly string $to,
        public readonly string $subject,
        public readonly string $emailBody,
        public readonly string $cc,
        public readonly string $bcc,
        public readonly array $attachFiles,
    ) {}
}
```

> [!IMPORTANT]
> `$fromEmail`/`$fromName` replaced the original `$from: array` after Psalm flagged `$data->from[0]` as `mixed`. Splitting the untyped array into named string properties is the correct type-safe fix — never use `@psalm-suppress` for this pattern.

---

## Round 3 — `SalesOrderPdfService` + `PdfHelper.php` deleted (June 2026)

> [!NOTE]
> Final PDF helper extracted. With no remaining callers, `PdfHelper.php` was deleted entirely — along with the dead `$pdfhelper` property in `MailerHelper`.

### What changed

| Before | After |
|--------|-------|
| `PdfHelper::generateSalesorderPdf` — **17 params** | Deleted — moved into `SalesOrderPdfService` (private) |
| `SalesOrderController::pdf` — **17 effective params** (built `PdfHelper` inline + `SalesOrderViewDependencies`) | `SalesOrderPdfService` injected — **2 params** |
| `PdfHelper.php` — ~207 lines | **Deleted** — zero callers remained |
| `MailerHelper` — dead `$pdfhelper` property | Property and constructor call removed |

### Sub-deps classes created

| Class | Repos grouped | Params |
|-------|--------------|--------|
| `SalesOrderPdfCoreDeps` | `soR`, `soaR`, `socR`, `uiR` | 4 |
| `SalesOrderPdfDocDeps` | `cR`, `cfR`, `cvR` | 3 |
| `SalesOrderPdfItemDeps` | `soiR`, `soiaR`, `acsoiR`, `sotrR` | 4 |

**`SalesOrderPdfService` constructor — exactly 7 params (compliant):**

```php
final readonly class SalesOrderPdfService
{
    public function __construct(
        private SR $s,
        private SessionInterface $session,
        private TranslatorInterface $translator,
        private WebViewRenderer $webViewRenderer,
        private SalesOrderPdfCoreDeps $coreDeps,
        private SalesOrderPdfDocDeps $docDeps,
        private SalesOrderPdfItemDeps $itemDeps,
    ) {}
}
```

**Public API:**

```php
public function generate(int $soId, bool $stream, bool $custom): string
public function findSalesOrder(int $soId): ?SalesOrder
```

> [!TIP]
> `SalesOrderPdfDocDeps` has only 3 params (no `dlR`) — the SalesOrder PDF template does not render a delivery location, unlike Inv and Quote. `SalesOrderPdfCoreDeps` has only 4 params (no `gR`) — sales orders have no draft-number generation step.

> [!IMPORTANT]
> **Bug fix included:** `PdfHelper::getPrintLanguage()` always returned `'English'` for sales orders because it only checked `instanceof Quote` and `instanceof Inv`. `SalesOrderPdfService::printLanguage()` directly calls `$so->getClient()?->getClientLanguage()` — correct behaviour.

---

## Post-round fix — `customValues()` contract (June 2026)

> [!IMPORTANT]
> A runtime error exposed a contract misunderstanding in all three `customValues()` implementations. Fixed in `InvPdfService`, `QuotePdfService`, and `SalesOrderPdfService`.

**Wrong (strings stored as values):**

```php
// WRONG — CustomValuesHelper cannot call ->reqCustomFieldId() on a string
foreach ($reader as $invCustom) {
    $values['custom[' . $invCustom->reqCustomFieldId() . ']'] = $invCustom->getValue() ?? '';
}
```

**Correct (entity objects stored as values):**

```php
// CORRECT — CustomValuesHelper::formValue() iterates the array and calls
// ->reqCustomFieldId() on each element to find the matching entity
foreach ($reader as $invCustom) {
    $values[] = $invCustom;
}
```

**Why:** `CustomValuesHelper::formValue(array $entity_custom_values, int $custom_field_id)` does:

```php
foreach ($entity_custom_values as $entity_custom_value) {
    if ($entity_custom_value->reqCustomFieldId() === $custom_field_id) {
        return $entity_custom_value->getValue();
    }
}
```

It searches by calling `->reqCustomFieldId()` on each array element — the array must contain **entity objects**, not strings. Array keys are irrelevant; `CustomValuesHelper` never reads them.

> [!TIP]
> This applies to every `*Custom` entity (`InvCustom`, `QuoteCustom`, `SalesOrderCustom`). When implementing future services (e.g., `InvEmailService`), always append the entity object to the values array, never the extracted string.

---

---

## Round 4 — S1142, S1448, S131, S3776 (June 2026)

> [!NOTE]
> Five SonarQube violations resolved across four files. No `@psalm-suppress` used; Psalm errorLevel 1 passes clean.

### S1142 — `InvPdfService::generateHtml` (4 returns → 2)

The three early null-guard returns were collapsed into one combined check by loading all three nullable values (`$invAmount`, `$invUnloaded`, `$inv`) before the single guard. Custom values and the sales-order lookup are deferred until after the guard to avoid unnecessary DB queries.

```php
// Before: 3 separate early returns + 1 success return = 4
// After: 1 combined guard + 1 success return = 2
$invAmount   = ...;
$invUnloaded = ...;
$inv         = ...;
if (null === $invAmount || null === $invUnloaded || null === $inv) {
    return '';
}
$invCustomValues = $this->customValues($invId);
...
return $this->renderHtml(...);
```

### S131 — `PaymentInformationController` switch missing `default`

Added `default: break;` to the gateway switch. The outer method already returns `getNotFoundResponse()` when no case matches, so `break` is semantically correct.

### S1142 — `PaymentInformationController::brainTreeInForm` (4 returns → 3)

Extracted `initializeBraintree(PaymentInformationGatewayContext $ctx): ?array` which:
- returns `null` (with flash warnings) if not configured or if the client token cannot be generated
- returns `['clientToken' => ..., 'merchantId' => ...]` on success

`brainTreeInForm` now has exactly 3 returns: one `getNotFoundResponse()` guard, one POST completion render, one GET form render.

### S3776 — `ProductController::add()` (complexity 36 → 2)

Extracted two private methods:

| Method | Complexity | Responsibility |
|--------|-----------|----------------|
| `handleAddPost(Request, FormHydrator, ProductForm, array &$parameters): ?Response` | ~4 | Form validation, product save, redirect |
| `saveProductCustomFields(Product, array, FormHydrator, array &$parameters): void` | ~3 | Custom-field loop with typed `@var` annotation |

`add()` itself drops to complexity 2 (one `if POST` check, one `if redirect !== null`).

### S1448 — `SalesOrdersListWidget` (30 methods → 18)

Two new classes extracted into the same `Widget\` namespace:

| New class | Methods extracted | Count |
|-----------|------------------|-------|
| `SalesOrdersColumnBuilder` | `buildCheckboxColumn`, `buildStatusColumn`, `buildNumberColumn`, `buildQuoteColumn`, `buildInvoiceColumn`, `buildDateCreatedColumn`, `buildClientColumn`, `buildTotalColumn` | 8 |
| `SalesOrdersGroupingRenderer` | `makeGroupValueResolver`, `computeGroupTotals`, `applyGrouping`, `groupingScriptAndStyle` | 4 |

`SalesOrdersListWidget` keeps: `__construct`, 13 `with*` setters, `render`, `buildToolbarString`, `buildStatusBar`, `buildColumns` = **18 methods** ✓

`SalesOrdersColumnBuilder` constructor (8 params):
```php
public function __construct(
    private readonly UrlGeneratorInterface $urlGenerator,
    private readonly TranslatorInterface $translator,
    private readonly bool $visible,
    private readonly ?InvRepo $iR,
    private readonly SR $sR,
    private readonly SoR $soR,
    private readonly SoAR $soaR,
    private readonly array $optionsDataClientsDropdownFilter,
)
```
`SalesOrdersGroupingRenderer` constructor (2 params): `SoR $soR`, `SR $sR`.

Both classes are `final` and not registered in DI — they are constructed locally inside `buildColumns()` and `render()` respectively.

---

## Round 5 — `InvEmailService` + `InvViewService` (June 2026)

### `InvEmailService`

`InvEmailStage2Deps` trimmed from 22 to **15 params** (7 dead repos dropped — `aciR`, `aciiR`, `cR`, `dlR`, `iiaR`, `iiR`, `itrR` — never referenced in `Email.php`).

`InvEmailStage1Data::$from: array` replaced with typed `$fromEmail: string` + `$fromName: string` (same Psalm-enforced fix as Quote).

`InvEmailService` (7 params) consolidates the send path:

```php
final readonly class InvEmailService
{
    public function __construct(
        private SR $sR,
        private SessionInterface $session,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
        private MailerInterface $mailer,
        public readonly InvEmailStage2Deps $d,
        private InvPdfService $invPdfService,
    ) {}

    public function mailerConfigured(): bool { ... }
    public function send(int $invId, InvEmailStage1Data $data): bool { ... }
}
```

`Email.php` trait `emailStage1` and `emailStage2` now each take 3 params (was 28/21).

PSR-7 body extraction in `emailStage2` uses the established pattern from the Quote email trait:

```php
/** @var array $body['MailerInvForm'] */
$to = (string) ($body['MailerInvForm']['to_email'] ?? '');
```

This avoids `MixedAssignment`/`MixedArrayAccess` Psalm errors without `@psalm-suppress` — `getParsedBody()` returns `null|array|object` so its array values are `mixed`; the `@var` annotation asserts the element type to Psalm.

### `InvViewService`

29-param `InvViewDeps` replaced by five sub-groups + a slim service:

| Class | Repos grouped | Params |
|-------|--------------|--------|
| `InvViewCoreDeps` | `iR`, `iaR`, `icR`, `irR`, `pymR`, `gR` | 6 |
| `InvViewItemDeps` | `iiR`, `iiaR`, `piR`, `pR`, `taskR`, `prjctR` | 6 |
| `InvViewMetaDeps` | `cfR`, `cvR`, `etR`, `pmR`, `trR`, `unR` | 6 |
| `InvViewAllowanceDeps` | `acR`, `aciR`, `aciiR`, `itrR`, `fR` | 5 |
| `InvViewRelationDeps` | `cR`, `dlR`, `soR`, `ucR`, `uiR`, `upR` | 6 |

```php
final readonly class InvViewService
{
    public function __construct(
        public readonly InvViewCoreDeps $core,
        public readonly InvViewItemDeps $items,
        public readonly InvViewMetaDeps $meta,
        public readonly InvViewAllowanceDeps $allowance,
        public readonly InvViewRelationDeps $relation,
    ) {}
}
```

`InvViewDeps` deleted. `View.php` trait updated throughout (`$d->iR` → `$service->core->iR`, etc.). Psalm errorLevel 1 clean.

---

## Round 6 — `SalesOrderViewService` (June 2026)

23-param `SalesOrderViewDependencies` split into four sub-groups. Dead `cR` (ClientRepository) dropped — never referenced in `view()`.

| Class | Repos grouped | Params |
|-------|--------------|--------|
| `SoViewCoreDeps` | `soR`, `soaR`, `soiR`, `sotrR`, `socR`, `soiaR` | 6 |
| `SoViewItemDeps` | `piR`, `pR`, `taskR`, `trR`, `uR` | 5 |
| `SoViewMetaDeps` | `cfR`, `cvR`, `gR`, `invRepo`, `settingRepository` | 5 |
| `SoViewRelationDeps` | `acsoiR`, `acsoR`, `dR`, `qR`, `ucR`, `uiR` | 6 |

```php
final readonly class SalesOrderViewService
{
    public function __construct(
        public readonly SoViewCoreDeps $core,
        public readonly SoViewItemDeps $items,
        public readonly SoViewMetaDeps $meta,
        public readonly SoViewRelationDeps $relation,
    ) {}
}
```

`SalesOrderViewDependencies` deleted. `SalesOrderController::view()` updated throughout (`$d->soR` → `$service->core->soR`, etc.). Psalm errorLevel 1 clean.

---

---

## Round 7 — `InvEmailStage2Deps` sub-split (June 2026)

15-param `InvEmailStage2Deps` reduced to 3 params by introducing three sub-groups:

| Class | Repos grouped | Params |
|-------|--------------|--------|
| `InvEmailCoreDeps` | `iR`, `iaR`, `icR`, `islR`, `gR`, `uiR` | 6 |
| `InvEmailCustomDeps` | `ccR`, `cfR`, `cvR`, `pcR`, `qcR`, `socR` | 6 |
| `InvEmailRelationDeps` | `qaR`, `qR`, `soR` | 3 |

```php
final class InvEmailStage2Deps
{
    public function __construct(
        public readonly InvEmailCoreDeps $core,
        public readonly InvEmailCustomDeps $custom,
        public readonly InvEmailRelationDeps $relation,
    ) {}
}
```

`InvEmailService` updated (`$d->iR` → `$d->core->iR`, `$d->cvR` → `$d->custom->cvR`, etc.). `Email.php` trait `emailStage2` updated (`$d->gR` → `$d->core->gR`, `$d->islR` → `$d->core->islR`). Psalm errorLevel 1 clean.

---

## Round 8 — `QuoteEmailStage2Deps` + `QuoteEmailStage0Deps` sub-split (June 2026)

14-param `QuoteEmailStage2Deps` and 10-param `QuoteEmailStage0Deps` reduced by introducing shared and per-stage sub-groups.

### Shared sub-group (used by both Stage0 and Stage2)

| Class | Repos grouped | Params |
|-------|--------------|--------|
| `QuoteEmailCustomDeps` | `ccR`, `cfR`, `cvR`, `icR`, `pcR`, `qcR` | 6 |

### Stage0-specific

| Class | Repos grouped | Params |
|-------|--------------|--------|
| `QuoteEmailStage0EntityDeps` | `etR`, `qR`, `socR`, `uiR` | 4 |

```php
final class QuoteEmailStage0Deps
{
    public function __construct(
        public readonly QuoteEmailCustomDeps $custom,
        public readonly QuoteEmailStage0EntityDeps $entity,
    ) {}
}
```

### Stage2-specific

| Class | Repos grouped | Params |
|-------|--------------|--------|
| `QuoteEmailStage2CoreDeps` | `gR`, `iaR`, `iR`, `socR`, `uiR` | 5 |
| `QuoteEmailStage2RelationDeps` | `qaR`, `qR`, `soR` | 3 |

```php
final class QuoteEmailStage2Deps
{
    public function __construct(
        public readonly QuoteEmailCustomDeps $custom,
        public readonly QuoteEmailStage2CoreDeps $core,
        public readonly QuoteEmailStage2RelationDeps $relation,
    ) {}
}
```

`Quote/Trait/Email.php` updated throughout (`$d->ccR` → `$d->custom->ccR`, `$d->qR` → `$d->entity->qR` / `$d->relation->qR`, etc.). `QuoteEmailCustomDeps` is shared — Yii3 DI autowires the same instance to both `QuoteEmailStage0Deps` and `QuoteEmailStage2Deps`. Psalm errorLevel 1 clean.

---

## Round 9 — `InvController::__construct` (June 2026)

23-param constructor reduced to 4 params by grouping all deps into four sub-classes. All 16 controller-specific properties keep their existing names — zero trait files changed.

| Class | Contents | Params |
|-------|----------|--------|
| `InvControllerBaseDeps` | `webService`, `userService`, `translator`, `webViewRenderer`, `session`, `sR`, `flash` | 7 |
| `InvControllerServiceDeps` | `invAllowanceChargeService`, `invAmountService`, `invService`, `invCustomService`, `invItemService`, `invTaxRateService` | 6 |
| `InvControllerInfraDeps` | `factory`, `htmlResponseFactory`, `logger`, `mailer`, `urlGenerator`, `delRepo` | 6 |
| `InvControllerUIDeps` | `aciis`, `formFields`, `buttonsToolbarFull`, `customFieldProcessor` | 4 |

```php
public function __construct(
    InvControllerBaseDeps $base,
    InvControllerServiceDeps $services,
    InvControllerInfraDeps $infra,
    InvControllerUIDeps $ui,
) {
    parent::__construct(
        $base->webService, $base->userService, $base->translator,
        $base->webViewRenderer, $base->session, $base->sR, $base->flash
    );
    $this->factory = $infra->factory;
    // ... all 16 properties assigned from groups
}
```

> [!TIP]
> The "re-expose via assignment" technique: properties keep the same name (`$this->factory`, `$this->inv_service`, etc.) but are no longer constructor-promoted — they are declared at class level and assigned in the constructor body from the deps objects. All 20+ trait files continue to work without modification.

Psalm errorLevel 1 clean.

---

## Round 10 — `QuoteController` + `SalesOrderController` constructors (June 2026)

### QuoteController (32p → 6p)

25 controller-specific params grouped into 5 deps classes:

| Class | Contents | Params |
|-------|----------|--------|
| `QuoteControllerBaseDeps` | `webService`, `userService`, `translator`, `webViewRenderer`, `session`, `sR`, `flash` | 7 |
| `QuoteControllerInvDeps` | `invAllowanceChargeService`, `invAmountService`, `invService`, `invCustomService`, `invItemService`, `invTaxRateService` | 6 |
| `QuoteControllerQuoteDeps` | `qacService`, `quoteAmountService`, `quoteCustomService`, `quoteItemService`, `quoteService`, `quoteTaxRateService` | 6 |
| `QuoteControllerSoDeps` | `soacService`, `soCustomService`, `soItemService`, `soService`, `soTaxRateService` | 5 |
| `QuoteControllerInfraDeps` | `factory`, `htmlResponseFactory`, `logger`, `mailer`, `urlGenerator`, `formFields` | 6 |
| `QuoteControllerUIDeps` | `quoteCustomFieldProcessor`, `quoteToolbar` | 2 |

Constructor reduced to **6 params**. All 25 properties re-declared at class level; zero trait files changed.

### SalesOrderController (17p → 3p)

Two dead params dropped (`InvAmountService $invAmountService`, `IIACS $inv_item_ac_service` — never referenced outside constructor):

| Class | Contents | Params |
|-------|----------|--------|
| `SoControllerBaseDeps` | `webService`, `userService`, `translator`, `webViewRenderer`, `session`, `sR`, `flash` | 7 |
| `SoControllerInvDeps` | `invService`, `invAllowanceChargeService`, `invCustomService`, `invItemService`, `invTaxRateService` | 5 |
| `SoControllerMiscDeps` | `factory`, `salesorderService`, `salesOrderToolbar` | 3 |

Constructor reduced to **3 params**. Psalm errorLevel 1 clean.

---

## Remaining S107 candidates

> [!NOTE]
> All major S107 violations resolved through Round 10.
