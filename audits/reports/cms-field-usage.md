# CMS field usage — admin ↔ front-end drift

Kit gate `gates/cms-field-usage-check.md`, run verbatim (report-only) as item 7b of unattended run 1. `main` = `3ba3657`.
Nothing was changed.

**How it was checked.**
- Inventoried every settings property (7 settings classes, plus whether each has an admin field) and
  every model `$fillable` field that an admin form edits.
- Traced each for consumers across `resources/views`, `app` (views, view models, `SiteContent`, mailables,
  `StructuredData`, actions, observers, accessors) and `routes`, excluding its own definition, admin
  forms, migrations and seeders. Script output: 322 fields.
- Checked every candidate by hand, including use through the model's own accessors (e.g.
  `show_from_price` → `summary_price_label`) and admin-only logic.

**Bottom line:** almost everything is wired. **10 genuine orphan fields**, all from two causes: a homepage
line that was removed in a redesign, and two features (Location addresses, Product duration) whose public
display was never built or was later dropped.

## 1. Orphaned — editable in the admin, consumed nowhere

| Field | Where it's edited | Current value | Best guess why | Recommendation |
|---|---|---|---|---|
| `HomePageSettings::team_lead` | Settings → Home → "Lead text (shown above the 'Meet the team' button)" (`ManageHomePageSettings.php:115`) | "The people you'll fly with." | Its teaser line was dropped when the homepage team mention became an arrow-link (`b494dd6`, "drop teaser line"). The label still says it's shown. | **REMOVE** from the admin (the homepage is signed off). |
| `Product::duration` | Products → Pricing → "Duration" (`ProductForm.php:103`) | Tandem: "Approx. half a day at the dropzone"; others empty | Shown on no product card, page or email. | **CONFIRM-WITH-OWNER:** re-surface it (e.g. a Tandem "what to expect" line or the product cards) or remove it. |
| `Location::address_line`, `town`, `region`, `postcode`, `country`, `lat`, `lng` | Locations → edit (`LocationResource.php:~82`) | Only `region`/`country` filled; no address, postcode or coordinates | Locations became a first-class table for clash rules and labels; the public pages and the course-event JSON-LD use only `name`. June's withdrawn cms-field-cleanup prompt proposed putting the address into the course-event JSON-LD. | **CONFIRM-WITH-OWNER:** re-surface (Event `location.address` in `StructuredData::courseEvent`, and/or a dropzone line on the course cards) once the owner supplies real addresses, or hide the fields until then. |
| `Location::image` | Locations → "Image" (`LocationResource.php:66-72`) | empty for all 4 | Accessor `image_url` exists (`Location.php:67`) but nothing renders it. | **REMOVE** (or re-surface with a design brief). |

## 2. Used, but only in non-obvious places (NOT orphans — don't delete)

| Field | Consumed by |
|---|---|
| `Product::show_from_price` | `Product::summaryPriceLabel` accessor → homepage service cards ("from £260" vs "£1,750" vs "Price on enquiry"). |
| `Testimonial::excerpt` | `Testimonial::homeQuote` accessor → homepage, the featured quote, the testimonial grid. |
| `UnmatchedInboundMessage::reviewed_at` | Admin logic: the `unreviewed()` scope, the table column, action visibility. |
| `Document::file_path`, `original_filename`, `mime_type`, `size_bytes` | Email only (`CourseMessageMail` attachments) and the attachment-size limit (`SendCourseMessage`). |
| `CourseReminder::days_before` | Scheduling logic (`CourseDate`, `CourseDateObserver`). |
| `Product::featured_on_home`, `NewsArticle::published` | Query logic in `SiteContent`. |
| `NewsArticle::featured_image`, `Testimonial::avatar` | Through `*_url` accessors, plus the WebP optimiser. |
| `SimplePagesSettings::privacy_title/body/updated_at` | `App\ViewModels\PrivacyPage` (`privacy_updated_at` is stamped automatically on save). |
| `SimplePagesSettings::contact_form_heading` | `App\Livewire\ContactForm`. |
| `GeneralSettings::tagline`, `footer_copyright` | Site footer **and** the newsletter email footer. |
| `GeneralSettings::og_image` | Every page's `og:image` (layout) and the news-article fallback. |

## 3. Reverse drift — hardcoded, but the owner would reasonably expect to edit it

- **Homepage `<title>` and meta description** are literals (`pages/home.blade.php:6-7`). General settings'
  "Default page title / description" (`seo_title`) never reach the homepage, which is the page that matters
  most. Edit the default and the homepage doesn't change. → Make the homepage read `seo_title` /
  `seo_description` (or give Home its own SEO fields).
- **`/news` and `/newsletter` intro and meta copy** (`news/index.blade.php:3-7`,
  `newsletter.blade.php:5-20`), and the **footer newsletter blurb** (`footer.blade.php:11`). Every other
  page's hero, subtitle and SEO are CMS fields (`SimplePagesSettings`).
- **Pay-card copy** on Tandem and AFF: eyebrow, heading, body, button (`tandem.blade.php:94-101`,
  `aff.blade.php:148-155`), and Tandem's three reassurance bullets (`:104-106`).
- **Policy text in templates:** voucher terms "valid 12 months · transferable · non-refundable"
  (`buy-voucher.blade.php:21,57`); "P6 third-party insurance and any weight surcharge are paid at the
  dropzone on the day" (`book-tandem.blade.php:199`); deposit-transferable wording (`book-aff.blade.php:149,153`).
  These are owner T&Cs, and the voucher/AFF T&Cs are already an open owner task. If the policy changes,
  these must change with it. → CMS fields, or one source shared with the terms page.
- **Shop** "Online ordering opens soon — contact us to order." (`shop.blade.php:24`). The shop is toggled
  off. A "coming soon" claim belongs in the CMS if the owner turns the shop on.

## 4. Fields wired correctly but holding sample or empty values (owner content)

- **Testimonials:** the 8 seeded samples, which also feed the published `AggregateRating` (completeness
  check item 2).
- **Hall of Fame:** 8 sample graduates with stock images.
- **Instructors:** stock photo crops (`jay/ren/lee.webp`).
- **Gallery:** 6 entries, 4 distinct stock images.
- **Privacy `[Owner: …]`:** placeholders in `privacy_body`.
- **Locations:** no addresses.
- **`GeneralSettings::email_signoff`** is used by every email but **has no admin field**, so the owner
  can't edit the sign-off. (The admin audit's owner question: one line to add it to General if wanted.)

## Summary

- **Genuine orphans: 10 fields in 4 groups.**
  - `team_lead` → **remove**.
  - `Product::duration` → **confirm with owner** (re-surface or remove).
  - Location address and coordinates (7) → **confirm with owner** (re-surface into Event JSON-LD and
    course cards once there are real addresses).
  - `Location::image` → **remove**.
- **Reverse drift worth fixing first:** the homepage SEO literal (the default-title setting doesn't reach
  the homepage).
- Everything else is wired, including the fields consumed only by email, meta or logic (listed so they
  aren't deleted by mistake).

*Deviation from the kit file:* the kit names the output `CMS-FIELD-AUDIT.md`. It's written to
`audits/reports/cms-field-usage.md` to follow this repo's convention (`RUNNING-ORDER.md`: new kit reports
go in `audits/reports/`).
