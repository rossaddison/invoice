# Dev Tools Web UI — `m.bat` / `m.php`

**June 2026**

## Overview

The legacy `m.bat` batch-file menu system — which suffered from persistent stdin
contamination issues when running interactive CLI tools such as Snyk — has been
replaced by a self-contained PHP web application served by PHP's built-in HTTP
server.

Running `m.bat` now launches `php -S 127.0.0.1:8099 m.php`, opens the browser
automatically, and presents all developer tools as a responsive Bootstrap 5.3
dark-themed web UI.

## What Changed

### `m.bat` — 9 lines, no more stdin problems

```bat
@echo off
title Yii3-i Dev Tools
cd /d "%~dp0"
echo  Yii3-i Dev Tools
echo  URL: http://127.0.0.1:8099
echo  Stop: Ctrl+C
start "" http://127.0.0.1:8099
php -S 127.0.0.1:8099 m.php
```

All menu logic, token storage, command execution, and output streaming moved to
`m.php`. The batch file has no interactive-input responsibility and cannot be
contaminated by tools that read from stdin.

### `m.php` — Single-file web application

A single ~1 100-line PHP file acts as both the HTTP router and the full UI.
Key features:

| Feature | Detail |
|---------|--------|
| **16 category submenus** | Psalm, Composer, Node, TypeScript, Angular, Testing, Snyk, PHP-CS-Fixer, PHPCS, Rector, SonarCloud, Yii, GitHub, Peppol, Benchmarks, System |
| **Streaming output** | `proc_open()` with stdin closed (`nul`) feeds stdout to the browser via `response.body.getReader()`; output appears line-by-line, not after the process exits |
| **ANSI colour rendering** | A JavaScript `ansiToHtml()` parser converts SGR escape codes to inline `<span style="…">` elements; applied after the stream completes; non-SGR sequences (cursor movement, erase) silently dropped |
| **Bootstrap 5.3 dark theme** | `data-bs-theme="dark"` via CDN; GitHub-style colour palette (`#0d1117` background) |
| **Session token storage** | SonarCloud and GitHub tokens stored in PHP `$_SESSION`; injected via `putenv()` before each command; never appear in URLs |
| **Confirm dialogs** | Destructive commands (TRUNCATE, rector apply) show a JS `confirm()` before running |
| **Background commands** | Commands that open a new window (`yii serve`, `ng serve`, `snyk auth`) use `popen()` and return immediately with a "Started in background" message |
| **Snyk summary filter** | `snyk code test` output buffered server-side and filtered to "Total issues" lines only; non-ASCII ANSI garbage stripped with `preg_replace` |
| **Static file passthrough** | A `PHP_SAPI === 'cli-server'` guard at the top of `m.php` returns `false` for real files on disk, letting the built-in server serve SVG icons and other assets directly |

### Snyk Resolved Vulnerabilities Index

A SQLite database (`snyk-resolved.db`, committed to the repository) tracks every
resolved or false-positive Snyk finding so that all contributors inherit the full
history on clone.

- Pre-seeded from the `.snyk` policy file with 12 entries (CWE advisory links
  included for each)
- Columns: severity, Snyk ID (linked to Snyk dashboard), title, file path,
  category, false-positive flag, AI-related flag, threat vector, resolved date
- Filter bar: All / False Positives / AI-Related with live counts
- Add / Delete rows via a collapsible form within the UI
- Accessible via **Snyk → Resolved Vulnerabilities Index** in the web UI

#### SQLite CLI vs Apache PHP distinction

`m.bat` uses the **CLI PHP** binary, which has a separate `php.ini` from the
WAMP Apache PHP. The setup page detects missing `pdo_sqlite` via
`PDO::getAvailableDrivers()`, shows the exact CLI `php.ini` path via
`php_ini_loaded_file()`, and clearly labels the WAMP tray method as
"Apache only — will not fix m.bat."

### Card Tooltip Popovers

Hovering or focusing any of the 16 main-menu category cards shows a Bootstrap 5
popover listing every command available inside that section — restoring the
at-a-glance discoverability of the previous flat-menu layout without sacrificing
the card-based organisation.

- **Data source** — each card's `<a>` element carries a `data-submenu-items`
  JSON attribute (array of label strings extracted from `$MENUS[$key]['items']`
  via `array_column`) and a `data-menu-title` attribute
- **Trigger** — `hover focus` (keyboard navigable)
- **Placement** — `top` with a 220 ms show / 80 ms hide delay to avoid
  accidental flicker
- **Styling** — custom `.cli-menu-pop` CSS class matches the dark GitHub-palette
  theme (`#0d1117` body, `#58a6ff` header, `#30363d` border)
- **Implementation** — `public/js/cli/menu-tooltips.ts` (TypeScript source),
  compiled to `public/js/cli/menu-tooltips.js` with esbuild targeting ES2024;
  loaded after the Bootstrap 5 bundle

New submenu items added to `$MENUS` in `m.php` are automatically reflected in
the popover with no additional changes needed.

### Menu Icons

Each of the 16 main-menu category cards displays an SVG icon:

- **Brand logos** (Composer, Node.js, TypeScript, Angular, Snyk, SonarCloud,
  GitHub) downloaded from the `simple-icons` npm package via jsDelivr
- **Yii3** uses the official logo from `yiiframework.com` (`yii3_full_for_light.svg`),
  displayed in its original brand colours without the invert filter
- **Generic icons** (Psalm → search, Testing → check2-circle, PHP-CS-Fixer →
  brush, PHPCS → code-slash, Rector → stars, Peppol → globe, Benchmarks →
  speedometer2, System → gear) from the `bootstrap-icons` npm package via jsDelivr,
  rendered white with `filter:brightness(0)invert(1)` for the dark theme

Icons are stored locally at `public/img/cli/` and served as static files by the
built-in server. The **System → Download Menu Icons** command re-fetches all 16
icons via `bin/download-cli-icons.php` (cURL with `file_get_contents` fallback).

## Files

| File | Purpose |
|------|---------|
| `m.bat` | Launches `php -S 127.0.0.1:8099 m.php` and opens the browser |
| `m.php` | Single-file web app: command registry, streaming runner, token endpoints, Snyk vuln DB, full HTML/JS UI |
| `bin/download-cli-icons.php` | Downloads all 16 menu SVG icons into `public/img/cli/` |
| `public/img/cli/` | Local icon files served statically by the built-in server |
| `snyk-resolved.db` | SQLite vulnerability log; seeded from `.snyk`; committed to the repo |
| `public/js/cli/menu-tooltips.ts` | TypeScript source for Bootstrap 5 Popover initialisation on main-menu cards |
| `public/js/cli/menu-tooltips.js` | Compiled JS (esbuild ES2024); loaded by `m.php` after the Bootstrap bundle |

## Running

```bat
m.bat
```

Opens `http://127.0.0.1:8099` in the default browser. Press `Ctrl+C` in the
terminal to stop the server.
