# Completeness check — what's absent, stubbed or placeholder

Kit gate `gates/completeness-check.md`, run verbatim (report-only) as item 7a of unattended run 1. `main` = `06cee5c`.
Nothing was changed.

**How it was checked.**
- Grepped `app/ resources/ routes/ config/` (plus settings migrations and seeders) for
  `todo|fixme|hack|placeholder|coming soon|not implemented|ask to enable|lorem`.
- Crawled every link on all 21 public pages (23 internal targets status-checked; `#`/empty/`javascript:`
  hrefs; external URLs listed).
- Loaded every parameterless admin screen as the owner (35 screens) and read the page text for
  stub markers.
- Re-checked each item from the June run (recovered from git `9bee71f`; the file was later removed in
  the `79e242b` docs reorganisation).
- Checked the features added since June (accounts, news, newsletter builder, course comms, vouchers,
  mail health).

**Bottom line:** the site is genuinely wired up. No stub routes, no `#` links, no lorem, no TODO/FIXME in
app code. One **real defect** blocks the owner (the FAQ list crashes). The one **pre-launch decision**
is the sample reviews and the rating built on them. Everything else is owner content.

## 🔴 Incomplete — needs a decision or build before launch

### 1. The admin FAQ list crashes (HTTP 500) whenever any FAQ exists
- **Where:** `/admin/faqs`, on the owner's "Site content → FAQs" screen. Cause:
  `App\Models\Faq::scopeForPage(FaqPage $page)` (`app/Models/Faq.php:61`) has the same name as the query
  builder's own pagination method `forPage($page, $perPage)`. Filament's paginator calls
  `forPage(1, 10)`, which Eloquent routes to the model scope, and that throws `TypeError … FaqPage, int
  given`.
- **Evidence:**
  - Loaded as the admin: **500**.
  - Log: `Faq::scopeForPage(): Argument #2 ($page) must be of type FaqPage, int given`.
  - Reproduced read-only: `Faq::query()->paginate(10)` throws with the 19 seeded FAQs.
  - A scan of every model's scopes against the builder's methods found **this is the only collision**.
- **Why the suite is green:** `FaqAdminTest::test_admin_can_list_and_create_faqs` lists an **empty**
  table, and the paginator only calls `forPage()` when there are rows. This is a false green: the test
  doesn't share the real state.
- **Now vs intended:** the owner can't open, reorder or edit FAQs from the list. The public pages are
  fine; they call the scope with an enum through `SiteContent::faqs()`.
- **Options:**
  - **Build (recommended):** rename the scope (e.g. `scopeOnPage`, one caller in
    `SiteContent.php:102`). Re-point the test to list *with* FAQs present (it must go red first). Add a
    structural guard that no model scope shadows an Eloquent/Query builder method.
  - There's no simplify or cut option; it's a defect.

  → **Recommendation: build, urgent.** A small prompt that goes to the front of the queue.

### 2. Published review rating built from sample reviews
- **Where:** `/testimonials`, plus the homepage "Voices from the sky". The 8 testimonials are still the
  seeded **samples** ("Sarah M.", "Tom R.", "Priya K."… `TestimonialSeeder`). `/testimonials` emits
  `AggregateRating` structured data **4.8 from 8 reviews** (`StructuredData::aggregateRating`), which
  invites a review-star rich result built on made-up reviews.
- **Now vs intended:** the code is right (it reads real model data). The data isn't real.
- **Options:**
  - **Owner (recommended):** replace them with genuine reviews, or unapprove the samples, before launch.
    Real customers can now leave reviews from their account, so these will fill in.
  - **Build (belt and braces):** a seed-only `is_sample` flag that the public pages and the rating
    ignore.
  - **Cut:** hide the testimonials section and the rating until there are real reviews.

  → **Recommendation: owner replaces or unapproves before launch.** Escalated as an owner decision.

## 🟡 June items re-checked

| June item | Now |
|---|---|
| Home "live Instagram feed — ask to enable" note | ✅ **Resolved.** The setting was removed (`2026_06_16_120000_remove_orphaned_cms_settings`); the homepage block is an honest "Follow us" card plus a curated grid. |
| `/aff#enquiry` didn't land on the form | ✅ **Resolved.** `id="enquiry"` at `aff.blade.php:161`. |
| Social URLs were generic homepages | ✅ **Resolved.** Real profiles (`instagram.com/gforceskydiving`, `facebook.com/Gforceskydiving.co.uk`). |
| Sample testimonials | ❌ **Still samples.** Now item 2 above, because they feed a published rating. |
| Sample Hall of Fame | ❌ Still the 8 samples (James Carter, Emma Walker…) with stock images. Owner content. |
| Sample instructors / stock photos | ⚠️ The names look real (Joby Chadd, Ricky, Lucy Davies) but the photos are stock crops (`instructors/jay|ren|lee.webp`). Owner content. |
| Thin gallery | ⚠️ 6 entries but only 4 distinct stock images (tandem/aff repeated). Owner content. |
| Privacy `[Owner: …]` placeholders | ❌ **Still live on `/privacy`:** minimum age/guardian process, and the retention period. Owner + solicitor. |

## 🟢 Owner content tasks (real features waiting on real data — not defects)

- **Real photography** throughout (heroes, instructors, Hall of Fame, gallery; bundled stock today).
- **Real Hall of Fame** graduates.
- **Privacy policy** `[Owner: …]` lines + the solicitor review (already an owner task in
  `RUNNING-ORDER.md`).
- **FAQ answers and page copy with typed prices** (consistency audit C-7): confirm they match the
  products, and remember to update them when prices change.
- **Coached price eyebrow** "From £60 per session" is CMS text: confirm it.
- **Shop** is toggled **off** (`/shop` 404s and nothing links to it). If it's turned on, add real items.
  `price_label` is free text (consistency C-15).
- **Production mail settings** (email audit OWNER/OPS): `MAIL_MAILER=resend`, key, from-address on the
  verified domain, `APP_URL`.

## ⚪ Intentional / fine (confirm you agree)

- **Empty states:**
  - "No dates online right now" / "New course dates coming soon" (`book-*`) only show with no dates,
    and link to an enquiry.
  - The account sections hide when empty.
  - The review prompt only shows when a review is allowed (`dashboard.blade.php:90`; the route 403s
    otherwise).
- **Local-dev sign-in shortcut** on `/account/login`: rendered only when `app()->environment('local')`
  (`account/login.blade.php:46`), with the route registered only in local (`routes/web.php:83`).
- **Coached is enquiry-only** (no online booking): deliberate.
- **No map or postal address on Contact:** an enquiry-first business with operational dropzones, as in
  June.
- **Help guide "placeholder" wording** (`HelpGuide.php:130,283`): instructions *about* placeholders (FAQ
  starters, `{{ }}` tokens), not stubs.
- **`fake()` data** lives in factories only.

## Summary

- **Genuine incomplete features: 2.**
  1. **The FAQ admin list 500s** → **build** (rename the scope, an honest test, a guard). Urgent.
  2. **Sample reviews behind a published rating** → **owner replaces or unapproves** before launch
     (or **build** an `is_sample` exclusion).
- **Resolved since June: 3** (Instagram note, `/aff#enquiry`, social URLs).
- Everything else is owner content (photos, Hall of Fame, privacy placeholders, typed prices) or
  intentional.

*Deviation from the kit file:* the kit names the output `COMPLETENESS-CHECK.md`. It's written to
`audits/reports/completeness-check.md` to follow this repo's convention (`RUNNING-ORDER.md`: new kit
reports go in `audits/reports/`).
