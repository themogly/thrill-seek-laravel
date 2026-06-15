# UI guidelines — the G-Force Skydiving design system

This is **our** project's concrete design system — the actual tokens, components and
conventions that exist in the code after the 4-pass UI level-up. The transferable
`frontend-design` skill is the general craft; **this doc is our specifics**. Read it before
building new UI here, and match it rather than reinventing.

**The one rule:** use the tokens and shared components below. Never hand-roll a
`text-[…]`/`py-[…]` value, a one-off button/input/date field, or a new colour.

Brand identity is fixed: **navy / blue / white**, **sharp corners** (radius 0), photo-led,
bold condensed display headings. No new brand colours, no dark mode, no rounded-glow.

---

## Type scale (`resources/css/app.css` `@theme`)

Fluid (`clamp`) so headings stay bold on desktop without overflowing mobile. Use the token
utility; **never** a raw `text-5xl`/`text-[Nrem]`.

| Utility | Size (min → max) | Line-height | Use for |
| --- | --- | --- | --- |
| `text-display` | 3.25 → 8.5rem | 0.9 | the home / monster hero only |
| `text-h1` | 2.75 → 5.5rem | 0.92 | page-hero titles (`<x-site.page-hero>`) |
| `text-h2` | 2.25 → 4.25rem | 0.95 | section headings (`<x-site.section-heading>`, feature bands) |
| `text-h3` | 1.5 → 1.875rem | 1.05 | card / sub-headings (news cards, "Follow us", etc.) |
| `text-lead` | 1.1875rem (19px) | 1.6 | lead paragraphs / hero subtitles (pair with `max-w-measure`) |
| `text-body` | 1rem (16px) | 1.65 | base reading text — set on `<body>`, applies everywhere |

- Tailwind's small steps (`text-sm` 14px, `text-xs` 12px) are fine for **genuine small UI
  text** (labels, captions, badges, eyebrows). Don't use them for headings or body copy.
- **Fonts:** `font-display` = Bebas Neue 400 (headings, `uppercase`); `font-sans` = Barlow
  400/500/600/700 (body). Self-hosted via `vite.config.js`, `font-display: swap`.
- **Letter-spacing** lives on `tracking-*` utilities (not the tokens). Eyebrow labels
  standardise on `tracking-[0.25em]`; uppercase nav/buttons use `tracking-widest`.

## Spacing & layout

- **Section rhythm — 2 steps, always tokens:**
  - Standard section: `py-section-sm lg:py-section` (64 → 96px). `<x-site.section>` does this.
  - Dramatic dark/photo **feature band**: `py-section lg:py-section-lg` (96 → 128px).
- **Reading measure:** `max-w-measure` (68ch) for any text column (leads, body, the FAQ
  accordion). Text shouldn't run full-width.
- **Page shell:** `mx-auto max-w-7xl px-4 lg:px-8` (the `<x-site.section>` default).
- 4px grid throughout (Tailwind default `--spacing`); gaps `gap-4`/`gap-6` etc.

## Palette (`:root` in `app.css` — semantic tokens, oklch)

Use the **semantic token names** (`bg-primary`, `text-secondary`, `border-border`…), never
a raw hex/oklch.

| Token | Role |
| --- | --- |
| `background` / `foreground` | white page / near-black navy ink text |
| `primary` (bright mid-blue) / `primary-foreground` | the brand action colour + on-primary text |
| `secondary` (deep navy) | dark surfaces, borders, headings on light |
| `sky-deep` / `sky-bright` | gradient + accents (sky-bright = accent on dark bands) |
| `ink` | the darkest band (`band-ink`) |
| `muted` / `muted-foreground` | quiet surfaces / secondary text |
| `accent` | subtle hover wash |
| `destructive` | errors only |
| `border` / `input` / `ring` | hairlines / field borders / focus ring (= primary) |

`--radius: 0` — **everything is sharp-cornered.** Helpers: `band-ink` (darkest band),
`bg-photo-scrim` (text-over-photo), `heading-rule` (short accent rule under a heading),
`.reveal` (JS scroll-in, reduced-motion-safe).

## Components — the catalogue

### `x-ui` (generic interactive primitives)
- **`<x-ui.button>`** — the ONE button. Variants `primary` / `outline` / `link`; sizes
  `default` / `sm` / `lg` / `icon`. States (hover/active/focus-visible/disabled) +
  reduced-motion baked in. Lead an icon for actions, trail for directional; `size="icon"`
  needs `aria-label`. Loading: `wire:loading.attr="disabled"` + a slot swap. **Never** a
  hand-styled button.
- **`<x-ui.input>` / `<x-ui.textarea>` / `<x-ui.select>`** — form controls; one focus
  treatment (`focus-visible:ring-2 ring-ring` + brand-blue border), `h-11`, `border-2`,
  sharp. `select` is an accessible WAI-ARIA combobox.
- **`<x-ui.date-field>`** — the ONE date input. Pass `model` (wire:model prop), `label`,
  `min`/`max` (Y-m-d). Keeps a native `<input type="date">` as the value carrier (same
  submitted `YYYY-MM-DD`); overlays a branded Alpine calendar (month + year jump) on
  fine-pointer desktops, native OS picker on mobile. **Never** a bare `<input type="date">`.
