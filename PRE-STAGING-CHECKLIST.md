# Pre-staging readiness gate — go/no-go

Kit gate `gates/pre-staging-gate.md` (identical to the kit copy), run verbatim and report-only as item 15
of unattended run 2. Read-only: nothing was changed except this file. Every claim is checked against the
code, git or a command result, not against what a report says.

- **Commit:** `main` @ `5663177` (`docs: run 2 — 018 launch checklist recorded`), in sync with
  `origin/main`. Report written on `docs/pre-staging-gate-run2`.
- **History:**
  - The previous gate (`8c96875`) returned ✅ GO.
  - Since then, run 1 (audits, majors) and run 2 (12 merged items: 019, 005–007 and 009–015, 018) have
    landed, each merged on green.

## Verdict: ✅ GO for staging

The codebase is clean, complete and safely configured to leave local:
- the full gate is green on both drivers (476 tests);
- 0 known vulnerabilities;
- `config:cache` and `route:cache` succeed;
- the local-only routes are provably absent outside `local`;
- the go-live blockers are in code.

What remains is server config (§5), post-deploy verification (§6) and three branches waiting on Ben (§1),
none of which blocks staging.

**Conditions on the GO**, all server-side and all in `verification/CHECKLIST.md` §0:
1. Staging behind **basic-auth**, with **`/webhooks/stripe` and `/webhooks/resend` exempted**.
2. Staging **noindexed at the server**. The app itself doesn't noindex any host (see §4).
3. The admin site-email setting pointed at a **test inbox** before the first test booking.

---

## 1. Repo & branch state
- `git status`: **clean.** The only ignored local noise is `.playwright-mcp/` (screenshot scratch, excluded),
  `database/database.sqlite` (pre-MySQL leftover, untracked, the app runs on MySQL), `.DS_Store` and
  `.phpunit.result.cache`.
- **No debug leftovers:** no `dd(`/`dump(`/`ray(` in `app`/`routes`/`config`/`database`, no `console.log`
  in `resources/js`.
- **No skipped or incomplete tests.**
- **Unmerged branches (3), all deliberate, none half-done, none about to deploy:**

  | Branch | Head | vs `main` | What it is | Status |
  |---|---|---|---|---|
  | `fix/fk-delete-rules` | `823d4a2` | 1 ahead, 31 behind | 008 **Phase 1 only**: the FK delete-rule proposal table (`audits/reports/fk-delete-rules.md`) | waits for Ben's approval; Phase 2 not built. Merges cleanly. |
  | `ui/primary-strong-contrast` | `fc0884a` | 1 ahead, 5 behind | 016 brand contrast, option B (axe 173 → 0) | **Ben looks, then merges.** Test-merge: conflict **only in `DECISIONS.md`**, two appends at the end of the log; keep both. |
  | `ui/feature-split-ratio` | `0a37a7b` | 1 ahead, 4 behind | 017 feature-split 16:10 | **Ben looks, then merges.** Same `DECISIONS.md`-only append conflict. |

- Every other branch (local and remote) is fully merged into `main`. The merged remote branches are
  deleted in this run's housekeeping.

## 2. Build & test health (actual results at `5663177`)
- **`composer check`: PASS.** Pint clean, phpstan 0 errors, **476 tests / 2104 assertions** (SQLite).
- **MySQL parity, `php artisan test -c phpunit.mysql.xml`: PASS, 476/476** on MySQL 8. Run because this
  run's merges touched settings and schema migrations (012, 013) and queries (014).
- **`composer audit`:** no advisories.
- **`npm audit`:** 0 vulnerabilities, both production-only (`--omit=dev`) and all.
  - The dev-only `shell-quote` criticals noted at the last gate are gone.
- **`npm run build`:** passes.
- **`composer validate --strict`:** passes. PHP is pinned with `"php": "^8.4.1"` and
  `config.platform.php = 8.4.1` (019).

## 3. What's actually DONE vs PENDING (verified against the code)

**Go-live blockers — all DONE, in code:**

