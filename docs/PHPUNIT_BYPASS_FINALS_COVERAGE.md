# PHPUnit — Bypass Finals & 100 % PurchaseEntry Coverage

## Problem

All 74 repositories in this project extend `Cycle\ORM\Select\Repository` and are
declared `final`. PHPUnit's built-in `createMock()` cannot double a `final` class,
which made it impossible to unit-test any service that depends on a repository.

Before this change:

| Class | Methods | Lines |
|-------|---------|-------|
| `PurchaseEntryService` | 25 % (1 / 4) | 25 % (5 / 20) |

Only `vatQuarterLabel()` was reachable because it is a pure static method that
requires no repository.

## Solution

### 1. `dg/bypass-finals` dev dependency

```bash
composer require --dev dg/bypass-finals
```

This library intercepts PHP's class-loading pipeline and removes `final` from
class declarations at parse time, without modifying source files on disk.

### 2. Custom PHPUnit bootstrap

`Tests/bootstrap.php` (new file):

```php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

DG\BypassFinals::enable();
```

### 3. `phpunit.xml.dist` updated

```xml
bootstrap="Tests/bootstrap.php"
```

was changed from the previous value of `vendor/autoload.php`.

`DG\BypassFinals::enable()` must run **before** any class is autoloaded, so
placing the call in the bootstrap (which runs before test collection) is the
correct location.

## Result

### `PurchaseEntryServiceTest` — 37 tests

#### `saveEntry()` tests (mock-based, now possible)

| Test | What it covers |
|------|----------------|
| `testSaveEntryPopulatesSupplier` | supplier field mapping |
| `testSaveEntryPopulatesAmounts` | `amount_ex_vat` and `vat_amount` cast from string |
| `testSaveEntryPopulatesDescription` | non-empty description stored |
| `testSaveEntryEmptyDescriptionBecomesNull` | empty string → `null` |
| `testSaveEntryMissingDescriptionBecomesNull` | absent key → `null` |
| `testSaveEntryWithValidDateSetsDateTimeImmutable` | `Y-m-d` string → `DateTimeImmutable` |
| `testSaveEntryWithInvalidDateFallsBackToNow` | unparseable date → current timestamp |
| `testSaveEntryWithNoDateKeyLeavesDateNull` | absent key → `getDate()` remains `null` |
| `testSaveEntryNewEntrySetsCreatedAt` | unpersisted entry gets `created_at` |
| `testSaveEntryExistingEntryDoesNotOverwriteCreatedAt` | persisted entry keeps original `created_at` |
| `testSaveEntryEmptyBodyUsesDefaults` | all fields fall back to zero / empty / null |
| `testSaveEntryZeroAmountsAreAccepted` | `0.00` is a valid, stored value |
| `testSaveEntryAlwaysCallsRepositorySave` | `repository->save()` called exactly once per call |

#### `deleteEntry()` tests

| Test | What it covers |
|------|----------------|
| `testDeleteEntryCallsRepositoryDelete` | `repository->delete($entry)` forwarded with correct argument |

#### `vatQuarterLabel()` tests (static, no mock needed)

UK (April start), calendar year (January start), Australian (July start), year-boundary
crossings, label format regex, consecutive year numbers, all-four-quarters-distinct.

### Coverage after

| Class | Methods | Lines |
|-------|---------|-------|
| `PurchaseEntry` (entity) | 100 % (17 / 17) | 100 % (20 / 20) |
| `RequireId` (trait) | 100 % | 100 % |
| `PurchaseEntryForm` | 100 % (7 / 7) | 100 % (16 / 16) |
| `PurchaseEntryService` | **100 % (4 / 4)** | **100 % (20 / 20)** |
| `PurchaseEntryVatAggregator` | 100 % | 100 % |

## Files changed

| File | Change |
|------|--------|
| `composer.json` / `composer.lock` | `dg/bypass-finals ^1.10` added to `require-dev` |
| `Tests/bootstrap.php` | new — requires autoloader then calls `DG\BypassFinals::enable()` |
| `phpunit.xml.dist` | `bootstrap` attribute changed to `Tests/bootstrap.php` |
| `Tests/Unit/Invoice/Service/PurchaseEntryServiceTest.php` | new — 37 tests covering `saveEntry`, `deleteEntry`, and `vatQuarterLabel` |

## Notes

- `dg/bypass-finals` operates only during the PHPUnit run; production classes
  remain unchanged.
- PHPUnit notices (`N` markers) emitted when mocking Cycle ORM repository classes
  are a known, pre-existing pattern in this project and do not cause test failures.
- The bootstrap approach also covers future service tests: any new test class that
  calls `createMock(SomeFinalRepository::class)` will work without further
  configuration.
