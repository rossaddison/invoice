# HTMX and HTTP Caching

## Overview

HTMX replaces full-page navigations with targeted XHR requests that swap a
fragment of the DOM.  Because the browser URL can still change (via
`hx-replace-url`), the same URL now serves two distinct response shapes — a
full HTML page and a bare HTML fragment — and any cache layer that does not
account for this will serve the wrong shape to the wrong caller.

---

## 1. The Core Problem: Two Response Shapes at One URL

`hx-replace-url: true` keeps the address bar in sync after every HTMX swap.
So `quote/index?groupBy=status` is legitimately reached by:

| Caller | Expected response |
|---|---|
| Browser navigation / hard refresh | Full HTML page (layout + grid) |
| HTMX XHR (sort, page, filter) | Bare `<div id="QuotesGridView">…</div>` fragment |

Both requests hit the same URL.  Without cache differentiation, whichever
response is stored first will be served to both callers — a fragment injected
as a full page, or a full page injected into a `<div>`, are both broken.

---

## 2. The Fix: `Vary: HX-Request`

The standard HTTP mechanism for this is the `Vary` header.  A CDN or reverse
proxy that sees `Vary: HX-Request` on a response stores **separate cache
entries** for requests that carry `HX-Request: true` and those that do not.

```
HTTP/1.1 200 OK
Content-Type: text/html; charset=utf-8
Vary: HX-Request
```

In a Yii3 / PSR-7 controller, add it to the response:

```php
// In HtmlResponseFactory path (HTMX branch)
return $this->htmlResponseFactory
    ->createResponse($widget->render())
    ->withHeader('Vary', 'HX-Request');
```

```php
// In the full-page view path
return $this->viewRenderer
    ->render('index', $params)
    ->withHeader('Vary', 'HX-Request');
```

> **Both** response types must carry the header, otherwise a proxy that
> receives the full-page response first will cache it without the `Vary` hint
> and never store the fragment separately.

---

## 3. Browser Caching of HTMX GET Requests

HTMX sort, pagination, and filter requests are plain XHRs (`hx-boost` on
`<a>` tags).  Browsers respect standard cache headers on XHR responses.

| Scenario | Recommended header |
|---|---|
| Frequently changing data (quote list) | `Cache-Control: no-cache` |
| Stable reference data | `Cache-Control: max-age=60` (or appropriate TTL) |
| Conditional re-validation | `ETag` + `Last-Modified` |

For the quote list, which can change any time a quote is created or updated,
`no-cache` (revalidate on every request) is safest.  `no-store` is a stronger
option if the data is sensitive and must never be written to disk.

---

## 4. POST Requests Are Exempt

The filter form uses `hx-boost` on a `<form>` element.  HTMX submits this as
a `POST`.  Browsers and CDNs do not cache POST responses, so the filter path
has no caching risk.

---

## 5. In This Project — Current State

The `quote/index` controller trait detects the `HX-Request` header and
branches correctly:

```php
// src/Invoice/Quote/Trait/Index.php
if ($request->hasHeader('Hx-Request')) {
    return $this->htmlResponseFactory->createResponse(
        QuotesListWidget::widget()->...->render()
    );
}
// else: full view render via viewRenderer
```

The response shape is correct but **no `Vary: HX-Request` header is currently
set**.  This is safe in development (no caching proxy) but should be addressed
before deploying behind Nginx proxy caching, Varnish, Cloudflare, or any other
HTTP cache.

---

## 6. Nginx Configuration Example

If Nginx is used as a caching reverse proxy, configure it to vary the cache
key on the `HX-Request` header:

```nginx
proxy_cache_key "$scheme$request_method$host$request_uri$http_hx_request";
```

This appends the value of the `HX-Request` header to the cache key, so full
page requests and HTMX partial requests are stored separately without needing
the application to set `Vary`.

---

## 7. Hard Refresh After `hx-replace-url`

When a user presses Ctrl+F5 (hard refresh) on a URL that HTMX navigated to,
the browser issues a full-page GET with `Cache-Control: no-cache` and **no**
`HX-Request` header.  The server must return a complete page.

This already works correctly in this project: without the `HX-Request` header
the controller trait falls through to the normal `viewRenderer` path.

---

## 8. Summary Checklist

| Item | Status |
|---|---|
| Controller branches on `HX-Request` header | Done |
| Full-page and partial paths produce correct HTML shape | Done |
| `Vary: HX-Request` on responses | Not yet set — needed before adding a caching proxy |
| `Cache-Control` on quote list responses | Not yet set |
| Hard-refresh (no `HX-Request`) falls through to full page | Done |
| POST filter form exempt from caching concerns | Done (POST is never cached) |
