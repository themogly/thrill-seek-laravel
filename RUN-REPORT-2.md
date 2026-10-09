# Run report — unattended run 2 (019, 005–018, pre-staging gate, housekeeping)

Brief: `prompts/unattended-run-2.md`, authorised by Ben on 9 Oct 2026. The queue was committed to `main`
as `23439f5` ("docs: queue unattended run 2"). The working tree before it held exactly the expected
files.

Ben's answers to run 1's owner questions (recorded in RUNNING-ORDER row 8) are the premises the prompts
build on:
- 1: toggle, default on;
- 2: option B;
- 3: admin Blue OK;
- 4: motion subtle OK;
- 5: sign-off later;
- 8: samples off;
- 9: addresses → JSON-LD, remove duration;
- 10: sync the global skills.

## 0 — 019 PHP platform · `chore/php-platform` · merge `a7c47ce`
- Tests 432 → 432. `composer check` green; MySQL suite 432/432; `composer validate --strict` passes.
- `composer.json` now requires PHP `^8.4.1` and pins `config.platform.php` to `8.4.1`. The lock changed
  only its hash and platform block: all 177 package versions are identical. SETUP says "server runs PHP
  8.4.x".
- Gap report: none. `OVERNIGHT-DEFAULT`: none. The pin is `8.4.1`, the floor the lock needs (the prompt's
  default), because SETUP names no exact patch version.

## 1 — 005 FAQ admin 500 · `fix/faq-scope-collision` · merge `fc94134`
- Tests 432 → 434. `composer check` and MySQL green.
- `Faq::scopeForPage` → `scopeOnPage`. The admin FAQ list works again: verified as the owner, 200 with
  rows. The test now lists real rows (it was red on main). A new guard fails on any model scope that
  shadows a builder method (proven by a planted violation).
- Public FAQ output is byte-identical to a pre-fix snapshot.
- Gap report: none. Owner/overnight items: none.

## 2 — 006 no known-password admin on servers · `fix/no-seeded-admin-on-servers` · merge `fc9a0e4`
- Tests 434 → 440. `composer check` and MySQL green.
- `DevAdminSeeder` (local only) holds the known login. `db:seed --force` on staging/production creates
  **zero users** (red on main). Servers use `php artisan make:filament-user --panel=admin` (tested).
  `canAccessPanel()` stays true, backed by a guard that nothing in the app creates `User` rows (proven red
  by a planted violation).
- **Ops:** if any server has ever been seeded, delete `test@example.com` there (command in SETUP "First
  run").
- Gap report: none. Owner/overnight items: none.
