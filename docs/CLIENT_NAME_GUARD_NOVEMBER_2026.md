# Two clients cannot share a first name and surname: Add, rename and the product-client form refuse it — November 2026

## In one paragraph

The client select on a quote, sales order or invoice shows a client's first name and surname, so **two clients with the
same name cannot be told apart** there; a live site had accumulated several. Adding a client, **or renaming one**, to the
same first name and surname as another client is now refused, with a message in the form's error summary naming the
name: "A client named … already exists. Choose a different name, or open that client instead." The **product-client
form** (the staff form that links a client to a product) refuses a new client with a taken name too, with a message of its
own, because there the answer is to link the existing client. Where a name cannot be refused (see "What it does not do"),
the client select **tells the clients apart instead**: it shows their email, postcode or number in brackets.

## How it behaves

- **Add** (`client/add`): checked after the form's own validation passes. Nothing is saved.
- **Rename** (`client/edit`): checked **only when the name is being changed**. Case and surrounding spaces do not count
  as a change. A client whose name is unchanged is never checked, so the clients that **already share a name** can still
  be edited for anything else (phone, address) without being forced to rename. The client's own id is left out of the
  lookup.
- **Product-client form** (`ProductClientController`, the "Create a new Client" choice): a client can be linked to many
  products, so a taken name means "link the existing client", not "make another". The refusal is a red flash that names the
  client and the **Select the existing client** option: "A client named … already exists. A client can be linked to many
  products: choose "Select the existing client" and link that client to this product instead of creating a second one."
  (`client.name.already.exists.link.existing`). It is checked before the client is saved, so nothing is created.
- The form keeps what was typed and shows the form's own field errors next to the refusal.
- **A client with no name at all is never refused.** A client made from an email alone at sign-up has no name, so
  `ClientNameGuard::isTaken()` ignores an empty first name and surname; otherwise every such client would look like a
  duplicate of every other. A surname on its own is still checked.
- The match is on the trimmed first name and surname, **case-insensitively** on the live database's collation: a name
  typed in lower case was refused against the same name in capitals (confirmed live on 24 November). It depends on the column collation, so it is not
  something a unit test can prove.
- **Required fields:** only the client's name and email are required on the form; the surname is optional. A client with
  a name and no surname is compared with an empty surname, so two surname-less clients with the same name are refused.

## Where it lives

`ClientNameGuard` (`errorsFor()` for Add, `errorsForEdit()` for rename, `isTaken()` for a plain yes or no),
`ClientRepository::findByFullName()`, and the `client.name.already.exists` and `client.name.already.exists.link.existing`
messages (English only). It is called from `ClientAddTrait::addPostErrors()`, `ClientController::edit()` (through
`ClientEditDeps`) and `ProductClientController::createNewClient()`.

### Why the first version showed nothing

The error was returned under the key `client_name`, but the client form's error summary is set to `onlyCommonErrors()`
and the name field shows only the form's own validation result, so a `client_name` entry was **never displayed**: the
form just re-rendered. The error is now a common (form-level) one, key `''`. A test renders the real summary exactly as
`_form.php` configures it, and fails with the old key.

## Clients that share a name: told apart in the select

Some paths cannot refuse a duplicate name, and a database that already has duplicates keeps them. So the Client field of
the invoice, quote and sales-order forms, the credit-note form and both modals no longer shows two identical labels.
`ClientOptionLabels` (called from `ClientRepository::optionsData()`, the one method all of those use) adds what tells
them apart, **only to clients whose name is shared**, ignoring case:

| Clients | Label |
|---|---|
| A name nobody else has | Exactly as before: first name, three spaces, surname |
| A shared name, with an email | `First   Last (email)` |
| A shared name, no email | `First   Last (postcode)`, else `First   Last (#client number)` |
| A shared name and the same email or postcode | `#client number` is added as well, so no two labels are identical |

The email is shown to staff on staff forms, who already see it. This was chosen over a "possible duplicate" warning
because it is right where the choice is made, and it also covers duplicates already in the database.

**Not covered:** only `optionsData()` changed. Several filter drop-downs build their own labels and were not touched, and
the sales-order list's client filter keys its options by the full name, so same-named clients collapse into **one**
entry there. That is a separate bug, not fixed. The guest-facing drop-downs were not changed.

## What it does not do

- **Existing duplicates are not touched.** Merge or rename them from the client list.
- **Other ways a client is created are deliberately not refused**, because refusing there does harm:
  - **Webshop and API orders** (`OrderService`) and **HomeCare sign-up**: a real customer is matched by email or by
    dwelling, and refusing would turn away a genuine customer who shares a common name.
  - **The InvoicePlane import and the HomeCare run-sheet import:** later invoices point at the client created for each
    row, so skipping a row would orphan them.
  - **User sign-up auto-assign** makes a client from an email alone (no name to compare); **seeds and commands** use fixed
    marker names.
  A duplicate name can therefore still arrive that way; the client select then tells the clients apart (see above). A
  "possible duplicate name" warning for staff on those paths was considered and not built.
- A client saved with a NULL surname (rather than an empty string) may not match a new one with no surname. Double spaces
  inside a name, and accents, count as different names. Not checked.
- There is no database unique index: two real people can share a name, so this is a rule for the forms, not the table.

## Tests

`ClientNameGuardTest` (13): a free name; a taken name (the message names the client); the client being edited is excluded;
the refusal is shown by the real error summary; renaming to a taken name is refused; an unchanged name, or a change of
case or spaces only, is never looked up; the name is read from under the form name too; renaming to a free name is
allowed; a nameless client is never looked up or refused (and a surname alone still is); `isTaken()` answers yes or no; and
the product-client message names the client and the "Select the existing client" option. Removing the "unchanged"
shortcut makes two of them fail, and removing the empty-name rule makes one fail.

`ClientOptionLabelsTest` (6), with virtual data only: a unique name keeps its exact old label; clients sharing a name are
told apart by email, then postcode, then number; case does not hide a shared name; the same name and email still get
different labels; no clients give no labels. With the disambiguation switched off, four of the six fail. The repository
method itself, which needs a database, and the select in a browser were not tested.
