# Wiring Up `no_front_contact_interest_page` (August 2026)

Second dead setting found while removing `no_front_contact_details_page`
(`docs/DEAD_CONTACT_DETAILS_SETTING_REMOVED_AUGUST_2026.md`):
`no_front_contact_interest_page` had a default value in
`InvoiceInstallTrait` but no settings checkbox and no route check at
all, unlike every other `no_front_X_page` setting. Its name closely
matches `App\Contact\ContactController::interest()` (route `/interest`,
the "Request a Trade Quote" form linked from a product's Trade Pricing
modal) — wired the two together.

## Fix

- `ContactController::interest()` gains the same
  `WebControllerService::getNotFoundResponse()` gate as every other
  `no_front_X_page` action, checked before the GET/POST branch so a
  disabled form 404s the submission path too, not just the display.
- New Settings → Front Page checkbox, placed next to Contact Us
  (`partial_settings_front_page.php`), new `menu.contact.interest`
  translation key.
- Stale "still open"/"flagged, not fixed" comments in
  `InvoiceInstallTrait` and `SiteController::contact()` updated to
  reflect this is now wired up.

## Verified

2 new Testo tests (404 + never touches the mailer/form when the setting
is on; renders normally when off). Full Testo Unit suite: 971 passed (up
from 969). Psalm `--no-cache` on all changed files: no errors found.
