# 004 — Refresh Composer and npm dependencies within their current constraints

One branch, one task. Read `CLAUDE.md`, `SETUP.md`, `DECISIONS.md` (the Sentry/`config:cache` entry and the
MySQL test-profile entry), and `false-green.md` (the "measurement is only as good as the tree" entries).
**Size: small to medium.** Runs after unattended run 1 has finished and merged. Check that
`RUN-REPORT-1.md` says the run ended. If it doesn't, stop.

**Expected uncommitted changes:** chat-Claude added this file and a `RUNNING-ORDER.md` row (step 7b). `git
status` should show only those two. Anything else: stop and list it.

`git checkout main && git pull` → `git checkout -b chore/deps-refresh-2026-10`. Commit those two files first,
as `docs: queue 004 dependency refresh`.

> **Why this exists.** Ben: *"make sure we're on latest Laravel and the same with Composer packages."*
>
> **Premise, checked by chat-Claude reading this Mac's `composer.json` / `composer.lock` on 9 Oct 2026; not run.**
> The lock was last written on 14 June 2026. Locked versions:
> - `laravel/framework` 13.14.0 (13 is the current major; 14 isn't released);
> - `filament/filament` 5.6.6;
> - `livewire/livewire` 4.3.1;
> - `laravel/horizon` 5.47.2;
> - `stripe/stripe-php` 20.2.0;
> - `sentry/sentry-laravel` 4.26.0;
> - `spatie/laravel-settings` 3.9.0;
> - `phpunit/phpunit` 12.5.29;
> - `larastan/larastan` 3.10.0.
>
> **Ruled out:** a major-version upgrade. Nothing here needs one to be on "latest Laravel". This branch is
> everything inside the existing `^` constraints, plus a report of what a major bump would take.

## Build

**1. Baseline, before touching anything.** Record these in the DECISIONS entry:
- `composer check` (test count);
- the MySQL suite (`phpunit.mysql.xml`);
- `composer audit`;
- `npm audit`;
- `composer outdated --direct`;
- `npm outdated`;
- `php artisan config:cache && php artisan config:clear` succeeds.

**2. Composer, within constraints.** Run `composer update -W`. Don't edit any constraint in `composer.json`.
Then:
- `php artisan filament:upgrade`, if Filament ships it in the new version;
- `php artisan vendor:publish --tag=livewire:assets --force`, only if the project publishes Livewire assets
  (check first);
- `php artisan horizon:publish`, if Horizon's assets are published.

Read the release notes and upgrade guides **for every minor version crossed** for: `laravel/framework`,
Filament, Livewire, Horizon, spatie/laravel-settings and Sentry. List anything that applies to this codebase.

**3. Re-run the structural guards specifically.** The Livewire reserved-name guard (002) parses the vendored
Livewire dist, so a Livewire bump is exactly what it exists for. Confirm it re-read the new alias map and didn't
silently find zero aliases.

**4. npm, within ranges.** `npm update`, then `npm run build`. Don't change ranges in `package.json`.

**5. Majors: report, don't do.** For every direct dependency where `composer outdated --direct` or
`npm outdated` shows a newer major, add one line per package to the DECISIONS entry: current → latest major,
what the upgrade guide says changes, and your estimate of the work. Each one becomes a separately numbered
prompt if Ben wants it.

**`stripe/stripe-php` is flagged regardless of size.** A major can change the pinned API version and the money
path, so it's an owner decision. Mark it `OWNER DECISION — PENDING`.

## Rules

- **No constraint changes in `composer.json` or `package.json`.** Only the lock files move.
- **No app code changes**, except where a minor version's upgrade guide requires one. Each such change gets its
  own commit naming the guide entry it follows. If any app change is more than mechanical, stop and report.
- **Money path:** the end-to-end tests asserting the real pence sent to mocked Stripe must still pass
  unchanged. Never edit a money test to make it pass.
- **`config:cache` must still succeed.** Sentry and other packages can add config callbacks.
- **The homepage is signed off.** After the npm update and rebuild, screenshot the homepage at 1440 and 390 and
  compare with a screenshot taken *before* the update on the same machine. Tailwind and Vite minors can shift
  rendering. Any difference: say exactly what, and don't merge until Ben has looked.
- Two commits: `chore(deps): composer update within constraints` and `chore(deps): npm update within ranges`.
  If npm causes a visual difference you can't explain, revert only the npm commit, keep the Composer one, and
  report.

## Tests

No new tests. Upgrades are proven by the existing suite and guards, so the "fails against main" requirement
doesn't apply. Required green, with counts reported before → after:
- `composer check`;
- the MySQL suite;
- the 002 structural guards (step 3).

`composer audit` and `npm audit` must be no worse than the baseline. List any advisory still open and say why.

## Finish

- Everything above green.
- DECISIONS entry: baseline, versions before → after for the packages named in the premise, applicable
  upgrade-guide items, the majors report, and the screenshot comparison.
- Push `chore/deps-refresh-2026-10`. **Do not merge.**
- Gap report if you deviated.
