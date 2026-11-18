# The minimal Telegram reminder (November 2026)

## Why

Telegram stores a bot's messages in the clear: bots cannot use Secret Chats, which are the only end-to-end
encrypted chats, so Telegram (and anyone who gets into the chat) can read them. The payment reminder used to say
`Invoice #12 has an outstanding balance of 340.00. Please log in to make a payment.` It now says nothing about
the invoice.

## What the customer receives

| | |
|---|---|
| Text | `You have an invoice awaiting payment.` |
| Button | **View invoice**, a URL button to the invoice page, which asks for a login |
| Protection | sent with `protect_content`: it cannot be forwarded or saved from the chat |

If the button cannot be built (see `APP_URL` below) the text becomes
`You have an invoice awaiting payment. Please log in to view it.` and there is no button.

The message is built by `App\Invoice\Telegram\OutstandingInvoiceReminder::message()`, a pure class with its own
tests. A test also asserts that the text contains no digit, no `#` and no "balance".

## Who gets it, and when

`InvRecurringCronService::sendTelegramReminderIfNeeded()` sends it when the previous recurring invoice still has
a balance, the account ticked the Telegram reminder box on their profile (`consent_telegram_outstanding`), and
the account has a linked chat (see [verified linking](TELEGRAM_VERIFIED_LINKING_NOVEMBER_2026.md)). It runs from
the recurring-invoice cron.

## `APP_URL`

A URL button needs an absolute address, and the job runs without a request, so there is no host to take one
from. Set the site's public address in `.env`, without a trailing slash:

```
APP_URL=https://invoice.example.test
```

It reaches the code as the `app.baseUrl` parameter (`config/common/params.php`) through
`SettingRepository::getConfigAppBaseUrl()`. The button address is `APP_URL` plus the route path of
`inv/view` for that invoice, for example `https://invoice.example.test/en/inv/view/12`. Telegram refuses a whole
message whose URL button it cannot use, so an address that is blank, has no `http` or `https` scheme, has no
host, or is not a valid URL yields no button instead.

## Known limits

- The reminder runs from the **HTTP cron endpoint**. The console command `php yii invrecurring/process` cannot be
  built at all, on `main` as well: the console container has no `IdentityRepositoryInterface`. That is a separate,
  older fault.
- The two texts are English constants.
- The reminder is not edited or deleted once the invoice is paid.

## Related

- [Telegram, November 2026](TELEGRAM_NOVEMBER_2026.md)
- [phptg/bot-api 0.23](TELEGRAM_PHPTG_BOT_API_0_23_NOVEMBER_2026.md)
- [Verified linking](TELEGRAM_VERIFIED_LINKING_NOVEMBER_2026.md)
