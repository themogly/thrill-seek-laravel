# 016 — Brand contrast: add a `primary-strong` token for text and button fills (option B)

One branch, one task. Read `CLAUDE.md` (design rules: palette only, the homepage is signed off),
`ui-guidelines.md` (palette and buttons), `DECISIONS.md` ("OWNER DECISION — PENDING: brand colour contrast")
and `audits/reports/accessibility-audit.md` Phase 1. **Size: medium. VISUAL: Ben looks before merging.**

`git checkout main && git pull` → `git checkout -b ui/primary-strong-contrast`.

> **Why this exists.** Ben, 9 Oct 2026, answering the contrast decision: **option B**. He knows it visibly
> changes buttons and links, including on the homepage. **This prompt is the authorised exception to the
> homepage freeze, for colour only.**
>
> **Verified on `origin/main` = `7e01bfe` by code read; numbers from the a11y audit (axe, by doing):**
> - `--primary: oklch(0.62 0.18 240)` = `#008fe6` (`resources/css/app.css:82`) is 3.45–3.5:1 as text on
>   white (active nav, eyebrows, dates, links, "£210 per jump").
> - It's 3.36–3.41:1 as the fill under white button text (every primary button).
> - That's 178 axe nodes: the only remaining axe failures.
> - **Nearest passing shade:** `oklch(0.545 0.18 240)` = `#0078cc` (4.63 as text, 4.51 as a fill).
> - `--destructive: oklch(0.6 0.24 27)` (`:94`) is 4.32–4.45:1. Its nearest passing shade is
>   `oklch(0.58 0.24 27)`.

## Build

1. Add `--primary-strong: oklch(0.545 0.18 240)` and the Tailwind colour `primary-strong`. Keep `--primary`
   unchanged.
2. **Destructive is darkened in place** to `oklch(0.58 0.24 27)`. Option B's split exists to keep the bright
   *brand* accent; the error red isn't a brand accent, so a split would only add a token. Record this reading
   of "option B".
3. **Enumerate before changing.** Inventory every use of `primary` (171 references in views, plus CSS and
   components) as text, as a fill under text, or as decoration (rules, borders, accent lines, icons beside
   text, large non-text fills). Commit that table to `audits/reports/primary-usage.md`.
4. Move **text** and **fills under text** to `primary-strong`, preferably at the shared component (button
   variants, eyebrow, link, nav active). Leave decoration on `primary`. Where it's unclear (e.g. a large
   display heading over 24px, which only needs 3:1), measure and record.
5. Re-run axe on the 21 pages at 1440 and 390, signed out, with real fonts loaded. Target: **0
   `color-contrast` failures.**
6. Update `ui-guidelines.md`: the palette, when to use `primary` vs `primary-strong`, and the button entry.

## Rules

- No new colours beyond these two values. No hue change, darker shades only.
- The Filament admin isn't in scope (it uses Blue since the admin audit).
- Email templates: check whether they use `#008fe6` for text or buttons. If they do, list it in DECISIONS and
  don't change it here. Mail markup has its own render check.

## Tests

- A structural guard: no `text-primary` / `bg-primary` under text classes outside an allowlist of decorative
  uses with reasons. Prove it with a planted violation.
- The axe run, recorded (before 178 nodes → after 0). Name the axe version.

## Finish

`composer check` green. **Before and after** screenshots of the homepage, `/tandem`, `/aff`, `/coached`, one
form and one account page at 1440 and 390 (cropped JPEGs, one folder) in `ui-review/primary-strong/`.
DECISIONS: mark the owner decision answered (B) and add the entry. Push the branch.

**Do not merge, on any run, unattended included.** Ben looks at the screenshots and merges himself.
