# Guest quote and invoice lists: a filter narrows the guest's own list, it never replaces it — November 2026

## In one paragraph

A signed-in guest (a customer) sees the quotes and invoices of the clients assigned to them. The list filters in the
address (`?filterClient=`, `?filterInvNumber=`, `?filterQuoteNumber=` and the rest) were run as **separate queries over
every record** and then used **instead of** that list. Adding a parameter to the address therefore listed **any
client's** records. Fixed on 24 November: every filter is now a condition added to the guest's own scoped query, so it can
only narrow it. **This was a data exposure between clients**, found by reading the code; it has not been shown on a live
database.

## What was exposed

| List | Parameters | What an added parameter listed |
|---|---|---|
| Guest quotes (`/client_quotes`) | `filterQuoteNumber`, `filterQuoteAmountTotal` | Any client's quotes, drafts included (the status limit went with the scope) |
| Guest invoices (`inv/guest`) | `filterClient` | Any client id's non-draft invoices |
| Guest invoices | `filterInvNumber`, `filterCreditInvNumber`, `filterInvAmountTotal`, `filterInvAmountPaid`, `filterInvAmountBalance` | Any client's invoices that matched, drafts included |

The guest had to know or guess a number, an amount or a client id, all of which are easy to guess. The
sales-order guest list has no such filters. The same filtering fed the run-sheet PDF, so that was exposed too.

## The fix

- `QuoteGuestTrait::repoGuestClientsSentViewedApprovedRejectedCancelled()` takes the two filter values and `andWhere`s
  them onto the scoped query.
- `InvGuestTrait::repoGuestClientsPostDraft()` and `repoWorkerVisible()` take an optional `InvGuestFilter`;
  `narrowGuestQuery()` adds each condition with AND. A `filterClient` for a client that is not the guest's now matches
  nothing.
- `Trait\Guest` builds the list through one path, `guestScopedInvs()`. The unused `filterGuestClient()` was **removed**.
- The rule for any new list filter: **add a condition to the scoped query; never start a new `select()`**.

Behaviour that changed on purpose: several filters used together now **all** apply (the last one used to win), and the
quote filters now respect the status tab and the sort. "Empty or `0` means no filter" is unchanged.

## Tests

- `QuoteGuestListFilterScopeTest` (6) and `InvGuestListFilterScopeTest` (9) check the conditions added to a mocked query.
- `QuoteGuestListSqliteTest` (8) and `InvGuestListSqliteTest` (11) run the **real repositories against an in-memory
  SQLite database**, with virtual data (ids and numbers only): the guest sees only their clients' sent invoices; another
  client's number, client id or amount finds nothing; the status tab, credit notes, several filters at once and a
  worker's allocation keep the scope. A canary test shows the old unscoped methods still find another client's record in
  the same data, and putting the bug back makes three tests fail each time.

## Limits

- **SQLite, not MySQL.** `LIKE` on decimal amounts and number-to-text comparison can differ, so the amount filters
  (total, paid, balance) are the one thing to check on the real database. The project stays on its Cycle ORM / data-cycle
  stack; no MySQL test container was added.
- The controller's passing of the address parameters has no automated test.
- **Not yet checked on a live site.**

## Check it by hand

Two test clients with a test invoice each, both linked to one test guest, and a third client linked to nobody: the guest
sees both invoices; choosing one client narrows to that client; `?filterClient=` with the third client's id shows
nothing; the amount filters still narrow. On the quote list, `?filterQuoteNumber=` with the third client's quote number
shows nothing.
