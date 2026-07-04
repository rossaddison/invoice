# RBAC Bridge Table — `user_rbac_link`

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
| `user_id` | `INT NOT NULL` | UNIQUE — FK → `userinv.user_id ON DELETE RESTRICT` |
| `rbac_user_id` | `VARCHAR(126) NOT NULL` | UNIQUE — mirrors `yii_rbac_assignment.user_id` |

UNIQUE indexes on both `user_id` and `rbac_user_id` prevent duplicate bridge rows.
The `BelongsTo` relation creates the FK at the DB level so deleting a `UserInv` with
an active RBAC link is blocked.

### Entity design choice

Cycle ORM's `RelationSchema::getPrimaryColumns()` is called when resolving `BelongsTo`
relations. It cannot read the `#[Table(primary: new PrimaryKey(columns: [...]))]`
attribute — it requires a column marked `#[Column(type: 'primary')]`. The entity
therefore uses a standard auto-increment `id` PK, and `user_id` becomes a UNIQUE
non-PK column (like every other infrastructure entity in this codebase).

```php
#[Entity(repository: UserRbacLinkRepository::class)]
#[Index(columns: ['user_id'], unique: true)]
#[Index(columns: ['rbac_user_id'], unique: true)]
class UserRbacLink
{
    use RequireId;

    #[BelongsTo(target: UserInv::class, nullable: false, fkAction: 'RESTRICT',
                innerKey: 'user_id', outerKey: 'user_id')]
    private ?UserInv $user_inv = null;

    public function __construct(
        #[Column(type: 'primary')]
        private ?int $id = null,
        #[Column(type: 'integer', nullable: false)]
        private ?int $user_id = null,
        #[Column(type: 'string(126)', nullable: false)]
        private ?string $rbac_user_id = null,
    ) {}
}
```

## Backfill on first boot — `syncIfEmpty()`

`UserRbacLinkRepository::syncIfEmpty()` is called from `InvoiceController::index()`
on every request, but short-circuits immediately if `count() > 0`:

```php
public function syncIfEmpty(AssignmentsStorageInterface $storage, UserInvRepository $uiR): void
{
    if ($this->count() > 0) {
        return;
    }
    foreach (array_keys($storage->getAll()) as $rbacUserId) {
        $userId = (int) $rbacUserId;
        if ($userId > 0 && $uiR->repoUserInvUserIdquery($userId) !== null) {
            $this->upsert($userId);
        }
    }
}
```

`AssignmentsStorageInterface::getAll()` returns only users who **already have** at
least one RBAC assignment (`yii_rbac_assignment` rows). This is the correct behaviour:

- **Active users** (previously logged in) already have RBAC assignments → backfilled.
- **Inactive users** (signed up but never activated) had no assignments → not backfilled.
  The ⚠️ button in `userinv/index` handles these on demand.
- **New signups going forward** receive a bridge row immediately via `assignSignupRole()`
  during the signup flow, before admin activation.

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

## Database setup

1. Set `BUILD_DATABASE=true` in `.env`.
2. Reload the application — Cycle ORM creates `user_rbac_link` with the FK.
3. Navigate to `/invoice` — `syncIfEmpty()` backfills existing active users.
4. Revert `BUILD_DATABASE=` in `.env`.

If the table already exists with `user_id` as PK (from a previous schema run):

```sql
DROP TABLE IF EXISTS user_rbac_link;
```

Then delete `runtime/schema.php` and repeat the steps above.

## Psalm

Psalm errorLevel 1 clean across all modified files (July 2026).
