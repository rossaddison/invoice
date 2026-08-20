# Product Category/Subcategory/Family Taxonomy + Images for the Webshop Feed (August 2026)

Follow-on to
[`STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md`](STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md)'s
`GET /api/products` feed — the headless `webshop` storefront (a separate
sibling repo, see
[`WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md`](WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md))
wanted a product image gallery and a checkbox filter sidebar, which meant
this side of the API needed to actually carry a photo and a taxonomy to
filter against.

## Images

`ProductsController::toArray()` (`src/Api/ProductsController.php`) gained
`image_path`, resolved via a new `firstImagePath()` lookup against
`ProductImageRepository::repoProductImageProductquery()`. Deliberately a
*relative* path (`/products/{file}`), not an absolute URL — uploaded
product images are plain static files under `@public_product_images`
(`SettingFileFolderTrait::getProductimagesFilesFolderAliases()`, aliased
to `@public/products`), served directly by the web server and never
routed through PHP at all. Reconstructing this app's own public base URL
from the current request could disagree with the real public URL behind
a reverse proxy, so the caller (webshop, which already knows its own
configured `INVOICE_API_BASE_URL`) joins the two itself instead.

## Category / subcategory / family

`Family` (`src/Infrastructure/Persistence/Family/Family.php`) already
carried `category_primary_id`/`category_secondary_id` columns — built
for HomeCare's street/run hierarchy, not products — but every demo
product on file so far had been filed under one flat, uncategorized
"Webshop Demo" family. `toArray()` now also resolves and returns
`family`, `category` (`CategoryPrimary`), and `subcategory`
(`CategorySecondary`), all nullable — `null` for any product whose
`Family` isn't set, or whose `Family` has no category links, which
covers every pre-existing catalog row from earlier ad-hoc seeding.

`product/seed-demo` (`src/Command/Invoice/ProductSeedDemoCommand.php`)
now files its 5 demo products into a real two-level taxonomy instead of
one flat family:

```
Computing   > Input Devices > Input Devices (Wireless Mouse, Mechanical Keyboard)
Computing   > Displays      > Monitors       (27" Monitor)
Accessories > Connectivity  > Hubs & Adapters (USB-C Hub)
Accessories > Storage       > Storage        (1TB External SSD)
```

`CategoryPrimary`/`CategorySecondary` are resolved find-or-create by name
— no repository method already did this, so it's a plain
iterate-and-compare inside the command rather than a new general-purpose
repository method for a demo-only need. `Family` reuses the existing
`repoFamilyByNameAndSecondaryCategoryQuery()`. `CategorySecondary`'s own
`BelongsTo` relation to `CategoryPrimary` is set alongside the scalar FK
— the project's own recurring Cycle ORM gotcha (a relation left null when
only the scalar FK column is set).

Re-running the command against products that already exist no longer
just skips them (the original behaviour) — it now reassigns their
`Family` too, so products seeded before this change get fixed up in
place. Confirmed live: `php yii product/seed-demo` against the local dev
DB re-categorized all 5 existing demo products; `GET /api/products`
returned the expected `family`/`category`/`subcategory` triples
afterwards, and correctly returned `null`/`null`/`null` for the older,
still-uncategorized catalog rows.

## Verified, merged

`vendor/bin/psalm --no-cache`: no errors, project-wide. `vendor/bin/testo
--suite=Unit`: 891/891 (one new test,
`indexIncludesFamilyCategoryAndSubcategoryWhenPresent`). Built on
`feat/product-seed-demo-command`, merged into `main` with `--no-ff` (no
file overlap with the two unrelated dependency-update commits main had
gathered in the meantime), branch deleted both locally and on `origin`
afterward.

## What webshop built on top of this

Not part of this repo, but the reason this work exists: webshop's
product listing became a `Bootstrap5\Carousel` gallery (three products
per slide), each product a clickable tile with its real photo or a
placeholder icon; a left sidebar gained checkbox filters for
category/subcategory/family (pure GET query params, no JavaScript — the
form just submits itself) plus a min/max price range as two plain number
inputs, since webshop has no JS/TS build step yet for a real drag-slider.
All server-rendered and verified live against this taxonomy.
