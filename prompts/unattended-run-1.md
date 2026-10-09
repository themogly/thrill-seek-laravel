# Unattended run 1 — RUNNING-ORDER steps 1–7

Ben has authorised an unattended run. Work through the items below **in order**, merging each one on green
before starting the next. **Stop only for the stop conditions.** Everything else, decide it, record it, and
carry on.

Read `RUNNING-ORDER.md` and `prompts/writing-prompts.md` first (after 001 has copied the latter in, re-read it).
At the start of each item, **re-read its file from disk**. Never work from memory of a prompt; context will
compact during a run this long.

## In scope, and nothing else

| # | item | branch it creates |
|---|---|---|
| 1 | `prompts/001-kit-sync.md` | `chore/kit-sync-2026-09` |
| 2 | `prompts/002-structural-guards.md` | `test/structural-guards` |
| 3 | `audits/email-audit.md` (kit file, verbatim; read the step-3 notes in `RUNNING-ORDER.md` *Standing caveats*) | `email/audit-pass` |
| 4 | `audits/admin-audit.md` (read the step-4 notes) | as the file says |
| 5 | `audits/accessibility-audit.md` | as the file says |
| 6 | `prompts/003-itemised-consistency-audit.md` | `docs/consistency-audit` |
| 7 | `gates/completeness-check.md`, then `gates/cms-field-usage-check.md` (report-only) | one report commit each |

**Resuming.** If an item is already merged on `main` (check `git log`, not this list), skip it and say so. If
its branch exists unmerged, finish it rather than starting again.

Steps 8 onwards in `RUNNING-ORDER.md` are **out of scope**: the fix prompts don't exist yet. Don't write them,
and don't fix what the reports in 6–7 find.

## Merging is required

Every prompt says "push, do not merge". **On this run that's overridden: merge each branch on green before the
next.** These branches all append to `DECISIONS.md` and touch shared components, so building them side by side
collides.

Per item:
1. Branch off the latest `main`.
2. Build.
3. `composer check` green. Also run `phpunit.mysql.xml` if the branch touched queries, casts, JSON columns,
   migrations or money.
4. `git checkout main && git merge --no-ff <branch> && git push origin main <branch>`.
5. Update `RUNNING-ORDER.md`: the `main at` sha and the item marked merged. Commit and push.

The audits normally stay unmerged until reviewed. On this run they merge too, so the review happens afterwards
from the run report. That's why the report has to be complete.

## Stop conditions

On any of these: **stop, leave `main` clean and green, write the run report, end.**

- **A false premise** you can't resolve by building less (writing-prompts: already fixed → skip it and carry
  on; reasoning wrong → record it, skip it and carry on. Stop only if a *later* item depends on it).
- **A structural decision the prompt hasn't already made**: data model, schema, or how something is wired.
- **A red suite you didn't cause**, or one you can't get green without weakening a test.
- **A branch you can't finish.** Land it smaller if the prompt allows; otherwise discard it, don't merge it,
  and stop. Never merge half a branch.
- **The homepage changes.** It's signed off. Any branch whose before/after screenshots differ on the homepage
  doesn't merge, unless you revert the homepage change and it then matches.

## Not stop conditions: escalate and carry on

- **Owner decisions** (policy, data retention, consumer-rights wording, brand colours). Write the options and
  the spec into `DECISIONS.md` with `OWNER DECISION — PENDING`, don't implement either side, and finish the rest
  of the branch. Stop only if the *next* item can't proceed without the answer.
  - **Contrast fixes that would change a brand colour are owner decisions.** Record the failing pairs and the
    nearest passing shade; don't apply it.
- **Agent-chosen defaults:** mark them `OVERNIGHT-DEFAULT — CONFIRM` in code and in DECISIONS, and carry on.

## Run report

Write `RUN-REPORT-1.md` at the repo root as you go, appending after each item, so a stop at any point leaves an
accurate report. Commit it with each `RUNNING-ORDER.md` update. Per item:

- the branch, the merge sha, and the test count before → after;
- one line on what it did;
- a **gap report** if anything deviated (what the prompt required that you didn't do; what you did that it
  forbids; what you did that it never mentions);
- every `OWNER DECISION — PENDING` and `OVERNIGHT-DEFAULT — CONFIRM` added;
- where the before/after screenshots are, for anything visual.

End the report with: why the run ended (finished, or which stop condition and where), `main`'s sha, and the
list of owner questions waiting for Ben, each answerable in one line.
