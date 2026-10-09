# 022 — Non-production hosts must tell search engines to stay out (and SETUP's staging notes)

One branch, one task. Read `CLAUDE.md`, `SETUP.md`, `PRE-STAGING-CHECKLIST.md` (§4 flag and §5) and
`verification/CHECKLIST.md` §0. **Size: small.**

`git checkout main && git pull` → `git checkout -b fix/noindex-non-production`.

> **Why this exists.** The run 2 pre-staging gate found that the app noindexes no host. Ben, 9 Oct 2026:
> **yes, do it in the app** rather than relying only on server config.
>
> **Verified on `origin/main` = `fd6bc55` by code read:**
> - `/robots.txt` is a route (`routes/web.php:91-…`) that allows crawling everywhere except `/admin`, on
>   every host.
> - Nothing sends a `noindex` meta or `X-Robots-Tag` by environment.
> - One forgotten basic-auth rule on staging, or a future preview host, and Google indexes a duplicate of the
>   site.
>
> The gate also found that `SETUP.md` doesn't mention the staging conditions `verification/CHECKLIST.md` §0
> relies on.

## Build

1. **Unless `app()->environment('production')`:**
   - `/robots.txt` returns `User-agent: *` / `Disallow: /`, with no Sitemap line;
   - every response carries `X-Robots-Tag: noindex, nofollow`. Put this in the existing `SecurityHeaders`
     middleware, or a sibling on the same stack. It isn't session-reading, so order doesn't matter, but say
     where it sits.

   Production behaviour is unchanged.
2. **SETUP.md "Staging"** (a new short section, or extend the existing one):
   - basic-auth on the whole host **except `/webhooks/stripe` and `/webhooks/resend`**;
   - the app now noindexes non-production hosts by itself;
   - the Stripe TEST webhook endpoint is created with API version **`2026-09-30.endive`** (stripe-php 22);
   - point the admin site-email setting at a test inbox before the first test booking.

   Don't copy `CHECKLIST.md`; link to it.

## Rules

- **Production output is byte-identical:** robots body, headers and `<head>`. Prove it with a test under
  `APP_ENV=production`.
- No change to the sitemap itself.

## Tests

- `staging`: robots disallows all, and the header is present on an HTML page and on `/robots.txt`. Red on
  current `main`.
- `production`: today's robots body exactly, and no `X-Robots-Tag`.

## Finish

`composer check` green. DECISIONS entry. Push the branch. **Do not merge** (unless on an authorised
unattended run).
