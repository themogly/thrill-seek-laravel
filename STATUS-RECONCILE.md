# Queue reconciliation — status

Branch `chore/queue-reconcile`, off main `21f4a04`.

Investigated each item against git history, the actual files/routes, and DECISIONS.md
before touching anything. Summary: **A, B, C need work; D, E, F are already done.**

| Item | Status | Evidence |
| --- | --- | --- |
| A — completed-booking balance/pay | **PARTIAL** | balance/pay gated on `hasOutstandingBalance()` only — no status guard |
| B — booking date "3am" times | **PARTIAL** | `BookingFactory::confirmed()` uses `dateTimeBetween` (random hour) |
| C — "Past & Awaiting" grouping | **PARTIAL** | `BookingController::index()` has just `upcoming` + `past` |
| D — account form widths | **DONE** | login `max-w-md`, review `max-w-xl`, message `max-w-2xl` |
| E — SEO | **DONE** (not missing) | sitemap/robots routes, `StructuredData`, canonical/OG/JSON-LD in layout |
| F — admin help guide rebuild | **DONE** | `HelpGuide::sections()` — 19 structured sections |

## Item A — Completed-booking balance bug — PARTIAL (fix this session, first)

`resources/views/components/account/booking-card.blade.php` and
`account/bookings/show.blade.php` both show "Balance due £X" + the "Pay £X by card"
action whenever `Booking::hasOutstandingBalance()` is true — with **no status check**.
So a **Completed** (or Cancelled) booking that isn't fully paid shows a customer-facing
"pay balance" action. `BookingController::pay()` only guards on `hasOutstandingBalance()`
too. The £260 seen was a demo Completed tandem (£260 price, no payment recorded).

Root cause = the customer view offers self-service payment regardless of booking status.
The balance *calculation* is correct (reused everywhere); the fix is a status guard so a
Completed/Cancelled booking never shows a balance-due alarm or pay action to the customer
(any genuine settlement on a finished booking is admin-side). Reuses the existing
`hasOutstandingBalance()` / `balance_due_pence` — no second balance path.

## Item B — Booking date/time "3am" — PARTIAL (fix)

`BookingFactory::confirmed()` sets `scheduled_at => fake()->dateTimeBetween('+1 week',
'+2 months')`, which carries a random hour (e.g. 03:12). Real bookings copy a sensible
time from the assigned `TandemDate` slot (`Booking::booted` saving hook), so this only
affects factory/demo data — but it's what produced "Saturday 4 July 2026, 3:12am".
Fix: factory/demo use sensible daytime slot times; the display already falls back to
"Date to be confirmed" when `scheduled_at` is null.

## Item C — "Past & Awaiting" grouping — PARTIAL (fix)

`BookingController::index()` partitions into `upcoming` (future-dated) and `past`
(everything else), rendered as "Upcoming" / "Past & awaiting" — the confusing single
bucket. Split into self-explanatory groups (Upcoming / Awaiting a date / Awaiting payment
/ Past), hiding empties.

## Item D — Account form widths — DONE

All account forms are already constrained to a centred column: login `mx-auto max-w-md`,
leave-a-review `mx-auto max-w-xl`, message reply `mx-auto max-w-2xl`. Content pages
(dashboard, bookings list) keep the wider layout. No change needed.

## Item E — SEO — DONE (the prompt assumed it was missing; it isn't)

Done in the `seo/audit-pass` round (merged). Present and verified: dynamic
`/sitemap.xml` and `/robots.txt` routes (absolute URLs, news articles), centralised
`<head>` meta in `layouts/app.blade.php` (unique titles, self-referencing canonical,
full Open Graph/Twitter, absolute `og:image`, per-page `robots` override),
`App\Support\StructuredData` JSON-LD from real data, and `noindex` on thin/transient
pages. **No separate SEO run needed** — it's complete. (Deferring nothing here.)

## Item F — Admin help guide rebuild — DONE

`HelpGuide::sections()` is a structured array of 19 sections, each with id/icon/title/
intro/steps and an "Open … →" link, rendered as Filament section cards with a jump-link
contents nav (the `feature/admin-docs` rebuild). Covers content, products/pricing-in-
pounds, tandem dates, AFF courses (5-day rule), locations (clash rule), bookings/
calendar, enquiries, payments ("pay by card"), vouchers, newsletter + block builder,
news, course comms, feature toggles, customer replies/inbound, customer accounts, email
templates, plus a launch checklist and a "something's wrong?" note. Already complete.

## Plan

Fix A (money-facing) → B → C, one commit each, `composer check` green before each.
D/E/F: no work. E is NOT deferred (it's done).
