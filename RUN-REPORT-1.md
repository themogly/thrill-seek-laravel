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
