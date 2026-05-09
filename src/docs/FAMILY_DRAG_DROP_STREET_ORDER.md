# Family Drag-and-Drop Street Order

A lightweight, dependency-free UI for reordering streets (families) before a
cleaning run, built on the native HTML5 Drag and Drop API with a Yii3 backend.

## Overview

The street order page lets users drag family rows into the sequence they want
to clean them. Positions are persisted to the database automatically on each
drop — no page reload required. The current position is also visible on the
family edit form with a direct link to the ordering page.

## Features

- **Zero external dependencies** — native HTML5 Drag and Drop API only; no
  jQuery, no Sortable.js
- **Real-time badge updates** — position numbers (1, 2, 3…) refresh in the DOM
  as items are dragged, before the save round-trip
- **Midpoint insertion** — dropping above the vertical midpoint of a target
  inserts before it; dropping below inserts after
- **Status feedback** — an inline alert shows `…saving`, `✓ Order saved`, or
  `✗ [error]` without leaving the page
- **CSRF-protected POST** — the reorder endpoint reads the token from a hidden
  input rendered server-side
- **Stable sort fallback** — when `street_sort_order` is NULL the query falls
  back to `family_name ASC`, so newly-added families always appear in a
  predictable position
- **Indexed column** — `street_sort_order` is database-indexed so the ordering
  query is fast even with many families

## User Guide

1. Navigate to **Families → Manage street order** (or go directly to
   `/family/street-order`).
2. The card lists every family in current cleaning-run order, each row showing
   a grip icon and a numbered position badge.
3. Drag any row up or down and release it in the desired position.
4. The status bar confirms the save. The next page load will reflect the new
   order.
5. Use the **Back to Families** button to return to the families index.

## Architecture

```
src/typescript/family-street-order.ts   ← drag logic + fetch POST
    ↓ compiled into
src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js

resources/views/invoice/family/street_order.php   ← renders <ul> + CSRF input

src/Invoice/Family/
    FamilyController.php   ← streetOrder() GET + reorder() POST
    FamilyService.php      ← saveStreetOrders(int[] $ids)
    FamilyRepository.php   ← findAllByStreetOrder(): EntityReader

src/Infrastructure/Persistence/Family/Family.php
    street_sort_order (?int, indexed)

config/common/routes/routes.php
    GET  /family/street-order  → FamilyController::streetOrder()
    POST /family/reorder       → FamilyController::reorder()
```

## Data Flow

```
1. GET /family/street-order
   └─ FamilyRepository::findAllByStreetOrder()
      ORDER BY street_sort_order ASC, family_name ASC
   └─ View renders <ul id="street-order-list" data-reorder-url="family/reorder">
         <li data-id="N" draggable="true"> … </li>  (one per family)
      Hidden <input id="street-order-csrf"> holds the CSRF token.
      initStreetOrder() wires the drag events on DOMContentLoaded.

2. User drags a row and drops it
   └─ dragenter: element inserted before/after target at midpoint
      refreshPositionBadges() updates badge text in real time

3. drop event fires
   └─ collectIds() reads data-id attributes top-to-bottom
   └─ postOrder() POSTs to /family/reorder:
        _csrf=<token>&order[]=id1&order[]=id2&…

4. POST /family/reorder
   └─ FamilyService::saveStreetOrders($ids)
      for each id at index $i → setStreetSortOrder($i + 1) → save
   └─ JSON response: { "success": true }

5. Status alert updated: "✓ Order saved"
   Page stays in place — next GET will reflect persisted order.
```

## File Reference

