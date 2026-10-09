# Setup

Laravel 12-skeleton app on Laravel 13 / PHP 8.4 with a Blade/Tailwind/Alpine frontend,
Livewire 4 forms and a Filament v5 admin panel at `/admin`.

## Requirements

- **PHP 8.4.x on the server** (8.4.1 or later; the lock needs it). `composer.json` requires `^8.4.1`
  and pins `config.platform.php` to `8.4.1`, so a laptop on a newer PHP can't lock packages the server
  can't run. Moving the server to 8.5 means raising the pin first. Composer,
  Node `^20.19` or `>=22.12` for the build (Vite 8); Node 22+ for the local `composer dev`
  script (concurrently 10)
- **MySQL 8+** — the application database (see "Production database" below). Locally via
  Laravel Herd, or `brew install mysql && brew services start mysql`; create a
  `thrill_seek` database. The `pdo_mysql` PHP extension is required.
- **Redis** — the cache and queue stores. Locally: `brew install redis && brew services
  start redis`. The PHP client is predis (composer dependency); no PHP extension needed.

## ⚠️ Production will silently break without these

1. **Cron** — `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`.
   Without it, abandoned-checkout holds are never released, booking/course
   reminders never send, and balance chasers never go out.
2. **Horizon under Supervisor** (config below) — all email and webhook
   side-effects are queued; without a worker nothing sends.
3. **Stripe webhook** registered for **both** `checkout.session.completed`
   AND `checkout.session.expired` (expiry releases held places and is the
   primary abandoned-checkout path).
4. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://your-domain`
   (emails build their links from APP_URL), `APP_NAME="G-Force Skydiving"`
   (appears in every email header).
5. After deploys that add settings properties: `php artisan settings:clear-cache`.

## Production security

Hardening that must be in place before the site is public (the app ships the
defence-in-depth headers and webhook throttling automatically — these are the parts
that depend on deployment/env):

