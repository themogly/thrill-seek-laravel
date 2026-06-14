# SEO audit — inventory & verification

Branch `seo/audit-pass`, off main `21f4a04`.

**Method:** inventoried what already exists (code + rendered `<head>`) before proposing
anything, per the brief. Read the layout/meta mechanism, routes (sitemap/robots),
`App\Support\StructuredData`, and the per-page Blade meta; spot-checked the rendered
`<head>` on home, `/tandem` (a money page) and a dynamic `/news/{slug}` article.

## Headline

**SEO is already comprehensively implemented** — a prior `seo/audit-pass` round plus the
code-style round's breadcrumb fix did the work, and it's all on main. This audit
**verifies** it and finds **one** genuine micro-gap (a run-together auto-description on
news articles). It is not a from-scratch build; per the brief I did not redo finished
work.

What's in place (verified, with the rendered evidence):

- **Shared meta mechanism** in `layouts/app.blade.php` — every page gets a unique
  `<title>`, meta description, self-referencing `<link rel="canonical">`, full Open
  Graph (site_name/locale/type/url/title/description/image) and Twitter
  `summary_large_image`, with per-page overrides via `@section('title'|'description'|
  'og_*'|'robots')` and `@stack('head'|'json-ld')`. Not copy-pasted tags. ✔
- **Dynamic meta** generated from model data: `/tandem` → "Tandem Skydive from 15,000ft
  — G-Force Skydiving" + "…from £260. …Devon, Swansea and Hinton."; `/news/{slug}` →
  the article's own title/description/`og:image`. ✔
- **JSON-LD** (`App\Support\StructuredData`, rendered via `<x-seo.json-ld>` from REAL
  data): Organization (`SportsActivityLocation`, sitewide) · Product + Brand + Offer on
  tandem/AFF (real price/availability) · Event for AFF course dates · AggregateRating
  for testimonials · Article for news · BreadcrumbList. ✔
- **robots.txt** + **sitemap.xml** dynamic routes (absolute URLs, published news with
  `lastmod`, sitemap referenced from robots, `/admin` + `/dev` disallowed). ✔
- **noindex** on the thin/transient pages: payment-success, payment-cancelled,
  newsletter-status, and all `/account` pages. ✔
- **CWV/markup**: hero image preloaded with `fetchpriority="high"` (LCP); images carry
  width/height via the optimisation pipeline; below-the-fold images `loading="lazy"`;
  favicon, apple-touch-icon, web manifest, theme-color all present. ✔
- **Breadcrumb fix verified**: positions are `$index + 1` (1, 2, 3…) — the CA-P1.1 fix
  is correct; not redone. ✔

## PHASE 1 — Critical (indexability & technical foundation)

All present and correct (evidence above): unique per-page titles/descriptions including
the dynamic news/AFF pages, canonical, correct noindex on thin/private pages, robots +
XML sitemap, one `<h1>` per page (the `page-hero`/hero `<h1>`), server-rendered crawlable
`<a href>` links (Laravel/Blade — no JS-only nav), and CMS-driven image alt text.

`Review:` Phase 1 is the foundation and it's solid — nothing here stops crawling,
indexing or understanding. **No work required.**

## PHASE 2 — Refinement (relevance, structure & local SEO)

Present: local targeting (the tandem title/description name Devon/Swansea/Hinton; the
Org is a `SportsActivityLocation` with `areaServed` Devon + Seville and phone/email from
the CMS), the full structured-data set above, OG + Twitter per page, compelling
keyword/price/location-aware titles, money pages linked from content, and clean readable
slugs.

- **[News article auto-description]: run-together text** — when an article has no
  `seo_description`, the fallback is `Str::limit(strip_tags($body), 150)`, which strips
  `</p><p>` and joins sentences with no space ("…progress fast.Spaces are limited…") →
  prefer the article's existing clean `lead` field as the fallback (and de-space the
  body fallback) → a tidy, readable SERP/social snippet on every article without the
  owner having to hand-write one. **(SEO-P2.1 — the one fix in this pass.)**

`Review:` Everything else in Phase 2 is done; this is the single real quality gap, and
it's CMS-respecting (uses an existing field; a hand-written `seo_description` still wins).

## PHASE 3 — Polish (performance signals, enrichment & monitoring)

Present and verified: hero preload, image dimensions, lazy-loading, favicon/manifest,
sitemap `lastmod` + robots reference. The 404 returns a real 404 status with a helpful
page (verified earlier this session). FAQPage is **N/A** — the site has no FAQ section.

`Review:` Nothing to add in markup. The remaining levers are owner-account tasks below.

## SEO tasks for the owner (NOT code — do not fabricate)

- **Real postal address.** The Org structured data is intentionally `SportsActivityLocation`
  with phone/email/areaServed but **no `streetAddress`** — none was invented. Add the
  real dropzone address (Settings → General, once the fields exist) and it upgrades to a
  full `LocalBusiness` address. Phone/email already come from the real CMS values.
- **Google Search Console** — verify the domain and submit `sitemap.xml`.
- **Google Business Profile** — claim/create for the Devon dropzone (local pack).
- **Canonical host + HTTPS** in production — enforce one host (www vs apex) so the
  canonical tags point at the live host (local is http by design).
- Real review counts/ratings already derive from actual `Testimonial` rows — nothing
  faked.

## Status

- [x] SEO-P2.1 — news auto-description now uses `lead` (+ de-spaced body fallback) — done, tested
- [x] Everything else — already implemented and verified; no work needed
