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
tail -100 /var/log/apache2/error_log | grep "429\|rate\|limit"
```

**Check your route rate limiter configuration:**
```bash
grep -r "LRM\|RateLimiter\|Counter" /var/www/invoice/config/web/di/rate-limit.php
```

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
chown -R apache:apache /var/www/invoice/resources/rbac/
chown -R apache:apache /var/www/invoice/runtime/
chown -R apache:apache /var/www/invoice/public/assets/
echo "Ownership fixed — deploy complete."
```

Make it executable:
```bash
chmod +x /var/www/invoice/deploy.sh
```

Run after every `git pull`:
```bash
git pull origin main && ./deploy.sh
```

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
