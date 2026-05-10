# Cycle ORM — Database Transactions

## Overview

Before this change, multi-step invoice operations (create, copy, credit) executed as
a series of independent `EntityWriter::write()` calls. Each call committed its own
transaction. If step 3 of a 5-step operation threw an exception, steps 1 and 2 were
already committed — leaving orphaned rows (e.g. an invoice with no items, or items
with no amounts).

The `withTransaction()` method on `InvService` wraps an entire operation in a single
database transaction. All writes succeed together or roll back together.

---

## Implementation

### `DatabaseManager` injection into `InvService`

```php
// src/Invoice/Inv/InvService.php

use Cycle\Database\DatabaseManager;

final readonly class InvService
{
    public function __construct(
        private InvRepository $repository,
        private Translator $translator,
        private CR $cR,
        private GR $gR,
        private UR $uR,
        private DatabaseManager $dbal,   // ← added
    ) {}

    public function withTransaction(callable $fn): void
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $this->dbal->database()->transaction($fn);
    }
}
```

`DatabaseManager` is already in the DI container — it is injected into the Cycle ORM
factory in `config/common/di/cycle.php`. No extra registration is needed.

The `@psalm-suppress` is required because Psalm types the `transaction()` parameter as
`callable(DatabaseInterface):mixed`, but our closures declare `void` and ignore the
injected `DatabaseInterface` argument. PHP silently discards the extra argument at
runtime.

### How it works at the database level

`$dbal->database()` returns the `'default'` MySQL connection configured in
`config/common/params.php`. `->transaction($fn)` issues:

1. `BEGIN`
2. `$fn()` — all `EntityWriter::write()` calls inside become `SAVEPOINT` + `RELEASE SAVEPOINT` (MySQL nested transactions)
3. `COMMIT` on success, or `ROLLBACK` on any `Throwable`

The closure pattern used in callers:

```php
$result = null;
$this->inv_service->withTransaction(
    function () use ($arg1, $arg2, &$result): void {
        // all writes here
        $result = ...;
    }
);
// use $result here (outside transaction — no writes)
```

`$result` is captured by reference (`&$result`) so the caller can access values
computed inside the transaction after it commits. Flash messages and HTTP responses
are always outside the transaction — they are not data writes.

---

## Operations Wrapped

### 1. Add — `Trait\Add::add()`

Writes wrapped:
- `InvService::saveInv()` — creates the `Inv` row
- `InvController::defaultTaxes()` — creates one `InvTaxRate` row per default tax rate

**Risk without transaction:** invoice row committed, then a tax rate validation failure
leaves the invoice with no default tax rates. Totals calculate as zero.

### 2. Credit — `Trait\Credit::credit()`

Writes wrapped:
- `InvService::saveInv()` — creates the credit invoice
- `InvAmountService::initializeInvAmount()` — creates the `InvAmount` row
- `InvController::defaultTaxes()` — creates default `InvTaxRate` rows

**Risk without transaction:** credit invoice exists but amount row is missing, causing
null-reference errors in total calculations.

### 3. Credit Confirm — `Trait\Credit::createCreditConfirm()`

Writes wrapped:
- `InvService::saveInv()` — creates the new credit note
- `InvItemService::initializeCreditInvItems()` — copies all line items
- `InvAmountService::initializeCreditInvAmount()` — copies the amount record
- `InvTaxRateService::initializeCreditInvTaxRate()` — copies tax rates
- `basis_inv->setCreditinvoiceParentId()` + `iR->save($basis_inv)` — links the original
  invoice back to the credit note

**Risk without transaction:** new credit note saved, items copied, but if the basis
invoice update fails, the original invoice does not record the credit note id — the
link between them is broken.

### 4. Multiple Copy — `Trait\MultipleCopy::multiplecopy()`

Each invoice in the key list gets its own transaction:

```
foreach ($keyList as $value) {
    withTransaction(function() {
        copyInv()                   // creates Inv row
        setDateCreated() + save()  // updates date
        invToInvInvItems()         // copies all line items
        invToInvInvTaxRates()      // copies tax rates
        invToInvInvCustom()        // copies custom fields
        invToInvInvAmount()        // copies amount record
        iR->save($copy)            // final save
    });
}
```

Per-invoice transactions mean one failed copy does not prevent others in the batch
from succeeding.

### 5. Invoice-to-Invoice Confirm — `Trait\MultipleCopy::invToInvConfirm()`

Writes wrapped:
- `InvService::saveInv()` — creates the new invoice
- `invToInvInvItems()` — copies line items with amounts and allowance charges
- `invToInvInvTaxRates()` — copies tax rates
- `invToInvInvCustom()` — copies custom fields
- `invToInvInvAllowanceCharges()` — copies document-level allowance charges
- `invToInvInvAmount()` — copies the amount record
- `iR->save($copy)` — final save

---

## Future Use

Any new multi-step write operation should follow the same pattern:

```php
$this->inv_service->withTransaction(function () use (..., &$outVar): void {
    // step 1 write
    // step 2 write
    $outVar = ...; // capture what the caller needs
});
// read $outVar, send response
```

If the operation lives in a different service, inject `DatabaseManager` there and
add the same `withTransaction()` helper method.
