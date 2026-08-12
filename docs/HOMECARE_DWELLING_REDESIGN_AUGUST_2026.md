# HomeCare `Dwelling` Redesign — August 2026

## Problem

Linking a HomeCare customer's real-world address to this app's own
`CategoryPrimary → CategorySecondary → Family` run/area hierarchy had been
consistently difficult — both for admin's bulk street/house setup and for
new-customer signup. Working backward from that, the root cause turned out
to be a modeling smell: `Product` (a catalog/business concept — "what's
being sold") was being repurposed to represent a physical house, one
`Product` row per address, `product_type = Service`. That conflated two
things that don't belong on the same object — a service's catalog identity
and a customer's location — and every attempted fix (postcode-encoded
`Product.name`, a `product_sku`-based key, a `Client.product_id` FK) kept
running back into that same mismatch.

## Design

`Product` reverts to meaning only "sellable catalog item." A new entity,
**`Dwelling`**, takes over the "house on a street" role entirely:

- `Dwelling` — `family_id` (nullable plain scalar, deliberately **not** a
  Cycle `#[BelongsTo]` relation, sidestepping a confirmed real gotcha
  already live in `Product.php`: its own `family_id` column is
  `nullable: true` while the `BelongsTo` annotation says `nullable: false`
  — a pre-existing inconsistency this design avoids repeating), split
  `house_number_numeric`/`house_number_suffix` columns for correct natural
  sort ("12" before "12A" before "13" — a single string column can't do
  that without a natural-sort library this codebase doesn't have),
  `flat_unit`, `postcode`, `latitude`/`longitude`, and `source` (which
  creation path produced the row).
- `Client.dwelling_id` — plain scalar, no `BelongsTo`, indexed. One
  `Client` → at most one `Dwelling` — a deliberate simplification (no
  `DwellingClient` join table, unlike the many-to-many `ProductClient`
  precedent) since the real relationship is one-to-one.
- Deliberately **no stored "claimed" flag** on `Dwelling` — whether a
  house is occupied is always determined live via an anti-join against
  `Client.dwelling_id`, never cached, since a denormalized flag has no
  structural guarantee of staying in sync with the real relationship.
- Every HomeCare invoice line now references **one shared "HomeCare
  Service" catalog `Product`** (found-or-created by name + type, not
  family) instead of one `Product` per house — the per-customer price
  still lives on the `InvItem`, exactly as it always did (the old
  per-house `Product`'s own base price was never actually read either).
- `ProductClient` and the existing commalist bulk-generator
  (`FamilyController::createProductsFromCommalist()`) are explicitly left
  untouched — the commalist generator may still be useful for non-HomeCare
  product-family use cases, so it wasn't retargeted to `Dwelling`.

## What's built so far

**Schema + CRUD** — `Dwelling` entity/repository/service/form/controller,
full admin CRUD at `/dwelling`, `Client.dwelling_id` added and indexed.
Verified live against the actual database (`SHOW COLUMNS`/`SHOW INDEX`),
not just the schema cache.

**`inv/index` house-number column** — new `Inv::getClientDwellingId()`
accessor (`Inv → Client → Dwelling`, distinct from the existing
`Inv → InvItem → Product → Family` chain the current-run column walks),
rendered via a new `InvsDwellingHouseNumberColumnTrait` mirroring
`InvsCategorySecondaryRunColumnTrait`'s exact shape. Always visible
whenever HomeCare mode is on, same as the Worker and current-run columns
— not added to the per-column hide-toggle list in
`partial_settings_homecare.php`, a deliberate choice matching that
existing precedent.

**Signup flow retargeted** — `HomeCareSignupController::confirm()` now
resolves the `Dwelling` (find-or-create, from the pending signup's
free-text street/building-number — the public `HomeCareSignupForm`'s UI is
unchanged) *before* creating the `Client`, so `Client.dwelling_id` is set
at creation time rather than backfilled after. `provisionInvoice()` no
longer creates a per-house `Product`; it resolves the shared "HomeCare
Service" `Product` instead. `client_address_1`/`client_building_number`
stay populated as a snapshot alongside the new, authoritative
`dwelling_id` link, for PDF/UBL bill-to backward compatibility.

## Explicitly not built yet

- **Spreadsheet import** for bulk-populating `Dwelling` rows from an
  existing run's data — right now the only way any `Dwelling` rows exist
  is the admin CRUD screens or a real signup's find-or-create.
- **Worker doorstep canvassing tool** (live geolocation, a dropdown of
  unclaimed `Dwelling`s) — a separate, worker-RBAC-gated flow, not the
  public signup form.
- **Google Maps integration** (Street View on `Family`/`Dwelling`,
  geofenced run-suggestion).
- **Production data migration** — existing `Client`s with HomeCare
  invoicing history predating this change have **no `dwelling_id`
  backfilled**. Their `inv/index` house-number column will show blank
  until a migration walks `Client → Inv → InvItem → Product → Family` to
  resolve/create the matching `Dwelling` for each. Not a regression — new
  signups work correctly going forward; this is pre-existing data that
  simply predates the new column.

## Verification

- `php -l` clean on every new/changed file.
- Full-project Psalm: no errors found (100% type inference), both after
  the schema/CRUD phase and again after the signup-flow retargeting.
- Full PHPUnit suite: 3,896/3,896 passing throughout, zero regressions.
- New `Tests/Unit/Invoice/Entity/DwellingEntityTest.php` (19 tests) per
  this project's DDD entity-test convention.
- **Not yet verified live**: an actual end-to-end signup → confirmation-
  email-click, to confirm a real `Dwelling` and the shared `HomeCare
  Service` `Product` get created and linked correctly against a real
  database. That's the one thing static analysis and unit tests can't
  substitute for here.
