# Decisions log

Running log of judgement calls made during the autonomous CMS/booking build, newest last.

## Baseline

- **`RefreshDatabase` on the base `TestCase`** — the Instructor feature broke the example
  feature test because the in-memory SQLite test database had no migrations. Every feature
  test in this project will need a migrated database, so the trait lives on `Tests\TestCase`
  rather than being repeated per test class.
- **Instructor work committed as the starting point** — the previous session left the
  Instructor model/resource and the dynamic "Meet the team" section uncommitted. Verified
  green and committed as the baseline before starting Phase 1.

## Phase 1 — Content CMS

- **No spatie/laravel-medialibrary** — the established Instructor pattern stores plain
  public-disk paths via Filament `FileUpload`, and most images live in settings groups
  (no model to attach media to). One consistent image approach beats two; medialibrary
  would have added a second convention plus a Filament-v5 plugin compatibility risk.
  Images store either a bundled path ("/images/x.jpg", seeded) or an uploaded
  public-disk path; accessors/`imageUrl()` resolve both.
- **Hand-rolled Filament settings pages** — `App\Filament\Pages\Settings\SettingsPage`
  base class instead of the spatie settings Filament plugin (v5 compatibility unverified).
  One admin page per public page; spatie/laravel-settings underneath with
  `SETTINGS_CACHE_ENABLED=true` (busts on save automatically).
- **Settings array props use `@phpstan-var` only** — spatie's docblock reflector throws
  on `@var array` and array shapes; `@phpstan-var` is invisible to it (= "no cast",
  correct for plain arrays) while keeping Larastan level 6 typing.
- **No FAQs resource** — the design has no FAQ section anywhere, so none was invented.
  The Instagram grid became the GalleryImage resource (the only gallery in the design).
- **Testimonials: `quote` + optional `excerpt`** — the home page shows shortened
  versions of two quotes; `excerpt` (nullable) preserves that pixel-exactly with a
  `home_quote` fallback accessor, instead of duplicating testimonial records.
- **Trust badges live in GeneralSettings** — the four trust cards are shared by the
  home and AFF pages, so they belong to a shared group, not a page group.
- **Product-priced content deferred to Phase 2** — home services cards, tandem pricing
  table + weight charges, AFF price cards stay hardcoded until the Product model exists,
  so each is extracted exactly once. Pay-card/enquiry blocks wait for Phases 3–4.
- **Coached "From £60 per session" eyebrow is plain editable text** — it mentions a
  price but is marketing copy; it will not auto-update when coaching product pricing
  changes. Admin edits it on the Coached page settings screen.
- **Legal pages store HTML** — seeded with the exact current markup (including the
  `mt-4` classes) so the first render is byte-identical; later edits via RichEditor
  may normalise the markup, which is acceptable for owner-edited legal copy.
- **Image uploads in settings/edit forms are "keep current when empty"** — FileUpload
  fields dehydrate only when filled, so saving a form without re-uploading never wipes
  a seeded bundled-path image.

## Phase 2 — Products

- **Money is integer pence everywhere** (`Money::formatPence` for display). Merch shop
  items keep a free-text price label ("£15 – £30") because they are display-only.
- **AFF deposit seeded at £300** — the original site never states a deposit amount;
  £300 is a sensible placeholder the admin can change on the product.
- **Tandem fees modelled as non-purchasable add-ons** — P6 insurance and the rebooking
  fee share the pricing table with camera packages, so they are ProductAddOn rows with
  `purchasable = false`; only purchasable add-ons can be attached to payments later.
- **Consolidation Jumps is a second `aff` product** — it is a price card on the AFF
  page, not a separate type. The deposit flow targets whichever AFF product has a
  deposit amount set.

## Phase 3 — Enquiries

- **Forms stay visually identical** — the fake Alpine handlers were replaced by
  Livewire components whose root element is the original `<form>` markup; success and
  error feedback still go through the site's existing `window.toast`.
- **Toast-based validation errors** — the design has no inline error markup, so server
  validation failures dispatch a toast (matching the original client-side behaviour);
  browser `required` attributes remain the first line of validation.
