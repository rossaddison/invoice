# Sensitive data guard (November 2026)

Real people's data must never reach the repository: not in a commit, and not as test data. This guard
enforces that automatically, in three places that use the same rules, so it does not depend on anyone
remembering.

| Where | When | What stops |
|---|---|---|
| `.githooks/pre-commit` | every `git commit` | refused staged files, and new real-looking data in test data |
| `.githooks/pre-push` | every `git push` | any commit in the push that adds a refused file, even one made with hooks switched off |
| `.github/workflows/sensitive-data-guard.yml` | every pull request and push to `main` | the same checks, on every commit in the range, so it cannot be skipped locally |

The hooks are switched on by `npm install` (the `prepare` script sets `core.hooksPath` to `.githooks`).

## What is refused

**Files (paths)** — `.githooks/sensitive-guard.sh`:

- anything under an `imports/` folder (customer run documents);
- `.env` and `.env.*` files (secrets);
- documents, databases and archives: `.docx .doc .xlsx .xls .odt .ods .pst .sqlite .sqlite3 .db .zip .7z
  .rar .tar .gz .tgz`;
- `.csv` / `.tsv` outside `Tests/` or a `Fixtures/` folder;
- any file over 5 MB.

A path that is legitimately one of these is listed, with a comment saying why it is not personal data, in
`.githooks/sensitive-allowlist`. Add to it only in a reviewed commit.

**Real-looking values in test data (content)** — `App\Invoice\Testing\VirtualizeSensitiveTestDataCheck`,
run through `bin/virtualize-sensitive-test-data-check.php`. Test data is anything under `Tests/` or a
`Fixtures/` folder. Test data must be *virtual*: values that look real but cannot belong to anybody.

| Kind | Virtual (allowed) | Everything else is real-looking |
|---|---|---|
| email | RFC 2606 / 6761 reserved domains: `example.com`, `example.org`, `example.net`, `*.test`, `*.example`, `*.invalid`, `*.localhost` | any other domain |
| telephone | numbers Ofcom reserves for drama: `01632 960xxx`, `07700 900xxx`, `020 7946 0xxx` and the other reserved ranges | any other UK number |
| postcode | outward code starting `ZZ` (not a UK postcode area), e.g. `ZZ1 1AA` | any other postcode |
| titled name | surname starting `Virtual`, `Test`, `Example` or `Sample` | `Mr`/`Mrs`/`Ms`/`Miss`/`Mx`/`Dr` plus any other surname |

The check never prints the value it found, only its kind, the line and its length, so a terminal or a CI
log does not become the next place the data leaks.

## Making real data virtual

To turn a real file into a fixture, write the virtual copy straight to where it will be committed:

```
php bin/virtualize-sensitive-test-data-check.php virtualize <input> <output>
```

The same real value always becomes the same virtual value inside one file, so the shape survives (the same
client on two lines, the same house on one street), while the values are replaced. Virtualizing virtual
text changes nothing. In code, `VirtualizeSensitiveTestDataCheck::virtualize()` does the same to a string.

**Street names, house names and other free text are not detected**, so read the output before committing
it. The check catches the structured identifiers; it cannot know that a word is somebody's street.

A line that needs a real-looking value on purpose (a test of the check itself) can carry the marker
`sensitive-data-check: ignore`, so every exception is visible in review.

## The baseline

Test data that existed when the guard was introduced uses placeholder-style values (`company.com`,
`SW1A`). Making a hook fail on 357 old lines would get it bypassed, so those values are tolerated per file
and rule in `.githooks/sensitive-data-baseline.tsv` and **only new real-looking values fail**. The baseline
should only ever shrink: virtualize a test, regenerate it, commit the smaller file:

```
git ls-files Tests | php bin/virtualize-sensitive-test-data-check.php write-baseline .githooks/sensitive-data-baseline.tsv -
```

## Never bypass it

Do not use `--no-verify`. If a legitimate file is refused, allowlist it in a reviewed commit; if real data
was staged, unstage it (`git restore --staged <path>`). The CI job and the pre-push hook exist for the case
where the first line of defence was skipped.

Stage files by name and read the staged list before every commit: `git add -A`, `git add .` and
`git commit -a` are what made this guard necessary.
