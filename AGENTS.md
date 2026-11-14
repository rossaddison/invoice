# AGENTS.md

Rules for every AI coding agent and human contributor working in this repository (Claude Code,
GitHub Copilot, Codex, Cursor and others). `CLAUDE.md` has the longer project context; these rules
apply to all of them equally.

## Never commit real people's data

Customer documents were once pushed to a public branch by a bulk `git add -A`. A commit that has
been public cannot be fully taken back: GitHub keeps every pull request's commits reachable under
`refs/pull/<number>/head` even after the branch is deleted.

- **Never** run `git add -A`, `git add .` or `git commit -a`. Stage files by explicit path, then
  read `git diff --cached --name-only` before every commit.
- Before every push, read the files in the commits that are about to leave, not the staged list
  (once committed, nothing is staged): `git diff --name-only <remote>/<branch>..HEAD`, or
  `git log --name-only <remote>/main..HEAD` for a new branch.
- Stop if anything under `imports/`, or any `.docx`, `.xlsx`, `.csv`, database, archive or `.env`
  file, is in either list.
- Customer documents (`imports/`) are for local reading only. Scratch files, outputs and scripts
  that touch real data go outside the repository.
- Tests, fixtures and docs use **virtual** data only: emails on `example.test` or `example.com`,
  telephone numbers in `01632 960xxx`, postcodes starting `ZZ`, surnames starting `Virtual` or
  `Test`. Never put real customers, streets, areas or company names in code, tests, docs, commit
  messages or pull request descriptions.
- To turn a real file into a fixture, run
  `php bin/virtualize-sensitive-test-data-check.php virtualize <input> <output>` and then read the
  output: street names and other free text are not detected.
- The pre-commit hook, the pre-push hook and the `Sensitive data guard` CI job enforce this.
  **Never bypass them** (`--no-verify`, disabling hooks, editing the allowlist to get past a
  refusal without a reviewed reason).
- If real data is committed or pushed by mistake: stop, tell the repository owner immediately and
  plainly, and push nothing further.

## Actions that need the owner's explicit approval

Do not do any of these on your own initiative:

- push to `main`, force-push, or delete remote branches or tags
- make a repository public, rename it, transfer it or delete it
- change repository settings, rulesets, secrets, tokens or app permissions
- publish anything outside this repository (comments, issues, support tickets)

Work on a branch and open a pull request. `main` is protected.

## Code standards

- PHP 8.4+, PSR-1 and PSR-12, Yii3 dependency injection preserved.
- Psalm at error level 1, with no baseline and no `@psalm-suppress`. Run it on what you changed
  before calling the work done.
- Avoid `mixed`; be type specific.
- New tests use Testo (`Tests/Testo/`), not PHPUnit.
- Translation strings live in `resources/messages/en/app.php` only.
- UBL is always version 2.4, never 2.1. Do not change Peppol or UBL string values.
- Never author raw `<script>` tags; use `Html::script()`. Never use `window` in TypeScript; use
  `globalThis`.
- Do not put secrets, tokens or API keys in code, commits, logs or pull request text.

Report results faithfully: if a test fails or a step was skipped, say so with the output.
