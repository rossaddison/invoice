# Home-Care QR Auto-Invoice Facility + Routes Config Refactor — Summary

## 🎯 What Was Built

Two related bodies of work from the same session: a new customer-facing
recurring-invoice facility, and a full restructuring of the route
configuration it (and everything else) is registered in.

---

## 1. Home-Care QR Auto-Invoice Facility

A customer gets a QR code tied to their account, sticks it somewhere
visible, and scans it whenever a recurring service visit is completed. If
their billing is in good standing, scanning auto-generates and sends a new
invoice — with no login required for the scan itself.

Originally scoped and named for a window-cleaning business; generalized
mid-implementation to `HomeCare` naming since the same facility applies to
any recurring home-service business (gardening, pool care, pest control,
etc.), not just window cleaning.

### Core Classes (PHP)

| Class | Purpose |
|---|---|
| `App\Invoice\Inv\HomeCareCleaningEligibilityService` | Pure business rule: eligible only when the `homecare_auto_invoice_enabled` setting is on, the client has an invoice on file, their last invoice is paid with a payment date on record, no invoice (paid or not) exists dated after that payment, **and** that invoice contains at least one Service-type product |
| `App\Invoice\Inv\Trait\HomeCareScan` (`homeCareScan()`) | Public, **unauthenticated** scan endpoint — a deliberate, scoped exception to this app's usual guest-access rule where a token alone never grants access without a session |
| `App\Invoice\Inv\Trait\MultipleCopy` (`generateHomeCareCleaningInvoice()`) | Reuses the existing invoice-copy machinery (`invToInvInvItems`, `invToInvInvAmount`, etc.) instead of a new converter; always forces `status_id = 2` (sent), regardless of the `mark_invoices_sent_copy` setting |
| `App\Invoice\Client\ClientService` (`getOrCreateQrToken()`, `renderQrDataUri()`) | Single shared source of the per-client QR token and its rendered image, used by both the guest and staff print actions |

### Data & Repository Additions

| Addition | Purpose |
|---|---|
| `Client::client_qr_token` (nullable string, indexed) | Lookup key for the public scan endpoint |
| `ClientRepository::repoClientByQrTokenquery()` | Resolves an active client from the printed token |
| `InvRepositoryInterface` / `InvRepository`: `repoClientInvoiceCountquery()`, `repoClientLatestPaidInvoicequery()`, `repoClientInvoiceCountAfterDatequery()` | The three queries the eligibility rule is built from |
| `PaymentRepositoryInterface` / `PaymentRepository`: `repoLatestPaymentDateForInvquery()` | The client's "last paid date" anchor |

