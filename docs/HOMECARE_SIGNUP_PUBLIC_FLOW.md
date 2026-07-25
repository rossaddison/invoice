# HomeCare Signup — Public Self-Service Flow

## Purpose

A public, unauthenticated signup form (`/homecare-signup`) lets a prospective
home-care customer create their own account, submit their address and price,
and — once they click the emailed confirmation link — have their Client,
street (`Family`), house-number `Product` (Service-type), and first `Inv`
raised automatically, with the QR code ready to print immediately. This
mirrors the generic `/signup` flow's shape but is intentionally separate from
it (`HomeCareSignupController`/`HomeCareSignupForm`, not `SignupController`/
`SignupForm`): it always creates and links a Client — unlike generic signup,
it never consults `signup_automatically_assign_client` — and it does the
Family/Product/Invoice resolution work generic signup has no concept of.

## Architecture

| Class | Role |
|---|---|
| `App\Auth\Form\HomeCareSignupForm` | Validates login/email/password + address/price/payment-option answers |
| `App\Auth\Controller\HomeCareSignupController` | `signup()` (form) and `confirm()` (email-link landing) actions |
| `App\Auth\Controller\HomeCareSignupDeps` / `HomeCareSignupConfirmDeps` | DI parameter-object bundles for each action |
| `App\Infrastructure\Persistence\HomeCarePendingSignup\HomeCarePendingSignup` | Holds the submitted answers between POST and confirm-click, keyed by `user_id` |
| `App\Invoice\HomeCarePendingSignup\HomeCarePendingSignupRepository` | Read-once-then-delete at confirm time |

Side effects (Client/Family/Product/Invoice creation) are deliberately
deferred to `confirm()` — nothing durable is written to the business tables
until the emailed link is clicked, so an unconfirmed/bot signup leaves only a
`User` row and a `HomeCarePendingSignup` row behind, both cleaned up or
harmless if abandoned.

## Family/Product resolution — exact match, not a partial `LIKE`

`FamilyService::findOrCreateByStreetName(string $streetName, int
$categorySecondaryId)` resolves a customer-typed street name to an existing
pre-entered `Family` (street/run), or creates a new one. The first
implementation used `->where('family_name', 'like', $name)` for a
"case-insensitive" match — but passing raw, unescaped user input into a
`LIKE` clause makes `%`/`_` live SQL wildcards: a street containing either
character could silently match (and get grouped under) a completely
unrelated pre-existing run. Fixed to an exact match on **both**
`family_name` and `category_secondary_id`
(`FamilyRepository::repoFamilyByNameAndSecondaryCategoryQuery()`), which also
disambiguates two different runs that happen to share a street name but
serve different categories.

## Secondary category — "not set yet" placeholder, not `null`

The signup form includes a `secondaryCategoryId` dropdown of existing
`CategorySecondary` options, plus a sentinel
`HomeCareSignupForm::NEW_AREA_SECONDARY_CATEGORY_ID = 0` for "my area isn't
listed yet". Rather than resolving that sentinel to `null` (which would let
every future "new area" signup collide with every other one on a `null`
match), `HomeCareSignupController::resolveSecondaryCategoryId()` auto-creates
a real, uniquely-named placeholder — `not_set_yet_<timestamp>` — under the
lowest-id `CategoryPrimary` (the same "pragmatic fallback" pattern already
used for `TaxRateRepository`/`UnitRepository::repoFirstByIdQuery()` when
auto-creating the house-number `Product`), so staff can find and rename it
later without two unrelated new-area signups ever merging. This resolution
happens at confirm-click time, matching the "no durable side effects before
confirmation" rule above.

## Rendering — must inherit the site layout

Both actions were originally written with
`$this->webViewRenderer->renderPartial(...)`, which explicitly skips the
configured layout (`withLayout(null)` internally) — the page rendered with
its own standalone `<!DOCTYPE html>` shell and no site navigation. The
generic `/signup` flow (`SignupController`) uses `render()` instead, which
does apply the layout, and its `site/signupsuccess`/`site/signupfailed`
redirect targets do too. Fixed by switching both `signup()` and `confirm()`
(via `renderConfirmed()`) to `render()`, and restyling both views as
Bootstrap-card content (`App\Auth\Trait\ClassList`, the same trait
`SignupController` uses) instead of a standalone HTML document — the
confirm-landing page now uses a `Yiisoft\Bootstrap5\Alert` (success for
paid/unpaid, warning for expired, danger for setup-incomplete), same as
`resources/views/site/signupsuccess.php`.

## `RadioList` fields — the floating-label theme doesn't fit a radio group

The payment-option field (`F::radioList(...)->label(...)`) rendered with its
group label ("How would you like to pay?") appearing *after* all the radio
options and visually overlapping them. Root cause: this project's default
form theme (`config/common/params.php`, `yiisoft/form` → `themes` →
`'default'`) uses Bootstrap's floating-label pattern —
`template: '{input}{label}{hint}{error}'`, `containerClass:
'form-floating mb-3'` — which only makes sense for a single-input field
(the label floats over *that* input). A radio group has no single input to
float over. `Checkbox::class` already had a targeted `fieldConfigs`
override for exactly this reason; the same fix was added for
`RadioList::class`:

```php
RadioList::class => [
    $containerClass => ['mb-3'],
    'template()' => ["{label}\n{input}\n{hint}\n{error}"],
    'labelClass()' => ['form-label'],
],
```

This is a config-level fix, so it applies to any `RadioList` field added to
the app in future, not just this one.

## Price steps

`HomeCareSignupForm::ALLOWED_PRICES` is £1 steps from £5–£15 (finer
granularity where a £1 difference matters most at the low end), then £5
steps up to £100: `[5, 6, 7, 8, 9, ..., 15, 20, 25, ..., 100]`.

## Settings

- `stop_homecare_signing_up` — Settings → General. Gates `signup()` (and the
  soletrader-layout nav link) shut without disabling the wider HomeCare QR
  auto-invoice feature for existing clients (see
  [Home-Care QR Auto-Invoice Facility](HOMECARE_QR_AUTOINVOICE_AND_ROUTES_REFACTOR.md)).
  On an already-installed site this key won't exist as a settings-table row
  until saved once through the Settings UI (the install trait only seeds
  defaults on a *fresh* install) — it degrades safely to "not blocked" while
  absent, since a missing key reads falsy.
- Nav link in `resources/views/layout/templates/soletrader/main.php`,
  gated by the same setting via `LayoutViewInjection::resolveBootstrapSettings()`.

## Database setup

`HomeCarePendingSignup` is a brand-new table (`user_id`, `client_name`,
`client_surname`, `street`, `building_number`, `price`, `payment_option`,
`secondary_category_id`). On any environment that isn't a fresh install,
this needs a schema sync: set `BUILD_DATABASE=true` in `.env` for one boot,
then revert it — the same one-off procedure used for every other Cycle ORM
schema change in this project.

## Testing

`Tests/Testo/Auth/HomeCareSignupFormTest.php`,
`Tests/Testo/Invoice/Family/FamilyServiceFindOrCreateTest.php`, and
`Tests/Testo/Invoice/Product/ProductServiceFindOrCreateHouseNumberTest.php`
cover form validation (including the price/payment-option/secondary-category
allow-lists) and the Family/Product find-or-create resolution logic via
Mockery, with no database required. Psalm errorLevel 1 clean throughout
(July 2026).
