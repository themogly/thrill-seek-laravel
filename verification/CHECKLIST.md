# Pre-Launch Verification Checklist — G-Force Skydiving (human-run launch gate)

**Purpose:** confirm that what the test suite proves in mocks actually works in REALITY, before a single
real customer touches the site. Green tests can't see the gap between a mocked Stripe/inbox and the real
one; this checklist closes it. Work top to bottom on **staging** (Ploi + Hetzner) first, then sections 7–8
on production. The money (§1) and email (§2) sections matter most.

Every route, command, setting and email named here exists in the code (tailored on 9 Oct 2026 from
`routes/`, `app/Actions`, `app/Livewire`, `routes/console.php` and the email-audit inventory). If a name
here doesn't match the build you're testing, the build changed: stop and update this file.

**Before you start:**
- [ ] Staging runs like production: `APP_ENV=staging`, `APP_DEBUG=false`, Redis cache/queue/sessions,
      **Horizon running** (`/horizon` shows workers), the `schedule:run` cron set (§7). The queue is NOT `sync`.
- [ ] Stripe is in **TEST mode** (`pk_test_…` / `sk_test_…`). Never live keys for this pass.
- [ ] Two inboxes open: **your own** (customer side) and the **test owner inbox** (see §0).
- [ ] Note every failure as a bug to fix BEFORE launch. Write it down; don't fix-and-continue from memory.

**Stripe's public test cards** (any future expiry, any CVC, any postcode):
`4242 4242 4242 4242` succeeds · `4000 0000 0000 0002` is declined · `4000 0025 0000 3155` asks for 3-D Secure.

---

## 0. Staging specifics (set these before anything else)
- [ ] **Owner emails go to a test inbox:** Admin → Site content → General settings → the site email
      (`GeneralSettings::email`, a DB value, not env) = a test inbox. Owner alerts ("New enquiry",
      "Payment received") and most Reply-To headers use it, so test bookings stay internal.
- [ ] **`MAIL_MAILER`** chosen deliberately:
  - `resend` (with `RESEND_API_KEY` and a `MAIL_FROM_ADDRESS` on the verified domain) to see real emails;
  - or `log` to send nothing.

  §2 needs `resend`.
- [ ] **Basic-auth is ON** for the staging site in Ploi: `curl -I https://<staging-host>/` → `401`
      without credentials. The app has no basic-auth of its own.
- [ ] **Staging is noindexed (by the app since prompt 022).** With `APP_ENV=staging`, `curl -I` (with the
      basic-auth credentials) shows `X-Robots-Tag: noindex, nofollow`, and `/robots.txt` reads
      `User-agent: *` / `Disallow: /` with no Sitemap line. If `APP_ENV` is wrong, both are missing.
- [ ] **Basic-auth vs the webhooks:** Stripe and Resend can't send basic-auth. Exempt `/webhooks/stripe`
      and `/webhooks/resend` in the Ploi/nginx auth rules, or the webhook deliveries in §1 will 401.
- [ ] **Stripe TEST webhook:** Stripe Dashboard (test mode) → Developers → Webhooks → endpoint
      `https://<staging-host>/webhooks/stripe`.
  - Events: **`checkout.session.completed`** and **`checkout.session.expired`**. These are the only two
    the app handles (`HandleStripeWebhook`).
  - Signing secret → `STRIPE_WEBHOOK_SECRET` (`whsec_…`).
- [ ] **Stripe API version on the endpoint = `2026-09-30.endive`.** stripe-php 22 sends that version on
      every API call, and the app doesn't pin one.
  - A webhook endpoint delivers events in **its own** version: create it with `2026-09-30.endive`
    selected, or events arrive in the account's older default shape.
  - Check it on the endpoint's page in the dashboard (Developers → Webhooks → the endpoint shows its
    "API version").
  - Same rule later for the live endpoint (§8).
- [ ] **Mail test:** on the server, `php artisan gforce:mail-test you@example.com` → it says sent AND the
      email arrives.
  - If it errors, read the error. It's usually the `RESEND_API_KEY` env var name, or an unverified
    from-domain.
