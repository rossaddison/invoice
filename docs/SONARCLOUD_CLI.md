# SonarCloud CLI — Psalm-style Issue Listing

The SonarCloud web interface provides no copyable file paths and no concise
issue listing.  `sonar-issues.php` queries the SonarCloud API and prints
issues in Psalm-style format directly in the terminal.

---

## Output format

```
ERROR: php:S1848 - src/Invoice/Foo.php:42 - Objects should not be created to be dropped immediately
WARN:  php:S116  - src/Infrastructure/Persistence/Inv/Inv.php:18 - Rename this field
HOTSPOT: xss - src/Invoice/Inv/InvController.php:123 - Make sure that this content is sanitized
```

File paths are fully copyable and clickable in VS Code's integrated terminal.

---

## Setup

Set your SonarCloud token once per terminal session:

```cmd
set SONAR_TOKEN=your-token
```

Or in PowerShell:

```powershell
$env:SONAR_TOKEN = "your-token"
```

Generate a token at `sonarcloud.io` → your avatar → **My Account** → **Security**
→ **Generate Token** → type **User Token**.

---

## Usage

```cmd
php sonar-issues.php
```

### Filters

| Command | What it shows |
|---------|--------------|
| `php sonar-issues.php` | All open issues |
| `php sonar-issues.php --pr=862` | Issues on a specific pull request |
| `php sonar-issues.php --type=BUG` | Bugs only |
| `php sonar-issues.php --type=VULNERABILITY` | Vulnerabilities only |
| `php sonar-issues.php --type=CODE_SMELL` | Code smells only |
| `php sonar-issues.php --severity=BLOCKER` | Blockers only |
| `php sonar-issues.php --severity=CRITICAL` | Critical issues only |
| `php sonar-issues.php --severity=MAJOR` | Major issues only |
| `php sonar-issues.php --hotspots` | Security hotspots |

### Composer shortcuts

```cmd
composer sonar
composer sonar:bugs
composer sonar:hotspots
composer sonar:security
```

### Save to file

```cmd
php sonar-issues.php > issues.txt
```

Then open `issues.txt` in VS Code to search, filter, and copy paths freely.

---

## Technical notes

- Uses `curl` (not `file_get_contents`) — required because WAMP disables
  `allow_url_fopen` for external URLs
- SonarCloud API parameter is `componentKeys` (not `projectKey` — the API
  returns an error if `projectKey` is used)
- Issue statuses use `issueStatuses=OPEN,CONFIRMED` (the older `statuses`
  parameter is no longer accepted)
- Paginates automatically — all issues are fetched regardless of count
- Project key: `rossaddison_invoice`
- SonarCloud region: EU (`https://sonarcloud.io`)
