# CMS field usage audit

**Commit:** `71987b2` (main) · **Date:** 2026-06-15 · **Scope:** read-only inventory.
Traced every admin-editable CMS field (Spatie settings classes + content-model Filament
forms) against its consumers across `resources/` (Blade, components, mail) and `app/`
(`SiteContent`, `ViewModels`, `Support\StructuredData`, controllers, mailables). A field
counts as **used** if consumed on the front end **or** in email, SEO/meta, or scoping/logic —
not only in a Blade view. The Filament `Manage*Settings` / `*Form` admin classes are the
EDIT surface and do **not** count as consumption.

**Bottom line: 7 genuinely orphaned content fields + a 9-field Location address group to
confirm.** Most are the direct, expected residue of recent deliberate UI rebuilds (homepage
team teaser removal, trust-stats dedupe, Instagram-feed → "Follow us" card). Everything else
is wired. No critical reverse-drift.

---

## 1. Orphaned — editable in admin, consumed NOWHERE (the real concern)

| Field | Defined | Why orphaned (best guess) | Recommendation |
|---|---|---|---|
| `HomePageSettings::about_stats` | `app/Settings/HomePageSettings.php:46` | The About-section stat tiles (15k ft / 30+ yrs / BS·USPA). **Removed from the view in the homepage-dedupe task** (they duplicated the Trust band) — the field was kept, its render deleted. | **REMOVE from admin** (owner already approved removing these tiles). Trust stats now live once, in `trust_items`. |
| `HomePageSettings::team_eyebrow` | `:56` | Headed the old homepage **team teaser** section (`<x-site.section-heading>`). The teaser was replaced (team-design-fixes) by a woven mention, then a button — the eyebrow lost its home. | **REMOVE from admin** — owner confirm. |
| `HomePageSettings::team_title` | `:58` | Same as above — the teaser's title ("THE COACHES"). No longer rendered. | **REMOVE from admin** — owner confirm. |
| `HomePageSettings::instagram_note` | `:64` (form `ManageHomePageSettings.php:133`) | Leftover from the original live **Instagram feed** that was replaced by the honest "Follow us" card. The card uses `instagram_caption` + `instagram_handle` + `instagram_url` (all live); this extra note renders nowhere. | **CONFIRM-WITH-OWNER** → likely REMOVE. |
| `SimplePagesSettings::home_team_teaser_line` | `app/Settings/SimplePagesSettings.php:46` | The teaser trust line ("Your jumps are run by British Skydiving and USPA-rated instructors"). Used briefly in the woven mention, then **superseded by `HomePageSettings::team_lead`** in the dedupe task. Added 2026-06-15, already orphaned. | **REMOVE from admin** — owner confirm. |
| `Product::duration` | form `ProductForm.php:98`; `$fillable` `Product.php:39` | Editable free-text "duration" on every product, but never displayed. Course length is driven by `CourseDate::duration_days` instead; tandem/coaching don't show a duration. Appears to be a dead field. | **CONFIRM-WITH-OWNER** → likely REMOVE (lean). |
| `Location::{description, image, address_line, town, region, postcode, country, lat, lng}` | `LocationResource.php:62–88` | All editable, but **not surfaced anywhere** — not on the public site, not in `StructuredData::courseEvent()` (which uses only `location->name`), not in booking emails. Either operational reference data the owner maintains, or fields intended for a map / richer course JSON-LD that was never wired. | **CONFIRM-WITH-OWNER.** Two clean outcomes: (a) keep as admin-only reference, or (b) **RE-SURFACE** — add the address to the course-event JSON-LD and/or a location detail block. Not dead, but currently inert. |

**Genuine orphan count: 7 content fields** (`about_stats`, `team_eyebrow`, `team_title`,
`instagram_note`, `home_team_teaser_line`, `Product::duration`) **+ the 9-field Location
address/geo group** (counted as one decision). The five homepage/teaser ones are unambiguous
residue of approved removals; the Location group is "wire it up or accept admin-only".

---

## 2. Used, but only in non-obvious places — do NOT mistake for orphans

These are absent from (or barely present in) Blade page views but legitimately consumed — list
here so they're not deleted by accident.

- **SEO / meta (rendered in `<head>`, not page body):** `GeneralSettings::seo_title`,
  `seo_description`, `og_image` → `layouts/app.blade.php:7–11`. Per-page `*_seo_title` /
  `*_seo_description` (Tandem/AFF/Simple pages) → each page's `@section('title'|'description')`.
