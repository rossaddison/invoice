# Storecove sandbox test plan — November 2026

Builds on [STORECOVE_GO_LIVE_TESTING_AUGUST_2026.md](STORECOVE_GO_LIVE_TESTING_AUGUST_2026.md), which proved
**one** real end-to-end send. This plan covers what that single send did not: every invoice shape the business
issues, whether what Storecove accepts is *correct* to the penny, how failures behave, and what the app cannot yet
see. The UK mandate starts on 1 April 2029 ([UK-E-INVOICING-MANDATE.md](UK-E-INVOICING-MANDATE.md)); this is the
evidence that the Storecove route is ready for it.

## Scope

**In scope: the UBL send path.** This is the only path that sends anything to Storecove:

```
inv/peppolSend/{id}  →  PeppolSendServiceRouter  →  StorecovePeppolSendService
   →  rossaddison/storecove-client  createDocumentSubmission()  →  POST /document_submissions
```

The service sends the invoice as **UBL 2.4 XML, base64-encoded** in `rawDocumentData`, with `legalEntityId` from the
`storecove_legal_entity_id` setting, the API key from the (encrypted) `storecove_api_key` setting, and the
recipient from the client's Peppol record (`endpoint scheme : endpoint id`). The provider is chosen by the
`peppol_access_point_provider` setting, which defaults to `storecove`.

**Out of scope:**

- The structured-JSON route `inv/storecove/{id}` only **prints** a JSON document built by `StoreCoveHelper`. Nothing
  sends it, so it is not on the send path. (It is worth a visual check against Storecove's JSON schema if that route
  is ever wired up for sending.)
- Receiving invoices, and production. No production key is used anywhere in this plan.

## Facts about the app that shape the tests

| Fact | Consequence for testing |
|---|---|
| The client library only has `createDocumentSubmission()`. | The app stores `SENT` plus Storecove's `guid` and **cannot ask Storecove what happened next**. `SENT` means "accepted by the API", not "delivered". Delivery and validation outcome must be read in the Storecove portal. |
| `new StorecoveClient(apiKey: …)` is built without a base URI, so it uses `https://api.storecove.com/api/v2/`. | If the sandbox turns out to use a different host, the app needs a small change first (see Open questions, Q1). |
| `storeCoveCallApi()` and `storeCoveCallApiGetLegalEntityId()` (debug helpers) send **hard-coded** data ("Test Party", an `nl:kvk` lookup) with whatever key is configured. | Never run them with a production key. They would create or query real records. |
| `inv/peppolSend/{id}` now runs the same validator as the preview (`PeppolValidator`) before sending and **does not send** an invoice with errors, unless the `peppol_validate_before_send` setting is exactly `0` (`PeppolPreSendValidation`). Warnings never block. | A bad invoice is stopped before the network call. Still read the preview before sending ([PEPPOL_VALIDATOR.md](PEPPOL_VALIDATOR.md), [PEPPOL_SCHEMATRON_VALIDATOR_ROUTE1.md](PEPPOL_SCHEMATRON_VALIDATOR_ROUTE1.md)). |
| Rule coverage is split. The bundled Schematron holds the Peppol-specific rules (`PEPPOL-EN16931-R*`). The validator adds 15 core EN 16931 rules in code (`BR-CO-04/05/10/13/14/15/16`, `BR-CL-04/05/14/20/21/23/24`, `BR-S-08`, `BR-E-08`, `BR-Z-08`). The UBL generator enforces completeness while it builds the document (for example a missing buyer postal address throws the `BR-10` exception). | The rule families checked on a finished document are arithmetic, code lists and Peppol rules. The mandatory-element presence rules (`BR-01` to `BR-09` and similar) are not re-checked on the finished XML: they rely on the generator never omitting them. Test that the generator refuses incomplete data (T19).
| `php yii peppol/seed-happy-path-fixture` seeds a clearly labelled virtual invoice, and `--generate-only` / `--generate-existing=<id>` produce the UBL with **no HTTP**. `--cleanup=<id>` removes it. | The no-network dry run. |

## Open questions for Storecove (their API documentation sits behind a login, so none of this is verified)

Ask at the Storecove developer portal or by email to their API support, and record the answers at the end of this
document.

1. **Sandbox endpoint.** Is the sandbox API base URL the same as production (`https://api.storecove.com/api/v2/`),
   with the environment decided by the key or account, or a different host?
2. **Test recipients.** Which Peppol identifiers can a sandbox submission be addressed to, and does the sandbox
   deliver to anything real? The August send used Storecove's own `0106:27375186`.
