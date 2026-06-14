# MySQL migration notes

Moving the production database from SQLite to MySQL. This file is the Step-1 risk
inventory (written before any change) plus a running log of what actually changed.

## Why move to MySQL
- **Persistence on any host.** SQLite is a single file on local disk — on ephemeral/
  containerised hosts that file can vanish on redeploy. MySQL is a managed service.
- **Concurrency.** SQLite takes a database-wide write lock; MySQL/InnoDB does row-level
  locking, which matters once webhooks, the queue worker and admins write at once.
- **Mature backup/restore + ecosystem** (mysqldump, PITR on managed DBs, replicas).

## Step 1 — SQLite-vs-MySQL risk inventory (this codebase)

Scope: 41 migrations, all via the Laravel schema builder (no `$table->enum()`, no
`DB::statement`, no fulltext). One `DB::raw('created_at')` (a backfill copy — valid on
both drivers). The findings below are ordered by risk.

### Highest risk — JSON columns
13 `$table->json(...)` columns: `products.features/weight_charges/repeat_pricing`,
`enquiries.context`, `bookings.customer_details`, `payments.metadata`,
`email_templates.variables`, `course_messages.recipients`, `settings.payload`,
`newsletter_campaigns.blocks`, `enquiry_messages.attachments`, `activity_log.*`.
- **SQLite:** `json()` is just a `TEXT` column with no validation; Laravel array casts
  `json_encode`/`json_decode` the whole value.
- **MySQL:** `json()` is the native `JSON` type — it **validates** on write (invalid JSON
  errors instead of silently storing), **normalises** stored objects (whitespace removed,
  object keys may be reordered) and (pre-8.0.13) **cannot have a column DEFAULT**.
- **Mitigating factor:** the app only ever reads/writes these columns *as whole arrays*
  via Eloquent `'array'`/`'collection'` casts — there are **no `whereJsonContains` or
  JSON-path queries** anywhere in `app/`. So normalisation/reordering is invisible to the
  app (it decodes to a PHP array regardless of key order).
- **Watch:** (a) any code that string-compares a raw JSON payload would see normalised
  output on MySQL — the only candidate is spatie settings, but the settings migrator hands
  the **decoded** value to update closures, so the privacy-policy conditional update
  compares decoded strings, not raw JSON (safe). (b) Seeders/factories must write valid
  JSON (the casts guarantee this). Verify on MySQL in Step 3.

### Medium risk — string length & unique indexes
Unique indexes on `VARCHAR(255)` columns: `products.slug`, `users.email`,
`enquiries.reference`, `bookings.reference`, `bookings.stripe_checkout_session_id`,
`customers.email`, `email_templates.key`, `*.external_id`, `enquiries.reply_token(40)`.
- On `utf8mb4` (4 bytes/char) a 255-char unique index = 1020 bytes, well under InnoDB's
  3072-byte limit on MySQL 8 (DYNAMIC row format default) — **fine on MySQL 8**. Would be
  a problem on MySQL 5.7 without `innodb_large_prefix`; **target MySQL 8.0+**.
- Action: pin the connection charset to `utf8mb4`/`utf8mb4_unicode_ci` (Laravel default).

### Low risk — handled by Laravel, but verify on MySQL
- **Booleans:** SQLite stores 0/1 integers; MySQL `boolean` = `TINYINT(1)`. Eloquent bool
  casts normalise both; `where('active', true)` works on each. (~10 boolean columns.)
- **`->after('col')`:** MySQL-specific column positioning; SQLite ignores it. Harmless on
  both (used in several add-column migrations).
- **Dates/times:** Carbon datetime casts; SQLite = TEXT, MySQL = DATETIME. Ordering and
  comparison behave the same for ISO strings; `whereBetween`/`whereDate` are portable.
- **Money:** integer pence in `integer`/`bigInteger` columns — identical on both.
- **Loose typing:** SQLite is permissive about type mismatches; MySQL is strict. Any place
  that relied on SQLite coercing '' ↔ 0 ↔ null would error on MySQL — Step 3 (full suite
  on MySQL) is the way to surface these.

