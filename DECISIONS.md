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
