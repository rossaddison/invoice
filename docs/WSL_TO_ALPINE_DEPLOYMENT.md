# WSL to Alpine Running on Apache2
## Updating with Latest Changes Seen on Github Repo

**1. Right click on windows 11 icon 🪟 … Run … wsl**

**2. Connect to Alpine:**
```bash
ssh root@ipaddress
# or
ssh root@yourdomain
```

**3. Enter your Alpine password:**
```
(copy... right click ... enter)
```

**4. Get into root directory:**
```bash
cd ..
dir
cd var/www/invoice
```

**5. Verify git is installed before using it:**
```bash
git --version
```

**6. Upgrade git:**
```bash
apk update && apk upgrade git
```

**7. Check for any local changes that you have made on the website before pulling:**
```bash
git status
```

**8. Always stash your changes depending on git status:**
```bash
git stash
# Restore with:
git stash pop
```

**9. Or necessary to override your changes:**
```bash
git checkout -- .
# (dash dash space fullstop)
```

**10. Restoring a specific file from the stash:**
```bash
git checkout stash@{0} -- .env
```

**11. Pull from the repository:**
```bash
git pull origin main
```

**12a. Accessing the Alpine MySQL/MariaDB database:**
```bash
mysql -u root -p
```

**12b.** Advisable to make sure the phpMyAdmin endpoint is not visible and that
phpMyAdmin is not used even with ipaddress restrictions and aliasing.

**13. Listing Multiple Stashes:**
```bash
git stash list
```

**14. Getting out of the stash list whilst viewing it:**
```
Press q
```

**15. See what is in your stash without applying it:**
```bash
git stash show -p
```

**16. Giving ownership to apache user:**
```bash
chown -R apache:apache /var/www/invoice/
chown -R apache:apache /var/www/invoice/resources/rbac/assignments.php
chown -R apache:apache /var/www/invoice/resources/rbac/items.php
chmod -R 755 /var/www/invoice/
chmod -R 775 /var/www/invoice/resources/
chmod -R 775 /var/www/invoice/runtime/
chmod -R 775 /var/www/invoice/public/assets/
```

**17.** Ensure that `resources/rbac/items.php` has `entry.to.base.controller`
permanently listed as a child of every role that requires access to the
application — `admin`, `observer`, and `accountant`. This permission must
**never** be added or removed at runtime. It is a static assignment that
should always be present:

```php
'admin' => [
    'children' => [
        'view.inv',
        'edit.inv',
        'view.payment',
        'edit.payment',
        'edit.user.inv',
        'edit.client.peppol',
        'entry.to.base.controller', // must always be present — never remove at runtime
    ],
],
'observer' => [
    'children' => [
        'view.inv',
        'view.payment',
        'edit.user.inv',
        'edit.client.peppol',
        'entry.to.base.controller', // must always be present — never remove at runtime
    ],
],
'accountant' => [
    'children' => [
        'view.inv',
        'view.payment',
        'edit.payment',
        'entry.to.base.controller', // must always be present — never remove at runtime
    ],
],
```

TFA gating is now handled by `session->set('tfa_verified', true/false)` and
not by mutating the RBAC hierarchy. See `AVOIDING_RBAC_MUTATION.md` for full
details.

**18. Install telnet to check that port 465 is open for smtps:**
```bash
# On Alpine:
apk add busybox-extras

# On Ubuntu:
sudo apt install telnet -y

# Checking e.g.:
telnet smtp.gmail.com 465
```

If `Connection closed by foreign host` is seen, this is normal — Gmail's SMTP
server closed the plain telnet connection because it expects an SSL handshake,
not a raw telnet connection.

**19. Ensure that all log files are initially deleted:**
```bash
rm runtime/logs/*.log
```

Yiisoft will rebuild them automatically. Viewing the logfile in real time:
```bash
# Note: do NOT use sudo nano — use cat or tail to avoid root ownership issues
tail -f /var/www/invoice/runtime/logs/app.log

# View last 50 lines
tail -50 /var/www/invoice/runtime/logs/app.log

# View last 100 lines
tail -100 /var/www/invoice/runtime/logs/app.log
```

