# 007 — Sample testimonials must not reach a server or the published review rating

One branch, one task. Read `CLAUDE.md` (the "never fabricate reviews or testimonials" rule), `DECISIONS.md` and
`audits/reports/completeness-check.md` #2. **Size: small. Run after 006.** It touches the same seeder.

`git checkout main && git pull` → `git checkout -b fix/sample-testimonials`.

> **Why this exists.** Ben, 9 Oct 2026: unapprove the sample testimonials now. They publish a fake
> **4.8-from-8** rating to Google.
>
> **Verified on `origin/main` = `7e01bfe` by code read:**
> - `TestimonialSeeder` seeds 8 made-up reviews ("Sarah M.", "Tom R.", …) with `approved => true`
>   (`database/seeders/TestimonialSeeder.php:79`).
> - `DatabaseSeeder` runs it on every environment.
> - `StructuredData::aggregateRating()` (`app/Support/StructuredData.php:80`) builds an `AggregateRating` from
>   whatever approved testimonials it's given.
> - `/testimonials` and the homepage "Voices from the sky" read
>   `Testimonial::approved()` (`SiteContent.php:111,118`).
>
> Real customers can now leave reviews from their account, so real ones will arrive through the approval
> flow.

## Build

1. **Samples are local-only.** `TestimonialSeeder` runs only in `local`. Ben still wants to see a populated
   page in dev, so there they stay approved.
2. **The rating needs real reviews.** Emit `AggregateRating` only from approved testimonials that come from a
   real customer, i.e. with a `customer_id`. The seeded samples have none, so check that first; if that's
   wrong, say so and propose the right discriminator. Decide a minimum count before it's emitted (my view: 3,
   so a single review doesn't publish a rating), mark it `OVERNIGHT-DEFAULT — CONFIRM`, and record it.
3. **One reader per figure.** The rating's `reviewCount` and the number of reviews actually shown on
   `/testimonials` come from the same query. Assert they're equal to each other, not to a hard-coded number.
4. **Empty states.** With zero approved testimonials:
   - The homepage "Voices from the sky" section must be an intentional absence (hidden cleanly, no empty
     box or orphaned heading). The homepage is signed off, so screenshot it at 1440 and 390 with
     testimonials and without. The with-testimonials render must be pixel-identical to before.
   - `/testimonials` must render an honest, on-brand empty state, not a blank grid. Reuse existing
     components.
5. **Report, don't change:** list the other sample seeders that put invented people or stock imagery on the
   public site (Hall of Fame, gallery, instructor photos) in the DECISIONS entry, as owner content tasks.

## Rules

- No change to the review-submission or approval flow.
- No deletion of any testimonial row. On an existing database, unapproving is the owner's action in the admin.

## Tests

- In `production`, seeding creates no testimonials. Red on current `main`.
- No `AggregateRating` with only sample or customer-less reviews, or below the minimum count. Red on current
  `main`.
- `reviewCount` equals the count rendered on `/testimonials`.
- Homepage and `/testimonials` render with zero testimonials, with no orphaned heading.

## Finish

`composer check` green. Screenshots in `audits/reports/` (cropped JPEGs, not full-page PNGs). DECISIONS entry.
Push the branch. **Do not merge** (unless on an authorised unattended run).

Owner line in the summary: on any database that's already seeded, unapprove the 8 samples in the admin.
