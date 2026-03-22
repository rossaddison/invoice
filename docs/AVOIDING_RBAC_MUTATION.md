# Avoiding RBAC Mutation at Runtime

## What is RBAC Mutation?

RBAC (Role-Based Access Control) mutation refers to the practice of dynamically
modifying the role and permission hierarchy at runtime — for example, calling
`addChild` or `removeChild` on roles during a user's login session to
temporarily grant or revoke access. While this may seem like a convenient
shortcut, it introduces a category of bugs that are difficult to diagnose and
dangerous in production.

---

## The Core Principle

RBAC is designed to be a **static, configuration-time concern**. Roles and their
permission hierarchies should be defined once — either in flat PHP files or in
database tables — and remain stable for the lifetime of the application. They
should only change when an administrator deliberately reconfigures the system,
not as a side effect of a user logging in.

The question of *what a user is allowed to do* (RBAC) is fundamentally different
from the question of *whether a user has completed a login step* (session state).
Conflating these two concerns is the root cause of most RBAC mutation problems.

---

## A Real-World Example: TFA Gating Gone Wrong

Consider a two-factor authentication (TFA) flow where the developer wants to
prevent access to the main application until the user has verified their TOTP
code. A tempting approach is to use RBAC permissions as the gate:

```php
// On login — block access
private function tfaIsEnabledBlockBaseController(string $userId): void
{
    $roles = $this->manager->getRolesByUserId($userId);
    foreach ($roles as $role) {
        $this->manager->removeChild(
            $role->getName(), Permissions::ENTRY_TO_BASE_CONTROLLER);
        $this->manager->addChild(
            $role->getName(), Permissions::NO_ENTRY_TO_BASE_CONTROLLER);
    }
}

// After TFA passes — restore access
private function tfaNotEnabledUnblockBaseController(string $userId): void
{
    $roles = $this->manager->getRolesByUserId($userId);
    foreach ($roles as $role) {
        $this->manager->removeChild(
            $role->getName(), Permissions::NO_ENTRY_TO_BASE_CONTROLLER);
        $this->manager->addChild(
            $role->getName(), Permissions::ENTRY_TO_BASE_CONTROLLER);
    }
}
```

This approach has severe consequences, described in detail below.

---

## Why RBAC Mutation is Dangerous

### 1. File Permission Failures are Silent

When using a file-based RBAC backend such as Yii3's `PhpManager`, the
`addChild` and `removeChild` calls write to flat PHP files on disk. If the web
server process does not have write permission on those files, the calls succeed
in memory but **silently fail to persist**. The user's role hierarchy is never
actually updated on disk.

The result is that the next request — or any request after a process restart —
reads the original, unmodified file and behaves as if the mutation never
happened. This produces a 403 error with no exception, no log entry from the
RBAC layer, and no obvious cause.

**Diagnosis is extremely difficult** because the application code appears
correct, the logic appears to run, and the error only manifests after a redirect
— by which time the in-memory state has been lost.

```
# The silent failure scenario on Alpine Linux with Apache:
-rw-r--r-- 1 root root 236 Mar 21 07:41 /var/www/invoice/resources/rbac/assignments.php
#                ^^^^
# Apache runs as 'apache', not 'root'.
# addChild writes to memory. Disk write fails. No exception thrown.
# Next request reads the old file. User gets 403.
```

### 2. RBAC Cache Invalidation

Most RBAC implementations cache the permission hierarchy in memory for
performance. When `addChild` writes to disk, the in-memory cache may not be
immediately invalidated. This creates a race condition where:

- The disk file is updated correctly
- The cache still holds the old hierarchy
- The very next permission check reads from the stale cache
- The user gets a 403 despite the file being correct

This race is particularly acute when the mutation and the permission check are
separated by an HTTP redirect, because the redirect triggers a new request
cycle where the cache may be repopulated from the old state before the new
state is flushed.

### 3. Multi-User Contamination

RBAC roles are **shared across all users** of that role. When you call:

```php
$this->manager->removeChild('observer', Permissions::ENTRY_TO_BASE_CONTROLLER);
```

You are not removing the permission for one user. You are removing it for
**every user who has the observer role**. If two observer users are logged in
simultaneously:

- User A starts TFA → `ENTRY_TO_BASE_CONTROLLER` removed from `observer` role
- User B, already past TFA, makes a request → gets 403 because their shared
  role was just mutated by User A's login flow

This is a critical concurrency bug that is nearly impossible to reproduce in
development but emerges unpredictably in production.