- [ ] **Dashboard → "Failed emails (last 7 days)" = `0`**, and the "Email setup" figure shows the real
      mailer, not `log`.
- [ ] `/dev/mail` and `/dev/account-login` return **404** (they exist only under `APP_ENV=local`).

## 1. Money paths (highest risk — READ the amounts in the Stripe dashboard, don't assume)
Expected amounts below use the seeded prices. **First open Admin → Products and note the live prices**:
expected = those, in pence. In Stripe, Payments → the payment shows £x.xx. It must equal
`amount_pence ÷ 100`, never 100× out.

| Path | Steps on staging | Stripe must show | Emails that must arrive |
|---|---|---|---|
| **Tandem booking** | `/book/tandem` → pick a date/slot → details (weight in kg) → add **Outside Camera** → review → pay with `4242…` | product + purchasable add-ons, e.g. 26000 + 14000 = **£400.00**. P6 insurance and the rebooking fee are display-only and **never** charged online. | Customer: `payment_received` + `booking_confirmed`. Owner: "Payment received" |
| **Tandem + full voucher** | buy a voucher first (row below), then book a £260 tandem with **no** add-ons → apply the code | **no Stripe session**: booked instantly, the success page shows the reference | as above; the voucher shows **Redeemed** in Admin → Vouchers |
| **Tandem + partial voucher** | a £260 voucher + Outside Camera | **£140.00** (the remainder only) | as tandem |
| **AFF deposit** | `/book/aff` → pick a course date → details → pay | the course date's deposit (default 30000 = **£300.00**; a course date can override it) | Customer: `payment_received` + `booking_confirmed`. Owner: "Payment received" |
| **AFF balance** | sign in at `/account/login` with the AFF customer's email → My bookings → the booking → **Pay £… by card** | price − paid, e.g. 175000 − 30000 = **£1,450.00** | Customer: `payment_received`. Owner: "Payment received". The booking shows **paid in full** |
| **Voucher purchase** | `/vouchers` → buyer + recipient details → pay | the Tandem product price, **£260.00** | `VoucherGiftMail` to the purchaser, with the PDF voucher attached; Admin → Vouchers shows it **Active**, expiring in 12 months |
| **Voucher redemption (admin)** | Admin → Vouchers → the voucher → **Redeem** against a booking, with "Email the customer" on | **nothing in Stripe**; a paid voucher payment on the booking | Customer: `payment_received` (only with the toggle on) |
| **Payment link from an enquiry** | Admin → Enquiries → an enquiry → **Send payment link** (type pounds, e.g. `50.00`) → open the email as the customer → pay | exactly what you typed: `50.00` → **£50.00** | Customer: `payment_link`, then `payment_received`. Owner: "Payment received". The enquiry becomes **Converted** with a booking |
| **Bank transfer** | Admin → Enquiries → an enquiry → **Record bank transfer** (amount, reference, date) | **nothing in Stripe** | same as a paid link: `payment_received` + owner notification; the enquiry converts |
| **Refunds** | **none in the app.** Refund in the Stripe dashboard; the app doesn't record refunds or change the booking. | — | none. Decide by hand what happens to the booking. |

For EVERY row above:
- [ ] The booking/voucher appears in Admin with the right name, date, location, status and amount.
- [ ] **Capacity:** a tandem slot's places / an AFF course's spaces left drop by one. Fill a slot to
      capacity and try once more → refused.
- [ ] **Abandoned checkout:** start a tandem booking, reach Stripe, close the tab.
  - No confirmed booking appears.
  - The held place is released within ~30 min: Stripe's `checkout.session.expired`, or the
    `bookings:release-expired-holds` sweep every 15 min.
  - The slot shows the place back.
- [ ] **Declined card** `4000 0000 0000 0002` → no booking, no email.
- [ ] **3-D Secure** card `4000 0025 0000 3155` → the challenge completes and the booking confirms.
- [ ] **A voucher can't be used twice:** apply a Redeemed voucher's code → "That voucher is redeemed."
- [ ] **Abandoning mid-redeem doesn't burn the code:** apply a voucher, then abandon a partial-voucher
      Stripe checkout → the voucher is still **Active**.
