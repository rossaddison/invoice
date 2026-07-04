# RBAC Bridge Table — `user_rbac_link`

## Purpose

The bridge table exists to answer one question cheaply: **does this `userinv` user
currently have an RBAC role assigned?**

`yiisoft/rbac-cycle-db` stores assignments in `yii_rbac_assignment.user_id` as a
`VARCHAR` string keyed to the Yii identity system. The application's `userinv` table
works with integer user IDs. Without the bridge, every page that needs to show role
status (e.g. the ⚠️ icon in the user list, the role badge in the nav bar) would have to
join or query `yii_rbac_assignment` on every request. The bridge provides an O(1) lookup
map (`findLinkedUserIdMap()`) that is built once per request and reused across all
rendering that needs it.

A row in `user_rbac_link` means: *this `userinv` user has at least one active RBAC
assignment and is therefore a recognised participant in the system's permission model.*
The bridge does not store which role — that remains in `yii_rbac_assignment`. It stores
only the existence of the link, so views can cheaply decide whether to render the
"assign role" button or the role badge.

## Problem

`yiisoft/rbac-cycle-db` stores RBAC assignments with the user identity as a `VARCHAR`
string (`yii_rbac_assignment.user_id`). The application's `userinv` table uses an
integer primary key (`user_id INT`). There was no persistent link between the two,
which made it impossible to look up a user's RBAC state from the `userinv` side without
a full scan of the assignments table on every request.

## Solution — `UserRbacLink` bridge entity

A dedicated bridge table `user_rbac_link` with a Cycle ORM entity at
`src/Infrastructure/Persistence/UserRbacLink/UserRbacLink.php`.

### Schema

| Column | Type | Notes |
|--------|------|-------|
| `id` | `INT AUTO_INCREMENT` | Standard PK via `#[Column(type: 'primary')]` |
| `user_inv_id` | `INT NOT NULL` | FK → `userinv.id` (PK) via Cycle ORM `BelongsTo` |
| `rbac_user_id` | `VARCHAR(126) NOT NULL` | mirrors `yii_rbac_assignment.user_id` |

### Entity design

`BelongsTo` uses no explicit `innerKey` or `outerKey`. Cycle ORM derives the FK column
name from the target entity's role (`userInv` → `user_inv_id`) and defaults the
`outerKey` to `userinv.id` (PK). This avoids `SyncTables` creating a second UNIQUE index
on a non-PK column, which was the root cause of the Alpine deployment crash.

Earlier attempts used `outerKey: 'user_id'` (a non-PK column on `userinv`). Because
`UserInv` already declares `#[Index(columns: ['user_id'], unique: true)]`, Cycle ORM
tried to add a duplicate UNIQUE index with a different generated name — which failed when
the target table contained duplicate `user_id` values (caused by data inserted before the
constraint was enforced).

```php
#[Entity(repository: UserRbacLinkRepository::class)]
class UserRbacLink
{
    use RequireId;

    #[BelongsTo(target: UserInv::class, nullable: false)]
    private ?UserInv $user_inv = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer', nullable: false)]
        private ?int $user_inv_id = null,
        #[Column(type: 'string(126)', nullable: false)]
        private ?string $rbac_user_id = null,
    ) {}

    public function getUserId(): ?int
    {
        return $this->user_inv?->reqUserId();  // traverses relation → userinv.user_id
    }
}
```

### Repository — `upsert()`

`upsert` requires both the `userinv.id` PK and the auth user ID separately:

```php
public function upsert(int $user_inv_id, int $userId): void
{
    $this->deleteByUserId($userId);
    $this->save(new UserRbacLink(user_inv_id: $user_inv_id, rbac_user_id: (string) $userId));
}
```

Callers look up the `UserInv` record first to obtain `user_inv_id`:

```php
$userInv = $uiR->repoUserInvUserIdquery($userId);
if ($userInv !== null) {
    $urlR->upsert($userInv->reqId(), $userId);
}
```

Files updated: `Callback.php`, `SignupController.php`, `UserInvController.php`
(three action methods + `assignSignupRole`).

## Automatic population — `syncIfEmpty()` via `InvoiceController`

`InvoiceController::index()` calls `syncIfEmpty()` on every request. The method
short-circuits immediately if any bridge rows already exist (`count() > 0`), so the
cost in steady state is a single `COUNT(*)` query:

```php
public function syncIfEmpty(AssignmentsStorageInterface $storage, UserInvRepository $uiR): void
{
    if ($this->count() > 0) {
        return;
    }
    foreach (array_keys($storage->getAll()) as $rbacUserId) {
        $userId = (int) $rbacUserId;
        $userInv = $uiR->repoUserInvUserIdquery($userId);
        if ($userId > 0 && ($userInv !== null)) {
            $this->upsert($userInv->reqId(), $userId);
        }
    }
}
```

The first request to `/invoice` after the table is created (or emptied) triggers the
full backfill automatically — no console command or manual SQL needed.

