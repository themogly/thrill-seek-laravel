# Email audit — G-Force Skydiving

Kit file `audits/email-audit.md`, run verbatim as item 3 of unattended run 1. Branch `email/audit-pass`
off `main` = `911ba91`. Report written before any fix.

**How it was verified.** I used `MAIL_MAILER=log` and the real Redis queue, processed by the real worker
(`queue:work redis --tries=1`, matching Horizon's supervisor `tries`), on `thrill-seek-laravel.test` with
rebuilt assets. I pressed the real controls with Playwright:
- **Public:** contact form, account sign-in link request, footer newsletter signup.
- **Admin:** enquiry Reply, newsletter "Send test to me", Record bank transfer, booking edit → Confirmed,
  and booking Create with status Confirmed.

The Stripe paths can't run in the real UI locally (no Stripe keys). For those, the real
`HandleStripeWebhook` handler took a constructed `checkout.session.completed` event, and the real worker
processed the result. Everything else marked *code read* is a hypothesis until someone presses it
(false-green #5).

## Inventory

All sends go to the `redis` connection, `default` queue, which is the only queue Horizon's `supervisor-1`
watches (`config/horizon.php:202`). No send calls `onQueue`. Confirmed by reading the Redis list: every
job sat in `queues:default`.

| Email | Trigger | Call sites | Queued? | Locale | UI claim after sending | Test asserting the send | Verified by doing |
|---|---|---|---|---|---|---|---|
| `EnquiryAdminNotification` (to owner) | public enquiry form; customer reply from account | `CreateEnquiry.php:67`, `RecordCustomerEnquiryReply.php:38` | yes | app default (en) | toast "Message/Enquiry sent!" (about the enquiry) | `Enquiries/EnquiryFormsTest`, `Account/MessagesTest` | ✅ contact form → delivered |
| `TemplatedMail` · `enquiry_acknowledgement` | public enquiry form | `CreateEnquiry.php:69` | yes | en | (same toast) | `Enquiries/EnquiryFormsTest` | ✅ delivered |
| `AccountLoginLinkMail` | "Email me a sign-in link" | `RequestAccountLoginLink.php:31` | yes | en | "we've emailed you a secure sign-in link" (neutral by design) | `Account/AuthTest` | ✅ delivered, link absolute |
| `NewsletterConfirmationMail` | newsletter signup (double opt-in) | `NativeNewsletterService.php:57` | yes | en | "check your inbox to confirm" | `NewsletterTest` | ✅ delivered |
| `NewsletterCampaignMail` · broadcast | admin "Send to subscribers" | `SendNewsletterCampaign.php:41` | yes, one per subscriber | en | "Newsletter queued — Sending to N" | `NewsletterCampaignTest`, `Newsletter/BuilderTest` | code read |
| `NewsletterCampaignMail` · test | admin "Send test to me" | `EditNewsletterCampaign.php` | **now synchronous (`sendNow`)** — was queued and failed | en | "Test sent" only on success; "Test email failed" + transport error otherwise | `Newsletter/BuilderTest` (real array transport + failure path) | ❌ was failing in the worker → ✅ fixed, delivered (re-checked by doing) |
| `EnquiryReplyMail` | admin enquiry Reply | `SendEnquiryReply.php:27` | yes | en | "Reply sent — Emailed to {email}" | `Enquiries/EnquiryInboxTest` | ✅ delivered, per-enquiry Reply-To |
| `TemplatedMail` · `payment_link` | admin "Send payment link" | `SendPaymentLink.php:43` | yes | en | "Payment link sent — Emailed to …" | `Payments/SendPaymentLinkTest` (one test named "can send a payment link" asserts no mail) | code read (no Stripe key) |
| `TemplatedMail` · `payment_received` + `PaymentReceivedAdminNotification` | payment succeeds: enquiry conversion, held-booking confirm, bank transfer | `SendPaymentReceipt.php:28,41` (from `ConvertEnquiryToBooking`, `ConfirmHeldBooking`) | yes | en | payment-success page "check your inbox for the confirmation" | `Payments/StripeWebhookTest`, `Booking/DirectBookingWebhookTest` | ✅ bank transfer → both delivered |
| `TemplatedMail` · `payment_received` + `PaymentReceivedAdminNotification` — **customer balance payment** | customer "Pay balance" in account → webhook | `HandleCheckoutSessionCompleted` → `SendPaymentReceipt` (**added**) | yes | en | payment-success page "check your inbox for the confirmation" | `Account/BookingsTest::test_paid_balance_webhook_emails_the_receipt_and_tells_the_owner` | ❌ nothing was queued → ✅ fixed, both delivered (re-checked by doing) |
| `TemplatedMail` · `booking_confirmed` | booking status changes to Confirmed | `BookingObserver.php:36` (`updated` only) | yes | en | none | `Bookings/AutomatedEmailsTest` | ✅ admin edit → Confirmed → delivered; ❌ admin **Create** with status Confirmed → nothing queued (see Owner decisions) |
| `TemplatedMail` · `booking_rescheduled` | admin Reschedule (toggle "Email the customer") | `RescheduleBooking.php` | yes, wrapped; **now returns whether it queued** | en | "The customer has been emailed." only when it was; else "Booking rescheduled — email not sent" | `Bookings/BookingManagementTest` (incl. failure path) | code read + test |
| `TemplatedMail` · `jump_reminder`, `balance_reminder` | `bookings:send-reminders` (daily) | `SendBookingReminders.php:42,70` | yes; `reminder_sent_at` / `balance_reminder_sent_at` markers | en | n/a | `Bookings/AutomatedEmailsTest` | code read |
| `VoucherGiftMail` | voucher purchase paid (webhook); admin "Email voucher" | both via the one `EmailVoucher` action (**was** two paths, one raw `Mail::`) | yes | en | "Voucher emailed" only when queued, else "Voucher email failed" | `Vouchers/PublicVoucherPurchaseTest`, `Vouchers/VoucherTest`, `Vouchers/VoucherEmailPathsTest` | code read (no redeemable voucher locally) |
| `CourseMessageMail` | admin course message; `courses:send-reminders` | `SendCourseMessageToRecipient.php:32` (job, one per student, `$tries = 3`) via `SendCourseMessage` | the **job** is queued; the mail is sent synchronously inside it | en | "Message queued …" | `Courses/CourseCommunicationsTest` (job pushed) | code read |
| Filament `ResetPassword` notification (to staff) — **added by prompt 027, run 3** | admin login → "Forgot password?" | Filament `RequestPasswordReset` (subclass `App\Filament\Auth\RequestPasswordReset`) | yes (`ShouldQueue`) | en | the same "We have emailed your password reset link" notice for known and unknown addresses (no enumeration) | `Admin/AdminPasswordResetTest` | render + `/dev/mail/admin-password-reset`; a real inbox on staging (`verification/CHECKLIST.md` §2) |

**Package-sent mail:** none. The Filament panel registers `->login()` only, with no password reset,
email verification or registration. No `Notification` classes exist. No `Mail::raw` / `Mail::html`.

**Absences checked** (an email that should exist but has no caller):
- All seven seeded `EmailTemplate` keys have a caller. All nine mailables have a production caller.
- **Missing:** the receipt for a customer's account balance payment (Phase 1 #1).
- Admin-originated acts that skip the customer's email: booking **created** already Confirmed, and a
  voucher **redeemed** against a booking from Vouchers → Redeem (Owner decisions).
- No email on admin cancellation. Nothing promises one, so it's not a finding; noted under Discussion.

## PHASE 1 — Must-fix (an email that is promised but never arrives)

- **Customer balance payment:** no receipt to the customer and no notification to the owner. The success
  page promises a confirmation email. → In `HandleCheckoutSessionCompleted`, a paid payment against an
  existing booking that isn't a pending hold sends the shared `SendPaymentReceipt`, the single action the
  other success paths already use. → **Why it matters:** this is the one Stripe path in "My Account". The
  customer pays, is told to check their inbox, and nothing comes. The owner isn't told money arrived
  either.
- **Newsletter "Send test to me":** the job fails in the worker with `ModelNotFoundException`. The mailable
  carries an unsaved `NewsletterSubscriber` (id 0), and `SerializesModels` can't restore it. The UI has
  already said "Test sent". → Send it **synchronously** (`sendNow`), and show a danger notification with
  the transport error if it throws. → **Why it matters:** the owner's only pre-flight check of a newsletter
  silently never arrives, so it's either skipped or trusted blind.
- **Retries:** every queued mailable gets exactly one attempt. Horizon `tries => 1`, and no mailable sets
  `$tries` or `$backoff`. → Add an abstract `App\Mail\QueuedMailable` base (`$tries = 4`,
  `$backoff = [30, 120, 600]`, `ShouldQueueAfterCommit`, and a `failed()` hook). Every queued mailable
  extends it. → **Why it matters:** one Resend timeout loses a confirmation, a receipt or a sign-in link
  for good.
- **After commit:** no mailable is queued after commit, and every one serialises a model. → Implement
  `ShouldQueueAfterCommit` on the base. → **Why it matters:** a send queued inside a transaction can run
  before the row exists or after a rollback. Today the sends sit mostly outside transactions, so this is
  the guard for the next one.
- **Nobody would see a mail job that finally fails.** Failures sit in `failed_jobs`. Sentry sees the
  exception only if `SENTRY_LARAVEL_DSN` is set, and Ben reads Sentry, not the owner. → The base
  `failed()` logs the mailable class (never the address). A dashboard widget shows failed email jobs in
  the last 7 days by type, plus mail-config warnings. → **Why it matters:** the owner lives in the panel.
- **The same voucher email is sent from two places.** `IssuePurchasedVoucher` (wrapped, with logging) and
  `VoucherResource` "Email voucher" (raw `Mail::`). → Move the send into one `EmailVoucher` action that
  both call. → **Why it matters:** this is the kit's sibling-path shape. A change to one path (CC the
  owner, add a guard) silently skips the other.
- **"The customer has been emailed." after a swallowed failure.** `RescheduleBooking::emailCustomer`
  catches a queueing failure and logs it, and the table action still says the customer was emailed. →
  Return whether the email was queued, and word the notification from the result. → **Why it matters:**
  the owner tells a customer "check your email" when nothing went.

**Configuration (checked, not a finding):**
- The provider key name matches end to end: `RESEND_API_KEY` in `config/services.php:22`,
  `.env.example:75` and `SETUP.md:183`.
- `QUEUE_CONNECTION=redis` matches SETUP.
- Every send uses the queue Horizon watches.

Review: Phase 1 fixes ship with failing-first tests (balance receipt, sync test-send, reschedule wording).
The retry/after-commit/failed rules ship in the inventory guard.

## PHASE 2 — The email that arrives is right

- **Locale — not applicable today, guarded.** The app has one locale (`APP_LOCALE=en`, no `lang/`
  directory), so the worker's default is the recipient's language. Pinning `->locale('en')` on 16 call
  sites would be ceremony. → The inventory test's locale rule asserts the app is single-locale. Adding a
  second locale fails the build until every `Mail::to()` pins one. → **Why it matters:** this is the
  classic queued-mail locale bug, and it can't arrive unnoticed.
- **Worker context.** `TemplatedMail` renders subject and body in its constructor, in the request, so the
  queued job carries frozen strings. The sign-off reads `GeneralSettings` through the accessor with a
  fallback (`EmailSignoffTest`). No template reads `request()`, `session()` or `auth()`. ✅
- **Render tests.** `MailRenderTest` renders all nine mailables, and `/dev/mail` lists them (local only).
  ✅
- **Links.**
  - Every link is absolute. Links built in the worker use `APP_URL`; locally that's `http://localhost`,
    so a wrong production `APP_URL` would break every link (OWNER/OPS).
  - The sign-in link is built in the request with a 20-minute expiry. That outlives the new retry window
    (30 s + 2 min + 10 min ≈ 12.5 min). ✅ It's close, so the inventory test records the relationship.
- **Subject, sender, reply-to.**
  - The sender is `MAIL_FROM_NAME`, and the delivered mail showed "G-Force Skydiving".
  - Reply-to is the owner's address, or the per-enquiry inbound address on enquiry replies. ✅
  - The `From` is the placeholder `hello@example.com` until the server env is set (OWNER/OPS).
- **Plain-text alternative.** Markdown mailables generate one, and `NewsletterCampaignMail` has
  `mail.newsletter-text`. Five `text/plain` parts were seen in the delivered log. ✅
- **Personal data.** The activity log records booking/payment state fields only (`Booking::logOnly`,
  `Payment::logOnly`), never addresses or tokens. The sign-in token appears only in the link. ✅
- **Sent once.**
  - Reminder markers, newsletter per-recipient claim rows, and course-reminder `sent_at` are all in place.
  - One message per recipient (newsletter, course messages).
  - No mailable writes rows or issues tokens in the worker, so a retry duplicates nothing. ✅
- **Unsubscribe.** The newsletter carries a working signed one-click unsubscribe link (footer and
  plain-text). It has **no `List-Unsubscribe` / `List-Unsubscribe-Post` header**. Gmail and Yahoo expect
  that for bulk senders, and it needs a POST endpoint. → Proposed as follow-up prompt **E-1**, not fixed
  here (a new route and a CSRF exemption).

Review: Phase 2 has no must-fix findings.

## PHASE 3 — Someone would notice

- **No mail test command.** → Add `php artisan gforce:mail-test {email}`. It sends one plain message
  **synchronously** through the configured mailer and prints "sent" or the transport's error.
- **No health check.** The app has no status page, only `/up`. → Mail health goes on the admin dashboard
  widget (Phase 1). It flags `log`/`array` in production, an empty Resend key and a placeholder
  from-address, and shows failed email jobs in the last 7 days by type. It reads config only and never
  sends.
- **No structural guard.** → `tests/Feature/Mail/MailInventoryTest.php` covers:
  1. every mailable has a production caller;
  2. every queued mailable extends `QueuedMailable` (exemption: `CourseMessageMail`, sent synchronously
     inside the retrying `SendCourseMessageToRecipient` job);
  3. single-locale, or every `Mail::to()` pins a locale;
  4. every UI "sent/emailed/check your inbox" claim is paired with its mailable.
  Each rule is proven by a planted violation.
- **Tests whose names say an email is sent but don't assert it:**
  - `SendPaymentLinkTest::test_admin_can_send_a_payment_link_from_the_enquiry_page`: add the mail
    assertion.
  - `VoucherPdfTest::test_the_gift_email_still_sends_without_a_pdf`: it renders and never sends, so
    rename it to `…_still_renders_…`.

Review: guard proven, command run against the log mailer, full suite green.

## OWNER / OPS

- **Production `.env`** (names exactly as config reads them): `MAIL_MAILER=resend`, `RESEND_API_KEY`,
  `MAIL_FROM_ADDRESS` (on the verified domain, not `hello@example.com`), `MAIL_FROM_NAME`,
  `APP_URL=https://<real host>`, `QUEUE_CONNECTION=redis`.
- **DNS:** SPF, DKIM and DMARC for the sending domain, and the domain verified in Resend's dashboard.
  Optional: a Resend bounce/complaint webhook.
- **Staging:** the mail logo is an absolute `APP_URL` image (`vendor/mail/html/header.blade.php:11`).
  Behind staging basic-auth, recipients' clients can't fetch it, so expect a broken logo on staging only.
  **Decision recorded:** keep the absolute URL for now. Switching to a CID-embedded PNG (the kit's newer
  default) is follow-up **E-2**. It changes every mail's markup and wants its own render-and-inbox check.
- **After deploy:** `php artisan gforce:mail-test you@yourdomain` should print "sent", and a real email
  should arrive in a real inbox. After the first day, the dashboard's failed-emails figure should read 0.

## OWNER DECISION — ANSWERED 9 Oct (see DECISIONS): option B, built in 009

- **Should admin-originated acts email the customer the way the online paths do?**
  - (a) Creating a booking in the admin with status Confirmed sends **no** `booking_confirmed`. Confirming
    an existing booking does send it. Verified by doing.
  - (b) Vouchers → Redeem against a booking records a paid payment but sends **no** receipt. Recording a
    bank transfer sends one. Code read.

  Options:
  - **A:** always send, matching the online paths. Risk: back-filling historic bookings emails customers.
  - **B (recommended):** add an "Email the customer" toggle, default on, like Reschedule.
  - **C:** never send from admin.

  Not implemented either way.

## Discussion

- CLAUDE.md says mail is "queued and wrapped so failures log instead of breaking the request". The kit
  says "a success message is never shown when the send threw". Both hold if wrapping is kept where the UI
  claim is about something else (enquiry stored, newsletter request recorded). Where the claim *is* the
  email (reschedule), the claim follows the result. `CreateEnquiry`'s "Message sent!" is about the
  enquiry, which is stored. Not changed.
- **Out of scope, its own prompt (E-3):** after a gift-voucher purchase, `PaymentSuccessPage` has no
  booking, so a *paid* voucher payment shows "Almost there — we're waiting to confirm your card payment.
  Refresh" indefinitely. Code read.
- No email on admin booking cancellation. Not promised anywhere; an owner question if wanted.

## Proposed follow-up prompts

- **E-1** — `List-Unsubscribe` + `List-Unsubscribe-Post` headers with a one-click POST endpoint on the
  newsletter.
- **E-2** — CID-embedded PNG mail logo instead of the absolute `APP_URL` image.
- **E-3** — the payment-success page for a paid voucher purchase.
- **E-4** — the owner's answer to the admin-originated emails decision above.

## Outcome (fixes on `email/audit-pass`)

Every Phase 1 and Phase 3 item was fixed failing-first, one commit each. Phase 2 had no must-fix
findings.

| Item | Commit | Proof |
|---|---|---|
| Balance-payment receipt + owner notification | `fix(email): receipt + owner notification for customer balance payments` | new test red on main; by doing: both delivered |
| Newsletter test-send synchronous, failure reported | `fix(email): newsletter test-send goes synchronously…` | two tests red on main with the worker's exact error; by doing: delivered, "Test sent" |
| `QueuedMailable` base: tries 4, backoff 30 s/2 min/10 min, after commit, `failed()` log | `fix(email): queued mail retries…` | `QueuedMailRetriesTest` red on main |
| One `EmailVoucher` action | `refactor(email): one EmailVoucher action…` | `VoucherEmailPathsTest` red on main |
| Reschedule claim follows the result | `fix(email): reschedule only says…` | failure-path test red on main |
| Dashboard mail health + help-guide section | `feat(email): mail health on the dashboard…` | `MailHealthTest` |
| `gforce:mail-test` | `feat(email): gforce:mail-test command…` | `MailTestCommandTest`; run against the log mailer and landed in the log |
| Inventory guard | `test(email): mail inventory guard…` | each of the 4 rules planted red, then green |
| Mail-named tests | `test(email): mail-named tests assert the send…` | — |

**Final check:**
- Every mailable has a caller and a render test (`MailRenderTest`, all 9).
- The inventory test passes and was proven red.
- The mail-test command works against the log mailer.
- The full suite is green (399 tests, SQLite and MySQL).

**Still open:** the owner decision (admin-originated emails), and follow-ups E-1 to E-4. The OWNER/OPS
list above is unchanged.
