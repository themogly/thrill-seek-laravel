# Pre-staging readiness gate — go/no-go

Read-only readiness check before deploying to staging (Ploi + Hetzner). Every claim below
is verified against the actual code/config at the stated evidence, not against what an
audit *says* it did.

- **Branch / commit:** `main` @ `8a1bd43` (Merge qa/functional-sweep …), in sync with
  `origin/main`.
- **Date of check:** evidence gathered against the working tree at that commit.

## Verdict: ✅ GO for staging

The codebase is clean, complete and safely configured. All three go-live blockers are
present **in code** (GDPR erasure, privacy-scrubbed Sentry, MySQL). Two MINOR
config/doc gaps remain (below) — neither blocks leaving local; both are server-setup
items the owner handles during deploy. Staging exists to surface environment issues, and
the bar to leave local ("clean, complete, safely configured") is met.

Fix-first-if-you-want-them-tidy (not blockers): add `SESSION_SECURE_COOKIE` to
`.env.example`; add `php artisan storage:link` to the deploy steps in SETUP.md.

---

## 1. Repo & branch state

- **Current branch:** `main`; **latest commit:** `8a1bd43`. `git status` is **clean** — no
  uncommitted or untracked work.
- **Branches:** only `main` (local) and `origin/main` (remote). No unmerged/leftover
  branches — every feature/security/mysql/qa branch from the build was merged and deleted.
  Nothing half-done is about to ship.
- **Leftover debug code:** none. No `dd(`/`dump(`/`ray(`/`var_dump(`/`print_r(` in
  `app/ routes/ database/ resources/views/`; no `console.log`/`debugger` in `resources/js/`;
  no skipped/`->only`/`markTestIncomplete` tests.
- **Local artifact:** `database/database.sqlite` exists on disk (425 KB, pre-MySQL
  leftover) but is **git-ignored / untracked** and unused (app is on MySQL). Harmless; can
  be deleted locally. Not deployed.

## 2. Build & test health

- **`composer check`: PASS** — pint clean, larastan/phpstan 0 errors, **344 tests passed,
  1403 assertions** (SQLite test driver).
- **`composer audit`: clean** — no PHP advisories (incl. `sentry/sentry-laravel`).
- **`npm audit`: 2 critical** — `shell-quote` pulled transitively by `concurrently`
  (the `composer dev` runner). **Dev-only, never in the production asset bundle**; the
  advisory range covers the latest published `shell-quote` so there's no non-breaking fix.
  Documented in DECISIONS.md. **Not a production risk.**
- **`npm run build`: PASS** — builds without error.

## 3. What's actually DONE vs PENDING (verified against code)

| Item | Status | Evidence |
| --- | --- | --- |
| **SEC-P1.1** webhook throttle | ✅ DONE | `routes/web.php:76,79` — `throttle:120,1` on `/webhooks/stripe` and `/webhooks/resend` |
| **SEC-P2.1** GDPR export + erasure | ✅ DONE | `app/Actions/ExportCustomerData.php`, `EraseCustomerData.php`; `CustomerResource` has both `export` + `erase` row actions; `…add_erased_at_to_customers` migration |
| **SEC-P3.1** security headers | ✅ DONE | `app/Http/Middleware/SecurityHeaders.php` registered in `bootstrap/app.php:26` (web group) — incl. report-only CSP |
| **SEC-P3.2** production SETUP note | ✅ DONE | SETUP.md "Production security" (HTTPS, `SESSION_SECURE_COOKIE`, `APP_DEBUG=false`, canonical host, CDN/WAF) |
| **SEC-P3.3 / Sentry** | ✅ DONE, privacy-scrubbed | `composer.json` `sentry/sentry-laravel ^4.26`; `config/sentry.php` `send_default_pii=false` + `before_send => [SentryScrubber::class,'scrub']`; `app/Support/SentryScrubber.php`; `Integration::handles()` in `bootstrap/app.php:41` (no-ops with empty DSN) |
| **MySQL** | ✅ DONE | `config/database.php:47` mysql connection; `.env.example` `DB_CONNECTION=mysql`; `phpunit.mysql.xml` (parity profile); `db-migration/MYSQL-NOTES.md` (migrations + full suite + tested backup/restore on MySQL) |
| **Functional QA sweep** | ✅ DONE + blocker fixed | `qa/QA-REPORT.md`; the one defect (stale-cache → emails fail) fixed via `GeneralSettings::emailSignoff()` + `tests/Feature/EmailSignoffTest.php` |
| Per-page FAQs | ✅ DONE | `app/Models/Faq.php` |
| Email greeting/sign-off + tandem-only pre-jump | ✅ DONE | `resources/views/components/mail/layout.blade.php`; `…add_email_signoff` settings migration; `JumpPrepSettings` |
| Improved privacy policy | ✅ DONE | `…improve_privacy_policy` settings migration; `App\ViewModels\PrivacyPage` |
| Footer newsletter signup | ✅ DONE | `components/site/footer.blade.php` (footer variant) |

**Go-live blockers (all present):** GDPR erasure ✅, privacy-scrubbed Sentry ✅, MySQL ✅.
None missing. (Sentry only *reports* once the owner sets the real DSN — see §6.)

## 4. Production config readiness

- **`.env.example` coverage:** documents APP_KEY/URL/DEBUG, full Stripe (key/secret/webhook
  secret), Resend (key + from-address + webhook secret + inbound domain), Redis, DB,
  `SENTRY_LARAVEL_DSN`, mail, session, cache/queue. **One gap:** `SESSION_SECURE_COOKIE` is
  read in `config/session.php:172` and required by SETUP.md for production, but is **not in
  `.env.example`**. → MINOR: add it (commented) so it's not forgotten in prod.
