# UI Components — buttons, inputs, links (pass 2 of 4)

Audit of the interactive layer, then (Step 2–4) the polish. Builds on pass 1's tokens
(`text-h3/lead/body`, `py-section`, `max-w-measure`). Brand stays fixed (navy/blue/white,
sharp corners, no rounded-glow, no new colours).

## Step 1 — Audit (current state)

### Buttons — `<x-ui.button>` is already the shared system (good base)
`resources/views/components/ui/button.blade.php` — 3 variants, 4 sizes:
- **variants:** `primary` (solid blue), `outline` (2px border-current), `link` (inline
  action; inherits the base `font-bold uppercase tracking-widest`).
- **sizes:** `default` (h-11 px-6 text-sm), `sm` (h-9 px-4 text-xs), `lg` (h-14 px-10
  text-base), `icon` (h-11 w-11). Heights/padding/text relate cleanly (44/36/56px).
- **states present:** `hover:` (bg shift), `focus-visible:ring-2 ring-ring ring-offset-2`
  (✓ keyboard), `disabled:` (opacity + not-allowed). Icons sized once via
  `[&_svg]:size-4` + `gap-2`.
- **gaps:**
  - **No `active:` (press) state** — buttons don't acknowledge the click.
  - **`transition-colors` is not `motion-reduce`-guarded** (minor; colour transitions are
    low-risk but the rest of the site guards motion).
  - **One-off button outside the system:** the header mobile "BOOK NOW"
    (`components/site/header.blade.php:173`) is a hand-styled
    `<a class="bg-primary px-4 py-3.5 …">` instead of `<x-ui.button>`.
  - Other `<button>`s are legitimately custom interactive widgets (FAQ accordion, select
    trigger, Why-Us dropdown, slot-pickers, account nav) — not "buttons", reviewed below.

### Form inputs — components exist and are mostly consistent
- `<x-ui.input>` / `<x-ui.textarea>`: `h-11`/`min-h-[60px]`, `border-2 border-input`, sharp
  corners, `focus-visible:border-primary focus-visible:ring-1 focus-visible:ring-ring`.
- `<x-ui.select>`: accessible WAI-ARIA combobox; trigger matches input height/border BUT
  uses `focus:` (not `focus-visible:`) and `focus:ring-1` — **inconsistent focus
  convention** with the inputs/buttons.
- `<x-ui.label>`: `text-sm font-medium`, `for` association. ✓
- **Focus ring strength is `ring-1` on inputs vs `ring-2` on buttons** — inconsistent; a
  unified 2px brand-blue focus-visible ring would read better for keyboard users.
- **Errors:** the booking/coached forms use `<x-booking.field :error>` which renders
  `<p class="text-destructive" role="alert">` ✓. The simpler forms (contact, newsletter)
  use the documented **toast** pattern (DECISIONS: "the design has no inline error
  markup") — kept as-is; this pass does not convert toasts to inline (that's a documented
  decision, not a defect).
- **Width:** the booking flow is `max-w-3xl` (768px), the contact form a 2-col card in a
  `2fr_1fr` grid — both already constrained, not full-bleed. ✓

### Links — the arrow "read more" pattern is ad-hoc (inconsistent)
Same intent, three hand-rolled variants of `text-sm font-bold uppercase tracking-widest`
+ trailing `arrow-right`:
- `home.blade.php:183` "All news" — standalone `<a>`, `hover:underline`, **arrow static**.
- `news/index.blade.php:35` "Read more" — `<span>` inside a card link, **arrow animates**
  (`group-hover:translate-x-1`).
- `home.blade.php:67` "Explore" — `<span>` inside a dark card, `border-b-2` treatment.
No shared component; animation + focus handling differ. The `link` button variant covers
inline text-button actions, but there's no shared **navigational arrow link**.

### Accordion / disclosure — already exemplary (reference pattern)
`components/site/faq-section.blade.php`: real `<button>` trigger,
`focus-visible:ring-2 ring-ring ring-offset-2`, `aria-expanded`/`aria-controls`,
`grid-rows` height transition, `motion-reduce:transition-none`. **No change needed** — this
is the canonical interactive pattern the rest should match.

### Summary of what to polish
1. Buttons: add `active:` press + `motion-reduce` guard; fold the one header one-off into
   `<x-ui.button>`.
2. Inputs: unify on a single **`focus-visible:ring-2`** brand-blue convention (input,
   textarea, select trigger).
3. Links: a shared `<x-ui.arrow-link>` for the navigational arrow pattern; migrate the
   standalone usages; consistent hover + focus + reduced-motion.
4. Accordion/select already accessible — leave the accordion; only align select's focus.

## Step 2–4 — implemented

Small, propagating changes to the shared components — no page-layout restructure (pass 3).

### Buttons (`<x-ui.button>`)
- Added an **`active:` press state** to every variant (primary `active:bg-primary/75`,
  outline `active:bg-current/20`, link `active:text-primary/80`) so a click is
  acknowledged — completing hover → active → focus-visible → disabled.
- Guarded the colour transition with `motion-reduce:transition-none`.
- **Folded the one one-off** (header mobile "BOOK NOW", was a hand-styled `<a bg-primary>`)
  into `<x-ui.button class="w-full">`, so it's now a proper full-width CTA in the mobile
  menu. There are now **zero** hand-styled buttons in the public views.
- Documented the icon convention in the component (lead the icon for action buttons,
  trail for directional; icon-only `size="icon"` MUST carry an `aria-label`) and that
  loading is wired per-use with `wire:loading` + a slot swap.

### Inputs (`<x-ui.input>` / `<x-ui.textarea>` / `<x-ui.select>`)
- **Unified the focus convention:** one `focus-visible:ring-2 ring-ring` (brand blue) +
  `border-primary` across input, textarea AND the select trigger — was a mix of `ring-1`
  and (on select) `focus:` instead of `focus-visible:`. Keyboard focus now reads clearly
  and matches the buttons' 2px ring. Labels (`<x-ui.label for>`), the inline-error field
  (`<x-booking.field role="alert">`) and the toast pattern for simple forms are unchanged
  (the toast pattern is a documented decision, not a defect). Forms are already constrained
  (`max-w-3xl` booking flow; 2-col contact card) — no full-bleed forms.

### Links (`<x-ui.arrow-link>` — new, the ONE arrow pattern)
- New shared component for the navigational "All news → / Read more →" pattern: uppercase
  tracked, trailing arrow that slides on hover, `motion-reduce` respected. With `href` it's
  an `<a>` with its own `focus-visible` ring + own hover; without one it's a `<span>` cue
  whose arrow slides on the **parent card's** hover.
- Migrated the standalone "All news" (`home`) and the in-card "Read more" (`news/index`) to
  it. The home "Explore" cue keeps its distinct `border-b` dark-card treatment (deferred to
  pass 3 / home polish) but got the missing `motion-reduce` guard.

### Already exemplary (left as the reference)
- FAQ accordion (`faq-section`) — real `<button>`, `focus-visible:ring-2`,
  `aria-expanded/controls`, `grid-rows` + `motion-reduce`. Unchanged.

### Verified
At 1440 / 1280 / 1024 / 390 + short-height: input focus ring is clearly visible and
consistent; the mobile "Book Now" is now a system button; the arrow links render and
animate consistently; brand look (navy/blue/white, sharp corners) intact. Before/after in
`ui-review/c-before` and `ui-review/c-after`. `composer check` green (344 tests).
No new tokens were needed.

