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

## Seed real content + logo (chore/seed-real-content)

Branched off main `179d8fb`. Seeded the owner's real facts into the existing models/seeders
(no schema/checkout-logic change). **Hard exclusions honoured:** no bank details, internal
back-office workflow, per-student checklists, safety-critical AFF training material, or
private/informal pricing notes were put anywhere in the repo.

### Logo
Processed the owner's file (`storage/app/brand/G-Force Logo Blue & Grey JPEG.jpg`,
white-bg, padded) → cropped, white→transparent 520px PNG + WebP for the header (via
`<picture>`), square app icons (apple-touch 180, manifest 192/512) and a real
`favicon.ico` (was 0 bytes). **Footer keeps its text wordmark** — the blue/grey logo
doesn't read on the navy band; **owner task: a reversed/white logo for dark surfaces.**
(Local Herd 404s `/favicon.ico`; the file is valid on disk and production nginx serves it,
plus a PNG favicon link covers browsers.)

### What changed per field (KEPT / ENRICHED / REPLACED)
- **Homepage About body — ENRICHED (fact fix):** "founded by ex-military jumpers" was
  inaccurate (the founders are mixed-background) → "founded in 2017 by friends with a
  shared passion…"; length/tone kept. Title "Established 2017. Built on experience." KEPT.
- **Instructor bios — ENRICHED + surnames:** Joby → **Joby Chadd** (military 2004; BS &
  USPA rated; signs off A Licence); Lucy → **Lucy Davies** (joined in Portugal 2018, AFF
  in 5 days). Ricky KEPT (no new detail supplied). Short, card-length; **no phone numbers.**
- **Tandem + AFF page copy / product pricing — KEPT:** already accurate — tandem £260,
  camera £140/£100, P6 £24.73, rebooking £50, weight surcharges, AFF £1,750, consolidation
  £600, the "15,000ft / highest tandem in the UK" hook, the AFF how-it-works explainer, and
  the charity-tandem line. Per the fit-to-slot rule these well-fitted slots were left alone.
- **FAQs (tandem + AFF) — ENRICHED/REPLACED placeholders with real facts:** weight
  surcharges, camera prices, the charity option, AFF 8-levels+10-consolidation→A Licence,
  A-Licence recognition, **BS membership not included / seasonal / provisional for ground
  school+L1**, kit list, and the trips/travel logistics (general, not personal specifics).
- **Social URLs — REPLACED generic placeholders** with the real Instagram
  (`/gforceskydiving/`) + Facebook (`/Gforceskydiving.co.uk/`). Email/phone already correct
  (`info@gforceskydiving.co.uk`, `+44 (0)7583 155 951`). **No staff mobile numbers anywhere.**
- **Locations (Devon, Swansea, Hinton, Seville/Spain) + AFF course dates — KEPT:** locations
  already seeded; course dates are dynamic future demos. The provided "Spain 8–12 June" is
  past/ambiguous → **owner enters real upcoming course dates in admin** (CourseDates).

### ⚠️ Testimonials — NOT seeded (owner content)
14 **real reviewer names** were provided (Leigh Bulmer, Anais Housley, Eddie Wilkins, Cam
Jones, Jordan Cooksley, Anthony 'Taff' Rabey, Matt Oakley, Lewis Cashel, Damian Muzsal,
Chris Fowler, James Martin, Susie Hay, Kevin T Hannam Bowen, Wayne Barnes) but the **actual
quotes were not in my materials** (the "prior version of this prompt" / legacy testimonials
page wasn't accessible, and the source React app isn't present). I **did not fabricate
quotes attributed to real named people.** The generic placeholder testimonials remain until
the owner supplies the real 2018–19 quotes — and should confirm they're happy to keep
displaying reviews that old.

### [VERIFY] — owner to confirm before publishing
- Tandem: the **"highest tandem in the UK at 15,000ft"** claim; exact **weight & minimum-age
  limits**; **wind limit** (~20kt).
- AFF: **upper age limit** (~55); **repeat-level / extra-jump pricing**; **packing fee**
  (~£5/jump?); **BS membership cost** (~£125/yr sliding scale); that **consolidation jumps**
  are sold as described (£600 / 10 jumps); real **course dates & year**.

### Owner image uploads (still placeholders)
Testimonial photos; **instructor photos** (bundled stock with mismatched filenames); hero /
gallery / about / Hall-of-Fame photos; and the **reversed/white logo** for the dark footer.

### Display-vs-checkout LOGIC questions (NOT wired in this pass — feature decisions)
All prices above are seeded as **displayed content only**. The owner should decide whether
the booking flow should *charge*:
- **Camera add-ons** (£140/£100) — already modelled as purchasable add-ons; confirm the
  checkout actually charges them.
- **Weight surcharges** (£20/£40/£60) — currently display-only; should checkout add them
  based on the entered weight?
- **Consolidation jumps** (£600) — a separate product; should it be a paid step after AFF?
- **P6 insurance** (£24.73) — paid on the day direct to British Skydiving; correctly NOT a
  site charge.

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

### APPROVED (owner, 2026-06-15)
1. **Homepage teaser** = compact avatar row + one trust line + "Meet the team →" (no bios, no carousel).
2. **Discipline strips** = YES — add a tag-filtered avatar strip ("Meet your AFF instructors →") to
   `/tandem`, `/aff`, `/coached`, linking to Meet the Team (queried by tag, no duplication).
3. **Disciplines CMS** = CheckboxList on the instructor form PLUS a small Disciplines resource
   under "Site content" (owner can rename/add). Three seeded: Tandem / AFF / Coaching.
Building now in logical commits: model+migration+seed → CMS → cache gateway → Meet the Team page +
nav → homepage teaser → discipline strips → visuals.