### 4. Session Regeneration Ordering

A common security practice is to call `session->regenerateId()` after a
successful login to prevent session fixation attacks. If `session->set()` is
called **before** `regenerateId()`, the session data written may be associated
with the old session ID. Depending on the session backend, this data may be
lost or inaccessible under the new session ID.

The correct order is always:

```php
// ✅ Correct — regenerate first, then write session data
$this->session->regenerateId();
$this->session->set('tfa_verified', true);

// ❌ Wrong — data written to old session ID, lost after regeneration
$this->session->set('tfa_verified', true);
$this->session->regenerateId();
```

The result of wrong ordering is that the unblock call appears to succeed, the
session is regenerated, but the new session has no record of the permission
change. The user is redirected to a protected page and immediately receives
a 403.

### 5. Orphaned Users

If a new user is created via OAuth2 and the RBAC assignment fails silently (due
to file permissions or a database error), the user record exists in the user
table but has no entry in `assignments.php` or the assignments table. On their
next login attempt, `getRolesByUserId()` returns an empty array. The foreach
loop over roles does nothing. No permission is ever assigned. The user can never
log in, and there is no error message indicating why.

### 6. Session Storage in /tmp

If PHP's `session.save_path` is not explicitly configured, sessions default to
`/tmp` on Alpine Linux. This means:

- An Apache restart wipes all active sessions
- Users are silently logged out
- `tfa_verified` flags are lost
- The next request hits the base controller with no session data and returns 403

This is indistinguishable from an RBAC bug without careful log analysis.

```ini
# php.ini — always set explicitly
session.save_path = "/var/www/invoice/runtime/sessions"
session.gc_maxlifetime = 3600
session.gc_probability = 1
session.gc_divisor = 100
```

### 7. The sudo nano Trap

Every file edited with `sudo nano` is saved with `root` ownership, even if the
content is unchanged. On Alpine Linux with Apache, this silently breaks all
write operations for any file Apache needs to write to:

```
# Before sudo nano
-rw-rw-r-- 1 apache apache  assignments.php

# After sudo nano — Apache can no longer write
-rw-r--r-- 1 root   root    assignments.php
```

The result is that RBAC assignments, session files, and log entries all fail
silently. The application appears to work but produces 403 errors that are
extremely difficult to trace.

**Rule: never use `sudo nano` for application files.** Only use `sudo` for
system files like `/etc/php84/php.ini` and `/etc/apache2/httpd.conf`. If you
must edit an application file with sudo, immediately fix ownership:

```bash
sudo chown apache:apache /path/to/file
```

---

## TFA and OAuth2 Should Not Be Combined

OAuth2 providers (Google, GitHub, Microsoft, LinkedIn, Facebook etc.) enforce
their own MFA before issuing an authorization code. By the time any callback
fires, the user has already passed the provider's own security checks. Applying
an additional TOTP challenge is redundant and should be skipped entirely for
all OAuth2 logins.

```php
// ✅ OAuth2 callback — skip TFA, grant full access immediately
public function tfaCheckBeforeRedirects(
    string $providerName,
    TokenRepository $tR,
    UserInvRepository $uiR,
): ResponseInterface {
    $identity = $this->authService->getIdentity();
    $userId = $identity->getId();
    if (null !== $userId) {
        $userInv = $uiR->repoUserInvUserIdquery($userId);
        if (null !== $userInv) {
            $status = $userInv->getActive();
            $isAdminUser = $this->isAdminUser($userId);
            if ($status || $isAdminUser) {
                $isAdminUser ? $this->disableToken($tR, $userId,
                        $providerName) : '';
                $this->session->regenerateId();
                $this->session->set('tfa_verified', true);
                return $this->redirectToInvoiceIndex();
            }
            $this->disableToken($tR, $userId,
                    $this->getTokenType($providerName));
            return $this->redirectToAdminMustMakeActive();
        }
    }
    return $this->redirectToMain();
}
```

TFA is only applied to the local username/password login path where the
provider cannot be trusted to have enforced MFA independently.

---

## The Correct Approach: Session Flags for Transient State

Any gate that needs to open and close during a single login session is **not an
RBAC concern**. It is a session state concern. Use a session boolean:

