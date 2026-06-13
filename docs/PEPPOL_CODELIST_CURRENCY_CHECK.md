# Peppol Code-List Currency Check

**Branch:** `sonarqube-parameter-reduction`  
**Date:** June 2026

## Purpose

Determines whether the local OpenPEPPOL VEFA code-list XML files are
up-to-date by querying the GitHub Commits API for each file's last commit
date on the `OpenPEPPOL/peppol-bis-invoice-3` master branch and comparing it
against the download date recorded in
`src/Invoice/Helpers/Peppol/DownloadedXml/README.md`.

## Running the check

Four equivalent entry points — use whichever suits your workflow:

| Surface | Command |
|---------|---------|
| **m.bat** (option 27) | `m` → `27` |
| **Make** | `make peppol-check` |
| **Composer** | `composer run peppol:check` |
| **Direct** | `php bin/check-peppol-codelists.php` |

### Optional: GitHub token

The GitHub API allows 60 unauthenticated requests per hour — more than enough
for occasional manual checks. Supply a personal-access token to raise the limit
to 5 000/hour (useful in CI):

```bat
REM PowerShell / Windows — persists for the current session
$env:GITHUB_TOKEN = "ghp_..."
make peppol-check

REM Or pass inline via make
make peppol-check GITHUB_TOKEN=ghp_...
```

`m.bat` option 27 prompts for the token interactively and stores it in
`GITHUB_TOKEN` for the remainder of the session.

## Output

```
File                  Downloaded    Last GitHub Commit          Status
--------------------------------------------------------------------------------
eas.xml               2026-06-12    2026-01-21                  UP-TO-DATE
icd.xml               2026-06-12    2026-01-21                  UP-TO-DATE
UNCL5305.xml          2026-06-12    2025-05-13                  UP-TO-DATE
UNCL7161.xml          2026-06-12    2024-05-13                  UP-TO-DATE
uncl7143.xml          2026-06-12    2025-04-30                  UP-TO-DATE

All files are up-to-date.
```

- **Green / UP-TO-DATE** — local download is on or after the last upstream commit.
- **Red / STALE** — GitHub has a newer commit; the file should be refreshed.
- **Yellow / UNKNOWN** — GitHub API could not be reached (network, rate limit).

Exit code `0` = all current; `1` = at least one file stale (suitable for CI).

## Files involved

| File | Role |
|------|------|
| `bin/check-peppol-codelists.php` | CLI script — queries GitHub API, reads README dates, prints table |
| `src/Invoice/Helpers/Peppol/DownloadedXml/README.md` | Source of truth for local download dates (edit after each update) |
| `src/Invoice/Helpers/Peppol/DownloadedXml/*.xml` | The five VEFA code-list XML files loaded at runtime by `PeppolArrays` |
| `m.bat` option `[27]` | Interactive Windows entry point with token prompt |
| `Makefile` target `peppol-check` | Unix/WSL entry point |
| `composer.json` script `peppol:check` | Composer entry point |

## Updating a stale file

1. Download the latest XML from the upstream URL listed in
   `DownloadedXml/README.md`.
2. Replace the file in `src/Invoice/Helpers/Peppol/DownloadedXml/`.
3. Update the **Downloaded** date for that row in `DownloadedXml/README.md`.
4. Re-run the check — it should now show **UP-TO-DATE**.
5. No PHP changes are required; `PeppolArrays::loadVefaCodeList()` reads the
   file at runtime.

## Tracked code lists

| File | Code list | Version | Upstream |
|------|-----------|---------|----------|
| `eas.xml` | Electronic Address Scheme (EAS) | — | https://docs.peppol.eu/poacc/billing/3.0/codelist/eas/ |
| `icd.xml` | ISO 6523 ICD | — | https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/icd.xml |
| `UNCL5305.xml` | Tax category codes (UNCL5305 subset) | D.16B | https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5305/ |
| `UNCL7161.xml` | Charge reason codes (UNCL7161) | D.16B | https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/UNCL7161.xml |
| `uncl7143.xml` | Item classification codes (UNCL7143) | D.19A | https://github.com/OpenPEPPOL/peppol-bis-invoice-3/blob/master/structure/codelist/UNCL7143.xml |

## How it works

`bin/check-peppol-codelists.php` uses `curl` to call:

```
GET https://api.github.com/repos/OpenPEPPOL/peppol-bis-invoice-3/commits
    ?path=structure/codelist/{filename}&sha=master&per_page=1
```

The response's `commit.committer.date` (ISO 8601) is compared against the
`YYYY-MM-DD` date parsed from the `DownloadedXml/README.md` table. No
third-party libraries are required — only `curl` and `json_decode`.
