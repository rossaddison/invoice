# Updating PHP 8.4 on Alpine Linux

Companion to [PHP 8.4 Alpine Linux Setup Guide](PHP84_ALPINE_SETUP.md), which
covers the initial install. This doc covers keeping an already-installed
`php84` up to date with the latest upstream patch release.

---

## Check what upstream is currently on

PHP.net publishes monthly patch releases for actively-maintained branches.
PHP 8.4 receives active bug-fix and security updates until **2026-12-31**.

Check the current latest release before updating:

- <https://www.php.net/releases/index.php>
- <https://php.watch/versions/8.4/releases>

## Update the Alpine package

```bash
# 1. Refresh the package index
apk update

# 2. See what's currently installed vs what's available in your Alpine branch
apk policy php84

# 3. Upgrade only the php84* packages (safer than a full 'apk upgrade' on a live box)
apk upgrade $(apk info | grep '^php84')

# 4. Confirm the CLI version
php -v
```

## Restart the web server

Apache/mod_php needs a restart before it picks up the new binary — the CLI
`php -v` updates immediately, but the Apache worker process does not.

```bash
# mod_php (bare-metal Apache2 setup, per PHP84_ALPINE_SETUP.md)
rc-service apache2 restart

# php-fpm (e.g. docker/dev/php/Dockerfile), instead of the above
rc-service php-fpm84 restart
```

Confirm the web-facing version actually changed (look for `PHP/8.4.x` in the
startup line):

```bash
tail -5 /var/log/apache2/error.log
```

## Caveats

### Alpine's package version can lag upstream

`apk upgrade` only gets you as high as whatever build Alpine's package
maintainers have shipped for your Alpine release branch (`v3.2x`
main/community, or `edge`) — that can trail the latest php.net release by a
version or two. Check what you'd actually land on before upgrading:

```bash
apk policy php84 | grep -A2 'lib/apk/db/installed'
```

If the branch hasn't synced to the latest upstream patch yet, your options
are: wait for the next Alpine package sync, switch that one package to
`edge` (not recommended on a production box — see
[Why Apache?](WHY_APACHE.md) and the general stability guidance elsewhere in
this repo), or build from source (generally overkill for a patch bump).

### Stale `.apk-new` config files

`apk upgrade` writes changed config files as `*.apk-new` instead of silently
overwriting local edits. This is the same mechanism documented in
[PHP84_ALPINE_SETUP.md](PHP84_ALPINE_SETUP.md)'s troubleshooting section for
stale PHP 8.3 `.ini` files left over from the 8.3→8.4 migration — check for
leftovers after every upgrade:

```bash
find /etc/php84 /etc/apache2/conf.d -name '*.apk-new'
```

Diff each one against the live file and merge manually if Alpine shipped a
config change alongside the patch.
