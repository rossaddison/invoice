# Cycle ORM — Entity Behaviors

## Overview

Cycle ORM's entity behavior package (`cycle/entity-behavior`) provides attribute-driven
lifecycle hooks and automatic column management. The behaviors below extend the
`CreatedAt` and `UpdatedAt` behaviors already in use across the infrastructure entities.

All behaviors are accessed via the `Cycle\ORM\Entity\Behavior` namespace, aliased as
`Behavior` in entity files:

```php
use Cycle\ORM\Entity\Behavior;
```

---

## SoftDelete — `Inv`

### What it does

Instead of issuing a `DELETE` statement, the ORM sets a `deleted_at` timestamp on the
row. All subsequent ORM queries automatically exclude soft-deleted records. Records can
be recovered by clearing `deleted_at`.

### Why on `Inv`

Invoices are financial records. Hard deletion destroys the audit trail and can break
foreign key references from `InvItem`, `InvAmount`, `InvSentLog`, and `InvRecurring`.
Soft delete means the ORM's delete action becomes a safe, reversible operation.

### Implementation

```php
#[Behavior\SoftDelete(field: 'deleted_at', column: 'deleted_at')]
class Inv
{
    #[Column(type: 'datetime', nullable: true)]
    private ?DateTimeImmutable $deleted_at = null;

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deleted_at;
    }
}
```

### Effect on queries

No repository changes are required. All existing `findAll*`, `repoInv*`, and
`findAllWithStatus` queries automatically gain a `WHERE deleted_at IS NULL` clause.
The `deleted_at` column is nullable datetime — `NULL` means active, a timestamp means
soft-deleted.

### Schema change

A `deleted_at datetime NULL` column is added to the `inv` table by the schema builder.

---

## Hook — `Client`

### What it does

`#[Behavior\Hook]` registers a static callable to fire on specified ORM lifecycle
events. The callable receives the event object, which exposes the entity instance.

### Why on `Client`

`client_full_name` is a denormalised computed column (`client_name + ' ' + client_surname`)
stored in the database for efficient lookup and display. Previously it was only set in
the constructor, meaning updates via `setClientName()` or `setClientSurname()` did not
automatically propagate to `client_full_name`. The hook closes that gap.

### Implementation

```php
use Cycle\ORM\Entity\Behavior;

#[Behavior\Hook(
    callable: [self::class, 'syncFullName'],
    events: [
        \Cycle\ORM\Entity\Behavior\Event\Mapper\Command\OnCreate::class,
        \Cycle\ORM\Entity\Behavior\Event\Mapper\Command\OnUpdate::class,
    ]
)]
class Client
{
    public static function syncFullName(
        \Cycle\ORM\Entity\Behavior\Event\Mapper\Command\OnCreate|
        \Cycle\ORM\Entity\Behavior\Event\Mapper\Command\OnUpdate $event
    ): void {
        $client = $event->entity;
        assert($client instanceof self);
        $client->setClientFullName(ltrim(rtrim(
            $client->getClientName() . ' ' . ($client->getClientSurname() ?? '')
        )));
    }
}
```

### Event classes

| Event | Fires when |
|---|---|
| `OnCreate` | Entity is first persisted |
| `OnUpdate` | Entity is updated via the ORM |
| `OnDelete` | Entity is deleted (or soft-deleted) |

### Note on Psalm

Psalm may raise `[InvalidArgument]` on `[self::class, 'syncFullName']` because it
types `self::class` as `class-string` rather than a `callable`. This is a known Psalm
limitation with array-style callables and does not affect runtime behaviour.

---

## Other Available Behaviors

| Behavior | Use case |
|---|---|
| `Behavior\CreatedAt` | Auto-set a datetime column on insert — already in use |
| `Behavior\UpdatedAt` | Auto-set a datetime column on update — already in use |
| `Behavior\SoftDelete` | Mark deleted with a timestamp — applied to `Inv` |
| `Behavior\Hook` | Custom lifecycle callbacks — applied to `Client` |
| `Behavior\OptimisticLock` | Add a `version` column; throw on concurrent save conflict |
| `Behavior\Uuid` | Auto-generate a UUID on create (less relevant — project uses integer PKs) |

### `OptimisticLock` — candidate for `Inv`

If concurrent editing of invoices becomes a concern (multiple admins editing the same
invoice simultaneously), `OptimisticLock` can be added:

```php
#[Behavior\OptimisticLock(field: 'version', column: 'version')]
class Inv
{
    #[Column(type: 'integer', default: 1)]
    private int $version = 1;
}
```

The ORM will throw `Cycle\ORM\Entity\Behavior\Exception\OptimisticLock\RecordIsLockedException`
if two processes attempt to save the same version concurrently.
