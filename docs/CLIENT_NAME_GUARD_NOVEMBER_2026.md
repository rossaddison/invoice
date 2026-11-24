# Two clients cannot share a first name and surname: Add and rename are refused — November 2026

## In one paragraph

The client select on a quote, sales order or invoice shows a client's first name and surname, so **two clients with the
same name cannot be told apart** there; a live site had accumulated several. Adding a client, **or renaming one**, to the
same first name and surname as another client is now refused, with a message in the form's error summary naming the
name: "A client named … already exists. Choose a different name, or open that client instead."

## How it behaves

- **Add** (`client/add`): checked after the form's own validation passes. Nothing is saved.
- **Rename** (`client/edit`): checked **only when the name is being changed**. Case and surrounding spaces do not count
  as a change. A client whose name is unchanged is never checked, so the clients that **already share a name** can still
  be edited for anything else (phone, address) without being forced to rename. The client's own id is left out of the
  lookup.
- The form keeps what was typed and shows the form's own field errors next to the refusal.
- The match is on the trimmed first name and surname, **case-insensitively** on the live database's collation: a name
  typed in lower case was refused against the same name in capitals (confirmed live on 24 November). It depends on the column collation, so it is not
  something a unit test can prove.
- **Required fields:** only the client's name and email are required on the form; the surname is optional. A client with
  a name and no surname is compared with an empty surname, so two surname-less clients with the same name are refused.

## Where it lives

`ClientNameGuard` (`errorsFor()` for Add, `errorsForEdit()` for rename), `ClientRepository::findByFullName()`, and the
`client.name.already.exists` message (English only). It is called from `ClientAddTrait::addPostErrors()` and
`ClientController::edit()`, through `ClientEditDeps`.

### Why the first version showed nothing

The error was returned under the key `client_name`, but the client form's error summary is set to `onlyCommonErrors()`
and the name field shows only the form's own validation result, so a `client_name` entry was **never displayed**: the
form just re-rendered. The error is now a common (form-level) one, key `''`. A test renders the real summary exactly as
`_form.php` configures it, and fails with the old key.

## What it does not do

- **Existing duplicates are not touched.** Merge or rename them from the client list.
- **Other ways a client is created are not guarded:** HomeCare sign-up (which only avoids duplicate emails), import,
  install seeding, product-client and user-inv creation. A duplicate name can still arrive that way.
- A client saved with a NULL surname (rather than an empty string) may not match a new one with no surname. Double spaces
  inside a name, and accents, count as different names. Not checked.
- There is no database unique index: two real people can share a name, so this is a rule for the forms, not the table.

## Tests

`ClientNameGuardTest` (9): a free name; a taken name (the message names the client); the client being edited is excluded;
the refusal is shown by the real error summary; renaming to a taken name is refused; an unchanged name, or a change of
case or spaces only, is never looked up; the name is read from under the form name too; renaming to a free name is
allowed. Removing the "unchanged" shortcut makes two of them fail.
