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

# Round 5A — dates & locations

- **Starting point**: branch `feature/dates-locations` off main at `10202a3` (Round 4
  head; both prior feature branches were found fast-forward merged).
- **AvailabilitySlot renamed to TandemDate** — pure table/column/class renames, safe
  on live data; the two date concepts (single-day tandem dates vs multi-day AFF
  courses) were already separate tables, so Part B5 is naming + seam-tightening
  rather than a structural split.
- **Location backfill strategy** — the migration itself creates a "Devon" default
  (the primary UK dropzone inferred from the tandem page content) for existing
  tandem dates, and lifts every distinct CourseDate location *string* into a
  Location row before the FK becomes required and the string column drops.
  Verified against the seeded development database, not just fresh migrations.
- **5-day minimum is enforced in validation + a live day count in the form**, not a
  SQL CHECK — adding a CHECK to an existing SQLite table requires a full table
  rebuild that Laravel's schema builder doesn't express for this case; the form rule
  plus tests are the practical guard, documented per the brief's "where practical".
- **Clash rule scope** — cancelled courses don't block tandem dates; the same day at
  a *different* location is explicitly allowed (tested). Self-exclusion isn't needed
  because each direction checks the other table.
- **Calendar courses render as day-chips spanning the range** (full label on day one,
  continuation bars after) rather than a true multi-cell spanning element — the
  month grid is CSS-grid day cells, and per-day chips keep the markup simple while
  reading clearly as a span. New panel utilities required a theme rebuild (caught by
  the authenticated Playwright screenshot — chips rendered uncoloured before).

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

## Consistency pass (chore/consistency)

- **Rule 1 — data access**: the only violations found were Livewire display reads of
  the tandem product (`BookTandem`, `BuyVoucher`) — now routed through
  `SiteContent::tandemProduct()`. The three enquiry forms keep a live `Product`
  lookup because it happens on the write path (attaching the product to a new
  enquiry), now commented as such. Views contain no inline queries. Spatie
  settings classes were left as-is: they already carry their own Redis-backed
  cache and invalidation (`settings:clear-cache`), so wrapping them in
  `SiteContent` would be a second cache layer for no behaviour gain.
- **Rule 2 — controllers**: `PageController::paymentSuccess()` was the only method
  with inline lookup logic; both resolution paths and their precedence were pinned
  by `PaymentSuccessResolutionTest` first, then the logic moved to
  `App\ViewModels\PaymentSuccessPage` (the repo had no page-class convention yet —
  this establishes it; see Rule 5). Every other controller method already just
  resolves and returns.
- **Rule 3 — webhooks**: the controller was already a thin verify-and-dispatch
  shell, but both event branches lived as private methods inside
  `HandleStripeWebhook`. They are now `HandleCheckoutSessionCompleted` and
  `HandleCheckoutSessionExpired` Actions (shared session→payment lookup in
  `Actions\Concerns\ResolvesCheckoutPayment`), with `HandleStripeWebhook`
  reduced to the event-type match. Both events were already covered by
  StripeWebhookTest / DirectBookingWebhookTest, which pinned the behaviour.
- **Rule 4 — Blade vs Livewire**: audit found zero violations in either direction.
  All 16 public routes render plain Blade from `PageController`; the only Livewire
  on the site is genuine server interactivity (booking flows, enquiry/contact
  forms, voucher purchase, newsletter island in the footer layout) and there is no
  hand-rolled fetch/XHR anywhere in views or resources/js. No conversions, so no
  visual re-verification was needed.
- **Rule 5 — Support folder**: kept as `App\Support`. Its six classes are genuine
  cross-cutting utilities (Money, SiteIcons, TemplateRenderer, the SiteContent
  cache gateway, the ImageOptimization registry, the DateClash domain rule) — none
  assemble data for a specific page, so a rename to ViewModels would mislabel all
  of them. Page view-models have their own home: `App\ViewModels`, established by
  `PaymentSuccessPage` (Rule 2). Convention: one class per page that needs
  assembly logic, named `<Thing>Page`, exposing `viewData(...)` for the view.
