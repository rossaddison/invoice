# HomeCare — Worker Allocation & Cleaning Route Data Flow (August 2026)

Scoped to the mechanism built in
[`PRODUCT_TAXONOMY_AND_IMAGES_FOR_WEBSHOP_AUGUST_2026.md`](PRODUCT_TAXONOMY_AND_IMAGES_FOR_WEBSHOP_AUGUST_2026.md)'s
sibling feature work — how a `Worker` ends up with a definitive, ordered
list of houses to clean. Not a full HomeCare data flow: client
signup/`Dwelling` creation, invoice generation itself, and payment are
separate flows, worth their own diagrams later rather than folded into
this one.

> The cleaning order is never directly edited. It's the timestamp order
> staff naturally produce by allocating one worker per invoice, one row
> at a time, down a list that's already sorted by street —
> `worker_allocated_at` just records when that happened.

```mermaid
flowchart LR
    Staff["Staff"]
    Worker["Worker<br/>(observer role)"]

    P1(("P1<br/>Order Streets"))
    P2(("P2<br/>Allocate Worker<br/>to Invoice"))
    P3(("P3<br/>Resolve House<br/>from Invoice"))
    P4(("P4<br/>View My Round"))

    D1[("D1<br/>Family (street)")]
    D2[("D2<br/>Inv")]
    D3[("D3<br/>Client")]
    D4[("D4<br/>Dwelling")]

    Staff -->|"drag-drop reorder"| P1
    P1 -->|"write street_sort_order"| D1

    D1 -->|"street order<br/>(the list staff walks)"| P2
    Staff -->|"pick worker, one row at a time"| P2
    P2 -->|"write worker_id +<br/>worker_allocated_at = now()"| D2

    D2 -->|"client_id"| P3
    D3 -->|"dwelling_id"| P3
    D4 -->|"house_number, lat / long"| P3
    D1 -->|"street name"| P3
    P3 -->|"house-number column"| Staff

    Worker -->|"log in, GET inv/guest"| P4
    D2 -->|"WHERE worker_id = me<br/>ORDER BY worker_allocated_at ASC"| P4
    P4 -->|"round, in visit order"| Worker
```

## Reading it

- **`D1`'s only consumer that matters here is `P2`.** `street_sort_order`
  (set via `family-street-order.ts`'s drag-and-drop, `family/street_order.php`)
  exists to control the order Staff *sees* invoices in on `inv/index`
  while allocating — nothing downstream reads it directly. It works on
  the worker's route only indirectly, by shaping the order `P2`'s writes
  happen in.
- **`P2`'s write is the one this whole feature turned on.**
  `Index::setWorker()` sets `worker_id` **and** stamps
  `worker_allocated_at = now()` in the same call — re-stamped on every
  reassignment, cleared back to `null` on unassignment. No separate
  "cleaning order" field exists; this timestamp *is* the order.
- **`P4`'s query is the payoff.** `InvRepository::repoWorkerVisible()`
  sorts by `worker_allocated_at` ascending instead of the app-wide
  `id desc` default — earliest-allocated first. As long as Staff worked
  down a street-ordered list in `P2`, the timestamps `P4` sorts by
  already follow the route, with no separate sequence number for anyone
  to keep in sync by hand.
- **`P3` is read-only plumbing, not part of the ordering mechanism.**
  It's the existing `Inv → Client → Dwelling → Family` chain
  (`InvsDwellingHouseNumberColumnTrait`) that turns an invoice into a
  displayable address — included here because both `P2` (Staff, via the
  house-number column next to the worker-assignment dropdown) and
  implicitly `P4` (Worker, via the same chain rendered on their own
  `inv/guest` rows) depend on it, even though it doesn't write anything.

## Out of scope here

Where `D3`/`D4` rows themselves come from (client signup, dwelling
creation/geocoding), invoice generation (`P1`'s missing upstream
neighbor), and what a Worker does once they're looking at a house
(marking a job done, flagging `do_not_send` on an on-site problem) —
each is a real, separate data flow worth its own diagram.
