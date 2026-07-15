# Content Security Policy Updates

## Overview

This document tracks changes made to the Content Security Policy (CSP)
configuration in the application to accommodate third-party payment gateway
scripts and stylesheets.

## File Location

- **Configuration File**: `public/.htaccess`
- **CSP Header Section**: Lines 12-48

## Recent Updates

### Date: March 2, 2026

#### Changes Made

The following domains were added to the CSP directives to support payment
gateway integrations:

##### 1. Stripe Payment Integration

- **Directive**: `script-src`
- **Domain Added**: `https://js.stripe.com`
- **Reason**: Allow loading of Stripe's JavaScript library (v3)
- **Error Resolved**: "Loading the script 'https://js.stripe.com/v3/'
  violates the following Content Security Policy directive"

##### 2. Braintree Payment Integration

- **Directives**: `style-src`, `script-src`, `connect-src`
- **Domains Added**:
  - `https://assets.braintreegateway.com` (styles and assets)
  - `https://js.braintreegateway.com` (JavaScript)
  - `https://*.braintreegateway.com` (API connections)
- **Reason**: Allow loading of Braintree Drop-in UI and API communication
- **Error Resolved**: Multiple CSP violations for stylesheets, scripts, and
  API connections to both sandbox and production Braintree environments

##### 3. Amazon Pay Integration

- **Directive**: `script-src`
- **Domain Added**: `https://*.payments-amazon.com`
- **Reason**: Allow loading of Amazon Pay checkout scripts from all regional
  domains (EU, NA, FE, etc.)
- **Error Resolved**: "Loading the script
  'https://static-eu.payments-amazon.com/checkout.js' violates the following
  Content Security Policy directive"
- **Note**: Wildcard pattern used to cover all regional Amazon Pay endpoints

##### 4. Stripe iframe Support

- **Directive**: `frame-src`
- **Domains Added**: `https://js.stripe.com`, `https://*.stripe.com`
- **Reason**: Allow Stripe to load secure iframes for payment input fields
- **Error Resolved**: "Framing 'https://js.stripe.com/' violates the
  following Content Security Policy directive"
- **Note**: Wildcard pattern covers all Stripe subdomains for payment elements

##### 5. Comprehensive Stripe CSP Configuration

- **Multiple Directives Updated**: `script-src`, `img-src`, `connect-src`,
  `frame-src`, `child-src`
- **Approach**: Explicitly define all Stripe domains across relevant directives
  while maintaining `default-src 'self'`
- **Reason**: Stripe Elements use iframes hosted on Stripe's domain for
  PCI-DSS compliance - credit card data never touches your server
- **Security Benefit**: `default-src 'self'` remains in place as a secure
  fallback for unspecified directives
- **Additional**: Added `object-src 'none'` to prevent legacy plugin content
  (Flash, Java applets)

##### 6. CDN Source Map Support

- **Directive**: `connect-src`
- **Domain Added**: `https://cdn.jsdelivr.net`
- **Reason**: Allow loading of CSS/JS source maps from jsDelivr CDN for
  debugging
- **Error Resolved**: "Connecting to
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css.map'
  violates the following Content Security Policy directive"

## CSP Configuration as of March 2026 (superseded — see July 2026 section below)

