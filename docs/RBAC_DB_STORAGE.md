# RBAC DB Storage

## Summary

RBAC assignments were migrated from a PHP flat-file backend
(`resources/rbac/assignments.php`) to a MySQL table (`yii_rbac_assignment`)
backed by Cycle ORM via `yiisoft/rbac-cycle-db`. Role/permission items remain
in `resources/rbac/items.php` (version-controlled, opcode-cached).

## Why

- PHP-file assignments cannot survive multi-server deploys without shared
  filesystem mounts.
- Runtime assignment (signup, OAuth2 callback) is safer to a DB row than a
  file write that can fail silently due to ownership/permission issues.

## What Changed

### Composer

- Added `yiisoft/rbac-cycle-db >=3`

### DI — `config/common/di/rbac.php`

`AssignmentsStorageInterface` is now wired to
`Yiisoft\Rbac\Cycle\AssignmentsStorage` via a factory that injects the Cycle
`DatabaseManager`. `ItemsStorageInterface` remains bound to
`Yiisoft\Rbac\Php\ItemsStorage`.

### Infrastructure entity — `src/Infrastructure/Persistence/RbacAssignment/RbacAssignment.php`

Cycle ORM entity that defines the `yii_rbac_assignment` table schema:

| Column | Type | Notes |
|--------|------|-------|
| `item_name` | `string(126)` | composite PK |
| `user_id` | `string(126)` | composite PK |
| `created_at` | `integer` | Unix timestamp |

The table is created automatically by Cycle ORM's `SyncTables` generator on
the first request with `BUILD_DATABASE=true`.

### Deleted

`resources/rbac/assignments.php` — superseded by the DB table.

### `.claude/sync-schema.ps1`

Updated to delete `runtime/schema.php` before setting `BUILD_DATABASE=true`,
guaranteeing a full entity rescan on every schema sync.

### `psalm.xml`

Added `vendor/yiisoft/rbac-cycle-db/src` to `projectFiles` so Psalm resolves
`Yiisoft\Rbac\Cycle\AssignmentsStorage` when analysing DI config files in
isolation.

## Initial Seeding

After the table is created, seed the bootstrap assignments once:

```bash
php yii user/assignRole admin 1
php yii user/assignRole observer 2
```

Subsequent users are assigned roles dynamically at signup via
`SignupController::assignRoleAndVerify()` and at OAuth2 callback via
`Callback::assignRoleAndVerify()`.