# Yii3-i Performance Benchmarks

## Overview

This repository includes a custom benchmark suite that tracks the execution speed
of four core Yii3 components over the entire lifespan of the project.  Results
accumulate in `benchmarks/results/history.json` and are visualised in an
interactive Chart.js dashboard at `benchmarks/dashboard/index.html`.

---

## Benchmark Suites

| Suite | File | What is measured |
|-------|------|-----------------|
| **di** | `DiContainerBench.php` | `Yiisoft\Di\Container` — singleton get, deep dependency chain, container build |
| **injector** | `InjectorBench.php` | `Yiisoft\Injector\Injector` — callable auto-wire (cached vs uncached reflection) |
| **router** | `RouterBench.php` | `Yiisoft\Router\FastRoute\UrlMatcher` — static, parametrised, worst-case, 404 |
| **strings** | `StringHelperBench.php` | `StringHelper`, `Inflector`, `WildcardPattern`, `CombinedRegexp` |

### DI Container (`di`)

| Subject | What it tests |
|---------|--------------|
| `benchSingletonGet` | Container cache hit — map lookup only; should be **< 0.5 μs** |
| `benchDeepChainGet` | Five-level dependency chain, all singletons cached |
| `benchWideConstructorGet` | Three-argument constructor, all deps cached |
| `benchContainerBuild` | Cold build of a fresh Container with 6 definitions |
| `benchHasDefinition` | `has()` boolean check — pure hash lookup |

### Injector (`injector`)

| Subject | What it tests |
|---------|--------------|
| `benchInvokeOneDep_cached` | One auto-resolved arg; reflection cache warm |
| `benchInvokeThreeDeps_cached` | Three auto-resolved args; reflection cache warm |
| `benchInvokeClosure_cached` | Closure variant (avoids function-name resolution overhead) |
| `benchInvokeThreeDeps_uncached` | Same as above but `withCacheReflections(false)` — shows reflection penalty |
| `benchMakeClass` | `make()` — create class with constructor injection |

### Router (`router`)

50-route table mirroring the real Yii3-i route set.

| Subject | What it tests |
|---------|--------------|
| `benchMatchRoot` | `/` — first route in table (best case) |
| `benchMatchStaticMid` | `/invoice` — static route, middle of table |
| `benchMatchParametrised` | `/invoice/view/{id:\d+}` — regex capture |
| `benchMatchDeepParam` | `/report/revenue/{year:\d{4}}` — named + constrained param |
| `benchMatchWorstCase` | `/admin/log/view/{id:\d+}` — near-last, parametrised |
| `benchMatch404` | `/does/not/exist` — full table exhaust, no match |

### String Helpers (`strings`)

| Subject | What it tests |
|---------|--------------|
| `benchStartsWith` | `StringHelper::startsWith` |
| `benchEndsWith` | `StringHelper::endsWith` |
| `benchTruncateEnd` | `StringHelper::truncateEnd` (UTF-8 aware) |
| `benchCountWords` | `StringHelper::countWords` |
| `benchToSnakeCase` | `Inflector::toSnakeCase` — used for column name derivation |
| `benchToCamelCase` | `Inflector::toCamelCase` |
| `benchToPascalCase` | `Inflector::toPascalCase` |
| `benchToPlural` | `Inflector::toPlural` — English pluralisation rules |
| `benchToSingular` | `Inflector::toSingular` |
| `benchClassToTable` | `Inflector::classToTable` — `InvoiceLineItem` → `invoice_line_items` |
| `benchWildcardMatch_hit` | `WildcardPattern::match` (hit) |
| `benchWildcardMatch_miss` | `WildcardPattern::match` (miss) |
| `benchCombinedRegexp_hit` | `CombinedRegexp` — 5 patterns merged into one `preg_match` |
| `benchMemoizedRegexp_hit` | `MemoizedCombinedRegexp` — same with result cache |

---

## Running Benchmarks

```bash
# All suites — appends one run to benchmarks/results/history.json
composer bench

# Single suite
composer bench:di
composer bench:injector
composer bench:router
composer bench:strings

# Dry run — prints table but does NOT write to history.json
composer bench:dry

# Pass flags directly
php benchmarks/run.php --suite=router --dry-run
```

