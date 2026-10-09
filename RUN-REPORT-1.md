# Run report — unattended run 1 (RUNNING-ORDER steps 1–7)

Started 9 Oct 2026 from `main` = `be9e145` (= `origin/main`). Brief: `prompts/unattended-run-1.md`.
Before branching, Step 0 of 001 stopped three times, and Ben resolved each one: he had me drop a June
untracked-only stash (`stash@{0}` on `ui/logo-svg`, recoverable as `56c8c02` until gc), delete
`.playwright-mcp/` (June browser-tool logs), and run this brief (`prompts/unattended-run-1.md`, which
wasn't on 001's expected-files list).

## 1 — 001 kit sync · `chore/kit-sync-2026-09` · merge `086d593`
- Tests 369 → 369 (`composer check` green before and after; docs only).
- Copied the September kit's prompts/, false-green.md, gates/, email audit and verification/ verbatim
  (all 20 `cmp`-identical), refreshed 8 drifted audit/ui-pass copies (kit-only additions), added 6
  architecture + 4 testing rules and a Workflow section to CLAUDE.md, and added the FPM reload and the
  never-`key:generate` rules to the SETUP.md deploy sequence.
- Gap report: in DECISIONS ("Kit sync to the September 2026 starter kit"). Short version: the Step 0
  stops above; `unattended-run-1.md` committed too; 001 misdescribes `bootstrap/app.php:25` (that's
  `SecurityHeaders`, not a session reader), though the rule holds.
- `OVERNIGHT-DEFAULT — CONFIRM`: motion ambition recorded as **subtle** (as built).
- Owner/Ben notes: the global `frontend-design` skill lags the kit; `admin-design`, `laravel-craft`,
  `web-app-security` aren't installed globally. The `motion` npm package is unused.
- Visual: none (docs only).

## 2 — 002 structural guards · `test/structural-guards` · merge `092536d`
- Tests 369 → 378. `composer check` green; `phpunit.mysql.xml` 378/378 green.
- Six guards (9 tests), each green on real code and **proven red by a planted violation** (one-line
  reds in DECISIONS, "Structural guards…"). No allowlists. No guard went red on real code.
