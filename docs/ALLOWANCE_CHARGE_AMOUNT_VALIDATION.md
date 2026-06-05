# AllowanceCharge Amount Validation

## What is an AllowanceCharge?

An **AllowanceCharge** is a line attached to a Peppol UBL invoice that represents
either a discount (allowance) or an extra cost (charge) applied at invoice level or
at individual line-item level.

Examples:
- A 20% trade discount on the whole invoice — an **allowance**
- A fixed £15 shipping fee — a **charge**
- A £10 handling surcharge per line item — a **charge**

---

## The Three Fields That Work Together

Every AllowanceCharge has three numeric fields:

| Field | Stored as | Meaning |
|---|---|---|
| `multiplier_factor_numeric` (MFN) | integer | The percentage to apply, e.g. `20` = 20% |
| `base_amount` | integer | The amount the percentage is applied to, e.g. `1000` = £1,000 |
| `amount` | integer | The resulting value that appears on the invoice |

### The Two Modes

**Fixed mode** (`MFN = 0`)
The user types an amount directly. The other two fields are irrelevant.
```
amount = whatever the user enters   (e.g. 15 for a flat £15 shipping charge)
```

**Percentage mode** (`MFN > 0`)
The amount is calculated from the other two fields.
```
amount = MFN × base_amount ÷ 100
```
For example: `20 × 1000 ÷ 100 = 200` (20% of £1,000 = £200 discount)

> All three values are stored as whole currency units (not pence), so `1000`
> means one thousand pounds, and `200` means two hundred pounds.

---

## The Problem Before This Change

The form let the user type whatever they liked into the `amount` box with no
checks at all. Nothing stopped someone from entering `999` as the amount while
also setting MFN to `20` and base amount to `1000` — even though the correct
value is `200`. The wrong number would be saved to the database and emitted
on the Peppol invoice, which could cause rejection by the receiving system.

---

## What Was Changed

Two validation rules were added to
`src/Invoice/AllowanceCharge/AllowanceChargeForm.php`.

They run automatically whenever the form is submitted, **before** anything is
saved to the database.

### Rule 1 — amount check

Attached to the `amount` field. It asks:

- If MFN is **zero** (fixed mode): is the amount at least 1? If not, error.
- If MFN is **greater than zero** (percentage mode): calculate the expected
  amount using `MFN × base_amount ÷ 100`. If what the user typed does not
  match, show the correct answer in the error message.

### Rule 2 — base amount check

Attached to the `base_amount` field. It asks:

- If MFN is **greater than zero**: is the base amount also greater than zero?
  If not, error — you cannot calculate a percentage of nothing.

---

## How the Yii3 `Callback` Rule Works

Yii3's validator library (`yiisoft/validator`) lets you attach validation rules
directly to form fields. Most built-in rules check one field in isolation, for example:

```php
#[Required]          // field must not be empty
#[Integer(min: 1)]   // field must be an integer of at least 1
```

But the `amount` field cannot be validated in isolation — whether it is correct
depends on what the user also typed into `multiplier_factor_numeric` and
`base_amount`. This is called **cross-field validation**.

### The `RulesProviderInterface` + inline closure approach

The form implements `RulesProviderInterface`, which tells Yii3's validator to call
`getRules()` on the form to collect extra validation rules. Inside `getRules()` the
`Callback` rule is constructed with an **inline anonymous function**:

```php
final class AllowanceChargeForm extends FormModel implements RulesProviderInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getRules(): iterable
    {
        return [
            'amount' => [
                new Callback(
                    callback: function (): Result {
                        $result = new Result();
                        $mfn    = $this->multiplier_factor_numeric ?? 0;
                        $amount = $this->amount ?? 0;

                        if ($mfn > 0) {
                            $base     = $this->base_amount ?? 0;
                            $expected = intdiv($mfn * $base, 100);
                            if ($amount !== $expected) {
                                $result->addErrorWithoutPostProcessing(
                                    sprintf('%d × %d ÷ 100 = %d', $mfn, $base, $expected)
                                );
                            }
                        } elseif ($amount <= 0) {
                            $result->addError(
                                $this->translator->translate(
                                    'allowance.or.charge.amount.fixed.must.be.positive'
                                )
                            );
                        }

                        return $result;
                    },
                    skipOnEmpty: true,
                ),
            ],
        ];
    }
}
```

**Why does this work?**