| File | Role |
|------|------|
| [src/typescript/family-street-order.ts](../typescript/family-street-order.ts) | TypeScript drag-and-drop logic and fetch POST |
| [resources/views/invoice/family/street_order.php](../../resources/views/invoice/family/street_order.php) | Server-rendered view: list, CSRF input, status div |
| [src/Invoice/Family/FamilyController.php](../Invoice/Family/FamilyController.php) | `streetOrder()` and `reorder()` actions |
| [src/Invoice/Family/FamilyService.php](../Invoice/Family/FamilyService.php) | `saveStreetOrders(int[] $ids)` persistence loop |
| [src/Invoice/Family/FamilyRepository.php](../Invoice/Family/FamilyRepository.php) | `findAllByStreetOrder(): EntityReader` |
| [src/Infrastructure/Persistence/Family/Family.php](../Infrastructure/Persistence/Family/Family.php) | `street_sort_order` column, getter, setter |
| [config/common/routes/routes.php](../../config/common/routes/routes.php) | Route definitions for `family/street-order` and `family/reorder` |
| [resources/messages/en/app.php](../../resources/messages/en/app.php) | Translation keys: `street.order.*` |

## TypeScript API

### `initStreetOrder(): void`

Called once from `src/typescript/index.ts` after `DOMContentLoaded`. Exits
silently when `#street-order-list` is not present (i.e. on every page except
the street order view).

Internally attaches four event listeners to the list element:

| Event | Action |
|-------|--------|
| `dragstart` | Records the dragged `<li>`, sets `opacity-50` |
| `dragover` | Calls `preventDefault()` to enable drop (no DOM move) |
| `dragenter` | Inserts the dragged element before/after the entered item at midpoint; refreshes badges |
| `dragend` | Clears opacity and internal state |
| `drop` | Collects final ID order, POSTs to backend, shows status message |

### `collectIds(list: HTMLUListElement): number[]`

Returns family IDs in DOM order by reading `data-id` from each `li[data-id]`.

### `refreshPositionBadges(list: HTMLUListElement): void`

Sets `.street-position` badge text to `index + 1` for each list item in order.

### `postOrder(url, csrf, ids): Promise<ReorderResponse>`

URL-encodes `_csrf` and `order[]` parameters and POSTs them. Returns the
parsed `{ success, message? }` JSON response, or `{ success: false, message:
"HTTP N" }` on a non-2xx status.

## Backend API

### `GET /family/street-order`

Renders the drag-and-drop page. Requires family edit permission.

### `POST /family/reorder`

**Request body** (URL-encoded):

```
_csrf=<token>&order[]=<id1>&order[]=<id2>&…
```

**Response** (JSON):

```json
{ "success": true }
// or
{ "success": false, "message": "…" }
```

`street_sort_order` is set to `1, 2, 3, …` in the submitted order. IDs that do
not correspond to a known family are silently skipped.

## Database

Column added to the `families` table:

```sql
street_sort_order INT(11) NULL DEFAULT NULL,
INDEX idx_street_sort_order (street_sort_order)
```

Managed via Cycle ORM attributes on
`src/Infrastructure/Persistence/Family/Family.php`.

## Browser Compatibility

Uses the [HTML5 Drag and Drop API](https://developer.mozilla.org/en-US/docs/Web/API/HTML_Drag_and_Drop_API),
which is supported in all modern browsers:

| Browser | Minimum version |
|---------|----------------|
| Chrome | 4 |
| Firefox | 3.5 |
| Safari | 3.1 |
| Edge | 12 |

Touch devices do not support the native drag-and-drop API. A future enhancement
could add a touch fallback (e.g. pointer events or a dedicated touch-sort library).

## Translations

Four keys added to `resources/messages/en/app.php`:

| Key | Default value |
|-----|---------------|
| `street.order` | `Cleaning Run — Street Order` |
| `street.order.drag.hint` | `Drag the streets into the order you want to clean them. The order is saved automatically when you drop a row.` |
| `street.order.back.to.families` | `Back to Families` |
| `street.order.position` | `Cleaning run position:` |
| `street.order.manage.link` | `Manage street order →` |

## Security

- All endpoints are guarded by the standard family-edit permission middleware
  (`$pEI`).
- The reorder POST validates the CSRF token via Yii3 middleware before the
  controller action runs.
- Family IDs are cast to `int` by `collectIds()` and validated by the
  repository fetch (unknown IDs are skipped without error).
- Family names in the view are XSS-escaped with `Html::encode()`.
