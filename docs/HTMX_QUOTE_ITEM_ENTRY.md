# HTMX Quote Item Entry

## Overview

The quote view (`quote/view`) supports adding both product and task line items without
a full page reload. Submitting either entry form sends an HTMX-powered `POST` request;
on success the item table refreshes in-place and the form resets automatically. A
Bootstrap Icons spinner appears on the save button during the request so the user gets
immediate visual feedback.

---

## 1. Architecture

| Layer | File | Role |
|---|---|---|
| Controller | `src/Invoice/QuoteItem/QuoteItemHtmxController.php` | Lean HTMX-first POST endpoints |
| View fragment | `resources/views/invoice/quote/partial_item_table.php` | The HTML that HTMX swaps in |
| Product entry form | `resources/views/invoice/quoteitem/_item_form_product.php` | Product form with HTMX attributes |
| Task entry form | `resources/views/invoice/quoteitem/_item_form_task.php` | Task form with HTMX attributes |

The existing `quoteitem/addProduct` and `quoteitem/addTask` actions were dual-purpose
page actions (GET renders form, POST saves then redirects). Bolting HTMX onto a
redirect-based action causes the browser to auto-follow the 302 and HTMX then swaps
the full redirected page into the target `<div>` — replicating the entire layout inside
the item table area. A dedicated controller with POST-only actions avoids this entirely.

The controller lives in `App\Invoice\QuoteItem` (alongside `QuoteItemController` and
`QuoteItemService`) because it operates on quote items, not on quotes.

---

## 2. Routes

Added to `config/common/routes/routes.php`:

```php
Route::methods([$mP], '/quoteitemhtmx/addProduct')
    ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
    ->action([QuoteItemHtmxController::class, 'addProduct'])
    ->name('quoteitemhtmx/addProduct'),
Route::methods([$mP], '/quoteitemhtmx/addTask')
    ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
    ->action([QuoteItemHtmxController::class, 'addTask'])
    ->name('quoteitemhtmx/addTask'),
```

Both routes accept `POST` only — HTMX submissions, never browser navigation.
`$pEI` is the `edit-invoice` permission. Both routes follow the `quoteitemhtmx/`
prefix convention for consistency.

---

## 3. Controller (`QuoteItemHtmxController`)

Both public actions delegate the calculate-and-render step to a shared private helper,
eliminating duplication and keeping each action within SonarQube's complexity limits.

```php
public function addProduct(Request $request, FormHydrator $formHydrator, ...): Response
{
    $quote_id = (int) $this->session->get('quote_id');
    $form = new QuoteItemForm();

    if ($request->getMethod() === Method::POST && $request->hasHeader('Hx-Request')) {
        // Auto-set order so #[Required] on $order never blocks save
        $body = $request->getParsedBody() ?? [];
        if (is_array($body) && empty($body['order'])) {
            $body['order'] = (string) $qiR->repoQuotequery($quote_id)->count();
            $request = $request->withParsedBody($body);
        }
        if ($formHydrator->populateFromPostAndValidate($form, $request)) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $this->quoteItemService->addQuoteItemProduct(
                    new QuoteItem(), $body, (string) $quote_id,
                    $pR, $qiar, new QIAS($qiar, $qiR), $uR, $trR, $this->translator,
                );
                return $this->renderPartial($quote_id, ...repos...);
            }
        }
        return $this->htmlResponseFactory->createResponse('', 422);
    }
    return $this->webService->getRedirectResponse('quote/view', ['id' => (string) $quote_id]);
}

public function addTask(Request $request, FormHydrator $formHydrator, ...): Response
{
    $quote_id = (int) $this->session->get('quote_id');
    $form = new QuoteItemForm();

    if ($request->getMethod() === Method::POST && $request->hasHeader('Hx-Request')) {
        $body = $request->getParsedBody() ?? [];
        if (is_array($body) && empty($body['order'])) {
            $body['order'] = (string) $qiR->repoQuotequery($quote_id)->count();
            $request = $request->withParsedBody($body);
        }
        if ($formHydrator->populateFromPostAndValidate($form, $request)) {
            $body = $request->getParsedBody() ?? [];
            if (is_array($body)) {
                $this->quoteItemService->addQuoteItemTask(
                    new QuoteItem(), $body, $quote_id,
                    $taskR, $qiar, new QIAS($qiar, $qiR), $trR,
                );
                return $this->renderPartial($quote_id, ...repos...);
            }
        }
        return $this->htmlResponseFactory->createResponse('', 422);
    }
    return $this->webService->getRedirectResponse('quote/view', ['id' => (string) $quote_id]);
}

private function renderPartial(int $quote_id, ...repos...): Response
{
    $numberHelper = new NumberHelper($this->sR);
    $numberHelper->calculateQuote($quote_id, $acqR, $qiR, $qiar, $qtrR, $qaR, $qR);
    $quoteAmount = $qaR->repoQuoteAmountquery($quote_id);
    if ($quoteAmount === null) {
        return $this->htmlResponseFactory->createResponse()->withHeader('HX-Refresh', 'true');
    }
    $html = $this->webViewRenderer->renderPartialAsString(
        '//invoice/quote/partial_item_table', [ ...all partial parameters... ],
    );
    return $this->htmlResponseFactory->createResponse($html);
}
```

