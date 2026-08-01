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
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=rossaddison_invoice&metric=code_smells)](https://sonarcloud.io/summary/new_code?id=rossaddison_invoice)
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

[HomeCare QR Auto-Invoice — Pitfalls Found and Fixed](docs/HOMECARE_AUTOINVOICE_PITFALLS_AUGUST_2026.md) — a pitfalls review of the QR-scan auto-invoice facility, partly informed by how Stripe (idempotency keys), ride-hailing apps (discrete trip-completion events), and field-service scheduling tools (ServiceM8/Jobber/Squeegee) solve the same class of problem, surfaced a race condition (the eligibility check ran outside the transaction that created the invoice, so two near-simultaneous scans could both pass before either committed) and — the more consequential finding — that the old rule blocked on "any invoice dated after the last payment, regardless of status," meaning an admin's completely unrelated invoice, credit note, or bulk copy run would silently pause a client's automation with no indication the two things were connected; worse, cleaning up a race-condition duplicate by deleting it would immediately regenerate another one. Fixed with a new `HomeCareVisit` table — one row per (client, calendar day) with a unique DB index, so the constraint itself (not application locking) makes concurrent/repeat scans safe — and re-anchored eligibility on this facility's *own* last generated invoice rather than the client's whole invoice history, so unrelated admin actions can no longer interfere. Also added: a per-client `homecare_auto_invoice_paused` override (previously only a site-wide switch existed), and a staff-only **Settings → HomeCare → 📋 Scan Log** page recording every scan's outcome and failure reason, since neither existed before and both the "not eligible" and "something's broken" customer-facing messages were previously indistinguishable and untraceable. Requires a `BUILD_DATABASE=true` schema sync for the new table/column before use. Full-project Psalm clean; Testo 594/597 (3 pre-existing unrelated failures), PHPUnit 3,875, all passing (August 2026)

[HomeCare inv/guest Hidden Columns + CSP Inline-Handler Sweep, Third Wave](docs/HOMECARE_GUEST_COLUMNS_AND_CSP_THIRD_WAVE_AUGUST_2026.md) — three issues reported together turned out to be two root causes. `script-src 'self'` (no `unsafe-inline`) silently blocks raw `onclick`/`onchange` attributes — the same CSP bug class as the first two sweeps (`docs/CSP_INLINE_HANDLER_SWEEP_GAPS.md`) — hit a third time: clicking a date field anywhere except the calendar icon did nothing, and a grep for `onclick.*showPicker` across the whole `resources/views` tree turned up **17 form files** still using the broken inline pattern instead of the project's own established `data-action="show-picker"` delegation (already correct on `FormFields::dateCreatedField()` and the reporting pages) — only 2 of the 17 had actually been reported. Settings → Front Page's "select all" checkbox hit the identical CSP block via its own inline `onchange`; fixed with a new `data-action="select-all"` primitive added to `data-actions.ts` (3 new Vitest cases, 19/19 passing). Separately, new `homecare_hidden_inv_guest_columns` setting gives `partial_settings_homecare.php` a second checklist scoped to `inv/guest.php`'s own smaller column set (Paid, Credit Note, Client, Date Created, Due Date, Total, Balance) — deliberately a new setting rather than reusing the staff-side one, since the two grids share almost no column keys. Psalm clean throughout; full Testo (593/596, pre-existing unrelated failures only), PHPUnit (3,875), and Vitest (143) suites all unaffected (August 2026)

[GoCardless Direct Debit — Setup Guide (Plain English)](docs/GOCARDLESS_DIRECT_DEBIT_SETUP_GUIDE.md) — step-by-step walkthrough for a first-time GoCardless setup, written to spell out everything GoCardless's own dashboard leaves implicit: Sandbox (`manage-sandbox.gocardless.com`) and Live (`manage.gocardless.com`) are two entirely separate accounts with no in-account toggle; use a plain **Access Token** ("Direct integration"), never a "Partner app" (OAuth, only relevant to platforms managing many separate merchants' own accounts); the token needs **Read-write** scope or mandate/payment creation silently fails; both the access token and the webhook secret are shown by GoCardless **once**, immediately after creation, and can't be viewed again; and the exact webhook endpoint URL/settings-page fields to fill in. Also documents two real 422 errors hit during live sandbox testing and now handled by the app rather than left to reoccur: `Custom payment references are not enabled for your scheme identifier` (this app never sets a custom reference — payments are matched to invoices via metadata instead) and `Your integration has already completed this redirect flow` (a redirect flow can only be completed once; `GoCardlessPaymentController::goCardlessComplete()` now guards on `Inv::direct_debit_date` already being set so a page refresh/back-button retry re-shows the completion page instead of calling GoCardless — and, before that guard existed, could have scheduled a second Direct Debit collection against the customer) (August 2026)

[`Identity::getId()` vs `Identity::getUserId()` — Auth/RBAC Lookups Using the Wrong Id](docs/IDENTITY_VS_USER_ID_AUTH_FIX_JULY_2026.md) — three call sites in the login/logout/OAuth-TFA path (`AuthController::resolveLoginResponse()`, `AuthController::logout()`, `Callback::tfaCheckBeforeRedirects()`) called `$identity->getId()` — which only ever returns the `identity` table's own auto-increment primary key — where they actually needed the signed-in user's id, to look up `user_inv`, check the admin RBAC role, and clear TFA state. Since `identity.id` and `identity.user_id` (the FK to the `user` table, itself on its own independent auto-increment sequence) only coincide when every identity/user row pair is created together in lockstep — which the app's several different signup/OAuth/console-creation paths don't guarantee — the two silently drift apart over the life of the database with no self-correcting mechanism; confirmed on this project's own dev DB at 2,570 of 4,579 identity rows (56%) with `id != user_id`. Fixed by switching all three call sites to `$identity->getUserId()` (narrowed via `instanceof Identity`, since the interface `AuthService::getIdentity()` returns doesn't declare it). Diagnosed via live login testing and direct MySQL queries at each step, not code reading alone — ruled out session staleness and stale FastCGI worker state along the way, and confirmed `user/assignRole` never touches the `identity` table. Psalm clean, PHPUnit `--filter Auth` (49 tests) and Testo `Unit` suite (569/572, the 3 failures pre-existing/unrelated RSA-key-generation environment errors) both pass (July 2026)

["Copy All to Date" Bulk Action on inv/index](docs/INV_COPY_ALL_TO_DATE_JULY_2026.md) — a new toolbar button lets a manager copy every invoice currently matching `inv/index`'s active filters to a single new date in one click, distinct from the existing checkbox-driven "copy to client(s)" flow: no row selection and no client picker, each copy just stays with its own original client. Reuses the same `InvRepository::filterCombined()` the grid itself calls to define "all" (so it always means precisely what's on screen) and the same `copyInvToClient()` machinery the existing bulk-copy feature already relies on. Since this is the only bulk action on the grid with no explicit selection step to double as an "I chose these" confirmation, a JS-side `confirm()` dialog is the sole safety gate before it runs. **Updated July 2026**: a full-project `vendor/bin/psalm --no-cache` run (not run at the time of the original change) caught that `copyAllToDate()` was actually calling `indexApplyFilters()` — a method that never existed anywhere in `InvController` — as `UndefinedMethod` plus a knock-on `MixedAssignment`; fixed to call the same `filterCombined()` the grid itself uses. A second, unrelated finding from the same run — `CategorySecondaryRepository::optionsDataCategorySecondaries()`'s bare `@return array` causing a `MixedArgumentTypeCoercion` on `InvsFilterOptions`'s constructor — fixed by tightening the annotation to `@return array<array-key, string>`. Full-project Psalm clean, full Testo suite (242 tests) unaffected (July 2026)

[Geolocation Blocked in Production — Three Independent Permissions-Policy Sources](docs/GEOLOCATION_PERMISSIONS_POLICY_TRIPLE_SOURCE_JULY_2026.md) — the Settings > Location tab's live GPS tester worked locally but always failed on `yii3i.online` with "Location permission was denied", indistinguishable by error code from a real browser denial. Traced live via `curl -sI` to a `Permissions-Policy: geolocation=()` header set independently in **three** places that all needed fixing: `public/.htaccess` (fixed first, alone not enough), a stray untracked `/var/www/invoice/.htaccess` predating the `public/`-as-`DocumentRoot` layout (never touched by `git pull` since it isn't in the repo) plus a duplicate line in the live `ssl.conf`, and — the one that actually explained the header still being wrong after every Apache-side fix — `config/web/params.php`'s own `'security-headers'` middleware, which deliberately mirrors `public/.htaccess`'s headers in PHP so they survive a change of web server, and had simply drifted out of sync. A red herring along the way: `rc-service apache2 restart` kept reporting success while the same master PID persisted across every attempt — fixed with a hard `stop`/`pkill -9`/`start` instead of trusting `restart`. Confirmed live via matching `curl` header output and the tester working in-browser (July 2026)

