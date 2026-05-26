# SonarQube for IDE — Setup Guide

> **Warning:** This setup process is needlessly complex, poorly documented by
> SonarSource, and contains multiple UX failures that are not your fault.
> Every pitfall below was discovered the hard way.  Read this entire document
> before starting.

---

## UX Failures Catalogue

These are real problems in the extension as of May 2026, recorded for future
improvements:

1. **The product was renamed but nothing tells you.**
   "SonarLint" no longer exists anywhere in VS Code — menus, commands,
   marketplace, or output panels.  The new name is **SonarQube for IDE**.
   Searching for "SonarLint" returns nothing and gives no hint of the new name.

2. **The backend silently crashes on Windows with no user-visible error.**
   The JVM takes ~60 seconds to start on Windows.  The client times out after
   ~21 seconds and the extension disappears without any message, notification,
   or suggestion to try again.

3. **The Connect wizard never asks for a region.**
   This project's organisation is on SonarCloud EU.  The wizard silently
   assumes US, connects to the wrong server, finds no organisations, and shows
   an empty dropdown with no explanation.

4. **The organisation dropdown appears empty with no explanation.**
   This is a direct consequence of #3.  There is no error message, no hint
   that the region might be wrong, and no link to documentation.

5. **The "connection name" prompt gives no hint that one already exists.**
   The workspace is pre-configured with `connectionId: rossaddison-sonarcloud`.
   When authenticating via the Command Palette the extension asks you to
   "enter a connection name" as if you are creating something new, with no
   indication that you must type the existing ID exactly or authentication
   will be applied to a phantom connection.

6. **The token type is not explained.**
   The SonarCloud token page offers three types — User Token, Project Analysis
   Token, Global Analysis Token — with no plain-English guidance on which to
   choose for an IDE.  The correct answer (User Token) is not obvious.

7. **The token is shown once with no warning.**
   After generating a token there is no prominent warning that it cannot be
   retrieved again.  If you close the tab before copying it, you must revoke
   and regenerate.

8. **`connectionId` mismatches fail silently.**
   If the ID in user settings does not match the ID in workspace settings,
   Connected Mode simply does not work.  No error is shown anywhere.

9. **Settings keys still say `sonarlint` after the rebrand.**
   `sonarlint.ls.javaOpts`, `sonarlint.connectedMode.*` — all still use the
   old namespace.  This is inconsistent with the display name and causes
   confusion when searching for settings documentation.

10. **The token field opens masked with asterisks — you must start typing to
    reveal the actual input, and the organisation dropdown stays empty until
    the full token has been entered.**
    The field initially appears as a password input showing asterisks.  Begin
    typing (or paste the token) to clear the mask and enter your token.  The
    organisation dropdown will not populate until the token field is completely
    filled — there is no "verify" button and no visual indicator that the
    dropdown is waiting on the token.  If the dropdown appears empty, ensure
    the token has been fully entered first.

11. **Selecting your organisation from the dropdown does not necessarily enable
    the "Save Connection" button.**
    Even after the dropdown populates and you click your organisation name, the
    Save Connection button may remain greyed out with no explanation.  If this
    happens, manually retype the organisation name directly into the field
    rather than relying on the dropdown selection.  Only then will Save
    Connection become clickable.

12. **The dropdown details disappear after a period of inactivity, after which
    the extension displays the ironic message "Need help setting up a
    connection?" directly underneath the form it just broke.**
    Nothing is replaced — the message simply appears beneath the now-empty
    form as if the extension is unaware that its own timeout caused the
    problem.  If this happens, start Step 3 again from scratch and have your
    token copied and ready before opening the form.

---

## Common Confusion: Names

| Old name | New name | Notes |
|----------|----------|-------|
| SonarLint | **SonarQube for IDE** | Rebranded — "SonarLint" no longer exists in menus, commands, or the marketplace |
| SonarQube (self-hosted) | SonarQube Server | Different product from SonarCloud |
| SonarCloud | SonarCloud | The free cloud-hosted version used by this project |

**If you search for "SonarLint" anywhere in VS Code you will find nothing.**
Search for `SonarQube` instead.

---

## Prerequisites

- VS Code with the **SonarQube for IDE** extension installed
  (`sonarsource.sonarlint-vscode` in the marketplace — the display name is
  "SonarQube for IDE")
- A SonarCloud account with access to the `rossaddison` organisation

---

## Step 1 — Fix the startup timeout (Windows only)

On Windows the SonarQube for IDE backend JVM takes ~60 seconds to start but
the client times out after ~21 seconds, causing the extension to silently fail
with no message of any kind.  Two fixes are required before anything else will
work.

### 1a — Increase JVM heap in VS Code user settings