- **Serve only over HTTPS.** Terminate TLS at your host/load balancer and redirect all
  HTTP → HTTPS. With HTTPS live, the app emits HSTS automatically (production + secure
  request only). Get a certificate (Let's Encrypt is fine).
- **`SESSION_SECURE_COOKIE=true`** so the session cookie is only ever sent over HTTPS.
  (`http_only` and `same_site=lax` are already set in `config/session.php`.)
- **`APP_DEBUG=false`** (and `APP_ENV=production`) — never expose stack traces / config
  in error pages. Already in the checklist above; it is also a security requirement.
- **Canonical HTTPS host.** Set `APP_URL=https://your-domain` and serve a single
  canonical host (redirect `www`/bare and any IP/hostname to it) so cookies, CORS and
  generated links all line up and there's no http fallback.
- **Owner/infra responsibilities (outside the app):** keep TLS certificates valid and
  auto-renewing; keep PHP and the OS patched; restrict `/admin` and `/horizon` exposure
  (they already require an admin login — consider IP allow-listing too); and put the site
  behind a CDN/WAF (e.g. Cloudflare) for TLS, DDoS protection and a web application
  firewall. These are deployment-layer concerns the application cannot enforce itself.
- The Content-Security-Policy currently ships in **Report-Only** mode; after the first
  production deploy, confirm a clean browser console (real Stripe redirect + any remote
  image hosts) and then switch it to enforcing — see DECISIONS.md (SEC-P3.1).

## Production database (MySQL)

The app runs on **MySQL 8+** in production and locally (SQLite is no longer used except
as the fast CI/`composer check` test driver — see below). See
`db-migration/MYSQL-NOTES.md` for the SQLite→MySQL migration notes.

- **Env vars** (`.env`):
  ```
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1        # or the managed-DB host
  DB_PORT=3306
  DB_DATABASE=thrill_seek
  DB_USERNAME=thrill_seek
  DB_PASSWORD=<strong password>
  # DB_SOCKET=             # set if connecting via socket instead of TCP
  ```
  Charset/collation default to `utf8mb4`/`utf8mb4_unicode_ci` (set in
  `config/database.php`). A **managed MySQL** (RDS, DigitalOcean, PlanetScale-compatible,
  etc.) is strongly recommended — you get backups, failover and patching for free.
- **Create the DB + a dedicated user** (don't use `root` in production):
  ```sql
  CREATE DATABASE thrill_seek CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'thrill_seek'@'%' IDENTIFIED BY '<strong password>';
  GRANT ALL PRIVILEGES ON thrill_seek.* TO 'thrill_seek'@'%';
  FLUSH PRIVILEGES;
  ```
- **On deploy:** run `php artisan migrate --force`. **NEVER run `migrate:fresh`,
  `migrate:refresh` or `db:wipe` against production** — they drop every table (this is how
  the admin user got wiped early in the build). Only `migrate` (forward-only) is safe.
- **Moving existing SQLite data to MySQL** (one-time, only if a SQLite prod file exists):
  the cleanest path is to point `.env` at MySQL, run `php artisan migrate --force` on the
  empty MySQL DB, then transfer rows with a tool that handles type/JSON differences
  (e.g. a short Laravel command reading from a second `sqlite` connection and writing via
  the models, or `mysql`-import after converting the dump). Verify row counts and spot-check
  the JSON columns (`bookings.customer_details`, `enquiries.context`) afterwards.

### Backups & restore (TESTED before go-live)

> **"We have backups" is worthless until a restore is proven.** Run the restore drill
> below once on a copy before launch.

- **Backup** — nightly `mysqldump` via cron (or use the managed-DB's automated
  backups/PITR if hosted, which is preferable):
  ```bash
  0 3 * * * mysqldump -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" \
      --single-transaction --routines --triggers --set-gtid-purged=OFF \
      thrill_seek | gzip > /backups/thrill_seek-$(date +\%F).sql.gz
  ```
  `--single-transaction` gives a consistent snapshot without locking; `--set-gtid-purged=OFF`
  is required or the dump won't restore onto another server (verified — without it the
  restore fails with `GTID_PURGED cannot be changed`).
- **Restore** (the tested procedure):
  ```bash
  gunzip < /backups/thrill_seek-YYYY-MM-DD.sql.gz \
    | mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" thrill_seek_restore
  ```
  Restore into a **separate** database first, compare row counts against production and
  spot-check a JSON column (`SELECT JSON_VALID(payload) FROM settings LIMIT 1;`), then
  cut over. This exact dump→restore→verify drill was run during the migration and passed
  (row counts matched; JSON intact).
- **Backups contain PII + medical data** (`customer_details`, `enquiries.context`,
  message bodies). Store them **encrypted and access-controlled** (encrypted bucket /
  encrypted volume), apply the same retention as the GDPR policy, and never commit a dump
  to git or leave it world-readable.
- **Retention:** keep enough daily backups to recover from a problem discovered late
  (e.g. 14–30 dailies + a few monthlies); managed-DB point-in-time recovery is ideal.

## Deploying (each release — Ploi/Hetzner)

Run these **in order** on every deploy (Ploi runs them as the deploy script). Steps 1–3
prepare the new code, 4–6 update state, and the worker restarts **last** so it never runs
stale code:

1. **Pull the release** — `git pull` (Ploi does this automatically).
2. **PHP deps** — `composer install --no-dev --optimize-autoloader`.
3. **Assets** — `npm ci && npm run build`.
4. **Migrate** — `php artisan migrate --force`. **NEVER `migrate:fresh`, `migrate:refresh`
   or `db:wipe` in production** — they drop every table. Only forward-only `migrate` is safe.
5. **Storage symlink** — `php artisan storage:link`. Required so owner-uploaded images on
   the `public` disk are served (bundled `/images/*` paths work without it). Idempotent and
   only needed once per environment — but **keep it in the Ploi deploy script**, because
   zero-downtime deploys that swap the release directory must recreate the symlink each
   release.
6. **Refresh caches** — `php artisan config:cache` (or at least `config:clear`),
   `php artisan cache:clear`, and **`php artisan settings:clear-cache`**. The settings clear
   is not optional: a deploy that adds a settings property otherwise leaves a **stale
   typed-settings cache** that silently fails queued email and 500s pages reading the new
   key (the mail layer now falls back gracefully for the sign-off, but other reads don't —
   clearing on every deploy is the mitigation).
7. **Reload PHP-FPM** — `sudo service php8.4-fpm reload` (the PHP version is part of the
   service name; match the server's PHP). Without it opcache keeps serving the previous
   release after a deploy that reported success.
8. **Restart the worker LAST** — `php artisan horizon:terminate` so Horizon restarts on the
   new code. Doing this before steps 4–7 would leave the worker running stale code.

There is no "converge permissions/roles" step: G-Force has no code-declared matrix to
re-sync. Admin access is a single `is_admin` flag, and settings properties are spatie
settings migrations that step 4 already applies (see DECISIONS, kit sync 2026-09).

**The deploy script must NEVER contain:**
- `php artisan key:generate` — a re-keyed app can't decrypt anything it encrypted
  (every session and cookie dies, signed URLs stop validating). Hosting templates
  sometimes include it.
- `migrate:fresh` or `migrate:refresh` (or `db:wipe`) — they drop every table.

**Owner task (once, after the first deploy):** open Ploi's generated deploy script and
delete any `php artisan key:generate` line it added.

**After a deploy that touches mail settings (and on the first deploy):**
- `php artisan gforce:mail-test you@yourdomain` — sends one plain email **synchronously** through
  the configured mailer and prints "Sent …" or the transport's actual error. Then check it arrived
  in a real inbox.
- The admin **Dashboard → Failed emails (last 7 days)** should read `0` after the first day, and
  **Email setup** should read `OK` (it flags `log`/`array` mailers, an empty `RESEND_API_KEY` and a
  placeholder `MAIL_FROM_ADDRESS` on any non-local server).
- Mail env vars, exactly as config reads them: `MAIL_MAILER=resend`, `RESEND_API_KEY`,
  `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`, and `APP_URL` (every link in a queued email is built
  from it inside the worker).
- Queued emails retry 4 times (30 s → 2 min → 10 min) on their own, whatever Horizon's
  supervisor `tries` says — see `App\Mail\QueuedMailable`.

**Must-be-running services (configure once — see "Queues, Horizon & scheduler"):**
- **Horizon** under Supervisor with `autorestart=true` — the queue worker; **without it
  nothing sends** (confirmations, reminders, magic-link logins).
- **`schedule:run` cron** (`* * * * * php …/artisan schedule:run`) — **silent if missing**:
  reminders, abandoned-checkout hold releases and balance chasers just stop, with no error.

## First run

**Local machine:**

```bash
composer setup          # install, .env, key, migrate, npm install + build
php artisan db:seed     # seed all current site content + the local dev admin
```

Locally, seeding also creates the dev admin `test@example.com` / `password` (`DevAdminSeeder`,
which runs only when `APP_ENV=local`).

**Staging and production:** `php artisan db:seed --force` seeds content only and **never creates a
user**. Create the real admin with your own email and a strong, unique password:

```bash
php artisan make:filament-user --panel=admin
```

It prompts for name, email and password (the password is stored hashed). Every `User` is staff
(`User::canAccessPanel()` trusts every row; customers are the separate `Customer` model), so only
create users this way.

**If a server was ever seeded before October 2026:** it has the old `test@example.com` / `password`
admin. Delete it there: `php artisan tinker --execute="App\Models\User::where('email','test@example.com')->delete();"`.

## Staging

Staging runs with `APP_ENV=staging` (never `production`). The launch checks themselves are in
[`verification/CHECKLIST.md`](verification/CHECKLIST.md) §0; these are the server conditions they rely on.

- **Basic-auth on the whole host, except `/webhooks/stripe` and `/webhooks/resend`.** Stripe and Resend
  can't send credentials, so without the exemption every webhook delivery 401s. The app has no basic-auth
  of its own; set it in the Ploi/nginx config.
- **Search engines are kept out by the app.** Any environment other than `production` sends
  `X-Robots-Tag: noindex, nofollow` on every response and serves a `/robots.txt` of `Disallow: /` with no
  Sitemap line. Check with `curl -I https://<staging-host>/` (with your basic-auth credentials). A server-level
  header as well does no harm.
- **The Stripe TEST webhook endpoint is created with API version `2026-09-30.endive`** (the version
  stripe-php 22 sends). An endpoint delivers events in its own version, so pick it when creating the
  endpoint; see [Stripe webhook](#stripe-webhook) for the URL and the two events.
- **Point the site email at a test inbox before the first test booking:** Admin → Site content → General
  settings → the site email (a database value, not env). Owner alerts and most Reply-To headers use it.

## Required environment variables

| Variable | Purpose |
| --- | --- |
| `RESEND_API_KEY` | Resend API key. Also set `MAIL_MAILER=resend` in production (locally `log` is fine). |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | The from-address customers see. Must be a domain verified in Resend. |
| `STRIPE_KEY` | Stripe publishable key (pk_…). |
| `STRIPE_SECRET` | Stripe secret key (sk_…) — used to create Checkout sessions. |
| `STRIPE_WEBHOOK_SECRET` | Signing secret (whsec_…) for the webhook endpoint below. |
| `SETTINGS_CACHE_ENABLED` | `true` — caches site settings; busts automatically on save. |
| `CACHE_STORE` / `QUEUE_CONNECTION` | `redis` (default). All email and webhook side-effects are queued. |
| `REDIS_HOST` / `REDIS_PORT` / `REDIS_CLIENT` | Redis connection; client is `predis`. |

Missing Stripe/Resend keys never break the site: enquiries still store, admin actions
surface a clear notification, and failures are logged.

## Stripe webhook

Register this endpoint in the Stripe dashboard (Developers → Webhooks):

```
POST https://your-domain.example/webhooks/stripe
```

Subscribe to **`checkout.session.completed` and `checkout.session.expired`** and copy
the signing secret into `STRIPE_WEBHOOK_SECRET`. The endpoint is CSRF-exempt and
signature-verified; retries are idempotent. Completed sessions confirm bookings /
issue vouchers; expired sessions release held places immediately (with the
`bookings:release-expired-holds` scheduled sweep as the safety net).

For local testing: `stripe listen --forward-to localhost:8000/webhooks/stripe`.

## Queues, Horizon & scheduler

Queues run on Redis under **Laravel Horizon**. The dashboard lives at `/horizon`
(linked from the admin sidebar under “System”) and requires an admin login.

```bash
php artisan horizon               # or: composer dev (serves, horizon, logs, vite)
php artisan schedule:work         # reminders (bookings 09:00, courses 09:10) + hold release sweep (15 min)
```

In production run Horizon under Supervisor and add the standard scheduler cron entry:

```ini
; /etc/supervisor/conf.d/horizon.conf
[program:horizon]
process_name=%(program_name)s
command=php /path/to/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/horizon.log
stopwaitsecs=3600
```

```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

On macOS (launchd) the equivalent is a LaunchAgent plist running
`php artisan horizon` with `KeepAlive: true`. After every deploy run
`php artisan horizon:terminate` so Horizon restarts with the new code, and
`php artisan settings:clear-cache` if a deploy added settings properties
(the cached payload otherwise lacks the new keys and pages 500).

## Day-to-day commands

```bash
php artisan test                  # full suite (never commit red)
./vendor/bin/pint                 # code style
./vendor/bin/phpstan analyse      # static analysis (level 6)
php artisan bookings:send-reminders   # manual reminder run
php artisan courses:send-reminders     # manual course-reminder run
# Local only: /dev/mail lists a rendered preview of every email the system sends
```

## Feature toggles (Settings → General → Features)

- **Online shop** — OFF by default. When off, Shop is hidden from the menu, footer
  and sitemap and `/shop` returns 404. Turn on once the storefront is ready.
- **Online payments** — ON by default. Turn OFF to run the site **enquiry-first**:
  public "book/buy" buttons send an enquiry (capturing the chosen date/course)
  instead of taking card payment. Admin Stripe links, bank-transfer recording and
  the Stripe webhook keep working regardless — the toggle only affects the public
  site. Changes take effect immediately.
- **News** — ON by default. When off, News is hidden from the menu, footer, sitemap
  and the home page, and `/news` returns 404. Articles are written under News →
  News articles (drafts and future publish dates stay hidden until live).

The booking calendar only appears in the admin once at least one booking exists.

## Newsletter

- Subscriptions use **double opt-in**: a signup stores a pending record and emails a
  confirmation link; the subscriber is only mailed newsletters after confirming.
- No new environment variables. Sending uses the existing **Resend** config and the
  queue worker; the signed confirm/unsubscribe links require `APP_KEY` (already set).
- Subscriber list, CSV export and manual add live under “Newsletter subscribers”;
  compose/send and history live under “Newsletters”. Sends go to confirmed
  subscribers only and each email has a one-click unsubscribe.
- (List management is in our own DB, not Resend Audiences — see DECISIONS.md.)

## Where things live

- **Site content** — every public page is editable under `/admin` (“Site content”
  group): per-page settings screens plus Testimonials, Hall of Fame, Shop items,
  Gallery and Instructors resources.
- **Sales** — “Bookings & sales” group: Enquiries inbox (reply threads, payment links,
  bank transfers), Products & pricing, Bookings + calendar, Tandem dates, AFF courses, Locations,
  Vouchers, Customers, Newsletter subscribers, Newsletters, Email templates.
- **Email templates** — all customer-facing automated emails are editable records with
  `{{ placeholder }}` variables listed on each template's edit screen.
- **Prices** — entered and shown in **pounds** in the admin (e.g. 260.00); stored
  internally as pence. Just type the pound amount; the conversion is automatic.
- **DECISIONS.md** — the judgement calls made during the build and why.

## Newsletter builder

- Newsletters are composed from content blocks under **Bookings & sales →
  Newsletters** (build → preview → test → send). Blocks render to email-safe HTML
  (tables + inlined CSS) via Laravel's Markdown Mail + the gforce theme.
- **`APP_URL` must be the real public URL in production** — newsletter images and
  links are made absolute from it (email clients require absolute URLs), as are the
  signed unsubscribe links. A wrong `APP_URL` breaks images/links in sent emails.
- Sends are queued (one per confirmed subscriber) and idempotent (a per-recipient
  claim row), so the queue worker must be running (see Queues above). Scheduling is
  groundwork only (status + scheduled_at columns exist) — see DECISIONS.md.

## SEO

- **Meta** is centralised in `layouts/app.blade.php`: every page gets a unique
  `<title>`/description, a self-referencing `<link rel="canonical">`, complete
  Open Graph/Twitter tags and an absolute `og:image`. Pages override via
  `@section('title' | 'description' | 'og_image' | 'og_type' | 'robots')`.
- **Sitemap**: `https://<host>/sitemap.xml` (absolute URLs, includes every published
  news article with `lastmod`). **Robots**: `https://<host>/robots.txt` (dynamic
  route, references the sitemap, disallows `/admin` and `/dev`). Only in `production`: every other
  environment serves `Disallow: /` and sends `X-Robots-Tag: noindex, nofollow` (see [Staging](#staging)).
  - Note: some nginx/Valet configs have a `location = /robots.txt` block that serves
    a static file and 404s when absent — ensure the server falls through to
    `index.php` so the dynamic route is hit (standard Laravel nginx `try_files` does).
- **Structured data** (JSON-LD) is built from real data in `App\Support\StructuredData`
  and emitted via `<x-seo.json-ld>` (Organization sitewide; Product/Event/Article/
  AggregateRating/Breadcrumb per page).
- **Production SEO steps** (owner/infra):
  - Enforce one canonical host (www vs apex) and HTTPS — canonical tags then point at
    the live host.
  - Verify the domain in **Google Search Console** and submit `sitemap.xml`.
  - Create/claim a **Google Business Profile** for the Devon dropzone (local SEO).
  - Replace the `og:image` (Settings → General) with a branded 1200×630 share image,
    and provide a square 512px app icon for the web manifest.
  - Add the real business **postal address** (Settings → General, once the fields
    exist) so the structured data upgrades to a full LocalBusiness address.

## Inbound email setup (threading customer replies)

Customer replies thread back into the enquiry automatically via Resend inbound email.
The code is in place; these one-time DNS + dashboard steps connect it (they can't be
done in code).

1. **Pick a receiving subdomain** for replies, e.g. `reply.gforce.co.uk`. Add it as a
   domain in the Resend dashboard and add the **MX record** Resend shows for it to your
   DNS (typically `reply  MX 10 inbound.resend.com`). Wait for it to verify.
2. **Add an inbound route / webhook** in Resend pointing at
   `https://<your-app>/webhooks/resend` for the `email.received` event. Resend signs
   these (Svix); copy the **signing secret** (`whsec_…`).
3. **Set env vars** (see `.env.example`):
   - `MAIL_INBOUND_DOMAIN=reply.gforce.co.uk`
   - `RESEND_WEBHOOK_SECRET=whsec_…`
   - `RESEND_API_KEY=…` (already set for sending; the second-step body fetch reuses it)
   - `APP_URL` must be the real public URL.
4. **Run the queue** — inbound processing is queued (Horizon). The webhook returns
   immediately; the `ProcessInboundEmail` job does the fetch + threading.
5. **Test**: open an enquiry in the admin and send the customer a reply (its reply-to is
   `enquiry+<token>@<inbound domain>`). Reply to that email from another account; within
   a moment the reply appears in the enquiry thread, the enquiry flips to **Customer
   replied**, and the Enquiries badge increments. Watch it process in **Horizon**.
   - Mail that can't be routed (unknown token, etc.) lands under **Unmatched messages**
     in the admin rather than being dropped.
   - The exact Resend inbound *fetch* endpoint is encapsulated in
     `App\Support\Inbound\ResendInboundEmailFetcher` — confirm its path/shape against
     the current Resend docs when wiring the live domain; it's the single integration
     seam and degrades gracefully (logs + Unmatched) if a field is missing.

**BCC dropbox (deferred):** capturing emails the owner sends from their *own* mail
client (by BCC'ing a dropbox address) is documented as future work — it needs reliable
matching by customer address and a manual-outbound message type, and wasn't built to
keep this round focused on the core reply-threading loop. The inbound webhook +
`HandleInboundEmail` action are the foundation to add it later.

## Customer accounts (passwordless "My Account")

Customers who have booked can sign in at `/account/login` to view bookings, pay
balances, read messages and leave reviews.

- **No new env vars.** Sign-in links email through the existing **Resend** config and
  the queue worker; the links rely on `APP_KEY` (already set) and `APP_URL` being the
  real public URL. Session lifetime is the standard `SESSION_LIFETIME`.
- Auth is a **separate `customer` guard** (passwordless magic links) — customers are
  never admin users and can't reach `/admin`. There are no customer passwords or stored
  card details (balances pay through the same Stripe Checkout flow as everything else).
- Reviews left from an account arrive **unapproved**; moderate them under
  **Site content → Testimonials** (the nav badge shows how many are waiting).
- Edit the pre-jump info shown to customers under **Site content → Before-your-jump
  info**.

## Data protection & retention (GDPR)

Subject-access and erasure requests are handled from the **Customers** resource
(`/admin/customers`), one customer at a time:

- **Export** (subject-access request): the *Export data* row action streams a single
  JSON file with everything tied to that customer — their record, bookings (incl. the
  `customer_details` JSON: DOB, weight, height, sex, medical notes), enquiries and
  message threads, payments, reviews, purchased vouchers and newsletter status. It is
  scoped strictly to that customer (joined by their id and email); no other customer's
  data is included. Send the file to the requester.
- **Erase / anonymise** (right to erasure): the *Erase / anonymise* row action is
  **irreversible** and behind a confirmation modal. It anonymises in place rather than
  hard-deleting: personal and medical fields (name, contact, address, postcode, DOB,
  weight, height, sex, medical notes), message bodies, reviews and the newsletter
  subscription are removed or blanked; **anonymised booking and payment records
  (references, amounts, dates) are retained** for finance/audit. The customer row is
  marked `erased_at` and its email anonymised, so the person can no longer request a
  sign-in link.
- **Retention**: keep anonymised financial records for as long as tax/accounting law
  requires (UK: typically 6 years). Personal/medical data should be erased once it is no
  longer needed for the booking it was collected for and there is no other legal basis to
  keep it — run *Erase / anonymise* on request, or periodically for long-past customers.
- The activity log records that an erasure happened (not the erased content). No PII is
  copied to logs or error tracking — see the Sentry scrubber notes below.

## Error tracking (Sentry)

Optional but recommended in production. Disabled until a DSN is set, so it's a no-op
locally and in tests.

- **Owner task:** create a project at <https://sentry.io> (platform: Laravel) and paste
  its DSN into `SENTRY_LARAVEL_DSN`. Nothing else is required.
- **Privacy:** `send_default_pii` is **off** (no IPs, cookies or authenticated user
  attached), and a `before_send` scrubber (`App\Support\SentryScrubber`) drops request
  bodies wholesale and redacts any personal/medical/secret key (name, email, phone,
  address, postcode, DOB, weight, height, sex, medical notes, message bodies, card/auth
  tokens, Stripe ids) from the request, query string and our `extra` context — so Sentry
  never becomes a second, unaudited PII store.
- **Sampling:** all errors are captured; `SENTRY_TRACES_SAMPLE_RATE` (default `0.2`)
  controls performance tracing — lower it on a busy site, raise it while debugging.
- **Verify after adding the DSN:** run `php artisan sentry:test` (sends one test event),
  confirm it appears in Sentry and that the event contains **no** customer PII, then
  you're done — there is no test trigger left in the app to remove.

> **Monitored services:** Horizon (the queue worker) and the `schedule:run` cron are
> **must-always-be-running** in production — if either stops, queued emails (booking
> confirmations, reminders, sign-in links) and the daily reminder/hold-release jobs
> silently stop. Supervisor `autorestart`/launchd `KeepAlive` cover crashes; also add an
> uptime/heartbeat check (e.g. Sentry Crons or a cron-monitor ping) so a stuck worker is
> noticed, not discovered via a missed email.
