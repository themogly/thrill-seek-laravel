# 013 — The homepage `<title>` and description ignore the admin's SEO settings

One branch, one task. Read `CLAUDE.md`, `DECISIONS.md` and `audits/reports/cms-field-usage.md` §3 (the first
bullet). **Size: small.**

`git checkout main && git pull` → `git checkout -b fix/home-seo-settings`.

> **Why this exists.** The CMS-field gate's top reverse-drift item.
>
> **Verified on `origin/main` = `7e01bfe` by code read:**
> - `resources/views/pages/home.blade.php:6-7` hardcodes the title ("G-Force Skydiving — One Life. One
>   Adventure. Live It.") and the description.
> - The layout falls back to `GeneralSettings::seo_title` / `seo_description` (`layouts/app.blade.php:7-8`)
>   only when a page doesn't set them, so the owner's "Default page title / description" never reaches the
>   page that matters most.
>
> **Ruled out:** a visual change. The title and meta aren't rendered on the page, so the signed-off homepage
> look isn't affected. Its `<head>` is.

## Build

- Decide between (a) the homepage uses the General defaults, and (b) Home gets its own SEO fields in
  `HomePageSettings`, in the same style as the `SimplePagesSettings` pages. My view is (b): the homepage title
  is usually different from a site-wide default. Decide it and record why.
- **The rendered values must not change on deploy.** Seed the new or used setting with exactly the current
  literal strings (settings migration), so Google sees no difference until the owner edits them.
- Help guide: one line saying where to edit the homepage title.

## Rules

- No visual change. Homepage screenshots before and after (1440, 390) must be pixel-identical.
- Read through an accessor with a fallback (the stale-cache rule).

## Tests

- Changing the setting changes the homepage `<title>` and meta description. Red on current `main`.
- With fresh settings, the rendered title and description equal today's literals.

## Finish

`composer check` green. DECISIONS entry. Push the branch. **Do not merge** (unless on an authorised unattended
run).
