# FrontAccounting (rossaddison/FA) — PHP 8.5 / Psalm Migration Progress (October 2026)

Not part of this repository's own codebase — a brief cross-project pointer to
ongoing work on a sister project, [rossaddison/FA](https://github.com/rossaddison/FA)
(a fork of [FrontAccountingERP/FA](https://github.com/FrontAccountingERP/FA)),
kept here since both are actively maintained by the same author.

## What's happening

Branch [`chore/php-8.5-minimum`](https://github.com/rossaddison/FA/tree/chore/php-8.5-minimum)
is a file-by-file Psalm static-analysis cleanup ahead of raising the project's
minimum PHP version to 8.5, run at Psalm's strictest `errorLevel=1`. Every
first-party source file also now declares `strict_types=1`.

**Progress:** 13,353 → 10,158 → 7,220 → **6,968** Psalm errors so far,
tracked via a static `Psalm Level 1` badge at the top of that branch's own
README (same hand-updated-badge convention this repo's README already
uses). Also underway: renaming the dozen-odd function names (`trans_view`,
`edit_link`, `can_delete`, ...) independently declared the same way across
~50 files, each own-page pager-callback colliding under Psalm's cross-file
name resolution and producing noise no local fix could resolve — first file
converted and verified clean.

## Why it's worth noting here

The cleanup keeps surfacing real, previously-silent bugs rather than just
satisfying the type checker — a login-breaking crash from an implicit
`float`→`int` coercion, a class wrongly marked `final` that a live subclass
outside Psalm's scanned scope actually extends, a stock-item code (a
`varchar(20)` column) that had been mistyped as native `int` and would have
silently truncated non-numeric codes, a path-traversal/arbitrary-file-write
bug in the extensions installer, a site-wide XSS, and several SQL injection
fixes among them. Full detail lives in that repo's own
[`doc/BUGS_FOUND.md`](https://github.com/rossaddison/FA/blob/chore/php-8.5-minimum/doc/BUGS_FOUND.md)
and [`doc/PSALM_MIGRATION.md`](https://github.com/rossaddison/FA/blob/chore/php-8.5-minimum/doc/PSALM_MIGRATION.md) —
not duplicated here.

Once this branch merges into `rossaddison/FA`'s own default (`master`)
branch, the same badge and progress tracking carry over there.
