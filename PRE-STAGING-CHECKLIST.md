# Pre-staging readiness gate — go/no-go

Read-only readiness check before deploying to staging (Ploi + Hetzner). Every claim is
verified against the actual code/config at the stated evidence, not against what an audit
*says* it did.

- **Branch / commit:** `main` @ `8c96875` (`fix(horizon): add staging supervisor environment`),
  in sync with `origin/main`.
- **History:** a prior gate returned ✅ GO at `8a1bd43`; this is a **re-verification** at the
  current head, 113 commits later (redesign + UI passes, image performance pass, accessibility
  fixes, the Horizon `staging` environment, and the Ploi/Horizon-stats integration have all
  landed since, each merged with `composer check` green).

## Verdict: ✅ GO for staging

The codebase is clean, complete and safely configured. The three go-live blockers (GDPR
erasure, privacy-scrubbed Sentry, MySQL) remain present in code; the one staging-specific
gap found during this re-check (Horizon would start zero workers on `APP_ENV=staging`) is
now **resolved** (`fix(horizon)` @ `8c96875`). The remaining work is server/owner config and
post-deploy verification, not code.

---

## 1. Repo & branch state
- **Only `main`** exists locally and on `origin` (all feature branches merged + deleted; verified
  via `git ls-remote --heads origin` → `main` only). `main` ↔ `origin/main` is 0/0.
- Working tree clean apart from the local-only `.playwright-mcp/` screenshot dir (git-ignored noise,
  never deployed) and `database/database.sqlite` (untracked pre-MySQL leftover; the app runs on MySQL).
- No debug leftovers (`dd`/`dump`/`ray`/`console.log`) and no skipped tests.

## 2. Build & test health
- **`composer check`: PASS** — pint clean, phpstan/larastan 0 errors, **369 tests / 1483
  assertions** (SQLite driver).
- **MySQL production-parity gate: PASS** — `php artisan test -c phpunit.mysql.xml` → **369/369
  green on real MySQL 8** (re-run at this head because post-GO work touched settings migrations and
  added the `disciplines` table; CLAUDE.md requires this gate for migration/query changes).
- `composer audit` clean; `npm run build` passes. (`npm audit`'s criticals are dev-only
  `shell-quote` via `concurrently`, never in the production bundle — documented in DECISIONS.md.)

## 3. Go-live blockers — all present in code
- **GDPR export + erasure** — `ExportCustomerData` / `EraseCustomerData` actions + Customer resource
  row actions + `erased_at` migration.
- **Privacy-scrubbed Sentry** — `send_default_pii=false`, `before_send => [SentryScrubber::class]`
  (array callable, `config:cache`-safe); no-ops with an empty DSN.
- **MySQL** — `mysql` connection default; `phpunit.mysql.xml` parity profile (green, above);
  `db-migration/MYSQL-NOTES.md` (migrations + tested backup/restore).

## 4. Production config readiness
- `.env.example` documents every required var: APP_KEY/ENV/DEBUG/URL, Stripe (key/secret/webhook),
  Resend (key + from + webhook secret + inbound domain), Redis, DB, `SESSION_SECURE_COOKIE`,
  `SENTRY_LARAVEL_DSN`, `HORIZON_TOKEN` (Ploi stats; empty placeholder).
- Local-only `routes/dev.php` is loaded **only** under `app()->environment('local')` (idempotent
  `require_once`) — `/dev/*` is unreachable in staging/production.
- No secrets committed; `.env.example` ships the **local** template (`APP_ENV=local`,
  `APP_DEBUG=true`, `MAIL_MAILER=log`) — staging/production must override per SETUP.md.

## 5. Server-side deployment checklist (Ploi / Hetzner / staging)
Specifics in SETUP.md "Deploying" + "Required environment variables".
1. **PHP 8.3** + `pdo_mysql`; **MySQL 8+** (DB + dedicated user); **Redis** (cache/queue/sessions).
2. **Env vars** — `APP_KEY` (`key:generate`), `APP_ENV=staging`, `APP_DEBUG=false`, `APP_URL=https://…`,
   Stripe (incl. webhook secret), Resend (`MAIL_MAILER=resend`, from-address, inbound domain +
   `RESEND_WEBHOOK_SECRET`), `SETTINGS_CACHE_ENABLED=true`, `SESSION_SECURE_COOKIE=true`,
   `SENTRY_LARAVEL_DSN`, and **`HORIZON_TOKEN`** (random; the same value goes in Ploi → Laravel
   settings to show the Horizon stats card).
3. **Build** — `composer install --no-dev`, `npm ci && npm run build`.
4. **`php artisan migrate --force`** (NEVER `migrate:fresh`/`refresh`/`db:wipe` on a shared/staging
   DB with data); **`php artisan storage:link`**; **`php artisan settings:clear-cache`** after any
   deploy adding a settings property.
5. **Horizon under Supervisor** (`autorestart`) + `php artisan horizon:terminate` on deploy — now
   that `staging` is defined it will actually run workers (maxProcesses=3). **Without the process
   running, nothing sends.**
6. **`schedule:run` cron** (`* * * * *`) — without it reminders / abandoned-checkout releases /
   balance chasers die silently.
7. **Stripe webhook** → `https://…/webhooks/stripe` (`checkout.session.completed` + `…expired`);
   copy the signing secret. **Inbound email** (optional on staging): MX → Resend + the
   `/webhooks/resend` webhook + `MAIL_INBOUND_DOMAIN`/`RESEND_WEBHOOK_SECRET`; use a separate
   subdomain + secret so prod inbound is untouched.
8. **SSL** (Let's Encrypt) + **monitoring** (Horizon + scheduler must be up; add a heartbeat).
9. **Staging mail sandboxing** — set the **`GeneralSettings::email`** admin setting (a DB value, not
   env) to a test inbox so owner alerts / Reply-To on test bookings stay internal; or leave
   `MAIL_MAILER=log` to send nothing.

## 6. Verifiable only AFTER deploy (owner; not blockers to leave local)
Real Stripe webhook delivery (booking → capacity decrement → slot release), email deliverability +
DKIM/SPF, SSL provisioning, a real scrubbed Sentry event (`sentry:test`), inbound-email round-trip,
the Ploi Horizon-stats card appearing, and a manual money-path test (test card → confirmation).
