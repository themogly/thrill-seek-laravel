# Unattended run 2: 019, 005 to 018, then the pre-staging gate

Ben authorised this run on 9 October 2026. Work through the items below in order. Merge each one once it's
green before starting the next, except where an item says "do not merge". Stop only for the stop conditions.
For anything else, decide, record what you decided, and carry on.

**First, before anything else:**
1. `git checkout main && git pull`.
2. `git status` should show only `RUNNING-ORDER.md` modified, plus the untracked prompt files
   `prompts/005-…` to `prompts/019-…` and `prompts/unattended-run-2.md`. Chat-Claude put these there. If
   anything else shows, stop and list it.
3. Commit those files to `main` as `docs: queue unattended run 2` and push.

Then read `RUNNING-ORDER.md`, `prompts/writing-prompts.md`, `false-green.md` and `RUN-REPORT-1.md`. At the
start of each item, re-read its file from disk. Never work from your memory of a prompt.

## In scope, and nothing else

| # | item | merge? |
|---|---|---|
| 0 | `prompts/019-pin-php-platform.md` | yes |
| 1 | `prompts/005-faq-admin-500.md` | yes |
| 2 | `prompts/006-no-known-admin-login-on-servers.md` | yes |
| 3 | `prompts/007-sample-testimonials-off-public-site.md` | yes |
| 4 | `prompts/008-database-refuses-money-linked-deletes.md`, **Phase 1 only** | **no.** Push the proposal and continue |
| 5 | `prompts/009-admin-acts-email-the-customer.md` | yes |
| 6 | `prompts/010-voucher-payment-success-page.md` | yes |
| 7 | `prompts/011-cid-embedded-mail-logo.md` | yes |
| 8 | `prompts/012-cms-orphan-fields.md` | yes |
| 9 | `prompts/013-homepage-seo-from-settings.md` | yes |
| 10 | `prompts/014-prices-in-cms-copy.md` | yes |
| 11 | `prompts/015-consistency-small-fixes.md` | yes |
| 12 | `prompts/016-brand-contrast-primary-strong.md` | **no.** Ben looks first |
| 13 | `prompts/017-feature-split-respects-admin-crop.md` | **no.** Ben looks first |
| 14 | `prompts/018-tailor-launch-checklist.md` | yes |
| 15 | `gates/pre-staging-gate.md` (kit file, report-only) | yes, the report only |
| 16 | Housekeeping (below) | n/a |

**Resuming:** an item already merged on `main` (check `git log`, not this list) is skipped, with a note. An
item whose branch exists unmerged gets finished, not restarted.

Not in scope: Phase 2 of 008, any follow-up the reports propose, and any prompt not listed here.

## Merging, for the "yes" rows

1. Branch off the latest `main`.
2. Build.
3. Run `composer check` green, plus `phpunit.mysql.xml` if the branch touched queries, casts, JSON columns,
   migrations or money.
4. `git checkout main && git merge --no-ff <branch> && git push origin main <branch>`.
5. Update `RUNNING-ORDER.md` (the `main at` sha, and mark the item merged), commit, push.

For the "no" rows: push the branch, record its sha in the run report, return to `main`, and carry on. The next
item branches from `main` without it.

## Stop conditions

On any of these: stop, leave `main` clean and green, write the run report, and end.

- A false premise that a later item depends on. If no later item depends on it, it isn't a stop: record it,
  skip the item, carry on.
- A structural decision the prompt hasn't already made, such as data model, schema, or wiring. 008 is
  already designed to stop at its checkpoint without ending the run.
- A red suite you didn't cause, or one you can't make green without weakening a test.
- A branch you can't finish. Land it smaller if the prompt allows it. Otherwise discard it unmerged and stop.
  Never merge half a branch.
- An unintended homepage change in a "yes" row. 016 is the only authorised homepage change, and it doesn't
  merge.

## Not stop conditions: escalate and carry on

- **Owner decisions:** write the options and the spec into DECISIONS as `OWNER DECISION — PENDING`, implement
  neither, and finish the rest of the branch.
- **Agent-chosen defaults:** mark them `OVERNIGHT-DEFAULT — CONFIRM` in the code and in DECISIONS.

## Screenshots

Use cropped JPEGs of the element or section that matters, not full-page PNGs. Run 1 committed 8.9 MB.

## Housekeeping (item 16)

- Sync the global Claude Code skills from the kit. Ben approved this on 9 Oct 2026, knowing it affects his
  other projects. For each `~/Sites/starter-kit/skills/<name>/SKILL.md`, copy it to
  `~/.claude/skills/<name>/SKILL.md`. Before overwriting, diff any existing file and paste a one-line summary
  per skill into the report.
- Delete remote branches that are fully merged into `main` (`git branch -r --merged origin/main`). Keep
  `main`, and never delete an unmerged branch (008, 016 and 017 stay).
- Leave the local "Audit …" records in the dev database alone. Ben will reset it himself.

## Run report

Write `RUN-REPORT-2.md` at the repo root, appending after each item and committing it with each
`RUNNING-ORDER.md` update. For each item record:
- the branch, the merge sha (or "pushed, unmerged"), and the test count before → after;
- one line on what it did;
- a gap report if anything deviated;
- every `OWNER DECISION — PENDING` and `OVERNIGHT-DEFAULT — CONFIRM` added;
- the screenshot paths for anything visual.

End the report with:
- why the run ended;
- `main`'s sha;
- **what Ben has to look at**: the 008 proposal table, the 016 and 017 screenshots, and the pre-staging
  GO/NO-GO;
- the owner questions, each answerable in one line.
