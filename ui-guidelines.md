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

## ⚠️ Inconsistencies to resolve (owner punch-list — derived from the code)

Real drifts/risks found while deriving this doc. Two were since fixed (kept here as a record);
the remainder is a low-priority follow-up.

1. **~~Dead `.dark {}` block in `app.css`~~ — RESOLVED.** The leftover shadcn `.dark` palette
   override (with an off-brand orange `--primary`) was inert on the public site and contradicted
   "no dark mode / no new colours"; it has been **deleted**. The Filament admin themes itself
   separately via Tailwind's `dark:` variant.
2. **~~`/newsletter` "Join the list" card off-pattern~~ — RESOLVED.** It used
   `rounded-2xl border … shadow-sm`; now the standard **`border-2 border-secondary` flat card**
   (no shadow), matching every other card.
3. **Type-scale mid gap** (low priority, intentional for now) — see "Known gaps" at the bottom;
   a few display titles (service tiles, stat numerals, instructor name plates `text-3xl`) sit as
   explicit sizes between `text-h3` and `text-h2`. Not a bug; a future `display-card` token could
   absorb them.

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
- **Section transitions use vertical spacing ONLY** (the section rhythm above). Do **not** add a
  full-width horizontal divider line between top-level sections — no `border-b/-y border-secondary`
  (or any rule) on a `<section>` wrapper to separate it from the next. Adjacent sections are
  separated by their own `py-section*` padding; a colour change (white → `band-ink`/`bg-secondary`)
  is its own edge. (Component-internal rules are fine and unaffected: FAQ item separators
  (`divide-y` ), table/input borders, the eyebrow accent (`h-0.5 w-10 bg-primary` / `heading-rule`),
  and the brand **blue** accent rules — `border-*-4 border-primary` on the page-hero underline,
  the newsletter/gift band frames and card tops. The rule below is about navy *section* dividers.)

## Palette (`:root` in `app.css` — semantic tokens, oklch)

Use the **semantic token names** (`bg-primary`, `text-secondary`, `border-border`…), never
a raw hex/oklch.

| Token | Value (oklch) | Role |
| --- | --- | --- |
| `background` / `foreground` | `1 0 0` / `0.15 0.04 250` | white page / near-black navy ink text |
| `primary` / `primary-foreground` | `0.62 0.18 240` / `0.99 0 0` | brand action colour (bright mid-blue) + on-primary text |
| `secondary` (deep navy) | `0.28 0.14 255` | dark surfaces, borders, headings on light |
| `sky-deep` / `sky-bright` | `0.22 0.12 258` / `0.7 0.16 235` | gradient + accents (sky-bright = accent on dark bands) |
| `ink` | `0.12 0.03 250` | the darkest band (`band-ink`) |
| `muted` / `muted-foreground` | `0.96 0.01 250` / `0.45 0.03 250` | quiet surfaces / secondary text |
| `accent` | `0.88 0.06 240` | subtle hover wash |
| `destructive` | `0.6 0.24 27` | errors only |
| `border` / `input` / `ring` | `0.9 0.02 250` / … / `=primary` | hairlines / field borders / focus ring (= primary) |

**No new shades** — use these tokens only; never a raw hex/oklch or a new colour (not in views,
CSS or PDFs). Fonts: `font-display` = Bebas Neue (headings, `uppercase`), `font-sans` = Barlow
400/500/600/700 (body).

`--radius: 0` — **everything is sharp-cornered.** The radius scale (`--radius-sm…-2xl`) is **all
pinned to `var(--radius)` = 0**, so even `rounded-2xl` renders square — there is no way to get a
rounded corner from the utilities; don't rely on a `rounded-*` class to imply roundness. Shadows
are removed sitewide except the header's functional sticky shadow (no decorative `shadow-*` on
content). Helpers: `band-ink` (darkest band), `bg-photo-scrim` (text-over-photo), `heading-rule`
(short accent rule under a heading), `.reveal` (JS scroll-in, reduced-motion-safe), `bg-sky-gradient`
(compact hero band).

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
- **`<x-ui.feature-list :items>`** — the ONE feature / "what's included" list (the product-page
  benefit lists). Renders a blue **checkmark** (`text-primary`) + text row per item, **no left
  vertical line**. Pass the CMS items; add spacing via a merged class (`class="mt-8"`). Do **not**
  vary the bullet icon or row treatment per page — every "what's included" list looks identical.
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
- **`<x-site.instructor-card :instructor :showDisciplines :showBio :heading>`** — the ONE
  instructor card: a **square (1:1) `object-cover`** photo with the navy name/role band
  overlaid, optional discipline chips + bio beneath. Meet the Team renders it in full
  (`showDisciplines`/`showBio` default true, `heading="h2"`); the discipline-page teaser reuses
  the SAME partial with both false and `heading="h3"` (photo + band only). One source of truth —
  the two surfaces are identical by construction, never a near-copy.
