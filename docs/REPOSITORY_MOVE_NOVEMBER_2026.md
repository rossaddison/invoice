# Repository move (November 2026)

**Short version:** the project is at <https://github.com/rossaddison/invoice>, the same address as
before. If you cloned it before 11 November 2026, your clone still points at the right place and
`git pull` works; your history and commit hashes are unchanged. See
[What you need to do](#what-you-need-to-do) for the one thing worth checking.

## What happened

On 11 November 2026 a bulk `git add -A` committed a local folder of working
documents to a branch of the public repository. That folder is not meant to be
under version control and was not git-ignored on the branch where it happened.
It did not belong in a public repository.

The commit was noticed within minutes, the branch was deleted about six minutes
after it was pushed, and the repository was then made private to stop all public
access.

## Why the repository was rebuilt and not rewritten

Deleting a branch on GitHub is not enough on its own. GitHub keeps every pull
request's commits reachable under `refs/pull/<number>/head`, so a commit that was
once part of a pull request can still be fetched by anyone who knows its hash until
GitHub's support team removes it. A removal request has been lodged with GitHub.

Rather than wait for that, a new repository was built **from only the branches
chosen for it**. The removed commit was never on those branches, and the new
repository holds no `refs/pull/*` references from before the move, so the commit does
not exist there. (A mirror clone or a fork would have copied the pull request
references, so neither was used.) The new repository was checked for this before any
work moved into it.

The new repository was first published as `rossaddison/inv`. Because many people and
tools use the original address, it was then **renamed to `rossaddison/invoice`**, and the
repository that held the removed commit was renamed `invoice-private-archive` and kept
private. The old `rossaddison/inv` address redirects to `rossaddison/invoice`.

## What stayed the same

- The full commit history of `main`, and of the branches moved across, with the
  **same commit hashes** as before. Nothing was rewritten.
- The code, licence, workflows and documentation. `composer.json` and
  `package.json` only changed their repository links.
- The address `https://github.com/rossaddison/invoice`.

## What did not move

- **Issues, pull requests, discussions and Actions runs.** They stay in the private
  archive. A few comments and documents still refer to numbered issues, pull requests
  or discussions by their old links (for example, "see discussion #215"). Those links
  were deliberately **not** rewritten, and because `rossaddison/invoice` now holds the new
  repository, those numbers no longer resolve. Treat them as references to the archive.
- Stars and watchers (the repository had none).
- Local uploads (company logo, attachments) and `.env`, which are git-ignored and
  have to be copied across by hand when moving a working copy.

## What you need to do

If you cloned `rossaddison/invoice` before the move, nothing is required: the address is the
same and the commit hashes match, so `git fetch` and `git pull` work. If you have local
branches or tags from before the move, check them with `git fetch --prune`.

If you cloned `rossaddison/inv`, the redirect keeps working, and you can tidy the remote:

```
git remote set-url origin https://github.com/rossaddison/invoice.git
```

Packagist and automation that point at `rossaddison/invoice` (CI secrets, code-quality and
review integrations) should keep working because the repository kept its identity through
the rename; re-run or re-save a Packagist update if it reports an error.

## What was changed to prevent a repeat

- `/imports` (the working-documents folder) is now git-ignored on `main`, so a
  bulk add cannot pick it up again.
- Staging is done by file name only (`git add <path>`), never `git add -A`,
  `git add .` or `git commit -a`, and the staged list is read before every commit
  and every push.
- A sensitive-data guard now refuses these files and real-looking personal data in test
  data in the pre-commit hook, the pre-push hook and CI
  (see `docs/SENSITIVE_TEST_DATA_GUARD_NOVEMBER_2026.md`).
- Repository links in `composer.json`, `package.json`, the translation files, views
  and docs were updated to `rossaddison/inv` in one commit, leaving the numbered
  links and two historical records (a benchmark merge message and a pasted error
  log) untouched. The `inv` address redirects, so those links keep working.

## Status

- `rossaddison/invoice` is the home of the project.
- The private archive stays private. It is never to be made public again.
