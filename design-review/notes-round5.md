# Round 5B — bold redesign notes

No reference screenshots in `design-review/references/` — the owner's written
critique is the brief. Direction: **sharp editorial adrenaline**. Condensed navy
display type pushed harder; flat surfaces and rules instead of card chrome;
full-bleed photography; dark navy bands for drama; blue as a sharp accent, not
a wash.

## Critique of the current site (before/, all pages)

**Global, the structural problem.** Every section on every page is the same
unit: a white rounded-2xl card floating on `shadow-deep`/`shadow-glow` with a
pale blue glow. Products, stats, coaches, testimonials, Facebook posts, price
tables, enquiry panels, CTA banner — identical chrome. The eye gets no
hierarchy and the subject (falling out of a plane) gets no drama. Specific
violations, page by page:

- **home**: services = 3 identical rounded cards; about photos in rounded
  boxes with glow; trust strip = 4 icon-in-circle badges (the most generic
  section on the site); coaches = letter-circles J/R/L on white cards;
  testimonial cards; CTA = giant rounded navy card *inside* a white section —
  a card in a card.
- **tandem**: hero is fine (photo) but everything below is boxed — the jump
  photo sits in a rounded container with glow; price list and enquiry form are
  cards in cards; voucher banner is another rounded navy card.
- **aff**: same; course-date cards are white rounded boxes; the four trust
  badges repeat; "investment" pricing = two cards.
- **coached**: photo in a rounded box, form in a card on grey.
- **book-tandem / book-aff / vouchers**: dotted navy gradient hero band +
  floating white cards; acceptable bones (calm), wrong chrome.
- **shop/testimonials/hall-of-fame/contact/legal/404**: inherit the same card
  language.
- **Photography** is confined: only the heroes are full-bleed; every other
  image is padded inside a rounded container.
- **100% light**: the only dark surfaces are the footer and the about band;
  no full-bleed dark photographic break anywhere.

## The new language (design-system commit)

- **Type**: Bebas Neue stays and gets louder — hero up to `text-[11rem]`-class
  scale at 1440, section headings 5xl→7xl with `leading-[0.95]` and a short
  accent rule; kickers = small bold uppercase tracked with a leading rule.
- **Corners**: `--radius: 0` globally. Sharp. (Filament admin has its own theme
  and is untouched.)
- **Shadows**: decorative `shadow-glow`/`shadow-deep` removed from public
  views; only the sticky header keeps a functional shadow over photography.
- **Structure**: hairline borders (`border-border`), 2px navy rules, strong
  grids with internal `divide-x` lines instead of gaps full of shadow.
- **Colour**: navy `--secondary`/`--ink` becomes full-bleed band surfaces;
  primary blue is applied as sharp accents — kicker text, 2px rules, hover
  fills, never as a glow/wash. Pale-blue gradient washes deleted.
- **Photography**: edge-to-edge sections, images that bleed past the grid,
  text set on photography over a bottom-weighted scrim.
- **Stats**: dark typographic band — oversized Bebas numerals, hairline
  separators, no icons.
- **Coaches**: portrait photography (placeholder photos seeded), name plate on
  a navy strip; the no-photo fallback is a full-block navy panel with a giant
  Bebas monogram — intentional, not a broken avatar.
- **Motion**: entrance reveals via a tiny IntersectionObserver adding
  `.revealed` (progressive enhancement — content visible without JS),
  `prefers-reduced-motion` short-circuits everything; hover motion stays CSS.
- **Transactional surfaces** (booking steps, payment, forms): same language
  (sharp, rules, flat) but quiet — white surfaces, navy headings, generous
  fields, no oversized display type mid-flow, trust microcopy kept.

## Per-page after-notes

(filled in as each page lands — see SUMMARY.md for the final account)
