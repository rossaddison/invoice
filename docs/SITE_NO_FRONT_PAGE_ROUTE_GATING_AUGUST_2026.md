# `no_front_X_page` Settings Didn't Actually Block Their Routes (August 2026)

Same gap as `no_front_webshop_page`
(`docs/WEBSHOP_NO_FRONT_PAGE_ROUTE_GATING_AUGUST_2026.md`), confirmed
live by the user on `site/gallery`: about, accreditations, gallery,
team, pricing, privacypolicy, termsofservice, testimonial, and contact
were all only ever unlinked from the navbar
(`LayoutViewInjection`/`main.php`'s `noFrontPageX` booleans) — every
route stayed reachable by direct URL regardless of its "no entry"
checkbox (Settings → Front Page).

## Fix

`SiteController` gains a shared `renderUnlessDisabled(view, settingKey)`
helper — one if/return instead of 9 copies — applied to all nine
actions, the same 404-via-`WebControllerService::getNotFoundResponse()`
convention `gatewayStatus()` already established for this exact pattern.

`contact()` is gated by `no_front_contact_us_page` specifically, not
`no_front_contact_details_page` — the latter has no route or view
anywhere in this app; its `main.php` docblock `@var` is declared but
never actually read, a separate pre-existing dead setting, left alone
here (removed in a follow-up, see
`docs/DEAD_CONTACT_DETAILS_SETTING_REMOVED_AUGUST_2026.md`).

## Verified

9 new Testo tests (2 assertions each: gated when on, renders the right
view when off). Full Testo Unit suite: 969 passed (up from 960). Psalm
`--no-cache` on both changed files: no errors found.
