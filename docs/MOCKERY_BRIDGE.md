# Mockery Bridge — Testo Integration

`testo/bridge-mockery` wires Mockery into the Testo test runner so that
`Mockery::close()` is called automatically in a `finally` block after every
test. Mock expectations are always verified and the Mockery container is always
cleared — no per-test `tearDown()` boilerplate.

---

## Why Mockery for this codebase

### 1. Automatic teardown

PHPUnit tests that use Mockery must call `Mockery::close()` in `tearDown()` or
expectations are silently skipped. Every Testo test class that uses Mockery
would need the same boilerplate. `MockeryPlugin` removes that requirement
entirely — the `finally` block fires even when a test throws.

### 2. Concrete class mocking without interfaces

Most Cycle ORM repositories in this project are `final class` (e.g.
`GroupRepository extends Select\Repository`). PHPUnit's `createMock()` cannot
subclass a `final` class and emits a notice or fatal error. Mockery uses a
different code-generation path and can create test doubles for non-final
concrete classes without requiring an interface to be extracted first.

Where interfaces already exist — `As4MessageRepositoryInterface`,
`As4SenderInterface`, `As4ReceiptParserInterface`, `InvRepositoryInterface` —
Mockery mocks them directly and enforces the contract at the call site.

### 3. Fluent expectation API

```php
$repo->expects('findByMessageId')
     ->once()
     ->with('<abc@example.com>')
     ->andReturn(null);
```

vs PHPUnit's:

```php
$repo->expects($this->once())
     ->method('findByMessageId')
     ->with('<abc@example.com>')
     ->willReturn(null);
```

The Mockery API is shorter, reads left-to-right, and separates stubs
(`allows`) from expectations (`expects`) — making test intent clearer.

### 4. Spy pattern for "don't-care" dependencies

```php
$engine = new As4RetryEngine(
    $repo,
    m::spy(As4SenderInterface::class),   // not called in this test path
    m::spy(LoggerInterface::class),      // called but output not asserted
    m::spy(As4ReceiptParserInterface::class),
);
```

`m::spy()` allows any call without asserting it was made. This avoids the
verbose `$this->createMock(...)->method(...)->willReturn(...)` stubs for
collaborators that are irrelevant to the behaviour under test.

---

## Setup

```bash
composer require --dev testo/bridge-mockery mockery/mockery
```

Register the plugin once in `testo.php`:

```php
use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\SuiteConfig;
use Testo\Bridge\Mockery\MockeryPlugin;

return new ApplicationConfig(
    src: ['src'],
    plugins: [new MockeryPlugin()],
    suites: [ ... ],
);
```

---

## Patterns used in this project

### Strict mock with call expectation

```php
/** @var MockInterface&As4MessageRepositoryInterface $repo */
$repo = m::mock(As4MessageRepositoryInterface::class);
$repo->expects('findAwaitingReceipts')->once()->andReturn([]);
```

`m::mock()` is strict: any unexpected call fails the test immediately.

### Stub (allows any number of calls)

```php
/** @var MockInterface&As4RetryState $retryState */
$retryState = m::mock(As4RetryState::class);
$retryState->allows('getFirstSentAt')->andReturn(new DateTime('2000-01-01'));
$retryState->allows('getMaxAttempts')->andReturn(3);
```

### Negative expectation

```php
$repo->shouldNotReceive('save');
```

### Spy (any call, no assertion)

```php
m::spy(LoggerInterface::class)
```

---

## Example — `As4RetryEngineTest`

`detectMissingReceipts()` has three unit-testable scenarios with no database
needed — all dependencies are interfaces:

| Test | Scenario |
|------|----------|
| `detectMissingReceiptsReturnsZeroForEmptyQueue` | `findAwaitingReceipts()` returns `[]` → count 0, `save` never called |
| `detectMissingReceiptsSkipsMessageWithNullFirstSentAt` | Message has no first-sent timestamp → skipped, `save` never called |
| `detectMissingReceiptsMarksTimedOutMessageFailed` | `firstSentAt` in 2000, deadline long passed → `markFailed('EBMS:0301', …)` + `save` called → count 1 |

See [`Tests/Testo/Invoice/As4/As4RetryEngineTest.php`](../Tests/Testo/Invoice/As4/As4RetryEngineTest.php).

---

## References

- [php-testo/testo issue #41](https://github.com/php-testo/testo/issues/41) — feature request that led to this bridge
- [testo/bridge-mockery on Packagist](https://packagist.org/packages/testo/bridge-mockery)
- [mockery/mockery](https://github.com/mockery/mockery)
