# Design Audit — design/audit-pass

Branched off main `2f0889d`. Method: every public page reviewed in the browser at
1440×900 and 390×844 (viewport captures in `design-review/audit/`), cross-checked
against `CLAUDE.md` (palette, shared buttons, photo-tile/scrim, no flat-black
secondary heroes, no rounded-glow cards) and the code.

**Overall:** the site is in strong shape. The earlier rounds already enforced the
structural rules — palette is navy/blue/white throughout, every CTA is a shared
`<x-ui.button>` variant, heroes are photographic or the compact navy-gradient band
(no flat-black), tiles are photo-led with scrims, inputs are labelled with visible
focus rings, social/icon links carry `aria-label`, the 404 returns a real 404. So
this audit is deliberately short: a few real items, no padding.

---

## PHASE 1 — Critical

- **News index card (no featured image)**: an article without a photo renders a
  large empty `band-ink` block with a small centred icon (16:10), which on a public
  landing page — especially at 390px where it's a tall dark void above the title —
  reads as a *broken / failed-to-load image* → make the no-image area an intentional
  branded panel (brand-blue gradient, larger icon, a small "News" eyebrow) so it
  always looks designed, never broken → first-timers judge trust by polish; a
  "missing image" on the news landing undersells. *(The actual article photos are a
  CONTENT task — listed below; this fixes the fallback's design.)*

`Review:` This is the only item that can read as *broken* to a visitor, and it sits
on a public marketing surface, so it ranks first. Everything else passed the
critical bar (no contrast failures, no unlabelled inputs, no missing focus states,
no off-palette colour, no one-off buttons, no flat-black heroes, no
stretched/stranded grid tiles — the testimonials grid was already fixed). It's a
small, safe, palette-compliant change.

## PHASE 2 — Refinement

- **News article body (`/news/{slug}`)**: the long-form body is plain body copy at
  full content width with modest vertical rhythm → give it an editorial measure
  (cap width, lift paragraph leading/spacing, style `h2`/lists to match the brand)
  so a full article reads as deliberately as the rest of the site → news is an SEO
  and trust surface; readable long-form copy keeps visitors engaged.
- **News index card hover**: the card has a border-colour hover but the image
  doesn't move, unlike the Services and Hall-of-Fame photo tiles which zoom on hover
  → add the same subtle image zoom (reduced-motion honoured) so the news cards feel
  part of the same interactive family → consistency of the photo-tile interaction
  language. *(Implemented in Phase 3 with the other hover work — noted here as the
  consistency rationale.)*

`Review:` Both are genuine quality lifts, not breakage, so they sit below Phase 1.
The article-body refinement is sequenced first because long-form readability has the
larger payoff; the hover consistency is folded into the Phase 3 interaction pass.

## PHASE 3 — Polish

- **News card hover zoom**: bring the news cards into the shared photo-tile hover
  language (subtle image scale, `prefers-reduced-motion` respected). Other tiles
  (services, Hall of Fame, testimonials) already do this.
- **Dark mode**: N/A. The site is intentionally light-themed with dark navy bands;
  no dark-mode toggle is in scope (the owner hasn't asked) — a generic checklist's
  "add dark mode" item does not apply here.
- **Loading / empty / success states**: already in good shape — booking and
  newsletter CTAs show `wire:loading` text, the news index and testimonials pages
  have written empty states ("No news just yet…", "No reviews yet."), and form
  validation surfaces inline/toast errors. No work needed; recorded so it's
  consciously checked, not assumed.

`Review:` Phase 3 is a single real interaction tweak plus confirmations. Cumulative
impact across the three phases: the news surface stops ever looking broken, reads
better, and behaves like the rest of the site — closing the last visible gaps
without touching the (already solid) structural design.

---

## Content tasks for the owner

These are CONTENT, not design defects — do not fake them:

- **News articles have no featured images** — both seeded articles fall back to the
  designed placeholder. Upload a real photo per article (Admin → News → edit →
  Featured image) and they'll render as full photo cards.
- **Home "What we do" lead** contained leftover test text ("i dont want it here") in
  the dev database; restored to the correct seeded copy for the audit. The owner
  controls this line under Site content → Home (it's optional — clearing it hides
  the line cleanly).
- **Some seeded photos are placeholders** (instructor crops reused as testimonial
  avatars/photos, bundled stock as Hall-of-Fame/hero images). Swap for real
  G-Force photography through the admin uploads when available.

---

## Status (after the phased fixes)

- **P1.1 — news no-image card** — ✅ done. Branded blue-gradient panel replaces the
  void (`fix(design): audit P1.1`).
- **P2.1 — article long-form typography** — ✅ done. text-lg / leading-8 / roomier
  rhythm (`fix(design): audit P2.1`).
- **P2.2 / P3 — news card hover zoom** — ✅ done. News cards now share the
  Services / Hall-of-Fame image-zoom hover, reduced-motion honoured
  (`fix(design): audit P3`).
- **P3 — dark mode** — N/A (intentionally light-themed; not in scope).
- **P3 — loading / empty / success states** — verified already in good shape; no
  change made.

No items deferred. Content tasks above remain with the owner (real article photos,
real photography for placeholders).
