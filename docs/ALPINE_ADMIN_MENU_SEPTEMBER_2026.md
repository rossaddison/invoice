# Interactive Alpine Admin Menu

**Branch:** `docs/readme-alpine-menu-link-fix`
**Date:** September 2026

## Purpose

`bin/alpine-menu.sh` is an interactive, numbered menu that runs directly at
the Alpine command prompt (once already SSH'd into the production box) so
day-to-day deployment/maintenance tasks don't require re-reading and
retyping commands from the deployment runbook,
[`resources/views/invoice/info/wsl_to_alpine.php`](../resources/views/invoice/info/wsl_to_alpine.php),
each time.

It came directly out of user feedback while working through that runbook
live: "I have been battling to setup a menu that will run under alpine" /
"I would like a menu that corresponds to the
resources/views/invoice/info/wsl_to_alpine.php steps and runs at the
alpine prompt". A follow-up pass on the runbook page itself
(the same session, "Not a very user friendly document" / "dont know where
to start") reorganized it into real numbered sections with a table of
contents, matching this menu's own numbering where the two overlap.

## Running it

```sh
ssh root@yii3i.online
cd /var/www/invoice
git pull origin main
sh bin/alpine-menu.sh
```

`git pull origin main` first is required the first time — the script has
to actually be present on the box before it can run. (Confirmed live: the
very first attempt to run it failed with the script not found, exactly
because that box hadn't pulled yet.)

## What it covers

A flat numbered menu (4 through 28, matching the runbook's own step
numbers so the two are easy to cross-reference):

- `git status` / `git stash` / discard local changes / restore `.env` from
  stash / `git pull origin main` / `git stash list` / `git stash show -p`
- Verifying `.env` and `resources/rbac/items.php` exist after a pull, and
  assigning the default admin/observer RBAC roles if missing
- Opening an interactive `mysql` shell, and finding any phpMyAdmin install
  on disk (there shouldn't be one exposed)
- Fixing file ownership/permissions (`chown -R apache:apache`, `chmod -R`)
- Installing telnet and checking SMTP port 465 is reachable
- Clearing `runtime/logs/*.log`
- Showing the current mailer settings (`grep` on `params.php`)
- Testing the Apache config and restarting it
- Checking/restarting MariaDB
- Clearing the Yii3 route cache (`runtime/cache/*`) — needed after every
  pull that adds/changes a route, otherwise the stale cache keeps serving
  404s for new routes
- Backing up the database (`mysqldump` piped through `gzip`)
- Updating Node.js via `apk` (Alpine is musl libc, not glibc — `nvm`'s
  precompiled builds don't work here)

## Design notes

- **POSIX `/bin/sh` only** — no bashisms (no arrays, no `[[`, no `local`,
  no `function` keyword). Alpine's default shell is BusyBox `ash`, not
  `bash`, and the goal was zero extra packages to run it.
- **Destructive actions confirm first** — discarding local changes,
  deleting logs, clearing caches, `chown -R`, restarting services all
  prompt `[y/N]` before doing anything.
- **No baked-in passwords** — `mysql`/`mysqldump` are left interactive
  (`-p` with no inline value) rather than ever embedding a credential in
  the script, in a menu item's output, or in shell history.
- Deliberately a **separate file** from `wsl_to_alpine.php`, not generated
  from it — one renders inside the app as an in-app help page, the other
  has to run as a shell script. Kept in sync by hand, matching step
  numbers.

## Verification

`sh -n` and `bash -n` both pass. No `shellcheck` available in the
development environment to check further, but the script avoids anything
`ash` is known not to support. **Live-confirmed working** on the real
Alpine production box after a `git pull origin main` (the one prerequisite
step, documented above, that the first live attempt caught).
