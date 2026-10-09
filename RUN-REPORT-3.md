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
