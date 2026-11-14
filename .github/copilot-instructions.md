# Copilot instructions

The full rules are in [`AGENTS.md`](../AGENTS.md) and apply to Copilot and to every other agent.
The ones that matter most:

- **Never** run `git add -A`, `git add .` or `git commit -a`. Stage files by explicit path and read
  the staged list before every commit, and the files in the outgoing commits before every push
  (`git log --name-only <remote>/main..HEAD`).
- Never commit real people's data. `imports/`, `.env`, `.docx`, `.xlsx`, `.csv`, databases and
  archives stay out of the repository. Tests and docs use virtual data only (`example.test` emails,
  `01632 960xxx` phones, `ZZ` postcodes, `Virtual`/`Test` surnames).
- Never bypass the git hooks or the `Sensitive data guard` CI check.
- Do not push to `main`, force-push, delete refs, change repository settings or make anything public
  without the owner's explicit approval. Use a branch and a pull request.
- PHP 8.4+, Psalm error level 1 with no suppressions, no `mixed`, new tests in Testo, UBL 2.4 only,
  translations only in `resources/messages/en/app.php`.