[WSL to Alpine Deployment](docs/WSL_TO_ALPINE_DEPLOYMENT.md) — step-by-step guide for pulling updates from GitHub to a live Alpine/Apache2 server via WSL; git stash/pop workflow; file ownership (`chown apache:apache`); session save-path configuration; Psalm on server; SCP file transfer; deploy script; OAuth2 and RBAC debugging commands. **Updated July 2026** after a real incident: a distributed bot run flooding `/login` saturated the app's global rate-limit bucket and locked out legitimate logins — traced to the actual live log files (corrected a stale `error_log` path in the doc's own Rate Limiter Diagnosis section along the way), fixed with a raised app-side limit plus a new `fail2ban` section (install, filter, jail, verify steps, all confirmed working against this server's real log format) that bans flooding IPs at the `iptables` level, since this server turned out to have no Cloudflare in front of it (`yii3i.online` resolves straight to the origin Vultr IP) and Turnstile alone can't prevent bucket-saturation since it only runs after the rate limiter already counted the request. Also documents that `mod_evasive` isn't packaged for Alpine at all, and the decision not to compile it from source (unaudited C code in the Apache process, no `apk upgrade` security updates, manual recompiles forever) in favor of fail2ban as a third independent layer alongside the app rate-limiter and Turnstile

[SonarQube Fixes: invoice.ts Cognitive Complexity + InvsColumnBuilder S138](docs/SONARQUBE_COGNITIVE_COMPLEXITY_AND_S138_FIXES_JULY_2026.md) — two thresholds tipped over by this month's `inv/index` work (Worker allocation column, "Copy All to Date"). `handleClick()`'s cognitive complexity (16, limit 15) fixed the same way the file already handled its PDF/HTML export checks — a new `handleCopyClick()` groups the three "copy invoice" branches (spreadsheet import, multi-copy, single copy) into one, mirroring the existing `handleExportClick()`. `InvsColumnBuilder::buildColumns()`'s line count (173, limit 150) was the harder one: the class was already sitting at exactly 20 methods (the S1448 ceiling), so a new named method to shrink it would have traded one violation for another — fixed with the same trick used for `AuthController` earlier this session, moving the Worker-column and quick-pay-column builders into a new `InvsWorkerColumnTrait`, since SonarQube doesn't count trait-provided methods toward the consuming class even though they're fully callable via `$this->`. `buildColumns()` is now 113 lines; `InvsColumnBuilder`'s own method count reverified at exactly 20, not assumed. Full-project Psalm clean, Testo suite (242 tests) unaffected (July 2026)

[Settings "Location" Tab — Live GPS Tester + Capture Placeholder](docs/SETTINGS_LOCATION_TAB_JULY_2026.md) — a new Settings tab rather than cramming a permission-prompt-driven widget into the `inv/index` breadcrumb (GPS is browser-only — `navigator.geolocation`, nothing PHP can read server-side). One card is a live "Test My Location" button (`SettingsHandler.handleGeolocationTestClick()`) that renders lat/long/accuracy straight from the browser with nothing submitted to the server, with specific messages for unsupported browsers, non-HTTPS contexts (this project's own `invoice.myhost` WAMP vhost doesn't qualify — only `localhost` does), and each of the three `GeolocationPositionError` codes rather than one generic failure. The other card is a new `capture_gps_on_send` toggle, off by default and honestly described as doing nothing yet — it's a placeholder for the still-unbuilt half of the worker/manager status workflow idea (capturing the manager's GPS + worker name at the moment an invoice is released to "sent"), whose worker-allocation half already shipped earlier this month. Full-project Psalm clean, Testo suite (242 tests) unaffected (July 2026)

[What `YII_ENV` Actually Controls, and the Real Cause Behind "Clear the Cache"](docs/YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md) — grew out of a support question ("a new navbar link isn't showing on yii3i.online after a push") whose real cause was mundane (the edit had simply never been committed), but tracking it down surfaced the one genuine environment-driven cache mechanism in the app worth documenting precisely: `YII_ENV=prod` (via `config/environments/prod/params.php`) is the only place `enableCache` gets turned on for `yiisoft/router-fastroute`'s `UrlMatcher`, which then caches the compiled FastRoute dispatch table with **no TTL and no invalidation logic whatsoever** — since `runtime/` is gitignored, a `git pull` can never clear it on its own. Draws a hard line around what this cache does and doesn't affect: it's scoped purely to route *matching* — view/layout content (like a navbar link) is plain PHP re-executed on every request with zero caching anywhere in the stack, so "a menu item isn't showing up" is never this mechanism; it's almost always an uncommitted or unpushed file. Also documents the one other thing `YII_ENV` drives — `SettingRepository::getEnv()`, consumed in exactly two places (`Auth/Trait/Callback.php`, `Auth/Trait/Oauth2.php`) to gate the HMRC developer-sandbox OAuth2 test-user flow to dev only. **Updated same month**: after `CacheInterface` switched to APCu (below), `php yii cache/clear`'s own APCu-clearing half turned out not to work for this case either — it runs on the CLI, which PHP gives its own memory pool entirely separate from the web server's, so it can never reach the cache the website is actually serving from. **Restarting Apache/PHP-FPM is the real fix** for a route change on prod now, not `cache/clear` (July 2026)

[HomeCare Worker Allocation — inv/index Assignment and Scoped Guest Portal](docs/HOMECARE_WORKER_ALLOCATION_JULY_2026.md) — lets a manager allocate a HomeCare invoice to a field worker from a new dropdown column on `inv/index`, via a new `Worker` entity with a genuinely-nullable `Inv.worker_id` `BelongsTo`. A new `worker` RBAC role — deliberately narrower than `observer` (no `view.payment`, no edit-type permissions) — gives the worker their own login, linked to a `Worker` record from the existing `userinv/index` admin screen after an ordinary signup. On `inv/guest`, a linked worker bypasses the usual client-assignment gate entirely and instead sees exactly (and only) whichever invoices are currently allocated to them, live, via a new `InvRepository::repoWorkerVisible()`; payment info (paid/total/balance columns, BACS quick-pay) is hidden for a worker-scoped request specifically, not just gated by the missing permission elsewhere. Full-project Psalm clean, new `WorkerTest` + full Testo suite (242 tests) passing, DB-level smoke-tested inside a rolled-back transaction (July 2026)

