# PHP 8.4 → 8.5 Production Upgrade on Alpine (October 2026)

`yii3i.online` runs on Alpine Linux with Apache + `mod_php` (embedded SAPI,
not FastCGI/CGI — see `docs/PHP2SHELL_THREAT_ANALYSIS_OCTOBER_2026.md` for
why that distinction matters). PHP 8.5 had just been released, and Alpine's
`edge`/`testing` repositories carried the interpreter itself well before
the surrounding extension packages had caught up — which is exactly what
made this upgrade more involved than a normal point-release bump.

## Phase 1 — getting the module loaded (pre-session, assisted by ChatGPT)

1. Confirmed PHP 8.5 packages existed in the configured repos:
   `apk search php85`.
2. Installed the CLI and Phar packages: `apk add php85 php85-phar`,
   verified with `php -v` → `PHP 8.5.12 (cli)`.
3. Installed the Apache module package: `apk add php85-apache2`, which
   placed `mod_php85.so` at `/usr/lib/apache2/`.
4. Found the existing Apache module config —
   `/etc/apache2/conf.d/php85-module.conf` already existed and was
   already the active one — and disabled the old PHP 8.4 config
   (`mv php84-module.conf php84-module.conf.disabled`) so Apache
   wouldn't try to load two competing `php_module` handlers.
5. `httpd -t` → `Syntax OK`, then `rc-service apache2 restart`.
6. Verified via the actual website, not just the CLI: a temporary
   `public/php-version-check.php` (`echo PHP_VERSION, ' ', PHP_SAPI;`)
   loaded through the browser returned `8.5.12 apache2handler`, confirming
   Apache itself — not just the shell — was now running PHP 8.5. Deleted
   the temp file afterward.

This phase correctly got the PHP *interpreter* running inside Apache. It
did **not** surface that `php84`'s extension packages (each a distinct
`apk` package per extension on Alpine) had no `php85` equivalents
installed yet — that only became visible once real requests started
exercising code paths that needed them, which is Phase 2.

## Phase 2 — chasing down every missing extension (this session)

The site returned a bare `500 Internal Server Error` with an **empty
response body** and **no corresponding entry in Apache's error log** —
PHP 8.5's freshly-installed default `php.ini` had different
`log_errors`/`display_errors` defaults than `php84`'s already-tuned one,
so the usual "tail the error log" approach found nothing. The fastest way
to see the real, unfiltered error turned out to be invoking the
application's own entry script directly through the PHP 8.5 CLI binary,
bypassing Apache (and its logging config) entirely:

```sh
php85 /var/www/invoice/public/index.php > /tmp/php85test.log 2>&1
cat /tmp/php85test.log
```

This surfaced one missing extension at a time — each fix uncovering the
next failure further into the bootstrap/dependency chain:

1. **`sodium`** — `Undefined constant "SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES"`,
   thrown from `php-standard-library/php-standard-library`'s crypto
   bootstrap.
2. **`mbstring` + `iconv`** — Symfony's `polyfill-mbstring` kicked in for
   the missing `mbstring`, but its own fallback implementation calls the
   *real* `iconv()`, which was also missing; `vlucas/phpdotenv` needs this
   chain just to read `.env`.
3. **`pdo` + `pdo_mysql`** — `Class "PDO" not found`, from
   `cycle/database`'s MySQL driver config — the actual database layer
   couldn't even construct its connection config.
4. **`intl`**.
5. **`session`** — surfaced as `Call to undefined function
   Yiisoft\Session\session_status()`; a core PHP function reported
   "undefined" is a reliable sign the whole extension (not just one
   function) isn't loaded, not a namespace/autoload problem.

Each was installed with `apk add php85-<name>` (`pdo` and `pdo_mysql` are
separate packages on Alpine), followed by a **full** `rc-service apache2
restart` — not a reload — since `mod_php` is an embedded module and new
`.so` extensions only get picked up when the Apache process itself starts
fresh.

### Closing the rest of the gap in one pass

Rather than continuing the same one-fatal-error-at-a-time cycle
indefinitely, once the site was reachable again the two PHP versions'
loaded-module lists were diffed directly to find every remaining gap at
once:

```sh
comm -23 <(php84 -m | sort) <(php85 -m | sort)
```

This listed sixteen more extensions `php84` had that `php85` didn't —
several of them load-bearing for real application features rather than
just the bootstrap path: `curl` (payment gateway API calls — Stripe,
Adyen, and the rest, per the app's own CSP header allow-list), `xml` /
`dom` / `simplexml` / `xmlreader` / `xmlwriter` / `xsl` (Peppol/UBL
e-invoicing XML generation, a core feature of this app), `zip` (exports),
`gd` / `imagick` (image handling), plus `apcu`, `bcmath`, `ctype`,
`fileinfo`, `pdo_sqlite`, `tokenizer`. Installed in one batch:

```sh
apk add php85-simplexml php85-apcu php85-bcmath php85-ctype php85-curl \
  php85-dom php85-fileinfo php85-gd php85-pecl-imagick php85-pdo_sqlite \
  php85-tokenizer php85-xml php85-xmlreader php85-xmlwriter php85-xsl \
  php85-zip
```

One package name needed a lookup first: `php85-imagick` doesn't exist —
on Alpine, Imagick (like APCu) is packaged under a `pecl-` prefix
(`php85-pecl-imagick`), found via `apk search php85*imagick*`. The rest
resolved on the first try. After installing, re-running the `comm -23`
diff returned empty — `php85 -m` now matches `php84 -m` exactly — and a
final `rc-service apache2 restart` plus `curl -sI https://yii3i.online/`
confirmed a genuine `200 OK`, `X-Powered-By: PHP/8.5.12`.

## Takeaways for the next PHP version bump

- **Diff the module list early, not just when something breaks.**
  `comm -23 <(phpOLD -m | sort) <(phpNEW -m | sort)` right after installing
  the new interpreter would have caught all 23 missing extensions
  (7 from the one-at-a-time chase + 16 from the batch) in a single pass,
  before ever touching the live site.
- **A successful Apache startup log line (`PHP/8.5.12 configured --
  resuming normal operations`) only proves the interpreter and Apache
  module loaded — it says nothing about which extensions are present.**
  The 500s only started once real request-handling code ran.
- **When the Apache error log goes quiet on a 500, go straight to a
  direct CLI invocation of the entry script** (`php85 public/index.php`)
  rather than hunting for the right log file/config — a freshly-installed
  PHP version's default `php.ini` can have different logging defaults
  than an already-tuned one.
- Full `rc-service apache2 restart` (not `reload`) after every extension
  install — `mod_php` only picks up new `.so` files on process start.
