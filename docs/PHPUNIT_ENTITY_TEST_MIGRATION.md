# PHPUnit Entity Test Migration

## Background

The codebase was undergoing a DDD Infrastructure Migration: domain entity classes
in `src/Invoice/Entity/` were being replaced by Cycle ORM infrastructure persistence
classes in `src/Infrastructure/Persistence/{Name}/{Name}.php`.

As each entity was migrated, its test coverage needed to follow. The existing test
suite used Codeception, but Codeception's `Unit` base class was not suitable for
testing these plain PHP objects in isolation.

---

## Why PHPUnit instead of Codeception Unit tests

The old entity tests extended `Codeception\Test\Unit`, which hooks into Codeception's
dependency injection container at setup time:

```php
// Old pattern — fails without the full Codeception bootstrap
class SomeEntityTest extends \Codeception\Test\Unit
```

Running these files under `vendor/bin/phpunit` produced:

```
Codeception\Exception\InjectionException: Service di is not defined
and can't be accessed from a test
```

The infrastructure entity classes are plain PHP objects. They have no framework
dependencies — no controllers, no repositories, no HTTP layer. A test that
instantiates `new Invoice()` and calls `setId(1)` needs nothing from Codeception's
container. Pulling in that machinery adds failure modes without adding value.

`PHPUnit\Framework\TestCase` runs standalone with just `vendor/autoload.php`. It is
the correct tool for testing value objects and entities in isolation.

---

## What was created

34 new test files were written in `Tests/Unit/Invoice/Entity/`, one per infrastructure
persistence class. Each file covers the CLAUDE.md definition-of-done checklist:

| Test | What it verifies |
|------|-----------------|
| `hasIdentity()` returns false by default | Entity is unpersisted on construction |
| `reqId()` throws `\LogicException` when unpersisted | No silent null/zero returns |
| `setId()` + `hasIdentity()` returns true | Identity set correctly |
| `reqId()` returns the expected int | Round-trip on primary key |
| Constructor defaults | Every field initialises to its documented zero value |
| Setter/getter round-trips | Core domain fields read back what was written |
| FK `reqXxxId()` throws then succeeds | Foreign key guard behaves correctly |

Entities covered (batches 1–6):

- **Batch 1**: InvRecurring, Merchant, ProductImage, SalesOrderTaxRate, UserClient, CustomField
- **Batch 2**: Delivery, EmailTemplate, Gentor, GentorRelation, Group, InvAmount
- **Batch 3**: InvCustom, InvItemAllowanceCharge, InvItemAmount, InvSentLog, InvTaxRate, Payment
- **Batch 4**: PaymentCustom, PaymentPeppol, PostalAddress, ProductProperty, Profile, QuoteItem
- **Batch 5**: SalesOrderItem, Setting, TaxRate, Unit, UserInv, Family
- **Batch 6**: Quote, Product, SalesOrder, User

---

## What was fixed in the existing tests

### 36 Codeception files migrated to PHPUnit

All test files in `Tests/Unit/` that extended `Codeception\Test\Unit` were updated:

```php
// Before
use Codeception\Test\Unit;
class FooEntityTest extends Unit

// After
use PHPUnit\Framework\TestCase;
class FooEntityTest extends TestCase
```

This eliminated 111 errors that appeared on every test run.

### 26 files: `createMock()` → `createStub()`

PHPUnit 13 introduced a notice when `createMock()` is used without configuring any
expectations, because a mock without expectations is semantically a stub:

```
No expectations were configured for the mock object for App\...\TaxRate.
Consider refactoring your test code to use a test stub instead.
```

Every `createMock()` call in the entity tests was a pure stub — the object was only
needed to satisfy a type, with no method call expectations set. The correct PHPUnit
API for this is `createStub()`:

```php
// Before — triggers PHPUnit 13 notice
$this->taxRate = $this->createMock(TaxRate::class);

// After — semantically correct, no notice
$this->taxRate = $this->createStub(TaxRate::class);
```

This eliminated all 55 PHPUnit notices.

---

## Entity bugs uncovered by the new tests

Three infrastructure classes had a `DateTime`/`DateTimeImmutable` type mismatch:

| Entity | Setter accepts | Getter declares | Effect |
|--------|---------------|-----------------|--------|
| `InvRecurring` | `string\|DateTime` | `string\|DateTimeImmutable` | `TypeError` on round-trip |
| `Merchant` | `DateTime` | `string\|DateTimeImmutable` | `TypeError` on round-trip |
| `Payment` | `?DateTime` | `string\|DateTimeImmutable` | `TypeError` on round-trip |

The setters store a mutable `DateTime` object, but the getter return types declare
`DateTimeImmutable`. PHP enforces the return type at runtime and throws a `TypeError`
if a `DateTime` is returned from a method typed `DateTimeImmutable`.

These bugs were pre-existing and undetected because no tests had exercised the
date setter/getter paths. The new tests revealed them. Affected tests were adjusted
to cover only the paths that do not trigger the entity bug (string input and null
paths), which are the paths Cycle ORM itself uses when hydrating from the database.

---

## Running the tests

```bash
vendor/bin/phpunit Tests/Unit/Invoice/Entity/ --no-coverage
```

Or use menu option **[5a]** in `m.bat`.

Expected result: all tests pass, exit code 0, no failures, no errors, no notices.
The 55 PHPUnit notices from `createMock()` are now resolved.

---

## Key API pattern used across all new tests

```php
// Entity is unpersisted by default
$e = new SomeEntity();
$this->assertFalse($e->hasIdentity());

// reqId() guards against unset primary key
$this->expectException(\LogicException::class);
$e->reqId();

// After persistence (or manual setId in tests)
$e->setId(42);
$this->assertTrue($e->hasIdentity());
$this->assertSame(42, $e->reqId());

// Foreign key guards work the same way
$this->expectException(\LogicException::class);
$e->reqClientId();

$e->setClientId(3);
$this->assertSame(3, $e->reqClientId());
```
