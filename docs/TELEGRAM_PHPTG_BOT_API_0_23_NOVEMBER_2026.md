# phptg/bot-api 0.23 and the swapped chat id (November 2026)

## What happened

[`phptg/bot-api`](https://github.com/phptg/bot-api) 0.23 (released 10 November 2026) made the `chatId`
parameter of `sendMessage()`, `sendDocument()`, `sendLocation()`, `sendPhoto()` and the other send methods
optional and **moved it after the required parameters**. `sendMessage(chatId, text)` became
`sendMessage(text, chatId = null)`.

`composer.json` allowed `>=0.23.1` and the lock file pinned 0.23.1. Three calls still passed the chat id
first, by position:

- `TelegramHelper::sendMessage()`, used by the low-stock and gateway-sandbox alerts;
- the outstanding-balance reminder in `InvRecurringCronService`;
- `TelegramController::processTestMessage()`, the "Hello World" test message on the settings tab.

Both arguments are strings, so nothing failed at the call site. The chat id went out as the **message text** and
the message as the **chat id**, and Telegram answered "chat not found". The two calls that already used named
arguments, `sendDocument()` and `sendLocation()`, were unaffected.

## Why nothing caught it

The tests mock the library, so they passed. And nothing required the update to be reviewed: `composer.json` allowed
any later version, `renovate.json` auto-merges everything except major updates, and for a `0.x` package a minor
release is allowed to change signatures.

## The fixes

| What | Where |
|---|---|
| Name the arguments: `sendMessage(text: $text, chatId: $chatId)` | `TelegramHelper`, `InvRecurringCronService`, `TelegramController` |
| `TelegramHelper` takes an optional transport, so a test can read the request that is really sent | `TelegramHelperSendMessageTest` (fails with the old call: `chat_id` becomes the message text) |
| Pin the range: `^0.23.1`, which accepts 0.23.x and refuses 0.24.0 | `composer.json` |
| Do not auto-merge minor or major updates of this one package; they still get a pull request | `renovate.json`, a `packageRules` entry (patches still auto-merge) |

## Rules to keep

- **Call library methods with named arguments.** Positional calls to a `0.x` library break silently when a
  parameter moves.
- **Take a new minor of a `0.x` package by hand**, with its changelog open.
- Tests for code that talks to Telegram should read the real request through a fake transport
  (`Tests\Testo\Support\RecordingTelegramTransport`), not assert on a mock.

## A trap when testing this library

The test setup enables `DG\BypassFinals`, which strips `readonly` from classes. The library's own JSON parser
(`Update::fromJson()`) reads **only `readonly` properties**, so under test it sees no constructor arguments and
fails. In production it works. Tests build `Update`, `Message` and `ChatMemberUpdated` directly instead.

## Related

- [Telegram, November 2026](TELEGRAM_NOVEMBER_2026.md)
- [Minimal reminder](TELEGRAM_MINIMAL_REMINDER_NOVEMBER_2026.md)
- [Typed mocks in tests, and what CI Psalm reports](TEST_MOCK_TYPING_AND_CI_PSALM_NOVEMBER_2026.md)
