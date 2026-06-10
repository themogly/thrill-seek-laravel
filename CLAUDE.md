# Project: thrill-seek-laravel

## What this is
A Laravel app for a skydiving instructor business (G-Force Skydiving): a Blade/Tailwind
frontend ported from a Lovable React app, with a Filament v5 admin panel at `/admin`
that manages all site content, enquiries, payments and bookings.
- Source app (reference only, do not modify): `../thrill-seek-co` (Vite + React + TS + Tailwind + shadcn/ui).
- Stack: Laravel 13 + Blade + Tailwind + Alpine.js + Livewire 4 + Filament v5,
  spatie/laravel-settings, spatie/laravel-activitylog, stripe/stripe-php, resend/resend-laravel.
- The frontend design is established and verified (last revised on the
  design/visual-polish branch — see design-review/SUMMARY.md). For functional work,
  preserve the existing look, Tailwind classes and animations; design changes need an
  explicit design brief.

## What's built (see DECISIONS.md for the why, SETUP.md for ops)
- **CMS**: every public page reads from settings groups (`app/Settings`, one Filament
  settings page per site page under “Site content”) or content models (Instructor,
  Testimonial, HallOfFameEntry, ShopItem, GalleryImage). Seeders reproduce the original
  static content exactly.
- **Products** (`tandem` / `aff` / `coaching` enum): pricing in integer pence, AFF
  deposits, add-ons (purchasable extras vs display-only fees), weight charges. Home
  cards, tandem pricing tables and AFF price cards read from these.
- **Enquiries**: Livewire forms (contact/tandem/AFF) with honeypot + rate limiting;
  Filament inbox with unread badges, message threads, replies via Resend.
- **Payments**: admin sends Stripe Checkout links or records bank transfers from an
  enquiry; the signature-verified `/webhooks/stripe` endpoint converts paid enquiries
  into bookings; AFF deposit/balance tracking.
- **Bookings**: Filament resource, custom month-grid calendar (tandem dates and
  spanning AFF courses rendered distinctly, location filter), reschedule action with
  customer email.
- **Dates & locations**: `TandemDate` (single-day slot: date/time/capacity) and
  `CourseDate` (multi-day range, minimum 5 days) are separate models; both belong to
  a `Location` (first-class table). The two are operationally exclusive per location
  — `App\Support\DateClash` enforces it in both admin forms, create and edit.
- **Extras**: gift vouchers (redeemable as payments), editable email templates,
  automated confirmation/reminder emails (`bookings:send-reminders`, scheduled daily),
  dashboard stats, activity log on bookings/payments, customers deduped by email.

## Conventions (match these exactly — no second ways of doing things)
- Money is **integer pence**; format with `App\Support\Money::formatPence()`.
- Statuses are string-backed **enums** in `app/Enums` implementing Filament's
  `HasLabel`/`HasColor`.
- Business logic lives in **`app/Actions`** classes with a `handle()` method;
  controllers and Filament actions stay thin.
- Filament resources follow the generated layout: `Resource` + `Schemas/*Form` +
  `Tables/*Table` + `Pages/*`. Public-content resources go in the “Site content”
  nav group; sales resources in “Bookings & sales”.
- Settings classes use spatie/laravel-settings; array properties document shapes with
  `@phpstan-var` ONLY (a `@var` tag breaks spatie's docblock reflector).
- Images are plain `FileUpload`s to the public disk; stored values are either bundled
  paths (`/images/x.jpg`) or upload paths, resolved by `image_url` accessors /
  `imageUrl()` helpers. No medialibrary.
- Customer-facing automated emails go through editable `EmailTemplate` records +
  `TemplatedMail`; all mail is queued and wrapped so failures log instead of breaking
  the request.
- **Never cache Eloquent objects** — Laravel 13's cache refuses to unserialize PHP
  objects (`cache.serializable_classes = false`). Settings caching is fine (plain values).
- **No `declare(strict_types=1)`** — vanilla Laravel conventions, enforced by
  `pint.json` (`declare_strict_types: false`). Full parameter and return
  type-hints are still required everywhere.

## Quality bar (enforced before every commit)
- `php artisan test` — full suite green, no skips. Feature tests for HTTP/Livewire
  flows, unit tests for actions/support classes, Filament resource tests. Mock Stripe
  via the `StripeCheckout` service binding; use `Mail::fake()` — tests never hit real APIs.
- **Gate every commit with `composer check`** (pint --test → phpstan → full test
  suite; aborts on first failure with a real exit code). The tools themselves
  propagate exit codes fine — last session's "swallowed failures" were caused by
  piping their output through `| tail`, which makes the shell return tail's status.
  Never pipe a command whose exit code you depend on.
- Migrations + factories + seeders for every model; conventional commits.
- After touching anything content-related, verify the public pages still render
  identically (tests assert seeded content; smoke-test key routes return 200 twice —
  first-request-only bugs exist).
