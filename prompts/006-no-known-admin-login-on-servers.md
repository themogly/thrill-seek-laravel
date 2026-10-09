# 006 — Every `db:seed` creates an admin with a published password

**Urgent: must land before staging.** One branch, one task. Read `CLAUDE.md`, `SETUP.md` ("First run"),
`DECISIONS.md` (the June security audit close-out) and the kit's `bootstrap.md` §3 (the local-only
`DevAdminSeeder` pattern). **Size: small.**

`git checkout main && git pull` → `git checkout -b fix/no-seeded-admin-on-servers`.

> **Why this exists.** Chat-Claude found this while reading the seeders for another prompt.
>
> **Verified on `origin/main` = `7e01bfe` by code read (not run):**
> - `DatabaseSeeder::run()` creates `test@example.com` with the factory password `password` on **every
>   environment** (`database/seeders/DatabaseSeeder.php:18-21`).
> - `User::canAccessPanel()` returns `true` for every user (`app/Models/User.php:36-39`).
> - `SETUP.md` "First run" tells you to run `php artisan db:seed` and says "change it immediately for
>   anything public-facing".
>
> So staging, and then production, would go up with a guessable admin login. Its only protection is a human
> remembering to change a password. The June security audit and pre-staging gate didn't catch it.
>
> **Ruled out:** customers reaching the panel. Customers are a separate `Customer` model with magic links, so
> today every `User` is staff. The problem is the known credentials, not who `User` is.
>
> Confirm on current `main`. Already fixed → stop.

## Build

1. **A local-only dev admin.** Move the known-credential user into a `DevAdminSeeder` that aborts unless
   `app()->environment('local')`. Make it idempotent (`updateOrCreate`). Call it from `DatabaseSeeder` only
   behind a `local` guard.
2. **A real way to create the production admin.** Use Filament's `make:filament-user`, or a small
   `gforce:create-admin` command that prompts for a name, email and password. Pick one and record why.
   Document it in `SETUP.md` "First run" for staging and production, and remove the "change it immediately"
   sentence.
3. **`canAccessPanel`.** Keep `return true` only if you can show that every `User` is staff, and record that
   reasoning in DECISIONS. A comment isn't enough: add a test asserting that nothing outside the
   admin-creation path creates `User` rows (customers, newsletter, inbound mail). If you can't show it, gate
   on a column. That's a schema change, so escalate it as `OWNER DECISION — PENDING` and stop.
4. **Content seeding on a server.** Other prompts change sample content seeding (007 makes the testimonial
   samples local-only). Here, only make sure `db:seed` in a non-local environment seeds content but no user.

## Rules

- Local dev must still get a working `/admin` login after `migrate:fresh --seed`, with the same credentials.
- No change to the customer magic-link login.

## Tests

- In the `production` environment, `db:seed` creates **zero** users. Red on current `main`.
- In `local`, the dev admin exists and can reach the panel.
- The `User`-creation-path test from Build 3, if you take that route.

## Finish

`composer check` green, plus the MySQL suite. DECISIONS entry. `SETUP.md` updated. Push the branch. **Do not
merge** (unless on an authorised unattended run).

Add an owner/ops line to the final summary: if any server has ever been seeded, delete `test@example.com`
there.