Open `Ctrl+Shift+P` → **Preferences: Open User Settings (JSON)** and add:

```json
"sonarlint.ls.javaOpts": "-Xmx1024m -Xms256m"
```

Note: the settings key still uses `sonarlint` even though the product is
renamed. This is an intentional inconsistency in the extension.

### 1b — Clear the stale H2 database

Close VS Code, then in PowerShell:

```powershell
Remove-Item -Recurse -Force "C:\Users\<you>\.sonarlint\storage\h2"
```

This forces a clean database rebuild on next startup.

Restart VS Code after both changes.

---

## Step 2 — Generate a SonarCloud token

1. Go to [sonarcloud.io](https://sonarcloud.io)
2. Click your **avatar** (top right) → **My Account**
3. Click the **Security** tab
4. Under **Generate Tokens**:
   - **Name**: anything, e.g. `vscode-sonarqube`
   - **Type**: **User Token** — this means "act as me".  It is the correct
     type for IDE plugins.  The other types (Project Analysis Token, Global
     Analysis Token) are for CI pipelines only.  The UI gives no hint of this.
   - **Expiration**: No expiration
5. Click **Generate**
6. **Copy the token immediately.**  It is shown only once.  The UI does not
   warn you of this prominently.  If you lose it, revoke it and generate a new
   one.

**Never paste the token into a chat window, issue, or commit.**

---

## Step 3 — Authenticate the existing connection

> **Do not use "SonarQube: Connect to SonarCloud"** — that wizard creates a
> new US connection and ignores the EU region already configured in settings,
> leaving the organisation dropdown empty with no explanation.

Instead, authenticate the connection that is already defined in settings:

1. `Ctrl+Shift+P` → **SonarQube: Update token** (or **SonarQube: Edit connection**)
2. When asked for a connection name, type exactly:
   ```
   rossaddison-sonarcloud
   ```
   The prompt looks like it wants you to invent a new name.  It does not.
   You must type the existing `connectionId` precisely or the token will be
   attached to a phantom connection that does nothing.
3. Paste your token when prompted.

---

## Step 4 — Verify the workspace binding

The workspace is pre-configured in `.vscode/settings.json`:

```json
"sonarlint.connectedMode.connections.sonarcloud": [
    {
        "connectionId": "rossaddison-sonarcloud",
        "organizationKey": "rossaddison"
    }
],
"sonarlint.connectedMode.project": {
    "connectionId": "rossaddison-sonarcloud",
    "projectKey": "rossaddison_invoice"
}
```

The VS Code **user** settings must use the same `connectionId` and include the
region:

```json
"sonarlint.connectedMode.connections.sonarcloud": [
    {
        "organizationKey": "rossaddison",
        "connectionId": "rossaddison-sonarcloud",
        "region": "EU"
    }
]
```

If these `connectionId` values do not match exactly, Connected Mode silently
fails with no error message of any kind.

---

## Step 5 — Confirm it is working

Open any PHP file in `src/`.  Within a few seconds you should see yellow
underlines on issues matching the SonarCloud quality profile.

If no underlines appear after 60 seconds:

1. `Ctrl+Shift+P` → **SonarQube: Show SonarQube output** — check for errors
2. `Ctrl+Shift+P` → **SonarQube: Update all project bindings**

---

## Alternative: command-line issue listing

Because the SonarCloud web UI provides no copyable file paths and no
Psalm-style output, a script is provided:

```powershell
$env:SONAR_TOKEN = "your-token"
php sonar-issues.php            # all open issues
php sonar-issues.php --pr=862   # issues on a specific PR
php sonar-issues.php --hotspots # security hotspots
```

Output format:
```
ERROR: php:S1848 - src/Invoice/Foo.php:42 - Objects should not be created to be dropped immediately
WARN:  php:S116  - src/Infrastructure/Persistence/Inv/Inv.php:18 - Rename this field
```

Composer shortcuts: `composer sonar`, `composer sonar:bugs`, `composer sonar:hotspots`.

---

## Project details

| Setting | Value |
|---------|-------|
| SonarCloud URL | `https://sonarcloud.io` |
| Region | EU |
| Organisation | `rossaddison` |
| Project key | `rossaddison_invoice` |
| `connectionId` | `rossaddison-sonarcloud` |
| Config file | `sonar-project.properties` |

---

## Windows Defender (optional but recommended)

Antivirus scanning of the JVM process adds startup latency.  In PowerShell
(as Administrator):

```powershell
Add-MpPreference -ExclusionPath "$env:USERPROFILE\.vscode\extensions\sonarsource.sonarlint-vscode-5.2.3-win32-x64"
Add-MpPreference -ExclusionPath "$env:USERPROFILE\.sonarlint"
```
