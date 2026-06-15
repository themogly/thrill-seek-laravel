# UI Foundations — type scale + spacing (pass 1 of 4)

Audit of the CURRENT state, then (Step 2–4) the intentional system that replaces it. The
brand identity is fixed (navy/blue/white, sharp corners, photo-led, Bebas display +
Barlow body) — this pass systematises craft, it is not a rebrand.

## Step 1 — Audit (current state)

### Fonts (loaded via `vite.config.js` → `bunny()`, self-hosted woff2, `@fonts` in head)
- **Bebas Neue** 400 — the condensed display face (`--font-display`), used on every heading
  (`h1/h2/h3/.font-display` in `app.css` base layer) + decorative monograms.
- **Barlow** 400/500/600/700/**800** — body face (`--font-sans`).
- Loading is already decent: self-hosted, subset by `unicode-range`, `font-display: swap`,
  woff2. **Issues:** (a) **Barlow 800 is loaded but never used** — `font-extrabold` appears
  0× in the views (weights actually used: 400 body, 500 medium ×14, 600 semibold ×33,
  700 bold ×89). (b) No `<link rel="preload">` for the primary body weight, so first paint
  can show a brief swap.

### Type — current scale is raw Tailwind defaults + ad-hoc one-offs
Font-size class frequency across `resources/views/` (no semantic tokens exist today):

| size | uses | | size | uses |
| --- | --- | --- | --- | --- |
| `text-sm` | 113 | | `text-4xl` | 11 |
| `text-xs` | 61 | | `text-3xl` | 12 |
| `text-2xl` | 42 | | `text-7xl` | 8 |
| `text-lg` | 26 | | `text-6xl` | 8 |
| `text-xl` | 14 | | `text-8xl` | 3 |
| `text-5xl` | 11 | | `text-base` | 4 |

- **No design tokens** — everything is a raw Tailwind step, so headings are picked
  per-element rather than from a named scale. Heading steps jump inconsistently
  (`text-5xl md:text-7xl`, `text-6xl sm:text-8xl lg:text-[10.5rem]`,
  `text-4xl md:text-6xl`).
- **Ad-hoc / magic sizes:** `text-[10.5rem]` (home hero), `text-[10rem]` (instructor &
  404 monograms), `text-[7rem]` (photo-tile monogram), `text-[11px]` (admin calendar).
- **Line-height** is ad-hoc per heading: `leading-[0.95]`, `leading-[0.92]`,
  `leading-[0.88]`, `leading-none`. Body text uses Tailwind's default `1.5` — slightly
  tight for comfortable reading.
- **Letter-spacing** is scattered: `tracking-wide` ×53, `tracking-widest` ×36,
  `tracking-[0.25em]` ×9, `tracking-[0.3em]` ×6, `tracking-[0.2em]` ×6, `[0.4em]` ×1.
- **Where headings are centralised (good — apply the scale here):** `<x-site.section-heading>`
  (`h2` = `text-5xl md:text-7xl`), `<x-site.page-hero>` (`h1` = `text-5xl md:text-7xl
  lg:text-8xl`), and the `app.css` base layer (`h1,h2,h3,.font-display`).

### Spacing — 4px-grid already, but values chosen ad-hoc per section
Tailwind's `--spacing` base is `0.25rem` (4px) so the system is already a 4/8 grid; the
inconsistency is in WHICH step each section picks.

- **Section vertical padding:** `py-16` ×14, `py-24` ×10, `py-20` ×7, `py-28` ×3,
  `py-12` ×3, plus one-offs `py-40`, `py-32`, `py-14`, `py-10`. The shared
  `<x-site.section>` uses `py-16 lg:py-24` — but several sections hand-roll different
  values, so the page-to-page rhythm drifts.
- **Grid/flex gaps:** `gap-4` ×45, `gap-3` ×33, `gap-2` ×20, `gap-6` ×8, `gap-12` ×6,
  `gap-10`, `gap-8`, `gap-16`, `gap-14`, `gap-5` — a wide spread with no clear "card grid"
  vs "tight inline" convention.
- **Heading block margin:** `section-heading` uses `mb-14`; lead `mt-5`; sub-leads `mt-6`.
- **Content measure / max-width:** `max-w-7xl` ×17 (page shell), `max-w-3xl` ×11,
  `max-w-2xl` ×5, `max-w-4xl` ×4 — prose/lead columns vary (2xl/3xl/4xl) with no single
  reading measure, so text-column width is inconsistent.

### Summary of what's "generic/templated"
1. No named type scale — sizes & line-heights are picked per element.
2. Body line-height (1.5) is a touch tight; no consistent reading measure.
3. Section rhythm and grid gaps drift between similar sections.
4. A handful of magic-number sizes and an unused font weight.

## Step 2–4 — the new system

_(filled in as implemented; documented here so later passes + future work reuse it.)_
