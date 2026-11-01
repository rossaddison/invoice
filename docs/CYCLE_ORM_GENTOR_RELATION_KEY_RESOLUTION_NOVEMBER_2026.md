# Gentor Code Generator: Relation innerKey/outerKey Resolution Fix

## Overview

This documents a generator-level bug found and fixed while using this repo's
own Gentor scaffolding tool (`App\Invoice\Generator\GeneratorCodeController`,
`resources/views/invoice/generator/templates_protected/Entity.php`) to
generate real `cycle/orm` entities for a sister project,
[`rossaddison/FA`](https://github.com/rossaddison/FA/tree/chore/php-8.5-minimum).
It is related to, but distinct from,
[Cycle ORM HasOne and outerKey Issue](CYCLE_ORM_HASONE_OUTERKEY_ISSUE.md):
that doc is about a hand-authored relation needing an explicit `outerKey`
when the target's FK property doesn't follow Cycle's default naming; this
one is about the generator itself never computing real keys at all, for
*any* entity it produces, every time it emits a `#[BelongsTo(...)]`.

## The Problem

Flagged directly by the project owner before any relation-bearing entity had
been generated: *"cycle/orm generates foreign fields in a specific table by
means of {}.{} so it will inspect the foreign table, pull its id field out
of it namely and append the name of the foreign table to it."*

Confirmed by reading `cycle/schema-builder` directly
(`Relation/BelongsTo.php`, `Definition/Map/FieldMap.php::getNames()`):
Cycle's default relation-key convention is
`INNER_KEY => '{relation}_{outerKey}'` / `OUTER_KEY => '{target:primaryKey}'`,
and `{target:primaryKey}` resolves to the target's **PHP property name**,
not its real database column name. In this generator's own convention every
entity's PK property is always named `id`, so the default always collapsed
to `innerKey = '{relation}_id'` — correct by coincidence for an
auto-increment-int FK like `grn_batch.supplier_id` only if the relation
happened to be named `supplier`, and silently wrong for any table with a
string/natural-keyed FK (`curr_code`, `loc_code`, `stock_id`, and similar
columns throughout `rossaddison/FA`).

The old template never resolved either key against the real schema at all —
it just didn't pass `innerKey`/`outerKey`, leaving Cycle to guess.

## The Fix

`Entity.php`'s relation-generation loop now resolves both keys from real
schema introspection before emitting the attribute, and **throws
`\RuntimeException` immediately** rather than guessing, when:

- no `Gentor` record can be found for the relation's target (this
  generator's `GentorRelation` records the target only as a free-text
  `camelcasename`, with no real foreign-key link to the target's own
  `Gentor` row — closed with a new best-effort lookup,
  `GeneratorRepository::findByCamelcaseCapitalName()`);
- the target's primary key can't be reliably determined;
- the expected `{relation}_id`-style column doesn't actually exist on the
  source table.

### Primary-key detection had its own bug

Resolving the target's real PK initially walked its columns looking for
`getAbstractType() === 'primary'/'bigPrimary'` — which turned out to be
Cycle's classification for auto-increment integer columns specifically, not
"the primary key." Confirmed concretely against `fa_spike.currencies`, whose
real PK `curr_abrev` is a non-auto-increment `char(3)`: the column-walk
found nothing. Fixed by resolving the PK via
`TableInterface::getPrimaryKeys(): list<non-empty-string>` instead (the
real DB-reflected PK), then separately verifying that PK is an
auto-increment int before using it as a relation target — composite keys
and natural/string keys are rejected with a clear message rather than
mishandled. This generator does **not** yet support generating an entity
*for* a natural/string-PK table at all (confirmed by deliberately
generating `currencies` itself, which now fails loudly:
`"Cannot generate entity 'Currencie': primary key column 'curr_abrev' on
table 'currencies' is not an auto-increment integer. This generator
doesn't support natural/string primary keys yet."`) — a known, deliberately
deferred limitation, not silently swallowed.

### A second, independent bug found while verifying the fix

Generating the first real relation pair (`GrnBatch` belongsTo `Supplier`)
and actually reading the output (not just inspecting the template) surfaced
a separate bug in the relation setter: the parameter was declared using the
target's CamelCase name (`setSupplier(?Supplier $Supplier)`) while the
method body assigned from the lowercase name
(`$this->supplier = $supplier;`) — two different variables, so `$supplier`
was always undefined and every generated relation setter silently nulled
the property instead of ever setting it. Fixed to use the lowercase name
for the parameter, matching the body and every other generated setter's
own convention.

## Verification

Confirmed with real execution against live `fa_spike` data, not just
generated-code inspection:

