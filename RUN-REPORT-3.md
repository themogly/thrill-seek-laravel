# Run report — unattended run 3 (Ben's answers, 016, 008 Phase 2, 020–024, pre-staging gate, housekeeping)

Brief: `prompts/unattended-run-3.md`, authorised by Ben on 9 Oct 2026. The working tree before it held exactly
the expected files (`RUNNING-ORDER.md` modified; `prompts/020…024` and `unattended-run-3.md` untracked). The
queue was committed to `main` as `4ee5960` ("docs: queue unattended run 3") and pushed.

**Dev database:** `php artisan migrate` (not `fresh`) applied the two pending run 2 migrations,
`2026_10_09_120000_drop_orphaned_cms_columns` and `2026_10_09_120100_remove_home_team_lead`; then
`php artisan settings:clear-cache`. The homepage returned 200 twice afterwards. The "Audit …" records were
not touched.

## 1 · Ben's answers recorded
- **Branch:** `docs/run3-owner-answers` → merged `90a9cbc`
- **Tests:** 476 → 476 (docs and comments only); `composer check` green.
- **What it did:** one DECISIONS entry dated 9 Oct 2026 with every answer (008 table + #26 → RESTRICT, 016 ×4,
  017 withdrawn, 007, 012, 014, and the four follow-ups 021–024). Flipped to `… — ANSWERED 9 Oct (see DECISIONS)`:
  - DECISIONS: 007's minimum-3 rating, 012's `team_lead` and `Location::image`, 014's weight surcharges;
  - code: `StructuredData::MIN_REVIEWS_FOR_RATING` (007);
  - also closed, because run 1's answers had already settled them but the wording was still open: admin
    Blue (`AdminPanelProvider`, `PanelFurnitureTest`, admin audit), motion subtle (DECISIONS, `CLAUDE.md`),
    the accessibility audit's brand-contrast rows, and the email audit's heading (option B, built in 009).
- **Gap report:** 012 left no marker in code (the fields were deleted). 016's markers (`app.css`, the
  message "You" label, its DECISIONS entry and the brand-contrast heading it rewrites) live on the 016
  branch, and 008's #26 is on the 008 branch. Flipping them on `main` now would only create merge
  conflicts, so each is flipped when its branch merges (items 2 and 3).
- **Markers:** none added.

## 2 · 016 brand contrast `primary-strong` merged
- **Branch:** `ui/primary-strong-contrast` (`fc0884a` + merge of `main`, `76ecc3d`) → merged `98584c7`
- **Tests:** 476 → 479 on `main`; `composer check` green at the branch tip after merging `main` (479/479).
  No MySQL run needed (CSS and views only, no queries/casts/migrations/money).