- [ ] **Payments off:** Admin → General settings → **Online payments** OFF.
  - `/book/tandem`, `/book/aff` and `/vouchers` submit an **enquiry** instead of opening Stripe ("Sending
    your request…").
  - No Stripe session is created.
  - Turn it back ON.

## 2. Email paths (mocks never render templates — LOOK at every real one)
For each email, check in a real inbox:
- **logo with images blocked**: the logo is embedded inline (CID), so it shows even when remote images
  are off;
- **links go to the staging host** (never `localhost`; links come from `APP_URL`);
- the greeting and the sign-off appear **once**;
- **Reply-To** as listed. Rows marked "From" set no Reply-To, so replies go to `MAIL_FROM_ADDRESS`, and
  that inbox must be read.

| Email | Trigger (how to fire it on staging) | Reply-To |
|---|---|---|
| `EnquiryAdminNotification` (owner) | submit any public form: `/contact`, the Tandem/AFF/Coached enquiry forms; or a customer reply from `/account/messages` | the customer |
| `enquiry_acknowledgement` (customer) | the same public forms | From |
| `AccountLoginLinkMail` | `/account/login` → "Email me a sign-in link" | From |
| `NewsletterConfirmationMail` | footer or `/newsletter` signup (double opt-in) | From |
| `NewsletterCampaignMail` (test) | Admin → Newsletter → a campaign → "Send test to me" | site email |
| `NewsletterCampaignMail` (broadcast) | Admin → Newsletter → "Send to subscribers" (a staging list of your own addresses only) | site email |
| `EnquiryReplyMail` | Admin → Enquiries → an enquiry → Reply | the per-enquiry inbound address if inbound is set up, else the site email |
| `payment_link` | Admin → Enquiries → **Send payment link** (§1) | From |
| `payment_received` + "Payment received" (owner) | any paid row in §1, and the bank transfer | From |
| `booking_confirmed` | a public booking paying, or Admin → Bookings → a booking → status → Confirmed; or **create** a booking as Confirmed with "Email the customer" on | From |
| `booking_rescheduled` | Admin → Bookings → **Reschedule** with "Email the customer" on (with it off, nothing is sent and the screen says so) | From |
| `jump_reminder` / `balance_reminder` | `bookings:send-reminders` (daily 09:00; run it by hand for a booking dated tomorrow / one with a balance due) | From |
| `VoucherGiftMail` (with PDF) | a voucher purchase (§1); or Admin → Vouchers → **Email voucher** | site email |
| `CourseMessageMail` (with attachments) | Admin → Course dates → a course → **Message students**, with a Document attached; or `courses:send-reminders` (daily 09:10) | site email |

- [ ] Every row arrives and renders on-brand. No Laravel default header text. The tandem-only "before
      your jump" block appears in `booking_confirmed` / `jump_reminder` for **tandem** bookings only.
- [ ] Press every "send" control in the real admin UI above. A screen that says "sent" proves nothing
      until the inbox shows it.
- [ ] Attachments open: the voucher PDF, and a course-message Document.
- [ ] **Idempotency in reality:**
  - run `php artisan bookings:send-reminders` twice → the second run sends **nothing** (the booking's
    reminder marker is set);
  - same for `php artisan courses:send-reminders`.
- [ ] **Newsletter:** the confirm link activates the subscriber; the unsubscribe link removes them
      immediately; the next "Send to subscribers" skips that address.
- [ ] **Opt-in at checkout:** the newsletter tick box on `/book/tandem`, `/book/aff` and `/vouchers` →
      ticked creates a pending subscriber (confirmation email); unticked doesn't.
- [ ] After the first day of real use: "Failed emails (last 7 days)" = `0` and
      `php artisan queue:failed` is empty.

## 3. Enquiry / contact / lead paths
- [ ] Submit `/contact`, the Tandem form (`/tandem`), the AFF form (`/aff#enquiry`) and the Coached form
      (`/coached#enquiry`).
  - Each lands in Admin → Enquiries with an unread badge.
  - The customer gets `enquiry_acknowledgement`; the owner gets the notification.
- [ ] Admin Reply → the customer receives it, and it's in the thread under `/account/messages`.
  - The customer replies from their account → the owner is notified, and the reply is in the thread.
- [ ] Inbound email (only if MX → Resend is set up for staging): reply to an `EnquiryReplyMail` from your
      mail client → it appears in the thread (Horizon shows the `ProcessInboundEmail` job).
- [ ] Payments-off mode (§1, last item) creates enquiries and no Stripe session.

## 4. Feature toggles & empty states
- [ ] Admin → General settings toggles. **Shop** off → `/shop` 404s and nothing links to it. **News** off →
      `/news` 404s, and its nav link and the homepage news block disappear. **Online payments** as in §1.
- [ ] Empty states read intentionally:
  - "No dates online right now" on `/book/tandem` with no tandem dates;
  - "New course dates coming soon" on `/book/aff`;
  - the account sections with no bookings/messages;
  - the testimonials section hides with no approved reviews.

## 5. Domain rules & validation
- [ ] Tandem dates vs AFF course dates are exclusive per location: creating an overlapping one in
      Admin → Tandem dates / Course dates is refused with a clear message (create AND edit).
- [ ] A course date shorter than 5 days is refused.
- [ ] Rescheduling into a full tandem slot is refused: fill a slot, then Admin → Bookings → **Reschedule** another
      booking. The full slot shows "— full" and can't be picked; nothing changes and no email is sent.
- [ ] Tandem weight outside 30–120 kg is refused on `/book/tandem` with an inline error.

## 6. Public site sweep (design + content)
- [ ] Walk every page at ~390 and ~1440:
  - `/`, `/tandem`, `/aff`, `/coached`, `/news` and an article, `/testimonials`, `/hall-of-fame`,
    `/meet-the-team`, `/contact`, `/newsletter`, `/vouchers`, `/privacy`, `/terms`;
  - every step of `/book/tandem` and `/book/aff`;
  - `/payment/success`, `/payment/cancelled`, a 404, and `/account/login` plus the signed-in account
    pages.
- [ ] Nothing off-palette or off-brand; no awkward gaps where optional copy is blank.
- [ ] **Owner content replaced:** real photos (heroes, instructors, Hall of Fame, gallery), real reviews
      (the sample testimonials are local-only now), Privacy `[Owner: …]` lines, real dropzone addresses
      in Locations.
- [ ] **Typed prices:** where CMS wording quotes a price, it uses a price token (`{price:tandem-skydive}`);
      the Help guide lists the prices that are still typed (weight surcharges, membership, kit,
      repeat-jump).
- [ ] Images load, with no layout shift and no broken images.
- [ ] Admin sanity as the owner:
  - create/edit a record end to end;
  - upload a wrong-shaped image to a fixed-ratio field (it crops, not distorts);
  - try an invalid save (it's blocked).

## 6b. Real devices (the browser on your desk is not the phone in a customer's hand)
Run on a real iPhone (Safari) and a real Android (Chrome), on the **staging HTTPS URL**, after the deploy
you intend to launch. This is the phone subset of `real-device-checks.md`. **Not applicable here:** the
tablet/counter items (camera/QR scanning, staff autofill, PIN-pad session timeout, second device at a
counter, Android back from a POS basket), because G-Force has no counter or kiosk use.
- [ ] **Toolbar:** with the browser bar showing, both orientations, the pay/continue button on every
      `/book/tandem` and `/book/aff` step, the voucher "Buy" button and the mobile menu are on screen.
- [ ] **On-screen keyboard:** tap the **lowest** input on every form (contact, the three enquiry forms,
      both booking flows, vouchers, account login, newsletter) → it scrolls into view above the keyboard.
- [ ] **Date pickers:** the booking date fields open the **native** OS picker on phones (the branded
      calendar is desktop-only).
- [ ] **Touch:** every control is at least 44×44 px and finger-reachable (nav, slot buttons, add-on tick
      boxes, FAQ toggles, the hamburger); no hover-only affordances.
- [ ] **Orientation:** rotate mid-booking; nothing is lost.
- [ ] **Font:** in the phone's remote console (`chrome://inspect` / Safari Web Inspector),
      `document.fonts.check('16px Barlow')` and `document.fonts.check('16px "Bebas Neue"')` are `true` on
      the live URL.
- [ ] **Deploy gap:** `git log -1` on the server is the commit you think you're testing.

## 7. Production-readiness (do NOT skip — these break SILENTLY)
- [ ] **`APP_KEY` is in a password manager off the server**, and the Ploi deploy script contains **no
      `php artisan key:generate`**. Check the script, not your memory.
- [ ] **Horizon** runs under Supervisor with `autorestart=true`, and the deploy ends with
      `php artisan horizon:terminate`. `/horizon` shows active workers. **Without it nothing sends.**
- [ ] **The scheduler cron** `* * * * * php …/artisan schedule:run` is set. `php artisan schedule:list`
      shows the three jobs. Proof each ran:
  - `bookings:send-reminders` (09:00 daily): tomorrow's tandem booking gets `jump_reminder` and its
    reminder marker is set;
  - `courses:send-reminders` (09:10 daily): a course whose reminder is due sends `CourseMessageMail` to
    its students;
  - `bookings:release-expired-holds` (every 15 min): an abandoned tandem hold over 30 min old is
    released.
- [ ] **`php artisan storage:link`** done: an image uploaded in the admin shows on the public page.
- [ ] **`php artisan config:cache`** run after every env change; `php artisan settings:clear-cache` after
      any deploy that adds a settings property; PHP-FPM reloaded (`php8.4-fpm`).
- [ ] **Clean dataset before real customers:** launch from a fresh install, not surgery on test rows.
  - No `Audit …` test records.
  - No sample testimonials (they're seeded only locally).
- [ ] **Production admin:** created with `php artisan make:filament-user --panel=admin`. **No
      `test@example.com`** exists: `DevAdminSeeder` is local-only, and there is no Users screen, so check
      with `php artisan tinker --execute 'echo App\Models\User::pluck("email");'`.
- [ ] Stripe **live** webhook registered at the PRODUCTION URL with both events (§8).
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, real `APP_URL` (email links depend on it), real
      `APP_NAME`, `SESSION_SECURE_COOKIE=true`.
- [ ] Resend sending domain verified (SPF/DKIM), or mail lands in spam. `MAIL_FROM_ADDRESS` is on it.
- [ ] Sentry DSN set; `php artisan sentry:test` arrives, scrubbed.
- [ ] HTTPS enforced (Let's Encrypt in Ploi).
- [ ] **A MySQL backup exists AND a restore has been tested** (`db-migration/MYSQL-NOTES.md`).

## 8. The flip to live
- [ ] Swap to **live** Stripe keys (`pk_live_…` / `sk_live_…`) ONLY after §1–2 pass in test mode.
- [ ] Create a **separate live webhook** at `https://<production-host>/webhooks/stripe`:
  - events `checkout.session.completed` + `checkout.session.expired`;
  - **API version `2026-09-30.endive`**;
  - its own `whsec_…` → `STRIPE_WEBHOOK_SECRET`.

  The test endpoint's secret will not work. Then `php artisan config:cache`.
- [ ] Do ONE real low-value transaction with a real card, end to end:
  - send yourself a **payment link** for `1.00` from a test enquiry;
  - pay it;
  - confirm **£1.00** in the live Stripe dashboard, and that `payment_received` arrives;
  - then **refund it in Stripe** (the app doesn't track refunds; tidy the test booking by hand).
- [ ] Set General settings → site email back to the **real** owner inbox.
- [ ] Watch Horizon and the logs for the first few real transactions.

---

**If anything in sections 0–2 or 7 fails, you are not ready to go live.** Sections 3–6b failing are bugs to
fix but not money/trust/silent-failure risks. Fix the criticals, re-run that section, then proceed.

**Why this exists:** tests and audits gate *code*; this gates *launch*. It's run by a human, by hand,
because no automated check can verify that a real card was charged the right amount in the real Stripe
dashboard, or that a real email arrived looking right in a real inbox. Never skip it because the suite is
green.
