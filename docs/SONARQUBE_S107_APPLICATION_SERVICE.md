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

## Remaining S107 candidates

> [!IMPORTANT]
> 135 violations remain project-wide. These are the highest-impact targets.

| Class | Approx params | Suggested service |
|-------|--------------|-------------------|
| `InvController::__construct` | ~23 | Action-level DI split |
| `InvEmailStage2Deps` | many | `InvEmailService` |
| `InvViewDeps` | ~30 | `InvViewService` |
| `SalesOrderViewDependencies` | ~24 | `SalesOrderViewService` |
| `PdfHelper::generateQuotePdf` | 16 | `QuotePdfService` |
| `PdfHelper::generateSalesorderPdf` | 17 | `SalesOrderPdfService` |