| Blocker | Evidence |
|---|---|
| GDPR export + erasure | `app/Actions/ExportCustomerData.php`, `app/Actions/EraseCustomerData.php`, the Customer resource row actions, the `erased_at` migration |
| Privacy-scrubbed error monitoring | `config/sentry.php`: `send_default_pii` false, `before_send => [SentryScrubber::class, 'scrub']` (array callable, `config:cache`-safe, proven above). DSN via `SENTRY_LARAVEL_DSN`. |
| Production database | `mysql` is the default connection (`.env.example` `DB_CONNECTION=mysql`); MySQL parity suite green |
| Payment + webhook handling | `StripeWebhookController` verifies the signature; `HandleStripeWebhook` dispatches `checkout.session.completed` / `checkout.session.expired`. stripe-php 22 sends API version `2026-09-30.endive`. |
| No known admin login on servers | `DevAdminSeeder` returns early unless `local`; `DatabaseSeeder` calls it only in `local` (006, guarded by `NoSeededAdminOnServersTest`) |
| No sample reviews behind a public rating | `TestimonialSeeder` local-only; `AggregateRating` needs 3+ customer-linked reviews (007) |

**Run 2 — DONE (merged, each on green):**
- 019 PHP platform pin (`a7c47ce`);
- 005 FAQ admin 500 (`fc94134`);
- 006 no seeded admin on servers (`fc9a0e4`);
- 007 sample testimonials off servers and an honest rating (`b5d6803`);
- 009 admin acts email the customer (`088f90e`);
- 010 voucher payment-success page (`ee6e64c`);
- 011 CID mail logo (`eff136b`);
- 012 CMS orphans (`6a7c6e8`);
- 013 homepage SEO from settings (`31e46bc`);
- 014 price tokens in CMS copy (`30e8c89`);
- 015 consistency small fixes (`1ec6b75`);
- 018 launch checklist tailored (`c23763a`).

**PENDING (not blockers for staging):**
- **016 brand contrast:** WCAG AA colour contrast is met only once Ben merges it. Until then axe reports
  173 `color-contrast` nodes on the public pages. *Not a staging blocker*, but should be in before launch.
- **017 feature-split ratio:** visual, Ben's call.
- **008 Phase 2:** database-level FK delete rules. App-level guards (`GuardsDeletion`) already block
  money-linked deletes in the admin.
- **Owner content:**
  - real photos, Hall of Fame, privacy `[Owner: …]` lines, dropzone addresses;
  - replacing typed prices in existing CMS text with tokens (list in DECISIONS, 014);
  - the open owner decisions in `RUN-REPORT-2.md`.

## 4. Production config readiness
- **`.env.example` covers every app-specific variable:** `STRIPE_KEY` / `STRIPE_SECRET` /
  `STRIPE_WEBHOOK_SECRET`, `RESEND_API_KEY` / `RESEND_WEBHOOK_SECRET`, `MAIL_MAILER`, `MAIL_FROM_*`,
  `MAIL_INBOUND_DOMAIN`, `HORIZON_TOKEN`, `SENTRY_LARAVEL_DSN`, `SETTINGS_CACHE_ENABLED`,
  `SESSION_SECURE_COOKIE`, plus DB/Redis.
  - Script check: 184 `env()` names are read in `config/`. The 126 not in `.env.example` are all optional
    framework/package knobs with defaults (auth, cache/queue alternative drivers, Sentry tuning, Slack,
    Postmark…). None is app-specific.
  - **No `env()` call outside `config/`**, so `config:cache` is safe.
- **Local-only features are gated, proven by behaviour:**
  - `php artisan route:list --path=dev` shows **0** routes under `APP_ENV=production` and `staging`
    (3 under `local`);
  - `DevAdminSeeder` and `TestimonialSeeder` return early outside `local`;
  - the account-login dev shortcut renders only in `local`.
- **Safe defaults:**
  - `config/app.php` defaults to `APP_ENV=production` and `APP_DEBUG=false`. `.env.example` is the local
    template (`local` / `true` / `MAIL_MAILER=log`), which servers must override per SETUP.
  - No secrets committed.
- **Filesystem:** the only path reads are `public_path()` checks for bundled images (`ResponsiveImage`,
  `MailLogo`), both server-safe. Uploads go to the `public` disk (needs `storage:link`). No SQLite file
  is used at runtime.
