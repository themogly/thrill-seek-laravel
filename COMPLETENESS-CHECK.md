# Completeness check — "anything else unfinished?"

Read-only inventory of placeholders / stubs / dummy content / dead UI vs real, wired-up
features. Tests/audits/QA verify that what exists *works*; this checks whether anything
intended was quietly shipped as a placeholder. `main` @ `4a85bab`.

**Bottom line: the site is largely complete and genuinely wired up.** Exactly **one**
genuine incomplete feature was found (the home "live Instagram feed" note), plus one
minor half-wired anchor. Everything else flagged is **owner content** (real photos /
copy / social URLs / real testimonials) — expected, not defects. No `TODO`/`FIXME`/`not
implemented`/dead-`#`-buttons/lorem ipsum in the app code.

---

## 🔴 Incomplete — needs a decision before launch

### 1. Home "Instagram" section claims a live feed that doesn't exist
- **Where:** home page "Instagram" block — `resources/views/pages/home.blade.php:150-166`;
  the note is the CMS field `home.instagram_note`, seeded as:
  > **"Live Instagram feed connects via Meta Graph API — ask to enable."**
  (`database/settings/2026_06_10_100000_create_home_page_settings.php:41`) — and it
  **renders live on the public home page** (verified).
- **What it does vs intended:** the grid above the note is NOT a live feed — it's a manual,
  owner-curated **GalleryImage** grid (`$galleryImages`, each tile links to
  `general.instagram_url`). The note implies a Meta Graph API live-feed integration is
  available "ask to enable" — **no such integration exists**. So a real visitor is told the
  site has a feature it doesn't.
- **Options:**
  - **Simplify (recommended):** the note is owner-editable CMS (`home.instagram_note`) —
    change it to describe the curated gallery (e.g. "Recent jumps from the dropzone —
    follow us on Instagram") or clear it. ~2-minute owner edit; the grid itself is real.
  - **Build it:** implement the Meta Graph API feed (needs a Meta app, page token, refresh
    job, caching) — significant work for marginal value over a curated grid.
  - **Cut:** remove the Instagram section entirely.
  - → **Recommendation: simplify** (edit the CMS note; keep the curated grid).

## 🟡 Minor — half-wired (latent)

### 2. `/aff#enquiry` anchor doesn't land on the enquiry form
- **Where:** the AFF booking empty-state CTA links to `/aff#enquiry`
  (`resources/views/livewire/book-aff.blade.php:24`, "Ask about the next course"), but the
  AFF page has **no `id="enquiry"`** — the enquiry form is `<livewire:aff-enquiry-form>`
  (`resources/views/pages/aff.blade.php:160-161`) with no anchor (the page only has
  `id="courses"`).
- **Effect:** clicking it loads `/aff` at the top instead of scrolling to the enquiry form.
  **Latent** — the empty state only shows when there are zero AFF course dates (currently
  there are 2 seeded).
- → **Recommendation: build (1-line)** — add `id="enquiry"` to the AFF enquiry section.
  (Not fixed here — report-only.)

## 🟢 Owner content tasks (real features; just need the owner's real data — NOT defects)

- **Social profile URLs are generic placeholders.** `general.instagram_url` =
  `https://instagram.com`, `general.facebook_url` = `https://facebook.com`
  (`…create_general_settings.php:13-14`). The social icons in the header, footer, contact
  page and the home Instagram tiles therefore link to Instagram/Facebook **homepages**, not
  G-Force's real profiles. → Owner sets the real profile URLs (Settings → General).
- **Testimonials are SAMPLE reviews.** Seeded as fabricated quotes — "Sarah M." (Tandem
  jumper), "Tom R." (AFF graduate) etc. (`database/seeders/TestimonialSeeder.php`). They
  look real. **Publishing fabricated reviews is misleading** — the owner must replace them
  with genuine testimonials (or unpublish them) before launch. → Owner content (important).
- **Hall of Fame entries are samples** — "James Carter / A Licence — Spain 2024", etc.
  (`HallOfFameSeeder.php`) with bundled stock images. → Real graduates/photos.
- **Instructors are samples** — generic bios and **mismatched stock photos** (e.g. 'Joby'
  → `instructors/jay.jpg`) (`InstructorSeeder.php`). → Real instructor names/bios/photos.
- **Gallery is thin** — only 2 images seeded (`/images/tandem.jpg`, `/images/aff.jpg`),
  so the home "Instagram" 3-column grid shows just 2 tiles. → Owner uploads real dropzone
  photos (Site content → Gallery).
- **Home stats** — `15k ft / Highest UK Tandem`, `30+ yrs / Combined Experience`,
  `BS / USPA / Certified` (`home.about_stats`). Plausible defaults, CMS-editable. → Owner
  confirms they're accurate (they're public marketing claims).
- **Contact details** — phone `+44 (0)7583 155 951`, email `info@gforceskydiving.co.uk`
  (`…create_general_settings.php:11-12`). Look real; owner should confirm.
- **Privacy policy `[Owner: …]` placeholders** — minimum age / guardian-consent process
  and retention period (intentional editable placeholders, already known from the privacy
  work). → Owner completes + solicitor review.
- **Bundled stock imagery throughout** — heroes, instructors, gallery, hall of fame use
  shipped `/images/*.jpg` placeholders. → Replace with real photography.
- **Shop merch (if enabled)** — 9 seeded sample products with `£15 – £30`-style price
  labels (`ShopItemSeeder.php`); the shop is **display-only** (no checkout) and toggled
  **off** by default. → If turning it on, add real products/prices/photos.

## ⚪ Intentional / fine (deliberate — confirm you agree)

- **AFF "New course dates coming soon"** (`book-aff.blade.php:22`) — a proper **empty
  state** shown only when there are no course dates; links to the enquiry. Functional.
- **Shop display-only catalogue, toggled off** — intentional per DECISIONS (merch is a
  display list with free-text prices, not e-commerce).
- **payment-success "check again" button** (`payment-success.blade.php:81`, `href=""` +
  `onclick="reload()"`) — a reload-while-the-webhook-catches-up control. Functional.
- **No map / postal address on the contact page** — appears deliberate (enquiry-first
  business; jump locations are operational and vary). Confirm you don't want a map.
- **Empty states generally** — the booking calendar is hidden until a booking exists;
  sections hide cleanly when their CMS content is empty. Correct behaviour, not gaps.
- **`fake()` data in `database/factories/`** — test factories only; never seeded to the
  live site.

---

## Summary

- **Genuine incomplete features found: 1** — the home "Instagram live feed — ask to enable"
  note (no Meta Graph API integration behind it). **→ Simplify:** edit the CMS note to
  describe the curated gallery; keep the grid. (Build the real feed only if the owner
  specifically wants auto-updating Instagram.)
- **Minor half-wired: 1** — `/aff#enquiry` doesn't scroll to the form (missing anchor id).
  **→ Build:** 1-line `id="enquiry"` (latent; low priority).
- Everything else is **owner content** (real social URLs, **real testimonials** ⚠️, real
  photos, real instructors/graduates) or **intentional** empty states. No stubbed routes,
  no dead buttons, no lorem, no TODO/FIXME in app code.
