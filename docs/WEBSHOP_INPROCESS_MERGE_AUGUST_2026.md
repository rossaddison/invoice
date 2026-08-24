# Webshop In-Process Merge (August 2026)

## What changed

The standalone `rossaddison/webshop` storefront app (a separate Yii3
app, session-only, no database of its own, proxying this app over HTTP
via an API-key-authenticated `/api` surface — see
[`WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md`](WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md)/
[`STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md`](STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md))
has been folded directly into this app, as new public, unauthenticated
routes under `/shop`. The two-repo, HTTP-API architecture is retired;
`App\Webshop\*` now calls this app's own repositories/services
in-process instead of round-tripping JSON.

## Why

Every bug this app's own checkout handoff needed fixing this cycle
(a CSP misconfiguration blocking product images served from a different
origin, a DI-eagerness bug that broke session persistence, a route-
generation bug needing an explicit `_language` argument, an `Inv.user_id`
ownership mismatch, a `tfa_verified` gap crashing the invoice view) was
a direct consequence of the storefront and the invoicing app being two
separate processes/origins/sessions. Running the storefront in-process
removes that whole boundary — and with it, that whole bug class — and
makes the rest of this app's machinery (Sales Orders, PDFs, payment
gateways, Peppol) available to the storefront for free, without needing
a bespoke API endpoint built for each one.

## Where the code lives

```
src/Webshop/
  Controller/StorefrontController.php   Base for every public /shop controller
  StorefrontViewParameters.php          Cart/currency/delivery/login-state chrome data
  Cart/                                 CartService, CartController, CartItem, Asset/
  Catalog/                              ProductsController, ProductFilter, ProductListing,
                                         CatalogQueryService, Asset/
  Checkout/                             CheckoutController, CheckoutForm
  Currency/                             CurrencyContext, CurrencyInfoProvider,
                                         CurrencyPreferenceService, CurrencyController,
                                         CurrencyInfo, CurrencySymbols
  Delivery/                             DeliveryAddressService, DeliveryController,
                                         DeliveryAddress

resources/views/shop/                   catalog/, cart/, checkout/
resources/views/layout/templates/storefront/main.php   The /shop navbar/chrome
config/common/routes/routes-shop.php    Every /shop route
Tests/Testo/Webshop/                    Mirrors the src/Webshop/ tree
```

## Architectural decisions worth knowing

- **`/shop` sits outside `Group::create('/{_language}')`** (unlike every
  other route file, which `config/common/di/router.php` wraps in that
  group automatically) — same shape as this app's other already-public
  routes (`/go/{key}`, `/scan/{token}`). Links *within* `/shop` never
  need `_language`; linking *out* to an `/{_language}`-group route
  (`inv/view`, `inv/guest`, `auth/login` — see the storefront layout)
  still does, explicitly, every time.

- **`StorefrontController` does not extend `App\Invoice\BaseController`.**
  That class's `initializeViewRenderer()` picks one of three *staff*
  layouts by RBAC permission + `tfa_verified` — irrelevant to a
  never-logged-in storefront visitor. The actual precedent is
  `App\Controller\SiteController` (plain class, `WebViewRenderer` only).

- **The storefront layout is its own thing, not a fork of
  `soletrader/main.php`.** That layout is wired to ~30
  `ViewInjection`-supplied marketing/company/locale parameters the
  storefront has no use for; it was ported from the standalone webshop
  app's own `layout/main.php` instead, which already did everything a
  storefront chrome needs in far fewer parameters.

- **The storefront layout gets its chrome data via a
  `LayoutSpecificInjections`-scoped injection, not via `render()`'s own
  `$parameters` argument.** `WebViewRenderer::renderProxy()` renders the
  view and the layout as two genuinely separate passes — the layout only
  ever receives `['content' => ...]` directly; everything else it uses
  has to come from a registered `LayoutParametersInjectionInterface`
  (or `CommonParametersInjectionInterface`) injection. Confirmed live:
  the layout threw `Undefined variable $deliveryAddress` until
  `StorefrontViewParameters` was registered as one (`config/common/
  di/webshop.php` + `config/common/params.php`'s `yiisoft/
  yii-view-renderer.injections`), scoped to
  `@views/layout/templates/storefront/main.php` specifically so it
  never fires for the staff-facing layouts. Each storefront controller
  *also* spreads the same `StorefrontViewParameters::
  getLayoutParameters()` into its own `render($view, [...])` call,
  since the view template itself (e.g. `shop/catalog/index.php`)
  references `$currency` directly — both paths resolve the same
  service, just for the two different render passes.

- **`CurrencyContext` stays deliberately lazy** — `CurrencyInfoProvider`
  (and the session it reads/writes) is only ever touched from inside a
  request-handling method, never a container-build-time factory
  closure. This is inherited discipline, not new: an earlier version of
  this exact class (in the standalone webshop app) was once wired
  eagerly and broke session persistence outright — see the class's own
  docblock.

- **Checkout logs the customer in directly — no one-time token.**
  `App\Api\OrderService::createOrder()` now calls `AuthService::
  oauthLogin()` (the same call this app's other non-password logins
  already use) and sets `tfa_verified` on the session immediately after
  creating the invoice, then returns the invoice id. `CheckoutController::
  submit()` redirects straight to `inv/view/{id}`, already authenticated,
  in the same request/response cycle. `WebshopOrderLoginController` and
  its masked-token machinery are gone — there's no cross-app handoff
  left to bridge with one. Confirmed live end-to-end: add to cart → fill
  in checkout → submit → 302 straight to a real, viewable invoice
  (`inv/view` returns 200, not 404/500), with the "Returns & Orders" nav
  link switching from a `auth/login` link to the real `inv/guest` order
  history link in the very next page load — same session throughout.

- **Product images are genuinely same-origin now.** `CatalogQueryService::
  firstImagePath()` returns a bare `/products/{file}` path (the exact
  static-file convention `App\Api\ProductsController::firstImagePath()`
  used to build for the old cross-origin HTTP feed) — no base-URL
  prefixing, and none of the CSP `img-src` cross-origin allowlisting the
  standalone app needed.

- **Catalog filtering is still done in PHP over an already-fetched flat
  list** (`ProductFilter::apply()`), not a real SQL range/category
  filter on `ProductRepository` — this is the exact, proven behaviour
  the standalone app already had; moving from an HTTP round trip to a
  direct repository call is a strict improvement even keeping the same
  shape. Worth revisiting with real `ProductRepository` filter methods
  once catalog size actually warrants it — not done as part of this
  merge.

## What's explicitly out of scope here

- The `c:\wamp64\www\webshop` repository itself was not touched, deleted,
  or archived — that's a separate decision for whoever owns that repo.
- Cloudflare Turnstile bot-checking on `/shop/checkout` (like
  `/homecare-signup` has) — not added; parity with the standalone app's
  own checkout, which had no bot-check either.

## Decommissioned

`src/Api/OrdersController.php`, `src/Api/ProductsController.php` (the
HTTP wrapper — `ProductRepository` itself is unaffected, `CatalogQueryService`
now calls it directly), `src/Api/CurrencyController.php` (ditto,
`SettingRepository`), `src/Middleware/ApiKeyAuthMiddleware.php`,
`src/Api/WebshopOrderLoginController.php`, the `/api/products`/
`/api/orders`/`/api/currency` routes, `AppConstants::
TOKEN_TYPE_WEBSHOP_ORDER_LOGIN`, and their Testo coverage. `api/info`/
`api/user` are unrelated and untouched.
