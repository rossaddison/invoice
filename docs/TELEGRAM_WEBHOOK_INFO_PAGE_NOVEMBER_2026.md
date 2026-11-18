# The Webhook info page (November 2026)

## Where it is

- On the Telegram settings tab, the **Webhook info** button next to **Register the webhook** (step 5). It opens
  the page in a new tab.
- Directly: `/invoice/telegram/getWebhookinfo`.

It is for administrators. The settings tab (`setting/tabIndex`) and the page itself both require the
edit-invoice permission, which only the administrator role holds, so the button is only seen by, and only works
for, administrators.

## What it shows

It asks Telegram (`getWebhookInfo`) and prints what Telegram itself reports:

| Line | Meaning |
|---|---|
| **A red warning first** when no Webhook Secret Token is set | the webhook then refuses every update until one is set and the webhook is registered again |
| The webhook address | `https://<site>/en/telegram/webhook`; if there is none, the page says you are using `getUpdates`, which excludes a webhook |
| Pending Update Count | updates Telegram is holding for delivery. Refused deliveries show up here |
| **Last error**, with its time in UTC | for example `Last error: Wrong response from the webhook: 403 Forbidden (2026-11-18 13:05:11 UTC)`, in red; or `Last error: none reported by Telegram.` |
| A synchronisation error, only when there is one | Telegram failed to sync the webhook with its servers |

Telegram **keeps showing the last error until a later update is delivered successfully**, so an old
`403 Forbidden` from before the secret was set is expected to linger until the next good delivery; the page says
so under the error.

Text that comes from Telegram is escaped before it is printed.

## How it is built

`App\Invoice\Telegram\WebhookInfoReport::lines()` builds the lines from the library's `WebhookInfo` and whether a
secret is configured; the view only prints them. That keeps the wording and the edge cases testable:
`WebhookInfoReportTest` covers a healthy webhook, an error with and without a time, a time in another zone, a blank
or missing error message, no secret, no webhook and a synchronisation error.

The page used to print only the address and the pending count, so a webhook whose deliveries were failing looked
healthy.

## Related

- [The webhook fails closed](TELEGRAM_WEBHOOK_FAIL_CLOSED_NOVEMBER_2026.md)
- [Telegram, November 2026](TELEGRAM_NOVEMBER_2026.md)
- [The settings tab](TELEGRAM_SETTINGS_TAB_NOVEMBER_2026.md)
