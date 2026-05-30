# SonarCloud First Gate

**May 2026**

## What Changed

SonarCloud analysis now runs as a dedicated `sonar` job at the **start** of the
`invoice build` workflow, before any PHP or Node.js matrix runners are allocated.

```yaml
jobs:
  sonar:
    name: SonarCloud
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@... # full history fetch
      - uses: SonarSource/sonarcloud-github-action@master

  tests:
    needs: [sonar]   # ← matrix blocked until sonar passes
    strategy:
      matrix:
        os: [ubuntu-latest, windows-latest]
        php: [8.4, 8.5]
```

Previously, SonarCloud was the last step inside the `tests` job — it ran after
all four matrix runners had already started, so violations had no chance to block
anything.

## Why

The 4-runner matrix (2 OS × 2 PHP versions) is expensive in both time and
GitHub Actions minutes. Spending those minutes on code that already has SonarQube
violations is wasteful. The `sonar` job completes in roughly one minute (checkout
+ scan only — no PHP or Node install). If it fails, the matrix never starts.

## Expected Contribution Workflow

1. Create a feature branch.
2. Push your change.
3. Watch the **SonarCloud** job in GitHub Actions — it is the first to appear.
4. If it reports issues, fix them on the branch before the matrix build is even
   worth running.
5. Open a pull request once SonarCloud is green.

## What SonarCloud Checks

The project's SonarCloud quality gate covers, among other rules:

| Category | Examples |
|----------|---------|
| String duplication | Literals appearing 3+ times → extract to a constant |
| Cognitive complexity | S3776 — deeply nested logic → guard clauses |
| Dead code | Unused variables, unreachable branches |
| Security hotspots | `eval`, unsanitised input, weak crypto |
| TypeScript | `window` → `globalThis` (S7764); no implicit `any` |
| PHP | Suppressed errors (`@`), `isset()` on class properties |

String-literal constants should follow the locations defined in `CLAUDE.md`:

| String type | File |
|-------------|------|
| Invoice statuses | `src/Invoice/InvoiceStatus.php` |
| UBL element names | `src/Invoice/Ubl/UblConstants.php` |
| Peppol codes | `src/Invoice/Peppol/PeppolConstants.php` |
| App-wide strings | `src/Invoice/AppConstants.php` |

## For AI-Assisted Contributions

When code is suggested by an AI assistant (e.g. Claude Code), the assistant is
expected to self-audit against the rules above **before** presenting the code.
Any likely violation should be flagged inline so it can be addressed before a
commit is made — not left for the CI gate to catch.

If a SonarCloud rule must be suppressed for a legitimate reason (e.g. a UBL or
Peppol string that must be reproduced exactly), add an explicit `// NOSONAR`
comment with a brief justification rather than silently leaving the violation.

## Related Docs

- [Sonarcloud CLI](SONARCLOUD_CLI.md) — query the SonarCloud API locally
- [Sonarcloud Setup](SONARCLOUD_SETUP.md) — initial project configuration
- [SonarQube IDE Setup](SONARQUBE_IDE_SETUP.md) — VS Code Connected Mode
- [Bootstrap 3 CSS Removal](BOOTSTRAP3_CSS_REMOVAL.md) — example of a
  large SonarCloud duplicate-selector cleanup
