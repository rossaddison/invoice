# Removed the Dead `no_front_contact_details_page` Setting (August 2026)

Traced content, not just wiring: `site/contact` (gated by
`no_front_contact_us_page`) has always rendered address/phone/email —
contact *details*, not a form — so the separate
`no_front_contact_details_page` checkbox in Settings → Front Page never
had any page of its own to gate. Its `main.php` docblock `@var` was
declared but never once read in the template body; it did nothing at
all, unlike the mechanical route-gating gap already fixed for the other
nine `no_front_X_page` settings
(`docs/SITE_NO_FRONT_PAGE_ROUTE_GATING_AUGUST_2026.md`).

## Fix

Removed the dead checkbox and every reference: the settings-form block,
`LayoutViewInjection`'s `noFrontPageContactDetails`
computation/array-shape/return-array entries, and `main.php`'s unused
`@var`. `SiteController::contact()` and `SiteControllerTest` gain a
short comment explaining why, for anyone who goes looking for it later.

## A second dead setting found, documented not fixed here

While tracing this, also found `no_front_contact_interest_page`: an
`InvoiceInstallTrait` default but no settings checkbox and no route
check anywhere — its name closely matches the real, still-live "Contact
Us" form (`App\Contact\ContactController::interest()`, route
`/interest`, currently reachable only from the webshop product page).
Wiring that one up was a product decision left for a separate pass — see
`docs/CONTACT_INTEREST_PAGE_SETTING_AUGUST_2026.md`.

## Verified

Full Testo Unit suite: 969 passed (no count change — no gating behaviour
changed, only dead plumbing removed). Psalm `--no-cache` on all changed
files: no errors found.
