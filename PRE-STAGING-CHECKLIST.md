# Pre-staging readiness gate — go/no-go

Kit gate `gates/pre-staging-gate.md`, run verbatim and report-only as item 9 of unattended run 3. Read-only:
nothing was changed except this file. Every claim is checked against the code, git or a command result, not
against what a report says.

- **Commit:** `main` @ `3b824b4` (`docs: run 3 — 8 024 table focus recorded`), in sync with `origin/main`.
  Report written on `docs/pre-staging-gate-run3`.
- **History:**
  - The previous gate (`5663177`, run 2) returned ✅ GO with three server-side conditions.
  - Since then, run 3 merged 8 branches on green: Ben's answers, 016, 008 Phase 2, and 020–024. `git log
    4ee5960..HEAD --merges` lists all eight.

## Verdict: ✅ GO for staging

The codebase is clean, complete and safely configured to leave local:
- the full gate is green on both drivers (**520 tests**);
- 0 known vulnerabilities;
- `config:cache` and `route:cache` succeed;
- the local-only routes are provably absent outside `local`;
- the go-live blockers are in code.

New since run 2: WCAG AA contrast is met (016 merged; axe 0 nodes on 20 public pages × 2 widths), the
database refuses money-linked deletes (008), and **the app now noindexes every non-production host itself**
(022). One of run 2's three conditions is therefore met in code.

**Conditions on the GO**, both server-side, both in `SETUP.md` "Staging" and `verification/CHECKLIST.md` §0:
1. Staging behind **basic-auth**, with **`/webhooks/stripe` and `/webhooks/resend` exempted**.
2. **`APP_ENV=staging`** (not `production`), so the app's noindex applies, and the admin site-email setting
   pointed at a **test inbox** before the first test booking.

---

## 1. Repo & branch state
- `git status`: **clean.** Local-only noise is ignored or excluded: `.playwright-mcp/` (screenshot scratch),
  `database/database.sqlite` (pre-MySQL leftover, untracked), `.DS_Store`, `.phpunit.result.cache`.
- **No debug leftovers:** no `dd(`/`dump(`/`ray(` in `app`/`routes`/`config`/`database`, and no `console.log`
  in `resources/js`.
- **No skipped or incomplete tests.**
- **Unmerged branches: 1**, deliberate:

  | Branch | Head | What it is | Status |
  |---|---|---|---|
  | `ui/feature-split-ratio` | `0a37a7b` | 017 feature-split 16:10 | **Withdrawn by Ben (9 Oct):** the 1024 imbalance is worse than the side-trim. Not to be merged; deleted in this run's housekeeping. |

- Every other remote branch (`fix/fk-delete-rules`, `ui/primary-strong-contrast` and this run's six) is fully
  merged into `origin/main` (`git branch -r --no-merged origin/main` lists only 017). They're deleted in this
  run's housekeeping.

## 2. Build & test health (actual results at `3b824b4`)
- **`composer check`: PASS.** Pint clean, phpstan 0 errors, **520 tests / 2299 assertions** (SQLite).
- **MySQL parity, `php artisan test -c phpunit.mysql.xml`: PASS, 520/520** on MySQL 8. Required: this run
  changed foreign keys (008) and added a locking query (021).
- **`composer audit`:** no advisories.
- **`npm audit`:** 0 vulnerabilities (both `--omit=dev` and all).
- **`npm run build`:** passes.
- **`composer validate --strict`:** passes.
- **axe-core 4.10.2** on the rebuilt `main`, 20 public pages × 1440/390: **0 violations of any rule** (was 173
  `color-contrast` nodes at run 2's gate).

## 3. What's actually DONE vs PENDING (verified against the code)

**Go-live blockers — all DONE, in code:**

| Blocker | Evidence |
|---|---|
| GDPR export + erasure | `app/Actions/ExportCustomerData.php`, `app/Actions/EraseCustomerData.php`; erasure re-proven with the new RESTRICT rules (`MoneyLinkedDeletesTest::test_erasure_still_works_on_a_customer_with_paid_bookings`) |
| Privacy-scrubbed error monitoring | `config/sentry.php`: `send_default_pii` false; `before_send` is an array callable (`config:cache` succeeds) |
| Production database | `mysql` default; MySQL parity suite green |
| Payment + webhook handling | `StripeWebhookController` verifies the signature; `HandleStripeWebhook` dispatches the two handled events |
| Money-linked records can't be destroyed | 14 FKs RESTRICT (`2026_10_09_140000_restrict_money_linked_deletes`), a model listener on every `GuardsDeletion` model, `GuardedParentsRestrictDeletesTest` |
| No overbooking | online checkout and reschedule lock the slot and share `TandemDate::isFull()` (`RescheduleCapacityTest`) |
| No known admin login on servers; no sample reviews behind a rating | `DevAdminSeeder` / `TestimonialSeeder` local-only (006, 007) |

**Run 3 — DONE (merged, each on green):**
- Ben's answers recorded (`90a9cbc`);
- 016 `primary-strong` contrast (`98584c7`);
- 008 Phase 2 FK delete rules (`b10a2c9`);
- 020 button hover/press (`6f0e021`);
- 021 reschedule capacity (`eeba9cf`);
- 022 noindex non-production + SETUP staging (`b22d879`);
- 023 email/PDF blue (`617ef9b`);
- 024 scrollable table focus (`32253e1`).

**PENDING (not blockers for staging):**
- **017:** withdrawn; nothing to do.
- **Owner content:**
  - real photos, Hall of Fame, privacy `[Owner: …]` lines, dropzone addresses;
  - replacing typed prices in existing CMS text with tokens (DECISIONS, 014);
  - the weight surcharges stay typed (Ben, 9 Oct), so change both places by hand.
- **Open owner decisions:** none left from run 2; any new ones are in `RUN-REPORT-3.md`.

## 4. Production config readiness
- **`.env.example` covers every app-specific variable** (Stripe ×3, Resend ×2, `MAIL_*`, `MAIL_INBOUND_DOMAIN`,
  `HORIZON_TOKEN`, `SENTRY_LARAVEL_DSN`, `SETTINGS_CACHE_ENABLED`, `SESSION_SECURE_COOKIE`, DB/Redis).
  - Script check: 182 `env()` names are read in `config/`; the 124 not in `.env.example` are optional
    framework/package knobs with defaults (e.g. `APP_PREVIOUS_KEYS`, `HORIZON_PATH`, `SETTINGS_CACHE_MEMO`).
  - **No `env()` call outside `config/`.**
- **Local-only features are gated, proven by behaviour:** `route:list --path=dev` shows **0** routes under
  `APP_ENV=production` and `staging` (3 under `local`). The dev seeders return early outside `local`.
- **Safe defaults:** `config/app.php` defaults to `production` and `APP_DEBUG=false`. No secrets committed.
- **Indexing (run 2's flag) — resolved in code:** outside `production`, `/robots.txt` is `Disallow: /` and every
  web response carries `X-Robots-Tag: noindex, nofollow` (022). Production output is byte-identical to before
  (tested). This depends on **`APP_ENV` being right**: a staging box set to `production` would be indexable.
- **Filesystem:** no runtime SQLite; uploads on the `public` disk (needs `storage:link`); voucher PDFs on the
  private `local` disk.
- **Drivers:** queue and cache use `redis`, sessions `database`.
- **`php artisan config:cache` and `route:cache`: succeed**, and both were cleared afterwards.

## 5. Server-side deployment checklist (Ploi / Hetzner — staging first)
Specifics are in SETUP.md ("Staging" is new this run, plus "Deploying", "Required environment variables",
"Queues, Horizon & scheduler").
1. **PHP 8.4.x** (≥ 8.4.1) with `pdo_mysql`, **MySQL 8+**, and **Redis**.
2. **Env vars:**
   - `APP_KEY` (generate once and keep it in a password manager);
   - **`APP_ENV=staging`**, `APP_DEBUG=false`, `APP_URL=https://…`;
   - Stripe TEST keys plus `STRIPE_WEBHOOK_SECRET`;
   - `MAIL_MAILER=resend` with `RESEND_API_KEY` and a verified `MAIL_FROM_ADDRESS` (or `log`);
   - `SETTINGS_CACHE_ENABLED=true`, `SESSION_SECURE_COOKIE=true`, `SENTRY_LARAVEL_DSN`, `HORIZON_TOKEN`.
3. **Build:** `composer install --no-dev`, then `npm ci && npm run build`.
4. **`php artisan migrate --force`.** NEVER `migrate:fresh`/`refresh`/`db:wipe`.
   - This run adds `2026_10_09_140000_restrict_money_linked_deletes` (FK rules only). It **aborts with a list if
     any orphaned row exists** and changes nothing; fix the data, then re-run.
   - Then `php artisan settings:clear-cache`.
5. **`php artisan storage:link`**, `config:cache`, reload `php8.4-fpm`, and **`horizon:terminate` last.**
6. **Horizon under Supervisor** (`autorestart=true`).
7. **`schedule:run` cron** (`* * * * *`). It's **silent if missing**: reminders at 09:00 and 09:10, hold release
   every 15 min.
8. **Stripe TEST webhook** → `https://…/webhooks/stripe`, for `checkout.session.completed` and
   `checkout.session.expired`, **created with API version `2026-09-30.endive`** (now in SETUP "Staging").
9. **First admin:** `php artisan make:filament-user --panel=admin`. On any server seeded before October 2026,
   delete `test@example.com` and unapprove the sample testimonials.
10. **Basic-auth** for staging, exempting `/webhooks/stripe` and `/webhooks/resend`. Noindex is now the app's job
    (a server header as well does no harm).
11. **SSL** (Let's Encrypt), and monitoring for Horizon and the scheduler.
12. **Staging mail sandboxing:** Admin → General settings → site email = a test inbox.

## 6. Can only be verified AFTER deploy (expected-pending, not failures)
Run the human launch gate **`verification/CHECKLIST.md`**:
- §0 staging setup (the noindex item now checks the app's header and robots file);
- §1 every money path;
- §2 every email in a real inbox (the buttons are now `#0078cc`; check the logo with images blocked);
- §5 refusals (new: rescheduling into a full slot is refused);
- §6b real phones;
- §7 the silent killers;
- §8 the live flip.

Specifically pending until a server exists:
- real Stripe webhook delivery;
- DKIM/SPF deliverability;
- SSL;
- a real scrubbed Sentry event;
- the inbound-email round-trip;
- the Gmail/Outlook check of the CID logo and the new button colour;
- a manual money-path test with a test card.
