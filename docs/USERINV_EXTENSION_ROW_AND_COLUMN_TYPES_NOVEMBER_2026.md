# The user-inv extension row and Cycle column types (November 2026)

`user_inv` is the extension table of `user`: one row per user, holding the user's type (administrator or
not), whether the account is active, language, consents and the observer's own preferences. It has a unique
index on `user_id`.

## One place builds a new signup's row

Signup, HomeCare signup and the OAuth button each built the same inactive row by hand: five lines, with the
rule "user 1 is the administrator" written out each time. `UserInvRepository::createInactiveFor()` now owns
it:

```php
$userInv = $repository->createInactiveFor($user, 'English', $consentPeriodicInvoice, $consentTelegram);
```

- the row is created **inactive**; confirming the emailed link, or an administrator, activates it;
- `type` is `UserInvRepository::TYPE_ADMIN` (0) for user 1 and `TYPE_NOT_ADMIN` (1) for everyone else;
- a user who already has a row gets that row back **untouched**, because `user_id` is unique.

The other places that create rows (the webshop order service, the console create command, the demo seeder,
the mailer helper, the items command and `UserInvController::add`) set other fields and still do it
themselves.

## The row is created before the role is assigned

In the generic signup the row used to be saved as a side effect of **building the email body**, after
`assignRoleAndVerify()` had already looked for it to link it to RBAC (`user_rbac_link`). That lookup always
found nothing, so the link was only made later, when the confirm link was pressed.

`SignupController::signup()` now creates the row right after the user, so the link is made at signup, and
the body builders only build. A test pins this order and was checked to fail when the order is reversed. In
the admin user list this shows as the green RBAC link on a signup that has not been confirmed yet.

## Eleven stray brackets in column types

`#[Column(type: 'string(34))')]` on `UserInv.iban` had an extra `)`, and ten more columns did the same
(`'datetime)'`, `'longText)'`, `'text)'` in `Token`, `ProductImage`, `Upload` and `FromDropDown`). Cycle
reads a type with an unanchored pattern, so each was **already parsed as the corrected type** and no table
differed. The eleven were fixed after parsing the old and new strings with Cycle's own pattern and getting
identical results, and `Tests/Testo/Infrastructure/EntityColumnTypesTest.php` now scans every column type
for unbalanced brackets so a typo cannot return unnoticed.

## What was left alone: the `type` default of 0

The column declares `default: 0`, which means **administrator**. It is not only a label: the recurring-
invoice cron selects administrators with `getType() === 0`, and the quote URL key and sales orders require
`getType() == 1`. A row inserted without a type would take the administrator default.

It was not changed, for two reasons. The live schema is cached in `runtime/schema.php`, and the next
rebuild runs `SyncTables`, which would **alter the live column**. And the six creators listed above need
reviewing first, in case one relies on today's behaviour. Every signup path now sets the type explicitly
through `createInactiveFor()`.

To change a column on purpose: set `BUILD_DATABASE` in `.env`, change the entity, delete
`runtime/schema.php`, reload a page so it rebuilds, then clear `BUILD_DATABASE` again (see the comment above
`schema-providers` in `config/common/params.php`). Back up first.

## Related

- [Signup confirmation link](SIGNUP_CONFIRMATION_LINK_NOVEMBER_2026.md)
- [Unconfirmed signup cleanup](UNCONFIRMED_SIGNUP_CLEANUP_NOVEMBER_2026.md)
- [Test mock typing and CI Psalm](TEST_MOCK_TYPING_AND_CI_PSALM_NOVEMBER_2026.md)