- **`<x-site.discipline-instructors :instructors :heading>`** — the per-discipline "your
  instructors" cross-link section (Tandem/AFF/Coaching): a `<x-site.section-heading>` (eyebrow
  "The team" + per-page heading) + a `gap-4` grid of `<x-site.instructor-card>` (chips/bio hidden)
  + a grouped `<x-ui.arrow-link href="/meet-the-team">`. Tag-filtered; renders nothing when empty.
- **`<x-site.discipline-tags :disciplines :tone>`** — the discipline chips (Tandem / AFF /
  Coaching): a `border-2` primary-bordered chip, navy text on light / white text + `sky-bright`
  border on dark. No "Teaches" label above them — the chips stand alone.
- **`<x-site.price-card>` / `<x-site.pay-card>` / `<x-site.trust-grid>` / `<x-site.stars>`
  / `<x-site.avatar>`** — pricing, payment CTA, trust badges, ratings, avatars. (`avatar` =
  square brand avatar: photo or navy monogram fallback.)
- **`<x-site.header>` / `<x-site.footer>`** — chrome (header has the footer newsletter +
  the Why-Us dropdown; footer has the newsletter signup). The header's sticky bottom rule
  (`border-b-2 border-secondary`) is chrome, **not** a section divider.

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
- **Section pattern (every section follows this):** small blue **eyebrow** (caps label + the
  `h-0.5 w-10 bg-primary` accent line) → **heading** (`text-h2`, `heading-rule`) → optional
  `text-lead` → content. `<x-site.section-heading>` produces it; the FAQ and the discipline
  "your instructors" sections use the SAME component so they match.
- **Imagery:** instructor photos are **square (1:1) `object-cover`** (via `<x-site.instructor-card>`);
  service/hero photos are full-bleed `object-cover` behind a `bg-photo-scrim`; the editorial
  fallback for a missing photo is a navy monogram, never a generic placeholder. Images are plain
  `FileUpload`s resolved by `image_url`/`imageUrl()` (bundled `/images/...` path or upload path).
- **Icons:** lucide via `<x-icon>`. Feature/"what's included" lists use the **blue checkmark**
  (`check`, `text-primary`); trust badges use their CMS `icon` in `sky-bright` on the dark band.
  Don't swap an established icon per page (the Coaching list used `target` and drifted — now `check`).
- **Motion stack:** Alpine.js for small interactive state (header/menu, select, FAQ); CSS
  `.reveal` scroll-in (added by `app.js` via IntersectionObserver, and **skipped entirely** under
  `prefers-reduced-motion: reduce` so content is never hidden). Every transition is
  `motion-reduce:`-guarded.
