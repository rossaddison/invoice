# Why Two `tsconfig` Files? — Plain-English Explainer

## The short version

`tsconfig.json` is the rulebook for almost every TypeScript file in this
app. `tsconfig.sw.json` is a second, separate rulebook for exactly **one**
file: `src/typescript/sw.ts`, the service worker. They can't be merged
into one, because that one file runs somewhere genuinely different from
every other file — not because anyone chose to organize it that way.

## THE WHAT

Think of a normal page script (`index.ts`, `product.ts`, all the rest) as
an employee working **on the shop floor**, with a till, a customer
counter, a phone — the normal tools of the shop. That's `tsconfig.json`'s
rulebook: it tells the type-checker "expect a shop floor — a till
(`document`), a counter (`window`), the usual."

`sw.ts` (the service worker) is a **completely different employee working
in the back office** — no till, no customer counter, none of that. But
the back office has its own equipment the shop floor doesn't: a safe
(`caches`), an intercom (`clients`), things the shop-floor rulebook never
mentions at all. That's `tsconfig.sw.json`: a rulebook written for the
back office specifically.

## THE WHY

You can't write ONE rulebook that correctly covers both rooms. Two real
problems if you tried:

1. **The shop-floor rulebook would wrongly tell the back-office worker
   "there's a till here, use it."** There isn't one. If the checker
   believed there was, it would happily approve code that crashes the
   instant it actually runs in the back office — the exact kind of bug a
   type-checker exists to catch, silently let through instead.

2. **Both rooms have something called "the front desk"** (`self`, in
   TypeScript terms) — but they mean *completely different things* by it.
   On the shop floor, "front desk" means the customer counter. In the back
   office, "front desk" means the intercom. Cramming both definitions into
   one rulebook doesn't average them out — TypeScript just refuses outright,
   because it can't have two conflicting meanings for the same word active
   at once. That's the literal error hit building this: `TS2451: Cannot
   redeclare block-scoped variable 'self'`.

So: two rooms, two genuinely different sets of equipment, two rulebooks.
Not a style choice — there's no single rulebook that's true for both at
the same time.

## THE WHEN

The trigger for ever needing a *third* separate `tsconfig` isn't "this
file is a bit different" — plenty of files are built slightly differently
(bundled a different way, output as a different file format) without
needing their own config. The trigger is specifically: **does this file
run somewhere with a genuinely different toolset than the shop floor?**

Right now, exactly one file does — `sw.ts`. Everything else, however it's
individually built or bundled, is still a shop-floor worker at heart, so
it stays under the one shared `tsconfig.json`.
