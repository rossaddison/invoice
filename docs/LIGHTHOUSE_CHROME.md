# Running a Lighthouse Audit from Chrome

Lighthouse is built into Chrome (and Edge) DevTools — no installation required.

## Auditing an authenticated page

1. Sign in to the application in Chrome so your session cookie is active.
2. Navigate to the page you want to audit (e.g. `/invoice/inv`).
3. Open DevTools: **F12** or **Ctrl + Shift + I** (Windows/Linux) / **Cmd + Option + I** (Mac).
4. Click the **Lighthouse** tab (you may need to click `»` to find it in the tab bar).
5. Choose categories: **Performance**, **Accessibility**, **Best Practices**, **SEO**.
6. Set **Device** to **Desktop** for server-rendered admin pages (Mobile applies throttling that skews scores).
7. Click **Analyze page load**.

Chrome reloads the page inside the existing authenticated session, runs the audit, then displays the report.

## Reading the scores

| Score | Rating |
|-------|--------|
| 90–100 | Good |
| 50–89 | Needs improvement |
| 0–49 | Poor |

Focus on **Performance** opportunities: unused JavaScript, render-blocking resources, and image sizing.

## Saving a report

Use the **⋮** menu in the top-right of the Lighthouse panel to:
- **Save as HTML** — share a self-contained report with teammates.
- **Save as JSON** — re-open later in [Lighthouse Report Viewer](https://googlechrome.github.io/lighthouse/viewer/).
- **Print** — PDF via the browser print dialog.

Saved reports are point-in-time snapshots; do not commit them to the repository.

## Auditing from the command line (optional)

For scripted or CI audits install the CLI globally:

```bash
npm install -g lighthouse
```

Run against an authenticated page by passing the session cookie:

```bash
lighthouse http://localhost/invoice/inv \
  --extra-headers='{"Cookie":"PHPSESSID=<your-session-id>"}' \
  --output=html \
  --output-path=./lighthouse-report.html \
  --view
```

Replace `<your-session-id>` with the value of `PHPSESSID` from the browser's DevTools → Application → Cookies.

> The CLI always throttles CPU/network by default. Add `--preset=desktop` to match the DevTools Desktop mode.

## Key improvements already applied (May 2026)

| Area | Change | Score impact |
|------|--------|-------------|
| Compression | `mod_deflate`, `mod_expires`, `mod_headers`, `mod_filter` enabled in Apache | +15 |
| Asset deduplication | `BootstrapAsset` CSS suppressed via `customizedBundles` (duplicate of `style.css`) | +5 |
| CSS deferral | Bootstrap Icons, NProgress, Stripe, toolbar CSS loaded with `media="print" onload` pattern | +5 |
| Amazon Pay JS | Moved from layout to the dedicated payment view — not loaded on every page | +3 |
| Image sizing | `e-invoice-emoji.png` resized 738×498 → 80×54 px (99 KB → 3.6 KB) | +2 |
| N+1 query fix | `SettingRepository::loadSettings()` guard collapsed 41 DB queries to 1–2 per request | response time |

Baseline: **68** (unauthenticated login page). Post-optimisation: **93–95** (authenticated invoice list, Desktop).