- **Drivers:** queue and cache use `redis`, sessions `database`. None is `sync`/`array`/`file`.
- **`php artisan config:cache`: succeeds** (no closures in config); `route:cache` succeeds; both cleared
  again afterwards.
- **Flag, staging indexing:** `/robots.txt` (`routes/web.php:91`) allows crawling on every host, and
  nothing sends `noindex` by environment. Staging must be noindexed and basic-authed **at the server**
  (condition 2 above).
  - A small code follow-up could make non-production hosts send `Disallow: /` plus a `noindex` meta.
  - SETUP.md doesn't mention this yet; `verification/CHECKLIST.md` §0 does.

## 5. Server-side deployment checklist (Ploi / Hetzner — staging first)
Specifics are in SETUP.md ("Deploying", "Required environment variables", "Queues, Horizon & scheduler").
1. **PHP 8.4.x** (the lock and the platform pin need ≥ 8.4.1; not 8.3) with `pdo_mysql`, **MySQL 8+**
   (DB plus a dedicated user), and **Redis**.
2. **Env vars:**
   - `APP_KEY` (generate once and store it in a password manager; never in the deploy script);
   - `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://…`;
   - Stripe TEST keys plus `STRIPE_WEBHOOK_SECRET`;
   - `MAIL_MAILER=resend` with `RESEND_API_KEY` and a verified `MAIL_FROM_ADDRESS` (or `log` to send
     nothing);
   - `SETTINGS_CACHE_ENABLED=true`, `SESSION_SECURE_COOKIE=true`, `SENTRY_LARAVEL_DSN`, `HORIZON_TOKEN`.
3. **Build:** `composer install --no-dev`, then `npm ci && npm run build`.
4. **`php artisan migrate --force`.** NEVER `migrate:fresh`/`refresh`/`db:wipe`.
   - This run adds a schema migration (012 drops 3 orphaned columns) and settings migrations (012 removes
     `home.team_lead`; 013 adds `home.seo_*`).
   - Then `php artisan settings:clear-cache`: 013 added settings properties, and an unmigrated DB 500s the
     homepage (`MissingSettings`).
5. **`php artisan storage:link`**, `config:cache`, reload `php8.4-fpm`, and **`horizon:terminate` last.**
6. **Horizon under Supervisor** (`autorestart=true`). Without it nothing sends.
7. **`schedule:run` cron** (`* * * * *`). It's **silent if missing**: `bookings:send-reminders` 09:00,
   `courses:send-reminders` 09:10, `bookings:release-expired-holds` every 15 min.
8. **Stripe TEST webhook** → `https://…/webhooks/stripe` for `checkout.session.completed` and
   `checkout.session.expired`.
   - **Create it with API version `2026-09-30.endive`**, matching stripe-php 22. SETUP.md doesn't say this
     yet; `verification/CHECKLIST.md` §0 does.
   - Inbound email is optional on staging: a separate subdomain and secret.
9. **First admin:** `php artisan make:filament-user --panel=admin`.
   - Never `db:seed` a server expecting a login.
   - On any server seeded before October 2026, delete `test@example.com` (SETUP "First run") and unapprove
     the sample testimonials.
10. **Basic-auth plus server-level noindex** for staging, exempting `/webhooks/*` (§4 flag).
11. **SSL** (Let's Encrypt), and **monitoring/heartbeat** for Horizon and the scheduler.
12. **Staging mail sandboxing:** Admin → General settings → site email (`GeneralSettings::email`, a DB
    value) = a test inbox.

## 6. Can only be verified AFTER deploy (expected-pending, not failures)
Run the human launch gate **`verification/CHECKLIST.md`**, tailored in this run (018):
- §0 staging setup;
- §1 every money path, with the expected Stripe amounts in pence → £;
- §2 every email in a real inbox (logo with images blocked, links, Reply-To);
- §6b real phones;
- §7 the silent killers;
- §8 the live flip.

Specifically pending until a server exists:
- real Stripe webhook delivery (booking → capacity → hold release);
- email deliverability with DKIM/SPF on the real domain;
- SSL;
- a real scrubbed Sentry event (`php artisan sentry:test`);
- the inbound-email round-trip;
- the Ploi Horizon-stats card;
- the Gmail/Outlook check of the CID-embedded logo (011);
- a manual money-path test with a test card.