### Key decisions

**`Hx-Request` header guard** — the controller only enters the HTMX branch when the
`Hx-Request` header is present, so a plain browser `POST` falls through to the normal
redirect.

**Auto-order assignment** — `QuoteItemForm::$order` carries `#[Required]`, but neither
entry form renders an `order` field for the user. The controller reads the current item
count and injects it into `$body` before validation.

**422 on validation failure** — returning HTTP 422 instead of redirecting means HTMX
ignores the response and leaves the page untouched. The browser never follows a
redirect and the layout is never accidentally swapped into the target `<div>`.

**`HX-Refresh` fallback** — a defensive guard for the edge case where `QuoteAmount` is
absent after `calculateQuote()`. In normal operation this branch is never reached; it
exists to prevent a PHP fatal error on a null method call inside `partial_item_table.php`.

**Shared `renderPartial`** — both `addProduct` and `addTask` call the same private method
for the calculate → fetch → render pipeline. This eliminates duplicated code and keeps
each public action within SonarQube's cognitive-complexity limit of 15.

**`renderPartialAsString`** — renders the template without the application layout,
producing a pure HTML fragment suitable for HTMX `innerHTML` swap.

---

## 4. Forms

Both entry forms follow the same pattern. The form tag always carries HTMX attributes —
there is no setting toggle:

**Product form (`_item_form_product.php`)**

```php
echo (new Form())
    ->post($action)
    ->csrf($csrf)
    ->id('QuoteItemFormAddProduct')
    ->addAttributes([
        'hx-post'              => $action,
        'hx-target'            => '#partial_item_table_parameters',
        'hx-swap'              => 'innerHTML',
        'hx-indicator'         => '#quote-item-saving',
        'hx-disabled-elt'      => '#btn-quote-item-save',
        'hx-on::after-request' => 'if(event.detail.successful) this.reset()',
    ])
    ->open();
```

**Task form (`_item_form_task.php`)**

```php
echo (new Form())
    ->post($action)
    ->csrf($csrf)
    ->id('QuoteItemFormAddTask')
    ->addAttributes([
        'hx-post'              => $action,
        'hx-target'            => '#partial_item_table_parameters',
        'hx-swap'              => 'innerHTML',
        'hx-indicator'         => '#quote-task-saving',
        'hx-disabled-elt'      => '#btn-quote-task-save',
        'hx-on::after-request' => 'if(event.detail.successful) this.reset()',
    ])
    ->open();
```

| Attribute | Value | Purpose |
|---|---|---|
| `hx-post` | route URL | Sends POST to the HTMX endpoint |
| `hx-target` | `#partial_item_table_parameters` | Element whose `innerHTML` is replaced |
| `hx-swap` | `innerHTML` | Replaces inner content only, not the wrapper |
| `hx-indicator` | `#quote-item-saving` / `#quote-task-saving` | Shows the spinner span during the request |
| `hx-disabled-elt` | `#btn-quote-item-save` / `#btn-quote-task-save` | Disables the save button during the request |
| `hx-on::after-request` | `if(event.detail.successful) this.reset()` | Resets the form on success; leaves it populated on failure |

