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

A professional Open Source E-Invoicing System for PHP (Yii3) with UBL 2.1 and
 Peppol support.

## Features

### Vat Support

### Multi-Currency Billing

### Peppol UBL 2.1 E-Invoicing
Automated generation and transmission of compliant UBL 2.1 documents via the
 Peppol network.

**Recent Implementations**

[Peppol Schematron Validator — Route 1](docs/PEPPOL_SCHEMATRON_VALIDATOR_ROUTE1.md) — `SchematronRuleRunner` evaluates `PEPPOL-EN16931-UBL.sch` directly against the invoice DOM at runtime; XPath 2.0 subset implemented in PHP (`normalize-space`, `substring`, `translate`, `castable as`, sequence constructors, `for…return`, axis `::` steps); ten `u:` checksum functions wired from existing `PeppolValidator` methods; hand-written rule methods gated off when `.sch` file present (June 2026)

[Peppol Schematron Code Generation](docs/PEPPOL_SCHEMATRON_CODEGEN.md) — PHP/TypeScript/Scala validator files generated from the official Peppol BIS Billing 3.0 Schematron `.sch` file; `bin/generate-php-validators.php`, `bin/generate-ts-validators.php`, `bin/generate-scala-validators.php`; VO layer; PHP upgrade path replacing `PeppolValidator` XPath methods with a hydrator + generated functions (June 2026)

[TypeScript Vitest Coverage](docs/TYPESCRIPT_VITEST_COVERAGE.md) — Vitest + jsdom + v8 coverage wired into CI for `inv-index.ts`, `list-utils.ts`, and `quote-index.ts`; `phpunit.xml.dist` case fix for Linux CI; PHP and TS coverage fed to SonarCloud; coverage badge added (May 2026)

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

[Content Security Policy Updates](docs/CONTENT_SECURITY_POLICY_UPDATES.md) (March 2026)

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
* Generate OpenPeppol UBL 2.1 Invoice 3.0.15 XML invoices – validated with Ecosio. 
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
