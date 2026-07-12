# Peppol Multi-Jurisdiction Profiles — VAT/GST Terminology

Summary of the work done to support multiple Peppol document profiles
(`core-bis3`, `pint-aunz`, `pint-sg`, `pint-uk`) and to make the entire UI show
the correct tax-scheme term ("VAT" or "GST") for whichever profile is active,
instead of hardcoding "VAT" everywhere. Companion reading:
[PEPPOL_PINT_AUNZ_ADAPTATION_PLAN.md](PEPPOL_PINT_AUNZ_ADAPTATION_PLAN.md) and
[PEPPOL_PINT_UK_SPECULATIVE_PLAN.md](PEPPOL_PINT_UK_SPECULATIVE_PLAN.md).

---

## The `PeppolProfile` enum

`src/Invoice/Helpers/Peppol/PeppolProfile.php` is the single source of truth
for everything that varies between document profiles:

| Case | `taxSchemeId()` | Notes |
|---|---|---|
| `CoreBis3` | `VAT` | Default; unrestricted `taxCategories()` (governed by admin `TaxRate` records, same as before this work) |
| `PintAuNz` | `GST` | Australia / New Zealand |
| `PintSg` | `GST` | Singapore |
| `PintUk` | `VAT` | Speculative — no official spec exists yet; see the PINT-UK plan doc |

Each case supplies `customizationId()`, `profileId()`, `taxSchemeId()`,
`label()` (for the settings dropdown), and `taxCategories()` (a
`list<TaxCategorySpec>`, informational in this pass — it documents which
UNCL5305-style codes a profile defines but does not yet constrain invoice
lines to them). `fromSetting(string $value)` resolves the active profile from
the `peppol_profile` setting, falling back to `CoreBis3`.

Everything downstream — `PeppolValidator`, `DocumentLevelValidator`,
`PeppolHelper`, `PeppolUblXml` — consumes the enum via `PeppolProfile::cases()`
/ `knownCustomizationIds()` rather than switching on individual cases, so
adding `PintUk` required zero changes outside the enum file itself.

---

## Dynamic VAT/GST terminology

`SettingMiscTrait::activeTaxSchemeTerm()` resolves
`PeppolProfile::fromSetting(...)->taxSchemeId()` and is the single call every
view/tooltip/business rule goes through:

```php
public function activeTaxSchemeTerm(): string
{
    return PeppolProfile::fromSetting($this->getSetting('peppol_profile'))->taxSchemeId();
}
```

- **24 translation keys** in `resources/messages/en/app.php` were
  parameterized with an ICU `{term}` placeholder (e.g. `'vat' => '{term}'`,
  `'vat.break.down' => '{term} Summary'`, `'enable.vat' => 'Enable {term}'`),
  with ~30 call sites across invoice/quote/salesorder views (screen, PDF, and
  public web templates) updated to pass `['term' => $s->activeTaxSchemeTerm()]`.
- Tooltips (`SettingTooltipTrait`) have no ICU pass — they're raw strings — so
  the equivalent tooltip text uses direct string interpolation with
  `$this->activeTaxSchemeTerm()` instead of a `{term}` placeholder.
- One deliberately **unfixed** business string: the literal
  `belongs_to_vat_invoice` DB/entity field name in the `enable_vat_registration`
  tooltip warning text — that's a schema identifier, not a UI label, so it
  stays `vat` regardless of active profile.
- **Deliberately out of scope for this pass**: non-English locale files (only
  `en` was parameterized) and tax-*calculation* logic — the ~43-file surface
  that actually computes tax amounts still assumes VAT-style behaviour.
  `documentLevelSummaryTaxApplicable()` (below) is the one calculation rule
  that *was* fixed, because it directly blocked GST from working correctly.

---

## Business logic: document-level Summary Tax

GST profiles use line-item tax exclusively; a document-level "Summary Tax"
doesn't apply the same way it does for VAT. `SettingMiscTrait`:

```php
public function documentLevelSummaryTaxApplicable(): bool
{
    return $this->getSetting('enable_vat_registration') === '0'
        && $this->activeTaxSchemeTerm() !== 'GST';
}
```

---

## "Enable Peppol" is now permanently on

Since `enable_vat_registration`'s meaning now depends on `PeppolProfile` (a
Peppol-owned concept), Peppol can no longer be meaningfully disabled.
`SettingRepository::loadSettings()` forces it on unconditionally — covering
existing installs with a stored `'0'`, not just fresh installs:

```php
public function loadSettings(): void
{
    if ($this->settingsArray !== []) {
        return;
    }
    $all_settings = $this->findAllPreloaded();
    foreach ($all_settings as $setting) {
        $this->settingsArray[$setting->getSettingKey()] = $setting->getSettingValue();
    }
    $this->settingsArray['enable_peppol'] = '1';
}
```

`InvoiceInstallTrait`'s install defaults gained `'enable_peppol' => 1` too
(documentation only — the real enforcement is the line above). The now-dead
"Peppol disabled ❌" toolbar button was removed from `ButtonsToolbarFull`.

---

## Settings UI changes

`resources/views/invoice/setting/views/partial_settings_peppol.php`:

- **Peppol Document Profile dropdown moved to the top** of the Peppol panel
  (right after the Enable Peppol indicator), since it now drives the VAT/GST
  term used throughout the rest of the app.
- Dropdown options render via `PeppolProfile::cases()` → `->label()`, so
  `PintUk` appears automatically as *"PINT UK — United Kingdom (speculative)
  (VAT)"* with no template change.
- "Enable Peppol" checkbox replaced with a static, non-interactive indicator
  (no `name` attribute — it can't be unchecked).

`resources/views/invoice/setting/views/partial_settings_vat_registered.php`:

- The panel's "VAT"/"GST" card-header title is now a clickable, tooltipped
  link to the Peppol Document Profile dropdown, built with
  `Yiisoft\Bootstrap5\Breadcrumbs` / `BreadcrumbLink` (the same component
  already proven working in `inv/view.php`) rather than a hand-rolled
  `Html::a()` call — a hand-rolled version silently failed to navigate for
  reasons never conclusively identified, while the widget version works:

  ```php
  echo Breadcrumbs::widget()
   ->links(
    BreadcrumbLink::to(
     label: $translator->translate('vat', ['term' => $s->activeTaxSchemeTerm()]),
     url: $urlGenerator->generate(
      'setting/tabIndex',
      [],
      ['active' => 'peppol'],
      'settings[peppol_profile]',
     ),
     active: false,
     attributes: [
      'data-bs-toggle' => 'tooltip',
      'title' => $translator->translate('click.to.toggle'),
     ],
     encodeLabel: false,
    ),
   )
   ->attribute('style', ['--bs-breadcrumb-font-size' => '1.25rem'])
   ->listId(false)
   ->render();
  ```

  The `--bs-breadcrumb-font-size` override brings the link's text up from
  Bootstrap's default (smaller) breadcrumb size to match the other plain-text
  `.card-header` titles on the same page.

---

## PINT UK (speculative)

Added as the fourth `PeppolProfile` case ahead of the real November 2026 HMRC
roadmap, so there's a concrete starting point to revise rather than starting
from zero. Full rationale, confirmed-vs-guessed breakdown, and what's
explicitly out of scope (NI `deliveryRegion` flag, reverse-charge/PVA
note-field conventions, real `CustomizationID`/`ProfileID` URNs) live in
[PEPPOL_PINT_UK_SPECULATIVE_PLAN.md](PEPPOL_PINT_UK_SPECULATIVE_PLAN.md). In
short: UK VAT never needed relabelling (`taxSchemeId()` stays `'VAT'`), but
needs six tax categories instead of one relabel (Standard 20%, Reduced 5%,
Zero-rated 0%, Exempt, Outside the scope, Domestic reverse charge) — closer in
shape to Singapore's multi-category problem than to Australia/New Zealand's
single-scheme swap.

---

## Verification

- `vendor/bin/psalm --no-cache` run clean (zero errors) on every file touched,
  including a full sweep of `src/Invoice/Helpers/Peppol/`.
- `php -l` run on every edited view file.
- Manual UI verification via screenshots at each step (Settings → Peppol tab,
  Settings → VAT/GST tab, invoice item table Subtotal/Summary/Total block)
  confirmed correct rendering and correct term substitution end-to-end.

## Known gaps / deliberately not done

- Non-English translation files were not parameterized — `{term}` only exists
  in `resources/messages/en/app.php`.
- Tax *calculation* logic elsewhere in the codebase still assumes VAT-only
  behaviour; only the document-level Summary Tax applicability rule above was
  actually fixed.
- `PintUk`'s `CustomizationID`/`ProfileID` are placeholders
  (`urn:peppol:pint:billing-1@gb-1`) — not real published identifiers.
- No NI (`deliveryRegion`) seam, no reverse-charge/PVA note-field convention,
  no shared vocabulary with the `vat-api-php` `VatReturn` boxes yet — all
  explicitly deferred until the real HMRC roadmap publishes.