[Invoice Checkbox-Copy — Full Bug Hunt, and Back-Button Fixes](docs/INV_COPY_AND_BACK_BUTTON_FIXES_JULY_2026.md) — hands-on live testing (creating invoices, copying them, inspecting the database directly) surfaced a chain of pre-existing bugs, each masking the next: `invToInvInvAmount()` refactored to mirror the already-correct `SalesOrderToInvoiceConverter::soToInvoiceSoAmount()` pattern (operating on each `Inv`'s own attached `InvAmount` relation object rather than a re-fetched detached one), eliminating both the wrong-`inv_id` bug and the Cycle `BelongsTo` `NullException` at the root; `saveInvAmountViaCalculations()` deleted as dead code. `invToInvInvTaxRates()` used the array key `'amount'` instead of the required `inv_tax_rate_amount`, so invoice-level tax was silently dropped on every copy — confirmed against `soToInvoiceSoTaxRates()`'s correct usage of the same key. `copyInvToClient()` never called `invToInvInvAllowanceCharges()` at all, unlike its sibling copy functions. `InvItemService::saveInvItemAmount()` applied the item's tax rate to the charge-inclusive subtotal instead of adding each charge/allowance's own `vat_or_tax` separately (matching the interactive add-charge UI's formula), which only coincidentally matched when rates lined up. Also fixed a silent, unrelated UI bug found along the way: the "back" buttons on `inv/view` and `quote/view` sat inside a `data-bs-toggle="tab"` nav, so Bootstrap's Tab plugin intercepted every click and called `preventDefault()` regardless of `href`, silently going nowhere; fixed by removing that attribute from just the back link and giving it a real `href`. `salesorder/view` had no back button at all — added one. Full-project Psalm clean throughout (July 2026)

[Invoice Checkbox-Copy — Wrong InvAmount inv_id + Missing Cycle Relation Fix](docs/INV_COPY_AMOUNT_WRONG_INV_ID_FIX_JULY_2026.md) — copying an invoice via the checkbox on `inv/index` produced a new invoice whose amount didn't display correctly until it was opened once. `invToInvInvAmount()` (`MultipleCopy.php`, the sole code path that populates a copy's `InvAmount` row) built its save array with `inv_id` taken from the *original* invoice's `InvAmount` rather than the copy's own id — since `Inv` has a `HasOne` relation to `InvAmount` keyed on `inv_id`, this overwrote the copy's own foreign key to point at the original invoice, detaching it from the new one, so `inv/index`'s by-`inv_id` lookup found nothing until opening the invoice ran `NumberHelper::calculateInv()` and re-saved it with the correct id; also fixed an adjacent copy-paste bug in the same block (`packhandleship_total` was reading `getPackhandleshipTax()` instead of `getPackhandleshipTotal()`). Fixing the `inv_id` value surfaced a second, deeper pre-existing bug live: `InvAmountService::saveInvAmountViaCalculations()` only ever set the plain `inv_id` scalar column, never Cycle ORM's separate, required (`nullable: false`) `BelongsTo` relation object (`InvAmount.inv`) — throwing `Cycle\ORM\Exception\Relation\NullException` on every checkbox-copy instead of the original silent-wrong-value symptom. Fixed by routing through the existing `persist()` helper (already used correctly by `saveInvAmount()`) to resolve and attach the `Inv` entity before saving. Psalm clean; existing `InvAmountService` test coverage (12 tests) unaffected (July 2026)

[Adyen Guest Payment — Session countryCode Fix](docs/ADYEN_SESSION_COUNTRYCODE_FIX_JULY_2026.md) — selecting Adyen on the guest invoice page rendered the Drop-in fine, but choosing certain payment methods (observed with Pay by Bank) failed immediately with Adyen's generic red-cross error. Traced live via browser DevTools to a `422 Field 'countryCode' is not valid` on `POST /v1/sessions/{id}/payments`: `AdyenPaymentController::resolveCountryCode()` correctly resolved the client's country, but only passed it to the front-end `AdyenCheckout()` config, never to session creation — so `AdyenPaymentService::createSession()` returned Adyen's full unfiltered payment-methods list (including country-restricted, US-only methods) for every session regardless of the guest's actual country. Fixed by passing `countryCode` into `CreateCheckoutSessionRequest` at session-creation time so Adyen filters methods to ones valid for that country. Psalm clean, existing Adyen test suite (22 tests) unaffected (July 2026)

[Login Denial Message — Distinguish "Email Not Verified" from "Contact Administrator"](docs/HOMECARE_LOGIN_UNVERIFIED_EMAIL_MESSAGE.md) — a HomeCare (or generic) signup customer who hadn't yet clicked their emailed confirmation link saw the same "contact the system administrator" message as an admin-deactivated account; `AuthController::handleNonTfaPath()` now checks for a still-live (unclicked) `email-verification`/`homecare-email-verification` token before falling back to the generic message, and shows "Access Denied: Click on the verification link sent to your email address." instead, via a new `site/emailnotverified` route/view mirroring the existing `adminmustmakeactive` pattern. Flagged, not-yet-fixed follow-up: an unrelated pre-existing `disableToken()` call on the same path invalidates a *generic*-signup user's real verification token on their first failed login attempt, so the new message only holds up reliably for HomeCare signups until that's addressed. Psalm errorLevel 1 clean (July 2026)

[HomeCare Signup — Public Self-Service Flow](docs/HOMECARE_SIGNUP_PUBLIC_FLOW.md) — new unauthenticated `/homecare-signup` form/confirm flow (`HomeCareSignupController`/`HomeCareSignupForm`, deliberately separate from the generic `SignupController`) that always creates a Client and, only once the emailed confirmation link is clicked, resolves/creates the street (`Family`) and house-number `Product` (Service-type) and raises the first invoice — no durable business records exist for an unconfirmed/bot signup. Found and fixed a real data-integrity bug along the way: the initial street-name resolution used an unescaped `LIKE` match, so a `%`/`_` in a customer-typed street name could silently merge two unrelated runs; replaced with an exact match on both `family_name` and `category_secondary_id` — the latter resolved from a form dropdown, with a `not_set_yet_<timestamp>` placeholder auto-created (never `null`) when the customer's area isn't listed yet, so two "new area" signups can never collide. Both actions switched from `renderPartial()` (explicitly skips the layout) to `render()` to inherit the site nav/footer, matching the generic signup flow; also surfaced and fixed an app-wide theming gap where the Bootstrap floating-label form theme — correct for single-input fields — broke `RadioList` groups (label rendered after and overlapping the options), fixed via a `fieldConfigs` override in `config/common/params.php` mirroring the existing `Checkbox::class` fix. Psalm errorLevel 1 clean throughout (July 2026)

[Stripe Pay by Bank — Open Banking for UK & Finland](docs/STRIPE_PAY_BY_BANK_UK_FINLAND.md) — documents how to enable Stripe's **Pay by Bank** Open Banking payment method (UK and Finland are both generally-available customer locations per [Stripe's docs](https://docs.stripe.com/payments/pay-by-bank), France/Germany/Ireland still private preview) so customers pay directly from their bank account/app instead of a card; this app already supports it with **zero code changes** since `StripePaymentService::createPaymentIntent()` creates every PaymentIntent with `automatic_payment_methods.enabled = true` rather than a hardcoded method list, so which methods appear is driven entirely by **Settings → Payment methods** in the Stripe Dashboard — turning on Pay by Bank and turning off Cards there is enough to go "Pay by Bank only"; covers the customer's bank-app redirect/approval flow, that it reuses the existing `payment_intent.succeeded` webhook handling unchanged, and its limitations (no recurring payments, no manual capture, no disputes, refunds supported up to 730 days) (July 2026)

[Payment Gateway Refund — Live Testing & Adyen v6 Upgrade](docs/PAYMENT_GATEWAY_REFUND_LIVE_TESTING_JULY_2026.md) — the refund dropdown on `payment/index` (`PaymentRefundController`) had only ever been proven via a script calling each gateway's `refund()` directly; this pass drove it through the real UI end-to-end for all four PCI-compliant gateways and verified every result against the provider's own API/dashboard, not just this app's database — Stripe, Braintree (sandbox needs a manual `Gateway::testing()->settle()` force-settle before refund is possible — real `Braintree\Test\Transaction::settle()` is a trap, it hits an unconfigured global gateway), and Mollie (first genuine success-path refund test; previously only proven against a fake, rejected reference) all passed cleanly. Adyen surfaced a real production bug: the pinned Web SDK v5.40.0 crashed outright ("The following properties should not be passed to the client: askDonation") because Adyen's `/sessions` response now always includes a Giving/Donation field v5's Drop-in rejects — fixed by upgrading to v6.41.0, which required real code changes (confirmed against the actual CDN bundle, not just docs): the global renamed `window.AdyenCheckout` → `window.AdyenWeb`, Drop-in creation moved to a `new AdyenWeb.Dropin(checkout)` constructor, and `countryCode` became mandatory (resolved via the existing `CountryHelper`/league-iso3166 lookup). Since `adyenComplete()` is deliberately read-only and Adyen has no Stripe-CLI equivalent for local webhook forwarding, payment/refund confirmation was verified by replaying a genuinely HMAC-signed `AUTHORISATION` notification — built from a real sandbox transaction's actual pspReference and signed with the app's own configured HMAC key — against the local webhook route directly, exercising the real signature-verification and handler code end-to-end (July 2026)

[Adyen Payment Gateway — Live Testing & Cross-Gateway CSP Fixes](docs/ADYEN_GATEWAY_LIVE_TESTING_AND_CSP_FIXES_JULY_2026.md) — Adyen added as a fifth PCI-compliant gateway and driven live end-to-end through the browser (session creation → Drop-in render → card/bank/Paysafecard), surfacing two external config gaps — a `gateway_adyen_merchantAccount` typo (`ECON` → `ECOM`, confirmed via Adyen's own API error in `app.log`) and the Adyen Client Key's Allowed-Origins CORS allowlist never including `http://localhost` (the actual "Adyen cannot test locally" blocker, fixed in the Adyen Customer Area, not code) — plus a batch of CSP domain gaps only visible by watching the console during a real payment flow: `img-src` missing `*.adyen.com`/`*.cdn.adyen.com` and separately `*.media-amazon.com` (Amazon's logo CDN, distinct from `*.payments-amazon.com`); `connect-src` missing Amazon's regional payments API domain (`payments-eu.amazon.com` etc. — first fix attempt used the CSP-invalid partial-label wildcard `payments-*.amazon.com`, silently ignored by browsers; corrected to `*.amazon.com`) and missing `*.braintree-api.com` entirely (Braintree Drop-in v3's tokenization API lives on a separate second-level domain from `*.braintreegateway.com`). Two unrelated bugs caught by the same live pass: the BACS quick-pay modal's inline `<script>` (ClipboardJS init) blocked by `script-src` — moved to `src/typescript/bacs-quickpay.ts` matching the `payment-adyen.ts`/`payment-braintree.ts` pattern — and a load-order bug where `guest.php` registers the Bootstrap-dependent bs5-lightbox asset *before* Bootstrap itself (reversed from `invoice.php`), throwing `Cannot read properties of undefined (reading 'Modal')` on every guest-facing page; fixed at the asset-dependency level (`$depends` on `BootstrapJsOnlyAsset`/`BootstrapCdnJsOnlyAsset`) rather than layout call order, which had already silently drifted out of sync once. Stripe's remaining console output confirmed informational only — HTTP-testing notices and Apple/Google Pay's inherent HTTPS requirement, not CSP or code issues. `config/web/params.php` and the mirrored `public/.htaccess` CSP header kept in sync throughout, per the established pattern (July 2026)

[Payment Gateway Live Testing — Real Bugs Found Only Under End-to-End Testing](docs/PAYMENT_GATEWAY_LIVE_TESTING_JULY_2026.md) — driving real invoices through Stripe/Braintree/Mollie/Amazon Pay end-to-end (admin-created invoice → Observer-role login → pay → server-side log/DB verification) surfaced 9 real defects invisible to static review and unit tests: Stripe's webhook secret was stored as plaintext (silently produces garbage on decrypt, no error — AES-256-CTR has no integrity check), the `payment_method` table was missing IDs 1–8 that every gateway hardcodes (FK violation on first live webhook), an `(null !== $x) ?: 'unknown'` boolean-cast-ternary bug recorded every payment reference as literal `"1"` instead of the real invoice number (present in original `stripeComplete()`, copied into the new webhook, and found identically in `mollieComplete()`), writing invoice status before the payment/merchant audit record left one invoice "paid" with no audit trail after a mid-request crash, Stripe's client-redirect `succeeded` status could race ahead of the async webhook and show a false "Payment failed", Braintree's card-nonce form had no CSRF token at all (the only one of the four gateways that POSTs a card nonce natively back to our own server), and Amazon Pay's CSP `img-src` was missing `*.payments-amazon.com` despite every other directive including it, silently breaking its button graphics. Amazon Pay's live payment itself stayed blocked on external Seller Central sandbox setup (`storeId`/`clientId`), confirmed as a config gap, not a code defect, after ruling out CSP via the live response header (July 2026)

[Stripe Payment Gateway — Webhook Signature Verification & `PaymentGatewayInterface`](docs/STRIPE_PAYMENT_GATEWAY_WEBHOOK.md) — `stripeComplete()` previously marked invoices paid by trusting a client-supplied `?redirect_status=succeeded` query parameter with no server-side confirmation — forging that URL could mark any invoice paid with no payment made. New `POST /paymentinformation/stripeWebhook` (outside `RoutePermission::invoiceGroup()`, matching the `telegram/webhook` precedent) verifies Stripe's signed events via `StripePaymentService::verifyWebhookSignature()` against a new `webhookSecret` setting and becomes the sole writer of payment status; `stripeComplete()` is now read-only, re-reading current state rather than trusting the redirect. New `App\Middleware\CsrfExemptMiddleware` decorates the globally-applied `CsrfTokenMiddleware` so this one webhook path skips CSRF validation (which would otherwise 422 every call from Stripe's servers before the signature check ever ran) — `telegram/webhook`/`as4/receive` look like they have the identical gap, flagged but not fixed. New `PaymentGatewayInterface` (`getDriverKey()`/`isConfigured()`/`verifyPayment()`) implemented fully for Stripe and retrofitted as thin, behavior-unchanged methods onto Braintree and Amazon Pay (both classes stay in active use regardless, so the conformance can't silently rot); a same-shape `MolliePaymentGatewayAdapter` was written too but had zero consumers anywhere — caught by a full-project Psalm run (`UnusedClass`; per-file Psalm explicitly can't detect this) and deleted rather than left as dead code, so Mollie has no interface conformance for now. Open Banking excluded on purpose — its SDK only exposes payment-creation calls, not lookup-by-reference, so a `verifyPayment()` there would be actively misleading. Stripe's JS/CSS now scoped to only its own payment page (previously loaded on every page in the app); dead `stripeIncomplete` route and a duplicate `PaymentIntent`-creation helper removed; new `StripeWebhookSignatureTest` covers the signature-verification primitive directly (pure HMAC, no network I/O — `StripePaymentService` itself can't be unit-tested due to a pre-existing dependency on the concrete `final SettingRepository` class). Post-merge SonarCloud CI (not local Psalm, which has no equivalent complexity checks) flagged the controller at 21 methods (php:S1448, limit 20) and `stripeWebhook()` at 5 returns (php:S1142, limit 3); fixed by extracting the shared `recordOnlinePaymentsAndMerchant()` (also used by Braintree/Mollie) into standalone `Service\OnlinePaymentRecorderService`, and the webhook's own signature/lookup/write logic into `Service\StripeWebhookHandler` with its guard-clause chain decomposed across `resolveContext()`/`applyEvent()` — controller action is now a one-line delegator, re-verified live end-to-end against a real invoice afterward. Doc includes a full local-testing setup guide for the Stripe CLI (`winget install --id Stripe.StripeCli` — note the exact casing, `StripeCLI` doesn't exist — `stripe login`, and critically `stripe listen --forward-to <url>`, since running bare `stripe listen` without `--forward-to` looks identical to working but silently never calls the app at all). Psalm errorLevel 1 clean (July 2026)

[Turnstile Widget Silently Broken by CSP — Missing `challenges.cloudflare.com`](docs/TURNSTILE_CSP_FIX.md) — login broke immediately after configuring a real Turnstile secret key; root cause was CSP `script-src`/`frame-src`/`child-src` never allowing `challenges.cloudflare.com`, so the widget silently failed to render and `cf-turnstile-response` stayed permanently empty — invisible beforehand only because `verifyTurnstile()` bypasses checking entirely when no secret is configured; fixed by adding the domain to `script-src`/`frame-src`/`child-src`/`connect-src` in both `config/web/params.php` and the mirrored `public/.htaccess`, matching how Stripe/Braintree already appear across those same four directives (July 2026)

[System Updates — PHP Version Check](docs/SYSTEM_UPDATES_PHP_VERSION_CHECK.md) — new Settings tab checks php.net for a newer PHP patch release on the running major.minor branch, via a cached background console command (`php yii system/check-php-version`, matching the existing `peppol-check`/`as4/monitor` pattern) plus an on-demand "Check Now" button; four platform buttons (yii/alpine/linux/wamp) show copyable — never executed — upgrade commands, a hard requirement consistent with this session's CSP hardening work; `PhpVersionCheckService` shared between the console command and `SettingController::checkPhpVersionNow()`; extended `SettingRepositoryInterface` with `withKey()`/`save()` (confirmed `SettingRepository` is its only implementor first) since the previously read-only interface couldn't support persisting the cached result; frontend reuses `data-actions.ts` with two new generic primitives (`toggle-panel`, `copy-to-clipboard`) rather than a bespoke script; 9 new PHPUnit tests establish this codebase's first Guzzle `MockHandler` testing pattern; full suite clean (PHPUnit 3,702+69+9, Vitest 135, Psalm 0 errors); verified end-to-end against the real php.net API in dev, browser-rendering not verified due to no local MySQL in this sandbox (July 2026)

[yii-dataview DropdownFilter CSP Bug — Reported and Fixed Upstream](docs/YII_DATAVIEW_DROPDOWNFILTER_UPSTREAM_FIX.md) — filed [yiisoft/yii-dataview#344](https://github.com/yiisoft/yii-dataview/issues/344) (root cause: `DropdownFilter` renders inline `onChange="this.form.submit()"`, silently blocked by any strict `script-src`, `final` class gave consumers no workaround) and [yiisoft/yii-dataview#345](https://github.com/yiisoft/yii-dataview/pull/345) (adds `submitOnChange(bool $enabled): self`, defaults `true` so existing output is byte-identical, non-breaking); verified against the real upstream toolchain before opening the PR — 514/514 tests, Psalm/php-cs-fixer/Rector all clean — after discovering the `rossaddison/yii-dataview` fork's `master` was a long-stale pre-1.0 branch and branching directly off `upstream/master` instead; this app's own `data-actions.ts` workaround stays regardless of upstream merge timing (July 2026)

[CSP Inline-Handler Sweep Gaps — Second Wave](docs/CSP_INLINE_HANDLER_SWEEP_GAPS.md) — the original CSP hardening sweep searched for literal `<script`/`onclick=` text and missed PHP array-based `->addAttributes(['onclick' => '...'])` attributes plus a vendor-rendered inline handler entirely outside app source; 17 instances across 12 files (`inv/index` dropdown filters via `vendor/yiisoft/yii-dataview`'s `DropdownFilter`, group-row collapse, toolbar expand/collapse-all, 5× delete-confirm — one of which also fixed a latent unescaped-apostrophe JS-injection bug from string-concatenated `confirm()` calls — and `showPicker`/`history.back`) all reused the existing `data-action`/`data-confirm` delegation in `src/typescript/data-actions.ts` rather than inventing new mechanisms; added `data-actions.test.ts` (zero prior coverage) plus 3 new `list-utils.test.ts` cases, catching and fixing a listener-accumulation bug along the way (131/131 passing); documents that neither Vitest/jsdom nor the existing Codeception `PhpBrowser` Acceptance suite can catch this bug class since neither enforces CSP against real rendered JS — a Playwright or Codeception-WebDriver test is a flagged, not-yet-actioned follow-up (July 2026)

[Angular Build Blocked by TypeScript 7 — Known Limitation](docs/ANGULAR_TYPESCRIPT7_BUILD_CONFLICT.md) — `@angular-devkit/build-angular` was never declared in `package.json` despite `angular.json` requiring its builders (`:browser`/`:dev-server`/`:extract-i18n`/`:karma`) — fixed, added at exact `22.0.7` matching this repo's Angular-pinning convention, reproduced as broken on Windows too (not Alpine-specific). That fix surfaced a deeper, still-unresolved conflict: `@angular/compiler-cli@22.0.6` peer-requires `typescript ">=6.0 <6.1"` but this project deliberately pins `typescript ^7.0.2` (plus `@typescript/native-preview`) for the ES2024/esbuild toolchain — TS 7's restructured internals break `readConfiguration` inside compiler-cli (`Cannot read properties of undefined (reading 'Error')`). Decision: documented as a known limitation rather than scoping an npm `overrides` entry or downgrading TypeScript project-wide, since Angular integration is already flagged fragile/WIP elsewhere in `package.json`; `npm run build:css && npm run build:typescript` works as a workaround, `build:angular` stays broken until Angular ships TS 7 support (July 2026)

[esbuild Scripts Broke on Linux — `node <path>` vs Calling the Binary](docs/ESBUILD_LINUX_BINARY_FIX.md) — `build:typescript:dev`/`:auth`/`:prod` hardcoded `node node_modules/esbuild/bin/esbuild ...`; esbuild's postinstall replaces that file with the real native binary on POSIX once the platform package (`@esbuild/linux-x64`) installs, so Node choked trying to parse raw ELF bytes as JavaScript (`SyntaxError: Invalid or unexpected token`) — worked on Windows only because that swap doesn't happen there; fix: call `esbuild` directly and let npm's `node_modules/.bin` shim resolve it per platform; verified identical bundle output on Windows post-fix (July 2026)

[Updating PHP 8.4 on Alpine Linux](docs/PHP84_ALPINE_UPDATE.md) — Companion to the initial setup guide: `apk update` + `apk policy php84` to check what's available, `apk upgrade $(apk info | grep '^php84')` to update just the PHP packages (safer than a full-system upgrade on a live box), then a mandatory `rc-service apache2 restart` (mod_php) or `rc-service php-fpm84 restart` (php-fpm) since the CLI version updates immediately but the running web-server worker doesn't; covers Alpine's package version lagging behind the latest php.net upstream release and checking for `*.apk-new` config files left behind by the upgrade (July 2026)

[Security Hardening Audit — All 9 Findings Fixed](docs/SECURITY_HARDENING_AUDIT_JULY_2026.md) — Static/config-level security review, now fully remediated. Critical: file uploads (`CompanyPrivateController`, `ProductAttachmentController`) validate extension/MIME/size via `Yiisoft\Validator\Rule\File` before `moveTo()`; session cookie `Secure` flag now driven by `SESSION_COOKIE_SECURE` env var. High: `/scan/{token}` HomeCare QR endpoint rate-limited (global + per-IP); cookie-signing secret moved to `COOKIE_SECRET_KEY` env var. Medium: new `SecurityHeadersMiddleware` adds HSTS/Permissions-Policy at the PHP layer; CSP `script-src` hardened to `'self'` — no `unsafe-inline`/`unsafe-eval` — with ~15 inline `<script>`/`onclick`/`hx-on:` blocks moved into `src/typescript/*.ts` (see [Content Security Policy Updates](docs/CONTENT_SECURITY_POLICY_UPDATES.md)); login now gated behind 2FA-or-admin-role plus a 5-attempts/15-minute per-account lockout. Low: 4 unescaped `Html::encode()` misses found and fixed via a full sweep; RBAC audit added the missing `manage.hmrc` permission and fixed 5 routes with broken or missing auth middleware. Follow-up regression caught after deploy: the CSP change silently broke Bootstrap Icons and other CSS site-wide — several `AssetBundle` classes loaded stylesheets via `media="print"` + inline `onload="this.media='all'"`, and the new policy blocked that `onload`; fixed in 4 files by loading them as normal blocking `<link>`s instead. Psalm errorLevel 1 clean throughout (July 2026)

[Home-Care QR Auto-Invoice Facility + Routes Config Refactor](docs/HOMECARE_QR_AUTOINVOICE_AND_ROUTES_REFACTOR.md) — Customer-facing recurring-invoice facility: a client's QR code (`Client::client_qr_token`) scans to a public, unauthenticated `public/homecare-scan` route (`/scan/{token}`) — a deliberate, scoped exception to the app's usual guest-access model — where `HomeCareCleaningEligibilityService` decides whether a new invoice should be generated (client has an invoice on file, last invoice paid with a payment date on record, nothing dated since) and `generateHomeCareCleaningInvoice()` reuses the existing invoice-copy machinery rather than a new converter, always forcing `status_id = 2` (sent); print actions on both the guest (`inv/guest/qr`) and staff (`client/printQrCode/{id}`) sides share one `ClientService::getOrCreateQrToken()`. Alongside this, `config/common/routes/routes.php` (2,582 lines, 70+ controllers in one block) was split into 71 strictly-per-controller files via a depth-tracking script, verified byte-identical against `php yii router/list` at every pass; splitting surfaced 548 duplicated permission-check closures and 71 duplicated `Group::create('/invoice')` wrappers, eliminated via new `App\Middleware\RoutePermission` and `App\Middleware\RateLimiter` static helpers (PHP traits don't apply to plain route-config scripts); confirmed with standalone `phpcpd`/`phpmd` PHARs — "No clones found" (down from 1.41%). New `php yii router/list --controller[=<name>]` option (also wired into `m.php`) adds a Controller column and filters by controller-name prefix. Psalm errorLevel 1 clean · Testo Unit 139/139 (July 2026)

[HMRC MTD Developer Sandbox — OAuth2 Backend Integration](docs/HMRC_MTD_DEVELOPER_SANDBOX.md) — `HmrcApiCatalogue` curates 8 MTD APIs (VAT, Self Assessment, Self-employed Business, etc.) with scope, identifier type (VRN/NINO/EORI), and route map; `backend/hmrc` dashboard shows a Full API Catalogue card (always visible, rows green-highlighted when within granted token scope), an Available APIs dropdown driving navigation to API-specific controller actions, and a "Log in with HMRC" button that initiates PKCE OAuth via `auth/authclient`; `callbackDeveloperGovSandboxHmrc` now checks `getIdentity()->getId() !== null` immediately after storing the 5 HMRC session tokens — if the admin is already authenticated it redirects straight to `backend/hmrc` without switching the session user; the login-page "Continue with Developer Gov Sandbox UK" button and its `no_developer_sandbox_hmrc_continue_button` setting were removed entirely — HMRC OAuth is now exclusively for API authorisation from `backend/hmrc`. Psalm errorLevel 1 clean (July 2026)

[Mockery Bridge — Testo Integration](docs/MOCKERY_BRIDGE.md) — `testo/bridge-mockery` wired via `MockeryPlugin` in `testo.php`; `Mockery::close()` called automatically after every test so expectations are always verified with no per-test `tearDown()` boilerplate. Solves three pain points in this codebase: final Cycle ORM repository classes that PHPUnit's `createMock()` cannot subclass; fluent `expects()`/`allows()`/`spy()` API that separates strict expectations from stubs; and automatic teardown that prevents silently-skipped mock assertions. First use: `As4RetryEngineTest` — three `detectMissingReceipts()` scenarios (empty queue, null `firstSentAt`, EBMS:0301 timeout) all tested against interface mocks with no database. Psalm errorLevel 1 clean (July 2026)

[Auth Controllers — Full Hardening Implementation](docs/AUTH_CONTROLLERS_HARDENING.md) — Seven-fix bot-hardening pattern extended to all five auth routes: `/change` (10/60 s global, 3/60 s per-IP), `/forgotpassword` (5/60 s global, 2/60 s per-IP — strictest, triggers email), `/resetpassword/{token}` (10/60 s global, 3/60 s per-IP — already token-gated). All routes now have `LimitAlways` + `LimitCallback` CF-Connecting-IP + `TooManyRequestsMiddleware` on both layers + Turnstile widget + pre-hydration verify + `checkRateLimit()` with distinct key prefixes. `TurnstileVerification` trait now shared by all four auth controllers. Repository interfaces introduced (`InvRepositoryInterface`, `InvItemRepositoryInterface`, `SettingRepositoryInterface`, etc.) to unblock mocking of `final` classes in PHPUnit. Dual `@dataProvider` + `#[DataProvider]` annotation pattern established for PHPUnit 13 + Codeception compatibility. PHPUnit 3,727 OK · Codeception 3,673 OK · Psalm no errors (July 2026)

[Change Password Route — Bot Susceptibility Analysis](docs/CHANGE_PASSWORD_BOT_SUSCEPTIBILITY.md) — Assessment of `/change` against the seven fixes applied to `/login` (July 2026): route has no middleware (fixes #1–#3 all missing); `FormHydrator` runs before any IP check (fix #4 missing); `AuthSecurityHelper` not injected so `checkRateLimit()` is never called (fixes #6–#7 missing). Key mitigation: `isGuest()` guard means unauthenticated bots cannot reach POST processing, narrowing the threat to compromised authenticated sessions and scripted abuse. Priority gaps: no route-level rate limiting (High), `checkRateLimit()` absent (High), hydration before IP check (Medium), no Turnstile (Low–Medium). Recommended fix mirrors the login pattern: `LimitAlways` + `LimitCallback` closures in `routes.php`, `AuthSecurityHelper` injected, `checkRateLimit()` called before hydration, storage key prefix `sha1('change_ctrl' . $ip)` to avoid GCRA collision (July 2026)

[Login Route — Hardening Implementation](docs/LOGIN_HARDENING.md) — Seven fixes applied to `/login` after the bot-susceptibility analysis (July 2026): `LimitCallback` reading `CF-Connecting-IP` for correct per-IP GCRA buckets; `LimitAlways` global counter (30/60 s) collapsing distributed botnet traffic; `TooManyRequestsMiddleware` on both rate-limit layers so `FileCache` CAS contention returns 429; Turnstile widget on the login form with token verification before `FormHydrator` runs; inner per-IP `Counter(5,60)` decoupled from the old test-inflated limit; `HTTP_CF_CONNECTING_IP` first in `AuthSecurityHelper::getClientIpAddress()` header chain; `checkRateLimit()` wired into `login()` with a distinct key prefix to activate the defence-in-depth layer. `verifyTurnstile()` extracted to a shared `TurnstileVerification` trait used by both `AuthController` and `SignupController`, eliminating the duplication SonarQube would have flagged. Psalm errorLevel 1 clean (July 2026)

[Login Route — Bot Susceptibility Analysis](docs/LOGIN_BOT_SUSCEPTIBILITY.md) — Seven deficiencies in the current `/login` protection (July 2026): `LimitPerIp` reads `REMOTE_ADDR` which is Cloudflare's edge IP behind the proxy — all users and bots share one bucket; no `LimitAlways` global path counter means a 900-IP botnet can deliver 18,000 attempts per 10 s before any per-IP bucket triggers; no `failStoreUpdatedDataMiddleware` means `FileCache` CAS contention silently forwards requests; no Turnstile on the login form allows unlimited automated credential-stuffing at zero CAPTCHA cost; the DI-bound limit of 20/10 s was raised for test-suite compatibility, not security, allowing 120 guesses per minute per IP; `HTTP_CF_CONNECTING_IP` is absent from `AuthSecurityHelper::getClientIpAddress()` header chain; `checkRateLimit()` is never called from `login()` leaving a defence-in-depth layer disconnected. Partial mitigations: 2FA (when enabled), `userInv->getActive()` gate, session ID regeneration on success — all operate after authentication, not before (July 2026)

[Rate Limiter & Signup — Bot-Wave Hardening](docs/RATE_LIMITER_SIGNUP_HARDENING.md) — Four fixes implemented after a 900-bot signup wave (July 2026): `LimitCallback` reading `CF-Connecting-IP` so the GCRA bucket fingerprints the real client IP rather than Cloudflare's edge IP (`REMOTE_ADDR`); a layered `LimitAlways` global path counter (50/10 s) that rejects the botnet's combined traffic before any per-IP bucket is consulted; `TooManyRequestsMiddleware` wired as the `failStoreUpdatedDataMiddleware` on both rate-limit layers so `FileCache` CAS contention returns 429 instead of silently forwarding the request; and `verifyTurnstile()` moved before `FormHydrator::populateFromPostAndValidate()` so malformed POST bodies that would fail validation early can no longer bypass Turnstile verification. Deployment assumption: direct origin access must be blocked at the firewall so `CF-Connecting-IP` cannot be spoofed. Psalm errorLevel 1 clean (July 2026)

[Rate Limiter & Signup Bot-Protection — Known Limitations and Fixes](docs/RATE_LIMITER_SIGNUP_LIMITATIONS.md) — Six structural limitations in `LimitRequestsMiddleware` exposed by a 900-bot signup wave (July 2026): `REMOTE_ADDR` wrong behind Cloudflare's proxy (critical — real IP is in `CF-Connecting-IP`); per-IP GCRA bucket useless against botnets with distinct IPs; `FileCache` CAS failure silently allows requests through when no `failStoreUpdatedDataMiddleware` is set; Turnstile verification fires only after form validation passes, letting malformed POSTs bypass it for free; rate-limit headers (`X-Rate-Limit-Reset`) advertise the retry window to bots; GET and POST counted in separate buckets doubling the effective attempt allowance. Fix summary: `LimitCallback` reading `CF-Connecting-IP`, layered `LimitAlways` global counter, APCu storage or explicit fail-middleware, move Turnstile before hydration.

[Golf Lessons & the Peppol Network — A Treasurer's Guide](docs/GOLF_LESSON_PEPPOL_DEMO.md) — A non-technical explainer for golf club treasurers who have never heard of Peppol: the full booking lifecycle from Purchase Order to Receipt Advice told in golf terms (England Golf register = SML, club mailroom = Access Point, official scorecard format = UBL 2.4, recorded-delivery envelope = AS4); the four-corner model illustrated with the PGA Pro ↔ Golf Club flow; what Yii3-i does at each corner (outbound: `SoapEnvelopeBuilder` → `WsSecuritySigner` → `As4HttpClient`; inbound: `As4ReceiveController` → `As4DuplicateDetector` → `As4InvoiceImportService`); the bilateral demo setup (`localhost` = club, `yii3i.online` = pro, ngrok for reverse direction); a full Peppol ↔ Golf glossary; and the one-component swap (`StaticAs4SmpResolver` → `As4SmpResolver`) that turns the demo into production Peppol (July 2026)

[AS4 Bilateral Test Infrastructure — `StaticAs4SmpResolver` + `as4/test-send`](docs/AS4_BILATERAL_ROADMAP.md) — `StaticAs4SmpResolver` implements `As4SmpResolverInterface` and returns a fixed `As4SmpEndpoint` from env config, bypassing SMP/SML DNS for direct node-to-node testing between `localhost` and `yii3i.online`; `php yii as4/test-send` command sends a minimal bilateral ping XML (or a supplied UBL file) through the full outbound pipeline — WsSecuritySigner → SoapEnvelopeBuilder → As4HttpClient → peer `/as4/receive` — and reports HTTP status, receipt signal, and error detail; `config/common/di/as4.php` wired with 9 outbound bindings including PEM strings loaded from env-specified cert file paths; `AS4_SIGNING_KEY_PATH`, `AS4_SIGNING_CERT_PATH`, `AS4_PEER_CERT_PATH`, `AS4_PEER_ENDPOINT`, `AS4_PEER_PARTY_ID`, `AS4_SENDER_PARTY_ID`, `AS4_PEER_TRANSPORT_PROFILE`, `AS4_RETRY_POLICY` added to `.env.example`; swap `StaticAs4SmpResolver` → `As4SmpResolver` when moving to real Peppol 4-corner SMP lookup; Psalm errorLevel 1 clean (July 2026)

[OpenPeppol Service Provider Certification — Preparation Guide](docs/OPENPEPPOL_CERTIFICATION_PREP.md) — Six-section guide covering OpenPeppol membership application (APCA or SCA tier), test-pilot environment setup (test SMP + EFTIA interoperability lab), pre-certification interoperability checklist with cross-references to code (`As4ReceiveController`, `As4RetryEngine`, `WsSecuritySigner`, `CycleOrmAs4MessageRepository`), production certification steps (EFTIA test suite → conformance certificate → SML registration), post-certification obligations (annual recertification, cert renewal, Peppol Directory maintenance, changelog monitoring), and recommended production cron schedule for `as4/retry`, `as4/monitor`, and `as4/status` (July 2026)

[Peppol PKI Certificate Request (CSR) — Template & Guide](docs/PEPPOL_PKI_CERTIFICATE_REQUEST.md) — OpenPeppol AP certificate CSR guide: exact subject field format (`C=GB/O=.../OU=PEPPOL/CN=PNO<country>:<participantId>`), three-step OpenSSL commands for key + CSR generation, Member Portal submission steps, install + verify commands (modulus fingerprint comparison, subject and validity checks), `php yii as4/monitor --warn-days=30` cron for expiry alerting, file permission hardening (`chmod 400` private key, `chmod 700` cert directory), annual renewal checklist including requirement to generate a new key (not reuse the old one) and update Peppol Directory if the certificate fingerprint changes (July 2026)

[AS4 Phase 2 — Message Log Dashboard & Console Tooling](docs/AS4_BILATERAL_ROADMAP.md) — `As4MessageController` with `/as4/messages` index (state-badged table: pending/sent/receiptReceived/delivered/failed/duplicate/received with Bootstrap colour coding) and `/as4/messages/view/{id}` detail page (6 cards: Identity, Routing, Retry State, Receipt, Error, Timestamps); `PeppolMessageController` with `/peppol/messages` index (inv link, status badge, retry count, sent/delivered/created) and detail page (Identity, Delivery, Error, UBL XML collapsible with copy button); four authenticated routes added before the public `as4/receive` endpoint; `as4/retry` command calls `As4RetryEngine::processRetries()` and `detectMissingReceipts()` — cron `*/5 * * * *`; `as4/status` prints a per-state count table from `findAllMessages()`, warns if FAILED > 0; `as4/resend --message-id=<id>` confirms interactively, parses stored SOAP XML, calls sender, maps result to `markReceiptReceived()` or `markSent()`; `as4/monitor --signing-cert --warn-days=30` checks cert expiry via `openssl_x509_parse()`, counts FAILED messages, exits code 1 if any issue (cron-alert friendly); all four commands registered in `config/console/params.php`; Psalm errorLevel 1 clean (July 2026)

[AS4 Complete Implementation — Summary](docs/AS4_COMPLETE_SUMMARY.md) — Production-ready eDelivery AS4 2.0 implementation: class inventory, SOAP 1.2 envelope construction, ebMS3 UserMessage/SignalMessage, WS-Security Ed25519 signing + X25519 HKDF-AES-128-GCM encryption, outbound retry engine, inbound duplicate detection, receipt generation, and bilateral `localhost ↔ yii3i.online` test harness (July 2026)

[AS4 Complete Implementation Guide](docs/AS4_IMPLEMENTATION_README.md) — Directory-level reference for the AS4 stack: production checklist, environment variable reference (`AS4_SIGNING_KEY_PATH`, `AS4_PEER_ENDPOINT`, `AS4_RETRY_POLICY`, etc.), DI wiring in `config/common/di/as4.php`, quick-start commands, and swap path from bilateral test mode to real Peppol 4-corner SMP lookup (July 2026)

[AS4 Integration Checklist](docs/AS4_INTEGRATION_CHECKLIST.md) — Phase-by-phase verification checklist — Project Setup → Core Classes → Outbound Pipeline → Inbound Pipeline → Console Commands → Production — with `php yii` verification commands at each milestone (July 2026)

[AS4 Implementation Guide for eDelivery 2.0](docs/AS4_Implementation_Guide.md) — Complete XML and PHP reference for the eDelivery AS4 2.0 Common Profile: annotated SOAP 1.2 envelope, ebMS3 header structure, WS-Security BinarySecurityToken placement, Ed25519 signature computation, X25519 key agreement for payload encryption, and PHP class mapping to each XML layer (July 2026)

[RBAC Bridge Table — `user_rbac_link`](docs/RBAC_BRIDGE_TABLE.md) — `UserRbacLink` Cycle ORM entity bridges `userinv.user_id` (INT) to `yii_rbac_assignment.user_id` (VARCHAR); auto-increment `id` PK + UNIQUE indexes on `user_id` and `rbac_user_id`; FK `ON DELETE RESTRICT` prevents orphaned RBAC rows; `syncIfEmpty()` backfills from `AssignmentsStorageInterface::getAll()` on first `/invoice` load; new signups get a bridge row immediately via `assignSignupRole()` before admin activation; `AppConstants::ROLE_ADMIN/OBSERVER/ACCOUNTANT` constants eliminate S1192 duplication across 7 files; `REDIRECT_USERINV_INDEX` constant eliminates 14 occurrences in `UserInvController`; Psalm errorLevel 1 clean (July 2026)

[Credit Note Workflow — `read_only_toggle` Interaction](docs/INV_CREDIT_NOTE_WORKFLOW.md) — **Create Credit Invoice** toolbar button appears for any Sent / Viewed / Paid invoice (`reqStatusId() >= 2`) or when `is_read_only = true`, provided the user has `editInv` permission and no credit note already exists (`creditinvoice_parent_id = null`); `read_only_toggle` setting (Sent = Peppol Requirement, Paid = Relaxed/General Use) is prospective only — changing it does not retroactively update existing invoice records; widened credit-button condition from `=== 4` to `>= 2` so Sent/Viewed invoices with `is_read_only = false` no longer require a manual status-to-Paid workaround; `createCreditConfirm()` fixed to read `group_id` from request instead of hardcoded `4`; `GroupRepository::generateNumber()` now throws `\RuntimeException` with descriptive message when group is missing; SQL alignment queries documented for DB migration after setting change (July 2026)

[Batch Email — Send Selected Invoices to Clients](docs/INV_BATCH_EMAIL.md) — ☑️📧 **Email Client** toolbar button on `inv/index`; select invoices via checkboxes, confirmation modal shows per-invoice email preview, choose a **From email** from `FromDropDown` (➕ button adds a new verified sender and redirects back to the modal via `?openModal=batchEmail`), choose an email template, confirm → **one email per selected invoice** with its PDF attached, each invoice marked Sent (status 2), `InvSentLog` entries created; `{{{invoice_table}}}` placeholder renders a responsive HTML table; `FromDropDownRepository` wired through `InvIndexNavDeps → InvsListWidget → InvsToolbarParams → InvsToolbar`; `from_dropdown_id` overrides the UserInv / EmailTemplate from-address fallback; `MailerHelper::yiiMailerSend` signature changed from `?string` to `array $pdfPaths`; `InvBatchEmailDeps` value object avoids SonarQube S107; Psalm errorLevel 1 clean (June 2026)

[Quick Pay — Per-Row Inline & Bulk Toolbar Payment](docs/QUICK_PAY.md) — 💰 button added immediately right of the Status column on `inv/index`; clicking expands an HTMX inline form (date + bank ref) that calls `inv/quickpay`, saves a `Payment` via `PaymentService`, and recalculates `inv_amount` + status via `InvRecalculator`; fully-paid invoices show a `✅ YYYY-MM-DD` badge instead; ☑️💰 **Quick Pay** toolbar button opens a Bootstrap 5 modal for all checked invoices (`inv/bulkquickpay`); date input uses `showPicker()` for native calendar on click; `Payment` `BelongsTo` annotation corrected from `nullable: false` → `nullable: true` (DB column was already nullable) fixing `Cycle\ORM\Exception\Relation\NullException` when invoice has no payment method set; three new GET routes; TypeScript `handleBulkQuickPay()`; `quick.pay` / `bank.ref` translation keys; Psalm errorLevel 1 clean (June 2026)

[Invoice Copy — Spreadsheet Import](docs/INV_COPY_SPREADSHEET_IMPORT.md) — `#modal-copy-inv-multiple` on `inv/index` gains a CSV import section: download a four-column template (`date_created`, `note`, `same_amount`, `payment_date`), upload a filled CSV, preview parsed rows, then click **Import Spreadsheet** to bulk-copy selected invoices with per-row date, note, amount flag, and optional payment; `CsvDateNormaliser` round-trip validates six input formats (`Y-m-d`, `d/m/y`, `d/m/Y`, `m/d/Y`, `d-m-Y`, `d.m.Y`) to prevent PHP `createFromFormat` month-overflow silently producing wrong dates; `PaymentService::savePayment` guarded against `false` from `createFromFormat`; endpoint uses GET with `rows_json` param matching the `multiplecopy` pattern (bypasses CSRF and Apache redirect issues); `parseCopyCsv()` auto-detects `,` or `;` delimiter; 10 Testo tests in `CsvDateNormaliserTest`; Psalm errorLevel 1 clean; TypeScript bundle rebuilt 146.4 kb (June 2026)

[`.env` Overwritten on `git pull` — Untrack from Git Index](docs/ENV_GIT_TRACKING_FIX.md) — `.env` was committed to the repo despite its `.gitignore` entry; once tracked, `.gitignore` is ignored by git, so every pull overwrites production credentials; fix: `git rm --cached .env` removes it from the index without touching the file on disk; `.gitignore` then takes effect permanently (June 2026)

[Invoice Index Filters Broken — `RequestInputParametersResolver` Missing](docs/INV_INDEX_FILTER_FIX.md) — All `inv/index` filter dropdowns (invoice number, client, status, year-month, family name, etc.) silently returned the full unfiltered row set on every selection; root cause: `InvIndexFilter` implements `RequestInputInterface` with class-level `#[FromQuery]` but `RequestInputParametersResolver` was never added to `CompositeParametersResolver` in `config/web/di/middleware-dispatcher.php` — Yii3 DI therefore injected a blank DTO with all properties `null`, `isset(null)` is always `false`, so no filter branch ever fired; fix: one line adding `Reference::to(RequestInputParametersResolver::class)` to the composite (vendor DI definition already present); secondary fix: `$nameParts[1] ?? ''` guard in `InvFilterTrait` for single-word client names; Psalm errorLevel 1 clean (June 2026)

[Testo Integration — PHP Testing Framework](docs/TESTO_INTEGRATION.md) — Testo (`php-testo/testo`) runs alongside PHPUnit; `#[Test]` attribute style matches Cycle ORM entity mapping; `testo/facade` + `yiisoft/injector` injects real Yii3 services into test methods, eliminating mock boilerplate for integration tests; `#[ExpectException]` accepts class only — message assertions use try/catch; no mock library yet (roadmap issue #41 — Mockery bridge); migration strategy: DDD entity + helper tests migrate now, service/controller tests stay in PHPUnit until #41; dual runner in `composer.json`; SonarCloud merges both Clover coverage files; two working examples in `Tests/Testo/` (`FamilyTest`, `CacheDiTest`) (June 2026)

[Invoice Copy — Multi-Client Selection & Workflow Badge Fix](docs/INV_COPY_MULTI_CLIENT.md) — `#modal-copy-inv-multiple` on `inv/index` extended with a live-filter client checkbox list so selected invoices can be copied to any combination of clients in one step; `ProductClient` pivot synced after each copy; `multiplecopy()` falls back to the invoice's own client when no selection is made; workflow-type badge bug fixed — `getSoId() !== null` → `> 0` so plain invoices copied with `so_id = 0` no longer show the 🔀 Peppol badge; modal rewritten to `Yiisoft\Html\Html as H` conventions; TypeScript bundle rebuilt (144.1 kb); Psalm errorLevel 1 clean (June 2026)

[PBES2 p2c Unbounded Iteration Count — CPU-Amplification DoS](docs/PBES2_P2C_DOS_ADVISORY.md) — `web-token/jwt-framework` transitive dep (via `rossaddison/yii-auth-client`); GHSA-3prj-6hqw-cm82; affected `<= 4.1.6`; installed `4.1.7` already contains the fix — `DEFAULT_MAX_COUNT = 1_000_000` constant and `p2c > max_count` guard in `PBES2AESKW::checkHeaderAdditionalParameters()` enforced before any `hash_pbkdf2()` call; project also never registers PBES2 algorithms; no PR required; logged in `snyk-resolved.db` as resolved (June 2026)

[JWT Framework — JWE Algorithm Confusion Fix](docs/JWT_FRAMEWORK_ALGORITHM_CONFUSION_FIX.md) — `web-token/jwt-framework` transitive dependency (via `rossaddison/yii-auth-client`); `JWSVerifier` already safe; `JWEDecrypter` passed merged protected+unprotected header to `getKeyEncryptionAlgorithm()` and `getContentEncryptionAlgorithm()` — last-wins `array_merge()` allowed attacker to override `alg`/`enc` via unprotected header (TOCTOU split, RFC 7516 §4.1.1/§4.1.2); fix reads both parameters exclusively from `getSharedProtectedHeader()`; `$completeHeader` preserved for `decryptCEK()` (ECDH `epk`/`apu`/`apv` legitimately in unprotected headers); `is_string()` guards added; contributed upstream as [web-token/jwt-framework #658](https://github.com/web-token/jwt-framework/pull/658) (June 2026)

[Dev Tools Console Improvements](docs/DEV_TOOLS_CONSOLE_IMPROVEMENTS.md) — Windows `proc_open` colour fix (CRT vs Win32 env blocks; `FORCE_COLOR` passed explicitly as 5th arg); ANSI background codes 40–107 + underline added so Psalm's green/red summary blocks render correctly; OSC 8 terminal hyperlinks converted to clickable HTML anchors (`composer outdated` package names open GitHub); **Copy** button on output panel (plain-text clipboard, green "Copied!" flash); SonarCloud *Filter by Rule Key* cascading dropdowns — language selector triggers live `?api=failing_rules` fetch, rule-number dropdown populated with only the `S####` codes currently failing for that language (June 2026)

[Dev Tools Web UI — `m.bat` / `m.php`](docs/DEV_TOOLS_WEB_UI.md) — `m.bat` replaced by a PHP built-in web server (`php -S 127.0.0.1:8099 m.php`) eliminating all batch-file stdin issues; 16 category submenus (Psalm, Composer, Node, TypeScript, Angular, Testing, Snyk, PHP-CS-Fixer, PHPCS, Rector, SonarCloud, Yii, GitHub, Peppol, Benchmarks, System); streaming output via `proc_open()` + `ReadableStream`; ANSI colour rendering; Bootstrap 5.3 dark theme; session-stored SonarCloud/GitHub tokens; Snyk Resolved Vulnerabilities Index (SQLite, seeded from `.snyk`, committed to repo, CWE advisory links); SQLite setup guide distinguishing CLI PHP from WAMP Apache PHP; 16 local SVG menu icons (Simple Icons for brands, Bootstrap Icons for generics, official Yii3 logo in brand colours) served statically via built-in server passthrough; Bootstrap 5 hover/focus popovers on each category card listing every submenu command for discoverability (June 2026)

[AS4 Access Point — Bilateral & Peppol Roadmap](docs/AS4_BILATERAL_ROADMAP.md) — Living roadmap for the native AS4 Access Point built in PHP; outbound stack complete (`As4RetryEngine`, `CycleOrmAs4MessageRepository`, `As4RetryPolicyInterface`, `As4SenderInterface`, ebMS3 signal detection, atomic concurrency claim, 15 PHPUnit tests); Phase 1 plans the inbound pipeline (`As4Receiver`, `As4SignatureVerifier`, `As4DuplicateDetector`, `As4ReceiptGenerator`, `As4ReceiveController`) for bilateral testing between `localhost` and `yii3i.online` without Peppol PKI; Phase 2 maps the small delta to a full Peppol 4-corner Access Point (SMP lookup already built, Peppol-issued certificate + SML registration + EFTIA conformance remaining) (June 2026)

[Peppol BIS Payload Validator — Schematron Caching](docs/PEPPOL_BIS_PAYLOAD_VALIDATOR_CACHING.md) — `PeppolBisPayloadValidator` caches the parsed `SchematronDocument` in a static property keyed by file path so only the first `validate()` call in a PHP-FPM worker pays the parse cost; every subsequent request in that worker reuses it at zero cost; cache survives across instances (a static property, not instance-bound, so it works regardless of DI singleton configuration) but is wiped on worker recycle/deploy; keyed by path so multiple `.sch` files coexist without collision; tests must call `clearCache()` in `tearDown()` to avoid one test's parsed document leaking into the next (June 2026)

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

[SonarQube S1144 — False Positive: Private Methods Called Across Trait Boundaries](docs/SONARQUBE_S1144_TRAIT_BOUNDARY.md) — SonarQube cannot trace `$this->method()` calls that cross PHP trait file boundaries, so it incorrectly reports `private` class methods as unused when their only callers live in a composed trait; concrete example: `displayEditDeleteButtons` and `flashNoEnabledGateways` in `InvController.php` called from `View.php`, and `redirectToAdminMustMakeActive` in `AuthController.php` called from `Callback.php`; fix: change `private` to `protected` — S1144 only fires on private methods (June 2026)

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

[FastRoute Dispatch Cache](docs/ROUTER_CACHE.md) — `UrlMatcher` PSR-16 cache wiring: `CacheInterface` → `FileCache` → `runtime/cache/routes-cache`; cache disabled in `dev` via `common/params.php`, enabled in `prod` via `environments/prod/params.php`; `YII_ENV` environment variable drives which params file is loaded; **new routes return 404 after `git pull` until `rm -rf runtime/cache/*` is run on the server** — add to deploy script before PHP-FPM restart; benchmark context explaining why the Windows dev figures include compilation overhead that disappears in production (June 2026)

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

[Alpine Linux CVE-2026-31431 Remediation](docs/ALPINE_LINUX_CVE_2026_31431.md) — local privilege escalation via `algif_aead` kernel interface; immediate mitigation (`/etc/modprobe.d/disable-algif.conf`); kernel upgrade from 6.12.49 to 6.18.29 via `apk`; OpenRC Apache restart commands; post-reboot verification (May 2026)

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

## Stage 1: Suitability Assessment for VAT Test Suite

This section records the pre-conditions and steps required before a VAT quarterly
submission can be tested end-to-end against the HMRC Making Tax Digital (MTD) API
using the Developer Sandbox environment.

### Critical: Fix Production URL Bug First

`HmrcController::vatObligations()` and `HmrcController::vatReturnSubmit()` currently
call `https://api.service.hmrc.gov.uk` (production). A sandbox OAuth2 token is
rejected at production endpoints. Before any VAT testing can succeed, those URLs must
be switched to `https://test-api.service.hmrc.gov.uk` when operating in sandbox mode.
`DeveloperSandboxHmrc::setEnvironment()` already exists for this purpose but is not
yet wired through to the controller HTTP calls.

### Step 1 — Register on the HMRC Developer Hub

1. Go to [https://developer.service.hmrc.gov.uk](https://developer.service.hmrc.gov.uk)
   and sign in (or create an account).
2. Create a new **Sandbox** application.
3. Set the **redirect URI** to match `DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_RETURN_URL`
   in `.env` (e.g. `https://yii3i.online/callbackDeveloperGovSandboxHmrc`).
4. Copy the new **client ID** and **client secret** into `.env`:
   ```
   DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_ID=...
   DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_SECRET=...
   DEVELOPER_GOV_SANDBOX_HMRC_API_CLIENT_RETURN_URL=...
   ```

### Step 2 — Subscribe to All Relevant APIs

Subscribe to every API below in the sandbox application. HMRC silently drops unsubscribed
scopes from the token response, so subscribing to all now costs nothing and means the
`HmrcApiCatalogue` can show the full grant without a second OAuth round-trip.

| API Name | Scope(s) | Identifier | Why Relevant |
|---|---|---|---|
| **VAT (MTD)** | `read:vat`, `write:vat` | VRN | Core — obligations retrieval and VAT100 return submission (Boxes 1–9) |
| **Self Assessment (Individual)** | `read:self-assessment`, `write:self-assessment` | NINO | Income and expenses for sole traders issuing invoices |
| **Self-Employed Business** | `read:self-employment`, `write:self-employment` | NINO | Used in `HmrcController::selfEmploymentBusinesses()` |
| **Business Details** | `read:self-assessment` | NINO | Business name and address on returns |
| **Individual Calculations** | `read:self-assessment`, `write:self-assessment` | NINO | Tax calculation results |
| **Income Received** | `read:self-assessment`, `write:self-assessment` | NINO | Invoice income classification |
| **National Insurance Record** | `read:national-insurance-record` | NINO | NI contributions for self-employed |
| **Customs Declarations** | `write:customs-declaration` | EORI | Import/export (future use) |
| **Create Test User** | _(sandbox utility)_ | — | Required to generate sandbox VRN/NINO individuals via `createTestUserIndividual()` |
| **Fraud Prevention Headers — Validate** | _(sandbox utility)_ | — | Used in `HmrcController::fphValidate()` |
| **Fraud Prevention Headers — Feedback** | _(sandbox utility)_ | — | Used in `HmrcController::fphFeedback()` |

### Step 3 — What Happens After Subscribing

Once the sandbox application is subscribed and the OAuth2 flow is completed via
`/auth/authclient?authclient=developersandboxhmrc`, the session stores `hmrc_scope`
containing all granted scopes. `HmrcController::index()` then displays the full
`HmrcApiCatalogue` with available APIs highlighted.

The VAT test sequence after that point is:

1. `createTestUserIndividual` — creates a sandbox individual with a **VRN** and **NINO**;
   the sandbox auto-generates open quarterly obligations for that VRN.
2. Store the VRN in **Settings → VAT Registration Number**.
3. `vatObligations()` — retrieves open quarterly obligations for the VRN.
4. `vatReturnPrepare()` — auto-fills Box 1 (output VAT) and Box 6 (sales ex-VAT) from
   `InvAmountRepository::repoVatTotalsForPeriod()`, and Box 4 (input VAT) and Box 7
   (purchases ex-VAT) from `PurchaseEntryRepository::repoVatTotalsForPeriod()`.
   Boxes 3 and 5 are computed client-side (JS). Boxes 2, 8, and 9 require manual entry.
5. `vatReturnSubmit()` — POSTs the nine-box `returnData` payload to the sandbox endpoint
   and receives a `processingDate` confirmation in the response.

### Stage 2 Preview — PHPUnit Test Data Suite

Once the sandbox URL bug is fixed and credentials are configured, Stage 2 will add:

- **`InvAmountRepository::repoVatTotalsForPeriod()` PHPUnit tests** — seed known invoices
  (status 2/3/4) within a fixed quarter and assert Box 1 and Box 6 totals.
- **Purchase entry fixtures** — seed `PurchaseEntry` rows with known VAT amounts and
  assert Box 4 and Box 7 totals.
- **Edge-case coverage** — zero-rated lines, invoices outside the quarter, draft invoices
  (status 1, excluded), and mixed VAT rates.

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
