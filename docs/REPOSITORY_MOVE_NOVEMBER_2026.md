# Repository move: `rossaddison/invoice` to `rossaddison/inv` (November 2026)

**Short version:** the project now lives at <https://github.com/rossaddison/inv>.
The old repository, `rossaddison/invoice`, is private and is being retired. If you
have a clone, change its remote (see [What you need to do](#what-you-need-to-do));
your history and commit hashes are unchanged.

## What happened

On 11 November 2026 a bulk `git add -A` committed a local folder of working
documents to a branch of the public repository. That folder is not meant to be
under version control and was not git-ignored on the branch where it happened.
It did not belong in a public repository.

The commit was noticed within minutes, the branch was deleted about six minutes
after it was pushed, and the repository was then made private to stop all public
access.

## Why a new repository and not a history rewrite

Deleting a branch on GitHub is not enough on its own. GitHub keeps every pull
request's commits reachable under `refs/pull/<number>/head`, so a commit that was
once part of a pull request can still be fetched by anyone who knows its hash until
GitHub's support team removes it. A removal request has been lodged with GitHub.

Rather than wait for that, a new repository was built **from only the branches
chosen for it**. The removed commit was never on those branches, and the new
repository holds no `refs/pull/*` references at all, so the commit does not exist
there. (A mirror clone or a fork would have copied the pull request references, so
neither was used.) The new repository was checked for this before any work moved
into it.

## What stayed the same

- The full commit history of `main`, and of the branches moved across, with the
  **same commit hashes** as before. Nothing was rewritten.
- The code, licence, workflows and documentation. `composer.json` and
  `package.json` only changed their repository links.

## What did not move

- **Issues, pull requests, discussions and Actions runs.** They stay in the old
  repository, which is private. A few comments and documents still refer to numbered
  issues, pull requests or discussions by their old links (for example, "see
  discussion #215"). Those links were deliberately **not** rewritten, because the
  same numbers do not exist in the new repository and a rewritten link would point
  at the wrong thing. Treat them as references to the old repository.
- Stars and watchers.
- Local uploads (company logo, attachments) and `.env`, which are git-ignored and
  have to be copied across by hand when moving a working copy.

## What you need to do

If you have a clone of the old repository:

```
git remote set-url origin https://github.com/rossaddison/inv.git
git fetch origin
```

Your branches and commit hashes still match. Pull requests opened against the old
repository will have to be reopened against the new one. Packagist and any
automation that points at `rossaddison/invoice` (CI secrets, code-quality and
review integrations) needs repointing at `rossaddison/inv`.

## What was changed to prevent a repeat

- `/imports` (the working-documents folder) is now git-ignored on `main`, so a
  bulk add cannot pick it up again.
- Staging is done by file name only (`git add <path>`), never `git add -A`,
  `git add .` or `git commit -a`, and the staged list is read before every commit
  and every push.
- Repository links in `composer.json`, `package.json`, the translation files, views
  and docs were updated to the new location in one commit, leaving the numbered
  links and two historical records (a benchmark merge message and a pasted error
  log) untouched.

## Status

- The new repository is the home of the project.
- The old repository stays private until GitHub confirms the removal request, and
  is then retired.
