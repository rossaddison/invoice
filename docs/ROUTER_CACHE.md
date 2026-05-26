# FastRoute Dispatch Cache

## What it does

`Yiisoft\Router\FastRoute\UrlMatcher` can persist its compiled route dispatch
table to a PSR-16 cache so that the compilation step is skipped on every
request after the first.

Without cache every request executes:

```
routes.php loaded → all Route objects built → FastRoute compiles regex dispatch table → match URL
```

With cache every request after the first executes:

```
runtime/cache/routes-cache loaded → match URL
```

The dispatch table is a plain PHP array, so loading it from file is a simple
`include` rather than regex compilation across the full route set.

---

## How it is wired in Yii3-i

### PSR-16 binding (`config/common/di/cache.php`)

```php
CacheInterface::class => FileCache::class,
```

`FileCache` writes to `@runtime/cache` (resolved to `runtime/cache/` from the
project root) using the vendor default from `yiisoft/cache-file`.

### UrlMatcher factory (`vendor/yiisoft/router-fastroute/config/di-web.php`)

```php
UrlMatcherInterface::class => static function (Injector $injector) use ($params) {
    $enableCache = $params['yiisoft/router-fastroute']['enableCache'] ?? true;
    $arguments = [];
    if ($enableCache === false) {
        $arguments['cache'] = null;
    }
    return $injector->make(UrlMatcher::class, $arguments);
},
```

When `enableCache` is `true` the injector creates `UrlMatcher` with the
container's `CacheInterface` instance (`FileCache`).  When `false` it passes
`cache: null`, disabling the cache entirely.

### Cache key

`UrlMatcher` uses the cache key `'routes-cache'` by default
(`UrlMatcher::CONFIG_CACHE_KEY`).  The compiled data is stored as
`runtime/cache/routes-cache` (exact filename depends on `FileCache`'s suffix
setting, typically no extension).

---

## Environment split

Cache is **off in development** and **on in production**.  This is controlled
by `YII_ENV` (set in `.env`):

| `YII_ENV` | params file loaded | `enableCache` |
|-----------|--------------------|--------------|
| `dev` | `config/environments/dev/params.php` | `false` (from `common/params.php`) |
| `prod` | `config/environments/prod/params.php` | `true` (overrides common) |
| `test` | `config/environments/test/params.php` | `false` (from `common/params.php`) |

### `config/common/params.php` (applies to all environments)

```php
'yiisoft/router-fastroute' => [
    'enableCache' => false,
    'encodeRaw'   => true,
],
```

### `config/environments/prod/params.php` (production override)

```php
'yiisoft/router-fastroute' => [
    'enableCache' => true,
],
```

The `YII_ENV` variable is also read in `config/common/params.php`:

```php
'env' => $_ENV['YII_ENV'] ?? 'dev',
```

and in `autoload.php` / `src/Auth/Trait/OAuth2` for other environment checks.

---

## Deployment rule

Whenever routes change on a production server the stale cache file must be
cleared before the next request, otherwise new or modified routes are invisible
to the dispatcher.

```bash
# Clear only the router cache
rm runtime/cache/routes-cache*

# Or wipe the entire runtime cache (also clears other stale data)
rm -rf runtime/cache/*
```

Add this as a step in your deploy script **before** restarting PHP-FPM /
Apache so the first real request after deploy rebuilds a fresh dispatch table.

---

## Testing the cache locally

To verify the cache works without deploying:

1. Temporarily set `YII_ENV=prod` in `.env`
2. Make one HTTP request — check `runtime/cache/` for a `routes-cache` file
3. Subsequent requests skip compilation
4. Revert to `YII_ENV=dev` and delete `runtime/cache/routes-cache*` when done

---

## Performance context

The benchmark suite (`composer bench:router`) measures `UrlMatcher::match()`
without a cache (the benchmark builds a fresh matcher per run).  The numbers
reflect compilation + dispatch overhead together.  In production with the cache
enabled only the dispatch half remains, which accounts for the much lower
latency seen on Linux vs the Windows benchmark figures.

See [Performance Benchmarks](PERFORMANCE_BENCHMARKS.md) for full benchmark
results and the Chart.js trend dashboard.
