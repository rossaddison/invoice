# `Identity::getId()` vs `Identity::getUserId()` — Auth/RBAC Lookups Using the Wrong Id

## Summary

Three call sites in the login/logout/OAuth-TFA path — `AuthController::resolveLoginResponse()`,
`AuthController::logout()`, and `Callback::tfaCheckBeforeRedirects()` — called
`$identity->getId()` where they actually needed the signed-in **user's** id, to look
up `user_inv`, check the admin RBAC role, and clear TFA state. `getId()` does not
return that. It returns the `identity` table's own primary key, which is a different
number from the user's id on a majority of accounts in this database. Fixed by
switching all three call sites to `$identity->getUserId()`.

## Why two different ids exist at all

`src/Infrastructure/Persistence/Identity/Identity.php` is a Cycle ORM entity backed
by its own `identity` table, with its own auto-increment `id` column:

```php
#[Column(type: 'primary')]
private ?int $id = null;

#[BelongsTo(target: User::class, nullable: false, load: 'eager')]
private ?User $user = null;
```

`user` is a `BelongsTo` relation to `User`, resolved via a `user_id` foreign-key
column that Cycle generates on the `identity` table for this relation — there is no
explicit `user_id` property in this class (see the comment above the constructor).
`User` has its own, entirely separate `id` primary key with its own auto-increment
sequence on the `user` table.

So an `identity` row carries two different id values:

- `identity.id` — this row's own position in the `identity` table's auto-increment
  sequence.
- `identity.user_id` — the id of the `user` row it belongs to, in the `user`
  table's own, independent auto-increment sequence.

These two sequences only produce the same numbers by coincidence, and only stay
that way if every `identity` row and its corresponding `user` row are always
created together, in the same order, with nothing else ever inserted into or
deleted from either table in between. In practice that invariant doesn't hold —
users and identities get created via different code paths (signup, OAuth callback,
console `user/create`, HomeCare self-service signup, admin-created accounts), not
always 1:1 in lockstep, and rows get deleted over the life of the app. Once a single
row is created on one side without a matching row on the other (or the two tables'
sequences are seeded from a migration/import at different starting points), `id` and
`user_id` permanently stop lining up for every row after that point — there's no
self-correcting mechanism, because both are ordinary independent `AUTO_INCREMENT`
columns.

This was confirmed empirically against this project's dev database rather than
assumed:

```sql
SELECT COUNT(*) FROM identity WHERE id != user_id;
-- 2570 of 4579 rows (56%)
```

More than half of all accounts in this dev DB have `identity.id != identity.user_id`.

## The two accessors, and which one is correct

```php
public function reqId(): int
{
    return $this->requireId($this->id, 'Identity');
}

#[\Override]
public function getId(): ?string
{
    return $this->hasIdentity() ? (string) $this->reqId() : null;
}

public function getUserId(): ?int
{
    if ($this->user) {
        return $this->user->reqId();
    }
    return null;
}
```

- `getId()` satisfies Yii's `IdentityInterface` contract, which requires every
  identity object to expose its own unique id — this is `identity.id`, nothing to
  do with the user.
- `getUserId()` walks the eager-loaded `user` relation and returns the user's own
  `reqId()` — this is the id that `user_inv`, RBAC role assignments, and every
  other user-scoped lookup in this app are actually keyed on.

`getId()` and `getUserId()` are not interchangeable, but the three call sites fixed
here had been using `getId()` to do user-keyed lookups — silently working whenever
`id` and `user_id` happened to coincide (correct login), and silently resolving the
wrong user, or no user at all, whenever they didn't (broken login resolution, wrong
`isAdminUser()` result, TFA state cleared for the wrong account).

## The fix

`IdentityInterface` (the interface `AuthService::getIdentity()` returns) doesn't
declare `getUserId()` — only the concrete `Identity` class does — so each call site
narrows with `instanceof` rather than widening the interface (which would be wrong
for the guest-identity case returned when nobody is logged in):

```php
// getId() returns the identity table's own row id, not the user's —
// see resolveLoginResponse() for why that matters here.
$userId = $identity instanceof Identity ? $identity->getUserId() : null;
```

applied in:

- `AuthController::resolveLoginResponse()` — drives `isAdminUser()`,
  `handleTfaPath()`/`handleNonTfaPath()`, and the `user_inv` lookup
  (`UserInvRepository::repoUserInvUserIdquery()`).
- `AuthController::logout()` — drives `clearTfaOnLogout()`.
- `Callback::tfaCheckBeforeRedirects()` — drives `isAdminUser()`, `disableToken()`,
  and the `user_inv` lookup on the OAuth2 callback path.

Fixed in commit `cd6e6ee2` on `main` (cherry-picked from the branch this was found
on, `17805f05`).

## How this was actually diagnosed

Not by code reading alone — the wrong-user symptom only showed up testing a real
login end-to-end (a console-created test user logged in successfully but resolved
to a different account's identity). Verified live against the real dev MySQL
database at each step before accepting a hypothesis: confirmed the session file
carried no stale identity data (ruling out session staleness), confirmed the
mismatch persisted after a full Apache restart (ruling out stale FastCGI worker
state), then queried `identity.id` vs `identity.user_id` directly for the affected
account before — and across the whole table after — concluding this was the root
cause, and separately confirmed `user/assignRole` never touches the `identity`
table at all (ruling it out as a contributing cause).
