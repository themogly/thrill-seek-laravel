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
