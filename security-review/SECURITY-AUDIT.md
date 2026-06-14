# Security & privacy audit

Branch `security/audit-pass`, off main `21f4a04`.

Inventoried `app/`, `routes/`, `config/`, `database/`, the Filament panel, the customer
account area, and dependencies before proposing fixes. The site collects **sensitive
personal + medical data** (name, address, postcode, DOB, weight, height, sex,
`medical_notes`) on the tandem/AFF forms — stored in the enquiry `context` / booking
`customer_details` JSON — and runs real Stripe payments, so this is treated as
first-class.

## Headline

**The exploitable foundation (Phase 1) is already strong and well-tested** — built and
pinned across this session's account/inbound/webhook work. The genuine remaining work is
**privacy operations (Phase 2: data erasure/export)** and **hardening + monitoring
(Phase 3: security headers + a privacy-scrubbed Sentry)**. Nothing here weakens a
documented decision.

## PHASE 1 — Must-fix (exploitable / data-exposure)

Verified **present and correct** (status, with the evidence):

- **Authorization / IDOR — DONE.** Every account screen reads the current customer only
  via `AccountController::customer()`; route-bound records pass through `ownedBooking()`
  / `ownedEnquiry()` which **404 on another customer's id**; the receipt PDF and payments
  list are scoped the same way. 10 tests across `BookingsTest`/`PaymentsTest`/
  `MessagesTest`/`ReviewsTest`/`AuthTest` prove cross-customer access is denied. Vouchers
  are matched by the customer's own email, never an id. ✔
- **Token security — DONE.** Magic-link login tokens are 48-char random, SHA-256-hashed,
  single-use and 20-min-expiring (tested); inbound reply tokens are 32-char unguessable
  and resolved by hash; the unsubscribe link is a Laravel signed URL. ✔
- **Guard separation — DONE.** Customers use a distinct `customer` guard; a customer can
  never reach `/admin` (Filament `web` guard) — tested. ✔
- **Secrets hygiene — DONE.** `.env`/`.env.backup`/`.env.production` are gitignored and
  untracked; `.env.example` holds placeholders only; no keys committed. ✔
- **Dependencies — DONE.** `composer audit` and `npm audit` both report **0
  vulnerabilities**. ✔
- **Webhooks — DONE.** Stripe (`Webhook::constructEvent`) and Resend inbound
  (`ResendWebhookSignature`, Svix HMAC) both verify the signature and `abort(400)` on
  failure before any work; both are CSRF-exempt only for their own path and idempotent
  (dedupe on event/email id) — tested. ✔
- **Rate limiting — DONE.** Every public form uses the `ProtectsAgainstSpam` concern
  (honeypot + 5 attempts / 10 min per IP): enquiry, contact, newsletter, tandem/AFF
  booking, voucher purchase, coached enquiry. Magic-link request is `throttle:6,1`,
  verify `throttle:10,1`. ✔
- **CSRF & mass assignment — DONE.** All state-changing forms post with `@csrf`; **no
  model uses `$guarded = []`** — every model is `$fillable`-listed; sensitive fields are
  set server-side, never from request input (e.g. review `approved => false`, booking
  `status` set by the webhook/admin, never a customer request). ✔
- **No prohibited storage — DONE.** Auth is passwordless (no customer passwords); payments
  go through Stripe Checkout — **no card/PAN data stored**. ✔
- **PII not leaked — DONE.** Medical/personal data lives in the enquiry/booking JSON,
  surfaced only in the admin; it is never in a URL/query string, and the account JSON/
  views expose only the signed-in customer's own non-medical booking summary. ✔

- **[Webhook endpoints — no rate limit]:** signature verification is the correct primary
  control (an unsigned/replayed request is rejected cheaply), but the routes have no
  throttle → add a generous `throttle` (high enough never to drop a legitimate Stripe/
  Resend retry burst) as defence-in-depth → caps brute abuse without harming delivery.
  **(SEC-P1.1 — the only Phase-1 addition.)**

