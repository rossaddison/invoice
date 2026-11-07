# HomeCare: Worker ↔ Company Link Table (November 2026)

The HomeCare runs are split between **two limited businesses** so that each
worker can be registered as self-employed under one of them. Nothing in the
app recorded which business a worker belongs to: `Worker` has no company, and
neither do invoices, runs or allocations. `WorkerCompany` records it.

## The table

`worker_company` is a **pure link table**. Its primary key is the combination
of the two keys it joins, `worker.id` and `company.id`, with no id column of its
own:

| Column | Type | |
|---|---|---|
| `worker_id` | integer(11) | part of the primary key |
| `company_id` | integer(11) | part of the primary key |

A row means "this worker is registered under this business"; the same pair
can exist only once. Both columns are plain scalar FKs, not Cycle
`#[BelongsTo]` relations (as in `InvWorkerAllocation`), and there is no
separate `#[Index]`: the composite primary key already indexes
`(worker_id, company_id)`, and an index on a not-yet-created table has taken
the schema build down before (the `MergeIndexes` ordering in the
`yii-cycle-1` fork).

The key is declared the way `RbacAssignment` already declares its own:
`#[Table(primary: new PrimaryKey(columns: ['worker_id', 'company_id']))]`.

## Code

- `App\Infrastructure\Persistence\WorkerCompany\WorkerCompany` — the entity
  (`reqWorkerId()`, `reqCompanyId()`).
- `App\Invoice\WorkerCompany\WorkerCompanyRepository` (+ `…Interface`, bound in
  `config/common/di/homecare.php`) — `repoLinkquery`, `repoWorkerquery`,
  `repoCompanyquery`, `repoAllquery`.
- `App\Invoice\WorkerCompany\WorkerCompanyService` — `link`, `unlink`,
  `unlinkWorker`, `syncFromChoice`, `companyIdsFor`, `workerIdsFor`,
  `companyIdsByWorker`.

## Where it shows up

- **Worker add/edit** has a **Business** dropdown (the companies from
  `company`); the choice is saved through `syncFromChoice()`, which leaves the
  worker linked to exactly that business ("No business" removes the link).
- **Worker list** has a Business column.
- **Deleting a worker** removes that worker's links.

The table itself allows a worker to be linked to more than one business (it is
just the pair of keys); the worker form offers one choice because a worker is
registered under one business.

## Not done yet (deliberately)

Nothing is *scoped* by business yet. The lead still sees every HomeCare house
and an admin sees everything; allocations, runs and invoices do not carry a
business. This table is the foundation for that (and for putting the right
business on a "carded" record or a worker's earnings) once it is decided how a
run or invoice maps to a business.

## Deploying

A new table: set `BUILD_DATABASE=true`, reload once, then set it back (or
`rm runtime/schema.php` as in the deploy checklist). Verified locally: the
table is created with the composite primary key, and the repository linked,
re-linked (still one row), synced and unlinked rows against the real database
inside a rolled-back transaction.
