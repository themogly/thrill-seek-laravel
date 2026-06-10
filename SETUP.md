# Setup

Laravel 12-skeleton app on Laravel 13 / PHP 8.3 with a Blade/Tailwind/Alpine frontend,
Livewire 4 forms and a Filament v5 admin panel at `/admin`.

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
| `QUEUE_CONNECTION` | `database` (default). All email and webhook side-effects are queued. |

Missing Stripe/Resend keys never break the site: enquiries still store, admin actions
surface a clear notification, and failures are logged.

## Stripe webhook

Register this endpoint in the Stripe dashboard (Developers → Webhooks):

```
POST https://your-domain.example/webhooks/stripe
```

Subscribe to the `checkout.session.completed` event and copy the signing secret into
`STRIPE_WEBHOOK_SECRET`. The endpoint is CSRF-exempt and signature-verified; retries
are idempotent.

For local testing: `stripe listen --forward-to localhost:8000/webhooks/stripe`.

## Queues & scheduler

```bash
php artisan queue:work            # or: composer dev (serves, queue, logs, vite)
php artisan schedule:work         # runs bookings:send-reminders daily at 09:00
```

In production run a `queue:work` supervisor process and add the standard cron entry:

```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Day-to-day commands

```bash
php artisan test                  # full suite (never commit red)
./vendor/bin/pint                 # code style
./vendor/bin/phpstan analyse      # static analysis (level 6)
php artisan bookings:send-reminders   # manual reminder run
```

## Where things live

- **Site content** — every public page is editable under `/admin` (“Site content”
  group): per-page settings screens plus Testimonials, Hall of Fame, Shop items,
  Gallery and Instructors resources.
- **Sales** — “Bookings & sales” group: Enquiries inbox (reply threads, payment links,
  bank transfers), Products & pricing, Bookings + calendar, Availability slots,
  Vouchers, Customers, Email templates.
- **Email templates** — all customer-facing automated emails are editable records with
  `{{ placeholder }}` variables listed on each template's edit screen.
- **DECISIONS.md** — the judgement calls made during the build and why.
