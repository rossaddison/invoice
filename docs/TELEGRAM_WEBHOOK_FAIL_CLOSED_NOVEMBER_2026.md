# The Telegram webhook fails closed (November 2026)

## The weakness

The webhook (`/en/telegram/webhook`) has to be reachable by Telegram, so it has no login. The only thing that
tells a real delivery from a forged one is the **secret token**: the value given to `setWebhook`, which Telegram
sends back in the `X-Telegram-Bot-Api-Secret-Token` header of every delivery.

The check used to be `secretToken !== '' && header !== secretToken`. With the secret **blank** the check was
skipped and every request went through. Anyone who knew the address could then post:

- a fake `successful_payment` update, which `handleWebhookUpdate()` turns into a payment recorded against an
  invoice (`PaymentService::addPaymentViaPaymentHandler`);
- fake `/start <token>`, `/stop` and block updates, the commands that link and unlink a chat to an account.

## The fix

`App\Invoice\Telegram\TelegramWebhookGuard::refusal()` decides, and the answer is no unless the request proves
itself:

| Case | Result |
|---|---|
| no bot token in Settings | refused |
| **no secret token in Settings** | **refused** |
| secret set, header absent or wrong | refused (compared with `hash_equals`, so the comparison takes the same time however many leading characters are right) |
| secret set and header matches | allowed |

A refusal is answered `403 {"fail":"unauthorized"}` **for every reason**, so a caller learns nothing about how
the site is set up; the real reason goes to the log as `Telegram webhook refused: <reason>.`

### Why 403 and not 200

A 200 tells Telegram the update was delivered, so it is dropped: a genuine payment that arrived while the secret
was blank would be lost without a trace. A 403 makes Telegram **retry** and show the error in `getWebhookInfo`
(see [the Webhook info page](TELEGRAM_WEBHOOK_INFO_PAGE_NOVEMBER_2026.md)). Telegram retries "a reasonable number
of times" and then gives up, so this is a window, not a guarantee: if the secret is set and the webhook registered
again soon, waiting updates are delivered; one that Telegram has already given up on is not, and the payment
would have to be recorded by hand.

### Both ways of registering the webhook now pass the secret

- the **Register the webhook** button on the Telegram tab (`telegram/index`), and
- the `/telegram/setWebhook` route, which used to register the webhook with **no secret at all**, even when one was
  configured.

Both refuse to register, and say so, when no secret is set: a webhook registered without one would only have its
deliveries refused.

## If you run this site

1. Set a long random **Webhook Secret Token** (32 or more letters, digits, `-` or `_`; Telegram allows 1 to 256)
   and save the settings.
2. Pull and restart.
3. Press **Register the webhook** (Settings, Telegram, step 5), then **Webhook info**: no waiting updates and no
   last error.

Skipping step 1 means Telegram payments are not recorded until the secret is set. A webhook that was already
registered **with** a secret needs nothing further.

## Tests

`TelegramWebhookGuardTest` covers the decision, including a secret that is only a prefix of the right one and a
different case. `TelegramWebhookActionTest` drives the real action: a blank secret, a missing header, a wrong
secret and a missing bot token all give the same 403 and reach nothing, and the right secret gets past the gate.
Putting the old fail-open behaviour back fails four of them (`expected 403, got 200`).

## Known limits

- The route has **no rate limit**, so a flood of forged requests fills the log with refusal warnings. It cannot do
  harm now, but a limiter would stop the noise.
- A short or guessable secret defeats the purpose. The settings text asks for 32 or more random characters; the
  code does not enforce a length.

## Related

- [Telegram, November 2026](TELEGRAM_NOVEMBER_2026.md)
- [Verified linking](TELEGRAM_VERIFIED_LINKING_NOVEMBER_2026.md)
- [Stripe webhook signature verification](STRIPE_PAYMENT_GATEWAY_WEBHOOK.md) for how another gateway's webhook is verified
