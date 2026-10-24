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
- `PaymentForm::$payment_date` gains `#[Required]`. It previously had
  no validation rule at all — harmless under normal use, since the
  field is only ever picked via the native `<input type="date">`
  picker, but any submission that bypassed that UI constraint (a
  hand-crafted request, JS disabled, the field simply missing from
  the body) reached `PaymentService::savePayment()`, where
  `DateTime::createFromFormat()` failing silently wrote `null` into
  `Payment::$payment_date`, a column declared
  `#[Column(type: 'date', nullable: false)]` — a DB constraint crash
  instead of a clean, recoverable validation error. With the branch
  fix above now actually surfacing form errors, this rule turns that
  edge case into the latter.

## The actual root cause (found after live retesting)

The two fixes above were real but insufficient on their own — live
retesting still showed no redirect and the date field blank on
redisplay. Reproduced directly against the real `FormHydrator`/
`Validator` stack (not mocked, unlike every existing test touching
this class) with a throwaway script: `populateAndValidate()` came
back `false` on an otherwise-valid submission, with
`payment_date` landing as `''` even though the posted value was a
real date string.

The culprit was `PaymentForm::$payment_date`'s own declared type —
`mixed`. `Yiisoft\Hydrator\TypeCaster\PhpNativeTypeCaster::cast()`
switches on each candidate type's name (`'string'`, `'int'`,
`'float'`, `'bool'`, `'array'`, plus matching against non-builtin
class types) and has no case at all for `'mixed'` — so a
`mixed`-typed property can never be successfully cast by this
Hydrator version; it silently returns `Result::fail()` and the
property is left at whatever its PHP default was. This had always
been true of this property — it just never mattered before, because
`payment_date` had no validation rule (so the failed hydration was
never checked) and the real save path uses the raw POST array
directly, not this temporary validation-only `$form`. Adding
`#[Required]` (above) was what first made the pre-existing hydration
failure actually surface as "payment_date cannot be blank" on every
submission.

Fixed by declaring the property as `string|DateTimeImmutable|null`
instead of `mixed` — the real union of what it's ever assigned,
matching `getPaymentDate()`'s own return type and `Payment::getPaymentDate()`'s
`string|DateTimeImmutable` return. `PhpNativeTypeCaster` handles this
union natively (a plain string from the posted body matches the
`string` arm directly; a `DateTimeImmutable` from `PaymentForm::show()`
matches via the class-instance check), confirmed via the same
throwaway script: `populateAndValidate()` now returns `true` and
`payment_date` hydrates to the real posted value.

## Verified

`php -l` clean; Psalm clean on all three changed files (single-file
runs); full `vendor/bin/phpunit --filter Payment`: 217 passed, 540
assertions, no regressions across all three fixes. No existing test
exercises the real `FormHydrator`/`Validator` stack against this
form — every existing test mocks `FormHydrator` outright — so none of
this would have been caught without live retesting and a direct
reproduction. A dedicated test against the real validator stack is
still worth adding.
