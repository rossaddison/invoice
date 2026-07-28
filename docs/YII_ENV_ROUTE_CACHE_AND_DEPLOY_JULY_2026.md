# What `YII_ENV` Actually Controls, and the Real Cause Behind "Clear the Cache" — July 2026

## TL;DR — in plain English

- On your **local WAMP box** (`YII_ENV=dev`), route caching is switched
  **off**. Every page request rebuilds the route table fresh from whatever's
  currently in the route config files. There is no cache sitting there
  going stale — so `php yii cache/clear` has nothing meaningful to do for
  routing. Running it locally is harmless, just pointless for this purpose.
- On **production** (`yii3i.online`, `YII_ENV=prod`), route caching is
  switched **on**. The first request after a fresh cache builds the route
  table once and saves it to a file, then every request after that reuses
  that same file forever — there's no expiry, no "has this changed?"
  check, nothing. So it's production, and only production, that ever needs
  `php yii cache/clear` — and only after a route was added, changed, or
  removed.
- This has **nothing to do with** other kinds of changes not showing up —
  a new menu link, a new form field, any HTML on a page. Those are plain
  PHP that reruns on every request everywhere, dev and prod alike. If one
  of those "isn't showing up," the cause is almost always that the change
  was never actually committed and pushed — check that first, long before
  suspecting any cache.

The rest of this doc is the detailed, line-by-line version of the above,
for anyone who wants to see exactly where in the code this happens.

## Background