- **Rule 6 — strict_types**: all 265 `declare(strict_types=1)` statements removed
  (app, database, routes, tests, config, bootstrap) and the decision pinned in
  `pint.json` with `"declare_strict_types": false` so Pint can never reintroduce
  it. Full suite re-run after removal — no behaviour change surfaced (the
  codebase's full type-hints keep coercion at the edges). The CLAUDE.md
  instruction now states the inverse rule.

## Round 5B — bold redesign (design/bold-redesign)

- Started from main after merging Round 5A and the consistency pass.
- **Design direction** (no reference images existed in design-review/references):
  the owner's written critique is the brief — sharp editorial language, radius 0,
  rules/borders for structure, navy bands, freed photography, typographic stats,
  Bebas pushed harder. Decorative shadows/glows/gradient washes removed from the
  public site; the sticky header keeps a functional shadow.
- **Coach photos**: the brief asks the section to be designed around real
  photography with placeholders seeded. Three distinct portrait crops were derived
  from the bundled brand photography (public/images/instructors/) and seeded as
  bundled paths — the owner replaces them through the existing admin upload. The
  Instructor::photo_url accessor gained the same bundled-path handling every other
  image model already had.
- **Voucher PDF**: dompdf (barryvdh/laravel-dompdf) over spatie/laravel-pdf — the
  latter needs headless Chrome on the server; dompdf is pure PHP. Helvetica is the
  closest bundled face to the display type; the brand look comes from the navy
  band/scale/tracking. Generated at purchase fail-soft (a PDF failure logs and the
  voucher still issues; the render test is the guard against template errors),
  stored on the private local disk, attached to the gift email, admin re-download
  regenerates for admin-issued vouchers.
- **Email theme**: published only the markdown theme CSS (gforce.css) rather than
  all vendor mail views — smallest surface that restyles every mailable.
- **Screenshot harness** now emulates prefers-reduced-motion (entrance reveals
  otherwise sit at opacity 0 in full-page captures); reveals verified by hand.
- Tests pinning old markup needed no changes — none asserted card chrome.

## Round 6 — owner's design feedback (design/bold-redesign, unmerged)

- **Hero light-blue token**: the invented `--hero-accent` shade is gone everywhere
  (`.text-hero-accent` utility deleted); the accent role is filled by the existing
  palette token `--sky-bright`. The voucher PDF's hex equivalent moved to the
  palette's `#0ea5e9`.
- **Trust band icons**: one flat monochrome icon per stat (existing CMS `icon`
  field), drawn in `sky-bright` at 32px, no circles/gradients/glows. The dark
  stats band itself stays — the owner named it as a band that works.
- **One button system**: `<x-ui.button>` is the only way to render a button.
  Exactly three variants — `primary` (solid brand blue), `outline` (2px
  `border-current`, so it self-adapts to light and dark surfaces; the old
  `outline-light` fork is gone), `link` (quiet inline text action, no box;
  adopted by the booking flows' back-step actions). All per-call colour
  overrides stripped. Display classes (`hidden`, `flex`…) are never passed to
  the button — base `inline-flex` conflicts and compiled-CSS order decides —
  responsive visibility is done with a wrapper (see header).
- **No flat-black heroes on secondary pages**: `page-hero` without an image is
  now a compact navy-gradient band (`bg-sky-gradient`, py-16/24, one type step
  smaller) instead of the full-height ink band. Per-page decision: tandem, AFF
  and coached keep their CMS photographic heroes; every other page (shop,
  testimonials, hall of fame, contact, vouchers, privacy, terms, book-tandem,
  book-aff, payment-success/cancelled, 404) gets the compact navy hero — none
  of those pages has a CMS hero-image field, and adding twelve image settings
  was judged scope creep for a corrections round; any page can be promoted to a
  photo hero later by adding the field and passing `:image`.
- **AFF "Secure your place"**: the shared `pay-card` (tandem + AFF) moved from
  the ink band to flat deep navy (`bg-secondary`) with the same primary rule.
- Dark `band-ink` survives only where the owner said it works: stats band,
  about band, newsletter band, tandem gift band, footer, image placeholders.

## Round 7 — feature toggles, admin date pickers, newsletter (feature/round-7)

- **Branch state**: branched off `main`. The Round 5B/6 bold redesign
  (`design/bold-redesign`) was still unmerged at the time, so this work sits on
  the *pre-redesign* design language. The new public surfaces added here (the
  enquiry-first confirmation panels, the dedicated `/newsletter` page, the
  newsletter status pages) follow the **current main** look; if/when the redesign
  merges, re-skin these to the Round 6 language (sharp corners, shared button
  variants, compact navy hero) and re-verify in Playwright.

### Part A — feature toggles
- Toggles live on `GeneralSettings` (a new "Features" section), matching the
  established spatie-settings pattern; reads are a cached settings lookup.
- **Shop** (`shop_enabled`, default OFF — the storefront has no checkout yet). Off
  hides Shop from the header nav, footer and sitemap and **404s** the `/shop` route
  via a reusable `feature:` middleware (chosen over a home redirect: the page
  genuinely doesn't exist while disabled, and 404 is the honest status — no shop
  code or data is removed, flipping it on restores everything immediately).
- **Online payments** (`online_payments_enabled`, default ON). Off reverts the
  public site to **enquiry-first**: the tandem/AFF/voucher flows keep their
  date/course pickers, but the terminal action creates an `Enquiry` (capturing the
  chosen date/course + all collected details) instead of a Stripe Checkout session
  — the funnel becomes an enquiry rather than the CTA pointing elsewhere, which
  keeps one coherent flow and satisfies "no Stripe session from the public site".
  **Scope, deliberately narrow**: the toggle governs *public checkout only*. Admin
  payment tools (send Stripe link, record bank transfer) and the signed
  `/webhooks/stripe` endpoint are untouched, so admin-sent links still complete.
- **Auto-hide empty UI**: the booking calendar nav hides until the first booking
  exists (a `bookings.any` flag cached forever, busted by the booking observer on
  create/delete — steady-state cost is one cache hit, not a COUNT per render). Same
  principle, applied narrowly: the read-only course "Message history" relation
  manager hides until a message exists, and "Message students" hides until the
  course has paid, non-cancelled students. We did not hide anything that the owner
  needs as an entry point.

### Part B — admin date pickers
- **Native input mode, uniformly, via `App\Support\AdminDates`.** Filament's
  non-native (JS) picker renders a *readonly* text field — click-only, the owner's
  complaint. Its native `<input type="date">`/`datetime-local` mode is
  keyboard-typeable *and* shows the browser calendar, so it's the one mode that
  gives "type it or pick it". Native renders in the browser locale (dd/mm/yyyy in
  the UK) and ignores a custom display format, so we don't set one. Every date
  field is built through the helper so it can never regress to the click-only
  picker. Defaults added: new tandem date → today 09:00; new course → today + 4
  days (the 5-day minimum), with the end tracking the start without shrinking a
  longer course. The live day-count and 5-day rule are unchanged.

### Part C — newsletter
- **Engine: our database is the list of record; Resend is the transport.** We keep
  subscribers, status and consent/unsubscribe timestamps in our own tables (behind
  a `NewsletterService` interface) and send through Resend via the normal queued
  mail pipeline. We did **not** delegate list management to Resend Audiences/
  Broadcasts: authoritative, auditable consent and unsubscribe records (with
  timestamps) belong in our DB for compliance, it keeps one provider and no new
  keys, and double opt-in + signed unsubscribe are cleaner in-app. The interface
  isolates list management so a future move to Resend Audiences (or another
  platform) is a single binding swap in `AppServiceProvider`.
- **Double opt-in**: signup records a *pending* subscriber (+ consent timestamp)
  and emails a signed confirmation link; confirming sets *confirmed*. An
  already-confirmed address is a quiet no-op (no duplicate, no re-send). Existing
  pre-Round-7 rows were migrated to confirmed/consented so none are dropped.
- **Compliance**: confirm and unsubscribe are signed, login-free routes (Laravel
  signed URLs — needs `APP_KEY`); unsubscribe is immediate and timestamped; every
  broadcast carries a one-click unsubscribe link; sending targets the `confirmed`
  scope only, so pending and unsubscribed/suppressed addresses are never emailed.
- **Sending**: a "Compose newsletter" admin action creates an immutable
  `NewsletterCampaign` (subject, rich body, recipient count, sent-at, sent-by) and
  queues one `NewsletterCampaignMail` per confirmed subscriber (one bad address
  can't fail the batch). Campaigns are read-only history afterwards. Both new
  mailables are in the permanent mail-render test and the `/dev/mail` preview.

## Round 8 — content, pricing, booking & layout polish (feature/round-8)

- **Branch state**: off `main` (which now includes the Round 5B/6 redesign).
  The Round 7 work (`feature/round-7`: feature toggles, native date pickers,
  newsletter) was still unmerged when this branched, so Round 8 does not build on
  it — e.g. the newsletter opt-in here reuses the *current main* newsletter
  capture, not Round 7's double-opt-in pipeline (see Item 5). Re-test the overlap
  once both merge.

- **Item 3 — pricing in pounds (storage decision)**: storage stays **integer
  pence** everywhere; we did NOT migrate the `*_pence` columns. Stripe charges in
  the smallest currency unit, so pence is the correct canonical form and every
  read site (Stripe `unit_amount`, Money::formatPence, balances) is unchanged —
  the lowest-risk path for the round's highest-risk item. The admin now enters and
  sees **pounds** via one shared presenter, `App\Support\MoneyField::pounds()`,
  which converts pence→pounds on load and pounds→pence (rounded) on save, only at
  the form edge. Applied to every money input (products + add-ons, course price/
  deposit overrides, booking price, voucher value, the enquiry payment-link and
  bank-transfer amounts — the last two also had their pence prefill divided to
  pounds). An end-to-end test (`PricingInPoundsTest`) enters £312.50 in the admin
  and asserts the resulting Stripe charge is exactly 31250 pence — guarding against
  the 100×/​÷100 error. Existing tests that filled these form fields were updated to
  enter pounds; tests that write models/DB directly keep using pence.

- **Item 1 — optional lead text**: section-heading and page-hero already gated
  their leads; the home newsletter/CTA subtitles and voucher intro are now wrapped
  too, and the lead/intro/subtitle fields are no longer `required` in the admin.
  Blanking a lead removes the block *and* its margin (conditional wrapper, no empty
  element), so the title sits directly on the next element.
- **Item 2 — testimonial avatars**: optional `avatar` via the existing image
  pipeline (new `testimonials` dir, 240px cap); shared `<x-site.avatar>` shows the
  photo or a navy initial badge (matching the coach-portrait fallback) on both the
  testimonials page and home pull-quotes. Two seeded testimonials carry photos.
- **Item 4 — booking dropdown location**: the jump-slot select leads with the
  dropzone so identical dates at different locations are distinguishable
  (location eager-loaded).
- **Item 5 — booking newsletter opt-in**: an unticked "Keep me posted" checkbox in
  the tandem/AFF/voucher flows, reusing the footer newsletter backend via a shared
  `OffersNewsletterOptIn` concern (no second path). Consent is active-only (never
  pre-ticked). On this branch the backend is the current-main capture; when Round 7
  merges it flows through the double-opt-in service unchanged.
- **Item 6 — instructors carousel**: a flex scroll-snap rail; `flex-1` + per-view
  min-width (50% / 25%) makes 1–4 coaches fill the row and 5+ overflow into a
  horizontal scroll, never a stack and never a stranded card. Keyboard-focusable,
  touch-draggable, no layout shift.
- **Item 7 — subscribe button**: folded into `<x-ui.button variant="primary">`;
  no one-off colour classes remain on the home page.

## Round 9 Part B — dynamic email engine: DEFERRED (owner decision)

Round 9 Part B (a dynamic, admin-managed email engine) is **deferred
indefinitely**. The system's existing hardcoded emails — booking confirmation,
jump/balance reminders, course messages, the voucher gift email and the enquiry
acknowledgement — are sufficient for launch. Admin-editable email automation may be
revisited post-launch if the client requests it. Part A (the in-panel help guide)
shipped; no email-engine model, hooks or dispatcher were built.

## Round 10 — News system replacing Facebook posts (feature/round-10)

Branched off main `bfce3ad` (Rounds 7–9 merged). Replaces the static "Facebook
posts" homepage block with an owner-managed News content type.

- **Model**: `NewsArticle` (table `news_articles`) — title, auto/editable unique
  slug (route key), optional lead (Round 8 pattern), rich `body`, optimised
  `featured_image`, `published` + `published_at`, optional `byline`, SEO fields,
  nullable `course_date_id` (nullOnDelete) linking an AFF course.
- **Scheduling + caching**: the `published` flag drives the SiteContent cache
  (`news.published`); the `published_at <= now` window is applied **live** on read,
  so drafts never show and a scheduled post appears the moment its time passes
  without waiting for a cache bust. The linked course's availability is always a
  live query (never cached) — places-left must be current.
- **Public**: `/news` (paginated, published-only, newest first, redesigned card
  grid, optional lead) and `/news/{slug}` (404 on draft/missing). Course-linked
  articles render a live navy course panel with a Book CTA into the AFF flow.
- **`news_enabled` toggle** (default ON, Round 7 pattern): gates the routes (404),
  nav, footer, sitemap and the home "Latest News" block. Added because it matches
  the established shop/payments toggles and lets the owner hide News pre-launch.
- **Facebook removal**: the posts feature is gone — `home.facebook_posts` and
  `home.facebook_caption` settings (removed via an existence-guarded settings
  migration), the HomePageSettings properties, the admin repeater and the homepage
  Facebook column. The homepage social row now pairs Instagram with a "Latest News"
  block and collapses to one balanced column when News is off/empty. The Facebook
  **profile link** (`general.facebook_url`, used by the footer/contact social
  icons) is a separate social link and was deliberately kept — it is not part of
  the "posts" feature; removing the company's Facebook presence is a separate call.

## design/social-proof — Testimonials + Hall of Fame photo-led redesign

Branched off main `835c373` (Round 10 merged in). Brings Testimonials up to the
Hall of Fame's photo-led standard and polishes Hall of Fame; nothing else changed.

- **Shared photo-tile**: extracted `<x-site.photo-tile>` (full-bleed image + bottom
  navy scrim caption, primary top-rule, hover zoom, monogram fallback) and
  `<x-site.stars>`. Both Testimonials and Hall of Fame are built from it so they are
  genuinely consistent; named in CLAUDE.md as the canonical pattern with both pages
  as reference implementations.
- **Testimonial data** (backward-compatible, nothing required): added `rating`
  (1–5, nullable) and a large `photo` action shot (separate `testimonials-photos`
  upload dir at 1280px, distinct from the 240px `avatar` headshot) via the
  optimisation pipeline. `featured` and `avatar` already existed (Round 8). Seed
  marks the featured ones, adds ratings and attaches bundled photos.
- **Testimonials layout**: the first `featured` testimonial is pulled out as a
  full-width **hero feature** (photographic scrim or navy) with an oversized brand
  display-type quote; the rest flow in a CSS-columns **masonry** of photo-tiles
  (photo-backed where a photo exists, navy monogram block where not) with varied
  aspect ratios for rhythm. Robust at 1/2/7 entries (items flow, never strand). The
  home pull-quote block gains stars for consistency.
- **Star colour**: palette only — **sky-bright** filled on dark scrims, **primary**
  on light surfaces; no gold (would breach the palette-only rule). Documented.
- **Hall of Fame hero**: the flat full-height navy band is replaced by a **compact
  photographic hero** (`page-hero` `:image` + new `compact` prop) so the photo grid
  starts high — applying the no-flat-secondary-hero spirit already in CLAUDE.md.
- **HoF interactivity**: achievements are **display-only** (no detail story in the
  data), so tiles are non-link `div`s with a decorative hover photo-zoom (reduced-
  motion honoured); no detail page was invented. Documented per the brief.
- **HoF featured tile**: deliberately **not** added — the tight hairline photo grid
  is the page's strength and the photos already carry it; a broken-rhythm tile would
  weaken it. Richer captions instead: optional nullable `achieved_on` + `note`.

## Merge + small design fixes (on main)

Round 10 (News) and design/social-proof (photo-led Testimonials + Hall of Fame)
were both green and approved, so both were merged into main (`818cacc`) and pushed
before the fixes below — the testimonials grid fix targets the social-proof masonry,
which had to be on main to address.

- **Footer logo**: dropped the duplicate logo IMAGE; the footer now shows the full
  "G-Force Skydiving" wordmark in brand display type (palette white on the ink
  band). The header logo is untouched.
- **"Stripe" → "by card" (public copy only)**: changed the public pay CTAs and
  reassurance copy — tandem/AFF pay buttons ("Pay … by card"), the tandem pay-card
  body + bullet ("Secure card checkout"), the booking/voucher reassurance lines
  ("Card payments are secure — we never see your card details") and the
  payment-success waiting line. No payment logic/routing/Stripe integration changed.
  Kept the word "Stripe" only in the **admin Help guide** (the operator knows it);
  the admin "Send payment link" action was already neutrally named.
- **Missing news icon**: the icon component had no `newspaper` glyph, so the home
  "Latest News" heading (and /news pages) rendered an empty SVG. Added the lucide
  newspaper path; the heading now matches the Instagram heading.
- **Testimonials grid**: the CSS-columns masonry stretched short/monogram cards to a
  neighbour's height and the taller 4/6 aspect left big in-tile voids. Replaced with
  a **uniform equal-aspect (4:5) grid** — every tile the same height, monogram/text
  cards at natural size, no voids; the hero feature still breaks the rhythm.
  Verified at 1/2/5/7 entries and 390/768/1440.
- **Home hero band**: investigated the reported empty/clipped band. The hero renders
  correctly at 390 and 1440 — full-bleed image, no navy/white gap — and the
  `home.hero_image` setting is populated (`/images/hero-skydive.jpg`). No layout bug
  reproduced; the earlier appearance was most likely a stale Vite build (rebuilt
  with `npm run build`). No code change; flagged here per the brief.

## Design audit pass (design/audit-pass)

Branched off main `2f0889d`. Report-first audit of every public page at 1440/390
(design-review/AUDIT.md). The site was already strong — prior rounds enforced the
structural rules — so the pass was short and real, no redesign:

- **News no-image card** (P1): the bare navy void + tiny icon read as a broken
  image; replaced with an intentional brand-blue gradient panel (dotted texture,
  newspaper icon, "G-Force News" eyebrow). Palette-only; real photos stay a content
  task.
- **News article typography** (P2): body lifted to text-lg / leading-8 with roomier
  paragraph and heading rhythm for comfortable long-form reading.
- **News card hover** (P3): the cards joined the shared photo-tile image-zoom hover
  (overflow-hidden wrapper + group-hover scale, `motion-reduce` honoured) used by
  the Services and Hall-of-Fame tiles.
- Confirmed (no change): dark mode is N/A (light-themed by design); loading/empty/
  success states already exist; a11y (focus rings, labels, aria-labels, alt) solid.
- Content artifact fixed in dev only: the home "What we do" lead held leftover test
  text ("i dont want it here") — restored to the seeded copy; flagged as an owner
  content field, not a code defect.

## Newsletter block builder (feature/newsletter-builder)

Branched off main `2491973` (the SEO branch `seo/audit-pass` was unmerged — these
branches will need merging later). Upgrades the Round 7 plain compose into a
Mailchimp-style block builder.

- **Email engine — Laravel Markdown Mail + the gforce theme**, not a hand-rolled
  inliner or MJML. Markdown mailables already produce table-based HTML and run
  Laravel's CSS-to-inline-styles step, and the gforce theme is reused. Each block is
  a self-contained, inline-styled partial (web-safe fonts, absolute image URLs, no
  flexbox/grid), wrapped by the message shell — proven and email-client-safe.
- **Block storage — a `blocks` JSON column** on NewsletterCampaign (not a child
  table): it maps 1:1 onto Filament's Builder field, keeps a campaign in one row, and
  is trivial to render. The legacy `body` column is kept (nullable) for Round 7
  campaigns.
- **Brand-locked blocks**: blocks carry content and order only — no colour/font
  fields — so every newsletter is on-brand and can't introduce email-breaking CSS.
- **Dynamic blocks** (latest news, featured course) resolve to **static HTML at send
  time**: `SendNewsletterCampaign` renders once and stores `rendered_html`, so a sent
  newsletter is frozen even if the underlying article/course changes later.
- **Idempotency**: a per-(campaign, subscriber) claim row (unique constraint) is taken
  before queueing, so a job retry or overlapping scheduler tick can never double-send.
- **Compliance unchanged**: sends target the `confirmed` scope only; every email keeps
  the signed one-click unsubscribe footer.
- **Scheduling — groundwork only**: the status enum (draft/scheduled/sent) and a
  `scheduled_at` column exist, but a scheduled dispatcher command was deferred as
  future work to keep this round focused; sending is immediate via the Send action.

## SEO audit pass (seo/audit-pass)

Branched off main `2491973`. Report-first (design-review/SEO-AUDIT.md); baseline was
already good (unique titles/descriptions, one h1, crawlable server-rendered links).

- **Meta mechanism**: centralised in the layout (not copy-pasted tags). Section
  content is echoed raw with `{!! !!}` because Blade's `startSection()` already
  HTML-escapes inline `@section` content — using `{{ }}` double-escaped titles with
  apostrophes/ampersands. Defaults are `e()`'d to match.
- **Sitemap**: absolute `<loc>` URLs (relative are invalid) + published news articles
  with `lastmod` via the cached gateway. **robots.txt** made a dynamic route so its
  `Sitemap:` line is absolute on any host.
- **og:image**: the seeded value was an external build-tool placeholder; a settings
  migration repoints it to a bundled site image and the admin field accepts a path or
  URL (dropped the `->url()` rule).
- **Structured data**: `App\Support\StructuredData` builds JSON-LD from real model/CMS
  data only. The business is typed **SportsActivityLocation** (a dropzone) with
  phone/email/areaServed (Devon + Seville); a full **postal address was NOT invented**
  — it's an owner CMS task and the type upgrades to a full LocalBusiness once added.
- **Local-SEO copy** (location in titles/descriptions) left to the owner via the CMS
  `seo_*` fields — guardrail: no hardcoded marketing copy in views.

## Admin Help guide rebuild (feature/admin-docs)
- **One page, not many**: the whole guide stays on the single `HelpGuide` Filament
  page (`/admin/help-guide`, nav group "Help", label "How it all works") with an
  on-page Contents card of jump links to per-topic anchors. A non-technical owner
  scans/searches one place; splitting 17 short topics across many nav items would
  bury them and clutter the sidebar. The page is wide enough for comfortable reading
  at desktop and tablet (`max-w-3xl`).
- **Developer-maintained, NOT CMS-editable**: the guide describes how the admin works,
  so it must change in lockstep with the code that ships features — a CMS-editable
  copy would drift and could misdescribe the panel. Content lives in code as
  `HelpGuide::sections()` (a plain array of structured sections) so a developer edits
  it in the same PR that adds the feature. No new settings/model, no migration.
- **Structured content, not prose**: each section is `{id, icon, title, intro,
  steps[], cta}` rendered as a Filament `<x-filament::section>` card (icon + heading,
  short intro, a bullet list of steps, an "Open … →" button to the live admin screen).
  This replaced the Round 9 wall-of-text and makes every topic scannable. Admin-screen
  links are built from each resource's `getUrl()` so they can never 404 — a test
  (`test_every_open_screen_link_resolves`) GETs every link and asserts success, and
  `test_the_guide_covers_every_major_area` guards coverage so a removed topic fails CI.

## Newsletter builder polish (feature/newsletter-polish)

Branched off main `a2b3547` (after the newsletter builder + admin docs + SEO merges).

- **Email shell is plain Blade, not Markdown (5a)**: the body was wrapped in
  `<x-mail::message>` (CommonMark), which mangled the pre-built block HTML —
  4-space indentation became code blocks, blank lines escaped tags — and only
  "worked" because blocks were joined with single newlines (one big HTML block).
  Replaced with `mail/newsletter/shell.blade.php`, a table-based, fully-inline
  shell that emits `{!! $body !!}` verbatim; the mailable returns
  `Content(htmlString:)`. No more CommonMark in the newsletter path.
- **Strip Livewire morph markers (5a)**: Livewire's global Blade precompiler injects
  `<!--[if BLOCK]><![endif]-->` around every `@if`/`@foreach`, including the mail
  block partials; because `SendNewsletterCampaign` freezes `rendered_html` during the
  admin's web request, those markers could bake into sent emails.
  `NewsletterRenderer::stripLivewireMarkers()` removes them from both the frozen body
  and the final shell render. A test asserts no markers / no escaped tags for every
  block type, verified against a live HTTP render where the markers are active.
- **Full-width builder (Item 1)**: the edit form is single-column so the block builder
  gets full page width, with a compact collapsible "Newsletter details" panel on top
  instead of a permanent half-width meta column.
- **Starter templates (Item 2)**: a code registry, `App\Support\NewsletterStarterTemplates`,
  picked via a create-screen select that pre-fills the builder. Chosen over storing
  templates in the DB/CMS: they're developer-curated starting points that should ship
  with the code, and they're just pre-filled blocks (same email-safe path, no second
  renderer). Adding one is a single array entry.
- **Logo block (Item 3)**: a brand-locked "Logo header" block using the existing
  `public/images/logo.png` (dark-on-transparent, reads on the white card) at 180×64
  (2× the 392×140 source), absolute URL, alt text. No new asset needed.
- **Duplicate = deep copy + reset (Item 4)**: `App\Actions\DuplicateNewsletterCampaign`
  copies blocks (JSON value → independent) and meta as a new **draft**, resetting all
  send state (no `rendered_html`, `recipient_count` 0, `sent_at`/`scheduled_at` null,
  no recipient rows). Image handling: the copy shares the original's image *paths*;
  Filament's Builder never deletes files referenced in nested JSON when a block is
  edited or removed, and replacing an image uploads a new file — so neither campaign
  can break the other's image. No file copying needed.
- **Branded footer (5b)**: a navy footer band (wordmark, tagline, social links,
  signed unsubscribe, copyright) in the established email palette; the sign-off reads
  as part of the template. Compliance unchanged (signed one-click unsubscribe).
- **Mobile preview (5c)**: the email is now responsive (viewport meta +
  `max-width:600px` media query), so the admin preview's mobile pane is a fixed 375px
  phone frame with overflow clipped — no sideways scroll. Email output unchanged.

## Inbound email threading (feature/inbound-email)

Branched off main `a2b3547`. Customer email replies thread back into the enquiry
conversation, built on Resend inbound (no second mail provider).

- **Token scheme**: each enquiry carries a 32-char `Str::random` `reply_token`
  (unguessable — not derived from the id), and replies are addressed to
  `enquiry+{token}@{MAIL_INBOUND_DOMAIN}`. Routing is by token, not the sender's
  address, so it's robust to forwarding/aliases. Inbound domain + webhook secret are
  env-config (`services.resend.inbound_domain` / `webhook_secret`), never hardcoded.
- **Webhook = sibling of Stripe**: `ResendWebhookController` verifies the Svix
  signature only (`ResendWebhookSignature`, HMAC-SHA256 over
  `{id}.{timestamp}.{body}`) and dispatches a queued `ProcessInboundEmail` job →
  `HandleInboundEmail` action. The slow **second Resend fetch** (metadata → full
  body/attachments) runs in the job, behind an `InboundEmailFetcher` interface that
  tests fake — the real `ResendInboundEmailFetcher` is the single API seam.
- **Reply parser**: `EmailReplyParser` is a line-by-line heuristic (cut at the first
  `On … wrote:` / `>` quote / Outlook divider / `-- ` signature / "Sent from my …"),
  tuned for Gmail/Apple/Outlook — NOT a full RFC parser. The full original is always
  kept in `raw_body`, so an over-aggressive cut is recoverable.
- **Idempotent + guarded**: dedupe on the Resend `email_id` (re-delivery never threads
  twice); auto-replies/bounces are detected (Auto-Submitted / Precedence / X-Autoreply
  headers, bounce senders, out-of-office subjects) and never threaded.
- **Nothing dropped**: unroutable mail (unknown/missing token, fetch failure) goes to
  an `unmatched_inbound_messages` table surfaced as a read-only "Unmatched messages"
  admin list, not silently discarded.
- **Status/triage**: a threaded reply flips the enquiry to `customer_replied`, records
  `last_customer_message_at` and re-marks it unread. Enquiries and Customers lists gain
  needs-attention indicators, snippets, last-activity, sort and "Needs reply"/"Has
  unread" filters; opening an enquiry marks it read.
- **BCC dropbox deferred**: capturing the owner's own outgoing mail via a BCC dropbox
  is documented as future work (SETUP.md) rather than half-built — it needs reliable
  match-by-customer-address and a manual-outbound message type; the inbound
  webhook/action are the foundation to add it on.

## Customer account area (feature/customer-accounts)

Branched off main `2a76611`. A customer-facing "My Account" that reuses the existing
booking/payment/enquiry/testimonial machinery — no duplicated business logic.

- **Passwordless magic-link auth, separate `customer` guard**: no customer passwords
  (a security liability and wrong fit for infrequent use). A sign-in request mints a
  single-use, 20-min, SHA-256-hashed token (`customer_login_links`) emailed as a link;
  the response is always the same neutral message (never reveals whether an email is on
  file); requests are rate-limited per email/IP and route-throttled. The `customer`
  guard is entirely separate from the Filament admin `web` guard — customers can never
  reach `/admin`. Account-area guests redirect to the customer sign-in, not admin.
- **Hard data isolation**: every account query scopes to `AccountController::customer()`
  (the authenticated customer), and route-bound records pass through `ownedBooking()` /
  `ownedEnquiry()` which 404 on someone else's id — a guessed URL never grants access.
  Tested explicitly per resource (booking, payment/receipt, message, review).
- **Pay-balance reuses the admin path exactly**: `StartBalanceCheckout` creates a
  pending Stripe Payment + `StripeCheckout::createSession`, recorded Paid and
  balance-cleared by the same `HandleCheckoutSessionCompleted` webhook. No second
  payment implementation. "Pay by card" wording (never "Stripe").
- **Reviews are moderated**: customer reviews create a Testimonial with a new `approved`
  flag, default false; existing/seeded testimonials backfilled true, and the public
  `SiteContent` queries filter to approved only (cache busted by the observer on
  approve). Only customers with a *completed* booking can review.
- **Receipt PDF**: a printable booking confirmation/receipt reuses the existing DomPDF
  approach (`pdf.booking-receipt`), scoped to the owner — cheap, so built rather than
  deferred.
- **No news in the account**: news is public and needs no account; the account links
  out to the public `/news` at most. Documented to avoid duplicating it behind auth.

## Code-style audit (chore/code-style-audit)

Branched off main `237392c`. A report-first idiomatic-Laravel audit
(`code-review/CODE-AUDIT.md`). The codebase was already clean and consistent; only two
items were actioned, no documented decision was revisited.

- **Breadcrumb JSON-LD positions were all `1`** — `StructuredData::breadcrumbs()` built
  positions with `$position++` inside an arrow function (arrow fns capture by value, so
  the increment never persisted). Fixed to derive the position from the explicit 1-based
  index; pinned by a test. Real bug in shipped structured data, not a style change.
- **Payment-receipt emails consolidated** — the duplicated `sendEmails`/`sendReceipt`
  bodies in `ConvertEnquiryToBooking` and `ConfirmHeldBooking` are now a single
  `App\Actions\SendPaymentReceipt` both delegate to (reinforces "one way to do
  everything"; the two webhook success paths can't drift). Behaviour identical, covered
  by `StripeWebhookTest`.
- **Deliberately not changed**: inline `$request->validate()` in the simple account
  controllers (idiomatic, no reuse to justify Form Requests); `CourseMessageMail`'s
  `Queueable`-without-`ShouldQueue` (documented — the per-recipient job queues it); the
  `ProtectsAgainstSpam` honeypot concern. All confirmed as correct-by-design.

## Queue reconciliation (chore/queue-reconcile)

Branched off main `21f4a04`. Investigated six queued items; fixed the three genuinely
incomplete ones (A/B/C) and confirmed the other three already done.
- **A (money-facing):** `Booking::awaitingBalance()` gates the customer balance-due
  display + pay action on status (not just `hasOutstandingBalance()`), so a completed or
  cancelled booking never shows "pay balance" — settlement on a finished jump is
  admin-side. Same balance calc, just guarded.
- **B:** `Booking::scheduledLabel()` shows a time only when a real one is set (date-only
  at midnight), used everywhere a booking date appears; factories seed daytime times.
- **C:** account bookings split into Upcoming / Awaiting a date / Awaiting payment / Past.
- **D/E/F already complete** (form widths; SEO in seo/audit-pass; help-guide rebuild in
  feature/admin-docs). E was assumed missing by the prompt but is present — no SEO run
  needed.

## SEO verification pass (seo/audit-pass, second run)

Re-ran as a verification/inventory pass (the SEO was already built in the first
seo/audit-pass round and is on main). Confirmed complete and correct across all three
phases — shared meta mechanism, dynamic news/AFF meta, full JSON-LD, sitemap/robots,
noindex on thin pages, hero preload, icons/manifest, and the breadcrumb-position fix.
One genuine gap fixed: the news auto-description fell back to `strip_tags($body)`, which
ran paragraphs together; it now prefers the article's clean `lead` field (a hand-written
`seo_description` still wins), with a de-spaced body fallback. Owner tasks (real postal
address → LocalBusiness upgrade, Search Console, Google Business Profile, canonical host)
remain owner-only and were not faked.

## Full design audit (design/audit-pass)

Branched off main `21f4a04`. Report-first browser audit across 1440/1280/1024/390 and a
short 1366×700 height (`design-review/AUDIT.md`). Outcome: **the site is in excellent
shape; no critical design changes required.** The four structural issues the owner
flagged (full-viewport heroes, the orphaned text+image photo, the footer white band, the
testimonials double-hero) were all fixed in earlier rounds and verified resolved at every
size. Phase 2/3 turned up only taste-level nuances and existing polish; per the brief I
did not invent problems or churn working code. Remaining items are owner content tasks
(real article/hall-of-fame photography), not design defects.

## Per-page FAQs (feature/page-faqs)

Branched off main `c71887d`. FAQs are PER-PAGE (tandem/aff/coached), not a standalone
FAQ page — the answers differ per page and each emits its own FAQPage schema.
- **Model:** `Faq` (page `FaqPage` enum, question, rich answer, sort_order, is_active),
  with active/forPage/ordered scopes and `plainAnswer()` (de-spaced strip_tags) for
  schema parity. Cached per page via `SiteContent::faqs()` and busted on save by
  `SiteContentObserver` (same pattern as the other content models). Filament resource
  under "Site content" with page filter, published toggle and drag-to-reorder.
- **Display:** shared `<x-site.faq-section>` accordion. Answers are ALWAYS in the DOM
  (crawlable + parity with the schema) and collapsed via a CSS `grid-rows-[0fr]→[1fr]`
  height transition — never `display:none` or removed. Real `<button>` triggers
  (native Enter/Space), `aria-expanded`/`aria-controls`, `role="region"`, visible focus,
  `motion-reduce` aware. Rendered only when the page has active FAQs (no empty section).
- **Schema:** `StructuredData::faqPage()` builds one FAQPage JSON-LD per page from the
  same FAQs, with the PLAIN-text answer to match the visible rich answer exactly. The
  component `@push`es it inside the not-empty guard, so it's absent when a page has none.
  Resolves the "FAQPage N/A" note from the SEO audit.
- Starter FAQs are general, clearly-editable placeholders — no fabricated safety/medical
  specifics; the owner refines them in the admin.

## Security audit fixes (security/fixes)

Branched off main after `security/audit-pass` merged. Implements the open items from
`security-review/SECURITY-AUDIT.md`. Everything here is **additive** — no documented
decision is weakened.

### SEC-P2.1 — Customer data export & erasure (GDPR)

Subject-access and erasure are admin actions on `CustomerResource`, one customer at a
time, not a self-service or bulk path.
- **Export** is `App\Actions\ExportCustomerData::handle(Customer): array`, streamed as a
  JSON download by the `export` row action. Strictly scoped to one customer — every join
  is by their id or their email (bookings/enquiries/messages/payments/reviews/vouchers/
  newsletter). It deliberately INCLUDES the medical `customer_details` (that is the point
  of a subject-access request) but never another customer's data.
- **Erase** is `App\Actions\EraseCustomerData::handle(Customer): void`, behind a
  `requiresConfirmation()` modal, run in a single DB transaction. The chosen model is
  **anonymise-in-place, not hard-delete**: financial/audit records (booking references,
  amounts, dates, payments) must survive for accounting, so we strip the personal/medical
  fields from them rather than deleting the rows — name → "Erased customer", email →
  `erased-<id>@erased.invalid`, phone/notes/`customer_details`/enquiry `context` nulled,
  message bodies → `[erased]`. Reviews, the newsletter subscription and login tokens ARE
  deleted (not financial). The customer row is anonymised and stamped `erased_at`
  (new nullable column, `isErased()` helper); the anonymised email means they can no
  longer request a magic link, so they're effectively locked out — intended.
- Why anonymise-in-place: hard-deleting a paid booking would break the accounts and the
  Stripe reconciliation trail. Anonymised shells keep the books correct with zero PII.
- Owner guidance lives in the Help guide ("Handling a data request (GDPR)") and SETUP.md
  ("Data protection & retention"); retention guidance is keep-anonymised-financials for
  the statutory accounting period, erase personal data when no longer needed.

### SEC-P3.1 — Security response headers + CSP

`App\Http\Middleware\SecurityHeaders`, appended to the `web` group in
`bootstrap/app.php`, so it covers the public site AND the Filament admin (both web).
- Static headers: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`,
  `Referrer-Policy: strict-origin-when-cross-origin`, a locked-down `Permissions-Policy`
  (camera/mic/geolocation/payment/usb/cohort all `()`).
- **HSTS only when `app()->isProduction()` AND the request is HTTPS** — never on local
  http, which would otherwise pin the dev domain to https with no cert.
- **CSP shipped in Report-Only mode** (`Content-Security-Policy-Report-Only`), not
  enforcing. The policy is `default-src 'self'` with `object-src 'none'`,
  `frame-ancestors 'self'`, `base-uri 'self'`; `script-src` allows `'unsafe-inline'` +
  `'unsafe-eval'` because Alpine compiles `x-*` with the Function constructor and
  Livewire/Alpine inject inline script/style; `img-src` allows `data:`/`blob:`/`https:`
  for CMS imagery; `frame-src`/`form-action` allow the Stripe Checkout hosts. All JS/CSS/
  fonts are self-hosted via Vite, so there are no other third-party origins.
- **Verified clean**: a Playwright pass at 1440 and 390 over home, the three service
  pages, contact and the admin login — plus an interactive run (Alpine mobile menu + a
  Livewire newsletter round-trip) — produced **zero CSP console violations**.
- **Enforcement call**: report-only is safe to enforce as written, but we keep it
  report-only through the first production deploy so the real Stripe redirect and any
  remote CMS image hosts can be confirmed against a live console; then flip the header
  name to `Content-Security-Policy`. Documented as an owner/ops follow-up.

### SEC-P3.3 — Sentry error tracking (privacy-first)

Installed `sentry/sentry-laravel`; `Integration::handles($exceptions)` wired in
`bootstrap/app.php`. Disabled until `SENTRY_LARAVEL_DSN` is set, so local/CI are no-ops.
- **`send_default_pii` stays false** and we add a `before_send` scrubber
  (`App\Support\SentryScrubber::scrub`) as the second layer: it removes the request body
  on every route (that's where the booking/enquiry forms carry name/DOB/weight/height/
  sex/medical notes/address) and recursively redacts any key whose name contains a
  personal/medical/secret fragment from the request, query string and our `extra`.
  Over-redaction is the intended failure mode — Sentry must never hold customer PII.
- **Why an array callable, not a closure**: `'before_send' => [SentryScrubber::class,
  'scrub']` is `var_export`-serializable, so `php artisan config:cache` (used in
  production) still works — a closure there would break it.
- **Sampling**: error `sample_rate` 1.0 (capture everything on a low-traffic site);
  `traces_sample_rate` defaults to 0.2 via env.
- **Verification**: the scrubber is covered by a unit test (request body dropped, query
  string + `extra` PII redacted, safe fields kept). End-to-end "does a real event arrive
  and is it scrubbed" is an owner step (`php artisan sentry:test` after pasting the DSN) —
  no temporary test-exception trigger is left in the app.
- SETUP.md notes Horizon + `schedule:run` as monitored must-be-running services (a
  stopped worker silently halts queued mail), recommending an uptime/heartbeat check.

### SEC-P1.1 — Webhook rate limiting

`throttle:120,1` on `/webhooks/stripe` and `/webhooks/resend`. Pure defence-in-depth
against a flood of forged/replayed calls — the per-controller signature verification
(`Webhook::constructEvent` / Svix) stays the primary gate and is unchanged. 120/min/IP
sits comfortably above real Stripe/Resend delivery and retry volume, so legitimate
bursts pass; tested that 10 normal calls are never 429 and that a 130-call flood does
trip the limiter.

### SEC-P3.2 — Production security checklist

Documentation only (the enforceable parts ship in code via SEC-P3.1/P1.1). Added a
"Production security" section to SETUP.md covering the deployment/env-dependent
hardening the app can't do for itself: HTTPS-only + redirect, `SESSION_SECURE_COOKIE=true`,
`APP_DEBUG=false`, a single canonical HTTPS host, and the owner/infra responsibilities
(TLS renewal, patching, restricting `/admin` + `/horizon`, CDN/WAF).

This completes all five open items in `security-review/SECURITY-AUDIT.md`
(SEC-P1.1, P2.1, P3.1, P3.2, P3.3); everything was additive — no prior decision weakened.

**Dependency audit at close:** `composer audit` is clean (incl. the newly added
`sentry/sentry-laravel`). `npm audit` reports one pre-existing **dev-only** advisory —
`shell-quote` pulled in transitively by `concurrently` (the `composer dev` runner). The
advisory range covers the latest published `shell-quote`, so there is no non-breaking
fix; it is never part of the production asset bundle. Left as-is rather than force a
major `concurrently` bump that could break the local dev script — flagged here for the
owner to revisit when an upstream fix ships.

## Footer newsletter signup (feature/footer-newsletter)

Branched off main `5c5b613`. Adds a compact newsletter signup to the site footer so it
appears on **every** page, reusing the existing `NewsletterSignup` Livewire component and
its double-opt-in pipeline — NOT a second signup path.
- **New `footer` variant** on the shared component (alongside `banner`/`card`): a compact
  `h-11` dark-band input + the standard `<x-ui.button>` primary, in a "Stay in the loop"
  row between the footer tagline and the link columns. Same honeypot + per-IP rate limit
  (`ProtectsAgainstSpam`) and the same "check your inbox to confirm" toast as the other
  signups. No opt-in was added to the enquiry/contact forms — those stay task-focused.
- **Toast scoping fix (required by the footer being site-wide):** the success/failure
  toast was relayed by each form's root via `@enquiry-sent.window` / `@enquiry-failed.window`.
  Because that listens on `window`, every form on a page fired a toast for any form's
  event — so adding the footer signup to pages that already carry an enquiry form (and the
  contact page, which already paired its form with a newsletter card) would have shown
  duplicate toasts. Fixed by dispatching these events with `->self()` and listening
  without `.window`, so a form only toasts its own result. Verified with Playwright: a
  footer subscribe and a contact submit each show exactly one toast, no console errors,
  at 1440 and 390. Applied uniformly across all five forms + the shared spam trait.

## Email content consistency (feature/email-content-consistency)

Branched off main `5c5b613`.

### Item 1 — "Before your jump" panel is tandem-only

`Booking::isTandem()` (`product?->type === ProductType::Tandem`) gates the account
dashboard + booking-detail prep panel. The dashboard shows the single next upcoming
booking, so the panel is tied to that booking and only renders when it's a tandem; AFF
courses and coaching (own briefings) never show it.

### Item 2 — Single source of truth for the pre-jump content

The "before your jump" info has ONE home: `JumpPrepSettings` (arrival/bring/expect), which
the account panel already reads. To surface the same content in the tandem confirmation +
reminder emails without duplicating it, `JumpPrepSettings::emailBlock()` formats those
three fields as a plain-text block, injected via a new `{{ jump_prep }}` variable on the
`booking_confirmed` and `jump_reminder` templates (placed just before the sign-off).
- **Why a token, not view-rendered:** it reuses the existing editable-template +
  `{{ placeholder }}` system (one way of doing things) rather than inventing a parallel
  mechanism. The value is resolved live from `JumpPrepSettings` at send — never stored in
  the template — so editing the CMS field updates the panel and both emails at once.
- **Tandem branching:** the senders (`BookingObserver`, `SendBookingReminders`) pass
  `jump_prep = isTandem() ? JumpPrepSettings::emailBlock() : ''`. For AFF/coaching the
  token resolves to an empty string and the block is absent. The token carries its own
  leading blank lines, so the empty case leaves no stray whitespace.

### Item 3 — Shared greeting + sign-off (consistency by construction)

The greeting (`Hi {name},`) and sign-off (`Blue skies, / The G-Force team`) were repeated
verbatim in every template body and designed mail view — and had already drifted
(account-login said "The G-Force **Skydiving** team"). Both now live ONCE in a shared mail
layout, so every transactional email opens and closes identically.
- **`<x-mail.layout :name="…">`** (`resources/views/components/mail/layout.blade.php`)
  wraps Laravel's `<x-mail::message>` and renders: the greeting (omitted when no `name`,
  e.g. the email-only newsletter confirmation), the unique body slot, then the sign-off.
- **Sign-off is a single setting** — `GeneralSettings::email_signoff` (default
  "Blue skies,\nThe G-Force team"), so the owner changes it everywhere from one place. It
  is NOT independently editable per-template (more consistent, simpler for a non-technical
  owner) — documented as the deliberate choice.
- **Editable templates carry only their unique body.** Removed the greeting prefix and
  sign-off suffix from all seven `EmailTemplate` seeder bodies; `TemplatedMail` passes the
  recipient `name` to the layout. Designed customer mails (account login, course message,
  voucher gift, newsletter confirmation) were refactored onto `<x-mail.layout>` too.
- **Excluded:** admin-facing notifications (enquiry/payment-received) and the block-based
  marketing `NewsletterCampaign` shell don't use the transactional layout; the admin's
  free-text `EnquiryReply` keeps its own wording (the admin writes the whole message).
  Verified every refactored email in `/dev/mail` at desktop + mobile widths — greeting/
  body/sign-off render once, tandem prep only on tandem emails, no console errors;
  `TemplatedMail` is now exercised by `MailRenderTest`.

## Improved privacy policy (feature/privacy-policy)

Branched off main `5c5b613`. Replaces the thin seeded privacy copy with a stronger,
UK-GDPR-aware default that names special-category (medical) data, legal bases, sharing,
under-18s, retention and data-subject rights.

- **Still owner-editable CMS content**, not hardcoded: it stays in
  `SimplePagesSettings::privacy_body` (RichEditor). The upgrade ships as a settings
  migration that **conditionally** rewrites the body — `migrator->update(…, fn($cur) =>
  $cur === $oldDefault ? $new : $cur)` — so it sets the better default on installs that
  never touched it but **never clobbers an owner edit** on re-run.
- **Dynamic variables resolve live, never stored** — `App\ViewModels\PrivacyPage` (the
  `<Thing>Page` + `viewData()` convention) substitutes `{{ business_name }}` →
  `GeneralSettings::site_name`, `{{ contact_email }}` → `GeneralSettings::email` (as a
  mailto link), `{{ last_updated }}` → the policy's last-saved date. Editing those
  settings updates the policy everywhere; the values are never baked into the text.
  Chose the existing `{{ token }}` approach (consistent with the email templates) over
  Blade-around-body because the variables sit mid-sentence; the `[Owner: …]` lines remain
  in the editable body for the owner to complete.
- **"Last updated" is auto-stamped**: spatie's settings row has no usable per-property
  `updated_at` (the repository `upsert`s only the payload), so a dedicated
  `simple_pages.privacy_updated_at` is bumped in `ManageSimplePagesSettings::save()`
  whenever the body changes.
- **NOT legal advice.** The policy is a strong starting draft only. Because the business
  processes medical (special-category) and potentially minors' data, it must be reviewed
  by a solicitor before go-live, and the two `[Owner: …]` sections (minimum age /
  guardian consent, retention period) completed with the real policy. This warning is in
  the admin Help guide ("Your privacy policy") and is an explicit owner task — the policy
  is never presented as legally complete in code or docs.

## MySQL is the production database (chore/mysql-migration)

Branched off main `3d2d7b8`. Moved the app database from SQLite to **MySQL 8+** for
production and local parity. Rationale: SQLite is a single file on local disk — on
ephemeral/containerised hosts it can vanish on redeploy — and it takes a database-wide
write lock; MySQL/InnoDB persists independently of the app host, does row-level locking
(matters once webhooks + the queue worker + admins write concurrently), and brings a
mature backup/restore + replica ecosystem.

- **Local + prod on MySQL; SQLite kept only as the fast test driver.** `.env` defaults to
  `mysql`; `phpunit.xml` stays SQLite `:memory:` for quick `composer check`/CI, and a new
  `phpunit.mysql.xml` runs the full suite against a real MySQL DB as the production-parity
  gate. Both are green (341 tests).
- **One SQLite-only bug surfaced and fixed:** a migration added a column
  `->after('tandem_date_id')` before that column existed; SQLite ignores `->after()`,
  MySQL enforces it. Dropped the cosmetic `->after()` (portable on both). No other code or
  test needed changing — the existing conventions (JSON via array casts with no JSON-path
  queries, string-backed enums, integer-pence money, Carbon date casts) are all
  driver-agnostic, which is why the suite went green on MySQL on the first run.
- **Backup/restore is documented AND tested** (SETUP.md): `mysqldump --single-transaction
  --set-gtid-purged=OFF` -> restore into a fresh DB -> row counts + JSON validity verified.
  The `--set-gtid-purged=OFF` flag is mandatory or the restore aborts. Chose mysqldump+cron
  over `spatie/laravel-backup` to avoid a new dependency; managed-DB PITR preferred where
  available. Full risk inventory + step log in `db-migration/MYSQL-NOTES.md`.

## Functional QA sweep (qa/functional-sweep)

Branched off main `87791a1`. Walked every user flow on the running local site (MySQL,
Redis/Horizon, Stripe keys empty → voucher/non-Stripe paths driven end-to-end, mail → log
+ `/dev/mail`). Report: `qa/QA-REPORT.md`. The site was already in strong shape (one real
defect; everything else pass or owner-manual — consistent with the 341-test suite and
prior audits).

- **Defect fixed — emails fail under a stale settings cache.** The shared `<x-mail.layout>`
  read `GeneralSettings::email_signoff` as a raw typed property; a Redis settings cache
  predating that property (e.g. a deploy that forgot `settings:clear-cache`) makes the
  property uninitialised, throwing "must not be accessed before initialization" and
  **silently failing every queued transactional email** (booking confirmations, magic-link
  logins, …), while the public site stays up (only the mail layout reads it). Fixed by
  reading through `GeneralSettings::emailSignoff()`, which falls back to the default
  sign-off when the property is uninitialised/blank — emails degrade gracefully instead of
  going dark. Pinned with a test using `unset()` to reproduce the uninitialised case. The
  documented `settings:clear-cache` deploy step remains the primary mitigation.
- **Verified passing:** full public crawl (0 broken links/images/console errors), tandem
  booking via full voucher redemption (+ double-redeem refused), AFF booking to the deposit
  step, contact/enquiry validation + creation, newsletter double-opt-in, the customer
  account area incl. **IDOR denial** (another customer's booking → 404), balance/paid-in-full
  display, all 14 `/dev/mail` templates (pre-jump block tandem-only, sign-off correct),
  admin resource lists/calendar/create-forms + export/erase, and feature toggles.
- **Owner-manual (cannot verify locally):** real Stripe Checkout → webhook → booking +
  abandoned-checkout release, real emails in Gmail/Outlook, real inbound-email round-trip,
  signed newsletter/magic-link clicks from a real inbox.

## UI level-up 1/4 — type scale + spacing foundations (ui/01-foundations)

Branched off main `4099068`. First of a 4-pass UI craft level-up: establish intentional
type-scale + spacing tokens so the site stops feeling AI-generated/templated, WITHOUT a
rebrand (navy/blue/white, sharp corners, photo-led, Bebas display all kept).

- **Type is now a named, fluid scale** (`--text-display/h1/h2/h3/lead/body` in `app.css`
  `@theme`), using `clamp()` so headings stay bold on desktop and don't overflow mobile —
  replacing per-breakpoint class soup (`text-5xl md:text-7xl …`) and magic one-offs
  (`text-[10.5rem]`). Applied via the shared components (`section`, `section-heading`,
  `page-hero`) + the home hero, so it propagates site-wide. Base body is now 16px / **1.65**
  line-height (was 1.5) — the biggest readability win.
- **Spacing rhythm tokenised** — `py-section`/`py-section-sm` (96/64px) on the shared
  section + hero components; `max-w-measure` (68ch) for reading columns. Kept the existing
  4px grid; just formalised which steps similar sections use.
- **Font loading:** dropped unused Barlow 800 (0 `font-extrabold` usages) — one fewer file;
  loading was already self-hosted/subset/swap.
- **Why keep heading sizes ≈ current** (not shrink dramatically): the brief says refine,
  not regress the brand look — so display maxes near the old sizes (hero 10.5rem→8.5rem is
  the one deliberate refinement of an oversized value). Verified at 1440/1280/1024/390 +
  short-height across 5 pages, no regressions. Later passes (buttons, page polish) migrate
  the remaining ad-hoc values to these tokens. Full detail in `ui-review/FOUNDATIONS.md`.

## UI level-up 2/4 — buttons, inputs & links polish (ui/02-components)

Branched off main `f868ac3`. Pass 2 polishes the interactive layer to the same intentional
standard as pass 1, staying inside the brand (navy/blue/white, sharp corners, no new
colours). The components were already a decent shared system — this tightens craft + states.

- **Buttons:** added `active:` press states + a `motion-reduce` transition guard to
  `<x-ui.button>`; folded the last hand-styled button (header mobile "Book Now") into the
  component (`w-full`). Zero one-off buttons remain in public views. Documented the icon
  lead/trail + icon-only `aria-label` convention.
- **Inputs:** unified ONE focus convention — `focus-visible:ring-2 ring-ring` + brand-blue
  border across input/textarea/select (was a `ring-1` / `focus:`-vs-`focus-visible:` mix);
  clearer, accessible keyboard focus that matches the buttons. Kept the documented toast
  error pattern for simple forms + the inline `<x-booking.field role="alert">` for booking
  forms (not converting toasts→inline — that's a prior decision, not a defect).
- **Links:** new `<x-ui.arrow-link>` for the "All news → / Read more →" navigational arrow
  pattern (consistent hover-slide + focus-visible + reduced-motion); migrated the standalone
  + in-card usages. The home "Explore" border-b cue is deferred to pass 3 (home polish).
- No new tokens needed. Verified at 1440/1280/1024/390 + short-height; `composer check`
  green (344). Detail in `ui-review/COMPONENTS.md`; before/after in `ui-review/c-*`.

## UI level-up 3/4 — page layout, hierarchy & homepage (ui/03-pages)

Branched off main `041e272`. Pass 3 works at the page level — applying passes 1–2's tokens
and components to section composition, hierarchy and rhythm; the pass allowed the most
visible structural change. Brand unchanged (navy/blue/white, sharp corners, photo-led).

- **Rhythm is now a 2-step system.** Added `--spacing-section-lg` (128px) for dramatic
  dark/photo feature bands; standard sections stay `py-section`. Replaced the drifting
  `py-20/28/32/40` hand-rolled paddings across home/tandem/aff/coached/testimonials.
- **Section headings migrated to the type scale** (the ones that bypassed it → `text-h2`,
  or `text-h1` for the home CTA close), leads → `text-lead`/`max-w-measure`, news card
  titles → `text-h3`; eyebrows standardised on `tracking-[0.25em]`.
- **Homepage News+social rebuilt (the headline change).** The old block put a manual
  gallery *mislabelled as a live Instagram feed* in the dominant left, squeezing real News
  into the right. Now **Latest News leads the dominant 2/3 column** and a compact navy
  **"Follow us"** card (real CMS social links, hidden when empty; a clearly-labelled curated
  "From the dropzone" photo grid — not a feed) sits in the right 1/3. The
  "connects via Meta Graph API — ask to enable" note is **gone** (closes the
  COMPLETENESS-CHECK item). Stacks News-first on mobile.
- One new token; no ad-hoc values added. Verified 1440/1280/1024/390 + short-height;
  `composer check` green (344). Detail in `ui-review/PAGES.md`; before/after in
  `ui-review/p-*`. Pass 4 distils all this into `ui-guidelines.md`.

## Branded date picker — desktop custom / mobile native (feature/date-picker)

Branched off main `d100559`. Native `<input type="date">` only opened on the icon and
rendered in the browser's own (per-browser) style — out of place. Replaced with a shared
`<x-ui.date-field>`.

**Date-field inventory (all public; none in the account area), all bind `YYYY-MM-DD`:**
- `TandemEnquiryForm` (`/tandem`): `date` (preferred date, `after_or_equal:today` → min
  today) and `dob` (`before:today` → max yesterday).
- `BookTandem` / `BookAff` (`/book/*` step 2): `date_of_birth` (`before:-18 years` → max =
  18 years ago, so the picker also opens ~18 years back — no endless "prev").

**Approach (value-safe):** the native `<input type="date">` stays the **single source of
truth** — same `wire:model`, same submitted `YYYY-MM-DD`, same validation/`required`/
min-max. On a **fine-pointer ≥1024px desktop** an Alpine calendar (`dateField` in app.js)
overlays it: click anywhere opens a branded popover (navy/blue/white, sharp corners, pass
1–2 tokens + focus rings), month **and year** `<select>`s for quick jumps, full keyboard
(arrows/Enter/Esc/PageUp-Down), outside-click/Esc to close; selecting a day writes the ISO
value back to the native input and fires `input`/`change` so Livewire syncs. On
touch/small screens the native OS picker is used (best touch UX, accessible for free).
Because the carrier is unchanged, the booking/enquiry suites (which set the wire property
directly) pass untouched; `DateFieldTest` pins that the native carrier + min/max stay wired.
Verified at 1440/1280/1024/390 — desktop popover + year-jump + selection (`1995-05-15`),
mobile native, no console errors.

## UI level-up 4/4 — guidelines doc (ui/04-guidelines)

Branched off main `7349671`. Docs-only final pass: distilled the design system that passes
1–3 + the date-picker built into one project reference, `ui-guidelines.md` (type/spacing
tokens with real values, the palette, the `x-ui`/`x-site` component catalogue, the
conventions — eyebrows, focus rings, native-control handling, CMS/empty-state, motion — and
an "adding a new page" checklist). CLAUDE.md points at it as the canonical system (the
`frontend-design` skill is the general craft; this is our specifics). No code/style/token
changes. Captured the known gaps for later (the h3→h2 type-scale mid gap; the home "Explore"
cue not yet on `arrow-link`; a few deliberate one-off `tracking-*`).

## Instructors: disciplines + Meet the Team page + homepage teaser (CHECKPOINT — proposal, awaiting approval)
Branch `feature/instructors-disciplines` off main (start `179d8fb`). Proposal only; no code yet.

### 1. Current state (inspected)
- **Model/storage**: `instructors` table = `name, role, bio (text), photo (nullable), sort_order`.
  Read through the cached `SiteContent::instructors()` gateway (caches raw attribute arrays then
  `Instructor::hydrate()` — Eloquent objects are never cached). Filament `InstructorResource`
  ("Site content" group) with `InstructorForm` (name/role/bio/photo upload).
- **Homepage roster**: `pages/home.blade.php` TEAM section is a horizontal scroll-snap rail
  (`flex snap-x overflow-x-auto`, 2-up mobile / 4-up desktop) showing the FULL roster with photo,
  name plate, role and the FULL bio per card. This is the scroll we're replacing.
- **"Why Us" is a DROPDOWN, not a page**: `$whyUs` array in `components/site/header.blade.php`
  = Testimonials + Hall of Fame, rendered as a desktop hover/click dropdown and a mobile
  expandable group. Those pages: `routes/web.php` → `PageController::testimonials()/hallOfFame()`,
  views `pages/testimonials.blade.php` + `pages/hall-of-fame.blade.php`, data via `SiteContent`.
  Meet the Team slots in as a THIRD `$whyUs` entry the same way (no new top-level nav, no Why-Us
  landing page).

### 2. Proposed data model — disciplines as a first-class lookup + pivot
Mirrors how `Location` is already first-class; idiomatic here, and keeps "one way of doing things".
- New `disciplines` table: `id, name, slug (unique), sort_order, timestamps`. Seed three to match
  the existing `ProductType` enum: **Tandem / AFF / Coaching** (slugs `tandem`/`aff`/`coaching`).
- New `discipline_instructor` pivot (composite-unique `instructor_id, discipline_id`).
- `Instructor::disciplines(): BelongsToMany` + `Discipline::instructors(): BelongsToMany`.
  One instructor carries MANY disciplines, rendered ONCE with all its tag chips — no duplication,
  no per-discipline buckets.
- Discipline-page query (no dupes): `Instructor::whereHas('disciplines', fn ($q) => $q->where('slug', $slug))->ordered()`.
- **CMS**: add a `CheckboxList::make('disciplines')->relationship('disciplines','name')` to
  `InstructorForm` (multi-select, mass-assignment safe via the relationship, not `$fillable`).
  Plus a small **Disciplines** Filament resource under "Site content" so the owner can rename/add
  disciplines (matches Locations being editable). Pivot is sync'd by Filament's relationship field.
- **Cache interaction**: `SiteContent::instructors()` caches plain attribute arrays, so the pivot
  won't ride along automatically. Plan: enrich the cached payload to carry a small
  `disciplines: [{name, slug}, …]` array per instructor and re-attach it as a relation on the
  hydrated model (stays within the gateway's "cache plain arrays, rehydrate on read" rule — no
  cached objects). Add `Discipline::class` (and a pivot-touch) to `SiteContent::KEYS_BY_MODEL` /
  the observer so editing disciplines or the assignment busts the `instructors` key.

### 3. Proposed design (follows the structure; existing tokens/components only)
- **Homepage teaser** (replaces the scroll): a COMPACT band — section heading + one tidy,
  wrapping row of small circular avatars (photo or monogram fallback) + a single trust line, and
  an `x-ui.arrow-link href="/meet-the-team"` "Meet the team →". No desktop carousel, no per-person
  bios. One short band vs the tall card rail → far less vertical space; on mobile the avatars wrap,
  they don't scroll. (RECOMMENDED — see question below for alternatives.)
- **Meet the Team page** (`/meet-the-team`, `PageController::meetTheTeam()`,
  `pages/meet-the-team.blade.php`, data `SiteContent::instructors()`): compact navy-gradient
  `<x-site.page-hero>` (secondary page → no photo hero, per design rules), then a STATIC
  responsive grid of full instructor cards (1-col / 2-col md / 3-col lg) — all visible, no desktop
  scroll, cards stack on mobile (no swipe needed). Each card = photo (aspect-[4/5]) or monogram
  fallback, name plate, role, **discipline tag chips** (palette only — e.g. `border-current`/
  sky-bright on the navy plate), and the LONGER bio below. Added to the Why Us dropdown after
  Hall of Fame, and to the sitemap (priority 0.6, like Testimonials).
- **Discipline pages**: optionally surface "instructors who teach this" on `/tandem`, `/aff`,
  `/coached` as a small tag-filtered avatar strip linking to Meet the Team (no duplicated records).
  See question below — default is team-page-only unless you want the strips.

### 4. Bios (fit-to-slot)
Bio field is already `text` → supports long copy. Long bios live on the **Meet the Team cards**;
the homepage teaser shows NONE (avatars + names only). The content-seed prompt
(`chore/seed-real-content`, currently unmerged) holds the actual bio text — this branch only
guarantees the field + the room for it. **Cross-branch note**: both branches touch
`InstructorSeeder`; whichever merges second reconciles (disciplines assignment seeding will be
additive here so it composes cleanly).