Each form has a unique indicator and button id so the two spinners do not interfere
with each other when both forms are on the page simultaneously.

---

## 5. Loading Indicator

Each form has its own spinner span placed immediately after its submit button:

```php
// Product form
Html::openTag('span', ['id' => 'quote-item-saving', 'class' => 'htmx-indicator ms-2'])
    new I()->addClass('bi bi-arrow-repeat spin')
Html::closeTag('span')

// Task form
Html::openTag('span', ['id' => 'quote-task-saving', 'class' => 'htmx-indicator ms-2'])
    new I()->addClass('bi bi-arrow-repeat spin')
Html::closeTag('span')
```

### CSS (in `src/Invoice/Asset/rebuild/css/form.css`)

```css
.htmx-indicator {
    opacity: 0;
    transition: opacity 200ms ease-in;
}
.htmx-request .htmx-indicator,
.htmx-indicator.htmx-request {
    opacity: 1;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
.spin {
    display: inline-block;
    animation: spin 0.8s linear infinite;
}
```

HTMX adds `.htmx-request` to the form and to any element matching `hx-indicator` while
the XHR is in flight. The spinner is the Bootstrap Icons `bi-arrow-repeat` glyph
animated by `@keyframes spin`.

---

## 6. Asset Loading

htmx 2.0.10 is bundled into `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js`
via the npm package `htmx.org`. The entry point `src/typescript/index.ts` imports
`./htmx.js` (compiled from `src/typescript/htmx.ts`), which pins the library on
`window.htmx`:

```typescript
import htmx from 'htmx.org';
window.htmx = htmx;
```

esbuild bundles and minifies the result as an IIFE. The invoice layout loads this
bundle unconditionally through `InvoiceNodeModulesAsset` or `InvoiceCdnAsset` —
no separate asset class is required for htmx.

To rebuild after TypeScript changes:

```bash
npm run build:typescript
```

Then copy the updated bundle to the published assets directory so the browser receives
the new file without a Yii3 cache clear:

```
src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js  →  public/assets/<hash>/rebuild/js/
```

---

## 7. Files Changed

| File | Change |
|---|---|
| `src/Invoice/QuoteItem/QuoteItemHtmxController.php` | **Created** — `addProduct`, `addTask`, and shared `renderPartial` |
| `config/common/routes/routes.php` | Added `POST /quoteitemhtmx/addProduct` and `POST /quoteitemhtmx/addTask` routes |
| `src/Invoice/Quote/Trait/View.php` | `actionName` updated for both forms to point at the new routes |
| `resources/views/invoice/quoteitem/_item_form_product.php` | HTMX attributes, spinner, `id` on save button |
| `resources/views/invoice/quoteitem/_item_form_task.php` | HTMX attributes, spinner, `id` on save button |
| `resources/views/invoice/quote/view_product_task_tabs.php` | Product tab pane given `active show` so form is visible on load |
| `src/Invoice/Asset/rebuild/css/form.css` | `.htmx-indicator` + `@keyframes spin` + `.spin` CSS added |
| `src/typescript/htmx.ts` | **Created** — imports htmx from npm and pins it on `window.htmx` |
| `src/typescript/index.ts` | Added `import './htmx.js'` so htmx is included in the compiled bundle |

---

## 8. Known Limitations and Future Work

**`findAllPreloaded()` called multiple times per save** — `partial_item_table.php`
requires `$products`, `$tasks`, `$units`, and `$taxRates` to render the existing-item
dropdowns in edit mode. Each call re-queries the database. For small catalogues this is
fine; for larger deployments a single-row append (sending only the new `<tr>` as the
swap target) with an OOB (`hx-swap-oob`) totals update would reduce this to zero
re-queries.

**`HX-Refresh` fallback is dead code in normal use** — `QuoteAmount` is always present
for quotes that have gone through the standard view flow, so the null guard never fires
in practice. It is kept as a crash-prevention safety net only.