3. **Outcome and evidence.** Where does the portal show a submission's validation result, delivery status and
   evidence, and is there an API call or webhook for it (the app would need one)?
4. **UBL validation.** Does Storecove validate submitted UBL against Peppol BIS Billing 3.0 and reject on the same
   rules as the Schematron, and what does a rejection response look like?
5. **UK profile.** Is Peppol BIS Billing 3.0 (`urn:fdc:peppol.eu:2017:poacc:billing:01:1.0`, the app's default
   `processId`) the right profile for UK sending, or is a different customization expected?
6. **Credit notes.** Is a UBL `CreditNote` submitted with `documentType: invoice`, or another type?
7. **Limits.** Attachment size limit, rate limits, and the idempotency behaviour of `idempotencyGuid`.

## Safety rules

- **Sandbox key and sandbox legal entity only.** Confirm the key's environment in the portal before the first call.
- **Virtual data only** (project rules): emails on `example.test`, telephone `01632 960xxx`, postcodes starting
  `ZZ`, surnames starting `Virtual` or `Test`. No real customer, street or company name in any test invoice.
- **Run it on a local checkout with a throw-away database, not the live site.** The live site holds real customers
  and real invoices.
- The API key lives only in the app's encrypted `storecove_api_key` setting. Never in `.env.example`, a commit, a
  pull request, a log, a screenshot, a chat message or a test file.
