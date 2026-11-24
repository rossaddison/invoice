# A quote, sales order or invoice moved to another client now moves to that client's user — November 2026

## In one paragraph

A quote, a sales order and an invoice each belong to **the user assigned to their client**. The guest menu, the emailed
link and the guest's own access checks all compare the record's `user_id` with the signed-in user, and require that user
to be linked to the record's client. The edit forms let staff **change the client** but never post a user, so a record
moved to another client kept the **old client's user** and then belonged to nobody who could open it. Quotes were fixed on
22 November; sales orders and invoices on 24 November. A client now also has **exactly one user**, enforced by a unique
index on `user_client.client_id`.

## Why it mattered

| Record | The check that failed for a moved record |
|---|---|
| Invoice | `InvController`: the record's user must be the signed-in guest **and** linked to the invoice's client **and** active |
| Sales order | `SalesOrderRbacGuard::isObserver()`: the same three conditions |
| Quote | The guest menu and the emailed `quote/urlKey` link go by the quote's user |

After a move the old user was no longer linked to the new client, and the new client's user did not match the record's
`user_id`, so **neither** client's guest could open or pay it. The invoice email and PDF also took the old user's
settings.

## What changed

| Record | Where | Now |
|---|---|---|
| Quote | `QuoteService::saveQuote()`, `QuoteEditClient` | A changed client gives the quote to that client's user |
| Sales order | `SalesOrderOwnerFollowsClient` (staff edit only) | A changed client puts that client's user in `user_id` before `saveSo()`; an observer still cannot change the client |
| Invoice | `Trait\Edit::editSaveFormFields()`, `InvService::saveInv()` | The owner is looked up for the **posted** client, and the stored `user_id` moves with the client |

All three use `PostedClientId` (the client the form is saving for, else the record's own) and, for the lookup,
`ClientActiveUser` (the client's single active user). A record left on its client keeps its owner.

**If the new client has no single active user** (nobody assigned, or an inactive account) the save is **refused** with
"Not saved: the client has no single active user assigned to it…" (`record.client.no.single.active.user`; quotes keep
`quote.client.no.single.active.user`). The invoice edit used to redirect silently in that case.

## One user per client

Everything above assumes a client has one user. That is now enforced:

- **Database:** `UserClient` declares `#[Index(columns: ['client_id'], name: 'user_client_client_id_unique', unique: true)]`.
- **Assign form:** `UserClientController` only asked whether *this user and this client* were already linked, so a crafted
  post could have created a second owner. `UserClientAssignmentRule` now also refuses a client that already has another
  user ("That client already has a user assigned. A client can have only one user.", `client.already.has.user`). The
  dropdown already hid assigned clients; this checks on the server.

### Applying the index to a database

Adding the index to the entity changes nothing on a running site until the Cycle schema is rebuilt. Follow the steps
in `config/common/params.php` ("Modifying entities"): `BUILD_DATABASE=true` in `.env`, delete `runtime/schema.php`, load
any page, then set `BUILD_DATABASE=` back to empty. It **fails if a client already has two links**, so first run:

```sql
SELECT client_id, COUNT(*) AS links, GROUP_CONCAT(user_id) AS users
FROM user_client GROUP BY client_id HAVING links > 1;
```

It must return no rows. Confirm afterwards with
`SHOW INDEX FROM user_client WHERE Key_name = 'user_client_client_id_unique';` (one row, `Non_unique = 0`). Both checks
were run on the live site on 24 November: no duplicates, index present. On a development machine the project's
`.claude/sync-schema.ps1` hook does the rebuild when an entity under `src/Infrastructure/Persistence/` is edited.

## Repairing records moved before the fix

The fix only stops new moves going wrong. A record moved earlier keeps the old client's user until it is repaired. The
command `invoice/repair-record-owners` lists them and, on request, gives each one to its client's user:

```
php yii invoice/repair-record-owners           # lists what it would change; changes nothing
php yii setting/backup-database                # back up first
php yii invoice/repair-record-owners --force   # gives each repairable record to its client's user
```

The list shows the record's type (`quote`, `sales_order` or `inv`), id and number, its client, its current user, the
client's user, and the action. Run the dry run first and read it before using `--force`.

| A record is... | What the command does |
|---|---|
| Owned by a user other than its client's user, and that user's account is active | Listed as **repair**; given to the client's user with `--force` |
| The same, but the client's user has an **inactive** account | Listed as **skip**; left as it is. Activate that user, or move the record, and run it again |
| A **deleted** invoice (quotes and sales orders have no deleted flag) | Not listed |
| On a client that has **no user** | Not listed: there is nobody to give it to |

The repair runs in **one transaction** (all or nothing). A record whose owner changed after the list was made is skipped,
and running it twice changes nothing the second time. It does **not** write the invoice audit log, so keep the command's
output as the record of what changed. `RecordOwnerRepair` holds the queries; `RepairRecordOwnersCommand` is the command.

**Tested** on in-memory SQLite with virtual data (`RecordOwnerRepairTest`, 7 tests): what is found and what is not, the
repair, a second run, a record changed since it was listed, and the command with and without `--force`. Breaking any of
three guards (the listed-owner check, the inactive-owner rule, the deleted-invoice filter) makes tests fail. **Not run
against MySQL or a live database:** do the dry run first.

## What it does not do

- **The two controller edits have no automated test** (the invoice edit and the sales-order edit). The rule classes and
  the invoice service are tested; the wiring was checked by reading it.
- Only the edit forms were changed. Copying a quote already uses the target client's user (checked); the other ways a
  record gets a client (converting a quote to a sales order or invoice, copying an invoice) were not reviewed.

## Tests

`PostedClientIdTest`, `ClientActiveUserTest`, `SalesOrderOwnerFollowsClientTest`, two new `InvServiceTest` cases (a client
move writes the new owner; an unchanged client leaves it alone), `UserClientAssignmentRuleTest` (including that the entity
declares the unique index), and the earlier `QuoteOwnerFollowsClientTest` and `QuoteEditClientTest`.

## Check it by hand

Use made-up test clients, one with a user and one without. Move a test invoice, then a test sales order, to the client
with a user: it saves, and that user sees it as a guest. Move one to the client with no user: it is refused with the
warning. In the assign screen, try to give a client that already has a user to a second user: it is refused.