- **Custom select binds via `x-init` watcher** — the WAI-ARIA select stores its value
  in Alpine state; a `$watch -> $wire.set` hook syncs it to Livewire without touching
  the component's markup or rebuilding JS assets.
- **Outbound-only threading** — admin replies are stored as EnquiryMessage records and
  emailed via Resend with reply-to set to the site address. Inbound email ingestion
  (Resend inbound webhooks + domain setup) is out of scope; customer replies arrive in
  the owner's normal inbox, and the enquiry reference in every subject links them back.
- **Email sending never blocks an enquiry** — mailables are queued and the queueing is
  wrapped in a try/catch (missing RESEND_API_KEY just logs); locally MAIL_MAILER=log.
- **Spam control** — honeypot field (silently pretends success) + 5 submissions per
  10 minutes per IP across all enquiry forms.

## Phase 4 — Payments

- **stripe/stripe-php over Cashier** — Cashier is built around subscriptions and
  customer billing; this business takes one-off Checkout payments with custom amounts,
  which the bare SDK models directly. `StripeCheckout` wraps the SDK so tests mock one
  seam and the SDK never leaks into actions.
- **Booking model created in Phase 4** (a phase early) — the webhook must convert paid
  enquiries into bookings, so the model/migration land here; the Filament resource,
  availability and calendar follow in Phase 5.
- **Booking price capture** — deposit/balance payments price the booking at the
  product's full price (instalments against it); full/custom payments price the
  booking at the amount paid (covers coaching and add-on-inclusive tandems).
- **Webhook idempotency** — Stripe retries deliveries; an already-paid payment is
  acknowledged and skipped, so retries can't double-convert or double-email.
- **Payment success/cancelled pages are minimal static Blade pages** built from the
  existing design components; they are new pages (nothing to preserve) and carry no
  content worth a settings group.
- **Pay-online cards on tandem/AFF pages still show the placeholder toast** — real
  public self-checkout (without an enquiry) would change the public flow; payments are
  currently admin-initiated via links, which matches the enquiry-first business model.

## Phase 5 — Bookings & calendar

- **Custom Livewire calendar instead of a FullCalendar plugin** —
  saade/filament-fullcalendar does not resolve against Filament v5, so the panel gets
  a hand-rolled month-grid page (bookings + slot capacity per day, month navigation,
  click-through to the booking). No drag-to-reschedule; the table's Reschedule action
  covers it.
- **Assigning a slot schedules + confirms** — a model-level saving hook copies the
  slot's start time onto the booking and promotes `pending_date` to `confirmed`, so
  the rule holds wherever a booking is updated, not just in one form.
- **Capacity is informational, not enforced** — slot options show remaining places and
  cancelled bookings free capacity, but the admin can deliberately overbook (their
  call on the dropzone). Public-facing booking would need hard enforcement.
- **Reschedule sets status `rescheduled`** and optionally emails the customer via the
  editable booking_rescheduled template; the admin promotes it back to confirmed once
  the customer is happy.

## Phase 6 — Extras

- **Vouchers are admin-issued, not publicly purchasable** — public voucher checkout
  would add a new public purchase flow (design change); the owner creates a voucher
  (typically after a payment-link sale or phone order), emails it via the editable
  gift_voucher template, and redeems it against a booking. Redemption creates a paid
  Payment with the new `voucher` method, so balances stay consistent.
- **Voucher "expired" is derived, not stored** — the stored status only tracks the
  admin-controlled lifecycle (active/redeemed/cancelled); a date comparison decides
  expired at display/redemption time, so no scheduled job can forget to flip it.
- **Booking confirmation emails fire from an observer** on the status transition to
  confirmed — including after a reschedule, which intentionally re-confirms the new date.
- **Reminders are once-only via timestamps** (`reminder_sent_at`,
  `balance_reminder_sent_at`) — jump reminders 7 days out, balance reminders 14 days
  out, sent by `bookings:send-reminders` scheduled daily at 09:00.
- **Customers dedupe on lowercased email** via `Customer::resolve()`; existing records
  gain missing phone numbers but names are never overwritten by later submissions.
