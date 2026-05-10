# Invoice Soft Delete & Trash

## Overview

Invoices are financial records. Hard-deleting a row destroys the audit trail and
breaks foreign-key references from `InvItem`, `InvAmount`, `InvTaxRate`, and
`InvRecurring`. The soft-delete feature replaces irreversible `DELETE` statements
with a timestamped `deleted_at` column. Archived invoices land on a Trash page and
can be fully restored.

---

## Inv Entity Changes

### SoftDelete behavior

`#[Behavior\SoftDelete]` was already applied in a prior session. The behaviors doc
(`docs/CYCLE_ORM_BEHAVIORS.md`) covers the attribute. This document covers everything
built on top of it.

### `restore()` method

```php
// src/Infrastructure/Persistence/Inv/Inv.php
public function restore(): void
{
    $this->deleted_at = null;
}
```

Clears the `deleted_at` timestamp so the ORM's SoftDelete scope treats the row as
active again on the next save. Calling `restore()` on an already-active invoice
(where `deleted_at` is `null`) is safe and idempotent.

### `isDeleted()` and `getDeletedAt()`

```php
public function isDeleted(): bool        { return $this->deleted_at !== null; }
public function getDeletedAt(): ?DateTimeImmutable { return $this->deleted_at; }
```

---

## InvRepository — Trash Queries

Two methods bypass the SoftDelete scope using `->scope(null)` so they can reach
soft-deleted rows:

```php
// src/Invoice/Inv/InvRepository.php

public function findTrashed(): EntityReader
{
    $query = $this->select()
        ->scope(null)
        ->where('deleted_at', '!=', null);
    return $this->prepareDataReader($query);
}

public function findTrashedById(int $id): ?Inv
{
    return $this->select()
        ->scope(null)
        ->where(['id' => $id])
        ->where('deleted_at', '!=', null)
        ->fetchOne();
}
```

`->scope(null)` removes the automatic `WHERE deleted_at IS NULL` clause that the
SoftDelete behavior injects into every standard `$this->select()` call.

---

## InvRepository — Explicit `deleted_at` Filters

Every other query method in `InvRepository` now carries an explicit
`->where('deleted_at', null)` in addition to the automatic scope. This defensive
redundancy means soft-deleted invoices cannot appear in any result set even if the
scope were bypassed by accident, and makes the intent visible in code review.

Methods covered include: `filterInvNumber`, `filterCreditInvNumber`,
`filterFamilyName`, all `filterInvAmount*`, `findAllWithStatus`, `findAllPreloaded`,
`findAllWithClient`, `findAllWithContract`, `findAllWithDeliveryLocation`,
`countAllWithUserClient`, all `repo*` single-entity lookups, `open`, `openCount`,
`guestVisible`, `isDraft`, `isSent`, `isViewed`, `isPaid`, `isOverdue`, `byClient`,
`byClientInvStatus`, `byClientInvStatusCount`, all date-range and product/task
aggregation methods.

---

## InvService — `deleteInv()` and `restoreInv()`

```php
// src/Invoice/Inv/InvService.php

public function deleteInv(Inv $inv): void
{
    $this->repository->delete($inv);   // ORM intercepts → sets deleted_at
}

public function restoreInv(Inv $inv): void
{
    $inv->restore();
    $this->repository->save($inv);
}
```

`InvDeletionService` (which hard-deleted child rows) was removed entirely. The ORM
soft-delete is now the only deletion path.

---

## Trash Page

### Route (`config/common/routes/routes.php`)

```php
Route::get('/inv/trash')
    ->name('inv/trash')
    ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
    ->action([InvController::class, 'trash']),

Route::methods([$mG, $mP], '/inv/restore/{id}')
    ->name('inv/restore')
    ->middleware(fn (AC $checker) => $checker->withPermission($pEI))
    ->action([InvController::class, 'restore']),
```

### Controller trait (`src/Invoice/Inv/Trait/Trash.php`)

```php
trait Trash
{
    public function trash(IR $invRepo): Response
    {
        return $this->webViewRenderer->render('trash', [
            'alert'   => $this->alert(),
            'trashed' => $invRepo->findTrashed(),
        ]);
    }

    public function restore(#[RouteArgument('id')] int $id, IR $invRepo): Response
    {
        $inv = $invRepo->findTrashedById($id);
        if ($inv) {
            $this->inv_service->restoreInv($inv);
            $this->flashMessage('success',
                $this->translator->translate('delete.invoice.restored'));
        }
        return $this->webService->getRedirectResponse('inv/trash');
    }
}
```

### Views

| File | Purpose |
|---|---|
| `resources/views/invoice/inv/trash.php` | Table of soft-deleted invoices with per-row restore button |
| `resources/views/invoice/inv/modal_restore_inv.php` | Bootstrap 5 confirmation modal for restore |

The modal is triggered by a `data-bs-toggle="modal"` link with id `restore-inv-{id}`.
The form inside posts to `inv/restore/{id}` with a CSRF token.

### Sidebar entry

```php
['route' => 'inv/trash', 'title' => 'invoice.trash',
 'icon' => 'bi bi-trash', 'color' => '#6c757d', 'show' => true],
```

---

## Translation Keys

All keys live under the `delete.invoice.*` prefix in
`resources/messages/en/app.php`:

| Key | Value |
|---|---|
| `delete.invoice` | Archive Invoice |
| `delete.invoice.warning` | This invoice will be soft-deleted … |
| `delete.invoice.trash` | Invoice Trash |
| `delete.invoice.trash.empty` | The trash is empty |
| `delete.invoice.date.soft.deleted` | Archived Date |
| `delete.invoice.restore` | Restore |
| `delete.invoice.restore.warning` | This invoice will be restored … |
| `delete.invoice.restored` | Invoice restored successfully |
| `delete.invoice.cancel` | Cancel |

---

## Unit Tests

File: `Tests/Unit/Invoice/Entity/InvEntityTest.php`

17 tests, 28 assertions. Covers:

| Group | Tests |
|---|---|
| Soft-delete defaults | `isDeleted()` false, `getDeletedAt()` null on fresh entity |
| Setting `deleted_at` | `isDeleted()` true, `getDeletedAt()` returns the timestamp |
| Clearing `deleted_at` | `isDeleted()` false, `getDeletedAt()` null after clear |
| Identity | `hasIdentity()` false without `setId()`, `reqId()` throws `LogicException` |
| `reqId()` | Returns correct int after `setId()` |
| Restore | `restore()` nullifies `deleted_at`, flips `isDeleted()` to false |
| Restore idempotent | Safe to call on a fresh (non-deleted) entity |
| Restore preserves id | `reqId()` still correct after restore |
| Multiple cycles | Soft-delete → restore → soft-delete → restore all correct |

`setDeletedAt()` in the test class uses `ReflectionProperty` to set the private
`deleted_at` field, simulating what the ORM does at the persistence layer without
exposing a public setter on the entity.
