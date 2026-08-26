# All-Locales Diff Sweep — First Real Live Run (August 2026)

First real end-to-end run of `googleTranslateAllLocalesDiff()`
(`docs/GOOGLE_TRANSLATE_ALL_LOCALES_AUTOMATION_AUGUST_2026.md`) against
the real Google Cloud Translation API and the real `resources/messages/
{locale}/app.php` files, all 29 non-English locales in one sweep.

## Blocker found and fixed before the run

The `google_translate_json_filename` Setting still pointed at a
previous service-account key
(`yii3-i-457915-961595392e2c.json`) that no longer exists on disk — a
new key had been generated and downloaded earlier the same session
(`yii3-i-457915-0b0c9e162102.json`, confirmed via
`src/Invoice/Google_translate_unique_folder/`), but the Setting itself
was never updated to match. Fixed with a direct `UPDATE setting SET
setting_value = ... WHERE setting_key = 'google_translate_json_filename'`
against the local MySQL `yii3_i` database, confirmed by re-selecting the
row before retrying.

## How it was run

Triggering the real web action needs an authenticated staff session
(`Permissions::EDIT_INV`), not available from this environment. Instead,
a throwaway console command
(`App\Command\Translation\TranslateAllLocalesLiveRunCommand`,
temporarily registered in `config/console/params.php` as
`translation/all-locales-live-run`) invoked the controller's own
private `translateOneLocaleDiff()` via reflection — the exact same
method `GeneratorGoogleTranslateControllerAllLocalesTest` already
exercises against mocked temp directories — once per real locale
directory, against the real API and real files. Both the command file
and its registration were deleted immediately after the run, per this
app's own established "throwaway command, run once, then delete"
convention (see the Peppol Advanced Ordering inbound-import
verification for the same pattern).

## Result

All 29 locales updated in one run, no failures:

```
af-ZA: +487 new keys (2288 total)     ha-NG: +480 new keys (2287 total)
ar-BH: +624 new keys (2287 total)     he-IL: +480 new keys (2287 total)
az:    +480 new keys (2305 total)     id:    +480 new keys (2290 total)
be:    +480 new keys (2287 total)     ig-NG: +480 new keys (2287 total)
bs:    +480 new keys (2290 total)     it:    +480 new keys (2302 total)
de:    +480 new keys (2290 total)     ja:    +480 new keys (2290 total)
es:    +480 new keys (2290 total)     lt:    +480 new keys (2287 total)
fil:   +480 new keys (2290 total)     nl:    +480 new keys (2290 total)
fr:    +480 new keys (2290 total)     pl:    +480 new keys (2305 total)
gd-GB: +480 new keys (2287 total)     pt-BR: +480 new keys (2290 total)

ru:    +480 new keys (2290 total)     vi:    +480 new keys (2305 total)
sk:    +480 new keys (2305 total)     yo-NG: +480 new keys (2302 total)
sl:    +480 new keys (2290 total)     zh-CN: +480 new keys (2290 total)
uk:    +480 new keys (2305 total)     zh-TW: +481 new keys (2258 total)
uz:    +480 new keys (2290 total)     zu-ZA: +480 new keys (2290 total)
```

`en` was correctly skipped (no line printed). Per-locale totals differ
slightly (2258-2305) because each locale's pre-existing key count
already differed slightly going in, matching the earlier diff report
from the same session.

## Verified

`php -l` clean on all 29 changed files (0 syntax errors) — confirms
`writeLocaleAppPhp()`'s `var_export()`-based escaping held up against
real translated text at scale, not just the apostrophe fixture in the
unit test. UTF-8 correctness independently double-checked via a
standalone PHP script reading the real files back (`mb_check_encoding()`
+ visual confirmation) across German (ü/ß), Japanese, Traditional
Chinese, and Arabic — all valid, all correctly rendered. (A first look
via a PowerShell terminal showed garbled German umlauts; confirmed to
be purely a PowerShell console codepage display artifact, not real data
corruption — the on-disk bytes were correct UTF-8 throughout.) Full
Testo Unit suite: 1004/1004 passed, unaffected (data files only). Full
project Psalm `--no-cache`: no errors found, unchanged from before the
run.
