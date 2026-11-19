# The self-service forms audit (November 2026)

## Why it was done

[Own profile form privilege fix](OWN_PROFILE_FORM_PRIVILEGE_FIX_NOVEMBER_2026.md) found one form that passed the
whole posted body to a service that applies privileged fields (`type`, `active`, `all_clients`, the user the row
belongs to). That page said "only this form was looked at". This audit looked at everything else a user without
`edit.inv` can reach, and at every public route.

Method: list every route by the permission that gates it (`RoutePermission::check(...)` in
`config/common/routes/`), then read each POST action for two patterns:

1. the raw posted body, or an id from the URL, reaching a service or entity that applies privileged fields, with
   no check that the row belongs to the caller;
2. a public route whose "secured by X" comment or doc is not backed by a check in the code.

Eleven findings. Ten were fixed and merged (PRs 51 to 60); one was accepted as it is.

## The findings

| # | Where | Who could do what | Fix | PR |
|---|---|---|---|---|
| 1 | `setting/listlimit/{setting_id}/{limit}` (any role with `view.inv`) | write any value into **any setting row**: the Telegram token, webhook secret, SMTP credentials. Also a GET | writes only `default_list_limit`, and only a whole number from 1 to 1000 | 51 |
| 2 | `salesorder/edit` (a customer, for their own orders) | `saveSo()` applied `client_id`, `group_id`, `user_id`, `quote_id`, `inv_id`, `status_id`, `discount_amount`, `url_key` and `password` from the posted body: move the order to another client, change its status, give it a discount | `SalesOrderEditInput::forObserver()` takes those from the stored order; staff are not filtered | 52 |
| 3 | `userinv/guestlimit`, `guestStickyNavbar`, `guestStickyGridHeader` | change another user's page size or sticky-bar preference (the `userinv_id` was in the URL) | only the signed-in user's own row | 53 |
| 4 | `clientpeppol/add`, `edit`, `delete` (observers hold `edit.client.peppol`) | add, change or delete the Peppol settings, and so the e-invoice endpoint, of **any client**; `edit` also applied a posted `id` and `client_id` | `mayAccessClient()`: staff any client, anyone else only the clients assigned to them; a posted `id` is dropped and the row keeps its own `client_id` | 54 |
| 5 | `quote/quoteToSoConfirm` (`view.inv`) | create a sales order from any quote, for any client named in the request, and mark the quote converted | the route needs `edit.inv`; the only button that calls it was already staff-only | 55 |
| 6 | `quote/approve` (`view.inv`) | approve a quote and create its sales order with just the quote's `url_key` | the same observer check `quote/reject` uses; **the guest's own confirmation still works** | 56 |
| 7 | `/shop/checkout` (public) | type an existing customer's email and be **signed in as that customer**, past their password, two-factor check and email verification | checkout no longer signs anyone in | 57 |
| 8 | `/paymentinformation/robokassaWebhook` (public) | with `gateway_robokassa_password2` blank (the default) the signature `md5(OutSum:InvId:password2)` is computable by anyone, and the whole balance was recorded as paid | a blank password or signature never verifies, and the payment is re-checked with Robokassa (`OpStateExt`) before it is recorded | 58 |
| 9 | `/as4/receive` (public) | documented as secured by message signing; nothing verifies a signature, and the sender is the unsigned `senderPartyId`, so anyone who knew a registered client's public Peppol endpoint id could post a forged Order or Invoice | **interim**: the caller must present `AS4_RECEIVE_SECRET`; with none set every call is refused | 60 |
| 10 | `/peppol/inbound/delivery` (public) | mark any outbound e-invoice `DELIVERED` by knowing its `messageId` | the caller must present `PEPPOL_INBOUND_SECRET`; with none set every call is refused | 59 |
| 11 | public HomeCare signup, "I have paid cash" | confirming the emailed link records the full amount as paid cash, with nobody checking cash changed hands | **accepted as it is**: the owner decided to leave it | none |

The corrected documents are in PR 61.

## What was checked and found sound

- The HomeCare allocation actions (`allocateSide`, `setCrew`, `settleAllocation`, `cardAllocation`,
  `delegatePullPoint`) and `setDoNotSend` check who the user is inside the action.
- `paymentpeppol/add`, `salesorderitem/edit` and `quote/reject` check the observer.
- The public `scan/{token}` routes are rate-limited and token-based by design.
- The shop cart takes prices from the catalogue and removes a negative quantity.
- The recurring-invoice cron route needs bearer authentication.
- Thirteen payment gateway webhooks: Stripe, Adyen, GoCardless, Paystack, Razorpay, Mercado Pago, Square and
  Checkout.com refuse a blank secret and compare in constant time; PayPal verifies through PayPal's own API; Mollie,
  TrueLayer and BitPay re-check the payment themselves; YooKassa checks the sender against its published IP list.
- The signup routes were hardened earlier in the month (see [signup confirmation link](SIGNUP_CONFIRMATION_LINK_NOVEMBER_2026.md)).

## If you run this site

1. Pull and restart (`rc-service apache2 restart`).
2. **Peppol through Oxalis:** set `PEPPOL_INBOUND_SECRET` in `.env` (32 or more random characters) and add the same
   value to `oxalis.as4.receipt.callback.url` as `?secret=...`, or send it as the `X-Peppol-Callback-Secret` header.
   Until then no delivery is marked `DELIVERED`.
