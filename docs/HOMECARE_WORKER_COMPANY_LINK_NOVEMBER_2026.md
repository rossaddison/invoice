# HomeCare: Worker ↔ Company Link Table (November 2026)

The HomeCare runs are split between **limited businesses** (currently two, belonging to a
father and son) so that the workers can be classed as self-employed rather than employees. To
be self-employed a worker has to earn their income from **more than one business**, so a worker
is normally registered under several. Nothing in the app recorded which businesses a worker is
registered under: `Worker` has no company, and neither do invoices, runs or allocations.
`WorkerCompany` records it.

The software does not depend on there being exactly two businesses, or on a worker having any:
the number of businesses is whatever is in `company`, and a worker may be linked to none, one
or many.

## The default company

**Every worker belongs to the default company**, which is the app's **current company**
(`company.current`, the company that prints on the invoices; which company is current is
chosen per run). It is a rule in `WorkerCompanyService`, not stored rows, so:

- every existing and future worker belongs to it, with no backfill;
- it follows the current company when the run's company changes (the previous default is no
  longer implied; only the businesses a worker is explicitly registered under remain);
- `worker_company` holds only the businesses a worker is registered under **in addition**.

`companyIdsFor()` lists the default first, then the extras; `workerIdsFor(<default>)` is every
worker; saving the worker form never adds, removes or duplicates the default (a stored link to a
company that later becomes the default is kept, so it is still there if the default changes
again). With no current company there is no default and only stored links count.

## The table

`worker_company` is a **pure link table**. Its primary key is the combination
of the two keys it joins, `worker.id` and `company.id`, with no id column of its
own:

| Column | Type | |
|---|---|---|
| `worker_id` | integer(11) | part of the primary key |
| `company_id` | integer(11) | part of the primary key |

A row means "this worker is registered under this business"; the same pair
can exist only once, and a worker has as many rows as businesses they are registered under. Both columns are plain scalar FKs, not Cycle
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
- `App\Invoice\WorkerCompany\WorkerCompanyService` — `defaultCompanyId`, `link`, `unlink`,
  `unlinkWorker`, `syncCompanies`, `companyIdsFor`, `storedCompanyIdsFor`, `workerIdsFor`,
  `companyIdsByWorker`.

## Where it shows up

- **Worker add/edit** shows the **default business** (read-only: every worker belongs to it) and
  a **Businesses** tick-box list of the other companies. The ticked set is saved through
  `syncCompanies()`, which makes the worker's stored links exactly those: unticked ones are
  removed, newly ticked ones added, ones already there left alone, anything that is not a
  positive id ignored, and nothing ticked means no business beyond the default.
- **Worker list** has a Businesses column (every business the worker belongs to, the default
  first, comma separated).
- **Deleting a worker** removes that worker's links.

## Not done yet (deliberately)

Nothing is *scoped* by business yet. The lead still sees every HomeCare house and an admin sees
everything; allocations, runs and invoices do not carry a business. The intention is that the
business is discernible via the worker, but a worker who is registered under several businesses
cannot by itself say which business a given house or invoice belongs to, so how a job is
attributed to one business (by the house or client, or when it is allocated) is still to be
decided before anything is scoped or reported per business.

## Deploying

A new table: set `BUILD_DATABASE=true`, reload once, then set it back (or
`rm runtime/schema.php` as in the deploy checklist). Verified locally: the
table is created with the composite primary key, and the repository linked,
re-linked (still one row), synced and unlinked rows against the real database
inside a rolled-back transaction.
