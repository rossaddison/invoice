# Peppol Access Point — Settings Redesign, Message Log Search, Public Status Page, Regional Survey — August 2026

## Summary

Follow-on to the Storecove go-live testing session, driven by direct
user feedback: "the assembling of different third party providers needs
to change... we need to integrate a more globally representative third
party provider perspective like the payment gateways." Four pieces of
work, each building on the last, all shipped and merged the same day.

## 1. Settings screen rebuilt to match Online Payments (PR #1131/#1132)

The Storecove settings screen conflated two different things:
`peppol_access_point_provider` (a general Peppol routing setting
`PeppolSendServiceRouter` reads regardless of which provider it
resolves to) was buried inside a tab visually branded "Storecove," with
field labels lifted straight from Storecove's own doc section numbers
("1.1.4. Create a sender - Legal Entity Country"). Oxalis had no card
at all, even though the provider dropdown offered it as a choice.

Rebuilt to match `partial_settings_online_payment.php`'s multi-gateway
shape: one master card holds the provider select, and each provider
gets its own card, shown/hidden by that select. The one real
difference from payment gateways: Peppol providers are mutually
exclusive (only one Access Point ever sends), not independently
enable-able, so there's no per-card enable checkbox — the master select
is the only switch, and exactly one card is ever shown.

- Tab renamed `storecove` → `peppol_access_point` throughout (no longer
  branded after one specific provider).
- Storecove's fields kept, relabeled in plain English; doc-reference
  links moved to small secondary links instead of being baked into the
  label text.
- New Oxalis card shows its `.env` variables read-only
  (`OXALIS_BASE_URL`, `PEPPOL_SENDER_ID`, `PEPPOL_SML_ZONE`,
  `PEPPOL_SMP_BASE_URL`) — honest that there's nothing DB-backed to
  save for it, rather than faking parity with Storecove.
- Caught and fixed a double-encoding bug while writing the Oxalis
  table: `Html::tag()` already encodes a plain string by default;
  wrapping it in `Html::encode()` first double-encodes it — the same
  bug class `docs/LANGUAGE_FLAG_DROPDOWN.md` had previously documented
  fixing in this same settings-view family.
- All 30 non-English locales backfilled with the new/changed keys via
  `GeneratorGoogleTranslateController::googleTranslateAllLocalesDiff()`
  (user-triggered — this requires an authenticated session and real
  Google Cloud credentials, neither available to drive directly).

## 2. Peppol/AS4 message logs wired up + made searchable (PR #1133)

Comparing this app's tooling against Storecove's own "Invoices
Received" dashboard screen (search fields above a filterable table)
turned up a genuine surprise: `PeppolMessageController` was the *only*
missing piece of an otherwise-complete stub. `routes-peppol-message.php`,
`resources/views/invoice/peppol/messages/{index,view}.php`, and
`PeppolMessageRepository::findAllPreloaded()`/`repoFind()` all already
existed — `/peppol/messages/*` were simply dead routes with no
controller to answer them. `as4/messages` had a working controller and
view, but no search/filter and no nav link either — both screens were
only reachable by typing the URL directly.

Added the missing controller, `filterCombined(array $queryParams)` on
both `PeppolMessageRepository` and `CycleOrmAs4MessageRepository` (same
shape as the existing `ProductRepository::filterCombined()`; AS4 column
names — `sender_party_id`, `receiver_party_id`, `state` — confirmed
against the live schema via `DESCRIBE as4_messages`, not assumed, since
Cycle flattens the embedded `As4Routing` properties with no prefix),
plain GET filter forms in both index views, and nav links neither
screen had before. Also fixed a real CSP violation found in the
pre-existing stub: the UBL XML "Copy" button used a raw
`onclick="navigator.clipboard..."` attribute, switched to the
established `data-action="copy-to-clipboard"` convention. Deliberately
not wrapped in an `OffsetPaginator` — it's `ReadableDataInterface`, not
`IteratorAggregate`, so the views' existing plain `foreach` would throw;
real pagination is a known follow-up.

## 3. Public status page + Front Page toggle (PR #1134)

