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

## 3 — 007 sample testimonials · `fix/sample-testimonials` · merge `b5d6803`
- Tests 440 → 446. `composer check` and MySQL green.
- Samples are seeded only locally. The review rating counts only real customers' approved reviews, at
  least 3. `reviewCount` and the rendered reviews come from the same collection. Clean empty states on
  the homepage (section dropped) and `/testimonials` (honest text + "Leave a review").
- `OVERNIGHT-DEFAULT — CONFIRM`: minimum 3 reviews before a rating is published. Also confirm that
  owner-typed reviews show on the page but don't count toward the rating.
- Gap report: two existing tests re-pointed because this prompt reverses their premise (details in
  DECISIONS). Homepage with testimonials: 0 pixel diff (one false alarm traced to a first-load capture).
- Screenshots: `audits/reports/run2/007-*.jpg`.
- **Owner:** on any already-seeded database, unapprove the 8 sample testimonials in the admin.

## 4 — 008 FK delete rules, Phase 1 · `fix/fk-delete-rules` · **pushed, unmerged** `823d4a2`
- Proposal only (`audits/reports/fk-delete-rules.md`). All 32 FKs, read from the live schema, with the
  current rule, what hangs off each parent, every delete path, and a proposed rule.
  - **13 move to RESTRICT** (including `course_dates.product_id`, which cascades today, and
    `payments.booking_id`).
  - **#26 (documents pivot)** is Ben's call.
  - The rest are kept, with reasons.
  - Also proposed: a `deleting` listener on the `GuardsDeletion` models (button explains, model refuses,
    DB refuses).
- Tests unchanged (no code). Phase 2 waits for Ben's approval in DECISIONS.

## 5 — 009 admin acts email the customer · `feat/admin-email-customer-toggle` · merge `088f90e`
- Tests 446 → 452. `composer check` and MySQL green.
- "Email the customer" toggle (default on) on admin-created Confirmed bookings and on Voucher → Redeem.
  It sends the same mail through the same action as the online path: a new single
  `SendBookingConfirmation`, plus `SendPaymentReceipt`. Off sends nothing. Editing never sends. Verified
  by doing (delivered).
- The email audit's `OWNER DECISION — PENDING` is marked answered (option B).
- Gap report: `SendPaymentReceipt` now returns bool, and `BookingObserver` delegates to the new action.
  Both were needed to keep one sender and truthful notices.
