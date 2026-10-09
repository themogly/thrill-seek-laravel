# Running order — from `be9e145`

Sequencing instruction, not a branch. It says which prompt to take next and why that position.
Written 9 October 2026 to put G-Force through the September 2026 starter kit.

**Merged:** everything through 24 June 2026, then (unattended run 1, `RUN-REPORT-1.md`) 001, 002, email audit.
**`main` at `d3ae38d`.** 001 branched from `be9e145`.
**Built, awaiting merge:** none. There are no other branches, local or remote.
**Prompt files:** `prompts/001-kit-sync.md`, `prompts/002-structural-guards.md`,
`prompts/003-itemised-consistency-audit.md`. The kit source is `~/Sites/starter-kit/`.
**Withdrawn:** the two June prompts that never ran — see *Withdrawn* at the end.

## The order

| # | prompt | why here |
|---|---|---|
| 1 | ✅ merged `086d593` — **001** — kit sync (docs + CLAUDE.md rules) | Everything after it cites `false-green.md`, `prompts/`, `gates/` and the new email audit, so they have to be in the repo first. It's docs only, so it can't break anything. |
| 2 | ✅ merged `092536d` — **002** — structural guards | The four new architecture rules all hold on `be9e145`. The cheapest time to lock them in is while they're true, and before the audits below start changing views. Tests only. |
| 3 | ✅ merged `d3ae38d` — `audits/email-audit.md` (kit file, pasted verbatim) | Never run on G-Force. It's the highest real risk before the first staging test booking. Code reads (to check, not trust) are under *Standing caveats*. |
| 4 | `audits/admin-audit.md` (kit file) | Never run on G-Force; the owner will live in this panel. Its new singleton-editor check targets G-Force's seven custom settings pages directly. |
| 5 | `audits/accessibility-audit.md` (kit file) | June's axe pass fixed two markup issues (`62b6c30`) and left colour-contrast unresolved, with no decision recorded. |
| 6 | **003** — itemised consistency audit (report-only) | Never run (`ui-review/CONSISTENCY.md` doesn't exist). It has to see the merged UI after 4–5, which can touch shared components. |
| 7 | `gates/completeness-check.md` + `gates/cms-field-usage-check.md` (kit files) | The June runs predate the later merges. Known example: `Product::duration` is still editable (`ProductForm.php:101`) but isn't rendered in any view. |
| 8 | Fix prompts written from the reports of 6 and 7 (numbered when written) | Report-only passes produce follow-ups. Each one gets a numbered file and goes in here. |
| 9 | Tailor `verification/CHECKLIST.md` to G-Force (prompt to be written) | 001 copies the kit's checklist in untailored. It has to name G-Force's real money and email paths before anyone walks it. |
| 10 | `gates/pre-staging-gate.md` (kit file) | Re-run required: the 24 June GO is stale once 2–8 land. |
| 11 | Staging: Ben on the server | `staging-setup-brief-gforce.md` + `PRE-STAGING-CHECKLIST.md` §5. |
| 12 | `verification/CHECKLIST.md` + `verification/real-device-checks.md`, by hand on staging | What no automated check covers. Use `tester-feedback-triage.md` for the G-Force staff's feedback. |

## Hard constraints

- **001 before everything.** The others reference files it adds.
- **002 merged before 3–5.** Audit fixes then run against the guards rather than around them.
- **3 before 11.** A staging test booking sends real mail through the real worker.
- **4 and 5 merged before 6.** A consistency audit of a state that's about to change isn't real.
- **6–9 before 10, and 10 GO before 11.**

## Protocol

**No prompt text, no work.** If you are asked to do a numbered prompt and do not have its full text in front of
you, **stop and ask for it**. Never reconstruct one from this file, `DECISIONS.md` or a WIP branch. Kit-file
steps (3, 4, 5, 7, 10) run the kit file from `audits/` or `gates/` verbatim. That file *is* the prompt.

**One branch, one task.** If a prompt contains two unrelated fixes, split it and say so.

**Ask before merging.** This is not an unattended run.

**Tests:** `composer check` locally, and `phpunit.mysql.xml` against MySQL 8 before any merge that touches
queries, casts, JSON columns or money.

**Rebuild assets before any browser measurement.**

**Homepage is signed off.** Any pass that renders it screenshots it before and after, and reverts any homepage
change Ben didn't ask for.

**Stop at a clean point rather than half-finish a branch.**

## Verify the premise before you build

Read the files the prompt names and confirm the defect is still there. **Already fixed** → say so and stop.
**Partly fixed** → build only the remainder and record which half existed. **Reasoning wrong** → say so with
evidence and don't build it. Silent compliance and silent deviation are both worse than saying it.

Project examples:
- 9 Oct 2026: the brief said "the repo predates the new rules". True for the docs, but none of the four rules is
  actually broken in the code. 001 is docs and 002 is guards; neither is a fix.
- The June "consistency pass" (`4ae6009`) reported "verified consistent" from a scan. Scans miss things. That's
  why 003 enumerates.

## Standing caveats

- **Evidence from chat-Claude is from GitHub, not the Mac**, and every code read below is a hypothesis until
  someone presses the control (`false-green.md` #5).
- **Email (for step 3). Code reads on `be9e145`, not verified by doing:** 9 mailables; none sets `$tries`,
  `$backoff` or `$afterCommit` (grep `app/Mail`). No mail-test command exists in `app/Console/Commands`.
  `VoucherGiftMail` is sent from two places: the `IssuePurchasedVoucher` action (`:55`) and a Filament table
  action that calls `Mail::` directly (`VoucherResource.php:160`). That's the kit's sibling-path shape.
  Horizon supervisors watch only `default`, and no send uses `onQueue`, so the queue match looks right. Say so
  rather than chase it.
- **Mail logo on staging.** The mailer logo is an absolute URL built from `APP_URL`
  (`resources/views/vendor/mail/html/header.blade.php:11`). Behind staging basic-auth, recipients' clients can't
  fetch it, so staging emails will show a broken logo. That's expected on staging, not a production defect. The
  kit's bootstrap now prefers a CID-embedded PNG. Whether to switch is a decision for the email audit to
  record, not something to fix in passing.
- **Admin (for step 4).** The settings editors are seven custom `Filament\Pages\Page` subclasses
  (`app/Filament/Pages/Settings/`, base `SettingsPage`), which is exactly what the new singleton check targets.
  The dashboard registers the stock `AccountWidget` (`AdminPanelProvider.php:50`). Verify by loading, editing
  and saving each page, not by reading `SettingsPage`.
- **Old reports stay where they are.** June's audit reports live in `design-review/`, `code-review/`,
  `security-review/` and `qa/`. New kit audits write to `audits/reports/`. Don't move the old ones; they're
  history.

## Out of band

These kit files are deliberately **not** queued. Don't pick them up.

- **`bootstrap.md`.** G-Force was bootstrapped on 5 June. Re-running it would rewrite `CLAUDE.md`. 001 syncs
  the new rules into the existing file instead.
- **`port-react-mock-to-laravel.md`, `HOWTO-get-mock-and-run-port.md`, `wire-content-to-models.md`,
  `post-import-redesign.md`.** One-time Phase A/B steps, all done in June. The redesign was round 5B.
- **`content-to-cms-simple.md`, `simple-content-site-profile.md`.** These are for content sites. G-Force is a
  full app.
- **`ui-passes/01–04`.** Ran 15–16 June (`f868ac3`, `0a7c398`, `1643fc6`, `179d8fb`), and the kit says to run
  them once. The only part that never ran is pass 04's Step 1b, which is 003.
- **`design-audit`, `seo-audit`, `code-style-audit`, `security-audit`.** All ran 14 June. Since then the kit has
  changed by one line each:
  - design: a motion guard (G-Force has no motion layer);
  - seo: unchanged;
  - code-style: current-Laravel idioms;
  - security: spam-hardening (G-Force already has `ProtectsAgainstSpam`).

  None justifies a re-run now. An optional design-audit sweep before go-live is fine.
- **`add-motion-layer.md`.** Not adopted. The homepage is signed off and the owner hasn't asked for
  "cinematic".
- **Owner tasks:** real photos, the voucher/AFF/coaching T&Cs, the solicitor review, the cookie/analytics
  decision. These aren't code.

## Numbers, supersession and urgency

- **A number is never reused.** A replaced prompt gets the next number, and the old one is listed under
  *Withdrawn*.
- **Every prompt is a file.** List it here by filename.
- **Urgent goes to the front, and the reason is written here.**
- **New evidence mid-queue.** If verifying a merged prompt shows it didn't do what it claimed, write a new prompt
  ahead of everything that builds on the claim, naming the prompt it corrects.

## Withdrawn prompts — do not build

- **June `claude-code-prompt-…cms-field-cleanup` (post-merge: 6 orphans + Location address into course-event
  JSON-LD).** Never merged. Its findings are 3½ months old, so the gate re-runs at step 7 and a fresh numbered
  prompt is written from that report.
- **June itemised consistency audit prompt.** Never run. Replaced by **003**.