`Review:` Phase 1 is the part that, if wrong, leaks data or lets one customer act as
another — and it's already locked down and tested. The single addition is a belt-and-
braces throttle on the webhooks; everything else is verified done.

## PHASE 2 — Privacy & GDPR

- **Privacy policy — present** (`/privacy`, CMS-editable). The actual legal copy is an
  **owner task** (below). ✔ (page) / owner (copy)
- **Consent + timestamps — DONE** for marketing: newsletter is double-opt-in with
  `consented_at` / `confirmed_at` / `unsubscribed_at`. ✔
- **[Data access / erasure — MISSING]:** there is no documented retention policy and no
  way to export or delete a customer's data for a GDPR access/erasure request → add (a)
  a documented manual process in SETUP.md and (b) a Filament admin action on the Customer
  to **export** (their bookings/enquiries/payments/messages as JSON) and **erase/
  anonymise** their personal data → a UK business handling personal + medical data must
  be able to honour subject-access and erasure requests. **(SEC-P2.1.)**
- **Medical data exposure — DONE.** `medical_notes`/DOB/weight are admin-only (enquiry/
  booking detail), not in any customer-facing list or the account area. ✔

`Review:` The collection side (consent) is handled; the gap is the *subject-rights* side
(access/erasure), which is the concrete GDPR obligation for this data — hence SEC-P2.1.

## PHASE 3 — Hardening & monitoring

- **[Security headers — MISSING]:** no `X-Content-Type-Options`, `X-Frame-Options`/
  `frame-ancestors`, `Referrer-Policy`, or HSTS are sent → add a `SecurityHeaders`
  middleware (nosniff, `frame-ancestors 'self'` to stop clickjacking, a strict
  `Referrer-Policy`, HSTS in production, and a conservative CSP — report-only first if
  needed so Livewire/Alpine/Stripe aren't broken) → standard browser-side hardening.
  **(SEC-P3.1.)**
- **HTTPS + cookies — mostly config.** `http_only` true, `same_site` lax; `secure` is
  env-driven (`SESSION_SECURE_COOKIE`) → document that production sets it true + HTTPS +
  `APP_DEBUG=false`. **(SEC-P3.2 — SETUP note.)**
- **[Sentry — NOT installed]:** add `sentry/sentry-laravel`, DSN via env, configured for
  **privacy first**: `send_default_pii = false`, and an event scrubber that strips the
  sensitive fields this audit protects (medical_notes, dob, weight, height, sex, address,
  postcode, card/auth tokens) and request bodies on payment/booking/account routes, so
  Sentry never becomes a second unaudited PII store. Verify a captured event is scrubbed,
  confirm a test exception reports, then remove the trigger. **(SEC-P3.3.)**

`Review:` Cumulative: defence-in-depth headers, secure-cookie/HTTPS confirmation, and
error monitoring that does NOT undermine the very privacy this audit enforces.

## Owner / ops tasks (not code)

- Real **privacy-policy legal copy** (and cookie notice text) via the CMS.
- Production **SSL/TLS** + `SESSION_SECURE_COOKIE=true` + `APP_DEBUG=false` + canonical
  HTTPS host.
- **WAF / network** controls and the **Sentry DSN** (owner's Sentry account).
- Keep **Horizon** (queues) and the **`schedule:run` cron** running — their silent
  failure breaks reminders/sweeps/emails (documented in SETUP).

## Discussion (conflicts with a documented decision)

None — every fix is additive and consistent with the existing architecture and rules.

## Status

- [x] SEC-P1.1 — webhook throttle (defence-in-depth)
- [x] SEC-P2.1 — customer data export + erasure action + retention docs
- [x] SEC-P3.1 — security headers middleware
- [x] SEC-P3.2 — production HTTPS/cookie/debug SETUP note
- [x] SEC-P3.3 — Sentry, privacy-scrubbed
