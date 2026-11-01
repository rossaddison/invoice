# inv/index Copy Invoice: Multi-Day Calendar (November 2026)

The **Copy Invoice** button on `inv/index` opens a modal
(`resources/views/invoice/inv/modal_copy_inv_multiple.php`) that used to
offer a single native `<input type="date">`, so copying one invoice to
several days meant repeating the whole copy once per day.

## What changed

- The date input is replaced by a month calendar where any number of
  days can be clicked on and off, with previous/next month buttons, today
  outlined and a summary line listing the chosen dates
  (`src/typescript/multi-date-picker.ts`, started from `index.ts`). It is
  dependency-free: the project had no date-picker library.
- `InvoiceHandler::handleCopyMultipleInvoices()` (`invoice.ts`) now sends
  `modal_created_dates[]` instead of `modal_created_date`.
- `MultipleCopy::multiplecopy()` de-duplicates and validates the
  `Y-m-d` dates, then copies every ticked invoice once per date for every
  ticked client. With no day selected it makes one copy dated today, as
  before.
- The CSV spreadsheet import in the same modal is unchanged.

Rebuild the bundles with `npm run build:typescript` after pulling.