3. **AS4:** set `AS4_RECEIVE_SECRET` in `.env` and give a bilateral partner `https://<host>/as4/receive?secret=...`.
   Until then every inbound AS4 message is refused. A standard Peppol access point finds your endpoint through the
   SMP and cannot add a secret, so its traffic is refused too.
4. **Webshop:** check the `no_front_webshop_page` setting. While it is off the public `/shop` was exposed to
   finding 7 until step 1. A first-time buyer now has to sign in (Forgot password) to see an invoice.
5. **Robokassa:** if you do not use it, look for payments with driver `Robokassa` and references like
   `<number>-robokassa-<id>`; before PR 58 anyone who knew an invoice's `url_key` could have recorded one. If you do
   use it, make one genuine test payment: the new re-check calls Robokassa.
6. **Settings:** compare the secret settings (`telegram_token`, the webhook secret token, SMTP and gateway keys)
   with what you expect. Finding 1 let any signed-in non-admin overwrite one.

## Known limits

- **Finding 9 is an interim.** The secret keeps strangers out. It does not prove which partner sent a message.
  `As4SignatureVerifier` is still not called on the inbound path, and it takes the public key from the message's own
  certificate, so even wired in it would prove only that the message was not altered. The real fix verifies each
  message's signing certificate against the registered partner's.
- **Finding 2:** the edit form still shows a customer the client, status and discount fields; their values are now
  ignored on save. Hiding them is a separate view change.
- **`quote/approve`:** an administrator or worker holding the key can no longer approve on the guest's behalf, as
  with `reject`.
- **The webshop** does not send a confirmation email or one-time sign-in link; that would restore the straight-to-invoice
  experience safely.
- The route `clientpeppol/view` points at an action that does not exist.

## How good the evidence is

Not equal across the fixes. For most of them a new test fails on the old code. Weaker cases, in the interest of being plain:

- **Finding 10 and 9 (interim):** the old controllers have no secret parameter, so the new tests error against them
  rather than failing on a missed check. Before the change those routes accepted any caller.
- **Finding 4:** the refusal paths and the delete path are tested; the `edit` save path (posted `id` and `client_id`
  dropped) is not.
- **Finding 7:** the three login expectations in `OrderServiceTest` now require no sign-in; the controller's new
  redirect and message have no test.
- **Finding 1, 2, 7 and 8 were read in code**; only the Robokassa forged signature (`md5('1:1:')` verifying as true
  against the real class) was run directly.
- Each PR was merged straight after it was opened, so none was seen to pass CI before merging.

## The pattern behind it

Almost every finding is the same mistake in two forms: a comment, a document or a test said a check existed, and the
code did not have it.

- The AS4 roadmap listed `As4SignatureVerifier::verify()` as a step of the inbound flow; it was never wired in.
- `peppol/inbound/delivery` said "secured by network perimeter" and was public.
- A webshop test asserted that checkout signs in as the existing account, so the hole passed its tests.
- The Telegram webhook (see [it fails closed](TELEGRAM_WEBHOOK_FAIL_CLOSED_NOVEMBER_2026.md)) skipped its only check on
  a blank secret; so did the Robokassa one.

Rules to keep:

- **A route moved out of `RoutePermission::invoiceGroup()` needs its own check in the code**, and a test that fails
  without it. A comment saying it has one is not evidence.
- **A secret that can be blank is a secret anyone can supply.** Refuse when it is blank, then compare in constant time.
- **A row id from the URL is not an authorisation.** Check the row belongs to the caller, or fix the id to the
  caller's own row.
- **A service that applies every field it is given is for staff forms only.** A customer-facing form passes it a body
  whose protected fields have been replaced by the stored ones.
- **Do not log a caller in because of a form field.** An email address is not a credential.

## Where the code is

| | |
|---|---|
| `SettingToggleController::listlimit()` | finding 1 |
| `App\Invoice\SalesOrder\SalesOrderEditInput` | finding 2 |
| `UserInvController::guestlimit()` and `setGuestBooleanPreference()` | finding 3 |
| `ClientPeppolController::mayAccessClient()` | finding 4 |
| `config/common/routes/routes-quote.php`, `Quote\Trait\QuoteToSo::approve()` | findings 5 and 6 |
| `App\Api\OrderService`, `Webshop\Checkout\CheckoutController` | finding 7 |
| `RobokassaSignatureService`, `RobokassaWebhookHandler` | finding 8 |
| `App\Invoice\As4\As4ReceiveGuard` | finding 9 |
| `App\Invoice\Peppol\PeppolInboundGuard` | finding 10 |

## Related

- [Own profile form privilege fix](OWN_PROFILE_FORM_PRIVILEGE_FIX_NOVEMBER_2026.md), the first of these
- [Telegram webhook fails closed](TELEGRAM_WEBHOOK_FAIL_CLOSED_NOVEMBER_2026.md)
- [July 2026 security hardening audit](SECURITY_HARDENING_AUDIT_JULY_2026.md), with the November correction
- [Peppol and Oxalis connection](PEPPOL_OXALIS_CONNECT.md)
- [AS4 bilateral roadmap](AS4_BILATERAL_ROADMAP.md)