```php
// Local login — TFA required
$this->session->set('tfa_verified', false);

// After successful TOTP verification
$this->session->regenerateId();
$this->session->set('tfa_verified', true);

// In BaseController — check session, not RBAC
protected function initializeViewRenderer(): void
{
    if (!$this->userService->hasPermission(Permissions::VIEW_INV)
            && !$this->userService->hasPermission(Permissions::EDIT_INV)) {
        // no access layout
    } elseif ($this->userService->hasPermission(Permissions::VIEW_INV)
        && !$this->userService->hasPermission(Permissions::EDIT_INV)
        && $this->session->get('tfa_verified') === true) {
        // guest layout
    } elseif ($this->userService->hasPermission(Permissions::EDIT_INV)
        && $this->session->get('tfa_verified') === true) {
        // full invoice layout
    }
}
```

This approach has none of the failure modes described above:

- No file writes during login
- No cache invalidation needed
- No shared state between users — each session is isolated
- Session regeneration ordering is explicit and controlled
- Logout calls `session->clear()` which automatically removes the flag

---

## The Correct Approach: Static RBAC Assignments

RBAC assignments should be made **once**, at user creation time, and never
modified as part of the login flow:

```php
// At user creation — assign role once and verify it persisted
private function assignRoleAndVerify(
    string $userId,
    string $role,
): bool {
    $this->manager->revokeAll($userId);
    $this->manager->assign($role, $userId);

    $roles = $this->manager->getRolesByUserId($userId);
    if (empty($roles)) {
        $this->logger->log(
            LogLevel::ERROR,
            'RBAC assignment failed to persist for userId: ' . $userId
                . ' role: ' . $role
                . ' — check file ownership of resources/rbac/assignments.php'
        );
        return false;
    }
    return true;
}
```

The role hierarchy in `items.php` defines permissions statically and is never
mutated at runtime:

```php
'observer' => [
    'children' => [
        'view.inv',
        'view.payment',
        'edit.user.inv',
        'edit.client.peppol',
        'entry.to.base.controller', // static — never removed at runtime
    ],
],
'admin' => [
    'children' => [
        'view.inv',
        'edit.inv',
        'view.payment',
        'edit.payment',
        'edit.user.inv',
        'edit.client.peppol',
        'entry.to.base.controller', // static — never removed at runtime
    ],
],
```

---

## Separation of Concerns Summary

| Concern | Correct Tool | Incorrect Tool |
|---|---|---|
| What can this role do? | RBAC — static items.php or DB | Session variables |
| Has this user completed TFA? | Session flag | RBAC addChild/removeChild |
| Is this user logged in? | Authentication middleware | RBAC permissions |
| Has this user verified email? | Token table + session | RBAC mutation |
| Is this user an admin? | RBAC role check | Session variable |
| Has OAuth2 provider verified MFA? | Skip TFA entirely | RBAC block/unblock |

---

## Deployment Checklist

File-based RBAC backends require correct file ownership on every deployment.
A `git pull` run as root will reset ownership and silently break all RBAC
writes. Add this to your deploy script:

```bash
#!/bin/sh
# Alpine Linux with Apache
chown -R apache:apache /var/www/invoice/resources/rbac/
chown -R apache:apache /var/www/invoice/runtime/
chown -R apache:apache /var/www/invoice/public/assets/
```

Verify after every deployment:

```bash
ls -la /var/www/invoice/resources/rbac/
ls -la /var/www/invoice/runtime/logs/
ls -la /var/www/invoice/runtime/sessions/
# All should show apache:apache, not root:root
```

Find all root-owned files in one command:

```bash
find /var/www/invoice -user root -not -path "*/vendor/*" -ls
```

---

## Things Still To Do

- Remove all temporary `LogLevel::INFO` debug lines added during diagnosis
- Remove `LoggerInterface` from `BaseController` if not needed permanently
- Switch from `PhpManager` to `DbManager` to eliminate file permission
  fragility permanently — assignments stored in the database cannot be broken
  by file ownership issues
- Raise the `getCurrentUserJsonArray` bug in `rossaddison/yii-auth-client`
  for new Facebook users — `array_key_last($params)` returns the last key
  not the last value, causing `$facebookId` to always be `0` for new users

---

## Conclusion

RBAC mutation at runtime is an antipattern that produces silent failures, cache
race conditions, multi-user contamination, and orphaned accounts. Combined with
file permission issues from `sudo nano`, session storage in `/tmp`, and
incorrect `regenerateId()` ordering, these problems compound into 403 errors
that are extremely difficult to diagnose.

The fix is a clean separation of concerns: RBAC defines static role
capabilities, session flags manage transient login state, OAuth2 providers
handle their own MFA, and file ownership is maintained correctly on every
deployment. None of these should substitute for the other.