- **Activity log is dirty-only on key fields** of bookings and payments (status,
  amounts, schedule) — an audit trail without logging every touch.

## Final review

- **Eloquent object caching removed** — final smoke testing found every public page
  500ing on its second request: Laravel 13's cache refuses to unserialize PHP objects
  (`cache.serializable_classes = false`, gadget-chain hardening) and the test suite
  missed it because tests use the array cache store. Rather than weaken the security
  default, pages query directly (a handful of indexed reads) and only the spatie
  settings cache (plain values) remains.
- **Tooling caveat** — the local pint/phpstan/phpunit wrappers do not propagate
  failure exit codes, so `&&`-chained commit commands can commit on red. One duplicate
  commit was squashed after this bit once; always read the JSON output.

# Round 4

## Branch situation

- **`design/visual-polish` was found fully merged** (fast-forward; `main` and
  `origin/design/visual-polish` point at the same commit, local branch deleted), so
  Round 4 work happens on a new `feature/round-4` branch off main, per the brief.
- **Per-recipient course-message status is batch-level** — emails are queued
  Mailables through Laravel's mail layer, which doesn't expose Resend message ids
  without swapping to direct SDK calls; the audit trail stores the full recipient
  snapshot (name/email/booking) per message and Horizon shows per-job failures.
  Documented as the "cheap" trade-off the brief allows.

## Part D — production-readiness audit

- **`APP_NAME` was still "Laravel"** — it heads every email the system sends; fixed
  in both env files. SETUP.md now opens with a "production will silently break
  without these" list: the cron entry, Horizon under Supervisor, BOTH Stripe webhook
  events (`checkout.session.expired` was missing from the docs — it's the primary
  abandoned-checkout path), APP_URL/APP_DEBUG, and the settings-cache deploy step.
- **Email previews are local-only routes** (`/dev/mail`, loaded only in the local
  environment) rendering every mailable with sample data inside a rolled-back
  transaction. Screenshotting all 12 caught a Blade syntax error in the voucher
  gift email that the whole suite missed — queued-mail fakes never render Blade —
  so a MailRenderTest now renders every designed mailable as a permanent guard.

## Part B — public vouchers

- **Voucher created at webhook time, not checkout time** — the purchase intent
  (purchaser/recipient/message) travels on the payment's new `metadata` column, so
  an abandoned checkout leaves nothing to clean up; issuing is idempotent against
  webhook retries via the `payment_id` link.
