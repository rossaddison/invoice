# SonarCloud Issues — m.bat Option 26

The SonarCloud web interface provides no copyable file paths and no
Psalm-style output.  Option 26 in `m.bat` wraps `sonar-issues.php` in a
guided menu so you can query SonarCloud issues without leaving the terminal.

For direct CLI use without the menu, see [SONARCLOUD_CLI.md](SONARCLOUD_CLI.md).

---

## Step 1 — Generate a token

1. Go to `sonarcloud.io` → your avatar (top right) → **My Account**
2. Click the **Security** tab
3. Under **Generate Tokens**:
   - **Name**: anything, e.g. `yii3i-terminal`
   - **Type**: **User Token** — this is the correct type for terminal tools.
     The other types (Project Analysis Token, Global Analysis Token) are for
     CI pipelines only.
   - **Expiration**: No expiration
4. Click **Generate** and **copy the token immediately** — it is shown once only.

**Never paste the token into a chat window, issue, or commit.**

---

## Step 2 — Open the SonarCloud menu

Run `m.bat` from the project root and enter `26`:

```
Enter your choice [0-26,99]: 26
```

You will be prompted for the token from sonarcloud.io
login ... avatar (top-right corner) ... My account ... security:

```
SonarCloud token (press Enter to reuse session token):
```

- **First run**: paste your token and press Enter.
- **Subsequent runs in the same terminal session**: press Enter to reuse the
  token already set — you do not need to paste it again.

If no token is available and you press Enter without entering one, the menu
aborts with an error and returns to the main menu.

---

## Step 3 — Choose a query

After the token step the SonarCloud sub-menu appears:

```
  [1]  All open issues
  [2]  Issues on a specific PR
  [3]  Filter by type        (BUG / VULNERABILITY / CODE_SMELL)
  [4]  Filter by severity    (BLOCKER / CRITICAL / MAJOR / MINOR / INFO)
  [5]  Security hotspots
  [6]  Combine type + severity filters
  [7]  Filter by rule key    (e.g. php:S1192 / javascript:S7647 / typescript:S7785)
  [0]  Back to Main Menu
```

### [1] All open issues

Fetches every open issue across the entire project.

```
ERROR: php:S1848 - src/Invoice/Foo.php:42 - Objects should not be created to be dropped immediately
WARN:  php:S116  - src/Infrastructure/Persistence/Inv/Inv.php:18 - Rename this field
```

### [2] Issues on a specific PR

Prompts for a PR number, then fetches only the issues introduced by that PR:

```
PR number: 862
```

Useful when working on a branch — shows exactly what SonarCloud will gate on.

### [3] Filter by type

Prompts for one of:

| Value | Meaning |
|-------|---------|
| `BUG` | Reliability issues |
| `VULNERABILITY` | Security issues |
| `CODE_SMELL` | Maintainability issues |

### [4] Filter by severity

Prompts for one of:

| Value | Priority |
|-------|----------|
| `BLOCKER` | Must fix — blocks release gate |
| `CRITICAL` | High priority |
| `MAJOR` | Medium priority |
| `MINOR` | Low priority |
| `INFO` | Informational only |

### [5] Security hotspots

Fetches security hotspots separately from issues.  Hotspots require manual
review to determine whether they are genuine vulnerabilities.

```
HOTSPOT: xss - src/Invoice/Inv/InvController.php:123 - Make sure content is sanitized
```

### [6] Combine type + severity

Prompts for both a type and a severity, then fetches issues matching both.
Example: `CODE_SMELL` + `MAJOR` shows only major code-smell issues.

### [7] Filter by rule key

Displays a numbered reference list of rules seen in this project, grouped by
language.  Enter a number to select a rule, or type a full rule key directly
for anything not in the list.  Press Enter with no input to cancel.

```
PHP
[1]  php:S1192   String literals duplicated 3+ times
[2]  php:S3776   Cognitive complexity too high
[3]  php:S107    Too many parameters in function/method
[4]  php:S116    Field name does not follow naming convention
[5]  php:S100    Function name does not follow naming convention
[6]  php:S1155   Use empty() instead of count() == 0 comparison
[7]  php:S6600   Remove unnecessary parentheses around echo argument
[8]  php:S2003   Use require_once instead of require
[9]  php:S7735   Negated conditions should be avoided
[10] php:S1848   Objects should not be created to be dropped immediately
[11] php:S1172   Unused function parameter
[12] php:S3358   Ternary operators should not be nested
TypeScript / JavaScript
[13] typescript:S7785  Replace async IIFE with top-level await
[14] typescript:S7647  Lifecycle methods should not be empty
[15] typescript:S7764  Use globalThis instead of window
[16] javascript:S7647  Lifecycle methods should not be empty (JS)
Shell
[17] shelldre:S1066    Merge this if statement with the enclosing one
```

Useful when fixing a specific rule across the whole project — shows every
occurrence so none are missed.

---

## Output format

All queries print in Psalm-compatible format:

```
ERROR: <rule> - <file>:<line> - <message>
WARN:  <rule> - <file>:<line> - <message>
```

File paths are fully copyable and clickable in VS Code's integrated terminal.
`ERROR` = BLOCKER or CRITICAL severity; `WARN` = everything else.

---

## Why issues persist after fixing them

Option 26 queries SonarCloud's **stored analysis results**, not your local files.
SonarCloud only re-analyzes when you push to GitHub and CI runs.

Until the branch is pushed:
- Fixed issues and resolved hotspots will **still appear** in option 26 output
- `// NOSONAR` suppressions have **no effect** locally
- New exclusions in `sonar-project.properties` are **not yet active**

After pushing:
1. The CI workflow triggers a new SonarCloud scan
2. Fixed issues disappear from the next `php sonar-issues.php` run
3. Files covered by `sonar.exclusions` drop out of the results entirely

To check what is actually uncommitted before pushing:

```powershell
git status --short
```

---

## Saving output to a file

Option 26 runs interactively so output cannot be redirected from inside the
menu.  To save to a file, run `sonar-issues.php` directly from a second
terminal:

```powershell
$env:SONAR_TOKEN = "your-token"
php sonar-issues.php > issues.txt
php sonar-issues.php --pr=862 > pr-862.txt
```

Then open the file in VS Code to search, filter, and copy paths freely.

---

## Project details

| Setting | Value |
|---------|-------|
| SonarCloud URL | `https://sonarcloud.io` |
| Region | EU |
| Organisation | `rossaddison` |
| Project key | `rossaddison_invoice` |
| Config file | `sonar-project.properties` |
