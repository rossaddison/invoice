# Entity to Infrastructure Migration Process

Tracks the staged conversion of Cycle ORM annotated entities from
`src/Invoice/Entity/{Name}.php` to the infrastructure persistence layer at
`src/Infrastructure/Persistence/{Name}/{Name}.php`.

The registry file that records conversion status for each entity is at
`src/Infrastructure/entity_to_infrastructure.php`.

---

## Why migrate?

- Separates domain logic from persistence concerns (DDD layering)
- Enables Psalm errorLevel 1 with 100% type inference per class
- Replaces nullable `getId(): ?int` with strict `reqId(): int` (throws
  `\LogicException` when not yet persisted) so null guards become redundant
- Reduces SonarQube duplication warnings by consolidating entity references

---

## Conversion stages

### CRITICAL pre-step — update view `@var` annotations first

**Do this before creating the infrastructure class.**

Grep views for stale entity annotations and update them to the planned
infrastructure FQCN before any other step:

```bash
grep -rn "@var App\Invoice\Entity\{Name}" resources/
```

Replace each match with:

```php
/**
 * @var App\Infrastructure\Persistence\{Name}\{Name} $var
 */
foreach ($repository->repoQuery() as $var) {
```

Doing this first means every view already carries the correct FQCN by the
time Psalm first runs against the new infrastructure class, eliminating an
entire category of cascading `MixedMethodCall` / `MixedArgument` errors
(typically 3-5 per foreach loop).

---

### Stage 0 — choose the right entity

**Priority check first** — grep inside already-converted infrastructure
classes for residual entity imports. These block existing infrastructure
classes from being fully clean:

```bash
grep -rn "App\Invoice\Entity\" src/Infrastructure/Persistence/
```

Convert every entity appearing in that output before picking from the
general ranked list.

**General ranking** (after priority targets are cleared):

```bash
grep -rl "App\\Invoice\\Entity\\" src/ \
  | xargs grep -h "App\\Invoice\\Entity\\" \
  | grep -oP "App\\\\Invoice\\\\Entity\\\\[A-Za-z]+" \
  | sort | uniq -c | sort -n
```

Lowest reference count = safest to convert next.

---

### Stage 1 — create the infrastructure class

Create `src/Infrastructure/Persistence/{Name}/{Name}.php`.

- Namespace: `App\Infrastructure\Persistence\{Name}`
- Cycle ORM `#[Entity]`, `#[Column]`, `#[BelongsTo]`, `#[HasMany]` attributes
- Add entry to `entity_to_infrastructure.php` with all flags `false`
- 85-character line length enforced throughout

---

### Stage 2 — audit callers

Two greps required — FQCN references AND short-name references:

```bash
grep -rl "App\\Invoice\\Entity\\{Name}" src/ resources/
grep -rl "{Name}" src/ resources/ --include="*.php"
```

Combine (deduplicated) into the `'callers'` array in the registry.

---

### Stage 3 — apply `reqId()` pattern

Update the infrastructure class:

```php
public function reqId(): int
{
    if ($this->id === null) {
        throw new \LogicException(
            '{Name} has no ID (not persisted yet)'
        );
    }
    return $this->id;
}

public function isPersisted(): bool { return $this->id !== null; }
public function setId(int $id): void { $this->id = $id; }
```

Set `'req_id' => true` in registry.

---

### Stage 4 — update `@var` annotations in the infrastructure class

Ensure no bare `@var $variable` and no stale `App\Invoice\Entity\{X}`
reference for any already-converted class. Set `'var_annotations' => true`.

---

### Stage 5 — update all external callers

For each file in the Stage 2 list:

- Replace `use App\Invoice\Entity\{Name}` with the infrastructure FQCN
- Use group use syntax when import exceeds 85 characters:
  ```php
  use App\Infrastructure\Persistence\{Name}\{
      {Name},
  };
  ```
- Verify no bare `@var $variable` left behind
- Remove null guards made redundant by `reqId()` returning non-nullable `int`

Set `'callers_updated' => true` and `'null_guards_removed' => true`.

---

### Stage 6 — run scoped Psalm

```bash
vendor/bin/psalm src/Infrastructure/Persistence/{Name}/
```

Must report zero errors at errorLevel 1, 100% type inference.
Set `'psalm' => true`.

---

### Stage 7 — delete the old entity file

Delete `src/Invoice/Entity/{Name}.php`.
Set `'entity_removed' => true`.

---

### Stage 8 — run full project-wide Psalm

```bash
vendor/bin/psalm
```

Any errors indicate missed callers from Stage 5. Resolve before marking the
conversion complete.

---

## Registry flags per entry

| Flag | Meaning |
|---|---|
| `req_id` | `reqId()` / `isPersisted()` / `setId()` pattern in place |
| `var_annotations` | All `@var` in infrastructure class correctly typed |
| `callers` | List of files referencing the old entity |
| `callers_updated` | All callers updated to infrastructure FQCN |
| `null_guards_removed` | Redundant null guards removed after `reqId()` |
| `group_use` | Group use syntax used where import exceeds 85 chars |
| `psalm` | Scoped Psalm passes at errorLevel 1 |
| `entity_removed` | Old `src/Invoice/Entity/{Name}.php` deleted |

---

## Current priority targets (April 2026)

Entities still imported inside infrastructure classes (blocking clean
infrastructure):

| Blocking entity | Blocked infrastructure class |
|---|---|
| `SalesOrder` | `SalesOrderItemAllowanceCharge` |
| `SalesOrderItem` | `SalesOrderItemAllowanceCharge` |
| `Inv` | `InvAllowanceCharge`, `Client` |
| `ProductClient` | `Client` |
