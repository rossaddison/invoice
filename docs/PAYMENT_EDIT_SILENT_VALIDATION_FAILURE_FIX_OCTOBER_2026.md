# payment/edit Silently Dropped Changes on Validation Failure (October 2026)

Reported as "the payment edit route won't let me adjust the date" —
the root cause turned out to have nothing to do with the date field
specifically. `PaymentController::handlePaymentEditBody()` branched on
`if (isset($params['errors']))`, but `$params['errors']` is
unconditionally initialized to `[]` in `edit()` just before this
method runs. `isset()` on an already-initialized empty array is
always `true`, so this branch ran on *every* submission, success or
failure alike — flashing "record.successfully.updated" and
redirecting to `payment/index` regardless of whether
`saveFormFields()` had actually persisted anything. The real
"show validation errors instead" branch right after it
(`if (null !== $form) { ... }`) was dead code in this flow: whenever
validation genuinely failed on any of `PaymentForm`'s required fields
(`payment_method_id`, `amount`, `note`, `inv_id`), `saveFormFields()`
correctly skipped the `PaymentService::savePayment()` call and
returned the invalid `$form` — but the controller never looked at
that return value. The user still saw a success message and a
redirect, with no indication anything had gone wrong and no record of
what was invalid. This matches the reported symptom exactly: change
the date, submit, get a "success" message, but the underlying record
never actually changes, because it was never saved in the first
place.

## Fix

- `handlePaymentEditBody()` now branches on `null === $form` instead
  of `isset($params['errors'])` — matching `saveFormFields()`'s own
  documented contract ("Only return the form if there are errors
  otherwise return null") and the already-correct equivalent check
  (`populateFromPostAndValidate()`) `add()` already uses a few lines
  above in the same class.
- On success (`$form === null`): unchanged behavior — process custom
  fields, recalculate the invoice, flash success, redirect.
- On failure (`$form !== null`): the form is now actually re-rendered
  with its real validation errors (`$params['errors']`/`$params['form']`)
  instead of silently redirecting as if nothing had gone wrong.

## Verified

`php -l` clean; Psalm clean on the changed file (single-file run); full
`vendor/bin/phpunit --filter Payment`: 217 passed, 540 assertions, no
regressions. No existing test exercised this exact branch — none
mocked `handlePaymentEditBody()`'s full request flow — so this was
caught and fixed by direct code reading rather than a failing test. A
dedicated regression test for the branch is still worth adding.
