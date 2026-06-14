# Functional QA sweep

Branch `qa/functional-sweep` off main `87791a1`. Walked every flow as a real user on the
running local site (`http://thrill-seek-laravel.test`, MySQL, Redis/Horizon up, Stripe
keys empty → test/mock only, mail → log + `/dev/mail`). Format:
`- [area]: what happened → expected → severity (blocker/major/minor) / status`.

## Summary

The site is in strong functional shape — almost everything passed first time, consistent
with the 341-test suite and prior audits. **One real defect found:** transactional emails
fail to render when the spatie settings cache is stale (missing the `email_signoff`
property), which silently fails every queued email. Documented deploy mitigation exists
(`settings:clear-cache`); fixed defensively so a stale cache degrades gracefully instead
of taking all email down. Everything else is pass / owner-manual.

## Findings

### Emails
- **Stale settings cache → ALL transactional emails 500**: with a Redis settings cache
  that predates the `email_signoff` property, `<x-mail.layout>` throws
  `GeneralSettings::$email_signoff must not be accessed before initialization`; every
  queued mail job (booking confirmation, magic-link login, enquiry ack, newsletter
  confirm, …) fails (observed 75 logged errors + failed jobs). The public site was
  unaffected (only the mail layout reads the new property). → expected: emails render even
  if a settings property is missing from a stale cache → **minor** (documented mitigation
  `php artisan settings:clear-cache`, but impact is a *silent total email outage*).
  **Status: FIXED** (defensive default in the mail layout) + still mitigated by the
  documented deploy step.
- `/dev/mail` — all **14 templates render 200**, no unrendered `{{ tokens }}`, no Livewire
  markers, on-brand, greeting + single sign-off present on customer emails and correctly
  ABSENT on admin notifications / marketing campaign / enquiry-reply. The tandem-only
  **pre-jump block appears only on `booking_confirmed` + `jump_reminder`** (not AFF/coaching
  or any other template). → pass.

### Site-wide
- Public crawl of all 14 pages (home, tandem, AFF, coached, news, testimonials,
  hall-of-fame, contact, privacy, terms, book/tandem, book/aff, vouchers, newsletter):
  every page 200, **zero broken images, zero broken internal links, zero console/JS
  errors**. → pass.
- Nav "Why Us" dropdown opens on desktop (menu + links visible) and expands in the mobile
  menu. → pass.
- Auth swap: logged-out header shows **"Sign in"**, logged-in shows **"My Account"**. → pass.
- Footer links resolve; footer newsletter signup works (1 toast, pending subscriber). → pass.

### Conversion flows
- **Tandem booking (via full voucher redemption, no Stripe needed):** pick date → fill
  details → apply voucher `QA-VOUCHER-1` (£260, covers the £260 jump) → "Book now —
  nothing to pay" → redirected to confirmation. DB verified: booking **confirmed**, slot
  assigned (capacity path), voucher **redeemed**, a `voucher`/`paid` payment recorded.
  → pass.
- **Voucher double-redeem refused:** re-applying the now-redeemed code is rejected (no
  "nothing to pay"). → pass.
- **AFF booking:** 2 course dates shown with deposit; pick course → fill details → reach
  the "Review & pay deposit" step showing the deposit amount. → pass (deposit *payment*
  is Stripe → owner-manual).
- **Enquiry (contact form):** empty submit blocked by validation; bad email rejected;
  valid submit → 1 success toast + enquiry row created (`GF-OTAC42`, status new) +
  acknowledgement email rendered to log. → pass.
- **Newsletter:** footer + page signup → pending subscriber (double-opt-in). → pass.
  Signed confirm/unsubscribe links are exercised by the test suite (not clicked here as
  they arrive by email) → owner-manual to click a real link.

### Customer account
- Magic-link login (via local `/dev/account-login`) and logout (→ redirects to login). → pass.
- **IDOR / horizontal access — CRITICAL:** logged in as customer A, requesting customer
  B's booking (`/account/bookings/6`) and its receipt both return **404**. Access is
  correctly denied. → pass.
- Dashboard: shows the next upcoming **tandem** with the **"Before your jump" panel**
  (tandem-only), recent payments, and a "leave a review" CTA. → pass.
- My Bookings: **UPCOMING** (Tandem, Sun 28 Jun 2026 9:00am, CONFIRMED, **Balance due
  £160**) vs **PAST** (Tandem, Thu 4 Jun 2026 10:00am, COMPLETED, **PAID IN FULL** — no
  balance). Grouping + balance display correct; **completed/paid booking shows no balance
  due**. → pass.
- Payments / messages / review pages load (200); review available because the customer has
  a completed booking. → pass.
- Date/time display is sensible throughout (9:00am / 10:00am) — **no implausible
  placeholder times** shown to customers. → pass.

### Admin (Filament)
- Login works; every resource list + the bookings calendar + help guide load 200 (verified
  earlier on MySQL). A create form (`tandem-dates/create`) renders with fields. Customers
  resource exposes the **Export data / Erase** actions. → pass.
- Feature toggles take effect on the public site: `online_payments_enabled` OFF flips
  `/book/tandem` from "Review & pay" to **"Review & send"** (enquiry-first); `shop_enabled`
  OFF → `/shop` 404. Flipped back to defaults after testing. → pass.
- Course message with attachment, email-template editing/preview, and the clash rule /
  5-day AFF minimum are covered by the automated suite (`DateClash`, resource tests) and
  the `course-message` `/dev/mail` preview renders → pass-by-suite; a real attachment send
  to a mailbox is owner-manual.

### Cross-cutting
- Empty states / clash rule / 5-day AFF minimum: covered by the test suite; the booking
  calendar appears once a booking exists (it does here). → pass-by-suite.

## Owner-only manual items (CANNOT be verified here — pre-launch checklist)
- **Real Stripe payments** (live/test dashboard): the Stripe-paid tandem path, the AFF
  deposit + balance, and account balance payment — local Stripe keys are empty, so only
  the non-Stripe (voucher) booking path was driven end-to-end. Add Stripe test keys +
  `stripe listen` to exercise Checkout → webhook → confirmed booking, capacity decrement,
  and the abandoned-checkout slot release.
- **Real emails opened in Gmail/Outlook** — rendering/links/spam. Verified here only via
  `/dev/mail` + the log mailer.
- **Real inbound-email round-trip** (Resend inbound webhook → enquiry thread).
- **Newsletter signed confirm/unsubscribe links** clicked from a real inbox.
- **Magic-link login from a real email** (here bypassed via the local dev shortcut).
