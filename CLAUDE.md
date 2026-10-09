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
- **Help guide**: a developer-maintained, in-panel owner's manual at
  `/admin/help-guide` ("How it all works", Help nav group). Content is structured data
  in `HelpGuide::sections()` (not CMS-editable, not prose) rendered as Filament section
  cards with jump-link contents and "Open … →" buttons to each admin screen.
  **When you ship a new admin-manageable feature, add a section to it** in the same PR:
  append a `{id, icon, title, intro, steps[], cta}` entry to `HelpGuide::sections()`
  (build any `cta` URL from the resource's `getUrl()`, never a hardcoded path) — the
  guide's tests then check the new link resolves and coverage holds. Task-oriented,
  plain English for a non-technical owner; pounds like `260.00` go in `<code>`.

## Architecture rules (one way of doing everything — learn from the named examples)
1. **Data access**: CMS/display content (page copy, products for display,
   testimonials, gallery, instructors, shop, hall of fame) is read through the
   cached `App\Support\SiteContent` gateway; transactional data (availability,
   course dates / spaces left, bookings, payments, vouchers) is ALWAYS queried
   live via Eloquent and never cached. Reference: `PageController::aff()` — cached
   products next to a commented live course-date query. Spatie settings have their
   own cache and are read directly (`@inject`/`app()`).
2. **Controllers delegate only**: resolve and return. Content via `SiteContent`,
   domain data via model scopes, multi-strategy lookups via a view model.
   Reference: `PageController::paymentSuccess()` + `App\ViewModels\PaymentSuccessPage`.
3. **Webhooks**: `StripeWebhookController` verifies the signature only;
   `HandleStripeWebhook` is a pure event-type dispatcher; each event has its own
   Action. Reference: `App\Actions\HandleCheckoutSessionExpired`. The inbound-email
   webhook is its sibling: `ResendWebhookController` verifies the Svix signature, then
   dispatches a queued `ProcessInboundEmail` job → `HandleInboundEmail` action (the
   slow Resend body-fetch runs in the job, behind the `InboundEmailFetcher` interface
   so tests fake it). Both routes are CSRF-exempt in `bootstrap/app.php`.
4. **Blade by default**: pages are plain Blade rendered by `PageController`;
   Livewire only where the page talks to the server after load, embedded as an
   island in a Blade page (reference: `NewsletterSignup` in the footer). Never a
   full-page Livewire component for static content; never hand-rolled fetch/XHR.
5. **Namespaces**: `App\Support` is cross-cutting utilities only; page view-models
   live in `App\ViewModels`, named `<Thing>Page` with a `viewData(...)` method.
   Business logic stays in `App\Actions` classes with `handle()`.
6. **No `declare(strict_types=1)`** (pinned by `pint.json`); full parameter and
   return type-hints required everywhere.
7. **State is re-rendered by the mechanism that changes it.** If a component's
   markup depends on server state, the component renders it. A Blade layout outside
   the Livewire root, an `x-data` object evaluated once, or a `wire:confirm` built
   from a deferred model is a snapshot that the update never revisits.
8. **Nothing loads or inserts DOM inside a Livewire-morphed view except Livewire.**
   No `@vite`/module `<script>` and no Alpine `x-if` under `resources/views/livewire/**`
   (a re-inserted script doesn't re-execute; an `x-if` clone survives the morph and
   is inserted twice). Scripts load from the layout; Alpine toggles with `x-show`.
   Guard: `NoDomInsertionInLivewireViewsTest`.
9. **Session-reading middleware registers on the `web` group, after `StartSession`**
   (`$middleware->web(append: …)` in `bootstrap/app.php`), never on the global stack:
   global middleware reads a null session in production while in-process tests,
   which pre-populate the store, stay green.
   Guards: `SessionMiddlewareIsNotGlobalTest` + `SessionSurvivesARealRequestTest`.
10. **Non-scrolling shells are sized in `svh`, never `100vh`/`h-screen`.** Mobile
    `100vh` is the toolbar-hidden height, so anything pinned to the bottom of a shell
    that never scrolls sits off-screen. Viewport units only as `min-`/`max-` caps
    (`layouts/app.blade.php`'s `min-h-screen` is fine). Guard: `NoFullViewportHeightShellsTest`.
11. **One writer per fact, one reader per figure.** Anything that changes a balance,
    a space count or a status goes through exactly one Action. A number shown on two
    screens comes from one resolver, and the test asserts the two agree with each
    other rather than each against a hard-coded value.
12. **A gate must never become a picture of a gate.** Restyling, moving or replacing a
    screen that blocks an action keeps the server-side refusal, and ships a test that
    the write is still refused when the screen is bypassed.
13. **No Livewire public method shadows a `$wire` alias** (`commit`, `get`, `set`, `call`,
    `dispatch`, …) — the front end can never reach it. Guard: `NoLivewireReservedNamesTest`
    (parses the alias map from the vendored Livewire dist; re-check after a Livewire bump).
14. **Every Alpine directive sits inside an `x-data` root** (a Livewire view's root element
    counts), and every page rendering `x-data` ships Alpine. Guards:
    `AlpineDirectivesHaveAScopeTest` + `AlpineShipsWherePagesUseItTest`.

## Conventions (match these exactly — no second ways of doing things)
- **SEO / meta**: `<head>` meta is centralised in `layouts/app.blade.php` — every page
  gets a unique title, self-referencing canonical, full OG/Twitter and an absolute
  `og:image`. A page overrides only what's unique via `@section('title' |
  'description' | 'og_image' | 'og_type' | 'robots')`. Section values are echoed with
  `{!! !!}` (Blade already escapes inline section content — `{{ }}` double-escapes).
  Structured data is built from **real** model/CMS data in `App\Support\StructuredData`
  and rendered via `<x-seo.json-ld :data="..." />` (push per-page into `@stack('json-ld')`);
  never invent prices, dates, addresses or review counts. New indexable page → it
  inherits meta automatically; add JSON-LD if it has a rich entity.
- **FAQs are per-page**: a `Faq` (page `FaqPage` enum) read via `SiteContent::faqs()` and
  rendered by `<x-site.faq-section :faqs="...">` — an accordion whose answers are ALWAYS
  in the DOM (collapsed via CSS `grid-rows` height, never `display:none`) so they're
  crawlable and match the `StructuredData::faqPage()` FAQPage JSON-LD the component
  `@push`es. Answer text in the schema is `Faq::plainAnswer()`. The section + schema only
  appear when the page has active FAQs. Add a new page's FAQs by passing its collection.
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
- **Admin date fields**: build every date/datetime input via `App\Support\AdminDates`
  (`AdminDates::date()` / `AdminDates::dateTime()`), never `DatePicker::make()`
  directly. The factories pin Filament's *native* input mode — keyboard-typeable and
  showing the browser calendar — because the non-native JS picker is readonly
  (click-only). Callers chain their own label/default/rules; defaults that help go on
  the field (new tandem date → today; course end → start + 4 to meet the 5-day min).
- Images are plain `FileUpload`s to the public disk; stored values are either bundled
  paths (`/images/x.jpg`) or upload paths, resolved by `image_url` accessors /
  `imageUrl()` helpers. No medialibrary.
- **Newsletter emails are block-based**: a campaign's `blocks` JSON renders through
  `App\Support\NewsletterRenderer` — each block is a self-contained, inline-styled
  partial in `resources/views/mail/blocks/` (web-safe fonts, tables, absolute image
  URLs, no flexbox), wrapped by the plain-Blade `mail/newsletter/shell.blade.php`
  (NOT a Markdown view — Markdown/CommonMark mangles pre-built HTML; the shell emits
  `{!! $body !!}` verbatim and the mailable strips Livewire morph markers via
  `NewsletterRenderer::stripLivewireMarkers()`). To add a block type: a partial + a
  Filament Builder block (content fields only — no colour/font; keep it brand-locked)
  + the `NewsletterRenderer::BLOCK_TYPES` list. Dynamic blocks resolve live but are
  frozen into `rendered_html` at send.
- **Starter templates**: a new newsletter can be pre-filled from
  `App\Support\NewsletterStarterTemplates` via the create-screen picker. To add one,
  append a `{label, description, blocks[]}` entry to `NewsletterStarterTemplates::all()`
  (blocks use existing `BLOCK_TYPES` with placeholder copy) — it appears in the picker
  automatically and is just pre-filled blocks, never a separate rendering path.
- Customer-facing automated emails go through editable `EmailTemplate` records +
  `TemplatedMail`; all mail is queued and wrapped so failures log instead of breaking
  the request. Transactional emails render inside the shared **`<x-mail.layout>`**, which
  owns the greeting (`Hi {name},`) and the sign-off ONCE (`GeneralSettings::email_signoff`)
  — editable template bodies and designed views carry ONLY their unique content, never a
  greeting/sign-off. The tandem-only "before your jump" block is single-sourced from
  `JumpPrepSettings` (the same data as the account panel), injected into the
  `booking_confirmed`/`jump_reminder` templates via `{{ jump_prep }}` for tandem bookings
  (`Booking::isTandem()`) only. Keep every mailable in `MailRenderTest` + `/dev/mail`.
- **Customer accounts** use a separate `customer` auth guard (passwordless magic links,
  no passwords) — entirely distinct from the Filament admin `web` guard; customers never
  reach `/admin`. Account controllers extend `App\Http\Controllers\Account\AccountController`
  and read the current customer ONLY via `customer()`; route-bound records pass through
  `ownedBooking()`/`ownedEnquiry()` (404 on someone else's id) — never trust a URL id for
  access. Login links are single-use, expiring, hashed rows (`CustomerLoginLink`).
  Customer balance payments reuse the existing Stripe path (`StartBalanceCheckout` →
  `StripeCheckout` → the same webhook), never a second payment implementation.
- **Never cache Eloquent objects** — Laravel 13's cache refuses to unserialize PHP
  objects (`cache.serializable_classes = false`). Settings caching is fine (plain values).

## Design rules (Round 5B/6 — owner-approved; see design-review/round5/SUMMARY.md)
- **UI conventions: see `ui-guidelines.md`** — the canonical design system for this project
  (the real type/spacing tokens, palette, and the `x-ui`/`x-site` component catalogue, after
  the 4-pass UI level-up). The `frontend-design` skill is the general craft; that doc is our
  specifics. Read it before building new UI; the rules below are the headline points.
- **Palette only**: text and UI colours come exclusively from the established brand
  tokens in `resources/css/app.css` (`primary`, `secondary`, `sky-bright`, `sky-deep`,
  `ink`, `muted`, `destructive`, …). Never invent a new shade, hex value or oklch —
  not in views, CSS or PDFs.
- **Type scale + spacing are tokens** (UI pass 1 — `resources/css/app.css` `@theme`, see
  `ui-review/FOUNDATIONS.md`): headings use the fluid `text-display / text-h1 / text-h2 /
  text-h3` tokens, body copy `text-lead` (19px leads) and the 16px/1.65 base; sections use
  the 2-step rhythm — standard `py-section-sm lg:py-section`, dramatic dark/photo feature
  bands `py-section lg:py-section-lg` (UI pass 3) — and reading columns `max-w-measure`. Use
  these tokens, NOT ad-hoc `text-5xl`/`py-24`/arbitrary `text-[Nrem]` values. Headings go
  through `<x-site.section-heading>` / `<x-site.page-hero>`. Letter-spacing stays on
  `tracking-*` (eyebrow labels standardise on `tracking-[0.25em]`). The homepage News+social
  block is News-dominant (2/3) + an honest "Follow us" card (no live-feed framing).
- **No section-divider lines — spacing only**: section transitions are separated by the
  `py-section*` rhythm alone, NEVER a full-width horizontal rule on a `<section>` wrapper
  (no `border-b/-y border-secondary` to divide one section from the next). A colour change
  (white → `band-ink`/`bg-secondary`) is its own edge. Component-internal rules are fine —
  FAQ separators, table/input borders, the eyebrow accent, and the brand **blue**
  `border-*-4 border-primary` rules (hero underline, newsletter/gift band frames, card tops).
- **Buttons**: every button/CTA renders through `<x-ui.button>` with its three
  variants — `primary`, `outline` (border-current; adapts to dark bands), `link`
  (inline text action). States (hover/active/focus-visible/disabled) and the icon
  convention (lead for action, trail for directional; `size="icon"` needs `aria-label`)
  are baked in; loading is wired per-use with `wire:loading`. Never style a one-off
  button or pass colour classes to it; don't pass display classes either (`hidden`
  fights the base `inline-flex` — wrap instead, see the header). The same action looks
  the same everywhere.
- **Interactive components (UI pass 2)**: form controls (`<x-ui.input>` / `textarea` /
  `select`) all share ONE focus treatment — `focus-visible:ring-2 ring-ring` + border
  in brand blue; labels via `<x-ui.label for>`; inline errors via `<x-booking.field>`
  (`role="alert"`), simple forms use the toast pattern. Navigational arrow links
  ("All news →", "Read more →") use `<x-ui.arrow-link>` (href → `<a>`; no href → in-card
  `<span>` cue). The FAQ accordion is the reference disclosure pattern. See
  `ui-review/COMPONENTS.md`.
- **Dates** use the ONE `<x-ui.date-field>` (never a bare `<input type="date">`): pass
  `model` (the wire:model prop), `label`, and `min`/`max` (Y-m-d). It keeps a native
  `<input type="date">` as the value carrier (same submitted `YYYY-MM-DD` + validation)
  and overlays a branded Alpine calendar (with month + year jump) only on fine-pointer
  desktops; mobile uses the native OS picker. `dateField` lives in `app.js`.
- **No flat-black hero bands on secondary pages**: `<x-site.page-hero>` without an
  image renders the compact navy-gradient hero; only tandem/AFF/coached get
  photographic CMS heroes. Dark `band-ink` sections stay only where already
  approved (stats, about, newsletter, gift band, footer). `page-hero` also takes a
  `compact` prop for a shorter photographic hero (Hall of Fame).
- **Photo tiles**: the canonical photo-led pattern is `<x-site.photo-tile>` — a
  full-bleed image with a bottom navy scrim carrying the caption (icon + text in
  the slot), a primary top-rule, sharp corners, hover photo-zoom (reduced-motion
  honoured) and a visible focus ring when given an `href`. Without an image it
  renders the intentional navy monogram fallback (bold brand initial), never a
  broken box. Reference implementations: **Hall of Fame** and **Testimonials** —
  the two photo-led pages are built from it so they read as siblings; build any
  new photo-grid from it. Star ratings render via `<x-site.stars>` in palette
  colours only (sky-bright on dark scrims, primary on light) — no gold.
- **Motion ambition: subtle** (as built — CSS transitions plus the opt-in `data-reveal`
  entrance reveals in `app.js`, skipped under `prefers-reduced-motion`; no Motion One
  calls). Content is visible without JS. Recorded in DECISIONS as
  `OVERNIGHT-DEFAULT — CONFIRM`; a cinematic layer needs the owner's ask.

## Quality bar (enforced before every commit)
- **Database is MySQL 8+** in production and local dev. SQLite (`:memory:`) is kept ONLY
  as the fast default test driver (`phpunit.xml`); the production-parity gate is
  `php artisan test -c phpunit.mysql.xml` against a real MySQL DB. Run both green before
  shipping anything that touches migrations/queries. Migrations must work on both drivers
  (e.g. avoid `->after()` referencing a not-yet-added column — SQLite ignores it, MySQL
  errors). See `db-migration/MYSQL-NOTES.md` + SETUP.md "Production database".
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
- **Fix the instance, then guard the class.** When a bug is one example of a category,
  ship a test that walks the whole category (every model with an object cast, every
  Livewire view, "exactly one implementation of X") — and **prove the guard guards** by
  making it fail on purpose before trusting it.
- **The suite must collect every `tests/*` directory.** A directory in no phpunit
  testsuite silently never runs. Guard: `SuiteCollectsEveryTestDirectoryTest` (both configs).
- **A measurement is only as good as the tree and build it was taken on.** Before
  reporting anything measured: dependencies match the lockfile, assets are rebuilt
  (`npm run build` — the gitignored build dir otherwise measures an old commit), and the
  real page is measured, not a harness approximation. State the caveat in the report.
- **Verify state against the code and git, not claims.** "The audit ran" is not "the
  fixes ran". Check `git log --all`, the files and the routes before treating work as
  landed.

## Workflow
- **Prompts are files.** Task prompts live in `prompts/NNN-*.md` (how to write one:
  `prompts/writing-prompts.md`); `RUNNING-ORDER.md` at the root says which runs next. A
  number is never reused. No prompt text in front of you → ask for it, never reconstruct
  one. Kit audits (`audits/`), gates (`gates/`) and the human launch checks
  (`verification/`) run verbatim. `false-green.md` catalogues checks that pass while the
  thing is broken — add to it whenever a green turns out false.
- **Verify a prompt's premise before building.** Read the files it names. Already fixed
  → say so and stop. Partly fixed → build only the remainder and record which half
  existed. Reasoning wrong → say so with evidence and don't build it. Silent compliance
  and silent deviation are both worse than saying it.
- **Gap report when a branch deviates** from its prompt, before merge: what it required
  that you didn't do; what you did that it forbids; what you did that it never mentions.
- **Escalate the owner's decisions** (policy, retention, consumer-rights wording, brand
  colours): write the options and the spec for each into DECISIONS.md as
  `OWNER DECISION — PENDING`, and implement neither side.
- **Mark agent-chosen defaults** `OVERNIGHT-DEFAULT — CONFIRM` beside the code and in
  DECISIONS.md, so every place an agent guessed is greppable.