- **Content is CMS-driven.** Pull copy/links/images from settings + content models; **hide**
  a link/section when its data is empty (e.g. a social link with no URL) rather than
  rendering a dead/generic one. Empty states are intentional (e.g. AFF "New course dates
  coming soon", the calendar hidden until a booking exists).
- **Homepage News+social:** Latest News is the dominant left column (real content); a
  compact navy "Follow us" card (real social links + a clearly-labelled curated photo grid)
  sits right. No "live feed" framing.

## Cross-page consistency (the anti-drift rule)

The recurring failure mode here is **cross-page drift**: an element is fine on each page in
isolation but differs between pages, because per-page review never compares them. It's how the
section dividers, the feature-list icons/lines, and the instructor cards each drifted. Defence:
every repeated element comes from ONE shared component and **must look identical on every page**.

| Repeated element | Shared source of truth | The rule |
| --- | --- | --- |
| Buttons / CTAs | `<x-ui.button>` (primary/outline/link) | no hand-styled buttons; no colour classes passed in |
| Arrow links (EXPLORE / MEET THE TEAM / All news) | `<x-ui.arrow-link>` | one animated arrow everywhere |
| Feature / "what's included" lists | `<x-ui.feature-list>` | blue checkmark + text, **no** left line; same icon every page |
| Section headings + eyebrows | `<x-site.section-heading>` | eyebrow `tracking-[0.25em]` + `text-h2` + `heading-rule` |
| Section transitions | — (spacing only) | **no** full-width divider line between sections |
| Instructor cards | `<x-site.instructor-card>` | square photo + navy band; Team = full, discipline = no chips/bio |
| Discipline chips | `<x-site.discipline-tags>` | bordered chip, no "Teaches" label |
| Forms under a heading | the page section | left-aligned to the heading's content edge (not `mx-auto`-centred) |
| Inputs / labels / date fields | `<x-ui.input>`/`select`/`textarea`/`label`/`date-field` | one focus treatment, labelled |
| Section rhythm | `py-section-sm lg:py-section` (bands `…-lg`) | same cadence on every page |

**A UI/consistency pass MUST compare across pages, not just within one** — take the SAME element
(a feature list, a section transition, a card, an eyebrow, a form-under-heading) on 3+ pages,
screenshot them side by side, and confirm they're identical and from the shared component. Drift
only shows when they're viewed together.

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
8. Section transitions are **spacing only** — no full-width divider line between sections (see
   Spacing & layout).
9. Verify at 1440 / 1280 / 1024 / 390 + a short laptop height; keyboard focus visible.

**Consistency passes — compare ACROSS pages, not just within one.** Per-page review misses
cross-page drift (it's how the section dividers crept onto some pages but not others — each page
passed its own check). As part of any consistency/UI pass, take the SAME transition zone (e.g. the
gap between two content sections, a hero→section boundary, a form-under-heading) on 3+ different
pages, screenshot them side by side, and reconcile — the inconsistency only shows when they're
compared together. This applies to any **repeated element** rendered on multiple pages — feature/
"what's included" lists, section transitions, instructor cards, eyebrows, CTAs: compare them across
pages (not just within one) and confirm they come from a single shared component.

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

### From the itemised consistency audit (`ui-review/CONSISTENCY.md`, 9 Oct 2026, on `e7b0921`)

Enumerated and compared, not noticed in passing. Details, evidence and file:line refs are in
`ui-review/CONSISTENCY.md`. Each item is its own small follow-up.

- **C-1** Coached intro heading is a hand copy of `<x-site.section-heading>`. Its eyebrow is a price, and
  it's the only product intro with its own CTA (owner question).
- **C-2** AFF trust band heading is built inline. It matches the homepage band (reference); add a `rule`
  option to the component and use it.
- **C-3** `/news` dates use `tracking-[0.2em]` (homepage cards: `0.25em`), and the "G-Force News" label
  is `0.3em`.
- **C-4** Testimonial-grid role labels (400, `0.2em`, white/70) differ from every other role label (700,
  `0.25em`, sky-bright).
- **C-5** Meta labels at `0.2em` (news byline, AFF course duration): pick one meta-label tracking.
- **C-6** Three check-list treatments (feature-list, inline pay-card list, price-card list); the AFF
  pay-card has no list.
- **C-7** Prices typed into CMS copy (FAQ answers, Tandem hero subtitle, Coached eyebrow, AFF per-jump,
  terms) drift from the product price.
- **C-8** Feature-split images are 16:10 on mobile but ~1.42:1 on desktop, while the admin crops 16:10.
- **C-9** Panel and account headings vary (ink 24/20/30px vs public navy 24px).
- **C-10** The newsletter signup panel is framed two ways (`/contact` light border, `/newsletter` navy).
- **C-11** Back links type `←` into a link button; there's no back direction on `<x-ui.arrow-link>`.
- **C-12** "Sending..." vs "Sending…" across the enquiry forms.
- **C-13** Tandem/AFF intro wrappers hand-roll `py-16 lg:py-24` (same values as the section tokens).
- **C-14** (record only) Homepage "Follow us" arrow links are hand-rolled; the homepage is the reference.
- **C-15** Shop `price_label` is free text, not the Money presenter.
