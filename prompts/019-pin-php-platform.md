# 019 — Make `composer.json` say the PHP version the lock actually needs, and pin the platform

One branch, one task. Read `CLAUDE.md`, `SETUP.md` (requirements and the deploy sequence) and `DECISIONS.md`
(the dependency refresh and majors entries: the two PHP follow-ups recorded there). **Size: small.**

`git checkout main && git pull` → `git checkout -b chore/php-platform`.

> **Why this exists.** The 004 run found that the lock has required **PHP 8.4.1+** since June: the locked
> Symfony 8.1 components and spatie/laravel-activitylog 5 need it. Meanwhile `composer.json` still says
> `"php": "^8.3"` and there's no `config.platform`. **Verified on `origin/main` = `4a4484e` by reading
> `composer.json`.** SETUP was corrected to 8.4 in that run. The two follow-ups it recorded are this
> prompt.
>
> **What goes wrong without it:**
> - a server on 8.3 can't install the app, and nothing says so until `composer install` fails mid-deploy;
> - a laptop on PHP 8.5 can resolve packages the 8.4 server can't run.

## Build

1. `"php": "^8.4.1"` in `require`.
2. `config.platform.php` set to the server's real PHP version. Ploi's 8.4 is the target. Use `8.4.1` unless
   SETUP names a more exact version, and record why.
3. `composer update --lock` (or the minimal command that rewrites only the platform hash). **No package
   version may change.** Diff the lock to prove it.
4. Pre-staging gate input: a one-line note in SETUP that the server must run PHP 8.4.x, and that the
   PHP-FPM reload uses `php8.4-fpm` (already corrected in 004; confirm, don't duplicate).

## Rules

- Lock-file package versions identical before and after (diff the `packages` arrays and show zero changes).
- No app code.

## Tests

`composer check` green, plus the MySQL suite. `composer validate --strict` passes. No new tests; this is
configuration.

## Finish

DECISIONS: mark the two 004 follow-ups done. Push the branch. **Do not merge** (unless on an authorised
unattended run).
