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