The anonymous function `function (): Result { ... }` is defined *inside* a method
of `AllowanceChargeForm`. PHP automatically binds the surrounding `$this` to the
closure — so `$this->amount`, `$this->base_amount`,
`$this->multiplier_factor_numeric`, and `$this->translator` inside the closure
all refer to the form's own properties. No extra parameters are needed.

The `TranslatorInterface` is passed into the form's constructor and stored as
`$this->translator`. The controller creates the form with
`new AllowanceChargeForm($this->translator)` (or via the `show()` static factory),
so the translator is available inside every closure.

**Why not use `#[Callback(method: 'someMethod')]`?**

The attribute-based `method:` approach relies on PHP reflection to bind a method
name to a closure after the class is instantiated. In practice, within this
framework stack, the reflection-based binding does not surface validation errors
on the field correctly. The inline closure approach (modelled on
`src/Auth/Form/ChangePasswordForm.php`) is the established, reliable pattern in
this codebase — the closure's `$this` binding is direct and unambiguous.

`skipOnEmpty: true` means the callback is skipped entirely if the field was left
blank — the `#[Required]` attribute above it already handles that case with its
own message.

The closure returns a `Result` object. Adding no errors to it means the field
passes. Calling `$result->addError(...)` means it fails and the message appears
inline next to the field in the form.

---

## Where the Error Messages Come From

Static messages (text that does not change) are stored as translation keys in
`resources/messages/en/app.php` and routed through the translator so they can
be localised:

```php
'allowance.or.charge.amount.fixed.must.be.positive' =>
    'Fixed amount must be greater than 0.',
'allowance.or.charge.base.amount.required.when.mfn.set' =>
    'Base amount must be greater than 0 when multiplier factor is set.',
```

The dynamic formula error (which includes the actual numbers) is built with
`sprintf` and bypasses translation, since translating a number calculation
serves no purpose:

```php
sprintf('%d × %d ÷ 100 = %d', $mfn, $base, $expected)
// e.g. "20 × 1000 ÷ 100 = 200"
```

---

## Files Changed

| File | Change |
|---|---|
| `src/Invoice/AllowanceCharge/AllowanceChargeForm.php` | Implements `RulesProviderInterface`; `TranslatorInterface` constructor injection; cross-field `Callback` rules in `getRules()` |
| `src/Invoice/AllowanceCharge/AllowanceChargeController.php` | All `new AllowanceChargeForm` and `AllowanceChargeForm::show()` calls updated to pass `$this->translator` |
| `resources/messages/en/app.php` | Added two translation keys for the error messages |
| `resources/views/invoice/quoteitemallowancecharge/_form.php` | Variable/fixed toggle (see below) |

---

## Quote Item AllowanceCharge Form Toggle

The same fixed/variable distinction is surfaced to the user in
`resources/views/invoice/quoteitemallowancecharge/_form.php`.

Without a toggle the `multiplier_factor_numeric` stored on the
`AllowanceCharge` template is completely ignored when assigning an
allowance or charge to a quote line item — the user would just type any
flat number into the `amount` field.

### How it works

A JSON map of every `AllowanceCharge` template is written into an inline
`<script>` block when the page renders:

```js
const data = { 1: { mfn: 20, base: 1000 }, 2: { mfn: 0, base: 0 }, ... };
```

On page load and whenever the dropdown selection changes, `applyMode()`
reads the selected template's `mfn` value and branches:

**Fixed mode** (`mfn = 0`)
- The `base_amount` helper row is hidden.
- The user types a flat amount directly into the `amount` field.

**Variable mode** (`mfn > 0`)
- A `base_amount` input appears, pre-filled from the template's stored
  `base_amount` (editable).
- As the user adjusts `base_amount`, `amount` is auto-calculated live:
  `mfn × base ÷ 100`, rounded to two decimal places.
- The formula is shown beneath the field as a `<small>` hint, e.g.
  `20 × 1000 ÷ 100 = 200.00`.
- `amount` stays editable — the user can override the calculated value
  (guided, not locked), consistent with the server-side `Callback` rule
  in `AllowanceChargeForm` which warns rather than hard-blocks.

The `base_amount` input has **no `name` attribute** and is never
submitted. Only `amount` reaches the server, preserving the existing
controller and service unchanged.

### Scope

| Form | Toggle needed | Reason |
|---|---|---|
| `quoteitemallowancecharge/_form.php` | Yes — done | First point of assignment; MFN must be applied |
| `quoteallowancecharge/_form.php` | Yes — todo | Same; quote-level allowance/charge |
| Salesorder / invoice forms | No | Quote item amount is already resolved before an order or invoice is raised |