`InvRepositoryInterface` and the new `PaymentRepositoryInterface` were
extracted specifically so `HomeCareCleaningEligibilityService` could be unit
tested against Mockery mocks without a database (`final class` repositories
can't be mocked directly by PHPUnit — see `docs/MOCKERY_BRIDGE.md`).

### Routes, Views, Access Model

| Route name | Access | Purpose |
|---|---|---|
| `public/homecare-scan` (`/scan/{token}`) | **No Authentication middleware** — bearer-token only | Customer scan; abuse is self-limiting since the eligibility rule blocks at most one spurious invoice until resolved |
| `inv/guest/qr` | Guest session (existing `VIEW_INV` permission) | Customer prints their own QR code |
| `client/printQrCode/{id}` | Staff session (existing `EDIT_INV` permission) | Staff prints a client's QR code on their behalf — many customers are elderly and lack printer access |

New views: `resources/views/invoice/_shared/qr_print.php` (shared by both
print actions) and `resources/views/invoice/inv/homecare_scan_result.php`
(minimal, no-PII public confirmation page — anyone holding the physical QR
code, not just the account owner, can reach it).

### Tests

`Tests/Testo/Invoice/Inv/HomeCareCleaningEligibilityServiceTest.php` — 7
Mockery-backed scenarios covering every branch of the eligibility rule
(originally 5; two added in the follow-up below).

---

### Follow-up (15 Jul 2026): Product/Service gating + Settings toggle

The eligibility rule originally only looked at payment history — it never
checked *what* was being invoiced. A separate piece of work added an explicit
`product_type` (`Product` | `Service`) classification to the `Product`
entity (replacing a fragile convention where `unit_id` doubled as an
implicit product/service signal). This follow-up wires that into the
eligibility rule and adds a Settings-level master switch.

**Eligibility rule change**: `findInvoiceToCopyIfEligible()` gained a final
check, cheapest-first after the existing payment/date logic — at least one
`InvItem` on the template invoice must reference a `Product` whose
`product_type` is `Service` (not that *every* item be a Service; mixed
invoices still qualify).

| Addition | Purpose |
|---|---|
| `App\Invoice\Enum\ProductType` | String-backed enum (`Product`/`Service`), same convention as `StoreCoveTaxType` — stored as a plain string column, not a native enum type |
| `Product::product_type` (string, default `'product'`) | The classification itself; existing rows silently default to `'product'`, no migration script |
| `App\Invoice\InvItem\InvItemRepositoryInterface`: `repoInvItemIdquery(): iterable` | Added to the existing interface so the eligibility service can iterate a template invoice's items without a DB round-trip through a `final` class |
| `App\Invoice\Product\ProductRepositoryInterface` (new) | `repoProductquery(): ?Product` — same reasoning: `ProductRepository` is `final`, unmockable directly |
| Setting `homecare_auto_invoice_enabled` (`'0'`/`'1'`, default `'0'`) | Master switch for the whole feature, Settings → Invoices tab, with a tooltip; the eligibility rule checks this first and short-circuits everything else when off |

**DI binding gap closed**: none of `HomeCareCleaningEligibilityService`'s
five interface dependencies (including the two original ones,
`InvRepositoryInterface`/`PaymentRepositoryInterface`) had an explicit
container binding anywhere in `config/` — the service would have thrown a
`NotFoundException` at the container the moment the router tried to
construct it for `/scan/{token}`. Added
`config/common/di/homecare.php` binding all five
`Interface::class => Concrete::class`, same pattern as
`As4MessageRepositoryInterface` in `config/common/di/as4.php`. Auto-merged
via the existing `'di' => 'common/di/*.php'` glob in `configuration.php`, no
further registration needed.

**QR button visibility**: both entry points into the feature —
`inv/guest.php`'s `$qrButton` and `client/view.php`'s `print.qr.code`
anchor (both ultimately print a QR code encoding the `public/homecare-scan`
URL) — rendered unconditionally regardless of the setting. Now both check
`$s->getSetting('homecare_auto_invoice_enabled') === '1'` before rendering.

**Verification**: `vendor/bin/testo` — 7/7 passing · `vendor/bin/psalm
--no-cache` — zero errors on every touched file · `vendor/bin/phpunit` on
`ProductEntityTest`/`ProductFormTest`/`ProductRepositoryTest` — no
regressions from the new `implements` clauses. Not yet click-tested through
a live `/scan/{token}` request in a browser.

---

## 2. Routes Configuration Refactor

`config/common/routes/routes.php` had grown to 2,582 lines covering 70+
controllers in one `Group::create('/invoice')` block. Split in three passes:

1. **Domain grouping** (8 files) — first pass, grouped by feature area
   (`inv`, `client`, `quote`, `product`, `payment`, `peppol`, `settings`,
   `salesorder`).
2. **Strictly per-controller** (71 files) — re-split on request so each
   controller owns exactly one route file.
3. **Naming cleanup** — hyphenated, `invoice`-prefix-free filenames
   (`routes-invoice-categoryprimary.php` → `routes-category-primary.php`).

The split was done mechanically (a depth-tracking PHP script, not
hand-editing or naive regex) that parses the `->routes(...)` body into
top-level `Route::`/`Group::` items and buckets each by the controller
referenced in its `->action([X::class, ...])` call. Verified after **every**
pass with `php yii router/list` before/after diffs — byte-for-byte identical
route table (same names, patterns, methods) throughout all three splits.

### Duplication Elimination

Splitting into 71 files surfaced heavy duplication: 548 identical
`fn (AC $checker) => $checker->withPermission($pXX)` closures and 71
identical `Group::create('/invoice')->middleware(...)->middleware(...)->routes(`
wrapper blocks. PHP traits can't apply here (`use Trait;` requires a class
body; route files are plain top-level scripts), so the equivalent DRY
mechanism is two small static-method helper classes:

| Class | Method | Replaces |
|---|---|---|
| `App\Middleware\RoutePermission` | `check()`, `invoiceGroup()` | The 548 closures + 71 wrapper blocks |
| `App\Middleware\RateLimiter` | `global()`, `perIp()` | A separate, **pre-existing** duplication in `routes.php` itself — the CF-Connecting-IP rate-limiter closure repeated across `login`/`forgotpassword`/`resetpassword`/`signup`/`change` |

Verified with `phpcpd` (PHP Copy/Paste Detector) and `phpmd` (PHP Mess
Detector) — neither installed in this project, so both were run as
standalone PHARs. Before: 1.41% duplicated lines (57 lines, 2 clones), all
in `routes.php`. After: **"No clones found"** across the entire
`config/common/routes/` tree and `src/Middleware/`. `phpmd` flagged one
`ElseExpression` style nitpick in `RateLimiter::perIp()`, fixed with a
`match()` expression.

### Tooling

- `php yii router/list --controller[=<name>]` — new optional option on
  `src/Command/Router/ListCommand.php`. No value: adds a `Controller` column
  (`ControllerClass::method`) to every row. With a value: filters to
  controllers whose class name **starts with** it, case-insensitive (a
  substring match was tried first and rejected — it false-matched, e.g.,
  `--controller=Inv` also caught `EmailTemplateController::addInvoice`).
- `m.php` dev-tools dashboard: new **Yii → `router/list --controller=<name>`**
  menu entry wired to the same option.

### Verification

`php yii router/list` before/after diff empty at every step · Psalm
errorLevel 1 clean on every new/changed file · `phpcpd`: no clones ·
`phpmd`: no violations · Testo Unit suite: 139/139 passing throughout
(July 2026)
