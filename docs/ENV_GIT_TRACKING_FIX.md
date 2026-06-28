# `.env` Overwritten on `git pull` — Staged File on Production Server

## Symptom

Every `git pull origin main` on the production server overwrites the server's `.env`
file, wiping production credentials. Running `git status` on the server shows:

```
Changes to be committed:
        new file:   .env
```

## Root Cause

`.env` has been **staged** on the production server (`git add .env` was run there, either
manually or by a deploy script using `git add .`). Once a file is in the git index, git
manages it as a tracked file:

- A fast-forward `git pull` replays incoming commits against the index. If any commit in
  the pull range touched `.env`, git overwrites the working-tree copy with that version.
- `git reset --hard origin/main` (often used in deploy scripts) resets the index and
  working tree to HEAD — removing `.env` from the staged set and potentially deleting
  the working-tree copy.

The file is already in `.gitignore`, but `.gitignore` only applies to **untracked** files.
Once staged, it is ignored by `.gitignore`.

## Fix — Run These Commands on the Production Server

```bash
# 1. Unstage .env — removes it from the index without touching the file on disk
git restore --staged .env

# 2. Verify it is now untracked (should appear under "Untracked files", not "Changes to be committed")
git status

# 3. Confirm the file contents are intact
head -5 .env
```

After step 1, `.gitignore` takes effect permanently. Future `git pull` and
`git reset --hard` operations will never touch `.env` again.

## What Caused the Staging in the First Place

Check whether a deploy script is running `git add .` or `git add -A`:

```bash
grep -r "git add" /path/to/deploy-scripts/
```

If found, replace with targeted `git add <specific-files>` and ensure `.env` is never
included. A safe deploy pattern on the server is:

```bash
git pull origin main           # fast-forward only
# do NOT run git add or git commit on the production server
```

## Preventing Recurrence — Pre-commit Guard (Local Dev)

Add this to `.git/hooks/pre-commit` on any machine where `.env` might be accidentally staged:

```bash
if git diff --cached --name-only | grep -q '^\.env$'; then
  echo "ERROR: .env is staged — aborting commit to protect credentials." >&2
  exit 1
fi
```

## Note on Git History

`.env` was tracked in old commits (before March 2026). The commit `d8b40273 Security
Improvements` removed it from tracking and converted it to `.env.example`. The root
of the current problem is not the git history but the staging on the production server.