`AssignmentsStorageInterface::getAll()` returns only users who **already have** at
least one RBAC assignment (`yii_rbac_assignment` rows). This is the correct behaviour:

- **Active users** (previously logged in) already have RBAC assignments → backfilled automatically on first `/invoice` request.
- **Inactive users** (signed up but never activated) had no assignments → not backfilled. Use the RBAC Link button (see below) to restore their row on demand.
- **New signups going forward** receive a bridge row immediately via `assignSignupRole()` during the signup flow, before admin activation.

## Restoring a missing bridge row manually

If a user's bridge row is missing (e.g. after a table rebuild or data loss), navigate to:

**Settings → Invoice User Account → (find the user) → RBAC Link**

Clicking the RBAC Link button calls `syncRbacLink()` in `UserInvController`, which
looks up the user's existing RBAC assignments and writes a fresh bridge row. This is
the correct recovery path for any user whose row is absent — it does not re-assign
roles, it only restores the bridge.

## SonarQube S1192 — Role string constants

Role name literals (`'admin'`, `'observer'`, `'accountant'`) appeared 7+ times across
7 source files. Three constants were added to `AppConstants`:

```php
public const string ROLE_ADMIN       = 'admin';
public const string ROLE_OBSERVER    = 'observer';
public const string ROLE_ACCOUNTANT  = 'accountant';
```

Files updated:
- `src/Invoice/AppConstants.php`
- `src/Auth/Controller/AuthController.php`
- `src/Auth/Controller/SignupController.php`
- `src/Auth/Trait/Callback.php`
- `src/User/Console/CreateCommand.php`
- `src/ViewInjection/LayoutViewInjection.php`
- `src/Invoice/UserInv/Trait/UserInvRoleTrait.php`
- `src/Invoice/UserInv/UserInvController.php`

## SonarQube S1192 — Redirect constant

`'userinv/index'` appeared 14 times in `UserInvController`. Replaced with a class constant:

```php
private const REDIRECT_USERINV_INDEX = 'userinv/index';
```

## `AuthController::callback()` — DI injection of `UserRbacLinkRepository`

`UserRbacLinkRepository` is injected as a Yii3 DI action parameter into `callback()` and
passed to `CallbackDeps` so the RBAC bridge row is created at the point where a user's
OIDC login is confirmed:

```php
public function callback(
    ServerRequestInterface $request,
    TokenRepository $tR,
    UserInvRepository $uiR,
    UserRepository $uR,
    UserRbacLinkRepository $urlR,
    string $_language,
): ResponseInterface {
    $d = new CallbackDeps($this->translator, $tR, $uiR, $uR, $urlR);
    ...
}
```

## DI explicit binding — `config/common/di/cycle.php`

`RepositoryContainer` (yii-cycle-1 delegate) did not automatically resolve
`UserRbacLinkRepository`. An explicit binding was added to
`config/common/di/cycle.php`:

```php
// Explicit binding so Yii3 DI can resolve UserRbacLinkRepository directly,
// bypassing RepositoryContainer delegate lookup which does not fire for this class.
UserRbacLinkRepository::class => static function (ORMInterface $orm): UserRbacLinkRepository {
    /** @var UserRbacLinkRepository */
    return $orm->getRepository(UserRbacLink::class);
},
```

## Database setup — fresh install or rebuild

> **Order matters.** The existing table must be dropped and `runtime/schema.php` deleted
> before setting `BUILD_DATABASE=true`. If the old table still exists with a stale FK
> constraint, Cycle ORM's `SyncTables` will attempt to alter it and may fail.

Steps:

1. **Drop the existing `user_rbac_link` table** (removes stale FK constraints):
   ```sql
   DROP TABLE IF EXISTS user_rbac_link;
   ```

2. **Delete the Cycle ORM schema cache**:
   ```sh
   rm runtime/schema.php
   ```

3. **Enable schema sync** — set `BUILD_DATABASE=true` in `.env`.

4. **Reload the application** — Cycle ORM runs `SyncTables`, creates `user_rbac_link`
   with the correct FK to `userinv.id`.

5. **Navigate to `/invoice`** — `InvoiceController::index()` calls `syncIfEmpty()`,
   which backfills bridge rows for all active users automatically.

6. **Revert** — set `BUILD_DATABASE=` (blank or `false`) in `.env`.

## Recovering from duplicate `userinv.user_id` data

If `SyncTables` fails with a duplicate-entry error on `userinv`, the table has rows
with the same `user_id` value — inserted before the UNIQUE constraint was enforced.
Deduplicate before retrying:

```sql
-- Find duplicates
SELECT user_id, COUNT(*) FROM userinv GROUP BY user_id HAVING COUNT(*) > 1;
-- Inspect both rows, then delete the unwanted one by its auto-increment id
DELETE FROM userinv WHERE id = <duplicate_id>;
```

Then repeat the schema rebuild steps above.

## Psalm

Psalm errorLevel 1 clean across all modified files (July 2026).