- Findings while building: Alpine reaches every page **two ways** (layout `@livewireScripts` +
  Livewire 4 auto-injection via the footer island). Two in-process traps in the test client
  (Livewire's rendered-scripts flag; the shared session store/guard) were neutralised and recorded.
  The naive session test is a demonstrated false green.
- Gap report: guard 4 widened to the components Livewire views render; CLAUDE.md gained rules
  13 (reserved names) and 14 (Alpine scope) so those guards have a rule to sit beside.
- `OWNER DECISION — PENDING` / `OVERNIGHT-DEFAULT — CONFIRM`: none added.
- Visual: none (tests only).

## 3 — email audit · `email/audit-pass` · merge `d3ae38d`
- Tests 378 → 399. `composer check` green; `phpunit.mysql.xml` 399/399 green.
- Report `audits/reports/email-audit.md`: inventory first, committed before fixes. Verified by doing:
  the log mailer, the real Redis queue and worker, and real controls pressed in the browser.
- **Fixed (each failing-first):**
  1. Customer balance payments sent **no receipt and no owner notification**, though the success page
     promised one.
  2. Newsletter "Send test to me" **failed in the worker every time** after saying "Test sent".
  3. Zero retries on all mail; added the `QueuedMailable` base (4 tries with backoff, after commit,
     `failed()` log).
  4. The two voucher-email paths now share one action.
  5. The reschedule notice no longer claims an email that wasn't queued.
  6. Dashboard mail-health widget plus a help-guide section.
  7. `gforce:mail-test` command.
  8. `MailInventoryTest` guard (4 rules, each proven red).
- Gap report in DECISIONS ("Email audit"): no status page, so the health check is on the dashboard;
  E-1/E-2 deferred; one false-green test re-pointed; audit test records left in the local dev DB.
- `OWNER DECISION — PENDING`: should admin-created Confirmed bookings and admin voucher redemptions
  email the customer? (Recommended: an "Email the customer" toggle, default on.)
- `OVERNIGHT-DEFAULT — CONFIRM`: none.
- Visual: the admin dashboard gained the mail-health widget — `audits/reports/email-audit-dashboard.png`.
  No public page changed, and the homepage wasn't touched.
- Proposed prompts:
  - **E-1** List-Unsubscribe header.
  - **E-2** CID-embedded mail logo.
  - **E-3** the payment-success page after a gift-voucher purchase.
  - **E-4** the owner decision.

## 4 — admin audit · `admin/audit-pass` · merge `6f9888f`
- Tests 399 → 425. `composer check` green; `phpunit.mysql.xml` 425/425 green.
- **All seven settings pages work by doing** (load, save, notify, persist), so the kit's new
  singleton check found nothing.
- **P1, verified by doing:**
  - Deleting a product cascade-deleted its course dates.
  - Deleting a booking orphaned its paid payment.
  - Course and voucher deletes were unguarded.
  - **A redeemed voucher could be saved back to Active and spent again.**
  - Fixed with one shared guard (`GuardsDeletion` + `AdminActions::guardedDelete()`, server-refused,
    tooltip says why) and a locked voucher form.
- **P2 fixed:**
  - Booking "Awaiting payment" is webhook-only.
  - The three page disciplines are locked.
  - A published news article needs a date.
  - A sent newsletter is read-only. A disabled form alone still saved; the test caught it, and
    `beforeSave()` now halts.
  - The AFF deposit is required.
  - Capacity has a floor at current bookings.
- **P3:** stock AccountWidget removed; panel primary Blue (white text measured 5.26:1; old amber was
  3.19:1).
- Gap report in DECISIONS ("Admin audit"): the guard pattern extended to Tandem dates, Locations and
  Disciplines; the FK cascade was left for its own prompt; local audit data noted.
- `OVERNIGHT-DEFAULT — CONFIRM`: admin primary colour = Blue.
- Owner questions:
  - Should the email sign-off be editable?
  - Is the admin blue OK?
  - Testimonial photo: one crop or two?
- Visual (admin only; the homepage wasn't touched; public pages 200×2):
  `audits/reports/admin-audit-dashboard-after.png`, `admin-audit-product-edit-after.png`.
- Proposed prompts:
  - **A-1** share-image upload.
  - **A-2** testimonial crop.
  - **A-3** owner password reset.
  - **A-4** admin polish batch (incl. the product→course-date FK cascade).

## 5 — accessibility audit · `a11y/audit-pass` · merge `d5863c1`
- Tests 425 → 432. `composer check` green; `phpunit.mysql.xml` 432/432 green.
- axe-core 4.10.2 on 21 public pages at 1440/390 plus 6 signed-in account pages, with keyboard,
  forms and toast checks by hand.
  - **Before:** brand-primary contrast, heading-order ×4, `/testimonials` with no `<h1>`.
  - **After:** brand-primary contrast only (178 nodes, all `#008fe6`; 0 other pairs).
- **Fixed:**
  - Toasts were unreadable (white on green-600, 3.13:1), off-palette and never announced.
  - Heading structure, now guarded across every page.
  - Booking-field errors tied to their controls (`@aware`).
  - Skip-to-content link.
- **`OWNER DECISION — PENDING`:** brand primary `#008fe6` (3.4–3.5:1) and `destructive` (4.3–4.45:1)
  fail AA. Nearest passing: primary `oklch(0.545 0.18 240)`/`#0078cc`, destructive
  `oklch(0.58 0.24 27)`. Options: A darken the tokens, B split into a `primary-strong` token for
  text/fills, C accept. Not applied.
- Homepage: before/after at 1440 and 390, **0 differing pixels** (`audits/reports/a11y/`).
- Note: the committed evidence screenshots total 8.9 MB (full-page PNGs). Future runs should crop
  them or keep them out of git.

## 6 — 003 itemised consistency audit · `docs/consistency-audit` · merge `5ebe29f`
- Report-only. Tests 432 → 432; `composer check` green.
- `ui-review/CONSISTENCY.md`: source enumeration, then every rendered instance of 13 element types on
  21 pages at 1440 and 390, plus the account pages, with element crops (`ui-review/consistency-audit/`,
  ~0.5 MB JPEGs).
- **15 odd ones out.** My view on what's worth fixing **before launch**:
  - **C-7** prices typed into CMS copy will contradict the product price the first time the owner
    changes one;
  - **C-8** feature-split images ignore the admin crop on desktop;
  - **C-4** testimonial role labels;
  - **C-3** /news date and label tracking;
  - **C-12** "Sending…".

  These are small and visible. The rest can wait (C-1/C-2 consolidation, C-6 check-lists, C-9/C-10
  panels, C-11 back link, C-13 tokens, C-15 shop prices, C-5 meta tracking).
- Owner question from C-1: is Coached's price-as-eyebrow plus its own intro CTA intended?
- Gap report: none. No view, component, CSS, test or config touched.
- Visual: none changed. The homepage is the reference and isn't touched.

## 7 — gates · `docs/completeness-check` (merge `3ba3657`) + `docs/cms-field-usage` (merge `370832f`)
- Report-only. Tests 432 → 432; `composer check` green.
- **Completeness** (`audits/reports/completeness-check.md`):
  - **The admin FAQ list returns 500 whenever any FAQ exists.** `Faq::scopeForPage` shadows the query
    builder's `forPage()` paginator, and `FaqAdminTest` lists an empty table, so it's a false green. This
    is the only scope collision across all models.
  - **Sample testimonials feed a published `AggregateRating` (4.8 from 8).**
  - Three June items are resolved (the Instagram note, `/aff#enquiry`, social URLs).
- **CMS field usage** (`audits/reports/cms-field-usage.md`):
  - 10 orphan fields: `team_lead`, `Product::duration`, Location address/coordinates (7),
    `Location::image`.
  - Reverse drift: the homepage `<title>`/description are literals, so the "Default page title" setting
    never reaches the homepage.
  - The email/meta/logic-only fields are listed so nobody deletes them.
- Both gates write to `audits/reports/` rather than the kit's root filenames (repo convention); noted in
  each report.

---

## Run end

**Why the run ended:** finished. All seven in-scope items (RUNNING-ORDER steps 1–7) were built, gated
and merged in order. No stop condition was hit. Nothing is left on a branch.

**`main` = `370832f`** (on GitHub). Tests 369 → 432; `composer check` green; the MySQL suite was green
at every merge that touched code.

**Not done, by design:** step 8 onwards (the fix prompts). RUNNING-ORDER step 8 now names the FAQ 500 as
the urgent first prompt, with its reason.

**Owner questions waiting for Ben** (each answerable in one line):
1. Should **admin-created Confirmed bookings** and **admin voucher redemptions** email the customer?
   (Recommended: an "Email the customer" toggle, default on.)
2. **Brand-colour contrast:** primary `#008fe6` and `destructive` fail AA. A darken
   (`#0078cc`), B add a darker `primary-strong` token for text/fills, or C accept?
3. **Admin panel primary = Blue** (`OVERNIGHT-DEFAULT — CONFIRM`): OK?
4. **Motion ambition recorded as "subtle"** (`OVERNIGHT-DEFAULT — CONFIRM`): OK?
5. Should the **email sign-off** be editable in the admin?
6. **Testimonial photos:** one crop ratio for both places, or two crops?
7. **Coached page:** is the price-as-eyebrow plus its own intro CTA intended?
8. **Sample testimonials:** replace or unapprove before launch? (They feed a published review rating.)
9. **`Product::duration` and Location addresses:** re-surface them on the site, or remove them from the
   admin?
10. **Your global skills:** sync `frontend-design` and install `admin-design`/`laravel-craft`/
    `web-app-security` from the kit? (These affect your other projects.)

**Housekeeping notes:**
- The local dev DB holds audit test records, all named "Audit …" (an enquiry, 2 bookings, payments,
  a subscriber, a redeemed voucher, a failed job).
- `audits/reports/a11y/` committed 8.9 MB of full-page PNGs; worth slimming.
