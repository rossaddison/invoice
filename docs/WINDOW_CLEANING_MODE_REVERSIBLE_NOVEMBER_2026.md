# Home-services (window cleaning) mode is reversible: Settings → General → Business type — November 2026

## In one paragraph

A home-services install, for example the HomeCare window-cleaning setup, runs in **window-cleaning mode**, which
takes **Quote and Sales Order** out of the menus. That mode is **not permanent**. It is one setting,
**Business type**, on the **General** tab of Settings (`setting/tab_index`). Choose **Standard**, press **Save**,
and the Quote and Sales Order menus come back, for staff and for guests. Choose **Window cleaning** and they go
again. No data is deleted either way.

## Where it is

1. Sign in as an administrator.
2. **Settings** → the **General** tab (`setting/tab_index`).
3. **Business type**, a drop-down with two choices:

| Choice | Stored value | Effect |
|---|---|---|
| **Standard (all menus)** | `standard` | Every menu is shown. This is the default for a new install. |
| **Window cleaning / home services** | `window_cleaning` | The menus listed below are left out. |

4. Press **Save**. The info icon next to the field explains the same thing.

An install that has never saved the General tab has no `business_type` row at all. A missing, blank or unrecognised
value behaves as **Standard** and is shown as Standard in the drop-down. The row appears the first time the General tab
is saved. Standard is saved as the word `standard`, not as an empty value: `setting/debug_index` draws an empty value
as a red ⌛ ("not set"), which made a correctly saved setting look corrupted.

## What window-cleaning mode hides

| Where | Left out |
|---|---|
| Staff top menu | Quote, Sales Order, Tasks, Projects, Peppol, and the Sales by Product and Sales by Task reports |
| Staff sidebar | Quotes and Tasks |
| Guest (customer) menu | Quote and Sales Order |
| Staff dashboard | Only the Invoice Overview is shown (no quotes, projects or tasks) |
| Public front page | The window-cleaning front page replaces the standard one |

Switching back to **Standard** reverses every row in this table, because each one reads the same setting.

## What it does not do

- **No data is deleted.** Existing quotes and sales orders stay in the database and reappear when you switch back.
- **The pages are not blocked.** No route checks the business type, so someone who types a quote address
  (`quote/index`, `/client_quotes`) can still reach it, subject to their usual permissions. Only the menus change.
- **Switching back does not undo the preset's other changes.** See the next section.

## The Front Page "Set up as a home-services business" button

The button on the **Front Page** tab sets **Business type** to window cleaning **and** makes further changes in one
go. Reversing Business type undoes only its own part. The rest are separate settings, changed where they live:

| The preset also | Reverse it at |
|---|---|
| Hides the Webshop, Gateway status, Peppol status and HMRC API status links on the public front page | Front Page tab, the "no front page" checkboxes |
| Stops general sign-up (customers use HomeCare sign-up) | General tab, **Stop signing up** |
| Switches on HomeCare QR auto-invoice | HomeCare tab |
| Records the services offered | Front Page tab, **Services offered** |
| Adds placeholder areas, runs and streets (only when no run exists) and a product for each service | These are ordinary records: edit or delete them as usual |

Pressing the preset again is safe. It sets the settings again and adds nothing once a run exists.

## For developers

- **Setting key:** `business_type` (`App\Invoice\Enum\BusinessType::SETTING_KEY`). Any value other than
  `window_cleaning`, including a missing row, means the general setup.
- **First-run default:** `business_type` = `standard` (and a blank `services_offered`) is seeded under
  `InvoiceController` by `InvoiceInstallTrait`, with a tooltip entry in `SettingTooltipTrait`. Existing installs get the
  row on first save of the General tab.
- **Where the flag is read:** `LayoutViewInjection` and `BaseController::windowCleaning()` for the staff layout and
  dashboard, `GuestLayoutViewParameters` for the guest menu, `layout/sidebar.php` for the sidebar, and
  `SiteController` for the front page.
- **Tests:** `BusinessTypeSettingTest` (the field, the default, the tooltip and the round trip),
  `GuestLayoutViewParametersTest` (the guest menu) and `SidebarWindowCleaningTest` (the sidebar).
  `SettingRegistrationRatchetTest` fails if a settings-tab field is added without a default and a tooltip.

## History

Before this change `business_type` had no field on any settings tab. Only the Front Page preset wrote it, and nothing
could undo it from the screen. It also had no first-run default or tooltip. The guest menu and the staff sidebar did
not follow it at all, so a window-cleaning install still showed Quote and Sales Order to guests and Quotes in the
sidebar.