Each run prints a formatted table to stdout:

```
──────────────────────────────────────────────────────────────────────────────────────────
  Benchmark                                              mean μs   stdev μs        ops/sec
──────────────────────────────────────────────────────────────────────────────────────────
  [di]
    benchSingletonGet                                    0.06000    0.00200     16,666,666
    benchDeepChainGet                                    0.08400    0.00300     11,904,762
    ...
```

---

## How Results Are Stored

Every successful run appends one JSON object to `benchmarks/results/history.json`:

```json
{
  "id":             "2026-05-25T02:00:00+00:00",
  "date":           "2026-05-25",
  "commit":         "ceff45c",
  "commit_message": "Manual Testing 3",
  "branch":         "main",
  "php_version":    "8.4.6",
  "os":             "Linux x86_64",
  "results": {
    "di::benchSingletonGet": {
      "mean_μs":    0.06000,
      "stdev_μs":   0.00200,
      "min_μs":     0.05800,
      "max_μs":     0.06400,
      "ops_per_sec": 16666666,
      "revs":        5000,
      "its":         7
    }
  }
}
```

The file is committed back to the repository by the CI workflow, so every
contributor can see the full historical trend without needing to run anything
locally.

---

## Timing Engine

`benchmarks/run.php` uses PHP's `hrtime(true)` (nanosecond monotonic clock) to
time $revs repetitions per iteration, over $iterations iterations (after warmup
rounds).  The single best and single worst iteration are discarded to reduce
noise, and the mean + stdev of the remaining values are stored.

This avoids:
- Cold-start effects (warmup rounds)
- Scheduling outliers (outlier removal)
- Clock resolution granularity (thousands of revs per timing window)

---

## Viewing the Dashboard

Open `benchmarks/dashboard/index.html` in a browser **served from a local HTTP
server** (not `file://` — the page fetches `results/history.json` via `fetch()`):

```bash
# From the project root:
php -S localhost:8080 -t benchmarks

# Then open:
# http://localhost:8080/dashboard/
```

The dashboard shows:
- **Summary cards** — latest run date, PHP version, total runs, fastest operation
- **Run selector** — toggle individual runs in/out of the charts
- **Main trend chart** — all benchmarks on one time-series (filterable by suite)
- **Per-suite charts** — zoomed-in view of each group
- **Results table** — latest numbers with trend arrows (↑ regression / ↓ improvement) and a normalised ops/sec bar chart

---

## CI / Automatic Recording

The GitHub Actions workflow `.github/workflows/benchmark.yml` runs every Monday
at 02:00 UTC (or on `workflow_dispatch`), records results, and commits
`history.json` back to the default branch.

Key settings:
- PHP 8.4 with **OPcache JIT (tracing mode)** enabled — mirrors production
- `ramsey/composer-install` with caching for fast setup
- Job summary posted to the GitHub Actions UI
- 90-day artifact retention for the raw JSON
- Skips the commit step on pull requests to avoid repo pollution

---

## Interpreting the Numbers

| Metric | Meaning |
|--------|---------|
| `mean_μs` | Average time per single operation in **microseconds** |
| `stdev_μs` | Standard deviation — low values mean stable results |
| `ops_per_sec` | `1,000,000 / mean_μs` — operations per second |
| Trend arrow ↓ | Mean improved (faster) vs the previous run — **green** |
| Trend arrow ↑ | Mean regressed (slower) vs the previous run — **red** |

A regression of < 2 % is shown as ≈ 0 % to suppress noise.

---

## Extending the Suite

To add a new benchmark:

1. Create `benchmarks/src/MyBench.php` returning a factory closure:

```php
<?php
declare(strict_types=1);

return static function (): array {
    // setup (runs once per suite load)
    $thing = new Thing();

    return [
        'benchSomething' => [
            'fn'     => static fn() => $thing->doSomething(),
            'revs'   => 1000,
            'warmup' => 3,
            'its'    => 7,
        ],
    ];
};
```

2. Register it in `benchmarks/run.php` → `$suiteFiles` array.
3. Add a `<canvas>` panel in `benchmarks/dashboard/index.html` and call
   `renderSuiteChart('myCanvas', 'mykey')`.