Same idea as the existing `/gateway-status` page (which payment
gateways have passed a real sandbox check), applied to the two Access
Point providers `PeppolSendServiceRouter` can resolve to. Deliberately
**not** built on gateway-status's full machinery — a second
Cycle-managed SQLite database, a JSON source of truth, two console
commands, a weekly CI workflow. That pipeline earns its keep tracking
8+ gateways' SDK versions and sandbox pings automatically; for exactly
two rows, both derivable live from data this app already has, it would
be pure overhead.

Sandbox status is derived from genuine send history, not a synthetic
ping — neither provider exposes a side-effect-free health-check call
the way a payment gateway's balance/methods-list endpoint does:
Storecove shows "pass" when the currently-configured provider is
Storecove and a real `SENT` `PeppolMessage` exists (this session's own
confirmed sends); Oxalis stays "untested," matching
`PeppolSendServiceRouter`'s own documented honesty that it's never been
live in this deployment. `PeppolMessage` has no column recording which
provider actually sent a given row — both send services write to the
same table — so attributing an existing `SENT` row to "whichever
provider is currently configured" is the best signal available without
a schema change, noted explicitly in code rather than silently assumed.

`no_front_peppol_status_page` was added to Settings → Front Page,
gating the route itself (a `404`, not just hiding a nav link) the same
way `no_front_gateway_status_page` does, since this page can expose
which provider is configured. Seeded `0` (visible) in
`InvoiceInstallTrait` explicitly — unlike every other `no_front_*_page`
setting (which default `1`, hidden until the installer fills in real
content), this is a functional page with nothing to configure first.
That seed also incidentally fixes a latent inconsistency this pass
found: `no_front_gateway_status_page` was never seeded at all, and its
two visibility checks disagree on what "unset" means (the navbar's
`!= '1'` treats unset as visible; `site/index.php`'s homepage-link
check uses exact `== '0'`, which unset fails).

## 4. Regional Access Point survey (PR #1135)

Asked for a region-specific list of alternative providers, on the
explicit condition that only ones with a *definite, confirmed* sandbox
get included — "not wasting our time," the same lesson as
Robokassa/YooKassa in the payment-gateway precedent (no sandbox at all,
or one gated behind a real barrier, means it stays untested
indefinitely).

Real web research, not assumed: Storecove (already integrated) is
accredited across all four original Peppol territories (EU, Australia,
New Zealand, Singapore) plus expanding into Malaysia and CTC-clearance
countries (Italy, Poland, Romania, India) — most realistic regional
ground is already covered by the one integration this app has.
Tradeshift, Basware, and Pagero were checked and excluded: Tradeshift's
sandbox is only documented in their own support knowledgebase with no
self-serve signup path found on their own site; Basware's requires
going through their support team to provision; Pagero's ownership is
genuinely unclear as of this writing (a 2023-24 Vertex acquisition bid
was withdrawn after competing Thomson Reuters/Avalara offers).
**PeppolSoft** held up: a complimentary sandbox with no sales gate
("no contractual commitment required to begin" per their own site,
though it does require submitting a request-access form rather than an
instant self-serve signup), transparent $0.10/invoice pricing with no
subscription or minimum, and explicit UK coverage "launching soon,
ahead of the 2029 mandate" — directly relevant to the HMRC timeline
this project already tracks. PeppolSoft does have a real API ("a
structured API layer for sending and receiving," UBL/Schematron
validation) — though the marketing page doesn't publicly expose actual
endpoint specs or SDKs.

Added as a separate "surveyed, not integrated" section on
`/peppol-status`, deliberately not reusing the "Sandbox tested ✓" badge
— that badge specifically means this app sent a real message through
the provider, which isn't true for anything not wired into
`PeppolSendServiceRouter`. Not added to the Access Point Provider
settings dropdown either, for the same reason the rest of this whole
arc existed: never offer a choice with nothing real behind it.

## Verification

Every PR individually: `vendor/bin/psalm --no-cache` (full project) 0
errors, `php -l` clean on every changed file, `npm run type-check`
(`tsc --noEmit`) clean where TypeScript changed. `PeppolMessageRepository::mostRecentByStatus()`
and the AS4 filter column names were confirmed against the live local
schema (`DESCRIBE`, direct `SELECT`) rather than inferred from entity
annotations alone. Live-confirmed: the redesigned settings screen, the
searchable message logs, and the public status page were all
screenshotted working correctly against the real running app in the
same session.
