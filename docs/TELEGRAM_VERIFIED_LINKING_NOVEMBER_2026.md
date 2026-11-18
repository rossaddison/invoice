# Linking a Telegram chat by proof (November 2026)

## The problem

The profile form took a **typed** Telegram chat id. Anyone could enter anyone's: reminders would then go to a
stranger. And a person who never pressed Start in the bot could never be reached at all, because a bot cannot
message a chat that has not started it, whatever id is stored.

## How it works now

1. On their profile page (`/invoice/userinv/guest`, reached from **Settings, View** in the observer menu) the user
   presses **Link Telegram**. That is a POST; opening the address does nothing.
2. `TelegramLinkService::createToken()` makes a **one-time token** for their identity and the page shows
   `https://t.me/<bot>?start=<token>` behind an **Open Telegram** button. The bot's username comes from `getMe()`.
3. The user presses **Start** in Telegram. Telegram sends the webhook a message `/start <token>`.
4. `TelegramUpdateHandler` hands the token and the sender's chat id to `TelegramLinkService::link()`, which stores
   the chat id on the account the token was made for, and the bot replies that the Telegram is linked.
5. Back on the profile page the Telegram shows as linked, with an **Unlink** button.

The user must also tick the Telegram reminder box (`consent_telegram_outstanding`) to receive reminders. Linking
and consent are separate on purpose.

## The token

| | |
|---|---|
| Storage | the existing `Token` entity, type `telegram-link` |
| Text | 32 characters from `Random::string()`: letters, digits, `-` and `_`, which is also exactly what Telegram allows in a start payload |
| Life | 15 minutes, measured from the server's own `created_at`, never from anything the sender supplies |
| Use | spent the moment it is read (renamed `already_used_token_<time>`), expired or not, so it cannot be tried twice |
| One at a time | making a new token spends an earlier unused one |

A first draft of the pattern accepted letters and digits only and would have rejected about two thirds of real
tokens; the tests caught it.

## What the bot understands

| Message | Effect |
|---|---|
| `/start <token>` | links this chat (valid), or says the link is invalid or has expired |
| `/start` | says how to link |
| `/stop` | unlinks this chat everywhere and says so |
| the person **blocks or leaves** the bot (a `my_chat_member` update to `kicked` or `left`) | unlinks this chat everywhere, silently |
| anything else | ignored |

Only a **private** chat is ever linked or unlinked; a group the bot is added to is ignored. "Unlinks everywhere"
means every account row linked to that chat loses the chat id **and** its reminder consent, and so does any
client record an administrator typed that chat id into.

## Where the code is

| | |
|---|---|
| `App\Invoice\Telegram\TelegramLinkService`, `LinkOutcome` | tokens, linking, unlinking |
| `App\Invoice\Telegram\TelegramUpdateHandler` | the bot's reactions to messages and blocks |
| `App\Invoice\UserInv\UserInvTelegramController` | the profile page's **Link** and **Unlink** actions (`/userinv/guest/telegram-link` and `-unlink`, behind the same permission as the profile page) |
| `resources/views/invoice/userinv/_form_guest.php`, `_telegram_link.php` | the Telegram box under the profile form, and the page with the link |
| `UserInvRepository::findByTelegramChatId()`, `ClientRepository::findByTelegramChatId()` | who to unlink |

The commands are acted on only for a request that passed the webhook's secret-token check; see
[the webhook fails closed](TELEGRAM_WEBHOOK_FAIL_CLOSED_NOVEMBER_2026.md). The profile form's typed field is gone,
and the profile save can no longer set the chat id at all; see
[the profile form fix](OWN_PROFILE_FORM_PRIVILEGE_FIX_NOVEMBER_2026.md).

## Known limits

- **Send Telegram Invoice and Send PDF** use the chat id on the **client record** (Clients, edit), which an
  administrator types. A customer's own link sets the chat for their **reminders**. They are two fields.
- Chat ids typed in before this change are still stored and still used; they are not verified.
- The administrator's own chat id for alerts and the test message (Settings, Telegram) is still entered by hand;
  see [the settings tab](TELEGRAM_SETTINGS_TAB_NOVEMBER_2026.md).
- The bot's replies are English constants.
- Starting a link with a working bot needs a real Telegram connection, so that one path is checked on the live
  site, not by a unit test. Everything around it is tested.

## Related

- [Telegram, November 2026](TELEGRAM_NOVEMBER_2026.md)
- [Minimal reminder](TELEGRAM_MINIMAL_REMINDER_NOVEMBER_2026.md)
- [Signup confirmation link](SIGNUP_CONFIRMATION_LINK_NOVEMBER_2026.md), which uses the same spend-on-read token rules
