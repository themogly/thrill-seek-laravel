# Full design audit

Branch `design/audit-pass`, off main `21f4a04`.

Method: rendered the public site in the browser and reviewed **viewport-sized**
screenshots (never full-page) across **1440, 1280, 1024, 390, and a short 1366×700
laptop height** — the in-between sizes where past rounds (overfit to 1440/390) broke.
Three parallel sweeps covered marketing pages, content/trust pages, and the
conversion/account pages; findings below were re-verified by hand. Evidence in
`design-review/audit/`.

## Headline (read this first)

**The site is in excellent shape and needs no critical design changes.** The four
structural issues the owner flagged were all fixed in earlier rounds and are confirmed
resolved at every tested size:

| Flagged issue | Status | Where fixed |
| --- | --- | --- |
| Full-viewport heroes / full-bleed images becoming one-screen walls | **Resolved** | `page-hero` padding + home hero capped to a rem band (`d8289f0`) |
| Text+image two-column collapsing to an orphaned tall photo | **Resolved** | shared `x-site.feature-split` — holds 2-col to `md`, stacks to `aspect-[16/10]`, groups with its text (`9006456`) |
| Stray white band between the CTA and the footer | **Resolved** | dropped the footer's `mt-24` (`b1c7953`) |
| Double hero on Testimonials | **Resolved** | featured quote is the hero with a small eyebrow |

Re-checked at 1024/1280/short-height: the booking-flow hero is a compact band with a
well-spaced step indicator; the tandem/aff/coached intros hold two columns and the photo
is a proportioned landscape when stacked; the account dashboard's balance card is already
visually distinguished (primary border + tint) when a balance is outstanding. No new
breakage was found.

This is a deliberately short report. Per the brief, I did not invent problems or churn
working code for taste.

## PHASE 1 — Critical

**None.** No broken/awkward layouts, no responsiveness breakage at the in-between sizes,
no off-palette colour, no one-off buttons, no flat-black secondary heroes, no
stranded/stretched tiles, and no accessibility failures found (inputs are labelled and
associated, focus states are defined via `focus-visible:ring`, hero/body text clears AA,
photo-tile captions sit on navy scrims). The owner-flagged structural issues are already
resolved (table above).

`Review:` Phase 1 is empty because the work it would contain was done in the prior
rounds referenced above and verified here across the full size range. Nothing remaining
actively hurts the experience.

## PHASE 2 — Refinement

Genuinely optional; none rise to a defect. Listed honestly, not as required work:

- [Vouchers form `buy-voucher`]: the footnote "Card payments are secure — we never see
  your card details" is phrased by negation → a confident framing ("Pay securely by
  card") reads as proactive rather than defensive → minor trust nuance on a payment page.
  **Optional** — the current copy is accurate and reassuring; left as-is.
- [Booking step indicator @ 1024]: spacing is slightly tighter than at 1440 but well
  within comfortable → no change needed (verified not cramped, contrary to first glance).

`Review:` These are taste-level nuances, not rhythm/alignment defects. The spacing,
type scale and palette application are already uniform across similar screens (the
codebase has had explicit consistency passes), so there is no Phase-2 cluster to fix.

## PHASE 3 — Polish

Already handled by the existing system; nothing to add:

- Hover/zoom on photo tiles and news cards (`scale-105`) honour `prefers-reduced-motion`.
- `data-reveal` entrance animations are reduced-motion aware.
- Visible focus rings on buttons, inputs and links.
- Livewire forms have loading (`wire:loading`), success and inline error/`role="alert"`
  states (contact, booking, voucher purchase, newsletter).
- **Dark mode: N/A** — the site is light-themed by design with intentional dark bands;
  a toggle is explicitly out of scope (owner did not ask).

`Review:` The premium micro-detail layer already exists and is consistent; adding more
would be noise.

## Content tasks for the owner (NOT design defects — do not fake)

- **News articles** use the branded blue-gradient "no image" fallback because the seeded
  articles have no `featured_image`. Upload real photos to give each article a hero.
- **Hall of Fame / some tiles** reuse instructor crops as placeholders; replace with the
  real milestone photography when available.
- A seeded demo booking shows an odd time (e.g. "3:12am") because the factory used a
  random time — cosmetic to demo data only; real tandem slots carry sensible times.

## Discussion / deferred

Nothing requires an owner decision, and nothing is deferred with an open design question.

## Status

- [x] Phase 1 — verified resolved (no new work required)
- [x] Phase 2 — reviewed; no required changes (one optional copy nuance noted, left as-is)
- [x] Phase 3 — reviewed; existing polish is sufficient
