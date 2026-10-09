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

## 6 — 010 voucher payment-success page · `fix/voucher-payment-success` · merge `ee6e64c`
- Tests 452 → 455. `composer check` green.
- Reproduced by doing (a paid voucher showed "waiting"). The page now shows a voucher confirmation:
  product, value, recipient, expiry, and that the email is on its way. It's resolved only through the
  visitor's own session. An unpaid payment still waits.
- Screenshots: `audits/reports/run2/010-*.jpg`.
- Gap report: none. Decision: the voucher code isn't shown on the page (the email carries it).

## 7 — 011 CID mail logo · `fix/mail-logo-cid` · merge `eff136b`
- Tests 455 → 458. `composer check` green.
- One logo partial for transactional and newsletter mail, embedded inline (CID) on every mailable, only
  when referenced. Previews swap in the public URL. Verified: all 7 templates were sent to the log
  mailer; `.eml` evidence at `audits/reports/run2/011-booking-confirmed.eml`.
- Gap report: one newsletter-builder test re-pointed (it encoded the absolute-URL decision this reverses).
- **Owner check after staging:** a real email in Gmail and in Outlook shows the logo with images blocked.

## 8 — 012 CMS orphan fields · `chore/cms-orphans` · merge `6a7c6e8`
- Tests 458 → 462. `composer check` and MySQL green.
- The Location address and coordinates now feed the course Event JSON-LD (a real `PostalAddress` + geo;
  the name is no longer passed off as an address).
- Removed `Product::duration` (value recorded in DECISIONS), `home.team_lead` and `Location::image`. The
  migrations were proven on seeded copies (MySQL clone and SQLite) with rollback. Homepage
  pixel-identical.
- `OVERNIGHT-DEFAULT — CONFIRM`: removing `team_lead` and `Location::image` (agreed generally, not by
  name).
- Gap report: one settings test re-pointed (it asserted `team_lead` existed).
- Screenshots: `audits/reports/run2/012-*.jpg`.
- **Owner:** enter real dropzone addresses and coordinates in Locations. Ben: run `php artisan migrate`
  on the dev DB.

## 9 · 013 homepage SEO settings
- **Branch:** `fix/home-seo-settings` → merged `31e46bc`
- **Tests:** 462 → 468; `composer check` and MySQL green.
- **What it did:** Home gets its own `seo_title`/`seo_description` (option (b), see DECISIONS), seeded with today's exact literals and read through fallback accessors. It's edited in a new "Search engines & sharing (SEO)" section on Site content → Home page. The help guide has one line on where to edit it.
- **Proof:** the new "changing the setting changes the `<title>`/description" test is red on `main`. The rendered `<head>` was byte-identical before/after; the homepage was pixel-identical at 1440 and 390 (2 before + 2 after captures, all 0 px).
- **Gap report:** 012 had left an empty "“Meet the team” teaser" section on the Home settings screen (a header with no fields). It's removed here, plus a guard `NoEmptyAdminSectionsTest` (red on `main`). Also: adding a settings property 500s an unmigrated DB (`MissingSettings`), so the dev homepage broke until this one additive settings migration was applied locally with `--path` (2 settings rows, no records touched). 012's drops are still pending for Ben's `php artisan migrate`.
- **Markers:** none.
- **Screenshots:** `audits/reports/run2/013-admin-home-seo.jpg` (homepage unchanged, so no homepage crops).

## 10 · 014 price tokens in CMS copy
- **Branch:** `feat/price-tokens-in-copy` → merged `30e8c89`
- **Tests:** 468 → 475; `composer check` and MySQL green.
- **What it did:** CMS wording can quote `{price:tandem-skydive}`, `{deposit:aff-course}` or `{addon:outside-camera}`. They resolve at render time through `Money` (FAQ accordion + FAQPage JSON-LD, meta, Product JSON-LD, hero subtitles, the charity note, the Coached price line, Terms). Seeds and settings migrations now use tokens; the inventory table is in DECISIONS. A Help guide section covers the tokens and the still-typed prices.
- **Proof:** "changing the Tandem price changes the FAQ, hero and FAQ JSON-LD" is red on `main`. A sweep of 15 seeded public pages shows no raw token. Token output `===` the product's `formatted_price`/`formatted_deposit`. The rich-editor preview was checked in a real browser (dev FAQ not saved). Homepage 0 px at 1440/390.
- **Gap report:**
  - A save-blocking validation rule was built, then removed: hiding or renaming a product later would have locked every other edit on that settings page. The prompt only asks for a visible preview.
  - Two existing render tests needed `ProductSeeder` in setup (a fresh install seeds products); their exact-text assertions are unchanged.
  - Add-on tokens key on the slugified name (no slug column; the rules forbid new columns), so renaming an add-on breaks its token. The preview shows it.
- **Markers:** **OWNER DECISION — PENDING** (weight surcharges typed twice: keep typed, or make them money + a `{weight:…}` token later).
- **Owner content (existing DBs):** swap the typed figures for tokens in FAQs tandem #4, #5, #8 and aff #12; Tandem description/subtitle/charity note; AFF description; Coached description and price line; Terms.
- **Screenshots:** `audits/reports/run2/014-admin-faq-preview.jpg`.

## 11 · 015 consistency small fixes (C-3, C-4, C-12, C-13)
- **Branch:** `ui/consistency-small-fixes` → merged `1ec6b75` (one commit per fix, plus docs)
- **Tests:** 475 → 476; `composer check` green (views only, so no MySQL run needed).
- **What it did:**
  - New shared `<x-ui.meta-label>`: `/news` dates and the image label at `0.25em` (C-3), and testimonial-grid role labels at 700 / `0.25em` / sky-bright (C-4; instructor cards use it too, unchanged).
  - New `<x-ui.loading-label>` defines "Sending…" once for the 4 enquiry forms (C-12).
  - Tandem/AFF intros on `py-section-sm lg:py-section` (C-13).
- **Proof:** full-page pixel diffs: homepage, `/tandem`, `/aff`, `/meet-the-team` and `/contact` all 0 px at 1440 and 390. Only `/news` and `/testimonials` changed, as intended.
- **Gap report:**
  - A 390 homepage diff of 2,592 px turned out to be lazy-loaded testimonial avatars missing from one capture. The instrument now force-loads images; re-proved 0 px with the code stashed and restored (recorded in DECISIONS).
  - 014 had left C-7 in `ui-guidelines.md` known gaps; removed here.
  - The homepage news dates keep their (identical) inline classes, because the prompt says to change `/news` only.
- **Markers:** none.
- **Screenshots:** `ui-review/consistency-small-fixes/` — news / testimonials / contact-form-loading, 1440 + 390, before + after.
