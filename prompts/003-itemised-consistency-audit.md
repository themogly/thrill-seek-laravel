# 003 — Itemised cross-page consistency audit (report-only)

One branch, one task. Read `CLAUDE.md`, `DECISIONS.md` (the June ui-pass entries and the 16 June consistency-pass
entry), `ui-guidelines.md`, `ui-review/FOUNDATIONS.md`, `COMPONENTS.md`, `PAGES.md`, and
`ui-passes/04-guidelines.md`. This prompt runs **only Step 1b** of that file.
**Size: medium. Report-only.** Depends on the admin and accessibility audits (RUNNING-ORDER steps 4–5) being
merged. Confirm on `main`; if either is still on a branch, stop and say so.

`git checkout main && git pull` → `git checkout -b docs/consistency-audit`. State the starting commit.

> **Why this exists.** UI passes 01–04 ran on 15–16 June, but pass 04's itemised audit, the step that enumerates
> every instance and diffs them, never did. On `be9e145` there's no `ui-review/CONSISTENCY.md`.
>
> The 16 June "consistency pass over merged state — verified consistent" (`4ae6009`) was a scan, and scans are
> what missed the newspaper-icon news heading. Enumeration doesn't depend on someone happening to notice.
>
> **Premise unverified on your machine.** Confirm `CONSISTENCY.md` is still absent. If it exists, say so and stop.

## Build

Follow `ui-passes/04-guidelines.md` Step 1b exactly: one table per element type, one row per instance (page and
location), attribute columns, and flag any row that differs from the majority. Cover the kit's list:
- section headings;
- eyebrows/labels;
- buttons and link styles;
- feature lists;
- cards;
- section transitions;
- section spacing rhythm;
- imagery.

Add G-Force's own repeated elements:
- **`x-ui.arrow-link`** and every hand-rolled "explore"/arrow link that isn't it;
- **discipline chips** (tandem / AFF / coached) wherever they appear;
- **instructor portraits and teaser/team cards**: crop ratio, role plate, name treatment;
- **page heroes**: photographic vs not, height cap, scrim;
- **price displays**: format (`£260` vs `£260.00`) and whether each one comes from the shared money presenter;
- **booking/enquiry CTAs** across product pages;
- **FAQ accordions**;
- **"Meet your team" strips** on the course pages.

**Method:**
- Enumerate from the source first: grep each component tag and each repeated class pattern, so nothing is missed
  because it didn't look odd.
- Then render: rebuild assets, and screenshot each element type at 1440 and 390 on every page that has it.
- A row is only "consistent" if you looked at it.

**The homepage is the reference, not a suspect.** It's signed off. Where it differs from the majority, record it
and presume the homepage is right. Flag the others, and mark that row "homepage = signed-off reference".

## Report

`ui-review/CONSISTENCY.md`: the tables, then an **"⚠️ odd ones out"** list. For each item:
- what it is, where it is (page and file:line), and how it differs from the majority;
- a one-line proposed follow-up, each scoped as its own small branch. Prefer "consolidate into the one shared
  component" over "edit to match". Ben numbers them into `RUNNING-ORDER.md`.

Then copy the odd-ones-out list into the **"known gaps / follow-ups"** section of `ui-guidelines.md`. That's the
only change to that file.

Keep it short and honest. A clean result for an element type is a good outcome, and one line says so.

## Rules

- **Report-only.** No change to any view, component, CSS, token, config, test or app file. The only files
  touched are `ui-review/CONSISTENCY.md`, the screenshots under `ui-review/consistency-audit/`, the known-gaps
  section of `ui-guidelines.md`, and one `DECISIONS.md` entry.
- Nothing is flagged that wasn't actually enumerated and compared. Nothing is called consistent that wasn't
  looked at.
- Don't propose changes to the homepage.

## Tests

None. Report-only. `composer check` must stay green, with the test count unchanged.

## Finish

- `composer check` green.
- `DECISIONS.md` entry: the audit ran, the count of odd ones out, the starting commit.
- Push `docs/consistency-audit`. **Do not merge.**
- Summary to Ben: the odd-ones-out list, with your view of which are worth fixing before launch and which can
  wait.
