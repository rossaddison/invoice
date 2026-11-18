# The own-profile form and self-service privilege changes (November 2026)

A security fix, found while building [verified Telegram linking](TELEGRAM_VERIFIED_LINKING_NOVEMBER_2026.md).

## What was wrong

`userinv/guest` is the page where a signed-in user edits their own details. It is open to every role that holds
`edit.user.inv`, and **observers hold it**. Its action passed the **whole posted form body** to
`UserInvService::saveUserInv()`, which was written for the administrator's form and applies, from that body:

- `type` (0 is administrator, which the recurring-invoice job uses to pick the administrator),
- `active` (whether the account is active),
- `all_clients` (whether the user is assigned every client),
- `user_id` (which user the row belongs to).

The profile form carries those four as **hidden inputs**, and a visitor can edit or add any field before posting.
Run against the real service in a throwaway test, a tampered post gave `type=0`, `active=true`, `all_clients=true`
and a chosen telegram chat id. So an observer could activate their own account, mark it administrator type, ask to
be assigned every client, or set the chat reminders went to. (This was shown against the service, not on a live
site.)

## The fix

`App\Invoice\UserInv\GuestProfileInput::forSave()` is applied to the body before it reaches the service. It takes
`user_id`, `type`, `active`, `all_clients` and `telegram_chat_id` from the **stored row**, whatever was posted. The
user's own fields (name, address, contact, language, VAT and bank details, consents) still pass through.

The chat id is protected for a second reason: `saveUserInv()` sets `telegram_chat_id` to `null` whenever it is not
posted, and the typed field is no longer on the form, so without this every save would have wiped a verified link.

`GuestProfileInputTest` covers each protected field, the pass-through of the user's own, a field left out of the post,
an administrator-type row staying administrator type, and a tampered post run through the **real**
`UserInvService`.

## If you run this site

Check for accounts changed through the hole. Only your administrator should be type 0, and `all_clients` should be
set only where you set it:

```
SELECT user_id, type, active, all_clients FROM user_inv WHERE all_clients = 1 OR type = 0;
```

## The rule

**Never hand a raw posted body to a service that applies privileged fields.** Either build the array from an
allow-list of the fields that form may change, or overwrite the privileged ones from the stored row, as here.

## Not audited

Only this form was looked at. Other self-service forms that pass a raw body to a shared save method should be
checked for the same pattern.

## Related

- [Telegram, November 2026](TELEGRAM_NOVEMBER_2026.md)
- [Two-factor and inactive-account gates](TWO_FACTOR_AND_INACTIVE_ACCOUNT_GATES_NOVEMBER_2026.md)
- [The user-inv extension row](USERINV_EXTENSION_ROW_AND_COLUMN_TYPES_NOVEMBER_2026.md)
