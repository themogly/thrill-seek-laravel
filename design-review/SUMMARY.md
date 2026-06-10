# Design review summary — design/visual-polish (updated on feature/round-4)

> Round 4 closed the two rows left open below; the table now has no silently
> ignored entries — everything is closed or consciously deferred in DECISIONS.md
> (printable voucher PDF is the only deferral).

Evidence: `before/` and `after/` screenshots (1440 / 390, plus 768 and `-hero`
viewport shots where relevant). Tooling: Playwright driven via Node
(`shoot.mjs`, `shoot-all.mjs`, `walk-tandem.mjs`) because the Playwright MCP
server was not reachable from this session; every shot also captures console
errors and failed requests, and the booking flow was walked interactively on a
390px viewport.

## Frontend–backend mismatches: closed vs left

| # | Mismatch | Outcome |
|---|---|---|
| M1 | Tandem payment was a placeholder toast; availability invisible | **Closed** — `/book/tandem`: 3-step flow (slot picker with live places, full customer/emergency/medical details + add-ons, review with price breakdown, Stripe). Capacity locked under concurrency; holds released on abandon. |
| M2 | No AFF course dates anywhere; deposit was a toast | **Closed** — CourseDate domain + admin (enrolment view with payment states), public course cards on /aff, `/book/aff` deposit flow with balance tracked. |
| M3 | Fake newsletter forms | **Closed** — NewsletterSubscriber + Livewire component (banner/card variants), admin list. |
| M4 | Coaching had no enquiry path with context | **Closed** — /coached enquiry form (discipline, jumps, licence, goals) into the existing inbox. |
| M5 | Vouchers invisible publicly | **Closed (fully, Round 4)** — /vouchers purchase page with Stripe, designed gift email, and code redemption inside the tandem booking flow (full and partial coverage). Printable PDF consciously deferred (DECISIONS.md). |
| M6 | Generic payment result pages | **Closed** — success page resolves the Stripe session: booking reference, date/course, amount, balance, what-happens-next, processing state; cancelled page returns the customer to the right flow. |
| M7 | Unbranded 404 | **Closed** — site layout, hero treatment, three useful destinations. |
| M8 | Course communications / document library (Round 2 Part C6) | **Closed (Round 4)** — message-everyone with attachments (per-recipient queued jobs), reusable document library, idempotent per-course scheduled reminders, full audit trail per course. |

## Page-by-page

- **Global** (`feat(design): photographic page heroes…`): sky gradient darkened
  (top was too light for any accent text); page-hero now accents only the LAST
  title word in a light sky tone (AA on the dark backdrop) instead of every
  other word in mid-blue; marketing pages (tandem/AFF/coached) take full-bleed
  CMS-managed hero photos behind a dark overlay; transactional pages keep the
  calmer gradient on purpose. Evidence: `before/tandem-1440.png` vs
  `after/tandem-390-hero.png`.
- **Home**: hero CTAs enlarged (h-14, scale hover) and eyebrow switched to the
  accent tone + drop shadow — it was mid-blue on sky photography. Newsletter
  banner now actually subscribes. `before/home-1440-hero.png` → `after/home-1440-hero.png`.
- **Tandem**: photo hero; booking pay-card links into the real flow; gift
  voucher box added. `before/tandem-1440.png` → `after/tandem-1440.png`.
- **AFF**: photo hero; upcoming-courses section (dates, location, price,
  deposit, places-left badges, book buttons); pay-card → deposit flow.
  `before/aff-1440.png` → `after/aff-1440.png`.
- **Coached**: photo hero; "Book a session" now scrolls to a real coaching
  enquiry form. `after/coached-*.png`.
- **Booking flows** (new): step indicator, inline validation, add-on picker,
  review with full price breakdown, reassurance microcopy, Stripe-handled
  payment note; mobile-first. Walked interactively: `after/book-tandem-step2-390.png`,
  `-step3-390.png`, `-step3-error-390.png` (inline error state).
- **Payment success/cancelled**: booking summary card + next-steps panel /
  flow-aware retry. `before/payment-success-1440.png` → `after/payment-success-*.png`.
- **404**: branded. `before/404-1440.png` → `after/404-390.png`.
- **Shop / Testimonials / Hall of Fame / Contact / Legal**: inherit the global
  hero contrast fix; layouts were already sound, deliberately left otherwise
  (no identified problem to fix — avoiding change for its own sake).

## Deliberately NOT changed

- Card style, radius and shadow language — already consistent site-wide.
- The enquiry forms' toast-based feedback (original UX, still appropriate);
  only the new booking flows use inline errors, where money is involved.
- Brand palette and logo untouched; the "fire-gradient" legacy class names kept
  (renaming is churn with no user-facing value).
- Public voucher purchase and course communications (see M5/M8).

## Bugs found by the visual loop (fixed, with tests where applicable)

1. Empty Stripe keys 500'd the whole booking flow at DI time (StripeClient
   constructor throws on '') — bound with null api_key; flows now degrade with
   a friendly message and release the hold.
2. Livewire 4 reserves `$slots` in component views (SlotProxy) — collided with
   the slot picker's variable.
3. Tailwind only scanned compiled views, so brand-new templates lost their
   utilities until a visit-then-rebuild cycle — `@source '../views'` added.
4. Stale spatie settings cache 500s when settings classes gain properties —
   `settings:clear-cache` documented as a deploy step.
