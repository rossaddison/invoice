# Webshop — Headless Storefront Design (August 2026)

> **Status: all four steps below now have a v1.** This document
> originally captured a settled architecture before any code existed.
> Steps 1-2 (API-key auth, the two endpoints) are implemented on this
> side — see
> [`STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md`](STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md).
> Building the actual handoff surfaced a real blocker not anticipated
> here — `POST /api/orders` returning a bare `url_key` was never usable
> by a fresh customer, since viewing any invoice requires a real logged-in
> account — closed with a one-time login link instead (same doc). Steps
> 3-4 (the `webshop` repo itself, `c:\wamp64\www\webshop`) are also now
> built: product listing, session cart, checkout form, and the redirect
> handoff, verified end-to-end against a local dev server. See that
> repo's own `docs/DESIGN.md` for what's built vs. still open (TypeScript
> cart interactivity, live verification against a real deployed
> `invoice` instance). The design below is otherwise still the settled
> plan it always was.

## Why

This app (`invoice`) already integrates 17 payment gateways, a full
guest invoice-payment page, and every piece of checkout machinery a
storefront would need. The temptation when asked "should we add gateway
#18" is to keep hunting for one — but a long search that same week
(Klarna/VNPay/CHIP/BTCPay/UK-bank-APIs, see
`docs/` gateway write-ups and the discarded VNPay branch) kept running
into dead SDKs, KYB walls, or straight redundancy with gateways already
integrated. The better lever isn't gateway #18, it's a new *front door*
that reuses all 17 gateways already built — a storefront that hands off
to this app's own existing checkout rather than re-implementing payment
code a second time.

## Two repos, clear responsibilities

### `invoice` (this app) — stays the system of record

Gains a small, deliberately narrow new API surface:

- `GET /api/products` (+ single-product detail) — read-only catalog feed.
- `POST /api/orders` — creates a `Client` if one doesn't already exist,
  then an `Inv` + `InvItem`s from a finished cart, and returns that
  invoice's `url_key`.

**Needs real API-key auth first.** The existing `/api` group
(`config/common/routes/routes.php`, `Group::create('/api')`) only has
session/cookie RBAC via `RoutePermission::check()` — genuinely unusable
by a separate, externally-deployed application as it stands today. This
is the actual blocking prerequisite, not the storefront code itself.

### New sibling repo — `webshop` — the storefront

Scaffolded from `ddd-template` (`c:\wamp64\www\ddd-template`) — same
PHP/Yii3 stack, same DI/routing/config conventions already proven there,
same TypeScript build convention this app already uses (esbuild → IIFE)
for client-side cart interactivity.

**Deliberately no local database at all.** Product data is fetched live
via `invoice`'s new API; the cart lives in session, not a table. There is
exactly one source of truth (`invoice`'s own database) and zero
duplicated state to keep in sync. This is a genuinely unusual choice —
see "Stack decision trail" below — but it's the whole point of the
design, not an oversight.

## The handoff — the actual point of this design

1. Customer builds a cart in the storefront (TypeScript-driven
   add/remove/update, no full page reload).
2. Checkout form collects customer details (name/email/address).
3. Storefront calls `POST /api/orders` on `invoice`.
4. Storefront 302s the customer straight to `invoice`'s *existing* guest
   invoice-payment page for that `url_key` — the same page a normal
   invoice recipient already uses today, all 17 gateways included, zero
   new payment code written anywhere.

## v1 scope — deliberately minimal

- Flat product list + detail page.
- Session cart.
- One checkout form.
- No search/categories/filtering, no account system of its own.

These aren't missing by oversight — they're explicit future scope, kept
out of v1 so the first version proves the handoff works end-to-end
before anything else is layered on.

## Stack decision trail

Worth keeping if this gets picked up by someone else later, since the
obvious industry-standard answer was seriously considered and rejected
for a specific reason, not by default.

Pure Next.js/TypeScript was considered first — real research that same
session confirmed this genuinely is where the headless-commerce
ecosystem has moved (Medusa, Your Next Store, Swell all Node/TS), and
PHP-based lightweight/headless storefront projects are largely
abandoned. Rejected anyway, in favour of PHP+TypeScript, specifically
because *this exact codebase* already runs that combination successfully
end-to-end (`src/typescript/` → esbuild pipeline) — reusing a proven
convention and deployment story beats introducing a second, unrelated
Node/React ecosystem next to PHP for no strong reason. Confirmed further
by researching what other people are doing with Claude Code on
commerce-API integrations right now (Shopify's own AI Toolkit, Medusa's
Claude Code tutorial, various skill packs) — every one of those still
assumes the storefront owns its own database. The "zero local DB, pure
API pass-through to a pre-existing external checkout" shape isn't a
pattern borrowed from anywhere; it's bespoke to this project's
particular situation of already having a fully-built checkout to hand
off to.

## Status of the four steps

1. ~~API-key auth mechanism for the new `/api` endpoints on `invoice`.~~
   **Done** — `ApiKeyAuthMiddleware` + `ApiClient` persistence entity +
   `api-client/generate` console command. See the implementation doc
   linked above.
2. ~~The two new endpoints themselves (`GET /api/products`,
   `POST /api/orders`).~~ **Done** — `ProductsController`/
   `OrdersController`/`OrderService`, same doc.
3. ~~Scaffold the new `webshop` repo from `ddd-template`.~~ **Done** —
   `c:\wamp64\www\webshop`, trimmed hard (no Cycle/DB/Auth/RBAC).
4. ~~Product listing + cart + checkout form + handoff redirect.~~
   **Done (v1)** — verified end-to-end against a local dev server.
   `webshop`'s own `docs/DESIGN.md` has the full status, including
   what's still open (TypeScript cart interactivity, real deployment).

This document still exists to preserve the settled design decisions
above (the two-repo split, the "zero local DB" choice, the handoff
sequence) — historical record now that all four steps have a v1, not an
open task list.