- **Merge of `main` into the branch:** one conflict, in `DECISIONS.md` only, as predicted: two appends at the
  end. Kept both, in date order (016's entry, then 018's, then item 1's answers).
- **Markers flipped on the branch** (they couldn't be on `main` before it merged): the three
  `OVERNIGHT-DEFAULT`s (white `primary-foreground` in `app.css` and DECISIONS; ≥24px figures stay
  `primary`; the "You" label navy in `account/messages/show.blade.php` and DECISIONS), and the hover
  `OWNER DECISION` → answered (b), darken, by 020. The DECISIONS heading no longer says "NOT MERGED".
- **axe-core 4.10.2**, re-run on rebuilt assets, 20 public pages × 1440/390, signed out, reduced motion:
  **0 nodes of any rule** (0 `color-contrast`), against 173 on `main` before.
- **Homepage, full page, images force-loaded, warm-up load after each resize:**
  - merged branch vs the 016 tip `fc0884a`: **0 px** at 1440 and at 390, so the merge brings exactly what
    016's screenshots showed;
  - merged branch vs `main`: 49,956 px (1440) and 70,664 px (390), same page height. Every changed pixel
    is a colour pair: `#008fe6→#0078cc` (42,623 / 63,169), `#fcfcfc→#ffffff` (button text), and the
    anti-aliased edges between them. No geometry change.
- **Gap report:** none against the brief.
- **Markers:** none added.

## 3 · 008 Phase 2: the database refuses money-linked deletes
- **Branch:** `fix/fk-delete-rules` (Phase 1 `823d4a2` + merge of `main` + build, `5b0ac48`) → merged `b10a2c9`
- **Tests:** 479 → 493. `composer check` green; **MySQL suite 493/493**.
- **What it did:** exactly the approved table.
  - Migration `2026_10_09_140000_restrict_money_linked_deletes`: 14 FKs → `RESTRICT` (#1, 2, 3, 7, 8, 9, 10, 11,
    15, 16, 17, 19, 20, 26). Only the rule changes. `down()` restores each.
  - **Orphan pre-check:** aborts with the list before changing anything. On a clone of the dev DB it found
    **none**, so no stop condition. Planting one orphan in the clone made it abort and change nothing.
  - **Seeded copies:** a MySQL clone of the dev DB migrated, rolled back and re-migrated with identical row
    counts (2 → 16 RESTRICT rules and back). A seeded SQLite file with a booking, paid payment and voucher kept
    its rows through the table rebuild, `foreign_key_check` clean.
  - `RefusesGuardedDeletion` (a `deleting` listener) on all eight `GuardsDeletion` models, throwing
    `DeletionBlockedException` with the record's own reason. `Document` joins `GuardsDeletion` ("Attached to N
    sent message(s)…"); its Delete buttons use `guardedDelete()`; the Help guide says why.
  - Red without the change, layer by layer: without the migration, 7 of the 12 new tests fail; without the
    listener, the 4 model tests fail with a raw SQL error instead of the plain-English refusal. The structural
    guard (every FK into a guarded table is RESTRICT or allowlisted with a reason; every guarded model has the
    listener) is proven with planted violations.
  - Erasure still works on a customer with a paid booking (test).
- **Gap report:**
  - **Did, not in the prompt:** `TandemDate::deletionBlocker()` now also counts cancelled bookings. The
    approved #7 RESTRICT refuses on any booking, so the old text ("active bookings only") would have shown an
    enabled Delete that failed with a database error. The interface and helpers are unchanged.
  - **Driver detail:** `bookings.tandem_date_id`'s MySQL constraint was still named
    `bookings_availability_slot_id_foreign` (old column name). The migration drops by the real name and
    re-adds under the conventional one.
  - The mail-claims guard flagged the new Help-guide line ("sent to students"); it's paired with
    `CourseMessageMail` in `MailInventoryTest`, as that guard asks.
- **Markers:** #26 "(Ben's call)" → approved, in the report and DECISIONS. None added.

## 4 · 020 button hover and press contrast
- **Branch:** `ui/button-hover-contrast` → merged `6f0e021`
- **Tests:** 493 → 496; `composer check` green. (No MySQL: CSS and views only.)
- **What it did:** new tokens `--primary-strong-hover` `oklch(0.50 0.18 240)` (5.56:1 with white) and
  `--primary-strong-active` `oklch(0.46 0.18 240)` (6.57:1). Primary hover/press fills and the link variant's
  press colour use them. Outline's washes already pass (≥ 8.1:1 on light and dark) and are unchanged.
  `ui-guidelines.md` palette and button entries updated.
  - **Premise confirmed:** hover `/85` 3.64:1, press `/75` 3.09:1 (016 said 3.54 / 2.99, against the old
    `#fcfcfc` text).
  - **Rest pixel-identical:** full-page homepage before/after, **0 px** at 1440 and 390.
  - `ButtonStateContrastTest` resolves each variant's hover/press colour from the real tokens and classes
    (compositing washes as the browser does); red on `main` with exactly 3 failures, proven with a planted wash.
- **Gap report:**
  - **Did, not in the prompt:** the link variant's press (`/80`, 3.35:1) had the same fault and is fixed the
    same way. `newsletter-status` passed its own colour classes (with a failing `/90` hover) to the button; it
    now uses the variant (identical at rest).
  - The first "before" screenshots were on a mixed tree (new Blade, old CSS build) and showed no hover. They
    were discarded and re-taken on a clean `main` build.
- **Screenshots:** `ui-review/button-hover-contrast/`: hero CTA, contact submit and a link button × rest/hover/press ×
  before/after (18 cropped JPEGs).
- **Markers:** 016's hover `OWNER DECISION` now reads "built in 020". None added.

## 5 · 021 reschedule respects capacity
- **Branch:** `fix/reschedule-capacity` → merged `eeba9cf`
- **Tests:** 496 → 506. `composer check` green; **MySQL suite 506/506** (locking query).
- **What it did:** the gate is in `RescheduleBooking`: a transaction locks the target slot (as the online
  checkout does) and refuses with "That date is full (N of N places taken) and this booking needs 1…".
  - **One reader:** the new `TandemDate::hasPlaceFor()` uses the online path's own `isFull()`.
  - **Holds:** unpaid `PendingPayment` holds take a place, exactly as online.
  - A booking moving within its own slot isn't counted against itself.
  - **The screen:** the slot select marks full slots "— full" and disables them. A race after validation shows
    a "Not rescheduled" notice. No email unless the move succeeded.
  - The ad-hoc date/time path is unchanged and deliberately unconstrained.
  - Launch checklist §5 and the Help guide mention it.
- **Gap report:**
  - **Premise:** confirmed by a failing test rather than on the dev DB, so no records were added to Ben's
    database.
  - **Party size:** bookings have no party-size field (one booking = one jumper), so "party size counted"
    reduces to "needs 1".
  - The "online and reschedule agree" test exists because capacity now has two callers of the same reader.
- **Markers:** none.

## 6 · 022 noindex on non-production hosts + SETUP staging notes
- **Branch:** `fix/noindex-non-production` → merged `b22d879`
- **Tests:** 506 → 511; `composer check` green. (No MySQL: no queries.)
- **What it did:** unless `APP_ENV=production`, `/robots.txt` is `Disallow: /` with no Sitemap line, and
  `SecurityHeaders` (on the `web` group, no session) adds `X-Robots-Tag: noindex, nofollow` to every web response.
  - **Production is byte-identical:** rendered in-process under `APP_ENV=production` on `main` vs the branch,
    robots, sitemap, every header, the homepage `<head>` and `/tandem` are identical, and the homepage body is
    identical apart from the per-request CSRF token.
  - SETUP.md has a new **Staging** section (basic-auth except the two webhooks; app noindex; Stripe TEST endpoint
    at `2026-09-30.endive`; site email → test inbox), linking to the checklist. Checklist §0's noindex item now
    checks the app.
  - On the local dev site, `curl -I` now shows the header (local isn't production): `X-Robots-Tag: noindex, nofollow`.
- **Gap report:**
  - A URL matching no route (a bare 404) runs no route middleware, so it has no header; a missing page isn't
    indexed, and robots disallows it. Recorded, not changed.
  - `RobotsTest`'s sitemap assertion now runs under `production`, the file it always described. Not
    weakened; the new test pins the exact production body.
  - The first byte comparison was invalid (the kernel re-read `APP_ENV=local` from `.env`); it was re-run with
    the process env set.
- **Markers:** none.

## 7 · 023 email link colour
- **Branch:** `fix/email-link-colour` → merged `617ef9b`
- **Tests:** 511 → 516; `composer check` green.
- **What it did:** `App\Support\BrandHex` defines the blues once for emails and PDFs (`STRONG #0078cc`,
  `ACCENT #2f8de4`).
  - **Moved to STRONG:** the button fill (pure `#ffffff` text, 4.61:1), the two-column link, the newsletter
    shell's link colour, the theme's `.button-primary` (pinned by test, since that CSS can't call PHP), and the
    receipt and voucher-code PDF labels.
  - **Kept bright, each with a reason in the test's allowlist:** text on the navy band (footer social links,
    featured-course eyebrow: 5.47:1 there, where STRONG fails at 4.12), the voucher's 34px £ amount (large
    text), and borders.
  - **Evidence** (`audits/reports/run3/`): all 14 `/dev/mail` previews sent through the **log** mailer on
    rolled-back data (only the 2 allowlisted on-navy links keep the old blue);
    `023-booking-confirmation.eml`; cropped receipt and voucher PDF renders.
- **Gap report:**
  - **Did, not in the prompt:** `BuilderTest` pinned the old button hex. It now asserts `BrandHex::STRONG`; the
    assertion's purpose (an inline-styled button) is unchanged.
  - The prompt implied every use moves. Three text uses sit on navy, where the darker blue would *fail*, so
    they stay bright (same rule as the site's dark surfaces).
  - "One definition per medium" became one PHP class for both, since neither medium can read CSS variables.
  - The voucher's `#0ea5e9` (on navy only, 6.85:1) is untouched.
- **Markers:** none.

## 8 · 024 scrollable table focus
- **Branch:** `a11y/scrollable-table-focus` → merged `32253e1`
- **Tests:** 516 → 520; `composer check` green.
- **What it did:** new `<x-ui.table-scroll label>` (focusable, `role="region"`, `aria-label`, standard focus ring
  on keyboard focus only), used for the account payments table, the only table in a scroll wrapper.
  - **axe at 390:** 1 `scrollable-region-focusable` → **0**.
  - Tab reaches it (`:focus-visible` ring paints), and the arrow keys scroll it its full 41px.
  - **At rest:** full-page payments, before vs after, **0 px** at 390 and at 1440.
  - Structural guard against bare scroll wrappers round a table, proven with a planted one.
  - `ui-guidelines.md` lists the component.
- **Gap report:** the grep found nothing else to route through the component. The admin calendar's scroller is a
  grid in Filament, not a table. The first arrow-key reading was 0 because it was read before the smooth scroll
  settled; re-measured after 800ms → 41px.
- **Screenshots:** `ui-review/scrollable-table-focus/` (cropped JPEGs). Browsed signed in via the local dev
  login, read-only.
- **Markers:** none.

## 9 · Pre-staging gate (report only)
- **Branch:** `docs/pre-staging-gate-run3` → merged `3d8d555` (only `PRE-STAGING-CHECKLIST.md` changed)
- **Tests:** 520 → 520. `composer check` + MySQL 520/520 green at `3b824b4`; `config:cache` / `route:cache` /
  `composer validate --strict` OK; composer and npm audits 0; axe 0 nodes on 20 public pages × 1440/390.
- **Verdict: ✅ GO for staging**, on two server-side conditions:
  - basic-auth exempting `/webhooks/stripe` and `/webhooks/resend`;
  - `APP_ENV=staging` (the app's noindex depends on it) and the site email → a test inbox.

  Run 2's "noindex at the server" condition is now met in code (022).
- **Gap report:** none. The only unmerged branch is the withdrawn 017.
- **Markers:** none.

## 10 · Housekeeping
- **017 withdrawn:** `ui/feature-split-ratio` (`0a37a7b`) deleted locally and on origin. It's listed under
  *Withdrawn* in `RUNNING-ORDER.md` with Ben's reason (the 1024 imbalance is worse than the side-trim) and the sha.
- **Remote branches:** the 9 branches fully merged into `origin/main` were deleted (each checked with
  `git merge-base --is-ancestor` first): `docs/run3-owner-answers`, `ui/primary-strong-contrast`,
  `fix/fk-delete-rules`, `ui/button-hover-contrast`, `fix/reschedule-capacity`, `fix/noindex-non-production`,
  `fix/email-link-colour`, `a11y/scrollable-table-focus`, `docs/pre-staging-gate-run3`. **`origin` now holds only
  `main`.**
- **Dev database:**
  - at the start, `php artisan migrate` applied run 2's two pending migrations, then `settings:clear-cache`
    ran;
  - after 008 merged, its FK migration was applied too (rules only; the pre-check found no orphans on a clone
    first).

  `migrate:status` shows nothing pending. No records were added or changed; the "Audit …" records are as
  they were. Browser checks used the local dev login read-only, and the evidence scripts ran inside
  rolled-back transactions.

---

## Outstanding fixes (Ben: "also fix anything outstanding")

The brief's table was finished at item 10. Ben's message added "also fix anything outstanding". I read that
as: the follow-ups earlier runs proposed but never prompted, where the fix is clear and needs no owner
decision. Each was written as a numbered prompt file first, as the workflow requires, and run like the items
above. Everything else outstanding is listed at the end with why it wasn't built.

## 11 · 025 admin tidy-up (the admin audit's A-4 batch)
- **Branch:** `chore/admin-tidy-up` → merged `dd9ad53`
- **Tests:** 520 → 530. `composer check` green; **MySQL 530/530**.
- **What it did:**
  - **Past choices block Save — a real defect the audit had down as "raw IDs":** a booking whose slot has
    passed, or a news article linked to a past course, couldn't be saved ("The selected jump slot is
    invalid"). `AdminOptions::bookablePlusCurrent()` keeps the current value in the options; there's a
    structural guard.
  - **Uploads accepted SVG on the public disk — a security fix:** `ImageCrop` called Filament's `->image()`
    (= `image/*`, SVG included). An SVG with `<script>` saved in a test. The new `AdminImages::upload()` factory
    (JPEG/PNG/WebP, 12 MB) is on all 18 image fields, with a guard against any other `FileUpload::make(` or a
    stray `->image()`.
  - The enquiry inbox's unread-first ordering is now the *default* sort, so column sorts work.
  - Four nav sort ties were renumbered; the rendered order is unchanged and pinned by a test.
  - The public CSS no longer compiles the admin views (3 selectors gone, homepage 0 px at 1440/390).
  - `motion` was uninstalled.
  - The Help guide states the upload types and limit.
- **Gap report:** the "unused slug fields" item is ruled out: Product's slug is the price-token key since 014;
  Location's is a CMS-gate question.
- **Markers:** **OVERNIGHT-DEFAULT — CONFIRM**: image types JPEG/PNG/WebP (no GIF/HEIC/SVG) and the 12 MB limit.

## 12 · 026 one-click List-Unsubscribe (email audit E-1)
- **Branch:** `feat/list-unsubscribe` → merged `78ba261`
- **Tests:** 530 → 535; `composer check` green. (No MySQL: no queries changed.)
- **What it did:** newsletters carry `List-Unsubscribe: <signed URL>` and `List-Unsubscribe-Post:
  List-Unsubscribe=One-Click`, the same signed URL as the footer. A signed, CSRF-exempt POST route unsubscribes
  through the same service method; it's idempotent, and tampered requests get 403. Headers are asserted on the
  real MIME message. Confirmation and transactional mail don't carry the header.
- **Gap report:** none against the prompt. The Resend transport passes the headers through (code read).
  Seeing Gmail's "Unsubscribe" button needs a real send on staging; it's in the post-deploy list.
- **Markers:** **OWNER DECISION — PENDING**: the footer link unsubscribes on GET, which link-scanning mail
  security can trigger. Keep it, or add a confirm button that POSTs? Not built either way.

## 13 · 027 owner password reset (admin audit A-3)
- **Branch:** `feat/admin-password-reset` → merged `0c3e574`
- **Tests:** 535 → 540. `composer check` green; **MySQL 540/540** (reset tokens table).
- **What it did:** the admin login has **Forgot password?**: a queued, signed, 60-minute reset link in the brand mail
  theme.
  - **No account enumeration:** Filament's own page says "We can't find a user" for an unknown address. A
    subclass shows the same "sent" notice for unknown and customer addresses. This is proven red with
    Filament's page class.
  - End to end in a test: request → token → new password → login.
  - Added to `/dev/mail`, the email audit inventory, the launch checklist §2 and the Help guide.
- **Gap report:**
  - `NoSeededAdminOnServersTest` flagged the `/dev/mail` preview's `User::factory()`. It now uses an unsaved
    `User::make()`; the guard is unchanged.
  - The email keeps Laravel's staff-facing wording ("Hello!", "Regards, G-Force Skydiving"). It isn't a customer
    email, so the `<x-mail.layout>` greeting rules don't apply.
- **Markers:** none.

## Gate re-run on the final `main`
The item-9 gate ran at `3b824b4`, before 025–027. It was re-run on the final `main` (`4c0d6c7`) and the report
updated (`PRE-STAGING-CHECKLIST.md`, merged `3a954c1`):
- `composer check` 540/540; MySQL 540/540;
- composer and npm audits 0 (after `motion` was removed);
- build, `config:cache` / `route:cache` and `composer validate --strict` OK;
- 0 dev routes outside `local`;
- axe 0 nodes on 20 public pages × 1440/390;
- no unmerged branches.

**Still ✅ GO for staging**, same two conditions. The 4 merged branches from 025–027 and the re-run were then
deleted from origin, which again holds only `main`.

## Outstanding, deliberately not built (each needs Ben, a design call or its own prompt)
- **A-1 share image as an upload:** changes the `og:image` consumer on every page; worth its own prompt and check.
- **A-2 testimonial crop:** two display ratios (16:9 featured, 4:5 tile), so it's a design choice.
- **Consistency items that change how things look:**
  - C-1 (Coached intro: are the price eyebrow and its own CTA intended?);
  - C-2 (AFF trust-band heading through the component);
  - C-5 (meta labels at 0.2em vs 0.25em);
  - C-6 (three check-list styles; should the AFF pay-card get Tandem's three bullets?);
  - C-9 (account vs public panel headings);
  - C-10 (newsletter panel framed two ways);
  - C-11 (a back-arrow link).

  The design rules need a brief or Ben's say for visual changes.
- **C-15 shop prices as money:** the shop is switched off with no items, and it's a schema change.
- **Editable email sign-off:** Ben said "later" (run 1).
- **CSP enforcement:** by design it stays report-only until the console is checked on production.
- **The Location slug field** (unused): a CMS-field-gate question.

---

## How the run ended
- **Every item in the brief done (1–10), with no stop condition hit**, then 3 outstanding-fix prompts (025–027)
  under Ben's "also fix anything outstanding", and the gate re-run on the final `main`.
  - 14 merges on green: items 1–9 (8 code/docs + the gate report), 025, 026, 027 and the gate re-run.
  - 017 withdrawn and deleted.
- Tests went from **476 to 540** on `main`. `composer check` was green at every merge, plus the MySQL suite
  wherever queries, migrations, FKs or auth changed (008, 021, 025, 027).
- **`main` is at `3a954c1`** before this closing docs commit; it's clean and green. `origin` holds only `main`.
- **Pre-staging verdict: ✅ GO for staging.**

## What Ben has to look at
1. **Staging, two conditions** (`SETUP.md` "Staging", new):
   - basic-auth that exempts `/webhooks/stripe` and `/webhooks/resend`;
   - **`APP_ENV=staging`**, because the app's noindex depends on it, and the site email set to a test inbox.
2. **The site's blue is now the darker `primary-strong` on text and buttons** (016), and buttons **darken** on
   hover (020). Screenshots: `ui-review/primary-strong/` and `ui-review/button-hover-contrast/`.
3. **Two fixes the owner will notice:**
   - old bookings and news posts with a past slot or course can be saved again (they couldn't);
   - image uploads now refuse SVG and GIF (025).
4. **Rescheduling into a full tandem date is refused**, and full dates show "— full" in the list (021).
5. **The FK migration on any server** (008) aborts with a list if it finds orphaned rows. It found none on a
   copy of the dev DB. If it ever stops a deploy, the data needs fixing first.
6. **The admin login has "Forgot password?"** (027), and newsletters get Gmail's own "Unsubscribe" button (026).
   Check both arrive on staging.

## Owner questions (each answerable in one line)
1. **025:** image uploads limited to JPEG/PNG/WebP up to 12 MB (no GIF/HEIC/SVG): OK?
2. **026:** the newsletter footer link unsubscribes on a plain click (GET), which some corporate link-scanners can
   trigger. Keep it, or add a "Confirm unsubscribe" button?
3. **C-1:** is the Coached intro's price eyebrow ("From £60 per session") and its own "Book a session" button
   intended? Or should Tandem/AFF match, or Coached lose them?
4. **C-6:** should the AFF pay-card show the same three reassurance bullets as Tandem's?
5. **C-9/C-10/C-5/C-11:** unify the small visual drifts (panel headings navy, one newsletter-panel frame, 0.25em
   meta labels, a back-arrow link)? Yes/no for the batch.
6. **A-2:** testimonial photos: one crop ratio for both places, or a second crop field?
7. **A-1:** make the social sharing image an upload (its own prompt)?

**Ops / owner tasks (carried over, not questions):**
- on any already-seeded server, delete `test@example.com` and unapprove the 8 sample testimonials;
- after staging, check the logo and the new button blue with images blocked in Gmail and Outlook;
- enter the dropzone addresses and coordinates;
- swap the typed prices in existing CMS text for tokens (DECISIONS, 014); the weight surcharges stay typed (both
  places).
