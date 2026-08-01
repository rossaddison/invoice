# HomeCare inv/guest Hidden Columns + CSP Inline-Handler Sweep, Third Wave

## Reported symptoms

Three issues reported together from live use:

1. `partial_settings_homecare.php` can hide columns on the staff `inv/index`
   grid, but there was no equivalent for `inv/guest` (used by both ordinary
   client guests and HomeCare workers) — it always showed every column.
2. Settings → Front Page tab's "select all" checkbox did nothing.
3. On `inv/edit`, date fields only opened the picker via the calendar icon —
   clicking anywhere else in the box (previously possible) did nothing.

## Root cause — #2 and #3 are the same bug, a third occurrence

This project's CSP (`config/web/params.php`) sets `script-src 'self'` with no
`unsafe-inline` — see `docs/SECURITY_HARDENING_AUDIT_JULY_2026.md` and
`docs/CSP_INLINE_HANDLER_SWEEP_GAPS.md` for the first two sweeps that
established this. A raw `onclick`/`onchange` HTML attribute is exactly the
kind of inline script CSP blocks, silently, with no visible error — the
element still renders, it just does nothing when triggered.

The front-page select-all checkbox used a hand-written inline `onchange`
that CSP had been quietly eating. Separately, a grep across the whole
`resources/views` tree for `onclick.*showPicker` turned up **17 form files**
still using `onclick="this.showPicker()"` instead of the project's own
already-established `data-action="show-picker"` delegation pattern (correct
already on `FormFields::dateCreatedField()` and the reporting pages, which is
what made the gap visible by direct comparison): `inv/_form_edit.php` (2),
`inv/modal_add_inv_form.php`, `inv/modal_copy_inv_multiple.php`,
`inv/_form_create_confirm.php`, `client/_form.php`, `contract/_form.php` (2),
`delivery/_form.php` (4), `invrecurring/_form.php` (2), `upload/_form.php`,
`merchant/_form.php`, `payment/_form.php`, `purchaseentry/_form.php`,
`productimage/_form.php`, `task/_form.php`, `salesorder/_form_edit.php`,
`clientnote/_form.php`, `clientnote/_view.php`.

Only the `date_supplied`/`date_tax_point` pair in `inv/_form_edit.php` was
reported; the other 15 were an undiscovered, silently broken instance of the
identical bug.

## Fix — #2 and #3

All 17 `showPicker` occurrences were switched to
`'data-action' => 'show-picker'`, which `src/typescript/data-actions.ts`
already handles via its existing `show-picker` case (`actionEl.showPicker?.()`
on click) — no new JS needed for this part. One unrelated inline
`onclick="return confirm(...)"` on `client/_form.php` (a deactivate-warning
prompt) was found in the same grep but left untouched — same bug class, but
out of scope for this pass since it wasn't reported and touches a
destructive-action confirmation rather than a form field.

The select-all checkbox needed a small new primitive since nothing in
`data-actions.ts` covered toggling a whole checkbox group yet: a
`data-action="select-all"` case was added to the existing `change` listener
(the same listener that already auto-submits `DropdownFilter` `<select>`
elements via the `native-reset` class), reading a `data-target` CSS selector
and setting every checkbox inside it to match the select-all checkbox's own
state. `partial_settings_front_page.php`'s checkbox now carries
`data-action="select-all" data-target="#front-page-checkboxes"` instead of
the inline handler. Three new Vitest cases cover check-all, uncheck-all, and
a missing-target no-throw guard; full `data-actions.test.ts` suite is 19/19.

## Fix — #1, HomeCare inv/guest hidden columns

New `homecare_hidden_inv_guest_columns` setting (comma-separated, same
convention as the existing `homecare_hidden_inv_columns`), with its own
checklist in a second card on the Settings → HomeCare tab. Deliberately a
**separate** setting from the staff-side one rather than reusing it: the two
grids share almost no column keys — `inv/guest.php` never renders the
staff-only HomeCare columns (`workflow_type`, `family_name`,
`client_number`, etc.) at all, so its toggleable set is smaller and
different: **Paid, Credit Note, Client, Date Created, Due Date, Total,
Balance**. Number, the PDF-download action column, and Status stay always
visible — mirroring how Worker/Amount/`do_not_send` are never hideable on
the staff side.

Applied the exact same pattern `InvsColumnBuilder`/`InvsColumnVisibilityTrait`
already use for the staff grid — a no-op while
`homecare_auto_invoice_enabled` is off, so the general guest grid for
non-HomeCare businesses is never affected — implemented inline in
`inv/guest.php` itself (a plain procedural view, unlike `InvsColumnBuilder`'s
object-oriented widget class) rather than trying to share the trait across
that boundary.

`SettingController::tabIndex()` now passes `hidden_inv_guest_columns` to the
`homecare` partial alongside the existing `hidden_inv_columns`, and its POST
handler joins the new checkbox array into a comma-separated string the same
way it already does for the staff-side setting.

## Verification

Psalm `--no-cache` clean on every changed file. Full Testo `Unit` suite
596 tests (593 passed, 3 pre-existing/unrelated OpenSSL RSA-key-generation
environment failures), full PHPUnit suite 3,875 tests (only the known
Cycle-ORM-mock notices), full Vitest suite 143 tests, all unaffected by
these changes (August 2026).
