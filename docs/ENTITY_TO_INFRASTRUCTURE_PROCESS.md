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
- Update every view file that calls `getId()` on this class to `reqId()`
- Create or update `src/Invoice/{Name}/{Name}Form.php` to reference the
  infrastructure FQCN (not `App\Invoice\Entity\{Name}`). The Form is always
  a caller and is required before the `add` / `edit` controller actions
  can function correctly. The `add` action must initialise the form via
  `{Name}Form::show($entity)` rather than `new {Name}Form()` so that any
  `#[Required]` fields pre-populated from the entity (e.g. `client_id`)
  survive `FormHydrator` validation.

Set `'callers_updated' => true`, `'form_created' => true`,
`'null_guards_removed' => true`, and `'view_get_id_updated' => true`.

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

### Stage 9 — clear the Cycle ORM schema cache

```bash
rm runtime/schema.php
```

Cycle ORM caches its entity map at `runtime/schema.php`. Without clearing it,
the proxy factory still maps the old `App\Invoice\Entity\{Name}::class` and
throws:

```
RuntimeException: The entity `App\Invoice\Entity\{Name}` class does not exist.
Proxy factory can not create classless entities.
```

Refresh the browser — Cycle rebuilds `schema.php` automatically on the next
request. Set `'schema_cache_cleared' => true`.

---

## Registry flags per entry

| Flag | Set in stage | Meaning |
|---|---|---|
| `req_id` | 3 | `reqId()` / `isPersisted()` / `setId()` pattern in place |
| `var_annotations` | 4 | All `@var` in infrastructure class correctly typed |
| `callers` | 2 | List of files referencing the old entity |
| `callers_updated` | 5 | All callers updated to infrastructure FQCN |
| `form_created` | 5 | `src/Invoice/{Name}/{Name}Form.php` created/updated to use infrastructure FQCN |
| `null_guards_removed` | 5 | Redundant null guards removed after `reqId()` |
| `view_get_id_updated` | 5 | All view `getId()` calls replaced with `reqId()` |
| `group_use` | 5 | Group use syntax applied where import exceeds 85 chars |
| `psalm` | 6 | Full project-wide Psalm passes at errorLevel 1 |
| `entity_removed` | 7 | Old `src/Invoice/Entity/{Name}.php` deleted |
| `schema_cache_cleared` | 9 | `runtime/schema.php` deleted and browser confirmed working |
| `tests` | — | List of test files covering this entity (where present) |
| `tests_updated` | — | All listed test files updated to infrastructure FQCN |

---

## Recent changes (April 2026)

- `form_created` flag added to all registry entries and documented in
  `entity_to_infrastructure.php` and this file.
- `view_get_id_updated` and `schema_cache_cleared` flags added to the
  flags table (were already tracked in the registry but undocumented here).
- Stage 9 (schema cache clearing) added to the process.
- `Project` promoted from `null` to a full registry entry:
  `entity_removed => true`, `form_created => true`, other flags pending
  verification.
- `DeliveryLocation` add function fixed: `add()` now calls
  `DeliveryLocationForm::show($entity)` instead of `new DeliveryLocationForm()`
  so that `client_id` is pre-populated before `FormHydrator` validation runs.
  A hidden `client_id` input was added to `_form.php` to ensure `persist()`
  receives it in the POST body.
- `DeliveryLocationForm` date getters hardened: `getDateCreated()` and
  `getDateModified()` now parse non-empty string values (from POST hydration)
  into `DateTimeImmutable` rather than falling back to `now`.
- Date timezone applied in `_form.php` and `_view.php` via
  `$s->getSetting('time_zone')` with `'Europe/London'` fallback.

---

## Current priority targets (April 2026)

Entities still imported inside infrastructure classes (blocking clean
infrastructure):

| Blocking entity | Blocked infrastructure class |
|---|---|
| `Inv` | `InvAllowanceCharge`, `Client` |

Previously blocking entities now resolved: `SalesOrder`, `SalesOrderItem`,
and `ProductClient` are fully converted (`entity_removed => true`).
