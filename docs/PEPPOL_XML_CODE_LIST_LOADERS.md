# Peppol XML Code-List Loaders — `PeppolArrays` Refactor

**Branch:** `sonarqube-parameter-reduction`  
**Date:** June 2026

## Problem

`PeppolArrays.php` contained six methods whose bodies were large hardcoded PHP
arrays copied verbatim from OpenPEPPOL code lists.  Each method triggered
SonarQube **S138** (function body > 150 lines).  Keeping these arrays in PHP
also created a maintainability trap: there was no trail connecting the values
back to their upstream source, making it impossible for a newcomer (or a future
update script) to judge whether the data was current.

| Method | Lines before | Violation |
|--------|-------------|-----------|
| `getUncl7143()` | ~925 | S138 |
| `getIso6523Icd()` | ~1 150 | S138 |
| `getChargesArrayAsAtAugust2023()` | ~357 | S138 |
| `getChargesArray()` | ~136 | S138 |
| `getUncl5305()` | ~74 | S138 |
| `electronicAddressScheme()` | ~400 | S138 |

In addition, the three first-generation XML loaders introduced during this work
were near-identical, triggering SonarQube **duplication** violations.

## Solution

### Shared private loader

A single `private static function loadVefaCodeList(string $filename): array`
reads any OpenPEPPOL VEFA-format code-list XML file:

```php
/** @return list<array{Id: string, Name: string, Description: string}> */
private static function loadVefaCodeList(string $filename): array
{
    $aliases = new Aliases(['@peppol' => __DIR__]);
    $dom = new \DOMDocument();
    if (!$dom->load($aliases->get('@peppol') . '/' . $filename)) {
        return [];
    }
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('cl', 'urn:fdc:difi.no:2017:vefa:structure:CodeList-1');
    /** @var \DOMNodeList<\DOMElement>|false $nodes */
    $nodes = $xpath->query('//cl:Code');
    if ($nodes === false) {
        return [];
    }
    $codes = [];
    foreach ($nodes as $node) {
        $idList   = $xpath->query('cl:Id',   $node);
        $nameList = $xpath->query('cl:Name', $node);
        $descList = $xpath->query('cl:Description', $node);
        $codes[] = [
            'Id'          => $idList   !== false ? ($idList->item(0)?->textContent   ?? '') : '',
            'Name'        => $nameList !== false ? ($nameList->item(0)?->textContent ?? '') : '',
            'Description' => trim($descList !== false ? ($descList->item(0)?->textContent ?? '') : ''),
        ];
    }
    return $codes;
}
```

`Yiisoft\Aliases\Aliases` resolves `@peppol` to `__DIR__ . '/DownloadedXml'`
(`src/Invoice/Helpers/Peppol/DownloadedXml/`) — consistent with the
`SettingRepository` pattern already used elsewhere in the project.
The `DownloadedXml/` subfolder keeps downloaded code-list XML files separate
from PHP source files in the same directory.

A second helper reformats the UNCL7161 output for the charge-reason dropdown:

```php
/** @return array<string, array{0: string, 1: string}> */
private static function loadUncl7161(): array
{
    $codes = [];
    foreach (self::loadVefaCodeList('UNCL7161.xml') as $entry) {
        if ($entry['Id'] === '') { continue; }
        $codes[$entry['Id']] = [$entry['Name'], $entry['Description']];
    }
    return $codes;
}
```

### Public methods — after

Every previously-large method is now ≤ 5 lines:

```php
public function getUncl7143(): array                    { return self::loadVefaCodeList('uncl7143.xml'); }
public function getIso6523Icd(): array                  { return self::loadVefaCodeList('icd.xml'); }
public function getUncl5305(): array                    { return self::loadVefaCodeList('UNCL5305.xml'); }
public function getChargesArrayAsAtAugust2023(): array  { return self::loadUncl7161(); }
public function getChargesArray(): array                { return self::loadUncl7161(); }
public static function electronicAddressScheme(): array { return self::loadVefaCodeList('eas.xml'); }
```

### XML files in `src/Invoice/Helpers/Peppol/DownloadedXml/`