- **`<x-ui.label for>`** — field labels (placeholders are not labels).
- **`<x-ui.arrow-link>`** — the navigational "All news → / Read more →" link. With `href`
  → an `<a>` (own focus ring + hover); without → an in-card `<span>` cue (arrow slides on
  the parent card's `group` hover).
- **`<x-icon name>`** — inline lucide SVGs; auto-sized in buttons.
- **`<x-ui.toaster>`** — the toast stack (success/error feedback; the simple-form pattern).

### `x-site` (page composition)
- **`<x-site.page-hero :title :subtitle :image :compact>`** — every page's hero. With an
  image → full-bleed photographic band; without → compact navy-gradient band (never flat
  `ink` on a secondary page). Title uses `text-display`/`text-h1`.
- **`<x-site.section :id>`** — the standard section wrapper (max-w-7xl + `py-section`).
- **`<x-site.section-heading :eyebrow :title :lead :light>`** — the canonical section
  header (eyebrow + `text-h2` + `heading-rule` + `text-lead`). Use this for section titles.
- **`<x-site.photo-tile>`** — the photo-led tile (image + navy scrim caption, or a navy
  monogram fallback). The pattern for Hall of Fame / Testimonials grids.
- **`<x-site.faq-section :faqs>`** — the per-page FAQ accordion (also the **reference
  disclosure pattern**: real `<button>`, `aria-expanded`, focus-visible ring, motion-reduce,
  answers always in the DOM + FAQPage JSON-LD). Constrained to `max-w-measure`.
- **`<x-site.feature-split>`** — image-bleeds-to-edge + text column.
- **`<x-site.price-card>` / `<x-site.pay-card>` / `<x-site.trust-grid>` / `<x-site.stars>`
  / `<x-site.avatar>`** — pricing, payment CTA, trust badges, ratings, avatars.
- **`<x-site.header>` / `<x-site.footer>`** — chrome (header has the footer newsletter +
  the Why-Us dropdown; footer has the newsletter signup).

## Conventions

- **Eyebrow label:** `text-sm/xs font-bold uppercase tracking-[0.25em] text-primary` (or
  `text-sky-bright` on dark) with a short `h-0.5 w-10 bg-primary` rule before it.
- **Focus ring (everywhere):** `focus-visible:ring-2 ring-ring` (brand blue) — buttons add
  `ring-offset-2`; inputs change border to primary + ring; on dark surfaces use
  `ring-inset`. Keyboard focus must always be visible.
- **Motion:** transitions are subtle and **always** `motion-reduce:`-guarded; the `.reveal`
  scroll-in never hides content without JS.
- **Native controls:** branded custom on fine-pointer desktop, native on touch/mobile (see
  `date-field`); DOB pickers open ~18 years back / offer a year jump.
- **Content is CMS-driven.** Pull copy/links/images from settings + content models; **hide**
  a link/section when its data is empty (e.g. a social link with no URL) rather than
  rendering a dead/generic one. Empty states are intentional (e.g. AFF "New course dates
  coming soon", the calendar hidden until a booking exists).
- **Homepage News+social:** Latest News is the dominant left column (real content); a
  compact navy "Follow us" card (real social links + a clearly-labelled curated photo grid)
  sits right. No "live feed" framing.

## Adding a new page or section — checklist

1. Hero via `<x-site.page-hero>`; content in `<x-site.section>` (or `py-section` rhythm).
2. Section titles via `<x-site.section-heading>` (or `text-h2` + eyebrow + `heading-rule`).
3. Body/lead in `text-body`/`text-lead`, constrained with `max-w-measure`.
4. Every button = `<x-ui.button>`; every field = `<x-ui.input>`/`select`/`date-field` +
   `<x-ui.label>`; links = `<x-ui.arrow-link>`.
5. Palette tokens only; sharp corners; eyebrow + focus-ring + motion conventions above.
6. CMS-drive the content; hide empty bits; intentional empty state.
7. New indexable page inherits `<head>` meta automatically; add JSON-LD if it has a rich
   entity (see `App\Support\StructuredData`).
8. Verify at 1440 / 1280 / 1024 / 390 + a short laptop height; keyboard focus visible.

## Known gaps / follow-ups (noticed during this pass; NOT fixed here)

- **Type-scale mid gap:** there's a jump between `text-h3` (≤1.875rem) and `text-h2`
  (≤4.25rem). A few deliberate display titles sit in it as explicit sizes — home service-
  tile titles (`text-4xl/5xl`), the about/news stat numerals, instructor name plates
  (`text-3xl`). A future "display-card" token (~2.5–3rem) could absorb these so they're
  tokenised too. Low priority (they read as intentional display type).
- **Home "Explore" service-card cue** still uses a bespoke `border-b` treatment rather than
  `<x-ui.arrow-link>` (its dark-card style differs). Could be folded into an arrow-link
  variant.
- **A few one-off `tracking-*` values remain** by intent (hero eyebrow `[0.4em]`, stat
  labels `[0.2em]`); most eyebrows are standardised to `[0.25em]`.
- **Owner content, not UI:** social profile URLs and curated gallery photos are still
  placeholder defaults; real testimonials/instructor photos to be added (see
  `COMPLETENESS-CHECK.md`).
