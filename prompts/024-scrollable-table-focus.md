# 024 — The account payments table scrolls sideways on phones but can't be reached by keyboard

One branch, one task. Read `CLAUDE.md`, `ui-guidelines.md` and `DECISIONS.md` (the 016 entry's "unrelated axe
item"). **Size: small.**

`git checkout main && git pull` → `git checkout -b a11y/scrollable-table-focus`.

> **Why this exists.** 016's axe run flagged `scrollable-region-focusable` on the account payments table at
> 390px. Ben, 9 Oct 2026: yes, fix it.
>
> **Verified on `origin/main` = `fd6bc55` by grep:** `resources/views/account/payments/index.blade.php` is
> the only account view with an `overflow-x-auto` wrapper. At 390 the table is wider than the screen, so the
> wrapper scrolls, and a keyboard user can't focus it to scroll.

## Build

- Make the scroll region focusable and named: `tabindex="0"`, `role="region"` and an `aria-label` (e.g.
  "Payments"). Give it the site's standard focus ring.
- If other tables in `resources/views` (public or account) use the same pattern, route them through one
  shared scroll-wrapper component rather than patching each. Grep for it.

## Rules

- No visual change at rest. The focus ring appears only on keyboard focus (`focus-visible`).

## Tests

- axe (or a structural test) passes `scrollable-region-focusable` on the payments page at 390. Red on
  current `main`.
- A structural test: every `overflow-x-auto` wrapper around a `<table>` uses the shared component. Prove it
  with a planted violation.

## Finish

`composer check` green. DECISIONS entry. Push the branch. **Do not merge** (unless on an authorised
unattended run).
