# `no_front_webshop_page` Didn't Actually Block `/shop` (August 2026)

The "no entry" checkbox (Settings → Front Page) only ever hid the
storefront's navbar link (`LayoutViewInjection`'s `noFrontPageWebshop` /
`main.php`'s `NavLink`) — every `/shop/*` route (catalog, cart,
checkout, ...) stayed reachable by typing the URL directly, unlike
`no_front_gateway_status_page`, whose `SiteController` already documents
actually 404ing its route when set.

## Fix

Added `WebshopAvailabilityMiddleware`, wired onto the whole
`Group::create('/shop')` in `routes-shop.php`: 404s every `/shop/*`
request while the setting is on, reusing the same
`WebControllerService::getNotFoundResponse()` convention
`shop/catalog/show` already uses for a missing product. Gates the whole
group rather than just the catalog index, since cart/checkout/etc. are
equally reachable by direct URL and none of them should work with the
storefront switched off.

This is the same class of gap later confirmed live by the user across
the rest of the `no_front_X_page` settings — see
`docs/SITE_NO_FRONT_PAGE_ROUTE_GATING_AUGUST_2026.md`.

## Verified

4 new Testo tests (`WebshopAvailabilityMiddlewareTest`). Full Testo Unit
suite: 960 passed (up from 956). Psalm `--no-cache` on all new/changed
files: no errors found.
