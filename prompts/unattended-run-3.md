# Unattended run 3: Ben's answers, merge 016, 008 Phase 2, 020–024, then the gate

Ben authorised this run on 9 October 2026, after reading `RUN-REPORT-2.md`, the 008 proposal and the 016/017
screenshots. Work through the items below in order, merging each on green before the next. Stop only for the
stop conditions. For anything else, decide, record and carry on.

**First:**
1. `git checkout main && git pull`.
2. `git status` should show only `RUNNING-ORDER.md` modified, plus the untracked files
   `prompts/020-…` to `prompts/024-…` and `prompts/unattended-run-3.md`. Chat-Claude put these there. If
   anything else shows, stop and list it.
3. Commit those files to `main` as `docs: queue unattended run 3` and push.
4. Run `php artisan migrate` (never `fresh`) on the local dev database: 012's column drops and the
   `home.team_lead` removal are still pending there. Then `php artisan settings:clear-cache`.

Then read `RUNNING-ORDER.md`, `prompts/writing-prompts.md`, `false-green.md` and `RUN-REPORT-2.md`. At the
start of each item, re-read its file from disk.

## Ben's answers (9 Oct 2026): item 1 records these in DECISIONS

- **008:** approve the FK table **as proposed**: 13 → RESTRICT, **plus #26 (documents pivot) → RESTRICT**,
  with `Document` joining `GuardsDeletion` ("attached to N sent messages"). Approve the model-level `deleting`
  listener on every `GuardsDeletion` model.
- **016:** merge it.
  - Pure-white `primary-foreground`: **yes**.
  - Display prices of 24px and up stay bright `primary`: **yes**.
  - The "You" label in navy: **yes**.
  - Hover and active: **darken on hover** (prompt 020).
- **017: withdrawn, do not merge.** The 1024 imbalance is worse than the desktop side-trim. The admin crop
  stays 16:10; the layout stays as it is on `main`.
- **007:** at least 3 real customer reviews before a rating, and owner-typed reviews show but don't count:
  **confirmed.**
- **012:** removing `home.team_lead` and `Location::image`: **confirmed.**
- **014:** the weight surcharges stay typed for now.
- **Rescheduling into a full tandem slot must be refused** (prompt 021).
- **App-level noindex for non-production hosts:** yes (prompt 022).
- **Email buttons and links to the accessible blue:** yes (prompt 023).
- **Keyboard focus on the scrollable payments table:** yes (prompt 024).

## In scope, and nothing else

| # | item | merge? |
|---|---|---|
| 1 | **Record Ben's answers.** One DECISIONS entry dated 9 Oct 2026 with every answer above. Flip each answered `OWNER DECISION — PENDING` / `OVERNIGHT-DEFAULT — CONFIRM` marker to `… — ANSWERED 9 Oct (see DECISIONS)`, in DECISIONS and beside the code markers for 007, 012 and 016. | yes (docs) |
| 2 | **Merge `ui/primary-strong-contrast` (016).** Merge `main` into it. The only expected conflict is `DECISIONS.md`, two appends at the end: keep both, in date order. Then run `composer check` and re-run axe on the 20 public pages at 1440 and 390, expecting 0 `color-contrast` nodes. Homepage diff: only the colour changes the 016 screenshots show. | yes |
| 3 | **`prompts/008-database-refuses-money-linked-deletes.md`, Phase 2**, on `fix/fk-delete-rules`. Merge `main` into it first. Build exactly the approved table (item 1). | yes |
| 4 | `prompts/020-button-hover-contrast.md` | yes |
| 5 | `prompts/021-reschedule-respects-capacity.md` | yes |
| 6 | `prompts/022-noindex-non-production.md` | yes |
| 7 | `prompts/023-email-link-colour.md` | yes |
| 8 | `prompts/024-scrollable-table-focus.md` | yes |
| 9 | `gates/pre-staging-gate.md` (kit file, report-only), on the final `main` | yes, the report only |
| 10 | **Housekeeping.** Delete `ui/feature-split-ratio` (017) locally and on origin, and list 017 under *Withdrawn* in `RUNNING-ORDER.md` with the reason above. Delete the other remote branches fully merged into `origin/main`. | n/a |

**Resuming:** an item already merged on `main` (check `git log`) is skipped, with a note.

## Merging

1. Branch off the latest `main` (or, for 2 and 3, merge `main` into the existing branch).
2. Build.
3. `composer check` green, plus `phpunit.mysql.xml` if the branch touched queries, casts, migrations or money.
   Item 3 must run it.
4. `git checkout main && git merge --no-ff <branch> && git push origin main <branch>`.
5. Update `RUNNING-ORDER.md`, commit and push.

## Stop conditions

On any of these: stop, leave `main` clean and green, write the run report, and end.
- A false premise that a later item depends on.
- A structural decision the prompt and the answers above haven't made.
- A red suite you didn't cause, or one you can't make green without weakening a test.
- A branch you can't finish. Never merge half a branch.
- **Item 3:** the migration's pre-check finds orphaned rows. Report them; don't fix data.
- An unintended homepage change. Only item 2's colour change and item 4's hover states are authorised.

## Not stop conditions: escalate and carry on

New owner decisions go into DECISIONS as `OWNER DECISION — PENDING`, and you carry on. Agent defaults are
marked `OVERNIGHT-DEFAULT — CONFIRM`.

## Screenshots

Cropped JPEGs only.

## Run report

Write `RUN-REPORT-3.md` as you go, in the same format as runs 1 and 2. End it with:
- why the run ended;
- `main`'s sha;
- the test count;
- the pre-staging verdict;
- anything Ben has to look at;
- the owner questions, each answerable in one line.
