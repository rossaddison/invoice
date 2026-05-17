# HTMX User Index — Sort, Pagination, and Page-Size Selector

## Overview

The user list (`user/index`) supports HTMX-powered sort and pagination: clicking a
column header or a pagination link sends an AJAX request and swaps only the grid
`<div>` in-place, without a full page reload. A page-size selector bar (identical to
every other index in the application) appears in the grid summary row and updates the
`default_list_limit` setting for all indexes at once.

---

## 1. Architecture

| Layer | File | Role |
|---|---|---|
| Widget | `src/User/Widget/UsersListWidget.php` | Builds and renders the `GridView` with HTMX attributes and the page-size selector |
| Controller | `src/User/Controller/UserController.php` | Detects `HX-Request` header; returns bare widget HTML for HTMX, full view otherwise |
| View | `resources/views/user/index.php` | Registers `HtmxAsset`; passes `$paginator` and `$s` to the widget |
| Utility | `src/Widget/PageSizeLimiter.php` | Static `buttons()` method; generates the limit-selector button row |

---

## 2. Widget Pattern

`UsersListWidget` extends `Yiisoft\Widget\Widget` and is instantiated via the
`WidgetFactory` / DI container. Cycle ORM repositories cannot be injected through
the widget factory (the factory calls `$container->has()` which returns `false` for
ORM-backed repos). The solution is the **immutable-setter** pattern:

```php
// Only auto-wirable dependencies in the constructor
public function __construct(
    private readonly CurrentRoute $currentRoute,
    private readonly UrlGeneratorInterface $urlGenerator,
    private readonly TranslatorInterface $translator,
) {}

// Paginator built by the controller and passed in
public function withPaginator(OffsetPaginator $paginator): static { ... }

// SettingRepository passed in (also an ORM-backed repo, same reason)
public function withSR(SR $sR): static { ... }
```

The controller builds the `OffsetPaginator` using action-injected `UserRepository`
(which works fine as an action parameter), then chains the setters:

```php
UsersListWidget::widget()->withPaginator($paginator)->withSR($this->sR)
```

---

## 3. Controller — HTMX Detection

```php
public function index(Request $request, UserRepository $userRepository): Response
{
    $paginator = (new OffsetPaginator($userRepository->findAllPreloaded()))
        ->withPageSize(max(1, (int) $this->sR->getSetting('default_list_limit')));

    if ($request->hasHeader('Hx-Request')) {
        return $this->htmlResponseFactory->createResponse(
            (UsersListWidget::widget()->withPaginator($paginator)->withSR($this->sR))->render()
        );
    }

    return $this->webViewRenderer->render('index', ['paginator' => $paginator]);
}
```

`HtmlResponseFactory` (`Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory`)
sends the raw HTML string. **Do not** use `DataResponseFactoryInterface` here — it
JSON-encodes the response body, breaking HTMX.

PSR-7 headers are case-insensitive, so `hasHeader('Hx-Request')` matches the
`HX-Request: true` header that HTMX sends with every XHR.

---

## 4. HTMX Attributes on the Grid

```php
$htmxAttrs = [
    'hx-indicator'   => '#UsersGridView',
    'hx-target'      => '#UsersGridView',
    'hx-replace-url' => 'true',
    'hx-swap'        => 'outerHTML',
];

GridView::widget()
    ->containerAttributes(['id' => 'UsersGridView', 'class' => 'mt-4 position-relative'])
    ->sortableLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
    ->filterFormAttributes(['hx-boost' => 'true', ...$htmxAttrs])
    ->paginationWidget(
        OffsetPagination::widget()->addLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
    )
```

`hx-boost="true"` on a sort or pagination `<a>` converts the browser navigation into
an AJAX GET. The response (just the widget `<div>`) replaces the existing
`#UsersGridView` element via `outerHTML`. `hx-replace-url` keeps the address bar in
sync so the browser back button works correctly.

`UrlParameterProvider` reads `?sort=` and `?page=` from `CurrentRoute` and applies
them to the `OffsetPaginator` inside `GridView::prepareDataReaderByParams()` — the
controller does not need to read query parameters manually.

---

## 5. Page-Size Selector

`render()` appends the selector to the grid's summary row when `$sR` has been set:

```php
if ($this->sR !== null) {
    $gridView = $gridView->summaryTemplate(
        PageSizeLimiter::buttons(
            $this->currentRoute, $this->sR, $this->translator, $this->urlGenerator, 'user'
        )
    );
}
```

`PageSizeLimiter::buttons()` renders:
- A **green** button showing the current limit (links to the General settings tab)
- A row of **red** buttons for preset limits (1 – 300)

Clicking a red button calls `setting/listlimit` with `origin=user`, which saves the
new limit to the database and redirects back to `user/index`.

`PageSizeLimiter::buttons()` parameter type for `$urlGenerator` was widened from the
concrete `Yiisoft\Router\FastRoute\UrlGenerator` to `Yiisoft\Router\UrlGeneratorInterface`
so the widget can pass its interface-typed property without a static-analysis error.

---

## 6. Asset Loading

The main layout (`resources/views/layout/templates/soletrader/main.php`) does **not**
load `HtmxAsset` — only the invoice layout does. Without htmx.js, `hx-boost` attributes
are inert HTML and every sort/pagination click causes a full page reload.

The asset is registered at the top of the view rather than added to the global layout
(to avoid loading HTMX on every login/signup/about page):

```php
// resources/views/user/index.php
$assetManager->register(HtmxAsset::class);
```

`$assetManager` is auto-injected into all views via `config/common/params.php`.

---

## 7. Files Changed

| File | Change |
|---|---|
| `src/User/Widget/UsersListWidget.php` | **Created** — HTMX-boosted `GridView`; `withPaginator()` and `withSR()` setters; `PageSizeLimiter` summary template |
| `src/User/Controller/UserController.php` | Added `HtmlResponseFactory` constructor dep; HTMX branch in `index()` returns bare widget HTML |
| `resources/views/user/index.php` | Registers `HtmxAsset`; passes `$s` to widget via `withSR()` |
| `src/Widget/PageSizeLimiter.php` | `buttons()` `$urlGenerator` type widened to `UrlGeneratorInterface` |

---

## 8. Key Decisions

**Widget not controller** — The `GridView` and its HTMX wiring live in the widget so
that both the full-page render (view) and the partial render (controller HTMX branch)
produce identical HTML. There is one source of truth for the grid markup.

**Immutable setters for ORM deps** — Widget constructors must only declare
auto-wirable dependencies. Cycle ORM-backed repositories are passed after construction
via `withX()` setters, which bypass the `WidgetFactory` DI resolution entirely.

**Asset in the view, not the layout** — Keeps htmx.js off pages that don't need it
(login, signup, public site pages). Any future page under the main layout that uses an
HTMX widget should follow the same pattern: `$assetManager->register(HtmxAsset::class)`
at the top of that specific view file.

**422 not applicable here** — Unlike the quote item entry (POST-based), all user-index
HTMX interactions are GET requests (sort, page, limit redirect). No validation failure
path exists, so the 422 guard used in `QuoteItemHtmxController` is not needed.
