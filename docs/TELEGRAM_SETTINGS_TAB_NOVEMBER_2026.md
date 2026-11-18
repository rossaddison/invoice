# The Telegram settings tab (November 2026)

`Settings, Telegram` is where an administrator sets the bot up. Its thirteen steps had grown out of order and out of
date; they are now **eleven steps in four sections**, each matched to what the code does. The file is
`resources/views/invoice/setting/views/partial_settings_telegram.php`, and all of its wording is in the
`telegram.tab.*` translation keys.

## The steps

| Section | Step | What it asks |
|---|---|---|
| **A. The bot and the webhook** | 1 | create the bot with @BotFather and disable group joining (`/setjoingroups`) |
| | 2 | paste the bot token (and how to revoke it if it leaks) |
| | 3 | the **Webhook Secret Token**, required: 32 or more random letters, digits, `-` or `_` |
| | 4 | set `APP_URL` in `.env` so the reminder's **View invoice** button works |
| | 5 | tick Enable Telegram, save, then **Register the webhook**; **Webhook info** shows what Telegram reports |
| **B. Your own alerts (optional)** | 6 | your chat id, found in the order that works: message the bot, switch the webhook off, open getUpdates, paste the id, save, then register the webhook again |
| | 7 | whether registering the webhook also sends the Hello World test to your own chat |
| **C. Customers** | 8 | customers link their own Telegram from their profile page; reminders also need their reminder box ticked |
| | 9 | the chat id for **Send Telegram Invoice** and **Send PDF** comes from the client record, typed by an administrator |
| **D. Telegram Payments (optional)** | 10 | the provider token, and the payment method recorded when a customer pays |
| | 11 | open any invoice and use **Send Telegram Invoice**; it goes to the chat id on the client record (step 9) |

The providers modal (the table of payment providers) is unchanged. See
[Telegram payment providers](TELEGRAM_PAYMENT_PROVIDERS.md).

## What was wrong before

- **Step 13 pointed at the wrong chat.** It said Send Telegram Invoice goes to "the chat set in step 7", which was
  the owner's own chat. It goes to the chat id on the **client's record**.
- **The step that registers the webhook again did not exist.** Switching the webhook off for getUpdates was a
  step; switching it back on was an undocumented side effect of the step 9 link.
- **The secret token was at the bottom and called "additional security".** It is required now, and is step 3.
- **A control that did nothing.** "Telegram Payment Notifications" is stored (default 0) but no code reads it; it
  was also rendered twice. It is gone from the tab. The stored setting is harmless.
- **Hard-coded English with literal `....` placeholders** in steps 1 to 6; the rest used translation keys.
- **Nothing said** how customers link, that reminders need `APP_URL`, or how to stop the bot being added to groups.

## Notes for whoever edits it

- Every setting keeps its field name (`settings[telegram_token]` and so on), so the settings save is unchanged. The
  secrets are `autocomplete="off"` password fields.
- `src/Invoice/Language/English/app_lang.php` is **generated from** `resources/messages/en/app.php`; the two had
  drifted (two keys the tab uses were missing from it). Edit `app.php` and keep both in step.
- `TelegramSettingsTabTest` renders the real partial: steps 1 to 11 in order, every saved field present, the dead
  control gone, password and autocomplete attributes, value encoding, the routes the buttons open, the webhook
  address, and that the providers modal is still there. A second test scans every translation key the partial
  uses and checks it exists in both language files.
- In the view, keep the type-block comment directly above the first statement and use `//` for helper comments:
  Psalm ties the `@var` lines to the statement that follows, and a second docblock in between breaks that.

## Related

- [Telegram, November 2026](TELEGRAM_NOVEMBER_2026.md)
- [The webhook fails closed](TELEGRAM_WEBHOOK_FAIL_CLOSED_NOVEMBER_2026.md)
- [The Webhook info page](TELEGRAM_WEBHOOK_INFO_PAGE_NOVEMBER_2026.md)
- [Verified linking](TELEGRAM_VERIFIED_LINKING_NOVEMBER_2026.md)
