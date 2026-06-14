# Setup

Laravel 12-skeleton app on Laravel 13 / PHP 8.3 with a Blade/Tailwind/Alpine frontend,
Livewire 4 forms and a Filament v5 admin panel at `/admin`.

## Requirements

- PHP 8.3, Composer, Node 20+
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

## First run

```bash
composer setup          # install, .env, key, migrate, npm install + build
php artisan db:seed     # seed all current site content + an admin user
```

Seeding creates the admin login `test@example.com` (password: `password` from the
factory default). Change it immediately for anything public-facing.

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
