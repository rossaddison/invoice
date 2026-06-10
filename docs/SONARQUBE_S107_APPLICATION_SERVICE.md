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

## Remaining S107 candidates

> [!IMPORTANT]
> ~126 violations remain project-wide after rounds 1–3. These are the highest-impact targets.

| Class | Approx params | Suggested service |
|-------|--------------|-------------------|
| `InvController::__construct` | ~23 | Action-level DI split |
| `InvEmailStage2Deps` | ~14 | `InvEmailService` |
| `InvViewDeps` | ~30 | `InvViewService` |
| `SalesOrderViewDependencies` | ~24 | `SalesOrderViewService` |
| `QuoteEmailStage0Deps::__construct` | 10 | Further domain split |
| `QuoteEmailStage2Deps::__construct` | 14 | Further domain split |
