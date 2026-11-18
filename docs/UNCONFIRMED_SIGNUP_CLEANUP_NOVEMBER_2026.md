# Cleaning up unconfirmed signups (November 2026)

Both signup forms create the user, identity, token and an inactive `user_inv` row **before** the email is
confirmed. A bot wave therefore leaves a pile of rows that also keep each login and email address taken.
`php yii signup/cleanup-unconfirmed` removes them.

## Usage

```
php yii signup/cleanup-unconfirmed                        # list what would be removed; deletes nothing
php yii signup/cleanup-unconfirmed --force                # remove it
php yii signup/cleanup-unconfirmed --older-than-hours=72  # only signups older than 72 hours (default 24)
```

It is a **dry run unless `--force` is given**. The minimum is 1 hour, because the confirmation link lives
for one hour and nothing younger may ever be removed.

## What counts as a candidate

An account is listed only when **all** of these hold (`App\Auth\UnconfirmedSignupCleaner`):

1. it has an email-verification token (either signup flow) that was **never clicked**. A clicked token is
   renamed `already_used_token_<time>`, so it no longer matches;
2. that token was issued before the cutoff;
3. it has **no active** `user_inv` row;
4. it is not user 1 and holds no `admin` role in `yii_rbac_assignment`.

Accounts an administrator created or deactivated carry no unclicked verification token, so they are never
candidates.

## What is removed

Each candidate is removed in **one transaction**, so a failure leaves that user whole and the command goes
on with the rest. The rows are deleted from `home_care_pending_signup`, `user_rbac_link`, `user_inv`,
`yii_rbac_assignment`, `token`, `identity` and `user`. The method refuses user 1 outright
(`LogicException`).

## A safe way to run it

1. Take a fresh database backup. A deletion cannot be undone.
2. Run the dry run and read the list. Look for accounts you recognise (test accounts) and for the shape of
   a bot wave: many near-identical random logins created close together.
3. Run it again immediately before `--force`, so the list you approved is the list that is deleted.
4. Run with `--force`, then run the dry run once more: it should list nothing.
5. Check the user list in the admin screens: active accounts and administrators are untouched.

## Known limits

- A link clicked **after it has expired** burns its token, so the account is not a candidate: it cannot be
  told apart from a real user an administrator deactivated.
- The queries use the production table names; the unit tests run them against an in-memory SQLite copy of
  only the tables involved (`Tests/Testo/Auth/UnconfirmedSignupCleanerTest.php`).

## Related

- [Signup hardening](SIGNUP_BOT_HARDENING_NOVEMBER_2026.md)
- [Signup confirmation link](SIGNUP_CONFIRMATION_LINK_NOVEMBER_2026.md)
- [The user-inv extension row](USERINV_EXTENSION_ROW_AND_COLUMN_TYPES_NOVEMBER_2026.md)