- Keep evidence **outside the repository** (for example `C:\storecove-sandbox-evidence\<date>\<case>\`), because a
  bulk `git add` once published customer documents. Stage files by path only.

## Prerequisites (one-off)

- [ ] Storecove **sandbox** account active, API key created for this application (one key per application).
- [ ] Sandbox **legal entity** for the supplier; its id saved in `storecove_legal_entity_id`.
- [ ] Supplier Peppol identifier registered for that legal entity if Storecove requires it before sending (Q2).
- [ ] Local app: `storecove_api_key`, `storecove_legal_entity_id`, `peppol_access_point_provider = storecove`,
      `peppol_debug_with_internal_validator = 1`, `peppol_validate_before_send = 1`, supplier details in `config/common/params.php`.
- [ ] Reference data complete: every tax rate used has a **Peppol tax rate code**, products have a Peppol unit, the
      client has a complete Peppol record (scheme and endpoint id valid for that scheme, not an email address).
- [ ] Open questions Q1 to Q3 answered.

## Method for every test case

1. **Build** the virtual invoice described by the case.
2. **Dry run, no network.** Generate the UBL (`inv/peppol/{id}` preview, or
   `php yii peppol/seed-happy-path-fixture --generate-existing=<id>`). Read the internal validator output: zero
   errors, and note every warning.
3. **Independent totals.** Work out the expected taxable, tax and payable amounts by hand or in a spreadsheet and
   compare them with the UBL `cac:TaxTotal` and `cac:LegalMonetaryTotal` **to the penny**. Passing a validator does
   not prove the amounts are right.
4. **Send** with `inv/peppolSend/{id}`. Record the `PeppolMessage` status and `peppol.message.id` (the Storecove guid).
5. **Verify in the Storecove portal.** Find the submission by guid and record its status, any validation messages
   and the evidence (Q3).
6. **Record** the result in the table below, with the UBL file and screenshots in the evidence folder.

## Test matrix

Priority: **P1** must pass before the 2029 route is called ready; **P2** should pass; **P3** nice to have.

| ID | Pri | Scenario | What to check |
|---|---|---|---|
| T01 | P1 | One line, standard rate (20%) | Tax category `S`, percent 20, totals match |
| T02 | P1 | Several lines, two standard-rate lines | Line and document VAT agree, no rounding break |
| T03 | P1 | Zero-rated line | Category `Z`, percent 0, taxable amount present |
| T04 | P1 | Reduced rate (5%) | Category `S` with percent 5 (reduced rates are category `S`, not a separate code) |
| T05 | P1 | Mixed rates on one invoice (20%, 5%, 0%) | One `cac:TaxSubtotal` per rate in use, sums equal the document total |
| T06 | P1 | Exempt or outside-scope line | Category `E` or `O`, with the exemption reason text the rules require |
| T07 | P1 | Special characters: client name `Virtual & Sons <Ltd>`, accents, a multi-line description | XML is well-formed, each character appears exactly once (no double encoding) |
| T08 | P1 | Credit note against a sent invoice | Credit note document type and line tags, amounts positive as the profile expects, reference to the original |
| T09 | P1 | Buyer with a valid endpoint id for its scheme | Accepted; the `scheme:id` recipient is split correctly |
| T10 | P2 | Line-level allowance or discount | Allowance elements present, totals still reconcile |
| T11 | P2 | Document-level allowance and charge | Allowance and charge totals in `LegalMonetaryTotal` |
| T12 | P2 | Rounding stress: several lines of 0.33 or 0.67 at 20% | Line sum versus document total within the rule tolerance |
| T13 | P2 | Non-GBP document currency (`peppol_document_currency`) | Converted amounts, `currencyID` on every amount |
| T14 | P2 | Purchase order and sales order references, buyer reference | `cac:OrderReference` and line references present when set |
| T15 | P2 | Delivery location and invoice period | Delivery and period elements present |
| T16 | P3 | Attachment | Embedded document present, size within the limit (Q7) |
| T17 | P3 | Large invoice (about 50 lines) | Accepted, timing recorded |
| T18 | P3 | Same invoice sent twice | Behaviour of a repeated submission (every send, including a retry, uses a new `idempotencyGuid`, so Storecove cannot deduplicate it; Q7) |
| T19 | P2 | Incomplete data the generator must refuse: client without a name, postal address or Peppol record, a tax rate without a Peppol code, no delivery location | A friendly, deep-linked message, no UBL sent and no `PeppolMessage` created |

### Negative tests (the app must fail clearly, never silently)

| ID | Setup | Expected |
|---|---|---|
| N01 | Wrong or revoked API key | `FAILED` with an HTTP 401 in the message, not a stack trace |
| N02 | Wrong `storecove_legal_entity_id` | `FAILED` with Storecove's message |
| N03 | Recipient not on the network | `FAILED` or a portal-side failure, and the app says which |
| N04 | An invoice the validator rejects (for example a total that does not add up, or a document currency that differs from a line's `currencyID`) | The send is **blocked**: a warning lists the rule ids and no `PeppolMessage` is created. Then switch `peppol_validate_before_send` to No, send the same invoice once to record what Storecove does with it, and switch it back |
| N09 | A hand-edited UBL file with a mandatory element deleted (for example the buyer, invoice number or issue date), submitted outside the app | Not reachable through the app. Record what Storecove says, to learn whether the receiving side is the only check on those elements (gap 4 below) |
| N05 | Client without a Peppol record | Friendly, deep-linked flash, no exception page |
| N06 | Client Peppol endpoint with an `@` | Rejected by the form validation (August bug #9) |
| N07 | Network down or timeout | `FAILED` with the error saved in the message |
| N08 | Retry of a `FAILED` message with `php yii peppol/retry-failed` (the only retry path; there is no button) | The retry count increments and a new attempt is made with a new `idempotencyGuid` |

## Results log

Copy this table into the evidence folder and fill it in. Do not commit it if it contains anything from a real account.

| ID | Date | Invoice no. | Dry run | Totals match | App status | Storecove guid | Portal outcome | Notes |
|---|---|---|---|---|---|---|---|---|

## Pass criteria

- Every **P1** case: clean dry run, totals match to the penny, accepted by Storecove, and the portal shows no
  validation errors.
- Every **negative** case fails the way the table says.
- Every unexplained **P2** result has a logged defect.
- No production key or real data was used at any point.

## Gaps this plan is expected to expose

1. **No status or evidence retrieval.** `SENT` is not "delivered". If Q3 shows an API or webhook, the app needs a
   status call (or a webhook receiver guarded like `PeppolInboundGuard`) so a failed delivery is visible without
   opening the portal.
2. **Sandbox base URI not configurable.** If Q1 shows a different host, `StorecovePeppolSendService` needs a
   setting passed to `new StorecoveClient(baseUri: …)`.
3. **Debug helpers send hard-coded data.** `storeCoveCallApi()` and `storeCoveCallApiGetLegalEntityId()` should be
   restricted or parameterised before anyone runs them against anything but a sandbox.
4. **Mandatory-element presence rules are not re-checked on the finished XML.** The send gate is in place and the
   generator refuses incomplete data, but a deleted element (`BR-01` to `BR-09` and similar) would not be caught by
   the validator. Adding the CEN EN 16931 UBL Schematron beside `PEPPOL-EN16931-UBL.sch` would close this. The risk
   is small while the generator is the only source of documents; T19 and N09 record what actually happens.

## Answers to the open questions

Record them here when known.

| Q | Answer | Source and date |
|---|---|---|
| Q1 | | |
| Q2 | | |
| Q3 | | |
| Q4 | | |
| Q5 | | |
| Q6 | | |
| Q7 | | |
