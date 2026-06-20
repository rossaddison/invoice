# Testo Integration — PHP Testing Framework

## What is Testo?

[Testo](https://github.com/php-testo/testo) is a standalone PHP testing framework by
Aleksei Gagarin (roxblnfk), built around PHP 8 attributes and a middleware plugin system.
It has no dependency on PHPUnit.

Key features relevant to this project:

| Feature | Plugin | Purpose |
|---|---|---|
| `#[Test]` attribute | `testo/test` | Mark test classes/methods — no `extends TestCase` |
| Yii3 DI injection | `testo/facade` + `yiisoft/injector` | Inject real services into test methods |
| Data providers | `testo/data` | Typed data sets replacing PHPUnit `#[DataProvider]` |
| Inline tests | `testo/inline` | Tests embedded in source files (helpers, utilities) |
| Coverage output | `testo/codecov` | Clover + Cobertura — SonarCloud compatible |
| Retry policy | `testo/retry` | `#[Retry(maxAttempts: 3)]` for flaky external calls |
| Benchmarks | `testo/bench` | Performance assertions alongside correctness tests |
| Mutation testing | `testo/bridge-infection` | Infection PHP integration |

## Why Testo in this Project

The `#[Test]` attribute style is consistent with Cycle ORM's attribute-based entity
mapping (`#[Entity]`, `#[Column]`, `#[HasOne]`). Adopting Testo means the entire
codebase — entities, relations, and tests — uses the same PHP 8 attribute syntax.

The `InjectPlugin` (via `testo/facade` + `yiisoft/injector`) means Yii3 services and
repositories can be injected directly into test methods using the real DI container,
eliminating mock boilerplate for integration tests:

```php
#[Test]
final class InvRepositoryTest
{
    public function countsInvoices(InvRepository $repo): void
    {
        Assert::same(0, $repo->repoCount(999));
    }
}
```

## Assertion Style

Testo provides its own `Testo\Assert` class — not PHPUnit's `PHPUnit\Framework\Assert`.
The API uses static calls and optional fluent chaining:

```php
use Testo\Assert;

// Static calls
Assert::same($result, 5);
Assert::true($flag);
Assert::null($value);
Assert::instanceOf($obj, MyClass::class);

// Fluent chain with type assertion
Assert::int($result)->same(5)->greaterThan(0);
Assert::string($name)->contains('invoice');
```

### Exception assertions

`#[ExpectException]` accepts only the exception class — there is no `message:` parameter.
To also assert the message, use a try/catch block:

```php
// Verify exception type only
#[ExpectException(\LogicException::class)]
public function reqIdThrowsWhenUnpersisted(): void
{
    (new Family())->reqId();
}

// Verify exception type AND message
public function reqIdThrowsWithCorrectMessage(): void
{
    try {
        (new Family())->reqId();
    } catch (\LogicException $e) {
        Assert::same($e->getMessage(), 'Family not persisted');
        return;
    }
    Assert::true(false);
}
```

## Mock Support — Current Status (June 2026)

**Testo has no mock library yet.** Roadmap issue
[#41](https://github.com/php-testo/testo/issues/41) plans a bridge to
[Mockery](https://github.com/mockery/mockery) but is unstarted.

Testo's intended approach is to replace mocks with **real DI container injection** —
inject the actual Yii3 service rather than a mock. This works well for integration
tests but requires a running database.

## Migration Strategy

Testo (`minimum-stability: dev`) runs alongside PHPUnit. Both output Clover XML;
SonarCloud merges coverage from both. The migration is therefore incremental.

### Migrate now

| Test type | Location | Notes |
|---|---|---|
| DDD entity tests | `Tests/Unit/Invoice/Entity/` | Pure assertions on `reqId()`, `isPersisted()`, setters |
| Helper / utility tests | Inline in `src/Invoice/Helper*/` | Use `testo/inline` |
| Simple service tests | `Tests/Unit/` | No mocks needed |

### Wait until Mockery bridge ships (#41)

| Test type | Blocker |
|---|---|
| Service tests using `createMock()` | No mock library |
| Repository tests using `createMock()` | No mock library |
| Controller tests | No HTTP layer + no mock library |

Until #41 lands, these tests remain in PHPUnit unchanged.

## Dual Runner Setup

Both test runners are registered in `composer.json` scripts:

```json
"scripts": {
    "test:phpunit": "vendor/bin/phpunit --coverage-clover coverage-phpunit.xml",
    "test:testo":   "vendor/bin/testo",
    "test":         ["@test:phpunit", "@test:testo"]
}
```

SonarCloud `sonar-project.properties` merges both coverage files:

```properties
sonar.php.coverage.reportPaths=coverage-phpunit.xml,coverage-testo.xml
```

## Testo Configuration (`testo.php`)

Place `testo.php` in the project root:

```php
<?php

declare(strict_types=1);

use Testo\Application\Config\ApplicationConfig;
use Testo\Application\Config\SuiteConfig;

return new ApplicationConfig(
    src: ['src'],
    suites: [
        new SuiteConfig(
            name: 'Unit',
            location: ['Tests/Testo'],
        ),
        // Inline tests and benchmarks embedded in source files
        new SuiteConfig(
            name: 'Sources',
            location: ['src'],
        ),
    ],
);
```

> **Note:** All default plugins (`AssertPlugin`, `InlineTestPlugin`, `LifecyclePlugin`,
> `TestPlugin`, `BenchmarkPlugin`) are loaded automatically via `class_exists` guards —
> no explicit plugin configuration needed. `FacadePlugin` (for DI injection) is silently
> skipped until `testo/facade` is installed.

## Working Examples

Both tests live in `Tests/Testo/` and pass as of June 2026.

### Example 1 — Infrastructure entity (`Family`)

[Tests/Testo/Infrastructure/Persistence/Family/FamilyTest.php](../Tests/Testo/Infrastructure/Persistence/Family/FamilyTest.php)

Covers the DDD infrastructure pattern: `reqId()`, `hasIdentity()`, `setId()`, and all
getters/setters. No DI container, no database — pure PHP assertions.

```php
<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Persistence\Family;

use App\Infrastructure\Persistence\Family\Family;
use Testo\Assert;
use Testo\Assert\ExpectException;
use Testo\Test;

#[Test]
final class FamilyTest
{
    public function defaultsToUnpersisted(): void
    {
        $family = new Family();

        Assert::false($family->hasIdentity());
    }

    #[ExpectException(\LogicException::class)]
    public function reqIdThrowsWhenUnpersisted(): void
    {
        (new Family())->reqId();
    }

    public function reqIdThrowsWithCorrectMessage(): void
    {
        try {
            (new Family())->reqId();
        } catch (\LogicException $e) {
            Assert::same($e->getMessage(), 'Family not persisted');
            return;
        }
        Assert::true(false);
    }

    public function setIdMakesEntityIdentifiable(): void
    {
        $family = new Family();
        $family->setId(42);

        Assert::true($family->hasIdentity());
        Assert::same($family->reqId(), 42);
    }

    public function settersAndGetters(): void
    {
        $family = new Family(
            family_name: 'Electronics',
            family_commalist: 'tv,radio',
            family_productprefix: 'ELEC',
            category_primary_id: 1,
            category_secondary_id: 2,
            street_sort_order: 5,
        );

        Assert::same($family->getFamilyName(), 'Electronics');
        Assert::same($family->getCategoryPrimaryId(), 1);
        Assert::same($family->getStreetSortOrder(), 5);
    }
}
```

### Example 2 — DI binding (`cache.php`)

[Tests/Testo/Infrastructure/Di/CacheDiTest.php](../Tests/Testo/Infrastructure/Di/CacheDiTest.php)

Verifies `config/common/di/cache.php` wiring without bootstrapping the full Yii3
config system. The `@runtime/cache` alias resolves to `runtime/cache` under the
project root — constructed directly since the path is known.

```php
<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Di;

use Psr\SimpleCache\CacheInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface as YiiCacheInterface;
use Yiisoft\Cache\File\FileCache;

#[Test]
final class CacheDiTest
{
    private readonly FileCache $fileCache;
    private readonly Cache $yiiCache;

    public function __construct()
    {
        $cachePath = dirname(__DIR__, 4) . '/runtime/cache';
        $this->fileCache = new FileCache($cachePath);
        $this->yiiCache  = new Cache($this->fileCache);
    }

    public function fileCacheImplementsPsrCacheInterface(): void
    {
        Assert::instanceOf($this->fileCache, CacheInterface::class);
    }

    public function yiiCacheImplementsYiiCacheInterface(): void
    {
        Assert::instanceOf($this->yiiCache, YiiCacheInterface::class);
    }

    public function fileCacheDirectoryIsWritable(): void
    {
        Assert::true(is_writable(dirname(__DIR__, 4) . '/runtime/cache'));
    }

    public function setAndGetValue(): void
    {
        $this->fileCache->set('testo_di_test', 'invoice', 60);

        Assert::same($this->fileCache->get('testo_di_test'), 'invoice');
    }

    public function deleteValue(): void
    {
        $this->fileCache->set('testo_di_delete', 'to-be-deleted', 60);
        $this->fileCache->delete('testo_di_delete');

        Assert::null($this->fileCache->get('testo_di_delete'));
    }

    public function missingKeyReturnsDefault(): void
    {
        Assert::same($this->fileCache->get('testo_nonexistent_key', 'default'), 'default');
    }
}
```

## Related

- [SonarQube S1144 — False Positive: Private Methods Called Across Trait Boundaries](SONARQUBE_S1144_TRAIT_BOUNDARY.md)
- [SonarQube S107 — Application Service pattern](SONARQUBE_S107_APPLICATION_SERVICE.md)
- [Testo documentation](https://php-testo.github.io)
- [Testo roadmap (issue #2)](https://github.com/php-testo/testo/issues/2)
- [Mockery bridge (issue #41)](https://github.com/php-testo/testo/issues/41)