- A genuine `Cycle\ORM\ORM` schema compile across `GrnBatch` and `Supplier`
  together, with a real eager-loaded `->load('supplier')` query, confirming
  the relation returns the *correct* `Supplier` row
  (`$supplier->reqId() === $batch->getSupplierId()`), including after the
  setter-parameter fix (Cycle's hydrator calls the relation's setter during
  eager-load, so this also proved the setter bug didn't break hydration).
- A loud-failure scratch test generating a relation to `currencies`
  (string PK), confirming the new `\RuntimeException` message fires instead
  of silently emitting a wrong key.
- A loud-failure scratch test against an unresolvable target name,
  confirming the missing-`Gentor`-record case also fails loudly.
- `php -l` on every touched file; full `--no-cache` Psalm rescans of
  `rossaddison/FA` after each regenerate.

## Foreign Key Originality

A natural follow-up question: does any of this rename FA's own FK columns?
No — for auto-increment-int PK/FK pairs this is already proven end to end:
`grn_batch.supplier_id` stays `supplier_id` on both sides, nothing is
renamed to a generic `id`-based guess. For string/natural-keyed FKs, see
below — now supported, and proven the same way.

## Natural/String Primary Key Support (added same month)

The deferred limitation above — this generator couldn't generate an entity
*for* a natural-key table at all, nor a relation *to* one — was closed
immediately after, since it was the direct next blocker for FrontAccounting
(`currencies.curr_abrev`, `stock_master.stock_id`, and others are natural
keys, and several tables carry FKs to them).

### Entity's own primary key

A natural-key PK (any PK column whose real type isn't an auto-increment
int) is no longer special-cased into the `$id`/`reqId()`/`hasIdentity()`/
`setId()` shape at all — that shape only ever made sense for a DB-assigned
surrogate key. Instead it flows through the exact same per-column loop as
every other field: its real column name becomes the property name
directly (no `name:` override needed, since they're already identical),
with an ordinary getter/setter. `RequireId`'s trait/import are now only
emitted at all when something on the entity actually needs
`requireId()` — the auto-increment PK, or an int-typed `_id` FK column —
checked explicitly so a natural-key entity with neither doesn't carry a
dead import.

One related latent bug surfaced while generalizing this: the "FK `_id`
fields get a `req*Id()`/int-typed accessor" branch matched purely on the
`_id` *name suffix*, which breaks for `stock_master.stock_id` — a real
FrontAccounting column that's a natural (`varchar`) key despite the name.
Now guarded on the column's real type being `int`, not just the suffix.

### Relations to a natural-key target: the new `foreign_key_column` field

Resolving a relation's `outerKey` to the target's real PK (via
`getPrimaryKeys()`, from the earlier fix) works for any PK type. But the
conventional `innerKey = '{relation}_id'` guess only ever made sense for
an auto-increment-int target — for a natural key, the source FK column
name doesn't follow any derivable convention in this schema at all (e.g.
`suppliers.curr_code` points at `currencies.curr_abrev`: neither
`curr_abrev` nor `currencie_id`). There's nothing in `GentorRelation` that
already captures this (`view_field_name` is a display-only field, used for
listing/dropdown labels, not a source FK column name), so a new nullable
`foreign_key_column` column was added to `GentorRelation` — set explicitly
by a developer when wiring up a relation to a natural-key target, left
blank otherwise. The generator now: uses it verbatim as `innerKey` when
set; falls back to the old `{relation}_id` convention when the target *is*
auto-increment-int (unchanged, fully backward compatible); and throws
`\RuntimeException` — rather than guessing — if the target is natural-key
and `foreign_key_column` wasn't set.

### A second latent bug, found generating a real cross-namespace relation

Generating `Supplier belongsTo Currencie` for real (via `suppliers.curr_code`)
surfaced a bug independent of natural keys: the relation's `use` import
statement hardcoded `App\Infrastructure\Persistence` as every target's
namespace root — correct only because every entity this generator had
produced so far happened to share one namespace tree with its relations.
It silently emitted the *source* entity's own namespace for the *target*
import the moment the two differ, which they do for
`Supplier` (`...\Purchasing\Infrastructure\Persistence`) and `Currencie`
(`...\Currency\Infrastructure\Persistence`). Fixed by resolving each
relation's target namespace from its own `Gentor` record's
`namespace_path`, same as every other piece of per-target data here — and,
while fixing it, the whole target-resolution block (previously duplicated
between the `use` statement loop and the `#[BelongsTo]` loop) was hoisted
into one `$relationTargets` lookup built once per entity, used by both.

### Verification

- Scratch tests confirming: a natural-key entity (`currencies`) generates
  with no `$id`/`RequireId`/`reqId()`/`hasIdentity()`, a real `curr_abrev`
  property with a getter; a relation to it with `foreign_key_column` set
  resolves `innerKey`/`outerKey` correctly; the same relation *without* it
  set fails loudly instead of guessing.
- A genuine `Cycle\ORM\ORM` schema compile of real, generated
  `FrontAccounting\Currency\...\Currencie`,
  `FrontAccounting\Purchasing\...\Supplier`, and
  `FrontAccounting\Purchasing\...\GrnBatch` entities together, with a real
  eager-loaded `->load('currencie')` query against live `fa_spike` data,
  confirming `Supplier::getCurrencie()->getCurrAbrev()` matches
  `Supplier::getCurrCode()` — the actual natural-key join resolving the
  correct row, plus a regression check that `GrnBatch`'s existing
  `Supplier` relation (auto-increment-int, from the earlier fix) still
  works unaffected.
- `php -l` on every touched file.

### A follow-on gap found during verification, not yet fixed

Compiling the schema for a natural-key entity at all requires
`Cycle\Annotated\MergeIndexes` in the generator pipeline (it's what
actually registers a `#[Table\PrimaryKey]`-annotated column as the
entity's primary key — Cycle's own `'primary'`/`'bigPrimary'` abstract
column type, which is all `RenderTables`/`GenerateRelations` otherwise
know about, is reserved for auto-increment surrogate keys and never
applies here). `Entity.php` now emits `#[PrimaryKey(columns: [...])]` for
a natural-key entity accordingly. But `MergeIndexes` must run **before**
`GenerateRelations` for a relation targeting that entity to compile at
all (`GenerateRelations` reads the target's primary columns eagerly) —
confirmed directly: the compile throws `"Entity \`currencie\` must have
defined primary key"` with `MergeIndexes` ordered after
`GenerateRelations`, and succeeds with it ordered before. This app's own
real schema-building pipeline
(`rossaddison/yii-cycle-1`'s `SchemaConveyor`) appends `MergeIndexes` to
the same stage as `GenerateRelations`, *after* it — meaning, as that
package stands today, the very first real relation to a natural-key
target wired up through the live app (not a standalone scratch script
like this one) will hit the same compile error. Flagged here rather than
fixed, since it lives in a separate package/repository from this one.
