# Round 5B — bold redesign (design/bold-redesign)

Evidence: `round5/before/` (1440/390) vs `round5/after/` (1440/768/390) for all
16 public routes, plus `round5/voucher-sample.pdf|png` and refreshed
`design-review/emails/*.png`. The tandem booking flow was walked interactively
at 390px (`walk-tandem.mjs`, no console errors). Screenshots are taken with
`reducedMotion: 'reduce'` — which doubles as verification of the reduced-motion
path; entrance reveals were checked by hand in a live viewport.

## The language (one commit, then applied page by page)

The owner's critique was the brief: the old site was one white rounded-2xl
card floating on a glow shadow, repeated forever. The replacement:

- **Radius 0 everywhere**; structure from 2px navy rules, hairline grid lines
  (`gap-px bg-secondary`) and borders — not floating shadows. The only shadow
  left on the public site is the functional one under the sticky header.
- **Bebas display type pushed much harder**: stacked 10.5rem hero headline,
  7xl section headings with a 4px accent rule, kickers with a leading rule.
- **Navy as a surface, blue as an accent**: full-bleed `band-ink` sections
  (trust, newsletter, gift voucher, page heroes, footer) instead of a 100%
  white page; pale-blue glows and gradient washes deleted.
- **Photography freed**: services are edge-to-edge photo tiles with text on a
  bottom-weighted scrim; about/tandem/aff/coached images bleed past the grid
  to the viewport edge; hall-of-fame is a hairline photo wall; the home page
  closes on a full-bleed photographic CTA.
- **Motion**: entrance reveals (IntersectionObserver, progressive enhancement,
  `prefers-reduced-motion` short-circuits), CSS hover transforms kept.

## Page by page

- **home** — stacked editorial hero; photo-tile services; navy about band with
  bleed imagery + numeral stats; dark typographic trust band (icon badges
  gone); coach portraits (placeholder photos seeded — `public/images/
  instructors/`, replaceable in the admin) with navy name plates and the
  monogram block as an intentional empty state; ruled pull-quote testimonials;
  photographic close. `before/home-1440.png` → `after/home-1440.png`.
- **tandem** — intro copy beside an edge-bleed photo; pricing as ruled tables
  under 4px-rule headings (card tables gone); navy pay panel; full-bleed gift
  voucher band.
- **aff** — intro photo bleeds left; dark trust band; pricing tiles on a
  hairline grid; course cards with status-coloured top rules (destructive red
  when ≤2 places); ruled info columns.
- **coached** — same intro treatment; enquiry form on white with sharp fields.
- **booking flows / vouchers / payment** — new language, deliberately calm:
  flat white surfaces, 2px borders, ruled summaries, quiet type; step
  indicator squared; trust microcopy kept. Walked at 390px.
- **shop / testimonials / hall-of-fame / contact / legal / 404** — flattened to
  match; testimonials are framed pull-quotes; hall-of-fame a photo wall; 404
  gets a 10rem numeral.
- **emails** — `gforce` markdown theme: uppercase navy headings, sharp
  corners, brand-blue buttons and a ruled panel. All 12 previews shot.
- **voucher PDF (deferred from Round 4 — built here)** — A5 landscape, navy
  tagline band, code box, message, redeem footer; generated at purchase
  (fail-soft), attached to the gift email, stored with the voucher, admin
  re-download regenerates on demand; render-tested. dompdf over
  spatie/laravel-pdf to avoid a headless-Chrome dependency.

## Deliberately kept

- Brand logo, palette (re-weighted: navy surfaces, blue accents) and the
  Bebas/Barlow pairing — the type was the site's strongest asset.
- All content CMS-driven — no new hardcoded copy; the only new seeded content
  is the instructor placeholder photos (a data change, not a schema change).
- Business logic untouched: payments, webhooks, bookings, capacity, email
  sending. The admin panel untouched.
- Round 5A features are first-class in the new design: locations shown on
  slot/course pickers and summaries, course ranges with duration and
  places-left states.
- Toast feedback on the enquiry forms (original UX); inline errors stay where
  money is involved.

## Bugs caught by the loop

1. Full-page screenshots left below-fold reveal sections at opacity 0 — the
   shoot harness now emulates `reducedMotion`, which is also an a11y check.
2. `routes/dev.php` course-message preview still used the dropped
   `course_dates.location` string column (Round 5A ripple) — 500'd the email
   preview index; fixed to the Location relation.
3. The voucher PDF overflowed A5 onto a second page at the first type scale —
   caught by the page-count check, tightened to one page.
4. Mail theme button borders stayed black behind the new blue fill — caught in
   the email screenshots.