- **Partial redemption consumes the voucher only when the card payment succeeds**
  (voucher_id in the Stripe payment's metadata). If the voucher was spent elsewhere
  between checkout start and webhook, the booking confirms with an outstanding
  balance for the admin to chase rather than failing the customer's payment —
  logged as a warning.
- **Atomic redemption claim** — RedeemVoucher flips `active → redeemed` with a
  conditional UPDATE; a second concurrent redemption finds zero affected rows and
  throws, so a voucher can never be double-spent (tested with a stale instance).
- **The voucher email is a designed mailable, not an editable template** — the gift
  layout (code panel, personal message quote, redeem button) doesn't survive
  free-text templating; the unused gift_voucher template row is removed by the
  seeder. The admin "Email voucher" action uses the same mailable (one pathway).
- **Printable/PDF voucher deferred** — would add a dompdf dependency for a
  nice-to-have; the email IS the voucher (code is what matters). Logged as future work.

# Round 2

## Part A — fixes

- **Calendar root cause** — the panel served Filament's precompiled CSS, which lacks
  the Tailwind utilities used by custom panel views; the month grid rendered as an
  unstyled stack. Fixed with a real panel theme compiled by the app's Vite/Tailwind v4
  build (`resources/css/filament/admin/theme.css`, `@source` over `app/Filament` and
  `resources/views/filament`). Any future custom panel view gets working utilities.
- **Pages-folder audit** — the calendar was the only model-managing page; it is now a
  page of the Bookings resource (`/admin/bookings/calendar`) with its own sidebar item
  via `BookingResource::getNavigationItems()`. The settings pages remain custom pages:
  they manage spatie settings groups, not models, which the brief explicitly allows.
  Every resource and settings page now declares an explicit `navigationSort`.

## Part B — infrastructure

- **Content cache stores raw attribute arrays, never objects** — `SiteContent`
  caches `getAttributes()` rows and rehydrates via `Model::hydrate()` on read, so
  casts, accessors and (for the tandem product) a manually re-attached add-ons
  relation all work while the cache payload stays object-free. A dedicated test
  walks every cached payload and fails on any PHP object. `SiteContentObserver`
  (registered via `#[ObservedBy]` on each content model) busts the affected keys
  on save/delete.
- **Horizon access** — `/horizon` uses `['web','auth']` middleware plus a
  `viewHorizon` gate allowing any authenticated user (the users table is admins
  only); guests are redirected to the panel login via `redirectGuestsTo`.
- **`composer dev` now runs Horizon** instead of `queue:listen`, since Redis is the
  default queue connection.
- **intervention/image (GD) only; spatie/image-optimizer skipped** — the optimizer
  package shells out to system binaries (jpegoptim, pngquant, optipng) that are not
  installed; GD has native WebP support here, and re-encoding resizes, compresses
  and strips metadata in one step with no system dependencies.
- **WebP conversion keeps the original file** as a fallback and rewrites every
  reference (model attributes and settings properties listed in the
  `ImageOptimization` registry) to the `.webp` path. Reference rewrites use
  `saveQuietly` so observers don't loop; the site-content cache is flushed manually.
- **Round 2 Parts C & D were superseded mid-run** — the Round 3 prompt arrived while
  Part B4 was in flight. AFF course dates and the public booking flows are built as
  part of Round 3's frontend–backend alignment (on the design branch) instead of as
  separate Round 2 features.

## Round 3 — direct booking flows (reverses the enquiry-first decision, per brief)

- **Holds via a `pending_payment` booking status** — the booking is created inside a
  `lockForUpdate` transaction (capacity re-checked under the lock, so concurrent
  customers cannot overbook), occupies a place immediately, and is released by the
  `checkout.session.expired` webhook, by a 45-minute scheduled sweep
  (`bookings:release-expired-holds`), or instantly if Stripe session creation fails.
  Stripe sessions are created with their 30-minute minimum expiry.
- **Booking flow UX**: three steps (pick date/course → details → review & pay) with
  inline validation (new design components: booking steps/field/notice) — unlike the
  enquiry forms, which keep their original toast-based feedback.
- **Direct bookings email a receipt + a booking confirmation** (the confirmation
  comes from the existing BookingObserver on the pending→confirmed transition) plus
  the admin notification — same templates the admin flows use.
- **Empty Stripe keys no longer 500** — StripeClient is bound with a null api_key
  (array config) so injection never throws; failures surface at call time where the
  flows catch them, release the hold and show a friendly message. Found by walking
  the flow in a real browser with no keys set.
- **Settings cache must be cleared when settings classes gain properties**
  (`php artisan settings:clear-cache`) — new booking-page settings 500'd against the
  stale Redis payload until cleared; added to SETUP.md deploy notes.
- **Reserved Livewire view variable**: `$slots` collides with Livewire 4's slot
  support (SlotProxy) — booking views receive `$availableSlots` instead, and
  components pass explicit data from render() (plain Livewire components do not get
  Filament's `getXProperty` magic).

## Tooling fix (first task)

- **The wrappers were never broken** — re-diagnosis showed laravel/pao (the
  agent-output formatter on these binaries) passes exit codes through correctly;
  last session's "swallowed failures" came from piping tool output through
  `| tail -n`, which makes the shell report tail's exit status. Fix: a committed
  `composer check` script (pint --test → phpstan → full suite) that aborts on the
  first failure; verified to exit 1 on a deliberately broken file and 0 when clean.
  CLAUDE.md's caveat corrected.
- **Redis installed via Homebrew** — the environment declared Redis enabled, but no
  server or binaries existed; `brew install redis` + `brew services start redis`
  fulfils the stated environment. predis is the PHP client (the phpredis extension
  is not loaded in the local PHP).
