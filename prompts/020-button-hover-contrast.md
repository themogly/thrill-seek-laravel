# 020 — Primary button hover and active states fail contrast after 016

One branch, one task. Read `CLAUDE.md`, `ui-guidelines.md` (palette, buttons) and `DECISIONS.md` (the 016
`primary-strong` entry and its `OWNER DECISION — PENDING` on hover washes). **Size: small. Depends on 016
being merged.** If `--primary-strong` isn't on `main`, stop.

`git checkout main && git pull` → `git checkout -b ui/button-hover-contrast`.

> **Why this exists.** Ben, 9 Oct 2026, answering 016's owner decision: **darken on hover** rather than keep
> the lighter wash.
>
> - **The cause:** on `be9e145`, the primary variant was
>   `bg-primary text-primary-foreground hover:bg-primary/85 active:bg-primary/75`
>   (`components/ui/button.blade.php:18`). 016 moved the fill to `primary-strong`, so the opacity wash now
>   lightens it toward the page background.
> - **The result:** 016's gap report measured white text on the hover state at **3.54:1** and on the active
>   state at **2.99:1**. The button passes at rest and fails the moment a mouse is on it.
>
> Confirm the current classes and the measurements on `main` first.

## Build

- Hover and active become **darker** solid shades of the same hue (e.g. `oklch(0.50 0.18 240)` and
  `oklch(0.46 0.18 240)`), as tokens next to `--primary-strong`. Measure white text on each: both must be at
  least 4.5:1. Record the values.
- The same check for the outline and link variants' hover and active states on light and on dark surfaces.
  Fix any that fail the same way.
- Update `ui-guidelines.md` (the buttons entry).

## Rules

- Rest-state colours don't change. Screenshots at rest must be pixel-identical; only hover and active move.
- Keep the transitions and `prefers-reduced-motion` behaviour.

## Tests

- A test that resolves each variant's hover and active colour from the CSS tokens and asserts at least 4.5:1
  against its text. Red on current `main`.
- Hover and active screenshots of the primary button (Playwright `hover()` / mousedown), before and after,
  as cropped JPEGs.

## Finish

`composer check` green. DECISIONS: mark the hover decision answered. Push the branch. **Do not merge**
(unless on an authorised unattended run).