`.env`'s `YII_ENV` (`dev` / `prod` / `test`) is documented as affecting "other
settings," but until now nothing spelled out exactly which settings, through
which mechanism, or why production deploys sometimes need an explicit cache
clear that local development never does. This grew out of a real support
question — a new `inv/index` navbar link wasn't showing up on
`yii3i.online` after a `git push`. The actual cause that time was mundane
(a file edit that had never been committed — see the "HomeCare Worker
Allocation" entry above), but tracking it down surfaced a second, entirely
real mechanism that **does** require a manual cache clear after certain
deploys: FastRoute's route-dispatch cache. This doc explains that mechanism
precisely, and draws a hard line around what it does and does not affect —
so the next time something "isn't showing up," it's obvious which of the
two categories it falls into.

## 1. Where `YII_ENV` is read

- `autoload.php:42-43` normalizes it (blank string → `null`) and mirrors it
  into `$_SERVER['YII_ENV']`.
- `public/index.php:44` and `yii:15` (the console entry point) both pass it
  straight through as the `environment:` constructor argument to Yii3's
  `HttpApplicationRunner` / `ConsoleApplicationRunner`.

That `environment:` argument is the single lever `YII_ENV` pulls. It tells
`yiisoft/config` which of `config/environments/{dev,prod,test}/params.php`
to merge on top of every other config file, **after** `config/common/params.php`
and everything else has already been merged — so an environment file's
values win over the app-wide defaults.

## 2. What's actually different per environment, today

| File | Content |
|---|---|
| `config/environments/dev/params.php` | `[]` — empty |
| `config/environments/test/params.php` | `[]` — empty |
| `config/environments/prod/params.php` | `['yiisoft/router-fastroute' => ['enableCache' => true]]` |

That's it — one real override in the whole app, and it only fires in
`prod`. `config/common/params.php:347-348` sets the app-wide default to
`'enableCache' => false` (overriding the `yiisoft/router-fastroute` package's
own default of `true`), so **route caching is off everywhere except prod**,
where the environment file explicitly turns it back on.

## 3. The exact mechanism the cache is — and isn't

`config/common/di/cache.php` binds `Psr\SimpleCache\CacheInterface` to
`Yiisoft\Cache\File\FileCache`, a plain file-backed cache writing under
`@runtime/cache`. `vendor/yiisoft/router-fastroute/config/di-web.php` reads
`enableCache` at container-build time:

```php
$enableCache = $params['yiisoft/router-fastroute']['enableCache'] ?? true;
$arguments = [];
if ($enableCache === false) {
    $arguments['cache'] = null;
}
return $injector->make(UrlMatcher::class, $arguments);
```

So the *only* thing `enableCache` decides is whether `UrlMatcher` gets a
real `FileCache` instance or a literal `null`. Inside
`Yiisoft\Router\FastRoute\UrlMatcher` (`vendor/yiisoft/router-fastroute/src/UrlMatcher.php`):

```php
private function loadDispatchData(): void
{
    if ($this->cache !== null && $this->cache->has($this->cacheKey)) {
        $this->dispatchData = $this->cache->get($this->cacheKey);
        $this->hasCache = true;
        return;
    }
    $this->hasCache = false;
}
```

`$this->cacheKey` defaults to the literal string `'routes-cache'` — this is
the `runtime/cache/routes-cache*` file the deploy doc already told you to
clear. Two things matter here:

- **When `$cache` is `null` (dev/test)**: `$this->cache !== null` is always
  false, `hasCache` never becomes `true`, and every single request
  recompiles the full FastRoute dispatch table straight from whatever's
  currently in `config/common/routes/*.php`. This is why adding, editing,
  or removing a route on localhost takes effect on the very next request —
  there is no cache to be stale.
- **When `$cache` is a real `FileCache` (prod)**: the very first request
  after the cache is empty computes the dispatch table once and writes it
  to `routes-cache` on disk. Every request after that hits
  `$this->cache->has('routes-cache')` and returns `true` — **there is no
  TTL, no timestamp check, no invalidation logic of any kind.** That cached
  dispatch table is used forever, for every subsequent request, until the
  file is physically deleted.

## 4. Why `git pull` alone never fixes this

`runtime/` is excluded from version control entirely (`runtime/.gitignore`
contains a bare `*`), specifically so runtime state like this cache
survives across deploys. That's the right default for most of what lives
under `runtime/` (sessions, logs, uploaded assets) — but it means a
`git pull` on the Alpine production server **cannot** touch
`runtime/cache/routes-cache`, no matter how many routes changed in the
pulled commits. The stale compiled table just keeps being served. This is
solved with either:

```bash
# Wipes everything under runtime/cache/ — DI, config, and route caches alike
php yii cache/clear
```

or manually:

```bash
rm -f /var/www/invoice/runtime/cache/routes-cache*
```

`CacheClearCommand` (`src/Command/CacheClearCommand.php`) does the former —
it doesn't distinguish which cache is which, it just recursively deletes
every file under `@runtime/cache`, which is a safe, complete fix (the
caches simply get rebuilt fresh from config on the next request).

## 5. What this does **not** explain — the other half of "not appearing"

This mechanism is scoped **exclusively** to route matching: it only matters
if you added, removed, or changed a route definition (a new
`Route::get(...)->name(...)` entry, a changed path pattern, a changed
controller action mapping). It has **nothing to do with**:

- View/layout content — like a new navbar dropdown link. Views are plain
  PHP executed fresh on every request in this app; there is no view-output
  cache anywhere in the stack. If a menu item, a form field, or any other
  rendered HTML "isn't showing up," route caching is never the cause.
- Settings values (`SettingRepository`) — those come from the database, not
  this file cache.
- The DI container's own compiled definitions, RBAC files, or anything
  else — `php yii cache/clear` happens to sweep all of `runtime/cache/` in
  one go, which is why its own description says "DI, config, and routes,"
  but only the FastRoute entry actually goes stale in a way that silently
  serves old behavior; the others are effectively inert once the container
  has already booted for a request.

The real, mundane cause behind July 2026's "Workers link isn't showing up
on `yii3i.online`" was simply that the layout edit had never been
committed — `git status` showed it as an uncommitted local change, so it
was never pushed and never could have reached the server regardless of any
cache. Route caching was investigated and ruled out as the cause, but is
documented here in full because it's a real, distinct failure mode that
*will* eventually bite a genuine route change if `cache/clear` is skipped
after a deploy.

## 6. A third, independent consumption path: raw `$_ENV['YII_ENV']` checks

Separate from both the environment-file merge (§1-2) and `$params['env']`/
`SettingRepository::getEnv()` (§3, in the earlier grep discussion), a few
places read the raw superglobal directly rather than going through either
of those. `autoload.php:42-43` does this to normalize the value before
anything else boots. `config/web/di/mailer.php` (added alongside this doc)
does the same thing for the same reason those files need to: **DI
bindings are decided while the container is being built, before
`SettingRepository` exists to inject anything into** — so a container-time
decision like "which `MailerInterface` implementation to bind" has to read
`$_ENV['YII_ENV']` directly:

```php
return ($_ENV['YII_ENV'] ?? '') === 'dev'
    ? [MailerInterface::class => FileMailer::class]
    : [];
```

In dev, every email the app would send (signup confirmation, forgot
password, HomeCare signup, batch email) is written to `runtime/mail/*.eml`
instead of actually being sent — no SMTP round-trip, no real inbox needed
to test a confirmation-link flow. This is strictly opt-in to `YII_ENV ===
'dev'`, so prod and test keep the real `yiisoft/mailer-symfony` transport
untouched. Since this is a DI-time branch rather than a value merged
through `config/environments/`, it doesn't show up in that table in §2 —
worth knowing it's a distinct third mechanism if you ever go looking for
"where does `YII_ENV` matter" and only check the environment-params files.

## 7. Quick reference: is it the route cache, or something else?

- **Added/changed/removed a route, deployed to prod, and hitting that URL
  404s or hits the wrong action?** → Route cache. Run `php yii cache/clear`
  on the server (or the manual `rm -f routes-cache*`), per
  `docs/WSL_TO_ALPINE_DEPLOYMENT.md`'s deploy script.
- **A view, layout, menu item, form field, or any other rendered HTML isn't
  showing up after a deploy?** → Not the route cache — verify the change
  was actually committed (`git status` / `git log -p -- <file>` on the
  server) and actually pulled (`git log origin/main..main` should be
  empty), in that order.
- **Testing locally on WAMP and a route change doesn't seem to apply?** →
  Can't be the route cache either — `enableCache` is `false` in dev by
  design, so caching isn't in play at all locally. Look elsewhere (browser
  cache, wrong vhost, PHP opcache with `validate_timestamps` disabled).