- **Structured data / JSON-LD (`app/Support/StructuredData.php`):** `GeneralSettings::site_name`,
  `phone`, `email`, `instagram_url`, `facebook_url` (Organization + `sameAs`);
  `Product::description` (product JSON-LD); `Testimonial::rating` (aggregateRating avg/count);
  `Faq::question` + `Faq::plainAnswer()` (FAQPage); `NewsArticle::byline`/`published_at`/
  `featured_image` (Article).
- **Email only:** `GeneralSettings::email_signoff` (`<x-mail.layout>` sign-off);
  `footer_copyright`, `tagline`, social URLs (newsletter footer);
  `JumpPrepSettings::{arrival_info, what_to_bring, what_to_expect}` (tandem confirmation/reminder
  emails **and** the customer account dashboard).
- **Scoping / logic flags (never rendered as values):** `Testimonial::{approved, featured,
  sort_order}`; `Faq::{page, is_active, sort_order}`; `Product::{type, slug, active,
  featured_on_home, show_from_price, page_path, sort_order}`; `Location::active`;
  `NewsArticle::{published, slug}`; all `sort_order` columns.
- **View-model only:** `SimplePagesSettings::{privacy_title, privacy_body, privacy_updated_at}`
  → `App\ViewModels\PrivacyPage`.
- **Transactional resources out of CMS-display scope** (their fields drive bookings, payments,
  enquiries, emails — not front-end content; verified as a class, not field-by-field):
  Bookings, CourseDates, Customers, Documents, EmailTemplates, Enquiries, NewsletterCampaigns,
  NewsletterSubscribers, TandemDates, Vouchers, UnmatchedInboundMessages, Disciplines
  (`name`/`slug` used on team + course pages).

Note: `Testimonial::customer_id` is a nullable FK in `$fillable` only — **not** in the admin
form and not displayed; it's a latent link column, not an owner-facing field (no action needed).

---

## 3. Reverse drift — hardcoded on the front end, arguably CMS-worthy

The codebase has strong discipline here — nearly all marketing copy already flows through
settings. Only two minor candidates, both judgement calls (not bugs):

- **Footer newsletter copy** — `components/site/footer.blade.php:10–11`: heading "Stay in the
  loop" + "Jump dates, course openings and the occasional offer — straight to your inbox.
  Unsubscribe anytime." Hardcoded, while the parallel home "Follow us" caption IS a setting
  (`instagram_caption`). Could become e.g. `GeneralSettings::footer_newsletter_text`. Low priority.
- **Home news section subhead** — `pages/home.blade.php:123`: "Fresh from the dropzone." (the
  "Latest News" block). A marketing descriptor sitting next to settings-driven leads elsewhere.
  Could be a setting; minor.

Everything else hardcoded is structural/navigational (nav labels, "Book Now", "Teaches",
table headings like "The jump"/"Weight charges", empty-state fallbacks) — correctly not CMS.

---

## 4. Wired correctly but empty / placeholder (owner content tasks)

Fields that work but currently hold sample/placeholder data — pulled from `DECISIONS.md`, not
re-audited value-by-value:

- **`Testimonial::quote` / `excerpt`** — the 14 real reviewer names were seeded but their
  **quotes are still placeholders** (real quotes weren't available; not fabricated). Owner to add.
- **`Instructor::photo`** — bundled placeholder crops; owner replaces with real portraits.
- **`Faq::answer` (Tandem/AFF)** — several answers carry **`[VERIFY …]`** flags (weight/age
  limits, the "highest in the UK" claim, BS-membership cost, packing fee, AFF age limit) awaiting
  owner confirmation.
- **Other owner image uploads** noted in DECISIONS: hero/gallery/Hall-of-Fame photos, a reversed
  white logo for the footer.

---

## Recommendations summary (genuine orphans)

1. `HomePageSettings::about_stats` → **REMOVE from admin** (deliberately removed in dedupe).
2. `HomePageSettings::team_eyebrow` → **REMOVE** (teaser gone).
3. `HomePageSettings::team_title` → **REMOVE** (teaser gone).
4. `HomePageSettings::instagram_note` → **CONFIRM → REMOVE** (Instagram-feed residue).
5. `SimplePagesSettings::home_team_teaser_line` → **REMOVE** (superseded by `team_lead`).
6. `Product::duration` → **CONFIRM → likely REMOVE** (dead; `CourseDate::duration_days` is live).
7. `Location` address/geo group (9 fields) → **CONFIRM**: keep as admin reference, or RE-SURFACE
   into course JSON-LD / a location block.

Removal/re-surfacing is a separate task once the owner decides — this report changes nothing.
