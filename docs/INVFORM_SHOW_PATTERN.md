# InvForm::show() — Populating Forms from Entities in Views

## The Bug

In `inv/view`, the status dropdown always showed "Draft" regardless of the actual
invoice status — even for sent, paid, or overdue invoices.

### Root cause

The view was built with a blank form:

```php
// src/Invoice/Inv/Trait/View.php  (before fix)
'form' => new InvForm(),
```

`InvForm` declares its default:

```php
private ?int $status_id = 1;   // 1 = draft
```

So `$form->getStatusId()` always returned `1`, and the select option comparison:

```php
->selected($key == (string) $form->getStatusId())
```

always highlighted the Draft option, no matter what `$inv->reqStatusId()` returned.

---

## The Fix

```php
// src/Invoice/Inv/Trait/View.php  (after fix)
'form' => InvForm::show($inv),
```

`InvForm::show(Inv $inv): self` is a static factory that copies all relevant fields
from the entity into a new form instance:

```php
public static function show(Inv $inv): self
{
    $form = new self();
    $form->status_id           = $inv->reqStatusId();
    $form->client_id           = $inv->reqClientId();
    $form->group_id            = $inv->reqGroupId();
    $form->is_read_only        = $inv->getIsReadOnly();
    $form->date_created        = $inv->getDateCreated();
    // ... all other fields
    return $form;
}
```

---

## Rule

> **Never pass `new InvForm()` to a view that displays invoice data.**
> Always use `InvForm::show($inv)`.

`new InvForm()` is for empty creation forms (e.g. the add modal, where no invoice
exists yet). `InvForm::show($inv)` is for any read or edit view that has a loaded
invoice entity.

This is the same principle documented in `docs/FORMS_DDD_.md` — forms are
display/validation helpers, not entity holders. `show()` is the bridge that copies
entity state into the form for rendering, without the form owning the entity.

---

## Why the Default Is 1

`private ?int $status_id = 1` exists so that a brand-new invoice form pre-selects
"Draft" on the add screen — which is correct behaviour there. The bug arose because
that default leaked into the view screen where an existing entity should have
overridden it.
