# Telegram, November 2026: what changed and what to do

Between 16 and 18 November the Telegram integration was rebuilt around one idea: a bot message is stored by
Telegram in the clear (bots cannot use end-to-end encrypted chats), so the integration should say as little as
possible in it, and should only ever talk to a person who has proved the chat is theirs. Along the way four real
faults were found and fixed. This page is the map; each row links to the page with the detail.

| Change | Pull request | Page |
|---|---|---|
| `phptg/bot-api` 0.23 moved `chatId`: three calls sent the chat id as the text. Fixed with named arguments; the dependency is pinned and Renovate no longer auto-merges it | #43, #44 | [phptg/bot-api 0.23](TELEGRAM_PHPTG_BOT_API_0_23_NOVEMBER_2026.md) |
| The payment reminder carries no invoice details, only a **View invoice** button | #43 | [Minimal reminder](TELEGRAM_MINIMAL_REMINDER_NOVEMBER_2026.md) |
| An observer could change their own `type`, `active` and `all_clients` from the profile form | #45 | [Profile form privilege fix](OWN_PROFILE_FORM_PRIVILEGE_FIX_NOVEMBER_2026.md) |
| A chat is linked by proof (a one-time `t.me` link), not by typing its id | #45 | [Verified linking](TELEGRAM_VERIFIED_LINKING_NOVEMBER_2026.md) |
| The webhook fails closed: no secret token, no updates acted on | #46 | [Webhook fails closed](TELEGRAM_WEBHOOK_FAIL_CLOSED_NOVEMBER_2026.md) |
| An admin-only **Webhook info** button, and Telegram's last error on that page | #47 | [Webhook info page](TELEGRAM_WEBHOOK_INFO_PAGE_NOVEMBER_2026.md) |
| The settings tab steps rewritten to match what the code does | #48 | [Settings tab](TELEGRAM_SETTINGS_TAB_NOVEMBER_2026.md) |
| Fourteen SonarQube findings fixed, one controller split | #49 | [SonarQube fixes](SONARQUBE_RETURNS_CLASS_SIZE_AND_TEMPORARIES_NOVEMBER_2026.md) |

## If you run this site: do these in order

1. **Set the Webhook Secret Token** (Settings, Telegram tab, step 3): 32 or more random letters, digits, `-` or
   `_`. It is required now. With none set the webhook refuses every update, and Telegram payments are not
   recorded until it is set. Telegram retries a refused update for a while, so set it soon; one it has given up
   on has to be recorded by hand.
2. **Set `APP_URL` in `.env`** to the site's public address with no trailing slash, for example
   `https://invoice.example.test`. Without it the reminder is plain text with no button.
3. `git pull`, then restart the web server.
4. On the Telegram tab, press **Register the webhook** (step 5). It registers the webhook with your secret and
   sends the Hello World test if step 7 is Yes. Then press **Webhook info**: it should show your address, no
   waiting updates and no last error.
5. Check for rows changed through the old profile-form hole:
   `SELECT user_id, type, active, all_clients FROM user_inv WHERE all_clients = 1 OR type = 0;`
   Only the administrator should be type 0, and `all_clients` should be set only where you set it.
6. Try the link yourself: on a profile page press **Link Telegram**, open Telegram, press Start. The bot should
   confirm, and the profile should show the Telegram as linked.

## What is still open

- **Send Telegram Invoice and Send PDF** read the chat id on the **client record**, which an administrator types.
  A customer who links their own Telegram sets the chat for their **reminders** only. The two are not joined up.
- The reminder is not edited or deleted once the invoice is paid.
- The webhook route has no rate limit, so a flood of forged requests only fills the log with refusal warnings.
- Chat ids typed in before the linking change are still stored and still work; they are simply not verified.
- The bot's own replies and the new settings texts exist in English only.
