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

## Step 2–4 — the new system (implemented)

All tokens live in `resources/css/app.css` `@theme inline`. They are the **canonical**
scale — new UI must use them, not raw `text-*`/`py-*` guesses.

### Type scale (`--text-*` → `text-{name}` utilities)
Fluid (`clamp`) so headings stay bold on desktop without overflowing small screens; the
condensed Bebas character is preserved. Letter-spacing stays on the existing `tracking-*`
utilities (the brand already manages it there).

| token | size (clamp min → max) | line-height | used for |
| --- | --- | --- | --- |
| `text-display` | 3.25rem → **8.5rem** | 0.9 | home / monster hero (was the `text-[10.5rem]` one-off — refined down, still huge) |
| `text-h1` | 2.75rem → **5.5rem** | 0.92 | page-hero titles |
| `text-h2` | 2.25rem → **4.25rem** | 0.95 | section headings (`<x-site.section-heading>`) |
| `text-h3` | 1.5rem → **1.875rem** | 1.05 | card / sub-headings |
| `text-lead` | **1.1875rem** (19px) | 1.6 | lead paragraphs / hero subtitles |
| `text-body` | **1rem** (16px) | **1.65** | base reading text (set on `body`) |

- **Base body** now renders at 16px / **1.65** line-height (was the default 1.5) with
  `optimizeLegibility` + antialiasing — the biggest readability win, applies everywhere.
- The default Tailwind steps (`text-sm`, `text-xs`, `text-2xl`, …) remain available for
  small UI text and are untouched this pass; later passes migrate more of them.

### Spacing system (`--spacing-*` / `--container-*`)
The site is already on Tailwind's 4px grid; these tokens formalise the rhythm.

| token | value | utility | used for |
| --- | --- | --- | --- |
| `--spacing-section` | 6rem (96px) | `py-section` | desktop section block |
| `--spacing-section-sm` | 4rem (64px) | `py-section-sm` | mobile section block |
| `--spacing-section-lg` | 8rem (128px) | `py-section-lg` | dramatic dark/photo feature bands (added pass 3) |
| `--container-measure` | 68ch | `max-w-measure` | reading column for body/lead text |

- `<x-site.section>` and `<x-site.page-hero>` now use `py-section-sm lg:py-section` (same
  64/96 rhythm, tokenised) so every section that goes through them shares one rhythm.
- Lead/body text columns use `max-w-measure` (≈68ch) for a comfortable reading length.

### Font loading
- Dropped the unused **Barlow 800** weight from `vite.config.js` (0 `font-extrabold`
  usages) — one fewer font file. Loaded weights are now 400/500/600/700 + Bebas 400.
- Loading was already good (self-hosted woff2, `unicode-range` subset, `font-display:
  swap`). A `<link rel="preload">` for the body weight remains a possible future tweak
  (the hashed filename makes a hardcoded preload fragile, so deferred).

### Applied this pass (shared infra — propagates everywhere)
`resources/css/app.css` (tokens + base body), `components/site/section.blade.php`,
`section-heading.blade.php`, `page-hero.blade.php`, `pages/home.blade.php` (hero),
`vite.config.js`. Verified at 1440 / 1280 / 1024 / 390 and a short 1440×560 height across
home, tandem, AFF, privacy and contact — hierarchy reads clearly, body is more readable,
section rhythm is consistent, and the brand look (navy/blue/white, sharp corners, bold
Bebas headings) is unchanged. Before/after screenshots in `ui-review/before` and
`ui-review/after`.

### For later passes (2–4)
- Pass 2 (buttons/components) and pass 3 (page polish) should migrate remaining ad-hoc
  `text-*`/`py-*` to these tokens (card titles → `text-h3`, more sections → `py-section`,
  the footer tagline, etc.) and tidy the `tracking-*` spread into a small set.