### BUILT (feature/instructors-disciplines)
Shipped in logical commits, `composer check` green before each (361 tests):
- **Data model**: `disciplines` lookup + `discipline_instructor` pivot;
  `Instructor::disciplines()` many-to-many. `SiteContent::instructors()` carries
  disciplines in the cached plain-array payload (no cached objects) and rehydrates
  them as a relation; `instructorsForDiscipline(slug)` filters the cached set.
  `DisciplineSeeder` seeds Tandem/AFF/Coaching and assigns them (matched on first
  name, so it survives the content-seed branch's surname additions).
- **CMS**: CheckboxList on the instructor form + a Disciplines resource ("Site
  content"). HelpGuide gains a "Your team & their disciplines" section.
- **Meet the Team page** (`/meet-the-team`, third in the Why Us dropdown): static
  responsive grid, all instructors visible (no desktop scroll, stacks on mobile),
  discipline tag chips (`<x-site.discipline-tags>`, palette only), longer bios.
  Settings copy under "Other pages → Meet the Team"; in sitemap (0.6).
- **Homepage teaser** REPLACED the scroll rail: a compact band of overlapping
  avatars + an editable trust line + "Meet the team →". No desktop carousel; far
  less vertical space; bios moved to the team page. Hidden when no instructors.
- **Course-page strips**: "Meet your <discipline> team" on Tandem/AFF/Coached,
  tag-filtered (each instructor once), above the FAQs; hidden when none tagged.

**KEPT vs REPLACED**: the homepage team *section heading* (eyebrow/title/lead from
`HomePageSettings`) was KEPT; only the scroll *rail* beneath it was REPLACED by the
teaser. Instructor name/role/bio fields KEPT as-is (bio field already `text` — room
for the longer Meet-the-Team bios; the actual long bio TEXT comes from the
content-seed branch). The editorial card aesthetic (navy plate, primary top-rule,
monogram fallback) was reused so the team page reads as a sibling of Testimonials /
Hall of Fame.

**Cross-branch**: both this and `chore/seed-real-content` touch `InstructorSeeder`
(this branch doesn't — only `DisciplineSeeder`, which is additive), so they compose;
whichever merges second is a clean fast-forward of the other's instructor changes.

## Team display design fixes (ui/team-design-fixes) — DESIGN CHECKPOINT (proposal, awaiting approval)
Branch off main `3e188de`. Presentation only — the disciplines DATA model is unchanged.
Current state (screenshots in `ui-review/team-fixes/00-*`):
- **Home teaser** says "meet the team" twice (eyebrow MEET THE TEAM + a big "THE COACHES"
  title + lead), then a heavy bordered box with 3 NAMELESS avatars + a trust line + a
  "MEET THE TEAM →" link. Heavy and redundant.
- **Tandem/AFF strip** is a near-identical heavy box ("MEET YOUR TANDEM TEAM" + nameless
  avatars + a comma-joined name list + link).
- **Team page tags** render as bare outlined boxes "TANDEM AFF COACHING" — ambiguous (could
  read as sizes/filters); card body padding/rhythm looks off (tags butt the top, uneven bottoms).

### A. Homepage team mention (remove the heavy box + duplicated heading)
- **A1 (recommended): weave one elegant line into the existing About band.** Delete the whole
  standalone teaser section. At the end of the navy About story add a single understated
  sentence with an inline `arrow-link` — e.g. *"The people behind it — meet the instructors
  who'll fly with you →"*. No box, no big heading, no avatars on the homepage (which also
  removes the nameless-avatar problem here entirely). Most subtle; contextually correct (the
  story → the people). The link text comes from the existing `home_team_teaser_line` setting.
- **A2: slim named-avatar row, no box.** Keep a team mention where the teaser sits but strip
  the border/heading: a single row of 3–4 SMALL avatars each WITH a name beneath, a short
  inline lead, and a "Meet the team →" link. Faces stay (named), but lighter than today.
- *Reasoning:* A1 is the cleaner answer to "subtle, woven, not its own section". A2 keeps
  faces on the homepage if you value that for trust.

### B. Avatars (resolve the nameless-thumbnail problem everywhere)
Rule: avatars only ever appear **WITH names**. Home = no avatars (A1) or named row (A2);
Tandem/AFF = named (section C); Team page keeps full portrait cards. No nameless rows anywhere.

### C. Tandem/AFF compact element (the opposite of the heavy home block)
A small, NAMED, discipline-filtered element above the FAQs (same slot as today), via a new
shared `<x-site.instructor-chip>` partial (avatar + name + role) reused across pages — one
source of truth, no per-page duplication. Queries the existing `instructorsForDiscipline(slug)`
(tandem→tandem, aff→aff); a multi-discipline instructor still shows once.
- **C1 (recommended): a row of small "person" chips** — small avatar with the name + one-line
  role beneath each, 2–4 across, under a quiet small label ("Your tandem instructors", text-xs
  uppercase — NOT a display heading), with a trailing "Meet the team →" link. Compact, named,
  scannable.
- **C2: inline horizontal chips** — avatar + name + role on one line each, stacked in a tidy
  list. Even more compact; better when there are many instructors.

### D. Team-page discipline tags + card padding
- **D1 (recommended): label the tags "Teaches".** Prefix the chips with a small muted
  "TEACHES" label (text-xs uppercase tracking) so meaning is unmistakable, keep the chips.
- **D2: drop the boxes for a labelled text line** — *"Teaches — Tandem · AFF · Coaching"*
  with the disciplines in brand primary. Lightest, very clear, no boxy ambiguity.
- **Padding fix (both):** standardise the card body to a consistent pad + rhythm — the
  "Teaches" line, a hairline `border-border` divider, then the bio, with even top/bottom
  spacing so cards with short and long bios read consistently. Reused monogram empty state kept.

Awaiting owner choice on A, C, D (B follows). Build only after approval.

### APPROVED (owner, 2026-06-15)
- **A = A1**: remove the standalone homepage teaser; weave ONE elegant line + inline
  "meet the team →" into the navy About band. No avatars on the homepage.
- **C = C1**: Tandem/AFF get a compact row of named person chips (shared
  `<x-site.instructor-chip>`: avatar + name + role) under a quiet small label, filtered by
  discipline, with a "Meet the team →" link. No heavy box.
- **D = D1**: team-page tags get a small "TEACHES" label; card body padding/rhythm
  standardised (Teaches line → hairline divider → bio, even spacing).
Note: the home `team_eyebrow/title/lead` settings become unused on the homepage but are KEPT
(no data change). Building now in steps: chip partial + course element → homepage About line →
team-page tags + padding.

### BUILT (ui/team-design-fixes)
Shipped in 3 commits, `composer check` green before each (360 tests); verified across
1440/1280/1024/390 + short height (`ui-review/team-fixes/*`).
- **A1 — homepage**: REPLACED the standalone teaser (bordered box + duplicated "MEET THE
  TEAM" heading + nameless avatars) with a single woven mention in the navy About band —
  the trust line (`home_team_teaser_line`) + one sky-bright "Meet the instructors who'll fly
  with you →" cue. No box, no avatars on the homepage. KEPT the About story and stats intact.
- **C1 — Tandem/AFF**: REPLACED the heavy "Meet your <discipline> team" box with a compact
  NAMED row — a quiet "Your <discipline> instructors" label + a new shared
  `<x-site.instructor-chip>` (avatar + name + role) per tagged instructor + "Meet the team →".
  Still `instructorsForDiscipline(slug)` (each once). Verified: tandem→Joby+Lucy, aff→Joby+Ricky.
- **D1 — team page**: discipline chips now sit under a muted "TEACHES" label, divided from the
  bio by a hairline rule; card body padding standardised (`p-6 lg:p-7`) with even rhythm and
  intact no-photo / no-bio empty states.
- **Avatars (B)**: no nameless avatar rows remain anywhere — home has none, course pages name
  them, the team page keeps full portraits.
- KEPT (unused on the homepage now, but no data change): the `team_eyebrow/title/lead`
  HomePageSettings. Disciplines DATA model untouched (presentation-only change).

## Recolour logo blue → brand navy (chore/logo-recolour)
Branch off main `3e188de`. Goal: the logo's blue "G-FORCE" (and swoosh) should match the
app's canonical navy exactly.
- **Navy used: `#00226b`** = the `--secondary` token (`oklch(0.28 0.14 255)`, "Secondary =
  deep navy"), which is the navy the headings / nav / brand use. Converted oklch→sRGB precisely
  (OKLab matrices, not eyeballed); the logo's recoloured core measures exactly `#00226b`.
- **Format found: RASTER** — `public/images/logo.png` (520×197 RGBA) + a derived
  `logo.webp`; the apple-touch / 192 / 512 / favicon-32 icons + `favicon.ico` embed the same
  logo on white. (No SVG source exists in the repo.)
- **Method**: luminance-preserving recolour — the blue pixels (blue-dominant, `b−r ≥ 10`) are
  remapped along core-blue→navy and light-edge→white, so anti-aliasing stays clean on BOTH the
  transparent logo and the white-background icons; the grey "SKYDIVING", transparency and edges
  are untouched. Regenerated `logo.webp` (cwebp) and repacked `favicon.ico` from the recoloured
  32px PNG. Footer uses a white TEXT wordmark (no image) — nothing to recolour there.
- **⚠️ Raster recolour is a WORKAROUND, not as clean as a vector edit.** IDEAL fix (owner task):
  get the original **SVG/vector** logo from the designer and change the blue fill to `#00226b`
  there — crisp at every size, and the icons can be re-exported from it. Flagging so the raster
  result isn't mistaken for the proper source-of-truth fix.

## Logo: make "SKYDIVING" readable (chore/logo-skydiving-fix)
Branch off main `4798387`.
- **Format: RASTER** (`public/images/logo.png` RGBA + `logo.webp`; icons embed the logo). No SVG.
- **Diagnosis**: "SKYDIVING" is a **hollow double-contour OUTLINE** with transparent interiors
  in a pale neutral grey (~`#b6b6b7`), which is why it's hard to read on the white header.
- **Tried for a true SOLID fill (the ideal)** — both failed cleanliness:
  1. *Even-odd ray-casting* → venetian-blind streak artefacts (italic strokes flip horizontal
     ray parity row-to-row).
  2. *Connectivity region-fill* (level regions by stroke-crossings, fill odd levels) → clean for
     most letters and correctly preserved the **D** counter, BUT the leading **"S"** body
     connects to the exterior past the adjacent navy swoosh and wouldn't fill — an inconsistent,
     un-shippable result. Confirms a hollow raster outline can't be reliably solid-filled.
- **Shipped (safe improvement)**: darkened the SKYDIVING outline to **`#495766`** =
  `muted-foreground` (the token for secondary text), **7.40:1 on white (WCAG AA pass)**.
  Luminance-preserving recolour keeps the AA edges crisp; navy "G-FORCE" untouched; two-tone
  identity intact and SKYDIVING stays visibly secondary. Regenerated `logo.webp` + the
  apple-touch/192/512 icons (favicon-32 too small to carry the word). Footer uses a white TEXT
  wordmark (no image) — nothing to change there.
- **⚠️ Still needs the VECTOR/SVG source for a true SOLID two-tone (owner task).** The darkened
  outline is a legibility improvement, not the requested solid fill — filling hollow letters in
  raster can't be done cleanly (see the "S" failure). With the designer's SVG, set the
  "SKYDIVING" glyphs to a solid `#495766` fill (and re-export the icons) — crisp at every size.

## Homepage: remove duplicate trust stats + prominent team link (ui/homepage-dedupe) — CHECKPOINT
Branch off main `4798387`. Presentation only. Screenshots: `ui-review/homepage-dedupe/00-*`.
Current state confirms the duplication:
- **Trust band** (`<x-site.trust-grid>`, `bg-secondary`): "WHY JUMP WITH US / TRUSTED. CERTIFIED.
  EXPERIENCED." + 4 tiles — EX-MILITARY / **30+ YEARS** / **BS·USPA** / EST. 2017. → KEEP.
- **About band** ("OUR STORY / ESTABLISHED 2017. BUILT ON EXPERIENCE."): prose + a faint trust
  line + the faint "MEET THE INSTRUCTORS…" link + a 3-tile grid `about_stats` — 15K FT /
  **30+ YRS** / **BS·USPA** (right column = two tall photos). → the stat tiles DUPLICATE the band.

### A. About section after removing the stat tiles (keep it balanced, no gap)
- Remove the `about_stats` 3-tile grid. Also drop the **faint trust line** ("Your jumps are run by
  British Skydiving and USPA-rated instructors") — it repeats BS/USPA, which is already in the band
  AND in the About body ("holds both British Skydiving and USPA certifications"). Triple redundancy.
- Left column becomes: eyebrow → title → body → **one prominent team element** (below). The band
  grid stays `items-center`, so the (now shorter) text column centres against the two-photo column
  — balanced symmetric whitespace, not a gap. A short one-line lead above the element anchors the
  bottom of the column. Photos unchanged. Net: prose + photos + a strong CTA = complete, calmer.

### B. The prominent team element (2 options)
- **B-i (recommended): a bold `<x-ui.button href="/meet-the-team">Meet the Team</x-ui.button>`**
  with a short lead line above ("The people you'll jump with."). Prominent, on-brand (matches the
  hero CTAs), routes clearly, zero new components/CSS. Clean and unmistakable.
- **B-ii: a small named team teaser** — a row of 3–4 instructor avatars WITH names (reusing
  `<x-site.instructor-chip>`, queried from the team, no duplication) + the bold button beneath.
  More human/visual, but heavier and re-introduces faces on the homepage (the earlier task made it
  deliberately face-free). Use if you want faces back, prominently and named.
- Button style on the navy band: **primary (orange)** for maximum prominence (matches hero CTAs),
  or **outline** (white border, subtler). See question.

Confirm: the trust band is untouched; the stats now appear ONCE (in the band).
Awaiting owner choice on B + button style. Build only after approval.

### APPROVED + BUILT (owner, 2026-06-15)
Chosen: **B-i** (bold solid-blue `<x-ui.button>` "Meet the Team") — no orange exists in the
palette; "primary" is the bright blue (`#008fe6`, same as Book Now / hero CTAs).
- **Removed** the `about_stats` 3 tiles (15k ft / 30+ yrs / BS·USPA) from the About section AND
  the faint woven trust line + arrow-link — both duplicated the Trust band (and the About body).
- **Rebalanced** the About left column: eyebrow → title → body → `team_lead` lead ("The people
  you'll fly with.") → bold **Meet the Team** button; `items-center` centres it against the
  two-photo column (no gap). Gated on instructors existing (intentional empty state).
- **Trust band untouched** — the credentials (EX-MILITARY / 30+ YEARS / BS·USPA / EST. 2017) now
  appear ONCE. Removed the now-unused `$pages` inject from the home view; `about_stats` setting
  KEPT (no data change), just not rendered. Verified 1440/1280/1024/390 (`ui-review/homepage-dedupe/`).

## Team display polish (ui/team-polish)
Branch off main `f96b426`. Three decided, reuse-existing fixes (3 commits, gate green each;
verified 1440/1280/1024/390 — `ui-review/team-polish/`):
- **Fix 1 — discipline chips** (`discipline-tags`): even padding (`px-3 py-1.5`), moderate
  tracking (`tracking-[0.1em]` — `tracking-widest` had pushed glyphs off-centre) and centred
  text so the "TEACHES" chips read deliberate; even gaps. One source of truth.
- **Fix 2 — Tandem/AFF instructor element** (`discipline-instructors` + `instructor-chip`):
  regrouped the floating "Meet the team →" into a header row with the label (same pattern as the
  home Latest-News header) + a hairline rule; compact avatar row beneath — smaller uniform
  `h-14` square `object-cover` crops, `w-24` chips, tighter gaps. Clearly less vertical space.
- **Fix 3 — homepage About team lead**: gave "The people you'll fly with." the EXACT about-title
  treatment (`heading-rule` blue accent line + `font-display text-h2 uppercase tracking-wide`,
  as `<h3>`), so it's a proper section heading above the Meet the Team button, not weak text.
No data/model changes; reused existing components/tokens throughout.

## Team display fixes v2 + UI passes (ui/team-fixes-v2)
Branch off main `01d560b`. Fully autonomous run (owner away). Decided, reuse-existing fixes;
`composer check` green before every commit; verified by Playwright at 1440/1280/1024/390
(`ui-review/team-fixes-v2/`).

- **Spec substitution (noted, not blocked):** the prompt referenced a `frontend-design` skill
  and a `ui-passes/01–04` directory that do **not exist** in this repo. The equivalent material
  lives in `ui-guidelines.md` (the concrete design system = "pass 4 / guidelines") and
  `ui-review/{FOUNDATIONS,COMPONENTS,PAGES}.md` (the retrospective pass 1–3 audits). I treated
  those as the passes and ran them as a craft sweep (tokens → components → page rhythm →
  guidelines) over the changed team elements + any drift. Made the most on-brand choice per the
  established system rather than waiting for input.
- **Fix 1 — homepage About team mention:** replaced the prior `text-h2`+`heading-rule` heading
  (team-polish) and the solid button with a **modest sky-bright eyebrow label** (`team_lead`,
  with the standard `h-0.5 w-10` rule) + the shared **`<x-ui.arrow-link href="/meet-the-team">`
  "Meet the Team"** — the canonical animated arrow that the service-card "EXPLORE →" cue is the
  bespoke ancestor of (closing the ui-guidelines "Explore uses a bespoke border-b" gap by
  adopting the real component for the team link). Now sits quietly in the About band, not a
  second dominant header. Still gated on instructors existing. `HomeTeamMentionTest` retitled
  button→arrow-link (assertions unchanged: href + "Meet the Team" + the lead are all present).
- **Fix 2 — Meet the Team cards:** removed the `border-b-2 border-border` divider under the
  "Teaches" chips (it read as a grey line butting the chips) — plain `mb-6` separates the block
  from the bio now. Switched the photo crop from `aspect-[4/5]` portrait to **`aspect-square`
  (1:1)** so cards are uniform and shorter (less vertical space). Navy name/role band overlay and
  the discipline chips (with the even padding from team-polish) are unchanged.
- **Fix 3 — AFF/Tandem/Coaching instructor teaser** (`discipline-instructors`): rebuilt from the
  old tiny-avatar (`instructor-chip`) row with a floating top-right link into the **Meet the Team
  card language** — a `aspect-square w-40/sm:w-44` photo with the navy `border-t-4 border-primary`
  name/role band overlaid, exactly like the team page but **without the discipline tags** (a
  single-discipline page doesn't need them) and no bio. Eyebrow label on top; the
  `<x-ui.arrow-link>` "Meet the Team" sits **beneath the cards** (grouped, not floating beside
  them — the "All news →" placement). Discipline filtering + the empty-state guard are unchanged;
  verified the new card renders on all three pages (AFF: Joby/Ricky, Tandem: Joby/Lucy,
  Coaching: Joby/Ricky — tags absent).
- **Fix 4 — consistency:** the rebuilt teaser no longer uses `instructor-chip` (the small-avatar
  partial) and nothing else did, so **deleted `components/site/instructor-chip.blade.php`** (the
  last bit of the old stretched-avatar markup). Sitewide, every team cross-link is now the shared
  `<x-ui.arrow-link>` (home About + the three discipline pages) and every instructor photo is a
  square `object-cover` crop (team page cards + the discipline teaser cards). The header nav
  "Meet the Team" item is a normal nav link (unchanged). Testimonial avatars use `<x-site.avatar>`
  and are out of scope (not instructors).

### UI passes (craft sweep over the changed team elements + site)
Ran the four passes as `ui-review/{FOUNDATIONS,COMPONENTS,PAGES}.md` + `ui-guidelines.md`.
- **Pass 1 — foundations (type/spacing):** audited pages/components — section rhythm already on
  the `py-section*` tokens (no `py-20/28/32/40` drift left), and the remaining raw display sizes
  (service-tile titles, stat numerals, monograms, footer tagline) are the documented intentional
  display-type exceptions, left as-is. Only fix: the new teaser role plate used a raw
  `text-[0.6rem]` → moved to **`text-xs` + `text-sky-bright`** to match the Meet the Team card
  exactly; bumped the teaser name to `text-xl`. Verified desktop + 390 (role wraps gracefully).
- **Pass 2 — components (buttons/inputs/links):** the new team elements already use the shared
  `<x-ui.arrow-link>` (own focus-visible ring) and `<x-ui.button>`; cards guard motion with
  `motion-reduce`. Closed the documented "home EXPLORE cue is a bespoke arrow" gap — folded the
  service-tile `Explore →` into `<x-ui.arrow-link>` (span variant; arrow slides on the card's
  group hover) while keeping its distinct dark-tile `border-b-2` underline via the class merge.
  Now **every** arrow cue sitewide (team links + service tiles + "All news") is one component.
  Visually identical; verified.
- **Pass 3 — pages (layout/hierarchy/rhythm):** verified the changed elements sit with the
  established 2-step section rhythm. The discipline teaser renders through `<x-site.section>`
  (`py-section-sm lg:py-section`) so it spaces correctly between neighbours (e.g. tandem: between
  "What it costs" and the FAQ); the home About column reads title → body → quiet team label +
  arrow without the old competing heading. No page hand-rolls a divergent rhythm; no change
  needed beyond the team work. Full-page captures at 1280 in `ui-review/team-fixes-v2/pass3-*`.
- **Pass 4 — guidelines (final consistency):** captured home + the three discipline pages + Meet
  the Team at **1440 / 1280 / 1024 / 390**. All consistent: every team cross-link is the shared
  animated `<x-ui.arrow-link>` (keyboard focus ring intact); instructor photos are uniform square
  `object-cover` crops; the discipline teaser matches the team card language minus tags; no
  stretched/floating old markup remains; brand look (navy/blue/white, sharp corners, Bebas
  display) unchanged. Role labels wrap gracefully on the narrow 390 cards. Captures in
  `ui-review/team-fixes-v2/pass4-*`.

**Net:** 4 fixes + 4 craft-pass commits, each `composer check` green (361 tests). No model/data
changes; reused existing components/tokens throughout; one component deleted (`instructor-chip`).

## Homepage team link as an EXPLORE-style arrow (ui/homepage-team-link)
Branch off main `b414abb`. One decided fix to the home About section:
- **Dropped the "THE PEOPLE YOU'LL FLY WITH." teaser label** (the `team_lead` eyebrow line above
  the link). It's no longer rendered anywhere on the home page.
- **Restyled "MEET THE TEAM →" to match the service-tile "EXPLORE →" exactly:** white text + the
  blue `border-b-2 border-primary` underline + the sliding arrow. Reuses the **same**
  `<x-ui.arrow-link>` span variant the service cards use, wrapped in an `<a href="/meet-the-team"
  class="group text-white …">` (so the arrow slides on the link's own group hover, text inherits
  white, focus-visible ring is `ring-inset` for the dark band). Visually identical to EXPLORE,
  just labelled differently. Still gated on instructors existing; `prefers-reduced-motion` honoured
  (the shared component's `motion-reduce:transition-none`).
- **`team_lead` setting is now unused** (`HomePageSettings::$team_lead`, still seeded/editable). Left
  in place — flagged here for a later CMS-field cleanup, not removed in this UI-only task.
- `HomeTeamMentionTest` updated: asserts the EXPLORE-style team link + that the teaser label is
  gone, and gates the About link on instructors via a cache-flushed link-count comparison.

## Meet the Team — drop the "TEACHES" label (ui/drop-teaches-label)
Branch off main `a1c5c3a`. One fix to the instructor cards:
- Removed the "Teaches" eyebrow above the discipline chips (it read awkwardly — "teaches
  coaching" — and under the name + role the chips are self-explanatory). The chips now stand on
  their own. Removed the chips' `mt-2.5` (it had spaced them below the label) and gave the row
  `mb-6 mt-1` so it sits deliberately between the navy role band (card `p-6` top padding) and the
  bio, cramped against neither. Chips themselves (style/padding/navy outline), photo, name, role
  and bio are unchanged.
- The visible "Teaches" text only existed here. The discipline chips are already the shared
  `<x-site.discipline-tags>` partial; the *label* was inline to this page only, so there was one
  place to change. (The `{{-- WHO TEACHES THIS --}}` strings on the discipline pages are code
  comments, not rendered.) No stray "Teaches" text remains on the site.

## Discipline-page instructor section: heading, cards, spacing (ui/instructor-section-fix)
Branch off main `a1c5c3a`. Fixed the AFF/Tandem/Coaching "your instructors" cross-link section
(verified on all three + the team page at 1440/1280/1024/390 — `ui-review/instructor-section-fix/`):
- **One shared card** — extracted `<x-site.instructor-card :instructor :showDisciplines :showBio
  :heading>` as the single source of truth. The Meet the Team page renders it in full (chips +
  bio); the discipline pages reuse the SAME partial with `:show-disciplines="false"
  :show-bio="false"` (square photo + navy name/role band only), so they're genuinely identical in
  style, not a near-copy. `heading` sets the name tag (h2 under the team hero, h3 under the
  discipline section-heading).
- **Proper section heading** — the discipline section now uses the same `<x-site.section-heading>`
  the FAQ uses, with eyebrow **"The team"** + per-page title (**Your AFF Instructors** /
  **Your Tandem Instructors** / **Your Coaches**), matching the FAQ's eyebrow+heading pattern
  directly below it. Replaced the lone eyebrow; the component prop changed `label` → `heading`.
- **Spacing** — the section is the standard `<x-site.section>` rhythm; cards → `mt-8` arrow-link
  (grouped, not floating); the old sprawl came from tiny content under full padding — now the
  heading + full-size cards fill it. Grid is `gap-4` (NOT the team page's seamless
  `gap-px bg-secondary`) so an empty cell from a small/variable instructor count reads as
  background, not a stray navy block.
- The shared card omits the old "Teaches" label (consistent with the parallel
  `ui/drop-teaches-label` branch) — so on the team page this also drops that label; the two
  branches are coherent. Built as one cohesive refactor (shared partial touches both surfaces).
- `DisciplineStripTest` updated to the new heading casing ("Your AFF/Tandem Instructors").

## Form/heading alignment sweep (ui/form-alignment)
Branch off main `a1c5c3a`. Audited every form surface for the "left heading over a centred form"
mismatch (verified 1440/1280/1024/390 — `ui-review/form-alignment/`):
- **Coached enquiry form — realigned.** The left `<x-site.section-heading>` ("GET COACHED / TELL US
  WHERE YOU'RE AT") sat above an `mx-auto max-w-3xl` (centred) form. Dropped `mx-auto` → the form
  now starts at the same left content edge as its heading and the FAQ (both measured at 32px).
- **Booking flows (book-tandem, book-aff) — realigned.** Their Livewire root was `mx-auto
  max-w-3xl` (centred) under a left-aligned hero; dropped `mx-auto` so the funnel sits on the
  standard left content edge (matching the nav/footer/sections), consistent with the left hero.
  (Judgment call — a centred funnel is defensible; this favours the site-wide left edge the task
  asked for. One-line revert if a centred funnel is preferred.)
- **Left as deliberately-centred (heading + content both centred), noted not changed:** the
  **vouchers** page (centred `text-center` intro + centred purchase form — an internally
  consistent focused single-product unit) and the **newsletter** surfaces (the `/newsletter`
  centred card and the home newsletter **banner** — centred bands by design).
- **Already correct (no change):** the **contact** form (left column of a `2fr_1fr` grid, left edge
  32px) and the **tandem/AFF enquiry** forms (left column beside the pay-card in a 2-col grid).
- Alignment/layout only — no form fields, validation or behaviour touched.

## Remove section-divider lines + codify the rule (ui/remove-section-dividers)
Branch off main `a6cfbc6`. Section transitions had drifted inconsistent (added piecemeal across
branches; per-page review never caught it): tandem/AFF/home carried full-width **navy**
`border-b-2`/`border-y-2 border-secondary` lines between sections, while coached and the newer
instructor/FAQ sections had none.
- **Part 1 — removed all 10 navy section-divider classes** across home/tandem/aff/coached so
  transitions use the section `py-section*` spacing only (a colour change to a dark band is its own
  edge). KEPT (not section dividers): the brand **blue** `border-*-4 border-primary` rules — the
  `<x-site.page-hero>` underline, the newsletter/gift band frames, card tops — plus header chrome
  and all component-internal lines (FAQ separators, tables, inputs, eyebrow accents). Spacing model
  is unchanged (sections are flush; the rhythm is their internal padding, which I didn't touch), so
  nothing cramped — verified 1440/1280/390 on home/tandem/aff/coached; other pages were already
  divider-free.
- **Homepage included** per this task's explicit scope ("apply across ALL pages (home, …)") — a
  deliberate owner decision that supersedes the earlier consistency-pass "don't touch home" guard
  (which was specific to that pass). Home's navy lines around the services/trust/news sections are
  gone; the newsletter band's blue frame stays.
- **Part 2 — codified the rule**: `ui-guidelines.md` (Spacing & layout + the new-page checklist)
  and `CLAUDE.md` design rules now state "section transitions use spacing only — no full-width
  divider line on a `<section>` wrapper", and the guidelines add a consistency-pass note to compare
  the SAME transition zone ACROSS 3+ pages (per-page review misses cross-page drift — exactly how
  these dividers crept in).

## Consistent feature lists + codify the rule (ui/feature-list-consistency)
Branch off main `af60553`. The product-page "what's included" lists had drifted (same cross-page
class as the section dividers): all three carried a left vertical line (`border-l-2 border-primary`)
and Coaching used `target`/concentric-circle icons while Tandem/AFF used checkmarks.
- **Consolidated into one shared `<x-ui.feature-list :items>`** — blue **checkmark** + text row, **no
  left vertical line** — and used it on Tandem, AFF and Coaching. One source of truth, so they can't
  diverge again. The CMS content (`$page->bullets` / `$page->skills`) is unchanged; only the icon +
  row styling is now identical (checkmarks read as "what's included" better than targets). Verified
  1280/390 — all three render an identical treatment (`hasLeftBorder:false`, check path on each).
- **Codified the rule**: `ui-guidelines.md` documents `<x-ui.feature-list>` in the component
  catalogue ("blue checkmark + text, no left line; don't vary the icon/row per page"), and the
  cross-page consistency note now explicitly covers any repeated element (feature lists, dividers,
  cards, eyebrows, CTAs) — compare across pages, confirm a single shared component.
- The pay-card's own feature highlights (sky-bright checks on the dark booking CTA) are a separate
  dark-band sub-element of `<x-site.pay-card>`, already consistent — left as-is (not the page
  "what's included" lists this rule governs).

## Consolidate complete ui-guidelines.md from the real system (docs/ui-guidelines)
Branch off main `dddfed4`. Doc-only — derived `ui-guidelines.md` from what the codebase actually
does (no site styling changed) and folded in the existing rules. Now covers: the type-scale tokens
(values + line-heights + use), the spacing rhythm + spacing-only section transitions, the **exact
oklch palette** + the radius scale being pinned to 0 (so `rounded-*` always renders square) +
shadows-removed, the full shared-component catalogue (added `<x-ui.feature-list>`,
`<x-site.instructor-card>`, `<x-site.discipline-instructors>`, `<x-site.discipline-tags>`), the
section pattern, imagery (square instructor photos), icon/motion/a11y conventions, and a new
**Cross-page consistency** table naming every repeated element + its single shared source of truth,
with the rule that a UI pass must compare each element ACROSS pages (the anti-drift defence).
- **⚠️ Inconsistencies to resolve** (listed at the top of the doc for the owner, NOT fixed here):
  (1) the dead `.dark {}` block in `app.css` with an off-brand **orange** `--primary` (inert on the
  public site, contradicts "no dark mode / no new colours" — delete it); (2) the `/newsletter`
  "Join the list" card uses `border` + `shadow-sm` instead of the standard `border-2 border-secondary`
  flat card (swap it); (3) the known type-scale mid-gap (low priority). Each is a small follow-up
  branch.
`composer check` green (docs only). Pushed, not merged.

## Resolve the two flagged ui-guideline inconsistencies (fix/ui-guideline-inconsistencies)
Branch off main `647408a`. Cleared the punch-list from `docs/ui-guidelines`:
- **Removed the dead `.dark {}` block** in `app.css` (off-brand orange `--primary`, never applied on
  the public site) — no visual change; eliminates the latent risk and the rule contradiction.
- **`/newsletter` "Join the list" card** → standard `border-2 border-secondary bg-card p-8` flat
  card (dropped `rounded-2xl border shadow-sm`); now matches every other card (2px border, sharp,
  no shadow — verified). Content/behaviour unchanged.
- Updated the `ui-guidelines.md` punch-list to mark both resolved. `composer check` green.

## Vector SVG logo — brand navy, readable SKYDIVING (ui/logo-svg)
Branch off main `f073fc5`. Replaced the raster header logo with a true-vector inline SVG.
- **Source + tools:** the owner's vector PDF (`storage/app/brand/G-Force Logo Blue & Grey pdf.pdf`)
  → `pdf2svg` (Homebrew) → recolour → `svgo` (npx, multipass). Conversion was **clean** (17 paths,
  no mangling); svgo merged to 3 paths and **4.9 KB** (from 15.5 KB).
- **Colour mapping (the "wrong blue" fix):** the print CMYK-derived blue
  `rgb(9.02%,43.53%,75.49%)` (≈ `#176FC1`) on the swoosh + G-FORCE, plus SKYDIVING's white fill and
  grey `rgb(65%,…)` outline, were ALL mapped to **`currentColor`**, so the logo themes via text
  colour: **`text-secondary` (brand navy)** in the header, `text-white` for reversed/dark use. No
  off-brand blue or CMYK remains.
- **SKYDIVING decision:** it was a white fill + a thin grey 2px outline → invisible/faint on the
  white header (the readability bug). Changed to a **solid fill** (now navy via currentColor) — the
  unified-wordmark, highest-contrast option. The two words stay distinct by **typography** (G-FORCE
  blocky upright vs SKYDIVING italic), not a faint outline. Verified legible at 1440→**390**.
- **viewBox crop:** the PDF exported on a full A4 page (`0 0 841.9 595.3`) with the logo centred;
  `getBBox` gave unreliable bounds on this old Illustrator file, so I measured the true ink box by
  **rendering at 1:1 and scanning canvas pixels** → cropped to `viewBox="62 162 720 272"` (+12
  margin), no width/height so it scales by CSS. Verified not clipped at any size.
- **Wiring:** new `<x-site.logo>` partial (inline SVG, `currentColor`, `role="img"
  aria-label="G-Force Skydiving"`), used in the header (`h-10 w-auto text-secondary`). The **footer**
  uses a Bebas **text wordmark** (not an image logo) on the dark band — already correct, left as-is.
- **Out of scope (flagged):** the newsletter EMAIL block still uses the raster `images/logo.png`
  (old blue) — emails need a raster; regenerating it as a navy PNG from the new SVG is a follow-up.
`composer check` green (361). Pushed, not merged.

## LATEST NEWS heading matches the standard section pattern (ui/latest-news-heading)
Branch off main `f073fc5`. The homepage "Latest News" heading was a hand-rolled `<h2>` with a
newspaper ICON, no eyebrow and no accent line — out of step with every other section heading.
- Replaced it with the shared **`<x-site.section-heading>`** (eyebrow + `text-h2` + heading-rule
  accent line), so it now matches "THREE WAYS TO FLY" / "FREQUENTLY ASKED QUESTIONS" exactly.
  Removed the newspaper icon (no other heading has one) and the redundant "Fresh from the dropzone."
  subtitle.
- **Eyebrow clash resolved (no duplicate on the page):** used **"From the dropzone"** as the LATEST
  NEWS eyebrow (news literally comes from the dropzone — it fits, and replaces the old subtitle's
  vibe), and renamed the FOLLOW US photo-grid label from "From the dropzone" → **"Recent jumps"**
  (describes the curated shots). Verified only one "From the dropzone" remains on the page; both
  labels are on-brand and use the standard eyebrow style.
Verified 1440/1280/1024/390. `composer check` green (361). Pushed, not merged.

## White footer logo (SVG) + new logo in all mailers (PNG) (ui/logo-footer-mailers)
Branch off main `5b21538`.
- **Part 1 — footer white logo (web SVG):** the footer showed a plain Bebas **text wordmark**
  (`<span>G-Force Skydiving</span>`), not the mark. Replaced it with the shared
  `<x-site.logo class="h-11 w-auto text-white">` wrapped in a home link — the new vector logo,
  reversed to **white** via `currentColor` (`text-white`) so it reads on the dark `band-ink`
  footer. Same mark as the navy header logo, just reversed. `aria-label` keeps the accessible
  name. Verified crisp + clearly visible at 1440 / 390.
- **Part 2 — new logo in all emails (PNG, NOT SVG):** email clients (Outlook/Gmail) don't render
  SVG, so the email logo must be a raster **PNG referenced by an absolute URL**.
  - **Export:** rasterised the new logo SVG to a **navy** (`#00226b`, the `secondary` token), **transparent**
    PNG via a headless-Chrome canvas render at **360×136** (2× of the 180×68 display), saved to
    `public/images/email/logo.png` (~5 KB). Navy because the email card is light (`#ffffff`).
  - **Two render paths, both updated to the new PNG via `url()` (APP_URL-absolute, never relative):**
    (1) the transactional **markdown mailers** show the app-name *text* via Laravel's default
    `mail::header` — overrode it at `resources/views/vendor/mail/html/header.blade.php` to render the
    logo `<img>` instead, so every email through `<x-mail.layout>` (confirmations, reminders, enquiry
    acks/replies, vouchers, booking, login link, course messages, payment notices) gets the mark;
    (2) the **newsletter** builder's `logo` block (`mail/blocks/logo.blade.php`, shared by
    `NewsletterRenderer`) now points at the same PNG.
  - **Verified by rendering the real mailables** (`/dev/mail`): booking-confirmed, voucher, enquiry-ack
    and the newsletter all show the PNG (`naturalWidth=360`, absolute URL, **no `<svg>`** in the
    email, app-name text gone). `BuilderTest` updated to assert the new path + no inline SVG.
  - **Out of scope:** the old `public/images/logo.png` stays (still used by the JSON-LD Organization
    `logo` in `StructuredData` — SEO, not email); regenerating that to the new mark is a follow-up.
`composer check` green (361). Pushed, not merged.

## Refresh the JSON-LD / SEO logo to the new mark (chore/jsonld-logo)
Branch off main `fc60055`. The follow-up flagged in the logo-footer-mailers work: `StructuredData`
(the Organization schema `logo` + an `ImageObject` logo) still pointed at the OLD blue raster
`public/images/logo.png`.
- **Regenerated `public/images/logo.png`** from the new logo SVG — **navy on solid white** (a brand
  logo for SEO must read on any background; white guarantees it, vs a transparent PNG that would
  vanish on a dark knowledge-panel/social surface), **600×227** raster, ~7.6 KB. The URL is
  unchanged, so both `StructuredData` references now serve the new mark with **no code change**.
  Verified the homepage JSON-LD emits `"logo":"…/images/logo.png"` (absolute) and the asset serves
  `200 image/png`.
- **Removed the orphaned `public/images/logo.webp`** — it only existed for the old header
  `<picture>`, which now uses the inline SVG; zero references remained.
`composer check` green (361). The new logo mark is now consistent across header (SVG), footer
(SVG white), emails (PNG navy) and structured data (PNG navy-on-white).

## Image performance pass — audit (perf/images, off main `fe5afdb`)
Brief: improve image performance for Core Web Vitals (WebP, sizing, responsive, lazy,
no-CLS) **without changing how anything looks**. Audit first, then fix in impact order.

**How images are served — two categories:**
1. **Uploaded** (admin via Filament `FileUpload` → public disk → relative path). These go
   through the optimisation pipeline: `ImageOptimizationObserver` (models) /
   `SettingsPage` (settings) dispatch `OptimizeUploadedImage`, which `scaleDown`s to the
   directory's longest-edge cap (`ImageOptimization::MAX_DIMENSIONS`: instructors 480,
   testimonials 240, testimonials-photos/news/products/locations 1280, gallery 1200,
   hall-of-fame 960, pages 1920) and re-encodes **WebP q82** (Intervention GD, also strips
   EXIF), then rewrites every reference to the `.webp` path. **Verified working** by the
   existing `tests/Feature/Images/ImageOptimizationTest`. So uploads are already WebP,
   sized-to-slot and metadata-stripped — **single size each, no responsive variants**.
2. **Bundled static** `/images/*` (the seeded/default content + the home hero). Resolved
   as-is by the `image_url`/`imageUrl()` accessors (any `/`-prefixed path is returned
   verbatim) and **explicitly skipped by the pipeline** (`isOptimisablePath()` returns
   false for `/`-prefixed paths). **These ship as JPEG/PNG — the gap.**

**Per-image audit (significant public images):**
| Image | Served | File | Pixel dims | loading | w/h | srcset |
|---|---|---|---|---|---|---|
| Home hero `hero-skydive.jpg` (home.hero_image default) — also home CTA close | **JPEG** | **157 KB** | 1920×1080 | eager + preload + fetchpriority=high ✓ | yes | **none** |
| Service/about/feature photos `tandem/aff/coached.jpg` | **JPEG** | ~55 KB | 1280×896 | lazy ✓ | yes | none |
| Instructor portraits `instructors/*.jpg` | **JPEG** | ~58 KB | 800×1000 | lazy ✓ | yes | none |
| Hall-of-Fame hero (hard-coded in blade) | **JPEG** | 157 KB | 1920×1080 | eager (hero) ✓ | yes | none |
| Testimonials/Hall-of-Fame tiles, home gallery thumbs | bundled/uploaded | — | — | lazy ✓ | mostly yes | none |
| News featured, page-hero (tandem/aff/coached) | uploaded → **WebP** | — | capped | lazy / eager-hero ✓ | yes | none |

**Findings (worst offenders + gaps):**
- **F1 — `hero-skydive.jpg`, 157 KB JPEG @1920, is the LCP and the single biggest asset.**
  Used by the home hero, the home CTA close, the Hall-of-Fame hero, plus og:image / JSON-LD
  and seeded into gallery/testimonials/hall-of-fame. WebP q82 ≈ halves it.
- **F2 — all bundled photos are JPEG/PNG** (tandem/aff/coached ~55 KB, instructors ~58 KB).
  The pipeline never touches them, so the *default/seeded* site ships no WebP.
- **F3 — no responsive variants anywhere.** Pipeline emits one size; no view has
  `srcset`/`sizes`. Phones download the desktop file. Material only for the **hero** (1920px
  shown ~390px on a phone); every other bundled photo is ≤1280px / ~55 KB and capped near its
  slot, so a full responsive-variant subsystem would be overreach (the brief warns against it).
- **F4 — CLS is essentially handled.** Nearly every `<img>` has explicit width/height or an
  `aspect-*` box. One nit: the home gallery thumb (`home.blade.php` Recent-jumps grid) has no
  width/height — but it sits in an `aspect-square` cell with `h-full w-full`, so no real shift.
- **F5 — loading is correct.** Hero is eager + preloaded + fetchpriority=high; everything
  below the fold is `loading="lazy"`. No change needed.

**Constraints that shape the fix (why conversion is selective, not blanket):**
- **Email/newsletter images MUST stay JPEG/PNG** — Outlook and many mail clients don't render
  WebP. So `NewsletterStarterTemplates`, `NewsletterCampaignSeeder`, `mail/blocks/*`,
  `email/logo.png` are left as-is.
- **og:image / JSON-LD stay JPEG/PNG** — social scrapers (notably LinkedIn) handle WebP
  unreliably and it's not a page-load asset. `general.og_image` default and `StructuredData`
  keep `/images/hero-skydive.jpg` / `/images/logo.png`. The original JPEGs/PNGs therefore
  **remain on disk** (also as pipeline-style fallbacks); we add `.webp` alongside.

**Plan (impact order):**
- **Fix 1 (format):** generate `.webp` (q82, identical dimensions) for the web-shown bundled
  photos (hero, tandem, aff, coached, 3 instructors) and repoint the **web-page** references
  only — settings defaults (home/tandem/aff/coached), content seeders (Product, Instructor,
  HallOfFame, Gallery, Testimonial), the Hall-of-Fame blade hero, and the Testimonial factory.
  Update the 4 tests coupled to seeded/web-shown assets. Leave email/og/JSON-LD on JPEG/PNG.
- **Fix 2 (responsive, hero only):** add a mobile hero variant + `srcset`/`sizes` and a
  responsive preload (`imagesrcset`/`imagesizes`) on the home hero — the one image materially
  oversized for phones. Document broader per-image variants as deferred (overreach: F3).
- **Fix 3 (CLS nit):** add explicit width/height to the home gallery thumb.
- Sizing (1b): already satisfied — uploads capped by the pipeline; bundled photos are exported
  at sensible sizes (≤1920) and kept at identical dims (appearance must not change).

### Outcome
- **Fix 1 (format)** — bundled photos now WebP q82 at identical dimensions: hero
  157→106 KB (-32%), tandem/aff/coached -39..-42%, instructors -50%. Web-page references
  repointed (settings, seeders, Hall-of-Fame hero); email/og/JSON-LD kept JPEG/PNG.
- **Fix 2 (responsive hero)** — generated `hero-skydive-1280.webp` (63 KB) and added
  `srcset`/`sizes="100vw"` + a matching responsive `<link rel=preload imagesrcset>` to the
  home hero, the home CTA close and the shared `page-hero` (so the Hall-of-Fame hero benefits
  too). Driven by `App\Support\ResponsiveImage`, which emits a srcset **only when a `-1280`
  sibling exists on disk** — bundled heroes get it; uploaded heroes degrade to the single
  pipeline-capped image. Verified in-browser: 390px loads the 1280 variant, 1440px the 1920.
  Generating per-upload variants in the pipeline is deferred (overreach — only the 1920 hero
  is materially oversized; every other photo is ≤1280 / ~35 KB).
- **Fix 3 (CLS)** — added explicit width/height to the home gallery thumb (the one `<img>`
  without intrinsic dimensions). All other images already carried width/height or an
  `aspect-*` box; loading (eager hero + preload, lazy below the fold) was already correct.
- Site verified visually unchanged at 1440 and 390. `composer check` green (363).

## config:cache fix — Sentry already cache-safe; real blocker was a dev-route redeclare (fix/sentry-config-cache)
Branch off main `fe5afdb`. Investigated the reported deploy failure
("`sentry.before_send` is non-serializable … `Closure::__set_state()`").
- **The Sentry closure was already gone.** `config/sentry.php` has **no closures**: `before_send`
  is the array callable `['App\Support\SentryScrubber', 'scrub']` (introduced cache-safe in
  SEC-P3.3, `deb4dc5`, and already documented above as `var_export`-serializable for `config:cache`).
  Production-only reporting is already enforced the simplest way — the **DSN is only set in the
  production env**, so `config('sentry.dsn')` is `null` everywhere else and Sentry sends nothing.
  Verified: `before_send` is not a `Closure`; local `sentry.dsn` is `NULL`; with a production DSN env
  it resolves; and **`APP_ENV=production php artisan config:cache` succeeds (EXIT 0)** — i.e. the
  actual deploy was already fine. No Sentry change was needed.
- **The real reason `config:cache` failed (locally only)** was unrelated: a fatal
  `Cannot redeclare function devMailPreviews()`. `routes/dev.php` (loaded ONLY in local) declares a
  top-level `devMailPreviews()` helper and was pulled in with `require`; `config:cache` re-bootstraps
  the app in the **same process**, including the file twice → redeclare fatal. Fixed by changing
  `require` → **`require_once`** in `routes/web.php` so the local-only dev file is included idempotently.
  (Never loaded outside local, so production is unaffected either way.)
- **Verified:** `php artisan config:cache` now completes locally (EXIT 0), `php artisan config:clear`
  EXIT 0, `grep -E 'function|fn\('` on `config/sentry.php` is empty, dev still boots, `composer check`
  green (361). Net change is one line (`require` → `require_once`); the Sentry config was already correct.

## Square service cards + admin image crop with enforced ratios (feat/image-crop-ratios)
Branch off main `fe5afdb`.

### Phase 0 — investigation + plan (CHECKPOINT)
- **Filament v5 has a built-in image editor — NO package needed.** `FileUpload` supports
  `->imageEditor()` (Cropper.js crop/zoom/rotate), `->imageEditorAspectRatios([...])` (the ratio
  options — pass a single ratio to LOCK it), `->imageAspectRatio('1:1')` + `->automaticallyCropImagesToAspectRatio()`
  (centre-crops to the ratio on upload even without opening the editor), and
  `->automaticallyOpenImageEditorForAspectRatio()` (auto-opens the cropper when the source doesn't
  match, so the owner positions the subject). `avatar()` already bundles 1:1 + auto-crop. No hard
  rejection — the crop tool IS the UX.
- **Pipeline:** Filament crops BEFORE storage; the existing `ImageOptimizationObserver` /
  `SettingsPage` then dispatch `OptimizeUploadedImage`, which re-encodes to WebP at the directory's
  longest-edge max (keeping the cropped aspect). So a crop flows through WebP automatically; no
  pipeline change. Existing images are NOT re-cropped — they keep displaying via the front-end
  `object-cover` safety net (already universal).
- **Per-field target ratios (derived from the current front-end display):**

  | Field | Model / Settings | Front-end display | Crop ratio |
  | --- | --- | --- | --- |
  | Card image | `Product::image` | home service cards (was `aspect-[3/4]`→**now `1:1`**) | **1:1** |
  | Photo | `Instructor::photo` | instructor-card `aspect-square` | **1:1** |
  | Image | `GalleryImage::image` | home "Recent jumps" grid `aspect-square` | **1:1** |
  | Avatar | `Testimonial::avatar` | `<x-site.avatar>` square | **1:1** |
  | Photo | `Testimonial::photo` | featured band full-bleed (1920×1080) | **16:9** |
  | Image | `HallOfFameEntry::image` | photo-tile `aspect-[3/4]` | **3:4** |
  | Featured image | `NewsArticle::featured_image` | news cards `aspect-[16/10]` | **16:10** |
  | Hero image | Home/Tandem/Aff/Coached `hero_image` | full-bleed wide hero band | **16:9** |
  | Left/Right photo | Home `about_image_1` / `about_image_2` | about band `aspect-[3/4]` | **3:4** |
  | Intro/feature image | Tandem/Aff `intro_image`, Coached `image` | feature-split `aspect-[16/10]` | **16:10** |
  | Image | `Location::image` | no fixed public display found | **free** (no lock) |
  | Newsletter block images / Documents | builder / course docs | inline / non-image | **free** |

- **Phase 1 (front end):** square only the service-card images (`aspect-[3/4] md:aspect-[4/5]` →
  `aspect-square`); `object-cover` is already present sitewide (safety net), other shape-matters
  images already carry a fixed `aspect-*` + `object-cover` and stay as-is.
- **Phase 2 (admin):** a DRY `App\Support\ImageCrop::ratio($fileUpload, $ratio)` helper applies the
  locked editor to each shape-matters field per the table; genuinely free-form fields (Location,
  newsletter inline, documents) are left unrestricted.

### Phase 1 — front end (service cards squared)
- Home service-card link: `aspect-[3/4] … md:aspect-[4/5]` → **`aspect-square`** (the image is already
  `absolute inset-0 object-cover`, so it crops gracefully and the bottom text overlay is unchanged).
  Cards are now uniform squares and noticeably shorter. `object-cover` was already universal across
  the shape-matters images (instructor square, gallery square, about 3:4, news 16:10, hero cover),
  so the safety net needed no new work.

### Phase 2 — admin crop tool (built-in Filament editor, ratio LOCKED)
- New `App\Support\ImageCrop::ratio($fileUpload, $ratio)` helper wraps an image `FileUpload` with
  Filament v5's built-in editor: `imageEditor()` + `imageEditorAspectRatios([$ratio])` (single
  option → locked crop box) + `imageAspectRatio($ratio)` + `automaticallyCropImagesToAspectRatio()`
  (centre-crop fallback) + `automaticallyOpenImageEditorForAspectRatio()` (auto-opens the cropper
  on a non-matching upload so the owner positions the subject). No package; no hard rejection.
- Applied per the Phase-0 table across 10 forms: Product (1:1), Instructor (1:1, replaced the
  misleading circular `avatar()` preset with a square editor), GalleryImage (1:1), Testimonial
  (avatar 1:1, photo 16:9), HallOfFameEntry (3:4), News featured_image (16:10), and the Home /
  Tandem / AFF / Coached settings (hero 16:9, about 3:4, intro/feature 16:10). Location, newsletter
  inline images and document uploads were left free-form (no fixed display).
- The cropped file still flows through `ImageOptimizationObserver` → `OptimizeUploadedImage` (WebP)
  unchanged; already-uploaded images are untouched and keep rendering via the front-end `object-cover`.
- **Verified in the real admin crop UI:** uploaded a 1920×1080 (16:9) image to the Instructor photo
  (1:1) → the editor auto-opened with the crop box LOCKED to 1:1 (ratio 1.0) and the saved file is
  **1080×1080 square**; uploaded the same image to News featured_image (16:10) → crop box locked to
  1.6. `ImageCrop` unit-tested for 1:1/16:9/16:10/3:4. Screenshots in `ui-review/image-crop-ratios/`.

## UI consistency pass over the merged state (ui/consistency-pass)
Branch off main `a6cfbc6`. A light reconciliation over the recently-merged UI work (discipline-page
instructor sections, Meet the Team, forms) against the established system — NOT a rebuild. Ran
passes 01→04 as a CHECK; audited each area and found it **already consistent**, so no code changes
were made (deliberately — "don't change for the sake of it"). What was verified:
- **One shared instructor card** — `<x-site.instructor-card>` is the single card markup, used by both
  Meet the Team (full: chips + bio) and the discipline teaser (`:show-disciplines/:show-bio="false"`).
  No inline near-copies remain (grep for the navy name-band markup returns only the partial).
- **Arrow-links** — every navigational arrow in the merged areas is the shared `<x-ui.arrow-link>`
  (EXPLORE on the home tiles, MEET THE TEAM on the About band + discipline sections). The only bespoke
  `arrow-right` icons left are on the **homepage** (the hero CTA button and the "Follow us" social
  rows) — protected/signed-off, intentionally untouched.
- **Section headings/eyebrows** — the discipline instructor sections use the same
  `<x-site.section-heading>` as the FAQ (eyebrow `tracking-[0.25em] text-primary` + `text-h2` rule);
  eyebrow tracking is the standard `[0.25em]` (the discipline-tags chip's `[0.1em]` is the documented
  deliberate exception).
- **Forms** — coached + both booking funnels are left-aligned (no `mx-auto`); contact + enquiry forms
  already left; vouchers/newsletter intentionally centred (documented). Consistent left content edge.
- **Tokens/palette** — no ad-hoc `text-[…]`/`py-[…]` drift in the merged components (only the
  intentional `text-[10rem]` monogram fallback); palette tokens only.
- **Homepage left untouched / verified unchanged** — captured HOME at 1440 + 390 before and after:
  1440 is byte-identical; 390 has identical layout/dimensions (the small pixel delta is lazy-loaded
  gallery/news imagery rendering nondeterministically — two consecutive after-shots also differ from
  each other). `git status` confirms zero code changes, so the homepage source is unchanged.
No files changed except this log + the audit screenshots in `ui-review/consistency-pass/`.

## Expose Horizon stats to the Ploi panel (feat/ploi-horizon-stats)
Branch off main `f8fa865`. Code side of Ploi's "Laravel Horizon statistics" integration
(Ploi panel + server `.env` token are the owner's steps, not in the repo). Three code
changes + one documented env var:
- **`config/cors.php`** — published the stock Laravel 13 stub (the project had none; the
  `HandleCors` middleware is in the framework's default global stack, so it's already active)
  and added `horizon/*` to `paths` (kept `api/*` + `sanctum/csrf-cookie`). Left
  `allowed_origins`/`supports_credentials` at the published defaults — only the path is needed.
- **`config/services.php`** — added `services.horizon.token` => `env('HORIZON_TOKEN')` (plain
  `env()` read, no closure, `config:cache`-safe).
- **`HorizonServiceProvider::gate()`** — added a bearer-token branch *in front of* the existing
  rule, which is preserved verbatim. NOTE: the real existing rule was **not** an email allow-list
  (the brief assumed one) — it's `$user !== null` ("every account is an admin, any authenticated
  user may view Horizon"), so that is what the fallback keeps. Token compared with `hash_equals`
  (constant-time). Gate reasoning (covered by `HorizonGateTest`, 4 cases):
  - (a) bearer token present **and** `HORIZON_TOKEN` set **and** they match ⇒ allowed (no user
    needed — Ploi calls without a session);
  - (b) missing/incorrect token ⇒ falls through to the user rule ⇒ only authenticated admins;
  - (c) empty/unset `HORIZON_TOKEN` ⇒ the `$configured !== ''` guard makes the bearer branch
    unreachable (an empty bearer can never authorise), so only the user rule applies.
- **`.env.example`** — documented `HORIZON_TOKEN=` (empty placeholder + comment; real token is
  server-only, never committed).
`config:cache` succeeds then `config:clear`; `composer check` green. Pushed, not merged.

## Kit sync to the September 2026 starter kit (chore/kit-sync-2026-09)
Branch off main `be9e145` (prompt `prompts/001-kit-sync.md`, run as item 1 of
`prompts/unattended-run-1.md`). Docs only: no app code, views, config or tests change.
Kit source `~/Sites/starter-kit/`, README confirmed to open with "What changed in this
revision (September 2026)".

**Copied verbatim (new):** `prompts/README.md`, `prompts/writing-prompts.md`,
`prompts/running-order.md`; `false-green.md` (root); `gates/README.md`,
`gates/completeness-check.md`, `gates/cms-field-usage-check.md`, `gates/pre-staging-gate.md`;
`audits/email-audit.md`; `verification/real-device-checks.md`,
`verification/tester-feedback-triage.md`. `verification/CHECKLIST.md` is the kit's
`pre-launch-checklist.md` with one line on top (`> NOT YET TAILORED to G-Force — see
RUNNING-ORDER.md step 9.`). Every copy `cmp`-identical to the kit (the checklist after its
first line). No other project's name appears in any copied file.

**Refreshed (drifted copies):** `audits/README.md`, `accessibility-audit.md`,
`admin-audit.md`, `code-style-audit.md`, `design-audit.md`, `security-audit.md`,
`ui-passes/README.md`, `ui-passes/04-guidelines.md`. Diffed each first: every difference was
a kit addition (1–5 lines: email-audit pointer, verify-by-attack, singleton editors, motion
guard, spam-hardening, phantom-component check). No repo copy carried a G-Force-specific
edit, so none was left. `seo-audit.md` and `ui-passes/01–03` were already identical.

**Skills (report only — not changed).** The project has no `.claude/skills/`. User-global
skills live in `~/.claude/skills/synced/…` (claude.ai-synced). Against the kit's `skills/`:
- `frontend-design` — installed, **differs**: the kit copy has ~125 more lines (AI-default
  font cluster, self-host brand fonts, and more); 3 lines differ the other way.
- `admin-design`, `laravel-craft`, `web-app-security` — **not installed** globally.
These affect Ben's other projects, so syncing them is his call, not this branch's.

**CLAUDE.md — rules added** (grepped for each in other words first; none was covered — the
nearest, "Business logic lives in `app/Actions`", doesn't say *one* writer):
- Architecture 7–12: state re-rendered by the mechanism that changes it; nothing loads/inserts
  DOM inside a Livewire-morphed view; session-reading middleware on `web` after
  `StartSession`; `svh` shells (viewport units only as caps); one writer per fact / one
  reader per figure; a gate never becomes a picture of a gate.
- Quality bar: fix the instance then guard the class (prove the guard); the suite collects
  every `tests/*` dir; a measurement is only as good as its tree/build; verify state against
  code and git.
- New `## Workflow` section: prompts are files (+ pointers to `prompts/`, `RUNNING-ORDER.md`,
  `audits/`, `gates/`, `verification/`, `false-green.md`); verify the premise; gap report;
  escalate owner decisions (`OWNER DECISION — PENDING`); mark `OVERNIGHT-DEFAULT — CONFIRM`.
- Design rules: motion ambition (below).
The four new code rules all hold on `be9e145` (0 `@vite`/`<script>`/`x-if` under
`resources/views/livewire/`; only `min-h-screen` in `layouts/app.blade.php:66`; no middleware
in `app/Http/Middleware/` reads the session). Nothing is enforced by a test yet — that's
prompt 002. There is no suite-collection test yet either (also 002).

**Motion ambition: subtle — `OVERNIGHT-DEFAULT — ANSWERED 9 Oct (see DECISIONS)`.** G-Force never recorded a level.
As built: CSS transitions plus the opt-in `data-reveal` IntersectionObserver reveals in
`resources/js/app.js` (skipped under `prefers-reduced-motion`; content visible without JS).
No Motion One calls — the `motion` package is in `package.json` but imported nowhere (a
possible cleanup for its own prompt; not touched here). That matches the kit's
*subtle-standard* minus Motion One, so "subtle". Cinematic would need the owner's ask.

**SETUP.md deploy sequence** reconciled with `bootstrap.md` §5: added the PHP-FPM reload
(`php8.3-fpm`, step 7, before the Horizon restart which is now step 8); the "never
`key:generate` / `migrate:fresh` / `migrate:refresh`" list; and the owner task to delete
any `key:generate` line from Ploi's generated deploy script after the first run.
**No converge-matrix step:** G-Force has no code-declared matrix. No roles/permissions
package; admin access is the single `is_admin` flag (`User::canAccessPanel()`); settings
properties are spatie settings migrations in `database/settings/`, which `migrate --force`
(step 4) already applies on every deploy. Adding a sync command would be ceremony.

**Deliberately not done:**
- `bootstrap.md` not re-run — it would rewrite CLAUDE.md, whose G-Force decisions win.
- ui-passes not re-run — they ran 15–16 June; the kit says run once.
- `add-motion-layer` not adopted — the homepage is signed off, no owner ask for cinematic.
- No audit or gate run on this branch.

**Gap report.**
1. *Required, not done:* none.
2. *Forbidden, done:* Step 0 says stop on any extra untracked file or stash. It found three
   (the `.playwright-mcp/` June browser-tool logs, a 16 June untracked-only stash on
   `ui/logo-svg` holding June kit copies + two `ui-review/merge-verify/*.png` screenshots,
   and `prompts/unattended-run-1.md`). Stopped and asked each time; Ben said drop the stash,
   delete `.playwright-mcp/`, and run `unattended-run-1`.
3. *Not mentioned, done:* `prompts/unattended-run-1.md` committed with the other prompts.
   001's premise cites `bootstrap/app.php:25` as "session middleware appended to `web`" —
   what's appended there is `SecurityHeaders`, which doesn't read the session; the rule
   holds anyway because no app middleware reads it. `RUNNING-ORDER.md`'s `main at` line
   already said `be9e145`, the sha branched from, so it's unchanged.

## Structural guards for the kit's architecture rules (test/structural-guards)
Branch off main `900fb4b` (prompt `prompts/002-structural-guards.md`, item 2 of unattended run 1).
Tests only, plus the guard pointers in CLAUDE.md. Premise confirmed: every rule holds on main,
so every guard is green on real code; each was proven by a planted violation instead.
Structural guards live in `tests/Unit/Architecture/` (new; plain PHPUnit, no app boot, sharing
`SourceFiles` helpers); the two request-level halves are feature tests.

| # | guard | scope | planted violation → red (one line) |
|---|---|---|---|
| 1 | `SuiteCollectsEveryTestDirectoryTest` | every top-level `tests/*` dir holding a `*Test.php`, against `phpunit.xml` **and** `phpunit.mysql.xml` | `tests/Planted/PlantedTest.php` → "tests/Planted holds *Test.php files but no <testsuite> in phpunit.xml collects it" |
| 2 | `NoLivewireReservedNamesTest` | public methods *declared by the app* (own or `App\` traits) on every `app/Livewire` class, vs the `var aliases = {…}` map parsed from `vendor/livewire/livewire/dist/livewire.js` (29 aliases on 4.3.1); fails loudly on < 10 aliases or no `commit` | `PlantedGuard::commit()` → "App\Livewire\PlantedGuard::commit() shadows $wire.commit" |
| 3a | `AlpineDirectivesHaveAScopeTest` | all `resources/views/**` except `vendor/` | `<button @click :class>` outside x-data → "planted-alpine.blade.php:2 x-on:click has no x-data root" |
| 3b | (same, component path) | a component with no root of its own is scope-dependent; every render site must be inside a root, transitively | `<x-planted.needs-scope />` outside x-data → "renders <x-planted.needs-scope> (its Alpine needs a root) outside any x-data" (the same tag inside x-data was not flagged) |
| 3c | `AlpineShipsWherePagesUseItTest` | renders every parameterless public GET page (18 pages); any page with `x-data` must carry the Livewire script | `@livewireScripts` removed **and** `inject_assets => false` → "/ renders x-data but loads no Livewire/Alpine script" |
| 4 | `NoDomInsertionInLivewireViewsTest` | `resources/views/livewire/**` **plus every app component those views render, transitively** | `<template x-if>` + `<script>` in a Livewire view → "livewire/planted.blade.php:2 uses x-if" |
| 5a | `SessionMiddlewareIsNotGlobalTest` | `app/Http/Middleware/*` readers (`session(`, `->session()`, `Session::`) vs the registrations in `bootstrap/app.php` | unregistered reader → "PlantedSessionReader reads the session but is not registered on the web group"; `$middleware->append(...)` → "… is registered on the GLOBAL stack"; on `web(append:)` → green |
| 5b | `SessionSurvivesARealRequestTest` | magic-link login (request 1) → `/account` behind `auth:customer` (request 2) carrying **only** the session cookie | second request without the cookie → "Expected response status code [200] but received 302" |
| 6 | `NoFullViewportHeightShellsTest` | `resources/views/**` + `resources/css/**` | `lg:h-screen` and `height: 100vh` → red on both lines; `min-h-screen`, `max-h-[100vh]`, `min-height:100vh` on the same planted file not flagged |

**Allowlists:** none. **Exclusions, with reasons:** 3a skips `resources/views/vendor/` (published
mail header + theme CSS — third-party, no Alpine). 2 ignores methods Livewire's own base class
declares (Livewire's API is Livewire's business). 5a accepts a reader registered as a route
**alias** as well as on `web`: aliases only run on routes, and every app route is in
`routes/web.php`, inside the web group.

**Decisions taken while building (record why):**
- **Guard 3 scope.** A Livewire view's root element counts as an Alpine root — Livewire calls
  `addRootSelector(() => "[wire\\:id]")` (`livewire.js:13892`). On `<x-…>` component tags,
  `:prop` is Blade's prop binding, so only `x-*` and `@event` count there. `@event`/`:attr` need a
  value (`@endif` inside a tag is Blade). Blade comments, `@php`, `{{ }}`, `{!! !!}`,
  `@directive(…)` arguments and script/style bodies are blanked before the walk (keeping
  newlines). The walker counts 129 directives today and must see more than 20, so a broken
  parser can't pass.
- **What a plain Blade page loads.** There's one layout (`layouts/app`), and it loads Alpine
  through `@livewireScripts`. There's also a second path: Livewire 4 auto-injects its assets when
  a component renders, and the footer's `NewsletterSignup` island renders on every page. The
  first plant (removing `@livewireScripts` alone) therefore stayed green — correctly, because
  Alpine still shipped. The plant had to remove both paths.
- **3c in-process trap.** Livewire records "scripts already rendered" in a singleton, so in one
  test only the first page got the script. A real FPM request starts fresh, so the test fires
  Livewire's own `flush-state` hook before each page.
- **5b in-process trap.** The test client shares the session store and the auth guard across
  requests. Proved it: the naive version (array driver, no reset, no cookie) **passes** even
  though nothing went through a cookie (`false-green.md` #6 exactly). So the test uses the
  `database` session driver, then forgets the session drivers, the `session.store` instance and
  the guards between the two requests. The second request carries only the cookie.
- **Guard 4 widened** beyond the prompt's "under `resources/views/livewire/`" to include the
  components those views render, since their markup is morphed just the same. All are clean.

**Gap report.**
1. *Required, not done:* none.
2. *Forbidden, done:* none. The planted violations touched `app/`, `resources/`, `config/` and
   `bootstrap/` temporarily; each was reverted (`git status` clean outside `tests/` + CLAUDE.md).
3. *Not mentioned, done:* guard 4's transitive component scope; the `SourceFiles` test helper;
   two new CLAUDE.md architecture lines (13 reserved names, 14 Alpine scope), because those
   guards had no rule to sit beside. 002's premise again cites `bootstrap/app.php:25` as
   "middleware appended to web": true, but it's `SecurityHeaders`, which isn't a session reader.
   No app middleware reads the session today, so 5a guards the next one.

Tests 369 → 378; `composer check` green; `phpunit.mysql.xml` 378/378 green.

## Email audit (email/audit-pass)
Branch off main `911ba91`. Kit file `audits/email-audit.md` run verbatim (item 3 of unattended run
1). Report: `audits/reports/email-audit.md`, committed before any fix, with the inventory table
first. Verified by doing: the log mailer, the real Redis queue and the real worker (`tries=1`, as
Horizon), with the real controls pressed in the browser. Stripe paths went through the real webhook
handler with a constructed event (no local Stripe keys).

**Phase 1 found and fixed (each failing-first, one commit):**
- **Customer balance payments sent nothing.** No receipt, no owner notification. The webhook only
  acted on held bookings, while the success page promised a confirmation email.
  `HandleCheckoutSessionCompleted` now sends the shared `SendPaymentReceipt` for a paid payment
  against an existing booking. Decided: any paid payment against a booking gets the receipt,
  including a late payment on a hold that already expired, because the money was taken and the
  owner needs to know.
- **Newsletter "Send test to me" never arrived.** The queued mailable carried an unsaved subscriber
  (id 0); the worker threw `ModelNotFoundException` after the UI said "Test sent". It now goes out
  with `sendNow`, and a transport error shows as "Test email failed". The existing
  `test_send_test_does_not_touch_real_subscribers_or_history` asserted `assertQueued`, which encoded
  the bug, so it was re-pointed to `assertSent` and its intent (test address only, no history) kept.
- **No retries.** Horizon has `tries => 1` and no mailable set its own. Added the abstract
  `App\Mail\QueuedMailable` (`tries 4`, `backoff [30,120,600]`, `ShouldQueueAfterCommit`, and
  `failed()` logging the type, never the address). All 8 queued mailables extend it.
  `CourseMessageMail` is exempt because it's sent synchronously inside `SendCourseMessageToRecipient`,
  which got the same rules (tries 3 → 4, plus backoff, after-commit and `failed()`). The sign-in
  link's 20-minute expiry outlives the ~12.5-minute retry window.
- **Sibling voucher path.** `EmailVoucher` is now the single sender for the webhook and the admin
  button.
- **The reschedule claim** follows the result (`RescheduleBooking::handle()` now returns `bool`).
- **Failure visibility:** the `MailHealthOverview` dashboard widget plus `App\Support\MailHealth`
  (failed email jobs in the last 7 days by type; config warnings on non-local servers). Help-guide
  section "Is email working?" added.

**Phase 3:** `php artisan gforce:mail-test {email}` (synchronous, real transport error);
`tests/Feature/Mail/MailInventoryTest.php` (4 rules, each proven by a planted violation: an unused
mailable, a raw `ShouldQueue` mailable, `lang/en` + `lang/cy`, and a new "We have emailed…"
sentence). Two mail-named tests fixed: one asserts the send, one renamed to "renders".

**Decisions recorded:**
- **Locale not pinned.** The app has a single locale (`en`, no `lang/`), so pinning 16 call sites
  would be ceremony. The inventory guard fails the build the moment a second locale appears.
- **Keep CLAUDE.md's "queued and wrapped".** Wrapping stays where the UI claim is about something
  else (a stored enquiry). Where the claim *is* the email (reschedule, voucher, test-send), it
  follows the outcome.
- **Mail logo stays an absolute `APP_URL` image for now.** It'll show broken on staging behind
  basic-auth. CID embedding is follow-up E-2.
- **`List-Unsubscribe` header not added** (needs a POST endpoint): follow-up E-1. The footer
  one-click link works.

~~OWNER DECISION — PENDING~~ **Answered by Ben 9 Oct 2026: option B (toggle, default on) — built in
`feat/admin-email-customer-toggle`.** Should admin-originated acts email the customer the way the online
paths do? (a) Creating a booking already Confirmed sends no `booking_confirmed` (verified by doing).
(b) Vouchers → Redeem sends no receipt. Options: A always send; **B (recommended)** an "Email the
customer" toggle defaulting on, like Reschedule; C never. Not implemented.

**Out of scope, proposed as prompts:**
- **E-1** List-Unsubscribe.
- **E-2** CID logo.
- **E-3** after a paid gift-voucher purchase, the payment-success page shows "Almost there… waiting
  to confirm" indefinitely (no booking).
- **E-4** the owner decision above.

**Gap report.**
1. *Required, not done:* the active health check runs on the dashboard rather than a status page
   (the app has none besides `/up`). E-1 and E-2 are deferred, as above.
2. *Forbidden, done:* none. "Do not change wording except as the fix to a finding": the new strings
   ("Test email failed", "Booking rescheduled — email not sent", "Voucher email failed") are all
   fixes to findings.
3. *Not mentioned, done:*
   - the help-guide section (CLAUDE.md rule for new admin features), with its coverage needle;
   - the course-message job's `tries` raised 3 → 4;
   - an existing false-green test re-pointed (above).
   - The local dev DB now holds audit test records: enquiry `GF-RKHF7B`, bookings `BK-PCIEBU` and
     `BK-KLBJK2`, payments, the subscriber `audit-newsletter@example.test`, and one failed job. All
     are named "Audit …". They were left in place.

Tests 378 → 399; `composer check` green; `phpunit.mysql.xml` green (see run report).

## Admin audit (admin/audit-pass)
Branch off main `39fd24a`. Kit file `audits/admin-audit.md` run verbatim (item 4 of unattended run
1). Report: `audits/reports/admin-audit.md`, committed before fixes. Audited by using the admin: every
settings page was loaded, edited, saved, reloaded and reverted; deletes ran against local data in
rolled-back transactions; a redeemed voucher's status was saved back to Active. A subagent did a
field-by-field code read of every resource, and each P1 it raised was re-checked by doing.

**Settings pages (the kit's singleton check):** all seven work (load on mount, save, notify, persist).
Not a finding, so the base `SettingsPage` is left as it is.

**Fixed (each failing-first, one commit):**
- **Deletion guards.** New `App\Contracts\GuardsDeletion` (a model returns a plain-English
  `deletionBlocker()` or null) and `App\Support\AdminActions::guardedDelete()` / `guardedBulkDelete()`.
  The Delete button is disabled with the reason as its tooltip; Filament refuses a disabled action even
  when called directly (tested); bulk delete deletes only the free records and says how many it kept.
  Applied to Product, Booking, CourseDate, TandemDate, Location, Voucher and Discipline.
  Decided: **no FK change.** `course_dates.product_id` stays `cascadeOnDelete`, because the guard
  stops the UI path. Making it `restrict` is schema, and belongs in its own prompt (A-4 notes it).
- **Voucher:** the Status field is removed (Redeem/Revoke own it); value and product are locked unless
  `isUnusedHandIssued()`.
- **Booking status:** "Awaiting payment" is offered only when the booking is already in it, and the
  field is locked while it is.
- **Disciplines:** `Discipline::TANDEM/AFF/COACHING` constants (now used by `PageController`). The slug
  is locked and Delete is blocked for those three.
- **Validation:**
  - a published news article needs a date;
  - the AFF deposit is required (min 1);
  - capacity has a floor at the current bookings.
- **Sent newsletter:** the form is disabled, Save is hidden, and `beforeSave()` halts. The disabled
  schema alone still saved, which the test caught.
- **Panel:** `AccountWidget` unregistered. Primary `Color::Blue`, **OVERNIGHT-DEFAULT — ANSWERED 9 Oct (see DECISIONS)**:
  white-on-primary measured 5.26:1, against the brand sky's 3.45:1, so the brand colour isn't used as
  a button fill.

**Deferred, with reasons:**
- **A-1** share-image upload: changes a settings consumer across every page.
- **A-2** testimonial crop: two display ratios, so it's a design/owner choice.
- **A-3** owner password reset: a new email path, which should go through the email inventory.
- **A-4** polish batch: upload limits, enquiry sort, unused slugs, raw-ID selects, nav-sort clash,
  public CSS sources, and the cascade FK.

**OWNER questions:**
- Should the email sign-off be editable? (`GeneralSettings::email_signoff` has no field.)
- Is the admin blue OK?
- Testimonial crop (A-2).

**Gap report.**
1. *Required, not done:* the three deferrals above. Every report item has a status (done or
   deferred).
2. *Forbidden, done:* none.
3. *Not mentioned, done:*
   - the guard pattern also covers Tandem dates, Locations and Disciplines;
   - one new string ("has been sent and can't be changed") is paired in `MailInventoryTest`;
   - local dev data: an "Audit Voucher" (redeemed) was created for verification. General settings
     were briefly saved with "AUDIT" by a crashed script and restored to the seeded values (verified).

Tests 399 → 425; `composer check` green.

## Accessibility audit (a11y/audit-pass)
Branch off main `69be8dd`. Kit file `audits/accessibility-audit.md` run verbatim (item 5 of unattended
run 1). Report: `audits/reports/accessibility-audit.md`, committed before fixes. Ran axe-core 4.10.2 in
Chromium on rebuilt assets: 21 public pages at 1440 and 390 px, plus 6 signed-in account pages, with
reduced motion emulated so reveals don't hide text. Then a keyboard pass, a forms pass, and computed
contrast for toasts and inline errors (axe never sees a toast that isn't on screen).

**OWNER DECISION — ANSWERED (Ben, 9 Oct 2026): option B — brand colour contrast.** Implemented on
`ui/primary-strong-contrast` (prompt 016; Ben reviews the screenshots and merges). This was June's
unrecorded item, now recorded.
- **Primary** `oklch(0.62 0.18 240)` = `#008fe6` fails AA two ways: as text on white (3.45–3.5:1:
  active nav, eyebrows, dates, links, `£210 per jump`) and as a fill under near-white button text
  (3.36–3.41:1: every primary button). That's 178 axe nodes, and they're the only remaining axe
  failures.
- **Destructive** `oklch(0.6 0.24 27)` is 4.45:1 as error text on white and 4.32:1 under white text.

Options:
- **A** — darken the tokens site-wide: primary → `oklch(0.545 0.18 240)` / `#0078cc` (4.63 text,
  4.51 fill); destructive → `oklch(0.58 0.24 27)` (4.78 / 4.65). One-line change each in `app.css`,
  but every CTA and the signed-off homepage shift slightly darker.
- **B** — split the token: keep `#008fe6` for rules, borders and decoration, and add a darker
  `primary-strong` for text and button fills. Same compliance, keeps the bright accent, but the
  palette gains a token and needs `ui-guidelines.md` updated.
- **C** — accept the shortfall (non-compliant; record why).

Not implemented either way.

**Fixed (each failing-first, one commit):**
- **Toasts:** white on green-600 was 3.13:1, and the colours were raw Tailwind. They now use navy
  `secondary` (14.2:1) with a primary/destructive left rule (the type isn't conveyed by colour alone).
  The stack is `role=status aria-live=polite`; errors are `role=alert`.
- **One `<h1>`, no skipped levels:**
  - `/testimonials`' featured-hero eyebrow is now the `<h1>`, kept `font-sans`, because base CSS gives
    `h1` the display font.
  - Booking-flow step headings h3 → h2 and h4 → h3.
  - News cards h3 → h2; AFF info cards h4 → h3.
  - Computed styles verified unchanged. `HeadingStructureTest` guards every public page.
- **Booking-field errors:** the error gets an id, and `x-ui.input`/`textarea`/`date-field` pick up the
  field's `error`/`for` via Blade `@aware` and set `aria-invalid` + `aria-describedby`. That's
  server-rendered, so it complies with architecture rule 7; the date field wires both the native input
  and the desktop trigger.
- **Skip link** in the layout; `<main id="main" tabindex="-1">`.

**Left as is, with reasons:**
- Dropdown items show focus as a background tint plus colour change, which is visible.
- Touch targets go to the real-device checks.

**Gap report.**
1. *Required, not done:* the brand-colour contrast (owner decision, per the run rules).
2. *Forbidden, done:* none. No brand colour changed. The toasts moved to existing palette tokens.
3. *Not mentioned, done:*
   - two structural guards (`HeadingStructureTest`, the field-error association);
   - the homepage pixel-diff;
   - local customer sign-in links were issued for the account scan (local DB only).

Tests 425 → 432; `composer check` green.

## Itemised consistency audit (docs/consistency-audit)
Prompt `prompts/003-itemised-consistency-audit.md` (item 6 of unattended run 1). Report-only. Starting
commit `e7b0921` (after the admin and accessibility audits merged). Premise confirmed:
`ui-review/CONSISTENCY.md` didn't exist.
- Method: source enumeration of every component tag and hand-rolled lookalike, then a rendered pass with
  computed attributes for every instance on all 21 public pages at 1440 and 390, plus the 6 signed-in
  account pages, and element crops in `ui-review/consistency-audit/`.
- Result: **15 odd ones out (C-1…C-15; 14 actionable, C-14 record-only).**
- Clean, stated in one line each:
  - every button goes through `<x-ui.button>`;
  - no section-divider lines;
  - no icons on section headings;
  - FAQs, "Meet your team" strips, instructor portraits (1:1), heroes and card corners are all
    consistent.
- The homepage was treated as the reference throughout; no homepage change is proposed.
- The "majority" column in the evidence tables is a raw site-wide signature diff and mixes roles; the
  verdicts are by role. Recorded in the file so nobody mistakes a `≠` for a finding.
- Only `ui-review/CONSISTENCY.md`, `ui-review/consistency-audit/`, the known-gaps section of
  `ui-guidelines.md` and this entry changed.

## Dependency refresh within constraints (chore/deps-refresh-2026-10)
Prompt `prompts/004-dependency-refresh.md`. Branch off main `7e01bfe` (after unattended run 1 ended).
Only the lock files moved; no constraint in `composer.json` or `package.json` changed. Ben asked for the
branch to be merged once green.

**Baseline (before):**
- `composer check` 432/432; MySQL suite 432/432; `config:cache` OK.
- `composer audit`: **37 advisories across 9 packages** (high: Filament < 5.7.0, Guzzle, league/commonmark,
  tiptap-php; medium: a Livewire ≤ 4.3.3 DOM XSS, dompdf; low: Laravel < 13.30).
- `npm audit`: **5 (3 high, 2 critical)** (shell-quote, nanoid, postcss, source-map-js).

**After:**
- `composer check` 432/432; MySQL suite 432/432; `config:cache` OK.
- `composer audit` **none**; `npm audit` **0**.
- The 002 structural guards are green. The Livewire reserved-name guard re-read **29 `$wire` aliases** from
  the 4.4.7 dist (the same set as 4.3.1), so it didn't parse zero.
- Money-path tests are unchanged and green.

**Versions (before → after):**
- laravel/framework 13.14.0 → 13.35.0
- filament/filament 5.6.6 → 5.10.1 (`filament:upgrade` ran from `post-autoload-dump` and republished its
  assets)
- livewire/livewire 4.3.1 → 4.4.7
- laravel/horizon 5.47.2 → 5.50.0
- stripe/stripe-php 20.2.0 → 20.3.1
- sentry/sentry-laravel 4.26.0 → 4.29.0
- spatie/laravel-settings 3.9.0 (no change)
- phpunit 12.5.29 → 12.5.38
- larastan 3.10.0 → 3.13.0
- **Transitive major:** guzzlehttp/guzzle 7.11.0 → 8.2.0 (Laravel 13.35 and resend-php 1.16 both allow
  `^8`). The app's one HTTP-client call (`ResendInboundEmailFetcher`: a string bearer token, Laravel's
  headers) is within Guzzle 8's stricter header rules. Resend's transport declares Guzzle 8 support. It
  can't be exercised without a key locally, so the post-deploy `gforce:mail-test` covers it.
- npm: tailwindcss / @tailwindcss/vite 4.3.0 → 4.3.3; vite 8.0.16 → 8.3.4; laravel-vite-plugin 3.1.0 →
  3.2.0; motion 12.40 → 12.43; @alpinejs/intersect 3.15.12 → 3.17.4; playwright 1.60 → 1.64;
  concurrently 9.2.1 → 9.2.5.
- Published assets: Livewire and Horizon publish none here (Horizon 5.50 inlines its assets;
  `horizon:publish` is now a no-op).

**Upgrade notes that apply.** Every minor's release notes were read: Laravel, Filament, Livewire,
Horizon, Sentry, plus Guzzle's UPGRADING. **None requires an app-code change, a migration or a publish
step.** Checked by doing on the rebuilt app:
- Laravel 13.24 rewrote signed-URL verification. A real signed newsletter-confirm link gives 200; tampered
  gives 403. (Check once behind the production proxy.)
- Livewire 4.4 `wire:loading.attr` now restores the attribute. A contact-form submit re-enabled its
  button, and the toast showed.
- Filament 5.10 reworked date-only/timezone handling. A course-date edit saved and reloaded with
  identical dates (the app is UTC).
- **Noted, no change:**
  - Filament 5.10 names new uploads with `hashName()` (existing paths unaffected; Documents keep the
    original name via `storeFileNamesIn`).
  - Filament table search now treats `% _ ! [` literally.
  - Horizon 5.48.3 actually honours `later()` delays on Redis, which makes the mail retry backoff real.
  - Laravel 13.32–13.35 fixed several passwordless-guard paths (the customer guard).
  - Sentry 4.27–4.29 adds optional config only (`data_collection` defaults to the `send_default_pii`
    behaviour).

**Found while verifying: production needs PHP 8.4.1+, not 8.3.** This was already true of June's lock:
symfony/* 8.1 and spatie/laravel-activitylog 5 require PHP ≥ 8.4.1. `composer.json` still says
`"php": "^8.3"`, and SETUP, PRE-STAGING-CHECKLIST and the FPM-reload line (written in 001) all said 8.3.
An 8.3 server can't `composer install` this lock. Docs corrected in their own commit. **Follow-up:**
raise the `php` constraint to `^8.4.1` and add `config.platform.php` set to the server's real version, so
a dev machine on PHP 8.5 can't lock something production can't run. Not done here; this prompt forbids
constraint changes.

**Majors behind after the refresh (report only):**
- **phpunit/phpunit 12.5.38 → 13.4.1.** Needs PHP 8.4 and removes PHPUnit 12's hard deprecations. Work:
  small (dev only; no `any()` matcher usage found).
- **stripe/stripe-php 20.3.1 → 22.0.0.** 21 made `ErrorObject` properties nullable. 22 pins API
  `2026-09-30.endive`, drops PHP 7.2/7.3, and removes `payment_method_types` from Checkout/PaymentIntent
  create (the app doesn't send it). Webhook signature verification is unchanged. Work: small in code, but
  the API version moves on the money path. ~~OWNER DECISION — PENDING~~ → **decided by Ben 9 Oct:
  upgrade** (done in `chore/deps-majors-2026-10`, below).
- **motion 12.43 → 14.0.0.** Imported nowhere (`resources/js` uses CSS transitions + IntersectionObserver).
  React/react-dom are optional peers. Recommendation: remove the dependency rather than upgrade it.
- **concurrently 9.2.5 → 10.0.6.** Needs Node ≥ 22; only the local `composer dev` script uses it.
  Work: trivial.

**Homepage:** screenshotted before the npm update and after the rebuild, at 1440 and 390, signed out,
reduced motion: **0 differing pixels** at both widths.

**Gap report.**
1. *Required, not done:* none.
2. *Forbidden, done:* none (no constraint or app-code change).
3. *Not mentioned, done:*
   - Step 0 found `RUNNING-ORDER.md` modified and `prompts/004-dependency-refresh.md` untracked on
     main: Ben's own registration of this prompt, committed first on the branch.
   - The PHP 8.4.1 docs correction (SETUP, PRE-STAGING-CHECKLIST).
   - The local dev DB gained one more "Audit Deps" enquiry from the button check.

## Major upgrades at Ben's request (chore/deps-majors-2026-10)
After 004 merged (`86c35dc`), Ben asked to take the four remaining majors too. They're on their own branch
with one commit each, so each can be reverted alone. Constraints in `composer.json`/`package.json` change
here by design.

- **phpunit/phpunit** `^12.5.12` → `^13.4` (13.4.1). Dev-only; needs PHP 8.4 (production already needs
  8.4.1+). PHPUnit's own run reports no deprecations or notices. 432/432.
- **stripe/stripe-php** `^20.2` → `^22.0` (22.0.0). Owner decision taken by Ben. The library now sends API
  version `2026-09-30.endive`. The app's Stripe surface (`StripeCheckout::createSession`: Checkout
  Session create with `mode`, `line_items`, `customer_email`, `metadata`, `success_url`/`cancel_url`,
  `expires_at`; `Webhook::constructEvent`; reading `id`, `url`, `payment_intent`, `metadata`) isn't
  touched by 21/22's removals. A null-key `StripeClient` still constructs and throws
  `AuthenticationException` at call time, which the booking flows rely on. All 117 payment, booking,
  voucher and checkout tests are unchanged and green, but they mock Stripe.
  **Still required before go-live:** one real test-mode checkout plus webhook on staging
  (`verification/CHECKLIST.md` money section). Also set the Stripe webhook endpoint's API version to
  match, or leave it at the account default and confirm the `checkout.session.*` payload fields read are
  present.
- **concurrently** `^9.0.1` → `^10.0.6`. Only the local `composer dev` script uses it; needs Node 22+
  (SETUP updated). Smoke-run OK.
- **motion** `^12.40.0` → `^14.0.0`. Imported nowhere, so there's no bundle or runtime change; React peers
  are optional and weren't installed. If it stays unused, removing it is cleaner. Not removed, because
  Ben asked for the upgrade.

Gates after all four: `composer check` 432/432; MySQL suite 432/432; `config:cache` OK; `composer audit` and
`npm audit` clean; homepage **0 differing pixels** against the pre-004 screenshots at 1440 and 390.

## PHP constraint and platform pin (chore/php-platform)
Prompt `prompts/019-pin-php-platform.md` (unattended run 2, item 0). Closes the two PHP follow-ups from the
dependency-refresh entry.
- `composer.json` `"php": "^8.3"` → **`"^8.4.1"`**: what the lock has needed since June (symfony/* 8.1,
  spatie/laravel-activitylog 5).
- **`config.platform.php` = `8.4.1`.** Ploi's PHP 8.4 is the target, and SETUP names no more exact patch
  version, so this uses the floor the lock needs. Pinning to the floor means Composer only ever picks
  packages that run on any 8.4.x, so a laptop on PHP 8.5 can't lock packages the server can't run.
- `composer update --lock` rewrote only the content hash and the platform block. **All 177 package
  versions are identical** (diffed name@version arrays: 0 changes). `composer validate --strict` passes.
- SETUP now says the server must run PHP 8.4.x and explains the pin. The `php8.4-fpm` reload line was
  already corrected in 004; confirmed, not duplicated.
- Tests 432 → 432; `composer check` and the MySQL suite are green.

## FAQ admin 500: scope collision (fix/faq-scope-collision)
Prompt `prompts/005-faq-admin-500.md` (unattended run 2, item 1). Premise confirmed on current main: with
the 19 seeded FAQs, `Faq::query()->paginate(10)` threw `scopeForPage(): Argument #2 must be of type FaqPage,
int given`.
- **`scopeForPage` renamed to `scopeOnPage`.** It reads naturally (`Faq::active()->onPage($page)`) and
  isn't a method on either builder. The one caller (`SiteContent::faqs`) is updated. Cache keys
  (`faqs.{page}`) and busting are untouched.
- `FaqAdminTest::test_admin_can_list_and_create_faqs` now lists **three FAQs** and asserts they're
  visible. It was red on main with the production error; the empty table was the false green.
- **Guard:** `tests/Unit/Architecture/NoModelScopeShadowsBuilderTest` reflects every model's `scope*`
  methods against the public methods of both builders. Proven red by a planted
  `PlantedScopeModel::scopeLatest()` ("shadows Builder::latest()"), then removed.
- Public output is unchanged:
  - `SiteContent::faqs()` for every `FaqPage`, with the cache flushed, is byte-identical to a pre-fix
    snapshot of the local data (Tandem 8, AFF 8, Coached 3);
  - a new per-page test asserts each page gets exactly its own active FAQs in order.
- Verified by doing: `/admin/faqs` as the owner gives 200 and 10 rows on the first page.
- Tests 432 → 434; `composer check` and MySQL green.

## No known-password admin on servers (fix/no-seeded-admin-on-servers)
Prompt `prompts/006-no-known-admin-login-on-servers.md` (unattended run 2, item 2). Premise confirmed:
`DatabaseSeeder` created `test@example.com` / `password` on every environment, and
`User::canAccessPanel()` returns `true`.
- **`DevAdminSeeder`** (idempotent `updateOrCreate`, same credentials) returns without doing anything
  unless `APP_ENV=local`. `DatabaseSeeder` calls it only behind a `local` guard. `db:seed --force` on
  staging or production seeds content and **zero users**. That's tested for production and staging, and
  the seeder called directly in production also creates nothing.
- **Server admin = Filament's `make:filament-user --panel=admin`**, not a custom command. It's maintained
  by Filament, prompts for name, email and a password (minimum 8 characters, stored hashed), and is already
  installed. A test runs it non-interactively and the new user reaches `/admin`.
- **`canAccessPanel()` stays `return true`.** Every `User` is staff: customers are the separate `Customer`
  model (magic links), and newsletter subscribers and inbound mail have their own tables. Guarded by
  `test_nothing_in_the_app_creates_user_rows`: no `User::create|firstOrCreate|updateOrCreate|factory|…` or
  `new User(` anywhere in `app/` or `routes/`. Proven red by a planted `App\Support\PlantedUserMaker`. A
  future customer-facing `User` would fail that test before it could reach the panel. No schema change, so
  no escalation.
- SETUP "First run" is split into local and server. The "change it immediately" sentence is gone, and
  there's an ops line to delete the old admin from any server seeded before this.
- Tests 434 → 440; `composer check` and MySQL green.

## Sample testimonials off servers; honest review rating (fix/sample-testimonials)
Prompt `prompts/007-sample-testimonials-off-public-site.md` (unattended run 2, item 3). Ben (9 Oct): samples
off. Premise confirmed: `TestimonialSeeder` seeded 8 invented reviews as approved, on every environment,
and `/testimonials` published `AggregateRating` 4.8 from 8.
- **Samples are local-only.** `TestimonialSeeder` returns early unless `APP_ENV=local`. Locally they stay
  approved, so dev pages are populated. Seeding production creates 0 testimonials (red on main).
- **The rating counts real customers only.** `StructuredData::aggregateRating()` keeps approved
  testimonials with a `customer_id` and a rating; the seeded samples have none, so the discriminator is
  correct. **`MIN_REVIEWS_FOR_RATING = 3`, `OVERNIGHT-DEFAULT — ANSWERED 9 Oct (see DECISIONS)`**, so one or two reviews never
  publish a star figure.
  - *Consequence to confirm:* a genuine review the **owner types into the admin** (no customer) shows on
    the page but **doesn't count toward the rating**. That's conservative and deliberate, since a
    self-entered review isn't verifiable.
- **One reader per figure.** The page passes the same collection it renders to `aggregateRating()`. The test
  asserts `reviewCount` equals the number of reviews rendered (`<cite>` count), each against the other.
- **Empty states:**
  - With no approved testimonials, the homepage drops the whole "Voices from the sky" section (no orphaned
    heading). The rendering with testimonials is unchanged: a fresh capture after the change matched the
    pre-change capture with **0 differing pixels** at 1440 and 390.
  - `/testimonials` shows "No reviews yet…" with a "Leave a review" button (`x-ui.button` outline →
    account sign-in), not a bare line. Screenshots:
    `audits/reports/run2/007-home-no-testimonials-{1440,390}.jpg`,
    `007-testimonials-empty-{1440,390}.jpg`. These were rendered by unapproving the 8 local samples for the
    shot and restoring exactly those ids.
- **Re-pointed tests whose premise this prompt reverses:**
  - `TestimonialsTest::test_seeded_content_matches_the_original_static_pages` now seeds as `local` (the
    samples' only environment); its assertions are unchanged.
  - `StructuredDataTest::test_testimonials_has_aggregate_rating_from_real_ratings` now uses three customer
    reviews (reviewCount 3, rating 4.7) instead of two customer-less ones.
- **Measurement note:** the first "after" homepage capture differed from "before" by 174,583 px, all inside
  photographs (first load straight after `cache:clear` and a rebuild). Two fresh captures of the same build
  were identical to each other and to "before". This was an instrument artefact, not a change.
- **Other sample content on the public site (owner content tasks, not changed):**
  - `HallOfFameSeeder`: 8 invented graduates with stock photos.
  - `GalleryImageSeeder`: stock images, repeated.
  - `InstructorSeeder`: real names, stock photo crops (`jay/ren/lee.webp`).
  - `ShopItemSeeder`: sample merch (shop off).
  - `NewsArticleSeeder` / `NewsletterCampaignSeeder`: sample posts and a draft.
- **Owner line:** on any database that's already seeded, unapprove the 8 sample testimonials in the
  admin (rows aren't deleted).
- Tests 440 → 446; `composer check` and MySQL green.

## Admin-originated emails: "Email the customer" toggle (feat/admin-email-customer-toggle)
Prompt `prompts/009-admin-acts-email-the-customer.md` (unattended run 2, item 5). Answers the email audit's
owner decision: Ben chose option B (toggle, default on, as Reschedule does). That owner decision is now
marked answered in the email-audit entry.
- **(a) Admin creates a booking already Confirmed** (by status, or by picking a slot, which confirms):
  - The create form shows "Email the customer" (default on), on create only and only then.
  - When on, `CreateBooking::afterCreate()` calls **`SendBookingConfirmation`**, a new single action.
    `BookingObserver::updated` now delegates to it too, so the confirmation has one sender (no second
    path).
  - When off, nothing is sent. The created notice says "The customer has been emailed." only when it was
    queued; a failure says so.
  - The toggle isn't a column; it's stripped in `mutateFormDataBeforeCreate`.
- **(b) Vouchers → Redeem:**
  - Same toggle (reused label, "Email the customer", default on).
  - When on, it calls **`SendPaymentReceipt`** (the online voucher-covered path's action) with the voucher
    payment. That also sends the owner the `PaymentReceivedAdminNotification`, exactly as the bank-transfer
    and online paths do.
  - `SendPaymentReceipt::handle()` now returns whether it queued (callers that ignore it are unaffected).
  - The notice matches Reschedule's wording.
- **No back-filling:** editing an existing booking (including a confirmed one) never sends from this
  path. The existing status-transition email on edit is unchanged.
- New "emailed" UI strings are paired in `MailInventoryTest`.
- Verified by doing: created a Confirmed booking in the admin, saw "The customer has been emailed.", and the
  worker delivered "Your jump is confirmed" to the customer.
- Tests 446 → 452 (6 new; both "default on" cases red on main). `composer check` and MySQL green.

## Voucher purchase success page (fix/voucher-payment-success)
Prompt `prompts/010-voucher-payment-success-page.md` (unattended run 2, item 6; email audit follow-up E-3).
**Reproduced by doing first:** a voucher-purchase payment was taken through the real `HandleStripeWebhook`
(paid; voucher `GV-YWL5VYHL` issued), but `/payment/success?session_id=…` showed "waiting to confirm your
card payment".
- `PaymentSuccessPage::viewData()` also returns `voucher`: the voucher whose `payment_id` is this payment,
  only for a **paid** `VoucherPurchase`. It's reached only through the payment the visitor's own Stripe
  session id resolves (the same strategy as bookings), so another customer's voucher is unreachable
  (tested with two vouchers plus an unknown session).
- New view branch "Gift voucher bought":
  - card: product, value, recipient, valid-until, paid;
  - "What happens next": the voucher (code + PDF) is being emailed to the purchaser, how it's used, and
    what to do if it doesn't arrive.
  - Built from the booking confirmation's own markup.
  - The voucher **code is deliberately not shown** on the page: a URL can be shared, and the email
    carries the code.
- An unpaid voucher payment keeps the existing "waiting" state. Booking pages, payment handling and the
  webhook Actions are unchanged. New UI claims are paired in `MailInventoryTest` (`VoucherGiftMail`).
- Screenshots: `audits/reports/run2/010-voucher-success-{paid,waiting}-{1440,390}.jpg`.
- Tests 452 → 455; `composer check` green.

## Mail logo embedded inline (CID) (fix/mail-logo-cid)
Prompt `prompts/011-cid-embedded-mail-logo.md` (unattended run 2, item 7; email audit E-2). Premise
confirmed: both `vendor/mail/html/header.blade.php` (transactional) and `mail/blocks/logo.blade.php`
(newsletter block) hot-linked `url('/images/email/logo.png')`.
- **One logo partial**, `mail/partials/logo-img.blade.php`, used by both. Same PNG, 180×68, same alt and
  inline style. It always points at `cid:gforce-logo` (`App\Support\MailLogo`).
- **`App\Mail\Concerns\EmbedsMailLogo`** (on `QueuedMailable` and `CourseMessageMail`, i.e. every
  mailable):
  - It overrides `buildAttachments()`. When the outgoing HTML references the logo, it adds the PNG as an
    inline `DataPart` named `gforce-logo`, and Symfony rewrites `cid:gforce-logo` to the part's real
    Content-ID.
  - A newsletter without a logo block gets no stray attachment (tested).
  - The PNG stays outside image optimisation.
- **Previews:** the admin builder's Preview and `/dev/mail` have no message to embed into.
  `EmbedsMailLogo::render()` swaps the `cid:` for the public URL, so previews (and `MailRenderTest`'s
  absolute-URL checks) still show the logo. Sending never calls `render()`.
- **Newsletters** freeze `rendered_html` at send; the frozen HTML holds `cid:gforce-logo`, and the
  per-recipient send embeds it.
- **Re-pointed test:** `BuilderTest::test_logo_block_renders_with_an_absolute_url_and_alt` →
  `…_renders_the_embedded_logo_with_alt` (asserts `cid:`, no hot-link). This prompt reverses the
  absolute-URL decision it encoded. Its other assertions are unchanged.
- **Verified by doing:**
  - All 7 email templates were sent through the log mailer, each with an inline `Content-ID` PNG part.
  - `audits/reports/run2/011-booking-confirmed.eml` is the real booking confirmation: its `<img>` points at
    the attached part's Content-ID, with no hot-linked URL.
  - The `/dev/mail` preview shows the logo URL.
- **Owner check after staging:** one real email in Gmail and one in Outlook shows the logo with remote
  images blocked.
- Tests 455 → 458; `composer check` green.

## CMS orphan fields resolved (chore/cms-orphans)
Prompt `prompts/012-cms-orphan-fields.md` (unattended run 2, item 8). Ben's decisions (9 Oct): re-surface the
Location address, remove `Product::duration`. Each field was re-confirmed unused publicly before removal.
`audits/reports/cms-field-usage.md` §2 fields are untouched.
- **Location address → course Event JSON-LD.**
  - New `StructuredData::place(Location)`: a `PostalAddress` from whichever of `address_line`, `town`,
    `region`, `postcode`, `country` are filled, and `GeoCoordinates` only when both `lat` and `lng` exist.
  - With nothing filled, `address` is omitted. It's never the location name again: `courseEvent()` used to
    put the name in `address`, which was wrong data.
  - Today only `region` and `country` are filled, so the events carry those.
- **`Product::duration` removed** (form, `$fillable`, factory, seeder, and the column via
  `2026_10_09_120000_drop_orphaned_cms_columns`).
  - **Its only value, recorded so it isn't lost:** Tandem Skydive = *"Approx. half a day at the dropzone"*.
- **`HomePageSettings::team_lead` removed** (admin field, property, and settings migration
  `2026_10_09_120100_remove_home_team_lead`, the June pattern). Value was *"The people you'll fly with."*
  **OVERNIGHT-DEFAULT — ANSWERED 9 Oct (see DECISIONS)** (Ben confirmed this field by name).
- **`Location::image` removed** (admin field, `image_url` accessor, `$fillable`, `ImageOptimization`
  entries, and the column in the same migration). It was empty on all 4 locations; no files touched.
  **OVERNIGHT-DEFAULT — ANSWERED 9 Oct (see DECISIONS).**
- **Migrations on a seeded copy, both drivers:**
  - MySQL: a `mysqldump` clone of the dev DB was migrated. Row counts were unchanged (4 products,
    4 locations, 3 bookings, 5 payments), the columns and `home.team_lead` were gone, rolled back cleanly,
    and the clone was dropped.
  - SQLite: a file DB seeded at the pre-012 schema, then migrated the same way.
- **Stale cache:** a cached `HomePageSettings` payload that still carries `team_lead` unserialises without
  error (tested), and the deploy clears the settings cache anyway.
- **Homepage:** pixel-identical before/after at 1440 and 390 (a repeat "before" capture was identical to
  the first, so the instrument is stable). Crops: `audits/reports/run2/012-home-team-band-after-*.jpg`.
- **Test re-pointed:** `HomePageSettingsTest` asserted `team_lead` exists. It now lists it among the removed
  keys; its intent (removed settings stay removed) is unchanged.
- **Local dev:** Ben's dev DB isn't migrated by this run (the brief says leave it). Run
  `php artisan migrate`. The code works either way.
- **Owner content:** enter real dropzone addresses and coordinates in Locations. Until then the events
  carry region and country only.
- Tests 458 → 462; `composer check` and MySQL green.

## Homepage SEO title/description from the admin (fix/home-seo-settings)

Prompt 013, the CMS-field gate's top reverse-drift item: `home.blade.php` hardcoded the `<title>` and meta
description, so neither the owner nor General's "Default page title" could change the homepage's.

- **Decision (b): Home gets its own fields** (`HomePageSettings::seo_title` / `seo_description`), edited in
  a "Search engines & sharing (SEO)" section at the top of Site content → Home page. This matches
  Tandem/AFF/Coached and the `SimplePagesSettings` pages.
  - **Why not (a), the General default:** the homepage title is the brand line, while the site-wide default
    is the fallback for pages that set none (e.g. `/news`). Tying them together would turn every edit to
    the fallback into a homepage SEO change. A test pins it: editing General's default leaves the homepage
    alone.
- **No change on deploy:** settings migration `2026_10_09_130000_add_home_seo` seeds exactly the two former
  literals. The rendered `<head>` (title, description, OG and Twitter) was byte-identical before/after
  locally, and the homepage was pixel-identical at 1440 and 390 (two "before" and two "after" captures each,
  all 0 px).
- **Read through accessors with a fallback:** `seoTitle()` / `seoDescription()`, the `emailSignoff()`
  pattern. An empty value or a stale cache that predates the properties renders the old literals (kept as
  `DEFAULT_SEO_*` constants). The values go through `@section('title', …)`, which escapes them once, as the
  layout's `{!! !!}` convention expects (tested with `&`).
- **Gap fixed in passing (left by 012):** removing `team_lead` left an empty "“Meet the team” teaser"
  section on the Home settings screen, a header with nothing in it. It's removed, and a structural guard
  (`NoEmptyAdminSectionsTest`) now fails on any `Section::make()` whose children list is empty. Field-less
  newsletter Builder *blocks* (divider, automatic news) are deliberate and not checked.
- **Help guide:** one line in "Editing page content & images" saying where the homepage title is edited.
- **Local dev:** adding a settings property without its stored row makes spatie throw `MissingSettings`
  (the dev homepage 500'd). So this one additive migration was applied to the dev DB with `--path`, which
  adds two settings rows and touches no records. 012's column drops are still pending there for Ben's
  `php artisan migrate`. On servers the deploy runs `migrate` as usual, with the same brief window as any
  settings addition.

## Price tokens in CMS wording (feat/price-tokens-in-copy)

Prompt 014, consistency audit C-7. Product prices go through `Money`, but the same figures were also typed
into CMS text, so changing a product price left those sentences quoting the old one.

### Inventory: every hand-typed price in CMS text, seeders and settings

| Where | Value | Backing data? | Now |
|---|---|---|---|
| Tandem FAQ "Can I jump for charity?" | £260 | ✅ Product `tandem-skydive` price | `{price:tandem-skydive}` |
| Tandem settings: `seo_description`, `hero_subtitle`, `charity_note_body` | £260 ×3 | ✅ same | `{price:tandem-skydive}` |
| Tandem FAQ "photos or video" | £140 / £100 | ✅ add-ons Outside Camera / HandCam | `{addon:outside-camera}` / `{addon:handcam}` |
| Tandem FAQ "weather is bad" | £50 | ✅ add-on Rebooking Fee | `{addon:rebooking-fee}` |
| Terms (`simple_pages.terms_body`) | £24.73, £50 | ✅ add-ons P6 Third Party Insurance / Rebooking Fee | `{addon:p6-third-party-insurance}` / `{addon:rebooking-fee}` |
| AFF FAQ "How much does it cost" | £1,750 / £600 | ✅ Products `aff-course` / `consolidation-jumps` | `{price:aff-course}` / `{price:consolidation-jumps}` |
| AFF settings `seo_description` | £1,750 | ✅ `aff-course` | `{price:aff-course}` |
| Coached settings `price_eyebrow`, `seo_description` | £60 ×2 | ✅ Product `coached-skills` | `{price:coached-skills}` (price only; the eyebrow's wording is C-1, still open) |
| Tandem FAQ "weight and age limits" | £20 / £40 / £60 | ⚠️ only as **text** in `Product::weight_charges` (`"charge": "£20"`), not money | typed — **OWNER DECISION — ANSWERED 9 Oct (see DECISIONS)**: stays typed |
| `Product::weight_charges` (Tandem weight table) | £20 / £40 / £60 | the same text | typed (it is the table itself) |
| AFF FAQ "membership" | ~£125/year | ❌ British Skydiving's price, not ours | typed, owner content |
| AFF FAQ "Is kit provided?" | ~£5 packing | ❌ none | typed, owner content |
| `Product::repeat_pricing` (AFF price card) | £210 / £140 per jump | ⚠️ text on the product; quoted nowhere else | typed; one place, so it can't drift |
| Shop `price_label`s, Hall of Fame "£3,200 raised", a testimonial "£1,000" | — | not our prices | out of scope (C-15 for shop) |

### How it works

- **Syntax:** `{price:<product slug>}`, `{deposit:<product slug>}`, `{addon:<add-on name, slugified>}`.
  - Single braces, so it can't collide with email templates' `{{ name }}` placeholders or with Blade.
  - Slugs, not product *type*: AFF has two products (course + consolidation), so `{price:aff}` would be
    ambiguous.
  - The add-on key is its slugified name because add-ons have no slug column and the rules forbid new
    columns. Renaming an add-on breaks its token; the preview shows it. The product slug field's helper
    text now warns about the same for products.
- **Resolution:** `App\Support\PriceTokens::render()` at render time, from `SiteContent::priceTokens()`.
  That's a cached map of plain integers (active products and their add-ons), busted with the product and
  add-on keys. The cached arrays stay plain; no model is cached.
- **Where it applies:**
  - FAQ answers: `Faq::answerHtml()` in the accordion and `Faq::plainAnswer()` in the FAQPage JSON-LD, so
    the schema carries the rendered price.
  - Tandem / AFF / Coached: page description (meta, OG and the Product JSON-LD description) and hero
    subtitle.
  - The Tandem charity note, the Coached price line, and Terms.
  - These are exactly the fields that quoted prices. Each uses `AdminPriceTokens::field()` in the admin, so
    the admin only advertises tokens where the page resolves them.
- **Public fallback for an unknown token:** "price on enquiry", the wording the site already uses for an
  unpriced product, plus a log warning.
  - Never the raw token, and never a guessed figure.
  - Detection is loose (`{ Price : x }`, an unknown slug, a known product with no deposit), so near-misses
    can't leak braces.
- **Admin preview, not a save lock:** each token field shows a live "Preview:" line, with known tokens in
  bold and unknown ones highlighted "⚠ Unknown price {…}". Verified in a real browser in the FAQ rich
  editor.
  - A save-blocking rule was built and then removed: hiding or renaming a product later would have stopped
    the owner saving *anything else* on that settings page.
- **Fresh installs:** `FaqSeeder` and the two original settings migrations now carry the tokens. They render
  byte-identical to the old literals because a fresh install seeds the products. Two render tests now seed
  products in their setup; their exact-text assertions are unchanged.
- **Existing databases are owner content:** nothing there is rewritten. Ben's list (from the dev DB, same
  as the seeds): FAQs tandem #4, #5, #8 and aff #12; Tandem `seo_description`, `hero_subtitle` and
  `charity_note_body`; AFF `seo_description`; Coached `seo_description` and `price_eyebrow`; Terms. Swap each
  figure for the token in the table above, and the preview confirms it.
- **OWNER DECISION — ANSWERED 9 Oct (see DECISIONS) (weight surcharges): (a), keep both typed for now.** the £20/£40/£60 bands are typed twice, in the Tandem
  weight table (text on the product) and the weight FAQ. Options:
  - (a) keep both typed, and the Help guide reminds the owner to change both;
  - (b) make the weight bands money (pence) on the product, with a `{weight:…}` token.

  (b) needs a schema change the rules forbid here. Neither was implemented; (a) is what's live.
- **Help guide:** a new "Prices in your wording" section covers the tokens, where they work, typos, and the
  still-typed prices to update by hand.
- Homepage pixel-identical (1440/390, 0 px, against the 013 baseline).

## Consistency small fixes C-3, C-4, C-12, C-13 (ui/consistency-small-fixes)

Prompt 015. Four odd ones out from the itemised consistency audit move onto shared styles, one commit each.
No shared component existed for the first three, so two small ones were made rather than more inline copies.

- **`<x-ui.meta-label>`** (new): the one small uppercase label. `text-xs` bold, `tracking-[0.25em]`, in a
  palette tone: `primary` / `sky-bright` / `current`.
  - **C-3, `/news`:** the article dates (`0.2em`) and the "G-Force News" image label (`0.3em`, the only one
    on the site) now use it at `0.25em`. Only `/news` changes.
  - The homepage news dates (the reference) already had exactly these classes and **were left inline**: the
    homepage is signed off and the prompt says to change `/news` only. The component's docblock and
    `ui-guidelines.md` say so.
  - **C-4, testimonial grid:** role labels go from 400 / `0.2em` / `white/70` to the shared 700 / `0.25em` /
    `sky-bright`. `<x-site.instructor-card>`'s role label (which already had those values) now renders
    through the component too, pixel-identically.
- **`<x-ui.loading-label>`** (new), **C-12:** the idle/loading swap inside a Livewire submit button.
  "Sending…" is defined once there. Contact, Tandem, AFF and Coached enquiry forms all use it; "Sending..."
  is gone. `SendingLabelTest` asserts every enquiry form renders it. The booking and voucher forms' "Sending
  your request…" / "Taking you to secure payment…" are different actions and are out of scope.
- **C-13:** the Tandem and AFF intro wrappers swap `py-16 lg:py-24` for `py-section-sm lg:py-section` (same
  4rem / 6rem).
- **Pixel checks (full page, 1440 + 390):** `/tandem`, `/aff`, `/meet-the-team` and `/contact` were 0 px.
  The homepage was 0 px.
  - **Instrument fix:** a first 390 homepage comparison showed 2,592 px. It was the below-the-fold
    testimonial avatars (`loading="lazy"`) not yet loaded in one capture, not a markup change. Homepage
    captures now force every image to load before the shot. With that, before (code stashed) and after were
    0 px at both widths, twice each. Use the eager-image step for any future full-page comparison.
- **Crops:** before/after JPEGs of `/news`, `/testimonials` and the contact form's loading state at 1440 and
  390 are in `ui-review/consistency-small-fixes/`.
- `ui-guidelines.md`:
  - catalogue: both components, plus two rows in the cross-page table;
  - known gaps: C-3, C-4, C-12 and C-13 removed, and C-7 too (fixed by 014, which missed this list);
  - C-5 notes that the new component is the likely fix.

## Brand contrast: `primary-strong` for text and fills (ui/primary-strong-contrast) — merged in run 3

Prompt 016, Ben's answer to the contrast decision: **option B**. This is the authorised exception to the
homepage freeze, for colour only.

- **Tokens:**
  - `--primary-strong: oklch(0.545 0.18 240)` (`#0078cc`) plus the Tailwind colour `primary-strong`.
  - `--primary` is unchanged (`#008fe6`).
  - `--destructive` is darkened **in place** to `oklch(0.58 0.24 27)` (4.80:1 as text, 4.68:1 as a fill).
    Reading of "option B": the split exists to keep the bright *brand* accent, and the error red isn't one,
    so a second red token would only add a token.
- **The rule:** anything read at normal size, and any fill under text, moves to `primary-strong`. Decoration
  stays on `primary`: rules, borders, focus rings, icons, checkbox accents, icon-only fills (non-text needs
  3:1; primary is 3.37–3.46:1) and star glyphs.
  - Done at the shared sources first: `<x-ui.button>` primary fill and link text, `<x-ui.arrow-link>`, the
    `<x-site.section-heading>` eyebrow, `<x-ui.meta-label>`, header and account nav active/hover, FAQ and
    news-body links, booking step, date-field selection.
  - Then the remaining page-level text.
  - Inventory: `audits/reports/primary-usage.md` (183 uses: 55 moved, 11 large-display stay, 2 dark-surface
    hovers stay, the rest decoration).
- **Large display text (≥24px) stays `primary`:** WCAG large text needs 3:1 and primary measures 3.46:1.
  These are the Tandem price table, price-card figures, account balances, 404 and the quote glyph.
  - **OVERNIGHT-DEFAULT — ANSWERED 9 Oct (see DECISIONS): yes.** This keeps the bright accent on the big figures, per option B's intent.
    The cost is that small prices (e.g. AFF "£210 per jump", 20px) are now the deeper blue while ≥24px
    prices stay bright. The alternative is moving those 11 to `primary-strong` too (still compliant).
- **Dark surfaces keep `primary`:** `primary-strong` is only 3.16:1 on navy, against 4.21:1 for primary.
  Allowlisted with reasons: the contact panel's hover links, and the section-heading eyebrow when
  `light=true`.
- **Premise correction: the fill is 4.49:1, not 4.51.** The prompt's nearest passing shade "4.51 as a fill"
  holds against pure white, but `--primary-foreground` was `oklch(0.99 0 0)` (`#fcfcfc`). axe measured every
  primary button at **4.49:1** and flagged it.
  - **OVERNIGHT-DEFAULT — ANSWERED 9 Oct (see DECISIONS): yes.** `--primary-foreground` set to `oklch(1 0 0)` (white, already the
    palette's `--background`/`--card`) gives 4.61:1. That's no new colour and visually indistinguishable.
  - The alternative is a slightly darker `primary-strong` (e.g. `oklch(0.54 0.18 240)`), which would leave
    the prompt's value.
- **Message bubble:** the customer's "You" label was `text-primary` on the `sky-bright/10` tint (3.15:1, the
  audit's "below 3:1" item). `primary-strong` is still only 4.19:1 there, so it is now `text-secondary`
  (navy).
  - **OVERNIGHT-DEFAULT — ANSWERED 9 Oct (see DECISIONS): yes.** Both labels are now navy; the bubbles stay distinct by alignment,
    border, tint and the label words.
  - Measured by calculation: axe wasn't run on the thread page, because opening it could mark the dev
    DB's Audit customer's messages read.
- **Hover/active button states, not in axe's reach:**
  - Hover `primary-strong/85` is 3.54:1 and active `/75` is 2.99:1, against 2.80 / 2.48 before. Better,
    but under 4.5.
  - **OWNER DECISION — ANSWERED 9 Oct (see DECISIONS): (b), darken on hover — built in 020.** (a) keep the lighter hover wash as designed; or (b) hovers darken instead
    (e.g. to `secondary` navy), which is a visual change. Neither implemented.
- **axe-core 4.10.2** (same version as the audit), WCAG 2.0/2.1 A+AA plus best practice, signed out, reduced
  motion, fonts loaded, injected into the real pages:
  - **Before:** 173 `color-contrast` nodes over 20 pages × 1440/390 (every one `#008fe6`: 99 as a fill,
    74 as text). The audit's 178 was over 21 pages.
  - **After:** **0 violations** of any rule.
  - Signed-in account pages (5 × 2 widths): 0 contrast failures.
  - One unrelated finding: `scrollable-region-focusable` on the 390 payments table, which needs
    `tabindex`/a label. That's keyboard access, not colour; a follow-up, not fixed here.
- **Guard:** `PrimaryIsNotUsedForTextTest` fails on `text-primary`, or on `bg-primary` under
  `text-primary-foreground`/`text-white`, in any Blade view.
  - Exempt: icons/svg, checkboxes, icon-only fills, large display, plus a two-entry reasoned allowlist.
  - It's red on the pre-change views (84 violations); a planted-violation test and a decoration test
    prove both directions.
- **Emails:** they don't use `#008fe6`. Buttons, links and accents use their own `#2f8de4` (13 uses:
  `vendor/mail/html/themes/gforce.css`, `mail/blocks/button|two_column|featured_course`, newsletter
  shell/footer). That's 3.47:1 both as text and under white button text, below AA. **Listed, not changed
  here** (mail has its own render check); a follow-up should take `#0078cc`.
- **Not in scope:** the Filament admin (Blue since the admin audit).
- **Screenshots:** `ui-review/primary-strong/` — homepage hero and news, Tandem pricing, AFF price card,
  Coached intro, the contact form, and the account dashboard, each at 1440 and 390, before and after.
## Launch checklist tailored to G-Force (docs/launch-checklist)

Prompt 018. `verification/CHECKLIST.md` was the kit's untailored template. It keeps the kit's structure
and non-negotiables (money/email/silent-killer sections are the hard gate; `APP_KEY` off-server; no
`key:generate` in deploy; restore tested; clean dataset; one real low-value transaction then refund), with
every generic item replaced by G-Force's real one. Every name was grepped. The "NOT YET TAILORED" line is
gone.

- **New §0, staging specifics:**
  - the site-email setting pointed at a test inbox;
  - the `MAIL_MAILER` choice;
  - basic-auth and noindex, both at the server;
  - the TEST webhook with exactly the two handled events and the `2026-09-30.endive` API version (and
    where the dashboard shows it);
  - `gforce:mail-test`;
  - the dashboard's "Failed emails (last 7 days)";
  - `/dev/*` must 404.
- **§1 money table:** every path enumerated from the code — tandem, tandem with a full or partial voucher,
  AFF deposit, AFF balance from the account, voucher purchase, admin Redeem, payment link, bank transfer.
  - Each row has the expected Stripe amount in pence → £ from the seeded prices (with "read the live
    prices first") and the emails that must arrive.
  - **Refunds: none in the app.** The checklist says so instead of inventing a path.
- **§2 email table:** one row per email-audit inventory row, each with its real trigger on staging and its
  Reply-To.
  - Reply-To was read from each mailable: `TemplatedMail`, the login link, newsletter confirmation and the
    owner payment notification set none, so replies go to `MAIL_FROM_ADDRESS`. The checklist says that
    inbox must be read.
- **§6b devices:** the phone subset of `real-device-checks.md`, with the dropped tablet/counter items named
  and why.
- **§7:**
  - the three real scheduled commands, with what proves each ran;
  - Horizon, `storage:link`, `config:cache` / `settings:clear-cache`;
  - the production admin via `make:filament-user --panel=admin`, with no `test@example.com`.
- **Gaps found while tailoring (recorded, not fixed; docs-only prompt):**
  - **Staging noindex isn't in the app.** `/robots.txt` allows crawling on every host and no `noindex` is
    sent by environment, so it must be an nginx `X-Robots-Tag` in Ploi. A code fix
    (`Disallow: /` + a `noindex` meta when `APP_ENV` isn't production) would be a small follow-up prompt.
  - **Basic-auth must exempt `/webhooks/stripe` and `/webhooks/resend`**, or staging webhooks 401.
  - **There's no Users screen in the admin**, so "no `test@example.com`" is checked with a tinker one-liner.
  - Rescheduling has no capacity check against the target slot. It isn't listed as a refusal to test,
    because the code doesn't refuse it. Worth an owner question if over-filling a slot by rescheduling
    matters.

## Ben's answers to run 2's owner questions (9 Oct 2026) — docs/run3-owner-answers

Ben read `RUN-REPORT-2.md`, the 008 proposal and the 016/017 screenshots, and answered every question
(`prompts/unattended-run-3.md`). Each answered `OWNER DECISION — PENDING` / `OVERNIGHT-DEFAULT — CONFIRM`
marker now reads `… — ANSWERED 9 Oct (see DECISIONS)`, pointing here.

- **008 — FK delete rules:** the table is approved **as proposed**: the 13 FKs → `RESTRICT`, **plus #26
  (the documents pivot) → `RESTRICT`**, with `Document` joining `GuardsDeletion` ("attached to N sent
  messages"). The model-level `deleting` listener on every `GuardsDeletion` model is approved. Built in
  run 3 (Phase 2).
- **016 — brand contrast `primary-strong`:** merge it.
  - Pure-white `primary-foreground`: **yes**.
  - Display prices of 24px and up stay bright `primary`: **yes**.
  - The customer's "You" label in navy: **yes**.
  - Hover and active states: **darken on hover**, not the lighter wash (prompt 020).
- **017 — feature-split 16:10: withdrawn, not merged.** The 1024 imbalance (the text running well past the
  photo) is worse than the desktop side-trim. The admin crop stays 16:10; the layout stays as on `main`.
- **007:** at least 3 real customer reviews before a rating is published, and owner-typed reviews show
  but don't count: **confirmed**.
- **012:** removing `home.team_lead` and `Location::image`: **confirmed**.
- **014:** the weight surcharges stay typed for now (option (a)).
- **Rescheduling into a full tandem slot must be refused** (prompt 021).
- **App-level noindex for non-production hosts:** yes (prompt 022).
- **Email buttons and links move to the accessible blue `#0078cc`:** yes (prompt 023).
- **Keyboard focus on the scrollable payments table:** yes (prompt 024).

Run 1's answers (RUNNING-ORDER row 8) had already closed two markers that were still worded as open: admin
panel primary **Blue** and motion ambition **subtle**. Both are flipped here too, as are the
accessibility audit's brand-contrast rows (answered by 016) and the email audit's heading (option B,
built in 009). The 016 and 008 markers live on their branches and are flipped when each merges in run 3.

## The database refuses money-linked deletes — 008 Phase 2 (fix/fk-delete-rules)

Prompt 008 Phase 2, built on Ben's approval (9 Oct 2026, entry above): the table in
`audits/reports/fk-delete-rules.md` exactly as proposed, plus #26.

- **Migration `2026_10_09_140000_restrict_money_linked_deletes`:** 14 foreign keys → `RESTRICT`: #1, 2, 3, 7, 8,
  9, 10, 11, 15, 16, 17, 19, 20 and #26. The other 18 are unchanged. Only the delete rule changes; no column, row
  or index is touched. `down()` restores each previous rule.
  - **Pre-check:** before changing anything, it counts orphaned values in every affected column and aborts with
    the list (`table.column → parent: N row(s)`). Proven by planting one orphaned payment in a MySQL clone: it
    aborted and changed nothing.
  - **Driver detail:** MySQL drops each FK by its real name, read from `Schema::getForeignKeys()`, because
    `bookings.tandem_date_id`'s constraint is still called `bookings_availability_slot_id_foreign` from its old
    column name; the conventional name would have failed. It's re-added under the conventional name. SQLite
    drops by column (Laravel rebuilds the table).
  - **Seeded copies:** a `mysqldump` clone of the dev DB (4 products, 2 course dates, 3 bookings, 5 payments,
    2 vouchers, 2 enquiries, 2 customers) migrated, rolled back and re-migrated with identical row counts, 2 → 16
    RESTRICT rules and back. A seeded SQLite file with a booking, paid payment and voucher added: same rows
    after the rebuild, `pragma foreign_key_check` clean, rolled back cleanly.
- **Model layer:** the `RefusesGuardedDeletion` trait on all eight `GuardsDeletion` models (Product, Booking,
  CourseDate, TandemDate, Location, Voucher, Discipline, Document) throws `DeletionBlockedException` with the
  record's own `deletionBlocker()` text. The button explains, the model refuses, the database refuses.
  `GuardsDeletion` and `guardedDelete()` / `guardedBulkDelete()` are unchanged.
- **`Document` joins `GuardsDeletion`:** "Attached to N sent message(s)…". Its table and edit-page Delete now use
  `AdminActions::guardedDelete()`, and the Help guide's course-communications section says why.
- **Gap report — one blocker changed to tell the truth:** `TandemDate::deletionBlocker()` counted only
  non-cancelled bookings, but the approved #7 RESTRICT refuses on any booking. A slot with only cancelled
  bookings would have shown an enabled Delete that then failed with a database error. It now says "N cancelled
  booking(s) still record this date, so it stays as history." That's the reason text only; the prompt's "leave
  `GuardsDeletion` as it is" refers to the interface and the helpers, which are untouched. The other blockers
  already matched their FKs (Product counts all four children; Booking counts payments and vouchers; CourseDate
  counts all bookings; Location both date types).
- **Not given a listener:** Customer, Enquiry and Payment are RESTRICT parents with no delete path; the DB rule is
  their backstop, as the proposal said.
- **Tests:**
  - `MoneyLinkedDeletesTest`: product with course dates, booking with payment, sent document, slot with only
    cancelled bookings, each refused at the model and, with the model bypassed (`deleteQuietly()` / raw query),
    at the database; records nothing hangs off still delete; erasure on a customer with a paid booking.
  - `GuardedParentsRestrictDeletesTest` walks every FK into a guarded table: each is RESTRICT or allowlisted with
    a reason (#4, 5, 6, 12, 27), and every guarded model uses the listener. Both checks are proven with planted
    violations.
  - Red without the change: without the migration, 7 of 12 fail (the database allowed each delete, and the guard
    listed 9 non-restrict FKs); without the listener, the 4 model tests fail with a raw SQL error instead of the
    plain-English refusal.
  - `DeletionGuardsTest` adds the sent document and the cancelled-only slot.

## Button hover and press darken (ui/button-hover-contrast)

Prompt 020, Ben's answer to 016's hover question (9 Oct 2026): **darken on hover**.

- **Premise confirmed on `main`:** the primary variant was `hover:bg-primary-strong/85 active:bg-primary-strong/75`,
  opacity washes that lighten the fill toward the page. White on them is **3.64:1 / 3.09:1** by the
  oklch→sRGB maths here (016's gap report said 3.54 / 2.99, measured against the old `#fcfcfc` text). The
  **link** variant had the same fault on press: `active:text-primary-strong/80` is **3.35:1** on white.
- **Tokens** (next to `--primary-strong`, plus Tailwind colours): `--primary-strong-hover: oklch(0.50 0.18 240)`
  (`#0069bd`, **5.56:1** with white) and `--primary-strong-active: oklch(0.46 0.18 240)` (`#005db0`, **6.57:1**),
  the prompt's suggested shades.
  - Primary: `hover:bg-primary-strong-hover active:bg-primary-strong-active`.
  - Link: press → `active:text-primary-strong-active`; hover stays an underline in the rest colour (4.6:1).
  - **Outline unchanged:** its `current/10` and `/20` washes measure ≥ 9.7:1 on white (navy text) and ≥ 8.1:1
    on navy, ink and sky-deep (white text).
- **Also fixed:** `pages/newsletter-status.blade.php` passed its own colour classes to `<x-ui.button>`
  (`… hover:bg-primary-strong/90`, a fourth failing wash, and against the "no colour classes on a button" rule).
  They were identical to the variant at rest, so removing them changes nothing at rest.
- **Rest is pixel-identical:** full-page homepage at 1440 and 390, before vs after, **0 px** each. Transitions and
  `motion-reduce:transition-none` unchanged.
- **Test:** `ButtonStateContrastTest` reads the variant classes from the component and the tokens from
  `app.css`, composites any opacity wash over its surface as the browser paints it, and asserts ≥ 4.5:1 for each
  variant's hover and press on light and dark surfaces. Red on `main` with exactly the three failures above, and
  a planted wash proves it. Its maths reproduces `#0078cc` from the token.
- **Screenshots:** `ui-review/button-hover-contrast/` — the home hero CTA, the contact submit, and a link button
  (news article), each at rest, hover and press, before and after (cropped JPEGs, 1440).
- **Measurement note:** the first "before" capture was taken on a mixed tree (the branch's Blade classes, `main`'s
  CSS build), so no hover rule matched and before looked like rest. It was discarded and re-taken with the
  branch stashed and `main` rebuilt.

## Rescheduling respects tandem slot capacity (fix/reschedule-capacity)

Prompt 021. Ben, 9 Oct 2026: rescheduling into a full tandem slot **must be refused**.

- **Premise confirmed** by code read and by a failing test on `main` (the dev DB wasn't used, so no records were
  added there): `RescheduleBooking::handle()` set `tandem_date_id` and saved with no capacity check.
- **One writer, one reader.** The gate is in `RescheduleBooking`, inside a transaction that `lockForUpdate()`s the
  target slot, exactly like `StartTandemCheckout`. The count is the online path's own: new
  `TandemDate::hasPlaceFor(Booking)` = "already holds a place here, or `! isFull()`", and `isFull()` is what the
  online checkout and the public slot list already use.
- **Holds match the online path exactly:** it counts every booking that isn't Cancelled, so an unpaid
  `PendingPayment` checkout hold takes a place until Stripe's `checkout.session.expired` cancels it (≤ 30 min,
  `StartTandemCheckout::HOLD_MINUTES`). Reschedule counts it the same way.
- **Party size:** bookings carry none. One booking is one jumper, so it needs one place. The refusal says so:
  "That date is full (N of N places taken) and this booking needs 1. Pick another date, or raise the date's
  capacity first."
- **Moving within its own slot** isn't counted against itself (a non-cancelled booking already there passes).
  A cancelled booking moving back onto a slot needs a free place.
- **The button explains:** in the action's slot select, a full slot reads "… — full" and is **disabled**
  (clearer than a label alone, since it can't be picked by mistake). Filament refuses a disabled option at
  validation. If the slot fills between validation and the save, the action refuses and the screen shows a
  "Not rescheduled" notice with the reason. No email goes out unless the reschedule succeeded.
- **The ad-hoc date/time path is deliberately unconstrained:** it has no slot, so no capacity to check. Unchanged.
- **Tests** (`RescheduleCapacityTest`):
  - full slot refused through the screen and through the action directly (bypassing the select), booking
    unchanged, no mail;
  - exactly one place left succeeds; within-slot moves succeed; a hold takes a place; a cancelled booking frees
    one; the ad-hoc path is unconstrained; the select marks and disables full slots;
  - the online checkout and the reschedule agree on the same slot, before and after it fills.

  6 are red on `main`.
- Launch checklist §5 gains the refusal check, and the Help guide's bookings section says full dates can't be
  picked.

## Non-production hosts tell search engines to stay out (fix/noindex-non-production)

Prompt 022. Ben, 9 Oct 2026: do it **in the app**, not only in server config.

- **Premise confirmed** by code read: `/robots.txt` allowed crawling on every host, and nothing sent `noindex`
  by environment.
- **Unless `app()->isProduction()`** (staging, a preview host, local):
  - `/robots.txt` returns `User-agent: *` / `Disallow: /`, with no Sitemap line (`routes/web.php`);
  - every web response carries `X-Robots-Tag: noindex, nofollow`.
- **Where it sits:** in the existing `SecurityHeaders` middleware on the `web` group (`bootstrap/app.php`),
  beside the other response headers. It reads no session, so its order doesn't matter. Every route in
  `routes/web.php` gets it (pages, `/robots.txt`, `/sitemap.xml`, the admin, the webhooks). A 404 for a URL
  that matches no route runs no route middleware, so it has no header; a missing page isn't indexed anyway,
  and `robots.txt` disallows it.
- **Production is unchanged, byte for byte:** rendered in-process with `APP_ENV=production`, `main` vs the
  branch:
  - `/robots.txt` and `/sitemap.xml` bodies are identical, as are the headers of every page;
  - the homepage `<head>` and `/tandem` are identical;
  - the homepage body is identical apart from the per-request CSRF token, which differs between two renders
    of `main` too.

  `NoindexNonProductionTest` pins today's production robots file exactly and asserts no `X-Robots-Tag` there.
- **No `<meta name="robots">` added:** the header covers every response type (HTML, txt, xml) with no `<head>`
  change, so production's `<head>` can't drift.
- **Docs:** SETUP.md gains a **Staging** section (basic-auth except `/webhooks/stripe` and `/webhooks/resend`;
  the app's noindex; the Stripe TEST endpoint at API version `2026-09-30.endive`; the site email pointed at a
  test inbox), linking to `verification/CHECKLIST.md` rather than copying it. The checklist's §0 noindex
  item now checks the app's header and robots file instead of asking for an nginx rule.
- **Tests:** staging robots disallows all with no sitemap; the header on HTML pages, `/robots.txt` and
  `/sitemap.xml`; local/testing/preview are noindexed too; production robots is exactly today's file with
  no header, and production pages carry no `noindex`. 3 are red on `main`. `RobotsTest`'s sitemap test now
  states production explicitly (it described the production file).

## Email and PDF blue moves to the accessible `#0078cc` (fix/email-link-colour)

Prompt 023. Ben, 9 Oct 2026: move the emails to the accessible blue.

- **Premise confirmed:** `#2f8de4` (3.47:1 on white, and under white text) in the mail blocks (button,
  featured course, two-column), the newsletter shell and footer, the Markdown-mail theme `gforce.css`, and both
  PDFs. Also found, not in the prompt: the voucher PDF's `#0ea5e9` eyebrow and accent are only on the navy band
  (6.85:1 there), so they were left alone.
- **Defined once:** `App\Support\BrandHex` (`STRONG = #0078cc`, the site's `primary-strong`; `ACCENT = #2f8de4`;
  `NAVY`). Blade mail partials and both PDF `<style>` blocks echo the constants. Neither medium can read
  `app.css` variables (mail clients, dompdf), so one PHP source serves both. The static `gforce.css` can't call
  PHP, so `EmailColourTest` pins its `.button-primary` to `BrandHex::STRONG`.
- **Moved to STRONG** (text or a fill under text, on a light surface):
  - the newsletter button fill (white `#ffffff` text: 4.61:1);
  - the two-column "button" link;
  - the newsletter shell's default link colour;
  - the theme's `.button-primary` fill and its border-padding (white `#fff` text);
  - the receipt PDF's labels;
  - the voucher PDF's "Voucher code" label.
- **Kept bright, with reasons (the allowlist):**
  - **Text on the navy band** (`#0a0f23`): the footer's Instagram/Facebook links and the featured-course
    eyebrow. ACCENT is 5.47:1 there; STRONG would fail at 4.12:1, the same rule as the site's dark surfaces.
  - **The voucher's £ amount** (34px bold): large text needs 3:1, and ACCENT is 3.47:1. This matches the site's
    ≥24px rule Ben confirmed.
  - **Decoration:** the voucher band rule, code-box border, message rule and the theme's panel rule.
- **Unchanged:** every email's copy, layout and recipients, and the CID logo (011).
- **Evidence** (`audits/reports/run3/`):
  - every `/dev/mail` preview (14) sent through the **log** mailer into its own file, on rolled-back sample data
    (`023-log-mailer-run.txt`; the script is `023-mail-evidence.php`). Each reports 0 old-blue text or fill uses,
    except the newsletter campaign's 2, which are the allowlisted on-navy footer links;
  - `023-booking-confirmation.eml`;
  - cropped renders of the receipt and voucher PDFs (`023-receipt-pdf.jpg`, `023-voucher-pdf.jpg`).
- **Test:** `EmailColourTest` reads every `#2f8de4` or `BrandHex::ACCENT` use in the mail, mail-component,
  vendor-mail and PDF views with the CSS property it sits in. Borders pass; `color` and `background` need an
  allowlist entry with a reason. It's red on `main` (6 uses), proven with a planted use, and pins the theme
  button and both contrast facts. `MailRenderTest` and `/dev/mail` still render every mailable.

## The payments table's scroll region is reachable by keyboard (a11y/scrollable-table-focus)

Prompt 024. Ben, 9 Oct 2026: yes, fix it.

- **Premise confirmed in the browser** (axe-core 4.10.2, signed in via the local dev login, read-only):
  `/account/payments` at 390 had **1 `scrollable-region-focusable`** node. The wrapper scrolls (scrollWidth 395
  vs clientWidth 354) and couldn't be focused. 1440 doesn't scroll and was clean.
- **One shared component:** `<x-ui.table-scroll label="…">`, an `overflow-x-auto` box with `tabindex="0"`,
  `role="region"`, `aria-label`, and the site's standard focus ring (`focus-visible:ring-2 ring-ring` +
  offset, like the buttons). Layout and border classes pass through.
  - **Grep:** the payments table was the only table in a scroll wrapper in the public and account views. The
    booking page's payments table and the Tandem tables aren't in one. The admin calendar's `overflow-x-auto`
    wraps a grid inside Filament, not a table, so it's out of scope.
- **After:** axe **0 violations** at 390 and 1440. Tab reaches the region (`:focus-visible`, the ring paints: white
  2px offset + brand-blue 4px), and the arrow keys scroll it to its full 41px.
  - **No change at rest:** the full-page payments screenshot before vs after is **0 px** at 390 and at 1440.
- **Tests:** `PaymentsTableKeyboardTest` (the rendered page has the focusable, named region round the table) and
  `TablesScrollThroughTheSharedRegionTest` (no table in a bare scroll wrapper in any non-mail view, proven by a
  planted wrapper). Both are red on `main`.
- `ui-guidelines.md` lists the component. Screenshots: `ui-review/scrollable-table-focus/` (390 and 1440 at
  rest, before and after, plus keyboard focus).

## Admin tidy-up: past choices save, safe upload types, a sortable inbox (chore/admin-tidy-up)

Prompt 025, written in run 3 under Ben's "also fix anything outstanding" (9 Oct 2026). It's the admin audit's
deferred **A-4** batch, which had never been written as a prompt, re-verified first.

- **A past choice blocked Save, a real defect** (the audit only saw "raw IDs"). Verified by a Livewire test:
  - editing a booking whose jump slot has passed showed "1" and failed with **"The selected jump slot is
    invalid."**;
  - a news article linked to a past course failed the same way.

  Filament refuses any value not among a select's options, and those options were upcoming-only.
  `App\Support\AdminOptions::bookablePlusCurrent()` lists what's bookable **plus the field's current value**,
  labelled "… — no longer bookable". It's used by the booking slot, news course and newsletter featured-course
  selects. The Reschedule action's select picks a *new* slot (no stored value) and is exempt, with that reason,
  in `SelectsKeepTheirCurrentValueTest`.
- **Image uploads accepted SVG onto the public disk — a security fix, deeper than the audit saw.**
  - The audit noted "no `acceptedFileTypes`". In fact `ImageCrop::ratio()` called Filament's `->image()`, which
    sets the accepted types to `image/*` (SVG included), and the free-form fields had no type rule at all. A
    Livewire test uploaded an SVG with a `<script>` and **saved it**.
  - New `App\Support\AdminImages::upload()` is the one image-upload factory (like `AdminDates`): JPEG, PNG or
    WebP, up to 12 MB (Livewire's own cap, now with Filament's plain message). All 18 image fields use it.
  - `ImageCrop` no longer calls `->image()`, and two newsletter-block fields lost a stray `->image()` that would
    have reset the types again.
  - **OVERNIGHT-DEFAULT — CONFIRM:** the type list (no GIF/HEIC/SVG; iPhones convert HEIC to JPEG on upload)
    and 12 MB.
  - The guard `ImageUploadsUseTheFactoryTest` fails on any other `FileUpload::make(` (Documents allowlisted:
    their own PDF/Word types) and on any `->image()` outside the factory. It's proven with planted violations.
- **Enquiry inbox:** unread-first was forced in `modifyQueryUsing`, ahead of any chosen sort. It's now the
  table's **default** sort (a closure), so sorting by "Last activity" works. The default view is unchanged.
- **Navigation:** four sort ties (Bookings/Unmatched 2, Documents/Newsletters 11, Disciplines/Testimonials 12,
  FAQs/Hall of Fame 13) renumbered to unique values **preserving today's rendered order exactly** (read from
  `Filament::getNavigation()` before and after). A test pins both groups' order.
- **Public CSS:** `@source not '../views/filament'` and the `dark` custom variant removed from `app.css`. The
  admin's views compile into its own theme. Only 3 selectors left the public bundle (`gap-x-4`, `gap-y-1`,
  `dark`), none used outside the admin views. Homepage full-page before/after: **0 px** at 1440 and 390.
- **`motion` uninstalled** (imported nowhere) and the stale "Motion One" mention in `SecurityHeaders` removed.
- **Ruled out:** the "unused slug fields". Product's slug is now the price-token key (014); Location's is a
  CMS-field-gate question, not a tidy-up.
- **Help guide:** the photo-upload step states the types and the 12 MB limit.
- **Tests:** `AdminTidyUpTest` (past slot and course save; SVG and GIF refused, JPEG/PNG/WebP taken; >12 MB
  refused; a column sort overrides unread-first; nav sorts unique, in today's order) plus the two structural
  guards. 6 are red on `main`.

## One-click List-Unsubscribe on newsletters (feat/list-unsubscribe)

Prompt 026, written in run 3 under Ben's "also fix anything outstanding". This is the email audit's follow-up
**E-1**, now done.

- **Premise confirmed** by code read: `NewsletterCampaignMail` set no headers; the unsubscribe route was GET-only;
  the Resend transport passes custom headers through (`ResendTransportFactory`).
- **Headers** (`NewsletterCampaignMail::headers()`): `List-Unsubscribe: <signed https URL>` and
  `List-Unsubscribe-Post: List-Unsubscribe=One-Click` (RFC 8058). It's the same signed URL as the footer link,
  built in one private method.
- **POST** `/newsletter/unsubscribe/{subscriber}`, behind `signed`, CSRF-exempt (`bootstrap/app.php`, beside the
  webhooks: providers POST with no session). It unsubscribes through the same `NewsletterService::unsubscribe()`
  as the footer link and returns a bare 200 `Unsubscribed.`. It's idempotent. A tampered or unsigned POST is 403
  and changes nothing.
- **Only newsletters carry it.** The double-opt-in confirmation and transactional mail have nothing to
  unsubscribe from, and a test asserts they don't.
- **Tests** (`ListUnsubscribeTest`): headers asserted on the real MIME message (array mailer); POST unsubscribes
  with no CSRF token, twice; tampered is refused; the footer GET is unchanged. The behaviour tests are red on
  `main`.
- **OWNER DECISION — PENDING (noted, not built): the footer link unsubscribes on GET.** It's one click by design
  ("one click, no login" in the footer). But some corporate mail scanners (e.g. Outlook Safe Links) fetch every
  link in a message, which can unsubscribe a reader who never clicked. Options:
  - (a) keep it as is, since it's simplest for readers;
  - (b) the GET shows a "Confirm unsubscribe" button that POSTs, so scanners can't trigger it but readers need
    a second click.

  The mail-client button (this prompt) is POST either way.

## The owner can reset a forgotten admin password (feat/admin-password-reset)

Prompt 027, written in run 3 under Ben's "also fix anything outstanding". This is the admin audit's follow-up
**A-3**, now done.

- **Premise confirmed:** the panel had `->login()` only. A forgotten password on the live server meant someone with
  SSH running `make:filament-user` or tinker.
- **`->passwordReset(requestAction: App\Filament\Auth\RequestPasswordReset::class)`.**
  - Filament's request page is rate-limited (2 a minute), and its `ResetPassword` notification is `ShouldQueue`
    (Horizon, like every mail; a failure lands in `failed_jobs`, which the dashboard counts).
  - The link is signed and expires in 60 minutes (the default broker).
- **No account enumeration:** Filament's page answers an unknown email with "We can't find a user with that email
  address", which reveals the admin login. The subclass returns the same "sent" notice for an unknown address
  (and for a customer's address: customers are a separate guard and model). The throttle message is unchanged.
  It lives in `app/Filament/Auth/`, outside page discovery, so it isn't a navigation item.
- **The email:** Filament's notification, rendered in the `gforce` mail theme (the brand button `#0078cc`). It's
  staff-facing, so it keeps Laravel's own wording. It's now in `/dev/mail` (`admin-password-reset`), the email
  audit's inventory, and the launch checklist's §2 email table.
- **Help guide:** one line in "Getting started" (worded without a "sent" claim, so the mail-claims guard has
  nothing to pair). The `/dev/mail` preview uses an unsaved `User::make()` (never persisted; the guard's pattern rightly allows it), since `NoSeededAdminOnServersTest` allows only
  `make:filament-user` and the dev seeder to create staff users.
- **Tests** (`AdminPasswordResetTest`):
  - the login links to the request page;
  - a staff email queues the notification to that user;
  - an unknown or customer email sends nothing and gets the same notice (red with Filament's own page class);
  - the emailed token resets the password, and the new one logs in;
  - the email renders in the brand theme.
