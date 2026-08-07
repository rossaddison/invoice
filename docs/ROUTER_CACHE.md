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
CacheInterface::class => extension_loaded('apcu') ? ApcuCache::class : FileCache::class,
```

**As of July 2026 this is conditional, not always `FileCache`.** Wherever the
`apcu` extension is loaded — which it is on this project's documented
Alpine setup and locally on WAMP — the binding resolves to `ApcuCache`
instead, and the dispatch table lives in shared memory, not
`runtime/cache/routes-cache` on disk. See
[YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md](YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md)
for the full detail — that doc is the authoritative one on this mechanism;
the rest of this page still describes the on-disk `FileCache` behavior,
which only applies when `apcu` isn't loaded.

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

Whenever routes change on a production server the stale cache must be
cleared before the next request, otherwise new or modified routes are invisible
to the dispatcher. **Which action actually clears it depends on the backend
(above):**

- **`FileCache`** (no `apcu` extension loaded): delete the file.
  ```bash
  rm runtime/cache/routes-cache*
  # or wipe the entire runtime cache (also clears other stale data)
  rm -rf runtime/cache/*
  ```
- **`ApcuCache`** (the normal case on this project's production/Alpine
  setup): there is no file to delete — `routes-cache` lives in the web
  server's own APCu shared-memory segment. **Restart the web server**
  (`rc-service apache2 restart` on Alpine) to tear it down; `php yii
  cache/clear` runs on the CLI, which APCu gives a completely separate
  memory pool from Apache/PHP-FPM's, so it cannot reach the cache the site
  is actually serving from.

Add whichever applies as a step in your deploy script so the first real
request after deploy rebuilds a fresh dispatch table.

### Symptom when cache is stale

New routes added in `config/common/routes/routes.php` return **404 Not Found**
even though `git pull` has run and the PHP files are on disk.  The browser
console shows the full URL with correct path and query string, but the server
finds no matching route.

**Real example (June 2026, `FileCache` era):** after adding
`inv/batchEmailPreview` and `inv/batchEmail`, every fetch to those endpoints
returned 404 on production while localhost (which runs with `YII_ENV=dev` /
cache disabled) worked perfectly.  Clearing `runtime/cache/*` on the
production server fixed it immediately with no other changes required.

**Don't assume every 404 on a payment/gateway route is this cache, though**
(real example, August 2026): `squareInForm` 404'd even after a full Apache
restart. The actual cause was unrelated to routing entirely —
`PaymentGatewayGuardTrait::requireGatewayConfigured()` deliberately returns
a 404 (with a flash message) whenever a gateway's `isConfigured()` check
fails, e.g. a missing `locationId` for Square. Route caching only explains a
404 for a route that doesn't resolve *at all*; a route that resolves fine
but the controller itself chooses to 404 is a different failure mode with
the same symptom. Check `runtime/logs/*.log` for a flash-message clue
before assuming cache.

---

## Testing the cache locally

To verify the cache works without deploying:

1. Temporarily set `YII_ENV=prod` in `.env`
2. Make one HTTP request
3. Subsequent requests skip compilation
4. Revert to `YII_ENV=dev` when done

Where to look for step 2 depends on the backend (above): if `apcu` isn't
loaded on your local machine, check `runtime/cache/` for a `routes-cache`
file and delete it in step 4. If `apcu` **is** loaded — true on this
project's documented WAMP setup — there's no file; the cache lives in PHP's
local APCu memory instead, and restarting your local web server (or PHP
process) clears it in step 4, same as production.

---

## Performance context

The benchmark suite (`composer bench:router`) measures `UrlMatcher::match()`
without a cache (the benchmark builds a fresh matcher per run).  The numbers
reflect compilation + dispatch overhead together.  In production with the cache
enabled only the dispatch half remains, which accounts for the much lower
latency seen on Linux vs the Windows benchmark figures.

See [Performance Benchmarks](PERFORMANCE_BENCHMARKS.md) for full benchmark
results and the Chart.js trend dashboard.