> This `.htaccess`-only snapshot is historical. `script-src` no longer
> carries `'unsafe-inline'`/`'unsafe-eval'` as of the July 2026 fix further
> down this document — see
> [Full removal of script-src `unsafe-inline`/`unsafe-eval` (July 2026)](#full-removal-of-script-src-unsafe-inlineunsafe-eval-july-2026).
> The current, accurate policy lives in `config/web/params.php`'s `'csp'`
> key (mirrored in `public/.htaccess`), not in this code block.

```apache
Header always set Content-Security-Policy "\
default-src 'self'; \
script-src 'self' 'unsafe-inline' 'unsafe-eval' \
  https://apis.google.com \
  https://cdn.jsdelivr.net \
  https://js.stripe.com \
  https://*.stripe.com \
  https://*.payments-amazon.com \
  https://assets.braintreegateway.com \
  https://js.braintreegateway.com; \
style-src 'self' 'unsafe-inline' \
  https://fonts.googleapis.com \
  https://cdn.jsdelivr.net \
  https://assets.braintreegateway.com; \
font-src 'self' \
  https://fonts.gstatic.com \
  https://cdn.jsdelivr.net; \
img-src 'self' data: blob: https: https://*.stripe.com; \
connect-src 'self' \
  https://api.storecove.com \
  https://api.stripe.com \
  https://*.stripe.com \
  https://cdn.jsdelivr.net \
  https://*.braintreegateway.com; \
frame-src 'self' \
  https://js.stripe.com \
  https://*.stripe.com \
  https://hooks.stripe.com \
  https://assets.braintreegateway.com; \
child-src 'self' https://js.stripe.com https://*.stripe.com; \
form-action 'self'; \
base-uri 'self'; \
object-src 'none'; \
manifest-src 'self'; \
worker-src 'self'"
```

**Key Security Features:**

- **`default-src 'self'`** - Secure fallback for unspecified directives
- **`object-src 'none'`** - Blocks legacy plugins (Flash, Java)
- **`base-uri 'self'`** - Prevents base tag hijacking
- **`form-action 'self'`** - Forms can only submit to same origin

## Payment Gateway Domains Summary

| Payment Gateway | CSP Directive | Domain(s) | Purpose |
|----------------|---------------|-----------|---------|
| **Stripe** | `script-src` | `https://js.stripe.com`,<br>`https://*.stripe.com` | JavaScript SDK<br>and scripts |
| **Stripe** | `connect-src` | `https://api.stripe.com`,<br>`https://*.stripe.com` | API connections<br>and tokenization |
| **Stripe** | `frame-src` | `https://js.stripe.com`,<br>`https://*.stripe.com`,<br>`https://hooks.stripe.com` | Secure payment<br>iframes & webhooks |
| **Stripe** | `child-src` | `https://js.stripe.com`,<br>`https://*.stripe.com` | Child browsing<br>contexts |
| **Stripe** | `img-src` | `https://*.stripe.com` | Payment method<br>icons and images |
| **Braintree** | `style-src` | `https://assets.braintreegateway.com` | Drop-in UI CSS |
| **Braintree** | `script-src` | `https://assets.braintreegateway.com`,<br>`https://js.braintreegateway.com` | Drop-in UI scripts |
| **Braintree** | `connect-src` | `https://*.braintreegateway.com` | API connections<br>(sandbox & production) |
| **Braintree** | `frame-src` | `https://assets.braintreegateway.com` | Payment iframes |
| **Amazon Pay** | `script-src` | `https://*.payments-amazon.com` | Regional checkout scripts |
| **StoreCove** | `connect-src` | `https://api.storecove.com` | E-invoicing API |
| **jsDelivr CDN** | `script-src`,<br>`style-src`,<br>`font-src`,<br>`connect-src` | `https://cdn.jsdelivr.net` | Bootstrap,<br>libraries, and<br>source maps |

## Security Considerations

### Maintaining default-src 'self' with Payment Gateways

**Why it's safe to keep `default-src 'self'`:**

- Payment gateways like Stripe use **iframe-based tokenization** for PCI-DSS
  compliance
- Credit card data is entered in iframes hosted on Stripe's domain
  (`https://js.stripe.com`)
- Your server never touches raw credit card data - only secure tokens
- By explicitly allowing Stripe domains in `frame-src`, `script-src`, and
  `connect-src`, the integration works securely
- `default-src 'self'` provides a secure fallback for any directives not
  explicitly defined

**Payment Gateway Security Model:**

1. User enters card details in Stripe-hosted iframe (on Stripe's domain)
2. Stripe tokenizes the data and returns a secure token
3. Your application sends only the token to your server
4. Your server uses the token to process payment via Stripe API

This architecture ensures PCI compliance without your server handling
sensitive card data.

### Unsafe Directives (status as of July 2026)

- **`'unsafe-inline'` in `script-src`**: ✅ Removed July 2026 — see the
  section further down this document.
- **`'unsafe-eval'` in `script-src`**: ✅ Removed July 2026 — turned out not
  to be needed by any payment gateway; the actual dependency was htmx's
  internal `hx-on:` implementation, migrated away instead.
- **`'unsafe-inline'` in `style-src`**: still present, deliberately —
  Bootstrap 5 injects inline styles at runtime. Not part of the July 2026
  fix's scope.

### Recommendations for Future Hardening

1. ~~Consider implementing CSP nonces for inline scripts instead of
   `'unsafe-inline'`~~ — done differently: full externalization to a bundled
   TS module or a `type="application/json"` data island, no nonce
   infrastructure needed. See the July 2026 section below.
2. ~~Evaluate if `'unsafe-eval'` is necessary for all payment gateways -
   gradually remove if possible~~ — done; it wasn't needed by any payment
   gateway.
3. Regularly review Stripe, Braintree, and Amazon Pay documentation for any
   new required domains
4. Monitor browser console for CSP violations during testing
5. Consider adding Subresource Integrity (SRI) hashes for external scripts
   where possible
6. Set up CSP reporting endpoint to track violations in production
7. Test payment flows thoroughly after any CSP changes

## Related Files

### Payment Gateway Assets

- **Layout files**:
  - `resources/views/layout/guest.php`
  - `resources/views/layout/invoice.php`
- **Asset classes**:
  - `App\Invoice\Asset\pciAsset\stripe_v10_Asset`
  - `App\Invoice\Asset\pciAsset\amazon_pay_v2_7_Asset`
  - `App\Invoice\Asset\pciAsset\braintree_dropin_1_33_7_Asset`

## Testing

After CSP updates:

1. Clear browser cache
2. Hard refresh the payment pages (`Ctrl+F5`)
3. Verify no CSP violations in browser console
4. Test payment gateway initialization on invoice payment pages

## Additional Resources

- [MDN: Content Security Policy](
  https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
- [Stripe.js Documentation](https://stripe.com/docs/js)
- [Braintree Drop-in UI](
  https://developers.braintreepayments.com/guides/drop-in/overview/javascript/v3)
- [Amazon Pay Integration Guide](
  https://developer.amazon.com/docs/amazon-pay-checkout/introduction.html)

---

## PSR-15 Middleware Migration (June 2026)

### Background

The `.htaccess` approach from March 2026 required Apache-specific configuration
and used `'unsafe-inline'` and `'unsafe-eval'` in `script-src`. The June 2026
implementation replaces that with a PSR-15 middleware injected into the Yii3
middleware stack — independent of the web server, compatible with any SAPI.

This was prompted by two CodeQL alerts (#194 XSS, #195 incomplete URL check) in
the bundled htmx IIFE. Those alerts are in third-party bundled source (excluded
from CodeQL via `paths-ignore`), but the correct defence-in-depth response was a
strict CSP that neutralises injected scripts even if htmx were exploited.

### Architecture

| Component | Path |
|-----------|------|
| Middleware class | `src/Middleware/ContentSecurityPolicyMiddleware.php` |
| DI wiring | `config/web/di/content-security-policy.php` |
| Policy config | `config/web/params.php` — `'csp' => ['policy' => ...]` |

### Middleware class

```php
final class ContentSecurityPolicyMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $policy) {}

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        return $handler->handle($request)
            ->withHeader('Content-Security-Policy', $this->policy);
    }
}
```

The policy string is injected via Yii3 DI — the middleware class has no
knowledge of specific domains, so adding a payment provider requires only a
change to `config/web/params.php`.

### Stack position

```php
'middlewares' => [
    RequestCatcherMiddleware::class,
    ErrorCatcher::class,
    ContentSecurityPolicyMiddleware::class,   // ← here, before session/CSRF
    PrometheusMiddleware::class,
    SessionMiddleware::class,
    ...
]
```

Placed immediately after `ErrorCatcher` so that error responses (400, 500 pages)
also carry the CSP header.

### Current policy directives

| Directive | Value | Reason |
|-----------|-------|--------|
| `default-src` | `'self'` | Block everything external by default |
| `script-src` | `'self'` | Only the IIFE bundle; neutralises XSS injection |
| `style-src` | `'self' 'unsafe-inline'` | Bootstrap 5 injects inline styles at runtime |
| `img-src` | `'self' data: blob:` | `data:` for QR codes and base64 charts |
| `font-src` | `'self' data:` | Embedded web fonts |
| `connect-src` | `'self'` | htmx AJAX stays on-origin |
| `form-action` | `'self'` | Forms may not POST off-site |
| `frame-ancestors` | `'none'` | Clickjacking prevention |
| `base-uri` | `'self'` | Block `<base>` tag injection |
| `object-src` | `'none'` | No plugins (Flash, Java applets) |

### Adding payment provider domains

When a payment integration is activated, append its domains in
`config/web/params.php` inside the `implode('; ', [...])` array:

```php
'csp' => [
    'policy' => implode('; ', [
        "default-src 'self'",
        "script-src 'self' https://js.stripe.com https://*.stripe.com",
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data: blob: https://*.stripe.com",
        "font-src 'self' data:",
        "connect-src 'self' https://api.stripe.com https://*.stripe.com",
        "frame-src 'self' https://js.stripe.com https://*.stripe.com",
        "form-action 'self'",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "object-src 'none'",
    ]),
],
```

### Key improvement over .htaccess approach

| | `.htaccess` (March 2026) | PSR-15 (June 2026) |
|---|---|---|
| `script-src unsafe-inline` | Yes | **No** |
| `script-src unsafe-eval` | Yes | **No** |
| Web-server dependency | Apache only | Framework-level, any SAPI |
| Error-page coverage | Depends on Apache config | Always (before router) |
| Payment domain extension | Edit `.htaccess` | Edit `params.php` |
| Psalm / static analysis | Not applicable | Level 1 clean |

> **Note (July 2026):** the table above describes intent as of the June
> migration, but `'unsafe-inline' 'unsafe-eval'` had crept back into
> `script-src` in both `config/web/params.php` and `public/.htaccess` by the
> time of the July 2026 security audit — inline `<script>`/`onclick=`/htmx
> usage had grown back in across views without anyone re-extracting it. The
> section below is what actually removed them, for real this time, with the
> full list of what moved where.

---

## Full removal of script-src `unsafe-inline`/`unsafe-eval` (July 2026)

### Background

The July 2026 security audit (`docs/SECURITY_HARDENING_AUDIT_JULY_2026.md`,
finding #6) found `script-src 'self' 'unsafe-inline' 'unsafe-eval'` still in
both `config/web/params.php` and `public/.htaccess`. A first grep for literal
`<script` text undercounted badly — it missed every script built via PHP's
`Html::script()` helper, which never contains that literal string in the
view source. The real inventory: **12 files with inline script content, 11
with inline `onclick=` attributes, and 6 uses of htmx's
`hx-on::after-request`** — including both core layouts
(`layout/guest.php`, `layout/invoice.php`) wrapping every page in the app.

`'unsafe-eval'` turned out not to be decorative: htmx implements `hx-on:`
attributes internally via `new Function(...)`, so removing `unsafe-eval`
required migrating every `hx-on::after-request` usage first, or htmx's
attribute processing would have silently broken.

### The two migration patterns

Every inline script fell into one of two shapes, handled differently:

**1. Static logic → a TS module, self-initializing, no data to pass in.**
Moved verbatim (or lightly refactored) into a new or existing file under
`src/typescript/`, exporting an `init*()` function that guards itself by
checking for its own target DOM elements — safe to call unconditionally on
every page, since it's a no-op where those elements don't exist. Wired into
the `InvoiceApp` constructor in `src/typescript/index.ts` (or, for the two
auth pages that don't load the main bundle, into
`src/Auth/Asset/keypad-copy-to-clipboard.ts`).

**2. Inline `onclick=`/`hx-on::after-request` → delegated listener.**
Replaced the attribute with a `data-action="…"` / `data-confirm="…"` /
`data-hx-reset-on-success="true"` marker, and added the actual behavior once
as a single delegated `click` (or htmx `afterRequest`) listener in
`src/typescript/data-actions.ts` / `src/typescript/htmx-hooks.ts`, rather
than repeating inline handler code at every call site.

**3. Server-side dynamic data that used to be interpolated directly into an
executable inline `<script>`** (e.g. `$positionsJson` spliced into a
heredoc) → a `<script type="application/json">` **data island** instead —
inert as far as CSP `script-src` is concerned (browsers never execute
`application/json` script tags), built with
`Html::script($json)->type('application/json')` or
`Html::tag('script', $json, ['type' => 'application/json', 'id' => '...'])`,
and read back with `JSON.parse(document.getElementById('...').textContent)`
in the corresponding TS module.

### Where each inline script moved to

| Where it lived | What it did | Moved to |
|---|---|---|
| `resources/views/invoice/layout/alert.php` (inline `<script>`) | Flash-message countdown/pause timer, site-wide | `src/typescript/flash-message-timer.ts` |
| `resources/views/invoice/family/_form.php` (inline `<script>`) | Require product-prefix when a comma-list is set | `src/typescript/family-form-validation.ts` |
| `resources/views/invoice/info/javascript_analysis.php` (inline `<script>`) | Smooth-scroll nav highlighting (static FAQ page) | `src/typescript/faq-pages.ts` (`initJavascriptAnalysisFaq`) |
| `resources/views/invoice/info/codeception_selectors_checklist.php` (`Html::script()`) | Section show/hide nav (static FAQ page) | `src/typescript/faq-pages.ts` (`initCodeceptionChecklistFaq`) |
| `resources/views/invoice/customfield/_form.php` (`Html::script()`, dynamic data) | Position-selector options per table | `src/typescript/customfield-position.ts`, data via `#customfield-position-config` JSON island |
| `resources/views/auth/login.php` / `src/Auth/Controller/AuthController.php` (`fadeOutJS`, `Html::script()`) | Fade out the "TFA enabled" badge after 2s | `src/Auth/Asset/keypad-copy-to-clipboard.ts` (the smaller bundle the login page actually loads — found via real browser testing, see below) |
| `resources/views/layout/guest.php` + `layout/invoice.php` (`iPageSizeRefresh` function, duplicated in both) | Re-fetch `#main-area` after a page-size change | `src/typescript/htmx-hooks.ts` (`pageSizeRefresh`, via delegated `htmx:afterRequest`) |
| `resources/views/layout/invoice.php` (`NProgress.start()`/`.done()`, two `Html::script()` one-liners) | Page-load progress bar | `src/typescript/index.ts` (`InvoiceApp` constructor, called directly) |
| `resources/views/invoice/inv/guest.php` — `$invScript` (`InvoiceAmountMagnifier` + group-toggle, ~200 lines) | Hover-zoom on amount badges, collapsible invoice groups | **Deleted**, not moved — was a near-duplicate of `src/typescript/list-utils.ts` + `inv-index.ts` already used by the authenticated `inv/index.php`; `initInvIndex()` was parameterized (`tableId`, `configElId`) so the guest page now reuses the same code |
| `resources/views/invoice/inv/guest.php` — `$filterPromptScript` | Filter-dropdown prompt-option labels | `#inv-guest-filter-config` JSON island, consumed by the same `initInvIndex()` call above (mirrors `inv/index.php`'s pre-existing `#inv-filter-config` pattern) |
| `resources/views/invoice/inv/guest.php` — `$mobilePreviewScript` (`MobilePreviewToggle` class, ~100 lines) | Floating mobile-preview popup toggle | **Deleted**, not moved — same reasoning; `src/typescript/inv-index.ts`'s existing `MobilePreviewToggle` is instantiated once by the shared `initInvIndex()` call. The separately-dead `src/typescript/mobile-preview.ts` (an abandoned iframe-based redesign, never wired into the bundle) was deleted outright. |
| `resources/views/invoice/inv/index.php` / `quote/index.php` (`Html::script('InvoiceApp.initInvIndex()')` one-liners) | Kick off the above init on page load | Removed entirely — `index.ts` now detects `#table-invoice` / `#table-invoice-guest` / `#table-quote` and self-invokes |
| 10 files across `report/`, `salesorder/`, `setting/`, `_shared/`, `inv/` (`onclick="this.showPicker()"` / `window.close()` / `window.print()` / `return confirm(...)`) | Native date-picker, window close/print, delete confirmation | `data-action="show-picker"` / `"window-close"` / `"window-print"` / `data-confirm="…"` attributes, handled by one delegated listener in `src/typescript/data-actions.ts` |
| `family/_form.php`'s "Show Number Picker" button (`onclick="toggleCommalistPicker()"`) | Toggle the family comma-list number picker | `data-action="toggle-commalist-picker"`, same delegated listener calling the pre-existing `family-commalist-picker.ts` global |
| 4 item-form files (`invitem`/`quoteitem` `_item_form_product.php`/`_item_form_task.php`) — `hx-on::after-request="if(event.detail.successful) this.reset()"` | Clear the form after a successful htmx add | `data-hx-reset-on-success="true"` marker, handled by the delegated `htmx:afterRequest` listener in `htmx-hooks.ts` |
| `layout/guest.php` + `layout/invoice.php`'s page-size `<a hx-on::after-request="iPageSizeRefresh(this);">` (14 links each) | Trigger the page-size refresh above | Attribute removed entirely — the delegated listener in `htmx-hooks.ts` matches on the existing `#page-size-btn-group` container, no new marker needed |

Also touched: `src/typescript/list-utils.ts`'s `AmountMagnifier` selector list
was widened to match both `.badge.bg-success` and `.badge.text-bg-success`
(and the warning/danger variants) — the guest invoice table used the newer
Bootstrap 5.3 `text-bg-*` utility class the shared code didn't previously
check for, which would have silently made the magnifier a no-op there.

### htmx eval-path removed

`htmx.config.allowEval` is now set to `false` explicitly in
`src/typescript/htmx-hooks.ts` — defense in depth, now that nothing in the
app relies on htmx's `new Function(...)`-based `hx-on:` processing.

### Verification

This is a change where mistakes fail silently in the browser (a CSP
violation and a dead button), not as a server error — Psalm, PHPUnit, and
vitest all stayed green throughout even while a real bug was live. Caught
by installing Playwright, creating a throwaway account, and driving the app
in headless Chromium watching the DevTools console for CSP violations: the
login-page fix above (`fadeOutJS`) was found this way, since the login page
doesn't load the main bundle and the static file-based inventory had no way
to know that.

### One known exception — not fixable from this repo

The login page still shows 2 CSP violations, both from the `AuthChoice`
widget in `rossaddison/yii-auth-client` — a forked package installed via a
`vcs` repository in `composer.json`, not this project's own code. Patching
`vendor/rossaddison/yii-auth-client/src/Widget/AuthChoice.php` directly
wouldn't persist (lost on the next `composer update`, untracked in this
repo's git history). See `docs/SECURITY_HARDENING_AUDIT_JULY_2026.md`
finding #6 for the exact violation hashes and stopgap options.

### Follow-up regression — async CSS `onload=` handlers (found post-deploy)

The original inventory only looked for `<script>` blocks, `onclick=`, and
`hx-on:` — it missed a fourth category: an inline event handler on a
`<link>` tag, emitted by Yii's `AssetBundle` mechanism rather than hand-written
HTML.

Several `AssetBundle` classes loaded their CSS using the classic
non-render-blocking pattern:

```php
public array $cssOptions = [
    'media' => 'print',
    'onload' => "this.media='all'",
];
```

This renders as `<link rel="stylesheet" media="print" onload="this.media='all'">`
— the browser downloads the stylesheet without blocking render, and the
inline `onload=` handler flips `media` back to `all` once it's loaded. With
`script-src 'unsafe-inline'` removed, that `onload=` is silently blocked by
CSP, so `media` is stuck at `print` forever and the stylesheet never applies
to screen. Symptom: Bootstrap Icons (`bi-*`) disappearing site-wide (reported
as "lost the bootstrap5 cog" / "other icons have also disappeared"), plus
NProgress's CSS and the Stripe checkout CSS silently not applying.

Fixed by removing `cssOptions` from each affected bundle and loading the
stylesheet as a normal blocking `<link>` instead:

- `src/Invoice/Asset/NodeModulesBootstrapIconsAsset.php` (local/npm Bootstrap Icons)
- `src/Asset/AppCdnAsset.php` (CDN Bootstrap Icons)
- `src/Invoice/Asset/NProgressAsset.php`
- `src/Invoice/Asset/pciAsset/StripeVersionTenAsset.php`

Sibling payment-gateway bundles (`AmazonPayTwoSevenAsset`,
`BraintreeDropInOneThirtyThreeSevenAsset`) were checked and don't use this
pattern. A repo-wide `onload` grep across `src/` turned up no other
instances.

**Lesson for future CSP audits:** when hardening `script-src`, also grep
`AssetBundle` classes (`src/**/Asset/**/*.php`) for `cssOptions`/`jsOptions`
containing `onload`/`onerror`/`on*` — these are just as much an inline event
handler as one written directly in a view, but they don't show up in a
`<script>`/`onclick`/`hx-on` search of the view layer.
