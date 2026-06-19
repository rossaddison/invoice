[![Yii3](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/) 
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT) 
![stable](https://img.shields.io/static/v1?label=No%20Release&message=0.0.0&color=9cf)  
![Downloads](https://img.shields.io/static/v1?label=Avg/wk&message=1200&color=9cf)  
![Build](https://img.shields.io/static/v1?label=Build&message=Passing&color=66ff00)
![Dependency Checker](https://img.shields.io/static/v1?label=Dependency%20Checker&message=Passing&color=66ff00) 
![Static Analysis](https://img.shields.io/static/v1?label=Static%20Analysis&message=Passing&color=66ff00)
![Psalm Level](https://img.shields.io/static/v1?label=Psalm%20Level&message=1&color=66ff00)
[![type-coverage](https://shepherd.dev/github/rossaddison/invoice/coverage.svg)](https://shepherd.dev/github/rossaddison/invoice)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=rossaddison_invoice&metric=coverage)](https://sonarcloud.io/summary/new_code?id=rossaddison_invoice)
[![PHP-CS-Fixer](https://img.shields.io/badge/php--cs--fixer-enabled-blue?logo=php)](https://github.com/FriendsOfPHP/PHP-CS-Fixer)
![Stats](https://github-readme-stats.vercel.app/api?username=rossaddison)
![Hosted by Vultr](https://img.shields.io/badge/hosting-vultr%20(yii3i.online)-blue?logo=vultr&style=flat-square)

(Place the contents of this download into the yii3-i invoice folder or run as a
 separate repository.)

# Yii3-i (Rossaddison/Invoice)

A professional Open Source E-Invoicing System for PHP (Yii3) with UBL 2.4 and
 Peppol support.

## Features

### Vat Support

### Multi-Currency Billing

### Peppol UBL 2.4 E-Invoicing
Automated generation and transmission of compliant UBL 2.4 documents via the
 Peppol network.

**Recent Implementations**

[Dev Tools Web UI — `m.bat` / `m.php`](docs/DEV_TOOLS_WEB_UI.md) — `m.bat` replaced by a PHP built-in web server (`php -S 127.0.0.1:8099 m.php`) eliminating all batch-file stdin issues; 16 category submenus (Psalm, Composer, Node, TypeScript, Angular, Testing, Snyk, PHP-CS-Fixer, PHPCS, Rector, SonarCloud, Yii, GitHub, Peppol, Benchmarks, System); streaming output via `proc_open()` + `ReadableStream`; ANSI colour rendering in browser; Bootstrap 5.3 dark theme; session-stored SonarCloud/GitHub tokens; Snyk Resolved Vulnerabilities Index (SQLite, seeded from `.snyk`, committed to repo, CWE advisory links); SQLite setup guide distinguishing CLI PHP from WAMP Apache PHP; 16 local SVG menu icons (Simple Icons for brands, Bootstrap Icons for generics, official Yii3 logo in brand colours) served statically via built-in server passthrough (June 2026)

[AS4 Access Point — Bilateral & Peppol Roadmap](docs/AS4_BILATERAL_ROADMAP.md) — Living roadmap for the native AS4 Access Point built in PHP; outbound stack complete (`As4RetryEngine`, `CycleOrmAs4MessageRepository`, `As4RetryPolicyInterface`, `As4SenderInterface`, ebMS3 signal detection, atomic concurrency claim, 15 PHPUnit tests); Phase 1 plans the inbound pipeline (`As4Receiver`, `As4SignatureVerifier`, `As4DuplicateDetector`, `As4ReceiptGenerator`, `As4ReceiveController`) for bilateral testing between `localhost` and `yii3i.online` without Peppol PKI; Phase 2 maps the small delta to a full Peppol 4-corner Access Point (SMP lookup already built, Peppol-issued certificate + SML registration + EFTIA conformance remaining) (June 2026)

[Oxalis Access Point — Localhost Setup](docs/OXALIS_LOCALHOST_SETUP.md) — Phase A: `docker-compose up oxalis-mock` runs a WireMock stub on port 8181 (no certificate needed, works today); Phase B: real Oxalis AS4 container on port 8080 once a test certificate is obtained from a Peppol AP provider; inbound callback wired to `POST /peppol/inbound/delivery`; all four env vars documented in `.env.example` (June 2026)

[Invoice Index — Workflow Type Badges](docs/INV_INDEX_WORKFLOW_BADGES.md) — Always-visible emoji badge column in `InvsListWidget` distinguishes standalone invoices (📄 grey), quote-derived invoices (💬→📄 teal), and full Peppol observer-workflow invoices (🔀 blue, `so_id` set); tooltips show full chain in UI language; `peppol_workflow` added to Group By dropdown with three named groups; Psalm errorLevel 1 clean (June 2026)

[Peppol Code-List Currency Check](docs/PEPPOL_CODELIST_CURRENCY_CHECK.md) — `bin/check-peppol-codelists.php` queries the GitHub Commits API for each of the five VEFA XML files in `DownloadedXml/` and compares the last upstream commit date against the recorded download date; green = UP-TO-DATE, red = STALE; optional `GITHUB_TOKEN` raises rate limit from 60 to 5 000/hr; exit code 1 when stale (CI-friendly); exposed via `m.bat [27]`, `make peppol-check`, and `composer run peppol:check` (June 2026)

[Peppol XML Code-List Loaders — `PeppolArrays` refactor](docs/PEPPOL_XML_CODE_LIST_LOADERS.md) — Six S138 violations eliminated by replacing ~2 900 lines of hardcoded PHP arrays with a shared `private static loadVefaCodeList(string $filename)` that reads OpenPEPPOL VEFA-format XML at runtime via `DOMXPath`; `Yiisoft\Aliases` resolves `@peppol` to `__DIR__`; five XML files in `DownloadedXml/` cover UNCL7143, ISO 6523 ICD, UNCL7161, UNCL5305, and EAS; six dead data files removed via `git rm`; `psalm.xml` `UnusedVariable` block updated; `electronicAddressScheme()` view key references migrated from `code`/`description` to `Id`/`Name`; upstream URL + quarterly-update note in `resources/peppol/uncl2005.php` establishes currency trail for UNCL2005 subset (June 2026)

[SonarQube S107 — `QuoteController` + `SalesOrderController`](docs/SONARQUBE_S107_APPLICATION_SERVICE.md) — `QuoteController` 32p → 6p via `QuoteControllerBaseDeps` (7p), `QuoteControllerInvDeps` (6p), `QuoteControllerQuoteDeps` (6p), `QuoteControllerSoDeps` (5p), `QuoteControllerInfraDeps` (6p), `QuoteControllerUIDeps` (2p); `SalesOrderController` 17p → 3p via `SoControllerBaseDeps` (7p), `SoControllerInvDeps` (5p), `SoControllerMiscDeps` (3p); 2 dead params dropped from `SalesOrderController` (`InvAmountService`, `IIACS`); all properties re-declared at class level — zero trait/method files changed; Psalm errorLevel 1 clean (June 2026)

[SonarQube S107 — `InvController::__construct`](docs/SONARQUBE_S107_APPLICATION_SERVICE.md) — 23-param constructor reduced to 4 params via `InvControllerBaseDeps` (7p: webService/userService/translator/webViewRenderer/session/sR/flash), `InvControllerServiceDeps` (6p: invAllowanceChargeService/invAmountService/invService/invCustomService/invItemService/invTaxRateService), `InvControllerInfraDeps` (6p: factory/htmlResponseFactory/logger/mailer/urlGenerator/delRepo), `InvControllerUIDeps` (4p: aciis/formFields/buttonsToolbarFull/customFieldProcessor); all 16 properties re-declared at class level and assigned from deps — zero trait files changed; Psalm errorLevel 1 clean (June 2026)

[SonarQube S107 — `QuoteEmailStage2Deps` + `QuoteEmailStage0Deps` sub-split](docs/SONARQUBE_S107_APPLICATION_SERVICE.md) — 14-param `QuoteEmailStage2Deps` reduced to 3 params; 10-param `QuoteEmailStage0Deps` reduced to 2 params; shared `QuoteEmailCustomDeps` (6p: ccR/cfR/cvR/icR/pcR/qcR) used by both stages; `QuoteEmailStage0EntityDeps` (4p: etR/qR/socR/uiR); `QuoteEmailStage2CoreDeps` (5p: gR/iaR/iR/socR/uiR); `QuoteEmailStage2RelationDeps` (3p: qaR/qR/soR); `Quote/Trait/Email.php` updated throughout; Psalm errorLevel 1 clean (June 2026)

[SonarQube S107 — `InvEmailStage2Deps` sub-split](docs/SONARQUBE_S107_APPLICATION_SERVICE.md) — 15-param `InvEmailStage2Deps` reduced to 3 params by introducing `InvEmailCoreDeps` (6p: iR/iaR/icR/islR/gR/uiR), `InvEmailCustomDeps` (6p: ccR/cfR/cvR/pcR/qcR/socR), `InvEmailRelationDeps` (3p: qaR/qR/soR); `InvEmailService` and `Email.php` trait updated throughout; Psalm errorLevel 1 clean (June 2026)

[SonarQube S107 — `SalesOrderViewService`](docs/SONARQUBE_S107_APPLICATION_SERVICE.md) — 23-param `SalesOrderViewDependencies` replaced by four sub-groups + `SalesOrderViewService` (4p): `SoViewCoreDeps` (6p: soR/soaR/soiR/sotrR/socR/soiaR), `SoViewItemDeps` (5p: piR/pR/taskR/trR/uR), `SoViewMetaDeps` (5p: cfR/cvR/gR/invRepo/settingRepository), `SoViewRelationDeps` (6p: acsoiR/acsoR/dR/qR/ucR/uiR); dead `cR` param dropped; `SalesOrderViewDependencies` deleted; Psalm errorLevel 1 clean (June 2026)

[SonarQube S107 — `InvEmailService` and `InvViewService`](docs/SONARQUBE_S107_APPLICATION_SERVICE.md) — Two S107 Application Services added: `InvEmailService` (7p) consolidates email send logic from the `Email` trait; `InvEmailStage2Deps` trimmed from 22 to 15 params by dropping 7 dead repos never referenced in `Email.php`; `InvEmailStage1Data::$from: array` replaced with typed `$fromEmail`/`$fromName` string fields; PSR-7 body extraction fixed using `/** @var array $body['MailerInvForm'] */` (matches established Quote email trait pattern — avoids `MixedAssignment`/`MixedArrayAccess` without `@psalm-suppress`); `InvViewService` (5p) replaces the 29-param `InvViewDeps` by splitting into five sub-groups — `InvViewCoreDeps` (6p: iR/iaR/icR/irR/pymR/gR), `InvViewItemDeps` (6p), `InvViewMetaDeps` (6p), `InvViewAllowanceDeps` (5p), `InvViewRelationDeps` (6p); `InvViewDeps` deleted; `View.php` trait updated throughout; Psalm errorLevel 1 clean (June 2026)

[SonarQube — S1142, S1448, S131, S3776 fixes](docs/SONARQUBE_S107_APPLICATION_SERVICE.md) — Five violations resolved: `InvPdfService::generateHtml` reduced from 4 returns to 2 by combining null guards; `PaymentInformationController` switch gained `default: break;` (S131); `brainTreeInForm` reduced from 4 returns to 3 by extracting `initializeBraintree(): ?array`; `ProductController::add()` cognitive complexity dropped from 36 to 2 by extracting `handleAddPost` and `saveProductCustomFields`; `SalesOrdersListWidget` dropped from 30 methods to 18 by extracting `SalesOrdersColumnBuilder` (8 column builders) and `SalesOrdersGroupingRenderer` (4 grouping helpers) into the same `Widget\` namespace — Psalm errorLevel 1 clean throughout (June 2026)

[SonarQube S107 — `customValues()` contract fix](docs/SONARQUBE_S107_APPLICATION_SERVICE.md) — Runtime fix applied to `InvPdfService`, `QuotePdfService`, and `SalesOrderPdfService`: `customValues()` must store entity objects (`$values[] = $entity`), not extracted strings — `CustomValuesHelper::formValue()` calls `->reqCustomFieldId()` on each array element and never reads the array keys; array keys are irrelevant (June 2026)

[SonarQube S107 — `SalesOrderPdfService` and `PdfHelper.php` deleted](docs/SONARQUBE_S107_APPLICATION_SERVICE.md) — `PdfHelper::generateSalesorderPdf` (17p) moved into `SalesOrderPdfService`; `SalesOrderController::pdf` reduced from inline `PdfHelper` construction + `SalesOrderViewDependencies` to 2 params; `SalesOrderPdfCoreDeps` (4p), `SalesOrderPdfDocDeps` (3p — no delivery location), `SalesOrderPdfItemDeps` (4p); `PdfHelper.php` deleted entirely — zero callers remained after all three PDF services extracted; dead `$pdfhelper` property removed from `MailerHelper`; bug fix: sales order PDF now uses client language correctly (old `getPrintLanguage()` always returned `'English'` for sales orders) (June 2026)

[SonarQube S107 — `QuotePdfService` and Quote Email Deps](docs/SONARQUBE_S107_APPLICATION_SERVICE.md) — Seven S107 violations eliminated (`PdfHelper::generateQuotePdf` 16p, `PdfTrait::pdf` 15p, two `pdfDashboard*` at 16p each, `emailStage0` 12p, `emailStage1` 28p, `emailStage2` 21p); `QuotePdfCoreDeps`, `QuotePdfDocDeps`, `QuotePdfItemDeps` all ≤6 params; `QuotePdfService` exposes clean `generate()`, `findQuote()`, and `uiR()` methods and is resolved automatically by Yii3 DI; `QuoteEmailStage0Deps`, `QuoteEmailStage1Data`, `QuoteEmailStage2Deps` consolidate email-stage wiring; `PdfHelper::generateQuotePdf` deleted; `QuoteController` no longer constructs `PdfHelper`; `QuoteEmailStage1Data::$from: array` replaced with typed `$fromEmail`/`$fromName` string fields (Psalm-enforced — no `@psalm-suppress`) (June 2026)

[SonarQube S107 — DDD Application Service Pattern (`InvPdfService`)](docs/SONARQUBE_S107_APPLICATION_SERVICE.md) — Three S107 violations eliminated (`InvPdfDeps` 17p, `generateInvPdf` 19p, `generateInvHtml` 18p) by replacing them with a proper Application Service; `InvPdfCoreDeps`, `InvPdfDocDeps`, and `InvPdfItemDeps` group related repos into ≤6-param sub-deps classes; `InvPdfService` exposes a clean 3-param `generate()` method and is resolved automatically by Yii3 DI; `PdfHelper` reduced by ~242 lines; `PdfTrait` and `Email` trait now inject the service directly; documents the reusable pattern for the remaining 135 S107 violations (`QuotePdfService`, `SalesOrderPdfService`, `InvEmailService`, etc.) (June 2026)

[MTD VAT — Purchase Entries & Bridging Software Strategy](docs/MTD_VAT_PURCHASE_ENTRIES.md) — `PurchaseEntry` lightweight entity for supplier invoice recording; CSV bridging import; VAT100 Box 4 and Box 7 auto-populated from `PurchaseEntryRepository::repoVatTotalsForPeriod()`; why `inv_type` on `Inv` was rejected; HMRC Developer Hub sandbox route map; `PurchaseEntryVatAggregator` extracted from repository so summation logic (Box 4 input VAT + Box 7 purchases ex-VAT, rounded to 2dp) is unit-testable without ORM infrastructure; 11 PHPUnit tests cover empty period, rounding, zero-rated supplies, large amounts, mixed VAT rates, and generator iterables (June 2026)

[PHPUnit — Bypass Finals & 100 % PurchaseEntry Coverage](docs/PHPUNIT_BYPASS_FINALS_COVERAGE.md) — `dg/bypass-finals` added as a dev dependency so `createMock()` can double `final` repository classes; `Tests/bootstrap.php` calls `DG\BypassFinals::enable()` before autoload; `phpunit.xml.dist` bootstrap updated; 37-test `PurchaseEntryServiceTest` covering `saveEntry` field mapping, date parsing, `created_at` guard, `deleteEntry` delegation, and all four VAT quarter labels for UK / calendar-year / Australian tax years; all five PurchaseEntry classes now at 100 % line coverage (June 2026)

[Purchase Entry — VAT Quarter Grouping, Locale Defaults & Index UI](docs/PURCHASE_ENTRY_QUARTER_GROUPING.md) — GridView + HTMX partial swap on `purchaseentry/index`; group-by toggle (All / By Month / By Supplier / By Quarter); VAT quarter key derived from `this_tax_year_from_date_*` settings using modular arithmetic across the year boundary; disabled "By Quarter" button with flash warning when tax year not configured; breadcrumbs linking directly to `setting/tabIndex?active=taxes#settings[field]` with ⏳ tooltip when unset; locale-defaults page covering ~50 countries with one-click Apply (POST saves month + day, preserves existing year); all `@psalm-suppress` removed; S1131/S1192/S3358 SonarQube violations resolved (June 2026)

[AllowanceCharge Amount Validation and View Toggle](docs/ALLOWANCE_CHARGE_AMOUNT_VALIDATION.md) — Cross-field validation on `AllowanceChargeForm` via inline `Callback` closures in `getRules()`; enforces `MFN × base ÷ 100 = amount` in percentage mode and rejects non-positive fixed amounts; dynamic formula in error message; two translation keys added; `AllowanceChargeToggleHandler` TypeScript class reads `data-ac-templates` from the select element and switches `quoteitemallowancecharge` and `quoteallowancecharge` forms between fixed-amount and variable (base + live formula) mode without page reload (June 2026)

[Peppol Schematron Validator — Route 1](docs/PEPPOL_SCHEMATRON_VALIDATOR_ROUTE1.md) — `SchematronRuleRunner` evaluates `PEPPOL-EN16931-UBL.sch` directly against the invoice DOM at runtime; XPath 2.0 subset implemented in PHP (`normalize-space`, `substring`, `translate`, `castable as`, sequence constructors, `for…return`, axis `::` steps); ten `u:` checksum functions wired from existing `PeppolValidator` methods; hand-written rule methods gated off when `.sch` file present (June 2026)

[Peppol Schematron Code Generation](docs/PEPPOL_SCHEMATRON_CODEGEN.md) — PHP/TypeScript/Scala validator files generated from the official Peppol BIS Billing 3.0 Schematron `.sch` file; `bin/generate-php-validators.php`, `bin/generate-ts-validators.php`, `bin/generate-scala-validators.php`; VO layer; PHP upgrade path replacing `PeppolValidator` XPath methods with a hydrator + generated functions (June 2026)

[TypeScript Vitest Coverage](docs/TYPESCRIPT_VITEST_COVERAGE.md) — Vitest + jsdom + v8 coverage wired into CI for `inv-index.ts`, `list-utils.ts`, and `quote-index.ts`; `phpunit.xml.dist` case fix for Linux CI; PHP and TS coverage fed to SonarCloud; coverage badge added (May 2026)

[Pre-commit TypeScript IIFE Build Hook](docs/PRE_COMMIT_TYPESCRIPT_BUILD.md) — `.githooks/pre-commit` rebuilds both IIFE bundles (≈ 20 ms via esbuild) and auto-stages the output before every commit so the compiled bundle is never stale relative to TypeScript source; `prepare` script in `package.json` runs `git config core.hooksPath .githooks` automatically after `npm install` on a fresh clone; esbuild invoked via `node node_modules/esbuild/bin/esbuild` to bypass missing `.bin/` shim on Windows (June 2026)

[SonarCloud First Gate](docs/SONARCLOUD_FIRST_GATE.md) — SonarCloud runs as a standalone job before the PHP matrix build; `needs: [sonar]` blocks all four runners until the quality gate passes; AI-assisted contributions must self-audit before commit (May 2026)

[BACS Quick Pay](docs/BACS_QUICK_PAY.md) — One-off bank-transfer modal on the invoice guest page: bank details card, per-invoice QR codes, copy-to-clipboard buttons, `BacsPaymentService`, 38 new PHPUnit tests; fixed gateway CDN script ordering, CSP `https://` violations, and missing `$bacsUnpaidInvs` parameter bug (May 2026)

[PCI Gateway Asset Loading](docs/PCI_GATEWAY_ASSET_LOADING.md) — Stripe/Braintree/Amazon Pay CDN scripts moved to `<head>` (`jsPosition = POSITION_HEAD`) to guarantee they execute before the IIFE; protocol-relative `//` URLs replaced with explicit `https://` to satisfy CSP on localhost (May 2026)

[Sonarcloud CLI](docs/SONARCLOUD_CLI.md) — Setup local SonarCloud integration in VS Code (May 2026)

[Sonarcloud Setup](docs/SONARCLOUD_SETUP.md) — Setup local SonarCloud integration (May 2026)

[Sonarqube IDE with m.bat/Makefile](docs/SONARQUBE_IDE_SETUP.md) — Sonarqube IDE Setup (May 2026) 

[SCSS Architecture](docs/SCSS_ARCHITECTURE.md) — two independent SCSS trees (light and dark); full import chain from `_yii3i_variables.scss` through Bootstrap 5 source to `_core.scss` and `_custom_styles.scss`; how Bootstrap `!default` variable overrides work; file roles; when and how to rebuild compiled CSS (May 2026)

[FontAwesome to Bootstrap Icons](docs/FONT_AWESOME_TO_BOOTSTRAP_ICONS.md) — complete removal of FontAwesome from the asset pipeline: ~1.1 MB of font files deleted, dead SCSS rules removed from `_core.scss` and `_welcome.scss`, compiled FA rules removed from `style.css` and `utilities.css`, `$fa-font-path` removed from `_yii3i_variables.scss`; Bootstrap Icons (`bi bi-*`) confirmed as sole icon library; outstanding dark-theme SCSS import noted (May 2026)

[CSS Variables Reorganization](docs/CSS_VARIABLES_REORGANIZATION.md) — plan to break monolithic `style.css` into six purpose-specific files (`variables.css`, `base.css`, `layout.css`, `components.css`, `utilities.css`, `overrides.css`); current live vs. planning-stage status; remaining migration items; original source line-range mapping (May 2026)

[PDF Bootstrap 5 Shim](docs/PDF_BOOTSTRAP5_SHIM.md) — `custom-pdf.css` Bootstrap 5 utility shim for mPDF replacing `kv-mpdf-bootstrap.min.css`; full BS5 class inventory (typography, spacing, tables, colour, borders); `templates.css` fixed (`clearfix::after` removed, `:nth-child` → `.odd`/`.even`, `th.text-end`); all five PDF templates updated (`text-end`, `m-0`, `item-table`, visible `<thead>`, odd/even row shading); watermark src bug fixed in `overdue.php`; stray `}` removed from `quote.php` and `salesorder.php` (May 2026)

[Lighthouse Performance Audit](docs/LIGHTHOUSE_CHROME.md) — How to run a Lighthouse audit from Chrome DevTools or the CLI against an authenticated page; performance score 68 → 95 via Apache compression modules, asset deduplication, CSS deferral, Amazon Pay JS conditionalisation, image resizing, and N+1 settings-query fix (May 2026)

[Bootstrap 3 CSS Removal](docs/BOOTSTRAP3_CSS_REMOVAL.md) — incremental removal of InvoicePlane's legacy Bootstrap 3 styles from `style.css` (custom section reduced 32 %, 966 → 653 lines); 484 `form-group` → `mb-3`, 23 `dropdown-button` → `dropdown-item`, 12 `input-sm` → `form-control-sm` replacements across 120 view files; SonarCloud duplicate-selector warnings eliminated by excluding `src/Invoice/Asset/**`; `.table { font-size: 0.25rem }` bug fix (4 px invisible text); `body *:focus { outline: none !important }` removed (WCAG 2.1 accessibility) (May 2026)

[Bootstrap 3 → Bootstrap 5 Migration Guide](docs/BS3_TO_BS5_MIGRATION_GUIDE.md) — PHP-community field guide documenting 16 categories of migration difficulty: class renames (grid, typography, buttons, forms, panels→cards, navbar, labels→badges, tables), data-attribute prefix change (`data-` → `data-bs-`), JS API (`$(el).modal()` → `bootstrap.Modal.getOrCreateInstance(el)`), mPDF CSS 2.1 limitations (no `var()`, no flexbox, no `:nth-child`), FontAwesome → Bootstrap Icons, Yii3 widget-layer BS3 class output, SCSS `!default` override order, `input-group` pitfall, SonarCloud false-positive suppression, and 10 things that surprised Claude most during the migration (May 2026)

[Bootstrap 5 Table Mobile Stacking Fix](docs/BOOTSTRAP5_TABLE_MOBILE_STACKING.md) — `table, thead, tbody, th, td, tr { display: block }` ported back into `layout.css` at `@media (max-width: 767px)`; restores the vertical cell stacking that existed in the Bootstrap 3 era and was removed when BS3 CSS was stripped; `td[data-label]` scoping prevents 50 % padding gap on GridView cells that do not emit `data-label` attributes (May 2026)

[Bootstrap 5 Tooltip Initialisation Fix](docs/BOOTSTRAP5_TOOLTIP_INIT_FIX.md) — `BootstrapJsOnlyAsset` registered before `InvoiceNodeModulesAsset` so `window.bootstrap` is defined when the IIFE runs; dead `DOMContentLoaded` wrapper removed from `initializeTooltips()`; bare `bootstrap` identifier replaced with `(window as any).bootstrap`; `new Tooltip()` replaced with `Tooltip.getOrCreateInstance()` to prevent duplicate instances (May 2026)

[Bootstrap 5 Settings Tabs & HTMX Page-Size Selector](docs/BOOTSTRAP5_SETTINGS_HTMX_PAGE_SIZE.md) — BS5 tab accessibility pass on all settings partials (`role="tablist/tab/tabpanel"`, `aria-*`); `form-select` applied to 16 partial files; 19 inline label style tags consolidated to `overrides.css`; page-size navbar buttons save via `hx-get` + `hx-swap="none"` then refresh `#main-area` via `fetch`+`DOMParser`+`replaceWith` without redirect or full reload (May 2026)

[Global Page Size Navbar Selector](docs/GLOBAL_PAGE_SIZE_NAVBAR.md) — `PageSizeLimiter` widget removed from 27 views and 3 widget classes; replaced by a single `<select>` in the invoice layout navbar backed by a TypeScript `PageSizeHandler`; `BootstrapJsOnlyAsset` hash-collision fix; dark mode removed; `CustomFieldRepository` PSR-4 path fix (May 2026)

[Onboarding](docs/ONBOARDING.md) — `Stacking Rule layout fix in src/Invoice/Asset/invoice/css/layout.css (May 2026)

[Performance Benchmarks](docs/PERFORMANCE_BENCHMARKS.md) — custom `hrtime()` benchmark suite tracking Yii3's four core speed-critical components over the repo's lifespan: DI container (singleton cache, 5-level dependency chain), injector auto-wire (reflection-cache vs uncached), FastRoute URL matcher (50-route table, parametrised, worst-case, 404), and string helpers (StringHelper, Inflector, WildcardPattern, CombinedRegexp); results accumulate in `benchmarks/results/history.json`; interactive Chart.js dashboard with trend arrows, suite filters, run selector, and ops/sec bars; GitHub Actions records a run every Monday at 02:00 UTC with OPcache JIT enabled (May 2026)

[FastRoute Dispatch Cache](docs/ROUTER_CACHE.md) — `UrlMatcher` PSR-16 cache wiring: `CacheInterface` → `FileCache` → `runtime/cache/routes-cache`; cache disabled in `dev` via `common/params.php`, enabled in `prod` via `environments/prod/params.php`; `YII_ENV` environment variable drives which params file is loaded; deployment rule to clear `runtime/cache/routes-cache*` on every route change; benchmark context explaining why the Windows dev figures include compilation overhead that disappears in production (May 2026)

[PHPUnit Entity Test Migration](docs/PHPUNIT_ENTITY_TEST_MIGRATION.md) — 34 new PHPUnit entity tests across 6 batches; 36 Codeception unit tests migrated to `PHPUnit\Framework\TestCase`; 26 `createMock()` calls replaced with `createStub()`; 3 pre-existing `DateTime`/`DateTimeImmutable` entity bugs uncovered (May 2026)

[Peppol SMP Lookup](docs/PEPPOL_SMP_LOOKUP.md) — participant discovery via SML DNS → SMP HTTP → XML parse; `SmpResolver` supports both PEPPOL SMP 1.0 and BDX SMP 1.0 namespaces; `SmpEndpoint` value object; `PEPPOL_SML_ZONE` and `PEPPOL_SMP_BASE_URL` env vars; 10-test PHPUnit suite; completes Phase 1 of the Peppol access point (May 2026)

[HTMX Caching](docs/HTMX_CACHING.md) — `Vary: HX-Request` strategy for CDN/proxy caches; browser cache headers for XHR GET requests; why POST filter forms are exempt; Nginx cache-key configuration; current project status checklist (May 2026)

[Peppol Send via Oxalis](docs/PEPPOL_SEND_OXALIS.md) — end-to-end implementation of "Send via Peppol (Oxalis)" on the invoice view; `PeppolMessage` Cycle ORM entity; `PeppolSendService` PSR-18 HTTP wrapper; QUEUED→SENT→FAILED status lifecycle; WireMock Phase A dev setup; Yii3 DI config auto-loaded from `OXALIS_BASE_URL` env var (May 2026)

[Peppol Oxalis Connect](docs/PEPPOL_OXALIS_CONNECT.md) — Phase B real-Oxalis wiring: `PeppolSendService` switched from JSON to `multipart/form-data`; `iso6523-actorid-upis::` and `cenbii-procid-ubl::` scheme prefixes; `PEPPOL_SENDER_ID` env var; HTTP 4xx/5xx mapped to FAILED; `PeppolInboundController` delivery callback; `DocumentTypeId` busdox caveat; Phase B checklist (May 2026)

[Oxalis Integration Plan](docs/OXALIS_INTEGRATION.md) — phased plan for self-hosted Peppol AS4 transport via Oxalis alongside the existing Storecove connector; cost comparison (managed AP vs. self-hosted); `PeppolMessage` state machine; `PeppolSendService` wrapping Oxalis REST API; inbound callback controller; SMP registration and OpenPeppol certification costs (May 2026)

[Peppol Access Point PHP Guide](docs/PEPPOL_ACCESS_POINT_PHP_GUIDE.md) — architectural overview for building a Peppol access point in PHP; AS4/WS-Security delegation strategy; recommended PHP libraries (`xmlseclibs`, `sabre/xml`); phased delivery from minimal outbound-only AP through full certification (May 2026)

[HTMX Invoices List Widget](docs/INVS_LIST_WIDGET.md) — sort, filter, pagination, and group-by on the invoice list using HTMX 2.x; `InvsListWidget` wraps `GridView` with `hx-boost`; partial `outerHTML` swap of `#InvsGridView`; edit-column read-only/disable-read-only matrix; sent-log columns; group-by with paid/balance totals per group header; 44-test PHPUnit suite (May 2026)

[HTMX Quotes List Widget](docs/QUOTES_LIST_WIDGET.md) — sort, filter, pagination, and group-by on the quote list using HTMX 2.x; `QuotesListWidget` wraps `GridView` with `hx-boost`; partial `outerHTML` swap of `#QuotesGridView`; group-by with collapsible headers; SonarQube S138/S3776/S107 refactoring (May 2026)

[HTMX User Index](docs/HTMX_USER_INDEX.md) — sort, pagination, and page-size selector on the user list using HTMX 2.x; `UsersListWidget` wraps `GridView` with `hx-boost` on sort and pagination links; partial `outerHTML` swap of `#UsersGridView` (May 2026)

[HTMX Quote Item Entry](docs/HTMX_QUOTE_ITEM_ENTRY.md) — in-place product and task line item addition on the quote view using HTMX 2.x; dedicated `QuoteItemHtmxController` with `quoteitemhtmx/addProduct` and `quoteitemhtmx/addTask` POST-only routes; no full page reload; loading spinner with auto-reset on success; htmx 2.0.10 bundled into the TypeScript iife via npm (May 2026)

[RBAC DB Storage](docs/RBAC_DB_STORAGE.md) — assignments migrated from `resources/rbac/assignments.php` to `yii_rbac_assignment` MySQL table via `yiisoft/rbac-cycle-db`; items remain PHP-file backed (May 2026)

[Cycle ORM Transactions](docs/CYCLE_ORM_TRANSACTIONS.md) — `InvService::withTransaction()` wraps invoice create, credit, copy, and invoice-to-invoice confirm in atomic database transactions; orphaned rows on partial failure are no longer possible (May 2026)

[Invoice Soft Delete & Trash](docs/INVOICE_SOFT_DELETE_TRASH.md) — Trash page listing archived invoices with per-row restore; `restore()` method on `Inv`; explicit `WHERE deleted_at IS NULL` on all 48 `InvRepository` query methods; `InvDeletionService` removed; 17-test PHPUnit suite covering soft-delete and restore lifecycle (May 2026)

[InvForm::show() Pattern](docs/INVFORM_SHOW_PATTERN.md) — Bug fix: `inv/view` status dropdown always showed Draft because `new InvForm()` defaults `status_id = 1`; replaced with `InvForm::show($inv)` which copies all entity fields into the form (May 2026)

[Cycle ORM Entity Behaviors](docs/CYCLE_ORM_BEHAVIORS.md) — SoftDelete on Inv (audit-safe deletion) and Hook on Client (auto-sync client_full_name on create/update) (May 2026)

[Cycle ORM Database Indexing](docs/CYCLE_ORM_INDEXING.md) — #[Index] attributes applied to Inv, Quote, SalesOrder, Product, Client, and Family entities; rules for choosing sort, filter, FK, and unique indexes (May 2026)

[Family Drag-and-Drop Street Order](src/docs/FAMILY_DRAG_DROP_STREET_ORDER.md) — reorder streets for a cleaning run via native HTML5 drag-and-drop, persisted automatically on drop (May 2026)

[Telegram Payment Providers](docs/TELEGRAM_PAYMENT_PROVIDERS.md) — native Telegram invoicing via [phptg/bot-api](https://github.com/phptg/bot-api) by [Sergei Predvoditelev (vjik)](https://github.com/vjik) (May 2026)

[Adapting forms for DDD](docs/FORMS_DDD_.md) (April 2026)

[Entity to Infrastructure Migration Process](docs/ENTITY_TO_INFRASTRUCTURE_PROCESS.md) (April 2026)

[Architecture Domain Application Infrastructure](docs/ARCHITECTURE_DOMAIN_APPLICATION_INFRASTRUCTURE.md) (April 2026)

[Cycle-Orm Psalm Lifecycle Safe Entities](docs/CYCLE_ORM_PSALM_LIFECYCLE_SAFE_ENTITIES.md) (April 2026)

[Language Flag Dropdown](docs/LANGUAGE_FLAG_DROPDOWN.md) (April 2026)

[Settings Tabs Improvements](docs/SETTINGS_TABS_IMPROVEMENTS.md) (April 2026)

[Soletrader Layout Improvements](docs/SOLETRADER_LAYOUT_IMPROVEMENTS.md) (April 2026)

[Sidebar Improvements](docs/SIDEBAR_IMPROVEMENTS.md) (April 2026)

[Eslint Sonarqube Build Session](docs/ESLINT_SONARQUBE_BUILD_SESSION.md) (March 2026)

[Avoiding RBAC Mutation](docs/AVOIDING_RBAC_MUTATION.md) (March 2026)

[Php 8.4 Alpine Setup](docs/PHP84_ALPINE_SETUP.md) (March 2026)

[Future Peppol Mena](docs/FUTURE_PEPPOL_MENA.md) (March 2026)

[Future Peppol Nigeria](docs/FUTURE_PEPPOL_NIGERIA.md) (March 2026)

[Future Peppol Republic of South Africa](docs/FUTURE_PEPPOL_RSA.md) (March 2026)

[Future Peppol UK](docs/FUTURE_PEPPOL_UK.md) (March 2026)

[Mobile-Desktop Toggle Toolbar](docs/MOBILE_DESKTOP_TOOLBAR.md) (March 2026)

[ssl.conf explained](docs/SSL_CONF_EXPLAINED.md) (March 2026)

[Why Apache?](docs/WHY_APACHE.md) (March 2026)

[Apache2 vs. Nginx](docs/APACHE_VS_NGINX.md) (March 2026)

[Vultr Alpine Security](docs/VULTR_ALPINE_SECURITY.md) (March 2026)

[WSL to Alpine Deployment](docs/WSL_TO_ALPINE_DEPLOYMENT.md) — step-by-step guide for pulling updates from GitHub to a live Alpine/Apache2 server via WSL; git stash/pop workflow; file ownership (`chown apache:apache`); session save-path configuration; Psalm on server; SCP file transfer; deploy script; OAuth2 and RBAC debugging commands (May 2026)

[Alpine Linux CVE-2026-31431 Remediation](docs/ALPINE_LINUX_CVE_2026_31431.md) — local privilege escalation via `algif_aead` kernel interface; immediate mitigation (`/etc/modprobe.d/disable-algif.conf`); kernel upgrade from 6.12.49 to 6.18.29 via `apk`; OpenRC Apache restart commands; post-reboot verification (May 2026)

[phpMyAdmin Vulnerabilities on Alpine](docs/PHPMYADMIN_VULNERABILITIES_ON_ALPINE.md) (March 2026)

[AuthController Production Environment Fix](docs/AUTHCONTROLLER_PROD_ENV_FIX.md) (March 2026)

[Content Security Policy Updates](docs/CONTENT_SECURITY_POLICY_UPDATES.md) — `.htaccess` CSP for Stripe/Braintree/Amazon Pay (March 2026); PSR-15 `ContentSecurityPolicyMiddleware` replacing it with `script-src 'self'` (no `unsafe-inline`/`unsafe-eval`), DI-injected policy string, payment-provider extensibility via `params.php`; response to htmx CodeQL alerts #194/#195 (June 2026)

[Email Setup for yii3i.online](docs/EMAIL_SETUP_SUMMARY.md) (March 2026)

[Automerge Renovate's dependency updates if tests pass](docs/RENOVATE_AUTOMERGE.md) (Feb 2026)

[Fraud Prevention Headers Bugfix](docs/FPH_BUTTON_EVENT_BINDING_BUG_REPORT.md) (Feb 2026)

[UK e-invoicing B2B/B2G 2029](docs/UK-E-INVOICING-MANDATE.md) (Jan 2026)

[PeppolValidator Integration.](docs/PEPPOL_VALIDATOR.md) (Jan 2026)

[CreditNote Integration.](docs/CREDIT_NOTE_WORKFLOW.md) (Jan 2026)

[VitePress Integration.](https://vitepress.dev/guide/getting-started) (Dec 2025)

[Prometheus Integration.](docs/PROMETHEUS_INTEGRATION.md) (Dec 2025)

[Prometheus Menu Integration.](docs/PROMETHEUS_MENU_INTEGRATION.md) (Dec 2025)

[Sonar Cloud Setup.](docs/SONARCLOUD_SETUP.md) (Nov 2025)

[SonarQube for IDE Setup](docs/SONARQUBE_IDE_SETUP.md) — VS Code Connected Mode setup; Windows startup timeout fix; JVM heap tuning; token generation explained in plain English; connectionId mismatch pitfall; Windows Defender exclusions (May 2026)

[SonarCloud CLI](docs/SONARCLOUD_CLI.md) — `sonar-issues.php` queries the SonarCloud API and prints all 4000+ issues in Psalm-style format with copyable file paths; filters by type, severity, PR, and hotspots; curl-based to bypass WAMP `allow_url_fopen` restriction; composer shortcuts included (May 2026)

[Netbeans ↔️ Vs Code: Sync Guide.](docs/NETBEANS_SYNC_GUIDE.md) (Dec 2025)
 
[Php Product Selection Workflow.](docs/PHP_PRODUCT_SELECTION_WORKFLOW.md) (Dec 2025)

[Security Commands.](docs/SECURITY_COMMANDS.md) (Dec 2025)

[Typescript Build Process.](docs/TYPESCRIPT_BUILD_PROCESS.md) — IIFE bundle 134.6 KB (ES2024, esbuild); full function-by-function reference for all 21 source modules; Bootstrap Icons migration; `icon-spin` CSS animation replacing `fa-spin` (May 2026)

[Typescript ES2023 Modernization.](docs/TYPESCRIPT_ES2023_MODERNIZATION.md) (Dec 2025)

[Typescript ES2024 Modernization.](docs/TYPESCRIPT_ES2024_MODERNIZATION.md) (Dec 2025)

[Typescript Go V7 Compatability Testing Guide.](docs/TYPESCRIPT_GO_V7_COMPATIBILITY_TESTING_GUIDE.md) (Dec 2025)

[Invoice Amount Magnifier using Angular.](docs/INVOICE_AMOUNT_MAGNIFIER.md) (Dec 2025)

[Family Commalist Picker using Angular.](docs/FAMILY_COMMALIST_PICKER.md) (Dec 2025)

[Cycle ORM HasOne and outerKey Issue.](docs/CYCLE_ORM_HASONE_OUTERKEY_ISSUE.md) (Jan 2026)

[Cycle ORM Join Optimization.](docs/CYCLE_ORM_JOIN_OPTIMIZATION.md) (Jan 2026)

[Cycle ORM Foreign Key Constraint Issue.](docs/CYCLE_ORM_FOREIGN_KEY_CONSTRAINT_ISSUE.md) (Jan 2026)

[Netbeans IDE 25-28 Guide.](docs/NETBEANS_IDE25_GUIDE.md) (Dec 2025)

[Tooltip Styles Configuration.](docs/TOOLTIP_STYLES_CONFIGURATION.md) (Jan 2026)

**Feature Specifics**

* Cycle ORM Interface using Invoiceplane type database schema. 
* Generate VAT invoices using mPDF. 
* Code Generator - Controller to views. 
* PCI-compliant payment gateway interfaces – Braintree Sandbox, Stripe Sandbox,
 and Amazon Pay integration tested. 
* Generate OpenPeppol UBL 2.4 Invoice 3.0.15 XML invoices – validated with Ecosio. 
* StoreCove API connector with JSON invoice. 
* Invoice cycle – Quote to Sales Order (with client's purchase order details) to Invoice.     
* Multiple language compliant – steps to generate new language files included. 
* Separate Client Console and Company Console. 
* Install with Composer.
* SonarQubeCloud / SonarCloud Code Analysis
* NetBeans 28 && Vs Code IDE Integration
* Eclipse IDE Integration
* SonarLint4NetBeans Plugin - Tools ... Options ... Miscellaneous ... php ... Rules

**Installing with Composer in Windows**
*````composer update````*

After a composer update, you'll need to manually:
1. Set `BUILD_DATABASE=true` in your `.env` file
2. Start the application to trigger table creation
3. Reset `BUILD_DATABASE=` for better performance

**Installing npm_modules**
* Step 1: Download node.js at https://nodejs.org/en/download
* Step 2: Ensure C:\ProgramFiles\nodejs is in environment variable path. Search ... edit the system environment variables
* Step 3: Run ````npm i```` in ````c:\wamp64\invoice```` folder. This will install @popperjs, Bootstrap 5, and TypeScript 
          into a new node_modules folder.
* Step 4: Keep your npm up to date by running, for example, ````npm install -g npm@10.8.1```` or just ````npm install -g````.

**Rebuilding the TypeScript bundle (invoice-typescript-iife.js)**

The compiled bundle at `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js` must be
rebuilt whenever TypeScript source files change (including `src/typescript/htmx.ts` which
bundles htmx 2.x). Run:

````npm run build:typescript````

Then copy the updated bundle to the Yii3-published assets directory so the browser
receives the new file without a cache clear:

````
src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js
  →  public/assets/<hash>/rebuild/js/invoice-typescript-iife.js
````

The `<hash>` folder name is derived from the asset source path and stays stable between
builds — check `public/assets/` for the existing folder name (e.g. `7246626a`).

**Recommended php.ini settings**
* Step 1: Wampserver ... Php {version} ... Php Settings ... xdebug.mode = off
* Step 2:                                               ... Maximum Execution Time = 360

**Installing the database in mySql**
1. Create a database in mySql called yii3_i.
2. The BUILD_DATABASE=true setting in the config/common/params.php file will ensure a firstrun setup of tables.
3. After the setup of tables, ensure that this setting is changed back to false otherwise you will get performance issues.

The c:\wamp64\yii3-i\config\common\params.php file line approx. 193 will automatically build up the tables under database yii3-i. 

````'mode' => $_ENV['BUILD_DATABASE'] ? PhpFileSchemaProvider::MODE_WRITE_ONLY : PhpFileSchemaProvider::MODE_READ_AND_WRITE,````

** If you adjust any Entity file you will have to always make two adjustments to**
** ensure the database is updated with the new changes and relevant fields: **
* 1. Change the BUILD_DATABASE=false in the .env file at the root to BUILD_DATABASE=true
* 2. Once the changes have been reflected and you have checked them via e.g. phpMyAdmin revert back to the original settings

Signup your first user using **+ Person icon**. This user will automatically be assigned the admin role. If you do not have an internet connection you will receive an email failed message
but you will still be able to login. 

You or your customer, signup the second user as your Client/Customer. They will automatically be assigned the observer role. 
If you do not have an internet connection you will get a failed message but if your admin makes the 'Invoice User Account' status active the user
will be able to log in.

If a user signs up by email, they will automatically be assigned as a client, and automatically be made active. 

**If your user has not signed up by email verification, to enable your signed-up Client to make payments:** 
* Step 1: Make sure you have created a client ie. Client ... View ... New
* Step 2: Create a Settings...Invoice User Account
* Step 3: Use the Assigned Client ... Burger Button ... and assign the New User Account to an existing Client.
* Step 4: Make sure they are active.
* Step 5: Make sure the relevant invoice has the status 'sent' either by manually editing the status of the invoice under Invoice ... View ... Options or by actually sending the invoice to the client by email under Invoice ... View ... Options.

**To install at least a service and a product, and a foreign and a non-foreign client automatically, please follow these steps:**

* Step 1: Settings ... View ... General ... Install Test Data ... Yes  AND   Use Test Date ... Yes
* Step 2: In the settings menu, you will now see 'Test data can now be installed'. Click on it.

**The package by default will not use VAT and will use the traditional Invoiceplane type installation providing both line-item tax and invoice tax** 

**If you require VAT based invoices, ensure VAT is setup by going to  Settings ... Views ... Value Added Tax and use a separate database for this purpose. Only line-item tax will be available.**

**Steps to translate into another language:** 

GeneratorController includes a function google_translate_lang ...          
This function takes the English app_lang.php array auto generated in 

````src/Invoice/Language/English```` 

and translates it into the chosen locale (Settings...View...Google Translate) 
outputting it to ````resources/views/generator/output_overwrite.```` 
* Step 1: Download https://curl.haxx.se/ca/cacert.pem into active c:\wamp64\bin\php\php8.1.12 folder.
* Step 2: Select your project that you created under https://console.cloud.google.com/projectselector2/iam-admin/serviceaccounts?pportedpurview=project
* Step 3: Click on Actions icon and select Manage Keys. 
* Step 4: Add Key.
* Step 5: Choose the JSON File option and download the file to src/Invoice/Google_translate_unique_folder.
* Step 6: You will have to enable the Cloud Translation API and provide your billing details. You will be charged 0 currency.
* Step 7: Move the file from views/generator/output_overwrite to eg. src/Invoice/Language/{your language}

**Xml electronic invoices - Can be output if the following sequence is followed:**

* a: A logged in Client sets up their Peppol details on their side via Client...View...Options...Edit Peppol Details for e-invoicing.

* b: A quote is created and sent by the Administrator to the Client.

* c: A logged in Client creates a sales order from the quote with their purchase order number, purchase order line number, and their contact person in the modal.

* d: A logged in Client, on each of the sales order line items, inputs their line item purchase order reference number, and their purchase order line number. (Mandatory or else exception will be raised).

* e: A logged in Administrator, requests that terms and conditions be accepted.

* f: A logged in Client accepts the terms and conditions.

* g: A logged in Administrator, updates the status of the sales order from assembled, approved, confirmed, to generate.

* h: A logged in Administrator can generate an invoice if the sales order status is on 'generate'

* i: A logged in Administrator can now generate a Peppol XML Invoice using today's exchange rates set up in Settings...View...Peppol Electronic Invoicing...One of From Currency and one of To Currency.

* j: Peppol exceptions will be raised.

## Renovate Auto-Merge Configuration

This repository uses Renovate Bot with auto-merge functionality enabled. The `platformAutomerge` is set to `true`, which enables GitHub's native auto-merge feature for Renovate pull requests.

### Auto-Merge Requirements

**IMPORTANT:** Before any auto-merge occurs, all required checks must pass, including:

#### ✅ Required Tests

- **Psalm Static Analysis** - Must pass successfully
- All other CI/CD pipeline tests must pass
- Branch protection rules must be satisfied

### How It Works

1. Renovate creates a pull request for a dependency update
2. GitHub's auto-merge is automatically enabled on the PR
3. GitHub Actions/CI pipeline runs automatically
4. **Psalm static analysis tests are executed**
5. If Psalm and all other required checks pass ✅
   - GitHub automatically merges the PR to `main`
6. If Psalm or any check fails ❌
   - The PR remains open
   - No auto-merge occurs
   - Manual review and fixes are required

### Protection Mechanism

The auto-merge will **NOT** proceed if:

- ❌ Psalm detects any type errors or issues
- ❌ Any required status check fails
- ❌ Branch protection rules are not met
- ❌ Merge conflicts exist

This ensures that only dependency updates that pass all quality gates (including Psalm static analysis) are automatically merged to the main branch.

### Configuration

The Renovate configuration in `renovate.json` includes:

```json
{
    "$schema": "https://docs.renovatebot.com/renovate-schema.json",
    "extends": [
        "config:recommended"
    ],
    "platformAutomerge": true,
    "major": {
        "dependencyDashboardApproval": true
    }
}
```

The `platformAutomerge: true` setting leverages GitHub's native auto-merge functionality, working in conjunction with your branch protection rules and required status checks to maintain code quality.

### Benefits

- 🚀 Faster dependency updates
- 🛡️ Protected by Psalm static analysis
- ✅ Only merges when all tests pass
- 🔒 Main branch remains stable
- 🔄 Uses GitHub's native auto-merge feature

### Additional Notes

Major version updates require manual approval via the Renovate Dependency Dashboard due to the "dependencyDashboardApproval": true setting for major updates.
