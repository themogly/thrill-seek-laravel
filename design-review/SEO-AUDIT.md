# SEO Audit — seo/audit-pass

Branched off main `2491973`. Server-rendered Laravel/Blade (good for SEO). Audited
the rendered `<head>`, headings and content of every public page plus the layout,
routes, sitemap and `robots.txt`.

**Baseline (already good):**
- Per-page **unique `<title>` and meta description**, mostly keyword-aware and
  CMS-editable (e.g. "Tandem Skydive from 15,000ft — G-Force Skydiving", AFF
  description names the £1,750 price and Spain). Dynamic news articles use their own
  title. No duplicate/missing titles found.
- One `<h1>` per page (the hero), sensible heading order.
- All pages **server-rendered** — news/AFF links are real `<a href>`, fully
  crawlable. Pagination on `/news` is real links.
- Images carry `width`/`height` (no CLS) and `alt` (from CMS where dynamic);
  decorative hero/background images use `alt="" aria-hidden`.
- `favicon.ico` present; 404 returns a real 404 status.

---

## PHASE 1 — Critical (indexability & technical foundation)

- **Sitemap uses relative URLs**: `<loc>/tandem</loc>` etc. → sitemaps require
  **absolute** URLs (`https://host/tandem`); relative locs are invalid and ignored →
  without a valid sitemap, discovery/crawl of all pages is hampered.
- **Sitemap omits dynamic news articles & has no `<lastmod>`**: only `/news` (the
  index) is listed → each published `/news/{slug}` should be in the sitemap with its
  `updated_at` as `lastmod`, and it must update as articles are published/edited →
  article pages won't be discovered/recrawled promptly otherwise.
- **No canonical URL** on any page → add a self-referencing
  `<link rel="canonical">` per page → prevents duplicate-content dilution from
  query strings, trailing slashes, and the http/https + host variants.
- **No `noindex` on thin/transactional pages**: `/payment/success` and
  `/payment/cancelled` are post-checkout pages with no search value → mark them
  `noindex,follow` → keeps thin pages out of the index. (Admin is auth-gated; dev
  routes are local-only — confirm both are not crawlable.)
- **`robots.txt` does not reference the sitemap** → add `Sitemap: https://host/sitemap.xml`
  → the standard way crawlers discover the sitemap.
- **OG/Twitter incomplete + placeholder image**: `og:url`, `og:site_name`,
  `og:locale` are missing, and `og:image` points at a **lovable.app/r2.dev preview
  URL** (a build placeholder), not a real hosted image → complete the tags and
  default `og:image` to a real absolute site image; dynamic pages (news article)
  should use their own image → broken/placeholder share previews lose social clicks.

`Review:` These are the foundation: a valid sitemap + canonical + correct
robots/noindex are what let Google crawl, de-duplicate and index the right pages.
Titles/descriptions (usually the first SEO job) are already done, so the critical
work here is the technical plumbing, not copy.

## PHASE 2 — Refinement (relevance, structure & local SEO)

- **No structured data (JSON-LD) anywhere** → add valid JSON-LD from real model/CMS
  data:
  - **Organization / LocalBusiness** (sitewide): name, url, logo, phone
    (`+44 (0)7583 155 951`), email (`info@gforceskydiving.co.uk`), social profiles,
    `areaServed`. Address needs CMS fields (see owner tasks) — emit `postalAddress`
    only once populated.
  - **Product + Offer** on `/tandem` and `/aff`: real price (from the Product model,
    pence→£), currency GBP, availability.
  - **Event** on AFF course dates: real `CourseDate` start/end, location, offers
    (deposit/price) — live data.
  - **AggregateRating / Review** on `/testimonials`: real testimonial count and the
    average of the real star ratings added in the social-proof round.
  - **Article** on `/news/{slug}`: headline, datePublished (`published_at`),
    author (byline), image.
  - **BreadcrumbList** on deep pages (news article, AFF).
  → rich results (stars, prices, event dates) lift CTR and relevance.
- **Local SEO**: titles/copy don't target the location. G-Force flies from **Devon**
  (the booking location) with AFF in **Seville, Spain** → add address/locality CMS
  fields for NAP consistency, weave location into default titles/descriptions
  ("Tandem Skydives in Devon…"), and surface `areaServed` in LocalBusiness → local
  intent ("skydiving devon") is high-value, low-competition.