- **Local-only routes gated:** `routes/web.php:82` loads `routes/dev.php` **only** under
  `app()->environment('local')`. The dev account-login shortcut (`/dev/account-login`) and
  `/dev/mail` previews therefore **cannot be reached in production** (APP_ENV=production).
  Verified.
- **Safe production defaults:** no secrets committed (`.env` untracked; all sensitive keys
  in `.env.example` are blank). `.env.example` ships `APP_ENV=local` / `APP_DEBUG=true` /
  `MAIL_MAILER=log` as the **local** template — production must set `APP_ENV=production`,
  `APP_DEBUG=false`, `MAIL_MAILER=resend` (all documented in SETUP.md "Production will
  silently break" + "Production security"). No `local`-only assumptions hardcoded; no
  absolute `/Users`/`/home` paths in `app/ config/ routes/`.
- **Filesystem:** owner-uploaded images use `Storage::disk('public')` (e.g.
  `Product::imageUrl`, `Instructor`, `Testimonial`, `HallOfFameEntry`), so the server needs
  **`php artisan storage:link`** or uploaded images 404. Bundled `/images/*` paths work
  without it. → MINOR: this step is **not in SETUP.md** (see §5).
- **Drivers production-appropriate:** `.env.example` has `QUEUE_CONNECTION=redis`,
  `CACHE_STORE=redis`, `SESSION_DRIVER=database` (sessions table exists in
  `0001_01_01_000000_create_users_table.php`). No `sync`/`array`/`file` dev leftovers. (The
  test profile uses array/sync via `phpunit.xml` only — not the app default.)

## 5. Server-side deployment checklist (Ploi / Hetzner)

Ordered; specifics from SETUP.md where present, gaps flagged.

1. **PHP 8.3** with `pdo_mysql` (SETUP "Requirements").
2. **MySQL 8+** — create the DB + a dedicated (non-root) user; set `DB_*` env (SETUP
   "Production database").
3. **Redis** — for cache, queue and (optionally) sessions (SETUP "Requirements").
4. **Env vars** — set all production values: `APP_KEY` (`php artisan key:generate`),
   `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, `APP_NAME`, Stripe
   (key/secret/**webhook secret**), Resend (`RESEND_API_KEY`, `MAIL_MAILER=resend`,
   `MAIL_FROM_*`, inbound domain + `RESEND_WEBHOOK_SECRET`), `SETTINGS_CACHE_ENABLED=true`,
   `SESSION_SECURE_COOKIE=true`, and `SENTRY_LARAVEL_DSN` (SETUP "Required environment
   variables" + "Production security").
5. **Deploy build step** — `composer install --no-dev`, `npm ci && npm run build`.
6. **`php artisan migrate --force`** on deploy. **NEVER `migrate:fresh`/`refresh`/`db:wipe`
   on production** (SETUP "Production database").
7. **`php artisan storage:link`** — ⚠️ **NOT in SETUP.md**; required so owner-uploaded
   images on the public disk are served. Add to the deploy script.
8. **`php artisan settings:clear-cache`** after any deploy that adds a settings property
   (otherwise pages/emails 500 on the missing key — SETUP notes this; the email layer now
   also degrades gracefully per the QA fix).
9. **Horizon** under Supervisor (`autorestart`) — the queue worker; **without it nothing
   sends** (SETUP "Queues, Horizon & scheduler"). Run `php artisan horizon:terminate` on
   each deploy.
10. **`schedule:run` cron** — `* * * * * php …/artisan schedule:run`. ⚠️ **Flag:** without
    it, reminders, abandoned-checkout hold releases and balance chasers **die silently**
    (SETUP "Production will silently break").
11. **Stripe webhook** — register `https://…/webhooks/stripe` for
    `checkout.session.completed` + `checkout.session.expired`; copy the signing secret
    (SETUP "Stripe webhook").
12. **SSL** (Ploi/Let's Encrypt) — HTTPS-only + redirect; enables HSTS + secure cookies.
13. **Monitoring** — Horizon + the scheduler are must-be-running; add an uptime/heartbeat
    check (SETUP "Error tracking").

## 6. Verifiable only AFTER deploy (expected-pending, NOT blockers to leave local)

These need the live server/domain and are the owner's post-deploy verification list (see
`qa/QA-REPORT.md` "Owner-only manual items" and SETUP "Error tracking"/"Stripe webhook"):

- Real **Stripe webhook delivery** to the live URL → confirmed booking, capacity decrement,
  abandoned-checkout slot release. (Local has no Stripe keys; only the voucher/non-Stripe
  booking path was driven end-to-end.)
- Real **email deliverability** + DKIM/SPF on the owner's domain; emails opened in
  Gmail/Outlook.
- **SSL provisioning** on the staging host.
- **Sentry** receiving a real scrubbed event once the real DSN is set (`php artisan
  sentry:test`) — confirm no PII in the payload.
- Real **inbound-email round-trip** (Resend inbound webhook → enquiry thread).
- Manual **money-path** verification (real/test card → confirmation).

## Owner action summary

- **Before real data is entered:** GDPR erasure ✅ and privacy-scrubbed Sentry ✅ are
  already in. Set the real `SENTRY_LARAVEL_DSN` so monitoring is live from day one.
- **Two MINOR tidy-ups** (optional before staging, recommended before production):
  1. Add `SESSION_SECURE_COOKIE` (commented) to `.env.example`.
  2. Add `php artisan storage:link` to the deploy steps in SETUP.md.
- Everything else: **GO**.
