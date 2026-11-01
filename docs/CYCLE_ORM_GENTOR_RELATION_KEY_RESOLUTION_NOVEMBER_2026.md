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
renamed to a generic `id`-based guess. For string/natural-keyed FKs, the
generator doesn't support the relation case at all yet (see above); when
that support is added, the expectation — not yet proven, since it doesn't
exist yet — is that it will behave the same way, since Cycle's `Column`
`name:` parameter already maps a property straight onto its real column
name regardless of type.