**20.** Settings → General → Stop Signing Up → No

Use [guerrillamail.com](https://guerrillamail.com) to receive the test email
addresses that are used in the signup process, in order for the recipient to
confirm their details i.e. clicking on the confirmation link — and therefore
confirm that an email can be sent through port 465 using the latest more secure
symfony-mailer.

**21. Output the first 30 lines of your mailer settings:**
```bash
grep -A 30 "yiisoft/mailer-symfony" /var/www/invoice/config/common/params.php
```

**22. Terminate your locally run WSL if you are using it so it does not conflict with WampServer:**
```bash
wsl --terminate Ubuntu
# or
wsl --terminate Alpine
```

**23. Update ssl.conf on Apache2:**
```bash
sudo nano /etc/apache2/conf.d/ssl.conf
```

Test and restart Apache2:
```bash
httpd -t && rc-service apache2 restart
```

**24. Finding where phpMyAdmin is installed:**
```bash
find / -name "index.php" -path "*/phpmyadmin/*" 2>/dev/null
```

---

## Debugging 403 Errors

**Check Apache error log:**
```bash
tail -50 /var/log/apache2/error_log
# or
tail -50 /var/log/apache2/error.log
```

**Check which process Apache is running as:**
```bash
ps aux | grep apache2 | grep -v root | head -1
```

**Find all files owned by root that Apache cannot write to:**
```bash
find /var/www/invoice -user root -not -path "*/vendor/*" -ls
```

**Fix all root-owned files in one command:**
```bash
sudo chown -R apache:apache /var/www/invoice/
```

**Check file ownership on critical RBAC files:**
```bash
ls -la /var/www/invoice/resources/rbac/
ls -la /var/www/invoice/runtime/logs/
ls -la /var/www/invoice/runtime/sessions/
```

**Check RBAC assignments for a specific user:**
```bash
cat /var/www/invoice/resources/rbac/assignments.php
```

**Check RBAC items and role hierarchy:**
```bash
cat /var/www/invoice/resources/rbac/items.php
```

---

## Session Configuration

**Check where PHP is currently storing sessions:**
```bash
php -r "echo ini_get('session.save_path');"
```

**Check the current session.save_path setting in php.ini:**
```bash
grep "session.save_path" /etc/php84/php.ini
```

**Set a persistent session path in php.ini to survive Apache restarts:**
```ini
session.save_path = "/var/www/invoice/runtime/sessions"
session.gc_maxlifetime = 3600
session.gc_probability = 1
session.gc_divisor = 100
session.cookie_lifetime = 0
```

**Create the sessions directory with correct ownership:**
```bash
mkdir -p /var/www/invoice/runtime/sessions
sudo chown -R apache:apache /var/www/invoice/runtime/sessions
sudo chmod 750 /var/www/invoice/runtime/sessions
```

**Verify sessions are being written after login:**
```bash
ls -la /var/www/invoice/runtime/sessions/
# Should show sess_ prefixed files
```

---

## Psalm Static Analysis

**Check Psalm version:**
```bash
cd /var/www/invoice
php vendor/bin/psalm --version
```

**Run Psalm with fresh scan (no cache):**
```bash
php vendor/bin/psalm --no-cache
```

**Run Psalm and save output to file for review:**
```bash
php vendor/bin/psalm --no-cache > /var/www/invoice/runtime/psalm-output.txt 2>&1
cat /var/www/invoice/runtime/psalm-output.txt
```

**Run Psalm with full details:**
```bash
php vendor/bin/psalm --no-cache --show-info=true --stats
```

**Clear Psalm cache:**
```bash
php vendor/bin/psalm --clear-cache
```

**Run Psalm against specific files only:**
```bash
php vendor/bin/psalm src/Auth/Controller/AuthController.php
php vendor/bin/psalm src/Auth/Trait/Callback.php
php vendor/bin/psalm src/Invoice/BaseController.php
```

---

## Rate Limiter Diagnosis

**Check if the rate limiter is causing 403s by checking Apache log for patterns:**
```bash
# This server's actual log files (confirmed July 2026 — access.log and
# yii3i_access.log are both live; access2.log and yii3i.eu.org-*.log are
# stale leftovers from a domain that no longer resolves):
tail -100 /var/log/apache2/access.log /var/log/apache2/yii3i_access.log | grep "429\|rate\|limit"
```

**Check your route rate limiter configuration:**
```bash
grep -r "LRM\|RateLimiter\|Counter" /var/www/invoice/config/web/di/rate-limit.php
```

**Find out which log file is actually being written to right now** (this
box has several `.log` files under `/var/log/apache2/`, not all of them
current — go by modification time, not by which name sounds right):
```bash
ls -la --time-style=full-iso /var/log/apache2/*.log
```

**See which IPs are actually hitting a route the most** (useful before
deciding a flood is really happening — a full-history `grep` across a
40–50MB log file can look alarming purely because it spans weeks, not
because there's an active burst right now; prefer `tail -n <N>` first to
look at *recent* traffic only):
```bash
tail -n 300 /var/log/apache2/*.log 2>/dev/null | grep '/login' | tail -50
grep -h '/login' /var/log/apache2/*.log 2>/dev/null | awk '{print $1}' | sort | uniq -c | sort -rn | head -15
```

---

## fail2ban: Auto-Banning Floods on Auth Routes

This app's own rate-limiter middleware and Cloudflare Turnstile both help,
but neither stops a flood of requests from reaching the origin server in
the first place — Turnstile only runs once a request is already inside
`AuthController::login()`, well after the rate limiter has already counted
it. This server has **no Cloudflare in front of it** (confirmed July 2026
— `yii3i.online` uses Vultr's own nameservers and resolves straight to the
origin IP), so there's no edge layer available either; blocking has to
happen on the box itself. `fail2ban` fills that gap: it watches the Apache
access log and bans an IP at the `iptables` level once it crosses a
threshold.

**Install** (`iptables`/`ip6tables` aren't installed by default on this
Alpine image):
```bash
apk add fail2ban iptables ip6tables
```

**Filter** — `/etc/fail2ban/filter.d/yii3i-login.conf`:
```bash
cat > /etc/fail2ban/filter.d/yii3i-login.conf << 'EOF'
[Definition]
failregex = ^<HOST> \S+ \S+ \[.*\] "(GET|POST) /login(\?\S*)? HTTP/\d\.\d" \d{3}
ignoreregex =
EOF
```

**Jail** — `/etc/fail2ban/jail.d/yii3i-login.conf` (adjust `logpath` to
whatever `ls -la --time-style=full-iso /var/log/apache2/*.log` shows as
actually current — this box currently writes to both of the two listed):
```bash
cat > /etc/fail2ban/jail.d/yii3i-login.conf << 'EOF'
[yii3i-login]
enabled  = true
filter   = yii3i-login
logpath  = /var/log/apache2/access.log
           /var/log/apache2/yii3i_access.log
port     = http,https
protocol = tcp
maxretry = 10
findtime = 60
bantime  = 3600
action   = iptables-multiport[name=yii3i-login, port="http,https", protocol=tcp]
EOF
```
Bans an IP for 1 hour once it hits `/login` 10+ times within 60 seconds —
well above any real user's retry pattern.

**Enable and start** (must be a `restart`, not just `start`, if fail2ban
was already running before the jail file existed — it won't pick up new
jail files otherwise):
```bash
rc-update add fail2ban
rc-service fail2ban restart
```

**Verify**:
```bash
fail2ban-client status yii3i-login
iptables -L -n | grep -i fail2ban
```
`Currently failed`/`Total failed` will read 0 until a real burst happens —
fail2ban only watches new log lines from the moment it starts, it doesn't
scan history retroactively. To test the filter regex actually matches this
server's real log format without waiting for a live flood:
```bash
fail2ban-regex /var/log/apache2/access.log /etc/fail2ban/filter.d/yii3i-login.conf
```

**Watch it catch something over time**:
```bash
tail -f /var/log/fail2ban.log
```

### mod_evasive is not available on Alpine

Investigated as a companion to fail2ban (reacts inside Apache itself,
faster than fail2ban's log-tail approach) — it isn't packaged for Alpine
at all:
```bash
apk search -v evasive        # nothing
apk search -v apache2-mod    # no evasive-related package, even with
                              # main + community + edge/testing all enabled
```
The only way to get it would be compiling from source via `apxs`
(`apache2-dev`, a C toolchain, and the module's source downloaded
directly). Decided against it: an unaudited, hand-compiled C module
running inside the Apache process is a real step up in risk (a
memory-safety bug in it is a whole-server problem), it won't receive
security updates through `apk upgrade` since it isn't a tracked package,
and it'd need manual recompiling after every future Apache/PHP version
bump. fail2ban alone, on top of the existing app-layer rate limiter and
Turnstile, was judged sufficient — three independent layers (app
rate-limit → Turnstile → fail2ban) without adding unaudited compiled code
to the web server process.

---

## OAuth2 Debugging

**Check your OAuth2 callback routes are not behind auth middleware:**
```bash
grep -A 3 "callbackGoogle\|callbackFacebook\|callbackGithub" \
    /var/www/invoice/config/common/routes.php
```

**Check your .env OAuth2 credentials are set:**
```bash
grep -i "google\|facebook\|github\|microsoft\|linkedin" /var/www/invoice/.env
```

**Confirm a user's RBAC assignment exists:**
```bash
cat /var/www/invoice/resources/rbac/assignments.php
```

**Check all users missing a userinv record in MySQL,
or go to Settings → Invoice User Account:**
```sql
SELECT u.id, u.login 
FROM user u 
LEFT JOIN userinv ui ON u.id = ui.user_id 
WHERE ui.user_id IS NULL;
```

**Check a specific OAuth2 user's active status in MySQL,
or go to Settings → Invoice User Account:**
```sql
SELECT u.id, u.login, ui.active, ui.user_id 
FROM user u 
LEFT JOIN userinv ui ON u.id = ui.user_id 
WHERE u.login LIKE 'google%'
   OR u.login LIKE 'facebook%'
   OR u.login LIKE 'github%';
```

**Activate a user manually if admin forgot to click proceed button,
or go to Settings → Invoice User Account:**
```sql
UPDATE userinv SET active = 1 WHERE user_id = ???;
```

---

## Copying Files from Server to Windows (SCP)

**Run from PowerShell on your Windows machine — NOT on the server:**
```powershell
# Copy a single file
scp root@yourdomain:/var/www/invoice/src/Auth/Controller/AuthController.php C:\y\

# Copy multiple files
scp root@yourdomain:/var/www/invoice/src/Auth/Controller/AuthController.php `
    root@yourdomain:/var/www/invoice/src/Auth/Trait/Callback.php `
    root@yourdomain:/var/www/invoice/src/Invoice/BaseController.php `
    C:\y\

# Copy an entire directory
scp -r root@yourdomain:/var/www/invoice/src/Auth/ C:\y\Auth\
```

Note: backtick `` ` `` is the line continuation character in PowerShell.

**Easier alternative — push from server to GitHub, then pull in GitHub Desktop:**

> ⚠️ **Before pushing to GitHub, always run Psalm first to catch any type
> errors or issues introduced by your changes:**
> ```bash
> cd /var/www/invoice
> php vendor/bin/psalm --no-cache > /var/www/invoice/runtime/psalm-output.txt 2>&1
> cat /var/www/invoice/runtime/psalm-output.txt
> ```
> Only proceed with the push if Psalm reports no errors.

```bash
# On the server — only after Psalm passes cleanly
cd /var/www/invoice
git add src/Auth/Controller/AuthController.php
git add src/Auth/Trait/Callback.php
git add src/Invoice/BaseController.php
git add resources/rbac/items.php
git commit -m "Fix RBAC mutation and session TFA flag"
git push origin main
```

Then in GitHub Desktop: **Fetch origin** → **Pull origin**.

---

## Deploy Script
## Run after every git pull to prevent root ownership breaking Apache

Create a file `/var/www/invoice/deploy.sh`:

```bash
#!/bin/sh
composer install
chown -R apache:apache /var/www/invoice/resources/rbac/
chown -R apache:apache /var/www/invoice/runtime/
chown -R apache:apache /var/www/invoice/public/assets/
echo "Dependencies installed, ownership fixed — deploy complete."
```

`composer install` reads `composer.lock` (already pulled) and installs
exactly what's pinned there — safe and fast to run on every deploy even
when no dependency changed (it just reports "Nothing to install, update or
remove" and exits). Skipping it after a deploy that *did* add a package —
like the `yiisoft/cache-apcu` addition in July 2026 — leaves `vendor/`
out of sync with `composer.lock`, and the app fatals on the first missing
autoloaded class. No `--no-dev` flag here deliberately: this doc's own
Psalm section above runs `php vendor/bin/psalm` directly on this server,
which needs the dev dependencies present.

Make it executable:
```bash
chmod +x /var/www/invoice/deploy.sh
```

Run after every `git pull`:
```bash
git pull origin main && ./deploy.sh
```

**If this deploy added, changed, or removed a route**, also restart Apache:
```bash
rc-service apache2 restart
```
`git pull` can never clear the route-dispatch cache on its own — see
`docs/YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md` for the full mechanism.
Since the July 2026 switch to APCu-backed caching, `php yii cache/clear`
alone is **not** sufficient for this specific case (it only reaches the
CLI's own APCu pool, not the web server's) — restarting Apache is what
actually clears it. Still run `php yii cache/clear` too for the general
DI/config cache housekeeping; just don't rely on it for a route change.

**If a deployed CSS/JS/image change doesn't seem to take effect**, clear
the published assets cache:
```bash
rm -rf /var/www/invoice/public/assets/*
```
`Yiisoft\Assets\AssetManager` publishes each asset bundle into a
content-hashed subdirectory under `public/assets/` (e.g.
`public/assets/194851c0/`) rather than serving straight from
`src/Invoice/Asset/`. There's no console command or admin UI button for
this despite a stale comment in `resources/views/layout/invoice.php`
implying one exists — it's a manual filesystem clear. Apache/PHP
republishes everything fresh on the very next request, no restart needed.
`deploy.sh` already `chown`s this directory for the `apache` user, so this
is safe to run before or after it.

---

## Important Reminders

- **Never use `sudo nano` for application files** — it transfers ownership to
  `root` and silently breaks Apache write access. Every file saved with
  `sudo nano` will be owned by root, causing silent failures for RBAC writes,
  session writes, and log writes. Use `tail` or `cat` to view files, and
  `nano` without `sudo` to edit application files. Only use `sudo nano` for
  system files like `/etc/php84/php.ini` and `/etc/apache2/httpd.conf`.
- **Always run `chown -R apache:apache`** after any `git pull` to ensure
  Apache retains write access to runtime, rbac and assets directories.
- **Session save path** must be set explicitly in `php.ini` — if left as
  default on Alpine, sessions are stored in `/tmp` and lost on every Apache
  restart, causing mysterious 403 errors that look like RBAC failures.
- **`session->regenerateId()` must always be called BEFORE
  `session->set()`** — writing session data before regenerating the ID can
  cause the data to be lost under the new session ID.
- **RBAC must never be mutated at runtime** — use session flags for transient
  login state such as TFA verification. See `AVOIDING_RBAC_MUTATION.md` for
  full details.
- **OAuth2 providers handle their own MFA** — do not apply TOTP TFA checks
  to OAuth2 login callbacks. TFA should only apply to local
  username/password logins.
- **Always verify RBAC assignments persisted** after calling `assign()` by
  immediately checking `getRolesByUserId()` — silent file permission failures
  will leave users with no role and a permanent 403 on every login.
