# Geolocation Blocked in Production — Three Independent Permissions-Policy Sources — July 2026

## Background

The Settings > Location tab's live GPS tester
(`docs/SETTINGS_LOCATION_TAB_JULY_2026.md`) worked fine locally but always
failed on `yii3i.online` with "Location permission was denied" — every
single time, regardless of browser or OS-level location settings. That
message is what `SettingsHandler.handleGeolocationTestClick()` shows for
`GeolocationPositionError.PERMISSION_DENIED`, which the browser also
returns when a `Permissions-Policy: geolocation=()` response header blocks
the API outright — indistinguishable from a real user denial by error code
alone.

`geolocation=()` was already present, added during an earlier July 2026
security-hardening pass that predates this feature entirely (it locks down
camera/microphone/geolocation/USB/etc. site-wide by default). Nothing at
the time connected that change to a not-yet-built feature that would later
need geolocation, so the conflict went uncaught.

What made this a multi-round live debugging session rather than a
one-line fix: **this exact header turned out to be set independently in
three separate places**, all needing the same fix before the browser would
actually receive a policy allowing it.

## 1. `public/.htaccess` — the first, expected source

`Header always set Permissions-Policy "... geolocation=(), ..."` on line 14.
Changed to `geolocation=(self)`, matching the existing
`payment=(self "https://js.stripe.com")` allowlist syntax already used on
the same line. Committed, pushed, deployed — and the live header still
showed `geolocation=()`.

## 2. A stray, untracked `.htaccess` directly under `/var/www/invoice`

`curl -sI` against the live site kept showing **two** `Permissions-Policy`
header lines in a single response, one fixed and one not. `git ls-files`
confirms this repository only ever tracks `public/.htaccess` — nothing at
the repo root. The second copy turned out to be a leftover file sitting
directly at `/var/www/invoice/.htaccess` on the server, predating the
current `public/`-as-`DocumentRoot` layout, still holding the old
`geolocation=()` value. Since it isn't part of the repo, `git pull` could
never remove or update it — it had to be deleted manually on the server
(`rm /var/www/invoice/.htaccess`).

Along the way, the *live* Apache vhost config
(`/etc/apache2/conf.d/ssl.conf`, documented in
`docs/SSL_CONF_EXPLAINED.md`) turned out to have its own third copy of this
header too — a separate drift between what that doc describes and what
`public/.htaccess` actually enforces. Removed from `ssl.conf` as well
(`sed -i '/Header always set Permissions-Policy/d' ssl.conf`) so
`public/.htaccess` stays the single Apache-level source of truth.

**A red herring along the way**: after all of the above, the header was
still duplicated. `ps aux` showed the exact same Apache master PID across
several `rc-service apache2 restart` calls, each reported as successful —
the master process had never actually died and respawned, so none of the
file edits had taken effect yet. Fixed with a hard stop/kill/start instead
of trusting `restart`:
```bash
rc-service apache2 stop
pkill -9 httpd
rc-service apache2 start
```
Confirmed via fresh PIDs in `ps aux` before re-testing.

## 3. `config/web/params.php` — the real remaining source, application-level

Even with a confirmed-fresh Apache process and every `.htaccess`/vhost copy
fixed or removed, the live response *still* showed two `Permissions-Policy`
lines — one correct, one still the old value. `config/web/params.php`'s
`'security-headers'` array sends this same header from PHP itself,
deliberately independent of Apache — its own comment explains why:
*"Guaranteed here regardless of the web server in front of the app
(Apache-only headers vanish on another server, a header-stripping proxy, or
PHP's built-in dev server)"*. This file mirrors `public/.htaccess`'s
headers by design and is documented as needing to be kept in sync with it
— but had drifted, still holding `geolocation=()`. This is genuinely a
different code path from anything Apache-side, which is why deleting files
and restarting Apache — however thoroughly — could never have touched it.

Fixed the same way: `geolocation=()` → `geolocation=(self)`.

Once all three sources agreed, the live response correctly showed two
identical `Permissions-Policy` headers (still two, by design — Apache and
PHP each send their own copy as intentional redundancy) both reading
`geolocation=(self)`, and the Location tab's tester started working.

## Lesson for next time

A security header appearing "not to take effect" after an edit doesn't
necessarily mean the edit was wrong or the service didn't reload — grep
*every* layer before assuming either:
- The repo's own web-server config file (`public/.htaccess`).
- Anything untracked sitting on the actual server outside the repo
  (`find / -iname ".htaccess"`, or equivalent, is worth running early
  rather than late).
- The web server's own vhost/global config, separately from per-directory
  overrides.
- The application layer itself — this project deliberately mirrors its
  security headers between Apache and PHP (`config/web/params.php` and
  `public/.htaccess`), specifically so headers survive a change of web
  server. That same design means **any header value change here needs
  updating in both places**, or exactly this kind of silent drift recurs.
- Whether the service actually restarted — a stable PID across multiple
  "successful" restarts is worth checking directly, not assumed.

## Files touched

- `public/.htaccess` — `geolocation=()` → `geolocation=(self)`
- `config/web/params.php` — same change, in the `'security-headers'` array
- Server-only, not in this repo: deleted a stray `/var/www/invoice/.htaccess`;
  removed the duplicate `Permissions-Policy` line(s) from
  `/etc/apache2/conf.d/ssl.conf`

## Verification

- `curl -sI https://yii3i.online/setting/tabIndex | grep -i permissions-policy`
  — confirmed both header lines read `geolocation=(self)`.
- User confirmed the Location tab's "Test My Location" button works live.
- `vendor/bin/psalm --no-cache config/web/params.php` clean.