| File | Code list | Upstream |
|------|-----------|----------|
| `uncl7143.xml` | Item classification codes (UNCL7143, D.19A) | [OpenPEPPOL GitHub](https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/UNCL7143.xml) |
| `icd.xml` | ISO 6523 ICD (participant identifier schemes) | [OpenPEPPOL GitHub](https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/icd.xml) |
| `UNCL7161.xml` | Charge reason codes (UNCL7161, D.16B) | [OpenPEPPOL GitHub](https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/UNCL7161.xml) |
| `UNCL5305.xml` | Tax category codes (UNCL5305 subset, D.16B) | [Peppol docs](https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5305/) |
| `eas.xml` | Electronic Address Scheme (EAS) | [Peppol docs](https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/) |

All files use the VEFA namespace `urn:fdc:difi.no:2017:vefa:structure:CodeList-1`
with `<CodeList><Code><Id><Name><Description>` structure.

### Dead files removed (`git rm`)

The following files were never `require`'d in production and existed only as
dead data, inflating SonarQube's duplication count:

- `src/Invoice/Helpers/Peppol/uncl2005.jsonld`
- `src/Invoice/Helpers/Peppol/uncl2005flattened.jsonld`
- `src/Invoice/Helpers/Peppol/uncl2005.php`
- `src/Invoice/Helpers/Peppol/uncl2005_flattened_jsonld.php`
- `src/Invoice/Helpers/Peppol/untdid2005.php`
- `src/Invoice/Helpers/Peppol/uncl2005_subset.php`

Their entries were also removed from the `psalm.xml` `UnusedVariable` suppress
block to keep Psalm's config in sync.

## Currency tracking

The canonical UNCL2005 subset used by the codebase (three Peppol-permitted
date codes: `3`, `35`, `432`) lives in `resources/peppol/uncl2005.php`.  That
file carries an upstream URL and a **"Quarterly update"** note so any maintainer
knows exactly where to look when a new Peppol BIS Billing specification is
published.

Stale inline comments in `PeppolHelper.php` and `StoreCoveHelper.php` that
referenced the deleted `src/Invoice/Helpers/Peppol/uncl2005.php` were updated
to point to `resources/peppol/uncl2005.php` instead.

## View key changes (`electronicAddressScheme`)

The old hardcoded array used `country`, `code`, `description` keys.  The EAS
XML has only `<Id>` and `<Name>` (no country field).  Three view files were
updated to use the standard VEFA keys:

| File | Old keys | New keys |
|------|----------|----------|
| `resources/views/invoice/clientpeppol/_form.php` | `code`, `description` | `Id`, `Name` |
| `resources/views/invoice/del/_form.php` | `code`, `description` | `Id`, `Name` |
| `resources/views/invoice/del/_view.php` | `code`, `description` | `Id`, `Name` |

Psalm `@psalm-var` annotation on `clientpeppol/_form.php` updated to
`array{Id: string, Name: string, Description: string}`.

## Checking currency (is anything stale?)

A CLI checker queries the OpenPEPPOL GitHub API and compares each file's last
upstream commit date against the downloaded date recorded in
`DownloadedXml/README.md`.

```bash
# Make (shows coloured STALE / UP-TO-DATE per file)
make peppol-check

# Composer
composer run peppol:check

# Direct
php bin/check-peppol-codelists.php
```

Optionally supply a GitHub personal-access token to raise the API rate limit
from 60 to 5 000 requests/hour:

```bash
# PowerShell
$env:GITHUB_TOKEN = "ghp_..."
make peppol-check

# or pass inline
make peppol-check GITHUB_TOKEN=ghp_...
```

Exit code `1` means at least one file is stale; `0` means all are current.
This makes it straightforward to add to CI if quarterly automation is wanted.

## Updating a code list

1. Download the latest XML from the upstream URL shown in the table above.
2. Drop it into `src/Invoice/Helpers/Peppol/DownloadedXml/` replacing the
   existing file (keep the same filename).
3. No PHP changes are required — `loadVefaCodeList()` reads the file at
   runtime.
4. Update the `Version` comment in the relevant method's docblock if the
   version string changed.