- **Per-page social image**: news articles should set `og:image` to their
  `featured_image` (absolute) rather than the global default → better article shares.
- **Internal linking**: news articles link to the AFF flow (good), but money pages
  (tandem/AFF/vouchers) could be linked from more body content with descriptive
  anchors → spreads authority to conversion pages.

`Review:` Structured data is the biggest single lever once indexability is sound — it
makes the listings richer and the local intent explicit. Sequenced after Phase 1
because there's no point enriching pages Google can't cleanly crawl/canonicalise.

## PHASE 3 — Polish (performance signals & enrichment)

- **Preload the hero image and display font** → faster LCP on the home/marketing
  heroes.
- **Apple touch icon + `site.webmanifest`** (favicon.ico already exists) → clean
  mobile bookmarks / PWA basics.
- **`og:locale` = en_GB**, `<html lang="en-GB">` → correct regional signal for a UK
  business.
- **Confirm image dimensions sitewide** (pipeline sets them; verify no late-added
  `<img>` lacks width/height) → guards against CLS.
- Already correct: 404 returns 404; server-rendered output.

`Review:` Marginal but cheap wins once the foundation and structured data are in.
Cumulative impact: a crawlable, canonical, sitemapped site with rich structured data
and local targeting — the difference between "indexed" and "ranking for the searches
that convert".

---

## SEO tasks for the owner (cannot/should not be coded)

- **Real business address** — there's no street address in the CMS (only phone +
  email). Add the real registered/dropzone address so LocalBusiness/NAP is complete.
  I'll add the CMS fields; the owner fills the real address (I will NOT invent one).
- **Replace the social-share image** — the current `og:image` is a build placeholder
  (lovable.app URL). I'll default it to a real bundled site image; ideally the owner
  uploads a branded 1200×630 share image.
- **Google Search Console** — verify the domain and submit `sitemap.xml`.
- **Google Business Profile** — create/claim the Devon listing (huge for local).
- **Genuine reviews / backlinks** — real reviews (Google/Trustpilot) and links from
  dropzone directories / BPA listings; never fabricated.
- **Production canonical host + HTTPS** — enforce one host (www vs apex) and HTTPS at
  the server/CDN; the canonical tags will then point at the live https host.

---

## Status (after the phased fixes)

**Phase 1 — done:**
- ✅ Self-referencing canonical + complete OG/Twitter (og:url/site_name/locale/type),
  absolute og:image, `lang="en-GB"` — centralised in the layout, per-page overridable.
- ✅ Sitemap now emits **absolute** URLs, lists every **published news article** with
  `<lastmod>`, and excludes drafts.
- ✅ `robots.txt` is a dynamic route referencing the absolute sitemap; disallows
  `/admin` and `/dev`.
- ✅ `noindex,follow` on payment success/cancelled and the newsletter status pages.
- ✅ News articles set `og:type=article` and their own (absolute) `og:image`.

**Phase 2 — done (structured data) / deferred (copy & address):**
- ✅ JSON-LD via `<x-seo.json-ld>` + `App\Support\StructuredData`, all from real data:
  sitewide SportsActivityLocation, Product+Offer (tandem/AFF, live GBP price), Event
  per AFF course date, AggregateRating (real star ratings), Article + BreadcrumbList
  on news. Validated as parseable.
- ⏸ **Local-SEO titles/descriptions** (e.g. "…in Devon"): these are CMS `seo_title`/
  `seo_description` fields — left for the owner to edit (guardrail: don't hardcode
  marketing copy into views). Noted as an owner task.
- ⏸ **Full LocalBusiness postal address / NAP**: no real street address exists in the
  CMS; not invented. SportsActivityLocation ships with phone/email/areaServed now and
  upgrades automatically once the owner adds the address (owner task).

**Phase 3 — done:**
- ✅ Web manifest, apple-touch-icon, theme-color, explicit favicon; home hero
  preloaded (LCP) with `fetchpriority=high`. Font already preloaded; image dimensions
  already set (no CLS). 404 already returns 404.
- ⏸ Dedicated square 512px maskable PWA icon — owner asset task (logo is non-square).

No critical items deferred. Owner tasks remain as listed above (GSC, GBP, real
address, square app icon, local-copy edits, real reviews/backlinks).
