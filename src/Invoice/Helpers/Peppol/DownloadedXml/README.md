# Peppol VEFA Code-List XML Files — Dropdown Data

> **Not the same as `resources/peppol/`.**
> This folder feeds **UI dropdowns** (rich Id + Name + Description).
> `resources/peppol/` feeds **validation** (flat membership lists used by `PeppolValidator`).
> Both must be kept in sync when upstream code lists change, but they serve different code paths.

These files are downloaded directly from the OpenPEPPOL GitHub repository and
the Peppol BIS Billing 3.0 documentation site. They are **not generated** — drop
in a replacement file with the same name to update a code list; no PHP changes
are required.

All files share the VEFA namespace:
`urn:fdc:difi.no:2017:vefa:structure:CodeList-1`

| File | Code list | Version | Downloaded | Upstream |
|------|-----------|---------|------------|----------|
| `eas.xml` | Electronic Address Scheme (EAS) | — | 2026-06-12 | https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/ |
| `icd.xml` | ISO 6523 ICD (participant identifier schemes) | — | 2026-06-12 | https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/icd.xml |
| `UNCL5305.xml` | Tax category codes (UNCL5305 subset) | D.16B | 2026-06-12 | https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5305/ |
| `UNCL7161.xml` | Charge reason codes (UNCL7161) | D.16B | 2026-06-12 | https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/UNCL7161.xml |
| `uncl7143.xml` | Item classification codes (UNCL7143) | D.19A | 2026-06-12 | https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/UNCL7143.xml |

## Updating a file

1. Download the latest XML from the upstream URL in the table above.
2. Replace the existing file in this folder (keep the exact filename).
3. Update the **Downloaded** date in this table.
4. No PHP changes are needed — `PeppolArrays::loadVefaCodeList()` reads the
   file at runtime.

## Quarterly update reminder

OpenPEPPOL publishes code-list updates roughly quarterly alongside new
Peppol BIS Billing specification releases. Check the upstream URLs above after
each OpenPEPPOL release to determine whether any file needs refreshing.