### Test-suite gap
`phpunit.xml` pins `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` — the entire suite has
**only ever run on SQLite**, so no SQLite-only assumption has ever been caught. Step 3
adds a MySQL test profile and runs the full suite on MySQL; every failure there is a real
prod-affecting difference.

## Step 2–5 — log

### Step 2 — configure MySQL & migrate
- Local dev + tests now use MySQL (verified against MySQL 9.6 locally; production target
  is MySQL 8+). `.env`/`.env.example` set `DB_CONNECTION=mysql` + host/port/db/user.
  `config/database.php` already had a standard `mysql` connection (utf8mb4) — unchanged.
- **One migration fix required** (a real SQLite-only bug the move surfaced):
  `2026_06_10_110000_create_course_dates_table` added `bookings.course_date_id`
  `->after('tandem_date_id')`, but `tandem_date_id` is only added two migrations later
  (`2026_06_12_090000_rename_availability_slots_to_tandem_dates`). SQLite **ignores**
  `->after()`, so it silently worked; MySQL **honours** column position and errored
  (`Unknown column 'tandem_date_id'`). Fix: dropped the cosmetic `->after()` — portable on
  both drivers, no behaviour change. `migrate:fresh` then ran clean (all 41) and `db:seed`
  populated without error.

### Step 3 — full test suite on MySQL
- Added `phpunit.mysql.xml` (MySQL test profile → `thrill_seek_test`). The default
  `phpunit.xml` stays SQLite `:memory:` for fast `composer check`/CI.
- `php artisan test -c phpunit.mysql.xml` → **341 passed, 1400 assertions, 0 failures.**
  **No test needed adjusting** — once the `after()` migration was fixed, the whole suite
  was green on MySQL first try. This is the payoff of the existing conventions: JSON via
  Eloquent array casts (no JSON-path queries), enums stored as strings, money as integer
  pence, dates via Carbon casts — all driver-agnostic.

### Step 4 — browser smoke on MySQL
- Public pages (home, tandem, AFF, coached, vouchers, contact, privacy, hall-of-fame,
  testimonials) at 1440 + 390: all 200, no console/page errors.
- Newsletter footer signup wrote to MySQL (`newsletter_subscribers`, source `footer`).
- Admin (Filament) logged in and every screen 200: dashboard, bookings, enquiries,
  customers, products, vouchers, tandem-dates, course-dates, **bookings calendar**,
  help-guide. No 500s.
- **JSON round-trip proof** on MySQL via the real models: an `Enquiry` with a nested
  `context` array and a `Booking` with `customer_details` (dob/height/sex) wrote and read
  back identically (nested arrays intact). This is the highest-risk area and it is clean.

### Step 5 — production setup + tested backup/restore
- Documented in SETUP.md ("Production database", "Backups & restore"): env vars, dedicated
  DB user, `migrate --force` on deploy, the **never run migrate:fresh/refresh in prod**
  rule, and the one-time SQLite→MySQL data-move path.
- **Backup/restore actually tested locally:** `mysqldump --single-transaction --routines
  --triggers --set-gtid-purged=OFF` → restore into a fresh DB → row counts matched across
  products/email_templates/bookings/settings/customers and `JSON_VALID(settings.payload)`
  = 1. The `--set-gtid-purged=OFF` flag is **required** — without it the restore aborts
  with `@@GLOBAL.GTID_PURGED cannot be changed`. Chose documented `mysqldump`+cron over
  `spatie/laravel-backup` to avoid a new dependency; managed-DB automated backups/PITR are
  recommended where available. Backups contain PII/medical data → store encrypted.

## Remaining driver-specific notes
- `phpunit.mysql.xml` is the production-parity test gate; `phpunit.xml` (SQLite) remains
  the fast default. Run **both** green before shipping DB-affecting changes.
- No code path is MySQL-only; the single migration change is portable to SQLite too.
